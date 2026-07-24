<?php
/**
 * NEXU HOSTING - Funciones Helper Globales
 * Sanitización, flash messages, redirección, formateo.
 */

// ── Sanitización / Escape ─────────────────────────────────────

/** Escapa output HTML — usar en TODAS las variables en vistas */
function e(?string $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Sanitiza cadena: trim + strip tags */
function sanitize(string $v): string
{
    return trim(strip_tags($v));
}

/** Valida email */
function isValidEmail(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/** Valida URL */
function isValidUrl(string $url): bool
{
    return (bool) filter_var($url, FILTER_VALIDATE_URL);
}

// ── Flash Messages ────────────────────────────────────────────

function setFlash(string $message, string $type = 'info'): void
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type']    = $type; // success | danger | warning | info
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash_message'])) return null;

    $flash = [
        'message' => $_SESSION['flash_message'],
        'type'    => $_SESSION['flash_type'] ?? 'info',
    ];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    return $flash;
}

function renderFlash(): string
{
    $flash = getFlash();
    if (!$flash) return '';

    $icons = [
        'success' => '✅',
        'danger'  => '❌',
        'warning' => '⚠️',
        'info'    => 'ℹ️',
    ];
    $colors = [
        'success' => 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400',
        'danger'  => 'bg-red-500/10 border-red-500/30 text-red-400',
        'warning' => 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400',
        'info'    => 'bg-blue-500/10 border-blue-500/30 text-blue-400',
    ];

    $t  = $flash['type'];
    $ic = $icons[$t]  ?? 'ℹ️';
    $cl = $colors[$t] ?? $colors['info'];

    return sprintf(
        '<div class="flex items-center gap-3 px-4 py-3 mb-6 rounded-lg border %s text-sm font-medium animate-fade-in" role="alert">
           <span>%s</span><span>%s</span>
         </div>',
        $cl, $ic, e($flash['message'])
    );
}

// ── Redirección ───────────────────────────────────────────────

function redirect(string $url, int $code = 302): never
{
    // Validar que la URL sea segura (no open redirect)
    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://') && !str_starts_with($url, '/')) {
        $url = '/' . ltrim($url, '/');
    }
    header("Location: $url", true, $code);
    exit;
}

/** Redirige a la URL anterior (Referer) o a un fallback */
function redirectBack(string $fallback = '/'): never
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    // Asegurar que el referer sea del mismo dominio
    if ($referer && parse_url($referer, PHP_URL_HOST) === parse_url(APP_URL, PHP_URL_HOST)) {
        redirect($referer);
    }
    redirect($fallback);
}

// ── Autenticación ─────────────────────────────────────────────

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function requireLogin(string $redirect = '/login.php'): void
{
    if (!isLoggedIn()) {
        setFlash('Debes iniciar sesión para acceder a esta página.', 'warning');
        redirect($redirect);
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        setFlash('No tienes permisos para acceder a esta sección.', 'danger');
        redirect('/dashboard.php');
    }
}

function currentUser(): ?array
{
    if (!isLoggedIn()) return null;

    static $user = null;
    if ($user !== null) return $user;

    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

function loginUser(array $user): void
{
    // Regenerar ID de sesión al autenticar (anti session fixation)
    session_regenerate_id(true);

    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_role']     = $user['role'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_currency'] = $user['preferred_currency'] ?? DEFAULT_CURRENCY;
    $_SESSION['__ip']          = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['__ua']          = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Actualizar último login en BD
    $stmt = db()->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?");
    $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user['id']]);
}

function logoutUser(): void
{
    session_unset();
    session_destroy();
    setcookie(SESSION_NAME, '', time() - 3600, '/', '', SESSION_SECURE, true);
}

// ── Formateo ──────────────────────────────────────────────────

function formatPrice(float $amount, string $currency = ''): string
{
    if (!$currency) {
        $currency = $_SESSION['user_currency'] ?? DEFAULT_CURRENCY;
    }

    $symbols = ['PEN' => 'S/', 'USD' => '$', 'EUR' => '€'];
    $sym     = $symbols[$currency] ?? $currency;
    $decimals = ($currency === 'PEN') ? 2 : 2;

    return $sym . ' ' . number_format($amount, $decimals, '.', ',');
}

function formatDate(string $datetime, string $format = 'd/m/Y'): string
{
    return date($format, strtotime($datetime));
}

function formatDateTime(string $datetime): string
{
    return date('d/m/Y H:i', strtotime($datetime));
}

function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)        return 'hace unos segundos';
    if ($diff < 3600)      return 'hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400)     return 'hace ' . floor($diff / 3600) . 'h';
    if ($diff < 2592000)   return 'hace ' . floor($diff / 86400) . ' días';
    return formatDate($datetime);
}

// ── Badges de estado ──────────────────────────────────────────

function statusBadge(string $status): string
{
    $map = [
        'pending'     => ['Pendiente',    'bg-yellow-500/15 text-yellow-400 border-yellow-500/30'],
        'verified'    => ['Verificado',   'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'],
        'rejected'    => ['Rechazado',    'bg-red-500/15 text-red-400 border-red-500/30'],
        'refunded'    => ['Reembolsado',  'bg-purple-500/15 text-purple-400 border-purple-500/30'],
        'active'      => ['Activo',       'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'],
        'suspended'   => ['Suspendido',   'bg-orange-500/15 text-orange-400 border-orange-500/30'],
        'terminated'  => ['Terminado',    'bg-red-500/15 text-red-400 border-red-500/30'],
        'installing'  => ['Instalando',   'bg-blue-500/15 text-blue-400 border-blue-500/30'],
        'operational' => ['Operacional',  'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'],
        'degraded'    => ['Degradado',    'bg-yellow-500/15 text-yellow-400 border-yellow-500/30'],
        'maintenance' => ['Mantenimiento','bg-blue-500/15 text-blue-400 border-blue-500/30'],
        'outage'      => ['Caído',        'bg-red-500/15 text-red-400 border-red-500/30'],
    ];

    [$label, $classes] = $map[$status] ?? [ucfirst($status), 'bg-gray-500/15 text-gray-400 border-gray-500/30'];

    return sprintf(
        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border %s">%s</span>',
        $classes, $label
    );
}

// ── Número de factura ─────────────────────────────────────────

function generateInvoiceNumber(): string
{
    $stmt = db()->query("SELECT COUNT(*) FROM orders WHERE YEAR(created_at) = YEAR(NOW())");
    $n    = intval($stmt->fetchColumn()) + 1;
    return 'NX-' . date('Y') . '-' . str_pad($n, 5, '0', STR_PAD_LEFT);
}

// ── IP del cliente ────────────────────────────────────────────

function clientIp(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

// ── Assets con cache busting ──────────────────────────────────

function asset(string $path): string
{
    $full = BASE_PATH . '/' . ltrim($path, '/');
    $mtime = file_exists($full) ? filemtime($full) : time();
    return ltrim($path, '/') . '?v=' . $mtime;
}

// ── Paginación ────────────────────────────────────────────────

function paginate(int $total, int $perPage, int $currentPage): array
{
    $totalPages  = max(1, (int) ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset      = ($currentPage - 1) * $perPage;

    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
    ];
}
