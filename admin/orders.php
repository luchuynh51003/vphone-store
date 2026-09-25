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

if (isset($_GET['delete_order'])) {
    $orderId = (int)$_GET['delete_order'];
    $del = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $del->execute([$orderId]);
    $message = "Đã xóa đơn hàng #VP-$orderId thành công!";
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Quản Lý Đơn Hàng</h3>
        <p class="text-secondary small mb-0">Duyệt đơn, xem chi tiết màu sắc, dung lượng và địa chỉ khách nhận hàng</p>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 py-2" role="alert">
        <i class="fa-solid fa-check me-2"></i><?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 rounded-4 shadow-sm bg-white p-4">
    <?php if (empty($orders)): ?>
        <div class="text-center py-5 text-muted">Chưa có đơn hàng nào trong hệ thống!</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Khách Hàng</th>
                        <th>Chi Tiết Máy & Màu Sắc Khách Chọn</th>
                        <th>Tổng Tiền</th>
                        <th>Trạng Thái</th>
                        <th>Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <?php 
                            // Lấy danh sách máy có trong đơn kèm màu và dung lượng
                            $items = $pdo->prepare("SELECT * FROM order_details WHERE order_id = ?");
                            $items->execute([$o['id']]);
                            $details = $items->fetchAll();
                        ?>
                        <tr>
                            <td class="fw-bold text-primary">#VP-<?= $o['id'] ?></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($o['fullname']) ?></div>
                                <small class="text-secondary d-block"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($o['phone']) ?></small>
                                <small class="text-muted d-block text-truncate" style="max-width: 180px;"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($o['address']) ?></small>
                            </td>
                            <td>
                                <?php foreach ($details as $d): ?>
                                    <div class="mb-1">
                                        <span class="fw-bold small text-dark">• <?= htmlspecialchars($d['product_name']) ?></span>
                                        <span class="badge bg-light text-primary border">SL: <?= $d['quantity'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td class="text-danger fw-bold fs-6"><?= number_format($o['total_money'], 0, ',', '.') ?> đ</td>
                            <td>
                                <form method="POST" action="orders.php" class="d-flex align-items-center">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status" class="form-select form-select-sm rounded-pill fw-bold" onchange="this.form.submit()">
                                        <option value="Chờ xử lý" <?= $o['status'] === 'Chờ xử lý' ? 'selected' : '' ?>>⏳ Chờ xử lý</option>
                                        <option value="Đang giao hàng" <?= $o['status'] === 'Đang giao hàng' ? 'selected' : '' ?>>🚚 Đang giao hàng</option>
                                        <option value="Đã giao thành công" <?= $o['status'] === 'Đã giao thành công' ? 'selected' : '' ?>>✅ Đã giao thành công</option>
                                        <option value="Đã hủy" <?= $o['status'] === 'Đã hủy' ? 'selected' : '' ?>>❌ Đã hủy</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td>
                                <a href="orders.php?delete_order=<?= $o['id'] ?>" class="btn btn-outline-danger btn-sm rounded-circle" onclick="return confirm('Bạn có chắc muốn xóa đơn này?');" title="Xóa đơn">
                                    <i class="fa-solid fa-trash-can"></i>
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
