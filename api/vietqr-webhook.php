<?php
/**
 * API Webhook nhận thông báo biến động số dư chuyển khoản VietQR tự động
 * Hỗ trợ các cổng Webhook phổ biến: SePay, Casso, Open API ngân hàng, v.v.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/VietQRService.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!is_array($data)) {
    $data = $_POST;
}

if (empty($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Yêu cầu không hợp lệ. Payload rỗng.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Lấy mã xác thực nếu có (tương thích cả CLI, FastCGI, Nginx, Apache)
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['HTTP_X_API_KEY'] ?? ($_GET['token'] ?? ''));
if (empty($authHeader) && function_exists('getallheaders')) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? ($headers['X-Api-Key'] ?? ''));
}
$authHeader = trim(str_ireplace('Bearer ', '', $authHeader));

$service = new VietQRService($pdo);
$result = $service->processWebhook($data, $authHeader);

if (!empty($result['success'])) {
    http_response_code(200);
} else {
    http_response_code(400);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
