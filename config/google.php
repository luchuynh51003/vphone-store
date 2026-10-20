<?php
/**
 * Cấu hình Đăng nhập / Đăng ký Google (Google Sign-In & OAuth 2.0)
 * Cho website V-Phone Store
 * 
 * Hướng dẫn lấy Client ID và Client Secret từ Google Cloud Console:
 * 1. Truy cập https://console.cloud.google.com/
 * 2. Tạo một Dự án mới (Project) ví dụ: "VPhone Store"
 * 3. Vào "APIs & Services" > "Credentials" (Thông tin xác thực)
 * 4. Nhấn "Create Credentials" > chọn "OAuth client ID"
 * 5. Chọn Application type: "Web application"
 * 6. Tại "Authorized JavaScript origins", thêm:
 *    - http://localhost
 *    - http://127.0.0.1
 * 7. Tại "Authorized redirect URIs", thêm:
 *    - http://localhost/vphone-store/index.php?page=auth&action=google_callback
 *    - http://localhost/vphone-store/index.php?page=google-callback
 *    - http://localhost/phone-store/index.php?page=auth&action=google_callback
 *    - http://localhost/phone-store/index.php?page=google-callback
 * 8. Copy Client ID và Client Secret dán vào bên dưới.
 */

// Tự động phát hiện và chuẩn hóa URL gốc (Base URL) cho Redirect URI
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$rawHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Chuẩn hóa 127.0.0.1 thành localhost để luôn đồng nhất với cấu hình Google
$host = preg_replace('/^127\.0\.0\.1(:[0-9]+)?$/', 'localhost$1', $rawHost);
$host = preg_replace('/:(80|443)$/', '', $host);

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/vphone-store/index.php');
$rawDir = rtrim(dirname($scriptName), '/.');
$scriptDir = ($rawDir === '' || $rawDir === '.') ? '' : $rawDir;
$dynamicRedirectUri = $protocol . '://' . $host . $scriptDir . '/index.php?page=auth&action=google_callback';

return [
    // Client ID từ Google Cloud Console
    'client_id' => '998905799354-lhgtofc0die7433sg0bb6k2no7aeu56l.apps.googleusercontent.com',

    // Client Secret từ Google Cloud Console
    'client_secret' => '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',

    // Redirect URI sau khi cấp quyền
    'redirect_uri' => $dynamicRedirectUri,

    // Chế độ mô phỏng / dùng thử nhanh (Demo Mode):
    // - false (Khuyến nghị): Khi bạn dán Client ID và Secret thật vào 2 dòng trên, hệ thống sẽ NGAY LẬP TỨC chuyển sang đăng nhập Google thật 100%.
    // - true: Chỉ dùng khi muốn ép mở bảng chọn tài khoản demo để test giao diện.
    'demo_mode' => false,
];

