<?php
/**
 * V-Phone Store - Bàn Làm Việc Nhân Viên (Staff Dashboard / Workspace)
 * Phiên bản UI Hiện Đại, Tinh Gọn, Đẳng Cấp 2026
 */
$pageTitle = 'Bàn Làm Việc Nhân Viên - V-Phone Store';
require_once __DIR__ . '/includes/header.php';

$currentUser = $_SESSION['user'] ?? [];
$userId = (int)($currentUser['id'] ?? 0);
$myPerms = getUserPermissions();
$staffTitle = getStaffTitleDisplay();

// Dữ liệu thống kê nhanh theo quyền hạn
$pendingOrdersList = [];
$pendingOrdersCount = 0;
if (currentUserCan('orders')) {
    $pendingOrdersCount = (int)($pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Chờ xử lý', 'Đang xử lý')")->fetchColumn() ?: 0);
    $pendingOrdersList = $pdo->query("SELECT id, fullname, phone, total_money, status, payment_method, created_at FROM orders WHERE status IN ('Chờ xử lý', 'Đang xử lý') ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
}

$pendingWarrantiesList = [];
$pendingWarrantiesCount = 0;
if (currentUserCan('warranties')) {
    try {
        $pendingWarrantiesCount = (int)($pdo->query("SELECT COUNT(*) FROM warranty_claims WHERE status = 'Chờ tiếp nhận'")->fetchColumn() ?: 0);
        $pendingWarrantiesList = $pdo->query("SELECT id, customer_name, customer_phone, product_name, imei, issue_type, created_at FROM warranty_claims WHERE status = 'Chờ tiếp nhận' ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}
}

$lowStockList = [];
$lowStockCount = 0;
if (currentUserCan('inventory') || currentUserCan('products')) {
    $lowStockCount = (int)($pdo->query("SELECT COUNT(*) FROM products WHERE quantity <= 5")->fetchColumn() ?: 0);
    $lowStockList = $pdo->query("SELECT id, name, price, quantity, image FROM products WHERE quantity <= 5 ORDER BY quantity ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
}

$pendingReviewsCount = 0;
if (currentUserCan('reviews')) {
    try {
        $pendingReviewsCount = (int)($pdo->query("SELECT COUNT(*) FROM product_reviews WHERE (admin_reply IS NULL OR admin_reply = '') AND status = 1")->fetchColumn() ?: 0);
    } catch (\Throwable $e) {}
}
?>

<style>
/* Tối ưu hiệu ứng UI cao cấp cho trang nhân viên */
.staff-hero-card {
    background: #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 22px;
    box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
}
.staff-stat-card {
    background: #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 20px;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.staff-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
}
.task-card-active {
    background: #ffffff;
    border: 1.5px solid #10b981;
    border-radius: 20px;
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.08);
    transition: all 0.2s ease;
}
.task-card-active:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(16, 185, 129, 0.15);
    border-color: #059669;
}
.task-card-disabled {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 20px;
    opacity: 0.72;
    transition: opacity 0.2s ease;
}
.task-card-disabled:hover {
    opacity: 0.85;
}
.digital-clock-box {
    background: linear-gradient(135deg, #0b1329 0%, #1e293b 100%);
    color: #ffffff;
    border-radius: 18px;
    padding: 12px 20px;
    box-shadow: 0 4px 14px rgba(11, 19, 41, 0.2);
}
</style>

<!-- 1. KHỐI CHÀO MỪNG NHÂN VIÊN CA TRỰC (CLEAN & MODERN TECH) -->
<div class="staff-hero-card p-4 p-lg-4 mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <?php 
                $staffAv = $currentUser['avatar'] ?? '';
                $staffAvSrc = null;
                if (!empty($staffAv)) {
                    if (str_starts_with($staffAv, 'http://') || str_starts_with($staffAv, 'https://')) {
                        $staffAvSrc = $staffAv;
                    } elseif (file_exists(__DIR__ . '/../' . $staffAv)) {
                        $staffAvSrc = '../' . $staffAv;
                    }
                }
            ?>
            <?php if ($staffAvSrc): ?>
                <img src="<?= htmlspecialchars($staffAvSrc) ?>" alt="Avatar" referrerpolicy="no-referrer" class="rounded-circle border border-2 border-white shadow-sm flex-shrink-0" style="width: 58px; height: 58px; object-fit: cover;">
            <?php else: ?>
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center shadow-sm flex-shrink-0 fw-bold" style="width: 58px; height: 58px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); font-size: 1.4rem;">
                    <?= mb_substr(htmlspecialchars($currentUser['fullname'] ?? 'NV'), 0, 1, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div>
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <h4 class="fw-black text-dark mb-0">Xin chào, <?= htmlspecialchars($currentUser['fullname'] ?? 'Nhân viên') ?>!</h4>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill fw-bold px-3 py-1" style="font-size: 0.72rem;">
                        <span class="online-dot me-1"></span>Đang trong ca trực
                    </span>
                    <?php if ($isAdmin): ?>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill fw-bold px-2.5 py-1" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-crown text-warning me-1"></i>Chế độ Admin
                        </span>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2 text-secondary small">
                    <span class="fw-semibold text-dark"><i class="fa-solid fa-id-badge text-success me-1"></i><?= htmlspecialchars($staffTitle) ?></span>
                    <span class="opacity-50">&bull;</span>
                    <span><i class="fa-solid fa-hashtag text-muted me-1"></i>Mã NV: <strong>#NV-<?= str_pad((string)$userId, 4, '0', STR_PAD_LEFT) ?></strong></span>
                    <span class="opacity-50">&bull;</span>
                    <span><i class="fa-solid fa-shield-halved text-primary me-1"></i>Được cấp: <strong class="text-success"><?= count($myPerms) ?></strong> / <?= count(VPHONE_PERMISSIONS) ?> quyền</span>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- ĐỒNG HỒ THỜI GIAN THỰC -->
            <div class="digital-clock-box text-center d-none d-sm-block">
                <div class="fw-black fs-5 lh-1 font-monospace" id="liveClockText">--:--:--</div>
                <small class="text-white text-opacity-75 mt-1 d-block" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                    <?= date('d/m/Y') ?> &bull; V-Phone Store
                </small>
            </div>

            <div class="d-flex flex-column gap-1.5">
                <a href="../index.php" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold d-flex align-items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-store"></i>
                    <span>Xem Website</span>
                    <i class="fa-solid fa-arrow-up-right-from-square small ms-0.5"></i>
                </a>
                <button type="button" class="btn btn-light border btn-sm rounded-pill px-3 fw-semibold text-secondary" onclick="window.location.reload();">
                    <i class="fa-solid fa-rotate me-1 text-muted"></i>Làm mới ca trực
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 2. HÀNG THỐNG KÊ NHIỆM VỤ CẦN XỬ LÝ (CHỈ HIỆN VIỆC CÓ QUYỀN) -->
<?php 
    $activeStatCards = 0;
    if (currentUserCan('orders')) $activeStatCards++;
    if (currentUserCan('warranties')) $activeStatCards++;
    if (currentUserCan('inventory') || currentUserCan('products')) $activeStatCards++;
    if (currentUserCan('reviews')) $activeStatCards++;
?>

<?php if ($activeStatCards > 0): ?>
    <div class="row g-3 mb-4">
        <?php if (currentUserCan('orders')): ?>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="staff-stat-card p-3.5 h-100 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-semibold">Đơn Hàng Chờ Xử Lý</span>
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #eff6ff; color: #2563eb;">
                            <i class="fa-solid fa-boxes-packing fs-6"></i>
                        </div>
                    </div>
                    <div class="fs-2 fw-black text-dark mb-1"><?= $pendingOrdersCount ?></div>
                    <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top border-light-subtle">
                        <small class="text-muted" style="font-size: 0.75rem;">Cần xác nhận giao</small>
                        <a href="orders.php" class="text-decoration-none small fw-bold text-primary d-inline-flex align-items-center gap-1">
                            <span>Vào xử lý</span>
                            <i class="fa-solid fa-arrow-right small"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (currentUserCan('warranties')): ?>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="staff-stat-card p-3.5 h-100 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-semibold">Bảo Hành Chờ Tiếp Nhận</span>
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #fef3c7; color: #d97706;">
                            <i class="fa-solid fa-shield-halved fs-6"></i>
                        </div>
                    </div>
                    <div class="fs-2 fw-black text-warning mb-1"><?= $pendingWarrantiesCount ?></div>
                    <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top border-light-subtle">
                        <small class="text-muted" style="font-size: 0.75rem;">Phiếu sửa chữa mới</small>
                        <a href="warranties.php?status=Chờ+tiếp+nhận" class="text-decoration-none small fw-bold text-warning d-inline-flex align-items-center gap-1">
                            <span>Tiếp nhận</span>
                            <i class="fa-solid fa-arrow-right small"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (currentUserCan('inventory') || currentUserCan('products')): ?>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="staff-stat-card p-3.5 h-100 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-semibold">Tồn Kho Sắp Hết (≤ 5)</span>
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #fee2e2; color: #dc2626;">
                            <i class="fa-solid fa-triangle-exclamation fs-6"></i>
                        </div>
                    </div>
                    <div class="fs-2 fw-black text-danger mb-1"><?= $lowStockCount ?></div>
                    <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top border-light-subtle">
                        <small class="text-muted" style="font-size: 0.75rem;">Cần bổ sung nguồn máy</small>
                        <a href="inventory.php?filter=low" class="text-decoration-none small fw-bold text-danger d-inline-flex align-items-center gap-1">
                            <span>Kiểm tra kho</span>
                            <i class="fa-solid fa-arrow-right small"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (currentUserCan('reviews')): ?>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="staff-stat-card p-3.5 h-100 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-semibold">Đánh Giá Chờ Phản Hồi</span>
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #f3e8ff; color: #7e22ce;">
                            <i class="fa-solid fa-star-half-stroke fs-6"></i>
                        </div>
                    </div>
                    <div class="fs-2 fw-black text-dark mb-1"><?= $pendingReviewsCount ?></div>
                    <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top border-light-subtle">
                        <small class="text-muted" style="font-size: 0.75rem;">Ý kiến & sao của khách</small>
                        <a href="reviews.php?status=unreplied" class="text-decoration-none small fw-bold text-primary d-inline-flex align-items-center gap-1">
                            <span>Trả lời (<?= $pendingReviewsCount ?>)</span>
                            <i class="fa-solid fa-arrow-right small"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- 3. KHU VỰC TRỌNG TÂM: BẢNG PHÂN CÔNG NHIỆM VỤ CA TRỰC (GRID NHIỆM VỤ) -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
    <div class="p-3.5 px-4 border-bottom bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="fa-solid fa-list-check text-success"></i>
                <span>Phân Công Nhiệm Vụ Ca Trực Của Bạn</span>
            </h5>
            <p class="text-secondary small mb-0">Hệ thống phân quyền tự động xác định phạm vi công việc. Bạn chỉ có thể thao tác với các nhiệm vụ màu xanh lá đã được cấp quyền.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-1.5 small fw-bold">
                <i class="fa-solid fa-circle-check me-1"></i><?= count($myPerms) ?> nhiệm vụ có quyền
            </span>
            <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-3 py-1.5 small fw-semibold">
                <i class="fa-solid fa-lock me-1"></i><?= count(VPHONE_PERMISSIONS) - count($myPerms) ?> nhiệm vụ bị khóa
            </span>
        </div>
    </div>

    <div class="p-4 bg-light bg-opacity-40">
        <div class="row g-3.5">
            <?php foreach (VPHONE_PERMISSIONS as $pKey => $pInfo): ?>
                <?php 
                    $hasPerm = currentUserCan($pKey);
                    $taskCountLabel = '';
                    if ($pKey === 'orders' && $pendingOrdersCount > 0) $taskCountLabel = $pendingOrdersCount . ' đơn chờ duyệt';
                    if ($pKey === 'warranties' && $pendingWarrantiesCount > 0) $taskCountLabel = $pendingWarrantiesCount . ' yêu cầu bảo hành';
                    if ($pKey === 'inventory' && $lowStockCount > 0) $taskCountLabel = $lowStockCount . ' máy sắp hết';
                    if ($pKey === 'reviews' && $pendingReviewsCount > 0) $taskCountLabel = $pendingReviewsCount . ' nhận xét mới';
                ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="h-100 <?= $hasPerm ? 'task-card-active' : 'task-card-disabled' ?> p-3.5 d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2.5">
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="rounded-3 p-2.5 d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs" style="width: 44px; height: 44px; background: <?= $hasPerm ? 'rgba(16, 185, 129, 0.12)' : '#e2e8f0' ?>; color: <?= $hasPerm ? '#059669' : '#94a3b8' ?>;">
                                    <i class="<?= $pInfo['icon'] ?> fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($pInfo['name']) ?></h6>
                                    <small class="text-muted font-monospace" style="font-size: 0.72rem;"><?= htmlspecialchars($pInfo['page']) ?></small>
                                </div>
                            </div>

                            <?php if ($hasPerm): ?>
                                <span class="badge bg-success text-white rounded-pill px-2.5 py-1 fw-bold shadow-xs" style="font-size: 0.68rem;">
                                    <i class="fa-solid fa-check-circle me-1"></i>CÓ QUYỀN
                                </span>
                            <?php else: ?>
                                <span class="badge bg-white text-muted border rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.68rem;">
                                    <i class="fa-solid fa-lock me-1"></i>BỊ KHÓA
                                </span>
                            <?php endif; ?>
                        </div>

                        <p class="small text-secondary mb-3 flex-grow-1" style="font-size: 0.8rem; line-height: 1.5;">
                            <?php if ($hasPerm): ?>
                                <?= htmlspecialchars($pInfo['desc']) ?>
                            <?php else: ?>
                                <em>Chức năng này thuộc bộ phận khác. Bạn không có quyền xem hay chỉnh sửa dữ liệu của phân hệ này.</em>
                            <?php endif; ?>
                        </p>

                        <?php if (!empty($taskCountLabel) && $hasPerm): ?>
                            <div class="mb-3">
                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning rounded-pill px-2.5 py-1 small fw-bold">
                                    <i class="fa-solid fa-bell me-1 text-warning"></i><?= $taskCountLabel ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="pt-2.5 border-top <?= $hasPerm ? 'border-success-subtle' : 'border-light-subtle' ?>">
                            <?php if ($hasPerm): ?>
                                <a href="<?= $pInfo['page'] ?>" class="btn btn-sm btn-success rounded-pill px-3 py-2 fw-bold w-100 d-flex align-items-center justify-content-center gap-1.5 shadow-sm text-decoration-none">
                                    <span>Bắt đầu làm việc</span>
                                    <i class="fa-solid fa-arrow-right small"></i>
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-light border text-muted rounded-pill px-3 py-1.5 w-100 small" disabled style="cursor: not-allowed; opacity: 0.75;">
                                    <i class="fa-solid fa-lock me-1"></i>Chưa được cấp quyền
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- 4. KHU VỰC THAO TÁC CA TRỰC & SỔ GHI CHÚ BÀN GIAO -->
<div class="row g-4 mb-4">
    <!-- Cột trái: Đơn hàng mới cần duyệt hoặc Tiêu chuẩn phục vụ -->
    <div class="col-12 col-lg-7">
        <?php if (currentUserCan('orders') && !empty($pendingOrdersList)): ?>
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="p-3.5 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-boxes-packing text-primary"></i>
                        <span>Đơn Hàng Chờ Xử Lý Gấp (Ưu Tiên Ca Trực)</span>
                    </h6>
                    <a href="orders.php" class="small text-primary text-decoration-none fw-bold">Xem tất cả <i class="fa-solid fa-arrow-right small"></i></a>
                </div>
                <div class="table-responsive">
                    <table class="table admin-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Thanh toán</th>
                                <th class="text-end">Xử lý</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingOrdersList as $pOrd): ?>
                                <tr>
                                    <td class="fw-bold text-dark">#VP-<?= $pOrd['id'] ?></td>
                                    <td>
                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($pOrd['fullname']) ?></div>
                                        <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($pOrd['phone']) ?></small>
                                    </td>
                                    <td class="text-danger fw-bold small"><?= number_format($pOrd['total_money'], 0, ',', '.') ?> đ</td>
                                    <td>
                                        <span class="badge bg-light text-dark border rounded-pill px-2 py-0.5 small"><?= htmlspecialchars($pOrd['payment_method']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="orders.php?search=<?= $pOrd['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 fw-bold">
                                            Duyệt ngay
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4">
                <h6 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-hand-holding-heart text-danger"></i>
                    <span>Tiêu Chuẩn Phục Vụ Khách Hàng Chuẩn V-Phone</span>
                </h6>
                <div class="d-flex flex-column gap-2.5">
                    <div class="p-3 rounded-3 bg-light border-start border-4 border-success">
                        <div class="fw-bold text-dark small mb-1"><i class="fa-solid fa-shield-heart text-success me-1"></i>1. Trung thực & Tận tâm:</div>
                        <small class="text-secondary">Tư vấn đúng cấu hình máy, báo rõ tình trạng bảo hành và chính sách 1 đổi 1 trong 30 ngày.</small>
                    </div>
                    <div class="p-3 rounded-3 bg-light border-start border-4 border-primary">
                        <div class="fw-bold text-dark small mb-1"><i class="fa-solid fa-truck-fast text-primary me-1"></i>2. Nhanh chóng & Chu đáo:</div>
                        <small class="text-secondary">Kiểm tra kỹ phụ kiện, dán tem niêm phong và đóng gói cẩn thận trước khi giao cho đơn vị vận chuyển.</small>
                    </div>
                    <div class="p-3 rounded-3 bg-light border-start border-4 border-warning">
                        <div class="fw-bold text-dark small mb-1"><i class="fa-solid fa-headset text-warning me-1"></i>3. Lắng nghe khách hàng:</div>
                        <small class="text-secondary">Tiếp nhận yêu cầu bảo hành và đánh giá với thái độ nhã nhặn, giải quyết thỏa đáng vì uy tín cửa hàng.</small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cột phải: Sổ ghi chú ca trực tiện ích (Notes app) -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="p-3.5 px-4 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-note-sticky text-warning"></i>
                    <span>Sổ Ghi Chú Ca Trực (Bàn Giao)</span>
                </h6>
                <span class="badge bg-light text-muted border rounded-pill small" id="staffNotesStatus"><i class="fa-solid fa-check text-success me-1"></i>Tự động lưu</span>
            </div>
            <div class="card-body p-4 d-flex flex-column">
                <p class="text-muted small mb-2">Ghi lại các việc cần lưu ý, khách hẹn nhận máy hoặc việc bàn giao cho ca tiếp theo:</p>
                <textarea id="staffShiftNotes" class="form-control rounded-3 border bg-light mb-3 flex-grow-1" rows="6" placeholder="Ví dụ:&#10;- Khách hẹn 15h qua lấy iPhone 18 Pro Max màu Titan&#10;- Phiếu bảo hành #BH-04 đã tiếp nhận, hẹn khách chiều mai&#10;- Đã kiểm tra số lượng máy tồn kho đầu ca..."></textarea>
                <div class="d-flex justify-content-between align-items-center mt-auto">
                    <small class="text-muted" style="font-size: 0.72rem;">Dữ liệu ghi chú được lưu an toàn trên máy</small>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 fw-bold" onclick="saveStaffNotes()">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Lưu ngay
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Đồng hồ điện tử thời gian thực
function updateLiveClock() {
    const clockEl = document.getElementById('liveClockText');
    if (!clockEl) return;
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    clockEl.textContent = `${h}:${m}:${s}`;
}
setInterval(updateLiveClock, 1000);
updateLiveClock();

// Quản lý ghi chú ca trực lưu nhanh vào localStorage
document.addEventListener('DOMContentLoaded', function() {
    const noteArea = document.getElementById('staffShiftNotes');
    const noteStatus = document.getElementById('staffNotesStatus');
    const storageKey = 'vphone_staff_notes_<?= $userId ?>';
    
    if (noteArea) {
        const saved = localStorage.getItem(storageKey);
        if (saved) {
            noteArea.value = saved;
        }
        noteArea.addEventListener('input', function() {
            localStorage.setItem(storageKey, this.value);
            if (noteStatus) {
                noteStatus.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1 text-primary"></i>Đang lưu...';
                clearTimeout(window._staffNoteTimer);
                window._staffNoteTimer = setTimeout(() => {
                    noteStatus.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i>Đã tự lưu';
                }, 400);
            }
        });
    }
});

function saveStaffNotes() {
    const noteArea = document.getElementById('staffShiftNotes');
    const noteStatus = document.getElementById('staffNotesStatus');
    const storageKey = 'vphone_staff_notes_<?= $userId ?>';
    if (noteArea) {
        localStorage.setItem(storageKey, noteArea.value);
        if (noteStatus) {
            noteStatus.innerHTML = '<i class="fa-solid fa-circle-check text-success me-1"></i>Đã lưu thành công!';
        }
        Swal.fire({
            icon: 'success',
            title: 'Đã lưu ghi chú ca trực!',
            text: 'Nội dung ghi chú ca làm việc đã được lưu lại an toàn.',
            timer: 1500,
            showConfirmButton: false,
            borderRadius: 16
        });
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
