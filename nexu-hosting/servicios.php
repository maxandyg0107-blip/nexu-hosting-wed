<?php
$titulo = 'Servicios';
require_once 'includes/header.php';

$categoriaSlug = $_GET['cat'] ?? null;
$categorias = obtenerCategorias();
$planes = obtenerPlanes($categoriaSlug);
?>

<section class="hero" style="padding: 140px 0 80px;">
    <div class="container text-center">
        <span class="hero-badge">🎮</span>
        <h1>Nuestros <span>Servicios</span></h1>
        <p>Servidores de juegos optimizados para las plataformas más populares. Selecciona tu categoría y encuentra el plan perfecto.</p>
    </div>
</section>

<section class="section" style="padding-top: 40px;">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Categorías</span>
            <h2>Selecciona tu <span>juego</span></h2>
        </div>
        
        <div class="features-grid mb-4">
            <?php foreach ($categorias as $cat): ?>
            <a href="servicios.php?cat=<?php echo e($cat['slug']); ?>" class="card" style="text-decoration: none;">
                <div class="card-icon">🎮</div>
                <h3><?php echo e($cat['nombre']); ?></h3>
                <p><?php echo e($cat['descripcion']); ?></p>
            </a>
            <?php endforeach; ?>
        </div>
        
        <?php if ($categoriaSlug): ?>
            <?php
            $categoriaActual = null;
            foreach ($categorias as $cat) {
                if ($cat['slug'] === $categoriaSlug) {
                    $categoriaActual = $cat;
                    break;
                }
            }
            ?>
            <div class="section-header" style="margin-top: 4rem;">
                <span class="section-label">Planes</span>
                <h2><?php echo e($categoriaActual['nombre'] ?? 'Planes'); ?></h2>
                <p><?php echo e($categoriaActual['descripcion'] ?? 'Elige el plan que mejor se adapte a tus necesidades.'); ?></p>
            </div>
            
            <?php if (count($planes) > 0): ?>
            <div class="pricing-grid">
                <?php foreach ($planes as $plan):
                    $caracteristicas = json_decode($plan['caracteristicas'] ?? '[]', true);
                ?>
                <div class="pricing-card <?php echo $plan['popular'] ? 'popular' : ''; ?>">
                    <?php if ($plan['popular']): ?>
                        <span class="popular-badge">MÁS POPULAR</span>
                    <?php endif; ?>
                    <h3><?php echo e($plan['nombre']); ?></h3>
                    <p class="category"><?php echo e($plan['categoria_nombre']); ?></p>
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
            <?php else: ?>
                <div class="alert alert-info text-center">No hay planes disponibles en esta categoría actualmente.</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<section class="section" style="background: var(--color-bg-light);">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Características incluidas</span>
            <h2>Todos nuestros servicios <span>incluyen</span></h2>
        </div>
        <div class="features-grid">
            <div class="card">
                <div class="card-icon">⚡</div>
                <h3>Setup instantáneo</h3>
                <p>Tu servidor se activa automáticamente tras la confirmación del pago.</p>
            </div>
            <div class="card">
                <div class="card-icon">🛡️</div>
                <h3>Anti-DDoS</h3>
                <p>Protección contra ataques DDoS en todas nuestras ubicaciones.</p>
            </div>
            <div class="card">
                <div class="card-icon">🔄</div>
                <h3>Backups</h3>
                <p>Copias de seguridad programadas para proteger tus mundos y configuraciones.</p>
            </div>
            <div class="card">
                <div class="card-icon">📊</div>
                <h3>Monitoreo 24/7</h3>
                <p>Supervisión constante del rendimiento y estado de tu servidor.</p>
            </div>
            <div class="card">
                <div class="card-icon">🔧</div>
                <h3>Soporte técnico</h3>
                <p>Asistencia experta por ticket y chat en todo momento.</p>
            </div>
            <div class="card">
                <div class="card-icon">🌍</div>
                <h3>Ubicaciones globales</h3>
                <p>Servidores en múltiples regiones para la mejor latencia.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
