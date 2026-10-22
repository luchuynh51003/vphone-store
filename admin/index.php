<?php
// Xử lý request AJAX lấy dữ liệu biểu đồ hoặc Xuất CSV trước khi xuất HTML
if (isset($_GET['ajax']) || isset($_GET['export_chart_csv'])) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../app/views/includes/functions.php';
    require_once __DIR__ . '/includes/permissions.php';
    require_once __DIR__ . '/includes/chart_helper.php';

    // Đảm bảo phiên đăng nhập và quyền hạn quản trị
    if (!isset($_SESSION['user']) || (int)($_SESSION['user']['role'] ?? 0) < 1) {
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        } else {
            header('Location: ../index.php?show_login=1');
        }
        exit;
    }

    $chartData = getDashboardChartData($pdo, $_GET);

    if (isset($_GET['ajax']) && $_GET['ajax'] === 'chart_data') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($chartData, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (isset($_GET['export_chart_csv'])) {
        $cleanTitle = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $chartData['title']);
        $filename = 'bao-cao-don-hang-' . ($cleanTitle ?: 'vphone') . '-' . date('Ymd-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM cho Microsoft Excel mở tiếng Việt không bị lỗi font
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['BÁO CÁO DOANH THU & ĐƠN HÀNG V-PHONE']);
        fputcsv($out, ['Kỳ lọc:', $chartData['title']]);
        fputcsv($out, ['Từ ngày:', $chartData['start_date'], 'Đến ngày:', $chartData['end_date']]);
        fputcsv($out, ['Tổng doanh thu:', $chartData['kpi']['total_revenue_fmt'], 'Tổng đơn:', $chartData['kpi']['total_orders'], 'Hoàn tất:', $chartData['kpi']['completed_orders']]);
        fputcsv($out, []); // Dòng trống
        fputcsv($out, ['STT', 'Mã đơn hàng', 'Thời gian đặt', 'Khách hàng', 'Số điện thoại', 'Sản phẩm đã mua', 'Tổng tiền (VNĐ)', 'Phương thức', 'Trạng thái']);

        $stt = 1;
        foreach ($chartData['orders_list'] as $ord) {
            fputcsv($out, [
                $stt++,
                $ord['order_code'],
                $ord['created_at_fmt'],
                $ord['fullname'],
                $ord['phone'],
                $ord['products_summary'],
                $ord['total_money'],
                $ord['payment_method'],
                $ord['status']
            ]);
        }
        fclose($out);
        exit;
    }
}

$pageTitle = 'Dashboard Quản Trị - V-Phone';
require_once 'includes/header.php';
require_once __DIR__ . '/includes/chart_helper.php';

// 1. Tính các chỉ số KPI tổng quan toàn hệ thống
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

// 2. Khởi tạo dữ liệu biểu đồ và phân bổ trạng thái theo bộ lọc thời gian
$initialChartData = getDashboardChartData($pdo, $_GET);
$orderStatusData = $initialChartData['status_distribution'];
$chartLabels = $initialChartData['labels'];
$chartRevenue = $initialChartData['revenue'];
$chartOrders = $initialChartData['orders'];

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
        <a href="warranties.php" class="btn btn-outline-primary rounded-pill fw-bold btn-sm px-3 shadow-none position-relative d-inline-flex align-items-center">
            <i class="fa-solid fa-shield-halved me-2"></i><span>Bảo hành</span>
            <?php if (!empty($pendingWarrantiesCount)): ?>
                <span class="badge bg-danger rounded-pill ms-2"><?= $pendingWarrantiesCount ?></span>
            <?php endif; ?>
        </a>
        <a href="inventory.php" class="btn btn-outline-secondary rounded-pill fw-bold btn-sm px-3 shadow-none d-inline-flex align-items-center">
            <i class="fa-solid fa-boxes-stacked me-2 text-warning"></i><span>Kiểm kho tồn</span>
        </a>
        <a href="products.php" class="btn btn-primary rounded-pill fw-bold btn-sm px-3 shadow-sm d-inline-flex align-items-center">
            <i class="fa-solid fa-plus me-2"></i><span>Thêm điện thoại mới</span>
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
                    <h3 class="fw-black text-dark my-2"><?= number_format($totalRevenue, 0, ',', '.') ?> <span class="fs-6 fw-bold text-muted">VNĐ</span></h3>
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
        <div class="card border-0 rounded-4 shadow-sm h-100 p-4 bg-white chart-dashboard-card position-relative" id="chartDashboardCard">
            <!-- HEADER BIỂU ĐỒ & THANH CÔNG CỤ BỘ LỌC -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-chart-line text-primary"></i> Biểu đồ doanh thu & đơn hàng
                    </h5>
                    <small class="text-muted">Theo dõi diễn biến doanh số và số lượng đơn hàng hoàn tất</small>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- DROPDOWN KHOẢNG THỜI GIAN NHANH -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border rounded-pill px-3 py-1.5 fw-bold d-flex align-items-center gap-2 dropdown-toggle shadow-sm text-primary" type="button" id="chartPresetDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-regular fa-calendar text-primary"></i>
                            <span id="chartActiveFilterTitle"><?= htmlspecialchars($initialChartData['title']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2" aria-labelledby="chartPresetDropdownBtn" style="min-width: 230px; font-size: 0.88rem; z-index: 1050;">
                            <li class="dropdown-header text-uppercase text-muted fw-bold small py-1 px-3">Khoảng thời gian nhanh</li>
                            <li><a class="dropdown-item rounded-3 py-1.5 px-3 d-flex align-items-center justify-content-between chart-preset-item <?= ($initialChartData['preset'] === '7days') ? 'active fw-bold' : '' ?>" href="javascript:void(0)" data-preset="7days" onclick="applyPresetFilter('7days', '7 ngày gần nhất')"><span><i class="fa-regular fa-calendar-days me-2 text-primary"></i>7 ngày gần nhất</span> <span class="badge bg-primary bg-opacity-10 text-primary small">Mặc định</span></a></li>
                            <li><a class="dropdown-item rounded-3 py-1.5 px-3 chart-preset-item <?= ($initialChartData['preset'] === '30days') ? 'active fw-bold' : '' ?>" href="javascript:void(0)" data-preset="30days" onclick="applyPresetFilter('30days', '30 ngày gần nhất')"><i class="fa-solid fa-calendar-check me-2 text-info"></i>30 ngày gần nhất</a></li>
                            <li><a class="dropdown-item rounded-3 py-1.5 px-3 chart-preset-item <?= ($initialChartData['preset'] === 'today') ? 'active fw-bold' : '' ?>" href="javascript:void(0)" data-preset="today" onclick="applyPresetFilter('today', 'Hôm nay (Theo giờ)')"><i class="fa-regular fa-clock me-2 text-warning"></i>Hôm nay (Theo giờ)</a></li>
                            <li><a class="dropdown-item rounded-3 py-1.5 px-3 chart-preset-item <?= ($initialChartData['preset'] === 'this_month') ? 'active fw-bold' : '' ?>" href="javascript:void(0)" data-preset="this_month" onclick="applyPresetFilter('this_month', 'Tháng này')"><i class="fa-solid fa-calendar me-2 text-success"></i>Tháng này (<?= date('m/Y') ?>)</a></li>
                            <li><a class="dropdown-item rounded-3 py-1.5 px-3 chart-preset-item <?= ($initialChartData['preset'] === 'last_month') ? 'active fw-bold' : '' ?>" href="javascript:void(0)" data-preset="last_month" onclick="applyPresetFilter('last_month', 'Tháng trước')"><i class="fa-solid fa-arrow-rotate-left me-2 text-secondary"></i>Tháng trước</a></li>
                            <li><a class="dropdown-item rounded-3 py-1.5 px-3 chart-preset-item <?= ($initialChartData['preset'] === 'this_year') ? 'active fw-bold' : '' ?>" href="javascript:void(0)" data-preset="this_year" onclick="applyPresetFilter('this_year', 'Cả năm nay')"><i class="fa-solid fa-chart-column me-2 text-danger"></i>Cả năm nay (<?= date('Y') ?>)</a></li>
                            <li><hr class="dropdown-divider my-2"></li>
                            <li class="dropdown-header text-uppercase text-muted fw-bold small py-1 px-3">Tùy chỉnh ngày / năm / tháng</li>
                            <li><a class="dropdown-item rounded-3 py-1.5 px-3 fw-semibold text-primary" href="javascript:void(0)" onclick="openCustomFilterTab('month')"><i class="fa-regular fa-calendar-plus me-2"></i>Chọn Ngày / Năm / Tháng...</a></li>
                        </ul>
                    </div>

                    <!-- NÚT MỞ BỘ LỌC TÙY CHỌN NÂNG CAO -->
                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 fw-bold d-flex align-items-center gap-1.5" type="button" data-bs-toggle="collapse" data-bs-target="#chartFilterCustomPanel" aria-expanded="false" id="btnToggleCustomFilter" title="Mở bảng chọn chi tiết theo ngày, năm hoặc tháng">
                        <i class="fa-solid fa-sliders"></i>
                        <span class="d-none d-sm-inline">Chọn ngày/tháng</span>
                    </button>

                    <!-- NÚT MỞ CHI TIẾT ĐƠN HÀNG TRONG KỲ -->
                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 fw-bold d-flex align-items-center gap-1.5" type="button" data-bs-toggle="collapse" data-bs-target="#chartOrdersDetailSection" aria-expanded="false" id="btnToggleOrderDetails" title="Xem danh sách chi tiết các đơn hàng trong kỳ này">
                        <i class="fa-solid fa-list-ul"></i>
                        <span>Xem chi tiết (<strong id="filterOrdersBadgeCount"><?= count($initialChartData['orders_list']) ?></strong>)</span>
                    </button>

                    <!-- NÚT XUẤT CSV EXCEL -->
                    <button class="btn btn-sm btn-outline-success rounded-pill px-2.5 py-1.5 fw-bold" type="button" id="btnExportChartCsv" onclick="exportCurrentChartCsv()" title="Tải xuống file Excel/CSV danh sách đơn kỳ này">
                        <i class="fa-solid fa-file-excel"></i>
                    </button>
                </div>
            </div>

            <!-- BẢNG BỘ LỌC TÙY CHỌN NGÀY / NĂM / THÁNG (COLLAPSIBLE) -->
            <div class="collapse mb-3" id="chartFilterCustomPanel">
                <div class="p-3 rounded-4 bg-light border filter-panel-box">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom">
                        <ul class="nav nav-pills gap-1" id="filterModeTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active py-1 px-3 rounded-pill fw-bold small" id="tab-month-btn" data-bs-toggle="pill" data-bs-target="#panel-by-month" type="button" role="tab">
                                    <i class="fa-regular fa-calendar-days me-1"></i>Theo Tháng
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-1 px-3 rounded-pill fw-bold small" id="tab-year-btn" data-bs-toggle="pill" data-bs-target="#panel-by-year" type="button" role="tab">
                                    <i class="fa-solid fa-calendar me-1"></i>Theo Năm
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-1 px-3 rounded-pill fw-bold small" id="tab-day-btn" data-bs-toggle="pill" data-bs-target="#panel-by-day" type="button" role="tab">
                                    <i class="fa-regular fa-clock me-1"></i>Theo Ngày cụ thể
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-1 px-3 rounded-pill fw-bold small" id="tab-range-btn" data-bs-toggle="pill" data-bs-target="#panel-by-range" type="button" role="tab">
                                    <i class="fa-solid fa-arrows-left-right me-1"></i>Khoảng ngày
                                </button>
                            </li>
                        </ul>
                        <button class="btn btn-sm btn-link text-muted p-0 text-decoration-none small" type="button" data-bs-toggle="collapse" data-bs-target="#chartFilterCustomPanel">
                            <i class="fa-solid fa-xmark me-1"></i>Đóng
                        </button>
                    </div>

                    <div class="tab-content pt-2" id="filterModeTabsContent">
                        <!-- TAB 1: THEO THÁNG & NĂM -->
                        <div class="tab-pane fade show active" id="panel-by-month" role="tabpanel">
                            <form id="formFilterMonth" onsubmit="handleFilterSubmit(event, 'by_month')" class="row g-2 align-items-end">
                                <div class="col-sm-5 col-6">
                                    <label class="form-label small fw-bold text-muted mb-1">Chọn Tháng</label>
                                    <select class="form-select form-select-sm rounded-3 shadow-none fw-semibold" id="filterMonthSelect">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= $m ?>" <?= ($m == (int)date('m')) ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-sm-4 col-6">
                                    <label class="form-label small fw-bold text-muted mb-1">Chọn Năm</label>
                                    <select class="form-select form-select-sm rounded-3 shadow-none fw-semibold" id="filterMonthYearSelect">
                                        <?php $curY = (int)date('Y'); for ($y = $curY; $y >= 2023; $y--): ?>
                                            <option value="<?= $y ?>" <?= ($y == $curY) ? 'selected' : '' ?>>Năm <?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-sm-3 col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-bold flex-fill py-1.5">
                                        <i class="fa-solid fa-filter me-1"></i>Xem tháng
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- TAB 2: THEO NĂM -->
                        <div class="tab-pane fade" id="panel-by-year" role="tabpanel">
                            <form id="formFilterYear" onsubmit="handleFilterSubmit(event, 'by_year')" class="row g-2 align-items-end">
                                <div class="col-sm-8 col-7">
                                    <label class="form-label small fw-bold text-muted mb-1">Chọn Năm cần xem (12 tháng)</label>
                                    <select class="form-select form-select-sm rounded-3 shadow-none fw-semibold" id="filterYearSelect">
                                        <?php for ($y = $curY; $y >= 2023; $y--): ?>
                                            <option value="<?= $y ?>" <?= ($y == $curY) ? 'selected' : '' ?>>Năm <?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-sm-4 col-5">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-bold w-100 py-1.5">
                                        <i class="fa-solid fa-filter me-1"></i>Xem năm
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- TAB 3: THEO NGÀY CỤ THỂ -->
                        <div class="tab-pane fade" id="panel-by-day" role="tabpanel">
                            <form id="formFilterDay" onsubmit="handleFilterSubmit(event, 'by_day')" class="row g-2 align-items-end">
                                <div class="col-sm-8 col-7">
                                    <label class="form-label small fw-bold text-muted mb-1">Chọn Ngày (Xem diễn biến 24 giờ)</label>
                                    <input type="date" class="form-control form-control-sm rounded-3 shadow-none fw-semibold" id="filterDayInput" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-sm-4 col-5">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-bold w-100 py-1.5">
                                        <i class="fa-solid fa-filter me-1"></i>Xem ngày
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- TAB 4: KHOẢNG NGÀY TÙY CHỌN -->
                        <div class="tab-pane fade" id="panel-by-range" role="tabpanel">
                            <form id="formFilterRange" onsubmit="handleFilterSubmit(event, 'custom')" class="row g-2 align-items-end">
                                <div class="col-sm-4 col-6">
                                    <label class="form-label small fw-bold text-muted mb-1">Từ ngày</label>
                                    <input type="date" class="form-control form-control-sm rounded-3 shadow-none fw-semibold" id="filterRangeStart" value="<?= date('Y-m-d', strtotime('-6 days')) ?>">
                                </div>
                                <div class="col-sm-4 col-6">
                                    <label class="form-label small fw-bold text-muted mb-1">Đến ngày</label>
                                    <input type="date" class="form-control form-control-sm rounded-3 shadow-none fw-semibold" id="filterRangeEnd" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-sm-4 col-12">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-bold w-100 py-1.5">
                                        <i class="fa-solid fa-filter me-1"></i>Lọc khoảng ngày
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- THANH THỐNG KÊ NHANH CHO KỲ ĐƯỢC CHỌN (MINI KPI STRIP) -->
            <div class="row g-2 mb-3" id="chartPeriodKpiRow">
                <div class="col-6 col-md-3">
                    <div class="p-2.5 px-3 rounded-3 border bg-light h-100 d-flex flex-column justify-content-center chart-kpi-box">
                        <span class="text-muted small d-block mb-1" style="font-size: 0.76rem;">
                            <i class="fa-solid fa-sack-dollar text-success me-1"></i>Doanh thu kỳ này
                        </span>
                        <h6 class="fw-black text-primary mb-0 text-truncate" id="kpiPeriodRevenue" style="font-size: 0.98rem; letter-spacing: -0.3px;">
                            <?= $initialChartData['kpi']['total_revenue_fmt'] ?>
                        </h6>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2.5 px-3 rounded-3 border bg-light h-100 d-flex flex-column justify-content-center chart-kpi-box">
                        <span class="text-muted small d-block mb-1" style="font-size: 0.76rem;">
                            <i class="fa-solid fa-cart-shopping text-info me-1"></i>Đơn hàng / Hoàn tất
                        </span>
                        <h6 class="fw-bold text-dark mb-0 text-truncate" id="kpiPeriodOrders" style="font-size: 0.92rem;">
                            <?= $initialChartData['kpi']['completed_orders'] ?> / <?= $initialChartData['kpi']['total_orders'] ?>
                            <small class="text-success fw-semibold ms-1">(<?= $initialChartData['kpi']['completed_rate'] ?>%)</small>
                        </h6>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2.5 px-3 rounded-3 border bg-light h-100 d-flex flex-column justify-content-center chart-kpi-box">
                        <span class="text-muted small d-block mb-1" style="font-size: 0.76rem;">
                            <i class="fa-solid fa-calculator text-warning me-1"></i>Giá trị TB/đơn (AOV)
                        </span>
                        <h6 class="fw-bold text-dark mb-0 text-truncate" id="kpiPeriodAov" style="font-size: 0.92rem;">
                            <?= $initialChartData['kpi']['avg_order_value_fmt'] ?>
                        </h6>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2.5 px-3 rounded-3 border bg-light h-100 d-flex flex-column justify-content-center chart-kpi-box">
                        <span class="text-muted small d-block mb-1" style="font-size: 0.76rem;">
                            <i class="fa-solid fa-chart-line-up text-danger me-1"></i>Mốc cao nhất
                        </span>
                        <h6 class="fw-bold text-dark mb-0 text-truncate" id="kpiPeriodPeak" style="font-size: 0.92rem;" title="<?= $initialChartData['kpi']['peak_label'] ?>: <?= $initialChartData['kpi']['peak_revenue_fmt'] ?>">
                            <?= $initialChartData['kpi']['peak_label'] ?>: <span class="text-danger fw-bold"><?= $initialChartData['kpi']['peak_revenue_fmt'] ?></span>
                        </h6>
                    </div>
                </div>
            </div>

            <!-- CANVAS BIỂU ĐỒ & OVERLAY TẢI DỮ LIỆU -->
            <div class="position-relative" style="min-height: 290px;">
                <div id="chartLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-none flex-column align-items-center justify-content-center rounded-3" style="background: rgba(255, 255, 255, 0.78); backdrop-filter: blur(2px); z-index: 20;">
                    <div class="spinner-border text-primary mb-2" role="status" style="width: 2rem; height: 2rem;">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <small class="fw-bold text-primary">Đang đồng bộ dữ liệu thời gian...</small>
                </div>
                <div style="position: relative; height: 290px; width: 100%;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- DANH SÁCH CHI TIẾT CÁC ĐƠN HÀNG TRONG KỲ (COLLAPSIBLE) -->
            <div class="collapse mt-4 pt-3 border-top" id="chartOrdersDetailSection">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="fa-solid fa-table-list text-primary"></i>
                            Danh sách chi tiết đơn hàng trong kỳ
                            <span class="badge bg-primary rounded-pill small" id="detailsCountBadge"><?= count($initialChartData['orders_list']) ?> đơn</span>
                        </h6>
                        <small class="text-muted" id="detailsActiveFilterSub"><?= htmlspecialchars($initialChartData['title']) ?></small>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Nút hủy lọc điểm nếu đang click điểm biểu đồ -->
                        <div id="chartPointActiveFilterTag" class="d-none align-items-center gap-1 bg-primary bg-opacity-10 text-primary px-2.5 py-1 rounded-pill small fw-bold">
                            <span>Mốc: <strong id="pointTagValue"></strong></span>
                            <button type="button" class="btn btn-sm btn-link p-0 text-primary ms-1" onclick="clearChartPointFilter()" title="Hủy lọc mốc này"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="input-group input-group-sm" style="width: 220px;">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="orderDetailSearchInput" class="form-control bg-light border-start-0" placeholder="Tìm tên, mã, sđt..." onkeyup="filterPeriodOrdersTable()">
                        </div>
                        <button class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold" type="button" onclick="exportCurrentChartCsv()">
                            <i class="fa-solid fa-file-excel me-1"></i>Xuất CSV
                        </button>
                    </div>
                </div>

                <!-- Bảng chi tiết đơn hàng -->
                <div class="table-responsive rounded-3 border" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 small" id="periodOrdersTable">
                        <thead class="table-light sticky-top" style="z-index: 5;">
                            <tr>
                                <th class="ps-3 py-2.5 text-secondary fw-bold" style="width: 95px;">Mã đơn</th>
                                <th class="py-2.5 text-secondary fw-bold" style="width: 125px;">Thời gian</th>
                                <th class="py-2.5 text-secondary fw-bold">Khách hàng</th>
                                <th class="py-2.5 text-secondary fw-bold" style="min-width: 200px;">Sản phẩm đặt mua</th>
                                <th class="py-2.5 text-secondary fw-bold text-end" style="width: 120px;">Tổng tiền</th>
                                <th class="py-2.5 text-secondary fw-bold text-center" style="width: 105px;">Phương thức</th>
                                <th class="py-2.5 text-secondary fw-bold text-center" style="width: 125px;">Trạng thái</th>
                                <th class="pe-3 py-2.5 text-secondary fw-bold text-center" style="width: 80px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="periodOrdersTableBody">
                            <?php if (empty($initialChartData['orders_list'])): ?>
                                <tr id="periodOrdersEmptyRow">
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fa-regular fa-folder-open fa-2x mb-2 d-block text-secondary opacity-50"></i>
                                        Không có đơn hàng nào trong khoảng thời gian này
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($initialChartData['orders_list'] as $ord): ?>
                                    <tr class="period-order-row" data-date="<?= $ord['date_key'] ?>" data-hour="<?= $ord['hour_key'] ?>" data-month="<?= $ord['month_key'] ?>" data-search="<?= strtolower(htmlspecialchars($ord['order_code'] . ' ' . $ord['fullname'] . ' ' . $ord['phone'] . ' ' . $ord['products_summary'])) ?>">
                                        <td class="ps-3 fw-bold text-primary"><?= htmlspecialchars($ord['order_code']) ?></td>
                                        <td class="text-secondary"><?= $ord['created_at_fmt'] ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($ord['fullname']) ?></div>
                                            <small class="text-muted"><i class="fa-solid fa-phone me-1 small"></i><?= htmlspecialchars($ord['phone']) ?></small>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($ord['products_summary']) ?>">
                                                <?= htmlspecialchars($ord['products_summary']) ?>
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold text-dark"><?= $ord['total_money_fmt'] ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1"><?= htmlspecialchars($ord['payment_method']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $ord['badge_class'] ?> rounded-pill px-2.5 py-1.5"><?= htmlspecialchars($ord['status']) ?></span>
                                        </td>
                                        <td class="pe-3 text-center">
                                            <a href="orders.php?status=<?= urlencode($ord['status']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0.5 fw-bold" title="Xem chi tiết đơn này trong Quản lý đơn hàng">
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- BIỂU ĐỒ TRẠNG THÁI ĐƠN HÀNG (DOUGHNUT) -->
    <div class="col-lg-4">
        <div class="card border-0 rounded-4 shadow-sm h-100 p-4 bg-white chart-dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-info"></i> Tỷ lệ đơn hàng
                    </h5>
                    <small class="text-muted">Phân bổ trạng thái xử lý trong kỳ</small>
                </div>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small fw-bold">
                    Tổng: <span id="doughnutTotalOrdersCount"><?= $initialChartData['kpi']['total_orders'] ?></span>
                </span>
            </div>
            <div style="position: relative; height: 210px; width: 100%;">
                <canvas id="orderStatusChart"></canvas>
            </div>
            <div class="row g-2 mt-3 pt-2 border-top text-center" style="font-size: 0.78rem;">
                <div class="col-6">
                    <div class="p-2 rounded-3 bg-light chart-kpi-box">
                        <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background: #10b981;"></span>
                        <span class="text-muted">Đã giao:</span> <strong id="doughnutCountCompleted" class="text-dark"><?= $orderStatusData['Đã giao thành công'] ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded-3 bg-light chart-kpi-box">
                        <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background: #f59e0b;"></span>
                        <span class="text-muted">Chờ xử lý:</span> <strong id="doughnutCountPending" class="text-dark"><?= $orderStatusData['Chờ xử lý'] ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded-3 bg-light chart-kpi-box">
                        <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background: #0066cc;"></span>
                        <span class="text-muted">Đang giao:</span> <strong id="doughnutCountShipping" class="text-dark"><?= $orderStatusData['Đang giao hàng'] ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded-3 bg-light chart-kpi-box">
                        <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background: #ef4444;"></span>
                        <span class="text-muted">Đã hủy:</span> <strong id="doughnutCountCanceled" class="text-dark"><?= $orderStatusData['Đã hủy'] ?></strong>
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
                                            Doanh số: <span class="fw-bold text-danger"><?= number_format($ts['total_sales'], 0, ',', '.') ?> VNĐ</span>
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
                                            <?= htmlspecialchars($lsp['brand_name'] ?? 'Chưa phân hãng') ?> • <?= number_format($lsp['sale_price'] > 0 ? $lsp['sale_price'] : $lsp['price'], 0, ',', '.') ?> VNĐ
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
                            <td class="text-danger fw-bold fs-6"><?= number_format($ord['total_money'], 0, ',', '.') ?> VNĐ</td>
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

<!-- CSS STYLING CHO BỘ LỌC BIỂU ĐỒ & BẢNG CHI TIẾT -->
<style>
.chart-kpi-box {
    transition: all 0.2s ease-in-out;
}
.chart-kpi-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
#periodOrdersTable tbody tr {
    transition: background-color 0.15s ease;
}
[data-bs-theme="dark"] .filter-panel-box {
    background: #1e293b !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .filter-panel-box .form-select,
[data-bs-theme="dark"] .filter-panel-box .form-control {
    background-color: #0f172a !important;
    border-color: rgba(255, 255, 255, 0.15) !important;
    color: #f1f5f9 !important;
}
[data-bs-theme="dark"] .chart-kpi-box {
    background: #1e293b !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .chart-kpi-box .text-dark {
    color: #f1f5f9 !important;
}
[data-bs-theme="dark"] #chartLoadingOverlay {
    background: rgba(15, 23, 42, 0.85) !important;
}
[data-bs-theme="dark"] #periodOrdersTable thead th {
    background: #1e293b !important;
    color: #cbd5e1 !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] #periodOrdersTable tbody tr td {
    background: transparent !important;
    color: #94a3b8 !important;
    border-color: rgba(255, 255, 255, 0.06) !important;
}
[data-bs-theme="dark"] #periodOrdersTable tbody tr:hover td {
    background: rgba(255, 255, 255, 0.04) !important;
}
[data-bs-theme="dark"] #periodOrdersTable .text-dark {
    color: #f1f5f9 !important;
}
[data-bs-theme="dark"] .chart-dashboard-card {
    background: #131f37 !important;
}
</style>

<!-- KHỞI TẠO VÀ ĐIỀU KHIỂN BIỂU ĐỒ CHART.JS & BỘ LỌC THỜI GIAN -->
<script>
// State toàn cục quản lý bộ lọc và dữ liệu chi tiết
let currentChartFilter = {
    type: <?= json_encode($initialChartData['filter_type']) ?>,
    preset: <?= json_encode($initialChartData['preset']) ?>,
    month: <?= (int)date('m') ?>,
    year: <?= (int)date('Y') ?>,
    date: <?= json_encode(date('Y-m-d')) ?>,
    start_date: <?= json_encode($initialChartData['start_date']) ?>,
    end_date: <?= json_encode($initialChartData['end_date']) ?>
};
let currentOrdersList = <?= json_encode($initialChartData['orders_list'], JSON_UNESCAPED_UNICODE) ?>;
let activePointFilterKey = null;

document.addEventListener('DOMContentLoaded', function() {
    // 1. Khởi tạo Biểu đồ Doanh thu & Đơn hàng (Combo Line & Bar)
    const revCtx = document.getElementById('revenueChart');
    if (revCtx) {
        const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
        const revData = <?= json_encode($chartRevenue) ?>;
        const ordData = <?= json_encode($chartOrders) ?>;
        const rawKeys = <?= json_encode($initialChartData['raw_keys']) ?>;

        window.vphoneRevChart = new Chart(revCtx, {
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
                        pointHoverRadius: 7,
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
                        barThickness: 'flex',
                        maxBarThickness: 28,
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
                        backgroundColor: 'rgba(11, 19, 41, 0.92)',
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                if (context.dataset.yAxisID === 'y') {
                                    return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.raw) + ' VNĐ';
                                } else {
                                    return 'Số đơn: ' + context.raw + ' đơn';
                                }
                            },
                            afterBody: function() {
                                return '\n👉 Click điểm này để lọc chi tiết đơn hàng';
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
        window.vphoneRevChart.rawKeys = rawKeys;
        window.vphoneRevChart.filterType = <?= json_encode($initialChartData['filter_type']) ?>;

        // Bắt sự kiện click vào cột hoặc điểm trên biểu đồ để lọc bảng chi tiết
        revCtx.onclick = function(evt) {
            if (!window.vphoneRevChart) return;
            const points = window.vphoneRevChart.getElementsAtEventForMode(evt, 'index', { intersect: false }, true);
            if (points && points.length > 0) {
                const idx = points[0].index;
                const label = window.vphoneRevChart.data.labels[idx];
                const rawKey = (window.vphoneRevChart.rawKeys && window.vphoneRevChart.rawKeys[idx]) ? window.vphoneRevChart.rawKeys[idx] : null;
                if (rawKey) {
                    applyChartPointFilter(rawKey, label);
                }
            }
        };
    }

    // 2. Khởi tạo Biểu đồ Doughnut Tỷ lệ trạng thái đơn hàng
    const statusCtx = document.getElementById('orderStatusChart');
    if (statusCtx) {
        const statusData = <?= json_encode(array_values($orderStatusData)) ?>;
        const total = statusData.reduce((a, b) => a + b, 0);

        window.vphoneStatusChart = new Chart(statusCtx, {
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
                        backgroundColor: 'rgba(11, 19, 41, 0.92)',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const val = context.raw;
                                const currentTotal = context.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = currentTotal > 0 ? ((val / currentTotal) * 100).toFixed(1) : 0;
                                return ' ' + context.label + ': ' + val + ' đơn (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Áp dụng màu giao diện Sáng / Tối cho biểu đồ
    function applyThemeToAdminCharts(theme) {
        const isDark = (theme === 'dark');
        const textColor = isDark ? '#94a3b8' : '#64748b';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(226, 232, 240, 0.6)';

        if (window.vphoneRevChart && window.vphoneRevChart.options && window.vphoneRevChart.options.scales) {
            if (window.vphoneRevChart.options.scales.x) {
                window.vphoneRevChart.options.scales.x.ticks = window.vphoneRevChart.options.scales.x.ticks || {};
                window.vphoneRevChart.options.scales.x.ticks.color = textColor;
            }
            if (window.vphoneRevChart.options.scales.y) {
                window.vphoneRevChart.options.scales.y.ticks = window.vphoneRevChart.options.scales.y.ticks || {};
                window.vphoneRevChart.options.scales.y.ticks.color = textColor;
                if (window.vphoneRevChart.options.scales.y.grid) {
                    window.vphoneRevChart.options.scales.y.grid.color = gridColor;
                }
            }
            if (window.vphoneRevChart.options.scales.y1) {
                window.vphoneRevChart.options.scales.y1.ticks = window.vphoneRevChart.options.scales.y1.ticks || {};
                window.vphoneRevChart.options.scales.y1.ticks.color = textColor;
            }
            if (window.vphoneRevChart.options.plugins && window.vphoneRevChart.options.plugins.legend) {
                window.vphoneRevChart.options.plugins.legend.labels.color = isDark ? '#f1f5f9' : '#1e293b';
            }
            window.vphoneRevChart.update();
        }

        if (window.vphoneStatusChart && window.vphoneStatusChart.data && window.vphoneStatusChart.data.datasets) {
            if (window.vphoneStatusChart.data.datasets[0]) {
                window.vphoneStatusChart.data.datasets[0].borderColor = isDark ? '#131f37' : '#ffffff';
            }
            window.vphoneStatusChart.update();
        }
    }

    const initialTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
    applyThemeToAdminCharts(initialTheme);

    window.addEventListener('vphoneThemeChanged', function(e) {
        applyThemeToAdminCharts(e.detail ? e.detail.theme : 'light');
    });
});

// 3. Hàm chọn khoảng thời gian nhanh (Presets)
function applyPresetFilter(presetKey, title) {
    currentChartFilter = {
        type: 'preset',
        preset: presetKey
    };
    clearChartPointFilter(false);
    fetchChartData(currentChartFilter);
}

// 4. Mở tab bộ lọc tùy chọn ngày / tháng / năm
function openCustomFilterTab(tabType) {
    const customPanel = document.getElementById('chartFilterCustomPanel');
    if (customPanel && !customPanel.classList.contains('show')) {
        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(customPanel);
        bsCollapse.show();
    }
    const tabBtn = document.getElementById(tabType === 'month' ? 'tab-month-btn' : (tabType === 'year' ? 'tab-year-btn' : 'tab-day-btn'));
    if (tabBtn) {
        const bsTab = bootstrap.Tab.getOrCreateInstance(tabBtn);
        bsTab.show();
    }
}

// 5. Xử lý submit bộ lọc tùy chọn
function handleFilterSubmit(e, filterType) {
    e.preventDefault();
    clearChartPointFilter(false);

    if (filterType === 'by_month') {
        const m = document.getElementById('filterMonthSelect').value;
        const y = document.getElementById('filterMonthYearSelect').value;
        currentChartFilter = { type: 'by_month', month: m, year: y };
    } else if (filterType === 'by_year') {
        const y = document.getElementById('filterYearSelect').value;
        currentChartFilter = { type: 'by_year', year: y };
    } else if (filterType === 'by_day') {
        const d = document.getElementById('filterDayInput').value;
        if (!d) {
            alert('Vui lòng chọn ngày hợp lệ');
            return;
        }
        currentChartFilter = { type: 'by_day', date: d };
    } else if (filterType === 'custom') {
        const s = document.getElementById('filterRangeStart').value;
        const end = document.getElementById('filterRangeEnd').value;
        if (!s || !end) {
            alert('Vui lòng chọn cả ngày bắt đầu và kết thúc');
            return;
        }
        currentChartFilter = { type: 'custom', start_date: s, end_date: end };
    }

    fetchChartData(currentChartFilter);
}

// 6. Gửi AJAX lấy dữ liệu biểu đồ và cập nhật giao diện
function fetchChartData(params) {
    const overlay = document.getElementById('chartLoadingOverlay');
    if (overlay) overlay.classList.remove('d-none');
    if (overlay) overlay.classList.add('d-flex');

    const searchParams = new URLSearchParams(params);
    searchParams.set('ajax', 'chart_data');

    fetch('index.php?' + searchParams.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (!data || !data.success) {
            console.error('Không thể tải dữ liệu biểu đồ:', data);
            return;
        }

        // 1. Cập nhật tiêu đề hiển thị
        const titleEl = document.getElementById('chartActiveFilterTitle');
        if (titleEl) titleEl.textContent = data.title;
        const subEl = document.getElementById('detailsActiveFilterSub');
        if (subEl) subEl.textContent = data.title;

        // Cập nhật trạng thái active trong dropdown presets
        document.querySelectorAll('.chart-preset-item').forEach(item => {
            const p = item.getAttribute('data-preset');
            if (data.filter_type === 'preset' && data.preset === p) {
                item.classList.add('active', 'fw-bold');
            } else {
                item.classList.remove('active', 'fw-bold');
            }
        });

        // 2. Cập nhật Mini KPI Summary Strip
        const kpiRev = document.getElementById('kpiPeriodRevenue');
        if (kpiRev) kpiRev.textContent = data.kpi.total_revenue_fmt;

        const kpiOrd = document.getElementById('kpiPeriodOrders');
        if (kpiOrd) {
            kpiOrd.innerHTML = data.kpi.completed_orders + ' / ' + data.kpi.total_orders + ' <small class="text-success fw-semibold ms-1">(' + data.kpi.completed_rate + '%)</small>';
        }

        const kpiAov = document.getElementById('kpiPeriodAov');
        if (kpiAov) kpiAov.textContent = data.kpi.avg_order_value_fmt;

        const kpiPeak = document.getElementById('kpiPeriodPeak');
        if (kpiPeak) {
            kpiPeak.innerHTML = data.kpi.peak_label + ': <span class="text-danger fw-bold">' + data.kpi.peak_revenue_fmt + '</span>';
            kpiPeak.title = data.kpi.peak_label + ': ' + data.kpi.peak_revenue_fmt;
        }

        // Cập nhật số lượng đơn trên các nút/badge
        const filterBadge = document.getElementById('filterOrdersBadgeCount');
        if (filterBadge) filterBadge.textContent = data.orders_list.length;
        const detailsBadge = document.getElementById('detailsCountBadge');
        if (detailsBadge) detailsBadge.textContent = data.orders_list.length + ' đơn';

        // 3. Cập nhật Biểu đồ Combo Line & Bar
        if (window.vphoneRevChart) {
            window.vphoneRevChart.data.labels = data.labels;
            window.vphoneRevChart.data.datasets[0].data = data.revenue;
            window.vphoneRevChart.data.datasets[1].data = data.orders;
            window.vphoneRevChart.rawKeys = data.raw_keys;
            window.vphoneRevChart.filterType = data.filter_type;
            window.vphoneRevChart.update();
        }

        // 4. Cập nhật Biểu đồ Doughnut Tỷ lệ đơn hàng & Thống kê số lượng
        if (window.vphoneStatusChart) {
            const sd = data.status_distribution;
            const statusVals = [
                sd['Đã giao thành công'] || 0,
                sd['Chờ xử lý'] || 0,
                sd['Đang giao hàng'] || 0,
                sd['Đã hủy'] || 0
            ];
            const sumVals = statusVals.reduce((a, b) => a + b, 0);
            window.vphoneStatusChart.data.datasets[0].data = sumVals === 0 ? [1, 0, 0, 0] : statusVals;
            window.vphoneStatusChart.update();

            const dTotal = document.getElementById('doughnutTotalOrdersCount');
            if (dTotal) dTotal.textContent = data.kpi.total_orders;
            const dComp = document.getElementById('doughnutCountCompleted');
            if (dComp) dComp.textContent = sd['Đã giao thành công'] || 0;
            const dPend = document.getElementById('doughnutCountPending');
            if (dPend) dPend.textContent = sd['Chờ xử lý'] || 0;
            const dShip = document.getElementById('doughnutCountShipping');
            if (dShip) dShip.textContent = sd['Đang giao hàng'] || 0;
            const dCanc = document.getElementById('doughnutCountCanceled');
            if (dCanc) dCanc.textContent = sd['Đã hủy'] || 0;
        }

        // 5. Cập nhật Bảng chi tiết đơn hàng
        currentOrdersList = data.orders_list || [];
        renderPeriodOrdersTable(currentOrdersList);
    })
    .catch(err => {
        console.error('Lỗi kết nối khi tải biểu đồ:', err);
    })
    .finally(() => {
        if (overlay) {
            overlay.classList.add('d-none');
            overlay.classList.remove('d-flex');
        }
    });
}

// 7. Render danh sách chi tiết đơn hàng
function renderPeriodOrdersTable(orders) {
    const tbody = document.getElementById('periodOrdersTableBody');
    if (!tbody) return;

    if (!orders || orders.length === 0) {
        tbody.innerHTML = `
            <tr id="periodOrdersEmptyRow">
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="fa-regular fa-folder-open fa-2x mb-2 d-block text-secondary opacity-50"></i>
                    Không có đơn hàng nào trong khoảng thời gian này
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    orders.forEach(ord => {
        const searchTerms = `${ord.order_code} ${ord.fullname} ${ord.phone} ${ord.products_summary}`.toLowerCase();
        html += `
            <tr class="period-order-row" data-date="${ord.date_key}" data-hour="${ord.hour_key}" data-month="${ord.month_key}" data-yearmonth="${ord.year_month_key}" data-search="${escapeHtml(searchTerms)}">
                <td class="ps-3 fw-bold text-primary">${escapeHtml(ord.order_code)}</td>
                <td class="text-secondary">${ord.created_at_fmt}</td>
                <td>
                    <div class="fw-bold text-dark">${escapeHtml(ord.fullname)}</div>
                    <small class="text-muted"><i class="fa-solid fa-phone me-1 small"></i>${escapeHtml(ord.phone)}</small>
                </td>
                <td>
                    <div class="text-truncate" style="max-width: 260px;" title="${escapeHtml(ord.products_summary)}">
                        ${escapeHtml(ord.products_summary)}
                    </div>
                </td>
                <td class="text-end fw-bold text-dark">${ord.total_money_fmt}</td>
                <td class="text-center">
                    <span class="badge bg-light text-secondary border rounded-pill px-2 py-1">${escapeHtml(ord.payment_method)}</span>
                </td>
                <td class="text-center">
                    <span class="badge ${ord.badge_class} rounded-pill px-2.5 py-1.5">${escapeHtml(ord.status)}</span>
                </td>
                <td class="pe-3 text-center">
                    <a href="orders.php?status=${encodeURIComponent(ord.status)}" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0.5 fw-bold" title="Xem chi tiết đơn này trong Quản lý đơn hàng">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;

    // Áp dụng lại bộ lọc tìm kiếm text hoặc điểm mốc nếu có
    filterPeriodOrdersTable();
}

// 8. Lọc bảng đơn hàng theo từ khóa tìm kiếm và điểm mốc biểu đồ
function filterPeriodOrdersTable() {
    const searchInput = document.getElementById('orderDetailSearchInput');
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const rows = document.querySelectorAll('.period-order-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const text = row.getAttribute('data-search') || '';
        const matchText = (query === '' || text.includes(query));

        let matchPoint = true;
        if (activePointFilterKey !== null) {
            const dateKey = row.getAttribute('data-date');
            const hourKey = row.getAttribute('data-hour');
            const monthKey = row.getAttribute('data-month');
            const ymKey = row.getAttribute('data-yearmonth');

            // Khớp theo ngày, theo giờ hoặc theo tháng tùy loại biểu đồ
            matchPoint = (dateKey === activePointFilterKey || hourKey === activePointFilterKey || monthKey === activePointFilterKey || ymKey === activePointFilterKey);
        }

        if (matchText && matchPoint) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const badge = document.getElementById('detailsCountBadge');
    if (badge) badge.textContent = visibleCount + ' đơn';
}

// 9. Lọc nhanh khi click điểm/cột trên biểu đồ
function applyChartPointFilter(rawKey, label) {
    activePointFilterKey = rawKey;

    // Hiển thị pill badge "Mốc: label"
    const tag = document.getElementById('chartPointActiveFilterTag');
    const tagVal = document.getElementById('pointTagValue');
    if (tag && tagVal) {
        tagVal.textContent = label;
        tag.classList.remove('d-none');
        tag.classList.add('d-inline-flex');
    }

    // Tự động mở phần bảng chi tiết nếu đang đóng
    const detailsSection = document.getElementById('chartOrdersDetailSection');
    if (detailsSection && !detailsSection.classList.contains('show')) {
        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(detailsSection);
        bsCollapse.show();
    }

    filterPeriodOrdersTable();
}

// 10. Hủy lọc theo điểm biểu đồ
function clearChartPointFilter(rerender = true) {
    activePointFilterKey = null;
    const tag = document.getElementById('chartPointActiveFilterTag');
    if (tag) {
        tag.classList.add('d-none');
        tag.classList.remove('d-inline-flex');
    }
    if (rerender) {
        filterPeriodOrdersTable();
    }
}

// 11. Xuất file CSV báo cáo cho kỳ đang chọn
function exportCurrentChartCsv() {
    const params = new URLSearchParams(currentChartFilter);
    params.set('export_chart_csv', '1');
    window.location.href = 'index.php?' + params.toString();
}

// Helper escape HTML phòng ngừa XSS
function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

<?php require_once 'includes/footer.php'; ?>
