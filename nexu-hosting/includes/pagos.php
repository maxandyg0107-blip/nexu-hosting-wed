<?php
/**
 * Nexu Hosting - Configuración y funciones de pagos
 */

require_once __DIR__ . '/db.php';

// Monedas soportadas
$MONEDAS = [
    'USD' => ['simbolo' => '$', 'nombre' => 'Dólar estadounidense'],
    'EUR' => ['simbolo' => '€', 'nombre' => 'Euro'],
    'MXN' => ['simbolo' => '$', 'nombre' => 'Peso mexicano'],
    'ARS' => ['simbolo' => '$', 'nombre' => 'Peso argentino'],
    'COP' => ['simbolo' => '$', 'nombre' => 'Peso colombiano'],
    'CLP' => ['simbolo' => '$', 'nombre' => 'Peso chileno'],
    'BRL' => ['simbolo' => 'R$', 'nombre' => 'Real brasileño'],
];

// Ciclos de facturación
$CICLOS = [
    'mensual' => ['meses' => 1, 'descuento' => 0],
    'trimestral' => ['meses' => 3, 'descuento' => 0.05],
    'anual' => ['meses' => 12, 'descuento' => 0.15],
];

function obtenerConfigPago($pasarela) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM config_pagos WHERE pasarela = ? LIMIT 1");
    $stmt->execute([$pasarela]);
    return $stmt->fetch();
}

function obtenerPasarelasActivas() {
    global $pdo;
    return $pdo->query("SELECT * FROM config_pagos WHERE activo = 1 ORDER BY pasarela ASC")->fetchAll();
}

function obtenerConfigGeneral($clave) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT valor FROM config_general WHERE clave = ? LIMIT 1");
    $stmt->execute([$clave]);
    $row = $stmt->fetch();
    return $row['valor'] ?? null;
}

function calcularPrecioCiclo($precioMensual, $ciclo) {
    global $CICLOS;
    if (!isset($CICLOS[$ciclo])) $ciclo = 'mensual';
    $meses = $CICLOS[$ciclo]['meses'];
    $descuento = $CICLOS[$ciclo]['descuento'];
    $subtotal = $precioMensual * $meses;
    return round($subtotal * (1 - $descuento), 2);
}

function calcularFechaRenovacion($ciclo, $desde = null) {
    if (!$desde) $desde = date('Y-m-d H:i:s');
    return match($ciclo) {
        'trimestral' => date('Y-m-d H:i:s', strtotime('+3 months', strtotime($desde))),
        'anual' => date('Y-m-d H:i:s', strtotime('+1 year', strtotime($desde))),
        default => date('Y-m-d H:i:s', strtotime('+1 month', strtotime($desde))),
    };
}

function generarNumeroFactura() {
    global $pdo;
    $prefijo = 'NX-' . date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM facturas WHERE creado_en >= '" . date('Y-01-01') . " 00:00:00'");
    $conteo = intval($stmt->fetchColumn()) + 1;
    return $prefijo . str_pad($conteo, 5, '0', STR_PAD_LEFT);
}

function crearFactura($usuarioId, $pedidoId, $concepto, $total, $vencimiento = null) {
    global $pdo;
    if (!$vencimiento) $vencimiento = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    $stmt = $pdo->prepare("INSERT INTO facturas (usuario_id, pedido_id, numero, concepto, total, estado, vencimiento) VALUES (?, ?, ?, ?, ?, 'pendiente', ?)");
    $stmt->execute([$usuarioId, $pedidoId, generarNumeroFactura(), $concepto, $total, $vencimiento]);
    return $pdo->lastInsertId();
}

function obtenerFactura($facturaId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT f.*, u.nombre as usuario_nombre, u.email as usuario_email, u.telefono, u.pais FROM facturas f JOIN usuarios u ON f.usuario_id = u.id WHERE f.id = ? LIMIT 1");
    $stmt->execute([$facturaId]);
    return $stmt->fetch();
}

function obtenerFacturasUsuario($usuarioId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM facturas WHERE usuario_id = ? ORDER BY creado_en DESC");
    $stmt->execute([$usuarioId]);
    return $stmt->fetchAll();
}

function registrarTransaccion($datos) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO transacciones 
        (usuario_id, factura_id, pedido_id, metodo_pago, monto, moneda, estado, referencia_externa, token_pago, datos_pago, notas) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $datos['usuario_id'],
        $datos['factura_id'] ?? null,
        $datos['pedido_id'] ?? null,
        $datos['metodo_pago'],
        $datos['monto'],
        $datos['moneda'] ?? 'USD',
        $datos['estado'] ?? 'pendiente',
        $datos['referencia_externa'] ?? null,
        $datos['token_pago'] ?? null,
        $datos['datos_pago'] ?? null,
        $datos['notas'] ?? null,
    ]);
    return $pdo->lastInsertId();
}

function procesarPagoCompletado($transaccionId, $datosAdicionales = []) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM transacciones WHERE id = ? LIMIT 1");
    $stmt->execute([$transaccionId]);
    $transaccion = $stmt->fetch();
    
    if (!$transaccion || $transaccion['estado'] === 'completado') {
        return false;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Actualizar transacción
        $stmt = $pdo->prepare("UPDATE transacciones SET estado = 'completado', datos_pago = ?, actualizado_en = NOW() WHERE id = ?");
        $stmt->execute([json_encode($datosAdicionales), $transaccionId]);
        
        // Actualizar factura
        if ($transaccion['factura_id']) {
            $stmt = $pdo->prepare("UPDATE facturas SET estado = 'pagada', pagado_en = NOW() WHERE id = ?");
            $stmt->execute([$transaccion['factura_id']]);
        }
        
        // Activar/renovar pedido
        if ($transaccion['pedido_id']) {
            $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? LIMIT 1");
            $stmt->execute([$transaccion['pedido_id']]);
            $pedido = $stmt->fetch();
            
            if ($pedido) {
                $fechaAnterior = $pedido['fecha_renovacion'];
                $nuevaFecha = calcularFechaRenovacion($pedido['ciclo'], $fechaAnterior);
                
                $stmt = $pdo->prepare("UPDATE pedidos SET estado = 'activo', fecha_renovacion = ? WHERE id = ?");
                $stmt->execute([$nuevaFecha, $transaccion['pedido_id']]);
                
                // Registrar renovación
                $stmt = $pdo->prepare("INSERT INTO renovaciones (pedido_id, factura_id, transaccion_id, ciclo, monto, fecha_renovacion_anterior, fecha_renovacion_nueva) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $transaccion['pedido_id'],
                    $transaccion['factura_id'],
                    $transaccionId,
                    $pedido['ciclo'],
                    $transaccion['monto'],
                    $fechaAnterior,
                    $nuevaFecha
                ]);
            }
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// Estadísticas financieras para admin
function obtenerEstadisticasFinancieras($periodo = 'mes') {
    global $pdo;
    
    $where = "WHERE estado = 'completado'";
    if ($periodo === 'mes') {
        $where .= " AND creado_en >= '" . date('Y-m-01') . " 00:00:00'";
    } elseif ($periodo === 'hoy') {
        $where .= " AND DATE(creado_en) = CURDATE()";
    } elseif ($periodo === 'anio') {
        $where .= " AND creado_en >= '" . date('Y-01-01') . " 00:00:00'";
    }
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(monto), 0) as total, COUNT(*) as cantidad FROM transacciones $where");
    $stmt->execute();
    return $stmt->fetch();
}

function obtenerGananciasPorMes($limite = 12) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(creado_en, '%Y-%m') as mes, COALESCE(SUM(monto), 0) as total, COUNT(*) as cantidad 
                           FROM transacciones 
                           WHERE estado = 'completado' 
                           GROUP BY mes 
                           ORDER BY mes DESC 
                           LIMIT ?");
    $stmt->execute([$limite]);
    return $stmt->fetchAll();
}

function obtenerMetodosPagoPopulares() {
    global $pdo;
    return $pdo->query("SELECT metodo_pago, COUNT(*) as cantidad, COALESCE(SUM(monto), 0) as total 
                        FROM transacciones 
                        WHERE estado = 'completado' 
                        GROUP BY metodo_pago 
                        ORDER BY total DESC")->fetchAll();
}

function obtenerTransacciones($limit = 100) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT t.*, u.nombre as usuario_nombre, f.numero as factura_numero 
                           FROM transacciones t 
                           JOIN usuarios u ON t.usuario_id = u.id 
                           LEFT JOIN facturas f ON t.factura_id = f.id 
                           ORDER BY t.creado_en DESC 
                           LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function nombrePasarelaAmigable($pasarela) {
    $nombres = [
        'stripe' => '💳 Stripe',
        'paypal' => '💵 PayPal',
        'mercadopago' => '💰 MercadoPago',
        'transferencia' => '🏦 Transferencia bancaria',
        'tarjeta' => '💳 Tarjeta',
        'cripto' => '₿ Criptomonedas',
        'manual' => '⚙️ Manual',
    ];
    return $nombres[$pasarela] ?? ucfirst($pasarela);
}
