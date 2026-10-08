<?php
require_once 'app/models/ProductModel.php';

class HomeController {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function index() {
        $prodModel = new ProductModel($this->pdo);
        $brandId = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

        $brands = $this->pdo->query("SELECT * FROM brands ORDER BY id ASC")->fetchAll();
        $products = $prodModel->getAll($brandId, $keyword);

        $pageTitle = 'V-Phone - Siêu Thị Flagship 2026 & Smartphone Chính Hãng';
        require_once 'app/views/home.php';
    }
}
