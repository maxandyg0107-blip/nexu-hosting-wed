<?php
/**
 * NEXU HOSTING - Modelo de Planes
 */

class PlanModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    public function getAll(bool $activeOnly = true): array
    {
        $sql  = "SELECT * FROM plans";
        if ($activeOnly) $sql .= " WHERE is_active = 1";
        $sql .= " ORDER BY plan_type, sort_order ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getByType(string $type, bool $activeOnly = true): array
    {
        $sql    = "SELECT * FROM plans WHERE plan_type = ?";
        $params = [$type];
        if ($activeOnly) { $sql .= " AND is_active = 1"; }
        $sql   .= " ORDER BY sort_order ASC";
        $stmt   = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM plans WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) $row['features_array'] = json_decode($row['features'] ?? '[]', true);
        return $row ?: null;
    }

    public function getBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM plans WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if ($row) $row['features_array'] = json_decode($row['features'] ?? '[]', true);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO plans (name, slug, description, plan_type, ram_gb, cpu_cores, disk_gb,
             bandwidth_tb, player_slots, price_pen, features, is_active, is_featured, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            $data['plan_type'],
            $data['ram_gb'],
            $data['cpu_cores'],
            $data['disk_gb'],
            $data['bandwidth_tb']  ?? null,
            $data['player_slots']  ?? null,
            $data['price_pen'],
            is_array($data['features'] ?? null) ? json_encode($data['features']) : ($data['features'] ?? null),
            $data['is_active']   ?? 1,
            $data['is_featured'] ?? 0,
            $data['sort_order']  ?? 0,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['name','description','plan_type','ram_gb','cpu_cores','disk_gb',
                    'bandwidth_tb','player_slots','price_pen','features','is_active','is_featured','sort_order'];
        $sets    = [];
        $params  = [];

        foreach ($data as $col => $val) {
            if (in_array($col, $allowed, true)) {
                $sets[]   = "`$col` = ?";
                $params[] = ($col === 'features' && is_array($val)) ? json_encode($val) : $val;
            }
        }

        if (empty($sets)) return false;
        $params[] = $id;
        $stmt = $this->pdo->prepare("UPDATE plans SET " . implode(', ', $sets) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    public function toggleActive(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE plans SET is_active = NOT is_active WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getTypes(): array
    {
        return ['minecraft' => 'Minecraft', 'web' => 'Web Hosting', 'vps' => 'VPS', 'other' => 'Otro'];
    }

    // Genera slug único a partir del nombre
    public function makeSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Verificar unicidad
        $stmt  = $this->pdo->prepare("SELECT COUNT(*) FROM plans WHERE slug LIKE ?");
        $stmt->execute([$slug . '%']);
        $count = (int)$stmt->fetchColumn();

        return $count > 0 ? $slug . '-' . ($count + 1) : $slug;
    }
}
