<?php
$pageTitle = 'Quản Lý Thương Hiệu - V-Phone Admin';
require_once 'includes/header.php';

$message = '';
$editId = (int)($_GET['edit_id'] ?? 0);
$editingBrand = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_brand'])) {
    $brandId = (int)($_POST['brand_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $message = 'Vui lòng nhập tên thương hiệu.';
    } else {
        try {
            if ($brandId > 0) {
                $stmt = $pdo->prepare('UPDATE brands SET name = ? WHERE id = ?');
                $stmt->execute([$name, $brandId]);
                $message = 'Đã cập nhật thương hiệu.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO brands (name) VALUES (?)');
                $stmt->execute([$name]);
                $message = 'Đã thêm thương hiệu.';
            }
            $editId = 0;
        } catch (PDOException $error) {
            $message = 'Không thể lưu thương hiệu. Tên có thể đã tồn tại.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_brand'])) {
    $brandId = (int)($_POST['brand_id'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM brands WHERE id = ?');
    $stmt->execute([$brandId]);
    $message = 'Đã xóa thương hiệu. Sản phẩm liên quan được chuyển về trạng thái chưa gán hãng.';
    $editId = 0;
}

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM brands WHERE id = ?');
    $stmt->execute([$editId]);
    $editingBrand = $stmt->fetch() ?: null;
}

$brands = $pdo->query('SELECT b.id, b.name, COUNT(p.id) AS product_count FROM brands b LEFT JOIN products p ON p.brand_id = b.id GROUP BY b.id, b.name ORDER BY b.name')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Quản lý thương hiệu</h3>
        <p class="text-secondary small mb-0">Cập nhật hãng hiển thị trên sản phẩm và bộ lọc cửa hàng</p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <section class="card border-0 rounded-4 shadow-sm p-4 bg-white">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-tags"></i>
                </div>
                <h5 class="fw-bold mb-0 text-dark"><?= $editingBrand ? 'Sửa thương hiệu' : 'Thêm thương hiệu mới' ?></h5>
            </div>
            <form method="POST" action="brands.php<?= $editingBrand ? '?edit_id=' . (int)$editingBrand['id'] : '' ?>">
                <input type="hidden" name="brand_id" value="<?= (int)($editingBrand['id'] ?? 0) ?>">
                <label for="brandName" class="form-label small fw-semibold">Tên thương hiệu</label>
                <input type="text" id="brandName" name="name" class="form-control rounded-3 mb-3" maxlength="50" required value="<?= htmlspecialchars($editingBrand['name'] ?? '') ?>" placeholder="Ví dụ: Apple, Samsung, Sony...">
                <div class="d-flex gap-2">
                    <button type="submit" name="save_brand" value="1" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-floppy-disk me-1"></i><?= $editingBrand ? 'Lưu thay đổi' : 'Thêm hãng' ?>
                    </button>
                    <?php if ($editingBrand): ?>
                        <a href="brands.php" class="btn btn-light rounded-pill px-3">Hủy sửa</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </div>
    <div class="col-lg-8">
        <div class="admin-table-card">
            <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-layer-group text-primary"></i> Danh Sách Hãng Sản Xuất
                    </h5>
                    <small class="text-secondary">Tổng cộng <?= count($brands) ?> thương hiệu trong hệ thống</small>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tên Thương Hiệu</th>
                            <th>Số Lượng Sản Phẩm</th>
                            <th class="text-end">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brands as $brand): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-soft-primary rounded-pill px-3 py-2 fw-bold fs-6">
                                        <?= htmlspecialchars($brand['name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border rounded-pill px-3 py-1">
                                        <?= (int)$brand['product_count'] ?> dòng máy
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="brands.php?edit_id=<?= (int)$brand['id'] ?>" class="btn btn-sm btn-light border text-primary rounded-pill px-2 py-1 me-1" title="Sửa thương hiệu" aria-label="Sửa <?= htmlspecialchars($brand['name']) ?>">
                                        <i class="fa-solid fa-pen"></i> Sửa
                                    </a>
                                    <form method="POST" action="brands.php" class="d-inline" id="deleteBrandForm<?= (int)$brand['id'] ?>">
                                        <input type="hidden" name="brand_id" value="<?= (int)$brand['id'] ?>">
                                        <input type="hidden" name="delete_brand" value="1">
                                    </form>
                                    <button type="button" class="btn btn-sm btn-light border text-danger rounded-pill px-2 py-1" title="Xóa thương hiệu" aria-label="Xóa <?= htmlspecialchars($brand['name']) ?>" data-bs-toggle="modal" data-bs-target="#adminDeleteConfirmModal" data-confirm-form="deleteBrandForm<?= (int)$brand['id'] ?>" data-confirm-message="Xóa thương hiệu <?= htmlspecialchars($brand['name']) ?>? Sản phẩm liên quan sẽ không bị xóa.">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
