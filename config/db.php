<?php
/**
 * config/db.php
 * ---------------------------------------------------------------------
 * Capa de conexión a MySQL / MariaDB mediante PDO.
 * Implementa el patrón Singleton para reutilizar una única conexión
 * por ciclo de vida de la petición, forzando SIEMPRE el uso de
 * "prepared statements" (ver models/*.php) para neutralizar
 * inyecciones SQL.
 * ---------------------------------------------------------------------
 */

if (!defined('NEXU_APP')) {
    http_response_code(403);
    die('Acceso directo no permitido.');
}

final class Database
{
    private static ?PDO $instance = null;

    // Constructor privado: impide instanciar la clase desde fuera (Singleton)
    private function __construct()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                // Lanza excepciones PDOException en lugar de fallos silenciosos
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Devuelve arrays asociativos por defecto
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Usa prepared statements NATIVOS del driver (no emulados)
                // Esto es CRÍTICO para la prevención real de inyección SQL
                PDO::ATTR_EMULATE_PREPARES => false,
                // Persistencia desactivada: evita fugas de estado entre peticiones
                PDO::ATTR_PERSISTENT => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Nunca se expone el mensaje real de PDO al cliente final
                error_log('[DB CONNECTION ERROR] ' . $e->getMessage());
                http_response_code(500);
                die('El servicio no está disponible en este momento. Intenta más tarde.');
            }
        }

        return self::$instance;
    }

    // Evita la clonación y deserialización de la instancia (rompería el Singleton)
    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new \Exception('No se permite deserializar un Singleton.');
    }
}
