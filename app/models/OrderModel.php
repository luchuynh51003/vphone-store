<?php
class OrderModel {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function create(?int $userId, string $fullname, string $phone, string $address, ?string $note, int|float $totalMoney, string $method) {
        $stmt = $this->pdo->prepare("INSERT INTO orders (user_id, fullname, phone, address, note, total_money, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Chờ xử lý')");
        $stmt->execute([$userId, $fullname, $phone, $address, $note, $totalMoney, $method]);
        return $this->pdo->lastInsertId();
    }

    public function addDetail(int $orderId, ?int $productId, string $productName, int|float $price, int $quantity, int|float $totalPrice) {
        $stmt = $this->pdo->prepare("INSERT INTO order_details (order_id, product_id, product_name, price, quantity, total_price) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$orderId, $productId, $productName, $price, $quantity, $totalPrice]);
    }

    public function getAll(): array {
        return $this->pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
    }
}
