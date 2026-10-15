<?php
class WarrantyModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->ensureTable();
    }

    public function ensureTable(): void {
        if (!empty($_SESSION['warranty_table_verified'])) {
            return;
        }

        $sql = "
        CREATE TABLE IF NOT EXISTS warranty_claims (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            order_id INT NOT NULL,
            detail_id INT NULL,
            product_id INT NULL,
            product_name VARCHAR(255) NOT NULL,
            imei VARCHAR(50) NOT NULL,
            customer_name VARCHAR(150) NOT NULL,
            customer_phone VARCHAR(20) NOT NULL,
            issue_type VARCHAR(100) NOT NULL,
            issue_description TEXT NULL,
            claim_method VARCHAR(50) DEFAULT 'store',
            status VARCHAR(50) DEFAULT 'Chờ tiếp nhận',
            admin_note TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_order_id (order_id),
            INDEX idx_imei (imei),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        try {
            $this->pdo->exec($sql);
            $_SESSION['warranty_table_verified'] = true;
        } catch (PDOException $e) {
            // Table creation or index already handled
        }
    }

    public function createClaim(array $data): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO warranty_claims 
            (user_id, order_id, detail_id, product_id, product_name, imei, customer_name, customer_phone, issue_type, issue_description, claim_method, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Chờ tiếp nhận')
        ");
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['order_id'],
            $data['detail_id'] ?? null,
            $data['product_id'] ?? null,
            $data['product_name'],
            $data['imei'],
            $data['customer_name'],
            $data['customer_phone'],
            $data['issue_type'],
            $data['issue_description'] ?? '',
            $data['claim_method'] ?? 'store'
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getClaimsByUserId(int $userId): array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM warranty_claims 
            WHERE user_id = ? 
            ORDER BY id DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLatestClaimForDevice(int $orderId, string $imei, ?int $detailId = null): ?array {
        if ($detailId !== null && $detailId > 0) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM warranty_claims 
                WHERE order_id = ? AND (imei = ? OR detail_id = ?)
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$orderId, $imei, $detailId]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT * FROM warranty_claims 
                WHERE order_id = ? AND imei = ? 
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$orderId, $imei]);
        }
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function getAllClaims(string $status = 'all', string $keyword = ''): array {
        $query = "SELECT * FROM warranty_claims WHERE 1=1";
        $params = [];

        if ($status !== 'all' && !empty($status)) {
            $query .= " AND status = ?";
            $params[] = $status;
        }

        if (!empty($keyword)) {
            $query .= " AND (product_name LIKE ? OR imei LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? OR CONCAT('#VP-', order_id) LIKE ?)";
            $kw = "%{$keyword}%";
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        $query .= " ORDER BY id DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClaimById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM warranty_claims WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateStatus(int $id, string $status, ?string $adminNote = null): bool {
        $stmt = $this->pdo->prepare("
            UPDATE warranty_claims 
            SET status = ?, admin_note = COALESCE(?, admin_note)
            WHERE id = ?
        ");
        return $stmt->execute([$status, $adminNote, $id]);
    }

    public function deleteClaim(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM warranty_claims WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getPendingCount(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM warranty_claims WHERE status = 'Chờ tiếp nhận'")->fetchColumn() ?: 0;
    }

    public function getStatusCounts(): array {
        $rows = $this->pdo->query("SELECT status, COUNT(*) as cnt FROM warranty_claims GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
        $counts = [
            'all' => 0,
            'Chờ tiếp nhận' => 0,
            'Đang kiểm tra' => 0,
            'Đã đổi mới' => 0,
            'Đã sửa chữa' => 0,
            'Từ chối' => 0
        ];
        foreach ($rows as $r) {
            $st = $r['status'];
            $cnt = (int)$r['cnt'];
            if (isset($counts[$st])) {
                $counts[$st] = $cnt;
            }
            $counts['all'] += $cnt;
        }
        return $counts;
    }
}
