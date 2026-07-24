<?php
/**
 * NEXU HOSTING - Protección CSRF
 * Tokens por sesión con expiración y one-time-use opcional.
 */

define('CSRF_SESSION_KEY', '_csrf_tokens');
define('CSRF_MAX_AGE',     3600); // 1 hora

/**
 * Genera o recupera el token CSRF de la sesión.
 * Se crea uno nuevo si no existe o expiró.
 */
function csrfToken(): string
{
    if (empty($_SESSION[CSRF_SESSION_KEY])) {
        $_SESSION[CSRF_SESSION_KEY] = [];
    }

    // Limpiar tokens expirados
    $now = time();
    $_SESSION[CSRF_SESSION_KEY] = array_filter(
        $_SESSION[CSRF_SESSION_KEY],
        fn($t) => ($now - $t['created_at']) < CSRF_MAX_AGE
    );

    // Crear nuevo token
    $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    $_SESSION[CSRF_SESSION_KEY][$token] = ['created_at' => $now];

    return $token;
}

/**
 * Valida el token recibido contra los tokens de sesión.
 * Elimina el token tras validarlo (one-time-use).
 */
function verifyCsrf(string $token): bool
{
    if (empty($token) || empty($_SESSION[CSRF_SESSION_KEY])) {
        return false;
    }

    $now = time();

    foreach ($_SESSION[CSRF_SESSION_KEY] as $stored => $meta) {
        if (
            hash_equals($stored, $token) &&
            ($now - $meta['created_at']) < CSRF_MAX_AGE
        ) {
            // Consumir el token
            unset($_SESSION[CSRF_SESSION_KEY][$stored]);
            return true;
        }
    }

    return false;
}

/**
 * Renderiza el campo oculto CSRF para formularios HTML.
 */
function csrfField(): string
{
    return sprintf(
        '<input type="hidden" name="_csrf_token" value="%s">',
        e(csrfToken())
    );
}

/**
 * Verifica el CSRF en una POST request y aborta si falla.
 * Usar al inicio de cada bloque POST.
 */
function validateCsrfOrFail(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $token = $_POST['_csrf_token'] ?? '';

    if (!verifyCsrf($token)) {
        error_log('[NEXU CSRF] Token inválido - IP: ' . clientIp() . ' - URL: ' . ($_SERVER['REQUEST_URI'] ?? ''));
        http_response_code(403);
        setFlash('Solicitud inválida. Por favor recarga la página e intenta de nuevo.', 'danger');
        redirectBack();
    }
}
