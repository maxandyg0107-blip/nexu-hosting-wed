<?php
/**
 * NEXU HOSTING - Modelo de Servidores
 */

class ServerModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*, p.name AS plan_name, p.plan_type, p.ram_gb, p.cpu_cores, p.disk_gb,
                    u.username, u.email
             FROM servers s
             JOIN plans p ON s.plan_id = p.id
             JOIN users u ON s.user_id = u.id
             WHERE s.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*, p.name AS plan_name, p.plan_type, p.ram_gb, p.cpu_cores, p.disk_gb
             FROM servers s
             JOIN plans p ON s.plan_id = p.id
             WHERE s.user_id = ?
             ORDER BY s.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getAll(string $status = '', int $limit = 50, int $offset = 0): array
    {
        $sql    = "SELECT s.*, p.name AS plan_name, p.plan_type, u.username, u.email
                   FROM servers s
                   JOIN plans p ON s.plan_id = p.id
                   JOIN users u ON s.user_id = u.id";
        $params = [];

        if ($status) {
            $sql     .= " WHERE s.status = ?";
            $params[] = $status;
        }

        $sql     .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(string $status = ''): int
    {
        $sql    = "SELECT COUNT(*) FROM servers";
        $params = [];
        if ($status) { $sql .= " WHERE status = ?"; $params[] = $status; }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $allowed = ['installing','active','suspended','terminated'];
        if (!in_array($status, $allowed, true)) return false;

        $stmt = $this->pdo->prepare(
            "UPDATE servers SET status=?, updated_at=NOW() WHERE id=?"
        );
        return $stmt->execute([$status, $id]);
    }

    public function updateNodeInfo(int $id, string $nodeIp, string $serverIp, ?int $pterodactylId, ?string $panelUrl): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE servers SET node_ip=?, server_ip=?, pterodactyl_server_id=?, panel_url=?,
             status='active', updated_at=NOW() WHERE id=?"
        );
        return $stmt->execute([$nodeIp, $serverIp, $pterodactylId, $panelUrl, $id]);
    }

    public function updateMetrics(int $id, int $ramPct, int $cpuPct, int $diskPct, int $uptimeSec): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE servers SET ram_used_percent=?, cpu_used_percent=?, disk_used_percent=?,
             uptime_seconds=?, updated_at=NOW() WHERE id=?"
        );
        return $stmt->execute([$ramPct, $cpuPct, $diskPct, $uptimeSec, $id]);
    }

    public function getStats(): array
    {
        return [
            'total'      => (int)$this->pdo->query("SELECT COUNT(*) FROM servers")->fetchColumn(),
            'active'     => (int)$this->pdo->query("SELECT COUNT(*) FROM servers WHERE status='active'")->fetchColumn(),
            'installing' => (int)$this->pdo->query("SELECT COUNT(*) FROM servers WHERE status='installing'")->fetchColumn(),
            'suspended'  => (int)$this->pdo->query("SELECT COUNT(*) FROM servers WHERE status='suspended'")->fetchColumn(),
        ];
    }
}
