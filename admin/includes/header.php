<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/views/includes/functions.php';
require_once __DIR__ . '/permissions.php';

if (!isset($_SESSION['user']) || (int)($_SESSION['user']['role'] ?? 0) < 1) {
    header('Location: ../index.php?show_login=1');
    exit;
}

// Kiểm tra trạng thái tài khoản và đồng bộ quyền hạn mới nhất từ DB
$currentUserId = (int)($_SESSION['user']['id'] ?? 0);
if ($currentUserId > 0) {
    try {
        $userCheckStmt = $pdo->prepare("SELECT status, role, staff_role, staff_title, permissions FROM users WHERE id = ?");
        $userCheckStmt->execute([$currentUserId]);
        $liveUserData = $userCheckStmt->fetch(PDO::FETCH_ASSOC);
        if ($liveUserData) {
            if ((int)($liveUserData['status'] ?? 1) === 0) {
                unset($_SESSION['user']);
                header('Location: ../index.php?show_login=1&error=' . urlencode('Tài khoản của bạn đã bị tạm khóa. Vui lòng liên hệ Quản trị viên!'));
                exit;
            }
            $_SESSION['user']['role'] = (int)$liveUserData['role'];
            $_SESSION['user']['staff_role'] = $liveUserData['staff_role'] ?? 'admin';
            $_SESSION['user']['staff_title'] = $liveUserData['staff_title'] ?? 'Nhân viên';
            $decodedPerms = json_decode($liveUserData['permissions'] ?? '[]', true);
            $_SESSION['user']['permissions'] = is_array($decodedPerms) ? $decodedPerms : [];
        }
    } catch (\Throwable $e) {}
}

$adminPage = basename($_SERVER['PHP_SELF']);
$isSuperAdmin = isCurrentSuperAdmin();
$isAdmin = isCurrentAdmin();
$isStaff = isCurrentStaff();

// Kiểm tra quyền hạn truy cập của nhân viên trên từng trang
$accessDenied = false;
$accessDeniedReason = '';

if ($isStaff) {
    // Nếu nhân viên cố vào Dashboard tài chính chung index.php -> Chuyển hướng về bàn làm việc nhân viên
    if ($adminPage === 'index.php') {
        header('Location: staff_dashboard.php');
        exit;
    }

    // Các trang cấm hoàn toàn đối với nhân viên
    if (in_array($adminPage, ['users.php', 'staff.php'])) {
        $accessDenied = true;
        $accessDeniedReason = ($adminPage === 'staff.php') 
            ? 'Trang "Quản lý Nhân sự" là chức năng quản trị tối cao, chỉ dành riêng cho Admin.' 
            : 'Trang "Quản lý Khách hàng" chỉ dành cho Quản trị viên hệ thống.';
    }

    // Kiểm tra quyền trên từng trang chuyên môn
    if ($adminPage === 'orders.php' && !currentUserCan('orders')) {
        $accessDenied = true;
        $accessDeniedReason = 'Tài khoản của bạn chưa được cấp quyền "Quản lý Đơn hàng".';
    } elseif (in_array($adminPage, ['products.php', 'brands.php']) && !currentUserCan('products')) {
        $accessDenied = true;
        $accessDeniedReason = 'Tài khoản của bạn chưa được cấp quyền "Quản lý Sản phẩm & Thương hiệu".';
    } elseif ($adminPage === 'inventory.php' && !currentUserCan('inventory')) {
        $accessDenied = true;
        $accessDeniedReason = 'Tài khoản của bạn chưa được cấp quyền "Quản lý Kho hàng & Kiểm kê".';
    } elseif ($adminPage === 'warranties.php' && !currentUserCan('warranties')) {
        $accessDenied = true;
        $accessDeniedReason = 'Tài khoản của bạn chưa được cấp quyền "Quản lý Bảo hành & Sửa chữa".';
    } elseif ($adminPage === 'reviews.php' && !currentUserCan('reviews')) {
        $accessDenied = true;
        $accessDeniedReason = 'Tài khoản của bạn chưa được cấp quyền "Quản lý Đánh giá & Phản hồi".';
    } elseif ($adminPage === 'vouchers.php' && !currentUserCan('vouchers')) {
        $accessDenied = true;
        $accessDeniedReason = 'Tài khoản của bạn chưa được cấp quyền "Quản lý Mã giảm giá & Voucher".';
    } elseif ($adminPage === 'news.php' && !currentUserCan('news')) {
        $accessDenied = true;
        $accessDeniedReason = 'Tài khoản của bạn chưa được cấp quyền "Quản lý Tin tức & Bài viết".';
    } elseif ($adminPage === 'support.php' && !currentUserCan('support') && !currentUserCan('orders') && !currentUserCan('warranties')) {
        $accessDenied = true;
        $accessDeniedReason = 'Tài khoản của bạn chưa được cấp quyền "CSKH & Hỗ trợ Đa kênh".';
    }
}

// Đếm số máy sắp hết hàng, số đơn hàng chờ xử lý, yêu cầu bảo hành và yêu cầu CSKH chờ phản hồi
$alertStock = 0;
if ($isAdmin || currentUserCan('inventory') || currentUserCan('products')) {
    $alertStock = (int)($pdo->query("SELECT COUNT(*) FROM products WHERE quantity <= 5")->fetchColumn() ?: 0);
}

$pendingOrdersCount = 0;
if ($isAdmin || currentUserCan('orders')) {
    $pendingOrdersCount = (int)($pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Chờ xử lý', 'Đang xử lý')")->fetchColumn() ?: 0);
}

$pendingWarrantiesCount = 0;
if ($isAdmin || currentUserCan('warranties')) {
    try {
        $pendingWarrantiesCount = (int)($pdo->query("SELECT COUNT(*) FROM warranty_claims WHERE status = 'Chờ tiếp nhận'")->fetchColumn() ?: 0);
    } catch (Exception $e) {}
}

$pendingReviewsCount = 0;
if ($isAdmin || currentUserCan('reviews')) {
    try {
        $pendingReviewsCount = (int)($pdo->query("SELECT COUNT(*) FROM product_reviews WHERE status = 0")->fetchColumn() ?: 0);
    } catch (Exception $e) {}
}

$pendingSupportCount = 0;
if ($isAdmin || currentUserCan('support') || currentUserCan('orders') || currentUserCan('warranties')) {
    try {
        $pendingSupportCount = (int)($pdo->query("SELECT COUNT(*) FROM callback_requests WHERE status = 'pending'")->fetchColumn() ?: 0);
    } catch (Exception $e) {}
}

$totalAlerts = $alertStock + $pendingOrdersCount + $pendingWarrantiesCount + $pendingReviewsCount + $pendingSupportCount;

// Lấy thông báo mới nhất cho dropdown thông báo
$topPendingOrders = [];
if ($isAdmin || currentUserCan('orders')) {
    $topPendingOrders = $pdo->query("SELECT id, fullname, total_money, created_at, status FROM orders WHERE status IN ('Chờ xử lý', 'Đang xử lý') ORDER BY id DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
}

$topLowStockProds = [];
if ($isAdmin || currentUserCan('inventory') || currentUserCan('products')) {
    $topLowStockProds = $pdo->query("SELECT id, name, quantity, image FROM products WHERE quantity <= 5 ORDER BY quantity ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
}

$topPendingWarranties = [];
if ($isAdmin || currentUserCan('warranties')) {
    try {
        $topPendingWarranties = $pdo->query("SELECT id, customer_name, customer_phone, product_name, imei, issue_type, created_at FROM warranty_claims WHERE status = 'Chờ tiếp nhận' ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

$topPendingSupport = [];
if ($isAdmin || currentUserCan('support') || currentUserCan('orders') || currentUserCan('warranties')) {
    try {
        $topPendingSupport = $pdo->query("SELECT id, fullname, phone, topic, department, note, created_at FROM callback_requests WHERE status = 'pending' ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title><?= $pageTitle ?? "V-Phone Admin" ?></title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png?v=2026">
    <link rel="shortcut icon" href="../assets/images/favicon.png?v=2026">
    <link rel="apple-touch-icon" href="../assets/images/vphone-icon.png?v=2026">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= file_exists(__DIR__ . '/../../assets/css/style.css') ? filemtime(__DIR__ . '/../../assets/css/style.css') : '20261016_v12' ?>">
    <!-- Chart.js Thư viện biểu đồ thống kê hiện đại -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <!-- SweetAlert2 Thư viện thông báo & hộp thoại popup chuẩn hiện đại -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --admin-dark: #0b1329;
            --admin-blue: #0066cc;
            --admin-light: #f8fafc;
        }
        html, body {
            overflow-x: hidden;
            max-width: 100vw;
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
            background: linear-gradient(180deg, #0b1329 0%, #070d1d 100%);
            color: #ffffff;
            position: fixed;
            top: 0;
            left: -300px;
            z-index: 1055;
            overflow: hidden;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.2);
            transition: left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .admin-sidebar.show {
            left: 0 !important;
        }
        .admin-sidebar-nav {
            display: flex !important;
            flex-direction: column !important;
            flex-wrap: nowrap !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        .admin-sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }
        .admin-sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }
        .admin-sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }
        .admin-sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        .admin-content {
            margin-left: 0;
            padding: 20px 16px 36px;
            min-height: 100vh;
            background: #f8fafc;
            width: 100%;
            transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1), width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Khi ĐÃ GHIM trên Desktop (>= 992px): Menu cố định bên trái, nội dung căn phải */
        @media (min-width: 992px) {
            html.sidebar-pinned .admin-sidebar,
            body.sidebar-pinned .admin-sidebar {
                left: 0 !important;
                width: 280px !important;
                box-shadow: 2px 0 12px rgba(15, 23, 42, 0.04) !important;
                z-index: 1020 !important;
            }
            html.sidebar-pinned .admin-content,
            body.sidebar-pinned .admin-content {
                margin-left: 280px !important;
                width: calc(100% - 280px) !important;
                padding: 24px 30px 48px !important;
            }
            html.sidebar-pinned #sidebarToggleBtn,
            body.sidebar-pinned #sidebarToggleBtn {
                display: none !important;
            }
            html.sidebar-pinned #btnCloseSidebarMobile,
            body.sidebar-pinned #btnCloseSidebarMobile {
                display: none !important;
            }
            html.sidebar-pinned .admin-sidebar-backdrop,
            body.sidebar-pinned .admin-sidebar-backdrop {
                display: none !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }

            /* Khi CHƯA GHIM trên Desktop (Unpinned): Menu ẩn bên trái, mở bằng nút Menu topbar, nội dung rộng 100% */
            html:not(.sidebar-pinned) .admin-sidebar,
            body:not(.sidebar-pinned) .admin-sidebar {
                left: -300px;
                z-index: 1055;
            }
            html:not(.sidebar-pinned) .admin-sidebar.show,
            body:not(.sidebar-pinned) .admin-sidebar.show {
                left: 0 !important;
            }
            html:not(.sidebar-pinned) .admin-content,
            body:not(.sidebar-pinned) .admin-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 24px 30px 48px !important;
            }
            html:not(.sidebar-pinned) #sidebarToggleBtn,
            body:not(.sidebar-pinned) #sidebarToggleBtn {
                display: inline-flex !important;
            }
            html:not(.sidebar-pinned) #btnCloseSidebarMobile,
            body:not(.sidebar-pinned) #btnCloseSidebarMobile {
                display: inline-flex !important;
            }
            #btnPinSidebar {
                display: inline-flex !important;
            }
        }

        /* Khi ở màn hình nhỏ / mobile (< 992px) */
        @media (max-width: 991.98px) {
            .admin-sidebar {
                left: -300px;
                z-index: 1055;
            }
            .admin-sidebar.show {
                left: 0 !important;
            }
            .admin-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 16px 14px 36px !important;
            }
            #sidebarToggleBtn {
                display: inline-flex !important;
            }
            #btnCloseSidebarMobile {
                display: inline-flex !important;
            }
            #btnPinSidebar {
                display: none !important;
            }
        }

        /* Lớp phủ mờ nền khi mở menu trượt (khi chưa ghim hoặc trên mobile) */
        .admin-sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.45);
            -webkit-backdrop-filter: blur(2px);
            backdrop-filter: blur(2px);
            z-index: 1050;
            opacity: 0;
            transition: opacity 0.2s ease;
            cursor: pointer;
        }
        .admin-sidebar-backdrop.show {
            display: block;
            opacity: 1;
        }
        /* Khi ĐÃ GHIM cố định thì không cần backdrop */
        html.sidebar-pinned .admin-sidebar-backdrop,
        body.sidebar-pinned .admin-sidebar-backdrop {
            display: none !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        .hover-white:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.12) !important;
        }
        .admin-nav-link {
            display: flex;
            align-items: center;
            padding: 10px 14px;
            color: #94a3b8;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.88rem;
            border-radius: 12px;
            margin: 2px 8px;
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
            width: 22px;
            font-size: 1rem;
            margin-right: 10px;
        }

        /* Nút bấm Menu cha dạng Accordion xổ xuống khi bấm */
        .admin-menu-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: calc(100% - 16px);
            padding: 10px 14px;
            color: #cbd5e1;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 12px;
            margin: 2px 8px;
            font-weight: 600;
            font-size: 0.88rem;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .admin-menu-toggle:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.08);
        }
        .admin-menu-toggle:not(.collapsed) {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .admin-menu-toggle.is-active-parent {
            color: #38bdf8;
            background: rgba(56, 189, 248, 0.1);
            border-color: rgba(56, 189, 248, 0.25);
            font-weight: 700;
        }
        .admin-menu-toggle .menu-chevron {
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.72rem;
            color: #64748b;
        }
        .admin-menu-toggle:not(.collapsed) .menu-chevron {
            transform: rotate(180deg);
            color: #38bdf8;
        }
        .admin-menu-toggle.is-active-parent .menu-chevron {
            color: #38bdf8;
        }

        /* Khung chứa các menu con xổ xuống */
        .admin-submenu {
            padding: 3px 0 3px 6px;
            margin: 2px 8px 6px 16px;
            border-left: 2px solid rgba(255, 255, 255, 0.12);
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .admin-submenu .admin-nav-link {
            font-size: 0.84rem;
            padding: 8px 12px;
            margin: 1px 0;
            border-radius: 10px;
            color: #94a3b8;
        }
        .admin-submenu .admin-nav-link i {
            width: 20px;
            font-size: 0.95rem;
            margin-right: 8px;
        }
        .admin-submenu .admin-nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(3px);
        }
        .admin-submenu .admin-nav-link.active {
            color: #ffffff !important;
            background: linear-gradient(135deg, #0066cc 0%, #0284c7 100%) !important;
            box-shadow: 0 3px 10px rgba(0, 102, 204, 0.4);
            font-weight: 700;
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
        /* Soft Badges chuẩn UI hiện đại */
        .bg-opacity-10 { --bs-bg-opacity: 0.1 !important; }
        .bg-opacity-15 { --bs-bg-opacity: 0.15 !important; }
        .bg-opacity-25 { --bs-bg-opacity: 0.25 !important; }
        .badge-soft-success { background-color: #ecfdf5 !important; color: #059669 !important; border: 1px solid #a7f3d0 !important; font-weight: 600; }
        .badge-soft-danger { background-color: #fef2f2 !important; color: #dc2626 !important; border: 1px solid #fecaca !important; font-weight: 600; }
        .badge-soft-warning { background-color: #fffbeb !important; color: #d97706 !important; border: 1px solid #fde68a !important; font-weight: 600; }
        .badge-soft-primary { background-color: #eff6ff !important; color: #2563eb !important; border: 1px solid #bfdbfe !important; font-weight: 600; }
        .badge-soft-secondary { background-color: #f8fafc !important; color: #64748b !important; border: 1px solid #e2e8f0 !important; font-weight: 600; }
        .badge-soft-info { background-color: #e0f2fe !important; color: #0284c7 !important; border: 1px solid #bae6fd !important; font-weight: 600; }
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
        /* ===== ADMIN DARK MODE DEEP INTEGRATION ===== */
        [data-bs-theme="dark"] body {
            background-color: #0b1329 !important;
            color: #f1f5f9 !important;
        }
        [data-bs-theme="dark"] .admin-content {
            background: #0b1329 !important;
            color: #f1f5f9 !important;
        }
        [data-bs-theme="dark"] .admin-topbar {
            background: #131f37 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4) !important;
        }
        [data-bs-theme="dark"] .admin-kpi-card {
            background: #131f37 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #f1f5f9 !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35) !important;
        }
        [data-bs-theme="dark"] .admin-kpi-card:hover {
            border-color: rgba(56, 189, 248, 0.4) !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5) !important;
        }
        [data-bs-theme="dark"] .admin-kpi-card .text-dark,
        [data-bs-theme="dark"] .admin-kpi-card h3,
        [data-bs-theme="dark"] .admin-kpi-card h4 {
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .admin-table-card {
            background: #131f37 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35) !important;
        }
        [data-bs-theme="dark"] .admin-table thead th {
            background: #0e172a !important;
            color: #94a3b8 !important;
            border-bottom-color: rgba(255, 255, 255, 0.09) !important;
        }
        [data-bs-theme="dark"] .admin-table tbody td {
            border-bottom-color: rgba(255, 255, 255, 0.06) !important;
            color: #f1f5f9 !important;
        }
        [data-bs-theme="dark"] .admin-table tbody tr:hover td {
            background-color: #1a2948 !important;
        }
        [data-bs-theme="dark"] .inv-kpi-card {
            background: #131f37 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #f1f5f9 !important;
        }
        [data-bs-theme="dark"] .inv-kpi-card.active-all {
            background: linear-gradient(135deg, rgba(2, 132, 199, 0.2), #131f37) !important;
            border-color: #38bdf8 !important;
        }
        [data-bs-theme="dark"] .inv-kpi-card.active-low {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), #131f37) !important;
            border-color: #fbbf24 !important;
        }
        [data-bs-theme="dark"] .inv-kpi-card.active-out {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), #131f37) !important;
            border-color: #f87171 !important;
        }
        [data-bs-theme="dark"] .inv-table thead th {
            background: #0e172a !important;
            color: #94a3b8 !important;
            border-bottom-color: rgba(255, 255, 255, 0.09) !important;
        }
        [data-bs-theme="dark"] .inv-table tbody tr {
            border-bottom-color: rgba(255, 255, 255, 0.06) !important;
        }
        [data-bs-theme="dark"] .inv-table tbody tr:hover {
            background: #1a2948 !important;
        }
        [data-bs-theme="dark"] .prod-name {
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .color-chip {
            background: #0e172a !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #cbd5e1 !important;
        }
        [data-bs-theme="dark"] .qty-input {
            background: #0e172a !important;
            border-color: #243556 !important;
            color: #f1f5f9 !important;
        }
        [data-bs-theme="dark"] .kpi-badge-blue { background: rgba(56, 189, 248, 0.16) !important; color: #38bdf8 !important; }
        [data-bs-theme="dark"] .kpi-badge-amber { background: rgba(245, 158, 11, 0.16) !important; color: #fbbf24 !important; }
        [data-bs-theme="dark"] .kpi-badge-emerald { background: rgba(16, 185, 129, 0.16) !important; color: #34d399 !important; }
        [data-bs-theme="dark"] .kpi-badge-rose { background: rgba(239, 68, 68, 0.16) !important; color: #f87171 !important; }
        [data-bs-theme="dark"] .badge-soft-success { background-color: rgba(16, 185, 129, 0.16) !important; color: #34d399 !important; border: 1px solid rgba(16, 185, 129, 0.3) !important; }
        [data-bs-theme="dark"] .badge-soft-danger { background-color: rgba(239, 68, 68, 0.16) !important; color: #f87171 !important; border: 1px solid rgba(239, 68, 68, 0.3) !important; }
        [data-bs-theme="dark"] .badge-soft-warning { background-color: rgba(245, 158, 11, 0.16) !important; color: #fbbf24 !important; border: 1px solid rgba(245, 158, 11, 0.3) !important; }
        [data-bs-theme="dark"] .badge-soft-primary { background-color: rgba(56, 189, 248, 0.16) !important; color: #38bdf8 !important; border: 1px solid rgba(56, 189, 248, 0.3) !important; }
        [data-bs-theme="dark"] .badge-soft-secondary { background-color: rgba(148, 163, 184, 0.16) !important; color: #94a3b8 !important; border: 1px solid rgba(148, 163, 184, 0.3) !important; }
        [data-bs-theme="dark"] .badge-soft-info { background-color: rgba(14, 165, 233, 0.16) !important; color: #38bdf8 !important; border: 1px solid rgba(14, 165, 233, 0.3) !important; }
        [data-bs-theme="dark"] .card {
            background-color: #131f37 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #f1f5f9 !important;
        }
        [data-bs-theme="dark"] .bg-white {
            background-color: #131f37 !important;
            color: #f1f5f9 !important;
        }
        [data-bs-theme="dark"] .bg-light {
            background-color: #0e172a !important;
            color: #cbd5e1 !important;
        }
        [data-bs-theme="dark"] .text-dark {
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .text-secondary {
            color: #94a3b8 !important;
        }
        [data-bs-theme="dark"] .text-muted {
            color: #94a3b8 !important;
        }
        [data-bs-theme="dark"] .btn-outline-primary {
            background-color: transparent !important;
            color: #38bdf8 !important;
            border-color: #38bdf8 !important;
        }
        [data-bs-theme="dark"] .btn-outline-primary:hover {
            background-color: #0066cc !important;
            color: #ffffff !important;
            border-color: #0066cc !important;
        }
        [data-bs-theme="dark"] .btn-outline-secondary {
            background-color: transparent !important;
            color: #cbd5e1 !important;
            border-color: #475569 !important;
        }
        [data-bs-theme="dark"] .btn-outline-secondary:hover {
            background-color: #334155 !important;
            color: #ffffff !important;
            border-color: #64748b !important;
        }
        [data-bs-theme="dark"] .btn-outline-success {
            background-color: transparent !important;
            color: #34d399 !important;
            border-color: #34d399 !important;
        }
        [data-bs-theme="dark"] .btn-outline-success:hover {
            background-color: #10b981 !important;
            color: #ffffff !important;
            border-color: #10b981 !important;
        }
        [data-bs-theme="dark"] .border,
        [data-bs-theme="dark"] .border-top,
        [data-bs-theme="dark"] .border-bottom,
        [data-bs-theme="dark"] .border-start,
        [data-bs-theme="dark"] .border-end {
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select {
            background-color: #0e172a !important;
            border-color: #243556 !important;
            color: #f1f5f9 !important;
        }
        [data-bs-theme="dark"] .form-control:focus,
        [data-bs-theme="dark"] .form-select:focus {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.22) !important;
        }
        [data-bs-theme="dark"] .text-primary,
        [data-bs-theme="dark"] a.text-primary,
        [data-bs-theme="dark"] i.text-primary,
        [data-bs-theme="dark"] span.text-primary,
        [data-bs-theme="dark"] .modal-title.text-primary {
            color: #38bdf8 !important;
            --bs-primary-rgb: 56, 189, 248 !important;
        }
        [data-bs-theme="dark"] .table-user-cell .user-fullname,
        [data-bs-theme="dark"] .user-fullname {
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .table-user-cell .user-subtext,
        [data-bs-theme="dark"] .user-subtext {
            color: #94a3b8 !important;
        }
        [data-bs-theme="dark"] .table-user-cell .user-subtext i,
        [data-bs-theme="dark"] .table-user-cell .user-subtext span {
            color: #94a3b8 !important;
        }
        [data-bs-theme="dark"] .badge.bg-light,
        [data-bs-theme="dark"] .badge.bg-white,
        [data-bs-theme="dark"] .badge.bg-body-tertiary {
            background-color: #1e293b !important;
            color: #f1f5f9 !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
        }
        [data-bs-theme="dark"] .badge.bg-light.text-muted,
        [data-bs-theme="dark"] .badge.bg-light.text-secondary {
            color: #94a3b8 !important;
        }
        [data-bs-theme="dark"] .badge.bg-light.text-dark,
        [data-bs-theme="dark"] .badge.bg-white.text-dark {
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .brand-chip {
            background: rgba(56, 189, 248, 0.15) !important;
            color: #38bdf8 !important;
            border-color: rgba(56, 189, 248, 0.3) !important;
        }
        [data-bs-theme="dark"] .order-id-chip {
            background: rgba(56, 189, 248, 0.15) !important;
            color: #38bdf8 !important;
            border-color: rgba(56, 189, 248, 0.3) !important;
        }
        [data-bs-theme="dark"] .color-chip {
            background: #1e293b !important;
            color: #e2e8f0 !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
        }
        [data-bs-theme="dark"] .color-chip span,
        [data-bs-theme="dark"] .color-chip .color-chip-qty {
            background: #0f172a !important;
            color: #38bdf8 !important;
        }
        [data-bs-theme="dark"] .color-chip.unset {
            background: rgba(245, 158, 11, 0.15) !important;
            color: #fbbf24 !important;
            border-color: rgba(245, 158, 11, 0.3) !important;
        }
        [data-bs-theme="dark"] .inv-thumb-wrap,
        [data-bs-theme="dark"] div[style*="background:#f8fafc"],
        [data-bs-theme="dark"] div[style*="background: #f8fafc"] {
            background: #1e293b !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
        [data-bs-theme="dark"] .btn-light,
        [data-bs-theme="dark"] .btn-white {
            background-color: #1e293b !important;
            color: #f1f5f9 !important;
            border-color: rgba(255, 255, 255, 0.14) !important;
        }
        [data-bs-theme="dark"] .btn-light:hover,
        [data-bs-theme="dark"] .btn-white:hover {
            background-color: #2b3d5b !important;
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.25) !important;
        }
        [data-bs-theme="dark"] .btn-light.text-muted,
        [data-bs-theme="dark"] .btn-light.text-secondary {
            color: #94a3b8 !important;
        }
        [data-bs-theme="dark"] .btn-light.text-dark,
        [data-bs-theme="dark"] .btn-white.text-dark {
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .form-label,
        [data-bs-theme="dark"] label {
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .modal-content {
            background-color: #131f37 !important;
            color: #f1f5f9 !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
        }
        [data-bs-theme="dark"] .modal-header {
            background-color: #131f37 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        [data-bs-theme="dark"] .modal-body {
            background-color: #131f37 !important;
            color: #f1f5f9 !important;
        }
        [data-bs-theme="dark"] .modal-footer {
            background-color: #131f37 !important;
            border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        [data-bs-theme="dark"] .modal-title {
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .modal-body .bg-light,
        [data-bs-theme="dark"] .modal-body div[class*="bg-light"] {
            background-color: #0e172a !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
        [data-bs-theme="dark"] .perm-card-item {
            background-color: #0e172a !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
        [data-bs-theme="dark"] .perm-card-item:hover {
            background-color: #16243f !important;
            border-color: rgba(56, 189, 248, 0.35) !important;
        }
        [data-bs-theme="dark"] .perm-card-item .text-dark {
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .perm-card-item .text-secondary {
            color: #94a3b8 !important;
        }
        [data-bs-theme="dark"] .admin-order-table-card,
        [data-bs-theme="dark"] .admin-order-table-wrap {
            background-color: #131f37 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
        [data-bs-theme="dark"] .swal2-popup {
            background-color: #131f37 !important;
            color: #f1f5f9 !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        [data-bs-theme="dark"] .swal2-title {
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .swal2-html-container {
            color: #cbd5e1 !important;
        }
        [data-bs-theme="dark"] .input-group-text {
            background-color: #1a2948 !important;
            border-color: #243556 !important;
            color: #cbd5e1 !important;
        }

        /* Fractional spacing utilities */
        .me-1\.5, .me-1-5 { margin-right: 0.45rem !important; }
        .me-2\.5, .me-2-5 { margin-right: 0.65rem !important; }
        .ms-1\.5, .ms-1-5 { margin-left: 0.45rem !important; }
        .gap-1\.5, .gap-1-5 { gap: 0.45rem !important; }
        .gap-2\.5, .gap-2-5 { gap: 0.65rem !important; }

        /* Universal icon clearance for compound elements */
        .btn > i.me-1, .badge > i.me-1, .dropdown-item i.me-1, a i.me-1 { margin-right: 0.45rem !important; }
        /* r */
        .badge > i:first-child:not(:only-child),
        .dropdown-item > i:first-child:not(:only-child),
        .admin-nav-link > i:first-child:not(:only-child),
        .theme-opt-btn i:first-child:not(:only-child) {
            margin-right: 0.45rem !important;
            display: inline-block;
            vertical-align: -0.05em;
        }

        /* Segmented Theme Choice in Admin Sidebar Footer */
        .admin-theme-segmented-group {
            display: flex !important;
            width: 100% !important;
            padding: 3px !important;
            border-radius: 9999px !important;
            background: rgba(255, 255, 255, 0.06) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            gap: 2px !important;
        }
        .admin-theme-segmented-group .btn-admin-theme-choice {
            flex: 1 1 0px !important;
            width: 0 !important;
            min-width: 0 !important;
            height: 30px !important;
            padding: 0 4px !important;
            border-radius: 9999px !important;
            border: none !important;
            font-size: 0.76rem !important;
            font-weight: 600 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.35rem !important;
            color: #94a3b8 !important;
            background: transparent !important;
            transition: background-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease !important;
            white-space: nowrap !important;
            cursor: pointer !important;
        }
        .admin-theme-segmented-group .btn-admin-theme-choice:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.12) !important;
        }
        .admin-theme-segmented-group .btn-admin-theme-choice.active {
            background: #0284c7 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.45) !important;
        }
        .admin-theme-segmented-group .btn-admin-theme-choice i {
            font-size: 0.78rem !important;
            margin-right: 0 !important;
            flex-shrink: 0 !important;
        }

        /* Silky smooth targeted transitions */
        html.theme-switching body, html.theme-switching .admin-content, html.theme-switching .admin-topbar, html.theme-switching .admin-kpi-card, html.theme-switching .admin-table-card, html.theme-switching .inv-kpi-card, html.theme-switching .card {
            transition: background-color 0.25s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        body, .admin-content, .admin-topbar, .admin-kpi-card, .admin-table-card, .inv-kpi-card, .card {
            transition: background-color 0.38s cubic-bezier(0.16, 1, 0.3, 1),
                        border-color 0.38s cubic-bezier(0.16, 1, 0.3, 1),
                        box-shadow 0.38s cubic-bezier(0.16, 1, 0.3, 1),
                        color 0.28s ease;
        }

        @keyframes themeIconSpinPop {
            0% { transform: rotate(-135deg) scale(0.45); opacity: 0.2; }
            60% { transform: rotate(18deg) scale(1.28); opacity: 0.95; }
            100% { transform: rotate(0deg) scale(1); opacity: 1; }
        }
        .theme-icon-animate {
            display: inline-block;
            animation: themeIconSpinPop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }
    </style>
    <script>
        (function() {
            try {
                var mode = localStorage.getItem('vphone_theme_mode') || localStorage.getItem('vphone_theme') || 'auto';
                var isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var effective = (mode === 'auto') ? (isDark ? 'dark' : 'light') : mode;
                document.documentElement.setAttribute('data-bs-theme', effective);
                document.documentElement.setAttribute('data-theme-mode', mode);
                document.documentElement.classList.remove('theme-dark', 'theme-light');
                document.documentElement.classList.add('theme-' + effective);

                var pinned = localStorage.getItem('adminSidebarPinned');
                if (pinned !== 'false' && window.innerWidth >= 992) {
                    document.documentElement.classList.add('sidebar-pinned');
                }
            } catch(e) {}
        })();
    </script>
</head>
<body>
<div id="adminSidebarBackdrop" class="admin-sidebar-backdrop" onclick="toggleAdminSidebar(false)"></div>

<div class="d-flex flex-column flex-lg-row">
    <!-- THANH MENU BÊN TRÁI -->
    <div class="admin-sidebar d-flex flex-column" id="adminSidebar" style="overflow: hidden; padding: 0;">
        <!-- KHU VỰC ĐẦU SIDEBAR CỐ ĐỊNH (BRAND LOGO & THÔNG TIN NGƯỜI DÙNG) -->
        <div class="p-3 pb-2 flex-shrink-0" style="border-bottom: 1px solid rgba(255, 255, 255, 0.06);">
            <!-- BRAND LOGO -->
            <div class="d-flex align-items-center justify-content-between mb-3 px-1 pt-1">
                <a href="<?= $isStaff ? 'staff_dashboard.php' : 'index.php' ?>" class="d-flex align-items-center text-decoration-none">
                    <img src="../assets/images/vphone-icon.png" alt="V-Phone" style="width: 38px; height: 38px; object-fit: contain; filter: drop-shadow(0 4px 10px rgba(0,102,204,0.5));" class="me-2.5 flex-shrink-0">
                    <div>
                        <div class="fs-5 fw-bold text-white lh-1">V-Phone</div>
                        <?php if ($isStaff): ?>
                            <small style="font-size: 0.68rem; color: #34d399; letter-spacing: 1px; font-weight: 700; text-transform: uppercase;">
                                <i class="fa-solid fa-id-badge me-1"></i>PORTAL NHÂN VIÊN
                            </small>
                        <?php else: ?>
                            <small style="font-size: 0.68rem; color: #38bdf8; letter-spacing: 1px; font-weight: 700; text-transform: uppercase;">ADMIN SUITE 2026</small>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-light border-0 rounded-circle d-none d-lg-inline-flex align-items-center justify-content-center text-secondary hover-white" id="btnPinSidebar" onclick="togglePinSidebar()" title="Ghim menu cố định / Tự động thu gọn" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-thumbtack" id="pinIcon" style="font-size: 0.85rem; transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), color 0.2s ease;"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light border-0 rounded-circle d-inline-flex align-items-center justify-content-center text-secondary hover-white" id="btnCloseSidebarMobile" onclick="toggleAdminSidebar(false)" title="Đóng menu (Esc)" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-xmark fs-5"></i>
                    </button>
                </div>
            </div>

            <!-- THÔNG TIN NGƯỜI DÙNG HIỆN TẠI (HIỂN THỊ ĐẦY ĐỦ, KHÔNG BỊ TRÀN HAY CẮT CHỮ) -->
            <div class="p-2.5 px-3 rounded-4 d-flex align-items-center gap-2.5" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08);">
                <?php 
                    $adminAv = $_SESSION['user']['avatar'] ?? '';
                    $adminAvSrc = null;
                    if (!empty($adminAv)) {
                        if (str_starts_with($adminAv, 'http://') || str_starts_with($adminAv, 'https://')) {
                            $adminAvSrc = $adminAv;
                        } elseif (file_exists(__DIR__ . '/../../' . $adminAv)) {
                            $adminAvSrc = '../' . $adminAv . '?v=' . filemtime(__DIR__ . '/../../' . $adminAv);
                        }
                    }
                ?>
                <?php if ($adminAvSrc): ?>
                    <img src="<?= htmlspecialchars($adminAvSrc) ?>" alt="Avatar" referrerpolicy="no-referrer" class="rounded-circle flex-shrink-0 border border-2 border-white-50" style="width: 40px; height: 40px; object-fit: cover; box-shadow: 0 2px 8px rgba(0,0,0,0.25);">
                <?php else: ?>
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 40px; height: 40px; background: <?= $isStaff ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #0284c7, #0066cc)' ?>; font-weight: 700; font-size: 0.95rem; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 2px 8px rgba(0,0,0,0.25);">
                        <?= mb_substr(htmlspecialchars($_SESSION['user']['fullname']), 0, 1, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <div class="min-w-0 flex-grow-1">
                    <div class="text-white fw-bold small sidebar-user-name" title="<?= htmlspecialchars($_SESSION['user']['fullname']) ?>"><?= htmlspecialchars($_SESSION['user']['fullname']) ?></div>
                    <div class="d-flex align-items-center gap-1.5 mt-0.5">
                        <span class="online-dot flex-shrink-0" style="<?= $isStaff ? 'background: #34d399; box-shadow: 0 0 8px #34d399;' : '' ?>"></span>
                        <?php if ($isSuperAdmin): ?>
                            <small style="color: #38bdf8; font-size: 0.72rem; font-weight: 700;"><i class="fa-solid fa-crown text-warning me-1"></i>Super Admin</small>
                        <?php elseif ($isAdmin): ?>
                            <small style="color: #94a3b8; font-size: 0.72rem;">Quản trị viên</small>
                        <?php else: ?>
                            <small style="color: #34d399; font-size: 0.72rem; font-weight: 700;"><i class="fa-solid fa-id-badge me-1"></i><?= htmlspecialchars(getStaffTitleDisplay()) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
            $isWarehouseActive = in_array($adminPage, ['products.php', 'brands.php', 'inventory.php']);
            $isBusinessActive = in_array($adminPage, ['orders.php', 'support.php', 'warranties.php', 'users.php', 'reviews.php', 'vouchers.php', 'news.php']);
            $isSystemActive = in_array($adminPage, ['staff.php', 'staff_dashboard.php', 'db_sync.php']);
            $isStaffWarehouseActive = in_array($adminPage, ['products.php', 'brands.php', 'inventory.php']);
            $isStaffBusinessActive = in_array($adminPage, ['orders.php', 'support.php', 'warranties.php', 'reviews.php', 'vouchers.php', 'news.php']);
            $totalBizAlerts = $pendingOrdersCount + $pendingWarrantiesCount + $pendingReviewsCount + $pendingSupportCount;
        ?>
        <!-- DANH SÁCH MENU ĐIỀU HƯỚNG CUỘN Ở GIỮA -->
        <nav class="nav flex-column flex-grow-1 p-2 py-2 admin-sidebar-nav" id="adminSidebarNav" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.2) transparent;">
            <?php if ($isStaff): ?>
                <!-- MENU DÀNH CHO NHÂN VIÊN -->
                <small class="px-3 pt-1 pb-1 text-uppercase fw-bold d-block" style="font-size: 0.68rem; color: #34d399; letter-spacing: 0.8px;">
                    <i class="fa-solid fa-briefcase me-1"></i>Không gian làm việc
                </small>
                <a href="staff_dashboard.php" class="admin-nav-link <?= ($adminPage === 'staff_dashboard.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-laptop-file"></i> <span>Bàn làm việc ca trực</span>
                </a>

                <?php if (currentUserCan('products') || currentUserCan('inventory')): ?>
                    <div class="mt-2 w-100">
                        <button type="button" class="admin-menu-toggle <?= $isStaffWarehouseActive ? 'is-active-parent' : 'collapsed' ?>" data-bs-toggle="collapse" data-bs-target="#menuStaffWarehouse" aria-expanded="<?= $isStaffWarehouseActive ? 'true' : 'false' ?>" aria-controls="menuStaffWarehouse">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <i class="fa-solid fa-boxes-stacked" style="width: 20px; font-size: 0.95rem; color: <?= $isStaffWarehouseActive ? '#38bdf8' : '#94a3b8' ?>;"></i>
                                <span class="text-truncate">Kho & Sản phẩm</span>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 flex-shrink-0 ms-2">
                                <?php if ($alertStock > 0): ?>
                                    <span class="badge bg-danger rounded-pill px-1.5 py-0.5" style="font-size: 0.65rem;"><?= $alertStock ?></span>
                                <?php endif; ?>
                                <i class="fa-solid fa-chevron-down menu-chevron"></i>
                            </div>
                        </button>
                        <div class="collapse <?= $isStaffWarehouseActive ? 'show' : '' ?> w-100" id="menuStaffWarehouse" data-bs-parent="#adminSidebarNav">
                            <div class="admin-submenu">
                                <?php if (currentUserCan('products')): ?>
                                    <a href="products.php" class="admin-nav-link <?= ($adminPage === 'products.php') ? 'active' : '' ?>">
                                        <i class="fa-solid fa-mobile-screen"></i> <span>Sản phẩm</span>
                                    </a>
                                    <a href="brands.php" class="admin-nav-link <?= ($adminPage === 'brands.php') ? 'active' : '' ?>">
                                        <i class="fa-solid fa-tags"></i> <span>Thương hiệu</span>
                                    </a>
                                <?php endif; ?>
                                <?php if (currentUserCan('inventory')): ?>
                                    <a href="inventory.php" class="admin-nav-link <?= ($adminPage === 'inventory.php') ? 'active' : '' ?>">
                                        <i class="fa-solid fa-boxes-stacked"></i> <span>Tồn kho</span>
                                        <?php if ($alertStock > 0): ?>
                                            <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 0.65rem;"><?= $alertStock ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (currentUserCan('orders') || currentUserCan('warranties') || currentUserCan('reviews') || currentUserCan('vouchers') || currentUserCan('news') || currentUserCan('support')): ?>
                    <div class="mt-1 w-100">
                        <button type="button" class="admin-menu-toggle <?= $isStaffBusinessActive ? 'is-active-parent' : 'collapsed' ?>" data-bs-toggle="collapse" data-bs-target="#menuStaffBusiness" aria-expanded="<?= $isStaffBusinessActive ? 'true' : 'false' ?>" aria-controls="menuStaffBusiness">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <i class="fa-solid fa-cart-shopping" style="width: 20px; font-size: 0.95rem; color: <?= $isStaffBusinessActive ? '#38bdf8' : '#94a3b8' ?>;"></i>
                                <span class="text-truncate">Nhiệm vụ bán hàng</span>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 flex-shrink-0 ms-2">
                                <?php if ($totalBizAlerts > 0): ?>
                                    <span class="badge bg-warning text-dark rounded-pill px-1.5 py-0.5 fw-bold" style="font-size: 0.65rem;"><?= $totalBizAlerts ?></span>
                                <?php endif; ?>
                                <i class="fa-solid fa-chevron-down menu-chevron"></i>
                            </div>
                        </button>
                        <div class="collapse <?= $isStaffBusinessActive ? 'show' : '' ?> w-100" id="menuStaffBusiness" data-bs-parent="#adminSidebarNav">
                            <div class="admin-submenu">
                                <?php if (currentUserCan('orders')): ?>
                                    <a href="orders.php" class="admin-nav-link <?= ($adminPage === 'orders.php') ? 'active' : '' ?>">
                                        <i class="fa-solid fa-boxes-packing"></i> <span>Đơn hàng</span>
                                        <?php if ($pendingOrdersCount > 0): ?>
                                            <span class="badge bg-warning text-dark rounded-pill ms-auto fw-bold" style="font-size: 0.65rem;"><?= $pendingOrdersCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (currentUserCan('support') || currentUserCan('orders') || currentUserCan('warranties')): ?>
                                    <a href="support.php" class="admin-nav-link <?= ($adminPage === 'support.php') ? 'active' : '' ?>">
                                        <i class="fa-solid fa-headset"></i> <span>CSKH & Hỗ trợ</span>
                                        <?php if ($pendingSupportCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill ms-auto fw-bold" style="font-size: 0.65rem;"><?= $pendingSupportCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (currentUserCan('warranties')): ?>
                                    <a href="warranties.php" class="admin-nav-link <?= ($adminPage === 'warranties.php') ? 'active' : '' ?>">
                                        <i class="fa-solid fa-shield-halved"></i> <span>Bảo hành</span>
                                        <?php if ($pendingWarrantiesCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill ms-auto fw-bold" style="font-size: 0.65rem;"><?= $pendingWarrantiesCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (currentUserCan('reviews')): ?>
                                    <a href="reviews.php" class="admin-nav-link <?= ($adminPage === 'reviews.php') ? 'active' : '' ?>">
                                        <i class="fa-solid fa-star-half-stroke"></i> <span>Đánh giá & Sao</span>
                                        <?php if ($pendingReviewsCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill ms-auto fw-bold" style="font-size: 0.65rem;"><?= $pendingReviewsCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (currentUserCan('vouchers')): ?>
                                    <a href="vouchers.php" class="admin-nav-link <?= ($adminPage === 'vouchers.php') ? 'active' : '' ?>">
                                        <i class="fa-solid fa-ticket"></i> <span>Mã giảm giá</span>
                                    </a>
                                <?php endif; ?>
                                <?php if (currentUserCan('news')): ?>
                                    <a href="news.php" class="admin-nav-link <?= ($adminPage === 'news.php') ? 'active' : '' ?>">
                                        <i class="fa-solid fa-newspaper"></i> <span>Tin tức</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- MENU DÀNH CHO ADMIN / SUPER ADMIN -->
                <small class="px-3 pt-1 pb-1 text-uppercase fw-bold d-block" style="font-size: 0.68rem; color: #64748b; letter-spacing: 0.8px;">
                    Tổng quan
                </small>
                <a href="index.php" class="admin-nav-link <?= ($adminPage === 'index.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
                </a>

                <!-- 1. QUẢN LÝ KHO HÀNG (ACCORDION XỔ XUỐNG KHI BẤM) -->
                <div class="mt-2 w-100">
                    <button type="button" class="admin-menu-toggle <?= $isWarehouseActive ? 'is-active-parent' : 'collapsed' ?>" data-bs-toggle="collapse" data-bs-target="#menuWarehouse" aria-expanded="<?= $isWarehouseActive ? 'true' : 'false' ?>" aria-controls="menuWarehouse">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <i class="fa-solid fa-boxes-stacked" style="width: 20px; font-size: 0.95rem; color: <?= $isWarehouseActive ? '#38bdf8' : '#94a3b8' ?>;"></i>
                            <span class="text-truncate">Quản lý kho hàng</span>
                        </div>
                        <div class="d-flex align-items-center gap-1.5 flex-shrink-0 ms-2">
                            <?php if ($alertStock > 0): ?>
                                <span class="badge bg-danger rounded-pill px-1.5 py-0.5" style="font-size: 0.65rem;" title="<?= $alertStock ?> sản phẩm sắp hết hàng"><?= $alertStock ?></span>
                            <?php endif; ?>
                            <i class="fa-solid fa-chevron-down menu-chevron"></i>
                        </div>
                    </button>
                    <div class="collapse <?= $isWarehouseActive ? 'show' : '' ?> w-100" id="menuWarehouse" data-bs-parent="#adminSidebarNav">
                        <div class="admin-submenu">
                            <a href="products.php" class="admin-nav-link <?= ($adminPage === 'products.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-mobile-screen"></i> <span>Sản phẩm</span>
                            </a>
                            <a href="inventory.php" class="admin-nav-link <?= ($adminPage === 'inventory.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-boxes-packing"></i> <span>Tồn kho</span>
                                <?php if ($alertStock > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 0.65rem;"><?= $alertStock ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="brands.php" class="admin-nav-link <?= ($adminPage === 'brands.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-tags"></i> <span>Thương hiệu</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 2. KINH DOANH (ACCORDION XỔ XUỐNG KHI BẤM) -->
                <div class="mt-1 w-100">
                    <button type="button" class="admin-menu-toggle <?= $isBusinessActive ? 'is-active-parent' : 'collapsed' ?>" data-bs-toggle="collapse" data-bs-target="#menuBusiness" aria-expanded="<?= $isBusinessActive ? 'true' : 'false' ?>" aria-controls="menuBusiness">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <i class="fa-solid fa-cart-shopping" style="width: 20px; font-size: 0.95rem; color: <?= $isBusinessActive ? '#38bdf8' : '#94a3b8' ?>;"></i>
                            <span class="text-truncate">Kinh doanh</span>
                        </div>
                        <div class="d-flex align-items-center gap-1.5 flex-shrink-0 ms-2">
                            <?php if ($totalBizAlerts > 0): ?>
                                <span class="badge bg-warning text-dark rounded-pill px-1.5 py-0.5 fw-bold" style="font-size: 0.65rem;" title="<?= $totalBizAlerts ?> thông báo cần duyệt"><?= $totalBizAlerts ?></span>
                            <?php endif; ?>
                            <i class="fa-solid fa-chevron-down menu-chevron"></i>
                        </div>
                    </button>
                    <div class="collapse <?= $isBusinessActive ? 'show' : '' ?> w-100" id="menuBusiness" data-bs-parent="#adminSidebarNav">
                        <div class="admin-submenu">
                            <a href="orders.php" class="admin-nav-link <?= ($adminPage === 'orders.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-boxes-packing"></i> <span>Đơn hàng</span>
                                <?php if ($pendingOrdersCount > 0): ?>
                                    <span class="badge bg-warning text-dark rounded-pill ms-auto fw-bold" style="font-size: 0.65rem;"><?= $pendingOrdersCount ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="support.php" class="admin-nav-link <?= ($adminPage === 'support.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-headset"></i> <span>CSKH & Hỗ trợ</span>
                                <?php if ($pendingSupportCount > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-auto fw-bold" style="font-size: 0.65rem;"><?= $pendingSupportCount ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="warranties.php" class="admin-nav-link <?= ($adminPage === 'warranties.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-shield-halved"></i> <span>Bảo hành</span>
                                <?php if ($pendingWarrantiesCount > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-auto fw-bold" style="font-size: 0.65rem;"><?= $pendingWarrantiesCount ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="users.php" class="admin-nav-link <?= ($adminPage === 'users.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-users"></i> <span>Khách hàng</span>
                            </a>
                            <a href="reviews.php" class="admin-nav-link <?= ($adminPage === 'reviews.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-star-half-stroke"></i> <span>Đánh giá & Sao</span>
                                <?php if ($pendingReviewsCount > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-auto fw-bold" style="font-size: 0.65rem;"><?= $pendingReviewsCount ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="vouchers.php" class="admin-nav-link <?= ($adminPage === 'vouchers.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-ticket"></i> <span>Mã giảm giá</span>
                            </a>
                            <a href="news.php" class="admin-nav-link <?= ($adminPage === 'news.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-newspaper"></i> <span>Tin tức</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 3. QUẢN TRỊ NHÂN SỰ & HỆ THỐNG -->
                <div class="mt-1 w-100">
                    <button type="button" class="admin-menu-toggle <?= $isSystemActive ? 'is-active-parent' : 'collapsed' ?>" data-bs-toggle="collapse" data-bs-target="#menuSystem" aria-expanded="<?= $isSystemActive ? 'true' : 'false' ?>" aria-controls="menuSystem">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <i class="fa-solid fa-shield-cat" style="width: 20px; font-size: 0.95rem; color: <?= $isSystemActive ? '#f59e0b' : '#94a3b8' ?>;"></i>
                            <span class="text-truncate">Quản trị Hệ thống</span>
                        </div>
                        <div class="d-flex align-items-center gap-1.5 flex-shrink-0 ms-2">
                            <span class="badge bg-warning text-dark rounded-pill px-1.5 py-0.5 fw-bold" style="font-size: 0.65rem;">VIP</span>
                            <i class="fa-solid fa-chevron-down menu-chevron"></i>
                        </div>
                    </button>
                    <div class="collapse <?= $isSystemActive ? 'show' : '' ?> w-100" id="menuSystem" data-bs-parent="#adminSidebarNav">
                        <div class="admin-submenu">
                            <a href="staff.php" class="admin-nav-link <?= ($adminPage === 'staff.php') ? 'active' : '' ?>" style="color: #fbbf24;">
                                <i class="fa-solid fa-user-shield text-warning"></i> <span class="flex-grow-1 text-truncate">Nhân sự & Phân quyền</span>
                            </a>
                            <a href="staff_dashboard.php" class="admin-nav-link <?= ($adminPage === 'staff_dashboard.php') ? 'active' : '' ?>" style="color: #34d399;">
                                <i class="fa-solid fa-id-badge text-success"></i> <span>Xem Portal Nhân viên</span>
                            </a>
                            <a href="db_sync.php" class="admin-nav-link <?= ($adminPage === 'db_sync.php') ? 'active' : '' ?>" style="color: #38bdf8;">
                                <i class="fa-solid fa-database text-info"></i> <span class="flex-grow-1 text-truncate">Đồng bộ CSDL Git</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </nav>

        <!-- CHÂN TRANG CỐ ĐỊNH Ở DƯỚI -->
        <div class="p-3 pt-2 pb-3 flex-shrink-0" style="border-top: 1px solid rgba(255, 255, 255, 0.08); background: rgba(0, 0, 0, 0.2);">
            <div class="admin-theme-segmented-group mb-2" role="group">
                <button type="button" class="btn-admin-theme-choice" id="admBtnLight" onclick="setVPhoneThemeMode('light')" title="Chế độ Sáng">
                    <i class="fa-solid fa-sun text-warning"></i><span>Sáng</span>
                </button>
                <button type="button" class="btn-admin-theme-choice" id="admBtnDark" onclick="setVPhoneThemeMode('dark')" title="Chế độ Tối">
                    <i class="fa-solid fa-moon text-info"></i><span>Tối</span>
                </button>
                <button type="button" class="btn-admin-theme-choice active" id="admBtnAuto" onclick="setVPhoneThemeMode('auto')" title="Theo hệ thống máy">
                    <i class="fa-solid fa-desktop text-primary"></i><span>Máy</span>
                </button>
            </div>
            <a href="../index.php" class="admin-nav-link" style="color: #38bdf8; margin: 2px 0;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> <span>Xem Website</span>
            </a>
            <a href="../index.php?page=logout" class="admin-nav-link text-danger" style="margin: 2px 0;">
                <i class="fa-solid fa-right-from-bracket"></i> <span>Đăng xuất</span>
            </a>
        </div>
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
                    <?php if ($isStaff): ?>
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-1.5 rounded-pill border border-success-subtle">
                            <i class="fa-solid fa-id-badge me-1.5"></i>V-Phone Staff Workspace
                        </span>
                    <?php else: ?>
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-1 rounded-pill">
                            <i class="fa-solid fa-shield-halved me-1"></i>V-Phone Admin Console
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Bộ Chọn Chế Độ Giao Diện Admin (Sáng / Tối / Theo Hệ Thống) -->
                <div class="dropdown me-1">
                    <button class="btn btn-light btn-sm rounded-circle border theme-toggle-btn shadow-xs dropdown-toggle no-caret" id="themeDropdownBtnAdmin" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;" title="Giao diện: Tự động theo hệ thống máy">
                        <i class="fa-solid fa-circle-half-stroke" id="themeCurrentIconAdmin"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-2 theme-select-dropdown" style="min-width: 200px; z-index: 1070;">
                        <li>
                            <button class="dropdown-item rounded-3 py-2 small d-flex align-items-center justify-content-between theme-opt-btn theme-opt-light" type="button" onclick="setVPhoneThemeMode('light')">
                                <span><i class="fa-solid fa-sun text-warning me-2" style="width: 16px;"></i>Chế độ Sáng</span>
                                <i class="fa-solid fa-check small text-primary d-none check-icon check-light"></i>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item rounded-3 py-2 small d-flex align-items-center justify-content-between theme-opt-btn theme-opt-dark" type="button" onclick="setVPhoneThemeMode('dark')">
                                <span><i class="fa-solid fa-moon text-info me-2" style="width: 16px;"></i>Chế độ Tối</span>
                                <i class="fa-solid fa-check small text-primary d-none check-icon check-dark"></i>
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <button class="dropdown-item rounded-3 py-2 small d-flex align-items-center justify-content-between theme-opt-btn theme-opt-auto active" type="button" onclick="setVPhoneThemeMode('auto')">
                                <span><i class="fa-solid fa-desktop text-primary me-2" style="width: 16px;"></i>Theo hệ thống máy</span>
                                <i class="fa-solid fa-check small text-primary d-none check-icon check-auto"></i>
                            </button>
                        </li>
                    </ul>
                </div>
                <a href="../index.php" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold d-flex align-items-center gap-1">
                    <i class="fa-solid fa-store"></i>
                    <span class="d-none d-md-inline">Xem Cửa Hàng</span>
                    <i class="fa-solid fa-arrow-up-right-from-square small ms-1"></i>
                </a>

                <!-- DROPDOWN THÔNG BÁO THÔNG MINH -->
                <div class="dropdown me-1 me-sm-2">
                    <button class="btn btn-light btn-sm rounded-circle position-relative border <?= $totalAlerts > 0 ? 'bell-pulse text-warning' : 'text-secondary' ?>" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;" data-bs-toggle="dropdown" aria-expanded="false" title="Thông báo hệ thống">
                        <i class="fa-regular fa-bell"></i>
                        <?php if ($totalAlerts > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white" style="font-size: 0.65rem; padding: 0.25em 0.45em; transform: translate(-45%, 15%) !important;">
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
                                    <a href="orders.php?status=<?= urlencode($pOrd['status'] ?? 'Chờ xử lý') ?>" class="dropdown-item p-3 border-bottom d-flex align-items-start gap-2 text-wrap">
                                        <div class="rounded-circle bg-warning bg-opacity-25 text-warning p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                            <i class="fa-solid fa-clock-rotate-left small"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-bold text-dark small">Đơn mới #VP-<?= $pOrd['id'] ?> (<?= htmlspecialchars($pOrd['fullname']) ?>)</div>
                                            <div class="text-danger small fw-bold"><?= number_format($pOrd['total_money'], 0, ',', '.') ?> VNĐ</div>
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
                                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; background: #fef3c7; color: #d97706;">
                                            <i class="fa-solid fa-shield-halved small"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-bold text-dark small">Yêu cầu bảo hành #BH-<?= $pw['id'] ?></div>
                                            <div class="text-primary small fw-semibold text-truncate" style="max-width: 220px;"><?= htmlspecialchars($pw['product_name']) ?></div>
                                            <small class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($pw['customer_name']) ?> &bull; <?= htmlspecialchars($pw['created_at']) ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>

                                <?php foreach ($topPendingSupport as $ps): ?>
                                    <a href="support.php?status=pending" class="dropdown-item p-3 border-bottom d-flex align-items-start gap-2 text-wrap">
                                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; background: #e0f2fe; color: #0284c7;">
                                            <i class="fa-solid fa-headset small"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-bold text-dark small">Hỗ trợ CSKH #YC-<?= $ps['id'] ?> (<?= htmlspecialchars($ps['fullname']) ?>)</div>
                                            <div class="text-secondary small text-truncate" style="max-width: 220px;"><?= htmlspecialchars($ps['topic'] ?: ($ps['note'] ?? 'Yêu cầu tư vấn')) ?></div>
                                            <small class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($ps['phone']) ?> &bull; <?= htmlspecialchars($ps['created_at']) ?></small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="p-2 bg-light text-center border-top d-flex justify-content-around">
                            <a href="orders.php" class="btn btn-link btn-sm text-primary text-decoration-none fw-bold p-0" style="font-size: 0.8rem;">Đơn hàng <i class="fa-solid fa-arrow-right small ms-1"></i></a>
                            <a href="support.php" class="btn btn-link btn-sm text-info text-decoration-none fw-bold p-0" style="font-size: 0.8rem;">CSKH & Hỗ trợ <i class="fa-solid fa-arrow-right small ms-1"></i></a>
                        </div>
                    </div>
                </div>

                <div class="dropdown ms-1">
                    <button class="btn btn-light btn-sm rounded-pill px-3 py-1.5 d-flex align-items-center gap-2 border shadow-xs" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if (!empty($adminAvSrc)): ?>
                            <img src="<?= htmlspecialchars($adminAvSrc) ?>" alt="Avatar" referrerpolicy="no-referrer" class="rounded-circle border" style="width: 28px; height: 28px; object-fit: cover;">
                        <?php else: ?>
                            <span class="rounded-circle text-white d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: <?= $isStaff ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #0284c7, #0066cc)' ?>; font-size: 0.8rem; font-weight: 700;">
                                <?= mb_substr(htmlspecialchars($_SESSION['user']['fullname']), 0, 1, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                        <span class="small fw-bold d-none d-sm-inline text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($_SESSION['user']['fullname']) ?>"><?= htmlspecialchars($_SESSION['user']['fullname']) ?></span>
                        <i class="fa-solid fa-angle-down text-muted" style="font-size: 0.75rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 mt-2 p-2" style="min-width: 220px;">
                        <li class="px-3 py-2 border-bottom mb-1 bg-light rounded-3">
                            <div class="d-flex align-items-center gap-2.5">
                                <?php if (!empty($adminAvSrc)): ?>
                                    <img src="<?= htmlspecialchars($adminAvSrc) ?>" alt="Avatar" referrerpolicy="no-referrer" class="rounded-circle border flex-shrink-0" style="width: 38px; height: 38px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background: <?= $isStaff ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #0284c7, #0066cc)' ?>; font-weight: 700; font-size: 0.95rem;">
                                        <?= mb_substr(htmlspecialchars($_SESSION['user']['fullname']), 0, 1, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-bold small text-truncate text-dark" title="<?= htmlspecialchars($_SESSION['user']['fullname']) ?>"><?= htmlspecialchars($_SESSION['user']['fullname']) ?></div>
                                    <div class="text-muted text-truncate" style="font-size: 0.74rem;" title="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>"><?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?></div>
                                </div>
                            </div>
                        </li>
                        <li><a class="dropdown-item rounded-3 py-2 small fw-medium" href="../index.php"><i class="fa-solid fa-store me-2 text-primary"></i>Về trang bán hàng</a></li>
                        <li><a class="dropdown-item rounded-3 py-2 small fw-medium" href="../index.php?page=profile"><i class="fa-regular fa-id-badge me-2 text-info"></i>Hồ sơ cá nhân</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item rounded-3 py-2 small fw-medium text-danger" href="../index.php?page=logout"><i class="fa-solid fa-right-from-bracket me-2 text-danger"></i>Đăng xuất</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (!empty($accessDenied)): ?>
            <!-- MÀN HÌNH CHẶN TRUY CẬP KHI NHÂN VIÊN KHÔNG CÓ QUYỀN -->
            <div class="row justify-content-center py-4 py-lg-5">
                <div class="col-12 col-md-9 col-lg-7 text-center">
                    <div class="card border-0 shadow rounded-4 p-4 p-md-5 bg-white">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 88px; height: 88px; background: #fee2e2; color: #dc2626; box-shadow: 0 10px 25px rgba(220, 38, 38, 0.2);">
                            <i class="fa-solid fa-user-lock fs-1"></i>
                        </div>
                        <h3 class="fw-black text-danger mb-2">Truy Cập Bị Từ Chối!</h3>
                        <div class="d-inline-block mx-auto mb-3">
                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-1.5 rounded-pill fw-bold border border-danger-subtle" style="font-size: 0.8rem;">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>403 Forbidden - Không Đủ Quyền Hạn
                            </span>
                        </div>
                        
                        <p class="text-secondary mb-4" style="line-height: 1.6; font-size: 0.95rem;">
                            <?= htmlspecialchars($accessDeniedReason ?: 'Bạn không có quyền thực hiện nhiệm vụ này trên hệ thống.') ?><br>
                            Tài khoản nhân viên của bạn (<strong><?= htmlspecialchars($_SESSION['user']['fullname']) ?></strong> - <em class="text-primary"><?= htmlspecialchars(getStaffTitleDisplay()) ?></em>) chỉ có thể làm việc với các chức năng được Quản trị viên phân công.
                        </p>

                        <div class="p-3.5 bg-light rounded-4 text-start mb-4 border">
                            <div class="small fw-bold text-dark mb-2.5 d-flex align-items-center gap-1.5">
                                <i class="fa-solid fa-clipboard-check text-success fs-6"></i>
                                <span>Các nhiệm vụ bạn ĐANG CÓ QUYỀN thực hiện:</span>
                            </div>
                            <?php 
                                $myPerms = getUserPermissions();
                                if (empty($myPerms)):
                            ?>
                                <div class="text-muted small py-2 text-center fst-italic">
                                    <i class="fa-solid fa-circle-info me-1"></i>Bạn hiện chưa được gán nhiệm vụ nào. Vui lòng liên hệ Quản trị viên (Admin) để được cấp quyền ca trực!
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($myPerms as $mp): ?>
                                        <?php if (isset(VPHONE_PERMISSIONS[$mp])): ?>
                                            <a href="<?= VPHONE_PERMISSIONS[$mp]['page'] ?>" class="btn btn-sm btn-white border rounded-pill px-3 py-1.5 shadow-sm fw-bold d-inline-flex align-items-center gap-1.5 text-decoration-none text-dark hover-primary">
                                                <i class="<?= VPHONE_PERMISSIONS[$mp]['icon'] ?> <?= VPHONE_PERMISSIONS[$mp]['text_color'] ?>"></i>
                                                <span><?= VPHONE_PERMISSIONS[$mp]['name'] ?></span>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="staff_dashboard.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                                <i class="fa-solid fa-laptop-file"></i>
                                <span>Về Bàn Làm Việc Nhân Viên</span>
                            </a>
                            <a href="../index.php" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-bold d-inline-flex align-items-center gap-1.5">
                                <i class="fa-solid fa-store"></i>
                                <span>Về Trang Cửa Hàng</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
        </body>
        </html>
        <?php exit; ?>
        <?php endif; ?>

        <script>
        function toggleAdminSidebar(forceState) {
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('adminSidebarBackdrop');
            if (!sidebar) return;
            
            const isOpen = sidebar.classList.contains('show');
            const shouldOpen = (typeof forceState === 'boolean') ? forceState : !isOpen;
            
            if (shouldOpen) {
                sidebar.classList.add('show');
                if (backdrop) backdrop.classList.add('show');
            } else {
                sidebar.classList.remove('show');
                if (backdrop) backdrop.classList.remove('show');
            }
        }

        function togglePinSidebar() {
            const isCurrentlyPinned = document.body.classList.contains('sidebar-pinned') || document.documentElement.classList.contains('sidebar-pinned');
            const newState = !isCurrentlyPinned;
            
            if (newState) {
                document.body.classList.add('sidebar-pinned');
                document.documentElement.classList.add('sidebar-pinned');
                localStorage.setItem('adminSidebarPinned', 'true');
                const backdrop = document.getElementById('adminSidebarBackdrop');
                if (backdrop) backdrop.classList.remove('show');
            } else {
                document.body.classList.remove('sidebar-pinned');
                document.documentElement.classList.remove('sidebar-pinned');
                localStorage.setItem('adminSidebarPinned', 'false');
                const sidebar = document.getElementById('adminSidebar');
                const backdrop = document.getElementById('adminSidebarBackdrop');
                if (sidebar) sidebar.classList.add('show');
                if (backdrop) backdrop.classList.add('show');
            }
            updatePinButton(newState);
        }

        function updatePinButton(isPinned) {
            const pinBtn = document.getElementById('btnPinSidebar');
            const pinIcon = document.getElementById('pinIcon');
            if (pinIcon) {
                pinIcon.style.color = isPinned ? '#38bdf8' : '#94a3b8';
                pinIcon.style.transform = isPinned ? 'rotate(45deg)' : 'rotate(0deg)';
            }
            if (pinBtn) {
                pinBtn.title = isPinned ? 'Đang ghim menu cố định (Bấm để giải phóng không gian màn hình)' : 'Bấm để ghim menu cố định';
                if (isPinned) {
                    pinBtn.classList.add('text-info');
                    pinBtn.classList.remove('text-secondary');
                } else {
                    pinBtn.classList.remove('text-info');
                    pinBtn.classList.add('text-secondary');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const pinnedSetting = localStorage.getItem('adminSidebarPinned');
            const isPinned = (pinnedSetting !== 'false') && (window.innerWidth >= 992);
            if (isPinned) {
                document.body.classList.add('sidebar-pinned');
                document.documentElement.classList.add('sidebar-pinned');
            } else {
                document.body.classList.remove('sidebar-pinned');
                document.documentElement.classList.remove('sidebar-pinned');
            }
            updatePinButton(isPinned);
        });

        // Tự động đóng menu trượt mobile khi bấm phím Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const isPinned = document.body.classList.contains('sidebar-pinned');
                if (!isPinned || window.innerWidth < 992) {
                    toggleAdminSidebar(false);
                }
            }
        });

        // Tự động đóng menu mobile khi phóng to màn hình desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992) {
                const pinnedSetting = localStorage.getItem('adminSidebarPinned');
                const isPinned = (pinnedSetting !== 'false');
                if (isPinned) {
                    document.body.classList.add('sidebar-pinned');
                    document.documentElement.classList.add('sidebar-pinned');
                    const backdrop = document.getElementById('adminSidebarBackdrop');
                    if (backdrop) backdrop.classList.remove('show');
                } else {
                    document.body.classList.remove('sidebar-pinned');
                    document.documentElement.classList.remove('sidebar-pinned');
                }
                updatePinButton(isPinned);
            } else {
                document.body.classList.remove('sidebar-pinned');
                document.documentElement.classList.remove('sidebar-pinned');
            }
        });
        </script>

