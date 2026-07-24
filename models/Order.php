<?php
/**
 * models/Order.php
 * ---------------------------------------------------------------------
 * Modelo de acceso a datos para la tabla `orders`.
 * Contiene la lógica transaccional de creación, listado y cambio de
 * estado de las órdenes (pending -> verified / rejected).
 * ---------------------------------------------------------------------
 */

if (!defined('NEXU_APP')) {
    http_response_code(403);
    die('Acceso directo no permitido.');
}

class Order
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(
        int $userId,
        int $planId,
        float $totalAmount,
        string $paymentMethod,
        string $voucherFilename,
        ?string $operationCode
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO orders (user_id, plan_id, total_amount, payment_method, voucher_image, operation_code, status)
             VALUES (:user_id, :plan_id, :total_amount, :payment_method, :voucher_image, :operation_code, "pending")'
        );
        $stmt->execute([
            ':user_id'        => $userId,
            ':plan_id'        => $planId,
            ':total_amount'   => $totalAmount,
            ':payment_method' => $paymentMethod,
            ':voucher_image'  => $voucherFilename,
            ':operation_code' => $operationCode,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT o.*, p.name AS plan_name, p.plan_type, p.ram_gb, p.cpu_cores, p.disk_gb,
                    u.username, u.email
             FROM orders o
             JOIN plans p ON p.id = o.plan_id
             JOIN users u ON u.id = o.user_id
             WHERE o.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT o.*, p.name AS plan_name, p.plan_type, p.ram_gb, p.cpu_cores, p.disk_gb
             FROM orders o
             JOIN plans p ON p.id = o.plan_id
             WHERE o.user_id = :user_id
             ORDER BY o.created_at DESC'
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Lista órdenes para el panel administrativo, con filtro opcional de estado.
     */
    public function allForAdmin(string $statusFilter = 'pending'): array
    {
        $sql = 'SELECT o.*, p.name AS plan_name, p.plan_type, u.username, u.email
                FROM orders o
                JOIN plans p ON p.id = o.plan_id
                JOIN users u ON u.id = o.user_id';

        $params = [];
        if ($statusFilter !== 'all') {
            $sql .= ' WHERE o.status = :status';
            $params[':status'] = $statusFilter;
        }
        $sql .= ' ORDER BY o.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Aprueba una orden de forma atómica: actualiza el estado a 'verified'
     * y aprovisiona el registro correspondiente en la tabla `servers`.
     * Se envuelve en una transacción para garantizar consistencia:
     * si el aprovisionamiento del servidor falla, la orden NO queda
     * marcada como verificada a medias.
     */
    public function approve(int $orderId, int $adminId): array
    {
        $order = $this->findById($orderId);
        if (!$order) {
            throw new RuntimeException('La orden solicitada no existe.');
        }
        if ($order['status'] !== 'pending') {
            throw new RuntimeException('Esta orden ya fue procesada anteriormente.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE orders
                 SET status = "verified", reviewed_by = :admin_id, reviewed_at = NOW()
                 WHERE id = :id AND status = "pending"'
            );
            $stmt->execute([':admin_id' => $adminId, ':id' => $orderId]);

            if ($stmt->rowCount() !== 1) {
                // Otra petición concurrente ya procesó esta orden (evita doble aprobación)
                throw new RuntimeException('La orden ya fue procesada por otra sesión.');
            }

            $serverName = 'nexu-' . strtolower($order['plan_type']) . '-' . bin2hex(random_bytes(3));

            $stmtServer = $this->db->prepare(
                'INSERT INTO servers (user_id, plan_id, order_id, server_name, status)
                 VALUES (:user_id, :plan_id, :order_id, :server_name, "installing")'
            );
            $stmtServer->execute([
                ':user_id'     => $order['user_id'],
                ':plan_id'     => $order['plan_id'],
                ':order_id'    => $orderId,
                ':server_name' => $serverName,
            ]);

            $serverId = (int) $this->db->lastInsertId();

            $this->logAudit($adminId, 'approve_order', 'order', $orderId, 'Servidor aprovisionado: ' . $serverName);

            $this->db->commit();

            return ['order_id' => $orderId, 'server_id' => $serverId, 'server_name' => $serverName];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function reject(int $orderId, int $adminId, string $reason): void
    {
        $stmt = $this->db->prepare(
            'UPDATE orders
             SET status = "rejected", admin_notes = :reason, reviewed_by = :admin_id, reviewed_at = NOW()
             WHERE id = :id AND status = "pending"'
        );
        $stmt->execute([
            ':reason'   => $reason,
            ':admin_id' => $adminId,
            ':id'       => $orderId,
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('La orden ya fue procesada o no existe.');
        }

        $this->logAudit($adminId, 'reject_order', 'order', $orderId, $reason);
    }

    private function logAudit(int $adminId, string $action, string $targetType, int $targetId, string $details): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address)
             VALUES (:admin_id, :action, :target_type, :target_id, :details, :ip)'
        );
        $stmt->execute([
            ':admin_id'    => $adminId,
            ':action'      => $action,
            ':target_type' => $targetType,
            ':target_id'   => $targetId,
            ':details'     => $details,
            ':ip'          => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
    }
}
