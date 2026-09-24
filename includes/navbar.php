<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}
$currentUser = $_SESSION['user'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-vphone sticky-top shadow-sm py-2">
    <!-- NÚT 3 GẠCH TRÒN SÁT MÉP TRÁI NGOÀI CÙNG -->
    <button class="btn btn-light rounded-circle shadow-sm ms-3 me-2 d-flex align-items-center justify-content-center" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenuLeft" style="width: 40px; height: 40px; flex-shrink: 0;" title="Mở danh mục">
        <i class="fa-solid fa-bars fs-5 text-primary"></i>
    </button>

    <div class="container ps-0">
        <!-- LOGO V-PHONE ĐỒ HỌA MỚI -->
        <a class="navbar-brand fw-bold text-white d-flex align-items-center fs-4 me-3" href="index.php">
            <img src="assets/images/vphone-logo.svg" alt="V-Phone" style="width: 36px; height: 36px; margin-right: 8px;" class="shadow-sm rounded-3">
            <span>V-Phone</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Ô TÌM KIẾM -->
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
            </div>

            <!-- MENU PHẢI -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-2">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>" href="index.php">
                        <i class="fa-solid fa-house me-1"></i>Trang chủ
                    </a>
                </li>

                <!-- Nút Giỏ Hàng -->
                <li class="nav-item">
                    <a class="nav-link position-relative <?= ($currentPage === 'cart.php') ? 'active' : '' ?>" href="cart.php" id="cartNavLink">
                        <i class="fa-solid fa-cart-shopping me-1"></i>Giỏ hàng
                        <span class="badge bg-danger rounded-pill ms-1" id="cartBadge"><?= $cartCount ?></span>
                    </a>
                </li>

                <!-- Nút Tài Khoản -->
                <?php if ($currentUser): ?>
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle bg-white bg-opacity-10 rounded-pill px-3 text-white" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-circle-user me-1 text-warning"></i><?= htmlspecialchars($currentUser['fullname']) ?>
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
                            <li><a class="dropdown-item py-2" href="cart.php"><i class="fa-solid fa-cart-shopping me-2 text-primary"></i>Giỏ hàng của tôi</a></li>
                            <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất</a></li>
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

<!-- MODAL ĐĂNG NHẬP / ĐĂNG KÝ -->
<div class="modal fade" id="unifiedAuthModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <img src="assets/images/vphone-logo.svg" alt="V-Phone" style="width: 32px; height: 32px; margin-right: 8px;">
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
                        <form id="formModalLogin" method="POST" action="login.php">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Email tài khoản</label>
                                <input type="email" name="email" class="form-control rounded-pill" required placeholder="name@vphone.vn">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Mật khẩu</label>
                                <input type="password" name="password" class="form-control rounded-pill" required placeholder="Nhập mật khẩu">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm mb-3" id="btnLoginSubmit">
                                ĐĂNG NHẬP NGAY
                            </button>
                            <div class="p-2 rounded-3 bg-light text-secondary small text-center">
                                Gợi ý test: <strong>admin@vphone.vn</strong> hoặc <strong>hieu@vphone.vn</strong> (Pass: <strong>123456</strong>)
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab-register">
                        <form id="formModalRegister" method="POST" action="register.php">
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
                            <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm" id="btnRegisterSubmit">
                                TẠO TÀI KHOẢN
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MENU TRƯỢT GÓC TRÁI (ĐÃ GẮN LOGO MỚI + CĂN ĐỀU CHUẨN XÁC) -->
<div class="offcanvas offcanvas-start sidebar-vphone shadow-lg" tabindex="-1" id="sidebarMenuLeft">
    <!-- ĐẦU HEADER CỦA SIDEBAR CÓ LOGO ĐỒ HỌA MỚI -->
    <div class="sidebar-header-custom">
        <div class="d-flex align-items-center">
            <img src="assets/images/vphone-logo.svg" alt="V-Phone" style="width: 32px; height: 32px; margin-right: 10px;">
            <h5 class="fw-bold text-white mb-0">Danh Mục V-Phone</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="p-3 bg-light border-bottom">
        <?php if ($currentUser): ?>
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-circle-user text-primary fs-2 me-2"></i>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-bold text-dark text-truncate small"><?= htmlspecialchars($currentUser['fullname']) ?></div>
                    <small class="text-secondary"><?= htmlspecialchars($currentUser['email']) ?></small>
                </div>
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
            <a href="news.php" class="list-group-item list-group-item-action d-flex align-items-center py-2 text-primary fw-bold">
                <i class="fa-solid fa-newspaper text-primary me-3" style="width:20px;"></i> Tin tức công nghệ
            </a>
            <a href="used-phones.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 text-danger fw-bold">
                <div><i class="fa-solid fa-tags text-danger me-3" style="width:20px;"></i> Kho máy cũ 99%</div>
                <span class="badge bg-danger rounded-pill">-50%</span>
            </a>
        </div>

        <div class="text-uppercase fw-bold text-secondary small mb-2"><i class="fa-solid fa-mobile-screen me-2 text-primary"></i>Điện Thoại Mới 2026</div>
        <div class="list-group list-group-flush mb-4 rounded-3 border">
            <a href="index.php?brand_id=1" class="list-group-item list-group-item-action d-flex align-items-center py-2">
                <i class="fa-brands fa-apple text-dark me-3" style="width:20px;"></i> Apple (iPhone 18, 17, 16)
            </a>
            <a href="index.php?brand_id=2" class="list-group-item list-group-item-action d-flex align-items-center py-2">
                <i class="fa-solid fa-mobile-screen text-primary me-3" style="width:20px;"></i> Samsung Galaxy (S26, Fold)
            </a>
            <a href="index.php?brand_id=3" class="list-group-item list-group-item-action d-flex align-items-center py-2">
                <i class="fa-solid fa-bolt text-warning me-3" style="width:20px;"></i> Xiaomi Flagship
            </a>
            <a href="index.php?brand_id=4" class="list-group-item list-group-item-action d-flex align-items-center py-2">
                <i class="fa-solid fa-camera text-success me-3" style="width:20px;"></i> OPPO Camera Phone
            </a>
            <a href="index.php?brand_id=5" class="list-group-item list-group-item-action d-flex align-items-center py-2">
                <i class="fa-solid fa-layer-group text-danger me-3" style="width:20px;"></i> Huawei Tri-Fold (Gập 3)
            </a>
        </div>

        <div class="text-uppercase fw-bold text-secondary small mb-2"><i class="fa-solid fa-gear me-2 text-primary"></i>Tiện Ích & Giỏ Hàng</div>
        <div class="list-group list-group-flush mb-3 rounded-3 border">
            <a href="cart.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                <div><i class="fa-solid fa-cart-shopping text-primary me-3" style="width:20px;"></i> Giỏ hàng của bạn</div>
                <span class="badge bg-danger rounded-pill"><?= $cartCount ?></span>
            </a>
            <a href="checkout.php" class="list-group-item list-group-item-action d-flex align-items-center py-2">
                <i class="fa-solid fa-credit-card text-success me-3" style="width:20px;"></i> Thanh toán đơn hàng
            </a>
        </div>

        <div class="p-3 bg-light rounded-4 text-secondary small border">
            <div><i class="fa-solid fa-phone-volume text-primary me-2"></i>Hotline: <strong>1800 6868</strong></div>
            <div class="mt-1"><i class="fa-solid fa-shield-halved text-primary me-2"></i>Bảo hành 1 đổi 1 trong 30 ngày</div>
        </div>
    </div>
</div>


