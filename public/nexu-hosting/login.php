<?php
$titulo = 'Iniciar sesión';
require_once 'includes/funciones.php';

if (estaLogueado()) {
    redirigir('panel.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        setMensaje('Por favor completa todos los campos.', 'danger');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($password, $usuario['password'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            
            setMensaje('Bienvenido de nuevo, ' . $usuario['nombre'] . '!', 'success');
            redirigir('panel.php');
        } else {
            setMensaje('Correo o contraseña incorrectos.', 'danger');
        }
    }
    
    redirigir('login.php');
}

require_once 'includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <h1>Iniciar sesión</h1>
        <p>Accede a tu panel de cliente</p>
        
        <?php echo mostrarMensaje(); ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" class="form-control" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Iniciar sesión</button>
        </form>
        
        <div class="auth-footer">
            <p>¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a></p>
            <p style="margin-top: 0.5rem;"><a href="recuperar.php">¿Olvidaste tu contraseña?</a></p>
            <p style="margin-top: 0.5rem; font-size: 0.85rem;"><a href="index.php">← Volver al inicio</a></p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
