<?php
/**
 * config/config.php
 * ---------------------------------------------------------------------
 * Configuración global del sistema "Nexu Hosting".
 * Centraliza constantes de entorno, rutas y parámetros de seguridad.
 * NUNCA se debe versionar este archivo con credenciales reales en Git;
 * en producción se recomienda cargar estos valores desde variables de
 * entorno del servidor (getenv()) en lugar de dejarlos hardcodeados.
 * ---------------------------------------------------------------------
 */

// Evita el acceso directo al archivo fuera del bootstrap de la app
if (!defined('NEXU_APP')) {
    http_response_code(403);
    die('Acceso directo no permitido.');
}

// ----- Entorno -----
define('APP_ENV', 'production'); // 'production' | 'development'
define('APP_NAME', 'Nexu Hosting');
define('APP_URL', 'https://nexuhosting.com'); // Ajustar al dominio real

// ----- Base de datos -----
define('DB_HOST', 'localhost');
define('DB_NAME', 'nexu_hosting');
define('DB_USER', 'nexu_user');
define('DB_PASS', 'CAMBIAR_ESTA_CONTRASEÑA_EN_PRODUCCION');
define('DB_CHARSET', 'utf8mb4');

// ----- Seguridad -----
define('HASH_ALGO', PASSWORD_ARGON2ID); // Fallback automático a BCRYPT si el build de PHP no soporta Argon2
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('CSRF_TOKEN_NAME', 'nexu_csrf_token');
define('SESSION_NAME', 'NEXU_SESSID');
define('SESSION_LIFETIME', 60 * 60 * 4); // 4 horas de inactividad máxima

// ----- Subida de comprobantes (vouchers) -----
define('UPLOAD_DIR', __DIR__ . '/../uploads/vouchers/');
define('UPLOAD_MAX_BYTES', 5 * 1024 * 1024); // 5 MB máximo por archivo
define('UPLOAD_ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_ALLOWED_MIME', [
    'image/jpeg',
    'image/png',
    'application/pdf',
]);

// ----- Datos de pago local (Perú) -----
// Estos valores alimentan las vistas de checkout (Yape, Plin, transferencias)
define('PAYMENT_INFO', [
    'yape' => [
        'label'   => 'Yape',
        'number'  => '999 999 999',
        'holder'  => 'Nexu Hosting E.I.R.L.',
    ],
    'plin' => [
        'label'   => 'Plin',
        'number'  => '999 999 999',
        'holder'  => 'Nexu Hosting E.I.R.L.',
    ],
    'banco_nacion' => [
        'label'   => 'Banco de la Nación',
        'account' => '00-000-000000',
        'cci'     => '018000000000000000',
        'holder'  => 'Nexu Hosting E.I.R.L.',
    ],
    'interbank' => [
        'label'   => 'Interbank',
        'account' => '000-0000000000',
        'cci'     => '003000000000000000',
        'holder'  => 'Nexu Hosting E.I.R.L.',
    ],
    'bcp' => [
        'label'   => 'BCP',
        'account' => '000-00000000-0-00',
        'cci'     => '002000000000000000',
        'holder'  => 'Nexu Hosting E.I.R.L.',
    ],
]);

// ----- Logs de errores internos -----
define('LOG_DIR', __DIR__ . '/../logs/');
define('ERROR_LOG_FILE', LOG_DIR . 'app_errors.log');

// ----- Zona horaria -----
date_default_timezone_set('America/Lima');

// ----- Reporte de errores -----
// En producción NUNCA se muestran errores al usuario final; se registran en log.
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ERROR_LOG_FILE);
    error_reporting(E_ALL);
}
