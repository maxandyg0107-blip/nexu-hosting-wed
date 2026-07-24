<?php
/**
 * Nexu Hosting - Webhook para notificaciones de pago
 * Compatible con Stripe, PayPal y MercadoPago (ejemplos)
 */

require_once 'includes/pagos.php';

header('Content-Type: application/json');

$payload = file_get_contents('php://input');
$datos = json_decode($payload, true);
$tipo = $_GET['tipo'] ?? 'desconocido';

if (!$datos) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido']);
    exit;
}

// Registrar intento de webhook para debug
$stmt = $pdo->prepare("INSERT INTO transacciones (usuario_id, metodo_pago, monto, estado, datos_pago, notas) VALUES (0, ?, 0, 'pendiente', ?, ?)");
$stmt->execute([$tipo, $payload, 'Webhook recibido']);

$procesado = false;

// Stripe
if ($tipo === 'stripe') {
    $evento = $datos['type'] ?? '';
    
    if ($evento === 'checkout.session.completed' && isset($datos['data']['object'])) {
        $session = $datos['data']['object'];
        $transaccionId = intval($session['client_reference_id'] ?? 0);
        
        if ($transaccionId > 0) {
            $procesado = procesarPagoCompletado($transaccionId, ['stripe_session_id' => $session['id']]);
        }
    }
}

// PayPal
if ($tipo === 'paypal') {
    $evento = $datos['event_type'] ?? '';
    
    if ($evento === 'PAYMENT.CAPTURE.COMPLETED' && isset($datos['resource'])) {
        $resource = $datos['resource'];
        $customId = $resource['custom_id'] ?? '';
        $transaccionId = intval($customId);
        
        if ($transaccionId > 0) {
            $procesado = procesarPagoCompletado($transaccionId, ['paypal_capture_id' => $resource['id']]);
        }
    }
}

// MercadoPago
if ($tipo === 'mercadopago') {
    $accion = $datos['action'] ?? '';
    
    if (strpos($accion, 'payment.') === 0 && isset($datos['data']['id'])) {
        $paymentId = $datos['data']['id'];
        // En producción aquí consultarías la API de MP para obtener external_reference
        // $transaccionId = intval($externalReference);
        // $procesado = procesarPagoCompletado($transaccionId, ['mp_payment_id' => $paymentId]);
    }
}

if ($procesado) {
    echo json_encode(['success' => true, 'message' => 'Pago procesado']);
} else {
    echo json_encode(['success' => false, 'message' => 'Webhook recibido pero no procesado automáticamente']);
}
