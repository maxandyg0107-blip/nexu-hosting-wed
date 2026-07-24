<?php
/**
 * voucher_view.php
 * ---------------------------------------------------------------------
 * Sirve el archivo de comprobante de una orden de forma controlada.
 * El directorio uploads/vouchers/ NO es de acceso público directo:
 * todo acceso pasa por aquí, donde se verifica que quien solicita el
 * archivo sea (a) el administrador, o (b) el dueño de la orden.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$orderId = (int) ($_GET['order_id'] ?? 0);

$orderModel = new Order();
$order = $orderModel->findById($orderId);

if (!$order) {
    http_response_code(404);
    die('Orden no encontrada.');
}

$isOwner = (int) $order['user_id'] === (int) $_SESSION['user_id'];
if (!$isOwner && !is_admin()) {
    http_response_code(403);
    die('No tienes permiso para ver este comprobante.');
}

if (empty($order['voucher_image'])) {
    http_response_code(404);
    die('Esta orden no tiene comprobante adjunto.');
}

// Sanea el nombre de archivo: solo se permiten los caracteres generados
// por generate_safe_filename() (letras, números, ., _ y -). Esto bloquea
// cualquier intento de "path traversal" (../../etc/passwd, etc.)
$filename = $order['voucher_image'];
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
    http_response_code(400);
    die('Nombre de archivo inválido.');
}

$filepath = realpath(rtrim(UPLOAD_DIR, '/') . '/' . $filename);
$uploadsRealPath = realpath(UPLOAD_DIR);

// Doble verificación: el archivo resuelto debe seguir dentro del
// directorio de uploads permitido (defensa en profundidad contra traversal)
if ($filepath === false || $uploadsRealPath === false || !str_starts_with($filepath, $uploadsRealPath)) {
    http_response_code(404);
    die('Comprobante no encontrado.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($filepath);

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, no-cache');
readfile($filepath);
exit;
