<?php
/**
 * NEXU HOSTING - Bootstrap Global
 * Incluir este archivo al inicio de CADA página PHP.
 * Orden de carga: config → session → database → helpers → autoload
 */

// ── Zona horaria ──────────────────────────────────────────────
date_default_timezone_set('America/Lima');

// ── Manejo de errores ─────────────────────────────────────────
if (!defined('APP_ENV')) {
    require_once __DIR__ . '/config.php';
}

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors',     '1');
    ini_set('error_log',      dirname(__DIR__) . '/logs/php_errors.log');
}

// ── Dependencias ──────────────────────────────────────────────
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/database.php';

// Iniciar sesión segura
initSecureSession();

// ── Autoloader PSR-4 ligero ───────────────────────────────────
// Carga clases desde /models, /controllers, /helpers automáticamente
spl_autoload_register(function (string $class): void {
    $base   = dirname(__DIR__);
    $paths  = [
        $base . '/models/'      . $class . '.php',
        $base . '/controllers/' . $class . '.php',
        $base . '/helpers/'     . $class . '.php',
    ];
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ── Helpers globales ──────────────────────────────────────────
require_once dirname(__DIR__) . '/helpers/functions.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/helpers/currency.php';

// ── Security headers ──────────────────────────────────────────
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (APP_ENV === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
