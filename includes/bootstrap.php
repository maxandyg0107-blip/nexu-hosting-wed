<?php
/**
 * includes/bootstrap.php
 * ---------------------------------------------------------------------
 * Punto de arranque único de la aplicación. Todo archivo público
 * (login.php, checkout.php, dashboard.php, admin_orders.php, etc.)
 * debe iniciar con: require_once __DIR__ . '/includes/bootstrap.php';
 * ---------------------------------------------------------------------
 */

define('NEXU_APP', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/functions.php';

// Autoload simple de Models/Controllers (sin Composer, PHP nativo)
spl_autoload_register(function (string $class) {
    $dirs = [
        __DIR__ . '/../models/',
        __DIR__ . '/../controllers/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Cabeceras de seguridad HTTP recomendadas (mitigan XSS, clickjacking, sniffing)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; script-src 'self' https://cdn.tailwindcss.com;");

nexu_start_session();
