<?php
require_once '../config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 1) {
    header('Location: ../login.php');
    exit;
}

$adminPage = basename($_SERVER['PHP_SELF']);

// Đếm số máy sắp hết hàng để gắn badge cảnh báo đỏ
$alertStock = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity <= 5")->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? "V-Phone Admin" ?></title><link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg?v=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --admin-dark: #0f172a;
            --admin-blue: #0066cc;
            --admin-light: #f8fafc;
        }
        body {
            background-color: var(--admin-light);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .admin-sidebar {
            width: 260px;
            min-height: 100vh;
            background: var(--admin-dark);
            color: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        .admin-content {
            margin-left: 260px;
            padding: 30px;
        }
        .admin-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #94a3b8;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.92rem;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        .admin-nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }
        .admin-nav-link.active {
            color: #ffffff;
            background: rgba(0, 102, 204, 0.2);
            border-left-color: var(--admin-blue);
        }
        .admin-nav-link i {
            width: 24px;
            font-size: 1.1rem;
            margin-right: 12px;
        }
        @media (max-width: 991px) {
            .admin-sidebar { position: relative; width: 100%; min-height: auto; }
            .admin-content { margin-left: 0; padding: 15px; }
        }
    </style>
</head>
<body>
<div class="d-flex flex-column flex-lg-row">
    <!-- THANH MENU BÊN TRÁI -->
    <div class="admin-sidebar p-3 shadow">
        <div class="d-flex align-items-center mb-4 px-2 pt-2">
            <span class="bg-white text-primary rounded-3 px-2 py-1 me-2 fw-black">
                <i class="fa-solid fa-bolt"></i> V
            </span>
            <span class="fs-5 fw-bold text-white">V-Phone Admin</span>
        </div>

        <div class="p-2 mb-3 bg-secondary bg-opacity-25 rounded-3 d-flex align-items-center">
            <i class="fa-solid fa-user-shield text-info fs-3 me-2"></i>
            <div class="min-w-0">
                <div class="text-white fw-bold small text-truncate"><?= htmlspecialchars($_SESSION['user']['fullname']) ?></div>
                <small class="text-warning">Quản trị viên cấp cao</small>
            </div>
        </div>

        <nav class="nav flex-column mb-auto">
            <a href="index.php" class="admin-nav-link <?= ($adminPage === 'index.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-pie"></i> Tổng quan Dashboard
            </a>
            <a href="products.php" class="admin-nav-link <?= ($adminPage === 'products.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-mobile-screen"></i> Quản lý Sản phẩm
            </a>

            <!-- MỤC QUẢN LÝ TỒN KHO MỚI THÊM CÓ SỐ CẢNH BÁO -->
            <a href="inventory.php" class="admin-nav-link <?= ($adminPage === 'inventory.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-stacked"></i> Quản lý Tồn kho
                <?php if ($alertStock > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto small"><?= $alertStock ?></span>
                <?php endif; ?>
            </a>

            <a href="orders.php" class="admin-nav-link <?= ($adminPage === 'orders.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-packing"></i> Quản lý Đơn hàng
            </a>
            <a href="users.php" class="admin-nav-link <?= ($adminPage === 'users.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> Quản lý Khách hàng
            </a>
            <hr class="border-secondary my-3">
            <a href="../index.php" class="admin-nav-link text-info">
                <i class="fa-solid fa-store"></i> Xem Website bán hàng
            </a>
            <a href="../logout.php" class="admin-nav-link text-danger">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
            </a>
        </nav>
    </div>

    <div class="admin-content flex-grow-1">

