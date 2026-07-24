<?php
/**
 * Nexu Hosting - Instalador Web
 * Accede a este archivo una sola vez para crear la base de datos automáticamente.
 * Elimínalo después de la instalación por seguridad.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nexuhosting');

$paso = $_GET['paso'] ?? 1;
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($paso == 1) {
        $host = $_POST['host'] ?? 'localhost';
        $user = $_POST['user'] ?? 'root';
        $pass = $_POST['pass'] ?? '';
        $name = $_POST['name'] ?? 'nexuhosting';
        
        try {
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS $name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Guardar en sesión temporal
            session_start();
            $_SESSION['install_host'] = $host;
            $_SESSION['install_user'] = $user;
            $_SESSION['install_pass'] = $pass;
            $_SESSION['install_name'] = $name;
            
            redirigir('instalar.php?paso=2');
        } catch (PDOException $e) {
            $error = 'Error de conexión: ' . $e->getMessage();
        }
    }
    
    if ($paso == 2) {
        session_start();
        $host = $_SESSION['install_host'] ?? 'localhost';
        $user = $_SESSION['install_user'] ?? 'root';
        $pass = $_SESSION['install_pass'] ?? '';
        $name = $_SESSION['install_name'] ?? 'nexuhosting';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = file_get_contents('sql/nexuhosting.sql');
            
            // Eliminar USE database para evitar conflictos
            $sql = preg_replace('/^USE\s+\w+;\s*$/m', '', $sql);
            
            $pdo->exec($sql);
            $exito = 'Base de datos instalada correctamente.';
            
            // Actualizar archivo db.php
            $dbConfig = "<?php
\ndefine('DB_HOST', '$host');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_NAME', '$name');
\ntry {
    \$pdo = new PDO(
        \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException \$e) {
    die(\"Error de conexión a la base de datos: \" . \$e->getMessage());
}
";
            file_put_contents('includes/db.php', $dbConfig);
            $exito .= ' Archivo includes/db.php actualizado.';
            
        } catch (PDOException $e) {
            $error = 'Error al instalar: ' . $e->getMessage();
        }
    }
}

function redirigir($url) {
    header("Location: $url");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador | Nexu Hosting</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <section class="auth-page">
        <div class="auth-card">
            <h1>Instalador Nexu Hosting</h1>
            <p>Paso <?php echo $paso; ?> de 2</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($exito): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($exito); ?></div>
                <p class="text-center text-muted">Ahora puedes <a href="index.php">ir al inicio</a> o <a href="login.php">iniciar sesión</a>.</p>
                <p class="text-center text-muted" style="font-size: 0.85rem; color: var(--color-danger);">⚠️ Elimina el archivo instalar.php por seguridad.</p>
            <?php elseif ($paso == 1): ?>
                <form action="instalar.php?paso=1" method="POST">
                    <div class="form-group">
                        <label>Servidor MySQL</label>
                        <input type="text" name="host" class="form-control" value="localhost" required>
                    </div>
                    <div class="form-group">
                        <label>Usuario MySQL</label>
                        <input type="text" name="user" class="form-control" value="root" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña MySQL</label>
                        <input type="password" name="pass" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Nombre de la base de datos</label>
                        <input type="text" name="name" class="form-control" value="nexuhosting" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Crear base de datos</button>
                </form>
            <?php elseif ($paso == 2): ?>
                <form action="instalar.php?paso=2" method="POST">
                    <p class="text-muted mb-3">Se importarán las tablas, datos iniciales, planes, admin y configuración. Haz clic en instalar para continuar.</p>
                    <button type="submit" class="btn btn-primary btn-block">Instalar base de datos</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>
