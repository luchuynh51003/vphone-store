<?php
$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
$orderSuccess = $orderSuccess ?? false;
$orderId = $orderId ?? 0;
$isBuyNowMode = $isBuyNowMode ?? (isset($_GET['mode']) && $_GET['mode'] === 'buy_now');
$itemsToCheckout = $itemsToCheckout ?? [];

$itemsToCheckout = array_values(array_filter($itemsToCheckout, static fn($i) => !empty($i) && is_array($i)));

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

$userAddresses = $userAddresses ?? [];
$defaultAddress = $defaultAddress ?? null;

$formDraft = $_SESSION['checkout_form_draft'] ?? [];
$deliveryName = $formDraft['fullname'] ?? ($defaultAddress['recipient_name'] ?? ($currentUser['fullname'] ?? ''));
$deliveryPhone = $formDraft['phone'] ?? ($defaultAddress['phone'] ?? ($currentUser['phone'] ?? ''));
$deliveryAddress = $formDraft['address'] ?? ($defaultAddress['address'] ?? ($currentUser['address'] ?? ''));
$deliveryNote = $formDraft['note'] ?? '';

if (!$orderSuccess && empty($itemsToCheckout)) {
    header('Location: index.php?page=cart');
    exit;
}

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<div class="container checkout-page-wrapper">
    <div class="d-flex justify-content-center mb-4 pb-1">
        <div class="checkout-stepper-wrapper">
            <a href="index.php?page=cart" class="step-badge step-completed" title="Xem lại giỏ hàng">
                <span class="step-circle"><i class="fa-solid fa-check"></i></span>
                <span class="step-text">Giỏ hàng</span>
            </a>

            <div class="step-connector active"></div>

            <div class="step-badge <?= $orderSuccess ? 'step-completed' : 'step-active' ?>">
                <span class="step-circle"><?= $orderSuccess ? '<i class="fa-solid fa-check"></i>' : '2' ?></span>
                <span class="step-text">Thanh toán</span>
            </div>

            <div class="step-connector <?= $orderSuccess ? 'active' : '' ?>"></div>

            <div class="step-badge <?= $orderSuccess ? 'step-active' : 'step-pending' ?>">
                <span class="step-circle"><?= $orderSuccess ? '<i class="fa-solid fa-check"></i>' : '3' ?></span>
                <span class="step-text">Hoàn tất</span>
            </div>
        </div>
    </div>

    <?php if ($orderSuccess): ?>
        <div class="checkout-panel-card p-4 p-md-5 text-center col-lg-7 mx-auto my-3">
            <div style="width: 84px; height: 84px; border-radius: 50%; background: rgba(16, 185, 129, 0.12); border: 3px solid #10b981; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.25);">
                <i class="fa-solid fa-check text-success fs-1"></i>
            </div>
            <h3 class="fw-black mb-2">Đặt Hàng Thành Công!</h3>
            <p class="text-secondary mb-3">Cảm ơn bạn đã tin tưởng V-Phone Store. Mã đơn hàng của bạn là:</p>
            <div class="display-6 fw-bold text-primary mb-4" style="letter-spacing: 1px;">#VP-<?= (int)$orderId ?></div>

            <div class="alert alert-light border rounded-4 text-start small p-3 p-md-4 mb-4" style="background: rgba(255, 255, 255, 0.04);">
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

            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="index.php?page=orders&order_code=<?= (int)$orderId ?><?= !empty($deliveryPhone) ? '&phone=' . rawurlencode($deliveryPhone) : '' ?>" class="btn btn-primary rounded-pill px-4 fw-bold py-2.5 shadow-sm">
                    <i class="fa-solid fa-truck-ramp-box me-2"></i>Theo dõi đơn hàng này
                </a>
                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold py-2.5">
                    <i class="fa-solid fa-store me-2"></i>Tiếp tục mua sắm
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1.5">
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
                <h3 class="fw-black mb-1">
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
                <div class="col-lg-7">
                    <!-- Delivery Info Panel -->
                    <div class="checkout-panel-card">
                        <div class="checkout-panel-header">
                            <h5 class="checkout-panel-title">
                                <span class="checkout-panel-icon"><i class="fa-solid fa-location-dot"></i></span>
                                <span>Địa Chỉ Nhận Hàng</span>
                            </h5>
                            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small fw-semibold">Giao tận nơi</span>
                        </div>

                        <?php if (!empty($userAddresses) && count($userAddresses) > 0): ?>
                            <div class="saved-addr-box mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-2.5">
                                    <label class="checkout-label mb-0">
                                        <i class="fa-solid fa-address-book text-primary me-1"></i> Chọn nhanh từ sổ địa chỉ đã lưu (<?= count($userAddresses) ?>)
                                    </label>
                                    <a href="index.php?page=profile&tab=addresses" target="_blank" class="small text-primary text-decoration-none fw-semibold">
                                        <i class="fa-solid fa-gear me-1"></i>Quản lý sổ địa chỉ
                                    </a>
                                </div>
                                <div class="d-flex flex-column gap-2" id="savedAddressSelector">
                                    <?php foreach ($userAddresses as $addr): ?>
                                        <?php $isSelected = ($addr['address'] === $deliveryAddress && $addr['phone'] === $deliveryPhone); ?>
                                        <div class="saved-addr-item <?= $isSelected ? 'active border-primary' : '' ?>" onclick="selectSavedAddress(<?= htmlspecialchars(json_encode($addr), ENT_QUOTES, 'UTF-8') ?>)">
                                            <div class="custom-addr-radio mt-1">
                                                <input class="d-none" type="radio" name="selected_saved_address" id="addr_radio_<?= $addr['id'] ?>" <?= $isSelected ? 'checked' : '' ?>>
                                            </div>
                                            <label class="form-check-label w-100 mb-0" for="addr_radio_<?= $addr['id'] ?>" style="cursor: pointer;">
                                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                    <span class="fw-bold small"><?= htmlspecialchars($addr['recipient_name']) ?></span>
                                                    <span class="text-secondary small">| <?= htmlspecialchars($addr['phone']) ?></span>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2 py-0.5" style="font-size:0.68rem;"><?= htmlspecialchars($addr['label'] ?? 'Nhà riêng') ?></span>
                                                    <?php if ($addr['is_default'] == 1): ?>
                                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-0.5 ms-auto" style="font-size:0.68rem;">Mặc định</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="small text-secondary"><?= htmlspecialchars($addr['address']) ?></div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="checkout-form-group">
                            <label class="checkout-label" for="customerFullname">
                                Họ và tên người nhận <span class="text-danger">*</span>
                            </label>
                            <div class="checkout-input-group">
                                <i class="fa-solid fa-user input-icon"></i>
                                <input type="text" name="fullname" id="customerFullname" class="checkout-form-control" required value="<?= htmlspecialchars($deliveryName) ?>" placeholder="Ví dụ: Nguyễn Văn A">
                            </div>
                        </div>

                        <div class="checkout-form-group">
                            <label class="checkout-label" for="customerPhone">
                                Số điện thoại liên hệ <span class="text-danger">*</span>
                            </label>
                            <div class="checkout-input-group">
                                <i class="fa-solid fa-phone input-icon"></i>
                                <input type="tel" name="phone" id="customerPhone" class="checkout-form-control" required value="<?= htmlspecialchars($deliveryPhone) ?>" placeholder="Ví dụ: 0901234567">
                            </div>
                        </div>

                        <div class="checkout-form-group">
                            <label class="checkout-label" for="customerAddress">
                                Địa chỉ giao hàng tận nơi <span class="text-danger">*</span>
                            </label>
                            <div class="checkout-input-group">
                                <i class="fa-solid fa-map-location-dot input-icon"></i>
                                <input type="text" name="address" id="customerAddress" class="checkout-form-control" required value="<?= htmlspecialchars($deliveryAddress) ?>" placeholder="Số nhà, tên đường, phường/xã, quận/huyện...">
                            </div>
                        </div>

                        <div class="checkout-form-group mb-0">
                            <label class="checkout-label" for="customerNote">
                                Ghi chú cho shipper (Tùy chọn)
                            </label>
                            <div class="checkout-input-group">
                                <i class="fa-solid fa-pen input-icon icon-textarea"></i>
                                <textarea name="note" id="customerNote" rows="2" class="checkout-form-control" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao 15 phút..."><?= htmlspecialchars($deliveryNote) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Options Panel -->
                    <div class="checkout-panel-card">
                        <div class="checkout-panel-header">
                            <h5 class="checkout-panel-title">
                                <span class="checkout-panel-icon"><i class="fa-solid fa-credit-card"></i></span>
                                <span>Phương Thức Thanh Toán</span>
                            </h5>
                            <span class="small text-secondary"><i class="fa-solid fa-shield-halved text-success me-1"></i>Bảo mật SSL 256-bit</span>
                        </div>

                        <div class="d-flex flex-column gap-3" id="paymentOptionsGroup">
                            <!-- COD Option -->
                            <label class="payment-card-luxury <?= $paymentMethod === 'COD' ? 'active' : '' ?>" for="pay_cod">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="custom-radio-circle">
                                        <input type="radio" name="payment_method" value="COD" id="pay_cod" <?= $paymentMethod === 'COD' ? 'checked' : '' ?> class="d-none">
                                        <span class="radio-dot"></span>
                                    </div>
                                    <div class="payment-icon-box" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                                        <i class="fa-solid fa-hand-holding-dollar"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <span class="fw-bold">Thanh toán tiền mặt khi nhận máy (COD)</span>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-0.5" style="font-size:0.7rem;">Khuyên dùng</span>
                                        </div>
                                        <small class="text-secondary d-block">Kiểm tra máy chuẩn seal, phụ kiện đầy đủ rồi mới gửi tiền cho shipper.</small>
                                    </div>
                                </div>
                            </label>

                            <!-- VietQR Option -->
                            <label class="payment-card-luxury <?= $paymentMethod === 'Chuyển khoản QR VietQR' ? 'active' : '' ?>" for="pay_qr">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="custom-radio-circle mt-1">
                                        <input type="radio" name="payment_method" value="Chuyển khoản QR VietQR" id="pay_qr" <?= $paymentMethod === 'Chuyển khoản QR VietQR' ? 'checked' : '' ?> class="d-none">
                                        <span class="radio-dot"></span>
                                    </div>
                                    <div class="payment-icon-box" style="background: rgba(2, 132, 199, 0.12); color: #0284c7;">
                                        <i class="fa-solid fa-qrcode"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <span class="fw-bold">Chuyển khoản ngân hàng qua mã VietQR 24/7</span>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-0.5" style="font-size:0.7rem;">Tự động nhận diện 24/7</span>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-0.5" style="font-size:0.68rem;">Khuyên dùng</span>
                                        </div>
                                        <small class="text-secondary d-block mb-2">Tự động sinh mã VietQR Napas 24/7 chính xác số tiền & nội dung đơn hàng. Tự động kích hoạt ngay sau khi chuyển mà không cần chụp bill.</small>
                                        <div class="d-flex align-items-center gap-1 flex-wrap pt-2 border-top" style="font-size:0.75rem;">
                                            <span class="text-secondary me-1"><i class="fa-solid fa-bolt text-warning me-1"></i>Hỗ trợ:</span>
                                            <span class="payment-bank-chip">MBBank</span>
                                            <span class="payment-bank-chip">Vietcombank</span>
                                            <span class="payment-bank-chip">Techcombank</span>
                                            <span class="payment-bank-chip">ACB</span>
                                            <span class="payment-bank-chip">VPBank</span>
                                            <span class="payment-bank-chip">BIDV</span>
                                            <span class="payment-bank-chip text-secondary">+50 ngân hàng</span>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <!-- MoMo Sandbox Option -->
                            <label class="payment-card-luxury <?= $paymentMethod === 'Ví điện tử MoMo' ? 'active' : '' ?>" for="pay_momo">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="custom-radio-circle mt-1">
                                        <input type="radio" name="payment_method" value="Ví điện tử MoMo" id="pay_momo" <?= $paymentMethod === 'Ví điện tử MoMo' ? 'checked' : '' ?> class="d-none">
                                        <span class="radio-dot"></span>
                                    </div>
                                    <div class="payment-icon-box text-white" style="background: linear-gradient(135deg, #d82d8b, #a50064); box-shadow: 0 4px 12px rgba(165, 0, 100, 0.3);">
                                        <i class="fa-solid fa-wallet"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <span class="fw-bold">Ví điện tử MoMo (Sandbox Developer)</span>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-0.5" style="font-size:0.7rem;">Cổng MoMo Test v2</span>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-0.5" style="font-size:0.68rem;">Tự động duyệt</span>
                                        </div>
                                        <small class="text-secondary d-block">Kết nối trực tiếp Cổng MoMo Sandbox API. Hỗ trợ quét mã QR MoMo và có sẵn nút giả lập "Thanh toán thành công" chính thức của MoMo.</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Order Summary & Voucher -->
                <div class="col-lg-5">
                    <div class="checkout-summary-card sticky-top" style="top: 90px; z-index: 10;">
                        <div class="checkout-panel-header">
                            <h5 class="checkout-panel-title">
                                <span class="checkout-panel-icon"><i class="fa-solid fa-receipt"></i></span>
                                <span>Tóm Tắt Đơn Hàng</span>
                            </h5>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 fw-bold">
                                <?= $isBuyNowMode ? '1 máy mua ngay' : count($itemsToCheckout) . ' sản phẩm' ?>
                            </span>
                        </div>

                        <!-- Product List -->
                        <div class="d-flex flex-column gap-2 mb-3">
                            <?php foreach ($itemsToCheckout as $item): ?>
                                <?php
                                    $itemPrice = (float)($item['price'] ?? 0);
                                    $itemQty = (int)($item['quantity'] ?? 1);
                                    $itemSubtotal = $itemPrice * $itemQty;
                                ?>
                                <div class="d-flex align-items-center gap-3 p-2.5 rounded-3 border" style="background: rgba(241, 245, 249, 0.4);">
                                    <div class="checkout-summary-thumb">
                                        <img src="<?= htmlspecialchars($item['image'] ?? 'assets/images/products/iphone-16.png') ?>" alt="" onerror="this.onerror=null; this.src='assets/images/products/iphone-16.png';">
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="small fw-bold text-truncate" title="<?= htmlspecialchars($item['name'] ?? 'Điện thoại') ?>">
                                            <?= htmlspecialchars($item['name'] ?? 'Điện thoại') ?>
                                        </div>
                                        <div class="d-flex gap-1 flex-wrap mt-1">
                                            <span class="checkout-item-badge text-primary"><?= htmlspecialchars($item['rom'] ?? 'Tiêu chuẩn') ?></span>
                                            <span class="checkout-item-badge"><?= htmlspecialchars($item['color'] ?? 'Tiêu chuẩn') ?></span>
                                            <span class="badge bg-dark text-white rounded-2" style="font-size:0.68rem;">x<?= $itemQty ?></span>
                                        </div>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <div class="small fw-bold text-danger"><?= number_format($itemSubtotal, 0, ',', '.') ?> VNĐ</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Voucher Section -->
                        <div class="checkout-voucher-panel">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label for="voucherInput" class="checkout-label mb-0">
                                    <i class="fa-solid fa-ticket text-primary me-1"></i>Mã giảm giá ưu đãi
                                </label>
                                <?php if ($appliedVoucher): ?>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none small fw-semibold" onclick="removeVoucherAjax()">
                                        <i class="fa-solid fa-xmark me-1"></i>Gỡ mã
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="input-group">
                                <span class="input-group-text border-end-0 bg-transparent text-secondary ps-3">
                                    <i class="fa-solid fa-tag"></i>
                                </span>
                                <input type="text" id="voucherInput" name="voucher_code" class="form-control text-uppercase fw-bold border-start-0 border-end-0" placeholder="Mã voucher (VD: WELCOME50)" value="<?= htmlspecialchars($appliedVoucher['code'] ?? '') ?>">
                                <button type="button" class="btn btn-primary px-3 fw-bold rounded-end-3" id="btnApplyVoucher" onclick="applyVoucherAjax()">
                                    Áp dụng
                                </button>
                            </div>

                            <div id="voucherStatusMessage" class="small mt-2 <?= $appliedVoucher ? 'text-success' : 'text-danger' ?> fw-semibold <?= empty($voucherMessage) ? 'd-none' : '' ?>">
                                <?= htmlspecialchars($voucherMessage) ?>
                            </div>

                            <div class="mt-2 pt-2 border-top">
                                <small class="text-secondary d-block mb-1.5" style="font-size: 0.75rem;">Gợi ý mã hot hôm nay:</small>
                                <div class="d-flex gap-1.5 flex-wrap">
                                    <button type="button" class="voucher-chip-btn" onclick="fillAndApplyVoucher('WELCOME50')">
                                        <i class="fa-solid fa-bolt-lightning text-warning me-1"></i>WELCOME50 (-50k)
                                    </button>
                                    <button type="button" class="voucher-chip-btn" onclick="fillAndApplyVoucher('VPHONE10')">
                                        <i class="fa-solid fa-fire text-danger me-1"></i>VPHONE10 (-10%)
                                    </button>
                                    <button type="button" class="voucher-chip-btn" onclick="fillAndApplyVoucher('FLASH20')">
                                        <i class="fa-solid fa-gift text-primary me-1"></i>FLASH20 (-20%)
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="d-flex flex-column gap-2 mb-3">
                            <div class="d-flex justify-content-between small text-secondary">
                                <span>Tạm tính hàng hóa:</span>
                                <span class="fw-bold" id="displaySubtotal"><?= number_format($subtotal, 0, ',', '.') ?> VNĐ</span>
                            </div>

                            <div class="d-flex justify-content-between small text-success <?= ($discountAmount > 0) ? '' : 'd-none' ?>" id="rowDiscount">
                                <span id="labelDiscount">Giảm giá voucher:</span>
                                <span class="fw-bold" id="displayDiscount">-<?= number_format($discountAmount, 0, ',', '.') ?> VNĐ</span>
                            </div>

                            <div class="d-flex justify-content-between small text-secondary align-items-center">
                                <span>Phí vận chuyển:</span>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 fw-bold">
                                    <i class="fa-solid fa-shield-check me-1"></i>MIỄN PHÍ TOÀN QUỐC
                                </span>
                            </div>

                            <hr class="my-2 opacity-50">

                            <div class="d-flex justify-content-between align-items-baseline">
                                <div>
                                    <div class="fw-bold fs-6">Tổng thanh toán:</div>
                                    <small class="text-secondary" style="font-size: 0.75rem;">(Đã gồm VAT & bảo hiểm)</small>
                                </div>
                                <div class="checkout-total-price" id="displayTotal">
                                    <?= number_format($totalMoney, 0, ',', '.') ?> VNĐ
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="btnSubmitOrder" class="btn-checkout-submit">
                            <i class="fa-solid fa-lock"></i>
                            <span>XÁC NHẬN ĐẶT HÀNG</span>
                        </button>
                    </div>

                    <!-- Trust & Reassurance Panel -->
                    <div class="checkout-trust-card mt-3">
                        <div class="d-flex align-items-center gap-3 mb-2.5">
                            <div class="checkout-trust-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div>
                                <strong class="d-block small">Bảo hành chính hãng 12 tháng</strong>
                                <small class="text-secondary">Lỗi 1 đổi 1 trong 30 ngày tại tất cả chi nhánh</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="checkout-trust-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-truck-fast"></i>
                            </div>
                            <div>
                                <strong class="d-block small">Giao hỏa tốc 2 giờ nội thành</strong>
                                <small class="text-secondary">Kiểm tra máy chuẩn seal 100% trước khi thanh toán</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateSubmitBtn(method) {
        const submitBtn = document.getElementById('btnSubmitOrder');
        if (!submitBtn) return;
        if (method === 'Chuyển khoản QR VietQR') {
            submitBtn.innerHTML = '<i class="fa-solid fa-qrcode me-2"></i><span>TIẾP TỤC QUÉT MÃ VIETQR</span><i class="fa-solid fa-arrow-right ms-2 small"></i>';
            submitBtn.className = 'btn-checkout-submit';
            submitBtn.style.background = 'linear-gradient(135deg, #0284c7 0%, #0369a1 100%)';
            submitBtn.style.boxShadow = '0 8px 24px -4px rgba(2, 132, 199, 0.45)';
        } else if (method === 'Ví điện tử MoMo') {
            submitBtn.innerHTML = '<i class="fa-solid fa-wallet me-2"></i><span>THANH TOÁN QUA MOMO SANDBOX</span><i class="fa-solid fa-arrow-right ms-2 small"></i>';
            submitBtn.className = 'btn-checkout-submit';
            submitBtn.style.background = 'linear-gradient(135deg, #e11d48 0%, #a50064 100%)';
            submitBtn.style.boxShadow = '0 8px 24px -4px rgba(165, 0, 100, 0.45)';
        } else {
            submitBtn.innerHTML = '<i class="fa-solid fa-lock me-2"></i><span>XÁC NHẬN ĐẶT HÀNG (COD)</span>';
            submitBtn.className = 'btn-checkout-submit';
            submitBtn.style.background = 'linear-gradient(135deg, #0284c7 0%, #0066cc 100%)';
            submitBtn.style.boxShadow = '0 8px 24px -4px rgba(2, 132, 199, 0.45)';
        }
    }

    const cards = document.querySelectorAll('.payment-card-luxury');
    cards.forEach(card => {
        card.addEventListener('click', function() {
            cards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                updateSubmitBtn(radio.value);
            }
        });
    });

    const checkedRadio = document.querySelector('input[name="payment_method"]:checked');
    if (checkedRadio) {
        updateSubmitBtn(checkedRadio.value);
    }

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

function selectSavedAddress(addr) {
    if (!addr) return;
    const nameInp = document.getElementById('customerFullname');
    const phoneInp = document.getElementById('customerPhone');
    const addrInp = document.getElementById('customerAddress');
    if (nameInp && addr.recipient_name) nameInp.value = addr.recipient_name;
    if (phoneInp && addr.phone) phoneInp.value = addr.phone;
    if (addrInp && addr.address) addrInp.value = addr.address;

    document.querySelectorAll('.saved-addr-item').forEach(el => el.classList.remove('active', 'border-primary'));
    const radio = document.getElementById('addr_radio_' + addr.id);
    if (radio) {
        radio.checked = true;
        const container = radio.closest('.saved-addr-item');
        if (container) container.classList.add('active', 'border-primary');
    }
}
</script>

<?php require_once 'app/views/includes/footer.php'; ?>
