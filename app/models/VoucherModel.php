<?php
class VoucherModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function ensureTable(): void {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS vouchers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(40) NOT NULL UNIQUE,
            discount_type ENUM('percent', 'fixed') NOT NULL,
            discount_value DECIMAL(12,0) NOT NULL,
            minimum_order DECIMAL(12,0) NOT NULL DEFAULT 0,
            max_discount DECIMAL(12,0) DEFAULT NULL,
            starts_at DATETIME DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL,
            usage_limit INT DEFAULT NULL,
            used_count INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $seed = $this->pdo->prepare("INSERT IGNORE INTO vouchers
            (code, discount_type, discount_value, minimum_order, max_discount)
            VALUES (?, ?, ?, ?, ?)");
        $vouchers = [
            ['VPHONE5', 'percent', 5, 500000, 250000],
            ['VPHONE10', 'percent', 10, 1000000, 500000],
            ['VPHONE15', 'percent', 15, 5000000, 1000000],
            ['FLASH20', 'percent', 20, 10000000, 2000000],
            ['WELCOME50', 'fixed', 50000, 500000, null],
            ['GIAM100K', 'fixed', 100000, 1000000, null],
            ['GIAM200K', 'fixed', 200000, 2000000, null],
            ['GIAM500K', 'fixed', 500000, 5000000, null]
        ];

        foreach ($vouchers as $voucher) {
            $seed->execute($voucher);
        }
    }

    public function findApplicable(string $code, int $subtotal): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM vouchers
            WHERE code = ? AND is_active = 1
                AND (starts_at IS NULL OR starts_at <= NOW())
                AND (expires_at IS NULL OR expires_at >= NOW())
                AND (usage_limit IS NULL OR used_count < usage_limit)
            LIMIT 1");
        $stmt->execute([strtoupper(trim($code))]);
        $voucher = $stmt->fetch();

        if (!$voucher || $subtotal < (int)$voucher['minimum_order']) {
            return null;
        }

        return $voucher;
    }

    public function calculateDiscount(array $voucher, int $subtotal): int {
        if ($voucher['discount_type'] === 'percent') {
            $discount = (int)round($subtotal * (float)$voucher['discount_value'] / 100);
            if ($voucher['max_discount'] !== null) {
                $discount = min($discount, (int)$voucher['max_discount']);
            }
        } else {
            $discount = (int)$voucher['discount_value'];
        }

        return min($subtotal, max(0, $discount));
    }

    public function recordUse(int $voucherId): void {
        $stmt = $this->pdo->prepare('UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?');
        $stmt->execute([$voucherId]);
    }
}
