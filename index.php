<?php
require_once 'config/database.php';

// Ép giá Pro Max đắt hơn bản Pro chuẩn thị trường
$pdo->exec("UPDATE products SET price = 45990000, sale_price = 41990000 WHERE id = 1 OR name LIKE '%18 Pro Max%';");
$pdo->exec("UPDATE products SET price = 36990000, sale_price = 33990000 WHERE id = 2 OR (name LIKE '%18 Pro%' AND name NOT LIKE '%Pro Max%');");

$pageTitle = 'V-Phone - Siêu Thị Flagship 2026 & Smartphone Chính Hãng';

$brandStmt = $pdo->query("SELECT * FROM brands ORDER BY id ASC");
$brands = $brandStmt->fetchAll();

$brandId = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

$sql = "SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE 1=1";
$params = [];

if ($brandId > 0) {
    $sql .= " AND p.brand_id = :brand_id";
    $params[':brand_id'] = $brandId;
}

if (!empty($keyword)) {
    $sql .= " AND p.name LIKE :keyword";
    $params[':keyword'] = '%' . $keyword . '%';
}

$sql .= " ORDER BY p.is_featured DESC, p.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$searchList = [];
foreach ($products as $p) {
    $pPrice = ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) ? $p['sale_price'] : $p['price'];
    $searchList[] = [
        'id' => (int)$p['id'],
        'name' => $p['name'],
        'price' => (int)$pPrice,
        'old_price' => (int)$p['price'],
        'image' => $p['image'],
        'rom' => $p['rom'] ?? '256 GB',
        'colors' => $p['colors'] ?? 'Đen, Trắng, Xanh'
    ];
}

function getHexColorBadge($name) {
    $c = mb_strtolower($name, 'UTF-8');
    if (strpos($c, 'sa mạc') !== false || strpos($c, 'vàng') !== false || strpos($c, 'gold') !== false || strpos($c, 'amber') !== false) return '#cbbba0';
    if (strpos($c, 'hồng') !== false || strpos($c, 'pink') !== false) return '#f472b6';
    if (strpos($c, 'tím') !== false || strpos($c, 'purple') !== false || strpos($c, 'lilac') !== false) return '#8b5cf6';
    if (strpos($c, 'xanh') !== false || strpos($c, 'blue') !== false || strpos($c, 'navy') !== false || strpos($c, 'icy') !== false) return '#38bdf8';
    if (strpos($c, 'đen') !== false || strpos($c, 'black') !== false || strpos($c, 'phantom') !== false || strpos($c, 'space') !== false) return '#1e293b';
    if (strpos($c, 'trắng') !== false || strpos($c, 'white') !== false || strpos($c, 'bạc') !== false || strpos($c, 'tự nhiên') !== false) return '#e2e8f0';
    if (strpos($c, 'đỏ') !== false || strpos($c, 'red') !== false) return '#ef4444';
    return '#0066cc';
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<style>
@keyframes floatPhoneSmooth {
    0% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-14px) rotate(1.5deg); }
    100% { transform: translateY(0px) rotate(0deg); }
}
@keyframes floatPhoneAlt {
    0% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-8px) rotate(-1deg); }
    100% { transform: translateY(0px) rotate(0deg); }
}
.banner-dual-stage {
    width: 100% !important;
    height: 380px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: relative !important;
    margin: 0 auto !important;
}
.banner-phone-left-float {
    max-height: 290px !important;
    max-width: 210px !important;
    object-fit: contain !important;
    position: relative !important;
    z-index: 1 !important;
    margin-right: -30px !important;
    filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.55)) !important;
    animation: floatPhoneAlt 4s ease-in-out infinite alternate !important;
}
.banner-phone-right-float {
    max-height: 330px !important;
    max-width: 250px !important;
    object-fit: contain !important;
    position: relative !important;
    z-index: 2 !important;
    filter: drop-shadow(0 25px 40px rgba(0, 0, 0, 0.65)) !important;
    animation: floatPhoneSmooth 3.5s ease-in-out infinite alternate !important;
}
.banner-phone-single-float {
    max-height: 330px !important;
    max-width: 270px !important;
    object-fit: contain !important;
    filter: drop-shadow(0 25px 40px rgba(0, 0, 0, 0.65)) !important;
    animation: floatPhoneSmooth 4s ease-in-out infinite alternate !important;
}
</style>

<script>
    window.STORE_PRODUCTS = <?= json_encode($searchList, JSON_UNESCAPED_UNICODE) ?>;
</script>

<!-- BANNER CAROUSEL CẢ 3 SLIDE ĐỒNG BỘ CĂN GIỮA VÀ LƠ LỬNG 3D -->
<div class="container my-3">
    <div id="vphoneCarousel" class="carousel slide carousel-fade carousel-banner-vip shadow-lg" data-bs-ride="carousel" data-bs-interval="3500" data-bs-pause="false">
        <div class="carousel-indicators carousel-indicators-vip">
            <button type="button" data-bs-target="#vphoneCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#vphoneCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#vphoneCarousel" data-bs-slide-to="2"></button>
        </div>

        <div class="carousel-inner h-100">
            <!-- SLIDE 1: APPLE -->
            <div class="carousel-item active h-100">
                <div class="banner-bg-apple px-4 px-md-5 text-white d-flex align-items-center h-100">
                    <div class="row align-items-center w-100 g-4">
                        <div class="col-lg-7">
                            <span class="badge bg-white text-primary px-3 py-1 mb-2 fw-bold rounded-pill shadow-sm" style="font-size:0.75rem;">
                                <i class="fa-brands fa-apple me-1"></i> SIÊU PHẨM GẬP ĐẦU TIÊN CỦA APPLE
                            </span>
                            <div class="banner-title-equal text-white mb-2">iPhone 18 Pro Max & Duo</div>
                            <div class="banner-desc-equal mb-3">
                                Khung Titan Vũ Trụ mạ PVD siêu bền. Màn hình gập đôi Liquid Retina không nếp gấp cùng vi xử lý Apple A20 Pro 2nm thế hệ mới nhất.
                            </div>
                            <div class="d-flex gap-2 flex-wrap mb-3">
                                <span class="badge glass-spec-pill px-3 py-1 rounded-pill"><i class="fa-solid fa-microchip text-info me-1"></i>A20 Pro (2nm)</span>
                                <span class="badge glass-spec-pill px-3 py-1 rounded-pill"><i class="fa-solid fa-camera text-warning me-1"></i>Camera 200x</span>
                                <span class="badge glass-spec-pill px-3 py-1 rounded-pill"><i class="fa-solid fa-shield-halved text-success me-1"></i>Bảo hành 1-1</span>
                            </div>
                            <div class="d-flex gap-3 align-items-center">
                                <a href="product-detail.php?id=1" class="btn btn-light text-primary btn-md rounded-pill fw-bold px-4 py-2 shadow">
                                    <i class="fa-solid fa-bolt me-1"></i>Đặt Mua Ngay
                                </a>
                                <span class="fw-bold text-white fs-6">Chỉ từ 41.990.000 đ</span>
                            </div>
                        </div>
                        <div class="col-lg-5 text-center d-flex align-items-center justify-content-center h-100">
                            <div class="banner-dual-stage">
                                <img src="assets/images/products/iphone-18-promax.png" alt="iPhone 18 Pro Max" class="banner-phone-left-float" onerror="this.src='assets/images/products/iphone-16-promax.png';">
                                <img src="assets/images/products/iphone-18-duo.png" alt="iPhone 18 Duo" class="banner-phone-right-float">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 2: SAMSUNG -->
            <div class="carousel-item h-100">
                <div class="banner-bg-samsung px-4 px-md-5 text-white d-flex align-items-center h-100">
                    <div class="row align-items-center w-100 g-4">
                        <div class="col-lg-7">
                            <span class="badge bg-warning text-dark px-3 py-1 mb-2 fw-bold rounded-pill shadow-sm" style="font-size:0.75rem;">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> GALAXY AI THẾ HỆ MỚI
                            </span>
                            <div class="banner-title-equal text-white mb-2">Galaxy S26 Ultra & Tri-Fold</div>
                            <div class="banner-desc-equal mb-3">
                                Mở rộng không gian hiển thị 10.2 inch với cơ chế gập ba độc bản. Trợ lý Galaxy AI quyền năng cùng cụm camera 200MP.
                            </div>
                            <div class="d-flex gap-2 flex-wrap mb-3">
                                <span class="badge glass-spec-pill px-3 py-1 rounded-pill"><i class="fa-solid fa-bolt text-warning me-1"></i>Snapdragon 8 Gen 5</span>
                                <span class="badge glass-spec-pill px-3 py-1 rounded-pill"><i class="fa-solid fa-layer-group text-info me-1"></i>Màn gập 3 màn hình</span>
                                <span class="badge glass-spec-pill px-3 py-1 rounded-pill"><i class="fa-solid fa-pen text-success me-1"></i>Bút S-Pen</span>
                            </div>
                            <div class="d-flex gap-3 align-items-center">
                                <a href="product-detail.php?id=4" class="btn btn-warning text-dark btn-md rounded-pill fw-bold px-4 py-2 shadow">
                                    <i class="fa-solid fa-sparkles me-1"></i>Khám Phá Galaxy AI
                                </a>
                                <span class="fw-bold text-white fs-6">Chỉ từ 34.990.000 đ</span>
                            </div>
                        </div>
                        <div class="col-lg-5 text-center d-flex align-items-center justify-content-center h-100">
                            <div class="banner-dual-stage">
                                <img src="assets/images/products/samsung-s26-ultra.png" alt="S26 Ultra" class="banner-phone-left-float" onerror="this.src='assets/images/products/samsung-s24-ultra.png';">
                                <img src="assets/images/products/samsung-tri-fold.png" alt="Galaxy Tri-Fold" class="banner-phone-right-float">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLIDE 3: THU CŨ ĐỔI MỚI -->
            <div class="carousel-item h-100">
                <div class="banner-bg-promo px-4 px-md-5 text-white d-flex align-items-center h-100">
                    <div class="row align-items-center w-100 g-4">
                        <div class="col-lg-7">
                            <span class="badge bg-danger text-white px-3 py-1 mb-2 fw-bold rounded-pill shadow-sm" style="font-size:0.75rem;">
                                <i class="fa-solid fa-fire me-1"></i> ĐẶC QUYỀN V-PHONE 2026
                            </span>
                            <div class="banner-title-equal text-white mb-2">Thu Cũ Đổi Mới Lên Đời</div>
                            <div class="banner-desc-equal mb-3">
                                Lên đời điện thoại Flagship cực dễ dàng. Trợ giá trực tiếp lên đến 5.000.000 đ, trả góp 0% lãi suất duyệt trong 5 phút.
                            </div>
                            <div class="d-flex gap-2 flex-wrap mb-3">
                                <span class="badge glass-spec-pill px-3 py-1 rounded-pill"><i class="fa-solid fa-money-bill-wave text-success me-1"></i>Trợ giá 5 triệu</span>
                                <span class="badge glass-spec-pill px-3 py-1 rounded-pill"><i class="fa-solid fa-credit-card text-warning me-1"></i>Trả góp 0% lãi</span>
                                <span class="badge glass-spec-pill px-3 py-1 rounded-pill"><i class="fa-solid fa-rotate-left text-info me-1"></i>Bảo hành VIP</span>
                            </div>
                            <div>
                                <a href="#product-list" class="btn btn-outline-light btn-md rounded-pill fw-bold px-4 py-2">
                                    <i class="fa-solid fa-arrow-down me-1"></i>Săn Deal Ngay Hôm Nay
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-5 text-center d-flex align-items-center justify-content-center h-100">
                            <div class="banner-dual-stage">
                                <img src="assets/images/products/iphone-16-promax.png" alt="iPhone 16 Pro Max" class="banner-phone-single-float">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- KHỐI TIỆN ÍCH DỊCH VỤ -->
<div class="container my-4">
    <div class="row g-3">
        <div class="col-md-3 col-6">
            <div class="policy-card d-flex align-items-center">
                <i class="fa-solid fa-truck-fast text-primary fs-2 me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">Giao Hỏa Tốc 2H</h6>
                    <small class="text-secondary">Nội thành TP.HCM</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="policy-card d-flex align-items-center">
                <i class="fa-solid fa-shield-halved text-primary fs-2 me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">Bảo Hành 12T</h6>
                    <small class="text-secondary">Chính hãng 100%</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="policy-card d-flex align-items-center">
                <i class="fa-solid fa-rotate-left text-primary fs-2 me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">Lỗi 1 Đổi 1</h6>
                    <small class="text-secondary">Trong vòng 30 ngày</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="policy-card d-flex align-items-center">
                <i class="fa-solid fa-credit-card text-primary fs-2 me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">Trả Góp 0%</h6>
                    <small class="text-secondary">Duyệt nhanh 5 phút</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BỘ LỌC THƯƠNG HIỆU -->
<div class="container my-4">
    <div class="bg-white p-3 rounded-4 shadow-sm d-flex flex-wrap gap-2 align-items-center">
        <span class="fw-bold text-primary me-2"><i class="fa-solid fa-filter me-1"></i>Thương hiệu:</span>
        <a href="index.php" class="btn btn-sm <?= $brandId == 0 ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">Tất cả</a>
        <?php foreach ($brands as $b): ?>
            <a href="index.php?brand_id=<?= $b['id'] ?>" class="btn btn-sm <?= $brandId == $b['id'] ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">
                <?= htmlspecialchars($b['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- DANH SÁCH ĐIỆN THOẠI -->
<div class="container" id="product-list">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-mobile-screen-button text-primary me-2"></i>
            <?= !empty($keyword) ? 'Kết quả tìm kiếm: "' . htmlspecialchars($keyword) . '"' : 'Flagship 2026 & Điện Thoại Mới Nhất' ?>
        </h4>
        <span class="badge bg-white text-secondary border px-3 py-2 rounded-pill"><?= count($products) ?> sản phẩm</span>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php foreach ($products as $p): ?>
            <?php 
                $romDisplayList = ['256GB', '512GB', '1TB'];
                if (strpos($p['rom'], '128') !== false) {
                    $romDisplayList = ['128GB', '256GB', '512GB'];
                } elseif (strpos($p['rom'], '64') !== false) {
                    $romDisplayList = ['64GB', '128GB', '256GB'];
                }
                $colorDisplayList = array_map("trim", explode(",", $p["colors"] ?? "Đen, Trắng"));
            ?>
            <div class="col">
                <div class="card h-100 product-card shadow-sm position-relative overflow-hidden">
                    <?php if ($p['is_featured']): ?>
                        <span class="badge badge-tech-new position-absolute top-0 end-0 m-3 px-2 py-1 rounded-pill">
                            <i class="fa-solid fa-bolt me-1 text-primary"></i>2026 NEW
                        </span>
                    <?php endif; ?>

                    <?php if ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']): ?>
                        <?php $percent = round((($p['price'] - $p['sale_price']) / $p['price']) * 100); ?>
                        <span class="badge bg-danger position-absolute top-0 start-0 m-3 px-2 py-1 rounded-pill">-<?= $percent ?>%</span>
                    <?php endif; ?>

                    <div class="product-img-wrapper p-3">
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" onerror="this.onerror=null; this.src='assets/images/products/iphone-16.png';">
                    </div>

                    <div class="card-body d-flex flex-column pt-2">
                        <small class="text-primary fw-bold text-uppercase"><?= htmlspecialchars($p['brand_name'] ?? 'Khác') ?></small>
                        <div class="card-prod-title"><?= htmlspecialchars($p['name']) ?></div>

                        <div class="specs-badge my-1">
                            <span class="badge"><?= htmlspecialchars($p['ram']) ?></span>
                            <span class="badge"><?= htmlspecialchars($p['screen']) ?></span>
                        </div>

                        <div class="d-flex align-items-center gap-1 my-1 flex-wrap" style="min-height: 22px;">
                            <small class="text-secondary fw-semibold" style="font-size: 0.68rem;">Bộ nhớ:</small>
                            <?php foreach ($romDisplayList as $rName): ?>
                                <span class="badge bg-light text-primary border" style="font-size: 0.65rem; font-weight: 600; padding: 3px 6px;">
                                    <?= $rName ?>
                                </span>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex align-items-center gap-1 my-1 flex-wrap" style="min-height: 24px;">
                            <small class="text-secondary fw-semibold" style="font-size: 0.68rem;"><i class="fa-solid fa-palette text-primary me-1"></i>Màu:</small>
                            <?php foreach (array_slice($colorDisplayList, 0, 3) as $cName): ?>
                                <span class="badge bg-white text-dark border d-flex align-items-center" style="font-size: 0.65rem; font-weight: 500; padding: 2px 6px;">
                                    <span style="width: 7px; height: 7px; border-radius: 50%; background: <?= getHexColorBadge($cName) ?>; display: inline-block; margin-right: 4px; border: 1px solid rgba(0,0,0,0.1);"></span>
                                    <?= htmlspecialchars($cName) ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if (count($colorDisplayList) > 3): ?>
                                <span class="badge bg-light text-primary border" style="font-size: 0.65rem; font-weight: 700; padding: 2px 5px;">+<?= count($colorDisplayList) - 3 ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="mt-auto pt-2">
                            <?php if ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']): ?>
                                <div class="text-danger fw-bold fs-5 mb-0"><?= number_format($p['sale_price'], 0, ',', '.') ?> đ</div>
                                <small class="text-decoration-line-through text-muted"><?= number_format($p['price'], 0, ',', '.') ?> đ</small>
                            <?php else: ?>
                                <div class="text-primary fw-bold fs-5 mb-0"><?= number_format($p['price'], 0, ',', '.') ?> đ</div>
                            <?php endif; ?>
                        </div>

                        <!-- CỤM 3 NÚT SẠCH SẼ -->
                        <div class="d-grid gap-2 mt-3">
                            <button type="button" class="btn btn-vphone btn-sm rounded-pill fw-bold py-2 shadow-sm text-center" onclick="openProductModal(<?= $p['id'] ?>, 'buy')">
                                <i class="fa-solid fa-bolt me-1"></i>MUA NGAY
                            </button>
                            <div class="d-flex gap-2">
                                <a href="product-detail.php?id=<?= $p['id'] ?>" class="btn btn-outline-vphone btn-sm rounded-pill flex-grow-1 fw-semibold text-center">
                                    Chi tiết
                                </a>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill flex-grow-1 fw-bold" onclick="openProductModal(<?= $p['id'] ?>, 'cart')">
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

<!-- ========================================================================= -->
<!-- MODAL POPUP CHỌN MÀU ĐỔI ĐÚNG FILE ẢNH & CHỌN BỘ NHỚ NHẢY TIỀN -->
<!-- ========================================================================= -->
<div class="modal fade" id="productSelectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="modal-title fw-bold text-primary mb-0"><i class="fa-solid fa-sliders me-2"></i>Tùy Chọn Màu Sắc & Phiên Bản</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-3">
                <div class="d-flex align-items-center mb-3 p-3 bg-light rounded-4 border">
                    <div style="width: 75px; height: 75px; display:flex; align-items:center; justify-content:center; background:#fff; border-radius:12px; margin-right:15px; flex-shrink:0;">
                        <img id="modalImg" src="" alt="" style="max-height: 65px; max-width: 65px; object-fit: contain; transition: all 0.2s ease;">
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <h6 id="modalTitle" class="fw-bold text-dark mb-1 text-truncate">Tên điện thoại</h6>
                        <div class="d-flex align-items-baseline gap-2">
                            <span id="modalPrice" class="fs-5 fw-bold text-danger">0 đ</span>
                            <small id="modalOldPrice" class="text-decoration-line-through text-muted small">0 đ</small>
                        </div>
                        <small class="text-primary fw-semibold" id="modalColorNotice">Màu: Đang chọn...</small>
                    </div>
                </div>

                <!-- 1. CHỌN MÀU SẮC -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-secondary mb-1">
                        <i class="fa-solid fa-palette text-primary me-1"></i>Chọn màu sắc:
                    </label>
                    <div id="modalColorPills" class="d-flex gap-2 flex-wrap"></div>
                </div>

                <!-- 2. CHỌN BỘ NHỚ -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-secondary mb-1">
                        <i class="fa-solid fa-hard-drive text-primary me-1"></i>Chọn dung lượng bộ nhớ:
                    </label>
                    <div id="modalRomPills" class="d-flex gap-2 flex-wrap"></div>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary rounded-pill fw-bold py-2 shadow-sm" id="btnConfirmBuyNow">
                        <i class="fa-solid fa-bolt me-1"></i>XÁC NHẬN MUA NGAY (GIAO TẬN NƠI)
                    </button>
                    <button type="button" class="btn btn-outline-primary rounded-pill fw-bold py-2" id="btnConfirmAddToCart">
                        <i class="fa-solid fa-cart-plus me-1"></i>THÊM VÀO GIỎ HÀNG
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentProduct = null;
let selectedColor = "";
let selectedRom = "";
let extraMoney = 0;
let bsModalInstance = null;

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
}

function toColorSlug(str) {
    str = str.toLowerCase();
    str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
    str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
    str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
    str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
    str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
    str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
    str = str.replace(/đ/g, "d");
    str = str.replace(/[^a-z0-9]/g, "-");
    str = str.replace(/-+/g, "-");
    return str.replace(/^-|-$/g, "");
}

function getExactColorImageFile(baseImg, colorName) {
    if (!colorName || colorName === "Tiêu chuẩn") return baseImg;
    const dot = baseImg.lastIndexOf(".");
    if (dot === -1) return baseImg;
    const basePath = baseImg.substring(0, dot);
    const ext = baseImg.substring(dot);
    return `${basePath}-${toColorSlug(colorName)}${ext}`;
}

function changeModalImageByColor(imgElement, baseImg, colorName) {
    const targetFile = getExactColorImageFile(baseImg, colorName);
    const tester = new Image();
    tester.src = targetFile;
    tester.onload = function() {
        imgElement.style.opacity = "0.3";
        setTimeout(() => {
            imgElement.src = targetFile;
            imgElement.style.opacity = "1";
        }, 100);
    };
    tester.onerror = function() {
        imgElement.src = baseImg;
    };
}

function openProductModal(productId, defaultAction) {
    const all = window.STORE_PRODUCTS || [];
    currentProduct = all.find(p => p.id === productId);
    if (!currentProduct) return;

    const modalEl = document.getElementById('productSelectModal');
    if (!bsModalInstance) {
        bsModalInstance = new bootstrap.Modal(modalEl);
    }

    const modalImg = document.getElementById('modalImg');
    modalImg.src = currentProduct.image;
    document.getElementById('modalTitle').innerText = currentProduct.name;
    document.getElementById('modalPrice').innerText = formatCurrency(currentProduct.price);
    document.getElementById('modalOldPrice').innerText = formatCurrency(currentProduct.old_price);

    // Render nút màu
    const colorBox = document.getElementById('modalColorPills');
    colorBox.innerHTML = '';
    const colors = (currentProduct.colors || 'Đen, Trắng, Xanh').split(',').map(c => c.trim());
    selectedColor = colors[0];
    document.getElementById('modalColorNotice').innerText = 'Màu: ' + selectedColor;

    // Load ảnh màu mặc định
    changeModalImageByColor(modalImg, currentProduct.image, selectedColor);

    colors.forEach((col, idx) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `btn btn-sm rounded-pill fw-bold px-3 py-1 ${idx === 0 ? 'btn-primary active text-white' : 'btn-outline-secondary'}`;
        btn.innerText = col;
        btn.onclick = function() {
            colorBox.querySelectorAll('button').forEach(b => b.className = 'btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-1');
            this.className = 'btn btn-sm btn-primary active text-white rounded-pill fw-bold px-3 py-1';
            selectedColor = col;
            document.getElementById('modalColorNotice').innerText = 'Màu: ' + col;

            // Đổi ảnh đúng file màu bạn đã tải
            changeModalImageByColor(modalImg, currentProduct.image, col);
        };
        colorBox.appendChild(btn);
    });

    // Render nút dung lượng
    const romBox = document.getElementById('modalRomPills');
    romBox.innerHTML = '';
    let romOptions = [];
    if (currentProduct.rom.includes('128')) {
        romOptions = [
            { rom: '128 GB', extra: 0, label: '128 GB (Tiêu chuẩn)' },
            { rom: '256 GB', extra: 2500000, label: '256 GB (+2.5tr)' },
            { rom: '512 GB', extra: 5500000, label: '512 GB (+5.5tr)' }
        ];
    } else if (currentProduct.rom.includes('64')) {
        romOptions = [
            { rom: '64 GB', extra: 0, label: '64 GB (Tiêu chuẩn)' },
            { rom: '128 GB', extra: 1500000, label: '128 GB (+1.5tr)' },
            { rom: '256 GB', extra: 3500000, label: '256 GB (+3.5tr)' }
        ];
    } else {
        romOptions = [
            { rom: '256 GB', extra: 0, label: '256 GB (Tiêu chuẩn)' },
            { rom: '512 GB', extra: 4000000, label: '512 GB (+4.0tr)' },
            { rom: '1 TB', extra: 9000000, label: '1 TB (+9.0tr)' }
        ];
    }
    selectedRom = romOptions[0].rom;
    extraMoney = 0;

    romOptions.forEach((opt, idx) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `btn btn-sm rounded-pill fw-bold px-3 py-1 ${idx === 0 ? 'btn-primary active text-white' : 'btn-outline-secondary'}`;
        btn.innerText = opt.label;
        btn.onclick = function() {
            romBox.querySelectorAll('button').forEach(b => b.className = 'btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-1');
            this.className = 'btn btn-sm btn-primary active text-white rounded-pill fw-bold px-3 py-1';
            selectedRom = opt.rom;
            extraMoney = opt.extra;

            document.getElementById('modalPrice').innerText = formatCurrency(currentProduct.price + opt.extra);
            document.getElementById('modalOldPrice').innerText = formatCurrency(currentProduct.old_price + opt.extra);
        };
        romBox.appendChild(btn);
    });

    document.getElementById('btnConfirmBuyNow').onclick = function() {
        bsModalInstance.hide();
        const currentChosenImg = modalImg.src;
        window.location.href = `checkout.php?action=buy_now&id=${currentProduct.id}&color=${encodeURIComponent(selectedColor)}&rom=${encodeURIComponent(selectedRom)}&extra=${extraMoney}&img=${encodeURIComponent(currentChosenImg)}`;
    };

    document.getElementById('btnConfirmAddToCart').onclick = function() {
        bsModalInstance.hide();
        const currentChosenImg = modalImg.src;
        const url = `cart.php?action=add&ajax=1&id=${currentProduct.id}&color=${encodeURIComponent(selectedColor)}&rom=${encodeURIComponent(selectedRom)}&extra=${extraMoney}&img=${encodeURIComponent(currentChosenImg)}`;
        fetch(url)
            .then(r => r.json())
            .then(data => {
                const badge = document.getElementById('cartBadge');
                if (badge) badge.innerText = data.cart_count;
                const toast = document.getElementById('vphoneLiveToast');
                const toastText = document.getElementById('vphoneToastText');
                if (toast && toastText) {
                    toastText.innerText = `Đã thêm "${currentProduct.name} - Màu ${selectedColor}" vào giỏ!`;
                    toast.style.display = 'block';
                    clearTimeout(window.toastTimer);
                    window.toastTimer = setTimeout(() => { toast.style.display = 'none'; }, 3500);
                }
            })
            .catch(() => {
                window.location.href = `cart.php?action=add&id=${currentProduct.id}&color=${encodeURIComponent(selectedColor)}&rom=${encodeURIComponent(selectedRom)}&extra=${extraMoney}&img=${encodeURIComponent(currentChosenImg)}`;
            });
    };

    bsModalInstance.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
