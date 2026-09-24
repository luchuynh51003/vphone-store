<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

require_once 'config/database.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

if ($keyword === '') {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name, price, sale_price, image FROM products WHERE name LIKE :keyword ORDER BY is_featured DESC, id ASC LIMIT 6");
    $stmt->execute([':keyword' => '%' . $keyword . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fallbackBase64 = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMjQgMjQiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzAwNTZiMyIgc3Ryb2tlLXdpZHRoPSIxLjUiPjxyZWN0IHg9IjUiIHk9IjIiIHdpZHRoPSIxNCIgaGVpZ2h0PSIyMCIgcng9IjMiIGZpbGw9IiNlOGYxZjkiLz48Y2lyY2xlIGN4PSIxMiIgY3k9IjE4IiByPSIxIiBmaWxsPSIjMDA1NmIzIi8+PHBhdGggZD0iTTkgNWg2IiBzdHJva2UtbGluZWNhcD0icm91bmQiLz48L3N2Zz4=';

    $data = [];
    foreach ($results as $p) {
        $finalPrice = ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) ? $p['sale_price'] : $p['price'];
        $data[] = [
            'id' => (int)$p['id'],
            'name' => $p['name'],
            'price_formatted' => number_format($finalPrice, 0, ',', '.') . ' đ',
            'old_price_formatted' => ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) ? number_format($p['price'], 0, ',', '.') . ' đ' : null,
            'image' => !empty($p['image']) ? $p['image'] : $fallbackBase64
        ];
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([]);
}
