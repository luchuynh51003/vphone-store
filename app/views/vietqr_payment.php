<?php
$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
$order = $order ?? [];
$orderId = (int)($order['id'] ?? 0);
$totalMoney = (float)($order['total_money'] ?? 0);
$bankConfig = $bankConfig ?? [];
$orderDetails = $orderDetails ?? [];
$qrUrl = $qrUrl ?? '';
$transferContent = trim($transferContent ?? ('VPHONE ' . $orderId));
$pageTitle = $pageTitle ?? ('Thanh toán VietQR - Đơn hàng #VP-' . $orderId);

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<style>
/* ==========================================================================
   VIETQR PAYMENT GATEWAY LUXURY FINTECH THEME
   ========================================================================== */
.vqr-gateway-container {
    max-width: 1040px;
    margin: 0 auto;
    padding-top: 10px;
    padding-bottom: 50px;
}

/* Card tổng thể */
.vqr-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 28px;
    box-shadow: 0 20px 50px -10px rgba(15, 23, 42, 0.08), 0 0 1px 1px rgba(15, 23, 42, 0.02);
    overflow: hidden;
    position: relative;
}

/* Header sang trọng chuẩn Fintech */
.vqr-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0284c7 100%);
    padding: 24px 32px;
    color: #ffffff;
    position: relative;
    overflow: hidden;
}
.vqr-header::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.15) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.vqr-header-title {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    margin-bottom: 2px;
}

/* Bộ đếm thời gian công nghệ */
.vqr-timer-pill {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 9999px;
    padding: 8px 18px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #ffffff;
    font-size: 0.85rem;
    font-weight: 600;
}
.vqr-timer-clock {
    font-family: 'SF Mono', Consolas, Monaco, monospace;
    font-weight: 800;
    font-size: 1rem;
    color: #38bdf8;
    letter-spacing: 1px;
}

/* Khung hiển thị Mã QR chuẩn Fintech */
.vqr-qr-panel {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    padding: 24px;
    text-align: center;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.vqr-qr-card-white {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 20px;
    padding: 16px;
    box-shadow: 0 12px 30px -5px rgba(15, 23, 42, 0.08);
    position: relative;
    max-width: 290px;
    width: 100%;
    margin-bottom: 18px;
}
.vqr-qr-card-white img {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 12px;
}

/* 4 góc định vị camera quét mã */
.qr-target-corner {
    position: absolute;
    width: 18px;
    height: 18px;
    border-color: #0284c7;
    pointer-events: none;
}
.qr-target-corner.tl { top: 10px; left: 10px; border-top: 3px solid #0284c7; border-left: 3px solid #0284c7; border-top-left-radius: 6px; }
.qr-target-corner.tr { top: 10px; right: 10px; border-top: 3px solid #0284c7; border-right: 3px solid #0284c7; border-top-right-radius: 6px; }
.qr-target-corner.bl { bottom: 10px; left: 10px; border-bottom: 3px solid #0284c7; border-left: 3px solid #0284c7; border-bottom-left-radius: 6px; }
.qr-target-corner.br { bottom: 10px; right: 10px; border-bottom: 3px solid #0284c7; border-right: 3px solid #0284c7; border-bottom-right-radius: 6px; }

/* Tia quét laser chuyển động tinh tế */
.vqr-laser-line {
    position: absolute;
    left: 20px;
    right: 20px;
    height: 2px;
    background: linear-gradient(90deg, transparent, #0284c7, #38bdf8, transparent);
    box-shadow: 0 0 10px #38bdf8;
    animation: laserScan 2.4s ease-in-out infinite alternate;
    pointer-events: none;
}
@keyframes laserScan {
    0% { top: 25px; opacity: 0.2; }
    50% { opacity: 1; }
    100% { top: calc(100% - 25px); opacity: 0.2; }
}

/* Tín hiệu Live Pulse */
.vqr-live-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 700;
}
.vqr-live-dot {
    width: 8px;
    height: 8px;
    background-color: #10b981;
    border-radius: 50%;
    position: relative;
}
.vqr-live-dot::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background-color: #10b981;
    border-radius: 50%;
    animation: livePulse 1.8s infinite;
}
@keyframes livePulse {
    0% { transform: scale(1); opacity: 0.8; }
    100% { transform: scale(2.8); opacity: 0; }
}

/* Thẻ tổng tiền phong cách Luxury */
.vqr-amount-hero {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 18px 24px;
    margin-bottom: 20px;
}
.vqr-amount-val {
    font-size: 2rem;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1.1;
}

/* Ô thông tin sao chép tương tác */
.vqr-data-row {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 12px 18px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.vqr-data-row:hover {
    border-color: #cbd5e1;
    background: #fbfcfe;
    transform: translateY(-1px);
}
.vqr-data-row.highlight {
    background: #f0fdf4;
    border-color: #86efac;
}
.vqr-data-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}
.vqr-data-value {
    font-family: 'SF Mono', Consolas, Monaco, monospace;
    font-weight: 800;
    font-size: 1.1rem;
    color: #0f172a;
}
.vqr-data-value.primary-code {
    color: #0284c7;
    font-size: 1.25rem;
}

/* Nút sao chép cao cấp */
.btn-vqr-copy {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 6px 14px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-vqr-copy:hover {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
}
.btn-vqr-copy.copied {
    background: #10b981;
    color: #ffffff;
    border-color: #10b981;
}

/* Hộp Developer Simulator hiện đại (Console style) */
.vqr-dev-console {
    background: #0f172a;
    border-radius: 20px;
    padding: 18px 24px;
    color: #ffffff;
    margin-top: 24px;
    box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.2);
}
.btn-vqr-simulate {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    padding: 10px 20px;
    font-weight: 800;
    font-size: 0.88rem;
    transition: all 0.2s ease;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.35);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}
.btn-vqr-simulate:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
    color: #ffffff;
}

/* Màn hình thành công đỉnh cao */
.vqr-success-card {
    display: none;
    background: #ffffff;
    border-radius: 28px;
    box-shadow: 0 25px 60px -15px rgba(16, 185, 129, 0.25);
    border: 2px solid #10b981;
    padding: 48px 32px;
    text-align: center;
    animation: successFadeIn 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes successFadeIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.vqr-success-check {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    background: #ecfdf5;
    border: 3px solid #10b981;
    color: #10b981;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2.8rem;
    margin-bottom: 24px;
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
}
</style>

<div class="container vqr-gateway-container">
    <!-- Stepper Tiến trình thanh toán -->
    <div class="d-flex justify-content-center mb-4">
        <div class="checkout-stepper-wrapper">
            <a href="index.php?page=cart" class="step-badge step-completed text-decoration-none">
                <span class="step-circle"><i class="fa-solid fa-check"></i></span>
                <span class="step-text">Giỏ hàng</span>
            </a>

            <div class="step-connector active"></div>

            <div class="step-badge step-completed">
                <span class="step-circle"><i class="fa-solid fa-check"></i></span>
                <span class="step-text">Đặt hàng</span>
            </div>

            <div class="step-connector active"></div>

            <div class="step-badge step-active" id="stepBadgeFinal">
                <span class="step-circle"><i class="fa-solid fa-qrcode"></i></span>
                <span class="step-text">Quét mã VietQR</span>
            </div>
        </div>
    </div>

    <!-- MÀN HÌNH XÁC NHẬN THÀNH CÔNG (TỰ ĐỘNG HIỆN KHI THANH TOÁN XONG) -->
    <div class="vqr-success-card col-lg-8 mx-auto" id="paymentSuccessOverlay">
        <div class="vqr-success-check">
            <i class="fa-solid fa-check"></i>
        </div>
        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 fw-bold mb-2">
            <i class="fa-solid fa-bolt me-1"></i>Đã nhận được tiền thành công 100%
        </span>
        <h3 class="fw-black text-dark mb-2">Thanh Toán VietQR Hoàn Tất!</h3>
        <p class="text-secondary mb-4">
            Đơn hàng <strong>#VP-<?= $orderId ?></strong> đã được chuyển sang bộ phận vận hành đóng gói và giao hỏa tốc.
        </p>

        <div class="alert alert-light border rounded-4 text-start p-3 p-md-4 mb-4 small shadow-sm col-md-10 mx-auto">
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                <span class="text-muted">Mã đơn hàng:</span>
                <span class="fw-bold text-primary">#VP-<?= $orderId ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                <span class="text-muted">Mã giao dịch ngân hàng:</span>
                <span class="fw-bold text-dark font-monospace" id="displayTransCode">-</span>
            </div>
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                <span class="text-muted">Số tiền đã thanh toán:</span>
                <span class="fw-black text-danger fs-6"><?= number_format($totalMoney, 0, ',', '.') ?> VNĐ</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">Thời gian xác nhận:</span>
                <span class="fw-bold text-success" id="displayPaidTime"><?= date('H:i:s d/m/Y') ?></span>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="index.php?page=orders&order_code=<?= $orderId ?>&phone=<?= rawurlencode($order['phone'] ?? '') ?>" class="btn btn-primary rounded-pill px-4 fw-bold py-2.5 shadow-sm">
                <i class="fa-solid fa-truck-ramp-box me-2"></i>Theo dõi hành trình đơn hàng
            </a>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold py-2.5">
                <i class="fa-solid fa-store me-2"></i>Tiếp tục mua sắm
            </a>
        </div>
    </div>

    <!-- CỔNG THANH TOÁN VIETQR CHÍNH -->
    <div class="vqr-card" id="vietqrMainBox">
        <!-- Header -->
        <div class="vqr-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-white text-dark rounded-pill fw-bold px-2.5 py-1 small">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i>Cổng thanh toán tự động
                    </span>
                    <span class="badge rounded-pill px-2.5 py-1 small font-monospace" style="background: rgba(255, 255, 255, 0.2); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.35);">
                        #VP-<?= $orderId ?>
                    </span>
                </div>
                <h4 class="vqr-header-title text-white">Chuyển Khoản Qua Mã VietQR 24/7</h4>
                <small class="text-white-50">Hệ thống ngân hàng Napas tự động nhận diện giao dịch chỉ trong 3 - 5 giây</small>
            </div>

            <div class="vqr-timer-pill" id="countdownWrapper">
                <i class="fa-regular fa-clock"></i>
                <span>Thời hạn thanh toán:</span>
                <span class="vqr-timer-clock" id="countdownClock">15:00</span>
            </div>
        </div>

        <!-- Nội dung chính 2 cột -->
        <div class="p-4 p-md-5">
            <div class="row g-4 align-items-stretch">
                <!-- Cột trái: Card Mã QR Đẳng Cấp -->
                <div class="col-lg-5">
                    <div class="vqr-qr-panel">
                        <div class="vqr-qr-card-white">
                            <span class="qr-target-corner tl"></span>
                            <span class="qr-target-corner tr"></span>
                            <span class="qr-target-corner bl"></span>
                            <span class="qr-target-corner br"></span>
                            <div class="vqr-laser-line"></div>
                            <img src="<?= htmlspecialchars($qrUrl) ?>" alt="Mã VietQR #VP-<?= $orderId ?>" id="vietqrImage">
                        </div>

                        <div class="vqr-live-indicator mb-3">
                            <span class="vqr-live-dot"></span>
                            <span id="autoStatusText">Đang chờ hệ thống ghi nhận giao dịch...</span>
                        </div>

                        <div class="d-flex gap-2 justify-content-center flex-wrap w-100">
                            <a href="<?= htmlspecialchars($qrUrl) ?>" download="VietQR_VP_<?= $orderId ?>.png" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.8rem;">
                                <i class="fa-solid fa-download me-1.5"></i>Tải ảnh mã QR
                            </a>
                            <button type="button" class="btn btn-light border btn-sm rounded-pill px-3 py-1.5 fw-bold text-secondary" style="font-size: 0.8rem;" onclick="checkStatusManual()">
                                <i class="fa-solid fa-rotate me-1.5"></i>Làm mới kiểm tra
                            </button>
                        </div>

                        <div class="mt-3 pt-3 border-top w-100 text-center" style="font-size: 0.76rem; color: #64748b;">
                            <i class="fa-solid fa-shield-halved text-success me-1"></i>Quét bằng mọi App Ngân hàng Việt Nam (VCB, MB, Techcombank, BIDV, ACB...)
                        </div>
                    </div>
                </div>

                <!-- Cột phải: Thông tin chuyển khoản & Tóm tắt đơn hàng -->
                <div class="col-lg-7">
                    <!-- Thẻ tổng tiền phong cách Fintech -->
                    <div class="vqr-amount-hero d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                Tổng số tiền cần chuyển
                            </div>
                            <div class="vqr-amount-val text-danger">
                                <?= number_format($totalMoney, 0, ',', '.') ?> <span class="fs-4 fw-bold">VNĐ</span>
                            </div>
                        </div>
                        <button type="button" class="btn-vqr-copy" onclick="copyValue('<?= (int)round($totalMoney) ?>', this, 'Đã sao chép số tiền!')">
                            <i class="fa-regular fa-copy"></i>
                            <span>Sao chép số tiền</span>
                        </button>
                    </div>

                    <!-- Danh sách các trường dữ liệu thanh toán -->
                    <div class="d-flex flex-column gap-2.5 mb-4">
                        <!-- Ngân hàng thụ hưởng -->
                        <div class="vqr-data-row">
                            <div class="min-w-0">
                                <div class="vqr-data-label">Ngân hàng thụ hưởng</div>
                                <div class="fw-bold text-dark d-flex align-items-center gap-2 mt-0.5">
                                    <span class="badge bg-primary text-white px-2 py-0.5 fw-bold rounded-2"><?= htmlspecialchars($bankConfig['bank_id'] ?? 'MB') ?></span>
                                    <span class="fs-6"><?= htmlspecialchars($bankConfig['bank_name'] ?? 'Ngân hàng Quân Đội (MB Bank)') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Số tài khoản -->
                        <div class="vqr-data-row">
                            <div class="min-w-0">
                                <div class="vqr-data-label">Số tài khoản thụ hưởng</div>
                                <div class="vqr-data-value text-dark" id="valAccountNo"><?= htmlspecialchars($bankConfig['account_no'] ?? '0388888899') ?></div>
                            </div>
                            <button type="button" class="btn-vqr-copy" onclick="copyValue('<?= htmlspecialchars($bankConfig['account_no'] ?? '0388888899') ?>', this, 'Đã sao chép số tài khoản!')">
                                <i class="fa-regular fa-copy"></i>
                                <span>Sao chép</span>
                            </button>
                        </div>

                        <!-- Tên người thụ hưởng -->
                        <div class="vqr-data-row">
                            <div class="min-w-0">
                                <div class="vqr-data-label">Tên chủ tài khoản</div>
                                <div class="fw-bold text-dark mt-0.5 text-uppercase fs-6"><?= htmlspecialchars($bankConfig['account_name'] ?? 'V-PHONE STORE') ?></div>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1 small fw-bold">
                                <i class="fa-solid fa-circle-check me-1"></i>Chính chủ
                            </span>
                        </div>

                        <!-- Nội dung chuyển khoản BẮT BUỘC -->
                        <div class="vqr-data-row highlight">
                            <div class="min-w-0">
                                <div class="vqr-data-label text-success d-flex align-items-center gap-1">
                                    <i class="fa-solid fa-triangle-exclamation text-warning"></i>
                                    <span>Nội dung chuyển khoản (Bắt buộc giữ nguyên):</span>
                                </div>
                                <div class="vqr-data-value primary-code" id="valTransferContent"><?= htmlspecialchars($transferContent) ?></div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 py-1.5 fw-bold shadow-sm" onclick="copyValue('<?= htmlspecialchars($transferContent) ?>', this, 'Đã sao chép nội dung!')">
                                <i class="fa-regular fa-copy me-1"></i>
                                <span>Sao chép</span>
                            </button>
                        </div>
                    </div>

                    <!-- Tóm tắt sản phẩm trong đơn -->
                    <?php if (!empty($orderDetails)): ?>
                        <div class="border rounded-4 p-3 bg-light mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold text-dark text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-mobile-screen-button text-primary me-1"></i>Mặt hàng thanh toán (<?= count($orderDetails) ?>)
                                </span>
                                <span class="badge bg-white text-secondary border rounded-pill small">Giao hàng hỏa tốc</span>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($orderDetails as $it): ?>
                                    <div class="d-flex align-items-center justify-content-between small bg-white p-2 rounded-3 border">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold">x<?= $it['quantity'] ?></span>
                                            <span class="fw-bold text-dark text-truncate" style="max-width: 260px;"><?= htmlspecialchars($it['product_name']) ?></span>
                                        </div>
                                        <span class="fw-bold text-danger flex-shrink-0"><?= number_format((float)$it['total_price'], 0, ',', '.') ?> VNĐ</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- CONSOLE DEVELOPER TESTER (PHONG CÁCH FINTECH SANG TRỌNG) -->
                    <?php if (!empty($bankConfig['demo_mode'])): ?>
                        <div class="vqr-dev-console">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25 rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 0.7rem;">
                                            <i class="fa-solid fa-code me-1"></i>TEST SIMULATOR
                                        </span>
                                        <span class="small text-white-50">Dành cho Tester & Đồ án</span>
                                    </div>
                                    <div class="small text-light opacity-75">
                                        Mô phỏng ngân hàng gửi biến động số dư thành công (không mất tiền thật).
                                    </div>
                                </div>
                                <button type="button" class="btn-vqr-simulate" id="btnSimulatePay" onclick="simulatePaymentAjax()">
                                    <i class="fa-solid fa-bolt"></i>
                                    <span>Giả lập đã nhận tiền</span>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-3 bg-light border-top text-center text-secondary small d-flex flex-wrap justify-content-between align-items-center px-4">
            <div>
                <i class="fa-solid fa-lock text-success me-1"></i>Kết nối mã hóa bảo mật SSL 256-bit chuẩn Napas VietQR
            </div>
            <div>
                Hỗ trợ 24/7: <a href="tel:18006868" class="text-primary text-decoration-none fw-bold">1800 6868</a>
            </div>
        </div>
    </div>
</div>

<!-- Toast thông báo chép dữ liệu -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="copyToast" class="toast align-items-center text-bg-dark border-0 rounded-4 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body small fw-bold" id="copyToastText">
                <i class="fa-solid fa-circle-check text-success me-2"></i>Đã sao chép!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
const ORDER_ID = <?= (int)$orderId ?>;
let isPaymentDone = false;
let pollingTimer = null;
let countdownRemaining = <?= (int)($bankConfig['timeout_minutes'] ?? 15) ?> * 60;

function playSuccessSound() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const now = audioCtx.currentTime;
        
        const osc1 = audioCtx.createOscillator();
        const gain1 = audioCtx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(587.33, now); // D5
        gain1.gain.setValueAtTime(0.2, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        osc1.connect(gain1);
        gain1.connect(audioCtx.destination);
        osc1.start(now);
        osc1.stop(now + 0.35);

        const osc2 = audioCtx.createOscillator();
        const gain2 = audioCtx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(880, now + 0.15); // A5
        gain2.gain.setValueAtTime(0.3, now + 0.15);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.8);
        osc2.connect(gain2);
        gain2.connect(audioCtx.destination);
        osc2.start(now + 0.15);
        osc2.stop(now + 0.8);
    } catch (e) {}
}

function fireConfetti() {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js';
    script.onload = () => {
        if (typeof confetti === 'function') {
            confetti({
                particleCount: 140,
                spread: 80,
                origin: { y: 0.55 }
            });
        }
    };
    document.body.appendChild(script);
}

function handlePaymentSuccess(data) {
    if (isPaymentDone) return;
    isPaymentDone = true;
    if (pollingTimer) clearInterval(pollingTimer);

    playSuccessSound();
    fireConfetti();

    const mainBox = document.getElementById('vietqrMainBox');
    const overlay = document.getElementById('paymentSuccessOverlay');
    const stepBadge = document.getElementById('stepBadgeFinal');
    const dispTrans = document.getElementById('displayTransCode');
    const dispTime = document.getElementById('displayPaidTime');

    if (mainBox) mainBox.style.display = 'none';
    if (overlay) overlay.style.display = 'block';
    if (stepBadge) {
        stepBadge.classList.remove('step-active');
        stepBadge.classList.add('step-completed');
        stepBadge.innerHTML = '<span class="step-circle"><i class="fa-solid fa-check"></i></span><span class="step-text">Hoàn tất</span>';
    }

    if (dispTrans && data.transaction_code) dispTrans.innerText = data.transaction_code;
    if (dispTime && data.paid_at) dispTime.innerText = data.paid_at;
}

function checkPaymentStatus() {
    if (isPaymentDone) return;
    fetch('index.php?page=checkout&action=check_payment&order_id=' + ORDER_ID)
        .then(res => res.json())
        .then(data => {
            if (data.is_paid) {
                handlePaymentSuccess(data);
            }
        })
        .catch(() => {});
}

function checkStatusManual() {
    const textEl = document.getElementById('autoStatusText');
    if (textEl) textEl.innerText = 'Đang đồng bộ giao dịch ngân hàng...';
    fetch('index.php?page=checkout&action=check_payment&order_id=' + ORDER_ID)
        .then(res => res.json())
        .then(data => {
            if (data.is_paid) {
                handlePaymentSuccess(data);
            } else {
                if (textEl) textEl.innerText = 'Chưa ghi nhận giao dịch. Vui lòng chuyển đúng nội dung!';
                setTimeout(() => {
                    if (!isPaymentDone && textEl) textEl.innerText = 'Đang chờ hệ thống ghi nhận giao dịch...';
                }, 3000);
            }
        })
        .catch(() => {
            if (textEl) textEl.innerText = 'Đang chờ hệ thống ghi nhận giao dịch...';
        });
}

function simulatePaymentAjax() {
    const btn = document.getElementById('btnSimulatePay');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Đang xử lý...';
    }

    fetch('index.php?page=checkout&action=simulate_payment&order_id=' + ORDER_ID, {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            checkPaymentStatus();
        } else {
            alert(data.message || 'Lỗi giả lập thanh toán.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-bolt"></i><span>Giả lập đã nhận tiền</span>';
            }
        }
    })
    .catch(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-bolt"></i><span>Giả lập đã nhận tiền</span>';
        }
    });
}

function copyValue(text, btnElement, successMsg) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => onCopyDone(btnElement, successMsg));
    } else {
        const tempInp = document.createElement('input');
        tempInp.value = text;
        document.body.appendChild(tempInp);
        tempInp.select();
        document.execCommand('copy');
        document.body.removeChild(tempInp);
        onCopyDone(btnElement, successMsg);
    }
}

function onCopyDone(btnElement, successMsg) {
    showToast(successMsg);
    if (btnElement) {
        const originalHtml = btnElement.innerHTML;
        btnElement.classList.add('copied');
        btnElement.innerHTML = '<i class="fa-solid fa-check"></i><span>Đã chép</span>';
        setTimeout(() => {
            btnElement.classList.remove('copied');
            btnElement.innerHTML = originalHtml;
        }, 2000);
    }
}

function showToast(msg) {
    const toastEl = document.getElementById('copyToast');
    const toastText = document.getElementById('copyToastText');
    if (toastText) toastText.innerHTML = '<i class="fa-solid fa-circle-check text-success me-2"></i>' + msg;
    if (toastEl && window.bootstrap) {
        const toast = new bootstrap.Toast(toastEl, { delay: 2000 });
        toast.show();
    }
}

function updateCountdown() {
    if (countdownRemaining <= 0) {
        const clock = document.getElementById('countdownClock');
        if (clock) clock.innerText = '00:00 (Hết hạn)';
        return;
    }
    countdownRemaining--;
    const mins = Math.floor(countdownRemaining / 60);
    const secs = countdownRemaining % 60;
    const clock = document.getElementById('countdownClock');
    if (clock) {
        clock.innerText = (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    pollingTimer = setInterval(checkPaymentStatus, 2500);
    checkPaymentStatus();
    setInterval(updateCountdown, 1000);
});
</script>

<?php require_once 'app/views/includes/footer.php'; ?>
