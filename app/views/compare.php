<?php
/**
 * View: So Sánh Sản Phẩm Đối Đầu
 * @var array $product1
 * @var array $product2
 * @var ?array $product3
 * @var array $ratingSummary1
 * @var array $ratingSummary2
 * @var ?array $ratingSummary3
 * @var array $allProducts
 * @var array $brands
 * @var array $hotMatchups
 */

require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/includes/functions.php';

// Khởi tạo an toàn cho biến từ Controller, loại bỏ triệt để cảnh báo Intelephense P1008
if (!isset($product1) || !is_array($product1)) $product1 = [];
if (!isset($product2) || !is_array($product2)) $product2 = [];
if (!isset($product3) || !is_array($product3)) $product3 = null;
if (!isset($ratingSummary1) || !is_array($ratingSummary1)) $ratingSummary1 = [];
if (!isset($ratingSummary2) || !is_array($ratingSummary2)) $ratingSummary2 = [];
if (!isset($ratingSummary3) || !is_array($ratingSummary3)) $ratingSummary3 = null;
if (!isset($allProducts) || !is_array($allProducts)) $allProducts = [];
if (!isset($brands) || !is_array($brands)) $brands = [];
if (!isset($hotMatchups) || !is_array($hotMatchups)) $hotMatchups = [];

// Tự động nạp fallback nếu biến chưa có dữ liệu
if (empty($product1) || empty($product2)) {
    try {
        $db = $pdo ?? ($GLOBALS['pdo'] ?? null);
        if (!$db) {
            require_once __DIR__ . '/../../config/database.php';
            $db = $pdo ?? ($GLOBALS['pdo'] ?? null);
        }
        if ($db instanceof PDO) {
            require_once __DIR__ . '/../models/ReviewModel.php';
            $fallbackProdModel = new ProductModel($db);
            $fallbackRevModel = new ReviewModel($db);
            if (empty($product1)) $product1 = $fallbackProdModel->getById(1) ?: [];
            if (empty($product2)) $product2 = $fallbackProdModel->getById(6) ?: [];
            if (empty($ratingSummary1) && !empty($product1['id'])) $ratingSummary1 = $fallbackRevModel->getRatingSummary((int)$product1['id']);
            if (empty($ratingSummary2) && !empty($product2['id'])) $ratingSummary2 = $fallbackRevModel->getRatingSummary((int)$product2['id']);
            if (empty($allProducts)) $allProducts = $fallbackProdModel->getAll();
            if (empty($brands)) $brands = $db->query("SELECT * FROM brands ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (\Throwable $e) {}
}

// Helper parse phone specs
function parseCompareSpecs(?array $product, ?array $ratingSummary): array {
    if (!$product) {
        return [
            'effPrice' => 0, 'oldPrice' => 0, 'saving' => 0, 'colors' => [],
            'storageList' => [], 'batteryVal' => 5000, 'ramVal' => 8,
            'screenSize' => 6.7, 'screenHz' => 120, 'ratingVal' => 5.0,
            'reviewCount' => 0, 'brandAdvantages' => []
        ];
    }
    $price = (int)($product['price'] ?? 0);
    $salePrice = (int)($product['sale_price'] ?? 0);
    $effPrice = ($salePrice > 0 && $salePrice < $price) ? $salePrice : $price;
    $oldPrice = $price;
    $saving = max(0, $oldPrice - $effPrice);
    $colors = array_map('trim', explode(',', $product['colors'] ?? 'Đen, Trắng'));

    $storageList = json_decode($product['storage_options'] ?? '', true);
    if (!is_array($storageList) || !$storageList) {
        $storageList = getStorageTiers($product['name'] ?? '', $product['rom'] ?? '256 GB');
    }

    preg_match('/(\d+)\s*mah/i', $product['battery'] ?? '', $mBat);
    $batteryVal = isset($mBat[1]) ? (int)$mBat[1] : 5000;

    preg_match('/(\d+)\s*gb/i', $product['ram'] ?? '', $mRam);
    $ramVal = isset($mRam[1]) ? (int)$mRam[1] : (int)filter_var($product['ram'] ?? '8', FILTER_SANITIZE_NUMBER_INT);

    preg_match('/(\d+(?:\.\d+)?)\s*"/i', $product['screen'] ?? '', $mSc);
    $screenSize = isset($mSc[1]) ? (float)$mSc[1] : 6.7;

    preg_match('/(\d+)\s*hz/i', $product['screen'] ?? '', $mHz);
    $screenHz = isset($mHz[1]) ? (int)$mHz[1] : 120;

    $ratingVal = (float)($ratingSummary['avg_rating'] ?? 5.0);
    $reviewCount = (int)($ratingSummary['total_reviews'] ?? 0);

    // Điểm mạnh thương hiệu
    $brandSlug = mb_strtolower($product['brand_name'] ?? '');
    $brandAdvantages = [];
    if (str_contains($brandSlug, 'apple') || str_contains($brandSlug, 'iphone')) {
        $brandAdvantages[] = 'Hệ điều hành iOS mượt mà, hỗ trợ cập nhật lâu dài 5-7 năm';
        $brandAdvantages[] = 'Quay video Cinematic & chống rung hàng đầu phân khúc';
        $brandAdvantages[] = 'Hệ sinh thái Apple đồng bộ hoàn hảo (Mac, iPad, Apple Watch)';
    } elseif (str_contains($brandSlug, 'samsung')) {
        $brandAdvantages[] = 'Màn hình Dynamic AMOLED hiển thị rực rỡ ngoài trời';
        $brandAdvantages[] = 'Hỗ trợ bút S-Pen & bộ công cụ Galaxy AI tiếng Việt đỉnh cao';
        $brandAdvantages[] = 'Đa nhiệm chia đôi màn hình và chế độ máy tính Samsung DeX';
    } elseif (str_contains($brandSlug, 'xiaomi')) {
        $brandAdvantages[] = 'Hệ thống ống kính quang học Leica chụp ảnh chân thực';
        $brandAdvantages[] = 'Công nghệ sạc siêu nhanh hàng đầu thị trường';
    } elseif (str_contains($brandSlug, 'huawei')) {
        $brandAdvantages[] = 'Màn hình gập sáng tạo, camera XMAGE chụp đêm siêu nét';
    } else {
        $brandAdvantages[] = 'Thiết kế hiện đại, tỉ lệ màn hình viền siêu mỏng';
    }

    return [
        'effPrice' => $effPrice,
        'oldPrice' => $oldPrice,
        'saving' => $saving,
        'colors' => $colors,
        'storageList' => $storageList,
        'batteryVal' => $batteryVal,
        'ramVal' => $ramVal,
        'screenSize' => $screenSize,
        'screenHz' => $screenHz,
        'ratingVal' => $ratingVal,
        'reviewCount' => $reviewCount,
        'brandAdvantages' => $brandAdvantages
    ];
}

$p1 = parseCompareSpecs($product1, $ratingSummary1);
$p2 = parseCompareSpecs($product2, $ratingSummary2);
$p3 = !empty($product3) ? parseCompareSpecs($product3, $ratingSummary3) : null;
$hasP3 = ($p3 !== null);

// Danh sách ưu điểm riêng của Máy 1 so với Máy 2
$p1Pros = [];
if ($p1['effPrice'] < $p2['effPrice']) {
    $p1Pros[] = 'Giá mềm hơn, tiết kiệm đến ' . formatPrice($p2['effPrice'] - $p1['effPrice']);
}
if ($p1['batteryVal'] > $p2['batteryVal']) {
    $p1Pros[] = 'Pin trâu hơn +' . ($p1['batteryVal'] - $p2['batteryVal']) . ' mAh (' . $p1['batteryVal'] . ' mAh)';
}
if ($p1['ramVal'] > $p2['ramVal']) {
    $p1Pros[] = 'RAM lớn hơn +' . ($p1['ramVal'] - $p2['ramVal']) . ' GB cho đa nhiệm tốt hơn';
}
if ($p1['screenHz'] > $p2['screenHz']) {
    $p1Pros[] = 'Tần số quét mượt hơn +' . ($p1['screenHz'] - $p2['screenHz']) . ' Hz (' . $p1['screenHz'] . ' Hz)';
}
if ($p1['screenSize'] > $p2['screenSize']) {
    $p1Pros[] = 'Màn hình rộng rãi hơn (' . $p1['screenSize'] . '" so với ' . $p2['screenSize'] . '")';
}
if ($p1['ratingVal'] > $p2['ratingVal']) {
    $p1Pros[] = 'Khách hàng đánh giá hài lòng cao hơn (' . number_format($p1['ratingVal'], 1) . '★)';
}
foreach ($p1['brandAdvantages'] as $ba) {
    if (count($p1Pros) < 5) $p1Pros[] = $ba;
}

// Danh sách ưu điểm riêng của Máy 2 so với Máy 1
$p2Pros = [];
if ($p2['effPrice'] < $p1['effPrice']) {
    $p2Pros[] = 'Giá mềm hơn, tiết kiệm đến ' . formatPrice($p1['effPrice'] - $p2['effPrice']);
}
if ($p2['batteryVal'] > $p1['batteryVal']) {
    $p2Pros[] = 'Pin trâu hơn +' . ($p2['batteryVal'] - $p1['batteryVal']) . ' mAh (' . $p2['batteryVal'] . ' mAh)';
}
if ($p2['ramVal'] > $p1['ramVal']) {
    $p2Pros[] = 'RAM lớn hơn +' . ($p2['ramVal'] - $p1['ramVal']) . ' GB cho đa nhiệm tốt hơn';
}
if ($p2['screenHz'] > $p1['screenHz']) {
    $p2Pros[] = 'Tần số quét mượt hơn +' . ($p2['screenHz'] - $p1['screenHz']) . ' Hz (' . $p2['screenHz'] . ' Hz)';
}
if ($p2['screenSize'] > $p1['screenSize']) {
    $p2Pros[] = 'Màn hình rộng rãi hơn (' . $p2['screenSize'] . '" so với ' . $p1['screenSize'] . '")';
}
if ($p2['ratingVal'] > $p1['ratingVal']) {
    $p2Pros[] = 'Khách hàng đánh giá hài lòng cao hơn (' . number_format($p2['ratingVal'], 1) . '★)';
}
foreach ($p2['brandAdvantages'] as $ba) {
    if (count($p2Pros) < 5) $p2Pros[] = $ba;
}

// Tính tỉ lệ phần trăm so sánh thanh đo đối đầu (Tug-of-war balance meter chuẩn 100%)
$batSum = (int)($p1['batteryVal'] + $p2['batteryVal']);
$p1BatPct = $batSum > 0 ? round(($p1['batteryVal'] / $batSum) * 100) : 50;
$p2BatPct = 100 - $p1BatPct;

$ramSum = (int)($p1['ramVal'] + $p2['ramVal']);
$p1RamPct = $ramSum > 0 ? round(($p1['ramVal'] / $ramSum) * 100) : 50;
$p2RamPct = 100 - $p1RamPct;

$hzSum = (int)($p1['screenHz'] + $p2['screenHz']);
$p1HzPct = $hzSum > 0 ? round(($p1['screenHz'] / $hzSum) * 100) : 50;
$p2HzPct = 100 - $p1HzPct;

// Tỉ lệ giá (máy rẻ hơn có tỉ lệ kinh tế tốt hơn, bằng nhau = 50-50)
if ($p1['effPrice'] == $p2['effPrice']) {
    $p1EcoScore = 50;
    $p2EcoScore = 50;
} elseif ($p1['effPrice'] > 0 && $p2['effPrice'] > 0) {
    $totalPrice = $p1['effPrice'] + $p2['effPrice'];
    $p1EcoScore = round(($p2['effPrice'] / $totalPrice) * 100);
    $p2EcoScore = 100 - $p1EcoScore;
} else {
    $p1EcoScore = 50;
    $p2EcoScore = 50;
}

// Huy hiệu người chiến thắng cho từng thông số
$batDiff = abs($p1['batteryVal'] - $p2['batteryVal']);
if ($batDiff === 0) {
    $batWinnerBadge = '<span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2.5 py-1 fw-bold small"><i class="fa-solid fa-equals me-1"></i>Bằng nhau</span>';
} elseif ($p1['batteryVal'] > $p2['batteryVal']) {
    $batPctAdv = round(($batDiff / max(1, $p2['batteryVal'])) * 100);
    $batWinnerBadge = '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 fw-bold small"><i class="fa-solid fa-crown text-warning me-1"></i>' . htmlspecialchars($product1['name']) . ' +' . $batDiff . ' mAh (+' . $batPctAdv . '%)</span>';
} else {
    $batPctAdv = round(($batDiff / max(1, $p1['batteryVal'])) * 100);
    $batWinnerBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 fw-bold small"><i class="fa-solid fa-crown text-warning me-1"></i>' . htmlspecialchars($product2['name']) . ' +' . $batDiff . ' mAh (+' . $batPctAdv . '%)</span>';
}

$ramDiff = abs($p1['ramVal'] - $p2['ramVal']);
if ($ramDiff === 0) {
    $ramWinnerBadge = '<span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2.5 py-1 fw-bold small"><i class="fa-solid fa-equals me-1"></i>Ngang nhau (' . $p1['ramVal'] . ' GB)</span>';
} elseif ($p1['ramVal'] > $p2['ramVal']) {
    $ramWinnerBadge = '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 fw-bold small"><i class="fa-solid fa-crown text-warning me-1"></i>' . htmlspecialchars($product1['name']) . ' +' . $ramDiff . ' GB</span>';
} else {
    $ramWinnerBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 fw-bold small"><i class="fa-solid fa-crown text-warning me-1"></i>' . htmlspecialchars($product2['name']) . ' +' . $ramDiff . ' GB</span>';
}

$hzDiff = abs($p1['screenHz'] - $p2['screenHz']);
if ($hzDiff === 0) {
    $hzWinnerBadge = '<span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2.5 py-1 fw-bold small"><i class="fa-solid fa-equals me-1"></i>Ngang nhau (' . $p1['screenHz'] . ' Hz)</span>';
} elseif ($p1['screenHz'] > $p2['screenHz']) {
    $hzWinnerBadge = '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 fw-bold small"><i class="fa-solid fa-crown text-warning me-1"></i>' . htmlspecialchars($product1['name']) . ' mượt hơn +' . $hzDiff . ' Hz</span>';
} else {
    $hzWinnerBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 fw-bold small"><i class="fa-solid fa-crown text-warning me-1"></i>' . htmlspecialchars($product2['name']) . ' mượt hơn +' . $hzDiff . ' Hz</span>';
}

$priceDiff = abs($p1['effPrice'] - $p2['effPrice']);
if ($priceDiff === 0) {
    $ecoWinnerBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 fw-bold small"><i class="fa-solid fa-check me-1"></i>Mức giá tương đương (' . formatPrice($p1['effPrice']) . ')</span>';
} elseif ($p1['effPrice'] < $p2['effPrice']) {
    $ecoWinnerBadge = '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 fw-bold small"><i class="fa-solid fa-piggy-bank text-warning me-1"></i>' . htmlspecialchars($product1['name']) . ' tiết kiệm hơn ' . formatPrice($priceDiff) . '</span>';
} else {
    $ecoWinnerBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2.5 py-1 fw-bold small"><i class="fa-solid fa-piggy-bank text-warning me-1"></i>' . htmlspecialchars($product2['name']) . ' tiết kiệm hơn ' . formatPrice($priceDiff) . '</span>';
}

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<style>
/* CSS chống tràn ảnh và cố định kích thước hiển thị chuẩn cho mọi trình duyệt */
.duel-image-container,
.duel-image-glow {
    height: 220px !important;
    max-width: 280px !important;
    width: 100% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: relative !important;
    overflow: hidden !important;
    margin: 0 auto !important;
    border-radius: 20px !important;
}
.duel-glow-blue {
    background: radial-gradient(circle, rgba(0, 102, 204, 0.12) 0%, rgba(255, 255, 255, 0) 72%) !important;
}
.duel-glow-red {
    background: radial-gradient(circle, rgba(239, 68, 68, 0.12) 0%, rgba(255, 255, 255, 0) 72%) !important;
}
.duel-glow-green {
    background: radial-gradient(circle, rgba(16, 185, 129, 0.12) 0%, rgba(255, 255, 255, 0) 72%) !important;
}
.duel-device-img {
    max-height: 195px !important;
    max-width: 100% !important;
    width: auto !important;
    height: auto !important;
    object-fit: contain !important;
    object-position: center !important;
    display: block !important;
    margin: 0 auto !important;
    filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.12)) !important;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
.duel-card-modern:hover .duel-device-img {
    transform: scale(1.05) translateY(-3px) !important;
}
.duel-card-blue {
    border-color: rgba(0, 102, 204, 0.25) !important;
}
.duel-card-red {
    border-color: rgba(239, 68, 68, 0.25) !important;
}
.duel-card-green {
    border-color: rgba(16, 185, 129, 0.25) !important;
}
.color-swatch-dot {
    width: 20px !important;
    height: 20px !important;
    border-radius: 50% !important;
    border: 2px solid #ffffff !important;
    box-shadow: 0 0 0 1px #cbd5e1 !important;
    cursor: pointer !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease !important;
}
.color-swatch-dot.active {
    box-shadow: 0 0 0 2.5px var(--v-blue-primary, #0066cc) !important;
    transform: scale(1.2) !important;
}
/* Smooth scrolling & Section anchor offsets */
html {
    scroll-behavior: smooth !important;
}
[id^="section-"],
tr.group-header-row {
    scroll-margin-top: 140px !important;
}

.legend-color-dot {
    width: 10px !important;
    height: 10px !important;
    border-radius: 50% !important;
    display: inline-block !important;
    flex-shrink: 0 !important;
    box-shadow: 0 0 0 1.5px #ffffff !important;
}

.stat-dual-progress {
    height: 20px !important;
    background-color: #f1f5f9 !important;
    border-radius: 999px !important;
    display: flex !important;
    overflow: hidden !important;
    position: relative !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.06) !important;
}
.stat-bar-left {
    height: 100% !important;
    background: linear-gradient(90deg, #0284c7 0%, #0066cc 100%) !important;
    border-radius: 999px 0 0 999px !important;
    transition: width 0.75s cubic-bezier(0.16, 1, 0.3, 1) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    padding-left: 10px !important;
    font-size: 0.7rem !important;
    font-weight: 800 !important;
    color: #ffffff !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
}
.stat-bar-right {
    height: 100% !important;
    margin-left: auto !important;
    background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%) !important;
    border-radius: 0 999px 999px 0 !important;
    transition: width 0.75s cubic-bezier(0.16, 1, 0.3, 1) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: flex-end !important;
    padding-right: 10px !important;
    font-size: 0.7rem !important;
    font-weight: 800 !important;
    color: #ffffff !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
}
.stat-divider-center {
    position: absolute !important;
    left: 50% !important;
    top: 0 !important;
    bottom: 0 !important;
    width: 2px !important;
    background: rgba(255, 255, 255, 0.95) !important;
    transform: translateX(-50%) !important;
    z-index: 3 !important;
    box-shadow: 0 0 4px rgba(0,0,0,0.2) !important;
}

.spec-row {
    transition: opacity 0.2s cubic-bezier(0.16, 1, 0.3, 1), transform 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
.quick-jump-nav .btn {
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
.duel-card-modern,
.duel-pros-box {
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
.duel-card-modern:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 14px 30px -10px rgba(15, 23, 42, 0.12) !important;
}
.duel-pros-box:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 10px 25px -8px rgba(15, 23, 42, 0.1) !important;
}

.sticky-compare-bar {
    position: fixed !important;
    top: 76px !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 1020 !important;
    background: rgba(255, 255, 255, 0.96) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    backdrop-filter: blur(12px) !important;
    border-bottom: 1px solid #e2e8f0 !important;
    box-shadow: 0 8px 25px rgba(15, 23, 42, 0.08) !important;
    transition: opacity 0.25s cubic-bezier(0.16, 1, 0.3, 1), transform 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
@media (max-width: 991.98px) {
    .sticky-compare-bar {
        top: 118px !important;
    }
}
.sticky-mini-thumb {
    width: 36px !important;
    height: 36px !important;
    object-fit: contain !important;
    background: #ffffff !important;
    padding: 2px !important;
    flex-shrink: 0 !important;
}

/* ==========================================================================
   DARK MODE OVERRIDES CHO TRANG SO SÁNH (COMPARE PAGE)
   ========================================================================== */
[data-bs-theme="dark"] .sticky-compare-bar {
    background: rgba(19, 31, 55, 0.96) !important;
    border-bottom-color: rgba(255, 255, 255, 0.1) !important;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.45) !important;
}
[data-bs-theme="dark"] .sticky-mini-thumb {
    background: transparent !important;
}
[data-bs-theme="dark"] .duel-card-modern {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #f1f5f9 !important;
}
[data-bs-theme="dark"] .duel-card-blue {
    border-color: rgba(56, 189, 248, 0.35) !important;
}
[data-bs-theme="dark"] .duel-card-red {
    border-color: rgba(239, 68, 68, 0.35) !important;
}
[data-bs-theme="dark"] .duel-card-green {
    border-color: rgba(16, 185, 129, 0.35) !important;
}
[data-bs-theme="dark"] .duel-pros-box {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #f1f5f9 !important;
}
[data-bs-theme="dark"] .quick-jump-nav {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
[data-bs-theme="dark"] .quick-jump-nav .btn-light {
    background: #0e172a !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #cbd5e1 !important;
}
[data-bs-theme="dark"] .quick-jump-nav .btn-light:hover {
    background: #1a2a4a !important;
    color: #38bdf8 !important;
}
[data-bs-theme="dark"] .stat-dual-progress {
    background-color: #0e172a !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
[data-bs-theme="dark"] .compare-matrix-table .group-header-row td {
    background-color: #1a2a4a !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #38bdf8 !important;
}
[data-bs-theme="dark"] .color-swatch-dot {
    border-color: #0e172a !important;
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.2) !important;
}
[data-bs-theme="dark"] .spec-winner-cell {
    background-color: rgba(16, 185, 129, 0.15) !important;
}
[data-bs-theme="dark"] .compare-page-modern .bg-white {
    background: #131f37 !important;
    color: #f1f5f9 !important;
}
[data-bs-theme="dark"] .compare-page-modern .border-bottom,
[data-bs-theme="dark"] .compare-page-modern .border-top,
[data-bs-theme="dark"] .compare-page-modern .border-start,
[data-bs-theme="dark"] .compare-page-modern .border-end,
[data-bs-theme="dark"] .compare-page-modern .border {
    border-color: rgba(255, 255, 255, 0.08) !important;
}
</style>

<?php
$p1Id = (int)($product1['id'] ?? 1);
$p2Id = (int)($product2['id'] ?? 6);
$p3Id = (int)($product3['id'] ?? 0);
$p1Name = htmlspecialchars($product1['name'] ?? 'Sản phẩm 1');
$p2Name = htmlspecialchars($product2['name'] ?? 'Sản phẩm 2');
$p3Name = htmlspecialchars($product3['name'] ?? 'Sản phẩm 3');
?>

<div class="compare-page-modern pb-5">
    <!-- Breadcrumb -->
    <div class="bg-white border-bottom py-2 mb-3">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small align-items-center">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted"><i class="fa-solid fa-house me-1"></i>Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="index.php?page=compare" class="text-decoration-none text-muted">So sánh</a></li>
                    <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">
                        <?= $p1Name ?> vs <?= $p2Name ?>
                        <?= $hasP3 ? ' vs ' . $p3Name : '' ?>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="container">
        <!-- Modern Hero Header -->
        <div class="compare-hero-modern p-4 p-md-4.5 rounded-4 mb-4 border shadow-sm position-relative overflow-hidden">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary bg-opacity-10 text-primary fw-bold small mb-2 border border-primary border-opacity-25">
                        <i class="fa-solid fa-code-compare animate-pulse"></i>
                        <span>SO SÁNH CÔNG NGHỆ CHUYÊN SÂU 2026</span>
                    </div>
                    <h1 class="fw-extrabold display-6 text-dark mb-2 tracking-tight">
                        <?= $p1Name ?> <span class="text-primary">VS</span> <?= $p2Name ?>
                        <?php if ($hasP3): ?>
                            <span class="text-success">VS</span> <?= $p3Name ?>
                        <?php endif; ?>
                    </h1>
                    <p class="text-secondary small mb-3 mb-lg-0" style="max-width: 620px;">
                        Đối đầu trực diện về cấu hình, trải nghiệm hiển thị, thời lượng pin và phân tích mức giá tối ưu nhất giúp bạn chọn đúng chiếc máy ưng ý.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-inline-flex gap-2 flex-wrap justify-content-lg-end">
                        <?php if (!$hasP3): ?>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-2 fw-semibold shadow-2xs d-inline-flex align-items-center gap-1.5" onclick="openProductSelector(3)">
                                <i class="fa-solid fa-plus-circle"></i>
                                <span>Thêm máy thứ 3 để so sánh</span>
                            </button>
                        <?php else: ?>
                            <a href="index.php?page=compare&p1=<?= $p1Id ?>&p2=<?= $p2Id ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-2 fw-semibold shadow-2xs d-inline-flex align-items-center gap-1.5">
                                <i class="fa-solid fa-xmark"></i>
                                <span>Trở về so sánh 2 máy</span>
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-white border btn-sm rounded-pill px-3 py-2 fw-semibold shadow-2xs text-secondary d-inline-flex align-items-center gap-1.5" onclick="copyCompareLink()">
                            <i class="fa-solid fa-share-nodes text-primary"></i>
                            <span>Chia sẻ</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cặp đấu HOT Pills -->
            <div class="d-flex align-items-center gap-2 flex-wrap pt-3 mt-3 border-top border-light">
                <span class="small fw-bold text-muted d-flex align-items-center gap-1"><i class="fa-solid fa-fire text-danger"></i>Gợi ý đối đầu:</span>
                <?php foreach ($hotMatchups as $hm): ?>
                    <?php 
                        $isCurMatchup = (($p1Id == $hm['p1'] && $p2Id == $hm['p2']) || ($p1Id == $hm['p2'] && $p2Id == $hm['p1']));
                    ?>
                    <a href="index.php?page=compare&p1=<?= $hm['p1'] ?>&p2=<?= $hm['p2'] ?>" 
                       class="btn btn-sm rounded-pill px-3 py-1 <?= $isCurMatchup ? 'btn-primary shadow-sm text-white fw-bold' : 'btn-light border text-dark' ?>" 
                       style="font-size: 0.76rem;">
                        <?= htmlspecialchars($hm['title']) ?>
                        <?php if (!empty($hm['hot'])): ?>
                            <span class="badge bg-danger rounded-pill ms-1" style="font-size: 0.62rem;">HOT</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- DUEL ARENA CARDS -->
        <div id="section-duel-arena" class="row <?= $hasP3 ? 'row-cols-1 row-cols-lg-3 g-3' : 'row-cols-1 row-cols-md-2 g-3 g-xl-4' ?> mb-4 position-relative">
            <!-- PRODUCT 1 CARD -->
            <div class="col">
                <div class="duel-card-modern duel-card-blue h-100 p-3.5 p-xl-4 rounded-4 border bg-white shadow-sm d-flex flex-column position-relative">
                    <!-- Card Top Action Bar -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1 fw-bold" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-mobile-screen me-1"></i>SẢN PHẨM 1
                        </span>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-semibold text-primary btn-change-device shadow-2xs" onclick="openProductSelector(1)">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>Đổi máy
                        </button>
                    </div>

                    <!-- Image with subtle glow & color selector -->
                    <div class="text-center my-2 position-relative">
                        <div class="duel-image-container duel-glow-blue mx-auto" style="height: 220px; max-width: 280px; width: 100%; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; margin: 0 auto; border-radius: 20px;">
                            <img id="duelImg1" src="<?= htmlspecialchars($product1['image']) ?>" alt="<?= htmlspecialchars($product1['name']) ?>" class="duel-device-img" style="max-height: 195px; max-width: 100%; width: auto; height: auto; object-fit: contain; display: block; margin: 0 auto;">
                        </div>

                        <!-- Color selection dots -->
                        <div class="d-flex justify-content-center gap-1.5 flex-wrap mt-2">
                            <?php foreach ($p1['colors'] as $cIdx => $cName): ?>
                                <?php 
                                    $cImg = getProductColorImage((int)$product1['id'], $cName, $product1['image']);
                                    $hex = getColorHexValue($cName);
                                ?>
                                <button type="button" class="color-swatch-dot <?= $cIdx === 0 ? 'active' : '' ?>" 
                                        style="background: <?= $hex ?>;" 
                                        title="<?= htmlspecialchars($cName) ?>" 
                                        onclick="changeDuelImage(1, '<?= htmlspecialchars(addslashes($cImg)) ?>', this)">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Title & Brand -->
                    <div class="text-center mt-2">
                        <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small mb-1">
                            <?= htmlspecialchars($product1['brand_name'] ?? 'Chính hãng') ?>
                        </span>
                        <h4 class="fw-bold text-dark mb-1 duel-title">
                            <a href="index.php?page=detail&id=<?= $product1['id'] ?>" class="text-decoration-none text-dark hover-primary" target="_blank">
                                <?= htmlspecialchars($product1['name']) ?>
                            </a>
                        </h4>
                        <div class="d-flex align-items-center justify-content-center gap-1 small mb-2">
                            <?= renderStarRating($p1['ratingVal'], 'small', true) ?>
                            <span class="text-muted ms-1">(<?= $p1['reviewCount'] ?> đánh giá)</span>
                        </div>
                    </div>

                    <!-- Price Block -->
                    <div class="duel-price-widget p-3 rounded-3 bg-light border text-center my-2">
                        <div class="d-flex align-items-baseline justify-content-center gap-2 flex-wrap">
                            <span class="text-danger fw-extrabold fs-4"><?= formatPrice($p1['effPrice']) ?></span>
                            <?php if ($p1['oldPrice'] > $p1['effPrice']): ?>
                                <span class="text-decoration-line-through text-muted small"><?= formatPrice($p1['oldPrice']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($p1['saving'] > 0): ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-0.5 small mt-1">
                                Tiết kiệm <?= formatPrice($p1['saving']) ?>
                            </span>
                        <?php endif; ?>
                        <div class="mt-1 small">
                            <?php if ((int)$product1['quantity'] > 0): ?>
                                <span class="text-success fw-semibold"><i class="fa-solid fa-circle-check me-1"></i>Còn <?= $product1['quantity'] ?> máy sẵn kho</span>
                            <?php else: ?>
                                <span class="text-danger fw-semibold"><i class="fa-solid fa-circle-xmark me-1"></i>Tạm hết hàng</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Buy Actions -->
                    <div class="row g-2 mt-auto pt-2">
                        <div class="col-7">
                            <?php if ((int)$product1['quantity'] > 0): ?>
                                <a href="index.php?page=cart&action=add&id=<?= $product1['id'] ?>" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm text-center">
                                    <i class="fa-solid fa-bolt me-1"></i>Mua ngay
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100 rounded-pill fw-bold py-2" disabled>Hết hàng</button>
                            <?php endif; ?>
                        </div>
                        <div class="col-5">
                            <?php if ((int)$product1['quantity'] > 0): ?>
                                <button type="button" class="btn btn-outline-primary w-100 rounded-pill fw-semibold py-2" onclick="addToCartDirect(this, <?= $product1['id'] ?>, '<?= htmlspecialchars(addslashes($product1['name'])) ?>')">
                                    <i class="fa-solid fa-cart-plus me-1"></i>Thêm giỏ
                                </button>
                            <?php else: ?>
                                <a href="index.php?page=detail&id=<?= $product1['id'] ?>" class="btn btn-outline-secondary w-100 rounded-pill fw-semibold py-2">Chi tiết</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRODUCT 2 CARD -->
            <div class="col">
                <div class="duel-card-modern duel-card-red h-100 p-3.5 p-xl-4 rounded-4 border bg-white shadow-sm d-flex flex-column position-relative">
                    <!-- Card Top Action Bar -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1 fw-bold" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-mobile-screen me-1"></i>SẢN PHẨM 2
                        </span>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-semibold text-danger btn-change-device shadow-2xs" onclick="openProductSelector(2)">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>Đổi máy
                        </button>
                    </div>

                    <!-- Image with subtle glow & color selector -->
                    <div class="text-center my-2 position-relative">
                        <div class="duel-image-container duel-glow-red mx-auto" style="height: 220px; max-width: 280px; width: 100%; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; margin: 0 auto; border-radius: 20px;">
                            <img id="duelImg2" src="<?= htmlspecialchars($product2['image']) ?>" alt="<?= htmlspecialchars($product2['name']) ?>" class="duel-device-img" style="max-height: 195px; max-width: 100%; width: auto; height: auto; object-fit: contain; display: block; margin: 0 auto;">
                        </div>

                        <!-- Color selection dots -->
                        <div class="d-flex justify-content-center gap-1.5 flex-wrap mt-2">
                            <?php foreach ($p2['colors'] as $cIdx => $cName): ?>
                                <?php 
                                    $cImg = getProductColorImage((int)$product2['id'], $cName, $product2['image']);
                                    $hex = getColorHexValue($cName);
                                ?>
                                <button type="button" class="color-swatch-dot <?= $cIdx === 0 ? 'active' : '' ?>" 
                                        style="background: <?= $hex ?>;" 
                                        title="<?= htmlspecialchars($cName) ?>" 
                                        onclick="changeDuelImage(2, '<?= htmlspecialchars(addslashes($cImg)) ?>', this)">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Title & Brand -->
                    <div class="text-center mt-2">
                        <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small mb-1">
                            <?= htmlspecialchars($product2['brand_name'] ?? 'Chính hãng') ?>
                        </span>
                        <h4 class="fw-bold text-dark mb-1 duel-title">
                            <a href="index.php?page=detail&id=<?= $product2['id'] ?>" class="text-decoration-none text-dark hover-primary" target="_blank">
                                <?= htmlspecialchars($product2['name']) ?>
                            </a>
                        </h4>
                        <div class="d-flex align-items-center justify-content-center gap-1 small mb-2">
                            <?= renderStarRating($p2['ratingVal'], 'small', true) ?>
                            <span class="text-muted ms-1">(<?= $p2['reviewCount'] ?> đánh giá)</span>
                        </div>
                    </div>

                    <!-- Price Block -->
                    <div class="duel-price-widget p-3 rounded-3 bg-light border text-center my-2">
                        <div class="d-flex align-items-baseline justify-content-center gap-2 flex-wrap">
                            <span class="text-danger fw-extrabold fs-4"><?= formatPrice($p2['effPrice']) ?></span>
                            <?php if ($p2['oldPrice'] > $p2['effPrice']): ?>
                                <span class="text-decoration-line-through text-muted small"><?= formatPrice($p2['oldPrice']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($p2['saving'] > 0): ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-0.5 small mt-1">
                                Tiết kiệm <?= formatPrice($p2['saving']) ?>
                            </span>
                        <?php endif; ?>
                        <div class="mt-1 small">
                            <?php if ((int)$product2['quantity'] > 0): ?>
                                <span class="text-success fw-semibold"><i class="fa-solid fa-circle-check me-1"></i>Còn <?= $product2['quantity'] ?> máy sẵn kho</span>
                            <?php else: ?>
                                <span class="text-danger fw-semibold"><i class="fa-solid fa-circle-xmark me-1"></i>Tạm hết hàng</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Buy Actions -->
                    <div class="row g-2 mt-auto pt-2">
                        <div class="col-7">
                            <?php if ((int)$product2['quantity'] > 0): ?>
                                <a href="index.php?page=cart&action=add&id=<?= $product2['id'] ?>" class="btn btn-danger w-100 rounded-pill fw-bold py-2 shadow-sm text-center">
                                    <i class="fa-solid fa-bolt me-1"></i>Mua ngay
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100 rounded-pill fw-bold py-2" disabled>Hết hàng</button>
                            <?php endif; ?>
                        </div>
                        <div class="col-5">
                            <?php if ((int)$product2['quantity'] > 0): ?>
                                <button type="button" class="btn btn-outline-danger w-100 rounded-pill fw-semibold py-2" onclick="addToCartDirect(this, <?= $product2['id'] ?>, '<?= htmlspecialchars(addslashes($product2['name'])) ?>')">
                                    <i class="fa-solid fa-cart-plus me-1"></i>Thêm giỏ
                                </button>
                            <?php else: ?>
                                <a href="index.php?page=detail&id=<?= $product2['id'] ?>" class="btn btn-outline-secondary w-100 rounded-pill fw-semibold py-2">Chi tiết</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRODUCT 3 CARD (If present) -->
            <?php if ($hasP3): ?>
                <div class="col">
                    <div class="duel-card-modern duel-card-green h-100 p-3.5 p-xl-4 rounded-4 border bg-white shadow-sm d-flex flex-column position-relative">
                        <!-- Card Top Action Bar -->
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 fw-bold" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-mobile-screen me-1"></i>SẢN PHẨM 3
                            </span>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 fw-semibold text-success btn-change-device shadow-2xs" onclick="openProductSelector(3)">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i>Đổi
                                </button>
                                <a href="index.php?page=compare&p1=<?= $product1['id'] ?>&p2=<?= $product2['id'] ?>" class="btn btn-sm btn-light border rounded-circle text-danger shadow-2xs" style="width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center;" title="Bỏ máy này khỏi so sánh">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            </div>
                        </div>

                    <div class="text-center my-2 position-relative">
                        <div class="duel-image-container duel-glow-green mx-auto" style="height: 220px; max-width: 280px; width: 100%; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; margin: 0 auto; border-radius: 20px;">
                            <img id="duelImg3" src="<?= htmlspecialchars($product3['image']) ?>" alt="<?= htmlspecialchars($product3['name']) ?>" class="duel-device-img" style="max-height: 195px; max-width: 100%; width: auto; height: auto; object-fit: contain; display: block; margin: 0 auto;">
                        </div>

                            <!-- Color selection dots -->
                            <div class="d-flex justify-content-center gap-1.5 flex-wrap mt-2">
                                <?php foreach ($p3['colors'] as $cIdx => $cName): ?>
                                    <?php 
                                        $cImg = getProductColorImage((int)$product3['id'], $cName, $product3['image']);
                                        $hex = getColorHexValue($cName);
                                    ?>
                                    <button type="button" class="color-swatch-dot <?= $cIdx === 0 ? 'active' : '' ?>" 
                                            style="background: <?= $hex ?>;" 
                                            title="<?= htmlspecialchars($cName) ?>" 
                                            onclick="changeDuelImage(3, '<?= htmlspecialchars(addslashes($cImg)) ?>', this)">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Title & Brand -->
                        <div class="text-center mt-2">
                            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-0.5 small mb-1">
                                <?= htmlspecialchars($product3['brand_name'] ?? 'Chính hãng') ?>
                            </span>
                            <h4 class="fw-bold text-dark mb-1 duel-title">
                                <a href="index.php?page=detail&id=<?= $product3['id'] ?>" class="text-decoration-none text-dark hover-primary" target="_blank">
                                    <?= htmlspecialchars($product3['name']) ?>
                                </a>
                            </h4>
                            <div class="d-flex align-items-center justify-content-center gap-1 small mb-2">
                                <?= renderStarRating($p3['ratingVal'], 'small', true) ?>
                                <span class="text-muted ms-1">(<?= $p3['reviewCount'] ?> đánh giá)</span>
                            </div>
                        </div>

                        <!-- Price Block -->
                        <div class="duel-price-widget p-3 rounded-3 bg-light border text-center my-2">
                            <div class="d-flex align-items-baseline justify-content-center gap-2 flex-wrap">
                                <span class="text-danger fw-extrabold fs-4"><?= formatPrice($p3['effPrice']) ?></span>
                                <?php if ($p3['oldPrice'] > $p3['effPrice']): ?>
                                    <span class="text-decoration-line-through text-muted small"><?= formatPrice($p3['oldPrice']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($p3['saving'] > 0): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-0.5 small mt-1">
                                    Tiết kiệm <?= formatPrice($p3['saving']) ?>
                                </span>
                            <?php endif; ?>
                            <div class="mt-1 small">
                                <?php if ((int)$product3['quantity'] > 0): ?>
                                    <span class="text-success fw-semibold"><i class="fa-solid fa-circle-check me-1"></i>Còn <?= $product3['quantity'] ?> máy sẵn kho</span>
                                <?php else: ?>
                                    <span class="text-danger fw-semibold"><i class="fa-solid fa-circle-xmark me-1"></i>Tạm hết hàng</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Buy Actions -->
                        <div class="row g-2 mt-auto pt-2">
                            <div class="col-7">
                                <?php if ((int)$product3['quantity'] > 0): ?>
                                    <a href="index.php?page=cart&action=add&id=<?= $product3['id'] ?>" class="btn btn-success w-100 rounded-pill fw-bold py-2 shadow-sm text-center">
                                        <i class="fa-solid fa-bolt me-1"></i>Mua ngay
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100 rounded-pill fw-bold py-2" disabled>Hết hàng</button>
                                <?php endif; ?>
                            </div>
                            <div class="col-5">
                                <?php if ((int)$product3['quantity'] > 0): ?>
                                    <button type="button" class="btn btn-outline-success w-100 rounded-pill fw-semibold py-2" onclick="addToCartDirect(this, <?= $product3['id'] ?>, '<?= htmlspecialchars(addslashes($product3['name'])) ?>')">
                                        <i class="fa-solid fa-cart-plus me-1"></i>Thêm giỏ
                                    </button>
                                <?php else: ?>
                                    <a href="index.php?page=detail&id=<?= $product3['id'] ?>" class="btn btn-outline-secondary w-100 rounded-pill fw-semibold py-2">Chi tiết</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- VISUAL STAT RATIO BARS (VERSUS STYLE BENCHMARK) -->
        <div class="card border rounded-4 shadow-sm mb-4 overflow-hidden" id="section-benchmark">
            <div class="card-header bg-white p-3 p-md-3.5 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; border: 1px solid rgba(0, 102, 204, 0.2);">
                        <i class="fa-solid fa-chart-simple fs-6"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Biểu Đồ Đối Đầu Thông Số Cốt Lõi</h5>
                        <small class="text-muted">Tỉ lệ cân bằng giữa 2 thiết bị (Càng nghiêng về bên nào thì bên đó vượt trội hơn)</small>
                    </div>
                </div>
                <!-- Legend badges with colored dots -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1.5 fw-bold d-inline-flex align-items-center gap-1.5" style="font-size: 0.78rem;">
                        <span class="legend-color-dot bg-primary"></span>
                        <span class="text-truncate" style="max-width: 190px;"><?= htmlspecialchars($product1['name']) ?></span>
                    </span>
                    <span class="text-muted fw-bold small">vs</span>
                    <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1.5 fw-bold d-inline-flex align-items-center gap-1.5" style="font-size: 0.78rem;">
                        <span class="legend-color-dot bg-danger"></span>
                        <span class="text-truncate" style="max-width: 190px;"><?= htmlspecialchars($product2['name']) ?></span>
                    </span>
                </div>
            </div>

            <div class="card-body p-3 p-md-4 bg-white">
                <div class="d-flex flex-column gap-3">
                    <!-- Stat Bar: Pin -->
                    <div class="benchmark-row p-3 rounded-3 bg-light bg-opacity-50 border">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <span class="fw-bold text-dark d-flex align-items-center gap-2">
                                <span class="rounded-circle bg-white border shadow-2xs d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                    <i class="fa-solid fa-battery-full text-success fs-6"></i>
                                </span>
                                <span>Thời lượng & Dung lượng Pin</span>
                            </span>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="text-primary fw-extrabold"><?= $p1['batteryVal'] ?> mAh</span>
                                <span class="text-muted small">vs</span>
                                <span class="text-danger fw-extrabold"><?= $p2['batteryVal'] ?> mAh</span>
                                <span class="ms-md-2"><?= $batWinnerBadge ?></span>
                            </div>
                        </div>
                        <div class="stat-dual-progress">
                            <div class="stat-bar-left" style="width: <?= $p1BatPct ?>%;" title="<?= htmlspecialchars($product1['name']) ?>: <?= $p1['batteryVal'] ?> mAh (<?= $p1BatPct ?>%)">
                                <span><?= $p1BatPct ?>%</span>
                            </div>
                            <div class="stat-divider-center" title="Điểm cân bằng 50%"></div>
                            <div class="stat-bar-right" style="width: <?= $p2BatPct ?>%;" title="<?= htmlspecialchars($product2['name']) ?>: <?= $p2['batteryVal'] ?> mAh (<?= $p2BatPct ?>%)">
                                <span><?= $p2BatPct ?>%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stat Bar: RAM -->
                    <div class="benchmark-row p-3 rounded-3 bg-light bg-opacity-50 border">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <span class="fw-bold text-dark d-flex align-items-center gap-2">
                                <span class="rounded-circle bg-white border shadow-2xs d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                    <i class="fa-solid fa-memory text-info fs-6"></i>
                                </span>
                                <span>Bộ nhớ RAM (Đa nhiệm ứng dụng)</span>
                            </span>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="text-primary fw-extrabold"><?= $p1['ramVal'] ?> GB</span>
                                <span class="text-muted small">vs</span>
                                <span class="text-danger fw-extrabold"><?= $p2['ramVal'] ?> GB</span>
                                <span class="ms-md-2"><?= $ramWinnerBadge ?></span>
                            </div>
                        </div>
                        <div class="stat-dual-progress">
                            <div class="stat-bar-left" style="width: <?= $p1RamPct ?>%;" title="<?= htmlspecialchars($product1['name']) ?>: <?= $p1['ramVal'] ?> GB (<?= $p1RamPct ?>%)">
                                <span><?= $p1RamPct ?>%</span>
                            </div>
                            <div class="stat-divider-center" title="Điểm cân bằng 50%"></div>
                            <div class="stat-bar-right" style="width: <?= $p2RamPct ?>%;" title="<?= htmlspecialchars($product2['name']) ?>: <?= $p2['ramVal'] ?> GB (<?= $p2RamPct ?>%)">
                                <span><?= $p2RamPct ?>%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stat Bar: Tần số quét màn hình -->
                    <div class="benchmark-row p-3 rounded-3 bg-light bg-opacity-50 border">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <span class="fw-bold text-dark d-flex align-items-center gap-2">
                                <span class="rounded-circle bg-white border shadow-2xs d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                    <i class="fa-solid fa-mobile-screen text-warning fs-6"></i>
                                </span>
                                <span>Tần số quét màn hình</span>
                            </span>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="text-primary fw-extrabold"><?= $p1['screenHz'] ?> Hz</span>
                                <span class="text-muted small">vs</span>
                                <span class="text-danger fw-extrabold"><?= $p2['screenHz'] ?> Hz</span>
                                <span class="ms-md-2"><?= $hzWinnerBadge ?></span>
                            </div>
                        </div>
                        <div class="stat-dual-progress">
                            <div class="stat-bar-left" style="width: <?= $p1HzPct ?>%;" title="<?= htmlspecialchars($product1['name']) ?>: <?= $p1['screenHz'] ?> Hz (<?= $p1HzPct ?>%)">
                                <span><?= $p1HzPct ?>%</span>
                            </div>
                            <div class="stat-divider-center" title="Điểm cân bằng 50%"></div>
                            <div class="stat-bar-right" style="width: <?= $p2HzPct ?>%;" title="<?= htmlspecialchars($product2['name']) ?>: <?= $p2['screenHz'] ?> Hz (<?= $p2HzPct ?>%)">
                                <span><?= $p2HzPct ?>%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stat Bar: Giá trị & Độ tiết kiệm -->
                    <div class="benchmark-row p-3 rounded-3 bg-light bg-opacity-50 border">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <span class="fw-bold text-dark d-flex align-items-center gap-2">
                                <span class="rounded-circle bg-white border shadow-2xs d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                    <i class="fa-solid fa-piggy-bank text-warning fs-6"></i>
                                </span>
                                <span>Mức giá & Hiệu quả chi phí</span>
                            </span>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="text-primary fw-extrabold"><?= formatPrice($p1['effPrice']) ?></span>
                                <span class="text-muted small">vs</span>
                                <span class="text-danger fw-extrabold"><?= formatPrice($p2['effPrice']) ?></span>
                                <span class="ms-md-2"><?= $ecoWinnerBadge ?></span>
                            </div>
                        </div>
                        <div class="stat-dual-progress">
                            <div class="stat-bar-left" style="width: <?= $p1EcoScore ?>%;" title="<?= htmlspecialchars($product1['name']) ?>: <?= formatPrice($p1['effPrice']) ?> (<?= $p1EcoScore ?>%)">
                                <span><?= $p1EcoScore ?>%</span>
                            </div>
                            <div class="stat-divider-center" title="Điểm cân bằng 50%"></div>
                            <div class="stat-bar-right" style="width: <?= $p2EcoScore ?>%;" title="<?= htmlspecialchars($product2['name']) ?>: <?= formatPrice($p2['effPrice']) ?> (<?= $p2EcoScore ?>%)">
                                <span><?= $p2EcoScore ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KEY ADVANTAGES (ƯU ĐIỂM VƯỢT TRỘI TỪNG MÁY) -->
        <div class="row g-3 mb-4" id="section-pros">
            <div class="col-md-6">
                <div class="p-3.5 p-xl-4 rounded-4 bg-white border h-100 shadow-sm duel-pros-box border-start-primary">
                    <div class="d-flex align-items-center gap-2.5 mb-3 pb-2.5 border-bottom">
                        <div class="rounded-circle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; border: 1px solid rgba(0, 102, 204, 0.25); background: #f0f7ff;">
                            <i class="fa-solid fa-thumbs-up fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Lý do chọn <?= htmlspecialchars($product1['name']) ?></h6>
                            <small class="text-muted">Những điểm mạnh nổi trội và khác biệt</small>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2.5 small">
                        <?php foreach ($p1Pros as $pro): ?>
                            <li class="d-flex align-items-start gap-2">
                                <i class="fa-solid fa-circle-check text-primary mt-1 flex-shrink-0"></i>
                                <span class="text-dark fw-medium"><?= htmlspecialchars($pro) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3.5 p-xl-4 rounded-4 bg-white border h-100 shadow-sm duel-pros-box border-start-danger">
                    <div class="d-flex align-items-center gap-2.5 mb-3 pb-2.5 border-bottom">
                        <div class="rounded-circle text-danger d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; border: 1px solid rgba(239, 68, 68, 0.25); background: #fef2f2;">
                            <i class="fa-solid fa-thumbs-up fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Lý do chọn <?= htmlspecialchars($product2['name']) ?></h6>
                            <small class="text-muted">Những điểm mạnh nổi trội và khác biệt</small>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2.5 small">
                        <?php foreach ($p2Pros as $pro): ?>
                            <li class="d-flex align-items-start gap-2">
                                <i class="fa-solid fa-circle-check text-danger mt-1 flex-shrink-0"></i>
                                <span class="text-dark fw-medium"><?= htmlspecialchars($pro) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- QUICK JUMP PILLS BAR -->
        <div class="quick-jump-nav bg-white rounded-pill p-1.5 border shadow-sm mb-4 d-flex align-items-center gap-1.5 overflow-x-auto" style="scrollbar-width: none;">
            <span class="small fw-bold text-muted px-2 d-none d-md-inline-block"><i class="fa-solid fa-compass me-1 text-primary"></i>Nhảy nhanh:</span>
            <a href="#section-price" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-semibold text-dark">Giá bán</a>
            <a href="#section-screen" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-semibold text-dark">Màn hình</a>
            <a href="#section-performance" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-semibold text-dark">Cấu hình & CPU</a>
            <a href="#section-battery" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-semibold text-dark">Pin & Sạc</a>
            <a href="#section-warranty" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fw-semibold text-dark">Hậu mãi V-Phone</a>
            
            <div class="ms-auto d-flex align-items-center pe-1">
                <!-- Toggle differences switch -->
                <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                    <input class="form-check-input mt-0" type="checkbox" id="switchDiffOnly" onchange="handleDiffToggle(this.checked)">
                    <label class="form-check-label small fw-bold text-secondary text-nowrap" for="switchDiffOnly">Chỉ xem điểm khác biệt</label>
                </div>
            </div>
        </div>

        <!-- SPECIFICATION MATRIX TABLE -->
        <div class="card border rounded-4 shadow-sm overflow-hidden mb-5" id="specMatrixCard">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 compare-matrix-table" id="compareSpecsTable">
                    <thead>
                        <tr class="table-light">
                            <th style="width: 26%;" class="ps-4 py-3 text-secondary text-uppercase small fw-bold">Thông số / Tính năng</th>
                            <th style="width: <?= $hasP3 ? '24%' : '37%' ?>;" class="py-3 text-primary fw-bold text-center border-start">
                                <?= htmlspecialchars($product1['name']) ?>
                            </th>
                            <th style="width: <?= $hasP3 ? '24%' : '37%' ?>;" class="py-3 text-danger fw-bold text-center border-start">
                                <?= htmlspecialchars($product2['name']) ?>
                            </th>
                            <?php if ($hasP3): ?>
                                <th style="width: 24%;" class="py-3 text-success fw-bold text-center border-start">
                                    <?= htmlspecialchars($product3['name']) ?>
                                </th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- NHÓM 1: GIÁ BÁN & TÌNH TRẠNG -->
                        <tr class="group-header-row" id="section-price">
                            <td colspan="<?= $hasP3 ? 4 : 3 ?>" class="ps-4 py-2.5 fw-bold text-uppercase text-dark small">
                                <i class="fa-solid fa-tag me-2 text-primary"></i>1. Giá Bán & Tình Trạng Kho Hàng
                            </td>
                        </tr>
                        <tr class="spec-row" data-is-diff="<?= $p1['effPrice'] !== $p2['effPrice'] ? '1' : '0' ?>">
                            <td class="ps-4 text-secondary fw-semibold">Giá khuyến mãi tại V-Phone:</td>
                            <td class="text-center fw-bold border-start <?= $p1['effPrice'] < $p2['effPrice'] ? 'spec-winner-cell' : '' ?>">
                                <span class="fs-5 text-danger"><?= formatPrice($p1['effPrice']) ?></span>
                                <?php if ($p1['effPrice'] < $p2['effPrice']): ?>
                                    <span class="badge bg-success rounded-pill ms-1 small"><i class="fa-solid fa-check me-0.5"></i>Tốt hơn</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold border-start <?= $p2['effPrice'] < $p1['effPrice'] ? 'spec-winner-cell' : '' ?>">
                                <span class="fs-5 text-danger"><?= formatPrice($p2['effPrice']) ?></span>
                                <?php if ($p2['effPrice'] < $p1['effPrice']): ?>
                                    <span class="badge bg-success rounded-pill ms-1 small"><i class="fa-solid fa-check me-0.5"></i>Tốt hơn</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($hasP3): ?>
                                <td class="text-center fw-bold border-start">
                                    <span class="fs-5 text-danger"><?= formatPrice($p3['effPrice']) ?></span>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <tr class="spec-row" data-is-diff="<?= $p1['oldPrice'] !== $p2['oldPrice'] ? '1' : '0' ?>">
                            <td class="ps-4 text-secondary fw-semibold">Giá gốc niêm yết:</td>
                            <td class="text-center text-muted border-start text-decoration-line-through"><?= formatPrice($p1['oldPrice']) ?></td>
                            <td class="text-center text-muted border-start text-decoration-line-through"><?= formatPrice($p2['oldPrice']) ?></td>
                            <?php if ($hasP3): ?>
                                <td class="text-center text-muted border-start text-decoration-line-through"><?= formatPrice($p3['oldPrice']) ?></td>
                            <?php endif; ?>
                        </tr>
                        <tr class="spec-row" data-is-diff="<?= $p1['saving'] !== $p2['saving'] ? '1' : '0' ?>">
                            <td class="ps-4 text-secondary fw-semibold">Mức tiết kiệm được:</td>
                            <td class="text-center text-success fw-bold border-start"><?= $p1['saving'] > 0 ? formatPrice($p1['saving']) : '0 VNĐ' ?></td>
                            <td class="text-center text-success fw-bold border-start"><?= $p2['saving'] > 0 ? formatPrice($p2['saving']) : '0 VNĐ' ?></td>
                            <?php if ($hasP3): ?>
                                <td class="text-center text-success fw-bold border-start"><?= $p3['saving'] > 0 ? formatPrice($p3['saving']) : '0 VNĐ' ?></td>
                            <?php endif; ?>
                        </tr>
                        <tr class="spec-row" data-is-diff="<?= ($product1['condition_desc'] ?? '') !== ($product2['condition_desc'] ?? '') ? '1' : '0' ?>">
                            <td class="ps-4 text-secondary fw-semibold">Tình trạng máy:</td>
                            <td class="text-center border-start">
                                <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill fw-semibold">
                                    <?= htmlspecialchars($product1['condition_desc'] ?? 'Mới 100%') ?>
                                </span>
                            </td>
                            <td class="text-center border-start">
                                <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill fw-semibold">
                                    <?= htmlspecialchars($product2['condition_desc'] ?? 'Mới 100%') ?>
                                </span>
                            </td>
                            <?php if ($hasP3): ?>
                                <td class="text-center border-start">
                                    <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill fw-semibold">
                                        <?= htmlspecialchars($product3['condition_desc'] ?? 'Mới 100%') ?>
                                    </span>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <tr class="spec-row" data-is-diff="1">
                            <td class="ps-4 text-secondary fw-semibold">Màu sắc có sẵn:</td>
                            <td class="text-center border-start">
                                <div class="d-flex justify-content-center gap-1.5 flex-wrap">
                                    <?php foreach ($p1['colors'] as $c): ?>
                                        <span class="badge bg-white text-dark border px-2 py-1 small rounded-pill">
                                            <span style="width: 7px; height: 7px; border-radius: 50%; background: <?= getColorHexValue($c) ?>; display: inline-block; margin-right: 4px; vertical-align: middle;"></span>
                                            <?= htmlspecialchars($c) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="text-center border-start">
                                <div class="d-flex justify-content-center gap-1.5 flex-wrap">
                                    <?php foreach ($p2['colors'] as $c): ?>
                                        <span class="badge bg-white text-dark border px-2 py-1 small rounded-pill">
                                            <span style="width: 7px; height: 7px; border-radius: 50%; background: <?= getColorHexValue($c) ?>; display: inline-block; margin-right: 4px; vertical-align: middle;"></span>
                                            <?= htmlspecialchars($c) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <?php if ($hasP3): ?>
                                <td class="text-center border-start">
                                    <div class="d-flex justify-content-center gap-1.5 flex-wrap">
                                        <?php foreach ($p3['colors'] as $c): ?>
                                            <span class="badge bg-white text-dark border px-2 py-1 small rounded-pill">
                                                <span style="width: 7px; height: 7px; border-radius: 50%; background: <?= getColorHexValue($c) ?>; display: inline-block; margin-right: 4px; vertical-align: middle;"></span>
                                                <?= htmlspecialchars($c) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <tr class="spec-row" data-is-diff="1">
                            <td class="ps-4 text-secondary fw-semibold">Tùy chọn dung lượng:</td>
                            <td class="text-center border-start">
                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                    <?php foreach ($p1['storageList'] as $st): ?>
                                        <span class="badge bg-light text-primary border px-2 py-1 small"><?= htmlspecialchars($st['rom']) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="text-center border-start">
                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                    <?php foreach ($p2['storageList'] as $st): ?>
                                        <span class="badge bg-light text-danger border px-2 py-1 small"><?= htmlspecialchars($st['rom']) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <?php if ($hasP3): ?>
                                <td class="text-center border-start">
                                    <div class="d-flex justify-content-center gap-1 flex-wrap">
                                        <?php foreach ($p3['storageList'] as $st): ?>
                                            <span class="badge bg-light text-success border px-2 py-1 small"><?= htmlspecialchars($st['rom']) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>

                        <!-- NHÓM 2: MÀN HÌNH & HIỂN THỊ -->
                        <tr class="group-header-row" id="section-screen">
                            <td colspan="<?= $hasP3 ? 4 : 3 ?>" class="ps-4 py-2.5 fw-bold text-uppercase text-dark small">
                                <i class="fa-solid fa-mobile-screen me-2 text-primary"></i>2. Màn Hình & Trải Nghiệm Thị Giác
                            </td>
                        </tr>
                        <tr class="spec-row" data-is-diff="<?= ($product1['screen'] ?? '') !== ($product2['screen'] ?? '') ? '1' : '0' ?>">
                            <td class="ps-4 text-secondary fw-semibold">Kích thước & Công nghệ:</td>
                            <td class="text-center fw-bold border-start <?= $p1['screenSize'] > $p2['screenSize'] ? 'spec-winner-cell' : '' ?>">
                                <?= htmlspecialchars($product1['screen'] ?? '6.7 inch AMOLED') ?>
                                <?php if ($p1['screenSize'] > $p2['screenSize']): ?>
                                    <span class="badge bg-success rounded-pill ms-1 small">Lớn hơn</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold border-start <?= $p2['screenSize'] > $p1['screenSize'] ? 'spec-winner-cell' : '' ?>">
                                <?= htmlspecialchars($product2['screen'] ?? '6.7 inch AMOLED') ?>
                                <?php if ($p2['screenSize'] > $p1['screenSize']): ?>
                                    <span class="badge bg-success rounded-pill ms-1 small">Lớn hơn</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($hasP3): ?>
                                <td class="text-center fw-bold border-start">
                                    <?= htmlspecialchars($product3['screen'] ?? '6.7 inch AMOLED') ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <tr class="spec-row" data-is-diff="<?= $p1['screenHz'] !== $p2['screenHz'] ? '1' : '0' ?>">
                            <td class="ps-4 text-secondary fw-semibold">Tần số làm tươi:</td>
                            <td class="text-center fw-bold border-start <?= $p1['screenHz'] > $p2['screenHz'] ? 'spec-winner-cell' : '' ?>">
                                <?= $p1['screenHz'] ?> Hz
                                <?php if ($p1['screenHz'] > $p2['screenHz']): ?>
                                    <span class="badge bg-success rounded-pill ms-1 small">+<?= $p1['screenHz'] - $p2['screenHz'] ?> Hz Mượt hơn</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold border-start <?= $p2['screenHz'] > $p1['screenHz'] ? 'spec-winner-cell' : '' ?>">
                                <?= $p2['screenHz'] ?> Hz
                                <?php if ($p2['screenHz'] > $p1['screenHz']): ?>
                                    <span class="badge bg-success rounded-pill ms-1 small">+<?= $p2['screenHz'] - $p1['screenHz'] ?> Hz Mượt hơn</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($hasP3): ?>
                                <td class="text-center fw-bold border-start">
                                    <?= $p3['screenHz'] ?> Hz
                                </td>
                            <?php endif; ?>
                        </tr>

                        <!-- NHÓM 3: VI XỬ LÝ & BỘ NHỚ -->
                        <tr class="group-header-row" id="section-performance">
                            <td colspan="<?= $hasP3 ? 4 : 3 ?>" class="ps-4 py-2.5 fw-bold text-uppercase text-dark small">
                                <i class="fa-solid fa-microchip me-2 text-primary"></i>3. Cấu Hình, Chipset & Hiệu Năng
                            </td>
                        </tr>
                        <tr class="spec-row" data-is-diff="<?= ($product1['cpu'] ?? '') !== ($product2['cpu'] ?? '') ? '1' : '0' ?>">
                            <td class="ps-4 text-secondary fw-semibold">Chipset CPU:</td>
                            <td class="text-center fw-bold text-dark border-start"><?= htmlspecialchars($product1['cpu'] ?? 'Cao cấp') ?></td>
                            <td class="text-center fw-bold text-dark border-start"><?= htmlspecialchars($product2['cpu'] ?? 'Cao cấp') ?></td>
                            <?php if ($hasP3): ?>
                                <td class="text-center fw-bold text-dark border-start"><?= htmlspecialchars($product3['cpu'] ?? 'Cao cấp') ?></td>
                            <?php endif; ?>
                        </tr>
                        <tr class="spec-row" data-is-diff="<?= $p1['ramVal'] !== $p2['ramVal'] ? '1' : '0' ?>">
                            <td class="ps-4 text-secondary fw-semibold">Dung lượng RAM:</td>
                            <td class="text-center fw-bold border-start <?= $p1['ramVal'] > $p2['ramVal'] ? 'spec-winner-cell' : '' ?>">
                                <?= htmlspecialchars($product1['ram'] ?? '8 GB') ?>
                                <?php if ($p1['ramVal'] > $p2['ramVal']): ?>
                                    <span class="badge bg-success rounded-pill ms-1 small">+<?= $p1['ramVal'] - $p2['ramVal'] ?> GB</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold border-start <?= $p2['ramVal'] > $p1['ramVal'] ? 'spec-winner-cell' : '' ?>">
                                <?= htmlspecialchars($product2['ram'] ?? '8 GB') ?>
                                <?php if ($p2['ramVal'] > $p1['ramVal']): ?>
                                    <span class="badge bg-success rounded-pill ms-1 small">+<?= $p2['ramVal'] - $p1['ramVal'] ?> GB</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($hasP3): ?>
                                <td class="text-center fw-bold border-start">
                                    <?= htmlspecialchars($product3['ram'] ?? '8 GB') ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <tr class="spec-row" data-is-diff="<?= ($product1['rom'] ?? '') !== ($product2['rom'] ?? '') ? '1' : '0' ?>">
                            <td class="ps-4 text-secondary fw-semibold">Bộ nhớ ROM tiêu chuẩn:</td>
                            <td class="text-center fw-bold text-dark border-start"><?= htmlspecialchars($product1['rom'] ?? '256 GB') ?></td>
                            <td class="text-center fw-bold text-dark border-start"><?= htmlspecialchars($product2['rom'] ?? '256 GB') ?></td>
                            <?php if ($hasP3): ?>
                                <td class="text-center fw-bold text-dark border-start"><?= htmlspecialchars($product3['rom'] ?? '256 GB') ?></td>
                            <?php endif; ?>
                        </tr>

                        <!-- NHÓM 4: PIN & SẠC -->
                        <tr class="group-header-row" id="section-battery">
                            <td colspan="<?= $hasP3 ? 4 : 3 ?>" class="ps-4 py-2.5 fw-bold text-uppercase text-dark small">
                                <i class="fa-solid fa-battery-full me-2 text-primary"></i>4. Pin & Nguồn Năng Lượng
                            </td>
                        </tr>
                        <tr class="spec-row" data-is-diff="<?= ($product1['battery'] ?? '') !== ($product2['battery'] ?? '') ? '1' : '0' ?>">
                            <td class="ps-4 text-secondary fw-semibold">Dung lượng Pin & Sạc:</td>
                            <td class="text-center fw-bold border-start <?= $p1['batteryVal'] > $p2['batteryVal'] ? 'spec-winner-cell' : '' ?>">
                                <?= htmlspecialchars($product1['battery'] ?? '5000 mAh') ?>
                                <?php if ($p1['batteryVal'] > $p2['batteryVal']): ?>
                                    <span class="badge bg-success rounded-pill ms-1 small">+<?= $p1['batteryVal'] - $p2['batteryVal'] ?> mAh Trâu hơn</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold border-start <?= $p2['batteryVal'] > $p1['batteryVal'] ? 'spec-winner-cell' : '' ?>">
                                <?= htmlspecialchars($product2['battery'] ?? '5000 mAh') ?>
                                <?php if ($p2['batteryVal'] > $p1['batteryVal']): ?>
                                    <span class="badge bg-success rounded-pill ms-1 small">+<?= $p2['batteryVal'] - $p1['batteryVal'] ?> mAh Trâu hơn</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($hasP3): ?>
                                <td class="text-center fw-bold border-start">
                                    <?= htmlspecialchars($product3['battery'] ?? '5000 mAh') ?>
                                </td>
                            <?php endif; ?>
                        </tr>

                        <!-- NHÓM 5: ĐÁNH GIÁ & HẬU MÃI -->
                        <tr class="group-header-row" id="section-warranty">
                            <td colspan="<?= $hasP3 ? 4 : 3 ?>" class="ps-4 py-2.5 fw-bold text-uppercase text-dark small">
                                <i class="fa-solid fa-shield-halved me-2 text-primary"></i>5. Đánh Giá & Hậu Mãi Độc Quyền V-Phone
                            </td>
                        </tr>
                        <tr class="spec-row" data-is-diff="<?= $p1['ratingVal'] !== $p2['ratingVal'] ? '1' : '0' ?>">
                            <td class="ps-4 text-secondary fw-semibold">Đánh giá thực tế từ khách:</td>
                            <td class="text-center border-start">
                                <?= renderStarRating($p1['ratingVal'], 'small', true) ?>
                                <div class="small text-muted mt-0.5">(<?= $p1['reviewCount'] ?> nhận xét)</div>
                            </td>
                            <td class="text-center border-start">
                                <?= renderStarRating($p2['ratingVal'], 'small', true) ?>
                                <div class="small text-muted mt-0.5">(<?= $p2['reviewCount'] ?> nhận xét)</div>
                            </td>
                            <?php if ($hasP3): ?>
                                <td class="text-center border-start">
                                    <?= renderStarRating($p3['ratingVal'], 'small', true) ?>
                                    <div class="small text-muted mt-0.5">(<?= $p3['reviewCount'] ?> nhận xét)</div>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <tr class="spec-row" data-is-diff="0">
                            <td class="ps-4 text-secondary fw-semibold">Chính sách đổi trả:</td>
                            <td class="text-center border-start text-success fw-semibold"><i class="fa-solid fa-rotate-left me-1"></i>Lỗi là đổi 1 - 1 trong 30 ngày</td>
                            <td class="text-center border-start text-success fw-semibold"><i class="fa-solid fa-rotate-left me-1"></i>Lỗi là đổi 1 - 1 trong 30 ngày</td>
                            <?php if ($hasP3): ?>
                                <td class="text-center border-start text-success fw-semibold"><i class="fa-solid fa-rotate-left me-1"></i>Lỗi là đổi 1 - 1 trong 30 ngày</td>
                            <?php endif; ?>
                        </tr>
                        <tr class="spec-row" data-is-diff="0">
                            <td class="ps-4 text-secondary fw-semibold">Bảo hành chính hãng:</td>
                            <td class="text-center border-start text-primary fw-semibold"><i class="fa-solid fa-shield-halved me-1"></i>12 tháng toàn diện</td>
                            <td class="text-center border-start text-primary fw-semibold"><i class="fa-solid fa-shield-halved me-1"></i>12 tháng toàn diện</td>
                            <?php if ($hasP3): ?>
                                <td class="text-center border-start text-primary fw-semibold"><i class="fa-solid fa-shield-halved me-1"></i>12 tháng toàn diện</td>
                            <?php endif; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- STICKY BOTTOM / TOP FLOATING HEADER ON SCROLL -->
<div id="stickyCompareBar" class="sticky-compare-bar d-none">
    <div class="container d-flex align-items-center justify-content-between py-2 px-3">
        <!-- Machine 1 Mini -->
        <div class="d-flex align-items-center gap-2 min-w-0" style="flex: 1;">
            <img src="<?= htmlspecialchars($product1['image']) ?>" alt="<?= htmlspecialchars($product1['name']) ?>" class="sticky-mini-thumb rounded-2 border" style="width: 38px; height: 38px; object-fit: contain; background: rgba(125,125,125,0.06); flex-shrink: 0;">
            <div class="min-w-0">
                <div class="fw-bold text-dark text-truncate small"><?= htmlspecialchars($product1['name']) ?></div>
                <div class="text-danger fw-bold small"><?= formatPrice($p1['effPrice']) ?></div>
            </div>
            <?php if ((int)$product1['quantity'] > 0): ?>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-bold ms-auto d-none d-lg-inline-block" onclick="addToCartDirect(this, <?= $product1['id'] ?>, '<?= htmlspecialchars(addslashes($product1['name'])) ?>')">
                    Thêm giỏ
                </button>
            <?php endif; ?>
        </div>

        <div class="px-3 text-center flex-shrink-0">
            <span class="badge rounded-pill bg-dark px-2.5 py-1 fw-bold">VS</span>
        </div>

        <!-- Machine 2 Mini -->
        <div class="d-flex align-items-center gap-2 min-w-0" style="flex: 1;">
            <img src="<?= htmlspecialchars($product2['image']) ?>" alt="<?= htmlspecialchars($product2['name']) ?>" class="sticky-mini-thumb rounded-2 border" style="width: 38px; height: 38px; object-fit: contain; background: rgba(125,125,125,0.06); flex-shrink: 0;">
            <div class="min-w-0">
                <div class="fw-bold text-dark text-truncate small"><?= htmlspecialchars($product2['name']) ?></div>
                <div class="text-danger fw-bold small"><?= formatPrice($p2['effPrice']) ?></div>
            </div>
            <?php if ((int)$product2['quantity'] > 0): ?>
                <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 py-1 fw-bold ms-auto d-none d-lg-inline-block" onclick="addToCartDirect(this, <?= $product2['id'] ?>, '<?= htmlspecialchars(addslashes($product2['name'])) ?>')">
                    Thêm giỏ
                </button>
            <?php endif; ?>
        </div>

        <?php if ($hasP3): ?>
            <div class="px-3 text-center flex-shrink-0 d-none d-md-block">
                <span class="badge rounded-pill bg-dark px-2.5 py-1 fw-bold">VS</span>
            </div>
            <!-- Machine 3 Mini -->
            <div class="d-flex align-items-center gap-2 min-w-0 d-none d-md-flex" style="flex: 1;">
                <img src="<?= htmlspecialchars($product3['image']) ?>" alt="<?= htmlspecialchars($product3['name']) ?>" class="sticky-mini-thumb rounded-2 border" style="width: 38px; height: 38px; object-fit: contain; background: rgba(125,125,125,0.06); flex-shrink: 0;">
                <div class="min-w-0">
                    <div class="fw-bold text-dark text-truncate small"><?= htmlspecialchars($product3['name']) ?></div>
                    <div class="text-danger fw-bold small"><?= formatPrice($p3['effPrice']) ?></div>
                </div>
                <?php if ((int)$product3['quantity'] > 0): ?>
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3 py-1 fw-bold ms-auto d-none d-lg-inline-block" onclick="addToCartDirect(this, <?= $product3['id'] ?>, '<?= htmlspecialchars(addslashes($product3['name'])) ?>')">
                        Thêm giỏ
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL CHỌN SẢN PHẨM THAY THẾ -->
<div class="modal fade" id="productSelectModal" tabindex="-1" aria-labelledby="productSelectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-mobile-screen fs-6"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="productSelectModalLabel">Chọn điện thoại để so sánh</h5>
                        <small class="text-muted" id="modalTargetSlotText">Thay thế cho vị trí Máy 1</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3 p-md-4">
                <!-- Search bar & Filter -->
                <div class="mb-3">
                    <div class="input-group bg-light rounded-pill p-1 border">
                        <span class="input-group-text bg-transparent border-0 ps-3 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="modalSearchInput" class="form-control border-0 bg-transparent ps-2 small shadow-none" placeholder="Tìm theo tên máy (iPhone, Samsung Galaxy, Xiaomi...)" onkeyup="filterModalProducts()">
                        <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 text-secondary" onclick="document.getElementById('modalSearchInput').value=''; filterModalProducts();">Xóa</button>
                    </div>
                </div>

                <!-- Brand Filter Tabs -->
                <div class="d-flex gap-1.5 overflow-x-auto pb-2 mb-3 brand-modal-scroll" style="scrollbar-width: none;">
                    <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-bold brand-filter-btn active btn-primary" data-brand="all" onclick="selectModalBrand('all', this)">
                        Tất cả
                    </button>
                    <?php foreach ($brands as $b): ?>
                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-semibold brand-filter-btn btn-outline-secondary" data-brand="<?= (int)$b['id'] ?>" onclick="selectModalBrand('<?= (int)$b['id'] ?>', this)">
                            <?= htmlspecialchars($b['name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Product Grid List -->
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-2.5" id="modalProductList">
                    <?php foreach ($allProducts as $ap): ?>
                        <?php 
                            $apPrice = ($ap['sale_price'] > 0 && $ap['sale_price'] < $ap['price']) ? (int)$ap['sale_price'] : (int)$ap['price'];
                            $isCurSelected = ($ap['id'] == $product1['id'] || $ap['id'] == $product2['id'] || ($hasP3 && $ap['id'] == $product3['id']));
                        ?>
                        <div class="col modal-prod-item" 
                             data-id="<?= (int)$ap['id'] ?>" 
                             data-name="<?= htmlspecialchars(mb_strtolower($ap['name'])) ?>" 
                             data-brand-id="<?= (int)$ap['brand_id'] ?>">
                            <div class="p-2.5 rounded-3 border bg-white h-100 d-flex flex-column justify-content-between hover-card position-relative <?= $isCurSelected ? 'border-primary bg-primary bg-opacity-10' : '' ?>">
                                <div class="d-flex gap-2.5 align-items-center mb-2">
                                    <img src="<?= htmlspecialchars($ap['image']) ?>" alt="<?= htmlspecialchars($ap['name']) ?>" class="rounded-2 border flex-shrink-0" style="width: 50px; height: 50px; object-fit: contain; background: rgba(125,125,125,0.06);">
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-bold text-dark text-truncate small" title="<?= htmlspecialchars($ap['name']) ?>"><?= htmlspecialchars($ap['name']) ?></div>
                                        <div class="text-danger fw-bold small"><?= formatPrice($apPrice) ?></div>
                                        <div class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($ap['ram'] ?? '') ?> • <?= htmlspecialchars($ap['rom'] ?? '') ?></div>
                                    </div>
                                </div>
                                <button type="button" 
                                        class="btn btn-sm <?= $isCurSelected ? 'btn-secondary disabled' : 'btn-outline-primary' ?> rounded-pill w-100 fw-semibold py-1 small" 
                                        onclick="applyProductSelection(<?= (int)$ap['id'] ?>)">
                                    <?= $isCurSelected ? 'Đang so sánh' : 'Chọn so sánh' ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="modalEmptyNotice" class="text-center py-4 d-none">
                    <i class="fa-solid fa-magnifying-glass fa-2x text-muted mb-2 opacity-50"></i>
                    <p class="text-secondary small mb-0">Không tìm thấy sản phẩm nào khớp với tìm kiếm.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.COMPARE_STATE = {
    slotTarget: 1,
    p1Id: <?= (int)$product1['id'] ?>,
    p2Id: <?= (int)$product2['id'] ?>,
    p3Id: <?= $hasP3 ? (int)$product3['id'] : 0 ?>,
    activeBrand: 'all'
};

// Đổi màu máy mượt mà không bị giật / nháy khung ảnh
function changeDuelImage(slot, imgSrc, btn) {
    const imgEl = document.getElementById('duelImg' + slot);
    if (imgEl && imgSrc) {
        imgEl.style.transition = 'opacity 0.22s cubic-bezier(0.16, 1, 0.3, 1), transform 0.22s cubic-bezier(0.16, 1, 0.3, 1)';
        imgEl.style.opacity = '0.35';
        imgEl.style.transform = 'scale(0.97)';
        const preloader = new Image();
        preloader.onload = function() {
            imgEl.src = imgSrc;
            imgEl.style.opacity = '1';
            imgEl.style.transform = 'scale(1)';
        };
        preloader.src = imgSrc;
    }
    const container = btn.closest('.duel-card-modern');
    if (container) {
        container.querySelectorAll('.color-swatch-dot').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');
    }
}

function openProductSelector(slot) {
    window.COMPARE_STATE.slotTarget = slot;
    const labelEl = document.getElementById('modalTargetSlotText');
    if (labelEl) {
        if (slot === 1) {
            labelEl.textContent = 'Thay thế cho vị trí Máy 1 (<?= htmlspecialchars(addslashes($product1['name'])) ?>)';
        } else if (slot === 2) {
            labelEl.textContent = 'Thay thế cho vị trí Máy 2 (<?= htmlspecialchars(addslashes($product2['name'])) ?>)';
        } else {
            labelEl.textContent = 'Chọn sản phẩm cho vị trí Máy thứ 3';
        }
    }
    const modalEl = document.getElementById('productSelectModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        setTimeout(() => {
            const inp = document.getElementById('modalSearchInput');
            if (inp) inp.focus();
        }, 300);
    }
}

function selectModalBrand(brandId, btn) {
    window.COMPARE_STATE.activeBrand = brandId;
    document.querySelectorAll('.brand-filter-btn').forEach(b => {
        b.classList.remove('active', 'btn-primary');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.add('active', 'btn-primary');
    btn.classList.remove('btn-outline-secondary');
    filterModalProducts();
}

function filterModalProducts() {
    const kw = (document.getElementById('modalSearchInput')?.value || '').trim().toLowerCase();
    const brand = window.COMPARE_STATE.activeBrand;
    const items = document.querySelectorAll('.modal-prod-item');
    let visibleCount = 0;

    items.forEach(item => {
        const name = item.getAttribute('data-name') || '';
        const bId = item.getAttribute('data-brand-id') || '';
        const matchKw = !kw || name.includes(kw);
        const matchBrand = (brand === 'all') || (bId === brand);

        if (matchKw && matchBrand) {
            item.classList.remove('d-none');
            visibleCount++;
        } else {
            item.classList.add('d-none');
        }
    });

    const emptyNotice = document.getElementById('modalEmptyNotice');
    if (emptyNotice) {
        if (visibleCount === 0) {
            emptyNotice.classList.remove('d-none');
        } else {
            emptyNotice.classList.add('d-none');
        }
    }
}

function applyProductSelection(newId) {
    let p1 = window.COMPARE_STATE.p1Id;
    let p2 = window.COMPARE_STATE.p2Id;
    let p3 = window.COMPARE_STATE.p3Id;

    if (window.COMPARE_STATE.slotTarget === 1) {
        p1 = newId;
    } else if (window.COMPARE_STATE.slotTarget === 2) {
        p2 = newId;
    } else {
        p3 = newId;
    }

    let url = `index.php?page=compare&p1=${p1}&p2=${p2}`;
    if (p3 > 0 && p3 !== p1 && p3 !== p2) {
        url += `&p3=${p3}`;
    }
    window.location.href = url;
}

// Bật/tắt điểm khác biệt mượt mà không làm giật chiều cao trang
function handleDiffToggle(isDiffOnly) {
    const rows = document.querySelectorAll('.spec-row');
    rows.forEach(r => {
        const isDiff = r.getAttribute('data-is-diff') === '1';
        if (isDiffOnly) {
            if (!isDiff) {
                r.style.opacity = '0';
                r.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    if (document.getElementById('switchDiffOnly')?.checked) {
                        r.classList.add('d-none');
                    }
                }, 180);
            } else {
                r.classList.remove('d-none');
                r.style.opacity = '1';
                r.style.transform = 'scale(1)';
            }
        } else {
            r.classList.remove('d-none');
            setTimeout(() => {
                r.style.opacity = '1';
                r.style.transform = 'scale(1)';
            }, 10);
        }
    });
}

function copyCompareLink() {
    const url = window.location.href;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            if (typeof window.showVPhoneToast === 'function') {
                window.showVPhoneToast('Đã sao chép link so sánh vào bộ nhớ tạm!', true);
            } else {
                alert('Đã chép liên kết so sánh: ' + url);
            }
        });
    } else {
        alert('Liên kết so sánh: ' + url);
    }
}

// Sticky header show / hide on scroll (chỉ hiện khi cuộn qua khu vực thẻ đối đầu duel arena)
window.addEventListener('scroll', function() {
    const stickyBar = document.getElementById('stickyCompareBar');
    const duelArena = document.getElementById('section-duel-arena');
    if (!stickyBar || !duelArena) return;

    const rect = duelArena.getBoundingClientRect();
    if (rect.bottom < 60) {
        stickyBar.classList.remove('d-none');
        stickyBar.classList.add('show');
    } else {
        stickyBar.classList.remove('show');
        stickyBar.classList.add('d-none');
    }
}, { passive: true });

// Cuộn mượt mà khi bấm các tab Nhảy Nhanh (Smooth Scroll with Offset)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.quick-jump-nav a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                const headerOffset = 135;
                const bodyRect = document.body.getBoundingClientRect().top;
                const elementRect = targetEl.getBoundingClientRect().top;
                const elementPosition = elementRect - bodyRect;
                const offsetPosition = elementPosition - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });

                document.querySelectorAll('.quick-jump-nav a').forEach(a => {
                    a.classList.remove('btn-primary', 'text-white', 'shadow-sm');
                    a.classList.add('btn-light', 'text-dark');
                });
                this.classList.remove('btn-light', 'text-dark');
                this.classList.add('btn-primary', 'text-white', 'shadow-sm');
            }
        });
    });

    // Scrollspy đồng bộ thanh Nhảy Nhanh theo vị trí cuộn
    const jumpSectionIds = ['section-price', 'section-screen', 'section-performance', 'section-battery', 'section-warranty'];
    window.addEventListener('scroll', function() {
        let currentId = '';
        const scrollPosition = window.pageYOffset + 155;
        jumpSectionIds.forEach(id => {
            const el = document.getElementById(id);
            if (el && el.offsetTop <= scrollPosition) {
                currentId = id;
            }
        });
        if (currentId) {
            document.querySelectorAll('.quick-jump-nav a').forEach(a => {
                if (a.getAttribute('href') === '#' + currentId) {
                    a.classList.remove('btn-light', 'text-dark');
                    a.classList.add('btn-primary', 'text-white', 'shadow-sm');
                } else {
                    a.classList.remove('btn-primary', 'text-white', 'shadow-sm');
                    a.classList.add('btn-light', 'text-dark');
                }
            });
        }
    }, { passive: true });

    // Hiệu ứng thanh đo trượt mượt mà khi vừa vào trang
    const progressBars = document.querySelectorAll('.stat-dual-progress .stat-bar-left, .stat-dual-progress .stat-bar-right');
    progressBars.forEach(bar => {
        const targetW = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = targetW;
        }, 120);
    });
});
</script>

<?php require_once 'app/views/includes/footer.php'; ?>
