<?php
require_once 'includes/funciones.php';

$id = intval($_GET['id'] ?? 0);
$noticia = obtenerNoticia($id);

if (!$noticia) {
    setMensaje('Noticia no encontrada.', 'danger');
    redirigir('noticias.php');
}

$titulo = $noticia['titulo'];
require_once 'includes/header.php';
?>

<section class="hero" style="padding: 140px 0 80px;">
    <div class="container">
        <span class="hero-badge">📰 Noticia</span>
        <h1><?php echo e($noticia['titulo']); ?></h1>
        <p class="text-muted">Publicado el <?php echo date('d \d\e F \d\e Y', strtotime($noticia['creado_en'])); ?></p>
    </div>
</section>

<section class="section" style="padding-top: 40px;">
    <div class="container">
        <div class="card" style="max-width: 900px; margin: 0 auto;">
            <?php if ($noticia['imagen']): ?>
                <img src="<?php echo e($noticia['imagen']); ?>" alt="<?php echo e($noticia['titulo']); ?>" style="width: 100%; border-radius: var(--radius-sm); margin-bottom: 2rem;">
            <?php endif; ?>
            <div style="color: var(--color-text-muted); font-size: 1.1rem; line-height: 1.8;">
                <?php echo nl2br(e($noticia['contenido'])); ?>
            </div>
            <div class="mt-4">
                <a href="noticias.php" class="btn btn-secondary">← Volver a noticias</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
