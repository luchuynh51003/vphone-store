<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/views/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$keyword = trim($_GET['keyword'] ?? $_GET['q'] ?? '');
$isAll = isset($_GET['all']) && $_GET['all'] == '1';

if ($isAll) {
    try {
        $stmt = $pdo->query("SELECT p.id, p.name, p.price, p.sale_price, p.image, b.name as brand_name 
                             FROM products p 
                             LEFT JOIN brands b ON p.brand_id = b.id 
                             ORDER BY p.is_featured DESC, p.id ASC");
        $prods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = [];
        foreach ($prods as $p) {
            $pPrice = ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) ? (int)$p['sale_price'] : (int)$p['price'];
            $results[] = [
                'id' => (int)$p['id'],
                'name' => $p['name'],
                'brand_name' => $p['brand_name'] ?? '',
                'price' => $pPrice,
                'old_price' => (int)$p['price'],
                'price_formatted' => number_format($pPrice, 0, ',', '.') . ' VNĐ',
                'image' => $p['image']
            ];
        }
        echo json_encode($results, JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($keyword === '') {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$cleanKw = mb_strtolower($keyword, 'UTF-8');
$searchTerms = [$cleanKw];

if ($cleanKw === 'ss' || $cleanKw === 'sam') {
    $searchTerms[] = 'samsung';
} elseif ($cleanKw === 'ip') {
    $searchTerms[] = 'iphone';
} elseif (preg_match('/^ip\s*(.*)$/i', $cleanKw, $m)) {
    $searchTerms[] = 'iphone ' . trim($m[1]);
} elseif (preg_match('/^ss\s*(.*)$/i', $cleanKw, $m)) {
    $searchTerms[] = 'samsung ' . trim($m[1]);
}

$whereClauses = [];
$params = [];
foreach ($searchTerms as $idx => $term) {
    $whereClauses[] = "(p.name LIKE :kw{$idx} OR b.name LIKE :kwb{$idx})";
    $params[":kw{$idx}"] = "%{$term}%";
    $params[":kwb{$idx}"] = "%{$term}%";
}

$sql = "SELECT p.id, p.name, p.price, p.sale_price, p.image, b.name as brand_name 
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        WHERE (" . implode(" OR ", $whereClauses) . ") 
        ORDER BY p.is_featured DESC, p.id ASC 
        LIMIT 8";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($products as $p) {
        $pPrice = ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) ? (int)$p['sale_price'] : (int)$p['price'];
        $results[] = [
            'id' => (int)$p['id'],
            'name' => $p['name'],
            'brand_name' => $p['brand_name'] ?? '',
            'price' => $pPrice,
            'old_price' => (int)$p['price'],
            'price_formatted' => number_format($pPrice, 0, ',', '.') . ' VNĐ',
            'image' => $p['image']
        ];
    }
    echo json_encode($results, JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
}
exit;

