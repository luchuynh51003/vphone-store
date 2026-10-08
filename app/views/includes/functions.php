<?php
// ĐỊNH DẠNG TIỀN TỆ VNĐ
function formatPrice($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' đ';
}

// LẤY DẢI DUNG LƯỢNG BỘ NHỚ THEO PHÂN KHÚC MÁY
function getStorageTiers($prodName, $defaultRom) {
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

// LẤY MÃ MÀU HEX CHUẨN XÁC
function getColorHexValue($name) {
    $c = mb_strtolower($name, 'UTF-8');
    if (strpos($c, 'sa mạc') !== false || strpos($c, 'vàng') !== false || strpos($c, 'gold') !== false) return '#cbbba0';
    if (strpos($c, 'hồng') !== false || strpos($c, 'pink') !== false) return '#f472b6';
    if (strpos($c, 'tím') !== false || strpos($c, 'purple') !== false) return '#8b5cf6';
    if (strpos($c, 'xanh') !== false || strpos($c, 'blue') !== false || strpos($c, 'navy') !== false) return '#38bdf8';
    if (strpos($c, 'đen') !== false || strpos($c, 'black') !== false || strpos($c, 'phantom') !== false) return '#1e293b';
    if (strpos($c, 'trắng') !== false || strpos($c, 'white') !== false || strpos($c, 'bạc') !== false || strpos($c, 'tự nhiên') !== false) return '#e2e8f0';
    if (strpos($c, 'đỏ') !== false || strpos($c, 'red') !== false) return '#ef4444';
    return '#0066cc';
}
