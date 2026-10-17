<?php

class VietQRService {
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $configFile = __DIR__ . '/../../config/vietqr.php';
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        } else {
            $this->config = [
                'bank_id' => 'MB',
                'bank_name' => 'Ngân hàng Quân Đội (MB Bank)',
                'account_no' => '0388888899',
                'account_name' => 'V-PHONE STORE',
                'template' => 'compact2',
                'prefix' => 'VPHONE',
                'timeout_minutes' => 15,
                'webhook_token' => 'vphone_secret_token_2026',
                'demo_mode' => true,
            ];
        }
    }

    public function getConfig(): array {
        return $this->config;
    }

    public function getTransferContent(int $orderId): string {
        $prefix = trim($this->config['prefix'] ?? 'VPHONE');
        return strtoupper($prefix) . ' ' . $orderId;
    }

    public function generateQrUrl(int $orderId, float|int $amount, ?string $customContent = null): string {
        $bankId = urlencode($this->config['bank_id'] ?? 'MB');
        $accountNo = urlencode($this->config['account_no'] ?? '0388888899');
        $template = urlencode($this->config['template'] ?? 'compact2');
        $accountName = rawurlencode($this->config['account_name'] ?? 'V-PHONE STORE');
        
        $content = $customContent ?: $this->getTransferContent($orderId);
        $encodedContent = rawurlencode($content);
        $amountInt = (int)round((float)$amount);

        return "https://img.vietqr.io/image/{$bankId}-{$accountNo}-{$template}.png?amount={$amountInt}&addInfo={$encodedContent}&accountName={$accountName}";
    }

    public function checkPaymentStatus(int $orderId): array {
        $stmt = $this->pdo->prepare("SELECT id, fullname, phone, total_money, payment_method, status, payment_status, transaction_code, paid_at FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng.',
                'is_paid' => false,
            ];
        }

        $isPaid = isset($order['payment_status']) && (
            mb_strtolower(trim($order['payment_status']), 'UTF-8') === 'đã thanh toán' ||
            mb_strtolower(trim($order['payment_status']), 'UTF-8') === 'paid'
        );

        return [
            'success' => true,
            'is_paid' => $isPaid,
            'order_id' => (int)$order['id'],
            'status' => $order['status'],
            'payment_status' => $order['payment_status'] ?? ($isPaid ? 'Đã thanh toán' : 'Chưa thanh toán'),
            'transaction_code' => $order['transaction_code'] ?? null,
            'paid_at' => $order['paid_at'] ?? null,
            'total_money' => (float)$order['total_money'],
        ];
    }

    public function markAsPaid(int $orderId, ?string $transactionCode = null, ?float $amount = null, string $noteAppend = ''): array {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return ['success' => false, 'message' => 'Không tìm thấy đơn hàng #VP-' . $orderId];
        }

        if (empty($transactionCode)) {
            $transactionCode = 'VQR' . date('YmdHis') . rand(100, 999);
        }

        $paidAt = date('Y-m-d H:i:s');
        $currentNote = $order['note'] ?? '';
        $addon = "[VietQR Auto-Pay: Đã thanh toán tự động qua mã QR lúc " . date('d/m/Y H:i:s') . " - Mã GD: $transactionCode]";
        if (!empty($noteAppend)) {
            $addon .= " ($noteAppend)";
        }
        $newNote = trim($currentNote . ' ' . $addon);

        // Chuyển status đơn sang 'Đang xử lý' nếu đang là 'Chờ xử lý' hoặc 'Chờ thanh toán'
        $newStatus = $order['status'];
        if (in_array($order['status'], ['Chờ xử lý', 'Chờ thanh toán', 'pending'])) {
            $newStatus = 'Đang xử lý';
        }

        $update = $this->pdo->prepare("UPDATE orders SET 
            payment_status = 'Đã thanh toán',
            status = ?,
            transaction_code = ?,
            paid_at = ?,
            note = ?
            WHERE id = ?");
        
        $update->execute([$newStatus, $transactionCode, $paidAt, $newNote, $orderId]);

        try {
            require_once __DIR__ . '/../models/NotificationModel.php';
            $notifModel = new NotificationModel($this->pdo);
            $totalFmt = number_format($order['total_money'], 0, ',', '.') . ' đ';
            $notifTitle = "Thanh toán thành công đơn hàng #VP-{$orderId}!";
            $notifMsg = "Đơn hàng #VP-{$orderId} (Tổng thanh toán: {$totalFmt}) đã được xác nhận thanh toán thành công qua VietQR. Cửa hàng đang chuẩn bị sản phẩm.";
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
            'message' => 'Xác nhận thanh toán đơn hàng #VP-' . $orderId . ' thành công!',
            'transaction_code' => $transactionCode,
            'paid_at' => $paidAt,
            'new_status' => $newStatus,
        ];
    }

    public function simulatePayment(int $orderId): array {
        $transCode = 'SIM-VQR-' . date('His') . '-' . rand(1000, 9999);
        return $this->markAsPaid($orderId, $transCode, null, 'Giả lập khách chuyển khoản thành công');
    }

    public function processWebhook(array $payload, string $tokenHeader = ''): array {
        $validToken = $this->config['webhook_token'] ?? '';
        if (!empty($validToken) && !empty($tokenHeader) && $tokenHeader !== $validToken) {
            return ['success' => false, 'message' => 'Mã bảo mật Webhook không chính xác.'];
        }

        // Hỗ trợ cả SePay: content, transferAmount, referenceCode
        // và Casso: description, amount, tid
        $content = $payload['content'] ?? ($payload['description'] ?? ($payload['addInfo'] ?? ''));
        $amount = (float)($payload['transferAmount'] ?? ($payload['amount'] ?? ($payload['transfer_amount'] ?? 0)));
        $transCode = $payload['referenceCode'] ?? ($payload['reference_number'] ?? ($payload['tid'] ?? ($payload['id'] ?? '')));

        if (empty($content)) {
            return ['success' => false, 'message' => 'Không tìm thấy nội dung giao dịch trong webhook payload.'];
        }

        $prefix = preg_quote($this->config['prefix'] ?? 'VPHONE', '/');
        // Tìm pattern VPHONE 123 hoặc VP123 hoặc DH123
        $orderId = 0;
        if (preg_match('/(?:' . $prefix . '|VP|DH|DONHANG)\s*(\d+)/i', $content, $m)) {
            $orderId = (int)$m[1];
        } elseif (preg_match('/#VP-(\d+)/i', $content, $m)) {
            $orderId = (int)$m[1];
        }

        if ($orderId <= 0) {
            return ['success' => false, 'message' => 'Nội dung giao dịch không chứa mã đơn hàng hợp lệ: ' . $content];
        }

        $stmt = $this->pdo->prepare("SELECT total_money, payment_status FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return ['success' => false, 'message' => "Không tìm thấy đơn hàng #VP-{$orderId} trong hệ thống."];
        }

        if ($order['payment_status'] === 'Đã thanh toán') {
            return ['success' => true, 'message' => "Đơn hàng #VP-{$orderId} đã được thanh toán từ trước."];
        }

        if ($amount > 0 && $amount < (float)$order['total_money']) {
            return [
                'success' => false,
                'message' => "Số tiền chuyển ({$amount} đ) nhỏ hơn tổng đơn ({$order['total_money']} đ)."
            ];
        }

        return $this->markAsPaid($orderId, (string)$transCode, $amount, 'Nhận diện tự động qua Webhook');
    }
}

