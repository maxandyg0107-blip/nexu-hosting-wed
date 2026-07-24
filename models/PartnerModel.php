<?php
/**
 * NEXU HOSTING - Modelo de Partners/Afiliados
 * Gestión completa del programa de afiliados
 */

class PartnerModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    // ═══════════════════════════════════════════════════════════════
    // CRUD BÁSICO
    // ═══════════════════════════════════════════════════════════════

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM partners WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM partners WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM partners WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function findByIdWithUser(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.email, u.username, u.full_name
            FROM partners p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $slug = $this->generateUniqueSlug($data['display_name'] ?? $data['company_name'] ?? 'partner');

        $stmt = $this->pdo->prepare("
            INSERT INTO partners
            (user_id, slug, company_name, display_name, description, logo_url, website_url,
             discord_url, twitter_url, youtube_url, twitch_url, tiktok_url,
             commission_rate, commission_type, min_payout, status, tier,
             custom_discount, landing_page, notify_email, notify_discord)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['user_id'],
            $slug,
            $data['company_name'] ?? null,
            $data['display_name'],
            $data['description'] ?? null,
            $data['logo_url'] ?? null,
            $data['website_url'] ?? null,
            $data['discord_url'] ?? null,
            $data['twitter_url'] ?? null,
            $data['youtube_url'] ?? null,
            $data['twitch_url'] ?? null,
            $data['tiktok_url'] ?? null,
            $data['commission_rate'] ?? $this->getSetting('default_rate'),
            $data['commission_type'] ?? 'recurring',
            $data['min_payout'] ?? $this->getSetting('min_payout'),
            $data['status'] ?? 'pending',
            $data['tier'] ?? 'bronze',
            $data['custom_discount'] ?? null,
            $data['landing_page'] ?? null,
            $data['notify_email'] ?? 1,
            $data['notify_discord'] ?? 0,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'company_name','display_name','description','logo_url','website_url',
            'discord_url','twitter_url','youtube_url','twitch_url','tiktok_url',
            'commission_rate','commission_type','min_payout','status','tier',
            'custom_discount','landing_page','notify_email','notify_discord',
            'rejection_reason'
        ];

        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            if (in_array($col, $allowed, true)) {
                $sets[] = "`$col` = ?";
                $params[] = $val;
            }
        }
        if (empty($sets)) return false;

        $params[] = $id;
        $stmt = $this->pdo->prepare("UPDATE partners SET " . implode(', ', $sets) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    public function approve(int $id, int $adminId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE partners
            SET status = 'active', approved_at = NOW(), approved_by = ?
            WHERE id = ? AND status = 'pending'
        ");
        return $stmt->execute([$adminId, $id]);
    }

    public function reject(int $id, int $adminId, string $reason): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE partners
            SET status = 'rejected', approved_by = ?, rejection_reason = ?
            WHERE id = ? AND status = 'pending'
        ");
        return $stmt->execute([$adminId, $reason, $id]);
    }

    public function suspend(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE partners SET status = 'suspended' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function activate(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE partners SET status = 'active' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ═══════════════════════════════════════════════════════════════
    // LISTADOS Y PAGINACIÓN
    // ═══════════════════════════════════════════════════════════════

    public function getAll(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "p.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['tier'])) {
            $where[] = "p.tier = ?";
            $params[] = $filters['tier'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(p.display_name LIKE ? OR p.company_name LIKE ? OR p.slug LIKE ? OR u.email LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if (!empty($filters['user_id'])) {
            $where[] = "p.user_id = ?";
            $params[] = $filters['user_id'];
        }

        $sql = "
            SELECT p.*, u.email, u.username
            FROM partners p
            JOIN users u ON p.user_id = u.id
        ";
        if ($where) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(array $filters = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['tier'])) {
            $where[] = "tier = ?";
            $params[] = $filters['tier'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(display_name LIKE ? OR company_name LIKE ? OR slug LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $sql = "SELECT COUNT(*) FROM partners";
        if ($where) $sql .= " WHERE " . implode(' AND ', $where);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getPendingCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM partners WHERE status = 'pending'");
        return (int)$stmt->fetchColumn();
    }

    public function getActivePartners(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, slug, display_name, logo_url, tier, commission_rate, total_earned, conversions
            FROM partners
            WHERE status = 'active'
            ORDER BY total_earned DESC, conversions DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════════
    // TRACKING: CLICKS Y CONVERSIONES
    // ═══════════════════════════════════════════════════════════════

    public function recordClick(int $partnerId, array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO partner_clicks
            (partner_id, ip_address, user_agent, referrer, landing_url, country)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $partnerId,
            $data['ip'],
            $data['ua'] ?? null,
            $data['referrer'] ?? null,
            $data['landing_url'] ?? null,
            $data['country'] ?? null,
        ]);

        // Incrementar contador en partners
        $this->pdo->prepare("UPDATE partners SET clicks = clicks + 1, last_click_at = NOW() WHERE id = ?")
            ->execute([$partnerId]);

        return (int)$this->pdo->lastInsertId();
    }

    public function markClickConverted(int $clickId, int $orderId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE partner_clicks
            SET converted = 1, converted_at = NOW(), order_id = ?
            WHERE id = ? AND converted = 0
        ");
        $stmt->execute([$orderId, $clickId]);
    }

    public function createConversion(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO partner_conversions
            (partner_id, click_id, order_id, user_id, amount_pen, commission_pen, commission_rate, status, recurring, billing_cycle)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['partner_id'],
            $data['click_id'] ?? null,
            $data['order_id'],
            $data['user_id'],
            $data['amount_pen'],
            $data['commission_pen'],
            $data['commission_rate'],
            $data['status'] ?? 'pending',
            $data['recurring'] ?? 1,
            $data['billing_cycle'] ?? 1,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getConversions(int $partnerId, string $status = '', int $limit = 50, int $offset = 0): array
    {
        $sql = "
            SELECT pc.*, o.invoice_number, o.status as order_status, u.username as customer_username, u.email as customer_email
            FROM partner_conversions pc
            JOIN orders o ON pc.order_id = o.id
            JOIN users u ON pc.user_id = u.id
            WHERE pc.partner_id = ?
        ";
        $params = [$partnerId];

        if ($status) {
            $sql .= " AND pc.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY pc.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countConversions(int $partnerId, string $status = ''): int
    {
        $sql = "SELECT COUNT(*) FROM partner_conversions WHERE partner_id = ?";
        $params = [$partnerId];
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getPendingCommissions(int $partnerId): float
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(commission_pen), 0) FROM partner_conversions
            WHERE partner_id = ? AND status = 'pending'
        ");
        $stmt->execute([$partnerId]);
        return (float)$stmt->fetchColumn();
    }

    public function getApprovedCommissions(int $partnerId): float
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(commission_pen), 0) FROM partner_conversions
            WHERE partner_id = ? AND status = 'approved'
        ");
        $stmt->execute([$partnerId]);
        return (float)$stmt->fetchColumn();
    }

    // ═══════════════════════════════════════════════════════════════
    // PAYOUTS (RETIROS)
    // ═══════════════════════════════════════════════════════════════

    public function createPayout(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO partner_payouts
            (partner_id, amount_pen, method, account_details, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $data['partner_id'],
            $data['amount_pen'],
            $data['method'],
            json_encode($data['account_details']),
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getPayouts(int $partnerId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM partner_payouts
            WHERE partner_id = ?
            ORDER BY requested_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$partnerId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    public function getPayout(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM partner_payouts WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function updatePayoutStatus(int $id, string $status, int $adminId = 0, string $txid = '', string $notes = ''): bool
    {
        $sets = ["status = ?", "processed_at = NOW()"];
        $params = [$status];

        if ($adminId) { $sets[] = "processed_by = ?"; $params[] = $adminId; }
        if ($txid)    { $sets[] = "txid = ?"; $params[] = $txid; }
        if ($notes)   { $sets[] = "notes = ?"; $params[] = $notes; }

        $params[] = $id;
        $stmt = $this->pdo->prepare("UPDATE partner_payouts SET " . implode(', ', $sets) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    // ═══════════════════════════════════════════════════════════════
    // ASSETS DE MARKETING
    // ═══════════════════════════════════════════════════════════════

    public function getAssets(string $type = '', bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM partner_assets WHERE 1=1";
        $params = [];
        if ($type) { $sql .= " AND type = ?"; $params[] = $type; }
        if ($activeOnly) { $sql .= " AND is_active = 1"; }
        $sql .= " ORDER BY sort_order, id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAsset(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM partner_assets WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ═══════════════════════════════════════════════════════════════
    // CONFIGURACIÓN GLOBAL
    // ═══════════════════════════════════════════════════════════════

    public function getSettings(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM partner_settings LIMIT 1");
        return $stmt->fetch() ?: [];
    }

    public function getSetting(string $key, $default = null)
    {
        $settings = $this->getSettings();
        return $settings[$key] ?? $default;
    }

    public function updateSettings(array $data): bool
    {
        $allowed = [
            'default_rate','min_payout','cookie_days','recurring_commissions',
            'max_recurring_months','auto_approve','require_kyc',
            'allowed_countries','banner_text','tos_url','faq_url'
        ];

        $sets = [];
        $params = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $allowed, true)) {
                $sets[] = "`$k` = ?";
                $params[] = is_array($v) ? json_encode($v) : $v;
            }
        }
        if (empty($sets)) return false;

        $params[] = 1; // id
        $stmt = $this->pdo->prepare("UPDATE partner_settings SET " . implode(', ', $sets) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    // ═══════════════════════════════════════════════════════════════
    // ESTADÍSTICAS Y REPORTES
    // ═══════════════════════════════════════════════════════════════

    public function getStats(int $partnerId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                p.clicks,
                p.signups,
                p.conversions,
                p.balance,
                p.total_earned,
                p.total_paid,
                p.commission_rate,
                p.tier,
                COALESCE(SUM(CASE WHEN pc.status = 'pending' THEN pc.commission_pen ELSE 0 END), 0) as pending_commissions,
                COALESCE(SUM(CASE WHEN pc.status = 'approved' THEN pc.commission_pen ELSE 0 END), 0) as approved_commissions,
                COALESCE(SUM(CASE WHEN pc.status = 'paid' THEN pc.commission_pen ELSE 0 END), 0) as paid_commissions
            FROM partners p
            LEFT JOIN partner_conversions pc ON pc.partner_id = p.id
            WHERE p.id = ?
            GROUP BY p.id
        ");
        $stmt->execute([$partnerId]);
        $row = $stmt->fetch() ?: [];

        $row['conversion_rate'] = $row['clicks'] > 0
            ? round($row['conversions'] / $row['clicks'] * 100, 2)
            : 0;

        return $row;
    }

    public function getChartData(int $partnerId, int $months = 6): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as conversions,
                SUM(commission_pen) as earnings
            FROM partner_conversions
            WHERE partner_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month
        ");
        $stmt->execute([$partnerId, $months]);
        return $stmt->fetchAll();
    }

    public function getTopPages(int $partnerId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare("
            SELECT landing_url, COUNT(*) as clicks,
                   SUM(converted) as conversions
            FROM partner_clicks
            WHERE partner_id = ? AND landing_url IS NOT NULL
            GROUP BY landing_url
            ORDER BY clicks DESC
            LIMIT ?
        ");
        $stmt->execute([$partnerId, $limit]);
        return $stmt->fetchAll();
    }

    public function getGeography(int $partnerId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare("
            SELECT country, COUNT(*) as clicks,
                   SUM(converted) as conversions
            FROM partner_clicks
            WHERE partner_id = ? AND country IS NOT NULL
            GROUP BY country
            ORDER BY clicks DESC
            LIMIT ?
        ");
        $stmt->execute([$partnerId, $limit]);
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    private function generateUniqueSlug(string $base): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $base), '-'));
        $slug = substr($slug, 0, 50);
        $original = $slug;
        $i = 1;

        while ($this->findBySlug($slug)) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }

    public function generateReferralUrl(int $partnerId, string $landingPage = ''): string
    {
        $partner = $this->findById($partnerId);
        if (!$partner) return APP_URL;

        $base = $landingPage ?: APP_URL;
        $separator = str_contains($base, '?') ? '&' : '?';
        return $base . $separator . 'ref=' . $partner['slug'];
    }

    public function getTierConfig(): array
    {
        return [
            'bronze'  => ['label' => 'Bronce', 'color' => '#CD7F32', 'min_earned' => 0,     'rate_bonus' => 0],
            'silver'  => ['label' => 'Plata',  'color' => '#C0C0C0', 'min_earned' => 500,   'rate_bonus' => 2],
            'gold'    => ['label' => 'Oro',    'color' => '#FFD700', 'min_earned' => 2000,  'rate_bonus' => 5],
            'platinum'=> ['label' => 'Platino','color' => '#E5E4E2', 'min_earned' => 10000, 'rate_bonus' => 10],
        ];
    }

    public function updateTier(int $partnerId): void
    {
        $partner = $this->findById($partnerId);
        if (!$partner) return;

        $tiers = $this->getTierConfig();
        $newTier = 'bronze';
        foreach (array_reverse($tiers) as $key => $cfg) {
            if ($partner['total_earned'] >= $cfg['min_earned']) {
                $newTier = $key;
                break;
            }
        }

        if ($newTier !== $partner['tier']) {
            $this->update($partnerId, ['tier' => $newTier]);
            // Log automático via AuditModel
        }
    }
}