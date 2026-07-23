<?php
$titulo = 'Estado de Servicios';
require_once 'includes/header.php';

$servicios = obtenerEstadoServicios();

// Calcular estado global
$total = count($servicios);
$caidos = 0;
$mantenimiento = 0;
foreach ($servicios as $s) {
    if ($s['estado'] === 'caido') $caidos++;
    if ($s['estado'] === 'mantenimiento') $mantenimiento++;
}

if ($caidos > 0) {
    $estadoGlobal = 'caido';
    $mensajeGlobal = 'Algunos servicios experimentan problemas';
} elseif ($mantenimiento > 0) {
    $estadoGlobal = 'mantenimiento';
    $mensajeGlobal = 'Mantenimiento programado en curso';
} else {
    $estadoGlobal = 'operacional';
    $mensajeGlobal = 'Todos los sistemas operativos';
}
?>

<section class="hero" style="padding: 140px 0 80px;">
    <div class="container text-center">
        <span class="hero-badge">📊</span>
        <h1>Estado de <span>Servicios</span></h1>
        <p class="text-muted">Monitoreo en tiempo real de nuestra infraestructura.</p>
    </div>
</section>

<section class="section" style="padding-top: 40px;">
    <div class="container">
        <div class="card text-center mb-4" style="border-color: <?php echo $estadoGlobal === 'operacional' ? 'var(--color-success)' : ($estadoGlobal === 'mantenimiento' ? 'var(--color-warning)' : 'var(--color-danger)'); ?>">
            <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">
                <?php echo $estadoGlobal === 'operacional' ? '🟢' : ($estadoGlobal === 'mantenimiento' ? '🟡' : '🔴'); ?>
                <?php echo $mensajeGlobal; ?>
            </h2>
            <p class="text-muted">Última actualización: <?php echo date('H:i'); ?> UTC</p>
        </div>
        
        <div class="card">
            <h3 class="mb-3">Infraestructura</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Uptime</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicios as $servicio): ?>
                    <tr>
                        <td>
                            <strong><?php echo e($servicio['nombre']); ?></strong>
                        </td>
                        <td class="text-muted"><?php echo e($servicio['ubicacion'] ?: '—'); ?></td>
                        <td><?php echo badgeEstado($servicio['estado']); ?></td>
                        <td><?php echo $servicio['uptime']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="grid grid-3 mt-4">
            <div class="card text-center">
                <div class="card-icon" style="margin-left: auto; margin-right: auto;">🟢</div>
                <h3>Operacional</h3>
                <p class="text-muted">El servicio funciona correctamente.</p>
            </div>
            <div class="card text-center">
                <div class="card-icon" style="margin-left: auto; margin-right: auto;">🟡</div>
                <h3>Mantenimiento</h3>
                <p class="text-muted">Mantenimiento programado o intermitencia.</p>
            </div>
            <div class="card text-center">
                <div class="card-icon" style="margin-left: auto; margin-right: auto;">🔴</div>
                <h3>Caído</h3>
                <p class="text-muted">El servicio presenta problemas.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
