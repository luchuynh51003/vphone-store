<?php
require_once 'app/views/includes/functions.php';
$products = $products ?? [];
$brands = $brands ?? [];
$brandId = $brandId ?? 0;
$keyword = $keyword ?? '';
$ratingMap = $ratingMap ?? [];

$searchList = [];
foreach ($products as $p) {
    $pPrice = ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) ? $p['sale_price'] : $p['price'];
    $storageOptions = json_decode($p['storage_options'] ?? '', true);
    if (!is_array($storageOptions) || !$storageOptions) {
        $storageOptions = getStorageTiers($p['name'], $p['rom'] ?? '256 GB');
    }
    $searchList[] = [
        'id' => (int)$p['id'],
        'name' => $p['name'],
        'price' => (int)$pPrice,
        'old_price' => (int)$p['price'],
        'price_formatted' => number_format($pPrice, 0, ',', '.') . ' VNĐ',
        'image' => $p['image'],
        'rom' => $p['rom'] ?? '256 GB',
        'storage_options' => $storageOptions,
        'colors' => $p['colors'] ?? 'Đen, Trắng, Xanh',
        'quantity' => (int)($p['quantity'] ?? 0),
        'color_quantities' => json_decode($p['color_quantities'] ?? '', true) ?: []
    ];
}

function getHexColorBadge(string $name): string {
    return getColorHexValue($name);
}

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<script>
    window.PAGE_PRODUCTS = <?= json_encode($searchList, JSON_UNESCAPED_UNICODE) ?>;
    if (!window.STORE_PRODUCTS || window.STORE_PRODUCTS.length === 0) {
        window.STORE_PRODUCTS = window.GLOBAL_SEARCH_PRODUCTS || window.PAGE_PRODUCTS;
    }
    window.PRODUCT_COLOR_MAP = <?= json_encode(getAllProductColorMap(), JSON_UNESCAPED_UNICODE) ?>;
</script>

<div class="container my-3">
    <div id="vphoneCarousel" class="carousel slide carousel-fade carousel-banner-vip shadow-lg" data-bs-ride="carousel" data-bs-interval="4000" data-bs-pause="false">
        <div class="carousel-indicators carousel-indicators-vip">
            <button type="button" data-bs-target="#vphoneCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#vphoneCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#vphoneCarousel" data-bs-slide-to="2"></button>
        </div>

        <div class="carousel-inner h-100">
            <div class="carousel-item active h-100">
                <div class="banner-bg-apple px-4 px-md-5 text-white d-flex align-items-center h-100">
                    <div class="row align-items-center w-100 g-4">
                        <div class="col-lg-7 col-md-7">
                            <span class="badge bg-white text-primary px-3 py-1 mb-2 fw-bold rounded-pill shadow-sm" style="font-size:0.78rem;">
                                <i class="fa-brands fa-apple me-1"></i> SIÊU PHẨM GẬP ĐẦU TIÊN CỦA APPLE
                            </span>
                            <div class="banner-title-equal text-white mb-2">iPhone 18 Pro Max & iPhone Duo</div>
                            <div class="banner-desc-equal mb-3">
                                Khung Titan Vũ Trụ siêu bền. Màn hình gập đôi Liquid Retina không nếp gấp cùng vi xử lý Apple A20 Pro 2nm thế hệ mới nhất.
                            </div>
                            <div class="d-flex gap-2 flex-wrap mb-3">
                                <span class="badge glass-spec-pill"><i class="fa-solid fa-microchip text-info me-1"></i>A20 Pro (2nm)</span>
                                <span class="badge glass-spec-pill"><i class="fa-solid fa-camera text-warning me-1"></i>Camera 200x</span>
                                <span class="badge glass-spec-pill"><i class="fa-solid fa-shield-halved text-success me-1"></i>Bảo hành 1-1</span>
                            </div>
                            <div class="d-flex gap-3 align-items-center flex-wrap">
                                <a href="index.php?page=detail&id=1" class="btn btn-light text-primary btn-md rounded-pill fw-bold px-4 py-2 shadow">
                                    <i class="fa-solid fa-bolt me-1"></i>Đặt Mua Ngay
                                </a>
                                <span class="fw-bold text-white fs-6">Chỉ từ 41.990.000 VNĐ</span>
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-5 text-center d-flex align-items-center justify-content-center justify-content-lg-end h-100 position-relative pe-lg-3">
                            <div class="banner-stage-glow banner-glow-apple"></div>
                            <div class="banner-dual-stage banner-stage-apple">
                                <img src="assets/images/products/iphone-18-promax.png" alt="iPhone 18 Pro Max" class="banner-phone-left-float" onerror="this.src='assets/images/products/iphone-16-promax.png';">
                                <img src="assets/images/products/iphone-18-duo.png" alt="iPhone Duo" class="banner-phone-right-float">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="carousel-item h-100">
                <div class="banner-bg-samsung px-4 px-md-5 text-white d-flex align-items-center h-100">
                    <div class="row align-items-center w-100 g-4">
                        <div class="col-lg-7 col-md-7">
                            <span class="badge bg-warning text-dark px-3 py-1 mb-2 fw-bold rounded-pill shadow-sm" style="font-size:0.78rem;">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> GALAXY AI THẾ HỆ MỚI
                            </span>
                            <div class="banner-title-equal text-white mb-2">Galaxy S26 Ultra & Tri-Fold</div>
                            <div class="banner-desc-equal mb-3">
                                Mở rộng không gian hiển thị 10.2 inch với cơ chế gập ba độc bản. Trợ lý Galaxy AI quyền năng cùng cụm camera 200MP đột phá.
                            </div>
                            <div class="d-flex gap-2 flex-wrap mb-3">
                                <span class="badge glass-spec-pill"><i class="fa-solid fa-bolt text-warning me-1"></i>Snapdragon 8 Gen 5</span>
                                <span class="badge glass-spec-pill"><i class="fa-solid fa-layer-group text-info me-1"></i>Màn Gập 3 Độc Bản 10.2"</span>
                                <span class="badge glass-spec-pill"><i class="fa-solid fa-pen text-success me-1"></i>Bút S-Pen Quyền Năng</span>
                            </div>
                            <div class="d-flex gap-3 align-items-center flex-wrap">
                                <a href="index.php?page=detail&id=6" class="btn btn-warning text-dark btn-md rounded-pill fw-bold px-4 py-2 shadow">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Khám Phá Galaxy AI
                                </a>
                                <span class="fw-bold text-white fs-6">Chỉ từ 34.990.000 VNĐ</span>
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-5 text-center d-flex align-items-center justify-content-center justify-content-lg-end h-100 position-relative pe-lg-3">
                            <div class="banner-stage-glow banner-glow-samsung"></div>
                            <div class="banner-dual-stage banner-stage-samsung">
                                <img src="assets/images/products/samsung-s26-ultra.png" alt="Galaxy S26 Ultra" class="banner-phone-left-float">
                                <img src="assets/images/products/samsung-tri-fold.png" alt="Galaxy Tri-Fold" class="banner-phone-right-float">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="carousel-item h-100">
                <div class="banner-bg-promo px-4 px-md-5 text-white d-flex align-items-center h-100">
                    <div class="row align-items-center w-100 g-4">
                        <div class="col-lg-7 col-md-7">
                            <span class="badge bg-danger text-white px-3 py-1 mb-2 fw-bold rounded-pill shadow-sm" style="font-size:0.78rem;">
                                <i class="fa-solid fa-fire me-1"></i> ĐẶC QUYỀN V-PHONE 2026
                            </span>
                            <div class="banner-title-equal text-white mb-2">Thu Cũ Đổi Mới Lên Đời</div>
                            <div class="banner-desc-equal mb-3">
                                Lên đời điện thoại Flagship cực dễ dàng. Trợ giá trực tiếp lên đến 5.000.000 VNĐ, duyệt hồ sơ trong 5 phút, trả góp 0% lãi suất.
                            </div>
                            <div class="d-flex gap-2 flex-wrap mb-3">
                                <span class="badge glass-spec-pill"><i class="fa-solid fa-money-bill-wave text-success me-1"></i>Trợ giá 5 triệu</span>
                                <span class="badge glass-spec-pill"><i class="fa-solid fa-credit-card text-warning me-1"></i>Trả góp 0% lãi</span>
                                <span class="badge glass-spec-pill"><i class="fa-solid fa-shield-halved text-info me-1"></i>Bảo hành VIP 1-1</span>
                            </div>
                            <div class="d-flex gap-3 align-items-center flex-wrap">
                                <a href="#product-list" class="btn btn-outline-light btn-md rounded-pill fw-bold px-4 py-2 shadow-sm">
                                    <i class="fa-solid fa-arrow-down me-1"></i>Săn Deal Ngay Hôm Nay
                                </a>
                                <span class="fw-bold text-white fs-6">Trợ giá đến 5.000.000 VNĐ</span>
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-5 text-center d-flex align-items-center justify-content-center justify-content-lg-end h-100 position-relative pe-lg-3">
                            <div class="banner-stage-glow banner-glow-promo"></div>
                            <div class="banner-dual-stage banner-stage-promo justify-content-center">
                                <img src="assets/images/products/iphone-16-promax.png" alt="iPhone 16 Pro Max" class="banner-phone-single-float">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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

<style>
@keyframes fadeInProduct {
    0% { opacity: 0; transform: translateY(14px) scale(0.98); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}
.product-col-card {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
}

.brand-filter-wrapper {
    background: #ffffff;
    border: 1px solid #eef2f6;
    border-radius: 18px;
    padding: 10px 14px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}
.brand-filter-scroll {
    display: flex;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    flex-wrap: nowrap;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding: 2px;
    -webkit-overflow-scrolling: touch;
}
.brand-filter-scroll::-webkit-scrollbar {
    display: none;
}

.btn-brand-pill {
    height: 40px;
    min-width: 86px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 16px;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #334155;
    font-weight: 600;
    font-size: 0.85rem;
    white-space: nowrap;
    flex-shrink: 0;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    cursor: pointer;
}
.btn-brand-pill:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}
.btn-brand-pill.active {
    background: #0066cc !important;
    border-color: #0066cc !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(0, 102, 204, 0.35) !important;
}

.brand-pill-icon {
    height: 18px;
    width: auto;
    max-width: 22px;
    object-fit: contain;
    display: block;
    flex-shrink: 0;
}
.brand-pill-wordmark {
    height: 14px;
    width: auto;
    max-width: 82px;
    object-fit: contain;
    display: block;
    flex-shrink: 0;
}
.brand-logo-apple { max-height: 18px; max-width: 18px; }
.brand-logo-samsung { max-height: 14px; max-width: 85px; }
.brand-logo-xiaomi { max-height: 18px; max-width: 18px; border-radius: 4px; }
.brand-logo-oppo { max-height: 14px; max-width: 82px; }
.brand-logo-vivo { max-height: 14px; max-width: 54px; }
.brand-logo-google { max-height: 18px; max-width: 18px; }
.brand-logo-asus { max-height: 18px; max-width: 26px; }
.brand-logo-sony { max-height: 14px; max-width: 80px; }
.brand-logo-huawei { max-height: 19px; max-width: 20px; }

.btn-brand-pill.active .brand-logo-invert {
    filter: brightness(0) invert(1) !important;
}
.btn-brand-pill.active .pill-icon-all {
    color: #ffffff !important;
}

/* Compact Multi-criteria filter styles */
.compact-filter-toolbar {
    border-radius: 18px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
}
.btn-filter-dropdown {
    height: 38px;
    padding: 0 14px;
    border-radius: 999px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #334155;
    font-weight: 600;
    font-size: 0.84rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}
.btn-filter-dropdown:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}
.btn-filter-dropdown.active {
    background: #eff6ff !important;
    border-color: #0066cc !important;
    color: #0066cc !important;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(0, 102, 204, 0.15) !important;
}
.btn-filter-dropdown.dropdown-toggle::after {
    margin-left: 4px;
    vertical-align: middle;
}
.dropdown-item {
    font-size: 0.84rem;
    font-weight: 500;
    color: #334155;
    transition: all 0.15s ease;
}
.dropdown-item:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.dropdown-item.active {
    background: #0066cc !important;
    color: #ffffff !important;
    font-weight: 600;
}
</style>

<div class="container my-3">
    <!-- 1. HÃNG SẢN XUẤT (Brand Pills) -->
    <div class="brand-filter-wrapper mb-3">
        <div class="brand-filter-scroll" id="brandFilterBar">
            <span class="fw-bold text-primary me-1 d-none d-lg-inline-flex align-items-center" style="font-size:0.85rem;">
                <i class="fa-solid fa-mobile-screen-button me-1"></i>Hãng:
            </span>
            <a href="index.php" class="btn btn-brand-pill <?= $brandId == 0 ? 'active btn-primary text-white' : 'btn-outline-secondary' ?>" data-brand-id="0" data-brand-name="">
                <i class="fa-solid fa-layer-group text-primary pill-icon-all"></i>
                <span>Tất cả</span>
            </a>
            <?php foreach ($brands as $b): ?>
                <?= renderBrandFilterPill($b, $brandId) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 2. BỘ LỌC ĐA TIÊU CHÍ & SẮP XẾP (GỌN GÀNG TRÊN 1 DÒNG DUY NHẤT) -->
    <div class="compact-filter-toolbar bg-white p-2.5 px-3 border shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2">
        <!-- Nhóm các nút Dropdown Lọc -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fw-bold text-secondary small d-none d-md-inline-flex align-items-center me-1">
                <i class="fa-solid fa-filter text-primary me-1"></i>Bộ lọc:
            </span>

            <!-- Mức giá Dropdown -->
            <div class="dropdown">
                <button class="btn btn-sm btn-filter-dropdown dropdown-toggle" type="button" id="dropdownPriceBtn" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                    <i class="fa-solid fa-money-bill-wave text-success me-1"></i>
                    <span id="priceBtnLabel">Mức giá</span>
                </button>
                <div class="dropdown-menu dropdown-menu-start shadow-lg border-0 rounded-4 p-2" style="min-width: 250px; z-index: 1050;">
                    <div class="dropdown-header small fw-bold text-secondary px-2 py-1">KHOẢNG GIÁ</div>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2 active" data-filter="price" data-value="all">Tất cả mức giá</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="price" data-value="under-5m">Dưới 5 triệu</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="price" data-value="5m-10m">Từ 5 - 10 triệu</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="price" data-value="10m-20m">Từ 10 - 20 triệu</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="price" data-value="20m-30m">Từ 20 - 30 triệu</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="price" data-value="above-30m">Trên 30 triệu</button>
                    <hr class="dropdown-divider my-2">
                    <div class="px-2 py-1" onclick="event.stopPropagation()">
                        <div class="small fw-bold text-secondary mb-1">Tự nhập giá (đ):</div>
                        <div class="d-flex align-items-center gap-1 mb-1.5">
                            <input type="number" id="inputMinPrice" class="form-control form-control-sm rounded-3" placeholder="Từ" step="500000" min="0">
                            <span class="text-muted">-</span>
                            <input type="number" id="inputMaxPrice" class="form-control form-control-sm rounded-3" placeholder="Đến" step="500000" min="0">
                        </div>
                        <button type="button" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold" id="btnApplyCustomPrice">Áp dụng</button>
                    </div>
                </div>
            </div>

            <!-- RAM Dropdown -->
            <div class="dropdown">
                <button class="btn btn-sm btn-filter-dropdown dropdown-toggle" type="button" id="dropdownRamBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-microchip text-primary me-1"></i>
                    <span id="ramBtnLabel">RAM</span>
                </button>
                <div class="dropdown-menu dropdown-menu-start shadow-lg border-0 rounded-4 p-2" style="min-width: 175px; z-index: 1050;">
                    <div class="dropdown-header small fw-bold text-secondary px-2 py-1">DUNG LƯỢNG RAM</div>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2 active" data-filter="ram" data-value="all">Tất cả RAM</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="ram" data-value="4">4 GB</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="ram" data-value="6">6 GB</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="ram" data-value="8">8 GB</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="ram" data-value="12">12 GB</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="ram" data-value="16">16 GB</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="ram" data-value="24">24 GB (Khủng)</button>
                </div>
            </div>

            <!-- ROM Dropdown -->
            <div class="dropdown">
                <button class="btn btn-sm btn-filter-dropdown dropdown-toggle" type="button" id="dropdownRomBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-hard-drive text-warning me-1"></i>
                    <span id="romBtnLabel">Bộ nhớ</span>
                </button>
                <div class="dropdown-menu dropdown-menu-start shadow-lg border-0 rounded-4 p-2" style="min-width: 175px; z-index: 1050;">
                    <div class="dropdown-header small fw-bold text-secondary px-2 py-1">BỘ NHỚ TRONG</div>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2 active" data-filter="rom" data-value="all">Tất cả ROM</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="rom" data-value="64">64 GB</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="rom" data-value="128">128 GB</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="rom" data-value="256">256 GB</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="rom" data-value="512">512 GB</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="rom" data-value="1024">1 TB (VIP)</button>
                </div>
            </div>

            <!-- Loại máy Dropdown -->
            <div class="dropdown">
                <button class="btn btn-sm btn-filter-dropdown dropdown-toggle" type="button" id="dropdownTypeBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-crown text-warning me-1"></i>
                    <span id="typeBtnLabel">Tất cả máy</span>
                </button>
                <div class="dropdown-menu dropdown-menu-start shadow-lg border-0 rounded-4 p-2" style="min-width: 190px; z-index: 1050;">
                    <div class="dropdown-header small fw-bold text-secondary px-2 py-1">PHÂN LOẠI MÁY</div>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2 active" data-filter="type" data-value="all"><i class="fa-solid fa-border-all text-primary me-2"></i>Tất cả điện thoại</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="type" data-value="flagship"><i class="fa-solid fa-crown text-warning me-2"></i>Flagship 2026</button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="type" data-value="used"><i class="fa-solid fa-recycle text-success me-2"></i>Hàng Cũ Like New</button>
                </div>
            </div>

            <!-- Nút Reset (hiện khi có lọc) -->
            <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none fw-semibold d-none" id="btnResetFilters" onclick="resetAllProductFilters()" title="Xóa tất cả bộ lọc">
                <i class="fa-solid fa-rotate-left me-1"></i>Xóa lọc (<span id="resetCountSpan">0</span>)
            </button>
        </div>

        <!-- Phía bên phải: Số lượng & Dropdown Sắp xếp -->
        <div class="d-flex align-items-center gap-2 ms-auto">
            <span class="badge bg-light text-secondary border rounded-pill px-3 py-2 fw-semibold" id="productCountBadge"><?= count($products) ?> sản phẩm</span>
            
            <div class="dropdown">
                <button class="btn btn-sm btn-filter-dropdown dropdown-toggle" type="button" id="dropdownSortBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-arrow-down-short-wide text-primary me-1" id="sortBtnIcon"></i>
                    <span id="sortBtnLabel">Nổi bật nhất</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2" style="min-width: 195px; z-index: 1050;">
                    <div class="dropdown-header small fw-bold text-secondary px-2 py-1">SẮP XẾP THEO</div>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2 active" data-filter="sort" data-value="default">
                        <i class="fa-solid fa-fire text-danger me-2"></i>Nổi bật nhất
                    </button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="sort" data-value="price-asc">
                        <i class="fa-solid fa-arrow-trend-up text-success me-2"></i>Giá: Thấp đến cao
                    </button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="sort" data-value="price-desc">
                        <i class="fa-solid fa-arrow-trend-down text-info me-2"></i>Giá: Cao đến thấp
                    </button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="sort" data-value="rating-desc">
                        <i class="fa-solid fa-award text-warning me-2"></i>Đánh giá cao nhất
                    </button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="sort" data-value="name-asc">
                        <i class="fa-solid fa-arrow-down-a-z text-primary me-2"></i>Tên A - Z
                    </button>
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="sort" data-value="newest">
                        <i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i>Hàng mới về
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container" id="product-list">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0 text-dark fs-5" id="productListTitle">
            <i class="fa-solid fa-mobile-screen-button text-primary me-2"></i>
            <?= !empty($keyword) ? 'Kết quả tìm kiếm: "' . htmlspecialchars($keyword) . '"' : 'Flagship 2026 & Điện Thoại Mới Nhất' ?>
        </h4>
        <small class="text-secondary" id="filterSummaryText">Đang hiển thị toàn bộ sản phẩm</small>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="productGridContainer">
        <div id="noProductNotice" class="col-12 text-center py-5 d-none w-100">
            <div class="py-5 px-3 bg-white rounded-4 border shadow-sm my-3 mx-auto" style="max-width: 540px;">
                <i class="fa-solid fa-filter-circle-xmark fa-3x text-muted mb-3 d-block opacity-75"></i>
                <h5 class="fw-bold text-dark mb-2">Chưa tìm thấy điện thoại nào phù hợp</h5>
                <p class="text-secondary small mb-4">Các tiêu chí lọc (Giá, RAM, ROM, Hãng) hiện không có mẫu máy nào đáp ứng cùng lúc. Hãy thử nới lỏng mức giá hoặc chọn cấu hình khác.</p>
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" onclick="resetAllProductFilters()">
                    <i class="fa-solid fa-rotate-left me-1"></i>Đặt lại tất cả bộ lọc
                </button>
            </div>
        </div>
        <?php foreach ($products as $p): ?>
            <?php
                $storageDisplayList = json_decode($p['storage_options'] ?? '', true);
                if (!is_array($storageDisplayList) || !$storageDisplayList) {
                    $storageDisplayList = getStorageTiers($p['name'], $p['rom'] ?? '256 GB');
                }
                $romDisplayList = array_column($storageDisplayList, 'rom');
                $colorDisplayList = array_map("trim", explode(",", $p["colors"] ?? "Đen, Trắng"));
                $pEffectivePrice = ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) ? (int)$p['sale_price'] : (int)$p['price'];
                $ramNumber = (int)filter_var($p['ram'] ?? '', FILTER_SANITIZE_NUMBER_INT);
                $baseRomGb = romToGigabytes($p['rom'] ?? '256 GB');
                $allRomGbs = array_unique(array_filter(array_map('romToGigabytes', $romDisplayList)));
                if (!in_array($baseRomGb, $allRomGbs)) {
                    $allRomGbs[] = $baseRomGb;
                }
                $rInfo = $ratingMap[$p['id']] ?? null;
                $pRatingVal = $rInfo ? (float)$rInfo['avg_rating'] : 5.0;
                $pRevCount = $rInfo ? (int)$rInfo['review_count'] : 0;
            ?>
            <div class="col product-col-card"
                data-id="<?= (int)$p['id'] ?>"
                data-brand-id="<?= (int)$p['brand_id'] ?>"
                data-brand-name="<?= htmlspecialchars($p['brand_name'] ?? '') ?>"
                data-is-featured="<?= (int)$p['is_featured'] ?>"
                data-is-used="<?= (int)$p['is_used'] ?>"
                data-price="<?= $pEffectivePrice ?>"
                data-ram="<?= $ramNumber ?>"
                data-base-rom="<?= $baseRomGb ?>"
                data-all-roms="<?= implode(',', $allRomGbs) ?>"
                data-rating="<?= $pRatingVal ?>"
                data-review-count="<?= $pRevCount ?>"
                data-name="<?= htmlspecialchars($p['name']) ?>">
                <div class="card h-100 product-card shadow-sm position-relative overflow-hidden">
                    <div class="product-img-wrapper position-relative">
                        <?php if (!empty($p['is_used'])): ?>
                            <span class="badge badge-used-phone position-absolute top-0 end-0 m-2 px-2 py-1 rounded-pill shadow-sm" style="z-index: 5;" title="Hàng Cũ Like New 99%">
                                <i class="fa-solid fa-recycle me-1"></i><?= htmlspecialchars(!empty($p['condition_desc']) && $p['condition_desc'] !== 'Mới 100%' ? $p['condition_desc'] : 'Máy cũ 99%') ?>
                            </span>
                        <?php elseif (!empty($p['is_featured'])): ?>
                            <span class="badge badge-flagship position-absolute top-0 end-0 m-2 px-2 py-1 rounded-pill shadow-sm" style="z-index: 5;" title="Flagship 2026">
                                <i class="fa-solid fa-crown text-warning me-1"></i>Flagship 2026
                            </span>
                        <?php endif; ?>

                        <?php if ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']): ?>
                            <?php $percent = round((($p['price'] - $p['sale_price']) / $p['price']) * 100); ?>
                            <span class="badge badge-sale-percent position-absolute top-0 start-0 m-2 px-2 py-1 rounded-pill shadow-sm" style="z-index: 5;">-<?= $percent ?>%</span>
                        <?php endif; ?>

                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" onerror="this.onerror=null; this.src='assets/images/products/iphone-16.png';">
                    </div>

                    <div class="card-body d-flex flex-column pt-2">
                        <small class="text-primary fw-bold text-uppercase"><?= htmlspecialchars($p['brand_name'] ?? 'Khác') ?></small>
                        <div class="card-prod-title"><?= htmlspecialchars($p['name']) ?></div>

                        <?php
                            $rInfo = $ratingMap[$p['id']] ?? null;
                            $pRatingVal = $rInfo ? (float)$rInfo['avg_rating'] : 5.0;
                            $pRevCount = $rInfo ? (int)$rInfo['review_count'] : 0;
                        ?>
                        <div class="d-flex align-items-center gap-1 my-1" style="font-size: 0.72rem;">
                            <span class="text-warning"><i class="fa-solid fa-star"></i></span>
                            <span class="fw-bold text-dark"><?= number_format($pRatingVal, 1) ?></span>
                            <span class="text-secondary">(<?= $pRevCount ?> đánh giá)</span>
                        </div>

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
                                <div class="text-danger fw-bold fs-5 mb-0"><?= number_format($p['sale_price'], 0, ',', '.') ?> VNĐ</div>
                                <small class="text-decoration-line-through text-muted"><?= number_format($p['price'], 0, ',', '.') ?> VNĐ</small>
                            <?php else: ?>
                                <div class="text-primary fw-bold fs-5 mb-0"><?= number_format($p['price'], 0, ',', '.') ?> VNĐ</div>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2 mt-3">
                            <?php if ((int)$p['quantity'] <= 0): ?>
                                <button type="button" class="btn btn-secondary btn-sm rounded-pill fw-bold py-2 text-center" disabled>
                                    <i class="fa-solid fa-ban me-1"></i>HẾT HÀNG
                                </button>
                                <div class="d-flex gap-1.5 align-items-center">
                                    <a href="index.php?page=detail&id=<?= $p['id'] ?>" class="btn btn-outline-vphone btn-sm rounded-pill flex-grow-1 fw-semibold text-center">
                                        Chi tiết
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill flex-grow-1 fw-bold" disabled>
                                        <i class="fa-solid fa-cart-plus me-1"></i>Hết hàng
                                    </button>
                                    <a href="index.php?page=compare&p1=<?= $p['id'] ?>" class="btn btn-light border btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-2xs text-secondary hover-primary" style="width: 32px; height: 32px; flex-shrink: 0;" title="So sánh đối đầu <?= htmlspecialchars($p['name']) ?>">
                                        <i class="fa-solid fa-code-compare" style="font-size: 0.76rem;"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <button type="button" class="btn btn-vphone btn-sm rounded-pill fw-bold py-2 shadow-sm text-center" onclick="openProductModal(<?= $p['id'] ?>, 'buy')">
                                    <i class="fa-solid fa-bolt me-1"></i>MUA NGAY
                                </button>
                                <div class="d-flex gap-1.5 align-items-center">
                                    <a href="index.php?page=detail&id=<?= $p['id'] ?>" class="btn btn-outline-vphone btn-sm rounded-pill flex-grow-1 fw-semibold text-center">
                                        Chi tiết
                                    </a>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill flex-grow-1 fw-bold" onclick="openProductModal(<?= $p['id'] ?>, 'cart')">
                                        <i class="fa-solid fa-cart-plus me-1"></i>Thêm giỏ
                                    </button>
                                    <a href="index.php?page=compare&p1=<?= $p['id'] ?>" class="btn btn-light border btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-2xs text-secondary hover-primary" style="width: 32px; height: 32px; flex-shrink: 0;" title="So sánh đối đầu <?= htmlspecialchars($p['name']) ?>">
                                        <i class="fa-solid fa-code-compare" style="font-size: 0.76rem;"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

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
                        <img id="modalImg" src="" alt="" style="max-height: 65px; max-width: 65px; object-fit: contain;">
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <h6 id="modalTitle" class="fw-bold text-dark mb-1 text-truncate">Tên điện thoại</h6>
                        <div class="d-flex align-items-baseline gap-2">
                            <span id="modalPrice" class="fs-5 fw-bold text-danger">0 VNĐ</span>
                            <small id="modalOldPrice" class="text-decoration-line-through text-muted small">0 VNĐ</small>
                        </div>
                        <small class="text-primary fw-semibold" id="modalColorNotice">Màu: Đang chọn...</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-secondary mb-1">
                        <i class="fa-solid fa-palette text-primary me-1"></i>Chọn màu sắc:
                    </label>
                    <div id="modalColorPills" class="d-flex gap-2 flex-wrap"></div>
                </div>

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
    return new Intl.NumberFormat('vi-VN').format(amount) + ' VNĐ';
}

function getStorageTiersJS(prodName, defaultRom) {
    const name = (prodName || '').toLowerCase();
    const rom = (defaultRom || '').toLowerCase();
    if (name.includes('ultra') || name.includes('pro max') || name.includes('fold') || name.includes('tri-fold') || name.includes('duo')) {
        return [
            { rom: '256 GB', extra: 0, label: '256 GB (Tiêu chuẩn)' },
            { rom: '512 GB', extra: 4000000, label: '512 GB (+4.0tr)' },
            { rom: '1 TB', extra: 9000000, label: '1 TB (+9.0tr)' }
        ];
    }
    if (rom.includes('64')) {
        return [
            { rom: '64 GB', extra: 0, label: '64 GB (Tiêu chuẩn)' },
            { rom: '128 GB', extra: 1200000, label: '128 GB (+1.2tr)' },
            { rom: '256 GB', extra: 2600000, label: '256 GB (+2.6tr)' }
        ];
    }
    return [
        { rom: '128 GB', extra: 0, label: '128 GB (Tiêu chuẩn)' },
        { rom: '256 GB', extra: 2500000, label: '256 GB (+2.5tr)' },
        { rom: '512 GB', extra: 5500000, label: '512 GB (+5.5tr)' }
    ];
}

function getJsColorHex(name) {
    const c = (name || '').toLowerCase();
    if (c.includes('cam') || c.includes('orange') || c.includes('ánh dương') || c.includes('hoàng hôn')) return '#ea580c';
    if (c.includes('matcha')) return '#65a30d';
    if (c.includes('mòng két') || c.includes('teal') || c.includes('mint')) return '#0d9488';
    if (c.includes('lục bảo') || c.includes('ngọc bích') || c.includes('sơn lâm') || c.includes('jade') || c.includes('rêu') || c.includes('botanic') || c.includes('xanh lục') || c.includes('xanh lá')) return '#059669';
    if (c.includes('lưu ly') || c.includes('navy') || c.includes('xanh đậm') || c.includes('deep blue')) return '#1e3a8a';
    if (c.includes('sa mạc') || c.includes('vàng') || c.includes('gold') || c.includes('amber') || c.includes('cá tính') || c.includes('lemon') || c.includes('biển cát') || c.includes('solar') || c.includes('hoàng kim')) return '#f59e0b';
    if (c.includes('hồng') || c.includes('pink') || c.includes('rosé') || c.includes('ngọc trai') || c.includes('rose') || c.includes('phấn')) return '#f472b6';
    if (c.includes('tím') || c.includes('purple') || c.includes('lilac') || c.includes('cobalt') || c.includes('coral') || c.includes('cực quang')) return '#8b5cf6';
    if (c.includes('kem') || c.includes('ivory') || c.includes('cotton')) return '#f5f0e1';
    if (c.includes('trời đêm') || c.includes('midnight')) return '#1e293b';
    if (c.includes('xanh') || c.includes('blue') || c.includes('sky') || c.includes('icy') || c.includes('topaz') || c.includes('iceblue') || c.includes('lạc quan') || c.includes('đại dương') || c.includes('tinh tú') || c.includes('maya') || c.includes('glacier')) return '#38bdf8';
    if (c.includes('đen') || c.includes('black') || c.includes('phantom') || c.includes('space') || c.includes('obsidian') || c.includes('graphite') || c.includes('huyền vũ') || c.includes('bóng đêm') || c.includes('đá') || c.includes('bản lĩnh') || c.includes('kính') || c.includes('da thuần') || c.includes('không gian') || c.includes('cổ điển') || c.includes('vũ trụ')) return '#1e293b';
    if (c.includes('trắng') || c.includes('white') || c.includes('bạc') || c.includes('silver') || c.includes('tự nhiên') || c.includes('porcelain') || c.includes('sứ') || c.includes('xỉu') || c.includes('ngân hà') || c.includes('ánh sao') || c.includes('starlight') || c.includes('da gốm') || c.includes('bach kim') || c.includes('bạch kim')) return '#e2e8f0';
    if (c.includes('xám') || c.includes('gray') || c.includes('grey') || c.includes('metal') || c.includes('marble') || c.includes('opal') || c.includes('hazel') || c.includes('titan')) return '#94a3b8';
    if (c.includes('đỏ') || c.includes('red') || c.includes('burgundy') || c.includes('ruihong') || c.includes('scarlet')) return '#ef4444';
    return '#64748b';
}

function getModalColorImage(product, colorName) {
    if (!product) return 'assets/images/products/iphone-16.png';
    const pId = parseInt(product.id, 10);
    const color = (colorName || '').trim();

    if (window.PRODUCT_COLOR_MAP && window.PRODUCT_COLOR_MAP[pId] && window.PRODUCT_COLOR_MAP[pId][color]) {
        return window.PRODUCT_COLOR_MAP[pId][color];
    }

    return product.image || 'assets/images/products/iphone-16.png';
}


function openProductModal(productId, defaultAction) {
    const all = window.PAGE_PRODUCTS || window.STORE_PRODUCTS || [];
    currentProduct = all.find(p => p.id === productId);
    if (!currentProduct) return;

    const modalEl = document.getElementById('productSelectModal');
    if (!bsModalInstance) {
        bsModalInstance = new bootstrap.Modal(modalEl);
    }

    const modalImg = document.getElementById('modalImg');
    document.getElementById('modalTitle').innerText = currentProduct.name;
    document.getElementById('modalPrice').innerText = formatCurrency(currentProduct.price);
    document.getElementById('modalOldPrice').innerText = formatCurrency(currentProduct.old_price);

    const colorBox = document.getElementById('modalColorPills');
    colorBox.innerHTML = '';
    const colors = (currentProduct.colors || 'Đen, Trắng, Xanh').split(',').map(c => c.trim());

    const colorStockMap = {};
    if (Array.isArray(currentProduct.color_quantities)) {
        currentProduct.color_quantities.forEach(cq => {
            if (cq && cq.color) colorStockMap[cq.color] = parseInt(cq.quantity, 10);
        });
    }

    let firstAvail = null;
    colors.forEach(col => {
        if (firstAvail === null && (colorStockMap[col] === undefined || colorStockMap[col] > 0)) {
            firstAvail = col;
        }
    });
    selectedColor = firstAvail || colors[0];
    modalImg.src = getModalColorImage(currentProduct, selectedColor);
    document.getElementById('modalColorNotice').innerText = 'Màu: ' + selectedColor;

    colors.forEach((col) => {
        const isOutOfStock = colorStockMap[col] !== undefined && colorStockMap[col] <= 0;
        const btn = document.createElement('button');
        btn.type = 'button';
        const dot = `<span style="width:7px;height:7px;border-radius:50%;background:${getJsColorHex(col)};display:inline-block;margin-right:5px;border:1px solid rgba(0,0,0,0.15);vertical-align:middle;"></span>`;
        if (isOutOfStock) {
            btn.className = 'btn btn-sm rounded-pill fw-bold px-3 py-1 btn-outline-secondary opacity-50';
            btn.disabled = true;
            btn.style.cursor = 'not-allowed';
            btn.style.textDecoration = 'line-through';
            btn.innerHTML = `${dot}${col} <span class="badge bg-secondary ms-1" style="font-size:0.6rem;">Hết</span>`;
        } else {
            const isSelected = col === selectedColor;
            btn.className = `btn btn-sm rounded-pill fw-bold px-3 py-1 ${isSelected ? 'btn-primary active text-white' : 'btn-outline-secondary'}`;
            btn.innerHTML = `${dot}${col}`;
            btn.onclick = function() {
                colorBox.querySelectorAll('button:not([disabled])').forEach(b => b.className = 'btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-1');
                this.className = 'btn btn-sm btn-primary active text-white rounded-pill fw-bold px-3 py-1';
                selectedColor = col;
                document.getElementById('modalColorNotice').innerText = 'Màu: ' + col;
                document.getElementById('modalImg').src = getModalColorImage(currentProduct, col);
            };
        }
        colorBox.appendChild(btn);
    });

    const romBox = document.getElementById('modalRomPills');
    romBox.innerHTML = '';
    let romOptions = Array.isArray(currentProduct.storage_options) ? currentProduct.storage_options : [];
    if (!romOptions.length) {
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
            romOptions = getStorageTiersJS(currentProduct.name, currentProduct.rom);
        }
    }
    selectedRom = romOptions[0].rom;
    extraMoney = Number(romOptions[0].extra) || 0;

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
        if (currentProduct && currentProduct.quantity <= 0) {
            alert('Sản phẩm "' + currentProduct.name + '" hiện đã hết hàng!');
            return;
        }
        bsModalInstance.hide();
        const selectedImage = getModalColorImage(currentProduct, selectedColor);
        window.location.href = `index.php?page=checkout&action=buy_now&id=${currentProduct.id}&color=${encodeURIComponent(selectedColor)}&rom=${encodeURIComponent(selectedRom)}&extra=${extraMoney}&img=${encodeURIComponent(selectedImage)}`;
    };

    document.getElementById('btnConfirmAddToCart').onclick = function() {
        if (currentProduct && currentProduct.quantity <= 0) {
            alert('Sản phẩm "' + currentProduct.name + '" hiện đã hết hàng!');
            return;
        }
        bsModalInstance.hide();
        const selectedImage = getModalColorImage(currentProduct, selectedColor);
        const url = `index.php?page=cart&action=add&ajax=1&id=${currentProduct.id}&color=${encodeURIComponent(selectedColor)}&rom=${encodeURIComponent(selectedRom)}&extra=${extraMoney}&img=${encodeURIComponent(selectedImage)}`;
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.success === false) {
                    const errMsg = data.message || 'Không thể thêm vào giỏ hàng!';
                    if (typeof window.showVPhoneToast === 'function') {
                        window.showVPhoneToast(errMsg, false);
                    } else {
                        const toast = document.getElementById('vphoneLiveToast');
                        const toastText = document.getElementById('vphoneToastText');
                        if (toast && toastText) {
                            toastText.innerText = errMsg;
                            toast.style.display = 'block';
                        } else {
                            alert(errMsg);
                        }
                    }
                    return;
                }
                const badge = document.getElementById('cartBadge');
                if (badge) {
                    badge.innerText = data.cart_count;
                    badge.classList.remove('badge-bounce');
                    void badge.offsetWidth;
                    badge.classList.add('badge-bounce');
                }
                const successMsg = `Đã thêm "${currentProduct.name} - Màu ${selectedColor} (${selectedRom})" vào giỏ!`;
                if (typeof window.showVPhoneToast === 'function') {
                    window.showVPhoneToast(successMsg, true);
                } else {
                    const toast = document.getElementById('vphoneLiveToast');
                    const toastText = document.getElementById('vphoneToastText');
                    if (toast && toastText) {
                        toastText.innerText = successMsg;
                        toast.style.display = 'block';
                    }
                }
            })
            .catch(() => {
                window.location.href = `index.php?page=cart&action=add&id=${currentProduct.id}&color=${encodeURIComponent(selectedColor)}&rom=${encodeURIComponent(selectedRom)}&extra=${extraMoney}&img=${encodeURIComponent(selectedImage)}`;
            });
    };

    bsModalInstance.show();
}
// --- BỘ LỌC ĐA TIÊU CHÍ (Giá, RAM, ROM, Sắp xếp) ---
const filterState = {
    brandId: <?= (int)$brandId ?>,
    filterType: '<?= htmlspecialchars($filters['filter_type'] ?? 'all', ENT_QUOTES) ?>',
    priceRange: '<?= htmlspecialchars($filters['price_range'] ?? 'all', ENT_QUOTES) ?>',
    customMinPrice: <?= !empty($filters['min_price']) && is_numeric($filters['min_price']) ? (float)$filters['min_price'] : 'null' ?>,
    customMaxPrice: <?= !empty($filters['max_price']) && is_numeric($filters['max_price']) ? (float)$filters['max_price'] : 'null' ?>,
    ram: '<?= htmlspecialchars($filters['ram'] ?? 'all', ENT_QUOTES) ?>',
    rom: '<?= htmlspecialchars($filters['rom'] ?? 'all', ENT_QUOTES) ?>',
    sort: '<?= htmlspecialchars($filters['sort'] ?? 'default', ENT_QUOTES) ?>'
};

const priceDisplayMap = {
    'under-5m': 'Dưới 5 triệu',
    '5m-10m': '5 - 10 triệu',
    '10m-20m': '10 - 20 triệu',
    '20m-30m': '20 - 30 triệu',
    'above-30m': 'Trên 30 triệu'
};

function formatVndShort(amount) {
    if (amount >= 1000000) {
        const tr = amount / 1000000;
        return (Number.isInteger(tr) ? tr : tr.toFixed(1)) + ' triệu';
    }
    return new Intl.NumberFormat('vi-VN').format(amount) + ' VNĐ';
}

function syncFilterUI() {
    // 1. Brand Pills
    document.querySelectorAll('.btn-brand-pill').forEach(btn => {
        const bId = parseInt(btn.dataset.brandId, 10);
        if (bId === filterState.brandId) {
            btn.classList.add('active', 'btn-primary', 'text-white');
            btn.classList.remove('btn-outline-secondary');
        } else {
            btn.classList.remove('active', 'btn-primary', 'text-white');
            btn.classList.add('btn-outline-secondary');
        }
    });

    // 2. Price Dropdown
    const priceBtn = document.getElementById('dropdownPriceBtn');
    const priceLabel = document.getElementById('priceBtnLabel');
    if (priceBtn && priceLabel) {
        if (filterState.priceRange !== 'all') {
            priceBtn.classList.add('active');
            if (filterState.priceRange === 'custom') {
                const minStr = filterState.customMinPrice !== null ? formatVndShort(filterState.customMinPrice) : '0';
                const maxStr = filterState.customMaxPrice !== null ? formatVndShort(filterState.customMaxPrice) : '∞';
                priceLabel.innerText = `${minStr} - ${maxStr}`;
            } else {
                priceLabel.innerText = priceDisplayMap[filterState.priceRange] || 'Mức giá';
            }
        } else {
            priceBtn.classList.remove('active');
            priceLabel.innerText = 'Mức giá';
        }
    }
    document.querySelectorAll('.dropdown-item[data-filter="price"]').forEach(item => {
        if (item.dataset.value === filterState.priceRange) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });

    // 3. RAM Dropdown
    const ramBtn = document.getElementById('dropdownRamBtn');
    const ramLabel = document.getElementById('ramBtnLabel');
    if (ramBtn && ramLabel) {
        if (filterState.ram !== 'all') {
            ramBtn.classList.add('active');
            ramLabel.innerText = filterState.ram + ' GB';
        } else {
            ramBtn.classList.remove('active');
            ramLabel.innerText = 'RAM';
        }
    }
    document.querySelectorAll('.dropdown-item[data-filter="ram"]').forEach(item => {
        if (item.dataset.value === String(filterState.ram)) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });

    // 4. ROM Dropdown
    const romBtn = document.getElementById('dropdownRomBtn');
    const romLabel = document.getElementById('romBtnLabel');
    if (romBtn && romLabel) {
        if (filterState.rom !== 'all') {
            romBtn.classList.add('active');
            const romNum = parseInt(filterState.rom, 10);
            romLabel.innerText = romNum >= 1024 ? '1 TB' : (filterState.rom + ' GB');
        } else {
            romBtn.classList.remove('active');
            romLabel.innerText = 'Bộ nhớ';
        }
    }
    document.querySelectorAll('.dropdown-item[data-filter="rom"]').forEach(item => {
        if (item.dataset.value === String(filterState.rom)) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });

    // 5. Type Dropdown
    const typeBtn = document.getElementById('dropdownTypeBtn');
    const typeLabel = document.getElementById('typeBtnLabel');
    if (typeBtn && typeLabel) {
        if (filterState.filterType === 'flagship') {
            typeBtn.classList.add('active');
            typeLabel.innerText = 'Flagship 2026';
        } else if (filterState.filterType === 'used') {
            typeBtn.classList.add('active');
            typeLabel.innerText = 'Hàng Cũ Like New';
        } else {
            typeBtn.classList.remove('active');
            typeLabel.innerText = 'Tất cả máy';
        }
    }
    document.querySelectorAll('.dropdown-item[data-filter="type"]').forEach(item => {
        if (item.dataset.value === filterState.filterType) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });

    // 6. Sort Dropdown
    const sortLabels = {
        'default': 'Nổi bật nhất',
        'price-asc': 'Giá: Thấp đến cao',
        'price-desc': 'Giá: Cao đến thấp',
        'rating-desc': 'Đánh giá cao nhất',
        'name-asc': 'Tên A - Z',
        'newest': 'Hàng mới về'
    };
    const sortIcons = {
        'default': 'fa-solid fa-fire text-danger',
        'price-asc': 'fa-solid fa-arrow-trend-up text-success',
        'price-desc': 'fa-solid fa-arrow-trend-down text-info',
        'rating-desc': 'fa-solid fa-award text-warning',
        'name-asc': 'fa-solid fa-arrow-down-a-z text-primary',
        'newest': 'fa-solid fa-clock-rotate-left text-secondary'
    };

    const sortBtn = document.getElementById('dropdownSortBtn');
    const sortLabel = document.getElementById('sortBtnLabel');
    const sortIcon = document.getElementById('sortBtnIcon');
    if (sortBtn && sortLabel) {
        sortLabel.innerText = sortLabels[filterState.sort] || 'Nổi bật nhất';
        if (filterState.sort !== 'default') {
            sortBtn.classList.add('active');
        } else {
            sortBtn.classList.remove('active');
        }
    }
    if (sortIcon && sortIcons[filterState.sort]) {
        sortIcon.className = sortIcons[filterState.sort] + ' me-1';
    }
    document.querySelectorAll('.dropdown-item[data-filter="sort"]').forEach(item => {
        if (item.dataset.value === filterState.sort) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });

    // 7. Active Count & Reset Button
    let activeFilterCount = 0;
    if (filterState.brandId !== 0) activeFilterCount++;
    if (filterState.filterType !== 'all') activeFilterCount++;
    if (filterState.priceRange !== 'all') activeFilterCount++;
    if (filterState.ram !== 'all') activeFilterCount++;
    if (filterState.rom !== 'all') activeFilterCount++;
    if (filterState.sort !== 'default') activeFilterCount++;

    const resetBtn = document.getElementById('btnResetFilters');
    const resetCountSpan = document.getElementById('resetCountSpan');
    if (resetBtn) {
        if (activeFilterCount > 0) {
            resetBtn.classList.remove('d-none');
            if (resetCountSpan) resetCountSpan.innerText = activeFilterCount;
        } else {
            resetBtn.classList.add('d-none');
        }
    }
}

function applyProductFilters(updateHistory = true) {
    syncFilterUI();

    const grid = document.getElementById('productGridContainer');
    const allCards = Array.from(document.querySelectorAll('.product-col-card'));
    const visibleCards = [];

    allCards.forEach(card => {
        const cardBrandId = parseInt(card.dataset.brandId || '0', 10);
        const cardIsFeatured = parseInt(card.dataset.isFeatured || '0', 10);
        const cardIsUsed = parseInt(card.dataset.isUsed || '0', 10);
        const cardPrice = parseFloat(card.dataset.price || '0');
        const cardRam = parseInt(card.dataset.ram || '0', 10);
        const cardBaseRom = parseInt(card.dataset.baseRom || '0', 10);
        const cardAllRoms = (card.dataset.allRoms || '').split(',').map(n => parseInt(n.trim(), 10)).filter(Boolean);

        // 1. Brand match
        const matchBrand = (filterState.brandId === 0 || cardBrandId === filterState.brandId);

        // 2. Type match
        let matchType = true;
        if (filterState.filterType === 'flagship') {
            matchType = (cardIsFeatured === 1);
        } else if (filterState.filterType === 'used') {
            matchType = (cardIsUsed === 1);
        }

        // 3. Price match
        let matchPrice = true;
        if (filterState.priceRange === 'under-5m') {
            matchPrice = (cardPrice < 5000000);
        } else if (filterState.priceRange === '5m-10m') {
            matchPrice = (cardPrice >= 5000000 && cardPrice <= 10000000);
        } else if (filterState.priceRange === '10m-20m') {
            matchPrice = (cardPrice >= 10000000 && cardPrice <= 20000000);
        } else if (filterState.priceRange === '20m-30m') {
            matchPrice = (cardPrice >= 20000000 && cardPrice <= 30000000);
        } else if (filterState.priceRange === 'above-30m') {
            matchPrice = (cardPrice > 30000000);
        } else if (filterState.priceRange === 'custom') {
            if (filterState.customMinPrice !== null && cardPrice < filterState.customMinPrice) {
                matchPrice = false;
            }
            if (filterState.customMaxPrice !== null && cardPrice > filterState.customMaxPrice) {
                matchPrice = false;
            }
        }

        // 4. RAM match
        let matchRam = true;
        if (filterState.ram !== 'all') {
            const targetRam = parseInt(filterState.ram, 10);
            matchRam = (cardRam === targetRam);
        }

        // 5. ROM match
        let matchRom = true;
        if (filterState.rom !== 'all') {
            const targetRom = parseInt(filterState.rom, 10);
            matchRom = (cardBaseRom === targetRom || cardAllRoms.includes(targetRom));
        }

        const isMatch = matchBrand && matchType && matchPrice && matchRam && matchRom;

        if (isMatch) {
            card.classList.remove('d-none');
            visibleCards.push(card);
        } else {
            card.classList.add('d-none');
        }
    });

    // Sort visible cards
    visibleCards.sort((a, b) => {
        const sortType = filterState.sort;
        const priceA = parseFloat(a.dataset.price || '0');
        const priceB = parseFloat(b.dataset.price || '0');
        const ratingA = parseFloat(a.dataset.rating || '0');
        const ratingB = parseFloat(b.dataset.rating || '0');
        const revCountA = parseInt(a.dataset.reviewCount || '0', 10);
        const revCountB = parseInt(b.dataset.reviewCount || '0', 10);
        const idA = parseInt(a.dataset.id || '0', 10);
        const idB = parseInt(b.dataset.id || '0', 10);
        const featA = parseInt(a.dataset.isFeatured || '0', 10);
        const featB = parseInt(b.dataset.isFeatured || '0', 10);

        if (sortType === 'price-asc') {
            return priceA - priceB || idA - idB;
        } else if (sortType === 'price-desc') {
            return priceB - priceA || idA - idB;
        } else if (sortType === 'rating-desc') {
            if (ratingB !== ratingA) return ratingB - ratingA;
            return revCountB - revCountA || priceB - priceA;
        } else if (sortType === 'name-asc') {
            return (a.dataset.name || '').localeCompare(b.dataset.name || '', 'vi', { sensitivity: 'base' });
        } else if (sortType === 'newest') {
            return idB - idA;
        } else {
            // default: featured first, then id ASC
            if (featB !== featA) return featB - featA;
            return idA - idB;
        }
    });

    // Reorder DOM nodes smoothly
    visibleCards.forEach(card => {
        grid.appendChild(card);
        card.style.animation = 'fadeInProduct 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards';
    });

    // Update empty notice
    const emptyNotice = document.getElementById('noProductNotice');
    if (emptyNotice) {
        if (visibleCards.length === 0) {
            emptyNotice.classList.remove('d-none');
            grid.appendChild(emptyNotice);
        } else {
            emptyNotice.classList.add('d-none');
        }
    }

    // Update badge count
    const countBadge = document.getElementById('productCountBadge');
    if (countBadge) {
        countBadge.innerText = visibleCards.length + ' sản phẩm';
    }

    // Update summary text
    const summaryText = document.getElementById('filterSummaryText');
    if (summaryText) {
        summaryText.innerText = visibleCards.length > 0 
            ? `Tìm thấy ${visibleCards.length} sản phẩm phù hợp tiêu chí`
            : 'Không có sản phẩm nào phù hợp';
    }

    // Update title
    const titleEl = document.getElementById('productListTitle');
    if (titleEl) {
        let typeText = '';
        if (filterState.filterType === 'flagship') typeText = 'Flagship 2026';
        else if (filterState.filterType === 'used') typeText = 'Hàng Cũ Like New 99%';

        if (filterState.brandId === 0) {
            titleEl.innerHTML = `<i class="fa-solid fa-mobile-screen-button text-primary me-2"></i>${typeText ? typeText + ' - Toàn Bộ Thương Hiệu' : 'Flagship 2026 & Điện Thoại Mới Nhất'}`;
        } else {
            const activeBrandBtn = document.querySelector(`.btn-brand-pill[data-brand-id="${filterState.brandId}"]`);
            const displayBrand = activeBrandBtn?.dataset.brandName || activeBrandBtn?.innerText.trim() || 'Thương hiệu';
            titleEl.innerHTML = `<i class="fa-solid fa-mobile-screen-button text-primary me-2"></i>Điện Thoại ${displayBrand} ${typeText ? '(' + typeText + ')' : ''}`;
        }
    }

    // Sync URL query string
    if (updateHistory) {
        const params = new URLSearchParams(window.location.search);
        if (filterState.brandId !== 0) params.set('brand_id', filterState.brandId); else params.delete('brand_id');
        if (filterState.filterType !== 'all') params.set('filter_type', filterState.filterType); else params.delete('filter_type');
        if (filterState.priceRange !== 'all') params.set('price', filterState.priceRange); else params.delete('price');
        if (filterState.priceRange === 'custom') {
            if (filterState.customMinPrice !== null) params.set('min_price', filterState.customMinPrice); else params.delete('min_price');
            if (filterState.customMaxPrice !== null) params.set('max_price', filterState.customMaxPrice); else params.delete('max_price');
        } else {
            params.delete('min_price');
            params.delete('max_price');
        }
        if (filterState.ram !== 'all') params.set('ram', filterState.ram); else params.delete('ram');
        if (filterState.rom !== 'all') params.set('rom', filterState.rom); else params.delete('rom');
        if (filterState.sort !== 'default') params.set('sort', filterState.sort); else params.delete('sort');

        const qs = params.toString();
        const newUrl = qs ? 'index.php?' + qs : 'index.php';
        window.history.pushState(filterState, '', newUrl);
    }
}

function resetFilter(filterName) {
    if (filterName === 'price') {
        filterState.priceRange = 'all';
        filterState.customMinPrice = null;
        filterState.customMaxPrice = null;
        const minInput = document.getElementById('inputMinPrice');
        const maxInput = document.getElementById('inputMaxPrice');
        if (minInput) minInput.value = '';
        if (maxInput) maxInput.value = '';
    } else if (filterName === 'ram') {
        filterState.ram = 'all';
    } else if (filterName === 'rom') {
        filterState.rom = 'all';
    } else if (filterName === 'brand') {
        filterState.brandId = 0;
    } else if (filterName === 'type') {
        filterState.filterType = 'all';
    } else if (filterName === 'sort') {
        filterState.sort = 'default';
    }
    applyProductFilters(true);
}

function resetAllProductFilters() {
    filterState.brandId = 0;
    filterState.filterType = 'all';
    filterState.priceRange = 'all';
    filterState.customMinPrice = null;
    filterState.customMaxPrice = null;
    filterState.ram = 'all';
    filterState.rom = 'all';
    filterState.sort = 'default';

    const minInput = document.getElementById('inputMinPrice');
    const maxInput = document.getElementById('inputMaxPrice');
    if (minInput) minInput.value = '';
    if (maxInput) maxInput.value = '';

    applyProductFilters(true);
}

function filterProductsByBrand(brandId, brandName, updateHistory = true) {
    filterState.brandId = parseInt(brandId, 10);
    applyProductFilters(updateHistory);
}

document.addEventListener('DOMContentLoaded', function() {
    // Read URL params on initial load
    const params = new URLSearchParams(window.location.search);
    if (params.has('brand_id')) filterState.brandId = parseInt(params.get('brand_id'), 10) || 0;
    if (params.has('filter_type')) filterState.filterType = params.get('filter_type');
    if (params.has('price')) filterState.priceRange = params.get('price');
    if (params.has('min_price')) filterState.customMinPrice = parseFloat(params.get('min_price'));
    if (params.has('max_price')) filterState.customMaxPrice = parseFloat(params.get('max_price'));
    if (params.has('ram')) filterState.ram = params.get('ram');
    if (params.has('rom')) filterState.rom = params.get('rom');
    if (params.has('sort')) filterState.sort = params.get('sort');

    // 1. Brand Pills
    document.querySelectorAll('.btn-brand-pill').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            filterState.brandId = parseInt(this.dataset.brandId, 10);
            applyProductFilters(true);
        });
    });

    // 2. Dropdown Filter Items (Price, RAM, ROM, Type)
    document.querySelectorAll('.dropdown-item[data-filter]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const fType = this.dataset.filter;
            const fVal = this.dataset.value;

            if (fType === 'price') {
                filterState.priceRange = fVal;
                filterState.customMinPrice = null;
                filterState.customMaxPrice = null;
                const minInput = document.getElementById('inputMinPrice');
                const maxInput = document.getElementById('inputMaxPrice');
                if (minInput) minInput.value = '';
                if (maxInput) maxInput.value = '';
            } else if (fType === 'ram') {
                filterState.ram = fVal;
            } else if (fType === 'rom') {
                filterState.rom = fVal;
            } else if (fType === 'type') {
                filterState.filterType = fVal;
            } else if (fType === 'sort') {
                filterState.sort = fVal;
            }

            applyProductFilters(true);
        });
    });

    // 3. Custom Price Filter
    const btnApplyCustom = document.getElementById('btnApplyCustomPrice');
    if (btnApplyCustom) {
        btnApplyCustom.addEventListener('click', function() {
            const minVal = parseFloat(document.getElementById('inputMinPrice')?.value || '');
            const maxVal = parseFloat(document.getElementById('inputMaxPrice')?.value || '');
            if (isNaN(minVal) && isNaN(maxVal)) {
                alert('Vui lòng nhập mức giá tối thiểu hoặc tối đa!');
                return;
            }
            if (!isNaN(minVal) && !isNaN(maxVal) && minVal > maxVal) {
                alert('Mức giá tối thiểu không thể lớn hơn mức giá tối đa!');
                return;
            }
            filterState.priceRange = 'custom';
            filterState.customMinPrice = !isNaN(minVal) ? minVal : null;
            filterState.customMaxPrice = !isNaN(maxVal) ? maxVal : null;

            // Close price dropdown
            const priceDropdownBtn = document.getElementById('dropdownPriceBtn');
            if (priceDropdownBtn && window.bootstrap) {
                const bsDropdown = bootstrap.Dropdown.getInstance(priceDropdownBtn) || new bootstrap.Dropdown(priceDropdownBtn);
                if (bsDropdown) bsDropdown.hide();
            }

            applyProductFilters(true);
        });
    }

    // 5. Reset All Filters Button
    const btnReset = document.getElementById('btnResetFilters');
    if (btnReset) {
        btnReset.addEventListener('click', function(e) {
            e.preventDefault();
            resetAllProductFilters();
        });
    }

    // 8. Sidebar Left Menu Brand Links
    document.querySelectorAll('#sidebarMenuLeft a[href*="brand_id="]').forEach(link => {
        link.addEventListener('click', function(e) {
            const hrefUrl = this.getAttribute('href');
            const match = hrefUrl.match(/brand_id=(\d+)/);
            if (match) {
                e.preventDefault();
                filterState.brandId = parseInt(match[1], 10);
                const offcanvasEl = document.getElementById('sidebarMenuLeft');
                if (offcanvasEl && window.bootstrap) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (bsOffcanvas) bsOffcanvas.hide();
                }
                applyProductFilters(true);
                const targetSec = document.getElementById('product-list');
                if (targetSec) targetSec.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Initial Filter Run without pushing state
    applyProductFilters(false);
});

window.addEventListener('popstate', function(event) {
    if (event.state) {
        Object.assign(filterState, event.state);
    } else {
        const params = new URLSearchParams(window.location.search);
        filterState.brandId = parseInt(params.get('brand_id') || '0', 10);
        filterState.filterType = params.get('filter_type') || 'all';
        filterState.priceRange = params.get('price') || 'all';
        filterState.customMinPrice = params.has('min_price') ? parseFloat(params.get('min_price')) : null;
        filterState.customMaxPrice = params.has('max_price') ? parseFloat(params.get('max_price')) : null;
        filterState.ram = params.get('ram') || 'all';
        filterState.rom = params.get('rom') || 'all';
        filterState.sort = params.get('sort') || 'default';
    }
    applyProductFilters(false);
});
</script>

<?php require_once 'app/views/includes/footer.php'; ?>
