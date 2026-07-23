<?php
$titulo = 'Registrarse';
require_once 'includes/funciones.php';

if (estaLogueado()) {
    redirigir('panel.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $pais = trim($_POST['pais'] ?? '');
    
    if (empty($nombre) || empty($email) || empty($password)) {
        setMensaje('Por favor completa todos los campos obligatorios.', 'danger');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setMensaje('Por favor ingresa un correo electrónico válido.', 'danger');
    } elseif (strlen($password) < 6) {
        setMensaje('La contraseña debe tener al menos 6 caracteres.', 'danger');
    } elseif ($password !== $password2) {
        setMensaje('Las contraseñas no coinciden.', 'danger');
    } else {
        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            setMensaje('Este correo electrónico ya está registrado.', 'danger');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, telefono, pais) VALUES (?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$nombre, $email, $hash, $telefono, $pais]);
                setMensaje('Cuenta creada correctamente. Ahora puedes iniciar sesión.', 'success');
                redirigir('login.php');
            } catch (PDOException $e) {
                setMensaje('Error al crear la cuenta. Intenta más tarde.', 'danger');
            }
        }
    }
    
    redirigir('register.php');
}

require_once 'includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <h1>Crear cuenta</h1>
        <p>Únete a Nexu Hosting y gestiona tus servidores</p>
        
        <?php echo mostrarMensaje(); ?>
        
        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="nombre">Nombre completo</label>
                <input type="text" id="nombre" name="nombre" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="telefono">Teléfono (opcional)</label>
                <input type="tel" id="telefono" name="telefono" class="form-control">
            </div>
            <div class="form-group">
                <label for="pais">País (opcional)</label>
                <input type="text" id="pais" name="pais" class="form-control">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="6">
            </div>
            <div class="form-group">
                <label for="password2">Confirmar contraseña</label>
                <input type="password" id="password2" name="password2" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Crear cuenta</button>
        </form>
        
        <div class="auth-footer">
            <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión</a></p>
            <p style="margin-top: 0.5rem; font-size: 0.85rem;"><a href="index.php">← Volver al inicio</a></p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
