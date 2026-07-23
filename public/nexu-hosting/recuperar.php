<?php
$titulo = 'Recuperar contraseña';
require_once 'includes/funciones.php';

if (estaLogueado()) {
    redirigir('panel.php');
}

$mensaje = '';
$tipo = 'info';
$mostrarFormulario = true;
$token = $_GET['token'] ?? null;

// Procesar solicitud de recuperación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            // Generar token
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $pdo->prepare("INSERT INTO password_resets (usuario_id, token, expira_en) VALUES (?, ?, ?)");
            $stmt->execute([$usuario['id'], $token, $expira]);
            
            // En producción aquí enviarías el correo
            $link = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/recuperar.php?token=' . $token;
            
            $mensaje = 'Si el correo existe, recibirás instrucciones. (Modo demo: <a href="' . $link . '">click aquí para restablecer</a>)';
            $tipo = 'success';
            $mostrarFormulario = false;
        } else {
            // No revelar si existe o no
            $mensaje = 'Si el correo existe, recibirás instrucciones.';
            $tipo = 'info';
            $mostrarFormulario = false;
        }
    } else {
        $mensaje = 'Por favor ingresa un correo electrónico válido.';
        $tipo = 'danger';
    }
}

// Procesar nuevo password con token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_password']) && $token) {
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    
    if (strlen($password) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres.';
        $tipo = 'danger';
    } elseif ($password !== $password2) {
        $mensaje = 'Las contraseñas no coinciden.';
        $tipo = 'danger';
    } else {
        $stmt = $pdo->prepare("SELECT pr.*, u.id as user_id FROM password_resets pr JOIN usuarios u ON pr.usuario_id = u.id WHERE pr.token = ? AND pr.usado = 0 AND pr.expira_en > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if ($reset) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $reset['user_id']]);
            
            $stmt = $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE id = ?");
            $stmt->execute([$reset['id']]);
            
            $mensaje = 'Contraseña actualizada correctamente. Ahora puedes iniciar sesión.';
            $tipo = 'success';
            $mostrarFormulario = false;
            $token = null;
        } else {
            $mensaje = 'El enlace de recuperación es inválido o ha expirado.';
            $tipo = 'danger';
            $token = null;
        }
    }
}

require_once 'includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <h1>Recuperar contraseña</h1>
        <p>Recupera el acceso a tu cuenta</p>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo; ?>"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if ($token && $tipo !== 'success'): ?>
            <form action="recuperar.php?token=<?php echo e($token); ?>" method="POST">
                <input type="hidden" name="nuevo_password" value="1">
                <div class="form-group">
                    <label for="password">Nueva contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="password2">Confirmar nueva contraseña</label>
                    <input type="password" id="password2" name="password2" class="form-control" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Restablecer contraseña</button>
            </form>
        <?php elseif ($mostrarFormulario): ?>
            <form action="recuperar.php" method="POST">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Enviar instrucciones</button>
            </form>
        <?php endif; ?>
        
        <div class="auth-footer">
            <p><a href="login.php">← Volver al inicio de sesión</a></p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
