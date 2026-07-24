<?php
/**
 * logout.php
 * Cierra la sesión activa de forma segura y redirige al login.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$auth = new AuthController();
$auth->logout();
