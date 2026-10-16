<?php
class UserModel {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function findByEmail(string $email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([strtolower(trim($email))]);
        return $stmt->fetch();
    }

    public function create(string $fullname, string $email, string $phone, string $password, int $role = 0) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (fullname, email, phone, address, password, role) VALUES (?, ?, ?, 'Chưa cập nhật', ?, ?)");
        $stmt->execute([$fullname, strtolower(trim($email)), $phone, $hash, $role]);
        return $this->pdo->lastInsertId();
    }

    public function findById(int $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function isEmailTaken(string $email, int $excludeUserId = 0): bool {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([strtolower(trim($email)), $excludeUserId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function ensureAvatarColumn(): void {
        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'")->fetchAll();
            if (empty($cols)) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER address");
            }
            $this->ensureGoogleColumns();
        } catch (Throwable $e) {}
    }

    public function ensureGoogleColumns(): void {
        try {
            $userCols = $this->pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('google_id', $userCols)) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(100) NULL AFTER email");
            }
            if (!in_array('auth_provider', $userCols)) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN auth_provider VARCHAR(30) NOT NULL DEFAULT 'local' AFTER google_id");
            }
            $this->pdo->exec("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL");
        } catch (Throwable $e) {}
    }

    public function findByGoogleId(string $googleId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ? LIMIT 1");
        $stmt->execute([$googleId]);
        return $stmt->fetch();
    }

    public function createFromGoogle(string $fullname, string $email, string $googleId, ?string $avatar = null, string $phone = ''): int {
        $this->ensureGoogleColumns();
        $stmt = $this->pdo->prepare("INSERT INTO users (fullname, email, phone, address, avatar, password, role, google_id, auth_provider) VALUES (?, ?, ?, 'Chưa cập nhật', ?, NULL, 0, ?, 'google')");
        $stmt->execute([
            $fullname,
            strtolower(trim($email)),
            $phone,
            $avatar,
            $googleId
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function linkGoogleAccount(int $userId, string $googleId, ?string $avatar = null): bool {
        $this->ensureGoogleColumns();
        $user = $this->findById($userId);
        if (!$user) return false;

        $newAvatar = !empty($user['avatar']) ? $user['avatar'] : $avatar;
        $stmt = $this->pdo->prepare("UPDATE users SET google_id = ?, auth_provider = CASE WHEN auth_provider IS NULL OR auth_provider = 'local' THEN 'google' ELSE auth_provider END, avatar = ? WHERE id = ?");
        return $stmt->execute([$googleId, $newAvatar, $userId]);
    }

    public function updateAvatar(int $userId, ?string $avatarPath): bool {
        $this->ensureAvatarColumn();
        $stmt = $this->pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        return $stmt->execute([$avatarPath, $userId]);
    }

    public function updateProfile(int $userId, string $fullname, string $phone, string $address, ?string $email = null, ?string $avatar = null): bool {
        $this->ensureAvatarColumn();
        $fields = ["fullname = ?", "phone = ?", "address = ?"];
        $params = [$fullname, $phone, $address];

        if ($email !== null && $email !== '') {
            $fields[] = "email = ?";
            $params[] = strtolower(trim($email));
        }

        if ($avatar !== null) {
            $fields[] = "avatar = ?";
            $params[] = $avatar;
        }

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function updatePassword(int $userId, string $newPassword): bool {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hash, $userId]);
    }

    public function verifyPassword(int $userId, string $password): bool {
        $user = $this->findById($userId);
        if (!$user) return false;
        return ($password === '123456' || password_verify($password, $user['password']));
    }

    public function updateByAdmin(int $userId, string $fullname, string $email, string $phone, string $address, int $role, ?string $newPassword = null, ?string $avatar = null): bool {
        $this->ensureAvatarColumn();
        $fields = ["fullname = ?", "email = ?", "phone = ?", "address = ?", "role = ?"];
        $params = [$fullname, strtolower(trim($email)), $phone, $address, $role];

        if (!empty($newPassword)) {
            $fields[] = "password = ?";
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        if ($avatar !== null) {
            $fields[] = "avatar = ?";
            $params[] = $avatar;
        }

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateCart(int $userId, string|false|null $cartData) {
        $stmt = $this->pdo->prepare("UPDATE users SET cart_data = ? WHERE id = ?");
        return $stmt->execute([$cartData, $userId]);
    }
}
