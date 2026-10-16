<?php
$user = $user ?? ($_SESSION['user'] ?? null);
$profileMessage = $profileMessage ?? '';
$profileError = $profileError ?? '';
$totalOrders = $totalOrders ?? 0;
$totalSpent = $totalSpent ?? 0;
$statusCounts = $statusCounts ?? [];
$userAddresses = $userAddresses ?? [];
$activeTab = $_GET['tab'] ?? 'addresses';
$userAvatar = $user['avatar'] ?? '';
$hasCustomAvatar = !empty($userAvatar) && (
    str_starts_with($userAvatar, 'http://') || 
    str_starts_with($userAvatar, 'https://') || 
    file_exists(__DIR__ . '/../../' . $userAvatar)
);

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
require_once 'app/views/includes/functions.php';
?>

<style>
.profile-page {
    padding-top: 24px;
    padding-bottom: 64px;
    background-color: #f8fafc;
    min-height: calc(100vh - 350px);
}

.profile-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    padding: 28px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
}

.avatar-wrapper {
    position: relative;
    width: 104px;
    height: 104px;
    margin: 0 auto 14px;
}

.profile-avatar-large {
    width: 104px;
    height: 104px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0066cc 0%, #0284c7 100%);
    color: #ffffff;
    font-size: 2.4rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(0, 102, 204, 0.25);
    border: 4px solid #ffffff;
    outline: 2px solid #e2e8f0;
    object-fit: cover;
}

.avatar-camera-btn {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #0066cc;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.25);
    border: 2px solid #ffffff;
    transition: all 0.2s ease;
    font-size: 0.85rem;
}

.avatar-camera-btn:hover {
    background: #0052a3;
    transform: scale(1.1);
    color: #ffffff;
}

.profile-nav-pills .nav-link {
    display: flex;
    align-items: center;
    padding: 12px 18px;
    border-radius: 14px;
    color: #475569;
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 8px;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.profile-nav-pills .nav-link:hover {
    background-color: #f1f5f9;
    color: #0066cc;
    transform: translateX(4px);
}

.profile-nav-pills .nav-link.active {
    background: linear-gradient(135deg, #0066cc 0%, #0284c7 100%);
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(0, 102, 204, 0.3);
}

.profile-nav-pills .nav-link i {
    width: 24px;
    font-size: 1.1rem;
    margin-right: 12px;
}

.form-floating-custom {
    position: relative;
    margin-bottom: 1.25rem;
}

.form-floating-custom label {
    font-size: 0.85rem;
    font-weight: 700;
    color: #475569;
    margin-bottom: 6px;
    display: block;
}

.form-floating-custom .input-group-text {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0066cc;
    border-radius: 14px 0 0 14px;
}

.form-floating-custom .form-control {
    border-color: #cbd5e1;
    border-radius: 0 14px 14px 0;
    padding: 11px 16px;
    font-size: 0.95rem;
    color: #1e293b;
    font-weight: 500;
}

.form-floating-custom .form-control:focus {
    border-color: #0066cc;
    box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.12);
}

.stat-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 14px 16px;
    text-align: center;
    transition: all 0.2s ease;
}

.stat-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
}

.stat-box .stat-val {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
}

.stat-box .stat-lbl {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.address-item-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 18px;
    padding: 20px 24px;
    transition: all 0.2s ease;
    position: relative;
}

.address-item-card.is-default-card {
    border-color: #0066cc;
    background: #f8fbff;
    box-shadow: 0 4px 16px rgba(0, 102, 204, 0.08);
}

.address-item-card:hover {
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
}
</style>

<div class="profile-page">
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-0 py-2 px-3 bg-white rounded-pill shadow-sm d-inline-flex border small">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted"><i class="fa-solid fa-house me-1"></i>Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="index.php?page=profile" class="text-decoration-none text-muted">Tài khoản</a></li>
                <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Sổ địa chỉ & Thông tin cá nhân</li>
            </ol>
        </nav>

        <?php if (!empty($profileMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 py-3 px-4 shadow-sm mb-4 border-0 d-flex align-items-center" role="alert" style="background-color: #dcfce7; color: #166534;">
                <i class="fa-solid fa-circle-check fs-4 me-3 text-success"></i>
                <div class="fw-semibold"><?= htmlspecialchars($profileMessage) ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($profileError)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 py-3 px-4 shadow-sm mb-4 border-0 d-flex align-items-center" role="alert" style="background-color: #fee2e2; color: #991b1b;">
                <i class="fa-solid fa-triangle-exclamation fs-4 me-3 text-danger"></i>
                <div class="fw-semibold"><?= htmlspecialchars($profileError) ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div id="profileLiveAlert" class="alert d-none rounded-4 py-3 px-4 shadow-sm mb-4 border-0 align-items-center"></div>

        <div class="row g-4">
            <!-- CỘT BÊN TRÁI: THẺ TỔNG QUAN TÀI KHOẢN & HÌNH ẢNH ĐẠI DIỆN -->
            <div class="col-lg-4">
                <div class="profile-card text-center mb-4">
                    <!-- KHU VỰC HÌNH ẢNH ĐẠI DIỆN VÀ NÚT TẢI ẢNH -->
                    <div class="avatar-wrapper">
                        <?php if ($hasCustomAvatar): ?>
                            <img src="<?= htmlspecialchars($userAvatar) ?>?v=<?= time() ?>" alt="Avatar" id="profileAvatarDisplay" class="profile-avatar-large">
                        <?php else: ?>
                            <div id="profileAvatarDisplay" class="profile-avatar-large">
                                <?= mb_substr(htmlspecialchars($user['fullname'] ?? 'V'), 0, 1, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <label for="quickAvatarInput" class="avatar-camera-btn" title="Bấm để đổi hình ảnh đại diện">
                            <i class="fa-solid fa-camera"></i>
                        </label>
                    </div>

                    <!-- Input tải ảnh nhanh -->
                    <input type="file" id="quickAvatarInput" accept="image/png, image/jpeg, image/jpg, image/webp, image/gif" class="d-none">

                    <!-- Các nút tương tác ảnh -->
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <label for="quickAvatarInput" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-bold" style="cursor: pointer;">
                            <i class="fa-solid fa-cloud-arrow-up me-1"></i>Đổi hình ảnh
                        </label>
                        <button type="button" id="btnRemoveAvatar" class="btn btn-sm btn-light border text-danger rounded-pill px-3 py-1 fw-bold <?= $hasCustomAvatar ? '' : 'd-none' ?>" title="Gỡ hình ảnh đại diện về mặc định">
                            <i class="fa-solid fa-trash me-1"></i>Gỡ ảnh
                        </button>
                    </div>

                    <h5 class="fw-bold text-dark mb-1" id="profileDisplayFullname"><?= htmlspecialchars($user['fullname'] ?? '') ?></h5>
                    <p class="text-secondary small mb-2" id="profileDisplayEmail"><?= htmlspecialchars($user['email'] ?? '') ?></p>

                    <div class="d-flex justify-content-center flex-wrap gap-2 mb-3">
                        <?php if (($user['role'] ?? 0) == 1): ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-2 fw-bold">
                                <i class="fa-solid fa-shield-halved me-1"></i>Quản Trị Viên
                            </span>
                        <?php else: ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 fw-bold">
                                <i class="fa-solid fa-gem me-1 text-warning"></i>Khách Hàng Thân Thiết
                            </span>
                        <?php endif; ?>

                        <?php if (($user['auth_provider'] ?? '') === 'google' || !empty($user['google_id'])): ?>
                            <span class="badge bg-white text-danger border rounded-pill px-3 py-2 fw-semibold shadow-xs">
                                <i class="fa-brands fa-google text-danger me-1"></i>Tài khoản Google
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="small text-muted py-2 px-3 bg-light rounded-4 mb-4 border">
                        <i class="fa-regular fa-calendar-check me-1 text-primary"></i>
                        Thành viên từ: <strong><?= !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : 'Hôm nay' ?></strong>
                    </div>

                    <!-- THỐNG KÊ MUA HÀNG -->
                    <div class="row g-2 mb-4 text-start">
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-val text-primary"><?= $totalOrders ?></div>
                                <div class="stat-lbl">Đơn hàng</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-val text-success"><?= $statusCounts['Đã giao thành công'] ?? 0 ?></div>
                                <div class="stat-lbl">Hoàn tất</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-val text-warning"><?= ($statusCounts['Chờ xử lý'] ?? 0) + ($statusCounts['Đang xử lý'] ?? 0) ?></div>
                                <div class="stat-lbl">Đang chờ & chuẩn bị</div>
                            </div>
                        </div>
                    </div>

                    <!-- MENU ĐIỀU HƯỚNG TÀI KHOẢN -->
                    <div class="nav flex-column profile-nav-pills text-start" role="tablist">
                        <button class="nav-link <?= ($activeTab === 'addresses') ? 'active' : '' ?>" id="tab-profile-addresses-btn" data-bs-toggle="pill" data-bs-target="#tab-profile-addresses" type="button" role="tab">
                            <i class="fa-solid fa-map-location-dot text-primary"></i>
                            <span>Sổ địa chỉ & Số điện thoại</span>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill ms-auto small"><?= count($userAddresses) ?></span>
                        </button>
                        <button class="nav-link <?= ($activeTab === 'info') ? 'active' : '' ?>" id="tab-profile-info-btn" data-bs-toggle="pill" data-bs-target="#tab-profile-info" type="button" role="tab">
                            <i class="fa-solid fa-address-card text-success"></i>
                            <span>Thông tin cá nhân & Avatar</span>
                        </button>
                        <button class="nav-link <?= ($activeTab === 'security') ? 'active' : '' ?>" id="tab-profile-security-btn" data-bs-toggle="pill" data-bs-target="#tab-profile-security" type="button" role="tab">
                            <i class="fa-solid fa-lock text-info"></i>
                            <span>Đổi mật khẩu tài khoản</span>
                        </button>
                        <a href="index.php?page=orders" class="nav-link">
                            <i class="fa-solid fa-truck-fast text-warning"></i>
                            <span>Đơn hàng của tôi</span>
                            <span class="badge bg-light text-secondary border rounded-pill ms-auto small"><?= $totalOrders ?></span>
                        </a>
                        <a href="index.php?page=cart" class="nav-link">
                            <i class="fa-solid fa-cart-shopping text-secondary"></i>
                            <span>Giỏ hàng hiện tại</span>
                        </a>
                        <?php if (($user['role'] ?? 0) == 1): ?>
                            <a href="admin/index.php" class="nav-link text-danger fw-bold">
                                <i class="fa-solid fa-gear text-danger"></i>
                                <span>Trang Quản Trị Admin</span>
                            </a>
                        <?php endif; ?>
                        <hr class="my-2 border-secondary border-opacity-10">
                        <a href="index.php?page=logout" class="nav-link text-danger">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>Đăng xuất</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- CỘT BÊN PHẢI: TAB SỔ ĐỊA CHỈ, THÔNG TIN CÁ NHÂN & ĐỔI MẬT KHẨU -->
            <div class="col-lg-8">
                <div class="profile-card">
                    <div class="tab-content" id="profileTabContent">

                        <!-- TAB 1: SỔ ĐỊA CHỈ & SỐ ĐIỆN THOẠI (NHIỀU ĐỊA CHỈ, CHỌN 1 MẶC ĐỊNH) -->
                        <div class="tab-pane fade <?= ($activeTab === 'addresses') ? 'show active' : '' ?>" id="tab-profile-addresses" role="tabpanel">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
                                <div>
                                    <h4 class="fw-bold text-dark mb-1">
                                        <i class="fa-solid fa-map-location-dot text-primary me-2"></i>Sổ Địa Chỉ & Số Điện Thoại Nhận Hàng
                                    </h4>
                                    <p class="text-secondary small mb-0">Lưu nhiều địa chỉ & số điện thoại để chọn nhanh khi mua máy, chọn 1 địa chỉ làm mặc định</p>
                                </div>
                                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalAddAddress">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Thêm Địa Chỉ Mới</span>
                                </button>
                            </div>

                            <!-- DANH SÁCH CÁC ĐỊA CHỈ ĐÃ LƯU -->
                            <?php if (empty($userAddresses)): ?>
                                <div class="text-center py-5">
                                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                                        <i class="fa-solid fa-map-pin text-secondary fs-2"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">Bạn chưa có địa chỉ nào trong sổ địa chỉ</h6>
                                    <p class="text-secondary small mb-3">Hãy thêm địa chỉ giao hàng và số điện thoại để thuận tiện khi mua điện thoại tại V-Phone</p>
                                    <button type="button" class="btn btn-outline-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddAddress">
                                        <i class="fa-solid fa-plus me-1"></i>Thêm địa chỉ ngay
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-3 mb-3">
                                    <?php foreach ($userAddresses as $addr): ?>
                                        <div class="address-item-card <?= ($addr['is_default'] == 1) ? 'is-default-card' : '' ?>">
                                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($addr['recipient_name']) ?></span>
                                                    <span class="text-secondary small">|</span>
                                                    <span class="fw-semibold text-dark small"><i class="fa-solid fa-phone me-1 text-primary"></i><?= htmlspecialchars($addr['phone']) ?></span>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2 py-1 small"><?= htmlspecialchars($addr['label'] ?? 'Nhà riêng') ?></span>
                                                    <?php if ($addr['is_default'] == 1): ?>
                                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1 fw-bold small">
                                                            <i class="fa-solid fa-star me-1"></i>Mặc định
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="d-flex align-items-center gap-1">
                                                    <button type="button" class="btn btn-sm btn-light border text-primary rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalEditAddress<?= $addr['id'] ?>">
                                                        <i class="fa-solid fa-pen-to-square me-1"></i>Sửa
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-light border text-danger rounded-pill px-3 fw-bold btn-delete-address" data-id="<?= $addr['id'] ?>" title="Xóa địa chỉ này">
                                                        <i class="fa-solid fa-trash me-1"></i>Xóa
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="text-secondary small mb-3">
                                                <i class="fa-solid fa-location-dot me-2 text-danger"></i>
                                                <?= htmlspecialchars($addr['address']) ?>
                                            </div>

                                            <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                                <small class="text-muted" style="font-size: 0.78rem;">
                                                    <i class="fa-regular fa-clock me-1"></i>Ngày lưu: <?= date('d/m/Y', strtotime($addr['created_at'])) ?>
                                                </small>

                                                <?php if ($addr['is_default'] == 1): ?>
                                                    <span class="small text-danger fw-bold d-flex align-items-center gap-1">
                                                        <i class="fa-solid fa-check"></i> Đang là địa chỉ nhận hàng mặc định
                                                    </span>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold btn-set-default" data-id="<?= $addr['id'] ?>">
                                                        <i class="fa-solid fa-circle-check me-1"></i>Thiết lập làm mặc định
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="p-3 bg-light rounded-4 border mt-4 d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 flex-shrink-0">
                                    <i class="fa-solid fa-circle-info fs-5"></i>
                                </div>
                                <small class="text-secondary">
                                    Địa chỉ và số điện thoại được gắn nhãn <strong>Mặc định</strong> sẽ tự động được chọn khi bạn bấm đặt mua điện thoại tại trang <strong>Thanh toán</strong>. Bạn cũng có thể chọn nhanh bất kỳ địa chỉ nào khác khi thanh toán.
                                </small>
                            </div>
                        </div>

                        <!-- TAB 2: SỬA THÔNG TIN CÁ NHÂN & HÌNH ẢNH -->
                        <div class="tab-pane fade <?= ($activeTab === 'info') ? 'show active' : '' ?>" id="tab-profile-info" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                                <div>
                                    <h4 class="fw-bold text-dark mb-1">
                                        <i class="fa-solid fa-user-pen text-primary me-2"></i>Chỉnh Sửa Thông Tin Cá Nhân
                                    </h4>
                                    <p class="text-secondary small mb-0">Cập nhật họ tên hiển thị, địa chỉ email và ảnh đại diện của bạn</p>
                                </div>
                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold d-none d-md-inline-block">
                                    <i class="fa-solid fa-shield-halved me-1"></i>Bảo mật SSL
                                </span>
                            </div>

                            <form id="formUpdateProfile" method="POST" action="index.php?page=profile" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_profile">
                                <input type="hidden" name="remove_avatar" id="hiddenRemoveAvatar" value="0">

                                <!-- CHỌN HÌNH ẢNH ĐẠI DIỆN MỚI -->
                                <div class="p-3 bg-light rounded-4 border mb-4">
                                    <label class="form-label small fw-bold text-dark mb-2">
                                        <i class="fa-solid fa-image text-primary me-1"></i> Hình ảnh đại diện khách hàng
                                    </label>
                                    <div class="d-flex align-items-center gap-3">
                                        <div id="formAvatarPreviewWrapper">
                                            <?php if ($hasCustomAvatar): ?>
                                                <img src="<?= htmlspecialchars($userAvatar) ?>?v=<?= time() ?>" alt="Preview" id="formAvatarPreview" class="rounded-circle border" style="width: 58px; height: 58px; object-fit: cover;">
                                            <?php else: ?>
                                                <div id="formAvatarPreviewFallback" class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold" style="width: 58px; height: 58px; background: linear-gradient(135deg, #0066cc, #0284c7); font-size: 1.4rem;">
                                                    <?= mb_substr(htmlspecialchars($user['fullname'] ?? 'V'), 0, 1, 'UTF-8') ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <input type="file" name="avatar" id="formAvatarInput" class="form-control rounded-pill" accept="image/png, image/jpeg, image/jpg, image/webp, image/gif">
                                            <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">
                                                Hỗ trợ: JPG, PNG, WEBP, GIF (Dung lượng tối đa: 5MB).
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <!-- Họ và tên -->
                                    <div class="col-md-12">
                                        <div class="form-floating-custom">
                                            <label for="profileFullname"><i class="fa-solid fa-user me-1 text-primary"></i> Họ và tên khách hàng <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fa-solid fa-signature"></i></span>
                                                <input type="text" class="form-control" id="profileFullname" name="fullname" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" required placeholder="Ví dụ: Nguyễn Văn An">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Email đăng nhập -->
                                    <div class="col-md-6">
                                        <div class="form-floating-custom">
                                            <label for="profileEmail"><i class="fa-solid fa-envelope me-1 text-primary"></i> Địa chỉ Email <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fa-solid fa-at"></i></span>
                                                <input type="email" class="form-control" id="profileEmail" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required placeholder="email@example.com">
                                            </div>
                                            <small class="text-muted" style="font-size: 0.78rem;">Email dùng để đăng nhập và nhận thông báo đơn hàng.</small>
                                        </div>
                                    </div>

                                    <!-- Số điện thoại mặc định -->
                                    <div class="col-md-6">
                                        <div class="form-floating-custom">
                                            <label for="profilePhone"><i class="fa-solid fa-phone me-1 text-primary"></i> Số điện thoại chính</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fa-solid fa-phone-volume"></i></span>
                                                <input type="tel" class="form-control" id="profilePhone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="0901234567" pattern="[0-9]{9,11}">
                                            </div>
                                            <small class="text-muted" style="font-size: 0.78rem;">Đồng bộ với số điện thoại trong sổ địa chỉ mặc định.</small>
                                        </div>
                                    </div>

                                    <!-- Địa chỉ mặc định -->
                                    <div class="col-12">
                                        <div class="form-floating-custom">
                                            <label for="profileAddress"><i class="fa-solid fa-location-dot me-1 text-primary"></i> Địa chỉ giao hàng mặc định</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fa-solid fa-map-location-dot"></i></span>
                                                <input type="text" class="form-control" id="profileAddress" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố">
                                            </div>
                                            <small class="text-muted" style="font-size: 0.78rem;">
                                                Để quản lý và thêm nhiều địa chỉ khác, bạn có thể sử dụng mục <a href="#" onclick="document.getElementById('tab-profile-addresses-btn').click(); return false;">Sổ địa chỉ & Số điện thoại</a>.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="reset" class="btn btn-light rounded-pill px-4 fw-semibold text-secondary border">
                                        <i class="fa-solid fa-arrow-rotate-left me-1"></i> Khôi phục
                                    </button>
                                    <button type="submit" id="btnSaveProfile" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                        <span>Lưu Thay Đổi Thông Tin</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- TAB 3: ĐỔI MẬT KHẨU -->
                        <div class="tab-pane fade <?= ($activeTab === 'security') ? 'show active' : '' ?>" id="tab-profile-security" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                                <div>
                                    <h4 class="fw-bold text-dark mb-1">
                                        <i class="fa-solid fa-key text-primary me-2"></i>Đổi Mật Khẩu Đăng Nhập
                                    </h4>
                                    <p class="text-secondary small mb-0">Thay đổi mật khẩu định kỳ để nâng cao bảo mật cho tài khoản của bạn</p>
                                </div>
                                <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill fw-bold border border-warning border-opacity-25 d-none d-md-inline-block">
                                    <i class="fa-solid fa-lock me-1"></i>Bảo vệ 24/7
                                </span>
                            </div>

                            <form id="formChangePassword" method="POST" action="index.php?page=profile">
                                <input type="hidden" name="action" value="change_password">

                                <!-- Mật khẩu hiện tại -->
                                <div class="form-floating-custom mb-3">
                                    <label for="currentPassword"><i class="fa-solid fa-unlock-keyhole me-1 text-primary"></i> Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                        <input type="password" class="form-control" id="currentPassword" name="current_password" required placeholder="Nhập mật khẩu bạn đang dùng">
                                        <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="currentPassword">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Mật khẩu mới -->
                                <div class="form-floating-custom mb-3">
                                    <label for="newPassword"><i class="fa-solid fa-shield-halved me-1 text-primary"></i> Mật khẩu mới <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                                        <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                                        <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="newPassword">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Xác nhận mật khẩu mới -->
                                <div class="form-floating-custom mb-4">
                                    <label for="confirmPassword"><i class="fa-solid fa-circle-check me-1 text-primary"></i> Nhập lại mật khẩu mới <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa-solid fa-check-double"></i></span>
                                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required minlength="6" placeholder="Nhập lại chính xác mật khẩu mới">
                                        <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="confirmPassword">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="p-3 bg-light rounded-4 border mb-4">
                                    <div class="fw-bold text-dark small mb-2"><i class="fa-solid fa-circle-question text-info me-1"></i> Tiêu chuẩn bảo mật mật khẩu V-Phone:</div>
                                    <ul class="list-unstyled small text-secondary mb-0 ps-2">
                                        <li class="mb-1"><i class="fa-solid fa-check text-success me-1"></i> Có độ dài từ 6 ký tự trở lên</li>
                                        <li class="mb-1"><i class="fa-solid fa-check text-success me-1"></i> Không nên đặt trùng với ngày sinh hoặc số điện thoại</li>
                                        <li><i class="fa-solid fa-check text-success me-1"></i> Sau khi đổi mật khẩu, bạn vẫn được duy trì đăng nhập trên trình duyệt này</li>
                                    </ul>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" id="btnChangePassword" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-key"></i>
                                        <span>Cập Nhật Mật Khẩu Mới</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL THÊM ĐỊA CHỈ & SĐT MỚI -->
<div class="modal fade" id="modalAddAddress" tabindex="-1" aria-labelledby="modalAddAddressLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="formAddAddress" method="POST" action="index.php?page=profile">
                <input type="hidden" name="action" value="add_address">

                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 me-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="fa-solid fa-map-location-dot fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark" id="modalAddAddressLabel">Thêm Địa Chỉ & SĐT Nhận Hàng</h5>
                            <small class="text-secondary">Điền thông tin nơi nhận máy khi đặt hàng</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Họ và tên người nhận <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-primary"></i></span>
                            <input type="text" name="recipient_name" class="form-control border-start-0" required value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" placeholder="Ví dụ: Nguyễn Văn A">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Số điện thoại nhận hàng <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-phone text-primary"></i></span>
                            <input type="tel" name="phone" class="form-control border-start-0" required placeholder="Ví dụ: 0901234567" pattern="[0-9]{9,11}">
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">Số điện thoại để shipper gọi khi giao hàng.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Địa chỉ giao hàng cụ thể <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-location-dot text-primary"></i></span>
                            <textarea name="address" class="form-control border-start-0" rows="2" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố"></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark d-block">Loại địa chỉ</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="label" id="labelHome" value="Nhà riêng" checked>
                                <label class="form-check-label small" for="labelHome"><i class="fa-solid fa-house me-1 text-primary"></i>Nhà riêng</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="label" id="labelOffice" value="Văn phòng / Công ty">
                                <label class="form-check-label small" for="labelOffice"><i class="fa-solid fa-building me-1 text-info"></i>Văn phòng / Công ty</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="label" id="labelOther" value="Khác">
                                <label class="form-check-label small" for="labelOther">Khác</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-check mt-3 p-3 bg-light rounded-3 border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="is_default" value="1" id="addIsDefaultCheck" <?= empty($userAddresses) ? 'checked disabled' : '' ?>>
                        <label class="form-check-label small fw-bold text-dark" for="addIsDefaultCheck">
                            <i class="fa-solid fa-star text-warning me-1"></i> Thiết lập làm địa chỉ nhận hàng mặc định
                        </label>
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Địa chỉ này sẽ được ưu tiên tự động chọn khi thanh toán đơn hàng.</small>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold text-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="btnSubmitAddAddress" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Lưu Địa Chỉ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CÁC MODAL CHỈNH SỬA TỪNG ĐỊA CHỈ -->
<?php foreach ($userAddresses as $addr): ?>
    <div class="modal fade" id="modalEditAddress<?= $addr['id'] ?>" tabindex="-1" aria-labelledby="modalEditAddressLabel<?= $addr['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form class="form-edit-address" method="POST" action="index.php?page=profile">
                    <input type="hidden" name="action" value="update_address">
                    <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">

                    <div class="modal-header border-0 pb-0 pt-4 px-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 me-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="fa-solid fa-pen-to-square fs-5"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-dark" id="modalEditAddressLabel<?= $addr['id'] ?>">Chỉnh Sửa Địa Chỉ</h5>
                                <small class="text-secondary"><?= htmlspecialchars($addr['recipient_name']) ?> - <?= htmlspecialchars($addr['phone']) ?></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Họ và tên người nhận <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-primary"></i></span>
                                <input type="text" name="recipient_name" class="form-control border-start-0" required value="<?= htmlspecialchars($addr['recipient_name']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Số điện thoại nhận hàng <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-phone text-primary"></i></span>
                                <input type="tel" name="phone" class="form-control border-start-0" required value="<?= htmlspecialchars($addr['phone']) ?>" pattern="[0-9]{9,11}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Địa chỉ giao hàng cụ thể <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-location-dot text-primary"></i></span>
                                <textarea name="address" class="form-control border-start-0" rows="2" required><?= htmlspecialchars($addr['address']) ?></textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark d-block">Loại địa chỉ</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="label" id="labelEditHome<?= $addr['id'] ?>" value="Nhà riêng" <?= ($addr['label'] === 'Nhà riêng') ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="labelEditHome<?= $addr['id'] ?>">Nhà riêng</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="label" id="labelEditOffice<?= $addr['id'] ?>" value="Văn phòng / Công ty" <?= ($addr['label'] === 'Văn phòng / Công ty') ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="labelEditOffice<?= $addr['id'] ?>">Văn phòng / Công ty</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="label" id="labelEditOther<?= $addr['id'] ?>" value="Khác" <?= (!in_array($addr['label'], ['Nhà riêng', 'Văn phòng / Công ty'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="labelEditOther<?= $addr['id'] ?>">Khác</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mt-3 p-3 bg-light rounded-3 border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="is_default" value="1" id="editIsDefaultCheck<?= $addr['id'] ?>" <?= ($addr['is_default'] == 1) ? 'checked disabled' : '' ?>>
                            <label class="form-check-label small fw-bold text-dark" for="editIsDefaultCheck<?= $addr['id'] ?>">
                                <i class="fa-solid fa-star text-warning me-1"></i> Thiết lập làm địa chỉ nhận hàng mặc định
                            </label>
                            <?php if ($addr['is_default'] == 1): ?>
                                <small class="text-danger d-block mt-1" style="font-size: 0.72rem;">(Đây hiện đang là địa chỉ mặc định của bạn)</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold text-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Cập Nhật Địa Chỉ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const liveAlert = document.getElementById('profileLiveAlert');
    function showLiveAlert(type, message) {
        if (!liveAlert) return;
        liveAlert.className = `alert alert-${type} rounded-4 py-3 px-4 shadow-sm mb-4 border-0 d-flex align-items-center`;
        const icon = type === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-danger';
        liveAlert.innerHTML = `
            <i class="fa-solid ${icon} fs-4 me-3"></i>
            <div class="fw-semibold">${message}</div>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.classList.add('d-none')"></button>
        `;
        liveAlert.classList.remove('d-none');
        liveAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Thiết lập địa chỉ làm mặc định
    document.querySelectorAll('.btn-set-default').forEach(btn => {
        btn.addEventListener('click', async function () {
            const addrId = this.getAttribute('data-id');
            const origHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang thiết lập...';

            const formData = new FormData();
            formData.append('action', 'set_default_address');
            formData.append('address_id', addrId);
            formData.append('ajax', '1');

            try {
                const res = await fetch('index.php?page=profile', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await res.json();
                if (result.success) {
                    window.location.href = 'index.php?page=profile&tab=addresses';
                } else {
                    this.disabled = false;
                    this.innerHTML = origHtml;
                    showLiveAlert('danger', result.message || 'Không thể thiết lập mặc định!');
                }
            } catch (err) {
                window.location.reload();
            }
        });
    });

    // Xóa địa chỉ
    document.querySelectorAll('.btn-delete-address').forEach(btn => {
        btn.addEventListener('click', async function () {
            if (!confirm('Bạn có chắc chắn muốn xóa địa chỉ nhận hàng này?')) return;

            const addrId = this.getAttribute('data-id');
            const formData = new FormData();
            formData.append('action', 'delete_address');
            formData.append('address_id', addrId);
            formData.append('ajax', '1');

            try {
                const res = await fetch('index.php?page=profile', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await res.json();
                if (result.success) {
                    window.location.href = 'index.php?page=profile&tab=addresses';
                } else {
                    showLiveAlert('danger', result.message || 'Không thể xóa địa chỉ này!');
                }
            } catch (err) {
                window.location.reload();
            }
        });
    });

    // Submit form thêm địa chỉ mới
    const formAddAddress = document.getElementById('formAddAddress');
    const btnSubmitAddAddress = document.getElementById('btnSubmitAddAddress');
    if (formAddAddress) {
        formAddAddress.addEventListener('submit', async function (e) {
            e.preventDefault();
            btnSubmitAddAddress.disabled = true;
            btnSubmitAddAddress.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang lưu...';

            const formData = new FormData(formAddAddress);
            formData.append('ajax', '1');

            try {
                const res = await fetch('index.php?page=profile', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await res.json();
                if (result.success) {
                    window.location.href = 'index.php?page=profile&tab=addresses';
                } else {
                    btnSubmitAddAddress.disabled = false;
                    btnSubmitAddAddress.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Lưu Địa Chỉ';
                    alert(result.message || 'Không thể lưu địa chỉ!');
                }
            } catch (err) {
                formAddAddress.submit();
            }
        });
    }

    // Submit form chỉnh sửa từng địa chỉ
    document.querySelectorAll('.form-edit-address').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang cập nhật...';
            }

            const formData = new FormData(form);
            formData.append('ajax', '1');

            try {
                const res = await fetch('index.php?page=profile', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await res.json();
                if (result.success) {
                    window.location.href = 'index.php?page=profile&tab=addresses';
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Cập Nhật Địa Chỉ';
                    }
                    alert(result.message || 'Không thể cập nhật địa chỉ!');
                }
            } catch (err) {
                form.submit();
            }
        });
    });

    function updateAvatarUI(src) {
        const avatarWrapper = document.querySelector('.avatar-wrapper');
        const btnRemove = document.getElementById('btnRemoveAvatar');
        const formPreviewWrapper = document.getElementById('formAvatarPreviewWrapper');

        if (src) {
            avatarWrapper.innerHTML = `
                <img src="${src}?v=${Date.now()}" alt="Avatar" id="profileAvatarDisplay" class="profile-avatar-large">
                <label for="quickAvatarInput" class="avatar-camera-btn" title="Bấm để đổi hình ảnh đại diện">
                    <i class="fa-solid fa-camera"></i>
                </label>
            `;
            if (formPreviewWrapper) {
                formPreviewWrapper.innerHTML = `<img src="${src}?v=${Date.now()}" alt="Preview" id="formAvatarPreview" class="rounded-circle border" style="width: 58px; height: 58px; object-fit: cover;">`;
            }
            if (btnRemove) btnRemove.classList.remove('d-none');

            const navAvatar = document.querySelector('.navbar .dropdown-toggle img');
            if (navAvatar) {
                navAvatar.src = `${src}?v=${Date.now()}`;
            } else {
                const navToggle = document.querySelector('.navbar .dropdown-toggle');
                if (navToggle) {
                    const icon = navToggle.querySelector('.fa-circle-user');
                    if (icon) {
                        const img = document.createElement('img');
                        img.src = `${src}?v=${Date.now()}`;
                        img.alt = 'Avatar';
                        img.className = 'rounded-circle me-2 border border-white';
                        img.style.cssText = 'width: 24px; height: 24px; object-fit: cover;';
                        icon.replaceWith(img);
                    }
                }
            }
        } else {
            const name = document.getElementById('profileFullname') ? document.getElementById('profileFullname').value : 'V';
            const initial = name.trim().charAt(0).toUpperCase() || 'V';
            avatarWrapper.innerHTML = `
                <div id="profileAvatarDisplay" class="profile-avatar-large">${initial}</div>
                <label for="quickAvatarInput" class="avatar-camera-btn" title="Bấm để đổi hình ảnh đại diện">
                    <i class="fa-solid fa-camera"></i>
                </label>
            `;
            if (formPreviewWrapper) {
                formPreviewWrapper.innerHTML = `<div id="formAvatarPreviewFallback" class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold" style="width: 58px; height: 58px; background: linear-gradient(135deg, #0066cc, #0284c7); font-size: 1.4rem;">${initial}</div>`;
            }
            if (btnRemove) btnRemove.classList.add('d-none');
        }
    }

    const quickAvatarInput = document.getElementById('quickAvatarInput');
    if (quickAvatarInput) {
        quickAvatarInput.addEventListener('change', async function () {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                updateAvatarUI(e.target.result);
            };
            reader.readAsDataURL(file);

            showLiveAlert('info', '<span class="spinner-border spinner-border-sm me-2"></span>Đang tải lên và cập nhật hình ảnh đại diện...');
            const formData = new FormData();
            formData.append('action', 'upload_avatar');
            formData.append('avatar', file);
            formData.append('ajax', '1');

            try {
                const response = await fetch('index.php?page=profile', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                if (result.success) {
                    showLiveAlert('success', result.message || 'Cập nhật ảnh đại diện thành công!');
                    updateAvatarUI(result.avatar);
                } else {
                    showLiveAlert('danger', result.message || 'Không thể tải ảnh lên!');
                }
            } catch (err) {
                showLiveAlert('danger', 'Có lỗi kết nối khi tải ảnh lên máy chủ!');
            }
        });
    }

    const formAvatarInput = document.getElementById('formAvatarInput');
    if (formAvatarInput) {
        formAvatarInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (e) {
                updateAvatarUI(e.target.result);
            };
            reader.readAsDataURL(file);
        });
    }

    const btnRemoveAvatar = document.getElementById('btnRemoveAvatar');
    if (btnRemoveAvatar) {
        btnRemoveAvatar.addEventListener('click', async function () {
            if (!confirm('Bạn có chắc chắn muốn gỡ hình ảnh đại diện này không?')) return;

            showLiveAlert('info', '<span class="spinner-border spinner-border-sm me-2"></span>Đang gỡ ảnh đại diện...');
            const formData = new FormData();
            formData.append('action', 'remove_avatar');
            formData.append('ajax', '1');

            try {
                const response = await fetch('index.php?page=profile', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                if (result.success) {
                    showLiveAlert('success', result.message || 'Đã gỡ ảnh đại diện!');
                    updateAvatarUI('');
                    const hiddenRemove = document.getElementById('hiddenRemoveAvatar');
                    if (hiddenRemove) hiddenRemove.value = '1';
                } else {
                    showLiveAlert('danger', result.message || 'Không thể gỡ ảnh đại diện!');
                }
            } catch (err) {
                showLiveAlert('danger', 'Có lỗi xảy ra khi kết nối máy chủ!');
            }
        });
    }

    document.querySelectorAll('.toggle-password-btn').forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    const formUpdateProfile = document.getElementById('formUpdateProfile');
    const btnSaveProfile = document.getElementById('btnSaveProfile');
    if (formUpdateProfile) {
        formUpdateProfile.addEventListener('submit', async function (e) {
            e.preventDefault();
            const origHtml = btnSaveProfile.innerHTML;
            btnSaveProfile.disabled = true;
            btnSaveProfile.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...';

            try {
                const formData = new FormData(formUpdateProfile);
                formData.append('ajax', '1');

                const response = await fetch('index.php?page=profile', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();

                btnSaveProfile.disabled = false;
                btnSaveProfile.innerHTML = origHtml;

                if (result.success) {
                    showLiveAlert('success', result.message || 'Cập nhật thông tin thành công!');
                    if (result.user) {
                        const nameEl = document.getElementById('profileDisplayFullname');
                        const emailEl = document.getElementById('profileDisplayEmail');
                        if (nameEl) nameEl.textContent = result.user.fullname;
                        if (emailEl) emailEl.textContent = result.user.email;
                        if (result.user.avatar) {
                            updateAvatarUI(result.user.avatar);
                        }
                    }
                } else {
                    showLiveAlert('danger', result.message || 'Không thể lưu thông tin!');
                }
            } catch (err) {
                btnSaveProfile.disabled = false;
                btnSaveProfile.innerHTML = origHtml;
                formUpdateProfile.submit();
            }
        });
    }

    const formChangePassword = document.getElementById('formChangePassword');
    const btnChangePassword = document.getElementById('btnChangePassword');
    if (formChangePassword) {
        formChangePassword.addEventListener('submit', async function (e) {
            e.preventDefault();
            const origHtml = btnChangePassword.innerHTML;
            btnChangePassword.disabled = true;
            btnChangePassword.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';

            try {
                const formData = new FormData(formChangePassword);
                formData.append('ajax', '1');

                const response = await fetch('index.php?page=profile', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();

                btnChangePassword.disabled = false;
                btnChangePassword.innerHTML = origHtml;

                if (result.success) {
                    showLiveAlert('success', result.message || 'Đổi mật khẩu thành công!');
                    formChangePassword.reset();
                } else {
                    showLiveAlert('danger', result.message || 'Không thể đổi mật khẩu!');
                }
            } catch (err) {
                btnChangePassword.disabled = false;
                btnChangePassword.innerHTML = origHtml;
                formChangePassword.submit();
            }
        });
    }
});
</script>

<?php require_once 'app/views/includes/footer.php'; ?>
