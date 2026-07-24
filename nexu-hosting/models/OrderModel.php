<?php
/**
 * NEXU HOSTING - Modelo de Órdenes (Pagos Peruanos)
 */

class OrderModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function create(array $data): int
    {
        $invoiceNumber = generateInvoiceNumber();

        $stmt = $this->pdo->prepare(
            "INSERT INTO orders
             (user_id, plan_id, amount_pen, amount_usd, exchange_rate, currency_paid,
              payment_method, status, voucher_image, voucher_hash, billing_cycle, invoice_number)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['user_id'],
            $data['plan_id'],
            $data['amount_pen'],
            $data['amount_usd']       ?? null,
            $data['exchange_rate']    ?? null,
            $data['currency_paid']    ?? 'PEN',
            $data['payment_method'],
            $data['voucher_image']    ?? null,
            $data['voucher_hash']     ?? null,
            $data['billing_cycle']    ?? 'monthly',
            $invoiceNumber,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.*, u.username, u.email, u.full_name, u.phone,
                    p.name AS plan_name, p.plan_type, p.ram_gb, p.cpu_cores, p.disk_gb
             FROM orders o
             JOIN users u ON o.user_id = u.id
             JOIN plans p ON o.plan_id = p.id
             WHERE o.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.*, p.name AS plan_name, p.plan_type, p.ram_gb, p.cpu_cores, p.disk_gb
             FROM orders o
             JOIN plans p ON o.plan_id = p.id
             WHERE o.user_id = ?
             ORDER BY o.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getPending(): array
    {
        return $this->pdo->query(
            "SELECT o.*, u.username, u.email, u.full_name,
                    p.name AS plan_name, p.plan_type
             FROM orders o
             JOIN users u ON o.user_id = u.id
             JOIN plans p ON o.plan_id = p.id
             WHERE o.status = 'pending'
             ORDER BY o.created_at ASC"
        )->fetchAll();
    }

    public function getAll(string $status = '', int $limit = 50, int $offset = 0): array
    {
        $sql    = "SELECT o.*, u.username, u.email, p.name AS plan_name, p.plan_type
                   FROM orders o
                   JOIN users u ON o.user_id = u.id
                   JOIN plans p ON o.plan_id = p.id";
        $params = [];

        if ($status) {
            $sql    .= " WHERE o.status = ?";
            $params[] = $status;
        }

        $sql     .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(string $status = ''): int
    {
        $sql    = "SELECT COUNT(*) FROM orders";
        $params = [];
        if ($status) { $sql .= " WHERE status = ?"; $params[] = $status; }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Aprueba la orden: actualiza estado, crea servidor, registra audit.
     * Todo en una sola transacción.
     */
    public function approve(int $orderId, int $adminId): bool
    {
        $order = $this->findById($orderId);
        if (!$order || $order['status'] !== 'pending') return false;

        $this->pdo->beginTransaction();
        try {
            // 1. Actualizar orden
            $this->pdo->prepare(
                "UPDATE orders SET status='verified', verified_by=?, verified_at=NOW(), updated_at=NOW()
                 WHERE id=?"
            )->execute([$adminId, $orderId]);

            // 2. Provisionar servidor
            $serverName = $order['plan_name'] . ' #' . $orderId;
            $expires    = match($order['billing_cycle']) {
                'quarterly' => date('Y-m-d H:i:s', strtotime('+3 months')),
                'annually'  => date('Y-m-d H:i:s', strtotime('+1 year')),
                default     => date('Y-m-d H:i:s', strtotime('+1 month')),
            };

            $this->pdo->prepare(
                "INSERT INTO servers (user_id, plan_id, order_id, server_name, status, expires_at)
                 VALUES (?, ?, ?, ?, 'installing', ?)"
            )->execute([$order['user_id'], $order['plan_id'], $orderId, $serverName, $expires]);

            // 3. Audit log
            AuditModel::log('order.approved', 'orders', $orderId, $adminId, [
                'prev_status' => 'pending',
                'new_status'  => 'verified',
            ]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('[NEXU ORDER APPROVE] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Rechaza la orden con un motivo obligatorio.
     */
    public function reject(int $orderId, int $adminId, string $notes): bool
    {
        if (empty(trim($notes))) return false;

        $order = $this->findById($orderId);
        if (!$order || $order['status'] !== 'pending') return false;

        $stmt = $this->pdo->prepare(
            "UPDATE orders SET status='rejected', admin_notes=?, verified_by=?, verified_at=NOW(), updated_at=NOW()
             WHERE id=?"
        );
        $ok = $stmt->execute([trim($notes), $adminId, $orderId]);

        if ($ok) {
            AuditModel::log('order.rejected', 'orders', $orderId, $adminId, [
                'reason' => trim($notes),
            ]);
        }

        return $ok;
    }

    public function updateVoucher(int $orderId, string $imagePath, string $hash): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE orders SET voucher_image=?, voucher_hash=?, updated_at=NOW() WHERE id=?"
        );
        return $stmt->execute([$imagePath, $hash, $orderId]);
    }

    // ── Finanzas ──────────────────────────────────────────────

    public function getRevenueSummary(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
               COALESCE(SUM(CASE WHEN status='verified' THEN amount_pen END), 0) AS total_pen,
               COALESCE(SUM(CASE WHEN status='verified' AND DATE(created_at)=CURDATE() THEN amount_pen END), 0) AS today_pen,
               COALESCE(SUM(CASE WHEN status='verified' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) THEN amount_pen END), 0) AS month_pen,
               COUNT(CASE WHEN status='pending' THEN 1 END) AS pending_count,
               COUNT(*) AS total_orders
             FROM orders"
        );
        return $stmt->fetch();
    }

    public function getMonthlyRevenue(int $months = 12): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
                    COALESCE(SUM(amount_pen),0) AS revenue,
                    COUNT(*) AS orders
             FROM orders
             WHERE status='verified' AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
             GROUP BY month ORDER BY month DESC"
        );
        $stmt->execute([$months]);
        return $stmt->fetchAll();
    }
}
