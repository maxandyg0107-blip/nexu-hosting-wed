<?php
/**
 * models/User.php
 * ---------------------------------------------------------------------
 * Modelo de acceso a datos para la tabla `users`.
 * Toda consulta usa exclusivamente PDO con prepared statements.
 * ---------------------------------------------------------------------
 */

if (!defined('NEXU_APP')) {
    http_response_code(403);
    die('Acceso directo no permitido.');
}

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Crea un nuevo cliente. La contraseña se recibe YA hasheada
     * (ver AuthController::register) para mantener el modelo agnóstico
     * al algoritmo de hashing usado.
     */
    public function create(string $username, string $email, string $passwordHash): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password, role, status)
             VALUES (:username, :email, :password, "client", "active")'
        );
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => $passwordHash,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Incrementa el contador de intentos fallidos y, de superar el
     * umbral configurado, bloquea temporalmente la cuenta.
     */
    public function registerFailedAttempt(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET failed_login_attempts = failed_login_attempts + 1,
                 locked_until = CASE
                     WHEN failed_login_attempts + 1 >= :max_attempts
                     THEN DATE_ADD(NOW(), INTERVAL :lockout_minutes MINUTE)
                     ELSE locked_until
                 END
             WHERE id = :id'
        );
        $stmt->execute([
            ':max_attempts'    => MAX_LOGIN_ATTEMPTS,
            ':lockout_minutes' => LOGIN_LOCKOUT_MINUTES,
            ':id'              => $userId,
        ]);
    }

    public function resetFailedAttempts(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = :id'
        );
        $stmt->execute([':id' => $userId]);
    }
}
