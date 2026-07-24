<?php
/**
 * controllers/AdminController.php
 * ---------------------------------------------------------------------
 * Controlador del panel administrativo: aprobación / rechazo de
 * órdenes pendientes. Todas las acciones quedan registradas en la
 * tabla `audit_log` (ver Order::approve / Order::reject).
 * ---------------------------------------------------------------------
 */

if (!defined('NEXU_APP')) {
    http_response_code(403);
    die('Acceso directo no permitido.');
}

class AdminController
{
    private Order $orderModel;

    public function __construct()
    {
        $this->orderModel = new Order();
    }

    public function approveOrder(int $adminId, int $orderId): array
    {
        csrf_verify_or_die();

        try {
            $result = $this->orderModel->approve($orderId, $adminId);
            return ['success' => true, 'message' => 'Orden #' . $orderId . ' aprobada. Servidor "' . $result['server_name'] . '" en aprovisionamiento.'];
        } catch (\Throwable $e) {
            log_error('AdminController::approveOrder', $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function rejectOrder(int $adminId, int $orderId, string $reason): array
    {
        csrf_verify_or_die();

        $reason = trim($reason);
        if ($reason === '') {
            return ['success' => false, 'message' => 'Debes indicar el motivo del rechazo.'];
        }

        try {
            $this->orderModel->reject($orderId, $adminId, $reason);
            return ['success' => true, 'message' => 'Orden #' . $orderId . ' rechazada correctamente.'];
        } catch (\Throwable $e) {
            log_error('AdminController::rejectOrder', $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
