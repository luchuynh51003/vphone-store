<?php
$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
$orderSuccess = $orderSuccess ?? false;
$orderId = $orderId ?? 0;
$isBuyNowMode = $isBuyNowMode ?? (isset($_GET['mode']) && $_GET['mode'] === 'buy_now');
$itemsToCheckout = $itemsToCheckout ?? [];

// Lọc các item hợp lệ
$itemsToCheckout = array_values(array_filter($itemsToCheckout, static fn($i) => !empty($i) && is_array($i)));

// TỰ ĐỘNG PHỤC HỒI GIÁ NẾU SESSION BỊ THIẾU HOẶC = 0
if (!empty($itemsToCheckout)) {
    foreach ($itemsToCheckout as &$it) {
        if (!isset($it['price']) || (float)$it['price'] <= 0) {
            $pId = (int)($it['id'] ?? 0);
            if ($pId > 0 && isset($pdo)) {
                $pRow = $pdo->query("SELECT price, sale_price FROM products WHERE id = " . $pId)->fetch();
                if ($pRow) {
                    $bPrice = ($pRow['sale_price'] > 0 && $pRow['sale_price'] < $pRow['price']) ? $pRow['sale_price'] : $pRow['price'];
                    $it['price'] = (float)$bPrice;
                }
            }
        }
    }
    unset($it);
}

$subtotal = 0;
foreach ($itemsToCheckout as $item) {
    $itemPrice = (float)($item['price'] ?? 0);
    $itemQty = (int)($item['quantity'] ?? 1);
    $subtotal += $itemPrice * $itemQty;
}

$discountAmount = $discountAmount ?? 0;
$appliedVoucher = $appliedVoucher ?? null;
$voucherMessage = $voucherMessage ?? '';
$totalMoney = max(0, $subtotal - $discountAmount);
$paymentMethod = $paymentMethod ?? 'COD';
$checkoutError = $checkoutError ?? '';
$pageTitle = $pageTitle ?? 'Thanh toán đơn hàng - V-Phone Store';

// Đọc thông tin đã lưu trong bản nháp (nếu có)
$formDraft = $_SESSION['checkout_form_draft'] ?? [];
$deliveryName = $formDraft['fullname'] ?? ($currentUser['fullname'] ?? '');
$deliveryPhone = $formDraft['phone'] ?? ($currentUser['phone'] ?? '');
$deliveryAddress = $formDraft['address'] ?? ($currentUser['address'] ?? '');
$deliveryNote = $formDraft['note'] ?? '';

// Nếu chưa đặt hàng thành công và danh sách thanh toán rỗng thì chuyển về giỏ hàng
if (!$orderSuccess && empty($itemsToCheckout)) {
    header('Location: index.php?page=cart');
    exit;
}

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<div class="container my-3 my-md-4">
    <!-- TIẾN TRÌNH ĐƠN HÀNG STEPPER CHUẨN HIỆN ĐẠI (1 2 3 KHÔNG LẶP SỐ) -->
    <div class="d-flex justify-content-center mb-4">
        <div class="checkout-stepper-wrapper">
            <!-- Bước 1: Giỏ hàng -->
            <a href="index.php?page=cart" class="step-badge step-completed text-decoration-none" title="Xem lại giỏ hàng">
                <span class="step-circle"><i class="fa-solid fa-check"></i></span>
                <span class="step-text">Giỏ hàng</span>
            </a>

            <div class="step-connector active"></div>

            <!-- Bước 2: Thanh toán -->
            <div class="step-badge <?= $orderSuccess ? 'step-completed' : 'step-active' ?>">
                <span class="step-circle"><?= $orderSuccess ? '<i class="fa-solid fa-check"></i>' : '2' ?></span>
                <span class="step-text">Thanh toán</span>
            </div>

            <div class="step-connector <?= $orderSuccess ? 'active' : '' ?>"></div>

            <!-- Bước 3: Hoàn tất -->
            <div class="step-badge <?= $orderSuccess ? 'step-active' : 'step-pending' ?>">
                <span class="step-circle"><?= $orderSuccess ? '<i class="fa-solid fa-check"></i>' : '3' ?></span>
                <span class="step-text">Hoàn tất</span>
            </div>
        </div>
    </div>

    <?php if ($orderSuccess): ?>
        <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm text-center col-lg-7 mx-auto border my-4">
            <div style="width: 84px; height: 84px; border-radius: 50%; background: #ecfdf5; border: 3px solid #10b981; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.25);">
                <i class="fa-solid fa-check text-success fs-1"></i>
            </div>
            <h3 class="fw-black text-dark mb-2">Đặt Hàng Thành Công!</h3>
            <p class="text-secondary mb-3">Cảm ơn bạn đã tin tưởng V-Phone Store. Mã đơn hàng của bạn là:</p>
            <div class="display-6 fw-bold text-primary mb-4" style="letter-spacing: 1px;">#VP-<?= (int)$orderId ?></div>

            <div class="alert alert-light border rounded-4 text-start small p-3 p-md-4 mb-4">
                <div class="mb-2 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-truck-fast text-primary fs-6"></i>
                    <span>Đơn hàng đang được bộ phận vận hành đóng gói và giao tận nơi hỏa tốc.</span>
                </div>
                <div class="mb-2 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-success fs-6"></i>
                    <span>Áp dụng chính sách <strong>Bảo hành chính hãng 12 tháng - 1 đổi 1 trong 30 ngày</strong>.</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-money-bill-wave text-info fs-6"></i>
                    <span>Hình thức thanh toán: <strong><?= htmlspecialchars($paymentMethod ?? 'COD') ?></strong></span>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-3">
                <a href="index.php" class="btn btn-primary rounded-pill px-4 fw-bold py-2 shadow-sm">
                    <i class="fa-solid fa-store me-2"></i>Tiếp tục mua sắm
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- HEADER TRANG THANH TOÁN -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <a href="<?= $isBuyNowMode ? 'index.php' : 'index.php?page=cart' ?>" class="btn btn-sm btn-light border rounded-pill px-3 py-1 text-secondary fw-semibold">
                        <i class="fa-solid fa-arrow-left me-1"></i><?= $isBuyNowMode ? 'Quay lại cửa hàng' : 'Xem lại giỏ hàng' ?>
                    </a>
                    <?php if ($isBuyNowMode): ?>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                            <i class="fa-solid fa-bolt me-1 text-primary"></i>Mua Ngay Tức Thì
                        </span>
                    <?php else: ?>
                        <span class="badge bg-light text-secondary border rounded-pill px-3 py-1 fw-semibold">
                            <i class="fa-solid fa-bag-shopping me-1 text-primary"></i><?= count($itemsToCheckout) ?> sản phẩm
                        </span>
                    <?php endif; ?>
                </div>
                <h3 class="fw-black text-dark mb-0">
                    <?= $isBuyNowMode ? 'Xác Nhận & Thanh Toán Đơn Hàng' : 'Thanh Toán Đơn Hàng' ?>
                </h3>
                <small class="text-secondary">Vui lòng kiểm tra địa chỉ nhận máy và chọn phương thức thanh toán phù hợp</small>
            </div>
        </div>

        <?php if (!empty($checkoutError)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 py-3 mb-4 shadow-sm" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($checkoutError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=checkout<?= $isBuyNowMode ? '&mode=buy_now' : '' ?>" id="checkoutMainForm">
            <div class="row g-4">
                <!-- CỘT TRÁI: THÔNG TIN GIAO HÀNG & PHƯƠNG THỨC THANH TOÁN -->
                <div class="col-lg-7">
                    <!-- KHỐI 1: THÔNG TIN GIAO HÀNG -->
                    <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <i class="fa-solid fa-location-dot text-primary"></i>Địa Chỉ Nhận Hàng
                            </h5>
                            <span class="badge bg-light text-secondary border rounded-pill small">Giao tận nơi</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark mb-1">Họ và tên người nhận <span class="text-danger">*</span></label>
                            <div class="checkout-input-group">
                                <i class="fa-solid fa-user input-icon"></i>
                                <input type="text" name="fullname" id="customerFullname" class="checkout-form-control" required value="<?= htmlspecialchars($deliveryName) ?>" placeholder="Ví dụ: Nguyễn Văn A">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark mb-1">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                            <div class="checkout-input-group">
                                <i class="fa-solid fa-phone input-icon"></i>
                                <input type="tel" name="phone" id="customerPhone" class="checkout-form-control" required value="<?= htmlspecialchars($deliveryPhone) ?>" placeholder="Ví dụ: 0901234567">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark mb-1">Địa chỉ giao hàng tận nơi <span class="text-danger">*</span></label>
                            <div class="checkout-input-group">
                                <i class="fa-solid fa-map-location-dot input-icon"></i>
                                <input type="text" name="address" id="customerAddress" class="checkout-form-control" required value="<?= htmlspecialchars($deliveryAddress) ?>" placeholder="Số nhà, tên đường, phường/xã, quận/huyện...">
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label small fw-bold text-dark mb-1">Ghi chú cho shipper (Tùy chọn)</label>
                            <div class="checkout-input-group">
                                <i class="fa-solid fa-pen input-icon" style="top: 24px;"></i>
                                <textarea name="note" id="customerNote" rows="2" class="checkout-form-control pt-2" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao 15 phút..."><?= htmlspecialchars($deliveryNote) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- KHỐI 2: PHƯƠNG THỨC THANH TOÁN -->
                    <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <i class="fa-solid fa-credit-card text-primary"></i>Phương Thức Thanh Toán
                            </h5>
                            <span class="small text-secondary"><i class="fa-solid fa-lock text-success me-1"></i>Bảo mật SSL</span>
                        </div>

                        <div class="d-flex flex-column gap-3" id="paymentOptionsGroup">
                            <!-- 1. COD -->
                            <label class="payment-card-luxury <?= $paymentMethod === 'COD' ? 'active' : '' ?>" for="pay_cod">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="custom-radio-circle">
                                        <input type="radio" name="payment_method" value="COD" id="pay_cod" <?= $paymentMethod === 'COD' ? 'checked' : '' ?> class="d-none">
                                        <span class="radio-dot"></span>
                                    </div>
                                    <div class="payment-icon-box bg-success bg-opacity-10 text-success">
                                        <i class="fa-solid fa-hand-holding-dollar fs-5"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="fw-bold text-dark">Thanh toán tiền mặt khi nhận máy (COD)</span>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-0" style="font-size:0.7rem;">Khuyên dùng</span>
                                        </div>
                                        <small class="text-secondary d-block">Kiểm tra máy chuẩn seal, phụ kiện đầy đủ rồi mới gửi tiền cho shipper.</small>
                                    </div>
                                </div>
                            </label>

                            <!-- 2. VietQR -->
                            <label class="payment-card-luxury <?= $paymentMethod === 'Chuyển khoản QR VietQR' ? 'active' : '' ?>" for="pay_qr">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="custom-radio-circle">
                                        <input type="radio" name="payment_method" value="Chuyển khoản QR VietQR" id="pay_qr" <?= $paymentMethod === 'Chuyển khoản QR VietQR' ? 'checked' : '' ?> class="d-none">
                                        <span class="radio-dot"></span>
                                    </div>
                                    <div class="payment-icon-box bg-primary bg-opacity-10 text-primary">
                                        <i class="fa-solid fa-qrcode fs-5"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="fw-bold text-dark">Chuyển khoản ngân hàng qua mã VietQR 24/7</span>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-0" style="font-size:0.7rem;">Miễn phí giao dịch</span>
                                        </div>
                                        <small class="text-secondary d-block">Tự động điền số tiền và mã đơn hàng qua mọi ứng dụng ngân hàng.</small>
                                    </div>
                                </div>
                            </label>

                            <!-- 3. Ví MoMo / ZaloPay -->
                            <label class="payment-card-luxury <?= $paymentMethod === 'Ví điện tử MoMo' ? 'active' : '' ?>" for="pay_momo">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="custom-radio-circle">
                                        <input type="radio" name="payment_method" value="Ví điện tử MoMo" id="pay_momo" <?= $paymentMethod === 'Ví điện tử MoMo' ? 'checked' : '' ?> class="d-none">
                                        <span class="radio-dot"></span>
                                    </div>
                                    <div class="payment-icon-box bg-danger bg-opacity-10 text-danger">
                                        <i class="fa-solid fa-wallet fs-5"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold text-dark">Ví điện tử MoMo / ZaloPay</span>
                                        </div>
                                        <small class="text-secondary d-block">Xác nhận thanh toán tiện lợi và nhanh chóng bằng ví điện tử.</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- CỘT PHẢI: TÓM TẮT ĐƠN HÀNG, VOUCHER, NÚT ĐẶT HÀNG -->
                <div class="col-lg-5">
                    <div class="bg-white p-4 rounded-4 shadow-sm border mb-3 sticky-top" style="top: 80px; z-index: 10;">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <i class="fa-solid fa-receipt text-primary"></i>Tóm Tắt Đơn Hàng
                            </h5>
                            <span class="badge bg-light text-primary border rounded-pill">
                                <?= $isBuyNowMode ? '1 máy mua ngay' : count($itemsToCheckout) . ' sản phẩm' ?>
                            </span>
                        </div>

                        <!-- DANH SÁCH SẢN PHẨM THANH TOÁN -->
                        <div class="d-flex flex-column gap-2 mb-3">
                            <?php foreach ($itemsToCheckout as $item): ?>
                                <?php
                                    $itemPrice = (float)($item['price'] ?? 0);
                                    $itemQty = (int)($item['quantity'] ?? 1);
                                    $itemSubtotal = $itemPrice * $itemQty;
                                ?>
                                <div class="d-flex align-items-center gap-3 p-2 bg-light rounded-3 border">
                                    <div class="bg-white p-1 rounded-3 border d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; flex-shrink:0;">
                                        <img src="<?= htmlspecialchars($item['image'] ?? 'assets/images/products/iphone-16.png') ?>" alt="" style="max-width: 100%; max-height: 100%; object-fit: contain;" onerror="this.onerror=null; this.src='assets/images/products/iphone-16.png';">
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="small fw-bold text-dark text-truncate" title="<?= htmlspecialchars($item['name'] ?? 'Điện thoại') ?>">
                                            <?= htmlspecialchars($item['name'] ?? 'Điện thoại') ?>
                                        </div>
                                        <div class="d-flex gap-1 flex-wrap mt-1">
                                            <span class="badge bg-white text-primary border" style="font-size:0.68rem;"><?= htmlspecialchars($item['rom'] ?? 'Tiêu chuẩn') ?></span>
                                            <span class="badge bg-white text-secondary border" style="font-size:0.68rem;"><?= htmlspecialchars($item['color'] ?? 'Tiêu chuẩn') ?></span>
                                            <span class="badge bg-dark text-white" style="font-size:0.68rem;">x<?= $itemQty ?></span>
                                        </div>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <div class="small fw-bold text-danger"><?= number_format($itemSubtotal, 0, ',', '.') ?> đ</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- KHUNG NHẬP MÃ VOUCHER (HỖ TRỢ AJAX KHÔNG MẤT DỮ LIỆU ĐỊA CHỈ) -->
                        <div class="border rounded-4 p-3 mb-3 bg-light">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label for="voucherInput" class="form-label fw-bold small mb-0 text-dark">
                                    <i class="fa-solid fa-ticket text-primary me-1"></i>Mã giảm giá ưu đãi
                                </label>
                                <?php if ($appliedVoucher): ?>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none small" onclick="removeVoucherAjax()">
                                        <i class="fa-solid fa-xmark me-1"></i>Gỡ mã
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="input-group">
                                <input type="text" id="voucherInput" name="voucher_code" class="form-control text-uppercase fw-bold rounded-start-pill ps-3" placeholder="Nhập mã voucher (VD: WELCOME50)" value="<?= htmlspecialchars($appliedVoucher['code'] ?? '') ?>">
                                <button type="button" class="btn btn-primary rounded-end-pill px-3 fw-bold" id="btnApplyVoucher" onclick="applyVoucherAjax()">
                                    Áp dụng
                                </button>
                            </div>

                            <div id="voucherStatusMessage" class="small mt-2 <?= $appliedVoucher ? 'text-success' : 'text-danger' ?> fw-semibold <?= empty($voucherMessage) ? 'd-none' : '' ?>">
                                <?= htmlspecialchars($voucherMessage) ?>
                            </div>

                            <div class="mt-2 pt-2 border-top">
                                <small class="text-secondary d-block mb-1" style="font-size: 0.75rem;">Gợi ý mã hot:</small>
                                <div class="d-flex gap-1 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-white border rounded-pill px-2 py-0 text-primary fw-semibold" style="font-size: 0.72rem; background: #fff;" onclick="fillAndApplyVoucher('WELCOME50')">
                                        WELCOME50 (-50k)
                                    </button>
                                    <button type="button" class="btn btn-sm btn-white border rounded-pill px-2 py-0 text-primary fw-semibold" style="font-size: 0.72rem; background: #fff;" onclick="fillAndApplyVoucher('VPHONE10')">
                                        VPHONE10 (-10%)
                                    </button>
                                    <button type="button" class="btn btn-sm btn-white border rounded-pill px-2 py-0 text-primary fw-semibold" style="font-size: 0.72rem; background: #fff;" onclick="fillAndApplyVoucher('FLASH20')">
                                        FLASH20 (-20%)
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- CHI TIẾT TÍNH TIỀN -->
                        <div class="d-flex flex-column gap-2 mb-3">
                            <div class="d-flex justify-content-between small text-secondary">
                                <span>Tạm tính hàng hóa:</span>
                                <span class="fw-bold text-dark" id="displaySubtotal"><?= number_format($subtotal, 0, ',', '.') ?> đ</span>
                            </div>

                            <div class="d-flex justify-content-between small text-success <?= ($discountAmount > 0) ? '' : 'd-none' ?>" id="rowDiscount">
                                <span id="labelDiscount">Giảm giá voucher:</span>
                                <span class="fw-bold" id="displayDiscount">-<?= number_format($discountAmount, 0, ',', '.') ?> đ</span>
                            </div>

                            <div class="d-flex justify-content-between small text-secondary align-items-center">
                                <span>Phí vận chuyển:</span>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold">MIỄN PHÍ TOÀN QUỐC</span>
                            </div>

                            <hr class="my-2">

                            <div class="d-flex justify-content-between align-items-baseline">
                                <div>
                                    <div class="fw-bold text-dark fs-6">Tổng thanh toán:</div>
                                    <small class="text-secondary" style="font-size: 0.75rem;">(Đã gồm VAT & bảo hiểm)</small>
                                </div>
                                <div class="fs-4 fw-black text-danger" id="displayTotal">
                                    <?= number_format($totalMoney, 0, ',', '.') ?> đ
                                </div>
                            </div>
                        </div>

                        <!-- NÚT XÁC NHẬN ĐẶT HÀNG -->
                        <button type="submit" id="btnSubmitOrder" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3 d-flex align-items-center justify-content-center gap-2">
                            <i class="fa-solid fa-lock"></i>
                            <span>XÁC NHẬN ĐẶT HÀNG</span>
                        </button>
                    </div>

                    <!-- CAM KẾT V-PHONE -->
                    <div class="bg-white rounded-4 p-3 border shadow-sm">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <i class="fa-solid fa-shield-halved text-primary fs-4"></i>
                            <small class="text-secondary"><strong>Bảo hành chính hãng 12 tháng</strong>, 1 đổi 1 trong 30 ngày.</small>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-truck-fast text-success fs-4"></i>
                            <small class="text-secondary"><strong>Giao hỏa tốc 2 giờ</strong>, kiểm tra máy 100% trước khi thanh toán.</small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tương tác thẻ chọn phương thức thanh toán
    const cards = document.querySelectorAll('.payment-card-luxury');
    cards.forEach(card => {
        card.addEventListener('click', function() {
            cards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    // Chặn double submit
    const form = document.getElementById('checkoutMainForm');
    const submitBtn = document.getElementById('btnSubmitOrder');
    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Đang xử lý đơn hàng...';
        });
    }
});

function fillAndApplyVoucher(code) {
    const input = document.getElementById('voucherInput');
    if (input) {
        input.value = code;
        applyVoucherAjax();
    }
}

function applyVoucherAjax() {
    const input = document.getElementById('voucherInput');
    const msgEl = document.getElementById('voucherStatusMessage');
    const code = input ? input.value.trim() : '';
    if (!code) return;

    const btn = document.getElementById('btnApplyVoucher');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
    }

    const formData = new FormData();
    formData.append('apply_voucher', '1');
    formData.append('voucher_code', code);
    formData.append('ajax', '1');

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
        if (msgEl) {
            msgEl.classList.remove('d-none', 'text-success', 'text-danger');
            msgEl.classList.add(data.success ? 'text-success' : 'text-danger');
            msgEl.innerText = data.message;
        }
        if (data.success) {
            const rowDisc = document.getElementById('rowDiscount');
            const dispDisc = document.getElementById('displayDiscount');
            const dispTot = document.getElementById('displayTotal');
            if (rowDisc) rowDisc.classList.remove('d-none');
            if (dispDisc) dispDisc.innerText = '-' + data.discount_formatted;
            if (dispTot) dispTot.innerText = data.total_formatted;
        }
    })
    .catch(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    });
}

function removeVoucherAjax() {
    const formData = new FormData();
    formData.append('remove_voucher', '1');
    formData.append('ajax', '1');

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        window.location.reload();
    })
    .catch(() => {
        window.location.reload();
    });
}
</script>

<?php require_once 'app/views/includes/footer.php'; ?>
