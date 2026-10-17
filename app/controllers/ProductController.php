<?php
class ProductController {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function detail() {
        $pdo = $this->pdo;
        require_once __DIR__ . '/../../config/database.php';
        require_once 'app/models/ProductModel.php';
        (new ProductModel($pdo))->ensureStorageOptionsColumn();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { header('Location: index.php'); exit; }

        $stmt = $pdo->prepare("SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) { echo "<h2 style='text-align:center;margin-top:50px;'>Không tìm thấy sản phẩm! <a href='index.php'>Quay lại</a></h2>"; exit; }

        $pageTitle = $product['name'] . ' - V-Phone Chính Hãng';
        $basePrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) ? $product['sale_price'] : $product['price'];
        $baseOldPrice = $product['price'];
        $saving = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) ? ($product['price'] - $product['sale_price']) : 0;

        $colorList = array_map('trim', explode(',', $product['colors'] ?? 'Đen, Trắng'));
        $colorQuantitiesData = json_decode($product['color_quantities'] ?? '', true);
        $colorStockMap = [];
        if (is_array($colorQuantitiesData)) {
            foreach ($colorQuantitiesData as $cq) {
                if (isset($cq['color'])) {
                    $colorStockMap[$cq['color']] = (int)$cq['quantity'];
                }
            }
        }

        $relStmt = $pdo->prepare("SELECT * FROM products WHERE brand_id = ? AND id != ? LIMIT 4");
        $relStmt->execute([$product['brand_id'], $id]);
        $relatedProducts = $relStmt->fetchAll();

        require_once 'app/models/ReviewModel.php';
        $reviewModel = new ReviewModel($pdo);
        $ratingSummary = $reviewModel->getRatingSummary($id);
        $reviews = $reviewModel->getReviewsByProduct($id);

        $currentUser = $_SESSION['user'] ?? null;
        $userHasPurchased = $reviewModel->hasUserPurchased(
            $id,
            $currentUser ? (int)$currentUser['id'] : null,
            $currentUser ? ($currentUser['phone'] ?? null) : null
        );

        require_once 'app/views/product_detail.php';
    }

    public function used() {
        $pdo = $this->pdo;
        require_once 'app/models/ReviewModel.php';
        $ratingMap = (new ReviewModel($pdo))->getSummaryMapForAllProducts();
        require_once 'app/views/used_phones.php';
    }

    public function news() {
        $pdo = $this->pdo;
        require_once 'app/views/news.php';
    }

    public function compare() {
        $pdo = $this->pdo;
        require_once __DIR__ . '/../../config/database.php';
        require_once 'app/models/ProductModel.php';
        require_once 'app/models/ReviewModel.php';

        $prodModel = new ProductModel($pdo);
        $prodModel->ensureStorageOptionsColumn();
        $prodModel->ensureColorQuantitiesColumn();
        $reviewModel = new ReviewModel($pdo);

        $p1Id = isset($_GET['p1']) ? (int)$_GET['p1'] : (isset($_GET['id1']) ? (int)$_GET['id1'] : 0);
        $p2Id = isset($_GET['p2']) ? (int)$_GET['p2'] : (isset($_GET['id2']) ? (int)$_GET['id2'] : 0);
        $p3Id = isset($_GET['p3']) ? (int)$_GET['p3'] : (isset($_GET['id3']) ? (int)$_GET['id3'] : 0);

        // Mặc định cặp đối đầu kinh điển: iPhone 18 Pro Max (1) vs Galaxy S26 Ultra (6)
        if ($p1Id <= 0 && $p2Id <= 0) {
            $p1Id = 1;
            $p2Id = 6;
        } elseif ($p1Id > 0 && $p2Id <= 0) {
            $p2Id = ($p1Id === 1) ? 6 : (($p1Id === 6) ? 1 : 6);
        } elseif ($p2Id > 0 && $p1Id <= 0) {
            $p1Id = ($p2Id === 1) ? 6 : (($p2Id === 6) ? 1 : 1);
        }

        $product1 = $prodModel->getById($p1Id);
        $product2 = $prodModel->getById($p2Id);
        $product3 = ($p3Id > 0 && $p3Id !== $p1Id && $p3Id !== $p2Id) ? $prodModel->getById($p3Id) : null;

        // Fallbacks nếu ID không tồn tại
        if (!$product1) {
            $fallback = $prodModel->getAll();
            $product1 = $fallback[0] ?? null;
            if ($product1) $p1Id = (int)$product1['id'];
        }
        if (!$product2) {
            $fallback = $prodModel->getAll();
            $product2 = $fallback[1] ?? $fallback[0] ?? null;
            if ($product2) $p2Id = (int)$product2['id'];
        }

        if (!$product1 || !$product2) {
            header('Location: index.php');
            exit;
        }

        // Đánh giá review
        $ratingSummary1 = $reviewModel->getRatingSummary($product1['id']);
        $ratingSummary2 = $reviewModel->getRatingSummary($product2['id']);
        $ratingSummary3 = $product3 ? $reviewModel->getRatingSummary($product3['id']) : null;

        // Danh sách tất cả sản phẩm & hãng cho Modal chọn máy
        $allProducts = $prodModel->getAll();
        $brandStmt = $pdo->query("SELECT * FROM brands ORDER BY id ASC");
        $brands = $brandStmt->fetchAll(PDO::FETCH_ASSOC);

        // Danh sách cặp đấu HOT
        $hotMatchups = [
            ['p1' => 1, 'p2' => 6, 'title' => 'iPhone 18 Pro Max vs Galaxy S26 Ultra', 'badge' => 'Siêu Flagship 2026', 'hot' => true],
            ['p1' => 14, 'p2' => 26, 'title' => 'iPhone 16 Pro Max vs Galaxy S24 Ultra', 'badge' => 'Kỳ Phùng Địch Thủ', 'hot' => true],
            ['p1' => 5, 'p2' => 7, 'title' => 'Galaxy Z Tri-Fold vs Huawei Mate XT', 'badge' => 'Đại Chiến Màn Gập 3', 'hot' => true],
            ['p1' => 2, 'p2' => 8, 'title' => 'iPhone 18 Pro vs Xiaomi 16 Ultra', 'badge' => 'Hiệu Năng & Leica', 'hot' => false],
            ['p1' => 3, 'p2' => 9, 'title' => 'iPhone Duo vs OPPO Find N5 Duo', 'badge' => 'Màn Gập Siêu Mỏng', 'hot' => false],
            ['p1' => 10, 'p2' => 6, 'title' => 'iPhone 17 Pro Max vs Galaxy S26 Ultra', 'badge' => 'Cân Não Flagship', 'hot' => false]
        ];

        $pageTitle = "So Sánh: " . $product1['name'] . " VS " . $product2['name'] . ($product3 ? " VS " . $product3['name'] : "") . " - V-Phone Store";
        require_once 'app/views/compare.php';
    }
}
