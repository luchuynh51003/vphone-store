<?php
require_once 'config/database.php';
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
    $finalP = ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) ? $p['sale_price'] : $p['price'];
    $searchList[] = [
        'id'        => (int)$p['id'],
        'name'      => $p['name'],
        'price'     => (int)$finalP,
        'old_price' => (int)$p['price'],
        'image'     => $p['image'],
        'rom'       => $p['rom'] ?? '256 GB',
        'colors'    => $p['colors'] ?? 'Đen, Trắng, Xanh'
    ];
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<script>
    window.STORE_PRODUCTS = <?= json_encode($searchList, JSON_UNESCAPED_UNICODE) ?>;
</script>

<!-- BANNER CAROUSEL 2026 -->
<div class="container my-3">
    <div id="vphoneCarousel" class="carousel slide carousel-fade shadow-sm" data-bs-ride="carousel" data-bs-interval="4500">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#vphoneCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#vphoneCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#vphoneCarousel" data-bs-slide-to="2"></button>
        </div>

        <div class="carousel-inner rounded-4">
            <div class="carousel-item active">
                <div class="carousel-banner-item banner-slide-1 p-4 p-md-5 text-white">
                    <div class="col-lg-7 py-3">
                        <span class="badge bg-white text-primary px-3 py-2 fw-bold rounded-pill mb-3">FLAGSHIP 2026</span>
                        <h1 class="display-5 fw-bold mb-2">iPhone 18 Pro Max & Duo</h1>
                        <p class="fs-5 opacity-90 mb-4">Màn hình gập Liquid Retina. Chip Apple A20 Pro Bionic thế hệ mới nhất.</p>
                        <a href="index.php?brand_id=1" class="btn btn-light text-primary btn-lg rounded-pill fw-bold px-4">
                            Đặt Trước Ngay <i class="fa-solid fa-chevron-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="carousel-item">
                <div class="carousel-banner-item banner-slide-2 p-4 p-md-5 text-white">
                    <div class="col-lg-7 py-3">
                        <span class="badge bg-info text-dark px-3 py-2 fw-bold rounded-pill mb-3">ĐỈNH CAO GẬP 3 MÀN HÌNH</span>
                        <h1 class="display-5 fw-bold mb-2">Galaxy Z Tri-Fold & S26 Ultra</h1>
                        <p class="fs-5 opacity-90 mb-4">Snapdragon 8 Gen 5 AI siêu việt. Trải nghiệm không gian hiển thị 10.2 inch.</p>
                        <a href="index.php?brand_id=2" class="btn btn-light text-primary btn-lg rounded-pill fw-bold px-4">
                            Khám Phá Galaxy <i class="fa-solid fa-chevron-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="carousel-item">
                <div class="carousel-banner-item banner-slide-3 p-4 p-md-5 text-white">
                    <div class="col-lg-7 py-3">
                        <span class="badge bg-white text-dark px-3 py-2 fw-bold rounded-pill mb-3">ĐẶC QUYỀN V-PHONE 2026</span>
                        <h1 class="display-5 fw-bold mb-2">Thu Cũ Đổi Mới Lên Đời 2026</h1>
                        <p class="fs-5 opacity-90 mb-4">Lên đời iPhone 18, Galaxy S26 Ultra trợ giá tới 5.000.000đ. Trả góp 0% lãi suất.</p>
                        <a href="#product-list" class="btn btn-outline-light btn-lg rounded-pill fw-bold px-4">
                            Săn Deal Hot <i class="fa-solid fa-arrow-down ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#vphoneCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#vphoneCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
    </div>
</div>

<!-- TIỆN ÍCH DỊCH VỤ -->
<div class="container my-4">
    <div class="row g-3">
        <div class="col-md-3 col-6">
            <div class="policy-card d-flex align-items-center">
                <i class="fa-solid fa-truck-fast text-primary fs-2 me-3"></i>
                <div><h6 class="fw-bold mb-0">Giao Hỏa Tốc 2H</h6><small class="text-secondary">Nội thành TP.HCM</small></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="policy-card d-flex align-items-center">
                <i class="fa-solid fa-shield-halved text-primary fs-2 me-3"></i>
                <div><h6 class="fw-bold mb-0">Bảo Hành 12T</h6><small class="text-secondary">Chính hãng 100%</small></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="policy-card d-flex align-items-center">
                <i class="fa-solid fa-rotate-left text-primary fs-2 me-3"></i>
                <div><h6 class="fw-bold mb-0">Lỗi 1 Đổi 1</h6><small class="text-secondary">Trong vòng 30 ngày</small></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="policy-card d-flex align-items-center">
                <i class="fa-solid fa-credit-card text-primary fs-2 me-3"></i>
                <div><h6 class="fw-bold mb-0">Trả Góp 0%</h6><small class="text-secondary">Duyệt nhanh 5 phút</small></div>
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
                $cardPrice = ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) ? $p['sale_price'] : $p['price'];
                $cList = array_map("trim", explode(",", $p["colors"] ?? "Đen, Trắng"));
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
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" onerror="this.onerror=null; this.src='assets/images/products/iphone-16.png';">
                    </div>

                    <div class="card-body d-flex flex-column pt-2">
                        <small class="text-primary fw-bold text-uppercase"><?= htmlspecialchars($p['brand_name'] ?? 'Khác') ?></small>
                        <div class="card-prod-title"><?= htmlspecialchars($p['name']) ?></div>

                        <div class="specs-badge my-1">
                            <span class="badge"><?= htmlspecialchars($p['ram']) ?></span>
                            <span class="badge text-primary fw-bold"><?= htmlspecialchars($p['rom']) ?></span>
                            <span class="badge"><?= htmlspecialchars($p['screen']) ?></span>
                        </div>

                        <!-- BẢNG MÀU CHỈ ĐỂ XEM LƯỚT -->
                        <div class="card-color-preview my-2 d-flex align-items-center flex-wrap gap-1" style="min-height: 24px;">
                            <small class="text-secondary fw-bold" style="font-size: 0.68rem;">
                                <i class="fa-solid fa-palette text-primary me-1"></i>Màu:
                            </small>
                            <?php foreach (array_slice($cList, 0, 3) as $cName): ?>
                                <span class="badge bg-white text-dark border px-2 py-1" style="font-size: 0.65rem; border-radius: 6px;">
                                    <?= htmlspecialchars($cName) ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if (count($cList) > 3): ?>
                                <span class="badge bg-light text-primary border" style="font-size: 0.65rem; font-weight: 700;">+<?= count($cList) - 3 ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="mt-auto pt-2">
                            <div class="text-danger fw-bold fs-5 mb-0"><?= formatPrice($cardPrice) ?></div>
                            <?php if ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']): ?>
                                <small class="text-decoration-line-through text-muted"><?= formatPrice($p['price']) ?></small>
                            <?php endif; ?>
                        </div>

                        <!-- 3 NÚT HÀNH ĐỘNG GỌN GÀNG -->
                        <div class="d-grid gap-2 mt-3">
                            <button type="button" class="btn btn-vphone btn-sm rounded-pill fw-bold py-2 shadow-sm text-center" onclick="openOrderModal(<?= $p['id'] ?>, 'buy')">
                                <i class="fa-solid fa-bolt me-1"></i>MUA NGAY
                            </button>
                            <div class="d-flex gap-2">
                                <a href="product-detail.php?id=<?= $p['id'] ?>" class="btn btn-outline-vphone btn-sm rounded-pill flex-grow-1 fw-semibold text-center">
                                    Chi tiết
                                </a>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill flex-grow-1 fw-bold" onclick="openOrderModal(<?= $p['id'] ?>, 'cart')">
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

<!-- MODAL CHỌN MÀU & BỘ NHỚ KHI BẤM MUA HOẶC THÊM GIỎ -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="modal-title fw-bold text-primary mb-0"><i class="fa-solid fa-sliders me-2"></i>Chọn Màu Sắc & Phiên Bản</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-3">
                <div class="d-flex align-items-center mb-3 p-3 bg-light rounded-4 border">
                    <div style="width: 75px; height: 75px; display:flex; align-items:center; justify-content:center; background:#fff; border-radius:12px; margin-right:15px; flex-shrink:0;">
                        <img id="popupImg" src="" alt="" style="max-height: 68px; max-width: 68px; object-fit: contain; transition: all 0.2s ease;">
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <h6 id="popupTitle" class="fw-bold text-dark mb-1 text-truncate">Tên điện thoại</h6>
                        <div class="d-flex align-items-baseline gap-2">
                            <span id="popupPrice" class="fs-5 fw-bold text-danger">0 đ</span>
                            <small id="popupOldPrice" class="text-decoration-line-through text-muted small">0 đ</small>
                        </div>
                        <small class="text-primary fw-semibold" id="popupColorNotice">Màu: Đang chọn...</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-secondary mb-1">
                        <i class="fa-solid fa-palette text-primary me-1"></i>Chọn màu sắc:
                    </label>
                    <div id="popupColorButtons" class="d-flex gap-2 flex-wrap"></div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-secondary mb-1">
                        <i class="fa-solid fa-hard-drive text-primary me-1"></i>Chọn dung lượng bộ nhớ:
                    </label>
                    <div id="popupRomButtons" class="d-flex gap-2 flex-wrap"></div>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary rounded-pill fw-bold py-2 shadow-sm" id="btnPopupAction">
                        XÁC NHẬN
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let curProduct = null;
let curColor = "";
let curRom = "";
let curExtra = 0;
let curAction = "buy";
let modalBs = null;

function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
}

function openOrderModal(productId, actionType) {
    const all = window.STORE_PRODUCTS || [];
    curProduct = all.find(p => p.id === productId);
    if (!curProduct) return;

    curAction = actionType;
    if (!modalBs) modalBs = new bootstrap.Modal(document.getElementById('orderModal'));

    const imgEl = document.getElementById('popupImg');
    imgEl.src = curProduct.image;
    document.getElementById('popupTitle').innerText = curProduct.name;
    document.getElementById('popupPrice').innerText = formatMoney(curProduct.price);
    document.getElementById('popupOldPrice').innerText = formatMoney(curProduct.old_price);

    // 1. Render nút màu sắc
    const colorContainer = document.getElementById('popupColorButtons');
    colorContainer.innerHTML = '';
    const colorArray = (curProduct.colors || 'Đen, Trắng, Xanh').split(',').map(c => c.trim());
    curColor = colorArray[0];
    document.getElementById('popupColorNotice').innerText = 'Màu: ' + curColor;

    colorArray.forEach((c, idx) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `btn btn-sm rounded-pill fw-bold px-3 py-1 ${idx === 0 ? 'btn-primary active text-white' : 'btn-outline-secondary'}`;
        btn.innerText = c;
        btn.onclick = function() {
            colorContainer.querySelectorAll('button').forEach(b => b.className = 'btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-1');
            this.className = 'btn btn-sm btn-primary active text-white rounded-pill fw-bold px-3 py-1';
            curColor = c;
            document.getElementById('popupColorNotice').innerText = 'Màu: ' + c;

            if (typeof getColorImage === 'function') {
                const targetImg = getColorImage(curProduct.image, c);
                imgEl.style.opacity = '0.3';
                setTimeout(() => { imgEl.src = targetImg; imgEl.style.opacity = '1'; }, 100);
            }
        };
        colorContainer.appendChild(btn);
    });

    // 2. Render nút dung lượng
    const romContainer = document.getElementById('popupRomButtons');
    romContainer.innerHTML = '';
    const nameLower = curProduct.name.toLowerCase();
    
    let romOptions = [];
    if (nameLower.includes("ultra") || nameLower.includes("pro max") || nameLower.includes("fold") || nameLower.includes("tri-fold") || nameLower.includes("duo")) {
        romOptions = [
            { rom: "256 GB", extra: 0, label: "256 GB (Tiêu chuẩn)" },
            { rom: "512 GB", extra: 4000000, label: "512 GB (+4.0tr)" },
            { rom: "1 TB", extra: 9000000, label: "1 TB (+9.0tr)" },
            { rom: "2 TB", extra: 16000000, label: "2 TB (+16tr)" }
        ];
    } else if (nameLower.includes("64") || nameLower.includes("11") || nameLower.includes("a05") || nameLower.includes("13c")) {
        romOptions = [
            { rom: "64 GB", extra: 0, label: "64 GB (Tiết kiệm)" },
            { rom: "128 GB", extra: 1200000, label: "128 GB (+1.2tr)" },
            { rom: "256 GB", extra: 2600000, label: "256 GB (+2.6tr)" },
            { rom: "512 GB", extra: 5000000, label: "512 GB (+5.0tr)" }
        ];
    } else {
        romOptions = [
            { rom: "128 GB", extra: 0, label: "128 GB (Tiêu chuẩn)" },
            { rom: "256 GB", extra: 2500000, label: "256 GB (+2.5tr)" },
            { rom: "512 GB", extra: 5500000, label: "512 GB (+5.5tr)" },
            { rom: "1 TB", extra: 10000000, label: "1 TB (+10tr)" }
        ];
    }

    curRom = romOptions[0].rom;
    curExtra = 0;

    romOptions.forEach((opt, idx) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `btn btn-sm rounded-pill fw-bold px-3 py-1 ${idx === 0 ? 'btn-primary active text-white' : 'btn-outline-secondary'}`;
        btn.innerText = opt.label;
        btn.onclick = function() {
            romContainer.querySelectorAll('button').forEach(b => b.className = 'btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-1');
            this.className = 'btn btn-sm btn-primary active text-white rounded-pill fw-bold px-3 py-1';
            curRom = opt.rom;
            curExtra = opt.extra;

            document.getElementById('popupPrice').innerText = formatMoney(curProduct.price + opt.extra);
            document.getElementById('popupOldPrice').innerText = formatMoney(curProduct.old_price + opt.extra);
        };
        romContainer.appendChild(btn);
    });

    const actionBtn = document.getElementById('btnPopupAction');
    if (actionType === 'buy') {
        actionBtn.className = "btn btn-primary rounded-pill fw-bold w-100 py-2 shadow-sm";
        actionBtn.innerHTML = '<i class="fa-solid fa-bolt me-1"></i> XÁC NHẬN MUA NGAY (GIAO TẬN NƠI)';
    } else {
        actionBtn.className = "btn btn-outline-primary rounded-pill fw-bold w-100 py-2";
        actionBtn.innerHTML = '<i class="fa-solid fa-cart-plus me-1"></i> XÁC NHẬN THÊM VÀO GIỎ HÀNG';
    }

    modalBs.show();
}

document.addEventListener("DOMContentLoaded", function() {
    const actionBtn = document.getElementById('btnPopupAction');
    if (actionBtn) {
        actionBtn.onclick = function() {
            if (!curProduct) return;
            if (modalBs) modalBs.hide();

            if (curAction === 'buy') {
                window.location.href = `checkout.php?action=buy_now&id=${curProduct.id}&rom=${encodeURIComponent(curRom)}&color=${encodeURIComponent(curColor)}&extra=${curExtra}`;
            } else {
                const url = `cart.php?action=add&ajax=1&id=${curProduct.id}&rom=${encodeURIComponent(curRom)}&color=${encodeURIComponent(curColor)}&extra=${curExtra}`;
                fetch(url)
                    .then(res => res.text())
                    .then(raw => {
                        const data = JSON.parse(raw.trim());
                        if (data.success) {
                            const badge = document.getElementById('cartBadge');
                            if (badge) badge.innerText = data.cart_count;

                            const toast = document.getElementById('vphoneLiveToast');
                            const toastText = document.getElementById('vphoneToastText');
                            if (toast && toastText) {
                                toastText.innerText = `Đã thêm "${curProduct.name} - Màu ${curColor} (${curRom})"`;
                                toast.style.display = "block";
                                clearTimeout(window.toastTimer);
                                window.toastTimer = setTimeout(() => { toast.style.display = "none"; }, 3500);
                            }
                        }
                    });
            }
        };
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
