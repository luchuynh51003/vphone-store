<?php
$product = $product ?? [];
$basePrice = $basePrice ?? 0;
$baseOldPrice = $baseOldPrice ?? 0;
$saving = $saving ?? 0;
$colorList = $colorList ?? [];

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
        <!-- Cột trái: Ảnh đổi theo màu -->
        <div class="col-lg-5">
            <div class="bg-white p-4 rounded-4 shadow-sm text-center position-relative border">
                <?php if ($product['is_featured']): ?>
                    <span class="badge badge-tech-new position-absolute top-0 start-0 m-3 px-3 py-2 rounded-pill shadow-sm">
                        <i class="fa-solid fa-bolt me-1 text-primary"></i>2026 FLAGSHIP
                    </span>
                <?php endif; ?>

                <div class="detail-img-box my-3 py-3" style="min-height: 360px; display: flex; align-items: center; justify-content: center;">
                    <img id="detailMainImg" src="<?= htmlspecialchars($product['image']) ?>" alt="" class="img-fluid" style="max-height: 340px; object-fit: contain; transition: all 0.25s ease;">
                </div>

                <div class="d-flex justify-content-center gap-2 mt-2">
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-shield-halved me-1"></i>Bảo hành 12T</span>
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-rotate-left me-1"></i>1 đổi 1 30 ngày</span>
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-truck-fast me-1"></i>Giao nhanh 2H</span>
                </div>
            </div>
        </div>

        <!-- Cột phải: Thông tin -->
        <div class="col-lg-7">
            <div class="bg-white p-4 rounded-4 shadow-sm h-100 d-flex flex-column border">
                <span class="badge badge-tech-new align-self-start px-3 py-1 mb-2 rounded-pill">
                    <?= htmlspecialchars($product['brand_name'] ?? 'CHÍNH HÃNG') ?>
                </span>
                <h3 class="fw-bold text-dark mb-2"><?= htmlspecialchars($product['name']) ?></h3>

                <div class="d-flex align-items-center gap-2 text-warning mb-3 small">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    <span class="text-secondary">(4.9/5 - 128 lượt mua)</span>
                </div>

                <div class="p-3 rounded-4 bg-light my-2 border">
                    <div class="d-flex align-items-baseline gap-3 flex-wrap">
                        <h2 class="text-danger fw-bold mb-0" id="displayPrice"><?= number_format($basePrice, 0, ',', '.') ?> đ</h2>
                        <h5 class="text-decoration-line-through text-secondary mb-0" id="displayOldPrice"><?= number_format($baseOldPrice, 0, ',', '.') ?> đ</h5>
                        <span class="badge bg-danger rounded-pill px-2 py-1" id="displaySavingTag">Tiết kiệm <?= number_format($saving, 0, ',', '.') ?> đ</span>
                    </div>
                    <div class="small text-success mt-2 fw-semibold">
                        <i class="fa-solid fa-circle-check me-1"></i>Còn <?= $product['quantity'] ?> máy sẵn tại kho V-Phone
                    </div>
                </div>

                <!-- 1. CHỌN MÀU SẮC -->
                <div class="my-2">
                    <label class="fw-bold mb-2 small text-uppercase text-secondary">
                        <i class="fa-solid fa-palette me-1 text-primary"></i>Chọn màu sắc: <span class="text-primary" id="chosenColorText"><?= htmlspecialchars($colorList[0] ?? 'Tiêu chuẩn') ?></span>
                    </label>
                    <div class="d-flex gap-2 flex-wrap" id="colorButtonsGroup">
                        <?php foreach ($colorList as $idx => $cName): ?>
                            <button type="button" class="btn btn-sm rounded-pill fw-bold px-3 py-2 color-pill-btn <?= $idx === 0 ? 'btn-primary active' : 'btn-outline-secondary' ?>" data-color="<?= htmlspecialchars($cName) ?>" onclick="selectDetailColor(this, '<?= htmlspecialchars(addslashes($cName)) ?>')">
                                <?= htmlspecialchars($cName) ?>
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
                        <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-bold storage-btn active" data-extra="0" data-rom="256 GB" onclick="selectDetailRom(this, '256 GB', 0)">
                            256 GB (Tiêu chuẩn)
                        </button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-bold storage-btn" data-extra="4000000" data-rom="512 GB" onclick="selectDetailRom(this, '512 GB', 4000000)">
                            512 GB (+4.0tr)
                        </button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-bold storage-btn" data-extra="9000000" data-rom="1 TB" onclick="selectDetailRom(this, '1 TB', 9000000)">
                            1 TB (+9.0tr)
                        </button>
                    </div>
                </div>

                <!-- 2 NÚT HÀNH ĐỘNG -->
                <div class="row g-2 mt-auto pt-3">
                    <div class="col-md-7 col-12">
                        <button type="button" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3" onclick="buyNowFromDetail(<?= $product['id'] ?>)">
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
const originalImgPath = "<?= htmlspecialchars($product['image']) ?>";

// BẢN ĐỒ KHỚP ĐÚNG CÁC FILE ẢNH MÀU BẠN ĐÃ TẠO
function resolveColorImage(colorName) {
    const c = colorName.toLowerCase();

    // Khớp các file bạn vừa tải trong thư mục
    if (c.includes("glacier blue") || c.includes("xanh glacier")) return "assets/images/products/iphone-18-promax-xanh-glacier-blue.png";
    if (c.includes("burgundy") || c.includes("đỏ")) return "assets/images/products/iphone-18-promax-do-burgundy.png";
    if (c.includes("bạc") || c.includes("silver")) return "assets/images/products/iphone-18-promax-bac.png";
    if (c.includes("đen") || c.includes("black")) return "assets/images/products/iphone-18-promax-den.png";
    if (c.includes("trắng ánh sao") || c.includes("ánh sao")) return "assets/images/products/iphone-18-duo-trang-anh-sao.png";
    if (c.includes("trời đêm")) return "assets/images/products/iphone-18-duo-troi-dem.png";
    if (c.includes("sa mạc")) return "assets/images/products/iphone-16-promax-sa-mac.png";
    if (c.includes("tự nhiên")) return "assets/images/products/iphone-16-promax-tu-nhien.png";
    if (c.includes("hồng")) return "assets/images/products/iphone-16-hong.png";
    if (c.includes("mòng két")) return "assets/images/products/iphone-16-xanh-mong-ket.png";
    if (c.includes("lưu ly")) return "assets/images/products/iphone-16-xanh-luu-ly.png";
    if (c.includes("xám titan") || c.includes("xám")) return "assets/images/products/samsung-s24-ultra-xam.jpg";
    if (c.includes("tím titan") || c.includes("tím")) return "assets/images/products/samsung-s24-ultra-tim.jpg";
    if (c.includes("vàng")) return "assets/images/products/samsung-s24-ultra-vang.jpg";

    return originalImgPath;
}

// BẤM NÚT ĐỔI MÀU LÀ ĐỔI ĐÚNG ẢNH TRÊN MÀN HÌNH
function selectDetailColor(btn, colorName) {
    document.querySelectorAll('.color-pill-btn').forEach(b => {
        b.className = "btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-2 color-pill-btn";
    });
    btn.className = "btn btn-sm btn-primary active rounded-pill fw-bold px-3 py-2 color-pill-btn";

    currentSelectedColor = colorName;
    document.getElementById('chosenColorText').innerText = colorName;

    const img = document.getElementById('detailMainImg');
    const newSrc = resolveColorImage(colorName);

    img.style.opacity = '0.2';
    setTimeout(() => {
        img.src = newSrc;
        img.style.opacity = '1';
    }, 120);
}

// ĐỔI BỘ NHỚ NHẢY TIỀN
function selectDetailRom(btn, romName, extra) {
    document.querySelectorAll('.storage-btn').forEach(b => {
        b.className = "btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-2 storage-btn";
    });
    btn.className = "btn btn-sm btn-primary active rounded-pill fw-bold px-3 py-2 storage-btn";

    currentSelectedRom = romName;
    currentExtraMoney = extra;

    const newP = basePrice + extra;
    const newOldP = baseOldPrice + extra;
    document.getElementById('displayPrice').innerText = new Intl.NumberFormat('vi-VN').format(newP) + ' đ';
    document.getElementById('displayOldPrice').innerText = new Intl.NumberFormat('vi-VN').format(newOldP) + ' đ';
}

function buyNowFromDetail(productId) {
    window.location.href = `checkout.php?action=buy_now&id=${productId}&color=${encodeURIComponent(currentSelectedColor)}&rom=${encodeURIComponent(currentSelectedRom)}&extra=${currentExtraMoney}`;
}

function addToCartFromDetail(btn, productId) {
    const selectedImage = resolveColorImage(currentSelectedColor);
    const url = `index.php?page=cart&action=add&ajax=1&id=${productId}&color=${encodeURIComponent(currentSelectedColor)}&rom=${encodeURIComponent(currentSelectedRom)}&extra=${currentExtraMoney}&img=${encodeURIComponent(selectedImage)}`;
    fetch(url).then(r => r.json()).then(data => {
        const badge = document.getElementById('cartBadge');
        if (badge) badge.innerText = data.cart_count;
        const toast = document.getElementById('vphoneLiveToast');
        const toastText = document.getElementById('vphoneToastText');
        if (toast && toastText) {
            toastText.innerText = `Đã thêm vào giỏ: Màu ${currentSelectedColor} (${currentSelectedRom})`;
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 3000);
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
