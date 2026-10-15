<?php
class AddressModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->ensureTable();
    }

    public function ensureTable(): void {
        if (!empty($_SESSION['address_table_verified'])) {
            return;
        }

        try {
            $sql = "CREATE TABLE IF NOT EXISTS user_addresses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                recipient_name VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                address VARCHAR(255) NOT NULL,
                label VARCHAR(50) DEFAULT 'Nhà riêng',
                is_default TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
            $this->pdo->exec($sql);

            // Tự động chuyển địa chỉ cũ từ bảng users nếu chưa có trong user_addresses
            $cnt = (int)$this->pdo->query("SELECT COUNT(*) FROM user_addresses")->fetchColumn();
            if ($cnt === 0) {
                $this->pdo->exec("
                    INSERT INTO user_addresses (user_id, recipient_name, phone, address, is_default)
                    SELECT id, fullname, IFNULL(NULLIF(phone,''), '0901234567'), IFNULL(NULLIF(address,''), 'Chưa cập nhật'), 1
                    FROM users
                    WHERE id NOT IN (SELECT DISTINCT user_id FROM user_addresses)
                ");
            }

            $_SESSION['address_table_verified'] = true;
        } catch (Throwable $e) {}
    }

    public function getByUserId(int $userId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getById(int $addressId, int $userId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getDefaultByUserId(int $userId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND is_default = 1 LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) {
            $stmt = $this->pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY id ASC LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
        }
        return $row ?: null;
    }

    public function add(int $userId, string $recipientName, string $phone, string $address, string $label = 'Nhà riêng', bool $isDefault = false): int {
        // Kiểm tra xem người dùng đã có địa chỉ nào chưa
        $existingCountStmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
        $existingCountStmt->execute([$userId]);
        $hasAny = (int)$existingCountStmt->fetchColumn() > 0;

        if (!$hasAny) {
            $isDefault = true; // Địa chỉ đầu tiên luôn là mặc định
        }

        if ($isDefault) {
            $resetStmt = $this->pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
            $resetStmt->execute([$userId]);
        }

        $stmt = $this->pdo->prepare("INSERT INTO user_addresses (user_id, recipient_name, phone, address, label, is_default) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $recipientName, $phone, $address, $label, $isDefault ? 1 : 0]);
        $newId = (int)$this->pdo->lastInsertId();

        if ($isDefault) {
            $this->syncUserDefault($userId, $phone, $address);
        }

        return $newId;
    }

    public function update(int $addressId, int $userId, string $recipientName, string $phone, string $address, string $label = 'Nhà riêng', bool $isDefault = false): bool {
        if ($isDefault) {
            $resetStmt = $this->pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
            $resetStmt->execute([$userId]);
        }

        $stmt = $this->pdo->prepare("UPDATE user_addresses SET recipient_name = ?, phone = ?, address = ?, label = ?, is_default = ? WHERE id = ? AND user_id = ?");
        $ok = $stmt->execute([$recipientName, $phone, $address, $label, $isDefault ? 1 : 0, $addressId, $userId]);

        if ($ok && $isDefault) {
            $this->syncUserDefault($userId, $phone, $address);
        }

        return $ok;
    }

    public function setDefault(int $addressId, int $userId): bool {
        $addr = $this->getById($addressId, $userId);
        if (!$addr) return false;

        $this->pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
        $this->pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$addressId, $userId]);

        $this->syncUserDefault($userId, $addr['phone'], $addr['address']);
        return true;
    }

    public function delete(int $addressId, int $userId): array {
        $addr = $this->getById($addressId, $userId);
        if (!$addr) {
            return ['success' => false, 'message' => 'Không tìm thấy địa chỉ cần xóa!'];
        }

        $all = $this->getByUserId($userId);
        if (count($all) <= 1) {
            return ['success' => false, 'message' => 'Bạn cần giữ ít nhất 1 địa chỉ nhận hàng trong sổ địa chỉ!'];
        }

        $isDefault = ($addr['is_default'] == 1);

        $stmt = $this->pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);

        // Nếu vừa xóa địa chỉ mặc định, tự động chỉ định địa chỉ còn lại đầu tiên làm mặc định
        if ($isDefault) {
            $remaining = $this->getByUserId($userId);
            if (!empty($remaining)) {
                $newDefault = $remaining[0];
                $this->setDefault((int)$newDefault['id'], $userId);
            }
        }

        return ['success' => true, 'message' => 'Đã xóa địa chỉ thành công!'];
    }

    private function syncUserDefault(int $userId, string $phone, string $address): void {
        $stmt = $this->pdo->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$phone, $address, $userId]);

        if (isset($_SESSION['user']) && (int)$_SESSION['user']['id'] === $userId) {
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['address'] = $address;
        }
    }
}
