<?php
require_once '../app/models/VoucherModel.php';
require_once '../config/database.php';

$pageTitle = 'Quản Lý Voucher - V-Phone Admin';
require_once 'includes/header.php';
$voucherModel = new VoucherModel($pdo);
$voucherModel->ensureTable();
$message = '';
$editId = (int)($_GET['edit_id'] ?? 0);
$editingVoucher = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_voucher'])) {
    $voucherId = (int)($_POST['voucher_id'] ?? 0);
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = $_POST['discount_type'] ?? '';
    $value = (int)($_POST['discount_value'] ?? 0);
    $minimumOrder = (int)($_POST['minimum_order'] ?? 0);
    $maxDiscount = trim($_POST['max_discount'] ?? '');
    $usageLimit = trim($_POST['usage_limit'] ?? '');
    $startsAt = trim($_POST['starts_at'] ?? '');
    $expiresAt = trim($_POST['expires_at'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($code === '' || !in_array($type, ['percent', 'fixed'], true) || $value <= 0 || $minimumOrder < 0) {
        $message = 'Kiểm tra lại mã và giá trị voucher.';
    } else {
        $startsAt = $startsAt !== '' ? str_replace('T', ' ', $startsAt) . ':00' : null;
        $expiresAt = $expiresAt !== '' ? str_replace('T', ' ', $expiresAt) . ':00' : null;
        $maxDiscountValue = $maxDiscount !== '' ? max(0, (int)$maxDiscount) : null;
        $usageLimitValue = $usageLimit !== '' ? max(1, (int)$usageLimit) : null;

        try {
            if ($voucherId > 0) {
                $stmt = $pdo->prepare('UPDATE vouchers SET code = ?, discount_type = ?, discount_value = ?, minimum_order = ?, max_discount = ?, starts_at = ?, expires_at = ?, usage_limit = ?, is_active = ? WHERE id = ?');
                $stmt->execute([$code, $type, $value, $minimumOrder, $maxDiscountValue, $startsAt, $expiresAt, $usageLimitValue, $isActive, $voucherId]);
                $message = 'Đã cập nhật voucher.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO vouchers (code, discount_type, discount_value, minimum_order, max_discount, starts_at, expires_at, usage_limit, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$code, $type, $value, $minimumOrder, $maxDiscountValue, $startsAt, $expiresAt, $usageLimitValue, $isActive]);
                $message = 'Đã tạo voucher.';
            }
            $editId = 0;
        } catch (PDOException $error) {
            $message = 'Không thể lưu voucher. Mã có thể đã được sử dụng.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_voucher'])) {
    $stmt = $pdo->prepare('DELETE FROM vouchers WHERE id = ?');
    $stmt->execute([(int)($_POST['voucher_id'] ?? 0)]);
    $message = 'Đã xóa voucher.';
    $editId = 0;
}

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM vouchers WHERE id = ?');
    $stmt->execute([$editId]);
    $editingVoucher = $stmt->fetch() ?: null;
}

$vouchers = $pdo->query('SELECT * FROM vouchers ORDER BY id DESC')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Quản lý voucher</h3>
        <p class="text-secondary small mb-0">Tạo mã, đặt mức giảm, điều kiện đơn hàng, thời hạn và lượt sử dụng</p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
<?php endif; ?>

<section class="card border-0 rounded-4 shadow-sm p-4 mb-4 bg-white">
    <div class="d-flex align-items-center gap-2 mb-3">
        <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="fa-solid fa-ticket"></i>
        </div>
        <h5 class="fw-bold mb-0 text-dark"><?= $editingVoucher ? 'Chỉnh sửa mã voucher' : 'Tạo mã voucher mới' ?></h5>
    </div>
    <form method="POST" action="vouchers.php<?= $editingVoucher ? '?edit_id=' . (int)$editingVoucher['id'] : '' ?>">
        <input type="hidden" name="voucher_id" value="<?= (int)($editingVoucher['id'] ?? 0) ?>">
        <div class="row g-3">
            <div class="col-md-3">
                <label for="voucherCode" class="form-label small fw-semibold">Mã voucher</label>
                <input id="voucherCode" name="code" class="form-control rounded-3 text-uppercase fw-bold text-primary" maxlength="40" required value="<?= htmlspecialchars($editingVoucher['code'] ?? '') ?>" placeholder="VD: SALE10">
            </div>
            <div class="col-md-2">
                <label for="voucherType" class="form-label small fw-semibold">Loại giảm</label>
                <select id="voucherType" name="discount_type" class="form-select rounded-3">
                    <option value="percent" <?= ($editingVoucher['discount_type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>Phần trăm (%)</option>
                    <option value="fixed" <?= ($editingVoucher['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Số tiền (đ)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="voucherValue" class="form-label small fw-semibold">Mức giảm</label>
                <input id="voucherValue" type="number" name="discount_value" class="form-control rounded-3 fw-bold" min="1" required value="<?= (int)($editingVoucher['discount_value'] ?? 10) ?>">
            </div>
            <div class="col-md-2">
                <label for="voucherMin" class="form-label small fw-semibold">Đơn tối thiểu (đ)</label>
                <input id="voucherMin" type="number" name="minimum_order" class="form-control rounded-3" min="0" value="<?= (int)($editingVoucher['minimum_order'] ?? 0) ?>">
            </div>
            <div class="col-md-3">
                <label for="voucherMax" class="form-label small fw-semibold">Giảm tối đa (đ, tùy chọn)</label>
                <input id="voucherMax" type="number" name="max_discount" class="form-control rounded-3" min="0" value="<?= htmlspecialchars((string)($editingVoucher['max_discount'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label for="voucherStarts" class="form-label small fw-semibold">Bắt đầu hiệu lực</label>
                <input id="voucherStarts" type="datetime-local" name="starts_at" class="form-control rounded-3" value="<?= !empty($editingVoucher['starts_at']) ? date('Y-m-d\\TH:i', strtotime($editingVoucher['starts_at'])) : '' ?>">
            </div>
            <div class="col-md-3">
                <label for="voucherExpires" class="form-label small fw-semibold">Hết hạn</label>
                <input id="voucherExpires" type="datetime-local" name="expires_at" class="form-control rounded-3" value="<?= !empty($editingVoucher['expires_at']) ? date('Y-m-d\\TH:i', strtotime($editingVoucher['expires_at'])) : '' ?>">
            </div>
            <div class="col-md-3">
                <label for="voucherLimit" class="form-label small fw-semibold">Giới hạn lượt (trống = vô hạn)</label>
                <input id="voucherLimit" type="number" name="usage_limit" class="form-control rounded-3" min="1" value="<?= htmlspecialchars((string)($editingVoucher['usage_limit'] ?? '')) ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="voucherActive" <?= !isset($editingVoucher['is_active']) || $editingVoucher['is_active'] ? 'checked' : '' ?>>
                    <label for="voucherActive" class="form-check-label fw-bold small text-dark">Đang kích hoạt</label>
                </div>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button type="submit" name="save_voucher" value="1" class="btn btn-primary rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-floppy-disk me-1"></i><?= $editingVoucher ? 'Lưu thay đổi' : 'Tạo voucher' ?>
            </button>
            <?php if ($editingVoucher): ?>
                <a href="vouchers.php" class="btn btn-light rounded-pill px-4">Hủy sửa</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<div class="admin-table-card">
    <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-ticket-simple text-primary"></i> Danh Sách Mã Khuyến Mãi
            </h5>
            <small class="text-secondary">Tổng cộng <?= count($vouchers) ?> mã trong cơ sở dữ liệu</small>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Mã Voucher</th>
                    <th>Mức Giảm</th>
                    <th>Đơn Tối Thiểu</th>
                    <th>Lượt Dùng</th>
                    <th>Hạn Dùng</th>
                    <th>Trạng Thái</th>
                    <th class="text-end">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vouchers as $voucher): ?>
                    <tr>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 fw-bold font-monospace fs-6">
                                <?= htmlspecialchars($voucher['code']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold text-danger fs-6">
                                <?= $voucher['discount_type'] === 'percent' ? (int)$voucher['discount_value'] . '%' : number_format($voucher['discount_value'], 0, ',', '.') . ' đ' ?>
                            </div>
                            <?php if ($voucher['max_discount'] !== null): ?>
                                <small class="text-muted d-block">Tối đa <?= number_format($voucher['max_discount'], 0, ',', '.') ?> đ</small>
                            <?php endif; ?>
                        </td>
                        <td><span class="fw-medium text-dark"><?= number_format($voucher['minimum_order'], 0, ',', '.') ?> đ</span></td>
                        <td>
                            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1">
                                <?= (int)$voucher['used_count'] ?><?= $voucher['usage_limit'] !== null ? ' / ' . (int)$voucher['usage_limit'] : '' ?>
                            </span>
                        </td>
                        <td class="small text-secondary"><?= $voucher['expires_at'] ? htmlspecialchars($voucher['expires_at']) : 'Vô thời hạn' ?></td>
                        <td>
                            <?php if ($voucher['is_active']): ?>
                                <span class="badge badge-soft-success rounded-pill px-3 py-1">Đang bật</span>
                            <?php else: ?>
                                <span class="badge badge-soft-secondary rounded-pill px-3 py-1">Đã tắt</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="vouchers.php?edit_id=<?= (int)$voucher['id'] ?>" class="btn btn-sm btn-light border text-primary rounded-pill px-2 py-1 me-1" aria-label="Sửa voucher <?= htmlspecialchars($voucher['code']) ?>">
                                <i class="fa-solid fa-pen"></i> Sửa
                            </a>
                            <form method="POST" action="vouchers.php" class="d-inline" id="deleteVoucherForm<?= (int)$voucher['id'] ?>">
                                <input type="hidden" name="voucher_id" value="<?= (int)$voucher['id'] ?>">
                                <input type="hidden" name="delete_voucher" value="1">
                            </form>
                            <button type="button" class="btn btn-sm btn-light border text-danger rounded-pill px-2 py-1" aria-label="Xóa voucher <?= htmlspecialchars($voucher['code']) ?>" data-bs-toggle="modal" data-bs-target="#adminDeleteConfirmModal" data-confirm-form="deleteVoucherForm<?= (int)$voucher['id'] ?>" data-confirm-message="Xóa voucher <?= htmlspecialchars($voucher['code']) ?>?">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
