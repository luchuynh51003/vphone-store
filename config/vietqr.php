<?php
// Cấu hình thanh toán VietQR tự động cho V-Phone Store
return [
    // Mã ngân hàng theo chuẩn Napas/VietQR (MB, VCB, TCB, VPB, ACB, ICB, BIDV, TPB, STB, VIB...)
    'bank_id' => 'MB',
    'bank_name' => 'Ngân hàng Quân Đội (MB Bank)',
    'account_no' => '0388888899',
    'account_name' => 'V-PHONE STORE',
    
    // Giao diện mẫu mã QR: compact, compact2, qr_only, print
    'template' => 'compact2',
    
    // Cú pháp nội dung chuyển khoản (VD: VPHONE 123)
    'prefix' => 'VPHONE',
    
    // Thời hạn đếm ngược thanh toán (phút)
    'timeout_minutes' => 15,
    
    // Mã bảo mật xác thực Webhook tự động (SePay, Casso hoặc Ngân hàng)
    'webhook_token' => 'vphone_secret_token_2026',
    
    // Bật chế độ Demo Simulator cho phép test xác nhận nhanh bằng 1 click
    'demo_mode' => true,
];

