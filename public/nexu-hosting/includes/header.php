<?php
require_once __DIR__ . '/funciones.php';

// Detectar página actual
$paginaActual = basename($_SERVER['PHP_SELF'], '.php');
$usuario = usuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Nexu Hosting - Servidores de juegos 24/7 con alto rendimiento, panel Pterodactyl y soporte 24/7.">
    <title><?php echo isset($titulo) ? e($titulo) . ' | ' : ''; ?>Nexu Hosting</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="index.php" class="logo">
                <div class="logo-icon">⚡</div>
                <span>Nexu Hosting</span>
            </a>
            
            <nav class="nav">
                <a href="index.php" class="<?php echo $paginaActual === 'index' ? 'active' : ''; ?>">Inicio</a>
                <a href="servicios.php" class="<?php echo $paginaActual === 'servicios' ? 'active' : ''; ?>">Servicios</a>
                <a href="planes.php" class="<?php echo $paginaActual === 'planes' ? 'active' : ''; ?>">Planes</a>
                <a href="noticias.php" class="<?php echo $paginaActual === 'noticias' ? 'active' : ''; ?>">Noticias</a>
                <a href="faq.php" class="<?php echo $paginaActual === 'faq' ? 'active' : ''; ?>">FAQ</a>
                <a href="contacto.php" class="<?php echo $paginaActual === 'contacto' ? 'active' : ''; ?>">Contacto</a>
            </nav>
            
            <div class="header-actions">
                <?php if (estaLogueado()): ?>
                    <a href="panel.php" class="btn btn-secondary btn-sm">Panel</a>
                    <a href="logout.php" class="btn btn-outline btn-sm">Salir</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary btn-sm">Iniciar sesión</a>
                    <a href="register.php" class="btn btn-primary btn-sm">Registrarse</a>
                <?php endif; ?>
                <button class="mobile-menu-btn" aria-label="Menú" aria-expanded="false">☰</button>
            </div>
        </div>
    </header>
