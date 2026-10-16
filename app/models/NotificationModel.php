<?php
class NotificationModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function ensureTable(): void {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS user_notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    phone VARCHAR(20) NULL,
                    order_id INT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    cancel_reason VARCHAR(255) NULL,
                    type VARCHAR(50) DEFAULT 'order_cancelled',
                    is_read TINYINT(1) DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX (user_id),
                    INDEX (phone),
                    INDEX (order_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (\Throwable $e) {}
    }

    public function create(?int $userId, ?string $phone, ?int $orderId, string $title, string $message, ?string $cancelReason = null, string $type = 'order_cancelled'): int {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("
            INSERT INTO user_notifications (user_id, phone, order_id, title, message, cancel_reason, type, is_read)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([$userId, $phone, $orderId, $title, $message, $cancelReason, $type]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getByUserId(int $userId, int $limit = 10): array {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_notifications 
            WHERE user_id = ? 
            ORDER BY id DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCancelledOrdersByUserId(int $userId, int $limit = 20): array {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_notifications 
            WHERE user_id = ? AND type = 'order_cancelled' 
            ORDER BY id DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnreadCount(int $userId): int {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getByOrderId(int $orderId): ?array {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("SELECT * FROM user_notifications WHERE order_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByPhone(string $phone, int $limit = 10): array {
        $this->ensureTable();
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($cleanPhone)) return [];
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_notifications 
            WHERE REPLACE(phone, ' ', '') LIKE ? 
            ORDER BY id DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, '%' . substr($cleanPhone, -9), PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead(int $id, int $userId): bool {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public function markAllAsRead(int $userId): bool {
        $this->ensureTable();
        $stmt = $this->pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}
