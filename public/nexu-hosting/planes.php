<?php
$titulo = 'Planes y Precios';
require_once 'includes/header.php';

$planSlug = $_GET['plan'] ?? null;

// Si se seleccionó un plan específico, mostrar página de contratación
if ($planSlug && $plan = obtenerPlan($planSlug)):
    $caracteristicas = json_decode($plan['caracteristicas'] ?? '[]', true);
    $ciclo = $_POST['ciclo'] ?? 'mensual';
    
    $precio = calcularPrecioCiclo($plan['precio_mensual'], $ciclo);
    $precioBase = match($ciclo) {
        'trimestral' => $plan['precio_mensual'] * 3,
        'anual' => $plan['precio_mensual'] * 12,
        default => $plan['precio_mensual'],
    };
    $ahorro = round($precioBase - $precio, 2);
?>

<section class="hero" style="padding: 140px 0 80px;">
    <div class="container">
        <div class="hero-badge">🛒 Contratación</div>
        <h1><?php echo e($plan['nombre']); ?></h1>
        <p class="text-muted"><?php echo e($plan['descripcion']); ?> | <?php echo e($plan['categoria_nombre']); ?></p>
    </div>
</section>

<section class="section" style="padding-top: 40px;">
    <div class="container">
        <div class="grid grid-2">
            <div class="card">
                <h3>Detalles del plan</h3>
                <div class="price" style="font-size: 2rem;"><?php echo formatoPrecio($plan['precio_mensual']); ?> <span>/mes</span></div>
                <ul class="mb-3">
                    <?php foreach ($caracteristicas as $caracteristica): ?>
                        <li style="padding: 0.5rem 0; color: var(--color-text-muted); display: flex; align-items: center; gap: 0.75rem;">
                            <span style="color: var(--color-success); font-weight: 700;">✓</span> <?php echo e($caracteristica); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($plan['ram_mb']): ?>
                    <p><strong>RAM:</strong> <?php echo $plan['ram_mb'] >= 1024 ? ($plan['ram_mb']/1024) . 'GB' : $plan['ram_mb'] . 'MB'; ?></p>
                <?php endif; ?>
                <?php if ($plan['cpu']): ?>
                    <p><strong>CPU:</strong> <?php echo e($plan['cpu']); ?></p>
                <?php endif; ?>
                <?php if ($plan['almacenamiento']): ?>
                    <p><strong>Almacenamiento:</strong> <?php echo e($plan['almacenamiento']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3>Completar pedido</h3>
                <?php echo mostrarMensaje(); ?>
                
                <?php if (!estaLogueado()): ?>
                    <div class="alert alert-info">
                        Debes <a href="login.php">iniciar sesión</a> o <a href="register.php">registrarte</a> para contratar este plan.
                    </div>
                <?php else: ?>
                    <form action="procesar_pedido.php" method="POST">
                        <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                        
                        <div class="form-group">
                            <label>Ciclo de facturación</label>
                            <select name="ciclo" class="form-control" id="ciclo" onchange="this.form.submit()">
                                <option value="mensual" <?php echo $ciclo === 'mensual' ? 'selected' : ''; ?>>Mensual - <?php echo formatoPrecio($plan['precio_mensual']); ?>/mes</option>
                                <option value="trimestral" <?php echo $ciclo === 'trimestral' ? 'selected' : ''; ?>>Trimestral - 5% descuento</option>
                                <option value="anual" <?php echo $ciclo === 'anual' ? 'selected' : ''; ?>>Anual - 15% descuento</option>
                            </select>
                        </div>
                        
                        <?php if ($ahorro > 0): ?>
                            <div class="alert alert-success" style="font-size: 0.9rem;">
                                💰 Ahorras <?php echo formatoPrecio($ahorro); ?> con el ciclo <?php echo $ciclo; ?>.
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Nombre del servidor / dominio (opcional)</label>
                            <input type="text" name="dominio" class="form-control" placeholder="ej. mi-servidor.nexu.host">
                        </div>
                        
                        <div class="form-group">
                            <label>Método de pago</label>
                            <select name="metodo_pago" class="form-control">
                                <option value="paypal">PayPal</option>
                                <option value="tarjeta">Tarjeta de crédito/débito</option>
                                <option value="cripto">Criptomonedas</option>
                                <option value="transferencia">Transferencia bancaria</option>
                            </select>
                        </div>
                        
                        <div style="background: var(--color-bg-light); padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem;">
                            <div class="flex justify-between">
                                <span>Total a pagar:</span>
                                <strong style="font-size: 1.25rem; color: var(--color-success);"><?php echo formatoPrecio($precio); ?></strong>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Confirmar pedido</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php else: // Mostrar todos los planes ?>

<section class="hero" style="padding: 140px 0 80px;">
    <div class="container text-center">
        <span class="hero-badge">💎</span>
        <h1>Planes y <span>Precios</span></h1>
        <p>Encuentra el plan perfecto para tu servidor de juegos. Todos incluyen recursos dedicados y soporte 24/7.</p>
    </div>
</section>

<section class="section" style="padding-top: 40px;">
    <div class="container">
        <?php
        $categorias = obtenerCategorias();
        foreach ($categorias as $categoria):
            $planesCat = obtenerPlanes($categoria['slug']);
            if (count($planesCat) === 0) continue;
        ?>
        <div class="mb-4">
            <div class="section-header" style="margin-bottom: 2rem;">
                <h2><?php echo e($categoria['nombre']); ?></h2>
                <p><?php echo e($categoria['descripcion']); ?></p>
            </div>
            <div class="pricing-grid">
                <?php foreach ($planesCat as $plan):
                    $caracteristicas = json_decode($plan['caracteristicas'] ?? '[]', true);
                ?>
                <div class="pricing-card <?php echo $plan['popular'] ? 'popular' : ''; ?>">
                    <?php if ($plan['popular']): ?>
                        <span class="popular-badge">MÁS POPULAR</span>
                    <?php endif; ?>
                    <h3><?php echo e($plan['nombre']); ?></h3>
                    <p class="category"><?php echo e($categoria['nombre']); ?></p>
                    <div class="price"><?php echo formatoPrecio($plan['precio_mensual']); ?> <span>/mes</span></div>
                    <ul>
                        <?php foreach ($caracteristicas as $caracteristica): ?>
                            <li><?php echo e($caracteristica); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="planes.php?plan=<?php echo e($plan['slug']); ?>" class="btn btn-primary btn-block">Contratar ahora</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
