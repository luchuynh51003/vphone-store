<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost';
$dbname = 'db_phone_store';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $prodCount = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($prodCount < 50) {
        $sqlDump = @file_get_contents(__DIR__ . '/../database/db_phone_store.sql');
        if ($sqlDump && preg_match('/INSERT INTO products[^;]+;/s', $sqlDump, $m)) {
            $insertQuery = str_replace('INSERT INTO products', 'INSERT IGNORE INTO products', $m[0]);
            $pdo->exec($insertQuery);
        }
    }

    $checkSync = $pdo->query("SELECT colors FROM products WHERE id = 10 LIMIT 1")->fetchColumn();
    if ($checkSync !== 'Bạc, Cam, Xanh') {
        $pdo->exec("UPDATE products SET colors = CASE id WHEN 1 THEN 'Đỏ Burgundy, Xanh Glacier Blue, Đen, Bạc' WHEN 2 THEN 'Đỏ Burgundy, Xanh Glacier Blue, Đen, Bạc' WHEN 3 THEN 'Trắng Ánh Sao, Trời Đêm' WHEN 5 THEN 'Đen Phantom' WHEN 6 THEN 'Tím Cobalt, Đen Classic, Xanh Sky Blue, Trắng Classic' WHEN 7 THEN 'Đỏ Ruihong' WHEN 8 THEN 'Đen Da Thuần, Xanh Lam' WHEN 9 THEN 'Đen' WHEN 10 THEN 'Bạc, Cam, Xanh' WHEN 11 THEN 'Bạc, Cam, Xanh' WHEN 12 THEN 'Đen, Trắng, Xanh' WHEN 13 THEN 'Đen, Trắng, Hồng, Xanh, Xanh Lá' WHEN 14 THEN 'Titan Sa Mạc, Titan Tự Nhiên, Titan Đen, Titan Trắng' WHEN 15 THEN 'Titan Sa Mạc, Titan Tự Nhiên, Titan Đen, Titan Trắng' WHEN 16 THEN 'Đen, Trắng, Hồng, Xanh Mòng Két, Xanh Lưu Ly' WHEN 17 THEN 'Đen, Trắng, Hồng, Xanh Mòng Két, Xanh Lưu Ly' WHEN 18 THEN 'Titan Tự Nhiên, Titan Xanh, Titan Đen, Titan Trắng' WHEN 19 THEN 'Đen, Hồng, Vàng, Xanh Lam, Xanh Mint' WHEN 20 THEN 'Tím Deep Purple, Vàng Gold, Bạc Silver, Đen Space' WHEN 21 THEN 'Xanh Dương, Tím, Vàng, Trắng Starlight, Đen Midnight, Đỏ' WHEN 22 THEN 'Trắng Starlight, Đen Midnight, Hồng, Xanh Lá, Đỏ, Xanh Dương' WHEN 23 THEN 'Tím, Xanh Lam, Xanh Lục, Đỏ, Trắng, Đen' WHEN 24 THEN 'Đen, Trắng, Tím, Vàng, Xanh Lục, Đỏ' WHEN 25 THEN 'Đen Midnight, Trắng Starlight, Đỏ Product Red' WHEN 26 THEN 'Xám Titan, Đen Titan, Tím Titan, Vàng Titan' WHEN 27 THEN 'Đen Onyx, Xám Marble, Tím Cobalt, Vàng Amber' WHEN 28 THEN 'Đen Onyx, Xám Marble, Tím Cobalt, Vàng Amber' WHEN 29 THEN 'Xanh Topaz, Đen Graphite, Xám Opal, Xanh Jade' WHEN 30 THEN 'Xám Metal, Hồng Rosé, Trắng Bạc' WHEN 31 THEN 'Xám Metal, Xanh Maya, Vàng Solar, Xanh Mint' WHEN 32 THEN 'Xanh Icy Blue, Kem Ivory, Đen Phantom' WHEN 33 THEN 'Đen Phantom, Xanh Botanic, Kem Cotton, Tím Lilac' WHEN 34 THEN 'Xanh Iceblue, Xanh Navy, Tím Lilac' WHEN 35 THEN 'Xanh Iceblue, Vàng Lemon, Xanh Navy' WHEN 36 THEN 'Xanh Lạc Quan, Vàng Cá Tính, Đen Bản Lĩnh' WHEN 37 THEN 'Đen Đá, Xanh Matcha, Bạc Xỉu' WHEN 38 THEN 'Đen, Xanh Dragon Crystal' WHEN 39 THEN 'Đen, Trắng, Xanh Ngọc Bích' WHEN 40 THEN 'Đen Midnight, Tím Cực Quang, Trắng Ánh Trăng' WHEN 41 THEN 'Đen Bóng Đêm, Xanh Đại Dương, Hồng Ngọc Trai' WHEN 42 THEN 'Đen Vũ Trụ' WHEN 43 THEN 'Vàng POCO, Đen, Xám' WHEN 44 THEN 'Vàng Cổ Điển, Đen Cổ Điển' WHEN 45 THEN 'Vàng Thạch Anh, Đen Kính' WHEN 46 THEN 'Bạc Ngân Hà, Đen Không Gian' WHEN 47 THEN 'Xanh Đại Dương, Xanh Sơn Lâm' WHEN 48 THEN 'Cam Hoàng Hôn, Xanh Tinh Tú, Đen' WHEN 49 THEN 'Cam Ánh Dương, Xanh Lục Bảo' WHEN 50 THEN 'Đen Obsidian, Trắng Sứ Porcelain, Xám Xanh Hazel, Hồng Rose Quartz' WHEN 51 THEN 'Đen Phantom' WHEN 52 THEN 'Đen, Bạc Bạch Kim, Xanh Rêu Khaki' WHEN 53 THEN 'Titan Tự Nhiên, Titan Xanh, Titan Đen' WHEN 54 THEN 'Tím Deep Purple, Vàng Gold, Đen Space' WHEN 55 THEN 'Trắng Ánh Sao, Đen Midnight, Hồng' WHEN 56 THEN 'Đen, Trắng, Đỏ' WHEN 57 THEN 'Đen Phantom, Kem Cotton, Xanh Botanic' WHEN 58 THEN 'Xanh Icy Blue, Đen Phantom' ELSE colors END WHERE id IN (1, 2, 3, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58);");
    }

    $checkImgSync = $pdo->query("SELECT image FROM products WHERE id = 10 LIMIT 1")->fetchColumn();
    if ($checkImgSync !== 'assets/images/products/iphone-17-promax.png') {
        $pdo->exec("UPDATE products SET image = 'assets/images/products/iphone-17-promax.png' WHERE id = 10;");
        $pdo->exec("UPDATE products SET image = 'assets/images/products/iphone-17-pro.png' WHERE id = 11;");
        $pdo->exec("UPDATE products SET image = 'assets/images/products/iphone-17-air.png' WHERE id = 12;");
        $pdo->exec("UPDATE products SET image = 'assets/images/products/iphone-17.png' WHERE id = 13;");
        $pdo->exec("UPDATE products SET image = 'assets/images/products/samsung-tri-fold.png' WHERE id = 5;");
    }
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

require_once __DIR__ . '/../app/views/includes/functions.php';

