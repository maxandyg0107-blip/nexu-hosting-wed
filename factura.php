<?php
require_once 'includes/funciones.php';

if (!estaLogueado()) {
    redirigir('login.php');
    exit;
}

$facturaId = intval($_GET['id'] ?? 0);
$factura = obtenerFacturaCompleta($facturaId);

$usuarioSesionId = (int)($_SESSION['usuario_id'] ?? 0);

if (!$factura || ((int)$factura['usuario_id'] !== $usuarioSesionId && !esAdmin())) {
    setMensaje('Factura no encontrada.', 'danger');
    redirigir('panel.php?seccion=facturas');
    exit;
}

$empresa = [
    'nombre' => obtenerConfigGeneral('nombre_empresa') ?: 'Nexu Hosting',
    'direccion' => obtenerConfigGeneral('direccion_empresa') ?: '',
    'email' => obtenerConfigGeneral('email_facturacion') ?: 'facturas@nexuhosting.com',
    'telefono' => obtenerConfigGeneral('telefono_empresa') ?: '',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura #<?php echo e($factura['numero']); ?> | Nexu Hosting</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; color: black; }
            .factura-container { box-shadow: none; border: none; }
        }
        .factura-container {
            max-width: 800px;
            margin: 2rem auto;
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            padding: 2.5rem;
        }
        .factura-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--color-border);
        }
        .factura-tabla {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
        }
        .factura-tabla th, .factura-tabla td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--color-border);
        }
        .factura-total {
            text-align: right;
            margin-top: 2rem;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container" style="padding-top: 2rem;">
        <div class="no-print" style="margin-bottom: 1rem;">
            <a href="panel.php?seccion=facturas" class="btn btn-secondary">← Volver</a>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir / Guardar PDF</button>
        </div>
        
        <div class="factura-container">
            <div class="factura-header">
                <div>
                    <h1 style="margin-bottom: 0.5rem;"><?php echo e($empresa['nombre']); ?></h1>
                    <p class="text-muted"><?php echo nl2br(e($empresa['direccion'])); ?></p>
                    <p class="text-muted"><?php echo e($empresa['email']); ?></p>
                    <p class="text-muted"><?php echo e($empresa['telefono']); ?></p>
                </div>
                <div style="text-align: right;">
                    <h2>FACTURA</h2>
                    <p><strong>#<?php echo e($factura['numero']); ?></strong></p>
                    <p class="text-muted">Fecha: <?php echo date('d/m/Y', strtotime($factura['creado_en'])); ?></p>
                    <p class="text-muted">Vencimiento: <?php echo $factura['vencimiento'] ? date('d/m/Y', strtotime($factura['vencimiento'])) : '—'; ?></p>
                    <p>Estado: <?php echo badgeEstado($factura['estado']); ?></p>
                </div>
            </div>
            
            <div style="margin-bottom: 2rem;">
                <h3>Facturar a:</h3>
                <p><strong><?php echo e($factura['usuario_nombre']); ?></strong></p>
                <p class="text-muted"><?php echo e($factura['usuario_email']); ?></p>
                <?php if ($factura['telefono']): ?><p class="text-muted">Tel: <?php echo e($factura['telefono']); ?></p><?php endif; ?>
                <?php if ($factura['pais']): ?><p class="text-muted"><?php echo e($factura['pais']); ?></p><?php endif; ?>
                <?php if ($factura['direccion']): ?><p class="text-muted"><?php echo nl2br(e($factura['direccion'])); ?></p><?php endif; ?>
            </div>
            
            <table class="factura-tabla">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo e($factura['concepto']); ?></td>
                        <td style="text-align: right;"><?php echo formatoPrecio($factura['total']); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="factura-total">
                <p><strong>Total: <?php echo formatoPrecio($factura['total']); ?></strong></p>
            </div>
            
            <?php if ($factura['estado'] === 'pagada'): ?>
                <div class="alert alert-success text-center mt-4">
                    ✅ Factura pagada el <?php echo date('d/m/Y H:i', strtotime($factura['pagado_en'])); ?>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 3rem; text-align: center; color: var(--color-text-muted); font-size: 0.85rem;">
                <p>Gracias por confiar en <?php echo e($empresa['nombre']); ?>.</p>
            </div>
        </div>
    </div>
</body>
</html>