<?php
/**
 * NEXU HOSTING - Controlador de Autenticación
 * Login, registro, OAuth Google/Discord, recuperación de contraseña.
 */

class AuthController
{
    private UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    // ── Login local ───────────────────────────────────────────

    public function login(): void
    {
        validateCsrfOrFail();

        $email    = strtolower(sanitize($_POST['email']    ?? ''));
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            setFlash('Por favor completa todos los campos.', 'warning');
            redirect('/login.php');
        }

        $user = $this->users->findByEmail($email);

        if (!$user) {
            setFlash('Credenciales incorrectas.', 'danger');
            AuditModel::log('user.login_fail', 'users', null, null, ['email' => $email, 'reason' => 'not_found']);
            redirect('/login.php');
        }

        // Cuenta suspendida
        if ($user['status'] === 'suspended') {
            setFlash('Tu cuenta ha sido suspendida. Contacta soporte.', 'danger');
            redirect('/login.php');
        }

        // Cuenta bloqueada por intentos fallidos
        if ($this->users->isLocked($user)) {
            $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
            setFlash("Cuenta bloqueada por intentos fallidos. Intenta en {$remaining} minuto(s).", 'danger');
            redirect('/login.php');
        }

        // Verificar contraseña
        if (!password_verify($password, $user['password'])) {
            $this->users->incrementLoginAttempts($user['id']);

            $attempts = $user['login_attempts'] + 1;
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $this->users->lockAccount($user['id'], LOCKOUT_MINUTES);
                setFlash("Demasiados intentos fallidos. Cuenta bloqueada por " . LOCKOUT_MINUTES . " minutos.", 'danger');
            } else {
                $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
                setFlash("Contraseña incorrecta. Te quedan {$remaining} intento(s).", 'danger');
            }

            AuditModel::log('user.login_fail', 'users', $user['id'], null, ['reason' => 'wrong_password']);
            redirect('/login.php');
        }

        // Re-hashear si usó algoritmo antiguo
        if (password_needs_rehash($user['password'], PASSWORD_ARGON2ID)) {
            $this->users->updatePassword($user['id'], password_hash($password, PASSWORD_ARGON2ID));
        }

        $this->users->resetLoginAttempts($user['id']);
        loginUser($user);
        AuditModel::log('user.login', 'users', $user['id']);

        setFlash('¡Bienvenido de nuevo, ' . e($user['username']) . '!', 'success');

        $next = $_SESSION['login_redirect'] ?? null;
        unset($_SESSION['login_redirect']);
        redirect($next ?? ($user['role'] === 'admin' ? '/admin/dashboard.php' : '/dashboard.php'));
    }

    // ── Registro local ────────────────────────────────────────

    public function register(): void
    {
        validateCsrfOrFail();

        $username  = sanitize($_POST['username']  ?? '');
        $email     = strtolower(sanitize($_POST['email']    ?? ''));
        $fullName  = sanitize($_POST['full_name'] ?? '');
        $password  = $_POST['password']  ?? '';
        $password2 = $_POST['password2'] ?? '';

        // Validaciones
        $errors = [];
        if (strlen($username) < 3 || strlen($username) > 50)    $errors[] = 'El usuario debe tener entre 3 y 50 caracteres.';
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username))       $errors[] = 'El usuario solo puede contener letras, números, puntos y guiones.';
        if (!isValidEmail($email))                                $errors[] = 'Correo electrónico inválido.';
        if (strlen($password) < PASSWORD_MIN_LEN)                 $errors[] = 'La contraseña debe tener al menos ' . PASSWORD_MIN_LEN . ' caracteres.';
        if ($password !== $password2)                             $errors[] = 'Las contraseñas no coinciden.';

        if (!empty($errors)) {
            setFlash(implode(' ', $errors), 'danger');
            redirect('/register.php');
        }

        // Verificar duplicados
        if ($this->users->findByEmail($email)) {
            setFlash('Este correo ya está registrado.', 'danger');
            redirect('/register.php');
        }
        if ($this->users->findByUsername($username)) {
            setFlash('Este nombre de usuario ya está en uso.', 'danger');
            redirect('/register.php');
        }

        $userId = $this->users->create([
            'username'          => $username,
            'email'             => $email,
            'password'          => password_hash($password, PASSWORD_ARGON2ID),
            'full_name'         => $fullName ?: null,
            'oauth_provider'    => 'local',
            'email_verified'    => 0,
            'preferred_currency'=> 'PEN',
        ]);

        AuditModel::log('user.register', 'users', $userId);
        setFlash('¡Cuenta creada exitosamente! Ya puedes iniciar sesión.', 'success');
        redirect('/login.php');
    }

    // ── Logout ────────────────────────────────────────────────

    public function logout(): void
    {
        if (isLoggedIn()) {
            AuditModel::log('user.logout', 'users', $_SESSION['user_id']);
        }
        logoutUser();
        setFlash('Sesión cerrada correctamente.', 'info');
        redirect('/login.php');
    }

    // ── OAuth Google ──────────────────────────────────────────

    public function googleRedirect(): void
    {
        if (!GOOGLE_CLIENT_ID) {
            setFlash('Login con Google no configurado.', 'warning');
            redirect('/login.php');
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $params = http_build_query([
            'client_id'     => GOOGLE_CLIENT_ID,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
        ]);

        redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    }

    public function googleCallback(): void
    {
        $state = $_GET['state'] ?? '';
        if (!hash_equals($_SESSION['oauth_state'] ?? '', $state)) {
            setFlash('Estado OAuth inválido.', 'danger');
            redirect('/login.php');
        }
        unset($_SESSION['oauth_state']);

        $code = $_GET['code'] ?? '';
        if (!$code) {
            setFlash('Error de autenticación con Google.', 'danger');
            redirect('/login.php');
        }

        // Obtener token de acceso
        $tokenResp = $this->httpPost('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ]);

        if (empty($tokenResp['access_token'])) {
            setFlash('No se pudo obtener el token de Google.', 'danger');
            redirect('/login.php');
        }

        // Obtener datos del usuario
        $profile = $this->httpGet(
            'https://www.googleapis.com/oauth2/v3/userinfo',
            $tokenResp['access_token']
        );

        if (empty($profile['sub'])) {
            setFlash('No se pudo obtener el perfil de Google.', 'danger');
            redirect('/login.php');
        }

        $this->loginOrCreateOAuth('google', $profile['sub'], [
            'email'     => $profile['email'] ?? '',
            'full_name' => $profile['name']  ?? '',
            'avatar'    => $profile['picture'] ?? null,
        ]);
    }

    // ── OAuth Discord ─────────────────────────────────────────

    public function discordRedirect(): void
    {
        if (!DISCORD_CLIENT_ID) {
            setFlash('Login con Discord no configurado.', 'warning');
            redirect('/login.php');
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $params = http_build_query([
            'client_id'     => DISCORD_CLIENT_ID,
            'redirect_uri'  => DISCORD_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => 'identify email',
            'state'         => $state,
        ]);

        redirect('https://discord.com/api/oauth2/authorize?' . $params);
    }

    public function discordCallback(): void
    {
        $state = $_GET['state'] ?? '';
        if (!hash_equals($_SESSION['oauth_state'] ?? '', $state)) {
            setFlash('Estado OAuth inválido.', 'danger');
            redirect('/login.php');
        }
        unset($_SESSION['oauth_state']);

        $code = $_GET['code'] ?? '';
        if (!$code) {
            setFlash('Error de autenticación con Discord.', 'danger');
            redirect('/login.php');
        }

        $tokenResp = $this->httpPost('https://discord.com/api/oauth2/token', [
            'client_id'     => DISCORD_CLIENT_ID,
            'client_secret' => DISCORD_CLIENT_SECRET,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => DISCORD_REDIRECT_URI,
        ]);

        if (empty($tokenResp['access_token'])) {
            setFlash('No se pudo obtener el token de Discord.', 'danger');
            redirect('/login.php');
        }

        $profile = $this->httpGet(
            'https://discord.com/api/users/@me',
            $tokenResp['access_token']
        );

        if (empty($profile['id'])) {
            setFlash('No se pudo obtener el perfil de Discord.', 'danger');
            redirect('/login.php');
        }

        $avatar = null;
        if (!empty($profile['avatar'])) {
            $avatar = "https://cdn.discordapp.com/avatars/{$profile['id']}/{$profile['avatar']}.png";
        }

        $this->loginOrCreateOAuth('discord', $profile['id'], [
            'email'      => $profile['email'] ?? '',
            'full_name'  => $profile['global_name'] ?? $profile['username'] ?? '',
            'username'   => $profile['username'] ?? '',
            'avatar'     => $avatar,
            'discord_tag'=> ($profile['username'] ?? '') . '#' . ($profile['discriminator'] ?? '0'),
        ]);
    }

    // ── Recuperar contraseña ──────────────────────────────────

    public function sendReset(): void
    {
        validateCsrfOrFail();

        $email = strtolower(sanitize($_POST['email'] ?? ''));

        if (!isValidEmail($email)) {
            setFlash('Ingresa un correo válido.', 'warning');
            redirect('/recuperar.php');
        }

        // SIEMPRE mostrar el mismo mensaje (evitar user enumeration)
        $user = $this->users->findByEmail($email);

        if ($user) {
            $token = $this->users->createPasswordReset($user['id']);
            $link  = APP_URL . '/recuperar.php?token=' . urlencode($token);

            // En producción enviar por email. Aquí lo logueamos para dev.
            error_log("[NEXU RESET] Token para {$email}: $link");

            AuditModel::log('user.password_reset_request', 'users', $user['id']);
        }

        setFlash('Si ese correo está registrado, recibirás un enlace de recuperación en breve.', 'info');
        redirect('/recuperar.php');
    }

    public function resetPassword(): void
    {
        validateCsrfOrFail();

        $token    = sanitize($_POST['token']     ?? '');
        $password = $_POST['password']  ?? '';
        $confirm  = $_POST['password2'] ?? '';

        $reset = $this->users->findValidResetToken($token);

        if (!$reset) {
            setFlash('Enlace inválido o expirado.', 'danger');
            redirect('/recuperar.php');
        }

        if (strlen($password) < PASSWORD_MIN_LEN) {
            setFlash('La contraseña debe tener al menos ' . PASSWORD_MIN_LEN . ' caracteres.', 'danger');
            redirect('/recuperar.php?token=' . urlencode($token));
        }

        if ($password !== $confirm) {
            setFlash('Las contraseñas no coinciden.', 'danger');
            redirect('/recuperar.php?token=' . urlencode($token));
        }

        $this->users->updatePassword($reset['user_id'], password_hash($password, PASSWORD_ARGON2ID));
        $this->users->markResetTokenUsed($token);
        AuditModel::log('user.password_changed', 'users', $reset['user_id']);

        setFlash('Contraseña actualizada. Ahora puedes iniciar sesión.', 'success');
        redirect('/login.php');
    }

    // ── Cambio de moneda ──────────────────────────────────────

    public function switchCurrency(): void
    {
        $currency = sanitize($_POST['currency'] ?? $_GET['currency'] ?? '');
        setCurrency($currency);
        redirectBack('/');
    }

    // ── Helpers OAuth privados ────────────────────────────────

    private function loginOrCreateOAuth(string $provider, string $oauthId, array $profile): void
    {
        // Buscar por OAuth ID
        $user = $this->users->findByOAuth($provider, $oauthId);

        // Si no existe y tenemos email, buscar por email
        if (!$user && !empty($profile['email'])) {
            $user = $this->users->findByEmail($profile['email']);

            if ($user) {
                // Vincular OAuth a cuenta existente
                $col  = $provider === 'google' ? 'google_id' : 'discord_id';
                db()->prepare("UPDATE users SET $col = ?, oauth_provider = ?, avatar_url = COALESCE(avatar_url, ?) WHERE id = ?")
                    ->execute([$oauthId, $provider, $profile['avatar'] ?? null, $user['id']]);
            }
        }

        // Si definitivamente no existe, crear cuenta
        if (!$user) {
            $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $profile['username'] ?? explode('@', $profile['email'] ?? 'user')[0]));
            $username     = $baseUsername ?: 'user' . random_int(1000, 9999);

            // Asegurar unicidad del username
            $i = 1;
            while ($this->users->findByUsername($username)) {
                $username = $baseUsername . $i++;
            }

            $userId = $this->users->create([
                'username'          => $username,
                'email'             => $profile['email'] ?? $oauthId . '@' . $provider . '.oauth',
                'password'          => password_hash(bin2hex(random_bytes(16)), PASSWORD_ARGON2ID),
                'full_name'         => $profile['full_name'] ?? null,
                'avatar_url'        => $profile['avatar'] ?? null,
                $provider . '_id'   => $oauthId,
                'oauth_provider'    => $provider,
                'email_verified'    => 1, // Confiamos en OAuth provider
                'preferred_currency'=> 'PEN',
            ]);

            $user = $this->users->findById($userId);
            AuditModel::log('user.register_oauth', 'users', $userId, null, ['provider' => $provider]);
        }

        if ($user['status'] !== 'active') {
            setFlash('Tu cuenta está suspendida. Contacta soporte.', 'danger');
            redirect('/login.php');
        }

        loginUser($user);
        AuditModel::log('user.login_oauth', 'users', $user['id'], null, ['provider' => $provider]);
        setFlash('¡Bienvenido, ' . e($user['username']) . '!', 'success');
        redirect($user['role'] === 'admin' ? '/admin/dashboard.php' : '/dashboard.php');
    }

    private function httpPost(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp ?: '{}', true) ?: [];
    }

    private function httpGet(string $url, string $bearerToken): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer $bearerToken"],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp ?: '{}', true) ?: [];
    }
}
