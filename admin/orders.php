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
    fputcsv($output, ['Mã đơn', 'Ngày đặt', 'Khách hàng', 'Số điện thoại', 'Địa chỉ', 'Ghi chú', 'Tổng tiền (VNĐ)', 'Phương thức thanh toán', 'Trạng thái']);
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
            $o['status']
        ]);
    }
    fclose($output);
    exit;
}

$pageTitle = 'Quản Lý Đơn Hàng - V-Phone Admin';
require_once 'includes/header.php';

$message = '';

if (isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = trim($_POST['status']);
    $up = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $up->execute([$newStatus, $orderId]);
    $message = "Đã cập nhật trạng thái đơn hàng #VP-$orderId thành '$newStatus'!";
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['delete_order'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $del = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $del->execute([$orderId]);
    $message = "Đã xóa đơn hàng #VP-$orderId thành công!";
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
    'Đang giao hàng' => 0,
    'Đã giao thành công' => 0,
    'Đã hủy' => 0
];
foreach ($orders as $o) {
    if (isset($statusCounts[$o['status']])) {
        $statusCounts[$o['status']]++;
    }
}

$filteredOrders = array_filter($orders, function($o) use ($filterStatus) {
    if ($filterStatus === 'all') return true;
    return $o['status'] === $filterStatus;
});
?>

<!-- TIÊU ĐỀ & NÚT THAO TÁC -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-black text-dark mb-1">Quản Lý Đơn Hàng</h3>
        <p class="text-secondary small mb-0">Theo dõi, duyệt giao hàng, xem chi tiết và in phiếu xuất kho V-Phone Flagship</p>
    </div>
    <div class="d-flex gap-2">
        <a href="orders.php?export=csv" class="btn btn-outline-success btn-sm rounded-pill fw-bold px-3">
            <i class="fa-solid fa-file-excel me-1.5"></i>Xuất Excel (CSV)
        </a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 py-2.5 px-3 mb-4 shadow-sm" role="alert">
        <i class="fa-solid fa-check-circle me-2 text-success"></i><?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- BỘ LỌC TRẠNG THÁI & TÌM KIẾM NHANH -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="d-flex flex-wrap gap-2">
        <a href="orders.php?status=all" class="btn btn-sm rounded-pill fw-bold px-3 <?= $filterStatus === 'all' ? 'btn-primary' : 'btn-light border text-secondary' ?>">
            Tất cả <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $statusCounts['all'] ?></span>
        </a>
        <a href="orders.php?status=Chờ xử lý" class="btn btn-sm rounded-pill fw-bold px-3 <?= $filterStatus === 'Chờ xử lý' ? 'btn-warning text-dark' : 'btn-light border text-secondary' ?>">
            ⏳ Chờ xử lý <span class="badge bg-dark text-white ms-1 rounded-pill"><?= $statusCounts['Chờ xử lý'] ?></span>
        </a>
        <a href="orders.php?status=Đang giao hàng" class="btn btn-sm rounded-pill fw-bold px-3 <?= $filterStatus === 'Đang giao hàng' ? 'btn-info text-white' : 'btn-light border text-secondary' ?>">
            🚚 Đang giao <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $statusCounts['Đang giao hàng'] ?></span>
        </a>
        <a href="orders.php?status=Đã giao thành công" class="btn btn-sm rounded-pill fw-bold px-3 <?= $filterStatus === 'Đã giao thành công' ? 'btn-success' : 'btn-light border text-secondary' ?>">
            ✅ Đã giao <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $statusCounts['Đã giao thành công'] ?></span>
        </a>
        <a href="orders.php?status=Đã hủy" class="btn btn-sm rounded-pill fw-bold px-3 <?= $filterStatus === 'Đã hủy' ? 'btn-danger' : 'btn-light border text-secondary' ?>">
            ❌ Đã hủy <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $statusCounts['Đã hủy'] ?></span>
        </a>
    </div>
    <div class="input-group input-group-sm" style="width: 250px;">
        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
        <input type="text" id="adminOrderSearch" class="form-control border-start-0" placeholder="Tìm tên, SĐT, mã đơn..." onkeyup="filterAdminOrders()">
    </div>
</div>

<!-- BẢNG DANH SÁCH ĐƠN HÀNG -->
<div class="admin-table-card">
    <?php if (empty($filteredOrders)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-inbox fs-1 text-secondary opacity-50 mb-3 d-block"></i>
            Không tìm thấy đơn hàng nào phù hợp với bộ lọc!
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0" id="adminOrdersTable">
                <thead>
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Khách Hàng</th>
                        <th>Chi Tiết Mặt Hàng</th>
                        <th>Tổng Tiền</th>
                        <th>Phương Thức</th>
                        <th>Trạng Thái</th>
                        <th class="text-end">Thao Tác</th>
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
                                'status' => $o['status'],
                                'created_at' => $o['created_at'],
                                'items' => $details
                            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr class="order-row" data-search="<?= htmlspecialchars(mb_strtolower($o['fullname'] . ' ' . $o['phone'] . ' ' . $o['id'] . ' ' . $o['address'], 'UTF-8')) ?>">
                            <td class="fw-bold text-primary fs-6">
                                #VP-<?= $o['id'] ?>
                                <small class="text-muted d-block fw-normal" style="font-size: 0.72rem;"><?= date('H:i d/m', strtotime($o['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($o['fullname']) ?></div>
                                <small class="text-secondary d-block"><i class="fa-solid fa-phone me-1 text-muted"></i><?= htmlspecialchars($o['phone']) ?></small>
                                <small class="text-muted d-block text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($o['address']) ?>">
                                    <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($o['address']) ?>
                                </small>
                            </td>

                            <td style="min-width: 240px;">
                                <?php foreach ($details as $d): ?>
                                    <div class="mb-1 d-flex align-items-center gap-2">
                                        <span class="fw-bold small text-dark">• <?= htmlspecialchars($d['product_name']) ?></span>
                                        <span class="badge badge-soft-primary rounded-pill" style="font-size: 0.72rem;">x<?= $d['quantity'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <div class="text-danger fw-black fs-6"><?= number_format($o['total_money'], 0, ',', '.') ?> đ</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small">
                                    <?= htmlspecialchars($o['payment_method']) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="orders.php" class="d-flex align-items-center">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status" class="form-select form-select-sm rounded-pill fw-bold border-0 shadow-sm <?php
                                        if ($o['status'] === 'Chờ xử lý') echo 'bg-warning bg-opacity-25 text-dark';
                                        elseif ($o['status'] === 'Đang giao hàng') echo 'bg-info bg-opacity-25 text-dark';
                                        elseif ($o['status'] === 'Đã giao thành công') echo 'bg-success bg-opacity-25 text-success';
                                        else echo 'bg-danger bg-opacity-25 text-danger';
                                    ?>" onchange="this.form.submit()">
                                        <option value="Chờ xử lý" <?= $o['status'] === 'Chờ xử lý' ? 'selected' : '' ?>>⏳ Chờ xử lý</option>
                                        <option value="Đang giao hàng" <?= $o['status'] === 'Đang giao hàng' ? 'selected' : '' ?>>🚚 Đang giao hàng</option>
                                        <option value="Đã giao thành công" <?= $o['status'] === 'Đã giao thành công' ? 'selected' : '' ?>>✅ Đã giao thành công</option>
                                        <option value="Đã hủy" <?= $o['status'] === 'Đã hủy' ? 'selected' : '' ?>>❌ Đã hủy</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td class="text-end text-nowrap">
                                <!-- Nút xem chi tiết đơn hàng dạng Popup -->
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 fw-bold me-1" onclick="viewOrderDetail(<?= $orderDataJson ?>)" title="Xem chi tiết đơn hàng">
                                    <i class="fa-solid fa-eye me-1"></i>Chi tiết
                                </button>

                                <!-- Nút in nhanh hóa đơn -->
                                <button type="button" class="btn btn-sm btn-light border rounded-circle me-1" style="width: 32px; height: 32px; padding: 0;" onclick="printOrderInvoice(<?= $orderDataJson ?>)" title="In phiếu xuất kho">
                                    <i class="fa-solid fa-print text-secondary"></i>
                                </button>

                                <!-- Nút xóa đơn hàng -->
                                <form method="POST" action="orders.php" class="d-inline" id="deleteOrderForm<?= (int)$o['id'] ?>">
                                    <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                    <input type="hidden" name="delete_order" value="1">
                                </form>
                                <button type="button" class="btn btn-sm btn-light border text-danger rounded-circle" style="width: 32px; height: 32px; padding: 0;" title="Xóa đơn" aria-label="Xóa đơn VP-<?= (int)$o['id'] ?>" data-bs-toggle="modal" data-bs-target="#adminDeleteConfirmModal" data-confirm-form="deleteOrderForm<?= (int)$o['id'] ?>" data-confirm-message="Xóa vĩnh viễn đơn hàng #VP-<?= (int)$o['id'] ?>?">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
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
                                <div class="step-icon mb-1"><i class="fa-solid fa-box-archive"></i></div>
                                <small class="fw-bold d-block">Chờ xử lý</small>
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
                <form method="POST" action="orders.php" class="p-3 bg-light rounded-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <input type="hidden" name="order_id" id="mFormOrderId" value="">
                    <input type="hidden" name="update_status" value="1">
                    <div class="d-flex align-items-center gap-2">
                        <label class="fw-bold small text-dark">Cập nhật trạng thái:</label>
                        <select name="status" id="mFormStatusSelect" class="form-select form-select-sm rounded-pill fw-bold" style="width: 200px;">
                            <option value="Chờ xử lý">⏳ Chờ xử lý</option>
                            <option value="Đang giao hàng">🚚 Đang giao hàng</option>
                            <option value="Đã giao thành công">✅ Đã giao thành công</option>
                            <option value="Đã hủy">❌ Đã hủy</option>
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

    // Cập nhật badge trạng thái
    const badge = document.getElementById('mOrderStatusBadge');
    badge.textContent = order.status;
    badge.className = 'badge rounded-pill px-2.5 py-1 ' + (
        order.status === 'Chờ xử lý' ? 'badge-soft-warning' :
        order.status === 'Đang giao hàng' ? 'badge-soft-primary' :
        order.status === 'Đã giao thành công' ? 'badge-soft-success' : 'badge-soft-danger'
    );

    // Cập nhật Stepper Timeline
    const steps = document.querySelectorAll('#orderTimeline .timeline-step');
    steps.forEach(s => s.className = 'timeline-step');
    const s0 = steps[0], s1 = steps[1], s2 = steps[2], s3 = steps[3];
    s0.classList.add('completed');

    if (order.status === 'Chờ xử lý') {
        s1.classList.add('active');
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
</script>

<?php require_once 'includes/footer.php'; ?>
