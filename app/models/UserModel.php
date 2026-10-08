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

    public function updateCart(int $userId, string|false|null $cartData) {
        $stmt = $this->pdo->prepare("UPDATE users SET cart_data = ? WHERE id = ?");
        return $stmt->execute([$cartData, $userId]);
    }
}
