<?php
/**
 * NEXU HOSTING - Modelo de Usuarios
 * Toda la lógica de acceso a datos de la tabla `users`.
 */

class UserModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    // ── CRUD ──────────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function findByOAuth(string $provider, string $oauthId): ?array
    {
        $col  = $provider === 'google' ? 'google_id' : 'discord_id';
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE $col = ? LIMIT 1");
        $stmt->execute([$oauthId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users
             (username, email, password, full_name, phone, country, role, status,
              google_id, discord_id, oauth_provider, email_verified, preferred_currency)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['username'],
            strtolower($data['email']),
            $data['password'],
            $data['full_name']         ?? null,
            $data['phone']             ?? null,
            $data['country']           ?? null,
            $data['role']              ?? 'client',
            $data['status']            ?? 'active',
            $data['google_id']         ?? null,
            $data['discord_id']        ?? null,
            $data['oauth_provider']    ?? 'local',
            $data['email_verified']    ?? 0,
            $data['preferred_currency'] ?? 'PEN',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['full_name','phone','country','status','avatar_url','preferred_currency',
                    'language','balance','email_verified','two_fa_enabled'];
        $sets    = [];
        $params  = [];

        foreach ($data as $col => $val) {
            if (in_array($col, $allowed, true)) {
                $sets[]   = "`$col` = ?";
                $params[] = $val;
            }
        }

        if (empty($sets)) return false;

        $params[] = $id;
        $stmt = $this->pdo->prepare("UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $id]);
    }

    public function getAll(int $limit = 100, int $offset = 0, string $search = ''): array
    {
        $sql    = "SELECT id, username, email, full_name, role, status, created_at, last_login_at, preferred_currency FROM users";
        $params = [];

        if ($search) {
            $sql    .= " WHERE (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
            $like    = "%$search%";
            $params  = [$like, $like, $like];
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(string $search = ''): int
    {
        $sql    = "SELECT COUNT(*) FROM users";
        $params = [];
        if ($search) {
            $sql    .= " WHERE (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
            $like    = "%$search%";
            $params  = [$like, $like, $like];
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function suspend(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ? AND role != 'admin'");
        return $stmt->execute([$id]);
    }

    public function activate(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ── Brute-force / Rate limiting ───────────────────────────

    public function incrementLoginAttempts(int $id): void
    {
        $this->pdo->prepare(
            "UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?"
        )->execute([$id]);
    }

    public function lockAccount(int $id, int $minutes): void
    {
        $until = date('Y-m-d H:i:s', time() + $minutes * 60);
        $this->pdo->prepare(
            "UPDATE users SET locked_until = ? WHERE id = ?"
        )->execute([$until, $id]);
    }

    public function resetLoginAttempts(int $id): void
    {
        $this->pdo->prepare(
            "UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?"
        )->execute([$id]);
    }

    public function isLocked(array $user): bool
    {
        if (!$user['locked_until']) return false;
        return strtotime($user['locked_until']) > time();
    }

    // ── Password reset ────────────────────────────────────────

    public function createPasswordReset(int $userId): string
    {
        // Invalidar tokens previos
        $this->pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$userId]);

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hora

        $this->pdo->prepare(
            "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)"
        )->execute([$userId, $token, $expires]);

        return $token;
    }

    public function findValidResetToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT pr.*, u.email, u.username FROM password_resets pr
             JOIN users u ON pr.user_id = u.id
             WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public function markResetTokenUsed(string $token): void
    {
        $this->pdo->prepare(
            "UPDATE password_resets SET used = 1 WHERE token = ?"
        )->execute([$token]);
    }

    // ── Stats para admin ──────────────────────────────────────

    public function getStats(): array
    {
        return [
            'total'     => (int)$this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'active'    => (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn(),
            'suspended' => (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE status='suspended'")->fetchColumn(),
            'today'     => (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
        ];
    }
}
