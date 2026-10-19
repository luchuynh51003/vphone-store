<?php
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/includes/functions.php';

$prodModel = new ProductModel($pdo);
$brandStmt = $pdo->query("SELECT * FROM brands ORDER BY id ASC");
$brands = $brandStmt->fetchAll();

$brandId = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$ratingMap = $ratingMap ?? [];

$filters = [
    'price_range' => $_GET['price'] ?? ($_GET['price_range'] ?? 'all'),
    'ram' => $_GET['ram'] ?? 'all',
    'rom' => $_GET['rom'] ?? 'all',
    'sort' => $_GET['sort'] ?? 'default',
    'min_price' => $_GET['min_price'] ?? null,
    'max_price' => $_GET['max_price'] ?? null,
];

// Fetch all used products for instant responsive filtering
$usedProducts = $prodModel->getUsed(0, $keyword);

$searchList = [];
foreach ($usedProducts as $p) {
    $pPrice = ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) ? $p['sale_price'] : $p['price'];
    $searchList[] = [
        'id' => (int)$p['id'],
        'name' => $p['name'],
        'price' => (int)$pPrice,
        'old_price' => (int)$p['price'],
        'price_formatted' => number_format($pPrice, 0, ',', '.') . ' VNĐ',
        'image' => $p['image'],
        'rom' => $p['rom'] ?? '128 GB'
    ];
}

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<style>
@keyframes fadeInProduct {
    0% { opacity: 0; transform: translateY(14px) scale(0.98); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}
.product-col-card {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
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

<script>
    window.USED_STORE_PRODUCTS = <?= json_encode($searchList, JSON_UNESCAPED_UNICODE) ?>;
    if (!window.STORE_PRODUCTS || window.STORE_PRODUCTS.length === 0) {
        window.STORE_PRODUCTS = window.GLOBAL_SEARCH_PRODUCTS || window.USED_STORE_PRODUCTS;
    }
</script>

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

<div class="container my-4">
    <div class="bg-white p-3 rounded-4 shadow-sm d-flex flex-wrap gap-2 align-items-center border">
        <span class="fw-bold text-primary me-2"><i class="fa-solid fa-filter me-1"></i>Hãng máy cũ:</span>
        <button type="button" class="btn btn-sm btn-brand-pill <?= $brandId == 0 ? 'active' : '' ?> rounded-pill px-3" data-brand-id="0" data-brand-name="">Tất cả máy cũ</button>
        <?php foreach ($brands as $b): ?>
            <button type="button" class="btn btn-sm btn-brand-pill <?= $brandId == $b['id'] ? 'active' : '' ?> rounded-pill px-3" data-brand-id="<?= $b['id'] ?>" data-brand-name="<?= htmlspecialchars($b['name']) ?>">
                <?= htmlspecialchars($b['name']) ?> Cũ
            </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- BỘ LỌC ĐA TIÊU CHÍ & SẮP XẾP (GỌN GÀNG TRÊN 1 DÒNG DUY NHẤT) -->
<div class="container my-3">
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
                    <button type="button" class="dropdown-item rounded-3 py-1.5 px-2" data-filter="rom" data-value="1024">1 TB</button>
                </div>
            </div>

            <!-- Nút Reset (hiện khi có lọc) -->
            <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none fw-semibold d-none" id="btnResetFilters" onclick="resetAllProductFilters()" title="Xóa tất cả bộ lọc">
                <i class="fa-solid fa-rotate-left me-1"></i>Xóa lọc (<span id="resetCountSpan">0</span>)
            </button>
        </div>

        <!-- Phía bên phải: Số lượng & Dropdown Sắp xếp -->
        <div class="d-flex align-items-center gap-2 ms-auto">
            <span class="badge bg-light text-secondary border rounded-pill px-3 py-2 fw-semibold" id="productCountBadge"><?= count($usedProducts) ?> máy có sẵn</span>
            
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
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container" id="product-list">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0 text-dark fs-5" id="productListTitle">
            <i class="fa-solid fa-tags text-primary me-2"></i>Điện Thoại Cũ Đang Sẵn Hàng
        </h4>
        <small class="text-secondary" id="filterSummaryText">Đang hiển thị toàn bộ máy cũ Like New</small>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="productGridContainer">
        <div id="noProductNotice" class="col-12 text-center py-5 d-none w-100">
            <div class="py-5 px-3 bg-white rounded-4 border shadow-sm my-3 mx-auto" style="max-width: 540px;">
                <i class="fa-solid fa-filter-circle-xmark fa-3x text-muted mb-3 d-block opacity-75"></i>
                <h5 class="fw-bold text-dark mb-2">Chưa tìm thấy điện thoại cũ phù hợp</h5>
                <p class="text-secondary small mb-4">Các tiêu chí lọc (Giá, RAM, ROM, Hãng) hiện chưa có máy cũ nào đáp ứng cùng lúc. Hãy thử nới lỏng mức giá hoặc chọn cấu hình khác.</p>
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" onclick="resetAllProductFilters()">
                    <i class="fa-solid fa-rotate-left me-1"></i>Đặt lại tất cả bộ lọc
                </button>
            </div>
        </div>

        <?php foreach ($usedProducts as $p): ?>
            <?php
                $pEffectivePrice = ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) ? (int)$p['sale_price'] : (int)$p['price'];
                $ramNumber = (int)filter_var($p['ram'] ?? '', FILTER_SANITIZE_NUMBER_INT);
                $baseRomGb = romToGigabytes($p['rom'] ?? '128 GB');
                $rInfo = $ratingMap[$p['id']] ?? null;
                $pRatingVal = $rInfo ? (float)$rInfo['avg_rating'] : 5.0;
                $pRevCount = $rInfo ? (int)$rInfo['review_count'] : 0;
            ?>
            <div class="col product-col-card"
                data-id="<?= (int)$p['id'] ?>"
                data-brand-id="<?= (int)$p['brand_id'] ?>"
                data-brand-name="<?= htmlspecialchars($p['brand_name'] ?? '') ?>"
                data-price="<?= $pEffectivePrice ?>"
                data-ram="<?= $ramNumber ?>"
                data-base-rom="<?= $baseRomGb ?>"
                data-all-roms="<?= $baseRomGb ?>"
                data-rating="<?= $pRatingVal ?>"
                data-review-count="<?= $pRevCount ?>"
                data-name="<?= htmlspecialchars($p['name']) ?>">
                <div class="card h-100 product-card shadow-sm position-relative overflow-hidden">
                    <div class="product-img-wrapper position-relative">
                        <span class="badge badge-used-phone position-absolute top-0 end-0 m-2 px-2 py-1 rounded-pill shadow-sm" style="z-index: 5;" title="Hàng Cũ Like New 99%">
                            <i class="fa-solid fa-recycle me-1"></i><?= htmlspecialchars(!empty($p['condition_desc']) && $p['condition_desc'] !== 'Mới 100%' ? $p['condition_desc'] : 'Máy cũ 99%') ?>
                        </span>

                        <?php if ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']): ?>
                            <?php $percent = round((($p['price'] - $p['sale_price']) / $p['price']) * 100); ?>
                            <span class="badge badge-sale-percent position-absolute top-0 start-0 m-2 px-2 py-1 rounded-pill shadow-sm" style="z-index: 5;">-<?= $percent ?>%</span>
                        <?php endif; ?>

                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    </div>

                    <div class="card-body d-flex flex-column pt-2">
                        <small class="text-primary fw-bold text-uppercase"><?= htmlspecialchars($p['brand_name'] ?? 'Khác') ?> CŨ</small>
                        <h6 class="card-title fw-bold my-1 text-truncate-2 text-dark"><?= htmlspecialchars($p['name']) ?></h6>

                        <div class="d-flex align-items-center gap-1 my-1" style="font-size: 0.72rem;">
                            <span class="text-warning"><i class="fa-solid fa-star"></i></span>
                            <span class="fw-bold text-dark"><?= number_format($pRatingVal, 1) ?></span>
                            <span class="text-secondary">(<?= $pRevCount ?> đánh giá)</span>
                        </div>

                        <div class="specs-badge my-2 d-flex flex-wrap gap-1">
                            <span class="badge"><?= htmlspecialchars($p['ram']) ?></span>
                            <span class="badge"><?= htmlspecialchars($p['rom']) ?></span>
                            <span class="badge bg-info bg-opacity-10 text-primary border border-info border-opacity-25">Bảo hành 6T</span>
                        </div>

                        <div class="mt-auto pt-2">
                            <?php if ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']): ?>
                                <div class="text-danger fw-bold fs-5 mb-0"><?= number_format($p['sale_price'], 0, ',', '.') ?> VNĐ</div>
                                <small class="text-decoration-line-through text-muted"><?= number_format($p['price'], 0, ',', '.') ?> VNĐ (Giá mới)</small>
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
                                <a href="index.php?page=checkout&action=buy_now&id=<?= $p['id'] ?>" class="btn btn-vphone btn-sm rounded-pill fw-bold py-2 shadow-sm text-center">
                                    <i class="fa-solid fa-bolt me-1"></i>MUA NGAY
                                </a>
                                <div class="d-flex gap-1.5 align-items-center">
                                    <a href="index.php?page=detail&id=<?= $p['id'] ?>" class="btn btn-outline-vphone btn-sm rounded-pill flex-grow-1 fw-semibold text-center">
                                        Chi tiết
                                    </a>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill flex-grow-1 fw-bold" onclick="addToCartDirect(this, <?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')">
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

<script>
const filterState = {
    brandId: <?= (int)$brandId ?>,
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

    // 5. Sort Dropdown
    const sortLabels = {
        'default': 'Nổi bật nhất',
        'price-asc': 'Giá: Thấp đến cao',
        'price-desc': 'Giá: Cao đến thấp',
        'rating-desc': 'Đánh giá cao nhất',
        'name-asc': 'Tên A - Z'
    };
    const sortIcons = {
        'default': 'fa-solid fa-fire text-danger',
        'price-asc': 'fa-solid fa-arrow-trend-up text-success',
        'price-desc': 'fa-solid fa-arrow-trend-down text-info',
        'rating-desc': 'fa-solid fa-award text-warning',
        'name-asc': 'fa-solid fa-arrow-down-a-z text-primary'
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

    // 6. Active Count & Reset Button
    let activeFilterCount = 0;
    if (filterState.brandId !== 0) activeFilterCount++;
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
        const cardPrice = parseFloat(card.dataset.price || '0');
        const cardRam = parseInt(card.dataset.ram || '0', 10);
        const cardBaseRom = parseInt(card.dataset.baseRom || '0', 10);
        const cardAllRoms = (card.dataset.allRoms || '').split(',').map(n => parseInt(n.trim(), 10)).filter(Boolean);

        const matchBrand = (filterState.brandId === 0 || cardBrandId === filterState.brandId);

        let matchPrice = true;
        if (filterState.priceRange === 'under-5m') matchPrice = (cardPrice < 5000000);
        else if (filterState.priceRange === '5m-10m') matchPrice = (cardPrice >= 5000000 && cardPrice <= 10000000);
        else if (filterState.priceRange === '10m-20m') matchPrice = (cardPrice >= 10000000 && cardPrice <= 20000000);
        else if (filterState.priceRange === '20m-30m') matchPrice = (cardPrice >= 20000000 && cardPrice <= 30000000);
        else if (filterState.priceRange === 'above-30m') matchPrice = (cardPrice > 30000000);
        else if (filterState.priceRange === 'custom') {
            if (filterState.customMinPrice !== null && cardPrice < filterState.customMinPrice) matchPrice = false;
            if (filterState.customMaxPrice !== null && cardPrice > filterState.customMaxPrice) matchPrice = false;
        }

        let matchRam = true;
        if (filterState.ram !== 'all') {
            matchRam = (cardRam === parseInt(filterState.ram, 10));
        }

        let matchRom = true;
        if (filterState.rom !== 'all') {
            const targetRom = parseInt(filterState.rom, 10);
            matchRom = (cardBaseRom === targetRom || cardAllRoms.includes(targetRom));
        }

        if (matchBrand && matchPrice && matchRam && matchRom) {
            card.classList.remove('d-none');
            visibleCards.push(card);
        } else {
            card.classList.add('d-none');
        }
    });

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

        if (sortType === 'price-asc') return priceA - priceB || idA - idB;
        if (sortType === 'price-desc') return priceB - priceA || idA - idB;
        if (sortType === 'rating-desc') {
            if (ratingB !== ratingA) return ratingB - ratingA;
            return revCountB - revCountA || priceB - priceA;
        }
        if (sortType === 'name-asc') return (a.dataset.name || '').localeCompare(b.dataset.name || '', 'vi', { sensitivity: 'base' });
        return idB - idA;
    });

    visibleCards.forEach(card => {
        grid.appendChild(card);
        card.style.animation = 'fadeInProduct 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards';
    });

    const emptyNotice = document.getElementById('noProductNotice');
    if (emptyNotice) {
        if (visibleCards.length === 0) {
            emptyNotice.classList.remove('d-none');
            grid.appendChild(emptyNotice);
        } else {
            emptyNotice.classList.add('d-none');
        }
    }

    const countBadge = document.getElementById('productCountBadge');
    if (countBadge) countBadge.innerText = visibleCards.length + ' máy có sẵn';

    const summaryText = document.getElementById('filterSummaryText');
    if (summaryText) {
        summaryText.innerText = visibleCards.length > 0 
            ? `Tìm thấy ${visibleCards.length} máy cũ phù hợp tiêu chí`
            : 'Không có máy cũ nào phù hợp';
    }

    if (updateHistory) {
        const params = new URLSearchParams(window.location.search);
        params.set('page', 'used-phones');
        if (filterState.brandId !== 0) params.set('brand_id', filterState.brandId); else params.delete('brand_id');
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
        const newUrl = qs ? 'index.php?' + qs : 'index.php?page=used-phones';
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
    } else if (filterName === 'sort') {
        filterState.sort = 'default';
    }
    applyProductFilters(true);
}

function resetAllProductFilters() {
    filterState.brandId = 0;
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

document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    if (params.has('brand_id')) filterState.brandId = parseInt(params.get('brand_id'), 10) || 0;
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

    // 2. Dropdown Filter Items (Price, RAM, ROM)
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

    applyProductFilters(false);
});

window.addEventListener('popstate', function(event) {
    if (event.state) {
        Object.assign(filterState, event.state);
    } else {
        const params = new URLSearchParams(window.location.search);
        filterState.brandId = parseInt(params.get('brand_id') || '0', 10);
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

