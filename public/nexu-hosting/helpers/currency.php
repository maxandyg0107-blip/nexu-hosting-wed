<?php
/**
 * NEXU HOSTING - Conversión de Monedas
 * Convierte precios usando tasas almacenadas en BD.
 */

/**
 * Obtiene la tasa de cambio entre dos monedas desde la BD.
 * Retorna 1.0 si no hay tasa o si son la misma moneda.
 */
function getExchangeRate(string $from, string $to): float
{
    if ($from === $to) return 1.0;

    static $rates = [];
    $key = "$from-$to";

    if (!isset($rates[$key])) {
        $stmt = db()->prepare(
            "SELECT rate FROM exchange_rates WHERE from_currency = ? AND to_currency = ? LIMIT 1"
        );
        $stmt->execute([$from, $to]);
        $row          = $stmt->fetch();
        $rates[$key]  = $row ? (float)$row['rate'] : 1.0;
    }

    return $rates[$key];
}

/**
 * Convierte un monto de PEN a la moneda de sesión del usuario.
 * Si no hay sesión activa devuelve el precio en PEN.
 */
function convertPrice(float $amountPen): float
{
    $targetCurrency = $_SESSION['user_currency'] ?? DEFAULT_CURRENCY;
    if ($targetCurrency === 'PEN') return $amountPen;

    $rate = getExchangeRate('PEN', $targetCurrency);
    return round($amountPen * $rate, 2);
}

/**
 * Formatea y convierte en un solo paso.
 * Uso: priceInCurrency(69.00) → "$ 18.29" (si sesión = USD)
 */
function priceInCurrency(float $amountPen): string
{
    $currency    = $_SESSION['user_currency'] ?? DEFAULT_CURRENCY;
    $converted   = convertPrice($amountPen);
    return formatPrice($converted, $currency);
}

/**
 * Retorna la moneda activa de la sesión del usuario.
 */
function activeCurrency(): string
{
    return $_SESSION['user_currency'] ?? DEFAULT_CURRENCY;
}

/**
 * Cambia la moneda de la sesión y guarda en BD si está logueado.
 */
function setCurrency(string $currency): void
{
    if (!in_array($currency, SUPPORTED_CURRENCIES, true)) return;

    $_SESSION['user_currency'] = $currency;

    if (isLoggedIn()) {
        $stmt = db()->prepare("UPDATE users SET preferred_currency = ? WHERE id = ?");
        $stmt->execute([$currency, $_SESSION['user_id']]);
    }
}
