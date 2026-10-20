<?php

class MoMoService {
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $configFile = __DIR__ . '/../../config/momo.php';
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        } else {
            $this->config = [
                'endpoint' => 'https://test-payment.momo.vn/v2/gateway/api/create',
                'partnerCode' => 'MOMOBKUN20180529',
                'accessKey' => 'klm05TvNBzhg7h7j',
                'secretKey' => 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa',
                'requestType' => 'captureWallet',
                'sandbox' => true,
            ];
        }
    }

    private function getBaseUrl(): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/vphone-store/';
        $dir = rtrim(dirname($uri), '/\\');
        if (strpos($dir, 'index.php') !== false) {
            $dir = dirname($dir);
        }
        $dir = ($dir === '' || $dir === '.') ? '' : $dir;
        return $scheme . $host . $dir;
    }

    public function createPayment(int $orderId, float|int $amount, ?string $orderInfo = null): array {
        $endpoint = $this->config['endpoint'];
        $partnerCode = $this->config['partnerCode'];
        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        $requestType = $this->config['requestType'] ?? 'captureWallet';

        $baseUrl = $this->getBaseUrl();
        $redirectUrl = $baseUrl . '/index.php?page=checkout&step=momo_return';
        $ipnUrl = $baseUrl . '/api/momo-ipn.php';

        $momoOrderId = 'VP_' . $orderId . '_' . time();
        $requestId = time() . '_' . rand(1000, 9999);
        $amountInt = (int)round((float)$amount);
        // Quy định của MoMo: Giới hạn số tiền thanh toán từ 1.000 VNĐ đến tối đa 50.000.000 VNĐ
        // Trong môi trường Sandbox Test, nếu đơn hàng có giá trị cao hơn 50 triệu (như Huawei Mate XT 75tr),
        // hệ thống tự động cân đối về 50.000.000 VNĐ để MoMo Sandbox không bị lỗi giới hạn hạn mức.
        if (!empty($this->config['sandbox']) && $amountInt > 50000000) {
            $amountInt = 50000000;
        }
        if ($amountInt < 1000) {
            $amountInt = 1000;
        }
        $info = $orderInfo ?: ("Thanh toan don hang V-Phone #VP-" . $orderId);
        $extraData = base64_encode(json_encode(['order_id' => $orderId]));

        $rawHash = "accessKey=" . $accessKey .
            "&amount=" . $amountInt .
            "&extraData=" . $extraData .
            "&ipnUrl=" . $ipnUrl .
            "&orderId=" . $momoOrderId .
            "&orderInfo=" . $info .
            "&partnerCode=" . $partnerCode .
            "&redirectUrl=" . $redirectUrl .
            "&requestId=" . $requestId .
            "&requestType=" . $requestType;

        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        $payload = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'V-Phone Store',
            'storeId' => 'VPhone_Store',
            'requestId' => $requestId,
            'amount' => $amountInt,
            'orderId' => $momoOrderId,
            'orderInfo' => $info,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature,
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=UTF-8']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return [
                'success' => false,
                'message' => 'Lỗi kết nối tới MoMo Sandbox: ' . $curlErr
            ];
        }

        $resData = json_decode($response, true);
        if (!is_array($resData)) {
            return [
                'success' => false,
                'message' => 'Phản hồi từ MoMo không hợp lệ.'
            ];
        }

        if (isset($resData['resultCode']) && (int)$resData['resultCode'] === 0 && !empty($resData['payUrl'])) {
            return [
                'success' => true,
                'payUrl' => $resData['payUrl'],
                'qrCodeUrl' => $resData['qrCodeUrl'] ?? '',
                'deeplink' => $resData['deeplink'] ?? '',
                'momoOrderId' => $momoOrderId,
                'resultCode' => 0,
            ];
        }

        return [
            'success' => false,
            'message' => $resData['message'] ?? 'Không thể khởi tạo giao dịch MoMo.',
            'resultCode' => $resData['resultCode'] ?? -1,
        ];
    }

    public function extractOrderId(string $rawOrderId): int {
        if (preg_match('/VP_(\d+)/', $rawOrderId, $m)) {
            return (int)$m[1];
        }
        return (int)preg_replace('/[^0-9]/', '', $rawOrderId);
    }

    public function verifyReturn(array $params): bool {
        if (empty($params['signature']) || empty($params['orderId'])) {
            return false;
        }

        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];

        $amount = $params['amount'] ?? '';
        $extraData = $params['extraData'] ?? '';
        $message = $params['message'] ?? '';
        $orderId = $params['orderId'] ?? '';
        $orderInfo = $params['orderInfo'] ?? '';
        $orderType = $params['orderType'] ?? '';
        $partnerCode = $params['partnerCode'] ?? '';
        $payType = $params['payType'] ?? '';
        $requestId = $params['requestId'] ?? '';
        $responseTime = $params['responseTime'] ?? '';
        $resultCode = $params['resultCode'] ?? '';
        $transId = $params['transId'] ?? '';

        $rawHash = "accessKey=" . $accessKey .
            "&amount=" . $amount .
            "&extraData=" . $extraData .
            "&message=" . $message .
            "&orderId=" . $orderId .
            "&orderInfo=" . $orderInfo .
            "&orderType=" . $orderType .
            "&partnerCode=" . $partnerCode .
            "&payType=" . $payType .
            "&requestId=" . $requestId .
            "&responseTime=" . $responseTime .
            "&resultCode=" . $resultCode .
            "&transId=" . $transId;

        $calcSignature = hash_hmac('sha256', $rawHash, $secretKey);
        return hash_equals($calcSignature, $params['signature']);
    }

    public function markAsPaid(int $orderId, string $transId, ?float $amount = null): array {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return ['success' => false, 'message' => "Không tìm thấy đơn hàng #VP-$orderId"];
        }

        $paidAt = date('Y-m-d H:i:s');
        $transCode = 'MOMO-' . $transId;
        $note = $order['note'] ?? '';
        $newNote = trim($note . " [MoMo Sandbox: Đã thanh toán qua MoMo lúc " . date('d/m/Y H:i:s') . " - Mã GD: $transCode]");

        $newStatus = $order['status'];
        if (in_array($order['status'], ['Chờ xử lý', 'Chờ thanh toán', 'pending'])) {
            $newStatus = 'Đang xử lý';
        }

        $up = $this->pdo->prepare("UPDATE orders SET 
            payment_status = 'Đã thanh toán',
            status = ?,
            transaction_code = ?,
            paid_at = ?,
            note = ?
            WHERE id = ?");
        $up->execute([$newStatus, $transCode, $paidAt, $newNote, $orderId]);

        try {
            require_once __DIR__ . '/../models/NotificationModel.php';
            $notifModel = new NotificationModel($this->pdo);
            $totalFmt = number_format($order['total_money'], 0, ',', '.') . ' VNĐ';
            $notifTitle = "Thanh toán thành công đơn hàng #VP-{$orderId}!";
            $notifMsg = "Đơn hàng #VP-{$orderId} (Tổng thanh toán: {$totalFmt}) đã được xác nhận thanh toán thành công qua MoMo. Cửa hàng đang chuẩn bị sản phẩm.";
            $notifModel->create(
                $order['user_id'] ? (int)$order['user_id'] : null,
                $order['phone'],
                $orderId,
                $notifTitle,
                $notifMsg,
                null,
                'order_processing'
            );
        } catch (\Throwable $e) {}

        return [
            'success' => true,
            'message' => "Thanh toán MoMo cho đơn hàng #VP-$orderId thành công!",
            'transaction_code' => $transCode,
            'paid_at' => $paidAt
        ];
    }
}

