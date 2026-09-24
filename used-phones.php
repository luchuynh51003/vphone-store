<?php
require_once 'config/database.php';
$pageTitle = 'Kho Máy Cũ Giá Rẻ - V-Phone Outlet';

$brandStmt = $pdo->query("SELECT * FROM brands ORDER BY id ASC");
$brands = $brandStmt->fetchAll();

$brandId = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// Chỉ lấy các sản phẩm là Hàng cũ (is_used = 1)
$sql = "SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.is_used = 1";
$params = [];

if ($brandId > 0) {
    $sql .= " AND p.brand_id = :brand_id";
    $params[':brand_id'] = $brandId;
}

if (!empty($keyword)) {
    $sql .= " AND p.name LIKE :keyword";
    $params[':keyword'] = '%' . $keyword . '%';
}

$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usedProducts = $stmt->fetchAll();

$searchList = [];
foreach ($usedProducts as $p) {
    $pPrice = ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) ? $p['sale_price'] : $p['price'];
    $searchList[] = [
        'id' => (int)$p['id'],
        'name' => $p['name'],
        'price' => (int)$pPrice,
        'old_price' => (int)$p['price'],
        'price_formatted' => number_format($pPrice, 0, ',', '.') . ' đ',
        'image' => $p['image'],
        'rom' => $p['rom'] ?? '128 GB'
    ];
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<script>
    window.STORE_PRODUCTS = <?= json_encode($searchList, JSON_UNESCAPED_UNICODE) ?>;
</script>

<!-- BANNER RIÊNG CHO MÁY CŨ -->
<div class="container my-3">
    <div class="p-4 p-md-5 rounded-4 text-white shadow-sm" style="background: linear-gradient(135deg, #0984e3 0%, #0056b3 60%, #0a3d62 100%);">
        <div class="col-lg-8 py-2">
            <span class="badge bg-warning text-dark px-3 py-2 fw-bold rounded-pill mb-3">
                <i class="fa-solid fa-piggy-bank me-1"></i> TIẾT KIỆM TỚI 50%
            </span>
            <h1 class="display-5 fw-bold mb-2">Kho Máy Cũ Chính Hãng</h1>
            <p class="fs-5 opacity-90 mb-3">Điện thoại Like New 99%, zin 100% chưa qua sửa chữa. Bảo hành 6 tháng 1 đổi 1 như máy mới!</p>
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge bg-white text-primary border px-3 py-2"><i class="fa-solid fa-check me-1"></i>Cam kết Pin 88% - 99%</span>
                <span class="badge bg-white text-primary border px-3 py-2"><i class="fa-solid fa-check me-1"></i>Tặng kèm củ sạc nhanh</span>
                <span class="badge bg-white text-primary border px-3 py-2"><i class="fa-solid fa-check me-1"></i>Dùng thử 7 ngày miễn phí</span>
            </div>
        </div>
    </div>
</div>

<!-- BỘ LỌC THƯƠNG HIỆU MÁY CŨ -->
<div class="container my-4">
    <div class="bg-white p-3 rounded-4 shadow-sm d-flex flex-wrap gap-2 align-items-center border">
        <span class="fw-bold text-primary me-2"><i class="fa-solid fa-filter me-1"></i>Hãng máy cũ:</span>
        <a href="used-phones.php" class="btn btn-sm <?= $brandId == 0 ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">Tất cả máy cũ</a>
        <?php foreach ($brands as $b): ?>
            <a href="used-phones.php?brand_id=<?= $b['id'] ?>" class="btn btn-sm <?= $brandId == $b['id'] ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">
                <?= htmlspecialchars($b['name']) ?> Cũ
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- DANH SÁCH MÁY CŨ -->
<div class="container" id="product-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-tags text-primary me-2"></i>Điện Thoại Cũ Đang Sẵn Hàng
        </h4>
        <span class="badge bg-white text-secondary border px-3 py-2 rounded-pill"><?= count($usedProducts) ?> máy có sẵn</span>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php foreach ($usedProducts as $p): ?>
            <div class="col">
                <div class="card h-100 product-card shadow-sm position-relative overflow-hidden">
                    <!-- TAG MÁY CŨ 99% NỔI BẬT -->
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 position-absolute top-0 end-0 m-3 px-2 py-1 rounded-pill fw-bold">
                        <i class="fa-solid fa-circle-check me-1"></i><?= htmlspecialchars($p['condition_desc'] ?? 'Đẹp 99%') ?>
                    </span>

                    <?php if ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']): ?>
                        <?php $percent = round((($p['price'] - $p['sale_price']) / $p['price']) * 100); ?>
                        <span class="badge bg-danger position-absolute top-0 start-0 m-3 px-2 py-1 rounded-pill">-<?= $percent ?>%</span>
                    <?php endif; ?>

                    <div class="product-img-wrapper p-3">
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    </div>

                    <div class="card-body d-flex flex-column pt-2">
                        <small class="text-primary fw-bold text-uppercase"><?= htmlspecialchars($p['brand_name'] ?? 'Khác') ?> CŨ</small>
                        <h6 class="card-title fw-bold my-1 text-truncate-2 text-dark"><?= htmlspecialchars($p['name']) ?></h6>

                        <div class="specs-badge my-2 d-flex flex-wrap gap-1">
                            <span class="badge"><?= htmlspecialchars($p['ram']) ?></span>
                            <span class="badge"><?= htmlspecialchars($p['rom']) ?></span>
                            <span class="badge bg-info bg-opacity-10 text-primary border border-info border-opacity-25">Bảo hành 6T</span>
                        </div>

                        <div class="mt-auto pt-2">
                            <?php if ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']): ?>
                                <div class="text-danger fw-bold fs-5 mb-0"><?= number_format($p['sale_price'], 0, ',', '.') ?> đ</div>
                                <small class="text-decoration-line-through text-muted"><?= number_format($p['price'], 0, ',', '.') ?> đ (Giá mới)</small>
                            <?php else: ?>
                                <div class="text-primary fw-bold fs-5 mb-0"><?= number_format($p['price'], 0, ',', '.') ?> đ</div>
                            <?php endif; ?>
                        </div>

                        <!-- 3 NÚT HÀNH ĐỘNG CHUẨN -->
                        <div class="d-grid gap-2 mt-3">
                            <a href="cart.php?action=add&id=<?= $p['id'] ?>&redirect=checkout" class="btn btn-vphone btn-sm rounded-pill fw-bold py-2 shadow-sm text-center">
                                <i class="fa-solid fa-bolt me-1"></i>MUA NGAY
                            </a>
                            <div class="d-flex gap-2">
                                <a href="product-detail.php?id=<?= $p['id'] ?>" class="btn btn-outline-vphone btn-sm rounded-pill flex-grow-1 fw-semibold text-center">
                                    Chi tiết
                                </a>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill flex-grow-1 fw-bold" onclick="addToCartDirect(this, <?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')">
                                    <i class="fa-solid fa-cart-plus me-1"></i>Thêm giỏ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
