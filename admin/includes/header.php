<?php
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 1) {
    header('Location: ../index.php?show_login=1');
    exit;
}

$adminPage = basename($_SERVER['PHP_SELF']);

// Đếm số máy sắp hết hàng, số đơn hàng chờ xử lý và yêu cầu bảo hành chờ tiếp nhận
$alertStock = (int)($pdo->query("SELECT COUNT(*) FROM products WHERE quantity <= 5")->fetchColumn() ?: 0);
$pendingOrdersCount = (int)($pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Chờ xử lý'")->fetchColumn() ?: 0);
$pendingWarrantiesCount = 0;
try {
    $pendingWarrantiesCount = (int)($pdo->query("SELECT COUNT(*) FROM warranty_claims WHERE status = 'Chờ tiếp nhận'")->fetchColumn() ?: 0);
} catch (Exception $e) {}
$totalAlerts = $alertStock + $pendingOrdersCount + $pendingWarrantiesCount;

// Lấy thông báo mới nhất cho dropdown thông báo
$topPendingOrders = $pdo->query("SELECT id, fullname, total_money, created_at FROM orders WHERE status = 'Chờ xử lý' ORDER BY id DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
$topLowStockProds = $pdo->query("SELECT id, name, quantity, image FROM products WHERE quantity <= 5 ORDER BY quantity ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
$topPendingWarranties = [];
try {
    $topPendingWarranties = $pdo->query("SELECT id, customer_name, customer_phone, product_name, imei, issue_type, created_at FROM warranty_claims WHERE status = 'Chờ tiếp nhận' ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? "V-Phone Admin" ?></title><link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg?v=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=20261015_v3">
    <!-- Chart.js Thư viện biểu đồ thống kê hiện đại -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
            width: 280px;
            min-height: 100vh;
            height: 100vh;
            background: linear-gradient(180deg, #0b1329 0%, #080f20 100%);
            color: #ffffff;
            position: fixed;
            top: 0;
            left: -290px;
            z-index: 1055;
            overflow-y: auto;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.4);
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .admin-sidebar.show {
            left: 0 !important;
        }
        .admin-content {
            margin-left: 0 !important;
            padding: 24px 32px 40px;
            min-height: 100vh;
            background: #f8fafc;
            width: 100%;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Chế độ Ghim cố định khi quản trị viên chủ động bật trên màn hình lớn */
        body.sidebar-pinned .admin-sidebar {
            left: 0 !important;
            box-shadow: 2px 0 16px rgba(0, 0, 0, 0.15);
        }
        @media (min-width: 992px) {
            body.sidebar-pinned .admin-content {
                margin-left: 280px !important;
                width: calc(100% - 280px) !important;
            }
            body.sidebar-pinned .admin-sidebar-backdrop {
                display: none !important;
            }
        }

        /* Lớp phủ mờ nền khi mở menu trượt */
        .admin-sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            z-index: 1050;
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        .admin-sidebar-backdrop.show {
            display: block;
            opacity: 1;
        }

        @media (max-width: 768px) {
            .admin-content {
                padding: 16px 14px 32px;
            }
            .admin-topbar {
                padding: 10px 14px;
                border-radius: 14px;
                margin-bottom: 20px;
            }
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
        .bell-pulse {
            animation: bellShake 2.5s infinite ease-in-out;
        }
        @keyframes bellShake {
            0%, 85%, 100% { transform: rotate(0); }
            88% { transform: rotate(15deg); }
            92% { transform: rotate(-15deg); }
            96% { transform: rotate(8deg); }
        }
        /* Định dạng in ấn hóa đơn chuyên nghiệp */
        @media print {
            .admin-sidebar, .admin-topbar, .btn, .no-print, header, footer, .modal-footer, nav, .dataTables_filter, .dataTables_length, .pagination {
                display: none !important;
            }
            .admin-content {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .modal {
                position: static !important;
                overflow: visible !important;
                display: block !important;
            }
            .modal-dialog {
                max-width: 100% !important;
                margin: 0 !important;
            }
            .modal-content {
                border: none !important;
                box-shadow: none !important;
            }
            .print-clean {
                display: block !important;
            }
        }
    </style>
</head>
<body>
<div id="adminSidebarBackdrop" class="admin-sidebar-backdrop" onclick="toggleAdminSidebar(false)"></div>

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
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-outline-light border-0 rounded-circle d-none d-lg-inline-flex align-items-center justify-content-center text-secondary hover-white" id="btnPinSidebar" onclick="togglePinSidebar()" title="Ghim menu cố định / Tự động thu gọn" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-thumbtack" id="pinIcon" style="font-size: 0.85rem;"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-light border-0 rounded-circle d-inline-flex align-items-center justify-content-center text-secondary hover-white" onclick="toggleAdminSidebar(false)" title="Đóng menu (Esc)" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-xmark fs-5"></i>
                </button>
            </div>
        </div>

        <!-- THÔNG TIN QUẢN TRỊ VIÊN -->
        <div class="p-3 mb-3 rounded-4 d-flex align-items-center" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08);">
            <?php if (!empty($_SESSION['user']['avatar']) && file_exists(__DIR__ . '/../../' . $_SESSION['user']['avatar'])): ?>
                <img src="../<?= htmlspecialchars($_SESSION['user']['avatar']) ?>" alt="Avatar" class="rounded-circle me-3 flex-shrink-0 border" style="width: 42px; height: 42px; object-fit: cover;">
            <?php else: ?>
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 42px; height: 42px; background: linear-gradient(135deg, #0284c7, #0066cc); font-weight: 700;">
                    <?= mb_substr(htmlspecialchars($_SESSION['user']['fullname']), 0, 1, 'UTF-8') ?>
                </div>
            <?php endif; ?>
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
                <?php if ($pendingOrdersCount > 0): ?>
                    <span class="badge bg-warning text-dark rounded-pill ms-auto fw-bold" style="font-size: 0.7rem;"><?= $pendingOrdersCount ?></span>
                <?php endif; ?>
            </a>
            <a href="warranties.php" class="admin-nav-link <?= ($adminPage === 'warranties.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-shield-halved"></i> Bảo hành
                <?php if ($pendingWarrantiesCount > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto fw-bold" style="font-size: 0.7rem;"><?= $pendingWarrantiesCount ?></span>
                <?php endif; ?>
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
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2 rounded-pill px-3 py-1 fw-bold shadow-sm" onclick="toggleAdminSidebar()" id="sidebarToggleBtn" title="Bấm để mở danh mục Menu quản trị (Phím tắt: Esc để đóng)">
                    <i class="fa-solid fa-bars fs-6"></i>
                    <span>Menu</span>
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

                <!-- DROPDOWN THÔNG BÁO THÔNG MINH -->
                <div class="dropdown">
                    <button class="btn btn-light btn-sm rounded-circle position-relative border <?= $totalAlerts > 0 ? 'bell-pulse text-warning' : 'text-secondary' ?>" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;" data-bs-toggle="dropdown" aria-expanded="false" title="Thông báo hệ thống">
                        <i class="fa-regular fa-bell"></i>
                        <?php if ($totalAlerts > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white" style="font-size: 0.65rem; padding: 0.25em 0.45em;">
                                <?= $totalAlerts ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-0 mt-2" style="width: 330px; max-width: 90vw; z-index: 1060; overflow: hidden;">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                            <span class="fw-bold small text-dark"><i class="fa-solid fa-bell text-primary me-1"></i>Thông báo</span>
                            <span class="badge bg-primary rounded-pill small"><?= $totalAlerts ?> thông báo mới</span>
                        </div>
                        <div style="max-height: 290px; overflow-y: auto;">
                            <?php if ($totalAlerts === 0): ?>
                                <div class="text-center py-4 text-muted small">
                                    <i class="fa-regular fa-circle-check fs-3 text-success mb-2 d-block"></i>
                                    Mọi việc đều ổn định! Không có thông báo cần xử lý.
                                </div>
                            <?php else: ?>
                                <?php foreach ($topPendingOrders as $pOrd): ?>
                                    <a href="orders.php?status=Chờ xử lý" class="dropdown-item p-3 border-bottom d-flex align-items-start gap-2 text-wrap">
                                        <div class="rounded-circle bg-warning bg-opacity-25 text-warning p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="fa-solid fa-clock small"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-bold text-dark small">Đơn mới #VP-<?= $pOrd['id'] ?> (<?= htmlspecialchars($pOrd['fullname']) ?>)</div>
                                            <div class="text-danger small fw-bold"><?= number_format($pOrd['total_money'], 0, ',', '.') ?> đ</div>
                                            <small class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($pOrd['created_at']) ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>

                                <?php foreach ($topLowStockProds as $ls): ?>
                                    <a href="inventory.php?filter=low" class="dropdown-item p-3 border-bottom d-flex align-items-start gap-2 text-wrap">
                                        <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="fa-solid fa-triangle-exclamation small"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-bold text-dark small text-truncate" style="max-width: 220px;"><?= htmlspecialchars($ls['name']) ?></div>
                                            <span class="badge bg-danger rounded-pill" style="font-size: 0.7rem;">Chỉ còn <?= $ls['quantity'] ?> máy</span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>

                                <?php foreach ($topPendingWarranties as $pw): ?>
                                    <a href="warranties.php?status=Chờ+tiếp+nhận" class="dropdown-item p-3 border-bottom d-flex align-items-start gap-2 text-wrap">
                                        <div class="rounded-circle bg-warning bg-opacity-15 text-warning p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="fa-solid fa-shield-halved small"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-bold text-dark small">Yêu cầu bảo hành #BH-<?= $pw['id'] ?></div>
                                            <div class="text-primary small fw-semibold text-truncate" style="max-width: 220px;"><?= htmlspecialchars($pw['product_name']) ?></div>
                                            <small class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($pw['customer_name']) ?> &bull; <?= htmlspecialchars($pw['created_at']) ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="p-2 bg-light text-center border-top">
                            <a href="orders.php" class="btn btn-link btn-sm text-primary text-decoration-none fw-bold p-0" style="font-size: 0.8rem;">Xem tất cả đơn hàng <i class="fa-solid fa-arrow-right small ms-1"></i></a>
                        </div>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="btn btn-light btn-sm rounded-pill px-3 py-1 d-flex align-items-center gap-2 border" data-bs-toggle="dropdown">
                        <?php if (!empty($_SESSION['user']['avatar']) && file_exists(__DIR__ . '/../../' . $_SESSION['user']['avatar'])): ?>
                            <img src="../<?= htmlspecialchars($_SESSION['user']['avatar']) ?>" alt="Avatar" class="rounded-circle border" style="width: 26px; height: 26px; object-fit: cover;">
                        <?php else: ?>
                            <span class="rounded-circle text-white d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; background: #0066cc; font-size: 0.75rem; font-weight: 700;">
                                <?= mb_substr(htmlspecialchars($_SESSION['user']['fullname']), 0, 1, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
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
        function toggleAdminSidebar(forceState) {
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('adminSidebarBackdrop');
            if (!sidebar) return;
            
            const isOpen = sidebar.classList.contains('show');
            const shouldOpen = (typeof forceState === 'boolean') ? forceState : !isOpen;
            
            if (shouldOpen) {
                sidebar.classList.add('show');
                if (backdrop && !document.body.classList.contains('sidebar-pinned')) {
                    backdrop.classList.add('show');
                }
            } else {
                sidebar.classList.remove('show');
                if (backdrop) backdrop.classList.remove('show');
            }
        }

        function togglePinSidebar() {
            const isPinned = document.body.classList.toggle('sidebar-pinned');
            localStorage.setItem('adminSidebarPinned', isPinned ? 'true' : 'false');
            updatePinButton(isPinned);
            
            const backdrop = document.getElementById('adminSidebarBackdrop');
            if (isPinned && backdrop) {
                backdrop.classList.remove('show');
            }
        }

        function updatePinButton(isPinned) {
            const pinBtn = document.getElementById('btnPinSidebar');
            const pinIcon = document.getElementById('pinIcon');
            if (pinIcon) {
                pinIcon.style.color = isPinned ? '#38bdf8' : '#94a3b8';
                pinIcon.style.transform = isPinned ? 'rotate(45deg)' : 'none';
            }
            if (pinBtn) {
                pinBtn.title = isPinned ? 'Đang ghim cố định (Bấm để mở khi cần, giải phóng không gian)' : 'Bấm để ghim menu cố định';
            }
        }

        // Khởi tạo trạng thái ban đầu: Mặc định là unpinned (để toàn màn hình rộng rãi cho bảng dữ liệu)
        document.addEventListener('DOMContentLoaded', function () {
            const isPinned = localStorage.getItem('adminSidebarPinned') === 'true';
            if (isPinned && window.innerWidth >= 992) {
                document.body.classList.add('sidebar-pinned');
                updatePinButton(true);
            } else {
                updatePinButton(false);
            }
            
            // Đóng menu khi bấm phím Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !document.body.classList.contains('sidebar-pinned')) {
                    toggleAdminSidebar(false);
                }
            });
        });
        </script>

