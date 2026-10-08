<?php
$pageTitle = 'Quản Lý Tồn Kho - V-Phone Admin';
require_once 'app/views/includes/header.php';

$message = '';

// Xử lý cập nhật số lượng tồn kho (Nhập thêm hàng)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $prodId = (int)$_POST['product_id'];
    $addQty = (int)$_POST['add_quantity'];
    $newQty = isset($_POST['set_quantity']) ? (int)$_POST['set_quantity'] : -1;

    if ($newQty >= 0) {
        $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQty, $prodId]);
        $message = "Đã cập nhật số lượng tồn kho thành $newQty máy!";
    } elseif ($addQty > 0) {
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
        $stmt->execute([$addQty, $prodId]);
        $message = "Đã nhập thêm +$addQty máy vào kho thành công!";
    }
}

// Thống kê tồn kho
$totalStock = $pdo->query("SELECT SUM(quantity) FROM products")->fetchColumn() ?: 0;
$lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity > 0 AND quantity <= 5")->fetchColumn() ?: 0;
$outOfStock = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity = 0")->fetchColumn() ?: 0;

// Lọc sản phẩm
$filter = $_GET['filter'] ?? 'all';
$querySql = "SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id";
if ($filter === 'low') {
    $querySql .= " WHERE p.quantity > 0 AND p.quantity <= 5";
} elseif ($filter === 'out') {
    $querySql .= " WHERE p.quantity = 0";
}
$querySql .= " ORDER BY p.quantity ASC, p.id DESC";
$stockList = $pdo->query($querySql)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Quản Lý Tồn Kho & Nhập Hàng</h3>
        <p class="text-secondary small mb-0">Theo dõi số lượng máy trong kho, cảnh báo hết hàng và nhập hàng nhanh</p>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 py-2" role="alert">
        <i class="fa-solid fa-check me-2"></i><?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- 3 THẺ KPI TỒN KHO -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-12">
        <a href="inventory.php?filter=all" class="text-decoration-none">
            <div class="bg-white p-3 rounded-4 shadow-sm border <?= $filter === 'all' ? 'border-primary border-2' : '' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-secondary fw-semibold text-uppercase">Tổng Số Lượng Trong Kho</small>
                        <h3 class="fw-bold text-primary my-1"><?= $totalStock ?> chiếc máy</h3>
                        <small class="text-muted">Đang sẵn sàng phân phối</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3"><i class="fa-solid fa-warehouse fs-3"></i></div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-4 col-12">
        <a href="inventory.php?filter=low" class="text-decoration-none">
            <div class="bg-white p-3 rounded-4 shadow-sm border <?= $filter === 'low' ? 'border-warning border-2' : '' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-secondary fw-semibold text-uppercase">Cảnh Báo Sắp Hết Hàng</small>
                        <h3 class="fw-bold text-warning my-1"><?= $lowStock ?> dòng máy</h3>
                        <small class="text-danger fw-semibold">Tồn kho còn &le; 5 máy (Cần nhập gấp)</small>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3"><i class="fa-solid fa-triangle-exclamation fs-3"></i></div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-4 col-12">
        <a href="inventory.php?filter=out" class="text-decoration-none">
            <div class="bg-white p-3 rounded-4 shadow-sm border <?= $filter === 'out' ? 'border-danger border-2' : '' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-secondary fw-semibold text-uppercase">Đã Hết Sạch Hàng</small>
                        <h3 class="fw-bold text-danger my-1"><?= $outOfStock ?> dòng máy</h3>
                        <small class="text-danger fw-semibold">Tồn kho = 0 chiếc</small>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-3"><i class="fa-solid fa-box-open fs-3"></i></div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- BẢNG DANH SÁCH TỒN KHO & NHẬP HÀNG -->
<div class="card border-0 rounded-4 shadow-sm bg-white p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Bảng Kiểm Kê Tồn Kho Chi Tiết</h5>
        <div class="btn-group">
            <a href="inventory.php?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">Tất cả</a>
            <a href="inventory.php?filter=low" class="btn btn-sm <?= $filter === 'low' ? 'btn-warning text-dark' : 'btn-outline-secondary' ?>">Sắp hết (&le; 5)</a>
            <a href="inventory.php?filter=out" class="btn btn-sm <?= $filter === 'out' ? 'btn-danger' : 'btn-outline-secondary' ?>">Đã hết (0)</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Ảnh</th>
                    <th>Tên Điện Thoại</th>
                    <th>Hãng</th>
                    <th>Giá Bán</th>
                    <th>Tồn Kho</th>
                    <th>Tình Trạng Kho</th>
                    <th style="min-width: 240px;">Thao Tác Nhập Thêm Hàng</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stockList as $p): ?>
                    <tr>
                        <td>
                            <img src="../<?= htmlspecialchars($p['image']) ?>" alt="" style="width: 44px; height: 44px; object-fit: contain;">
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></div>
                            <small class="text-secondary"><?= $p['rom'] ?> | <?= $p['screen'] ?></small>
                        </td>
                        <td><span class="badge bg-light text-primary border"><?= htmlspecialchars($p['brand_name'] ?? 'Khác') ?></span></td>
                        <td class="text-danger fw-bold"><?= number_format($p['sale_price'] > 0 ? $p['sale_price'] : $p['price'], 0, ',', '.') ?> đ</td>
                        <td>
                            <span class="fs-5 fw-bold <?= $p['quantity'] == 0 ? 'text-danger' : ($p['quantity'] <= 5 ? 'text-warning' : 'text-success') ?>">
                                <?= $p['quantity'] ?>
                            </span> <small class="text-muted">máy</small>
                        </td>
                        <td>
                            <?php if ($p['quantity'] == 0): ?>
                                <span class="badge bg-danger rounded-pill px-3 py-1"><i class="fa-solid fa-circle-xmark me-1"></i>HẾT HÀNG</span>
                            <?php elseif ($p['quantity'] <= 5): ?>
                                <span class="badge bg-warning text-dark rounded-pill px-3 py-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>SẮP HẾT (Còn <?= $p['quantity'] ?>)</span>
                            <?php else: ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1"><i class="fa-solid fa-circle-check me-1"></i>Dồi dào</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- FORM NHẬP THÊM HÀNG NHANH -->
                            <form method="POST" action="inventory.php" class="d-flex align-items-center gap-1">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="update_stock" value="1">

                                <input type="number" name="set_quantity" value="<?= $p['quantity'] ?>" min="0" max="999" class="form-control form-control-sm text-center fw-bold rounded-pill" style="width: 75px;" title="Chỉnh số lượng trực tiếp">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-2 fw-semibold" title="Lưu số lượng mới">Lưu</button>

                                <button type="submit" name="add_quantity" value="10" class="btn btn-outline-success btn-sm rounded-pill px-2 small fw-bold">+10</button>
                                <button type="submit" name="add_quantity" value="50" class="btn btn-outline-info btn-sm rounded-pill px-2 small fw-bold">+50</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'app/views/includes/footer.php'; ?>
