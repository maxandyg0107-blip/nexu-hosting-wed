<?php
/**
 * NEXU HOSTING - Modelo de Auditoría
 * Registro inmutable de acciones críticas.
 */

class AuditModel
{
    /**
     * Registra una acción en el audit_log.
     *
     * @param string   $action   'order.approved' | 'user.login' | 'plan.created' etc.
     * @param string   $entity   Nombre de la tabla afectada
     * @param int|null $entityId ID del registro afectado
     * @param int|null $userId   ID del usuario que ejecutó la acción (null = sistema)
     * @param array    $data     Datos adicionales { old, new, reason, ... }
     */
    public static function log(
        string $action,
        string $entity    = '',
        ?int   $entityId  = null,
        ?int   $userId    = null,
        array  $data      = []
    ): void {
        try {
            $stmt = db()->prepare(
                "INSERT INTO audit_log (user_id, action, entity, entity_id, old_value, new_value, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $userId  ?? ($_SESSION['user_id'] ?? null),
                $action,
                $entity  ?: null,
                $entityId,
                isset($data['old']) ? json_encode($data['old']) : null,
                isset($data['new']) ? json_encode(array_diff_key($data, ['old' => 1])) : json_encode($data),
                clientIp(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (Throwable $e) {
            // El log no debe romper la aplicación
            error_log('[NEXU AUDIT ERROR] ' . $e->getMessage());
        }
    }

    public static function getRecent(int $limit = 50): array
    {
        $stmt = db()->prepare(
            "SELECT al.*, u.username, u.email
             FROM audit_log al
             LEFT JOIN users u ON al.user_id = u.id
             ORDER BY al.created_at DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public static function getByUser(int $userId, int $limit = 30): array
    {
        $stmt = db()->prepare(
            "SELECT * FROM audit_log WHERE user_id = ? ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public static function getByEntity(string $entity, int $entityId): array
    {
        $stmt = db()->prepare(
            "SELECT al.*, u.username FROM audit_log al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE al.entity = ? AND al.entity_id = ?
             ORDER BY al.created_at DESC"
        );
        $stmt->execute([$entity, $entityId]);
        return $stmt->fetchAll();
    }
}
