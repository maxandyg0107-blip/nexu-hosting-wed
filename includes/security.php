<?php
/**
 * includes/security.php
 * ---------------------------------------------------------------------
 * Funciones auxiliares globales de seguridad:
 *  - Configuración endurecida de cookies de sesión
 *  - Generación y verificación de tokens anti-CSRF
 *  - Saneamiento de entradas contra XSS
 *  - Límite de intentos de login (fuerza bruta)
 * ---------------------------------------------------------------------
 */

if (!defined('NEXU_APP')) {
    http_response_code(403);
    die('Acceso directo no permitido.');
}

/**
 * Inicializa una sesión segura con cookies endurecidas.
 * Debe llamarse UNA sola vez, antes de cualquier salida HTML.
 */
function nexu_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name(SESSION_NAME);

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '', // usar el dominio actual automáticamente
        'secure'   => true,     // solo se transmite sobre HTTPS
        'httponly' => true,     // inaccesible desde JavaScript (mitiga XSS -> robo de cookie)
        'samesite' => 'Strict', // mitiga ataques CSRF cross-site
    ]);

    session_start();

    // Regenera el ID de sesión periódicamente para mitigar "session fixation"
    if (empty($_SESSION['_last_regen']) || (time() - $_SESSION['_last_regen']) > 600) {
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = time();
    }

    // Expiración por inactividad
    if (!empty($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity']) > SESSION_LIFETIME) {
        nexu_logout_and_destroy();
    }
    $_SESSION['_last_activity'] = time();
}

/**
 * Destruye completamente la sesión actual (logout seguro).
 */
function nexu_logout_and_destroy(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Genera (o reutiliza) un token CSRF almacenado en sesión.
 */
function csrf_token(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Imprime un <input type="hidden"> listo para insertar en formularios.
 */
function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
}

/**
 * Verifica el token CSRF recibido por POST usando comparación
 * de tiempo constante (hash_equals) para evitar "timing attacks".
 */
function csrf_verify(): bool
{
    $sent = $_POST[CSRF_TOKEN_NAME] ?? '';
    $real = $_SESSION[CSRF_TOKEN_NAME] ?? '';

    if (empty($sent) || empty($real)) {
        return false;
    }

    return hash_equals($real, $sent);
}

/**
 * Aborta la petición si el token CSRF es inválido o está ausente.
 */
function csrf_verify_or_die(): void
{
    if (!csrf_verify()) {
        http_response_code(419);
        die('Token de seguridad inválido o expirado. Recarga la página e inténtalo de nuevo.');
    }
}

/**
 * Sanea una cadena de texto simple contra XSS antes de mostrarla en HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida y normaliza un correo electrónico. Devuelve null si es inválido.
 */
function clean_email(?string $email): ?string
{
    $email = trim($email ?? '');
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
}

/**
 * Valida un nombre de usuario: 3-50 caracteres alfanuméricos, guiones y guion bajo.
 */
function clean_username(?string $username): ?string
{
    $username = trim($username ?? '');
    if (preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username)) {
        return $username;
    }
    return null;
}

/**
 * Registro centralizado de errores internos sin exponer detalles al usuario.
 */
function log_error(string $context, \Throwable $e): void
{
    $message = sprintf(
        '[%s] %s | %s:%d',
        $context,
        $e->getMessage(),
        basename($e->getFile()),
        $e->getLine()
    );
    error_log($message);
}

/**
 * Redirige de forma segura y detiene la ejecución.
 */
function redirect(string $path): void
{
    header('Location: ' . $path, true, 302);
    exit;
}

/**
 * Verifica si el usuario actual está autenticado.
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Verifica si el usuario actual tiene rol de administrador.
 */
function is_admin(): bool
{
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Middleware: exige sesión iniciada. Redirige a login si no la hay.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

/**
 * Middleware: exige rol de administrador. Corta con 403 si no lo tiene.
 * Se usa al inicio de cada endpoint del panel administrativo.
 */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('403 - No tienes privilegios suficientes para acceder a este recurso.');
    }
}

/**
 * Sistema de limitación de intentos de login (anti fuerza bruta),
 * respaldado en la propia tabla `users` (columnas failed_login_attempts / locked_until).
 */
function account_is_locked(?string $lockedUntil): bool
{
    if (empty($lockedUntil)) {
        return false;
    }
    return strtotime($lockedUntil) > time();
}
