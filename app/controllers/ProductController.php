<?php
class ProductController {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function detail() {
        $pdo = $this->pdo; // Khởi tạo biến $pdo để View sử dụng trực tiếp
        require_once 'config/database.php';
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

        require_once 'app/views/product_detail.php';
    }

    public function used() {
        $pdo = $this->pdo;
        require_once 'app/views/used_phones.php';
    }

    public function news() {
        $pdo = $this->pdo;
        require_once 'app/views/news.php';
    }
}
