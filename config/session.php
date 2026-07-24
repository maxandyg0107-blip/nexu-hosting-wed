<?php
/**
 * NEXU HOSTING - Configuración segura de sesiones
 * Ejecutar ANTES de session_start() en el bootstrap.
 */

require_once __DIR__ . '/config.php';

function initSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return; // ya iniciada
    }

    // Configurar parámetros ANTES de session_start
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',                        // vacío = dominio actual
        'secure'   => SESSION_SECURE,            // true en HTTPS/producción
        'httponly' => true,                      // bloquea acceso JS → anti-XSS
        'samesite' => 'Strict',                  // anti-CSRF máximo
    ]);

    ini_set('session.use_strict_mode', '1');     // rechaza IDs no iniciados por server
    ini_set('session.use_only_cookies', '1');    // no session IDs en URL
    ini_set('session.cookie_httponly', '1');
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor',     '100');

    session_name(SESSION_NAME);
    session_start();

    // Regenerar ID en primer uso para prevenir session fixation
    if (!isset($_SESSION['__initialized'])) {
        session_regenerate_id(true);
        $_SESSION['__initialized'] = true;
        $_SESSION['__created_at']  = time();
        $_SESSION['__ip']          = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['__ua']          = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    // Validar que la sesión no fue secuestrada (IP o UA cambiaron)
    if (
        $_SESSION['__ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '') ||
        $_SESSION['__ua'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')
    ) {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $_SESSION['__initialized'] = true;
        $_SESSION['__created_at']  = time();
        $_SESSION['__ip']          = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['__ua']          = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    // Expirar sesión si superó el lifetime
    if (isset($_SESSION['__created_at']) && (time() - $_SESSION['__created_at']) > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $_SESSION['__initialized'] = true;
        $_SESSION['__created_at']  = time();
        $_SESSION['__ip']          = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['__ua']          = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}
