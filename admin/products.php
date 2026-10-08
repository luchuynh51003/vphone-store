<?php
$pageTitle = 'Quản Lý Sản Phẩm - V-Phone Admin';
require_once 'includes/header.php';

$message = '';
$editId = (int)($_GET['edit_id'] ?? 0);
$editingProduct = null;

// 1. XỬ LÝ XÓA SẢN PHẨM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $delId = (int)($_POST['product_id'] ?? 0);
    $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $del->execute([$delId]);
    $message = 'Đã xóa sản phẩm thành công!';
}

// Thêm hoặc cập nhật thông tin sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $brand_id = (int)$_POST['brand_id'];
    $price = (float)$_POST['price'];
    $sale_price = (float)$_POST['sale_price'];
    $image = trim($_POST['image'] ?? 'assets/images/products/iphone-16-promax.png');
    $screen = trim($_POST['screen'] ?? '6.7 inch AMOLED');
    $cpu = trim($_POST['cpu'] ?? 'Snapdragon / Apple Bionic');
    $ram = trim($_POST['ram'] ?? '8 GB');
    $rom = trim($_POST['rom'] ?? '256 GB');
    $battery = trim($_POST['battery'] ?? '5000 mAh');
    $colors = trim($_POST['colors'] ?? 'Đen, Trắng');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_used = isset($_POST['is_used']) ? 1 : 0;
    $condition_desc = $is_used ? 'Đẹp 99%' : 'Mới 100%';

    if (!empty($name) && $price > 0) {
        if ($productId > 0) {
            $stmt = $pdo->prepare("UPDATE products SET brand_id = ?, name = ?, price = ?, sale_price = ?, image = ?, screen = ?, cpu = ?, ram = ?, rom = ?, battery = ?, colors = ?, is_featured = ?, is_used = ?, condition_desc = ? WHERE id = ?");
            $stmt->execute([$brand_id, $name, $price, $sale_price, $image, $screen, $cpu, $ram, $rom, $battery, $colors, $is_featured, $is_used, $condition_desc, $productId]);
            $message = 'Đã cập nhật sản phẩm.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (brand_id, name, price, sale_price, image, screen, cpu, ram, rom, battery, colors, is_featured, is_used, condition_desc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$brand_id, $name, $price, $sale_price, $image, $screen, $cpu, $ram, $rom, $battery, $colors, $is_featured, $is_used, $condition_desc]);
            $message = 'Đã thêm điện thoại mới thành công!';
        }
        $editId = 0;
    }
}

// Lấy danh sách sản phẩm và thương hiệu
$brands = $pdo->query("SELECT * FROM brands ORDER BY id ASC")->fetchAll();
$products = $pdo->query("SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id ORDER BY p.id DESC")->fetchAll();
if ($editId > 0) {
    $editStmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $editStmt->execute([$editId]);
    $editingProduct = $editStmt->fetch() ?: null;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Quản Lý Danh Sách Điện Thoại</h3>
        <p class="text-secondary small mb-0">Thêm mới, sửa giá bán, cập nhật cấu hình và xóa sản phẩm</p>
    </div>
    <button class="btn btn-primary rounded-pill fw-bold shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#addProductModal">
        <i class="fa-solid fa-plus me-2"></i>Thêm Máy Mới
    </button>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 py-2" role="alert">
        <i class="fa-solid fa-check me-2"></i><?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- BẢNG DANH SÁCH ĐIỆN THOẠI -->
<div class="card border-0 rounded-4 shadow-sm bg-white p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Ảnh</th>
                    <th>Tên Điện Thoại</th>
                    <th>Hãng</th>
                    <th>Giá Niêm Yết</th>
                    <th>Giá Bán</th>
                    <th>Loại Máy</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><img src="../<?= htmlspecialchars($p['image']) ?>" alt="" style="width: 48px; height: 48px; object-fit: contain;"></td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></div>
                            <small class="text-secondary"><?= htmlspecialchars($p['ram']) ?> - <?= htmlspecialchars($p['rom']) ?> | <?= htmlspecialchars($p['screen']) ?></small>
                        </td>
                        <td><span class="badge bg-light text-primary border"><?= htmlspecialchars($p['brand_name'] ?? 'Khác') ?></span></td>
                        <td class="text-muted text-decoration-line-through small"><?= number_format($p['price'], 0, ',', '.') ?> đ</td>
                        <td class="text-danger fw-bold"><?= number_format($p['sale_price'] > 0 ? $p['sale_price'] : $p['price'], 0, ',', '.') ?> đ</td>
                        <td>
                            <?php if ($p['is_used']): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Máy cũ 99%</span>
                            <?php elseif ($p['is_featured']): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Flagship 2026</span>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border">Mới 100%</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <a href="products.php?edit_id=<?= (int)$p['id'] ?>" class="btn btn-outline-primary btn-sm" title="Sửa sản phẩm" aria-label="Sửa <?= htmlspecialchars($p['name']) ?>"><i class="fa-solid fa-pen"></i></a>
                            <button type="button" class="btn btn-outline-danger btn-sm" title="Xóa sản phẩm" aria-label="Xóa <?= htmlspecialchars($p['name']) ?>" data-bs-toggle="modal" data-bs-target="#deleteProductModal" data-product-id="<?= (int)$p['id'] ?>" data-product-name="<?= htmlspecialchars($p['name']) ?>"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL THÊM SẢN PHẨM MỚI -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary"><i class="fa-solid fa-mobile-screen-button me-2"></i><?= $editingProduct ? 'Sửa Thông Tin Điện Thoại' : 'Thêm Điện Thoại Mới' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="products.php">
                <input type="hidden" name="product_id" value="<?= (int)($editingProduct['id'] ?? 0) ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Tên Điện Thoại *</label>
                            <input type="text" name="name" class="form-control rounded-pill" required placeholder="Ví dụ: iPhone 18 Pro Max 512GB" value="<?= htmlspecialchars($editingProduct['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Hãng Sản Xuất *</label>
                            <select name="brand_id" class="form-select rounded-pill">
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= (int)($editingProduct['brand_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Giá Gốc Niêm Yết (VNĐ) *</label>
                            <input type="number" name="price" class="form-control rounded-pill" required placeholder="35000000" value="<?= htmlspecialchars((string)($editingProduct['price'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Giá Bán Khuyến Mãi (VNĐ)</label>
                            <input type="number" name="sale_price" class="form-control rounded-pill" placeholder="32000000" value="<?= htmlspecialchars((string)($editingProduct['sale_price'] ?? '')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Đường Dẫn Ảnh</label>
                            <input type="text" name="image" class="form-control rounded-pill" value="<?= htmlspecialchars($editingProduct['image'] ?? 'assets/images/products/iphone-16-promax.png') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">RAM</label>
                            <input type="text" name="ram" class="form-control rounded-pill" value="<?= htmlspecialchars($editingProduct['ram'] ?? '12 GB') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Bộ Nhớ ROM</label>
                            <input type="text" name="rom" class="form-control rounded-pill" value="<?= htmlspecialchars($editingProduct['rom'] ?? '256 GB') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Dung Lượng Pin</label>
                            <input type="text" name="battery" class="form-control rounded-pill" value="<?= htmlspecialchars($editingProduct['battery'] ?? '5000 mAh') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Chipset CPU</label>
                            <input type="text" name="cpu" class="form-control rounded-pill" value="<?= htmlspecialchars($editingProduct['cpu'] ?? 'Snapdragon 8 Gen 5 / Apple A20') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Màn Hình</label>
                            <input type="text" name="screen" class="form-control rounded-pill" value="<?= htmlspecialchars($editingProduct['screen'] ?? '6.8 inch 120Hz') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Màu sắc (phân cách bằng dấu phẩy)</label>
                            <input type="text" name="colors" class="form-control rounded-pill" value="<?= htmlspecialchars($editingProduct['colors'] ?? 'Đen, Trắng') ?>">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="isFeat" <?= !empty($editingProduct['is_featured']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold small" for="isFeat">Gắn nhãn Flagship 2026</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_used" value="1" id="isUsed" <?= !empty($editingProduct['is_used']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold small text-success" for="isUsed">Hàng cũ Like New 99%</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="save_product" value="1" class="btn btn-primary rounded-pill px-4 fw-bold">Lưu Sản Phẩm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-body p-4 text-center">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width:56px;height:56px"><i class="fa-solid fa-trash"></i></div>
                <h5 class="fw-bold" id="deleteProductTitle">Xóa sản phẩm?</h5>
                <p class="text-secondary" id="deleteProductMessage"></p>
                <form method="POST" action="products.php" class="d-flex justify-content-center gap-2">
                    <input type="hidden" name="product_id" id="deleteProductId">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="delete_product" value="1" class="btn btn-danger rounded-pill px-4">Xóa sản phẩm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteModal = document.getElementById('deleteProductModal');
    deleteModal?.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        document.getElementById('deleteProductId').value = trigger.dataset.productId;
        document.getElementById('deleteProductMessage').textContent = `Sản phẩm "${trigger.dataset.productName}" sẽ bị xóa khỏi cửa hàng.`;
    });

    <?php if ($editingProduct): ?>
    bootstrap.Modal.getOrCreateInstance(document.getElementById('addProductModal')).show();
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>
