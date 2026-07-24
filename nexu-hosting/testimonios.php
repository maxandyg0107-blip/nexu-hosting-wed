<?php
$titulo = 'Testimonios';
require_once 'includes/header.php';

// Procesar nuevo testimonio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $pais = trim($_POST['pais'] ?? '');
    $estrellas = intval($_POST['estrellas'] ?? 5);
    $comentario = trim($_POST['comentario'] ?? '');
    
    if (empty($nombre) || empty($comentario)) {
        setMensaje('Por favor completa todos los campos obligatorios.', 'danger');
    } elseif ($estrellas < 1 || $estrellas > 5) {
        setMensaje('La calificación debe estar entre 1 y 5 estrellas.', 'danger');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO testimonios (nombre, pais, estrellas, comentario) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $pais, $estrellas, $comentario]);
            setMensaje('¡Gracias por tu reseña! Será revisada antes de publicarse.', 'success');
        } catch (PDOException $e) {
            setMensaje('Error al enviar tu reseña. Intenta más tarde.', 'danger');
        }
    }
    redirigir('testimonios.php');
}

$testimonios = obtenerTestimonios();
?>

<section class="hero" style="padding: 140px 0 80px;">
    <div class="container text-center">
        <span class="hero-badge">⭐</span>
        <h1>Lo que dicen <span>nuestros clientes</span></h1>
        <p class="text-muted">Más de 100 reseñas positivas respaldan nuestro servicio.</p>
    </div>
</section>

<section class="section" style="padding-top: 40px;">
    <div class="container">
        <?php echo mostrarMensaje(); ?>
        
        <div class="features-grid mb-4">
            <?php foreach ($testimonios as $testimonio): ?>
            <div class="card">
                <div style="margin-bottom: 1rem;">
                    <?php echo estrellasHtml($testimonio['estrellas']); ?>
                </div>
                <p class="text-muted mb-3" style="font-style: italic;">"<?php echo e($testimonio['comentario']); ?>"</p>
                <div class="flex justify-between" style="align-items: center;">
                    <strong><?php echo e($testimonio['nombre']); ?></strong>
                    <small class="text-muted"><?php echo e($testimonio['pais'] ?: 'Cliente'); ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card" style="max-width: 700px; margin: 0 auto;">
            <h3>Deja tu reseña</h3>
            <form action="testimonios.php" method="POST">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>País (opcional)</label>
                        <input type="text" name="pais" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Calificación</label>
                    <select name="estrellas" class="form-control">
                        <option value="5">⭐⭐⭐⭐⭐ Excelente</option>
                        <option value="4">⭐⭐⭐⭐ Muy bueno</option>
                        <option value="3">⭐⭐⭐ Bueno</option>
                        <option value="2">⭐⭐ Regular</option>
                        <option value="1">⭐ Malo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tu experiencia</label>
                    <textarea name="comentario" class="form-control" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Enviar reseña</button>
            </form>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
