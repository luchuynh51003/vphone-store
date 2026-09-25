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

// Danh sách màu sắc từ CSDL
$colorList = array_map('trim', explode(',', $product['colors'] ?? 'Đen, Trắng, Xanh'));
if (empty($colorList[0])) $colorList = ['Đen', 'Trắng'];
$firstColor = $colorList[0];

// Hàm lấy mã màu HEX
function getColorHexPhp($name) {
    $c = mb_strtolower($name, 'UTF-8');
    if (strpos($c, 'sa mạc') !== false || strpos($c, 'vàng') !== false || strpos($c, 'gold') !== false) return '#cbbba0';
    if (strpos($c, 'hồng') !== false || strpos($c, 'pink') !== false) return '#f472b6';
    if (strpos($c, 'tím') !== false || strpos($c, 'purple') !== false) return '#8b5cf6';
    if (strpos($c, 'xanh') !== false || strpos($c, 'blue') !== false || strpos($c, 'navy') !== false) return '#38bdf8';
    if (strpos($c, 'đen') !== false || strpos($c, 'black') !== false || strpos($c, 'phantom') !== false) return '#1e293b';
    if (strpos($c, 'trắng') !== false || strpos($c, 'white') !== false || strpos($c, 'bạc') !== false || strpos($c, 'tự nhiên') !== false) return '#e2e8f0';
    return '#0066cc';
}

$relStmt = $pdo->prepare("SELECT * FROM products WHERE brand_id = :bid AND id != :id LIMIT 4");
$relStmt->execute([':bid' => $product['brand_id'], ':id' => $id]);
$relatedProducts = $relStmt->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<!-- Đường dẫn Breadcrumb -->
<div class="container my-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb py-2 px-3 bg-white rounded-4 shadow-sm mb-0">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-primary"><i class="fa-solid fa-house me-1"></i>Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="index.php?brand_id=<?= $product['brand_id'] ?>" class="text-decoration-none text-primary"><?= htmlspecialchars($product['brand_name'] ?? 'Điện thoại') ?></a></li>
            <li class="breadcrumb-item active text-truncate"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>
</div>

<!-- KHU VỰC CHI TIẾT SẢN PHẨM -->
<div class="container my-4">
    <div class="row g-4">
        <!-- CỘT TRÁI: ẢNH MÁY TO RÕ (TỰ ĐỔI MÀU THEO NÚT BẤM) -->
        <div class="col-lg-5">
            <div class="bg-white p-4 rounded-4 shadow-sm text-center position-relative border">
                <?php if ($product['is_featured']): ?>
                    <span class="badge badge-tech-new position-absolute top-0 start-0 m-3 px-3 py-2 rounded-pill shadow-sm">
                        <i class="fa-solid fa-bolt me-1 text-primary"></i>2026 FLAGSHIP
                    </span>
                <?php endif; ?>

                <div class="detail-img-box my-3 py-3" style="min-height: 360px; display: flex; align-items: center; justify-content: center;">
                    <img id="detailMainImg" 
                         src="<?= htmlspecialchars($product['image']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="img-fluid" 
                         style="max-height: 340px; object-fit: contain; transition: all 0.25s ease;"
                         onerror="this.onerror=null; this.src='assets/images/products/iphone-16.png';">
                </div>

                <div class="d-flex justify-content-center gap-2 mt-2">
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-shield-halved me-1"></i>Bảo hành 12T</span>
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-rotate-left me-1"></i>1 đổi 1 30 ngày</span>
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-truck-fast me-1"></i>Giao hỏa tốc 2H</span>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI: GIÁ BÁN, CHỌN MÀU SẮC, CHỌN BỘ NHỚ NHẢY TIỀN -->
        <div class="col-lg-7">
            <div class="bg-white p-4 rounded-4 shadow-sm h-100 d-flex flex-column border">
                <span class="badge badge-tech-new align-self-start px-3 py-1 mb-2 rounded-pill">
                    <?= htmlspecialchars($product['brand_name'] ?? 'CHÍNH HÃNG') ?>
                </span>
                <h3 class="fw-bold text-dark mb-2"><?= htmlspecialchars($product['name']) ?></h3>

                <!-- Đánh giá sao -->
                <div class="d-flex align-items-center gap-2 text-warning mb-3 small">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    <span class="text-secondary">(4.9/5 - 128 lượt mua đánh giá)</span>
                </div>

                <!-- Bảng giá tiền nhảy số -->
                <div class="p-3 rounded-4 bg-light my-2 border">
                    <div class="d-flex align-items-baseline gap-3 flex-wrap">
                        <h2 class="text-danger fw-bold mb-0" id="displayPrice"><?= number_format($basePrice, 0, ',', '.') ?> đ</h2>
                        <h5 class="text-decoration-line-through text-secondary mb-0" id="displayOldPrice"><?= number_format($baseOldPrice, 0, ',', '.') ?> đ</h5>
                        <span class="badge bg-danger rounded-pill px-2 py-1" id="displaySavingTag">Tiết kiệm <?= number_format($saving, 0, ',', '.') ?> đ</span>
                    </div>
                    <div class="small text-success mt-2 fw-semibold">
                        <i class="fa-solid fa-circle-check me-1"></i>Còn <?= $product['quantity'] ?> máy sẵn hàng tại kho - Miễn phí giao tận nơi
                    </div>
                </div>

                <!-- 1. MỤC CHỌN MÀU SẮC (CÓ CHẤM TRÒN MÀU THẬT & ĐỔI ẢNH MÁY TO BÊN TRÁI) -->
                <div class="my-3">
                    <label class="fw-bold mb-2 small text-uppercase text-secondary">
                        <i class="fa-solid fa-palette text-primary me-1"></i>Chọn màu sắc: <span id="chosenColorText" class="text-primary fw-bold"><?= htmlspecialchars($firstColor) ?></span>
                    </label>
                    <div class="d-flex gap-2 flex-wrap" id="detailColorButtonsGroup">
                        <?php foreach ($colorList as $idx => $cName): ?>
                            <?php $hex = getColorHexPhp($cName); ?>
                            <button type="button" 
                                    class="btn btn-sm rounded-pill fw-bold px-3 py-2 detail-color-btn d-flex align-items-center <?= $idx === 0 ? 'btn-primary active text-white' : 'btn-outline-secondary' ?>" 
                                    data-color="<?= htmlspecialchars($cName) ?>"
                                    onclick="selectDetailColor(this, '<?= htmlspecialchars(addslashes($cName)) ?>')">
                                <span style="width:13px; height:13px; border-radius:50%; background:<?= $hex ?>; display:inline-block; margin-right:7px; border:1px solid rgba(0,0,0,0.2);"></span>
                                <?= htmlspecialchars($cName) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. MỤC CHỌN PHIÊN BẢN BỘ NHỚ (BẤM ĐỔI LÀ GIÁ TIỀN NHẢY NGAY) -->
                <div class="my-2">
                    <label class="fw-bold mb-2 small text-uppercase text-secondary">
                        <i class="fa-solid fa-hard-drive text-primary me-1"></i>Chọn phiên bản bộ nhớ (Bấm để đổi giá):
                    </label>
                    <div class="d-flex gap-2 flex-wrap" id="detailStorageButtonsGroup">
                        <!-- Sẽ được Javascript tự động điền đủ 4 mức: 256GB, 512GB, 1TB, 2TB -->
                    </div>
                </div>

                <div class="border border-primary border-opacity-25 rounded-4 p-3 my-2 bg-light">
                    <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-gift me-2"></i>Đặc Quyền Mua Hàng Tại V-Phone:</h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-1 text-secondary">
                        <li><i class="fa-solid fa-check text-primary me-2"></i>Bảo hành chính hãng toàn diện 12 tháng 1 đổi 1.</li>
                        <li><i class="fa-solid fa-check text-primary me-2"></i>Thu cũ đổi mới trợ giá trực tiếp lên đến 5.000.000 đ.</li>
                    </ul>
                </div>

                <!-- 2 NÚT HÀNH ĐỘNG -->
                <div class="row g-2 mt-auto pt-3">
                    <div class="col-md-7 col-12">
                        <button type="button" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3" onclick="buyNowFromDetail(<?= $product['id'] ?>)">
                            <i class="fa-solid fa-bag-shopping me-2"></i>MUA NGAY (GIAO TẬN NƠI)
                        </button>
                    </div>
                    <div class="col-md-5 col-12">
                        <button type="button" class="btn btn-outline-vphone btn-lg w-100 rounded-pill fw-bold py-3" onclick="addToCartFromDetailPage(this, <?= $product['id'] ?>)">
                            <i class="fa-solid fa-cart-plus me-2"></i>Thêm vào giỏ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BẢNG THÔNG SỐ KỸ THUẬT -->
    <div class="my-5">
        <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border">
            <h4 class="fw-bold text-dark border-bottom pb-3 mb-4">
                <i class="fa-solid fa-sliders text-primary me-2"></i>Bảng Thông Số Kỹ Thuật Chi Tiết
            </h4>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <table class="table table-striped table-hover align-middle border rounded-3 overflow-hidden">
                        <tbody>
                            <tr><th class="text-secondary py-3 ps-3 w-40">Màn hình:</th><td class="fw-semibold py-3"><?= htmlspecialchars($product['screen']) ?></td></tr>
                            <tr><th class="text-secondary py-3 ps-3">Chipset CPU:</th><td class="fw-semibold py-3"><?= htmlspecialchars($product['cpu']) ?></td></tr>
                            <tr><th class="text-secondary py-3 ps-3">Bộ nhớ RAM:</th><td class="fw-semibold py-3"><?= htmlspecialchars($product['ram']) ?></td></tr>
                            <tr><th class="text-secondary py-3 ps-3">Bộ nhớ trong:</th><td class="fw-bold text-primary py-3" id="tableRomValue"><?= htmlspecialchars($product['rom']) ?></td></tr>
                            <tr><th class="text-secondary py-3 ps-3">Dung lượng Pin:</th><td class="fw-semibold py-3"><?= htmlspecialchars($product['battery']) ?></td></tr>
                            <tr><th class="text-secondary py-3 ps-3">Màu sắc sẵn có:</th><td class="fw-semibold py-3"><?= htmlspecialchars($product['colors'] ?? 'Đen, Trắng, Xanh') ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const basePrice = <?= (int)$basePrice ?>;
const baseOldPrice = <?= (int)$baseOldPrice ?>;
const prodName = "<?= htmlspecialchars(addslashes($product['name'])) ?>";
let currentSelectedColor = "<?= htmlspecialchars(addslashes($firstColor)) ?>";
let currentSelectedRom = "256 GB";
let currentExtraMoney = 0;

function formatCurrency(number) {
    return new Intl.NumberFormat('vi-VN').format(number) + ' đ';
}

// 1. HÀM CHỌN MÀU SẮC & ĐỔI ẢNH MÁY TO BÊN TRÁI
function selectDetailColor(btn, colorName) {
    document.querySelectorAll('.detail-color-btn').forEach(b => {
        b.className = 'btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-2 detail-color-btn d-flex align-items-center';
    });
    btn.className = 'btn btn-sm btn-primary active text-white rounded-pill fw-bold px-3 py-2 detail-color-btn d-flex align-items-center';

    currentSelectedColor = colorName;
    document.getElementById('chosenColorText').innerText = colorName;

    // Đổi ảnh máy to bên trái
    const mainImg = document.getElementById('detailMainImg');
    if (mainImg && typeof getColorImage === 'function') {
        const newSrc = getColorImage(mainImg.getAttribute('src'), colorName);
        mainImg.style.opacity = '0.3';
        setTimeout(() => {
            mainImg.src = newSrc;
            mainImg.style.opacity = '1';
        }, 120);
    }
}

// 2. RENDER ĐẦY ĐỦ CÁC MỨC DUNG LƯỢNG BỘ NHỚ (256GB, 512GB, 1TB, 2TB)
document.addEventListener("DOMContentLoaded", function () {
    const romContainer = document.getElementById("detailStorageButtonsGroup");
    if (!romContainer) return;

    let options = [];
    const nameLower = prodName.toLowerCase();

    if (nameLower.includes("ultra") || nameLower.includes("pro max") || nameLower.includes("fold") || nameLower.includes("tri-fold") || nameLower.includes("duo")) {
        options = [
            { rom: "256 GB", extra: 0, label: "256 GB (Tiêu chuẩn)" },
            { rom: "512 GB", extra: 4000000, label: "512 GB (+4.0tr)" },
            { rom: "1 TB", extra: 9000000, label: "1 TB (+9.0tr)" },
            { rom: "2 TB", extra: 16000000, label: "2 TB (+16tr)" }
        ];
    } else if (nameLower.includes("64") || nameLower.includes("11") || nameLower.includes("a05") || nameLower.includes("13c")) {
        options = [
            { rom: "64 GB", extra: 0, label: "64 GB (Tiết kiệm)" },
            { rom: "128 GB", extra: 1200000, label: "128 GB (+1.2tr)" },
            { rom: "256 GB", extra: 2600000, label: "256 GB (+2.6tr)" },
            { rom: "512 GB", extra: 5000000, label: "512 GB (+5.0tr)" }
        ];
    } else {
        options = [
            { rom: "128 GB", extra: 0, label: "128 GB (Tiêu chuẩn)" },
            { rom: "256 GB", extra: 2500000, label: "256 GB (+2.5tr)" },
            { rom: "512 GB", extra: 5500000, label: "512 GB (+5.5tr)" },
            { rom: "1 TB", extra: 10000000, label: "1 TB (+10tr)" }
        ];
    }

    currentSelectedRom = options[0].rom;
    currentExtraMoney = 0;

    options.forEach((opt, idx) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = `btn btn-sm rounded-pill fw-bold px-3 py-2 storage-btn ${idx === 0 ? "btn-primary active text-white" : "btn-outline-secondary"}`;
        btn.innerText = opt.label;
        btn.onclick = function() {
            romContainer.querySelectorAll(".storage-btn").forEach(b => {
                b.className = "btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-2 storage-btn";
            });
            this.className = "btn btn-sm btn-primary active text-white rounded-pill fw-bold px-3 py-2 storage-btn";

            currentExtraMoney = opt.extra;
            currentSelectedRom = opt.rom;

            // Nhảy tiền ngay lập tức
            const newPrice = basePrice + opt.extra;
            const newOldPrice = baseOldPrice + opt.extra;
            document.getElementById('displayPrice').innerText = formatCurrency(newPrice);
            document.getElementById('displayOldPrice').innerText = formatCurrency(newOldPrice);
            
            const savingTag = document.getElementById('displaySavingTag');
            if (savingTag) savingTag.innerText = 'Tiết kiệm ' + formatCurrency(newOldPrice - newPrice);

            const tableRom = document.getElementById('tableRomValue');
            if (tableRom) tableRom.innerText = opt.rom;
        };
        romContainer.appendChild(btn);
    });
});

// 3. NÚT THÊM GIỎ HÀNG: LƯU ĐÚNG MÀU ĐÃ CHỌN VÀ BỘ NHỚ
function addToCartFromDetailPage(btn, productId) {
    const fullNameWithVariant = `${prodName} - Màu ${currentSelectedColor} (${currentSelectedRom})`;
    const url = `cart.php?action=add&ajax=1&id=${productId}&color=${encodeURIComponent(currentSelectedColor)}&rom=${encodeURIComponent(currentSelectedRom)}&extra=${currentExtraMoney}`;
    
    addToCartDirect(btn, productId, fullNameWithVariant, currentSelectedColor);
}

// 4. NÚT MUA NGAY: ĐƯA ĐÚNG MÀU ĐÃ CHỌN VÀO THẲNG THANH TOÁN
function buyNowFromDetail(productId) {
    window.location.href = `checkout.php?action=buy_now&id=${productId}&color=${encodeURIComponent(currentSelectedColor)}&rom=${encodeURIComponent(currentSelectedRom)}&extra=${currentExtraMoney}`;
}
</script>

<?php require_once 'includes/footer.php'; ?>
