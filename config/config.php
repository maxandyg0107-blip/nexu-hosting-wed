<?php
/**
 * NEXU HOSTING - Configuración Central
 * Todos los ajustes de la plataforma en un solo lugar.
 * NUNCA exponer este archivo al público (ver .htaccess).
 */

// ── Entorno ──────────────────────────────────────────────────
define('APP_ENV',    getenv('APP_ENV')  ?: 'production'); // 'development' | 'production'
define('APP_DEBUG',  APP_ENV === 'development');
define('APP_VERSION', '2.0.0');
define('APP_NAME',   'Nexu Hosting');
define('APP_URL',    rtrim(getenv('APP_URL') ?: 'https://nexuhosting.com', '/'));

// ── Base de datos ─────────────────────────────────────────────
$db_host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST') ?? $_ENV['MYSQL_ADDON_HOST'] ?? $_SERVER['MYSQL_ADDON_HOST'] ?? getenv('MYSQL_ADDON_HOST') ?: 'bhn3opeyfr7d8hhaxpsj-mysql.services.clever-cloud.com';
$db_port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? getenv('DB_PORT') ?? $_ENV['MYSQL_ADDON_PORT'] ?? $_SERVER['MYSQL_ADDON_PORT'] ?? getenv('MYSQL_ADDON_PORT') ?: '3306';
$db_name = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? getenv('DB_NAME') ?? $_ENV['MYSQL_ADDON_DB']   ?? $_SERVER['MYSQL_ADDON_DB']   ?? getenv('MYSQL_ADDON_DB')   ?: 'bhn3opeyfr7d8hhaxpsj';
$db_user = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? getenv('DB_USER') ?? $_ENV['MYSQL_ADDON_USER'] ?? $_SERVER['MYSQL_ADDON_USER'] ?? getenv('MYSQL_ADDON_USER') ?: 'uzasgvvixdrvhsnj';
$db_pass = $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? getenv('DB_PASS') ?? $_ENV['MYSQL_ADDON_PASSWORD'] ?? $_SERVER['MYSQL_ADDON_PASSWORD'] ?? getenv('MYSQL_ADDON_PASSWORD') ?: 'o3altpkx4NOL2ocUTh7v';

define('DB_HOST',    $db_host);
define('DB_PORT',    $db_port);
define('DB_NAME',    $db_name);
define('DB_USER',    $db_user);
define('DB_PASS',    $db_pass);
define('DB_CHARSET', 'utf8mb4');

// ── Sesiones ──────────────────────────────────────────────────
define('SESSION_NAME',     'nexu_sess');
define('SESSION_LIFETIME', 7200);         // 2 horas en segundos
define('SESSION_SECURE',   APP_ENV === 'production');

// ── Seguridad ─────────────────────────────────────────────────
define('CSRF_TOKEN_LENGTH', 32);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES',   15);
define('PASSWORD_MIN_LEN',   8);

// ── Rutas de directorios ──────────────────────────────────────
define('BASE_PATH',     dirname(__DIR__));                       // /public/nexu-hosting
define('CONFIG_PATH',   BASE_PATH . '/config');
define('UPLOADS_PATH',  BASE_PATH . '/uploads');
define('VOUCHERS_PATH', UPLOADS_PATH . '/vouchers');
define('LOGS_PATH',     BASE_PATH . '/logs');

// ── Subida de archivos ────────────────────────────────────────
define('MAX_UPLOAD_SIZE',    10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'application/pdf',
]);

// ── Monedas ───────────────────────────────────────────────────
define('DEFAULT_CURRENCY', 'PEN');
define('SUPPORTED_CURRENCIES', ['PEN', 'USD', 'EUR']);

// ── OAuth - Google ────────────────────────────────────────────
define('GOOGLE_CLIENT_ID',     getenv('GOOGLE_CLIENT_ID')     ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI',  APP_URL . '/auth/google/callback.php');

// ── OAuth - Discord ───────────────────────────────────────────
define('DISCORD_CLIENT_ID',     getenv('DISCORD_CLIENT_ID')     ?: '');
define('DISCORD_CLIENT_SECRET', getenv('DISCORD_CLIENT_SECRET') ?: '');
define('DISCORD_REDIRECT_URI',  APP_URL . '/auth/discord/callback.php');

// ── Pagos Locales Perú (Yape / Plin / Bancos) ─────────────────
define('PAYMENT_CONFIG', [
    'yape' => [
        'label'       => 'Yape',
        'phone'       => '987 654 321',
        'holder_name' => 'Nexu Hosting SAC',
        'qr_image'    => 'assets/img/qr-yape.png',  // colocar QR real aquí
        'icon'        => '📲',
        'color'       => '#6B21A8',
    ],
    'plin' => [
        'label'       => 'Plin',
        'phone'       => '987 654 321',
        'holder_name' => 'Nexu Hosting SAC',
        'qr_image'    => 'assets/img/qr-plin.png',
        'icon'        => '💚',
        'color'       => '#16A34A',
    ],
    'banco_nacion' => [
        'label'        => 'Banco de la Nación',
        'account'      => '04-123456789012',
        'cci'          => '018-014-001234567890-12',
        'holder_name'  => 'Nexu Hosting SAC',
        'holder_dni'   => '12345678',
        'icon'         => '🏛️',
        'color'        => '#1E40AF',
    ],
    'interbank' => [
        'label'       => 'Interbank',
        'account'     => '200-3001234567',
        'cci'         => '003-200-003001234567-34',
        'holder_name' => 'Nexu Hosting SAC',
        'holder_dni'  => '12345678',
        'icon'        => '🏦',
        'color'       => '#DC2626',
    ],
    'bcp' => [
        'label'       => 'BCP',
        'account'     => '194-12345678-0-78',
        'cci'         => '002-194-001234567890-78',
        'holder_name' => 'Nexu Hosting SAC',
        'holder_dni'  => '12345678',
        'icon'        => '🏧',
        'color'       => '#1D4ED8',
    ],
]);

// ── Email (PHPMailer / SMTP) ──────────────────────────────────
define('MAIL_HOST',       getenv('MAIL_HOST')       ?: 'smtp.gmail.com');
define('MAIL_PORT',       getenv('MAIL_PORT')       ?: 587);
define('MAIL_USER',       getenv('MAIL_USER')       ?: '');
define('MAIL_PASS',       getenv('MAIL_PASS')       ?: '');
define('MAIL_FROM',       getenv('MAIL_FROM')       ?: 'noreply@nexuhosting.com');
define('MAIL_FROM_NAME',  APP_NAME);

// ── Pterodactyl Panel ─────────────────────────────────────────
define('PTERODACTYL_URL',  getenv('PTERODACTYL_URL')  ?: '');
define('PTERODACTYL_KEY',  getenv('PTERODACTYL_KEY')  ?: '');
