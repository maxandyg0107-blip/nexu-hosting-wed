<?php
$titulo = 'Noticias';
require_once 'includes/header.php';

$noticias = obtenerNoticias();
?>

<section class="hero" style="padding: 140px 0 80px;">
    <div class="container text-center">
        <span class="hero-badge">📰</span>
        <h1>Noticias y <span>Anuncios</span></h1>
        <p class="text-muted">Mantente al día con las últimas novedades de Nexu Hosting.</p>
    </div>
</section>

<section class="section" style="padding-top: 40px;">
    <div class="container">
        <div class="features-grid">
            <?php foreach ($noticias as $noticia): ?>
            <article class="card">
                <div class="flex justify-between" style="margin-bottom: 1rem;">
                    <?php if ($noticia['destacada']): ?>
                        <span class="badge badge-warning">Destacada</span>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($noticia['creado_en'])); ?></small>
                </div>
                <h3><?php echo e($noticia['titulo']); ?></h3>
                <p class="text-muted mb-3"><?php echo e(substr(strip_tags($noticia['contenido']), 0, 150)) . '...'; ?></p>
                <a href="noticia.php?id=<?php echo $noticia['id']; ?>" class="btn btn-outline btn-sm">Leer más</a>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
