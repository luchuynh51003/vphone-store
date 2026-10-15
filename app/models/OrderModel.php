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
        if (($productId === null || $productId <= 0) && !empty($productName)) {
            $cleanName = preg_replace('/\s*\(.*$/u', '', $productName);
            $cleanName = trim($cleanName);
            if (!empty($cleanName)) {
                $stmt = $this->pdo->prepare("SELECT id FROM products WHERE name LIKE ? LIMIT 1");
                $stmt->execute([$cleanName . '%']);
                $fId = $stmt->fetchColumn();
                if ($fId) $productId = (int)$fId;
            }
        }
        $stmt = $this->pdo->prepare("INSERT INTO order_details (order_id, product_id, product_name, price, quantity, total_price) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$orderId, $productId, $productName, $price, $quantity, $totalPrice]);
    }

    public function getAll(): array {
        return $this->pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public function getByUserId(int $userId, string $status = 'all'): array {
        if ($status !== 'all') {
            $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE user_id = ? AND status = ? ORDER BY id DESC");
            $stmt->execute([$userId, $status]);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll();
    }

    public function getStatusCountsByUserId(int $userId): array {
        $stmt = $this->pdo->prepare("SELECT status, COUNT(*) as cnt FROM orders WHERE user_id = ? GROUP BY status");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $counts = [
            'all' => 0,
            'Chờ xử lý' => 0,
            'Đang giao hàng' => 0,
            'Đã giao thành công' => 0,
            'Đã hủy' => 0
        ];

        foreach ($rows as $r) {
            $counts[$r['status']] = (int)$r['cnt'];
            $counts['all'] += (int)$r['cnt'];
        }

        return $counts;
    }

    public function resolveDetailImage(array $detail): string {
        $pId = (int)($detail['product_id'] ?? 0);
        $pName = (string)($detail['product_name'] ?? '');

        // 1. Nếu chưa có product_id, tìm từ database
        if ($pId <= 0 && !empty($pName)) {
            $cleanName = preg_replace('/\s*\(.*$/u', '', $pName);
            $cleanName = trim($cleanName);
            if (!empty($cleanName)) {
                $stmt = $this->pdo->prepare("SELECT id, image FROM products WHERE name LIKE ? LIMIT 1");
                $stmt->execute([$cleanName . '%']);
                $found = $stmt->fetch();
                if ($found) {
                    $pId = (int)$found['id'];
                    if (empty($detail['product_image'])) {
                        $detail['product_image'] = $found['image'];
                    }
                }
            }
        }

        // 2. Tìm màu sắc từ tên biến thể
        $color = '';
        if (preg_match('/Màu:\s*([^,)\-]+)/u', $pName, $m)) {
            $color = trim($m[1]);
        }

        require_once __DIR__ . '/../data/product_color_map.php';
        if ($pId > 0 && function_exists('getAllProductColorMap')) {
            $map = getAllProductColorMap();
            if (!empty($color) && isset($map[$pId][$color]) && !empty($map[$pId][$color]) && file_exists($map[$pId][$color])) {
                return $map[$pId][$color];
            }
            // Nếu không có tiền tố "Màu:", đối soát trực tiếp các màu đã khai báo của sản phẩm (ưu tiên màu tên dài/chi tiết hơn)
            if (isset($map[$pId])) {
                $candidateColors = array_keys($map[$pId]);
                usort($candidateColors, function($a, $b) {
                    return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
                });
                foreach ($candidateColors as $colName) {
                    $colImg = $map[$pId][$colName];
                    if (mb_stripos($pName, $colName, 0, 'UTF-8') !== false && file_exists($colImg)) {
                        return $colImg;
                    }
                }
            }
        }

        // 3. Ảnh mặc định của sản phẩm
        if (!empty($detail['product_image']) && file_exists($detail['product_image'])) {
            return $detail['product_image'];
        }

        if ($pId > 0) {
            $stmt = $this->pdo->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$pId]);
            $img = $stmt->fetchColumn();
            if ($img && file_exists($img)) {
                return $img;
            }
        }

        // 4. Fallback nhận diện tên
        $lower = mb_strtolower($pName, 'UTF-8');
        if (strpos($lower, 'iphone 18') !== false) {
            if (strpos($lower, 'duo') !== false) return 'assets/images/products/iphone-18-duo.png';
            if (strpos($lower, 'standard') !== false) return 'assets/images/products/iphone-18.png';
            return 'assets/images/products/iphone-18-promax.png';
        }
        if (strpos($lower, 'iphone 17 pro max') !== false) return 'assets/images/products/iphone-17-promax.png';
        if (strpos($lower, 'iphone 17 pro') !== false) return 'assets/images/products/iphone-17-pro.png';
        if (strpos($lower, 'iphone 17 air') !== false) return 'assets/images/products/iphone-17-air.png';
        if (strpos($lower, 'iphone 17') !== false) return 'assets/images/products/iphone-17.png';
        if (strpos($lower, 'iphone 16') !== false) return 'assets/images/products/iphone-16-promax.png';
        if (strpos($lower, 'iphone 15') !== false) return 'assets/images/products/iphone-15-promax.png';
        if (strpos($lower, 'iphone 14') !== false) return 'assets/images/products/iphone-14-promax.png';
        if (strpos($lower, 'iphone 13') !== false) return 'assets/images/products/iphone-13.png';
        if (strpos($lower, 'iphone 12') !== false) return 'assets/images/products/iphone-12.jpg';
        if (strpos($lower, 'iphone 11') !== false) return 'assets/images/products/iphone-11.png';
        if (strpos($lower, 's26 ultra') !== false) return 'assets/images/products/samsung-s26-ultra.png';
        if (strpos($lower, 'tri-fold') !== false) return 'assets/images/products/samsung-tri-fold.png';
        if (strpos($lower, 's24') !== false) return 'assets/images/products/samsung-s24-ultra.png';
        if (strpos($lower, 'z fold') !== false) return 'assets/images/products/samsung-z-fold6.png';
        if (strpos($lower, 'z flip') !== false) return 'assets/images/products/samsung-z-flip6.jpg';
        if (strpos($lower, 'mate xt') !== false) return 'assets/images/products/huawei-mate-xt.png';
        if (strpos($lower, 'xiaomi') !== false) return 'assets/images/products/xiaomi-16-ultra.png';
        if (strpos($lower, 'oppo') !== false) return 'assets/images/products/oppo-find-n5.png';
        if (strpos($lower, 'pixel') !== false) return 'assets/images/products/google-pixel-9.png';
        if (strpos($lower, 'rog') !== false) return 'assets/images/products/asus-rog-phone-8.jpg';
        if (strpos($lower, 'sony') !== false) return 'assets/images/products/sony-xperia-1-vi.png';

        return 'assets/images/products/iphone-16-promax.png';
    }

    public function getDetailsByOrderId(int $orderId): array {
        $stmt = $this->pdo->prepare("
            SELECT od.*, 
                   COALESCE(p.image, p2.image, '') AS product_image,
                   COALESCE(od.product_id, p2.id) AS resolved_product_id
            FROM order_details od
            LEFT JOIN products p ON od.product_id = p.id
            LEFT JOIN products p2 ON (od.product_id IS NULL OR od.product_id = 0) AND od.product_name LIKE CONCAT(p2.name, '%')
            WHERE od.order_id = ?
            ORDER BY od.id ASC
        ");
        $stmt->execute([$orderId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['product_image'] = $this->resolveDetailImage($r);
        }
        return $rows;
    }

    public function getDetailsForOrders(array $orderIds): array {
        if (empty($orderIds)) return [];
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = $this->pdo->prepare("
            SELECT od.*, 
                   COALESCE(p.image, p2.image, '') AS product_image,
                   COALESCE(od.product_id, p2.id) AS resolved_product_id
            FROM order_details od
            LEFT JOIN products p ON od.product_id = p.id
            LEFT JOIN products p2 ON (od.product_id IS NULL OR od.product_id = 0) AND od.product_name LIKE CONCAT(p2.name, '%')
            WHERE od.order_id IN ($placeholders)
            ORDER BY od.id ASC
        ");
        $stmt->execute($orderIds);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $row['product_image'] = $this->resolveDetailImage($row);
            $result[$row['order_id']][] = $row;
        }
        return $result;
    }


    public function findOrderForTracking(string|int $orderCode, string $phone): ?array {
        // Chuẩn hóa mã đơn hàng: chấp nhận "#VP-123", "VP-123", "123"
        $cleanId = (int)preg_replace('/[^0-9]/', '', (string)$orderCode);
        if ($cleanId <= 0) return null;

        // Chuẩn hóa số điện thoại: bỏ khoảng trắng, dấu gạch ngang, dấu chấm
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($cleanPhone)) return null;

        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$cleanId]);
        $order = $stmt->fetch();
        if (!$order) return null;

        $orderPhone = preg_replace('/[^0-9]/', '', $order['phone']);
        // So khớp SĐT (cho phép 9 số cuối hoặc so sánh trực tiếp để tránh lỗi tiền tố 84 / 0)
        if ($orderPhone === $cleanPhone || substr($orderPhone, -9) === substr($cleanPhone, -9)) {
            return $order;
        }

        return null;
    }

    public function cancelOrder(int $orderId, ?int $userId = null, string $cancelReason = ''): array {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            return ['success' => false, 'message' => 'Không tìm thấy đơn hàng cần hủy.'];
        }

        if ($userId !== null && (int)$order['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'Bạn không có quyền hủy đơn hàng này.'];
        }

        if ($order['status'] !== 'Chờ xử lý') {
            return ['success' => false, 'message' => 'Đơn hàng hiện đang ở trạng thái "' . $order['status'] . '", không thể tự hủy. Vui lòng liên hệ hotline 1800 6868 để được hỗ trợ!'];
        }

        $note = $order['note'] ?? '';
        if (!empty($cancelReason)) {
            $note .= " [Khách hủy: " . htmlspecialchars($cancelReason) . "]";
        }

        $up = $this->pdo->prepare("UPDATE orders SET status = 'Đã hủy', note = ? WHERE id = ?");
        $up->execute([$note, $orderId]);

        // Phục hồi lại số lượng tồn kho
        require_once __DIR__ . '/ProductModel.php';
        $prodModel = new ProductModel($this->pdo);
        $details = $this->getDetailsByOrderId($orderId);
        foreach ($details as $d) {
            if (!empty($d['product_id'])) {
                $color = '';
                if (preg_match('/Màu:\s*([^)]+)/u', $d['product_name'], $m)) {
                    $color = trim($m[1]);
                }
                $prodModel->restoreStock((int)$d['product_id'], (int)$d['quantity'], $color);
            }
        }

        return ['success' => true, 'message' => 'Hủy đơn hàng #VP-' . $orderId . ' thành công! Số lượng sản phẩm đã được hoàn lại.'];
    }
}

