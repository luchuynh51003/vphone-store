<?php
/**
 * Dịch vụ gửi Email thông báo (OTP Đặt lại mật khẩu, Đơn hàng, v.v.)
 * Hỗ trợ hàm mail() chuẩn của PHP và cơ chế fallback thông minh cho môi trường Localhost/XAMPP
 */
class MailService {
    /**
     * Gửi email mã OTP và link đặt lại mật khẩu
     */
    public static function sendPasswordResetEmail(string $toEmail, string $recipientName, string $otp, string $resetUrl): bool {
        $subject = "=?UTF-8?B?" . base64_encode("V-Phone Store - Mã xác thực đặt lại mật khẩu: $otp") . "?=";
        
        $htmlContent = '
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Đặt lại mật khẩu V-Phone Store</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #333333; line-height: 1.6;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f4f6f9; padding: 40px 15px;">
                <tr>
                    <td align="center">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 560px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                            <!-- Header -->
                            <tr>
                                <td style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); padding: 32px 30px; text-align: center;">
                                    <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">V-PHONE STORE</h1>
                                    <p style="color: #bfdbfe; margin: 6px 0 0; font-size: 14px;">Hệ Thống Bán Lẻ Flagship Smartphone 2026</p>
                                </td>
                            </tr>
                            <!-- Body -->
                            <tr>
                                <td style="padding: 36px 30px;">
                                    <h2 style="font-size: 20px; font-weight: 700; color: #1e293b; margin: 0 0 16px;">Yêu cầu đặt lại mật khẩu</h2>
                                    <p style="color: #475569; font-size: 15px; margin: 0 0 20px;">
                                        Xin chào <strong>' . htmlspecialchars($recipientName) . '</strong>,
                                    </p>
                                    <p style="color: #475569; font-size: 15px; margin: 0 0 24px;">
                                        Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản V-Phone liên kết với email <strong>' . htmlspecialchars($toEmail) . '</strong>. Vui lòng sử dụng mã xác thực (OTP) bên dưới:
                                    </p>
                                    
                                    <!-- OTP Box -->
                                    <div style="background: #f0fdf4; border: 2px dashed #86efac; border-radius: 12px; padding: 20px; text-align: center; margin: 0 0 24px;">
                                        <div style="font-size: 13px; font-weight: 600; color: #15803d; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Mã OTP xác thực của bạn</div>
                                        <div style="font-size: 36px; font-weight: 900; color: #16a34a; letter-spacing: 8px; font-family: monospace;">' . $otp . '</div>
                                        <div style="font-size: 13px; color: #64748b; margin-top: 8px;">Hiệu lực trong <strong>15 phút</strong> (Không chia sẻ mã này cho bất kỳ ai)</div>
                                    </div>

                                    <p style="color: #475569; font-size: 14px; margin: 0 0 20px; text-align: center;">
                                        Hoặc bạn có thể bấm trực tiếp vào nút dưới đây để đổi mật khẩu:
                                    </p>

                                    <!-- CTA Button -->
                                    <div style="text-align: center; margin: 0 0 28px;">
                                        <a href="' . htmlspecialchars($resetUrl) . '" target="_blank" style="display: inline-block; background: #0066cc; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 700; padding: 14px 32px; border-radius: 50px; box-shadow: 0 4px 14px rgba(0, 102, 204, 0.35);">
                                            ĐẶT LẠI MẬT KHẨU NGAY
                                        </a>
                                    </div>

                                    <div style="border-top: 1px solid #f1f5f9; padding-top: 20px; color: #94a3b8; font-size: 13px;">
                                        <p style="margin: 0 0 8px;">Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email hoặc đổi mật khẩu để bảo vệ tài khoản.</p>
                                        <p style="margin: 0;">Trân trọng,<br><strong>Đội ngũ Chăm sóc khách hàng V-Phone Store</strong></p>
                                    </div>
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 12px;">
                                    © 2026 V-Phone Store. Tất cả các quyền được bảo lưu.<br>
                                    Hotline CSKH: 1900 8888 | Website: localhost/vphone-store
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: V-Phone Store <no-reply@vphone.vn>',
            'Reply-To: cskh@vphone.vn',
            'X-Mailer: PHP/' . phpversion()
        ];

        // Thử gửi qua mail()
        $sent = false;
        try {
            $sent = @mail($toEmail, $subject, $htmlContent, implode("\r\n", $headers));
        } catch (Throwable $e) {
            $sent = false;
        }

        return (bool)$sent;
    }
}
