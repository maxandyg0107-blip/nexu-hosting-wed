<?php
/**
 * controllers/AuthController.php
 * ---------------------------------------------------------------------
 * Controlador de autenticación: registro, login (con protección contra
 * fuerza bruta) y logout seguro.
 * ---------------------------------------------------------------------
 */

if (!defined('NEXU_APP')) {
    http_response_code(403);
    die('Acceso directo no permitido.');
}

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Procesa el formulario de registro. Devuelve un array
     * ['success' => bool, 'errors' => array] para que la vista lo renderice.
     */
    public function register(array $post): array
    {
        csrf_verify_or_die();

        $errors = [];

        $username = clean_username($post['username'] ?? '');
        $email    = clean_email($post['email'] ?? '');
        $password = (string) ($post['password'] ?? '');
        $confirm  = (string) ($post['password_confirm'] ?? '');

        if (!$username) {
            $errors[] = 'El nombre de usuario debe tener entre 3 y 50 caracteres (letras, números, - y _).';
        }
        if (!$email) {
            $errors[] = 'El correo electrónico no tiene un formato válido.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Las contraseñas no coinciden.';
        }

        if ($username && $this->userModel->findByUsername($username)) {
            $errors[] = 'Ese nombre de usuario ya está en uso.';
        }
        if ($email && $this->userModel->findByEmail($email)) {
            $errors[] = 'Ese correo electrónico ya está registrado.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $hash = password_hash($password, HASH_ALGO);
            $userId = $this->userModel->create($username, $email, $hash);

            $_SESSION['user_id']  = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['role']     = 'client';

            return ['success' => true, 'errors' => []];
        } catch (\Throwable $e) {
            log_error('AuthController::register', $e);
            return ['success' => false, 'errors' => ['Ocurrió un error inesperado. Intenta nuevamente.']];
        }
    }

    /**
     * Procesa el formulario de login con límite de intentos fallidos.
     */
    public function login(array $post): array
    {
        csrf_verify_or_die();

        $email    = clean_email($post['email'] ?? '');
        $password = (string) ($post['password'] ?? '');

        if (!$email || $password === '') {
            return ['success' => false, 'errors' => ['Credenciales inválidas.']];
        }

        try {
            $user = $this->userModel->findByEmail($email);

            // Mensaje genérico deliberado: no revelar si el email existe o no
            $genericError = 'Correo o contraseña incorrectos.';

            if (!$user) {
                return ['success' => false, 'errors' => [$genericError]];
            }

            if (account_is_locked($user['locked_until'])) {
                return ['success' => false, 'errors' => [
                    'Cuenta bloqueada temporalmente por múltiples intentos fallidos. Intenta más tarde.',
                ]];
            }

            if ($user['status'] === 'suspended') {
                return ['success' => false, 'errors' => ['Tu cuenta se encuentra suspendida. Contacta soporte.']];
            }

            if (!password_verify($password, $user['password'])) {
                $this->userModel->registerFailedAttempt((int) $user['id']);
                return ['success' => false, 'errors' => [$genericError]];
            }

            // Login correcto: resetea contador y regenera sesión
            $this->userModel->resetFailedAttempts((int) $user['id']);
            session_regenerate_id(true);

            $_SESSION['user_id']  = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            return ['success' => true, 'errors' => [], 'role' => $user['role']];
        } catch (\Throwable $e) {
            log_error('AuthController::login', $e);
            return ['success' => false, 'errors' => ['Ocurrió un error inesperado. Intenta nuevamente.']];
        }
    }

    public function logout(): void
    {
        nexu_logout_and_destroy();
        redirect('/login.php');
    }
}
