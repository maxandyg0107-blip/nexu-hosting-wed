<?php
/**
 * models/Server.php
 * ---------------------------------------------------------------------
 * Modelo de acceso a datos para la tabla `servers`.
 * ---------------------------------------------------------------------
 */

if (!defined('NEXU_APP')) {
    http_response_code(403);
    die('Acceso directo no permitido.');
}

class Server
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, p.name AS plan_name, p.plan_type, p.ram_gb, p.cpu_cores, p.disk_gb
             FROM servers s
             JOIN plans p ON p.id = s.plan_id
             WHERE s.user_id = :user_id
             ORDER BY s.created_at DESC'
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function countByStatusForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT status, COUNT(*) AS total FROM servers WHERE user_id = :user_id GROUP BY status'
        );
        $stmt->execute([':user_id' => $userId]);
        $counts = ['installing' => 0, 'active' => 0, 'suspended' => 0, 'terminated' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }
}
