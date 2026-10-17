<?php
require_once __DIR__ . '/../config/database.php';

// Xử lý xuất file CSV nếu có yêu cầu
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'vphone_don_hang_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    // Ghi UTF-8 BOM để Microsoft Excel đọc đúng tiếng Việt
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Mã đơn', 'Ngày đặt', 'Khách hàng', 'Số điện thoại', 'Địa chỉ', 'Ghi chú', 'Tổng tiền (VNĐ)', 'Phương thức', 'Trạng thái TT', 'Mã GD VietQR', 'Trạng thái giao']);
    $expOrders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($expOrders as $o) {
        fputcsv($output, [
            '#VP-' . $o['id'],
            $o['created_at'],
            $o['fullname'],
            $o['phone'],
            $o['address'],
            $o['note'] ?? '',
            $o['total_money'],
            $o['payment_method'],
            $o['payment_status'] ?? 'Chưa thanh toán',
            $o['transaction_code'] ?? '',
            $o['status']
        ]);
    }
    fclose($output);
    exit;
}

$pageTitle = 'Quản Lý Đơn Hàng - V-Phone Admin';
require_once 'includes/header.php';

$message = '';

if (isset($_POST['confirm_payment'])) {
    $orderId = (int)$_POST['order_id'];
    require_once __DIR__ . '/../app/services/VietQRService.php';
    $vietqr = new VietQRService($pdo);
    $res = $vietqr->markAsPaid($orderId, null, null, 'Admin xác nhận trực tiếp');
    if ($res['success']) {
        $message = "Đã xác nhận thanh toán VietQR cho đơn hàng #VP-$orderId thành công!";
    }
}

if (isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = trim($_POST['status']);

    $stStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stStmt->execute([$orderId]);
    $currentOrder = $stStmt->fetch(PDO::FETCH_ASSOC);

    if ($currentOrder) {
        $oldStatus = $currentOrder['status'];
        $up = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $up->execute([$newStatus, $orderId]);

        // Tạo thông báo cho khách hàng khi thay đổi trạng thái
        if ($oldStatus !== $newStatus) {
            require_once __DIR__ . '/../app/models/NotificationModel.php';
            $notifModel = new NotificationModel($pdo);
            $totalFmt = number_format($currentOrder['total_money'], 0, ',', '.') . ' đ';

            if ($newStatus === 'Đang giao hàng') {
                $notifTitle = "Đơn hàng #VP-{$orderId} đang được giao đến bạn!";
                $notifMsg = "Đơn hàng #VP-{$orderId} (Tổng thanh toán: {$totalFmt}) đã được đóng gói và bàn giao cho đơn vị vận chuyển. Shipper sẽ sớm liên hệ giao hàng đến bạn!";
                $notifModel->create(
                    $currentOrder['user_id'] ? (int)$currentOrder['user_id'] : null,
                    $currentOrder['phone'],
                    $orderId,
                    $notifTitle,
                    $notifMsg,
                    null,
                    'order_shipping'
                );
            } elseif ($newStatus === 'Đã giao thành công') {
                $notifTitle = "Đơn hàng #VP-{$orderId} đã giao thành công!";
                $notifMsg = "Đơn hàng #VP-{$orderId} (Tổng thanh toán: {$totalFmt}) đã được giao thành công. Cảm ơn quý khách đã tin tưởng và ủng hộ V-Phone Store!";
                $notifModel->create(
                    $currentOrder['user_id'] ? (int)$currentOrder['user_id'] : null,
                    $currentOrder['phone'],
                    $orderId,
                    $notifTitle,
                    $notifMsg,
                    null,
                    'order_delivered'
                );
            } elseif ($newStatus === 'Đang xử lý') {
                $notifTitle = "Đơn hàng #VP-{$orderId} đã được xác nhận!";
                $notifMsg = "Đơn hàng #VP-{$orderId} (Tổng thanh toán: {$totalFmt}) đã được tiếp nhận. Cửa hàng đang tiến hành kiểm tra và chuẩn bị sản phẩm.";
                $notifModel->create(
                    $currentOrder['user_id'] ? (int)$currentOrder['user_id'] : null,
                    $currentOrder['phone'],
                    $orderId,
                    $notifTitle,
                    $notifMsg,
                    null,
                    'order_processing'
                );
            } elseif ($newStatus === 'Đã hủy') {
                $notifTitle = "Đơn hàng #VP-{$orderId} đã chuyển sang trạng thái Đã hủy";
                $notifMsg = "Đơn hàng #VP-{$orderId} (Tổng thanh toán: {$totalFmt}) đã được chuyển sang trạng thái Đã hủy.";
                $notifModel->create(
                    $currentOrder['user_id'] ? (int)$currentOrder['user_id'] : null,
                    $currentOrder['phone'],
                    $orderId,
                    $notifTitle,
                    $notifMsg,
                    'Hủy đơn trong danh sách quản trị',
                    'order_cancelled'
                );
            }
        }

        $message = "Đã cập nhật trạng thái đơn hàng #VP-$orderId thành '$newStatus' và gửi thông báo cho khách hàng!";
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['delete_order'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $cancelReason = trim($_POST['cancel_reason'] ?? 'Sản phẩm tạm thời hết hàng hoặc theo yêu cầu của khách hàng');
    $restoreStock = isset($_POST['restore_stock']) && $_POST['restore_stock'] == '1';

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // 1. Hoàn lại số lượng tồn kho sản phẩm nếu được yêu cầu
        if ($restoreStock) {
            require_once __DIR__ . '/../app/models/OrderModel.php';
            require_once __DIR__ . '/../app/models/ProductModel.php';
            $orderModel = new OrderModel($pdo);
            $prodModel = new ProductModel($pdo);
            $details = $orderModel->getDetailsByOrderId($orderId);
            foreach ($details as $d) {
                if (!empty($d['product_id'])) {
                    $color = '';
                    if (preg_match('/Màu:\s*([^)]+)/u', $d['product_name'], $m)) {
                        $color = trim($m[1]);
                    }
                    $prodModel->restoreStock((int)$d['product_id'], (int)$d['quantity'], $color);
                }
            }
        }

        // 2. Tạo thông báo chi tiết cho khách hàng
        require_once __DIR__ . '/../app/models/NotificationModel.php';
        $notifModel = new NotificationModel($pdo);
        $notifTitle = "Đơn hàng #VP-{$orderId} đã bị hủy bởi cửa hàng";
        $notifMsg = "Đơn hàng #VP-{$orderId} (Tổng thanh toán: " . number_format($order['total_money'], 0, ',', '.') . " đ) đã được xử lý hủy bởi V-Phone Store.\nLý do: {$cancelReason}.\nRất xin lỗi quý khách vì sự bất tiện này. Quý khách vui lòng liên hệ hotline 1800 6868 nếu cần hỗ trợ thêm.";

        $notifModel->create(
            $order['user_id'] ? (int)$order['user_id'] : null,
            $order['phone'],
            $orderId,
            $notifTitle,
            $notifMsg,
            $cancelReason,
            'order_cancelled'
        );

        // 3. Xóa đơn hàng
        $del = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $del->execute([$orderId]);

        $message = "Đã xóa đơn hàng #VP-$orderId, hoàn kho và gửi thông báo lý do hủy cho khách hàng thành công!";
    }
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$detailsByOrder = [];
if ($orders) {
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $detailsStmt = $pdo->prepare("SELECT od.id, od.order_id, od.product_id, od.product_name, od.price, od.quantity, od.total_price, p.image
        FROM order_details od
        LEFT JOIN products p ON od.product_id = p.id
        WHERE od.order_id IN ($placeholders)
        ORDER BY od.id");
    $detailsStmt->execute($orderIds);
    foreach ($detailsStmt->fetchAll(PDO::FETCH_ASSOC) as $detail) {
        $detailsByOrder[$detail['order_id']][] = $detail;
    }
}

// Lọc đơn hàng theo trạng thái
$filterStatus = $_GET['status'] ?? 'all';
$statusCounts = [
    'all' => count($orders),
    'Chờ xử lý' => 0,
    'Đang xử lý' => 0,
    'Đang giao hàng' => 0,
    'Đã giao thành công' => 0,
    'Đã hủy' => 0
];
foreach ($orders as $o) {
    if (isset($statusCounts[$o['status']])) {
        $statusCounts[$o['status']]++;
    } elseif ($o['status'] === 'Chờ thanh toán') {
        $statusCounts['Chờ xử lý']++;
    }
}

$filteredOrders = array_filter($orders, function($o) use ($filterStatus) {
    if ($filterStatus === 'all') return true;
    if ($filterStatus === 'Chờ xử lý') {
        return ($o['status'] === 'Chờ xử lý' || $o['status'] === 'Chờ thanh toán');
    }
    return $o['status'] === $filterStatus;
});
?>

<!-- TIÊU ĐỀ & NÚT THAO TÁC -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h3 class="fw-black text-dark mb-0">Quản Lý Đơn Hàng</h3>
            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2.5 py-1 rounded-pill small">
                Live Console
            </span>
        </div>
        <p class="text-secondary small mb-0">Theo dõi, duyệt giao hàng, xem chi tiết và in phiếu xuất kho V-Phone Flagship</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="orders.php?export=csv" class="btn btn-outline-success btn-sm rounded-pill fw-bold px-3.5 py-2 shadow-sm d-flex align-items-center gap-1.5">
            <i class="fa-solid fa-file-excel"></i>
            <span>Xuất Excel (CSV)</span>
        </a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 py-3 px-4 mb-4 shadow-sm border-0 d-flex align-items-center gap-3" role="alert" style="background:#ecfdf5; border-left: 4px solid #10b981 !important;">
        <i class="fa-solid fa-circle-check text-success fs-4"></i>
        <div class="flex-grow-1">
            <strong class="d-block text-dark">Thành công!</strong>
            <span class="small text-secondary"><?= htmlspecialchars($message) ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: '<?= addslashes($message) ?>',
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-4 shadow-lg border-0 py-2.5 px-3'
                    }
                });
            }
        });
    </script>
<?php endif; ?>

<!-- 4 THẺ THỐNG KÊ NHANH (KPI QUICK GLANCE) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="admin-kpi-card h-100 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Tổng đơn</span>
                    <h4 class="fw-black text-dark my-1"><?= $statusCounts['all'] ?> <span class="fs-6 fw-normal text-muted">đơn</span></h4>
                    <span class="badge kpi-badge-blue rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-layer-group me-1"></i>Toàn hệ thống
                    </span>
                </div>
                <div class="admin-kpi-icon-box" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #0066cc;">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="admin-kpi-card h-100 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Cần chuẩn bị</span>
                    <h4 class="fw-black text-warning my-1"><?= ($statusCounts['Chờ xử lý'] + $statusCounts['Đang xử lý']) ?> <span class="fs-6 fw-normal text-muted">đơn</span></h4>
                    <span class="badge kpi-badge-amber rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-box-open me-1"></i>Cần đóng gói & giao
                    </span>
                </div>
                <div class="admin-kpi-icon-box" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); color: #d97706;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="admin-kpi-card h-100 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Đang giao hàng</span>
                    <h4 class="fw-black text-info my-1"><?= $statusCounts['Đang giao hàng'] ?> <span class="fs-6 fw-normal text-muted">đơn</span></h4>
                    <span class="badge kpi-badge-sky rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-truck-fast me-1"></i>V-Phone Express
                    </span>
                </div>
                <div class="admin-kpi-icon-box" style="background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); color: #0284c7;">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="admin-kpi-card h-100 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Đã hoàn thành</span>
                    <h4 class="fw-black text-success my-1"><?= $statusCounts['Đã giao thành công'] ?> <span class="fs-6 fw-normal text-muted">đơn</span></h4>
                    <span class="badge kpi-badge-emerald rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-circle-check me-1"></i>Giao thành công
                    </span>
                </div>
                <div class="admin-kpi-icon-box" style="background: linear-gradient(135deg, #ecfdf5 0%, #a7f3d0 100%); color: #16a34a;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BỘ LỌC TRẠNG THÁI (SEGMENTED BAR) & Ô TÌM KIẾM NHANH -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="order-filter-nav-wrap flex-grow-1" style="max-width: 820px;">
        <a href="orders.php?status=all" class="order-tab-item <?= $filterStatus === 'all' ? 'active-all' : '' ?>">
            <i class="fa-solid fa-layer-group"></i>
            <span>Tất cả</span>
            <span class="order-tab-count"><?= $statusCounts['all'] ?></span>
        </a>
        <a href="orders.php?status=Chờ xử lý" class="order-tab-item <?= $filterStatus === 'Chờ xử lý' ? 'active-pending' : '' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Chờ xử lý</span>
            <span class="order-tab-count"><?= $statusCounts['Chờ xử lý'] ?></span>
        </a>
        <a href="orders.php?status=Đang xử lý" class="order-tab-item <?= $filterStatus === 'Đang xử lý' ? 'active-processing' : '' ?>">
            <i class="fa-solid fa-boxes-packing"></i>
            <span>Đang chuẩn bị</span>
            <span class="order-tab-count"><?= $statusCounts['Đang xử lý'] ?></span>
        </a>
        <a href="orders.php?status=Đang giao hàng" class="order-tab-item <?= $filterStatus === 'Đang giao hàng' ? 'active-shipping' : '' ?>">
            <i class="fa-solid fa-truck-fast"></i>
            <span>Đang giao</span>
            <span class="order-tab-count"><?= $statusCounts['Đang giao hàng'] ?></span>
        </a>
        <a href="orders.php?status=Đã giao thành công" class="order-tab-item <?= $filterStatus === 'Đã giao thành công' ? 'active-delivered' : '' ?>">
            <i class="fa-solid fa-circle-check"></i>
            <span>Đã giao</span>
            <span class="order-tab-count"><?= $statusCounts['Đã giao thành công'] ?></span>
        </a>
        <a href="orders.php?status=Đã hủy" class="order-tab-item <?= $filterStatus === 'Đã hủy' ? 'active-cancelled' : '' ?>">
            <i class="fa-solid fa-circle-xmark"></i>
            <span>Đã hủy</span>
            <span class="order-tab-count"><?= $statusCounts['Đã hủy'] ?></span>
        </a>
    </div>

    <div class="input-group" style="width: 270px;">
        <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3 text-muted">
            <i class="fa-solid fa-magnifying-glass"></i>
        </span>
        <input type="text" id="adminOrderSearch" class="form-control border-start-0 rounded-end-pill py-2 small bg-white" placeholder="Tìm tên, SĐT, mã đơn..." onkeyup="filterAdminOrders()">
    </div>
</div>

<style>
.admin-order-table-card {
    border-radius: 20px;
    border: 1px solid rgba(226, 232, 240, 0.85);
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    overflow: visible !important;
}
.admin-order-table-wrap {
    min-height: 340px;
    overflow-y: visible !important;
}
@media (min-width: 992px) {
    .admin-order-table-wrap {
        overflow-x: visible !important;
    }
}
.dropdown-menu-status {
    z-index: 99999 !important;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.16), 0 4px 12px rgba(15, 23, 42, 0.08) !important;
}
.dropdown-menu-status .dropdown-item:hover {
    background-color: #f1f5f9;
}
.hover-primary:hover {
    background-color: #eff6ff !important;
    color: #0066cc !important;
    border-color: #bfdbfe !important;
}
.hover-danger:hover {
    background-color: #fef2f2 !important;
    color: #dc2626 !important;
    border-color: #fecaca !important;
}
</style>

<!-- BẢNG DANH SÁCH ĐƠN HÀNG FLAGSHIP -->
<div class="admin-table-card admin-order-table-card shadow-sm border-0">
    <?php if (empty($filteredOrders)): ?>
        <div class="text-center py-5 text-muted bg-white">
            <div style="width: 76px; height: 76px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i class="fa-solid fa-inbox fs-2 text-secondary opacity-50"></i>
            </div>
            <h6 class="fw-bold text-dark mb-1">Không Có Đơn Hàng Nào</h6>
            <p class="small text-secondary mb-0">Không tìm thấy đơn hàng nào phù hợp với bộ lọc đã chọn.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive admin-order-table-wrap bg-white">
            <table class="table admin-table align-middle mb-0" id="adminOrdersTable">
                <thead>
                    <tr>
                        <th class="ps-4">Mã Đơn</th>
                        <th>Khách Hàng</th>
                        <th>Sản Phẩm Đặt Mua</th>
                        <th>Tổng Tiền</th>
                        <th>Thanh Toán</th>
                        <th class="text-center">Trạng Thái Đơn</th>
                        <th class="text-end pe-4">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredOrders as $o): ?>
                        <?php
                            $details = $detailsByOrder[$o['id']] ?? [];
                            $orderDataJson = htmlspecialchars(json_encode([
                                'id' => $o['id'],
                                'fullname' => $o['fullname'],
                                'phone' => $o['phone'],
                                'address' => $o['address'],
                                'note' => $o['note'] ?? '',
                                'total_money' => (float)$o['total_money'],
                                'payment_method' => $o['payment_method'],
                                'payment_status' => $o['payment_status'] ?? 'Chưa thanh toán',
                                'transaction_code' => $o['transaction_code'] ?? '',
                                'paid_at' => $o['paid_at'] ?? '',
                                'status' => $o['status'],
                                'created_at' => $o['created_at'],
                                'items' => $details
                            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                            $stMeta = getOrderStatusMeta($o['status']);
                        ?>
                        <tr class="order-row" data-search="<?= htmlspecialchars(mb_strtolower($o['fullname'] . ' ' . $o['phone'] . ' ' . $o['id'] . ' ' . $o['address'], 'UTF-8')) ?>">
                            <!-- MÃ ĐƠN & NGÀY ĐẶT -->
                            <td class="ps-4 text-nowrap">
                                <div class="order-id-chip">
                                    <i class="fa-solid fa-receipt me-1.5 opacity-75"></i>#VP-<?= $o['id'] ?>
                                </div>
                                <div class="text-muted small mt-1.5 d-flex align-items-center gap-1.5" style="font-size: 0.74rem;">
                                    <i class="fa-regular fa-clock text-primary"></i>
                                    <span><?= date('H:i • d/m', strtotime($o['created_at'])) ?></span>
                                </div>
                            </td>

                            <!-- THÔNG TIN KHÁCH HÀNG -->
                            <td style="min-width: 200px;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="customer-avatar-badge" style="background: linear-gradient(135deg, #0066cc 0%, #38bdf8 100%);">
                                        <?= mb_substr(htmlspecialchars($o['fullname']), 0, 1, 'UTF-8') ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($o['fullname']) ?>">
                                            <?= htmlspecialchars($o['fullname']) ?>
                                        </div>
                                        <a href="tel:<?= htmlspecialchars($o['phone']) ?>" class="text-secondary text-decoration-none small d-flex align-items-center gap-1.5 my-0.5">
                                            <i class="fa-solid fa-phone text-primary small"></i><span class="fw-semibold"><?= htmlspecialchars($o['phone']) ?></span>
                                        </a>
                                        <div class="text-muted text-truncate d-flex align-items-center gap-1.5" style="max-width: 180px; font-size: 0.74rem;" title="<?= htmlspecialchars($o['address']) ?>">
                                            <i class="fa-solid fa-location-dot text-danger opacity-75 flex-shrink-0"></i><span class="text-truncate"><?= htmlspecialchars($o['address']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- SẢN PHẨM TRONG ĐƠN (CÓ THUMBNAIL & CHIPS) -->
                            <td style="min-width: 260px;">
                                <?php foreach ($details as $d): ?>
                                    <?php
                                    $rawName = $d['product_name'];
                                    $baseName = preg_replace('/\s*\(.*$/u', '', $rawName);
                                    $variantRom = '';
                                    if (preg_match('/\((\d+\s*GB|\d+\s*TB)/i', $rawName, $m)) {
                                        $variantRom = trim($m[1]);
                                    }
                                    $variantColor = '';
                                    if (preg_match('/Màu:\s*([^)]+)/u', $rawName, $m)) {
                                        $variantColor = trim($m[1]);
                                    }
                                    $colorHex = !empty($variantColor) ? getColorHexValue($variantColor) : '#64748b';
                                    $imgUrl = !empty($d['image']) ? '../' . $d['image'] : '../assets/images/products/iphone-17-pro.png';
                                    ?>
                                    <div class="d-flex align-items-center gap-3 py-1">
                                        <div class="admin-prod-thumb">
                                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($baseName) ?>" loading="lazy" onerror="this.src='../assets/images/products/iphone-17-pro.png'">
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-bold text-dark small text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($rawName) ?>">
                                                <?= htmlspecialchars($baseName) ?>
                                            </div>
                                            <div class="d-flex align-items-center gap-1.5 flex-wrap mt-1">
                                                <?php if (!empty($variantRom)): ?>
                                                    <span class="badge bg-light text-primary border rounded-pill" style="font-size: 0.68rem; padding: 2.5px 8px;">
                                                        <i class="fa-solid fa-microchip me-1"></i><?= htmlspecialchars($variantRom) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($variantColor)): ?>
                                                    <span class="badge bg-light text-dark border rounded-pill" style="font-size: 0.68rem; padding: 2.5px 8px;">
                                                        <span class="color-dot-swatch me-1" style="background-color: <?= $colorHex ?>; width: 7px; height: 7px; display: inline-block; border-radius: 50%;"></span>
                                                        <?= htmlspecialchars($variantColor) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill" style="font-size: 0.68rem; padding: 2.5px 8px;">
                                                    x<?= (int)$d['quantity'] ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </td>

                            <!-- TỔNG TIỀN -->
                            <td class="text-nowrap">
                                <div class="text-danger fw-black fs-6" style="letter-spacing: -0.3px;">
                                    <?= number_format($o['total_money'], 0, ',', '.') ?> đ
                                </div>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small mt-1 d-inline-flex align-items-center" style="font-size: 0.68rem;">
                                    <i class="fa-solid fa-truck-fast me-1.5"></i>Freeship
                                </span>
                            </td>

                            <!-- PHƯƠNG THỨC & THANH TOÁN -->
                            <td>
                                <?php 
                                    $pMethod = $o['payment_method'];
                                    $isPaid = (($o['payment_status'] ?? '') === 'Đã thanh toán');
                                    $isVietQR = ($pMethod === 'Chuyển khoản QR VietQR');
                                    $isMoMo = ($pMethod === 'Ví điện tử MoMo');
                                ?>
                                <div class="mb-1.5">
                                    <?php if ($isVietQR): ?>
                                        <span class="badge rounded-pill px-2.5 py-1 small fw-bold d-inline-flex align-items-center" style="background: #eff6ff; color: #0066cc; border: 1px solid #bfdbfe;">
                                            <i class="fa-solid fa-qrcode text-danger me-1.5"></i>VietQR
                                        </span>
                                    <?php elseif ($isMoMo): ?>
                                        <span class="badge rounded-pill px-2.5 py-1 small fw-bold d-inline-flex align-items-center" style="background: #fdf2f8; color: #d82d8b; border: 1px solid #fbcfe8;">
                                            <i class="fa-solid fa-wallet me-1.5"></i>MoMo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill px-2.5 py-1 small fw-bold d-inline-flex align-items-center" style="background: #f8fafc; color: #334155; border: 1px solid #e2e8f0;">
                                            <i class="fa-solid fa-money-bill-wave text-success me-1.5"></i>COD
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <?php if ($isPaid): ?>
                                        <span class="badge rounded-pill px-2.5 py-1 small fw-bold d-inline-flex align-items-center" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;">
                                            <i class="fa-solid fa-circle-check me-1.5"></i>Đã TT
                                        </span>
                                        <?php if (!empty($o['transaction_code'])): ?>
                                            <small class="text-muted d-block font-monospace mt-1" style="font-size: 0.68rem;" title="Mã GD: <?= htmlspecialchars($o['transaction_code']) ?>">
                                                <?= htmlspecialchars($o['transaction_code']) ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge rounded-pill px-2.5 py-1 small fw-bold d-inline-flex align-items-center" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                                            <i class="fa-solid fa-clock me-1.5 text-warning"></i>Chưa TT
                                        </span>
                                        <?php if ($isVietQR && $o['status'] !== 'Đã hủy'): ?>
                                            <div class="mt-1">
                                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill py-0.5 px-2.5 fw-bold d-inline-flex align-items-center" style="font-size: 0.72rem;" onclick="confirmVietQRPayment(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes($o['fullname'])) ?>', '<?= number_format($o['total_money'], 0, ',', '.') ?> đ', '<?= htmlspecialchars($o['transaction_code'] ?? '') ?>')" title="Xác nhận đã nhận tiền">
                                                    <i class="fa-solid fa-check me-1"></i>Duyệt TT
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- TRẠNG THÁI ĐƠN HÀNG (INTERACTIVE DROPDOWN) -->
                            <td class="text-center text-nowrap">
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm admin-status-btn admin-status-<?= $stMeta['key'] ?? 'pending' ?> shadow-none" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Bấm để đổi trạng thái">
                                        <i class="<?= $stMeta['icon'] ?> status-btn-icon"></i>
                                        <span class="status-btn-text"><?= htmlspecialchars($stMeta['shortLabel']) ?></span>
                                        <i class="fa-solid fa-chevron-down status-btn-chevron"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-status shadow-lg border-0 rounded-4 p-2 mt-1" style="min-width: 230px;">
                                        <li class="dropdown-header text-uppercase small fw-bold px-2 py-1 text-muted" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                            Chuyển Trạng Thái
                                        </li>
                                        <?php
                                        $statuses = [
                                            'Chờ xử lý' => ['icon' => 'fa-solid fa-clock-rotate-left', 'color' => '#d97706', 'label' => 'Chờ xử lý'],
                                            'Đang xử lý' => ['icon' => 'fa-solid fa-boxes-packing', 'color' => '#2563eb', 'label' => 'Đang chuẩn bị hàng'],
                                            'Đang giao hàng' => ['icon' => 'fa-solid fa-truck-fast', 'color' => '#0284c7', 'label' => 'Đang giao hàng'],
                                            'Đã giao thành công' => ['icon' => 'fa-solid fa-circle-check', 'color' => '#16a34a', 'label' => 'Đã giao thành công'],
                                            'Đã hủy' => ['icon' => 'fa-solid fa-circle-xmark', 'color' => '#dc2626', 'label' => 'Hủy đơn hàng']
                                        ];
                                        foreach ($statuses as $stKey => $stCfg):
                                            $isCur = ($o['status'] === $stKey);
                                        ?>
                                            <li>
                                                <form method="POST" action="orders.php<?= !empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '' ?>" class="m-0">
                                                    <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                                    <input type="hidden" name="status" value="<?= htmlspecialchars($stKey) ?>">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <button type="submit" class="dropdown-item rounded-3 py-2 px-2.5 d-flex align-items-center justify-content-between gap-2 <?= $isCur ? 'active fw-bold' : '' ?>" style="<?= $isCur ? 'background: #eff6ff; color: #0066cc;' : '' ?>">
                                                        <span class="d-flex align-items-center gap-2">
                                                            <i class="<?= $stCfg['icon'] ?>" style="color: <?= $stCfg['color'] ?>; width: 16px;"></i>
                                                            <span class="small"><?= $stCfg['label'] ?></span>
                                                        </span>
                                                        <?php if ($isCur): ?>
                                                            <i class="fa-solid fa-check small text-primary"></i>
                                                        <?php endif; ?>
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </td>

                            <!-- THAO TÁC -->
                            <td class="text-end text-nowrap pe-4">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <!-- Nút xem chi tiết đơn hàng dạng Popup -->
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" onclick="viewOrderDetail(<?= $orderDataJson ?>)" title="Xem chi tiết đơn hàng">
                                        <i class="fa-solid fa-eye"></i><span>Chi tiết</span>
                                    </button>

                                    <!-- Nút in nhanh hóa đơn -->
                                    <button type="button" class="btn btn-sm btn-light border rounded-circle text-secondary hover-primary" style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" onclick="printOrderInvoice(<?= $orderDataJson ?>)" title="In phiếu xuất kho">
                                        <i class="fa-solid fa-print"></i>
                                    </button>

                                    <!-- Nút xóa đơn hàng có thông báo cho khách -->
                                    <button type="button" class="btn btn-sm btn-light border text-danger rounded-circle hover-danger" style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Hủy & Xóa đơn (có thông báo)" aria-label="Xóa đơn VP-<?= (int)$o['id'] ?>" onclick="openOrderDeleteModal(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes($o['fullname'])) ?>', '<?= htmlspecialchars($o['phone']) ?>', '<?= number_format($o['total_money'], 0, ',', '.') ?> đ')">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL XEM CHI TIẾT ĐƠN HÀNG -->
<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header border-0 bg-dark text-white p-4">
                <div>
                    <h5 class="modal-title fw-bold" id="orderDetailModalLabel">
                        <i class="fa-solid fa-receipt text-primary me-2"></i>Chi Tiết Đơn Hàng <span id="mOrderId" class="text-info">#VP-0</span>
                    </h5>
                    <small class="text-light opacity-75">Ngày đặt: <span id="mOrderDate">--</span></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- THANH TIẾN ĐỘ TRẠNG THÁI (STEPPER) -->
                <div class="mb-4 p-3 bg-light rounded-4">
                    <div class="row text-center g-2" id="orderTimeline">
                        <div class="col-3">
                            <div class="timeline-step step-received">
                                <div class="step-icon mb-1"><i class="fa-solid fa-file-invoice"></i></div>
                                <small class="fw-bold d-block">Đặt hàng</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="timeline-step step-processing">
                                <div class="step-icon mb-1"><i class="fa-solid fa-boxes-packing"></i></div>
                                <small class="fw-bold d-block">Chuẩn bị máy</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="timeline-step step-shipping">
                                <div class="step-icon mb-1"><i class="fa-solid fa-truck-fast"></i></div>
                                <small class="fw-bold d-block">Đang giao</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="timeline-step step-completed">
                                <div class="step-icon mb-1"><i class="fa-solid fa-circle-check"></i></div>
                                <small class="fw-bold d-block">Hoàn thành</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- THÔNG TIN KHÁCH HÀNG & GIAO NHẬN -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <span class="text-uppercase fw-bold text-muted small d-block mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">Người nhận hàng</span>
                            <div class="fw-bold fs-6 text-dark" id="mCustomerName">--</div>
                            <div class="text-secondary small mt-1"><i class="fa-solid fa-phone me-1 text-muted"></i><span id="mCustomerPhone">--</span></div>
                            <div class="text-secondary small mt-1"><i class="fa-solid fa-location-dot me-1 text-muted"></i><span id="mCustomerAddress">--</span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <span class="text-uppercase fw-bold text-muted small d-block mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">Thanh toán & Ghi chú</span>
                            <div class="small mb-1">
                                <span class="text-muted">Phương thức:</span> <strong id="mPaymentMethod" class="badge bg-white text-dark border">COD</strong>
                            </div>
                            <div class="small mb-1">
                                <span class="text-muted">Thanh toán:</span> <span id="mPaymentStatusBadge" class="badge bg-secondary">Chưa thanh toán</span>
                            </div>
                            <div class="small mb-1" id="mTransCodeBox" style="display: none;">
                                <span class="text-muted">Mã GD VietQR:</span> <span id="mTransCode" class="fw-bold font-monospace text-success">-</span>
                            </div>
                            <div class="small mb-1">
                                <span class="text-muted">Trạng thái:</span> <span id="mOrderStatusBadge" class="badge bg-warning text-dark">Chờ xử lý</span>
                            </div>
                            <div class="small text-muted mt-2" id="mOrderNoteBox">
                                <i class="fa-regular fa-comment-dots me-1"></i>Ghi chú: <span id="mOrderNote" class="text-dark fw-medium">Không có</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BẢNG MẶT HÀNG TRONG ĐƠN -->
                <div class="table-responsive rounded-3 border mb-3">
                    <table class="table align-middle mb-0" id="mItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">Ảnh</th>
                                <th>Sản phẩm</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody id="mItemsTbody">
                            <!-- JS render các item -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Tổng thanh toán:</td>
                                <td class="text-end fw-black text-danger fs-6" id="mTotalMoney">0 đ</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- CẬP NHẬT TRẠNG THÁI TRỰC TIẾP -->
                <form method="POST" action="orders.php<?= !empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '' ?>" class="p-3 bg-light rounded-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <input type="hidden" name="order_id" id="mFormOrderId" value="">
                    <input type="hidden" name="update_status" value="1">
                    <div class="d-flex align-items-center gap-2">
                        <label class="fw-bold small text-dark">Cập nhật trạng thái:</label>
                        <select name="status" id="mFormStatusSelect" class="form-select form-select-sm rounded-pill fw-bold" style="width: 220px;">
                            <option value="Chờ xử lý">Chờ xử lý</option>
                            <option value="Đang xử lý">Đang chuẩn bị hàng</option>
                            <option value="Đang giao hàng">Đang giao hàng</option>
                            <option value="Đã giao thành công">Đã giao thành công</option>
                            <option value="Đã hủy">Đã hủy đơn hàng</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">
                        Lưu thay đổi
                    </button>
                </form>
            </div>

            <div class="modal-footer border-0 bg-light p-3">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-3 btn-sm" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-success rounded-pill px-4 btn-sm fw-bold" id="mPrintBtn">
                    <i class="fa-solid fa-print me-1.5"></i>In Phiếu Xuất Kho
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL XÁC NHẬN XÓA / HỦY ĐƠN VÀ THÔNG BÁO CHO KHÁCH HÀNG -->
<div class="modal fade" id="adminOrderDeleteModal" tabindex="-1" aria-labelledby="adminOrderDeleteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header border-0 bg-danger text-white p-3 px-4">
                <div>
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="adminOrderDeleteModalTitle">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Hủy & Xóa Đơn Hàng <span id="delModalOrderId" class="ms-1.5 text-warning">#VP-0</span>
                    </h5>
                    <small class="text-white opacity-75">Hệ thống sẽ lưu lý do và gửi thông báo trực tiếp đến khách hàng</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="orders.php<?= !empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '' ?>" id="realDeleteOrderForm">
                <input type="hidden" name="order_id" id="delModalInputOrderId" value="0">
                <input type="hidden" name="delete_order" value="1">
                
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Khách hàng:</span>
                            <strong class="text-dark" id="delModalCustomerName">--</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Số điện thoại:</span>
                            <span class="text-dark fw-semibold" id="delModalCustomerPhone">--</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Tổng thanh toán:</span>
                            <span class="text-danger fw-bold" id="delModalTotalMoney">--</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small mb-1.5">
                            <i class="fa-solid fa-bell text-danger me-1"></i>Lý do hủy đơn gửi cho khách:
                        </label>
                        
                        <div class="d-flex flex-wrap gap-1.5 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-2.5 small" onclick="setCancelReason('Sản phẩm tạm thời hết hàng trong kho. V-Phone xin lỗi quý khách vì sự bất tiện này.')">
                                <i class="fa-solid fa-box-open me-1 text-danger"></i>Hết hàng trong kho
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-2.5 small" onclick="setCancelReason('Khách hàng đã liên hệ yêu cầu hủy đơn hàng này.')">
                                <i class="fa-solid fa-user me-1 text-primary"></i>Khách yêu cầu hủy
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-2.5 small" onclick="setCancelReason('Không thể liên lạc được qua số điện thoại để xác nhận giao hàng.')">
                                <i class="fa-solid fa-phone-slash me-1 text-warning"></i>Không liên lạc được
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-2.5 small" onclick="setCancelReason('Đơn hàng bị trùng lặp trên hệ thống.')">
                                <i class="fa-solid fa-clone me-1 text-secondary"></i>Trùng lặp đơn
                            </button>
                        </div>

                        <textarea name="cancel_reason" id="delModalCancelReason" class="form-control rounded-3 small" rows="3" placeholder="Nhập lý do gửi đến khách hàng..." required>Sản phẩm tạm thời hết hàng trong kho. V-Phone xin lỗi quý khách vì sự bất tiện này.</textarea>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="restore_stock" value="1" id="restoreStockSwitch" checked>
                        <label class="form-check-label small fw-semibold text-dark" for="restoreStockSwitch">
                            Tự động hoàn lại số lượng sản phẩm vào kho hàng
                        </label>
                    </div>

                    <div class="p-2.5 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 small text-danger">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Thông báo hủy kèm lý do sẽ hiển thị ngay khi khách đăng nhập hoặc tra cứu đơn.
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-medium" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-trash-can me-1.5"></i>Xóa Đơn & Gửi Thông Báo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- KHU VỰC IN HÓA ĐƠN CHUYÊN NGHIỆP (ẨN KHI XEM, HIỆN KHI IN) -->
<div id="printableInvoiceArea" class="print-clean d-none">
    <div style="max-width: 800px; margin: 0 auto; padding: 24px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #111;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0066cc; padding-bottom: 16px; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0; font-weight: 800; color: #0066cc;">V-PHONE STORE</h2>
                <div style="font-size: 13px; color: #555; margin-top: 4px;">Hệ Thống Phân Phối Smartphone & Flagship Cao Cấp</div>
                <div style="font-size: 12px; color: #666;">Địa chỉ: 123 Đường 3/2, Phường 11, Quận 10, TP. Hồ Chí Minh</div>
                <div style="font-size: 12px; color: #666;">Hotline: 1900 8888 • Website: vphone.vn</div>
            </div>
            <div style="text-align: right;">
                <h3 style="margin: 0; font-weight: 700; text-transform: uppercase;">PHIẾU XUẤT KHO</h3>
                <div style="font-size: 13px; font-weight: bold; color: #0066cc; margin-top: 4px;" id="pInvoiceId">#VP-0</div>
                <div style="font-size: 12px; color: #777;">Ngày in: <?= date('d/m/Y H:i') ?></div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 13px; line-height: 1.6;">
            <div style="width: 55%;">
                <div style="font-weight: bold; text-transform: uppercase; color: #444; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-bottom: 6px;">Thông tin khách hàng</div>
                <div>Khách hàng: <strong id="pCustomerName">--</strong></div>
                <div>Số điện thoại: <strong id="pCustomerPhone">--</strong></div>
                <div>Địa chỉ: <span id="pCustomerAddress">--</span></div>
            </div>
            <div style="width: 40%;">
                <div style="font-weight: bold; text-transform: uppercase; color: #444; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-bottom: 6px;">Thông tin đơn đặt</div>
                <div>Ngày đặt: <span id="pOrderDate">--</span></div>
                <div>Thanh toán: <strong id="pPaymentMethod">COD</strong></div>
                <div>Ghi chú: <span id="pOrderNote">--</span></div>
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left;">
                    <th style="padding: 10px; border: 1px solid #e2e8f0; width: 40px; text-align: center;">STT</th>
                    <th style="padding: 10px; border: 1px solid #e2e8f0;">Tên sản phẩm</th>
                    <th style="padding: 10px; border: 1px solid #e2e8f0; text-align: center; width: 70px;">SL</th>
                    <th style="padding: 10px; border: 1px solid #e2e8f0; text-align: right; width: 130px;">Đơn giá</th>
                    <th style="padding: 10px; border: 1px solid #e2e8f0; text-align: right; width: 140px;">Thành tiền</th>
                </tr>
            </thead>
            <tbody id="pItemsTbody">
                <!-- Render items print -->
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="padding: 10px; border: 1px solid #e2e8f0; text-align: right; font-weight: bold;">TỔNG TIỀN THANH TOÁN:</td>
                    <td style="padding: 10px; border: 1px solid #e2e8f0; text-align: right; font-weight: bold; color: #d00; font-size: 15px;" id="pTotalMoney">0 đ</td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 30px; display: flex; justify-content: space-between; text-align: center; font-size: 13px;">
            <div style="width: 45%;">
                <div style="font-weight: bold;">KHÁCH HÀNG KÝ NHẬN</div>
                <div style="font-size: 11px; color: #888;">(Ký và ghi rõ họ tên)</div>
                <div style="height: 70px;"></div>
                <div id="pCustomerSignName" style="font-weight: 500;">--</div>
            </div>
            <div style="width: 45%;">
                <div style="font-weight: bold;">NGƯỜI LẬP PHIẾU</div>
                <div style="font-size: 11px; color: #888;">(V-Phone Store xuất kho)</div>
                <div style="height: 70px;"></div>
                <div style="font-weight: 600;"><?= htmlspecialchars($_SESSION['user']['fullname']) ?></div>
            </div>
        </div>

        <div style="margin-top: 40px; padding-top: 14px; border-top: 1px dashed #ccc; font-size: 11px; color: #777; text-align: center;">
            <i class="fa-solid fa-shield-halved me-1"></i>Sản phẩm chính hãng được bảo hành 12 tháng tại hệ thống V-Phone Store toàn quốc. Đổi mới trong 30 ngày nếu phát sinh lỗi từ nhà sản xuất. Cảm ơn Quý khách!
        </div>
    </div>
</div>

<style>
.timeline-step {
    padding: 8px 4px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #94a3b8;
    transition: all 0.2s;
}
.timeline-step .step-icon {
    font-size: 1.25rem;
}
.timeline-step.active {
    background: #eff6ff;
    border-color: #0066cc;
    color: #0066cc;
}
.timeline-step.completed {
    background: #ecfdf5;
    border-color: #10b981;
    color: #10b981;
}
.timeline-step.canceled {
    background: #fef2f2;
    border-color: #ef4444;
    color: #ef4444;
}
</style>

<script>
// Xác nhận duyệt thanh toán VietQR bằng SweetAlert2 hiện đại (thay thế confirm mặc định)
function confirmVietQRPayment(orderId, customerName, totalMoney, transCode) {
    if (typeof Swal === 'undefined') {
        if (confirm('Xác nhận đã nhận đủ tiền chuyển khoản cho đơn #VP-' + orderId + '?')) {
            submitPaymentConfirmation(orderId);
        }
        return;
    }

    Swal.fire({
        title: 'Xác Nhận Đã Nhận Tiền?',
        html: `
            <div class="text-start p-3 rounded-4 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; font-size: 0.88rem;">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Mã đơn hàng:</span>
                    <strong class="text-primary font-monospace">#VP-${orderId}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Khách hàng:</span>
                    <strong class="text-dark">${customerName}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Tổng thanh toán:</span>
                    <strong class="text-danger fw-black">${totalMoney}</strong>
                </div>
                ${transCode ? `
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Mã GD VietQR:</span>
                    <span class="font-monospace text-success fw-bold">${transCode}</span>
                </div>` : ''}
            </div>
            <p class="text-secondary small mb-0">Sau khi xác nhận, đơn hàng sẽ chuyển sang trạng thái <strong>Đã thanh toán</strong> và lưu lịch sử giao dịch.</p>
        `,
        icon: 'question',
        iconColor: '#10b981',
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-circle-check me-1.5"></i>Xác Nhận Đã Nhận Đủ Tiền',
        cancelButtonText: 'Hủy Bỏ',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#94a3b8',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-4 shadow-lg border-0 p-4',
            title: 'fw-bold fs-5 text-dark mb-3',
            confirmButton: 'btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm',
            cancelButton: 'btn btn-light rounded-pill px-4 py-2 fw-bold text-secondary me-2'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            submitPaymentConfirmation(orderId);
        }
    });
}

function submitPaymentConfirmation(orderId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'orders.php' + window.location.search;
    
    const inputId = document.createElement('input');
    inputId.type = 'hidden';
    inputId.name = 'order_id';
    inputId.value = orderId;
    form.appendChild(inputId);

    const inputConfirm = document.createElement('input');
    inputConfirm.type = 'hidden';
    inputConfirm.name = 'confirm_payment';
    inputConfirm.value = '1';
    form.appendChild(inputConfirm);

    document.body.appendChild(form);
    form.submit();
}

// Tìm kiếm lọc đơn hàng theo từ khóa
function filterAdminOrders() {
    const q = document.getElementById('adminOrderSearch').value.toLowerCase().trim();
    document.querySelectorAll('.order-row').forEach(row => {
        const text = row.getAttribute('data-search') || '';
        if (!q || text.includes(q)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

let currentActiveOrder = null;

// Mở modal xem chi tiết đơn hàng
function viewOrderDetail(order) {
    currentActiveOrder = order;
    document.getElementById('mOrderId').textContent = '#VP-' + order.id;
    document.getElementById('mOrderDate').textContent = order.created_at;
    document.getElementById('mCustomerName').textContent = order.fullname;
    document.getElementById('mCustomerPhone').textContent = order.phone;
    document.getElementById('mCustomerAddress').textContent = order.address;
    document.getElementById('mPaymentMethod').textContent = order.payment_method;
    document.getElementById('mOrderNote').textContent = order.note || 'Không có';
    document.getElementById('mTotalMoney').textContent = new Intl.NumberFormat('vi-VN').format(order.total_money) + ' đ';

    document.getElementById('mFormOrderId').value = order.id;
    document.getElementById('mFormStatusSelect').value = order.status;

    // Cập nhật badge thanh toán & mã GD
    const pBadge = document.getElementById('mPaymentStatusBadge');
    const isPaid = (order.payment_status === 'Đã thanh toán');
    if (pBadge) {
        pBadge.textContent = order.payment_status || 'Chưa thanh toán';
        pBadge.className = 'badge rounded-pill px-2.5 py-1 ' + (isPaid ? 'badge-soft-success text-success' : 'badge-soft-warning text-dark');
    }
    const tcBox = document.getElementById('mTransCodeBox');
    const tcVal = document.getElementById('mTransCode');
    if (tcBox && tcVal) {
        if (order.transaction_code) {
            tcBox.style.display = 'block';
            tcVal.textContent = order.transaction_code;
        } else {
            tcBox.style.display = 'none';
        }
    }

    // Cập nhật badge trạng thái
    const badge = document.getElementById('mOrderStatusBadge');
    badge.textContent = order.status;
    badge.className = 'badge rounded-pill px-2.5 py-1 ' + (
        order.status === 'Chờ xử lý' ? 'badge-soft-warning' :
        order.status === 'Đang xử lý' ? 'badge-soft-primary' :
        order.status === 'Đang giao hàng' ? 'badge-soft-info' :
        order.status === 'Đã giao thành công' ? 'badge-soft-success' : 'badge-soft-danger'
    );

    // Cập nhật Stepper Timeline
    const steps = document.querySelectorAll('#orderTimeline .timeline-step');
    steps.forEach(s => s.className = 'timeline-step');
    const s0 = steps[0], s1 = steps[1], s2 = steps[2], s3 = steps[3];
    s0.classList.add('completed');

    if (order.status === 'Chờ xử lý' || order.status === 'Đang xử lý' || order.status === 'Chờ thanh toán') {
        s1.classList.add('active');
        s1.querySelector('small').textContent = (order.status === 'Đang xử lý') ? 'Đang chuẩn bị' : 'Chờ xử lý';
    } else if (order.status === 'Đang giao hàng') {
        s1.classList.add('completed');
        s2.classList.add('active');
    } else if (order.status === 'Đã giao thành công') {
        s1.classList.add('completed');
        s2.classList.add('completed');
        s3.classList.add('completed');
    } else {
        s1.classList.add('canceled');
        s3.classList.add('canceled');
        s3.querySelector('small').textContent = 'Đã hủy';
    }

    // Render danh sách sản phẩm
    const tbody = document.getElementById('mItemsTbody');
    tbody.innerHTML = '';
    if (order.items && order.items.length > 0) {
        order.items.forEach(it => {
            const tr = document.createElement('tr');
            const imgHtml = it.image ? `<img src="../${it.image}" alt="" class="rounded-2 border" style="width: 40px; height: 40px; object-fit: contain;">`
                                     : `<div class="rounded-2 bg-light border d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fa-solid fa-mobile-screen text-muted small"></i></div>`;
            const unitPrice = parseFloat(it.price) || 0;
            const lineTotal = parseFloat(it.total_price) || (unitPrice * (it.quantity || 1));

            tr.innerHTML = `
                <td>${imgHtml}</td>
                <td>
                    <div class="fw-bold text-dark small">${it.product_name}</div>
                </td>
                <td class="text-center fw-bold"><span class="badge bg-light text-dark border px-2 py-1">${it.quantity}</span></td>
                <td class="text-end small">${new Intl.NumberFormat('vi-VN').format(unitPrice)} đ</td>
                <td class="text-end fw-bold text-danger small">${new Intl.NumberFormat('vi-VN').format(lineTotal)} đ</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Gán nút in trong modal
    document.getElementById('mPrintBtn').onclick = function() {
        printOrderInvoice(order);
    };

    const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
    modal.show();
}

// In hóa đơn xuất kho
function printOrderInvoice(order) {
    document.getElementById('pInvoiceId').textContent = '#VP-' + order.id;
    document.getElementById('pCustomerName').textContent = order.fullname;
    document.getElementById('pCustomerSignName').textContent = order.fullname;
    document.getElementById('pCustomerPhone').textContent = order.phone;
    document.getElementById('pCustomerAddress').textContent = order.address;
    document.getElementById('pOrderDate').textContent = order.created_at;
    document.getElementById('pPaymentMethod').textContent = order.payment_method;
    document.getElementById('pOrderNote').textContent = order.note || 'Giao giờ hành chính';
    document.getElementById('pTotalMoney').textContent = new Intl.NumberFormat('vi-VN').format(order.total_money) + ' đ';

    const pTbody = document.getElementById('pItemsTbody');
    pTbody.innerHTML = '';
    if (order.items && order.items.length > 0) {
        order.items.forEach((it, idx) => {
            const tr = document.createElement('tr');
            const unitPrice = parseFloat(it.price) || 0;
            const lineTotal = parseFloat(it.total_price) || (unitPrice * (it.quantity || 1));
            tr.innerHTML = `
                <td style="padding: 8px; border: 1px solid #e2e8f0; text-align: center;">${idx + 1}</td>
                <td style="padding: 8px; border: 1px solid #e2e8f0; font-weight: 500;">${it.product_name}</td>
                <td style="padding: 8px; border: 1px solid #e2e8f0; text-align: center;">${it.quantity}</td>
                <td style="padding: 8px; border: 1px solid #e2e8f0; text-align: right;">${new Intl.NumberFormat('vi-VN').format(unitPrice)} đ</td>
                <td style="padding: 8px; border: 1px solid #e2e8f0; text-align: right; font-weight: bold;">${new Intl.NumberFormat('vi-VN').format(lineTotal)} đ</td>
            `;
            pTbody.appendChild(tr);
        });
    }

    // Kích hoạt cửa sổ in trình duyệt
    window.print();
}

// Mở modal xóa đơn hàng có lưu lý do và thông báo cho khách hàng
function openOrderDeleteModal(orderId, customerName, customerPhone, totalMoney) {
    document.getElementById('delModalOrderId').textContent = '#VP-' + orderId;
    document.getElementById('delModalInputOrderId').value = orderId;
    document.getElementById('delModalCustomerName').textContent = customerName;
    document.getElementById('delModalCustomerPhone').textContent = customerPhone;
    document.getElementById('delModalTotalMoney').textContent = totalMoney;
    
    const modalEl = document.getElementById('adminOrderDeleteModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function setCancelReason(reason) {
    document.getElementById('delModalCancelReason').value = reason;
}
</script>

<?php require_once 'includes/footer.php'; ?>
