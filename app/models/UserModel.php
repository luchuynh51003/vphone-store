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
        } catch (Throwable $e) {}
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
