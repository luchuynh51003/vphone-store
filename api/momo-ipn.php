<?php
/**
 * MoMo IPN (Instant Payment Notification) Webhook
 * Nhận thông báo giao dịch ngầm từ MoMo Sandbox
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/MoMoService.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data) || empty($data)) {
    $data = $_POST;
}

if (!empty($data) && isset($data['orderId'])) {
    $momoService = new MoMoService($pdo);
    $orderId = $momoService->extractOrderId($data['orderId']);
    $resultCode = (int)($data['resultCode'] ?? -1);
    $transId = (string)($data['transId'] ?? ($data['requestId'] ?? time()));

    if ($orderId > 0 && $resultCode === 0) {
        $momoService->markAsPaid($orderId, $transId, (float)($data['amount'] ?? 0));
    }
}

// MoMo yêu cầu phản hồi HTTP 204 No Content
http_response_code(204);
exit;

