<?php
$pageTitle = 'Quản Lý Đơn Hàng - V-Phone Admin';
require_once 'includes/header.php';

$message = '';

if (isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = trim($_POST['status']);
    $up = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $up->execute([$newStatus, $orderId]);
    $message = "Đã cập nhật trạng thái đơn hàng #VP-$orderId thành công!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $del = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $del->execute([$orderId]);
    $message = "Đã xóa đơn hàng #VP-$orderId thành công!";
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
$detailsByOrder = [];
if ($orders) {
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $detailsStmt = $pdo->prepare("SELECT order_id, product_name, quantity FROM order_details WHERE order_id IN ($placeholders) ORDER BY id");
    $detailsStmt->execute($orderIds);
    foreach ($detailsStmt->fetchAll() as $detail) {
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

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Quản Lý Đơn Hàng</h3>
        <p class="text-secondary small mb-0">Theo dõi, duyệt giao hàng và kiểm tra lịch sử đặt mua của khách hàng</p>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 py-2 mb-4" role="alert">
        <i class="fa-solid fa-check me-2"></i><?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- BỘ LỌC TRẠNG THÁI & TÌM KIẾM -->
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
    <div class="input-group input-group-sm" style="width: 220px;">
        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
        <input type="text" id="adminOrderSearch" class="form-control border-start-0" placeholder="Tìm tên, SĐT..." onkeyup="filterAdminOrders()">
    </div>
</div>

<div class="admin-table-card">
    <?php if (empty($filteredOrders)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-inbox fs-1 text-secondary opacity-50 mb-3 d-block"></i>
            Không có đơn hàng nào phù hợp với bộ lọc!
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0" id="adminOrdersTable">
                <thead>
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Khách Hàng</th>
                        <th>Chi Tiết Sản Phẩm Khách Mua</th>
                        <th>Tổng Tiền</th>
                        <th>Phương Thức</th>
                        <th>Trạng Thái Xử Lý</th>
                        <th class="text-end">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredOrders as $o): ?>
                        <?php $details = $detailsByOrder[$o['id']] ?? []; ?>
                        <tr class="order-row" data-search="<?= htmlspecialchars(mb_strtolower($o['fullname'] . ' ' . $o['phone'] . ' ' . $o['id'], 'UTF-8')) ?>">
                            <td class="fw-bold text-primary fs-6">#VP-<?= $o['id'] ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($o['fullname']) ?></div>
                                <small class="text-secondary d-block"><i class="fa-solid fa-phone me-1 text-muted"></i><?= htmlspecialchars($o['phone']) ?></small>
                                <small class="text-muted d-block text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($o['address']) ?>">
                                    <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($o['address']) ?>
                                </small>
                            </td>
                            <td style="min-width: 260px;">
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
                                <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small">
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
                            <td class="text-end">
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

<script>
function filterAdminOrders() {
    const q = document.getElementById('adminOrderSearch').value.toLowerCase().trim();
    document.querySelectorAll('.order-row').forEach(row => {
        const text = row.getAttribute('data-search');
        if (!q || text.includes(q)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
