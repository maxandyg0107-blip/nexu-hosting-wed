<?php
$titulo = 'Contacto';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/db.php';
    
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');
    
    if (empty($nombre) || empty($email) || empty($asunto) || empty($mensaje)) {
        setMensaje('Por favor completa todos los campos.', 'danger');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setMensaje('Por favor ingresa un correo electrónico válido.', 'danger');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO contactos (nombre, email, asunto, mensaje) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $email, $asunto, $mensaje]);
            setMensaje('¡Mensaje enviado correctamente! Te responderemos lo antes posible.', 'success');
        } catch (PDOException $e) {
            setMensaje('Error al enviar el mensaje. Por favor intenta más tarde.', 'danger');
        }
    }
    
    redirigir('contacto.php');
}
?>

<section class="hero" style="padding: 140px 0 80px;">
    <div class="container text-center">
        <span class="hero-badge">📞</span>
        <h1>Contacta con <span>Nosotros</span></h1>
        <p>¿Tienes dudas? Escríbenos y nuestro equipo te responderá en menos de 1 hora.</p>
    </div>
</section>

<section class="section" style="padding-top: 40px;">
    <div class="container">
        <div class="grid grid-2">
            <div class="card">
                <h3>Información de contacto</h3>
                <p class="text-muted mb-3">Estamos disponibles para ayudarte por múltiples canales.</p>
                
                <div style="margin-bottom: 1.5rem;">
                    <strong>📧 Email</strong>
                    <p class="text-muted">soporte@nexuhosting.com</p>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <strong>💬 Discord</strong>
                    <p class="text-muted">discord.gg/nexuhosting</p>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <strong>🕐 Horario de atención</strong>
                    <p class="text-muted">Soporte 24/7 por tickets</p>
                </div>
                
                <div>
                    <strong>📍 Ubicaciones</strong>
                    <p class="text-muted">Norteamérica · Europa · Sudamérica</p>
                </div>
            </div>
            
            <div class="card">
                <h3>Enviar mensaje</h3>
                <?php echo mostrarMensaje(); ?>
                <form action="contacto.php" method="POST">
                    <div class="form-group">
                        <label for="nombre">Nombre completo</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="asunto">Asunto</label>
                        <select id="asunto" name="asunto" class="form-control" required>
                            <option value="">Selecciona un asunto</option>
                            <option value="Soporte técnico">Soporte técnico</option>
                            <option value="Ventas">Ventas</option>
                            <option value="Facturación">Facturación</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="mensaje">Mensaje</label>
                        <textarea id="mensaje" name="mensaje" class="form-control" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Enviar mensaje</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
