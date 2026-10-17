<?php
/**
 * V-Phone Store 2026 - Callback & Multi-Channel Support Request API
 * Xử lý tiếp nhận yêu cầu gọi lại / tư vấn đa kênh từ khách hàng và trả về danh sách phản hồi từ CSKH
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/NotificationModel.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = $_SESSION['user'] ?? null;
$userId = ($currentUser && !empty($currentUser['id'])) ? (int)$currentUser['id'] : null;
$action = $_REQUEST['action'] ?? 'submit';

// =========================================================================
// 1. ACTION: MY_INQUIRIES - KHÁCH HÀNG XEM LỊCH SỬ VÀ PHẢN HỒI TỪ CSKH
// =========================================================================
if ($action === 'my_inquiries') {
    $searchPhone = trim($_REQUEST['phone'] ?? ($currentUser['phone'] ?? ''));
    $cleanPhone = preg_replace('/[^0-9+]/', '', $searchPhone);

    if (empty($userId) && empty($cleanPhone)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng cung cấp số điện thoại hoặc đăng nhập để tra cứu phản hồi từ CSKH.',
            'inquiries' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        if ($userId && !empty($cleanPhone)) {
            $stmt = $pdo->prepare("
                SELECT id, user_id, fullname, phone, email, topic, department, note, reply_message, 
                       replied_by_name, replied_by_title, replied_at, status, created_at
                FROM callback_requests
                WHERE user_id = ? OR phone = ?
                ORDER BY id DESC
                LIMIT 30
            ");
            $stmt->execute([$userId, $cleanPhone]);
        } elseif ($userId) {
            $stmt = $pdo->prepare("
                SELECT id, user_id, fullname, phone, email, topic, department, note, reply_message, 
                       replied_by_name, replied_by_title, replied_at, status, created_at
                FROM callback_requests
                WHERE user_id = ?
                ORDER BY id DESC
                LIMIT 30
            ");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, user_id, fullname, phone, email, topic, department, note, reply_message, 
                       replied_by_name, replied_by_title, replied_at, status, created_at
                FROM callback_requests
                WHERE phone = ?
                ORDER BY id DESC
                LIMIT 30
            ");
            $stmt->execute([$cleanPhone]);
        }

        $deptNames = [
            'cskh' => 'Chăm sóc khách hàng & Khiếu nại',
            'sales' => 'Bán hàng & Báo giá Flagship',
            'warranty' => 'Kỹ thuật & Bảo hành',
            'installment' => 'Hỗ trợ Trả góp 0%',
            'shipping' => 'Vận chuyển & Giao hàng'
        ];

        $statusNames = [
            'pending' => 'Chờ tiếp nhận',
            'processing' => 'Đang xử lý',
            'replied' => 'Đã phản hồi',
            'closed' => 'Đã hoàn tất',
            'cancelled' => 'Đã hủy'
        ];

        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($inquiries as &$item) {
            $item['dept_title'] = $deptNames[$item['department'] ?? 'cskh'] ?? 'CSKH & Hỗ trợ';
            $item['status_title'] = $statusNames[$item['status'] ?? 'pending'] ?? 'Đang xử lý';
            $item['created_at_formatted'] = date('H:i d/m/Y', strtotime($item['created_at']));
            $item['replied_at_formatted'] = !empty($item['replied_at']) ? date('H:i d/m/Y', strtotime($item['replied_at'])) : null;
        }

        echo json_encode([
            'success' => true,
            'count' => count($inquiries),
            'inquiries' => $inquiries
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (\Throwable $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi tra cứu danh sách yêu cầu: ' . $e->getMessage(),
            'inquiries' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// =========================================================================
// 2. ACTION: SUBMIT - GỬI YÊU CẦU HỖ TRỢ / GỌI LẠI MỚI
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$fullname = trim($_POST['fullname'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? ($currentUser['email'] ?? ''));
$department = trim($_POST['department'] ?? 'cskh');
$topic = trim($_POST['topic'] ?? 'Tư vấn mua Flagship mới');
$note = trim($_POST['note'] ?? '');

if (empty($fullname)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập họ và tên của bạn.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Chuẩn hóa và kiểm tra số điện thoại
$cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
if (strlen($cleanPhone) < 8 || strlen($cleanPhone) > 13) {
    echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại 9 - 11 chữ số.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$validDepts = ['cskh', 'sales', 'warranty', 'installment', 'shipping'];
if (!in_array($department, $validDepts)) {
    $department = 'cskh';
}

$deptNames = [
    'cskh' => 'Chăm sóc khách hàng & CSKH',
    'sales' => 'Tư vấn Bán hàng & Báo giá',
    'warranty' => 'Kỹ thuật & Bảo hành 1 đổi 1',
    'installment' => 'Hỗ trợ Trả góp 0%',
    'shipping' => 'Giao hàng & Vận chuyển'
];
$deptLabel = $deptNames[$department] ?? 'Bộ phận CSKH';

try {
    $stmt = $pdo->prepare("
        INSERT INTO callback_requests (user_id, fullname, phone, email, topic, department, note, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$userId, $fullname, $cleanPhone, $email ?: null, $topic, $department, $note]);
    $requestId = (int)$pdo->lastInsertId();

    // Gửi thông báo trong hệ thống cho khách hàng nếu có tài khoản
    if ($userId) {
        try {
            $notifModel = new NotificationModel($pdo);
            $notifModel->create(
                $userId,
                $cleanPhone,
                null,
                "Đã nhận yêu cầu hỗ trợ #YC-{$requestId}",
                "{$deptLabel} đã tiếp nhận câu hỏi của bạn về chủ đề: \"{$topic}\". Chuyên viên sẽ gọi điện và gửi phản hồi cho bạn trong ít phút.",
                null,
                'callback_request'
            );
        } catch (\Throwable $e) {}
    }

    echo json_encode([
        'success' => true,
        'message' => "Yêu cầu #YC-{$requestId} của bạn đã được chuyển tới {$deptLabel}! Chuyên viên sẽ liên hệ lại qua số " . htmlspecialchars($cleanPhone) . " hoặc phản hồi trực tuyến trong vòng 5 phút.",
        'request_id' => $requestId,
        'department' => $department,
        'department_name' => $deptLabel
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể lưu yêu cầu lúc này: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
