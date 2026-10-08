<?php
$pageTitle = 'Dashboard Quản Trị - V-Phone';
require_once 'app/views/includes/header.php';

// Thống kê số liệu
$totalRevenue = $pdo->query("SELECT SUM(total_money) FROM orders WHERE status = 'Đã giao thành công'")->fetchColumn() ?: 0;
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Chờ xử lý'")->fetchColumn() ?: 0;
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0;
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 0")->fetchColumn() ?: 0;

// Lấy 5 đơn hàng mới nhất cần duyệt
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Bảng Điều Khiển Quản Trị</h3>
        <p class="text-secondary small mb-0">Theo dõi doanh thu, tình trạng đơn hàng và quản lý sản phẩm</p>
    </div>
    <a href="products.php" class="btn btn-primary rounded-pill fw-bold shadow-sm">
        <i class="fa-solid fa-plus me-1"></i>Thêm điện thoại mới
    </a>
</div>

<!-- 4 THẺ THỐNG KÊ KPI RỰC RỠ -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="bg-white p-3 rounded-4 shadow-sm border border-primary border-opacity-25 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-secondary fw-semibold text-uppercase">Doanh Thu Đã Thu</small>
                    <h4 class="fw-bold text-success my-1"><?= number_format($totalRevenue, 0, ',', '.') ?> đ</h4>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-3"><i class="fa-solid fa-money-bill-wave fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="bg-white p-3 rounded-4 shadow-sm border border-primary border-opacity-25 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-secondary fw-semibold text-uppercase">Đơn Hàng Mới</small>
                    <h4 class="fw-bold text-danger my-1"><?= $pendingOrders ?> đơn chờ duyệt</h4>
                </div>
                <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-3"><i class="fa-solid fa-clock fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="bg-white p-3 rounded-4 shadow-sm border border-primary border-opacity-25 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-secondary fw-semibold text-uppercase">Sản Phẩm Trong Kho</small>
                    <h4 class="fw-bold text-primary my-1"><?= $totalProducts ?> mẫu máy</h4>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3"><i class="fa-solid fa-mobile-screen fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="bg-white p-3 rounded-4 shadow-sm border border-primary border-opacity-25 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-secondary fw-semibold text-uppercase">Khách Hàng Đăng Ký</small>
                    <h4 class="fw-bold text-info my-1"><?= $totalUsers ?> thành viên</h4>
                </div>
                <div class="bg-info bg-opacity-10 text-info p-3 rounded-3"><i class="fa-solid fa-users fs-4"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- ĐƠN HÀNG MỚI NHẤT CẦN DUYỆT GẤP -->
<div class="card border-0 rounded-4 shadow-sm bg-white p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-bell text-warning me-2"></i>Đơn Hàng Gần Đây Cần Xử Lý</h5>
        <a href="orders.php" class="text-primary fw-bold text-decoration-none small">Xem tất cả đơn &rarr;</a>
    </div>

    <?php if (empty($recentOrders)): ?>
        <div class="p-4 text-center text-muted">Chưa có đơn hàng nào phát sinh!</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Khách Hàng</th>
                        <th>Số Điện Thoại</th>
                        <th>Tổng Tiền</th>
                        <th>Phương Thức</th>
                        <th>Trạng Thái</th>
                        <th>Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $ord): ?>
                        <tr>
                            <td class="fw-bold text-primary">#VP-<?= $ord['id'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($ord['fullname']) ?></td>
                            <td><?= htmlspecialchars($ord['phone']) ?></td>
                            <td class="text-danger fw-bold"><?= number_format($ord['total_money'], 0, ',', '.') ?> đ</td>
                            <td><span class="badge bg-light text-dark border"><?= $ord['payment_method'] ?></span></td>
                            <td>
                                <?php if ($ord['status'] === 'Chờ xử lý'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>Chờ xử lý</span>
                                <?php elseif ($ord['status'] === 'Đang giao hàng'): ?>
                                    <span class="badge bg-info text-dark"><i class="fa-solid fa-truck-fast me-1"></i>Đang giao</span>
                                <?php elseif ($ord['status'] === 'Đã giao thành công'): ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Đã giao</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Đã hủy</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="orders.php" class="btn btn-primary btn-sm rounded-pill px-3">Xử lý ngay</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'app/views/includes/footer.php'; ?>
