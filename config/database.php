<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = 'localhost';
$dbname   = 'db_phone_store';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS db_phone_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE db_phone_store;");

    // Đảm bảo bảng users tồn tại
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fullname VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        address VARCHAR(255),
        password VARCHAR(255) NOT NULL,
        role TINYINT DEFAULT 0,
        cart_data LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    // Tự động nạp tài khoản nếu bảng users đang có 0 người dùng
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        $passHash = password_hash('123456', PASSWORD_DEFAULT);
        $userStmt = $pdo->prepare("INSERT INTO users (fullname, email, phone, address, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $userStmt->execute(['Võ Minh Hiếu', 'hieu@vphone.vn', '0901234567', 'Quận 1, TP. Hồ Chí Minh', $passHash, 0]);
        $userStmt->execute(['Huỳnh Bá Lực', 'luc@vphone.vn', '0909888999', 'Quận 7, TP. Hồ Chí Minh', $passHash, 0]);
        $userStmt->execute(['Quản Trị Viên V-Phone', 'admin@vphone.vn', '18006868', 'Trụ sở V-Phone Store', $passHash, 1]);
    }

} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
