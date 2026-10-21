<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../../services/GoogleAuthService.php';
$googleAuthService = new GoogleAuthService();
$googleClientId = $googleAuthService->getClientId();
$googleIsConfigured = $googleAuthService->isConfigured();

$currentPage = $_GET['page'] ?? 'home';
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) { $cartCount += $item['quantity']; }
}
$currentUser = $_SESSION['user'] ?? null;
$navUserNotifications = [];
$navUnreadNotifCount = 0;
if ($currentUser && !empty($currentUser['id'])) {
    try {
        require_once __DIR__ . '/../../models/NotificationModel.php';
        $dbForNotif = $pdo ?? ($GLOBALS['pdo'] ?? null);
        if (!$dbForNotif) {
            require_once __DIR__ . '/../../../config/database.php';
            $dbForNotif = $pdo ?? ($GLOBALS['pdo'] ?? null);
        }
        if ($dbForNotif instanceof PDO) {
            $navNotifModel = new NotificationModel($dbForNotif);
            $navUnreadNotifCount = $navNotifModel->getUnreadCount((int)$currentUser['id']);
            $navUserNotifications = $navNotifModel->getByUserId((int)$currentUser['id'], 30);
        }
    } catch (\Throwable $e) {}
}

if (empty($GLOBALS['vphone_search_products_loaded'])) {
    $GLOBALS['vphone_search_products_loaded'] = true;
    try {
        $db = $pdo ?? ($GLOBALS['pdo'] ?? null);
        if (!$db) {
            require_once __DIR__ . '/../../../config/database.php';
            $db = $pdo ?? ($GLOBALS['pdo'] ?? null);
        }
        if ($db instanceof PDO) {
            $navSearchStmt = $db->query("SELECT p.id, p.name, p.price, p.sale_price, p.image, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id ORDER BY p.is_featured DESC, p.id ASC");
            $navRawProds = $navSearchStmt->fetchAll(PDO::FETCH_ASSOC);
            $navSearchList = [];
            foreach ($navRawProds as $np) {
                $nPrice = ($np['sale_price'] > 0 && $np['sale_price'] < $np['price']) ? (int)$np['sale_price'] : (int)$np['price'];
                $navSearchList[] = [
                    'id' => (int)$np['id'],
                    'name' => $np['name'],
                    'brand_name' => $np['brand_name'] ?? '',
                    'price' => $nPrice,
                    'old_price' => (int)$np['price'],
                    'price_formatted' => number_format($nPrice, 0, ',', '.') . ' VNĐ',
                    'image' => $np['image']
                ];
            }
            $GLOBALS['vphone_nav_search_list'] = $navSearchList;
        }
    } catch (\Throwable $e) {
        $GLOBALS['vphone_nav_search_list'] = [];
    }
}
$navSearchList = $GLOBALS['vphone_nav_search_list'] ?? [];
?>
<nav class="navbar navbar-vphone sticky-top">
    <div class="container-fluid px-3 px-xl-4 d-flex flex-wrap align-items-center justify-content-between">
        <!-- NHÓM TRÁI: DANH MỤC & LOGO V-PHONE -->
        <div class="nav-left-group d-flex align-items-center gap-2 gap-sm-3 flex-shrink-0">
            <!-- Nút Mở Danh Mục Khám Phá -->
            <button class="btn btn-nav-category d-flex align-items-center gap-2 flex-shrink-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenuLeft" title="Mở danh mục khám phá">
                <div class="nav-category-icon-box">
                    <i class="fa-solid fa-bars-staggered"></i>
                </div>
                <span class="d-none d-sm-inline fw-semibold nav-category-label">Danh mục</span>
            </button>

            <!-- Logo V-Phone (Bấm về Trang chủ) -->
            <a class="navbar-brand-vphone d-flex align-items-center flex-shrink-0" href="index.php" title="V-Phone Flagship Store">
                <img src="assets/images/vphone-icon.png" alt="V-Phone" class="navbar-logo-img">
                <span class="navbar-logo-text">V-Phone</span>
                <span class="brand-flagship-tag d-none d-xl-inline-block">FLAGSHIP</span>
            </a>
        </div>

        <!-- TRUNG TÂM: Ô TÌM KIẾM HIỆN ĐẠI CAPSULE GLASS -->
        <div class="nav-search-section position-relative">
            <form action="index.php" method="GET" autocomplete="off" class="m-0">
                <div class="nav-search-inner d-flex align-items-center">
                    <button type="submit" class="nav-search-icon-btn" title="Tìm kiếm">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <input id="searchInput" class="nav-search-input" type="search" name="keyword" placeholder="Tìm kiếm iPhone 16 Pro, Galaxy S25, Xiaomi..." value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '' ?>">
                </div>
            </form>
            <div id="searchDropdown" class="search-dropdown-menu d-none">
                <div id="searchResultsList"></div>
            </div>
            <script>
                window.GLOBAL_SEARCH_PRODUCTS = <?= json_encode($navSearchList ?? [], JSON_UNESCAPED_UNICODE) ?>;
                if (!window.STORE_PRODUCTS || window.STORE_PRODUCTS.length === 0) {
                    window.STORE_PRODUCTS = window.GLOBAL_SEARCH_PRODUCTS;
                }
            </script>
        </div>

        <!-- NHÓM PHẢI: TIỆN ÍCH THIẾT YẾU (GIỎ HÀNG - THÔNG BÁO - TÀI KHOẢN) -->
        <div class="nav-right-group d-flex align-items-center gap-2 gap-sm-2.5 flex-shrink-0">
            <!-- Bộ Chọn Chế Độ Giao Diện (Sáng / Tối / Theo Hệ Thống) -->
            <div class="dropdown position-relative">
                <button class="nav-action-circle theme-toggle-btn dropdown-toggle no-caret" id="themeDropdownBtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Giao diện: Tự động theo hệ thống máy">
                    <i class="fa-solid fa-circle-half-stroke" id="themeCurrentIcon"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-2 theme-select-dropdown" style="min-width: 200px;">
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
            <!-- Giỏ Hàng -->
            <a class="nav-action-pill nav-cart-pill <?= ($currentPage === 'cart') ? 'active' : '' ?>" href="index.php?page=cart" id="cartNavLink" title="Giỏ hàng của bạn">
                <div class="nav-action-icon-box position-relative">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="nav-badge-pill bg-danger" id="cartBadge"><?= $cartCount ?></span>
                </div>
                <span class="d-none d-md-inline fw-semibold nav-action-text">Giỏ hàng</span>
            </a>

            <?php if ($currentUser): ?>
                <!-- Chuông Thông Báo -->
                <div class="dropdown position-relative">
                    <button class="nav-action-circle position-relative" 
                            type="button" id="notifDropdownToggle" data-bs-toggle="dropdown" aria-expanded="false" title="Thông báo hệ thống">
                        <i class="fa-solid fa-bell"></i>
                        <span class="nav-badge-pill bg-danger <?= $navUnreadNotifCount > 0 ? '' : 'd-none' ?>" 
                              id="navNotifBadge" <?= $navUnreadNotifCount > 0 ? '' : 'style="display: none !important;"' ?>>
                            <?= $navUnreadNotifCount > 9 ? '9+' : ($navUnreadNotifCount > 0 ? $navUnreadNotifCount : '') ?>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-0 notif-dropdown-menu" 
                         aria-labelledby="notifDropdownToggle" 
                         style="width: 430px; max-width: calc(100vw - 24px); border-radius: 20px; z-index: 1060; border: 1px solid rgba(15, 23, 42, 0.08); box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.22), 0 4px 12px rgba(0, 0, 0, 0.05); overflow: hidden;">
                            <!-- Header -->
                            <div class="d-flex align-items-center justify-content-between px-3.5 py-3 border-bottom" style="background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary flex-shrink-0" style="width: 34px; height: 34px;">
                                        <i class="fa-solid fa-bell" style="font-size: 0.9rem;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark d-flex align-items-center gap-2" style="font-size: 0.95rem; line-height: 1.2;">
                                            <span>Thông báo</span>
                                            <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 <?= $navUnreadNotifCount > 0 ? '' : 'd-none' ?>" 
                                                  id="navNotifHeaderCount" style="font-size: 0.7rem; font-weight: 700; padding: 0.25em 0.6em;">
                                                <?= $navUnreadNotifCount ?> mới
                                            </span>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Cập nhật đơn hàng & hệ thống</div>
                                    </div>
                                </div>
                                <?php if ($navUnreadNotifCount > 0): ?>
                                    <button class="btn btn-sm btn-light border text-primary fw-semibold rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1 shadow-none" 
                                            id="btnMarkAllNotifsRead" 
                                            onclick="markAllNotificationsRead(event)"
                                            style="font-size: 0.74rem;">
                                        <i class="fa-solid fa-check-double text-primary"></i>
                                        <span>Đã đọc tất cả</span>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <!-- Notification List -->
                            <div class="notif-dropdown-list" id="navNotifList" style="max-height: 420px; overflow-y: auto;">
                                <?php if (empty($navUserNotifications)): ?>
                                    <div class="text-center py-5 px-3 text-muted" id="navNotifEmptyState">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-light text-muted mb-2.5" style="width: 52px; height: 52px;">
                                            <i class="fa-regular fa-bell-slash fs-4 text-secondary opacity-50"></i>
                                        </div>
                                        <div class="fw-bold text-dark small">Bạn chưa có thông báo nào</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Các cập nhật về đơn hàng sẽ xuất hiện tại đây</div>
                                    </div>
                                <?php else: ?>
                                    <?php 
                                        $notifIndex = 0;
                                        $initialVisibleLimit = 4; // Mặc định hiển thị 4 thông báo đầu, các thông báo cũ hơn ẩn bớt
                                    ?>
                                    <?php foreach ($navUserNotifications as $n): ?>
                                        <?php 
                                            $notifIndex++;
                                            $isExtra = ($notifIndex > $initialVisibleLimit);
                                            $type = $n['type'] ?? 'order_cancelled';
                                            $isUnread = empty($n['is_read']);
                                            
                                            $cfg = [
                                                'order_shipping' => [
                                                    'icon' => 'fa-solid fa-truck-fast',
                                                    'icon_bg' => '#e0f2fe',
                                                    'icon_color' => '#0284c7',
                                                    'icon_border' => '#bae6fd',
                                                    'badge_bg' => '#e0f2fe',
                                                    'badge_color' => '#0284c7',
                                                    'badge_border' => '#bae6fd',
                                                    'badge_text' => 'Đang giao hàng',
                                                    'badge_icon' => 'fa-solid fa-truck-fast',
                                                    'unread_class' => 'notif-unread-shipping',
                                                    'dot_color' => '#0284c7',
                                                    'action_text' => 'Theo dõi hành trình',
                                                    'action_color' => 'text-primary'
                                                ],
                                                'order_delivered' => [
                                                    'icon' => 'fa-solid fa-circle-check',
                                                    'icon_bg' => '#dcfce7',
                                                    'icon_color' => '#16a34a',
                                                    'icon_border' => '#bbf7d0',
                                                    'badge_bg' => '#dcfce7',
                                                    'badge_color' => '#16a34a',
                                                    'badge_border' => '#bbf7d0',
                                                    'badge_text' => 'Giao thành công',
                                                    'badge_icon' => 'fa-solid fa-circle-check',
                                                    'unread_class' => 'notif-unread-delivered',
                                                    'dot_color' => '#16a34a',
                                                    'action_text' => 'Xem chi tiết đơn',
                                                    'action_color' => 'text-success'
                                                ],
                                                'order_processing' => [
                                                    'icon' => 'fa-solid fa-boxes-packing',
                                                    'icon_bg' => '#eff6ff',
                                                    'icon_color' => '#2563eb',
                                                    'icon_border' => '#bfdbfe',
                                                    'badge_bg' => '#eff6ff',
                                                    'badge_color' => '#2563eb',
                                                    'badge_border' => '#bfdbfe',
                                                    'badge_text' => 'Đang chuẩn bị',
                                                    'badge_icon' => 'fa-solid fa-boxes-packing',
                                                    'unread_class' => 'notif-unread-processing',
                                                    'dot_color' => '#2563eb',
                                                    'action_text' => 'Chi tiết đơn',
                                                    'action_color' => 'text-primary'
                                                ],
                                                'order_cancelled' => [
                                                    'icon' => 'fa-solid fa-ban',
                                                    'icon_bg' => '#fee2e2',
                                                    'icon_color' => '#dc2626',
                                                    'icon_border' => '#fecaca',
                                                    'badge_bg' => '#fee2e2',
                                                    'badge_color' => '#dc2626',
                                                    'badge_border' => '#fecaca',
                                                    'badge_text' => 'Đã hủy đơn',
                                                    'badge_icon' => 'fa-solid fa-ban',
                                                    'unread_class' => 'notif-unread-cancelled',
                                                    'dot_color' => '#dc2626',
                                                    'action_text' => 'Chi tiết hủy',
                                                    'action_color' => 'text-danger'
                                                ],
                                            ];

                                            $curCfg = $cfg[$type] ?? [
                                                'icon' => 'fa-solid fa-bell',
                                                'icon_bg' => '#f1f5f9',
                                                'icon_color' => '#475569',
                                                'icon_border' => '#e2e8f0',
                                                'badge_bg' => '#f1f5f9',
                                                'badge_color' => '#475569',
                                                'badge_border' => '#e2e8f0',
                                                'badge_text' => 'Hệ thống',
                                                'badge_icon' => 'fa-solid fa-bell',
                                                'unread_class' => 'notif-unread-default',
                                                'dot_color' => '#0066cc',
                                                'action_text' => 'Xem chi tiết',
                                                'action_color' => 'text-primary'
                                            ];

                                            // Trích xuất số tiền thanh toán nếu có trong message
                                            $orderTotal = '';
                                            if (preg_match('/Tổng thanh toán:\s*([^)]+)/iu', $n['message'], $mTotal)) {
                                                $orderTotal = trim($mTotal[1]);
                                            }
                                        ?>
                                        <div class="dropdown-item px-3.5 py-3 border-bottom text-wrap notif-item <?= $isExtra ? 'notif-item-extra d-none' : '' ?> <?= $isUnread ? $curCfg['unread_class'] : 'notif-read' ?>" 
                                             style="cursor: pointer; transition: all 0.15s ease;" 
                                             onclick="handleNotifClick(<?= (int)$n['id'] ?>, <?= !empty($n['order_id']) ? (int)$n['order_id'] : 'null' ?>)">
                                            <div class="d-flex align-items-start gap-3">
                                                <!-- Icon Trạng Thái -->
                                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 mt-0.5" 
                                                     style="width: 38px; height: 38px; background: <?= $curCfg['icon_bg'] ?>; color: <?= $curCfg['icon_color'] ?>; border: 1.5px solid <?= $curCfg['icon_border'] ?>;">
                                                    <i class="<?= $curCfg['icon'] ?>" style="font-size: 0.95rem;"></i>
                                                </div>

                                                <!-- Nội Dung Chi Tiết -->
                                                <div class="flex-grow-1 min-w-0">
                                                    <!-- Tiêu đề & Thời gian -->
                                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                                        <span class="fw-bold text-dark text-truncate" style="font-size: 0.86rem;">
                                                            <?= htmlspecialchars($n['title']) ?>
                                                        </span>
                                                        <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                                                            <span class="text-muted" style="font-size: 0.7rem;">
                                                                <?= date('d/m • H:i', strtotime($n['created_at'])) ?>
                                                            </span>
                                                            <?php if ($isUnread): ?>
                                                                <span class="notif-dot rounded-circle" style="width: 7px; height: 7px; background: <?= $curCfg['dot_color'] ?>; display: inline-block;"></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <!-- Khối Lý Do Hủy (Nếu là thông báo hủy) -->
                                                    <?php if (!empty($n['cancel_reason'])): ?>
                                                        <div class="p-2 px-2.5 rounded-3 my-1.5" style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.18);">
                                                            <div class="d-flex align-items-center gap-1.5 text-danger fw-bold" style="font-size: 0.72rem;">
                                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                                <span>Lý do hủy từ cửa hàng:</span>
                                                            </div>
                                                            <div class="text-dark fw-medium mt-0.5" style="font-size: 0.78rem; line-height: 1.45;">
                                                                <?= htmlspecialchars($n['cancel_reason']) ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Nội dung tin nhắn tóm tắt -->
                                                    <?php if (empty($n['cancel_reason'])): ?>
                                                        <div class="text-secondary mt-1 notif-message-clamp">
                                                            <?= htmlspecialchars($n['message']) ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Footer hàng thông báo: Badge trạng thái & Nút thao tác -->
                                                    <div class="d-flex align-items-center justify-content-between mt-1.5 pt-1 border-top border-light text-muted" style="font-size: 0.74rem;">
                                                        <div class="d-flex align-items-center gap-1.5">
                                                            <span class="badge rounded-pill" style="background: <?= $curCfg['badge_bg'] ?>; color: <?= $curCfg['badge_color'] ?>; border: 1px solid <?= $curCfg['badge_border'] ?>; font-size: 0.7rem; font-weight: 600;">
                                                                <i class="<?= $curCfg['badge_icon'] ?> me-1"></i><?= $curCfg['badge_text'] ?>
                                                            </span>
                                                            <?php if ($orderTotal): ?>
                                                                <span class="text-muted ms-1" style="font-size: 0.72rem;">• <?= htmlspecialchars($orderTotal) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <span class="<?= $curCfg['action_color'] ?> fw-medium d-inline-flex align-items-center gap-1">
                                                            <span><?= $curCfg['action_text'] ?></span>
                                                            <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <!-- Nút xem thêm / thu gọn khi danh sách có nhiều thông báo -->
                                    <?php if (count($navUserNotifications) > $initialVisibleLimit): ?>
                                        <?php $extraCount = count($navUserNotifications) - $initialVisibleLimit; ?>
                                        <div class="p-2 text-center border-bottom bg-light bg-opacity-75" id="notifLoadMoreWrap">
                                            <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-1 text-primary fw-semibold d-inline-flex align-items-center gap-1.5 shadow-none" 
                                                    id="btnToggleMoreNotifs" 
                                                    onclick="toggleMoreNotifications(event)" 
                                                    style="font-size: 0.76rem; background: #ffffff; transition: all 0.2s;">
                                                <i class="fa-solid fa-angles-down" id="iconToggleNotifs"></i>
                                                <span id="textToggleNotifs">Xem thêm <?= $extraCount ?> thông báo cũ hơn</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Footer -->
                            <div class="p-2.5 text-center bg-light bg-opacity-75 border-top">
                                <a href="index.php?page=orders" class="small text-decoration-none fw-bold text-primary d-inline-flex align-items-center gap-1.5">
                                    <i class="fa-solid fa-truck-fast"></i>
                                    <span>Xem tất cả đơn hàng & lịch sử</span>
                                    <i class="fa-solid fa-arrow-right small opacity-75" style="font-size: 0.68rem;"></i>
                                </a>
                            </div>
                    </div>
                </div>

                <!-- Tài Khoản (Đã Đăng Nhập) -->
                <div class="dropdown position-relative">
                    <a class="nav-user-pill dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" title="<?= htmlspecialchars($currentUser['fullname']) ?>">
                        <?php 
                            $userNavAvatar = null;
                            if (!empty($currentUser['avatar'])) {
                                if (str_starts_with($currentUser['avatar'], 'http://') || str_starts_with($currentUser['avatar'], 'https://')) {
                                    $userNavAvatar = $currentUser['avatar'];
                                } elseif (file_exists(__DIR__ . '/../../../' . $currentUser['avatar'])) {
                                    $userNavAvatar = $currentUser['avatar'] . '?v=' . filemtime(__DIR__ . '/../../../' . $currentUser['avatar']);
                                }
                            }
                        ?>
                        <?php if ($userNavAvatar): ?>
                            <img src="<?= htmlspecialchars($userNavAvatar) ?>" alt="Avatar" referrerpolicy="no-referrer" class="nav-user-avatar">
                        <?php else: ?>
                            <span class="nav-user-avatar nav-user-avatar-text shadow-xs">
                                <?= mb_substr(htmlspecialchars($currentUser['fullname']), 0, 1, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                        <div class="d-none d-md-flex flex-column text-start lh-1 min-w-0" style="max-width: 140px;">
                            <span class="nav-user-sub">Xin chào,</span>
                            <span class="nav-user-name text-truncate"><?= htmlspecialchars($currentUser['fullname']) ?></span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-2" style="min-width: 250px; border-radius: 18px;">
                        <li class="px-3 py-2.5 bg-light rounded-3 mb-2 border">
                            <div class="d-flex align-items-center gap-2.5">
                                <?php if ($userNavAvatar): ?>
                                    <img src="<?= htmlspecialchars($userNavAvatar) ?>" alt="Avatar" referrerpolicy="no-referrer" class="rounded-circle border flex-shrink-0 shadow-xs" style="width: 38px; height: 38px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs" style="width: 38px; height: 38px; background: linear-gradient(135deg, #0284c7, #0066cc); font-weight: 700; font-size: 0.9rem;">
                                        <?= mb_substr(htmlspecialchars($currentUser['fullname']), 0, 1, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($currentUser['fullname']) ?>"><?= htmlspecialchars($currentUser['fullname']) ?></div>
                                    <div class="small text-muted text-truncate font-monospace" style="font-size: 0.74rem;"><?= htmlspecialchars($currentUser['email']) ?></div>
                                </div>
                            </div>
                        </li>
                        <?php if ((int)($currentUser['role'] ?? 0) === 1): ?>
                            <li>
                                <a class="dropdown-item py-2 text-danger fw-bold rounded-3 bg-danger bg-opacity-10 mb-1" href="admin/index.php">
                                    <i class="fa-solid fa-gear me-2"></i>Trang Quản Trị Admin
                                </a>
                            </li>
                        <?php elseif ((int)($currentUser['role'] ?? 0) === 2): ?>
                            <li>
                                <a class="dropdown-item py-2 text-success fw-bold rounded-3 bg-success bg-opacity-10 mb-1" href="admin/staff_dashboard.php">
                                    <i class="fa-solid fa-laptop-file me-2"></i>Bàn Làm Việc Nhân Viên
                                </a>
                            </li>
                        <?php endif; ?>
                        <li><a class="dropdown-item py-2 fw-semibold <?= ($currentPage === 'profile') ? 'text-primary' : '' ?>" href="index.php?page=profile"><i class="fa-solid fa-address-card me-2 text-primary"></i>Thông tin tài khoản</a></li>
                        <li><a class="dropdown-item py-2 fw-semibold" href="index.php?page=orders"><i class="fa-solid fa-truck-ramp-box me-2 text-primary"></i>Đơn hàng của tôi</a></li>
                        <li><a class="dropdown-item py-2" href="index.php?page=cart"><i class="fa-solid fa-cart-shopping me-2 text-primary"></i>Giỏ hàng của tôi</a></li>
                        <li><a class="dropdown-item py-2 text-danger" href="index.php?page=logout"><i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <!-- Nút Tài Khoản / Đăng Nhập -->
                <button class="btn btn-nav-auth d-flex align-items-center gap-2" type="button" data-bs-toggle="modal" data-bs-target="#unifiedAuthModal">
                    <div class="nav-auth-icon-box">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <span class="fw-semibold nav-auth-text d-none d-sm-inline">Tài khoản</span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="modal fade" id="unifiedAuthModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <img src="assets/images/vphone-icon.png" alt="V-Phone" style="width: 32px; height: 32px; margin-right: 8px; object-fit: contain;">
                    <h5 class="modal-title fw-bold text-dark">Tài Khoản V-Phone</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="nav nav-pills nav-fill bg-light p-1 rounded-pill mb-4">
                    <li class="nav-item">
                        <button class="nav-link active rounded-pill fw-bold small py-2" data-bs-toggle="pill" data-bs-target="#tab-login" type="button">ĐĂNG NHẬP</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link rounded-pill fw-bold small py-2" data-bs-toggle="pill" data-bs-target="#tab-register" type="button">ĐĂNG KÝ MỚI</button>
                    </li>
                </ul>
                <div id="authAlert" class="alert d-none small py-2 rounded-3 mb-3"></div>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-login">
                        <!-- Nút Đăng nhập bằng Google -->
                        <div class="mb-3">
                            <a href="index.php?page=google-login" class="btn btn-outline-secondary w-100 rounded-pill py-2 d-flex align-items-center justify-content-center shadow-sm btn-google-auth-trigger fw-semibold text-decoration-none" style="border-color: #dadce0; background: #ffffff; color: #3c4043; transition: all 0.2s ease;">
                                <svg class="me-2" width="20" height="20" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                                </svg>
                                <span>Đăng nhập với Google</span>
                            </a>
                        </div>

                        <div class="position-relative text-center my-3">
                            <hr class="text-muted opacity-25 m-0">
                            <span class="position-absolute top-50 start-50 translate-middle bg-white px-2 small text-muted" style="font-size: 0.82rem;">hoặc dùng tài khoản Email</span>
                        </div>

                        <form id="formModalLogin" method="POST" action="index.php?page=auth&action=login">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Email tài khoản</label>
                                <input type="email" name="email" class="form-control rounded-pill" required placeholder="name@vphone.vn">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Mật khẩu</label>
                                <input type="password" name="password" class="form-control rounded-pill" required placeholder="Nhập mật khẩu">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm mb-3">ĐĂNG NHẬP NGAY</button>
                            <div class="p-2 rounded-3 bg-light text-secondary small text-center">
                                Gợi ý test: <strong>admin@vphone.vn</strong> hoặc <strong>hieu@vphone.vn</strong> (Pass: <strong>123456</strong>)
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="tab-register">
                        <!-- Nút Đăng ký nhanh bằng Google -->
                        <div class="mb-3">
                            <a href="index.php?page=google-login" class="btn btn-outline-secondary w-100 rounded-pill py-2 d-flex align-items-center justify-content-center shadow-sm btn-google-auth-trigger fw-semibold text-decoration-none" style="border-color: #dadce0; background: #ffffff; color: #3c4043; transition: all 0.2s ease;">
                                <svg class="me-2" width="20" height="20" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                                </svg>
                                <span>Đăng ký nhanh bằng Google</span>
                            </a>
                        </div>

                        <div class="position-relative text-center my-3">
                            <hr class="text-muted opacity-25 m-0">
                            <span class="position-absolute top-50 start-50 translate-middle bg-white px-2 small text-muted" style="font-size: 0.82rem;">hoặc điền thông tin tài khoản</span>
                        </div>

                        <form id="formModalRegister" method="POST" action="index.php?page=auth&action=register">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Họ và tên *</label>
                                <input type="text" name="fullname" class="form-control rounded-pill" required placeholder="Ví dụ: Nguyễn Văn A">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Email *</label>
                                <input type="email" name="email" class="form-control rounded-pill" required placeholder="email@example.com">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Số điện thoại</label>
                                <input type="tel" name="phone" class="form-control rounded-pill" placeholder="0901234567">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Mật khẩu *</label>
                                <input type="password" name="password" class="form-control rounded-pill" required placeholder="Tối thiểu 6 ký tự">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm">TẠO TÀI KHOẢN</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.vphoneGoogleConfig = <?= json_encode([
    'client_id' => $googleClientId,
    'is_configured' => $googleIsConfigured,
]) ?>;
</script>

<div class="offcanvas offcanvas-start sidebar-vphone shadow-lg" tabindex="-1" id="sidebarMenuLeft">
    <div class="sidebar-header-custom">
        <div class="d-flex align-items-center">
            <img src="assets/images/vphone-icon.png" alt="V-Phone" style="width: 28px; height: 28px; margin-right: 8px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.3));">
            <h6 class="fw-bold text-white mb-0">Danh Mục V-Phone</h6>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close" style="font-size: 0.8rem;"></button>
    </div>

    <!-- KHỐI TÀI KHOẢN NGƯỜI DÙNG TINH GỌN -->
    <div class="py-2.5 px-3 bg-light border-bottom">
        <?php if ($currentUser): ?>
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <?php if ($userNavAvatar): ?>
                        <img src="<?= htmlspecialchars($userNavAvatar) ?>" alt="Avatar" referrerpolicy="no-referrer" class="rounded-circle border border-primary flex-shrink-0" style="width: 32px; height: 32px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; background: linear-gradient(135deg, #0284c7, #0066cc); font-weight: 700; font-size: 0.82rem;">
                            <?= mb_substr(htmlspecialchars($currentUser['fullname']), 0, 1, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-bold text-dark text-truncate small" style="font-size: 0.82rem; line-height: 1.2;">Xin chào, <?= htmlspecialchars($currentUser['fullname']) ?></div>
                        <small class="text-secondary text-truncate d-block font-monospace" style="font-size: 0.7rem;"><?= htmlspecialchars($currentUser['email']) ?></small>
                    </div>
                </div>
                <a href="index.php?page=profile" class="btn btn-outline-primary btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 30px; height: 30px;" title="Chỉnh sửa thông tin">
                    <i class="fa-solid fa-user-pen" style="font-size: 0.75rem;"></i>
                </a>
            </div>
        <?php else: ?>
            <button class="btn btn-primary btn-sm w-100 rounded-pill fw-bold py-1.5 shadow-xs" data-bs-dismiss="offcanvas" data-bs-toggle="modal" data-bs-target="#unifiedAuthModal" style="font-size: 0.84rem;">
                <i class="fa-solid fa-circle-user me-1.5"></i> Đăng nhập / Đăng ký
            </button>
        <?php endif; ?>
    </div>

    <div class="offcanvas-body p-3">
        <!-- CHUYỂN ĐỔI CHẾ ĐỘ SÁNG / TỐI / HỆ THỐNG (MOBILE) -->
        <div class="p-2.5 px-3 rounded-3 mb-3 bg-light border theme-toggle-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-bold text-dark"><i class="fa-solid fa-circle-half-stroke text-primary me-1.5"></i>Giao diện</span>
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill small" id="mobileThemeStatusBadge">Theo hệ thống</span>
            </div>
            <div class="theme-segmented-group" role="group">
                <button type="button" class="btn btn-theme-choice" id="mBtnLight" onclick="setVPhoneThemeMode('light')" title="Chế độ Sáng">
                    <i class="fa-solid fa-sun text-warning"></i><span>Sáng</span>
                </button>
                <button type="button" class="btn btn-theme-choice" id="mBtnDark" onclick="setVPhoneThemeMode('dark')" title="Chế độ Tối">
                    <i class="fa-solid fa-moon text-info"></i><span>Tối</span>
                </button>
                <button type="button" class="btn btn-theme-choice active" id="mBtnAuto" onclick="setVPhoneThemeMode('auto')" title="Theo hệ thống máy">
                    <i class="fa-solid fa-desktop text-primary"></i><span>Tự động</span>
                </button>
            </div>
        </div>
        <!-- TIỆN ÍCH MUA SẮM THIẾT YẾU -->
        <div class="text-uppercase fw-bold text-secondary mb-2 d-flex align-items-center justify-content-between" style="font-size: 0.7rem; letter-spacing: 0.4px;">
            <span><i class="fa-solid fa-compass me-1.5 text-primary"></i>Tiện Ích Nhanh</span>
            <span class="text-muted fw-normal" style="font-size: 0.68rem;">Thiết yếu</span>
        </div>
        <div class="list-group list-group-flush mb-3 rounded-3 border overflow-hidden">
            <a href="index.php?page=used-phones" class="list-group-item list-group-item-action sidebar-menu-item d-flex justify-content-between align-items-center py-2 px-3 text-danger fw-bold border-0 border-bottom">
                <div class="d-flex align-items-center gap-2.5 min-w-0">
                    <i class="fa-solid fa-tags text-danger" style="width: 18px;"></i>
                    <span class="text-truncate">Kho máy cũ 99%</span>
                </div>
                <span class="badge bg-danger rounded-pill px-2 py-0.5 flex-shrink-0" style="font-size: 0.68rem;">-50%</span>
            </a>
            <a href="index.php?page=compare" class="list-group-item list-group-item-action sidebar-menu-item d-flex justify-content-between align-items-center py-2 px-3 <?= in_array($currentPage, ['compare', 'so-sanh']) ? 'text-primary fw-bold bg-light' : 'text-dark fw-semibold' ?> border-0 border-bottom">
                <div class="d-flex align-items-center gap-2.5 min-w-0">
                    <i class="fa-solid fa-code-compare text-primary" style="width: 18px;"></i>
                    <span class="text-truncate">So sánh điện thoại</span>
                </div>
                <span class="badge bg-primary rounded-pill px-2 py-0.5 flex-shrink-0" style="font-size: 0.68rem;">Hot</span>
            </a>
            <a href="index.php?page=orders" class="list-group-item list-group-item-action sidebar-menu-item d-flex align-items-center py-2 px-3 text-dark fw-semibold border-0 border-bottom">
                <div class="d-flex align-items-center gap-2.5 min-w-0">
                    <i class="fa-solid fa-truck-fast text-info" style="width: 18px;"></i>
                    <span class="text-truncate">Tra cứu đơn hàng</span>
                </div>
            </a>
            <a href="index.php?page=stores" class="list-group-item list-group-item-action sidebar-menu-item d-flex justify-content-between align-items-center py-2 px-3 <?= in_array($currentPage, ['stores', 'cua-hang', 'he-thong-cua-hang']) ? 'text-primary fw-bold bg-light' : 'text-dark fw-semibold' ?> border-0 border-bottom">
                <div class="d-flex align-items-center gap-2.5 min-w-0">
                    <i class="fa-solid fa-store text-primary" style="width: 18px;"></i>
                    <span class="text-truncate">Hệ thống cửa hàng</span>
                </div>
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-0.5 flex-shrink-0" style="font-size: 0.68rem;">15 shop</span>
            </a>
            <a href="index.php?page=warranty" class="list-group-item list-group-item-action sidebar-menu-item d-flex justify-content-between align-items-center py-2 px-3 <?= ($currentPage === 'warranty') ? 'text-primary fw-bold bg-light' : 'text-dark fw-semibold' ?> border-0 border-bottom">
                <div class="d-flex align-items-center gap-2.5 min-w-0">
                    <i class="fa-solid fa-shield-halved text-success" style="width: 18px;"></i>
                    <span class="text-truncate">Chính sách bảo hành</span>
                </div>
                <span class="badge bg-light text-secondary border rounded-pill px-2 py-0.5 flex-shrink-0" style="font-size: 0.68rem;">12T</span>
            </a>

            <!-- Nút Thu Gọn Các Trang Phụ (Tin Tức, Giới Thiệu) -->
            <?php $isExtraPageActive = in_array($currentPage, ['news', 'tin-tuc', 'about', 'about-us', 'gioi-thieu']); ?>
            <button class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 px-3 text-secondary small bg-light border-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExtraPages" aria-expanded="<?= $isExtraPageActive ? 'true' : 'false' ?>" aria-controls="collapseExtraPages" id="btnToggleExtraPages" style="font-size: 0.82rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-ellipsis text-primary" style="width: 18px;"></i>
                    <span class="fw-semibold text-primary">Thông tin & Tin tức khác</span>
                </div>
                <i class="fa-solid fa-chevron-down small text-primary" style="transition: transform 0.2s ease;"></i>
            </button>
            <div class="collapse <?= $isExtraPageActive ? 'show' : '' ?>" id="collapseExtraPages">
                <a href="index.php?page=news" class="list-group-item list-group-item-action sidebar-menu-item d-flex align-items-center py-2 px-3 ps-4 text-secondary small bg-light bg-opacity-50 border-0 border-top">
                    <div class="d-flex align-items-center gap-2.5 min-w-0">
                        <i class="fa-solid fa-newspaper text-muted" style="width: 16px;"></i>
                        <span class="text-truncate">Tin tức công nghệ</span>
                    </div>
                </a>
                <a href="index.php?page=about" class="list-group-item list-group-item-action sidebar-menu-item d-flex align-items-center py-2 px-3 ps-4 text-secondary small bg-light bg-opacity-50 border-0 border-top <?= in_array($currentPage, ['about', 'about-us', 'gioi-thieu']) ? 'text-primary fw-bold' : '' ?>">
                    <div class="d-flex align-items-center gap-2.5 min-w-0">
                        <i class="fa-solid fa-circle-info text-muted" style="width: 16px;"></i>
                        <span class="text-truncate">Giới thiệu về V-Phone</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- THƯƠNG HIỆU ĐIỆN THOẠI CHÍNH HÃNG -->
        <div class="text-uppercase fw-bold text-secondary mb-2 mt-3 d-flex align-items-center justify-content-between" style="font-size: 0.7rem; letter-spacing: 0.4px;">
            <span><i class="fa-solid fa-mobile-screen me-1.5 text-primary"></i>Thương Hiệu Chính Hãng</span>
            <span class="text-muted fw-normal" style="font-size: 0.68rem;">9 Hãng</span>
        </div>
        <div class="list-group list-group-flush mb-3 rounded-3 border overflow-hidden">
            <?php
            $mainBrands = [
                1 => ['name' => 'Apple', 'label' => 'Apple (iPhone)'],
                2 => ['name' => 'Samsung', 'label' => 'Samsung Galaxy'],
                3 => ['name' => 'Xiaomi', 'label' => 'Xiaomi Flagship'],
                4 => ['name' => 'OPPO', 'label' => 'OPPO Camera Phone']
            ];
            $otherBrands = [
                5 => ['name' => 'Vivo', 'label' => 'Vivo Smartphone'],
                6 => ['name' => 'Google Pixel', 'label' => 'Google Pixel'],
                7 => ['name' => 'ASUS ROG', 'label' => 'ASUS ROG Gaming'],
                8 => ['name' => 'Sony', 'label' => 'Sony Xperia'],
                9 => ['name' => 'Huawei & Honor', 'label' => 'Huawei & Honor']
            ];
            $currentBrandId = (int)($_GET['brand_id'] ?? 0);
            $isOtherBrandActive = array_key_exists($currentBrandId, $otherBrands);
            ?>
            <?php foreach ($mainBrands as $bId => $bData): ?>
                <?= renderSidebarBrandItem($bId, $bData) ?>
            <?php endforeach; ?>
            <button class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 px-3 text-primary fw-semibold bg-light border-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOtherBrands" aria-expanded="<?= $isOtherBrandActive ? 'true' : 'false' ?>" aria-controls="collapseOtherBrands" id="btnToggleOtherBrands" style="font-size: 0.82rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-layer-group text-primary" style="width: 18px;"></i>
                    <span>Xem thêm thương hiệu khác (<?= count($otherBrands) ?>)</span>
                </div>
                <i class="fa-solid fa-chevron-down small" style="transition: transform 0.2s ease;"></i>
            </button>
            <div class="collapse <?= $isOtherBrandActive ? 'show' : '' ?>" id="collapseOtherBrands">
                <?php foreach ($otherBrands as $bId => $bData): ?>
                    <?= renderSidebarBrandItem($bId, $bData) ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TỔNG ĐÀI HỖ TRỢ TINH GỌN (1 DÒNG) -->
        <div class="py-2.5 px-3 rounded-3 border d-flex align-items-center justify-content-between mt-2 sidebar-support-bar" style="font-size: 0.82rem; background: rgba(2, 132, 199, 0.08); border-color: rgba(2, 132, 199, 0.2) !important;">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-phone-volume text-primary"></i>
                <span class="text-muted">Tổng đài:</span>
            </div>
            <a href="tel:18006868" class="text-primary fw-bold text-decoration-none text-nowrap flex-shrink-0" style="letter-spacing: 0.2px;">1800 6868 <span class="small fw-normal text-muted" style="font-size: 0.72rem;">(Miễn phí)</span></a>
        </div>
    </div>
</div>

<script>
function markAllNotificationsRead(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    fetch('api/notifications.php?action=mark_all_read', { credentials: 'same-origin' })
        .then(res => res.json())
        .then(data => {
            if (data && data.success) {
                const badge = document.getElementById('navNotifBadge');
                if (badge) {
                    badge.classList.add('d-none');
                    badge.style.display = 'none';
                    badge.textContent = '';
                }
                const headerCount = document.getElementById('navNotifHeaderCount');
                if (headerCount) {
                    headerCount.classList.add('d-none');
                    headerCount.style.display = 'none';
                }
                const btn = document.getElementById('btnMarkAllNotifsRead');
                if (btn) btn.classList.add('d-none');
                document.querySelectorAll('.notif-item').forEach(el => {
                    el.classList.remove('notif-unread-cancelled', 'notif-unread-shipping', 'notif-unread-delivered', 'notif-unread-processing', 'notif-unread-default', 'bg-danger', 'bg-opacity-10');
                    el.classList.add('notif-read');
                    el.style.borderLeft = '4px solid transparent';
                    el.style.backgroundColor = '#ffffff';
                });
                document.querySelectorAll('.notif-dot').forEach(dot => dot.remove());
            }
        })
        .catch(err => console.error('Error marking notifications as read:', err));
}

function toggleMoreNotifications(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const extraItems = document.querySelectorAll('.notif-item-extra');
    const textSpan = document.getElementById('textToggleNotifs');
    const iconEl = document.getElementById('iconToggleNotifs');
    if (!extraItems.length) return;

    const isHidden = extraItems[0].classList.contains('d-none');
    extraItems.forEach(el => {
        if (isHidden) {
            el.classList.remove('d-none');
        } else {
            el.classList.add('d-none');
        }
    });

    if (isHidden) {
        if (textSpan) textSpan.textContent = 'Thu gọn thông báo';
        if (iconEl) iconEl.className = 'fa-solid fa-angles-up';
    } else {
        if (textSpan) textSpan.textContent = 'Xem thêm ' + extraItems.length + ' thông báo cũ hơn';
        if (iconEl) iconEl.className = 'fa-solid fa-angles-down';
    }
}

function handleNotifClick(notifId, orderId) {
    fetch('api/notifications.php?action=mark_read&id=' + notifId, { credentials: 'same-origin' })
        .finally(() => {
            if (orderId) {
                window.location.href = 'index.php?page=orders&order_code=' + orderId;
            } else {
                window.location.href = 'index.php?page=orders';
            }
        });
}

// Eliminate layout shifts & jitter when opening/closing offcanvas or modals
document.addEventListener('DOMContentLoaded', function () {
    const sidebarEl = document.getElementById('sidebarMenuLeft');
    if (sidebarEl) {
        ['show.bs.offcanvas', 'shown.bs.offcanvas', 'hide.bs.offcanvas', 'hidden.bs.offcanvas'].forEach(function (evt) {
            sidebarEl.addEventListener(evt, function () {
                document.body.style.paddingRight = '0px';
                const navbar = document.querySelector('.navbar-vphone');
                if (navbar) navbar.style.paddingRight = '0px';
            });
        });
    }

    if (window.MutationObserver) {
        const obs = new MutationObserver(function (mutations) {
            for (let i = 0; i < mutations.length; i++) {
                if (mutations[i].attributeName === 'style' && document.body.style.paddingRight && document.body.style.paddingRight !== '0px') {
                    document.body.style.paddingRight = '0px';
                }
            }
        });
        obs.observe(document.body, { attributes: true, attributeFilter: ['style'] });
    }
});
</script>

