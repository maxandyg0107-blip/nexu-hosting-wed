<?php
require_once 'includes/funciones.php';

session_destroy();
setMensaje('Has cerrado sesión correctamente.', 'info');
redirigir('index.php');
