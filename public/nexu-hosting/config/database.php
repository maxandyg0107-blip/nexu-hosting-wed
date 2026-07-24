<?php
/**
 * NEXU HOSTING - Singleton de Conexión PDO
 * Una sola instancia de PDO para toda la request.
 * Usa prepared statements. Nunca exponer errores al usuario final.
 */

require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Retorna la instancia PDO compartida.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,   // prepared statements reales
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Log interno — NUNCA mostrar detalles al usuario
                error_log('[NEXU DB ERROR] ' . $e->getMessage());

                if (APP_DEBUG) {
                    throw $e; // en dev sí mostramos
                }

                // En producción, pantalla amigable
                http_response_code(503);
                die('El servicio no está disponible temporalmente. Por favor intenta más tarde.');
            }
        }

        return self::$instance;
    }

    /**
     * Atajo global: db() devuelve el PDO.
     */
    public static function pdo(): PDO
    {
        return self::getInstance();
    }
}

/**
 * Función helper global para evitar Database::pdo() en cada archivo.
 * Uso: $stmt = db()->prepare("SELECT ...");
 */
function db(): PDO
{
    return Database::pdo();
}
