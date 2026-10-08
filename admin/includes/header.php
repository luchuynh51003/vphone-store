<?php
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 1) {
    header('Location: ../index.php?show_login=1');
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --admin-dark: #0b1329;
            --admin-blue: #0066cc;
            --admin-light: #f8fafc;
        }
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
        }
        .admin-sidebar {
            width: 270px;
            min-height: 100vh;
            height: 100vh;
            background: linear-gradient(180deg, #0b1329 0%, #080f20 100%);
            color: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            overflow-y: auto;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .admin-content {
            margin-left: 270px;
            padding: 24px 32px 40px;
            min-height: 100vh;
            background: #f8fafc;
        }
        .admin-nav-link {
            display: flex;
            align-items: center;
            padding: 11px 16px;
            color: #94a3b8;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border-radius: 12px;
            margin: 3px 10px;
            transition: all 0.2s ease;
        }
        .admin-nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.07);
            transform: translateX(3px);
        }
        .admin-nav-link.active {
            color: #ffffff !important;
            background: linear-gradient(135deg, #0066cc 0%, #0284c7 100%) !important;
            box-shadow: 0 4px 14px rgba(0, 102, 204, 0.35);
        }
        .admin-nav-link i {
            width: 24px;
            font-size: 1.05rem;
            margin-right: 10px;
        }
        .admin-topbar {
            background: #ffffff;
            border-radius: 18px;
            padding: 12px 24px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
            border: 1px solid rgba(226, 232, 240, 0.9);
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .online-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px #10b981;
        }
        @media (max-width: 991px) {
            .admin-sidebar {
                position: fixed;
                left: -270px;
                top: 0;
                width: 270px;
                box-shadow: 0 0 25px rgba(0,0,0,0.4);
            }
            .admin-sidebar.show { left: 0; }
            .admin-content { margin-left: 0; padding: 16px; }
            .mobile-sidebar-backdrop {
                display: none;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1035;
            }
            .mobile-sidebar-backdrop.show { display: block; }
        }
    </style>
</head>
<body>
<div id="mobileSidebarBackdrop" class="mobile-sidebar-backdrop" onclick="toggleAdminSidebar()"></div>

<div class="d-flex flex-column flex-lg-row">
    <!-- THANH MENU BÊN TRÁI -->
    <div class="admin-sidebar p-3 d-flex flex-column" id="adminSidebar">
        <!-- BRAND LOGO -->
        <div class="d-flex align-items-center justify-content-between mb-4 px-2 pt-2">
            <a href="index.php" class="d-flex align-items-center text-decoration-none">
                <span class="rounded-3 px-2 py-1 me-2 fw-black text-white" style="background: linear-gradient(135deg, #0066cc, #38bdf8); box-shadow: 0 4px 12px rgba(0,102,204,0.4);">
                    <i class="fa-solid fa-bolt"></i> V
                </span>
                <div>
                    <div class="fs-5 fw-bold text-white lh-1">V-Phone</div>
                    <small style="font-size: 0.68rem; color: #38bdf8; letter-spacing: 1px; font-weight: 700; text-transform: uppercase;">ADMIN SUITE 2026</small>
                </div>
            </a>
            <button class="btn btn-sm text-secondary d-lg-none" onclick="toggleAdminSidebar()">
                <i class="fa-solid fa-xmark fs-5"></i>
            </button>
        </div>

        <!-- THÔNG TIN QUẢN TRỊ VIÊN -->
        <div class="p-3 mb-3 rounded-4 d-flex align-items-center" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08);">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 42px; height: 42px; background: linear-gradient(135deg, #0284c7, #0066cc); font-weight: 700;">
                <?= mb_substr(htmlspecialchars($_SESSION['user']['fullname']), 0, 1, 'UTF-8') ?>
            </div>
            <div class="min-w-0 flex-grow-1">
                <div class="text-white fw-bold small text-truncate"><?= htmlspecialchars($_SESSION['user']['fullname']) ?></div>
                <div class="d-flex align-items-center gap-1 mt-1">
                    <span class="online-dot"></span>
                    <small style="color: #94a3b8; font-size: 0.72rem;">Quản trị viên</small>
                </div>
            </div>
        </div>

        <!-- DANH SÁCH MENU ĐIỀU HƯỚNG -->
        <nav class="nav flex-column mb-auto">
            <small class="px-3 py-1 text-uppercase fw-bold" style="font-size: 0.68rem; color: #64748b; letter-spacing: 0.8px;">Tổng quan</small>
            <a href="index.php" class="admin-nav-link <?= ($adminPage === 'index.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-pie"></i> Dashboard
            </a>

            <small class="px-3 pt-3 pb-1 text-uppercase fw-bold" style="font-size: 0.68rem; color: #64748b; letter-spacing: 0.8px;">Quản lý kho hàng</small>
            <a href="products.php" class="admin-nav-link <?= ($adminPage === 'products.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-mobile-screen"></i> Sản phẩm
            </a>
            <a href="brands.php" class="admin-nav-link <?= ($adminPage === 'brands.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-tags"></i> Thương hiệu
            </a>
            <a href="inventory.php" class="admin-nav-link <?= ($adminPage === 'inventory.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-stacked"></i> Tồn kho
                <?php if ($alertStock > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 0.7rem;"><?= $alertStock ?></span>
                <?php endif; ?>
            </a>

            <small class="px-3 pt-3 pb-1 text-uppercase fw-bold" style="font-size: 0.68rem; color: #64748b; letter-spacing: 0.8px;">Kinh doanh</small>
            <a href="orders.php" class="admin-nav-link <?= ($adminPage === 'orders.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-packing"></i> Đơn hàng
            </a>
            <a href="users.php" class="admin-nav-link <?= ($adminPage === 'users.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> Khách hàng
            </a>
            <a href="vouchers.php" class="admin-nav-link <?= ($adminPage === 'vouchers.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-ticket"></i> Mã giảm giá
            </a>
            <a href="news.php" class="admin-nav-link <?= ($adminPage === 'news.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-newspaper"></i> Tin tức
            </a>

            <hr style="border-color: rgba(255, 255, 255, 0.08); margin: 16px 8px;">
            <a href="../index.php" class="admin-nav-link" style="color: #38bdf8;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Xem Website
            </a>
            <a href="../index.php?page=logout" class="admin-nav-link text-danger">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
            </a>
        </nav>
    </div>

    <!-- KHU VỰC NỘI DUNG CHÍNH -->
    <div class="admin-content flex-grow-1">
        <!-- TOPBAR HEADER -->
        <div class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none rounded-3 px-2 py-1" onclick="toggleAdminSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="d-none d-sm-block">
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-1 rounded-pill">
                        <i class="fa-solid fa-shield-halved me-1"></i>V-Phone Admin Console
                    </span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="../index.php" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold d-flex align-items-center gap-1">
                    <i class="fa-solid fa-store"></i>
                    <span class="d-none d-md-inline">Xem Cửa Hàng</span>
                    <i class="fa-solid fa-arrow-up-right-from-square small ms-1"></i>
                </a>
                <a href="orders.php" class="btn btn-light btn-sm rounded-circle position-relative" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;" title="Đơn hàng">
                    <i class="fa-regular fa-bell text-secondary"></i>
                </a>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm rounded-pill px-3 py-1 d-flex align-items-center gap-2 border" data-bs-toggle="dropdown">
                        <span class="rounded-circle text-white d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; background: #0066cc; font-size: 0.75rem; font-weight: 700;">
                            <?= mb_substr(htmlspecialchars($_SESSION['user']['fullname']), 0, 1, 'UTF-8') ?>
                        </span>
                        <span class="small fw-bold d-none d-sm-inline"><?= htmlspecialchars($_SESSION['user']['fullname']) ?></span>
                        <i class="fa-solid fa-angle-down small text-muted"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2">
                        <li><a class="dropdown-item small py-2" href="../index.php"><i class="fa-solid fa-store me-2 text-primary"></i>Về trang bán hàng</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item small py-2 text-danger" href="../index.php?page=logout"><i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <script>
        function toggleAdminSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('mobileSidebarBackdrop');
            if (sidebar) sidebar.classList.toggle('show');
            if (backdrop) backdrop.classList.toggle('show');
        }
        </script>

