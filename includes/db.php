<?php
/**
 * Nexu Hosting - Compatibilidad para el código legado.
 * Las credenciales se leen desde config/config.php y variables de entorno.
 */

require_once dirname(__DIR__) . '/config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]
    );
} catch (PDOException $e) {
    error_log('[NEXU DB ERROR] ' . $e->getMessage());
    http_response_code(503);
    exit('El servicio no está disponible temporalmente.');
}
