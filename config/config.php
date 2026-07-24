<?php
/**
 * NEXU HOSTING - Configuración Central v2.1
 * Compatible con Render.com (variables de entorno) y desarrollo local.
 * NUNCA exponer este archivo al público.
 */

// ── Entorno ───────────────────────────────────────────────────
define('APP_ENV',     getenv('APP_ENV')  ?: 'production');
define('APP_DEBUG',   APP_ENV === 'development');
define('APP_VERSION', '2.1.0');
define('APP_NAME',    'Nexu Hosting');
define('APP_URL',     rtrim(getenv('APP_URL') ?: 'https://nexuhosting.com', '/'));

// ── Base de datos — busca en todas las fuentes de Render ──────
function _env(array $keys, string $default = ''): string
{
    foreach ($keys as $k) {
        $v = $_ENV[$k]    ?? null;
        if ($v !== null && $v !== '') return (string)$v;
        $v = $_SERVER[$k] ?? null;
        if ($v !== null && $v !== '') return (string)$v;
        $v = getenv($k);
        if ($v !== false  && $v !== '') return (string)$v;
    }
    return $default;
}

$db_host = _env(['DB_HOST', 'MYSQL_ADDON_HOST', 'DATABASE_HOST']);
$db_port = _env(['DB_PORT', 'MYSQL_ADDON_PORT', 'DATABASE_PORT'], '3306');
$db_name = _env(['DB_NAME', 'MYSQL_ADDON_DB',   'DATABASE_NAME']);
$db_user = _env(['DB_USER', 'MYSQL_ADDON_USER',  'DATABASE_USER']);
$db_pass = _env(['DB_PASS', 'MYSQL_ADDON_PASSWORD','DATABASE_PASSWORD']);

/* ── MODO DE INSTALACIÓN: si no hay BD configurada, mostrar
      la página de instalación en lugar de 503 ──────────────── */
if (($db_host === '' || $db_name === '' || $db_user === '') && !defined('SKIP_DB_CHECK')) {
    // Solo mostrar 503 si NO estamos en la página de instalación
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (!str_contains($requestUri, 'instalar.php')) {
        // En producción mostrar mensaje genérico, en dev mostrar hint
        if (APP_DEBUG) {
            die('[NEXU] Faltan variables de entorno: DB_HOST, DB_NAME, DB_USER, DB_PASS');
        }
        // Redirigir a instalador si existe
        if (!headers_sent()) {
            header('Location: /instalar.php', true, 302);
            exit;
        }
        http_response_code(503);
        exit('El servicio no está disponible. Por favor configura las variables de entorno de la base de datos.');
    }
}

define('DB_HOST',    $db_host);
define('DB_PORT',    $db_port);
define('DB_NAME',    $db_name);
define('DB_USER',    $db_user);
define('DB_PASS',    $db_pass);
define('DB_CHARSET', 'utf8mb4');

// ── Sesiones ──────────────────────────────────────────────────
define('SESSION_NAME',     'nexu_sess');
define('SESSION_LIFETIME', 7200);
define('SESSION_SECURE',   APP_ENV === 'production');

// ── Seguridad ─────────────────────────────────────────────────
define('CSRF_TOKEN_LENGTH',  32);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES',    15);
define('PASSWORD_MIN_LEN',   8);

// ── Rutas ─────────────────────────────────────────────────────
define('BASE_PATH',     dirname(__DIR__));
define('CONFIG_PATH',   BASE_PATH . '/config');
define('UPLOADS_PATH',  BASE_PATH . '/uploads');
define('VOUCHERS_PATH', UPLOADS_PATH . '/vouchers');
define('LOGS_PATH',     BASE_PATH . '/logs');

// ── Subida de archivos ────────────────────────────────────────
define('MAX_UPLOAD_SIZE',    10 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);

// ── Monedas ───────────────────────────────────────────────────
define('DEFAULT_CURRENCY',     'PEN');
define('SUPPORTED_CURRENCIES', ['PEN', 'USD', 'EUR']);

// ── OAuth Google ──────────────────────────────────────────────
define('GOOGLE_CLIENT_ID',     _env(['GOOGLE_CLIENT_ID']));
define('GOOGLE_CLIENT_SECRET', _env(['GOOGLE_CLIENT_SECRET']));
define('GOOGLE_REDIRECT_URI',  APP_URL . '/auth/google/callback.php');

// ── OAuth Discord ─────────────────────────────────────────────
define('DISCORD_CLIENT_ID',     _env(['DISCORD_CLIENT_ID']));
define('DISCORD_CLIENT_SECRET', _env(['DISCORD_CLIENT_SECRET']));
define('DISCORD_REDIRECT_URI',  APP_URL . '/auth/discord/callback.php');

// ── WhatsApp de soporte ───────────────────────────────────────
// EDITA el número aquí (formato internacional sin +, sin espacios)
define('WHATSAPP_NUMBER',  _env(['WHATSAPP_NUMBER'], '51987654321'));
define('WHATSAPP_MESSAGE', urlencode('¡Hola! Necesito ayuda con Nexu Hosting.'));

// ── Pagos Locales (Perú) ──────────────────────────────────────
define('PAYMENT_CONFIG', [
    'yape' => [
        'label'       => 'Yape',
        'phone'       => _env(['YAPE_PHONE'], '987 654 321'),
        'holder_name' => _env(['PAYMENT_HOLDER_NAME'], 'Nexu Hosting SAC'),
        'qr_image'    => 'assets/img/qr-yape.png',
        'icon'        => '📲',
        'color'       => '#6B21A8',
    ],
    'plin' => [
        'label'       => 'Plin',
        'phone'       => _env(['PLIN_PHONE'], '987 654 321'),
        'holder_name' => _env(['PAYMENT_HOLDER_NAME'], 'Nexu Hosting SAC'),
        'qr_image'    => 'assets/img/qr-plin.png',
        'icon'        => '💚',
        'color'       => '#16A34A',
    ],
    'banco_nacion' => [
        'label'       => 'Banco de la Nación',
        'account'     => '04-123456789012',
        'cci'         => '018-014-001234567890-12',
        'holder_name' => _env(['PAYMENT_HOLDER_NAME'], 'Nexu Hosting SAC'),
        'holder_dni'  => '12345678',
        'icon'        => '🏛️',
        'color'       => '#1E40AF',
    ],
    'interbank' => [
        'label'       => 'Interbank',
        'account'     => '200-3001234567',
        'cci'         => '003-200-003001234567-34',
        'holder_name' => _env(['PAYMENT_HOLDER_NAME'], 'Nexu Hosting SAC'),
        'holder_dni'  => '12345678',
        'icon'        => '🏦',
        'color'       => '#DC2626',
    ],
    'bcp' => [
        'label'       => 'BCP',
        'account'     => '194-12345678-0-78',
        'cci'         => '002-194-001234567890-78',
        'holder_name' => _env(['PAYMENT_HOLDER_NAME'], 'Nexu Hosting SAC'),
        'holder_dni'  => '12345678',
        'icon'        => '🏧',
        'color'       => '#1D4ED8',
    ],
]);

// ── Email ─────────────────────────────────────────────────────
define('MAIL_HOST',      _env(['MAIL_HOST'],     'smtp.gmail.com'));
define('MAIL_PORT',      (int)_env(['MAIL_PORT'], '587'));
define('MAIL_USER',      _env(['MAIL_USER']));
define('MAIL_PASS',      _env(['MAIL_PASS']));
define('MAIL_FROM',      _env(['MAIL_FROM'],      'noreply@nexuhosting.com'));
define('MAIL_FROM_NAME', APP_NAME);

// ── Pterodactyl ───────────────────────────────────────────────
define('PTERODACTYL_URL', _env(['PTERODACTYL_URL']));
define('PTERODACTYL_KEY', _env(['PTERODACTYL_KEY']));
