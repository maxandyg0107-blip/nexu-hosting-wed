<?php
$titulo = 'Actualizar plan';
require_once 'includes/funciones.php';

if (!estaLogueado()) {
    setMensaje('Debes iniciar sesión.', 'danger');
    redirigir('login.php');
}

$pedidoId = intval($_GET['pedido'] ?? 0);

$stmt = $pdo->prepare("SELECT p.*, pl.nombre as plan_nombre, pl.categoria_id, c.nombre as categoria_nombre 
                       FROM pedidos p 
                       JOIN planes pl ON p.plan_id = pl.id 
                       JOIN categorias c ON pl.categoria_id = c.id 
                       WHERE p.id = ? AND p.usuario_id = ? AND p.estado = 'activo' LIMIT 1");
$stmt->execute([$pedidoId, $_SESSION['usuario_id']]);
$pedido = $stmt->fetch();

if (!$pedido) {
    setMensaje('Pedido no encontrado o no está activo.', 'danger');
    redirigir('panel.php?seccion=servicios');
}

// Obtener planes disponibles para upgrade (misma categoría, mayor precio o RAM)
$stmt = $pdo->prepare("SELECT * FROM planes WHERE categoria_id = ? AND activo = 1 AND (precio_mensual > ? OR ram_mb > ?) ORDER BY precio_mensual ASC");
$stmt->execute([$pedido['categoria_id'], $pedido['total'], $pedido['ram_mb']]);
$planesUpgrade = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoPlanId = intval($_POST['nuevo_plan'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT * FROM planes WHERE id = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$nuevoPlanId]);
    $nuevoPlan = $stmt->fetch();
    
    if (!$nuevoPlan) {
        setMensaje('Plan no válido.', 'danger');
    } else {
        // Crear factura por el upgrade (prorrateado al ciclo actual)
        $montoUpgrade = calcularPrecioCiclo($nuevoPlan['precio_mensual'], $pedido['ciclo']);
        $concepto = 'Upgrade a ' . $nuevoPlan['nombre'];
        $facturaId = crearFactura($_SESSION['usuario_id'], $pedidoId, $concepto, $montoUpgrade);
        
        // Actualizar pedido al nuevo plan
        $stmt = $pdo->prepare("UPDATE pedidos SET plan_id = ?, total = ?, ciclo = ? WHERE id = ?");
        $stmt->execute([$nuevoPlanId, $montoUpgrade, $pedido['ciclo'], $pedidoId]);
        
        setMensaje('Upgrade solicitado. Debes pagar la factura generada para aplicar el cambio.', 'success');
        redirigir('pagar.php?factura=' . $facturaId);
    }
    
    redirigir('upgrade.php?pedido=' . $pedidoId);
}

require_once 'includes/header.php';
?>

<section class="hero" style="padding: 140px 0 80px;">
    <div class="container">
        <span class="hero-badge">⬆️</span>
        <h1>Actualizar <span>plan</span></h1>
        <p class="text-muted">Mejora tu servidor <?php echo e($pedido['plan_nombre']); ?> de <?php echo e($pedido['categoria_nombre']); ?></p>
    </div>
</section>

<section class="section" style="padding-top: 40px;">
    <div class="container">
        <?php echo mostrarMensaje(); ?>
        
        <?php if (count($planesUpgrade) > 0): ?>
        <div class="pricing-grid">
            <?php foreach ($planesUpgrade as $plan):
                $caracteristicas = json_decode($plan['caracteristicas'] ?? '[]', true);
                $precioUpgrade = calcularPrecioCiclo($plan['precio_mensual'], $pedido['ciclo']);
            ?>
            <div class="card">
                <h3><?php echo e($plan['nombre']); ?></h3>
                <p class="text-muted"><?php echo e($pedido['categoria_nombre']); ?></p>
                <div class="price" style="font-size: 2rem;"><?php echo formatoPrecio($precioUpgrade); ?> <span>/<?php echo $pedido['ciclo']; ?></span></div>
                <ul class="mb-3">
                    <?php foreach ($caracteristicas as $caracteristica): ?>
                        <li style="padding: 0.5rem 0; color: var(--color-text-muted); display: flex; align-items: center; gap: 0.75rem;">
                            <span style="color: var(--color-success); font-weight: 700;">✓</span> <?php echo e($caracteristica); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <form action="upgrade.php?pedido=<?php echo $pedidoId; ?>" method="POST">
                    <input type="hidden" name="nuevo_plan" value="<?php echo $plan['id']; ?>">
                    <button type="submit" class="btn btn-primary btn-block">Seleccionar upgrade</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="alert alert-info text-center">No hay planes superiores disponibles para este servicio.</div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
