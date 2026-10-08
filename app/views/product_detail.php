<?php
$product = $product ?? [];
$basePrice = $basePrice ?? 0;
$baseOldPrice = $baseOldPrice ?? 0;
$saving = $saving ?? 0;
$colorList = $colorList ?? [];
$colorStockMap = $colorStockMap ?? [];
$firstAvailableColor = null;
foreach ($colorList as $c) {
    if (!isset($colorStockMap[$c]) || $colorStockMap[$c] > 0) {
        $firstAvailableColor = $c;
        break;
    }
}
if (!$firstAvailableColor && !empty($colorList)) {
    $firstAvailableColor = $colorList[0];
}
require_once 'app/views/includes/functions.php';
$storageOptions = json_decode($product['storage_options'] ?? '', true);
if (!is_array($storageOptions) || !$storageOptions) {
    $storageOptions = getStorageTiers($product['name'] ?? '', $product['rom'] ?? '256 GB');
}

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
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
                    <?php if ((int)$product['quantity'] > 0): ?>
                    <div class="small text-success mt-2 fw-semibold">
                        <i class="fa-solid fa-circle-check me-1"></i>Còn <?= $product['quantity'] ?> máy sẵn tại kho V-Phone
                    </div>
                    <?php else: ?>
                    <div class="small text-danger mt-2 fw-semibold">
                        <i class="fa-solid fa-circle-xmark me-1"></i>Sản phẩm hiện đã hết hàng
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 1. CHỌN MÀU SẮC -->
                <div class="my-2">
                    <label class="fw-bold mb-2 small text-uppercase text-secondary">
                        <i class="fa-solid fa-palette me-1 text-primary"></i>Chọn màu sắc: <span class="text-primary" id="chosenColorText"><?= htmlspecialchars($firstAvailableColor ?? ($colorList[0] ?? 'Tiêu chuẩn')) ?></span>
                    </label>
                    <div class="d-flex gap-2 flex-wrap" id="colorButtonsGroup">
                        <?php foreach ($colorList as $idx => $cName): ?>
                            <?php
                                $isOutOfStockColor = isset($colorStockMap[$cName]) && $colorStockMap[$cName] <= 0;
                                $isActive = ($cName === $firstAvailableColor && !$isOutOfStockColor);
                            ?>
                            <?php if ($isOutOfStockColor): ?>
                                <button type="button" class="btn btn-sm rounded-pill fw-bold px-3 py-2 color-pill-btn btn-outline-secondary opacity-50" disabled title="Màu này tạm hết hàng" style="cursor: not-allowed; text-decoration: line-through;">
                                    <?= htmlspecialchars($cName) ?> <span class="badge bg-secondary ms-1" style="font-size:0.6rem;">Hết</span>
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm rounded-pill fw-bold px-3 py-2 color-pill-btn <?= $isActive ? 'btn-primary active' : 'btn-outline-secondary' ?>" data-color="<?= htmlspecialchars($cName) ?>" onclick="selectDetailColor(this, '<?= htmlspecialchars(addslashes($cName)) ?>')">
                                    <?= htmlspecialchars($cName) ?>
                                    <?php if (isset($colorStockMap[$cName])): ?>
                                        <span class="badge bg-light text-primary border ms-1" style="font-size:0.6rem;"><?= $colorStockMap[$cName] ?></span>
                                    <?php endif; ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. CHỌN BỘ NHỚ -->
                <div class="my-2">
                    <label class="fw-bold mb-2 small text-uppercase text-secondary">
                        <i class="fa-solid fa-hard-drive me-1 text-primary"></i>Chọn dung lượng bộ nhớ:
                    </label>
                    <div class="d-flex gap-2 flex-wrap" id="storageButtonsGroup">
                        <?php foreach ($storageOptions as $index => $option): ?>
                            <button type="button" class="btn btn-sm <?= $index === 0 ? 'btn-primary active' : 'btn-outline-secondary' ?> rounded-pill px-3 py-2 fw-bold storage-btn" data-extra="<?= (int)$option['extra'] ?>" data-rom="<?= htmlspecialchars($option['rom']) ?>" onclick="selectDetailRom(this, '<?= htmlspecialchars(addslashes($option['rom'])) ?>', <?= (int)$option['extra'] ?>)">
                                <?= htmlspecialchars($option['label'] ?? $option['rom']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2 NÚT HÀNH ĐỘNG -->
                <div class="row g-2 mt-auto pt-3">
                    <?php if ((int)$product['quantity'] > 0): ?>
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
                    <?php else: ?>
                    <div class="col-12">
                        <button type="button" class="btn btn-secondary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3" disabled>
                            <i class="fa-solid fa-ban me-2"></i>SẢN PHẨM ĐÃ HẾT HÀNG
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- KHUNG ƯU ĐÃI ĐẶC QUYỀN V-PHONE -->
                <div class="mt-3 p-3 rounded-4 bg-light border">
                    <div class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1">
                        <i class="fa-solid fa-gift text-danger"></i> Ưu đãi đặc quyền khi mua tại V-Phone:
                    </div>
                    <ul class="list-unstyled mb-0 small text-secondary d-flex flex-column gap-1">
                        <li><i class="fa-solid fa-circle-check text-success me-1"></i> Tặng gói bảo hành VIP <strong>1 đổi 1 trong 30 ngày</strong> đầu tiên.</li>
                        <li><i class="fa-solid fa-circle-check text-success me-1"></i> Giảm thêm tới <strong>200.000đ</strong> khi nhập mã voucher trong giỏ hàng.</li>
                        <li><i class="fa-solid fa-circle-check text-success me-1"></i> Miễn phí giao hàng hỏa tốc trong 2 giờ nội thành.</li>
                        <li><i class="fa-solid fa-circle-check text-success me-1"></i> Trợ giá thu cũ đổi mới lên đến <strong>2.000.000đ</strong>.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- KHU VỰC THÔNG SỐ KỸ THUẬT & CHÍNH SÁCH BẢO HÀNH -->
    <div class="row g-4 mt-2">
        <div class="col-lg-7">
            <div class="bg-white p-4 rounded-4 shadow-sm border h-100">
                <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-microchip text-primary"></i> Thông Số Kỹ Thuật Chi Tiết
                </h5>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0 small">
                        <tbody>
                            <tr>
                                <td class="text-secondary fw-semibold" style="width: 35%;"><i class="fa-solid fa-mobile-screen me-2 text-primary"></i>Màn hình:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($product['screen'] ?? '6.7 inch AMOLED 120Hz') ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary fw-semibold"><i class="fa-solid fa-microchip me-2 text-primary"></i>Chip CPU:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($product['cpu'] ?? 'Snapdragon / Apple Bionic') ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary fw-semibold"><i class="fa-solid fa-memory me-2 text-primary"></i>RAM:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($product['ram'] ?? '8 GB') ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary fw-semibold"><i class="fa-solid fa-hard-drive me-2 text-primary"></i>Bộ nhớ trong:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($product['rom'] ?? '256 GB') ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary fw-semibold"><i class="fa-solid fa-battery-full me-2 text-primary"></i>Pin & Sạc:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($product['battery'] ?? '5000 mAh, sạc nhanh') ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary fw-semibold"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Tình trạng máy:</td>
                                <td>
                                    <span class="badge badge-soft-success rounded-pill px-3 py-1 fw-bold">
                                        <?= htmlspecialchars($product['condition_desc'] ?? 'Mới 100% nguyên hộp') ?>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="bg-white p-4 rounded-4 shadow-sm border h-100 d-flex flex-column justify-content-between">
                <div>
                    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-award text-warning"></i> Cam Kết Chất Lượng V-Phone
                    </h5>
                    <div class="d-flex flex-column gap-3 small">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary flex-shrink-0" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-box-check fs-6"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Hàng chính hãng 100%</div>
                                <div class="text-secondary">Nguyên seal, đầy đủ phụ kiện và hóa đơn bảo hành điện tử chính hãng.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success flex-shrink-0" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-rotate-left fs-6"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Lỗi là đổi mới trong 30 ngày</div>
                                <div class="text-secondary">Áp dụng cho mọi sản phẩm phát sinh lỗi từ nhà sản xuất.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-3 p-2 bg-info bg-opacity-10 text-info flex-shrink-0" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-truck-fast fs-6"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Giao nhanh hỏa tốc toàn quốc</div>
                                <div class="text-secondary">Kiểm tra máy trước khi thanh toán tiền, yên tâm tuyệt đối.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top text-center text-secondary small">
                    Hotline tư vấn kỹ thuật miễn phí: <strong class="text-primary fs-6">1800 6868</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const basePrice = <?= (int)$basePrice ?>;
const baseOldPrice = <?= (int)$baseOldPrice ?>;
const isIphone18Pro = <?= json_encode(strpos($product['name'], 'iPhone 18 Pro') === 0) ?>;
let currentSelectedRom = <?= json_encode($storageOptions[0]['rom'] ?? '256 GB', JSON_UNESCAPED_UNICODE) ?>;
let currentExtraMoney = <?= (int)($storageOptions[0]['extra'] ?? 0) ?>;
let currentSelectedColor = "<?= htmlspecialchars($firstAvailableColor ?? ($colorList[0] ?? 'Tiêu chuẩn')) ?>";
const originalImgPath = "<?= htmlspecialchars($product['image']) ?>";

// BẢN ĐỒ KHỚP ĐÚNG CÁC FILE ẢNH MÀU BẠN ĐÃ TẠO
function resolveColorImage(colorName) {
    const c = colorName.toLowerCase();

    // Khớp các file bạn vừa tải trong thư mục
    if (isIphone18Pro && (c.includes("glacier blue") || c.includes("xanh glacier"))) return "assets/images/products/iphone-18-promax-xanh-glacier-blue.png";
    if (isIphone18Pro && (c.includes("burgundy") || c.includes("đỏ"))) return "assets/images/products/iphone-18-promax-do-burgundy.png";
    if (isIphone18Pro && (c.includes("bạc") || c.includes("silver"))) return "assets/images/products/iphone-18-promax-bac.png";
    if (isIphone18Pro && (c.includes("đen") || c.includes("black"))) return "assets/images/products/iphone-18-promax-den.png";
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
    const selectedImage = resolveColorImage(currentSelectedColor);
    window.location.href = `index.php?page=checkout&action=buy_now&id=${productId}&color=${encodeURIComponent(currentSelectedColor)}&rom=${encodeURIComponent(currentSelectedRom)}&extra=${currentExtraMoney}&img=${encodeURIComponent(selectedImage)}`;
}

function addToCartFromDetail(btn, productId) {
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Đang thêm...';
    }
    const selectedImage = resolveColorImage(currentSelectedColor);
    const url = `index.php?page=cart&action=add&ajax=1&id=${productId}&color=${encodeURIComponent(currentSelectedColor)}&rom=${encodeURIComponent(currentSelectedRom)}&extra=${currentExtraMoney}&img=${encodeURIComponent(selectedImage)}`;
    fetch(url).then(r => r.json()).then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
        if (data.success === false) {
            alert(data.message || 'Không thể thêm vào giỏ hàng!');
            return;
        }
        const badge = document.getElementById('cartBadge');
        if (badge) badge.innerText = data.cart_count;
        const toast = document.getElementById('vphoneLiveToast');
        const toastText = document.getElementById('vphoneToastText');
        if (toast && toastText) {
            toastText.innerText = `Đã thêm vào giỏ: Màu ${currentSelectedColor} (${currentSelectedRom})`;
            toast.style.display = 'block';
            clearTimeout(window.toastTimer);
            window.toastTimer = setTimeout(() => toast.style.display = 'none', 3500);
        }
    }).catch(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
        window.location.href = `index.php?page=cart&action=add&id=${productId}&color=${encodeURIComponent(currentSelectedColor)}&rom=${encodeURIComponent(currentSelectedRom)}&extra=${currentExtraMoney}&img=${encodeURIComponent(selectedImage)}`;
    });
}
</script>

<?php require_once 'app/views/includes/footer.php'; ?>
