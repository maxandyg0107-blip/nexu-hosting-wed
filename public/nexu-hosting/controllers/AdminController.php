<?php
/**
 * NEXU HOSTING - Controlador de Administración
 */

class AdminController
{
    private OrderModel  $orders;
    private UserModel   $users;
    private ServerModel $servers;
    private PlanModel   $plans;

    public function __construct()
    {
        requireAdmin();
        $this->orders  = new OrderModel();
        $this->users   = new UserModel();
        $this->servers = new ServerModel();
        $this->plans   = new PlanModel();
    }

    // ── Dashboard ─────────────────────────────────────────────

    public function getDashboardData(): array
    {
        $revenue = $this->orders->getRevenueSummary();
        return [
            'revenue'        => $revenue,
            'monthly_revenue'=> $this->orders->getMonthlyRevenue(6),
            'user_stats'     => $this->users->getStats(),
            'server_stats'   => $this->servers->getStats(),
            'pending_orders' => $this->orders->countAll('pending'),
            'recent_orders'  => $this->orders->getAll('', 10),
            'audit_log'      => AuditModel::getRecent(20),
        ];
    }

    // ── Órdenes pendientes ────────────────────────────────────

    public function getPendingOrders(): array
    {
        return $this->orders->getPending();
    }

    public function getAllOrders(string $status = '', int $page = 1): array
    {
        $perPage = 20;
        $pag     = paginate($this->orders->countAll($status), $perPage, $page);
        return [
            'orders'     => $this->orders->getAll($status, $perPage, $pag['offset']),
            'pagination' => $pag,
            'status'     => $status,
        ];
    }

    public function approveOrder(int $orderId): void
    {
        validateCsrfOrFail();

        $ok = $this->orders->approve($orderId, $_SESSION['user_id']);

        if ($ok) {
            setFlash('Pago aprobado y servidor en proceso de instalación.', 'success');
        } else {
            setFlash('No se pudo aprobar el pedido. Verifica que esté pendiente.', 'danger');
        }

        redirect('/admin/orders.php');
    }

    public function rejectOrder(int $orderId): void
    {
        validateCsrfOrFail();

        $notes = sanitize($_POST['admin_notes'] ?? '');

        if (strlen(trim($notes)) < 5) {
            setFlash('Debes ingresar un motivo de rechazo (mínimo 5 caracteres).', 'danger');
            redirect('/admin/orders.php');
        }

        $ok = $this->orders->reject($orderId, $_SESSION['user_id'], $notes);

        if ($ok) {
            setFlash('Pedido rechazado. El cliente fue notificado.', 'warning');
        } else {
            setFlash('No se pudo rechazar el pedido.', 'danger');
        }

        redirect('/admin/orders.php');
    }

    // ── Clientes ──────────────────────────────────────────────

    public function getClients(int $page = 1, string $search = ''): array
    {
        $perPage = 25;
        $total   = $this->users->countAll($search);
        $pag     = paginate($total, $perPage, $page);

        return [
            'users'      => $this->users->getAll($perPage, $pag['offset'], $search),
            'pagination' => $pag,
            'search'     => $search,
        ];
    }

    public function suspendClient(int $userId): void
    {
        validateCsrfOrFail();

        if ($userId === (int)$_SESSION['user_id']) {
            setFlash('No puedes suspenderte a ti mismo.', 'danger');
            redirect('/admin/clients.php');
        }

        $this->users->suspend($userId);
        AuditModel::log('user.suspended', 'users', $userId);
        setFlash('Cuenta suspendida.', 'warning');
        redirect('/admin/clients.php');
    }

    public function activateClient(int $userId): void
    {
        validateCsrfOrFail();
        $this->users->activate($userId);
        AuditModel::log('user.activated', 'users', $userId);
        setFlash('Cuenta reactivada.', 'success');
        redirect('/admin/clients.php');
    }

    // ── Servidores ────────────────────────────────────────────

    public function getServers(string $status = '', int $page = 1): array
    {
        $perPage = 25;
        $pag     = paginate($this->servers->countAll($status), $perPage, $page);
        return [
            'servers'    => $this->servers->getAll($status, $perPage, $pag['offset']),
            'pagination' => $pag,
            'status'     => $status,
        ];
    }

    public function updateServerStatus(int $serverId): void
    {
        validateCsrfOrFail();
        $status = sanitize($_POST['status'] ?? '');
        $ok     = $this->servers->updateStatus($serverId, $status);

        if ($ok) {
            AuditModel::log('server.status_changed', 'servers', $serverId, $_SESSION['user_id'], ['new_status' => $status]);
            setFlash('Estado del servidor actualizado.', 'success');
        } else {
            setFlash('Estado inválido.', 'danger');
        }

        redirect('/admin/servers.php');
    }

    public function updateServerNode(int $serverId): void
    {
        validateCsrfOrFail();

        $nodeIp       = sanitize($_POST['node_ip']             ?? '');
        $serverIp     = sanitize($_POST['server_ip']           ?? '');
        $pterodactylId= (int)($_POST['pterodactyl_server_id'] ?? 0) ?: null;
        $panelUrl     = sanitize($_POST['panel_url']           ?? '');

        $ok = $this->servers->updateNodeInfo($serverId, $nodeIp, $serverIp, $pterodactylId, $panelUrl ?: null);

        if ($ok) {
            AuditModel::log('server.node_updated', 'servers', $serverId, $_SESSION['user_id']);
            setFlash('Información del nodo guardada y servidor activado.', 'success');
        } else {
            setFlash('Error al guardar la información del nodo.', 'danger');
        }

        redirect('/admin/servers.php');
    }

    // ── Planes ────────────────────────────────────────────────

    public function getPlans(): array
    {
        return [
            'plans' => $this->plans->getAll(false),
            'types' => $this->plans->getTypes(),
        ];
    }

    public function savePlan(): void
    {
        validateCsrfOrFail();

        $id = (int)($_POST['plan_id'] ?? 0);

        $data = [
            'name'        => sanitize($_POST['name']        ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'plan_type'   => sanitize($_POST['plan_type']   ?? 'minecraft'),
            'ram_gb'      => (float)($_POST['ram_gb']       ?? 0),
            'cpu_cores'   => (float)($_POST['cpu_cores']    ?? 0),
            'disk_gb'     => (int)($_POST['disk_gb']        ?? 0),
            'player_slots'=> ($_POST['player_slots'] ?? '') !== '' ? (int)$_POST['player_slots'] : null,
            'price_pen'   => (float)($_POST['price_pen']    ?? 0),
            'is_active'   => (int)($_POST['is_active']      ?? 1),
            'is_featured' => (int)($_POST['is_featured']    ?? 0),
            'sort_order'  => (int)($_POST['sort_order']     ?? 0),
            'features'    => array_filter(array_map('trim', explode("\n", $_POST['features'] ?? ''))),
        ];

        if (!$data['name'] || $data['price_pen'] <= 0) {
            setFlash('Nombre y precio son obligatorios.', 'danger');
            redirect('/admin/settings.php');
        }

        if ($id > 0) {
            $this->plans->update($id, $data);
            AuditModel::log('plan.updated', 'plans', $id, $_SESSION['user_id']);
            setFlash('Plan actualizado.', 'success');
        } else {
            $data['slug'] = $this->plans->makeSlug($data['name']);
            $newId        = $this->plans->create($data);
            AuditModel::log('plan.created', 'plans', $newId, $_SESSION['user_id']);
            setFlash('Plan creado exitosamente.', 'success');
        }

        redirect('/admin/settings.php');
    }

    public function togglePlan(int $planId): void
    {
        validateCsrfOrFail();
        $this->plans->toggleActive($planId);
        AuditModel::log('plan.toggled', 'plans', $planId, $_SESSION['user_id']);
        setFlash('Visibilidad del plan actualizada.', 'success');
        redirect('/admin/settings.php');
    }
}
