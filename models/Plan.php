<?php
/**
 * models/Plan.php
 * ---------------------------------------------------------------------
 * Modelo de acceso a datos para la tabla `plans` (catálogo comercial).
 * ---------------------------------------------------------------------
 */

if (!defined('NEXU_APP')) {
    http_response_code(403);
    die('Acceso directo no permitido.');
}

class Plan
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM plans WHERE id = :id AND is_active = 1 LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY plan_type, price_PEN ASC');
        return $stmt->fetchAll();
    }

    public function allActiveByType(string $type): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM plans WHERE is_active = 1 AND plan_type = :type ORDER BY price_PEN ASC'
        );
        $stmt->execute([':type' => $type]);
        return $stmt->fetchAll();
    }
}
