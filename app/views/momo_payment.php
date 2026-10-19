<?php
$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
$order = $order ?? [];
$orderId = (int)($order['id'] ?? 0);
$totalMoney = (float)($order['total_money'] ?? 0);
$momoPayUrl = $momoPayUrl ?? '';
$momoQrCode = $momoQrCode ?? '';
$pageTitle = $pageTitle ?? ('Cổng thanh toán MoMo Sandbox - Đơn hàng #VP-' . $orderId);

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<style>
.momo-card-wrapper {
    background: #ffffff;
    border: 1px solid #fed7e2;
    border-radius: 24px;
    box-shadow: 0 15px 40px rgba(165, 0, 100, 0.08);
    overflow: hidden;
}
.momo-header-banner {
    background: linear-gradient(135deg, #a50064 0%, #d82d8b 100%);
    color: #ffffff;
    padding: 24px 30px;
}
.momo-qr-frame {
    background: #ffffff;
    padding: 18px;
    border-radius: 20px;
    border: 2px dashed #d82d8b;
    display: inline-block;
    position: relative;
    box-shadow: 0 8px 25px rgba(216, 45, 139, 0.15);
}
.momo-qr-frame img {
    max-width: 250px;
    width: 100%;
    height: auto;
    display: block;
    border-radius: 12px;
}
.momo-btn-confirm {
    background: linear-gradient(135deg, #d82d8b 0%, #a50064 100%);
    color: #ffffff;
    border: none;
    border-radius: 50px;
    padding: 14px 28px;
    font-weight: 800;
    font-size: 1.05rem;
    box-shadow: 0 8px 20px rgba(165, 0, 100, 0.3);
    transition: all 0.3s ease;
    cursor: pointer;
    width: 100%;
}
.momo-btn-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(165, 0, 100, 0.45);
    color: #ffffff;
}
.dev-helper-card {
    background: #fff5f8;
    border: 1.5px dashed #f687b3;
    border-radius: 18px;
    padding: 18px 22px;
}
.pulse-momo-dot {
    width: 10px;
    height: 10px;
    background-color: #d82d8b;
    border-radius: 50%;
    display: inline-block;
    position: relative;
}
.pulse-momo-dot::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background-color: #d82d8b;
    border-radius: 50%;
    animation: momoPulse 1.8s infinite;
}
@keyframes momoPulse {
    0% { transform: scale(1); opacity: 0.8; }
    100% { transform: scale(2.6); opacity: 0; }
}
</style>

<div class="container my-3 my-md-4">
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
                <span class="step-text">Thanh toán</span>
            </div>

            <div class="step-connector active"></div>

            <div class="step-badge step-active">
                <span class="step-circle"><i class="fa-solid fa-wallet"></i></span>
                <span class="step-text">Cổng MoMo</span>
            </div>
        </div>
    </div>

    <div class="momo-card-wrapper col-lg-9 mx-auto">
        <!-- Header Banner -->
        <div class="momo-header-banner d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-white text-danger fw-bold rounded-pill px-3 py-1" style="color: #a50064 !important;">
                        <i class="fa-solid fa-wallet me-1"></i>MoMo Developer Sandbox
                    </span>
                    <span class="badge bg-black bg-opacity-25 text-white rounded-pill px-3 py-1">
                        Đơn hàng #VP-<?= $orderId ?>
                    </span>
                </div>
                <h4 class="fw-bold mb-0 text-white">Cổng Thanh Toán MoMo Trực Tuyến</h4>
                <small class="text-white-50">Môi trường thử nghiệm Developer (Không trừ tiền thật)</small>
            </div>

            <div class="text-end">
                <span class="badge rounded-pill px-3 py-1.5 fw-bold" style="background: rgba(255, 255, 255, 0.2); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.35);">
                    <span class="pulse-momo-dot me-1.5" style="background:#fff;"></span>Sẵn sàng thanh toán
                </span>
            </div>
        </div>

        <div class="p-4 p-md-5">
            <div class="row g-4 align-items-center">
                <!-- Cột trái: Mã QR MoMo -->
                <div class="col-md-5 text-center">
                    <div class="momo-qr-frame mb-3">
                        <?php
                            // Sinh ảnh QR đại diện cho mã thanh toán MoMo
                            $qrImgUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($momoPayUrl ?: ("https://test-payment.momo.vn/order/" . $orderId));
                        ?>
                        <img src="<?= htmlspecialchars($qrImgUrl) ?>" alt="Mã MoMo QR Đơn hàng #VP-<?= $orderId ?>">
                    </div>

                    <div class="small text-muted mb-2">
                        <i class="fa-solid fa-circle-info text-danger me-1"></i>Mã thanh toán giả lập MoMo v2
                    </div>

                    <?php if (!empty($momoPayUrl)): ?>
                        <a href="<?= htmlspecialchars($momoPayUrl) ?>" target="_blank" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold" style="font-size: 0.78rem;">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Mở cổng test-payment.momo.vn gốc
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Cột phải: Thông tin đơn hàng & Nút Xác Nhận Tức Thì -->
                <div class="col-md-7">
                    <div class="p-3 bg-light rounded-4 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-secondary small fw-bold">Số tiền thanh toán:</span>
                            <span class="badge bg-danger text-white rounded-pill px-2.5 py-0.5 fw-bold" style="background: #a50064 !important;">
                                MoMo Sandbox
                            </span>
                        </div>
                        <div class="fs-2 fw-black text-danger mb-0" style="color: #a50064 !important;">
                            <?= number_format($totalMoney, 0, ',', '.') ?> <span class="fs-5">VNĐ</span>
                        </div>
                    </div>

                    <div class="border rounded-4 p-3 bg-white mb-3 small">
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Nhà cung cấp:</span>
                            <span class="fw-bold text-dark">V-Phone Store (Đối tác MoMo)</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted">Mã đơn hàng:</span>
                            <span class="fw-bold text-primary">#VP-<?= $orderId ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Nội dung thanh toán:</span>
                            <span class="fw-medium text-dark text-truncate" style="max-width: 220px;">Thanh toán đơn hàng #VP-<?= $orderId ?></span>
                        </div>
                    </div>

                    <!-- HỘP XÁC NHẬN GIẢ LẬP 1-CLICK -->
                    <div class="dev-helper-card mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-danger text-white rounded-pill px-2.5 py-0.5 fw-bold" style="background: #a50064 !important;">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Xác nhận thử nghiệm (0 VNĐ)
                            </span>
                            <small class="text-secondary fw-semibold">Dành cho Tester & Chấm đồ án</small>
                        </div>
                        <p class="small text-secondary mb-3">
                            Vì cổng MoMo Sandbox trên web chỉ hiển thị mã QR (cần app MoMo Developer chuyên dụng), bạn có thể bấm nút bên dưới để <strong>mô phỏng sự kiện thanh toán thành công tức thì</strong>:
                        </p>

                        <form method="POST" action="index.php?page=checkout&action=momo_confirm" id="momoConfirmForm">
                            <input type="hidden" name="order_id" value="<?= $orderId ?>">
                            <button type="submit" class="momo-btn-confirm d-flex align-items-center justify-content-center gap-2" id="btnMomoSimulate">
                                <i class="fa-solid fa-circle-check fs-5"></i>
                                <span>XÁC NHẬN THANH TOÁN MOMO THÀNH CÔNG</span>
                            </button>
                        </form>
                    </div>

                    <div class="text-center">
                        <a href="index.php?page=orders&order_code=<?= $orderId ?>" class="text-secondary small text-decoration-none">
                            <i class="fa-solid fa-arrow-left me-1"></i>Hủy và quay lại theo dõi đơn hàng
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-3 bg-light border-top text-center text-secondary small px-4">
            <i class="fa-solid fa-shield-halved text-success me-1"></i>Giao dịch thử nghiệm được bảo vệ trong môi trường MoMo Sandbox. Không có bất kỳ giao dịch tiền thật nào phát sinh.
        </div>
    </div>
</div>

<script>
document.getElementById('momoConfirmForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnMomoSimulate');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Đang xác thực với MoMo...';
    }
});
</script>

<?php require_once 'app/views/includes/footer.php'; ?>

