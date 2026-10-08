<?php
$pageTitle = 'Dashboard Quản Trị - V-Phone';
require_once 'includes/header.php';

// Tính các chỉ số trong một lượt đọc mỗi bảng.
$stats = $pdo->query("SELECT
    COUNT(*) AS total_orders,
    COALESCE(SUM(CASE WHEN status = 'Đã giao thành công' THEN total_money ELSE 0 END), 0) AS total_revenue,
    COALESCE(SUM(CASE WHEN status = 'Chờ xử lý' THEN 1 ELSE 0 END), 0) AS pending_orders,
    (SELECT COUNT(*) FROM products) AS total_products,
    (SELECT COUNT(*) FROM users WHERE role = 0) AS total_users
    FROM orders")->fetch();
$totalRevenue = $stats['total_revenue'];
$totalOrders = (int)$stats['total_orders'];
$pendingOrders = (int)$stats['pending_orders'];
$totalProducts = (int)$stats['total_products'];
$totalUsers = (int)$stats['total_users'];

// Lấy 5 đơn hàng mới nhất cần duyệt
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();
?>

<!-- HEADER TITLE & ACTIONS -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Bảng Điều Khiển Quản Trị</h3>
        <p class="text-secondary small mb-0">Hệ thống giám sát doanh thu, đơn hàng và kho vận V-Phone Flagship 2026</p>
    </div>
    <div class="d-flex gap-2">
        <a href="inventory.php" class="btn btn-outline-secondary rounded-pill fw-bold btn-sm px-3">
            <i class="fa-solid fa-boxes-stacked me-1"></i>Kiểm kho tồn
        </a>
        <a href="products.php" class="btn btn-primary rounded-pill fw-bold btn-sm px-3 shadow-sm">
            <i class="fa-solid fa-plus me-1"></i>Thêm điện thoại mới
        </a>
    </div>
</div>

<!-- 4 THẺ THỐNG KÊ KPI HIỆN ĐẠI 2026 -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="admin-kpi-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Doanh thu đã thu</span>
                    <h3 class="fw-black text-dark my-2"><?= number_format($totalRevenue, 0, ',', '.') ?> <span class="fs-6 fw-bold text-muted">đ</span></h3>
                    <span class="badge badge-soft-success rounded-pill px-2 py-1">
                        <i class="fa-solid fa-arrow-trend-up me-1"></i>Đơn thành công
                    </span>
                </div>
                <div class="admin-kpi-icon-box" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; box-shadow: 0 8px 16px rgba(16, 185, 129, 0.25);">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="admin-kpi-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Đơn hàng mới</span>
                    <h3 class="fw-black text-dark my-2"><?= $pendingOrders ?> <span class="fs-6 fw-bold text-muted">đơn</span></h3>
                    <?php if ($pendingOrders > 0): ?>
                        <span class="badge badge-soft-danger rounded-pill px-2 py-1">
                            <i class="fa-solid fa-bell me-1"></i>Cần xử lý ngay
                        </span>
                    <?php else: ?>
                        <span class="badge badge-soft-success rounded-pill px-2 py-1">
                            <i class="fa-solid fa-check me-1"></i>Đã xử lý hết
                        </span>
                    <?php endif; ?>
                </div>
                <div class="admin-kpi-icon-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; box-shadow: 0 8px 16px rgba(245, 158, 11, 0.25);">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="admin-kpi-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Sản phẩm trong kho</span>
                    <h3 class="fw-black text-dark my-2"><?= $totalProducts ?> <span class="fs-6 fw-bold text-muted">mẫu</span></h3>
                    <span class="badge badge-soft-primary rounded-pill px-2 py-1">
                        <i class="fa-solid fa-mobile-screen me-1"></i>Đang kinh doanh
                    </span>
                </div>
                <div class="admin-kpi-icon-box" style="background: linear-gradient(135deg, #0066cc 0%, #0284c7 100%); color: #ffffff; box-shadow: 0 8px 16px rgba(0, 102, 204, 0.25);">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="admin-kpi-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Khách hàng đăng ký</span>
                    <h3 class="fw-black text-dark my-2"><?= $totalUsers ?> <span class="fs-6 fw-bold text-muted">thành viên</span></h3>
                    <span class="badge badge-soft-secondary rounded-pill px-2 py-1">
                        <i class="fa-solid fa-users me-1"></i>Tài khoản hoạt động
                    </span>
                </div>
                <div class="admin-kpi-icon-box" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: #ffffff; box-shadow: 0 8px 16px rgba(139, 92, 246, 0.25);">
                    <i class="fa-solid fa-user-group"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- LỐI TẮT NHANH (QUICK ACTIONS) -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <a href="products.php" class="card border-0 rounded-4 p-3 text-decoration-none shadow-sm h-100 d-flex flex-row align-items-center gap-3 bg-white hover-lift">
            <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: #eff6ff; color: #0066cc;">
                <i class="fa-solid fa-plus fs-5"></i>
            </div>
            <div>
                <div class="fw-bold text-dark small">Thêm sản phẩm</div>
                <small class="text-muted" style="font-size: 0.75rem;">Đăng bán máy mới</small>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-6">
        <a href="inventory.php" class="card border-0 rounded-4 p-3 text-decoration-none shadow-sm h-100 d-flex flex-row align-items-center gap-3 bg-white hover-lift">
            <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: #fffbeb; color: #d97706;">
                <i class="fa-solid fa-boxes-stacked fs-5"></i>
            </div>
            <div>
                <div class="fw-bold text-dark small">Kiểm kê tồn kho</div>
                <small class="text-muted" style="font-size: 0.75rem;">Cảnh báo hết hàng</small>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-6">
        <a href="orders.php" class="card border-0 rounded-4 p-3 text-decoration-none shadow-sm h-100 d-flex flex-row align-items-center gap-3 bg-white hover-lift">
            <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: #ecfdf5; color: #059669;">
                <i class="fa-solid fa-truck-ramp-box fs-5"></i>
            </div>
            <div>
                <div class="fw-bold text-dark small">Quản lý đơn hàng</div>
                <small class="text-muted" style="font-size: 0.75rem;">Duyệt & xuất kho</small>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-6">
        <a href="vouchers.php" class="card border-0 rounded-4 p-3 text-decoration-none shadow-sm h-100 d-flex flex-row align-items-center gap-3 bg-white hover-lift">
            <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: #fdf2f8; color: #db2777;">
                <i class="fa-solid fa-ticket fs-5"></i>
            </div>
            <div>
                <div class="fw-bold text-dark small">Mã giảm giá</div>
                <small class="text-muted" style="font-size: 0.75rem;">Khuyến mãi & voucher</small>
            </div>
        </a>
    </div>
</div>

<!-- ĐƠN HÀNG MỚI NHẤT CẦN DUYỆT -->
<div class="admin-table-card">
    <div class="d-flex justify-content-between align-items-center p-4 border-bottom bg-white">
        <div>
            <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-clipboard-list text-primary"></i> Đơn hàng gần đây cần xử lý
            </h5>
            <small class="text-secondary">Hiển thị các đơn đặt hàng mới nhất trên hệ thống</small>
        </div>
        <a href="orders.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
            Xem tất cả đơn <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
    </div>

    <?php if (empty($recentOrders)): ?>
        <div class="p-5 text-center text-muted">
            <i class="fa-solid fa-box-open fs-1 text-secondary opacity-50 mb-3 d-block"></i>
            Chưa có đơn hàng nào phát sinh!
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Số điện thoại</th>
                        <th>Tổng tiền</th>
                        <th>Phương thức</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $ord): ?>
                        <tr>
                            <td class="fw-bold text-primary">#VP-<?= $ord['id'] ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($ord['fullname']) ?></div>
                                <small class="text-muted text-truncate d-block" style="max-width: 200px; font-size: 0.78rem;">
                                    <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($ord['address']) ?>
                                </small>
                            </td>
                            <td><span class="fw-medium text-secondary"><?= htmlspecialchars($ord['phone']) ?></span></td>
                            <td class="text-danger fw-bold fs-6"><?= number_format($ord['total_money'], 0, ',', '.') ?> đ</td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">
                                    <?= htmlspecialchars($ord['payment_method']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($ord['status'] === 'Chờ xử lý'): ?>
                                    <span class="badge badge-soft-warning rounded-pill px-3 py-1"><i class="fa-solid fa-clock me-1"></i>Chờ xử lý</span>
                                <?php elseif ($ord['status'] === 'Đang giao hàng'): ?>
                                    <span class="badge badge-soft-primary rounded-pill px-3 py-1"><i class="fa-solid fa-truck-fast me-1"></i>Đang giao</span>
                                <?php elseif ($ord['status'] === 'Đã giao thành công'): ?>
                                    <span class="badge badge-soft-success rounded-pill px-3 py-1"><i class="fa-solid fa-check me-1"></i>Đã giao</span>
                                <?php else: ?>
                                    <span class="badge badge-soft-danger rounded-pill px-3 py-1"><i class="fa-solid fa-xmark me-1"></i>Đã hủy</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="orders.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">
                                    Xử lý <i class="fa-solid fa-arrow-right small ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
