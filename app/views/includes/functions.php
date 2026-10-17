<?php
function formatPrice(float|int|string $amount): string {
    return number_format((float)$amount, 0, ',', '.') . ' đ';
}

function romToGigabytes(string $romStr): int {
    $r = mb_strtolower(trim($romStr), 'UTF-8');
    if (strpos($r, 'tb') !== false) {
        preg_match('/([0-9]+(?:\.[0-9]+)?)/', $r, $m);
        $num = isset($m[1]) ? (float)$m[1] : 1.0;
        return (int)round($num * 1024);
    }
    preg_match('/([0-9]+)/', $r, $m);
    return isset($m[1]) ? (int)$m[1] : 0;
}

function getStorageTiers(string $prodName, string $defaultRom): array {
    $name = mb_strtolower($prodName, 'UTF-8');
    $rom = mb_strtolower($defaultRom, 'UTF-8');

    if (strpos($name, 'ultra') !== false || strpos($name, 'pro max') !== false || strpos($name, 'fold') !== false || strpos($name, 'tri-fold') !== false || strpos($name, 'duo') !== false) {
        return [
            ['rom' => '256 GB', 'extra' => 0, 'label' => '256 GB (Tiêu chuẩn)'],
            ['rom' => '512 GB', 'extra' => 4000000, 'label' => '512 GB (+4.0tr)'],
            ['rom' => '1 TB', 'extra' => 9000000, 'label' => '1 TB (+9.0tr)'],
            ['rom' => '2 TB', 'extra' => 16000000, 'label' => '2 TB (+16tr)']
        ];
    } elseif (strpos($rom, '64') !== false || strpos($name, '11') !== false || strpos($name, 'a05') !== false || strpos($name, '13c') !== false) {
        return [
            ['rom' => '64 GB', 'extra' => 0, 'label' => '64 GB (Tiết kiệm)'],
            ['rom' => '128 GB', 'extra' => 1200000, 'label' => '128 GB (+1.2tr)'],
            ['rom' => '256 GB', 'extra' => 2600000, 'label' => '256 GB (+2.6tr)'],
            ['rom' => '512 GB', 'extra' => 5000000, 'label' => '512 GB (+5.0tr)']
        ];
    } else {
        return [
            ['rom' => '128 GB', 'extra' => 0, 'label' => '128 GB (Tiêu chuẩn)'],
            ['rom' => '256 GB', 'extra' => 2500000, 'label' => '256 GB (+2.5tr)'],
            ['rom' => '512 GB', 'extra' => 5500000, 'label' => '512 GB (+5.5tr)'],
            ['rom' => '1 TB', 'extra' => 10000000, 'label' => '1 TB (+10tr)']
        ];
    }
}

function getColorHexValue(string $name): string {
    $c = mb_strtolower(trim($name), 'UTF-8');
    if (strpos($c, 'cam') !== false || strpos($c, 'orange') !== false || strpos($c, 'ánh dương') !== false || strpos($c, 'hoàng hôn') !== false) return '#ea580c';
    if (strpos($c, 'matcha') !== false) return '#65a30d';
    if (strpos($c, 'mòng két') !== false || strpos($c, 'teal') !== false || strpos($c, 'mint') !== false) return '#0d9488';
    if (strpos($c, 'lục bảo') !== false || strpos($c, 'ngọc bích') !== false || strpos($c, 'sơn lâm') !== false || strpos($c, 'jade') !== false || strpos($c, 'rêu') !== false || strpos($c, 'botanic') !== false || strpos($c, 'xanh lục') !== false || strpos($c, 'xanh lá') !== false) return '#059669';
    if (strpos($c, 'lưu ly') !== false || strpos($c, 'navy') !== false || strpos($c, 'xanh đậm') !== false || strpos($c, 'deep blue') !== false) return '#1e3a8a';
    if (strpos($c, 'sa mạc') !== false || strpos($c, 'vàng') !== false || strpos($c, 'gold') !== false || strpos($c, 'amber') !== false || strpos($c, 'cá tính') !== false || strpos($c, 'lemon') !== false || strpos($c, 'biển cát') !== false || strpos($c, 'solar') !== false || strpos($c, 'hoàng kim') !== false) return '#f59e0b';
    if (strpos($c, 'hồng') !== false || strpos($c, 'pink') !== false || strpos($c, 'rosé') !== false || strpos($c, 'ngọc trai') !== false || strpos($c, 'rose') !== false || strpos($c, 'phấn') !== false) return '#f472b6';
    if (strpos($c, 'tím') !== false || strpos($c, 'purple') !== false || strpos($c, 'lilac') !== false || strpos($c, 'cobalt') !== false || strpos($c, 'coral') !== false || strpos($c, 'cực quang') !== false) return '#8b5cf6';
    if (strpos($c, 'kem') !== false || strpos($c, 'ivory') !== false || strpos($c, 'cotton') !== false) return '#f5f0e1';
    if (strpos($c, 'trời đêm') !== false || strpos($c, 'midnight') !== false) return '#1e293b';
    if (strpos($c, 'xanh') !== false || strpos($c, 'blue') !== false || strpos($c, 'sky') !== false || strpos($c, 'icy') !== false || strpos($c, 'topaz') !== false || strpos($c, 'iceblue') !== false || strpos($c, 'lạc quan') !== false || strpos($c, 'đại dương') !== false || strpos($c, 'tinh tú') !== false || strpos($c, 'maya') !== false || strpos($c, 'glacier') !== false) return '#38bdf8';
    if (strpos($c, 'đen') !== false || strpos($c, 'black') !== false || strpos($c, 'phantom') !== false || strpos($c, 'space') !== false || strpos($c, 'obsidian') !== false || strpos($c, 'graphite') !== false || strpos($c, 'huyền vũ') !== false || strpos($c, 'bóng đêm') !== false || strpos($c, 'đá') !== false || strpos($c, 'bản lĩnh') !== false || strpos($c, 'kính') !== false || strpos($c, 'da thuần') !== false || strpos($c, 'không gian') !== false || strpos($c, 'cổ điển') !== false || strpos($c, 'vũ trụ') !== false) return '#1e293b';
    if (strpos($c, 'trắng') !== false || strpos($c, 'white') !== false || strpos($c, 'bạc') !== false || strpos($c, 'silver') !== false || strpos($c, 'tự nhiên') !== false || strpos($c, 'porcelain') !== false || strpos($c, 'sứ') !== false || strpos($c, 'xỉu') !== false || strpos($c, 'ngân hà') !== false || strpos($c, 'ánh sao') !== false || strpos($c, 'starlight') !== false || strpos($c, 'da gốm') !== false || strpos($c, 'bach kim') !== false || strpos($c, 'bạch kim') !== false) return '#e2e8f0';
    if (strpos($c, 'xám') !== false || strpos($c, 'gray') !== false || strpos($c, 'grey') !== false || strpos($c, 'metal') !== false || strpos($c, 'marble') !== false || strpos($c, 'opal') !== false || strpos($c, 'hazel') !== false || strpos($c, 'titan') !== false) return '#94a3b8';
    if (strpos($c, 'đỏ') !== false || strpos($c, 'red') !== false || strpos($c, 'burgundy') !== false || strpos($c, 'ruihong') !== false || strpos($c, 'scarlet') !== false) return '#ef4444';
    return '#64748b';
}

function getBrandLogoFile(string $brandName): string {
    $b = mb_strtolower(trim($brandName), 'UTF-8');
    $slug = 'other';
    if (strpos($b, 'apple') !== false || strpos($b, 'iphone') !== false) $slug = 'apple';
    elseif (strpos($b, 'samsung') !== false) $slug = 'samsung';
    elseif (strpos($b, 'xiaomi') !== false) $slug = 'xiaomi';
    elseif (strpos($b, 'oppo') !== false) $slug = 'oppo';
    elseif (strpos($b, 'vivo') !== false) $slug = 'vivo';
    elseif (strpos($b, 'google') !== false || strpos($b, 'pixel') !== false) $slug = 'google';
    elseif (strpos($b, 'asus') !== false || strpos($b, 'rog') !== false) $slug = 'asus';
    elseif (strpos($b, 'sony') !== false) $slug = 'sony';
    elseif (strpos($b, 'huawei') !== false || strpos($b, 'honor') !== false) $slug = 'huawei';

    $extensions = ['png', 'svg', 'webp', 'jpg', 'jpeg'];
    foreach ($extensions as $ext) {
        $path = "assets/images/brands/{$slug}.{$ext}";
        if (file_exists($path)) {
            return $path;
        }
    }
    return '';
}

function getBrandMeta(string $brandName): array {
    $b = mb_strtolower(trim($brandName), 'UTF-8');
    if (strpos($b, 'apple') !== false || strpos($b, 'iphone') !== false) {
        return ['slug' => 'apple', 'type' => 'icon', 'displayName' => 'Apple', 'shortName' => 'Apple', 'invertOnActive' => true];
    }
    if (strpos($b, 'samsung') !== false) {
        return ['slug' => 'samsung', 'type' => 'wordmark', 'displayName' => 'Samsung', 'shortName' => 'Samsung', 'invertOnActive' => true];
    }
    if (strpos($b, 'xiaomi') !== false) {
        return ['slug' => 'xiaomi', 'type' => 'icon', 'displayName' => 'Xiaomi', 'shortName' => 'Xiaomi', 'invertOnActive' => false];
    }
    if (strpos($b, 'oppo') !== false) {
        return ['slug' => 'oppo', 'type' => 'wordmark', 'displayName' => 'OPPO', 'shortName' => 'OPPO', 'invertOnActive' => true];
    }
    if (strpos($b, 'vivo') !== false) {
        return ['slug' => 'vivo', 'type' => 'wordmark', 'displayName' => 'Vivo', 'shortName' => 'Vivo', 'invertOnActive' => true];
    }
    if (strpos($b, 'google') !== false || strpos($b, 'pixel') !== false) {
        return ['slug' => 'google', 'type' => 'icon', 'displayName' => 'Google Pixel', 'shortName' => 'Google Pixel', 'invertOnActive' => false];
    }
    if (strpos($b, 'asus') !== false || strpos($b, 'rog') !== false) {
        return ['slug' => 'asus', 'type' => 'icon', 'displayName' => 'ASUS ROG', 'shortName' => 'ASUS ROG', 'invertOnActive' => false];
    }
    if (strpos($b, 'sony') !== false) {
        return ['slug' => 'sony', 'type' => 'wordmark', 'displayName' => 'Sony', 'shortName' => 'Sony', 'invertOnActive' => true];
    }
    if (strpos($b, 'huawei') !== false || strpos($b, 'honor') !== false) {
        return ['slug' => 'huawei', 'type' => 'icon', 'displayName' => 'Huawei & Honor', 'shortName' => 'Huawei', 'invertOnActive' => false];
    }
    return ['slug' => 'other', 'type' => 'generic', 'displayName' => $brandName, 'shortName' => $brandName, 'invertOnActive' => false];
}

function renderBrandLogo(string $brandName, int $boxW = 32, int $boxH = 22, int $imgMaxH = 18): string {
    $file = getBrandLogoFile($brandName);
    if (!empty($file)) {
        $meta = getBrandMeta($brandName);
        $invertClass = $meta['invertOnActive'] ? 'brand-logo-invert' : '';
        return '<span class="brand-logo-frame" style="width:' . $boxW . 'px; height:' . $boxH . 'px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;">' .
               '<img src="' . htmlspecialchars($file) . '" alt="' . htmlspecialchars($brandName) . '" style="max-height:' . $imgMaxH . 'px; max-width:' . $boxW . 'px; width:auto; height:auto; object-fit:contain; display:block;" class="brand-logo-img ' . $invertClass . ' brand-logo-' . $meta['slug'] . '">' .
               '</span>';
    }
    return '';
}

function renderBrandFilterPill(array $brand, int $activeBrandId): string {
    $bId = (int)$brand['id'];
    $bName = $brand['name'];
    $meta = getBrandMeta($bName);
    $logoFile = getBrandLogoFile($bName);
    $isActive = ($activeBrandId === $bId);
    $activeClass = $isActive ? 'active btn-primary text-white' : 'btn-outline-secondary';
    $invertClass = $meta['invertOnActive'] ? 'brand-logo-invert' : '';

    $html = '<a href="index.php?brand_id=' . $bId . '" ';
    $html .= 'class="btn btn-brand-pill ' . $activeClass . '" ';
    $html .= 'data-brand-id="' . $bId . '" ';
    $html .= 'data-brand-name="' . htmlspecialchars($bName) . '">';

    if (!empty($logoFile)) {
        if ($meta['type'] === 'wordmark') {
            $html .= '<img src="' . htmlspecialchars($logoFile) . '" alt="' . htmlspecialchars($bName) . '" class="brand-pill-wordmark ' . $invertClass . ' brand-logo-' . $meta['slug'] . '">';
        } else {
            $html .= '<img src="' . htmlspecialchars($logoFile) . '" alt="' . htmlspecialchars($bName) . '" class="brand-pill-icon ' . $invertClass . ' brand-logo-' . $meta['slug'] . '">';
            $html .= '<span>' . htmlspecialchars($meta['shortName']) . '</span>';
        }
    } else {
        $html .= '<span>' . htmlspecialchars($bName) . '</span>';
    }

    $html .= '</a>';
    return $html;
}

function renderSidebarBrandItem(int $brandId, array $brandData): string {
    $meta = getBrandMeta($brandData['name']);
    $logoFile = getBrandLogoFile($brandData['name']);

    $html = '<a href="index.php?brand_id=' . $brandId . '" class="list-group-item list-group-item-action sidebar-brand-link d-flex align-items-center py-2 px-3 gap-3 border-0 border-bottom" data-brand-id="' . $brandId . '">';
    $html .= '<span class="sidebar-brand-badge" style="width:46px; height:32px; flex-shrink:0; overflow:hidden; display:inline-flex; align-items:center; justify-content:center;">';
    if (!empty($logoFile)) {
        $html .= '<img src="' . htmlspecialchars($logoFile) . '" alt="' . htmlspecialchars($brandData['name']) . '" class="sidebar-brand-logo sidebar-logo-' . $meta['slug'] . '" style="max-width:38px; max-height:22px; width:auto; height:auto; object-fit:contain; display:block;">';
    } else {
        $html .= '<i class="fa-solid fa-mobile text-primary"></i>';
    }
    $html .= '</span>';
    $html .= '<span class="fw-semibold text-dark flex-grow-1 text-truncate sidebar-brand-label">' . htmlspecialchars($brandData['label']) . '</span>';
    $html .= '<i class="fa-solid fa-chevron-right text-muted small opacity-50 ms-auto"></i>';
    $html .= '</a>';
    return $html;
}

require_once __DIR__ . '/../../data/product_color_map.php';

function getProductColorImage(int $productId, string $colorName, string $fallback = ''): string {
    $map = getAllProductColorMap();
    if (isset($map[$productId][$colorName])) {
        return $map[$productId][$colorName];
    }
    return $fallback;
}

function renderStarRating(float $rating, string $sizeClass = 'small', bool $showNumber = false): string {
    $rating = max(0, min(5, $rating));
    $fullStars = (int)floor($rating);
    $fraction = $rating - $fullStars;
    $hasHalf = ($fraction >= 0.25 && $fraction < 0.75);
    if ($fraction >= 0.75) {
        $fullStars++;
    }
    $emptyStars = 5 - $fullStars - ($hasHalf ? 1 : 0);

    $html = '<span class="star-rating text-warning ' . $sizeClass . '" title="' . number_format($rating, 1) . ' / 5 sao">';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fa-solid fa-star"></i>';
    }
    if ($hasHalf) {
        $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="fa-regular fa-star text-muted opacity-50"></i>';
    }
    if ($showNumber) {
        $html .= ' <strong class="ms-1 text-dark">' . number_format($rating, 1) . '</strong>';
    }
    $html .= '</span>';
    return $html;
}

function formatTimeAgo(string $datetime): string {
    $time = strtotime($datetime);
    if (!$time) return $datetime;

    $diff = time() - $time;
    if ($diff < 60) {
        return 'Vừa xong';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' phút trước';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' giờ trước';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' ngày trước';
    } else {
        return date('d/m/Y', $time);
    }
}

/**
 * Lấy cấu hình biểu tượng và phong cách hiện đại cho trạng thái đơn hàng
 */
function getOrderStatusMeta(string $status): array {
    $st = trim($status);
    switch ($st) {
        case 'Chờ xử lý':
            return [
                'key' => 'pending',
                'status' => 'Chờ xử lý',
                'label' => 'Chờ xử lý',
                'shortLabel' => 'Chờ xử lý',
                'icon' => 'fa-solid fa-clock-rotate-left',
                'pillClass' => 'status-pill-pending',
                'badgeClass' => 'badge-soft-warning',
                'color' => '#d97706',
                'textClass' => 'text-warning'
            ];
        case 'Đang xử lý':
            return [
                'key' => 'processing',
                'status' => 'Đang xử lý',
                'label' => 'Đang chuẩn bị máy',
                'shortLabel' => 'Đang chuẩn bị',
                'icon' => 'fa-solid fa-boxes-packing',
                'pillClass' => 'status-pill-processing',
                'badgeClass' => 'badge-soft-primary',
                'color' => '#2563eb',
                'textClass' => 'text-primary'
            ];
        case 'Chờ thanh toán':
            return [
                'key' => 'payment',
                'status' => 'Chờ thanh toán',
                'label' => 'Chờ thanh toán VietQR',
                'shortLabel' => 'Chờ thanh toán',
                'icon' => 'fa-solid fa-qrcode',
                'pillClass' => 'status-pill-payment',
                'badgeClass' => 'badge-soft-purple',
                'color' => '#9333ea',
                'textClass' => 'text-purple'
            ];
        case 'Đang giao hàng':
        case 'Đang giao':
            return [
                'key' => 'shipping',
                'status' => 'Đang giao hàng',
                'label' => 'Đang giao hỏa tốc',
                'shortLabel' => 'Đang giao',
                'icon' => 'fa-solid fa-truck-fast',
                'pillClass' => 'status-pill-shipping',
                'badgeClass' => 'badge-soft-info',
                'color' => '#0284c7',
                'textClass' => 'text-info'
            ];
        case 'Đã giao thành công':
        case 'Đã giao':
            return [
                'key' => 'delivered',
                'status' => 'Đã giao thành công',
                'label' => 'Đã giao thành công',
                'shortLabel' => 'Đã giao',
                'icon' => 'fa-solid fa-circle-check',
                'pillClass' => 'status-pill-delivered',
                'badgeClass' => 'badge-soft-success',
                'color' => '#16a34a',
                'textClass' => 'text-success'
            ];
        case 'Đã hủy':
        case 'Đã hủy đơn hàng':
            return [
                'key' => 'cancelled',
                'status' => 'Đã hủy',
                'label' => 'Đã hủy đơn hàng',
                'shortLabel' => 'Đã hủy',
                'icon' => 'fa-solid fa-circle-xmark',
                'pillClass' => 'status-pill-cancelled',
                'badgeClass' => 'badge-soft-danger',
                'color' => '#dc2626',
                'textClass' => 'text-danger'
            ];
        default:
            return [
                'key' => 'default',
                'status' => $st,
                'label' => $st,
                'shortLabel' => $st,
                'icon' => 'fa-solid fa-tag',
                'pillClass' => 'status-pill-pending',
                'badgeClass' => 'badge-soft-secondary',
                'color' => '#64748b',
                'textClass' => 'text-secondary'
            ];
    }
}

/**
 * Hiển thị thẻ trạng thái hiện đại với hiệu ứng pulse và biểu tượng FontAwesome
 */
function renderOrderStatusPill(string $status, bool $pulse = true, string $customClass = '', ?string $customLabel = null): string {
    $meta = getOrderStatusMeta($status);
    $label = $customLabel ?? $meta['label'];
    $dotHtml = $pulse ? '<span class="pulse-dot me-2"></span>' : '';
    return '<span class="status-pill ' . $meta['pillClass'] . ' ' . $customClass . '">' .
           $dotHtml .
           '<i class="' . $meta['icon'] . ' me-2"></i>' .
           '<span>' . htmlspecialchars($label) . '</span>' .
           '</span>';
}

/**
 * Hiển thị huy hiệu trạng thái nhỏ gọn cho danh sách bảng hoặc admin
 */
function renderOrderStatusBadge(string $status, string $customClass = '', ?string $customLabel = null): string {
    $meta = getOrderStatusMeta($status);
    $label = $customLabel ?? $meta['shortLabel'];
    return '<span class="badge rounded-pill ' . $meta['badgeClass'] . ' ' . $customClass . '">' .
           '<i class="' . $meta['icon'] . ' me-1"></i>' .
           htmlspecialchars($label) .
           '</span>';
}

