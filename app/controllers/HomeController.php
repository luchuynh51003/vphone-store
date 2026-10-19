<?php
require_once 'app/models/ProductModel.php';

class HomeController {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function index() {
        $prodModel = new ProductModel($this->pdo);
        $brandId = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

        $filters = [
            'price_range' => $_GET['price'] ?? ($_GET['price_range'] ?? 'all'),
            'ram' => $_GET['ram'] ?? 'all',
            'rom' => $_GET['rom'] ?? 'all',
            'sort' => $_GET['sort'] ?? 'default',
            'filter_type' => $_GET['filter_type'] ?? 'all',
            'min_price' => $_GET['min_price'] ?? null,
            'max_price' => $_GET['max_price'] ?? null,
        ];

        $brands = $this->pdo->query("SELECT * FROM brands ORDER BY id ASC")->fetchAll();
        $products = $prodModel->getAll(0, $keyword);

        require_once 'app/models/ReviewModel.php';
        $ratingMap = (new ReviewModel($this->pdo))->getSummaryMapForAllProducts();

        $pageTitle = 'V-Phone - Siêu Thị Flagship 2026 & Smartphone Chính Hãng';
        require_once 'app/views/home.php';
    }

    public function warranty() {
        $pdo = $this->pdo;
        require_once 'app/models/OrderModel.php';
        require_once 'app/models/ProductModel.php';
        require_once 'app/models/WarrantyModel.php';
        $orderModel = new OrderModel($pdo);
        $warrantyModel = new WarrantyModel($pdo);

        $currentUser = $_SESSION['user'] ?? null;
        $userWarrantyDevices = [];

        if ($currentUser && !empty($currentUser['id'])) {
            $userPhone = $currentUser['phone'] ?? '';
            $stmt = $pdo->prepare("
                SELECT o.id as order_id, o.created_at, o.phone, o.fullname, o.status,
                       od.id as detail_id, od.product_id, od.product_name, od.price, od.quantity, od.total_price
                FROM orders o
                JOIN order_details od ON o.id = od.order_id
                WHERE (o.user_id = :uid OR (o.user_id IS NULL AND :phone != '' AND o.phone = :phone)) 
                  AND o.status = 'Đã giao thành công'
                ORDER BY o.id DESC
            ");
            $stmt->execute([':uid' => (int)$currentUser['id'], ':phone' => $userPhone]);
            $rawItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rawItems as $item) {
                $detailForImg = [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name']
                ];
                $resolvedImg = $orderModel->resolveDetailImage($detailForImg);
                
                $orderDate = !empty($item['created_at']) ? strtotime($item['created_at']) : time();
                $isUsed = (mb_stripos($item['product_name'], 'cũ') !== false || mb_stripos($item['product_name'], 'like new') !== false);
                $warrantyMonths = $isUsed ? 6 : 12;
                $expireDate = strtotime("+{$warrantyMonths} months", $orderDate);
                $now = time();
                $totalDays = max(1, (int)round(($expireDate - $orderDate) / 86400));
                $daysUsed = max(0, (int)round(($now - $orderDate) / 86400));
                $daysRemaining = (int)ceil(($expireDate - $now) / 86400);
                $percentUsed = min(100, max(0, (int)round(($daysUsed / $totalDays) * 100)));
                $isExpired = ($daysRemaining < 0);

                $imeiSeed = (int)$item['order_id'] * 1000 + (int)$item['detail_id'];
                $cleanHash = abs(crc32((string)$imeiSeed));
                $imei = '35' . substr(str_pad((string)$cleanHash, 13, '8', STR_PAD_RIGHT), 0, 13);

                // Fetch latest claim for this device if exists
                $latestClaim = $warrantyModel->getLatestClaimForDevice((int)$item['order_id'], $imei, (int)$item['detail_id']);

                $userWarrantyDevices[] = [
                    'order_id' => $item['order_id'],
                    'detail_id' => $item['detail_id'],
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_image' => $resolvedImg,
                    'phone' => $item['phone'],
                    'imei' => $imei,
                    'purchase_date' => date('d/m/Y', $orderDate),
                    'expire_date' => date('d/m/Y', $expireDate),
                    'warranty_months' => $warrantyMonths,
                    'days_remaining' => max(0, $daysRemaining),
                    'percent_used' => $percentUsed,
                    'is_expired' => $isExpired,
                    'is_used' => $isUsed,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'claim' => $latestClaim
                ];
            }
        }

        $pageTitle = 'Chính Sách Bảo Hành & Đổi Trả Toàn Diện - V-Phone Store';
        require_once 'app/views/warranty.php';
    }

    public function submitClaim(): void {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức yêu cầu không hợp lệ.']);
            exit;
        }

        require_once 'app/models/WarrantyModel.php';
        $warrantyModel = new WarrantyModel($this->pdo);

        $currentUser = $_SESSION['user'] ?? null;
        $orderId = (int)($_POST['order_id'] ?? 0);
        $detailId = (int)($_POST['detail_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $productName = trim($_POST['product_name'] ?? '');
        $imei = trim($_POST['imei'] ?? '');
        $issueType = trim($_POST['issue_type'] ?? '');
        $issueDesc = trim($_POST['issue_description'] ?? '');
        $claimMethod = trim($_POST['claim_method'] ?? 'store');

        $customerName = !empty($currentUser['fullname']) ? $currentUser['fullname'] : trim($_POST['customer_name'] ?? '');
        $customerPhone = !empty($currentUser['phone']) ? $currentUser['phone'] : trim($_POST['customer_phone'] ?? '');

        if ($orderId <= 0 || empty($productName) || empty($imei) || empty($issueType)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp đầy đủ thông tin sự cố của máy.']);
            exit;
        }

        $issueMap = [
            'nguon' => 'Lỗi nguồn / sạc không vào điện',
            'manhinh' => 'Lỗi màn hình / cảm ứng / sọc màn',
            'camera_loa' => 'Lỗi camera / loa rè / mic thoại',
            'song_wifi' => 'Lỗi kết nối sóng 4G/5G / Wifi',
            'pin' => 'Pin chai tụt nhanh / phồng pin / nóng máy',
            'khac' => 'Sự cố phần cứng / phần mềm khác'
        ];
        $readableIssue = $issueMap[$issueType] ?? $issueType;

        $claimId = $warrantyModel->createClaim([
            'user_id' => $currentUser ? (int)$currentUser['id'] : null,
            'order_id' => $orderId,
            'detail_id' => $detailId > 0 ? $detailId : null,
            'product_id' => $productId > 0 ? $productId : null,
            'product_name' => $productName,
            'imei' => $imei,
            'customer_name' => $customerName ?: 'Khách hàng V-Phone',
            'customer_phone' => $customerPhone ?: '18006868',
            'issue_type' => $readableIssue,
            'issue_description' => $issueDesc,
            'claim_method' => $claimMethod === 'shipping' ? 'Gửi bưu điện hỏa tốc' : 'Mang trực tiếp tới cửa hàng'
        ]);

        if ($claimId > 0) {
            echo json_encode([
                'success' => true,
                'claim_id' => $claimId,
                'message' => 'Yêu cầu bảo hành đã được tiếp nhận thành công! Kỹ thuật viên V-Phone sẽ liên hệ theo SĐT ' . $customerPhone . ' trong vòng 15 phút.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể tạo yêu cầu bảo hành. Vui lòng thử lại hoặc gọi 1800 6868.']);
        }
        exit;
    }

    public function about(): void {
        $pageTitle = 'Giới Thiệu V-Phone Store - Hệ Thống Smartphone Chính Hãng';
        require_once 'app/views/about.php';
    }

    public function stores(): void {
        $pageTitle = 'Hệ Thống Cửa Hàng V-Phone - 15+ Chi Nhánh Toàn Quốc';
        require_once 'app/views/stores.php';
    }
}
