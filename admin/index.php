<?php
$pageTitle = 'Dashboard Quản Trị - V-Phone';
require_once 'includes/header.php';

// 1. Tính các chỉ số KPI tổng quan
$stats = $pdo->query("SELECT
    COUNT(*) AS total_orders,
    COALESCE(SUM(CASE WHEN status = 'Đã giao thành công' THEN total_money ELSE 0 END), 0) AS total_revenue,
    COALESCE(SUM(CASE WHEN status = 'Chờ xử lý' THEN 1 ELSE 0 END), 0) AS pending_orders,
    (SELECT COUNT(*) FROM products) AS total_products,
    (SELECT COUNT(*) FROM users WHERE role = 0) AS total_users
    FROM orders")->fetch();

$totalRevenue = (float)$stats['total_revenue'];
$totalOrders = (int)$stats['total_orders'];
$pendingOrders = (int)$stats['pending_orders'];
$totalProducts = (int)$stats['total_products'];
$totalUsers = (int)$stats['total_users'];

// 2. Thống kê tỷ lệ trạng thái đơn hàng cho biểu đồ Doughnut
$statusStatsRaw = $pdo->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$orderStatusData = [
    'Đã giao thành công' => (int)($statusStatsRaw['Đã giao thành công'] ?? 0),
    'Chờ xử lý' => (int)(($statusStatsRaw['Chờ xử lý'] ?? 0) + ($statusStatsRaw['Đang xử lý'] ?? 0) + ($statusStatsRaw['Chờ thanh toán'] ?? 0)),
    'Đang giao hàng' => (int)($statusStatsRaw['Đang giao hàng'] ?? 0),
    'Đã hủy' => (int)($statusStatsRaw['Đã hủy'] ?? 0),
];

// 3. Chuẩn bị dữ liệu doanh thu & đơn hàng 7 ngày gần nhất cho biểu đồ Line/Bar
$chartDates = [];
$chartLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $dt = date('Y-m-d', strtotime("-$i days"));
    $chartDates[] = $dt;
    $chartLabels[] = date('d/m', strtotime($dt));
}

$dailyRevStmt = $pdo->query("SELECT DATE(created_at) as odate,
    SUM(CASE WHEN status = 'Đã giao thành công' THEN total_money ELSE 0 END) as daily_revenue,
    COUNT(*) as daily_orders
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)");
$dailyData = $dailyRevStmt->fetchAll(PDO::FETCH_ASSOC);

$dailyRevMap = [];
$dailyOrdMap = [];
foreach ($dailyData as $row) {
    $dailyRevMap[$row['odate']] = (float)$row['daily_revenue'];
    $dailyOrdMap[$row['odate']] = (int)$row['daily_orders'];
}

$chartRevenue = [];
$chartOrders = [];
foreach ($chartDates as $dt) {
    $chartRevenue[] = $dailyRevMap[$dt] ?? 0;
    $chartOrders[] = $dailyOrdMap[$dt] ?? 0;
}

// Nếu 7 ngày qua chưa có đơn hoàn tất nhưng hệ thống đã có đơn từ trước, lấy các mốc đơn thực tế để biểu đồ sinh động
if (array_sum($chartRevenue) == 0 && $totalRevenue > 0) {
    $histStmt = $pdo->query("SELECT DATE(created_at) as odate,
        SUM(CASE WHEN status = 'Đã giao thành công' THEN total_money ELSE 0 END) as daily_revenue,
        COUNT(*) as daily_orders
        FROM orders
        GROUP BY DATE(created_at)
        ORDER BY odate ASC
        LIMIT 7");
    $histData = $histStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($histData)) {
        $chartLabels = [];
        $chartRevenue = [];
        $chartOrders = [];
        foreach ($histData as $h) {
            $chartLabels[] = date('d/m', strtotime($h['odate']));
            $chartRevenue[] = (float)$h['daily_revenue'];
            $chartOrders[] = (int)$h['daily_orders'];
        }
    }
}

// 4. Lấy Top 5 sản phẩm bán chạy nhất
$topSelling = $pdo->query("SELECT od.product_name, p.image, p.price, p.sale_price, p.quantity as current_stock,
    SUM(od.quantity) as total_sold,
    SUM(od.total_price) as total_sales
    FROM order_details od
    LEFT JOIN products p ON od.product_id = p.id
    GROUP BY od.product_name, p.image, p.price, p.sale_price, p.quantity
    ORDER BY total_sold DESC
    LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// 5. Lấy danh sách sản phẩm cảnh báo hết hàng (<= 5 máy)
$lowStockProducts = $pdo->query("SELECT p.*, b.name as brand_name
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE p.quantity <= 5
    ORDER BY p.quantity ASC
    LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// 6. Lấy 5 đơn hàng mới nhất cần duyệt
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();
?>

<!-- TIÊU ĐỀ BẢNG ĐIỀU KHIỂN & HÀNH ĐỘNG NHANH -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h3 class="fw-black text-dark mb-0">Bảng Điều Khiển Quản Trị</h3>
            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2.5 py-1 rounded-pill small">
                Live 2026
            </span>
        </div>
        <p class="text-secondary small mb-0">
            Hôm nay là <strong><?= date('d/m/Y') ?></strong> • Hệ thống giám sát doanh thu, đơn hàng & kho vận V-Phone
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="warranties.php" class="btn btn-outline-primary rounded-pill fw-bold btn-sm px-3 shadow-none position-relative">
            <i class="fa-solid fa-shield-halved me-1.5"></i>Bảo hành
            <?php if (!empty($pendingWarrantiesCount)): ?>
                <span class="badge bg-danger rounded-pill ms-1"><?= $pendingWarrantiesCount ?></span>
            <?php endif; ?>
        </a>
        <a href="inventory.php" class="btn btn-outline-secondary rounded-pill fw-bold btn-sm px-3 shadow-none">
            <i class="fa-solid fa-boxes-stacked me-1.5 text-warning"></i>Kiểm kho tồn
        </a>
        <a href="products.php" class="btn btn-primary rounded-pill fw-bold btn-sm px-3 shadow-sm">
            <i class="fa-solid fa-plus me-1.5"></i>Thêm điện thoại mới
        </a>
    </div>
</div>

<?php if (!empty($pendingWarrantiesCount) && $pendingWarrantiesCount > 0): ?>
    <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3 p-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                <i class="fa-solid fa-shield-halved fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-0">Có <?= $pendingWarrantiesCount ?> yêu cầu bảo hành mới đang chờ tiếp nhận!</h6>
                <small class="text-secondary">Khách hàng đã gửi yêu cầu bảo hành từ website. Vui lòng tiếp nhận và phân công kỹ thuật viên giám định 32 bước.</small>
            </div>
        </div>
        <a href="warranties.php?status=Chờ+tiếp+nhận" class="btn btn-warning rounded-pill fw-bold btn-sm px-4 shadow-sm text-dark">
            <i class="fa-solid fa-screwdriver-wrench me-1"></i>Xử lý ngay
        </a>
    </div>
<?php endif; ?>

<!-- 4 THẺ THỐNG KÊ KPI HIỆN ĐẠI -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="admin-kpi-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Tổng doanh thu</span>
                    <h3 class="fw-black text-dark my-2"><?= number_format($totalRevenue, 0, ',', '.') ?> <span class="fs-6 fw-bold text-muted">đ</span></h3>
                    <span class="badge badge-soft-success rounded-pill px-2.5 py-1">
                        <i class="fa-solid fa-arrow-trend-up me-1"></i>Đơn hoàn thành
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
                        <a href="orders.php?status=Chờ xử lý" class="badge badge-soft-danger text-decoration-none rounded-pill px-2.5 py-1">
                            <i class="fa-solid fa-bell me-1"></i><?= $pendingOrders ?> đơn cần duyệt ngay
                        </a>
                    <?php else: ?>
                        <span class="badge badge-soft-success rounded-pill px-2.5 py-1">
                            <i class="fa-solid fa-check me-1"></i>Đã xử lý xong
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
                    <span class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Sản phẩm kho</span>
                    <h3 class="fw-black text-dark my-2"><?= $totalProducts ?> <span class="fs-6 fw-bold text-muted">mẫu</span></h3>
                    <?php if ($alertStock > 0): ?>
                        <a href="inventory.php?filter=low" class="badge badge-soft-danger text-decoration-none rounded-pill px-2.5 py-1">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i><?= $alertStock ?> mẫu sắp hết
                        </a>
                    <?php else: ?>
                        <span class="badge badge-soft-primary rounded-pill px-2.5 py-1">
                            <i class="fa-solid fa-boxes-stacked me-1"></i>Kho hàng ổn định
                        </span>
                    <?php endif; ?>
                </div>
                <div class="admin-kpi-icon-box" style="background: linear-gradient(135deg, #0066cc 0%, #0284c7 100%); color: #ffffff; box-shadow: 0 8px 16px rgba(0, 102, 204, 0.25);">
                    <i class="fa-solid fa-mobile-screen"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="admin-kpi-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Khách hàng</span>
                    <h3 class="fw-black text-dark my-2"><?= $totalUsers ?> <span class="fs-6 fw-bold text-muted">thành viên</span></h3>
                    <span class="badge badge-soft-secondary rounded-pill px-2.5 py-1">
                        <i class="fa-solid fa-users me-1"></i><?= $totalOrders ?> lượt mua hàng
                    </span>
                </div>
                <div class="admin-kpi-icon-box" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: #ffffff; box-shadow: 0 8px 16px rgba(139, 92, 246, 0.25);">
                    <i class="fa-solid fa-user-group"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- KHU VỰC BIỂU ĐỒ THỐNG KÊ (CHART.JS) -->
<div class="row g-4 mb-4">
    <!-- BIỂU ĐỒ DOANH THU & ĐƠN HÀNG THEO THỜI GIAN -->
    <div class="col-lg-8">
        <div class="card border-0 rounded-4 shadow-sm h-100 p-4 bg-white">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-chart-line text-primary"></i> Biểu đồ doanh thu & đơn hàng
                    </h5>
                    <small class="text-muted">Theo dõi diễn biến doanh số và số lượng đơn hàng hoàn tất</small>
                </div>
                <span class="badge bg-light text-primary border rounded-pill px-3 py-1.5 fw-bold small">
                    <i class="fa-regular fa-calendar me-1"></i>7 ngày gần nhất
                </span>
            </div>
            <div style="position: relative; height: 290px; width: 100%;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- BIỂU ĐỒ TRẠNG THÁI ĐƠN HÀNG (DOUGHNUT) -->
    <div class="col-lg-4">
        <div class="card border-0 rounded-4 shadow-sm h-100 p-4 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-info"></i> Tỷ lệ đơn hàng
                    </h5>
                    <small class="text-muted">Phân bổ trạng thái xử lý</small>
                </div>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small fw-bold">
                    Tổng: <?= $totalOrders ?>
                </span>
            </div>
            <div style="position: relative; height: 210px; width: 100%;">
                <canvas id="orderStatusChart"></canvas>
            </div>
            <div class="row g-2 mt-3 pt-2 border-top text-center" style="font-size: 0.78rem;">
                <div class="col-6">
                    <div class="p-2 rounded-3 bg-light">
                        <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background: #10b981;"></span>
                        <span class="text-muted">Đã giao:</span> <strong><?= $orderStatusData['Đã giao thành công'] ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded-3 bg-light">
                        <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background: #f59e0b;"></span>
                        <span class="text-muted">Chờ xử lý:</span> <strong><?= $orderStatusData['Chờ xử lý'] ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded-3 bg-light">
                        <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background: #0066cc;"></span>
                        <span class="text-muted">Đang giao:</span> <strong><?= $orderStatusData['Đang giao hàng'] ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded-3 bg-light">
                        <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background: #ef4444;"></span>
                        <span class="text-muted">Đã hủy:</span> <strong><?= $orderStatusData['Đã hủy'] ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TOP BÁN CHẠY VÀ CẢNH BÁO TỒN KHO -->
<div class="row g-4 mb-4">
    <!-- TOP 5 SẢN PHẨM BÁN CHẠY -->
    <div class="col-lg-6">
        <div class="card border-0 rounded-4 shadow-sm h-100 bg-white overflow-hidden">
            <div class="d-flex justify-content-between align-items-center p-3.5 px-4 border-bottom bg-white">
                <div>
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-fire text-danger"></i> Top Sản Phẩm Bán Chạy
                    </h5>
                    <small class="text-muted">Những dòng điện thoại được đặt mua nhiều nhất</small>
                </div>
                <a href="products.php" class="btn btn-light btn-sm rounded-pill border px-3 fw-bold small">
                    Kho hàng <i class="fa-solid fa-arrow-right small ms-1"></i>
                </a>
            </div>

            <div class="p-3">
                <?php if (empty($topSelling)): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="fa-solid fa-box-open fs-2 opacity-50 mb-2 d-block"></i>
                        Chưa có dữ liệu sản phẩm bán ra.
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($topSelling as $idx => $ts): ?>
                            <div class="d-flex align-items-center justify-content-between p-2.5 rounded-3 hover-bg-light border border-light">
                                <div class="d-flex align-items-center gap-3 min-w-0">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle fw-black flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.85rem; <?php
                                        if ($idx === 0) echo 'background: #fef3c7; color: #d97706;';
                                        elseif ($idx === 1) echo 'background: #f1f5f9; color: #64748b;';
                                        elseif ($idx === 2) echo 'background: #ffedd5; color: #c2410c;';
                                        else echo 'background: #f8fafc; color: #94a3b8;';
                                    ?>">
                                        <?= $idx + 1 ?>
                                    </div>
                                    <?php if (!empty($ts['image']) && file_exists(__DIR__ . '/../' . $ts['image'])): ?>
                                        <img src="../<?= htmlspecialchars($ts['image']) ?>" alt="Product" class="rounded-2 border flex-shrink-0" style="width: 44px; height: 44px; object-fit: contain;">
                                    <?php else: ?>
                                        <div class="rounded-2 bg-light border d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                            <i class="fa-solid fa-mobile-screen text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="min-w-0">
                                        <div class="fw-bold text-dark small text-truncate" style="max-width: 250px;">
                                            <?= htmlspecialchars($ts['product_name']) ?>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            Doanh số: <span class="fw-bold text-danger"><?= number_format($ts['total_sales'], 0, ',', '.') ?> đ</span>
                                        </small>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold rounded-pill px-2.5 py-1">
                                        <?= $ts['total_sold'] ?> đã bán
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- CẢNH BÁO TỒN KHO THẤP -->
    <div class="col-lg-6">
        <div class="card border-0 rounded-4 shadow-sm h-100 bg-white overflow-hidden">
            <div class="d-flex justify-content-between align-items-center p-3.5 px-4 border-bottom bg-white">
                <div>
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-warning"></i> Cảnh Báo Kho Tồn Sắp Hết
                    </h5>
                    <small class="text-muted">Các sản phẩm có lượng tồn kho ≤ 5 máy cần nhập thêm</small>
                </div>
                <a href="inventory.php?filter=low" class="btn btn-warning btn-sm rounded-pill fw-bold text-dark px-3 small">
                    Nhập kho <i class="fa-solid fa-arrow-right small ms-1"></i>
                </a>
            </div>

            <div class="p-3">
                <?php if (empty($lowStockProducts)): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="fa-regular fa-circle-check fs-2 text-success opacity-75 mb-2 d-block"></i>
                        Tất cả sản phẩm hiện có mức tồn kho dồi dào!
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($lowStockProducts as $lsp): ?>
                            <div class="d-flex align-items-center justify-content-between p-2.5 rounded-3 hover-bg-light border border-light">
                                <div class="d-flex align-items-center gap-3 min-w-0">
                                    <?php if (!empty($lsp['image']) && file_exists(__DIR__ . '/../' . $lsp['image'])): ?>
                                        <img src="../<?= htmlspecialchars($lsp['image']) ?>" alt="Product" class="rounded-2 border flex-shrink-0" style="width: 44px; height: 44px; object-fit: contain;">
                                    <?php else: ?>
                                        <div class="rounded-2 bg-light border d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                            <i class="fa-solid fa-mobile-screen text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="min-w-0">
                                        <div class="fw-bold text-dark small text-truncate" style="max-width: 250px;">
                                            <?= htmlspecialchars($lsp['name']) ?>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            <?= htmlspecialchars($lsp['brand_name'] ?? 'Chưa phân hãng') ?> • <?= number_format($lsp['sale_price'] > 0 ? $lsp['sale_price'] : $lsp['price'], 0, ',', '.') ?> đ
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <?php if ($lsp['quantity'] == 0): ?>
                                        <span class="badge bg-danger rounded-pill px-2.5 py-1">Hết hàng (0)</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1">Còn <?= $lsp['quantity'] ?> máy</span>
                                    <?php endif; ?>
                                    <a href="inventory.php" class="btn btn-sm btn-outline-primary rounded-circle" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Nhập hàng">
                                        <i class="fa-solid fa-plus small"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
            <small class="text-secondary">Hiển thị các đơn đặt hàng mới nhất trên hệ thống V-Phone</small>
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
                                <small class="text-muted text-truncate d-block" style="max-width: 220px; font-size: 0.78rem;">
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
                                <?= renderOrderStatusBadge($ord['status'], 'px-3 py-1') ?>
                            </td>
                            <td class="text-end">
                                <a href="orders.php?status=<?= urlencode($ord['status']) ?>" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">
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

<!-- KHỞI TẠO BIỂU ĐỒ CHART.JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Biểu đồ đường & cột Doanh thu + Lượng đơn
    const revCtx = document.getElementById('revenueChart');
    if (revCtx) {
        const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
        const revData = <?= json_encode($chartRevenue) ?>;
        const ordData = <?= json_encode($chartOrders) ?>;

        new Chart(revCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        type: 'line',
                        label: 'Doanh thu (VNĐ)',
                        data: revData,
                        borderColor: '#0066cc',
                        backgroundColor: 'rgba(0, 102, 204, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#0066cc',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        fill: true,
                        tension: 0.35,
                        yAxisID: 'y'
                    },
                    {
                        type: 'bar',
                        label: 'Số đơn hàng',
                        data: ordData,
                        backgroundColor: 'rgba(56, 189, 248, 0.55)',
                        borderColor: '#38bdf8',
                        borderWidth: 1.5,
                        borderRadius: 8,
                        barThickness: 24,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto', size: 12, weight: 600 },
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(11, 19, 41, 0.9)',
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                if (context.dataset.yAxisID === 'y') {
                                    return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.raw) + ' đ';
                                } else {
                                    return 'Số đơn: ' + context.raw + ' đơn';
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: { color: 'rgba(226, 232, 240, 0.6)' },
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                                if (value >= 1000) return (value / 1000).toFixed(0) + 'k';
                                return value;
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // 2. Biểu đồ Doughnut Tỷ lệ trạng thái đơn hàng
    const statusCtx = document.getElementById('orderStatusChart');
    if (statusCtx) {
        const statusData = <?= json_encode(array_values($orderStatusData)) ?>;
        const total = statusData.reduce((a, b) => a + b, 0);

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Đã giao thành công', 'Chờ xử lý', 'Đang giao hàng', 'Đã hủy'],
                datasets: [{
                    data: total === 0 ? [1, 0, 0, 0] : statusData,
                    backgroundColor: ['#10b981', '#f59e0b', '#0066cc', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(11, 19, 41, 0.9)',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const val = context.raw;
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return ' ' + context.label + ': ' + val + ' đơn (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
