<?php
require_once 'config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) { echo "<h2 style='text-align:center; margin-top:50px;'>Không tìm thấy sản phẩm! <a href='index.php'>Quay lại</a></h2>"; exit; }

$pageTitle = $product['name'] . ' - V-Phone Chính Hãng';
$basePrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) ? $product['sale_price'] : $product['price'];
$baseOldPrice = $product['price'];
$saving = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) ? ($product['price'] - $product['sale_price']) : 0;

$colorList = array_map('trim', explode(',', $product['colors'] ?? 'Đen, Trắng, Xanh'));

$relStmt = $pdo->prepare("SELECT * FROM products WHERE brand_id = :bid AND id != :id LIMIT 4");
$relStmt->execute([':bid' => $product['brand_id'], ':id' => $id]);
$relatedProducts = $relStmt->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container my-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb py-2 px-3 bg-white rounded-4 shadow-sm mb-0">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-primary"><i class="fa-solid fa-house me-1"></i>Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="index.php?brand_id=<?= $product['brand_id'] ?>" class="text-decoration-none text-primary"><?= htmlspecialchars($product['brand_name'] ?? 'Điện thoại') ?></a></li>
            <li class="breadcrumb-item active text-truncate"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>
</div>

<div class="container my-4">
    <div class="row g-4">
        <!-- Cột trái: Ảnh to đổi màu trực tiếp -->
        <div class="col-lg-5">
            <div class="bg-white p-4 rounded-4 shadow-sm text-center position-relative border">
                <?php if ($product['is_featured']): ?>
                    <span class="badge badge-tech-new position-absolute top-0 start-0 m-3 px-3 py-2 rounded-pill shadow-sm">
                        <i class="fa-solid fa-bolt me-1 text-primary"></i>2026 FLAGSHIP
                    </span>
                <?php endif; ?>

                <div class="detail-img-box my-3 py-3" style="min-height: 360px; display: flex; align-items: center; justify-content: center;">
                    <img id="detailMainProductImage" 
                         src="<?= htmlspecialchars($product['image']) ?>" 
                         data-base-src="<?= htmlspecialchars($product['image']) ?>"
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="img-fluid" 
                         style="max-height: 340px; object-fit: contain; transition: all 0.25s ease;">
                </div>

                <div class="d-flex justify-content-center gap-2 mt-2">
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-shield-halved me-1"></i>Bảo hành 12T</span>
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-rotate-left me-1"></i>1 đổi 1 30 ngày</span>
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-truck-fast me-1"></i>Giao hỏa tốc 2H</span>
                </div>
            </div>
        </div>

        <!-- Cột phải: Giá, Chọn Màu Sắc, Chọn Dung Lượng -->
        <div class="col-lg-7">
            <div class="bg-white p-4 rounded-4 shadow-sm h-100 d-flex flex-column border">
                <span class="badge badge-tech-new align-self-start px-3 py-1 mb-2 rounded-pill">
                    <?= htmlspecialchars($product['brand_name'] ?? 'CHÍNH HÃNG') ?>
                </span>
                <h3 class="fw-bold text-dark mb-2"><?= htmlspecialchars($product['name']) ?></h3>

                <div class="d-flex align-items-center gap-2 text-warning mb-3 small">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    <span class="text-secondary">(4.9/5 - 128 lượt mua đánh giá)</span>
                </div>

                <div class="p-3 rounded-4 bg-light my-2 border">
                    <div class="d-flex align-items-baseline gap-3 flex-wrap">
                        <h2 class="text-danger fw-bold mb-0" id="displayPrice"><?= number_format($basePrice, 0, ',', '.') ?> đ</h2>
                        <h5 class="text-decoration-line-through text-secondary mb-0" id="displayOldPrice"><?= number_format($baseOldPrice, 0, ',', '.') ?> đ</h5>
                        <span class="badge bg-danger rounded-pill px-2 py-1" id="displaySavingTag">Tiết kiệm <?= number_format($saving, 0, ',', '.') ?> đ</span>
                    </div>
                    <div class="small text-success mt-2 fw-semibold">
                        <i class="fa-solid fa-circle-check me-1"></i>Còn <?= $product['quantity'] ?> máy sẵn hàng tại kho - Miễn phí giao hàng
                    </div>
                </div>

                <!-- 1. CHỌN MÀU SẮC (BẤM VÀO ĐỔI ĐÚNG ẢNH MÀU CỦA BẠN) -->
                <div class="my-2">
                    <label class="fw-bold mb-2 small text-uppercase text-secondary">
                        <i class="fa-solid fa-palette me-1 text-primary"></i>Chọn màu sắc: <span class="text-primary fw-bold" id="selectedColorDisplayLabel"><?= htmlspecialchars($colorList[0] ?? 'Tiêu chuẩn') ?></span>
                    </label>
                    <div class="d-flex gap-2 flex-wrap" id="detailColorPillsGroup">
                        <?php foreach ($colorList as $idx => $colorName): ?>
                            <button type="button" class="btn btn-sm rounded-pill fw-bold px-3 py-2 btn-detail-color <?= $idx === 0 ? 'btn-primary active text-white' : 'btn-outline-secondary' ?>" data-color="<?= htmlspecialchars($colorName) ?>">
                                <?= htmlspecialchars($colorName) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. CHỌN BỘ NHỚ -->
                <div class="my-2">
                    <label class="fw-bold mb-2 small text-uppercase text-secondary">
                        <i class="fa-solid fa-hard-drive me-1 text-primary"></i>Chọn dung lượng bộ nhớ:
                    </label>
                    <div class="d-flex gap-2 flex-wrap" id="storageButtonsGroup">
                        <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-bold storage-btn active" data-extra="0" data-rom="256 GB">
                            256 GB <small class="fw-normal">(Tiêu chuẩn)</small>
                        </button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-bold storage-btn" data-extra="3500000" data-rom="512 GB">
                            512 GB <small class="text-primary fw-semibold">(+3.5tr)</small>
                        </button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-bold storage-btn" data-extra="8000000" data-rom="1 TB">
                            1 TB <small class="text-primary fw-semibold">(+8.0tr)</small>
                        </button>
                    </div>
                </div>

                <div class="border border-primary border-opacity-25 rounded-4 p-3 my-2 bg-light">
                    <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-gift me-2"></i>Đặc Quyền Tại V-Phone:</h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-1 text-secondary">
                        <li><i class="fa-solid fa-check text-primary me-2"></i>Bảo hành chính hãng toàn diện 12 tháng.</li>
                        <li><i class="fa-solid fa-check text-primary me-2"></i>Thu cũ đổi mới trợ giá tới 5.000.000 đ.</li>
                    </ul>
                </div>

                <!-- 2 NÚT HÀNH ĐỘNG -->
                <div class="row g-2 mt-auto pt-3">
                    <div class="col-md-7 col-12">
                        <button type="button" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3" onclick="buyNow(<?= $product['id'] ?>)">
                            <i class="fa-solid fa-bag-shopping me-2"></i>MUA NGAY (GIAO TẬN NƠI)
                        </button>
                    </div>
                    <div class="col-md-5 col-12">
                        <button type="button" class="btn btn-outline-vphone btn-lg w-100 rounded-pill fw-bold py-3" onclick="addToCartFromDetail(this, <?= $product['id'] ?>)">
                            <i class="fa-solid fa-cart-plus me-2"></i>Thêm vào giỏ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const basePrice = <?= (int)$basePrice ?>;
const baseOldPrice = <?= (int)$baseOldPrice ?>;
let currentSelectedRom = "256 GB";
let currentExtraMoney = 0;
let currentSelectedColor = "<?= htmlspecialchars($colorList[0] ?? 'Tiêu chuẩn') ?>";

function formatCurrency(number) {
    return new Intl.NumberFormat('vi-VN').format(number) + ' đ';
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

// HÀM CHUYỂN ĐÚNG FILE ẢNH MÀU BẠN ĐÃ LƯU
function switchProductImageByColor(colorName) {
    const mainImg = document.getElementById('detailMainProductImage');
    if (!mainImg) return;

    const baseSrc = mainImg.getAttribute('data-base-src') || mainImg.src;
    const dot = baseSrc.lastIndexOf('.');
    if (dot === -1) return;

    const basePath = baseSrc.substring(0, dot);
    const ext = baseSrc.substring(dot);
    const colSlug = toColorSlug(colorName);

    // Tên file ảnh màu: ví dụ assets/images/products/iphone-18-promax-xanh-glacier-blue.png
    const targetFile = `${basePath}-${colSlug}${ext}`;

    const tester = new Image();
    tester.src = targetFile;
    tester.onload = function() {
        mainImg.style.opacity = '0.2';
        setTimeout(() => {
            mainImg.src = targetFile;
            mainImg.style.opacity = '1';
        }, 120);
    };
    tester.onerror = function() {
        // Nếu chưa có file ảnh riêng thì giữ nguyên ảnh gốc
        mainImg.src = baseSrc;
    };
}

// Bắt sự kiện chọn MÀU SẮC
document.querySelectorAll('.btn-detail-color').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.btn-detail-color').forEach(b => {
            b.className = "btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-2 btn-detail-color";
        });
        this.className = "btn btn-sm btn-primary active text-white rounded-pill fw-bold px-3 py-2 btn-detail-color";

        currentSelectedColor = this.getAttribute('data-color');
        document.getElementById('selectedColorDisplayLabel').innerText = currentSelectedColor;

        // GỌI ĐỔI ẢNH MÀU NGAY LẬP TỨC
        switchProductImageByColor(currentSelectedColor);
    });
});

// Bắt sự kiện chọn BỘ NHỚ
document.querySelectorAll('.storage-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.storage-btn').forEach(b => {
            b.className = "btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-2 storage-btn";
        });
        this.className = "btn btn-sm btn-primary active text-white rounded-pill fw-bold px-3 py-2 storage-btn";

        currentExtraMoney = parseInt(this.getAttribute('data-extra')) || 0;
        currentSelectedRom = this.getAttribute('data-rom') || "256 GB";

        const newPrice = basePrice + currentExtraMoney;
        const newOldPrice = baseOldPrice + currentExtraMoney;

        document.getElementById('displayPrice').innerText = formatCurrency(newPrice);
        document.getElementById('displayOldPrice').innerText = formatCurrency(newOldPrice);
    });
});

function addToCartFromDetail(btn, productId) {
    const mainImg = document.getElementById('detailMainProductImage');
    const currentImgSrc = mainImg ? mainImg.src : "";
    const url = `cart.php?action=add&ajax=1&id=${productId}&rom=${encodeURIComponent(currentSelectedRom)}&color=${encodeURIComponent(currentSelectedColor)}&extra=${currentExtraMoney}&img=${encodeURIComponent(currentImgSrc)}`;
    
    addToCartDirect(btn, productId, "<?= htmlspecialchars(addslashes($product['name'])) ?> - Màu " + currentSelectedColor, currentSelectedColor);
}

function buyNow(productId) {
    const mainImg = document.getElementById('detailMainProductImage');
    const currentImgSrc = mainImg ? mainImg.src : "";
    window.location.href = `checkout.php?action=buy_now&id=${productId}&color=${encodeURIComponent(currentSelectedColor)}&rom=${encodeURIComponent(currentSelectedRom)}&extra=${currentExtraMoney}&img=${encodeURIComponent(currentImgSrc)}`;
}
</script>

<?php require_once 'includes/footer.php'; ?>
