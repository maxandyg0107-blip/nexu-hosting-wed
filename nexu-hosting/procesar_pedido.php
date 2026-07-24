<?php
require_once 'includes/funciones.php';

if (!estaLogueado()) {
    setMensaje('Debes iniciar sesión para realizar un pedido.', 'danger');
    redirigir('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('planes.php');
}

$planId = intval($_POST['plan_id'] ?? 0);
$ciclo = $_POST['ciclo'] ?? 'mensual';
$dominio = trim($_POST['dominio'] ?? '');
$metodoPago = $_POST['metodo_pago'] ?? 'paypal';

if (!in_array($ciclo, ['mensual', 'trimestral', 'anual'])) {
    $ciclo = 'mensual';
}

$plan = null;
$stmt = $pdo->prepare("SELECT * FROM planes WHERE id = ? AND activo = 1 LIMIT 1");
$stmt->execute([$planId]);
$plan = $stmt->fetch();

if (!$plan) {
    setMensaje('El plan seleccionado no existe.', 'danger');
    redirigir('planes.php');
}

// Calcular total
$total = calcularPrecioCiclo($plan['precio_mensual'], $ciclo);

// Calcular fechas
$fechaInicio = date('Y-m-d H:i:s');
$fechaRenovacion = calcularFechaRenovacion($ciclo);

try {
    $pdo->beginTransaction();
    
    // Crear pedido
    $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, plan_id, dominio, ciclo, total, estado, fecha_inicio, fecha_renovacion, metodo_pago) 
                           VALUES (?, ?, ?, ?, ?, 'pendiente', ?, ?, ?)");
    $stmt->execute([$_SESSION['usuario_id'], $planId, $dominio, $ciclo, $total, $fechaInicio, $fechaRenovacion, $metodoPago]);
    $pedidoId = $pdo->lastInsertId();
    
    // Crear factura con número oficial
    $concepto = $plan['nombre'] . ' - ' . ucfirst($ciclo);
    crearFactura($_SESSION['usuario_id'], $pedidoId, $concepto, $total);
    
    $pdo->commit();
    
    setMensaje('Pedido creado correctamente. Tu factura está pendiente de pago.', 'success');
    redirigir('panel.php?seccion=facturas');
    
} catch (PDOException $e) {
    $pdo->rollBack();
    setMensaje('Error al procesar el pedido. Intenta más tarde.', 'danger');
    redirigir('planes.php?plan=' . $plan['slug']);
}
