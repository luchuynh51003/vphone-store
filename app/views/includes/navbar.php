<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../../services/GoogleAuthService.php';
$googleAuthService = new GoogleAuthService();
$googleClientId = $googleAuthService->getClientId();
$googleIsConfigured = $googleAuthService->isConfigured();
$googleIsDemo = $googleAuthService->isDemoMode();
$googleDemoUsers = $googleAuthService->getDemoUsers();

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
            $navUserNotifications = $navNotifModel->getByUserId((int)$currentUser['id'], 8);
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
                    'price_formatted' => number_format($nPrice, 0, ',', '.') . ' đ',
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
<nav class="navbar navbar-expand-lg navbar-dark navbar-vphone sticky-top shadow-sm py-2">
    <button class="btn btn-light rounded-circle shadow-sm ms-3 me-2 d-flex align-items-center justify-content-center" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenuLeft" style="width: 42px; height: 42px; flex-shrink: 0;" title="Mở danh mục">
        <i class="fa-solid fa-bars fs-5 text-primary"></i>
    </button>

    <div class="container ps-0">
        <a class="navbar-brand fw-bold text-white d-flex align-items-center fs-4 me-3" href="index.php">
            <img src="assets/images/vphone-icon.png" alt="V-Phone" style="width: 36px; height: 36px; margin-right: 8px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.3));">
            <span>V-Phone</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <div class="position-relative mx-auto my-2 my-lg-0 col-12 col-lg-5 search-wrapper">
                <form action="index.php" method="GET" autocomplete="off">
                    <div class="input-group bg-white rounded-pill p-1 shadow-sm">
                        <input id="searchInput" class="form-control border-0 bg-transparent ps-3" type="search" name="keyword" placeholder="Tìm kiếm điện thoại: iPhone, Samsung, Ultra..." value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '' ?>">
                        <button class="btn btn-primary rounded-pill px-4" type="submit">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
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

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-2">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'home') ? 'active' : '' ?>" href="index.php">
                        <i class="fa-solid fa-house me-1"></i>Trang chủ
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link position-relative <?= ($currentPage === 'cart') ? 'active' : '' ?>" href="index.php?page=cart" id="cartNavLink">
                        <i class="fa-solid fa-cart-shopping me-1"></i>Giỏ hàng
                        <span class="badge bg-danger rounded-pill ms-1" id="cartBadge"><?= $cartCount ?></span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['orders', 'tracking']) ? 'active' : '' ?>" href="index.php?page=orders" title="Tra cứu & theo dõi đơn hàng">
                        <i class="fa-solid fa-truck-fast me-1"></i>Tra cứu đơn
                    </a>
                </li>

                <?php if ($currentUser): ?>
                    <!-- Notification Bell Dropdown -->
                    <li class="nav-item dropdown position-relative ms-lg-1">
                        <button class="btn btn-link nav-link position-relative px-2.5 border-0 text-white shadow-none d-flex align-items-center" 
                                type="button" id="notifDropdownToggle" data-bs-toggle="dropdown" aria-expanded="false" title="Thông báo hệ thống">
                            <i class="fa-solid fa-bell fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $navUnreadNotifCount > 0 ? '' : 'd-none' ?>" 
                                  id="navNotifBadge" style="font-size: 0.65rem; padding: 0.25em 0.55em; transform: translate(-30%, 10%) !important;">
                                <?= $navUnreadNotifCount > 9 ? '9+' : $navUnreadNotifCount ?>
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-0 overflow-hidden" 
                             aria-labelledby="notifDropdownToggle" 
                             style="width: 360px; max-width: 90vw; border-radius: 18px; z-index: 1060;">
                            <div class="d-flex align-items-center justify-content-between px-3 py-2.5 bg-light border-bottom">
                                <span class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-bell text-primary"></i> Thông báo
                                    <span class="badge bg-danger rounded-pill small <?= $navUnreadNotifCount > 0 ? '' : 'd-none' ?>" id="navNotifHeaderCount"><?= $navUnreadNotifCount ?> mới</span>
                                </span>
                                <?php if ($navUnreadNotifCount > 0): ?>
                                    <button class="btn btn-sm btn-link text-primary text-decoration-none p-0 small fw-semibold" id="btnMarkAllNotifsRead" onclick="markAllNotificationsRead(event)">
                                        <i class="fa-solid fa-check-double me-1"></i>Đã đọc tất cả
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="notif-dropdown-list" id="navNotifList" style="max-height: 380px; overflow-y: auto;">
                                <?php if (empty($navUserNotifications)): ?>
                                    <div class="text-center py-4 px-3 text-muted" id="navNotifEmptyState">
                                        <i class="fa-regular fa-bell-slash fs-2 mb-2 text-secondary opacity-50"></i>
                                        <p class="small mb-0">Bạn chưa có thông báo nào</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($navUserNotifications as $n): ?>
                                        <div class="dropdown-item px-3 py-2.5 border-bottom text-wrap notif-item <?= empty($n['is_read']) ? 'bg-danger bg-opacity-10' : '' ?>" 
                                             style="cursor: pointer;" 
                                             onclick="handleNotifClick(<?= (int)$n['id'] ?>, <?= !empty($n['order_id']) ? (int)$n['order_id'] : 'null' ?>)">
                                            <div class="d-flex align-items-start gap-2.5">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-0.5" 
                                                     style="width: 34px; height: 34px; background: <?= $n['type'] === 'order_cancelled' ? '#fee2e2; color: #ef4444;' : '#e0f2fe; color: #0284c7;' ?>">
                                                    <i class="fa-solid <?= $n['type'] === 'order_cancelled' ? 'fa-ban' : 'fa-bell' ?> small"></i>
                                                </div>
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="d-flex align-items-center justify-content-between mb-0.5">
                                                        <span class="fw-bold text-dark small text-truncate pe-1"><?= htmlspecialchars($n['title']) ?></span>
                                                        <span class="text-muted" style="font-size: 0.68rem;"><?= date('d/m H:i', strtotime($n['created_at'])) ?></span>
                                                    </div>
                                                    <?php if (!empty($n['cancel_reason'])): ?>
                                                        <div class="mb-1">
                                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill" style="font-size: 0.7rem;">
                                                                Lý do: <?= htmlspecialchars($n['cancel_reason']) ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="text-secondary small" style="font-size: 0.76rem; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                        <?= htmlspecialchars($n['message']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="p-2 text-center bg-light border-top">
                                <a href="index.php?page=orders" class="small text-decoration-none fw-semibold text-primary">
                                    <i class="fa-solid fa-truck-fast me-1"></i>Xem quản lý đơn hàng
                                </a>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle bg-white bg-opacity-10 rounded-pill px-3 text-white d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <?php 
                                $userNavAvatar = null;
                                if (!empty($currentUser['avatar'])) {
                                    if (str_starts_with($currentUser['avatar'], 'http://') || str_starts_with($currentUser['avatar'], 'https://')) {
                                        $userNavAvatar = $currentUser['avatar'];
                                    } elseif (file_exists(__DIR__ . '/../../../' . $currentUser['avatar'])) {
                                        $userNavAvatar = $currentUser['avatar'];
                                    }
                                }
                            ?>
                            <?php if ($userNavAvatar): ?>
                                <img src="<?= htmlspecialchars($userNavAvatar) ?>" alt="Avatar" class="rounded-circle me-2 border border-white" style="width: 24px; height: 24px; object-fit: cover;">
                            <?php else: ?>
                                <i class="fa-solid fa-circle-user me-2 text-warning fs-5"></i>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($currentUser['fullname']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-2" style="min-width: 220px; border-radius: 16px;">
                            <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($currentUser['email']) ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($currentUser['role'] == 1): ?>
                                <li>
                                    <a class="dropdown-item py-2 text-danger fw-bold rounded-3 bg-danger bg-opacity-10 mb-1" href="admin/index.php">
                                        <i class="fa-solid fa-gear me-2"></i>Trang Quản Trị Admin
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><a class="dropdown-item py-2 fw-semibold <?= ($currentPage === 'profile') ? 'text-primary' : '' ?>" href="index.php?page=profile"><i class="fa-solid fa-address-card me-2 text-primary"></i>Thông tin tài khoản</a></li>
                            <li><a class="dropdown-item py-2 fw-semibold" href="index.php?page=orders"><i class="fa-solid fa-truck-ramp-box me-2 text-primary"></i>Đơn hàng của tôi</a></li>
                            <li><a class="dropdown-item py-2" href="index.php?page=cart"><i class="fa-solid fa-cart-shopping me-2 text-primary"></i>Giỏ hàng của tôi</a></li>
                            <li><a class="dropdown-item py-2 text-danger" href="index.php?page=logout"><i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2">
                        <button class="btn btn-outline-light rounded-pill px-3 py-1 d-flex align-items-center" type="button" data-bs-toggle="modal" data-bs-target="#unifiedAuthModal">
                            <i class="fa-solid fa-circle-user me-2 text-warning fs-5"></i>
                            <span>Tài khoản</span>
                        </button>
                    </li>
                <?php endif; ?>
            </ul>
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
                            <button type="button" class="btn btn-outline-secondary w-100 rounded-pill py-2 d-flex align-items-center justify-content-center shadow-sm btn-google-auth-trigger fw-semibold" style="border-color: #dadce0; background: #ffffff; color: #3c4043; transition: all 0.2s ease;">
                                <svg class="me-2" width="20" height="20" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                                </svg>
                                <span>Đăng nhập với Google</span>
                            </button>
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
                            <button type="button" class="btn btn-outline-secondary w-100 rounded-pill py-2 d-flex align-items-center justify-content-center shadow-sm btn-google-auth-trigger fw-semibold" style="border-color: #dadce0; background: #ffffff; color: #3c4043; transition: all 0.2s ease;">
                                <svg class="me-2" width="20" height="20" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                                </svg>
                                <span>Đăng ký nhanh bằng Google</span>
                            </button>
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

<div class="modal fade" id="googleChooserModal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered my-4" style="max-width: 440px; margin-top: 96px !important; margin-bottom: 40px !important;">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <svg class="me-2" width="24" height="24" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <h5 class="modal-title fw-bold text-dark mb-0">Đăng nhập với Google</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 pt-3">
                <p class="text-secondary small mb-3">Chọn một tài khoản Google để tiếp tục truy cập <strong>V-Phone Store</strong>:</p>
                
                <div id="googleAuthAlert" class="alert d-none small py-2 rounded-3 mb-3"></div>

                <div class="list-group list-group-flush mb-3 border rounded-3 overflow-hidden shadow-sm">
                    <?php foreach ($googleDemoUsers as $dUser): ?>
                        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 btn-select-demo-google" data-demo-id="<?= htmlspecialchars($dUser['google_id']) ?>">
                            <img src="<?= htmlspecialchars($dUser['avatar']) ?>" alt="Avatar" class="rounded-circle me-3 border" style="width: 42px; height: 42px; object-fit: cover;">
                            <div class="flex-grow-1 text-start">
                                <div class="fw-bold text-dark small mb-0"><?= htmlspecialchars($dUser['fullname']) ?></div>
                                <div class="text-secondary small" style="font-size: 0.8rem;"><?= htmlspecialchars($dUser['email']) ?></div>
                            </div>
                            <i class="fa-solid fa-arrow-right-to-bracket text-primary opacity-50 ms-2"></i>
                        </button>
                    <?php endforeach; ?>

                    <button type="button" class="list-group-item list-group-item-action d-flex align-items-center py-3 px-3 text-primary fw-semibold" id="btnToggleCustomGoogle">
                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px;">
                            <i class="fa-solid fa-user-plus text-primary"></i>
                        </div>
                        <span class="small">Sử dụng một tài khoản Google khác...</span>
                    </button>
                </div>

                <!-- Form nhập tài khoản Google tùy chỉnh -->
                <div id="boxCustomGoogleForm" class="d-none bg-light p-3 rounded-3 border mb-3">
                    <form id="formCustomGoogle">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Họ và tên của bạn</label>
                            <input type="text" id="customGoogleName" class="form-control form-control-sm rounded-pill" placeholder="Ví dụ: Hoàng Văn Nam" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Địa chỉ Gmail</label>
                            <input type="email" id="customGoogleEmail" class="form-control form-control-sm rounded-pill" placeholder="name@gmail.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Số điện thoại (tùy chọn)</label>
                            <input type="tel" id="customGooglePhone" class="form-control form-control-sm rounded-pill" placeholder="0901234567">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold py-2 shadow-sm">
                            <i class="fa-brands fa-google me-1"></i> Đăng nhập / Đăng ký ngay
                        </button>
                    </form>
                </div>

                <div class="p-2 bg-light-subtle border rounded-3 text-secondary small text-center" style="font-size: 0.78rem;">
                    <i class="fa-solid fa-circle-info text-primary me-1"></i>
                    Chế độ trải nghiệm nhanh Google Sign-In. Để kích hoạt OAuth Google thực tế, bạn chỉ cần điền <code>client_id</code> và <code>client_secret</code> tại <code>config/google.php</code>.
                </div>
                
                <div class="text-center mt-3">
                    <a href="index.php?page=google-login" class="text-decoration-none small text-muted">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Thử kết nối trực tiếp máy chủ Google OAuth &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.vphoneGoogleConfig = <?= json_encode([
    'client_id' => $googleClientId,
    'is_configured' => $googleIsConfigured,
    'is_demo' => $googleIsDemo,
]) ?>;
</script>

<div class="offcanvas offcanvas-start sidebar-vphone shadow-lg" tabindex="-1" id="sidebarMenuLeft">
    <div class="sidebar-header-custom">
        <div class="d-flex align-items-center">
            <img src="assets/images/vphone-icon.png" alt="V-Phone" style="width: 32px; height: 32px; margin-right: 10px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.3));">
            <h5 class="fw-bold text-white mb-0">Danh Mục V-Phone</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="p-3 bg-light border-bottom">
        <?php if ($currentUser): ?>
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center min-w-0">
                    <?php if ($userNavAvatar): ?>
                        <img src="<?= htmlspecialchars($userNavAvatar) ?>" alt="Avatar" class="rounded-circle me-3 border border-primary flex-shrink-0" style="width: 44px; height: 44px; object-fit: cover;">
                    <?php else: ?>
                        <i class="fa-solid fa-circle-user text-primary fs-1 me-3 flex-shrink-0"></i>
                    <?php endif; ?>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-dark text-truncate small"><?= htmlspecialchars($currentUser['fullname']) ?></div>
                        <small class="text-secondary"><?= htmlspecialchars($currentUser['email']) ?></small>
                    </div>
                </div>
                <a href="index.php?page=profile" class="btn btn-outline-primary btn-sm rounded-pill px-2 py-1 ms-2" title="Chỉnh sửa thông tin">
                    <i class="fa-solid fa-pen-to-square"></i>
                </a>
            </div>
        <?php else: ?>
            <button class="btn btn-primary btn-sm w-100 rounded-pill fw-bold" data-bs-dismiss="offcanvas" data-bs-toggle="modal" data-bs-target="#unifiedAuthModal">
                <i class="fa-solid fa-right-to-bracket me-1"></i> Đăng nhập / Đăng ký
            </button>
        <?php endif; ?>
    </div>

    <div class="offcanvas-body p-3" style="overflow-y: auto;">
        <div class="text-uppercase fw-bold text-secondary small mb-2"><i class="fa-solid fa-compass me-2 text-primary"></i>Khám Phá Nhanh</div>
        <div class="list-group list-group-flush mb-3 rounded-3 border">
            <a href="index.php" class="list-group-item list-group-item-action d-flex align-items-center py-2">
                <i class="fa-solid fa-house text-primary me-3" style="width:20px;"></i> Trang chủ V-Phone
            </a>
            <?php if ($currentUser): ?>
                <a href="index.php?page=profile" class="list-group-item list-group-item-action d-flex align-items-center py-2 text-primary fw-semibold">
                    <i class="fa-solid fa-address-card text-primary me-3" style="width:20px;"></i> Thông tin tài khoản
                </a>
            <?php endif; ?>
            <a href="index.php?page=news" class="list-group-item list-group-item-action d-flex align-items-center py-2 text-primary fw-bold">
                <i class="fa-solid fa-newspaper text-primary me-3" style="width:20px;"></i> Tin tức công nghệ
            </a>
            <a href="index.php?page=used-phones" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 text-danger fw-bold">
                <div><i class="fa-solid fa-tags text-danger me-3" style="width:20px;"></i> Kho máy cũ 99%</div>
                <span class="badge bg-danger rounded-pill">-50%</span>
            </a>
            <a href="index.php?page=orders" class="list-group-item list-group-item-action d-flex align-items-center py-2 text-dark fw-semibold">
                <i class="fa-solid fa-truck-fast text-info me-3" style="width:20px;"></i> Tra cứu / Theo dõi đơn hàng
            </a>
            <a href="index.php?page=warranty" class="list-group-item list-group-item-action d-flex align-items-center py-2 <?= ($currentPage === 'warranty') ? 'text-primary fw-bold bg-light' : 'text-dark fw-semibold' ?>">
                <i class="fa-solid fa-shield-halved text-primary me-3" style="width:20px;"></i> Chính sách bảo hành & đổi trả
            </a>
        </div>


        <div class="text-uppercase fw-bold text-secondary small mb-2"><i class="fa-solid fa-mobile-screen me-2 text-primary"></i>Thương Hiệu Chính Hãng</div>
        <div class="list-group list-group-flush mb-4 rounded-3 border overflow-hidden">
            <?php
            $sidebarBrands = [
                1 => ['name' => 'Apple', 'label' => 'Apple (iPhone)'],
                2 => ['name' => 'Samsung', 'label' => 'Samsung Galaxy'],
                3 => ['name' => 'Xiaomi', 'label' => 'Xiaomi Flagship'],
                4 => ['name' => 'OPPO', 'label' => 'OPPO Camera Phone'],
                5 => ['name' => 'Vivo', 'label' => 'Vivo Smartphone'],
                6 => ['name' => 'Google Pixel', 'label' => 'Google Pixel'],
                7 => ['name' => 'ASUS ROG', 'label' => 'ASUS ROG Gaming'],
                8 => ['name' => 'Sony', 'label' => 'Sony Xperia'],
                9 => ['name' => 'Huawei & Honor', 'label' => 'Huawei & Honor']
            ];
            ?>
            <?php foreach ($sidebarBrands as $bId => $bData): ?>
                <?= renderSidebarBrandItem($bId, $bData) ?>
            <?php endforeach; ?>
        </div>

        <div class="p-3 bg-light rounded-4 text-secondary small border">
            <div><i class="fa-solid fa-phone-volume text-primary me-2"></i>Hotline: <strong>1800 6868</strong></div>
            <div class="mt-1"><a href="index.php?page=warranty" class="text-secondary text-decoration-none"><i class="fa-solid fa-shield-halved text-primary me-2"></i>Bảo hành 1 đổi 1 trong 30 ngày</a></div>
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
                if (badge) badge.classList.add('d-none');
                const headerCount = document.getElementById('navNotifHeaderCount');
                if (headerCount) headerCount.classList.add('d-none');
                const btn = document.getElementById('btnMarkAllNotifsRead');
                if (btn) btn.classList.add('d-none');
                document.querySelectorAll('.notif-item').forEach(el => {
                    el.classList.remove('bg-danger', 'bg-opacity-10');
                });
            }
        })
        .catch(err => console.error('Error marking notifications as read:', err));
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
</script>

