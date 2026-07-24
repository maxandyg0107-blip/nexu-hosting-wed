<?php
/**
 * includes/functions.php
 * ---------------------------------------------------------------------
 * Funciones utilitarias de propósito general reutilizadas por vistas
 * y controladores (formato de moneda, badges de estado, etc.)
 * ---------------------------------------------------------------------
 */

if (!defined('NEXU_APP')) {
    http_response_code(403);
    die('Acceso directo no permitido.');
}

/**
 * Formatea un monto decimal como Soles Peruanos (PEN).
 */
function money_pen(float $amount): string
{
    return 'S/ ' . number_format($amount, 2, '.', ',');
}

/**
 * Devuelve las clases Tailwind para un "badge" visual según el estado de la orden.
 */
function order_status_badge(string $status): string
{
    return match ($status) {
        'pending'  => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
        'verified' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
        'rejected' => 'bg-red-500/10 text-red-400 border border-red-500/20',
        default    => 'bg-slate-500/10 text-slate-400 border border-slate-500/20',
    };
}

function order_status_label(string $status): string
{
    return match ($status) {
        'pending'  => 'Pendiente de verificación',
        'verified' => 'Pago verificado',
        'rejected' => 'Rechazado',
        default    => ucfirst($status),
    };
}

function server_status_badge(string $status): string
{
    return match ($status) {
        'installing' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
        'active'     => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
        'suspended'  => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
        'terminated' => 'bg-red-500/10 text-red-400 border border-red-500/20',
        default      => 'bg-slate-500/10 text-slate-400 border border-slate-500/20',
    };
}

/**
 * Genera un nombre de archivo aleatorio y único para vouchers subidos,
 * preservando únicamente la extensión validada (nunca el nombre original).
 */
function generate_safe_filename(string $extension): string
{
    return uniqid('voucher_', true) . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
}
