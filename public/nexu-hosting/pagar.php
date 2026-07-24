<?php
$titulo = 'Pagar factura';
require_once 'includes/funciones.php';

if (!estaLogueado()) {
    setMensaje('Debes iniciar sesión para pagar.', 'danger');
    redirigir('login.php');
}

$facturaId = intval($_GET['factura'] ?? 0);
$factura = obtenerFacturaCompleta($facturaId);

if (!$factura || $factura['usuario_id'] != $_SESSION['usuario_id']) {
    setMensaje('Factura no encontrada.', 'danger');
    redirigir('panel.php?seccion=facturas');
}

if ($factura['estado'] === 'pagada') {
    setMensaje('Esta factura ya está pagada.', 'info');
    redirigir('panel.php?seccion=facturas');
}

$pasarelas = obtenerPasarelasActivas();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodo = $_POST['metodo_pago'] ?? 'transferencia';
    $config = obtenerConfigPago($metodo);
    
    // Crear transacción pendiente
    $transaccionId = registrarTransaccion([
        'usuario_id' => $_SESSION['usuario_id'],
        'factura_id' => $factura['id'],
        'pedido_id' => $factura['pedido_id'],
        'metodo_pago' => $metodo,
        'monto' => $factura['total'],
        'moneda' => $factura['moneda'] ?? 'USD',
        'estado' => 'pendiente',
        'notas' => 'Pago iniciado desde portal de cliente',
    ]);
    
    // Si es transferencia/manual, queda pendiente de aprobación
    if ($metodo === 'transferencia' || $metodo === 'manual') {
        setMensaje('Hemos registrado tu pago. El equipo lo verificará en breve.', 'warning');
        redirigir('panel.php?seccion=facturas');
    }
    
    // Para Stripe, PayPal, MercadoPago se redirige a su pasarela (simulado aquí)
    // En producción aquí iría la integración real con la API
    setMensaje('Redirigiendo a ' . nombrePasarelaAmigable($metodo) . '... (Modo demo: pago simulado pendiente de confirmación)', 'info');
    redirigir('panel.php?seccion=facturas');
}

require_once 'includes/header.php';
?>

<section class="hero" style="padding: 140px 0 80px;">
    <div class="container">
        <span class="hero-badge">💳</span>
        <h1>Pagar <span>factura</span></h1>
        <p class="text-muted">Factura #<?php echo e($factura['numero']); ?></p>
    </div>
</section>

<section class="section" style="padding-top: 40px;">
    <div class="container">
        <div class="grid grid-2">
            <div class="card">
                <h3>Resumen de factura</h3>
                <p><strong>Número:</strong> <?php echo e($factura['numero']); ?></p>
                <p><strong>Concepto:</strong> <?php echo e($factura['concepto']); ?></p>
                <p><strong>Fecha de emisión:</strong> <?php echo date('d/m/Y', strtotime($factura['creado_en'])); ?></p>
                <p><strong>Vencimiento:</strong> <?php echo $factura['vencimiento'] ? date('d/m/Y', strtotime($factura['vencimiento'])) : '—'; ?></p>
                <p><strong>Estado:</strong> <?php echo badgeEstado($factura['estado']); ?></p>
                <hr style="border-color: var(--color-border); margin: 1.5rem 0;">
                <div class="flex justify-between" style="align-items: center;">
                    <span>Total a pagar:</span>
                    <strong style="font-size: 2rem; color: var(--color-success);"><?php echo formatoPrecio($factura['total']); ?></strong>
                </div>
            </div>
            
            <div class="card">
                <h3>Método de pago</h3>
                <?php echo mostrarMensaje(); ?>
                
                <?php if (count($pasarelas) > 0): ?>
                <form action="pagar.php?factura=<?php echo $factura['id']; ?>" method="POST">
                    <div class="form-group">
                        <?php foreach ($pasarelas as $pasarela): ?>
                        <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: var(--color-bg-light); border: 1px solid var(--color-border); border-radius: var(--radius-sm); margin-bottom: 0.75rem; cursor: pointer;">
                            <input type="radio" name="metodo_pago" value="<?php echo e($pasarela['pasarela']); ?>" <?php echo $pasarela['pasarela'] === 'transferencia' ? 'checked' : ''; ?>>
                            <span><?php echo nombrePasarelaAmigable($pasarela['pasarela']); ?></span>
                            <?php if ($pasarela['modo_sandbox']): ?>
                                <span class="badge badge-warning" style="margin-left: auto;">Sandbox</span>
                            <?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="alert alert-info" style="font-size: 0.9rem;">
                        💡 En producción se conectará con la API real del método seleccionado. Actualmente el admin debe confirmar los pagos manuales desde el panel.
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Confirmar pago</button>
                </form>
                <?php else: ?>
                    <div class="alert alert-warning">No hay pasarelas de pago activas. Contacta a soporte.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
