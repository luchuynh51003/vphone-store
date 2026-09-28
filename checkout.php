<?php
require_once 'config/database.php';
$pageTitle = 'Thanh toán đơn hàng - V-Phone';

// 1. Bắt buộc đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}

$currentUser = $_SESSION['user'];
$orderSuccess = false;
$orderId = 0;

// 2. XỬ LÝ CHẾ ĐỘ "MUA NGAY DUY NHẤT 1 MÁY"
$isBuyNowMode = false;

// Nếu bấm MUA NGAY từ Trang chủ hoặc Trang chi tiết truyền vào
if (isset($_GET['action']) && $_GET['action'] === 'buy_now' && isset($_GET['id'])) {
    $buyId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $buyId]);
    $prod = $stmt->fetch();

    if ($prod) {
        $rom = trim($_GET['rom'] ?? $prod['rom']);
        $cList = array_map("trim", explode(",", $prod["colors"] ?? "Đen, Trắng"));
        $color = trim($_GET['color'] ?? ($cList[0] ?? 'Tiêu chuẩn'));
        $extra = (int)($_GET['extra'] ?? 0);
        $basePrice = ($prod['sale_price'] > 0 && $prod['sale_price'] < $prod['price']) ? $prod['sale_price'] : $prod['price'];

        // Lưu tạm món Mua Ngay riêng biệt (KHÔNG ĐỤNG VÀO GIỎ HÀNG CHÍNH)
        $_SESSION['buy_now_item'] = [
            'id' => $prod['id'],
            'name' => $prod['name'],
            'rom' => $rom,
            'color' => $color,
            'price' => $basePrice + $extra,
            'image' => (!empty($_GET['img']) ? $_GET['img'] : $prod['image']),
            'quantity' => 1
        ];

        header('Location: checkout.php?mode=buy_now');
        exit;
    }
}

// Xác định danh sách món cần thanh toán
if (isset($_GET['mode']) && $_GET['mode'] === 'buy_now' && !empty($_SESSION['buy_now_item'])) {
    $isBuyNowMode = true;
    $itemsToCheckout = [ $_SESSION['buy_now_item'] ]; // CHỈ THANH TOÁN ĐÚNG 1 MÁY NÀY
} else {
    // Thanh toán toàn bộ giỏ hàng
    $itemsToCheckout = $_SESSION['cart'] ?? [];
}

// 3. XỬ LÝ KHI BẤM "XÁC NHẬN ĐẶT HÀNG"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($itemsToCheckout)) {
        $fullname = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $paymentMethod = $_POST['payment_method'] ?? 'COD';

        if (!empty($fullname) && !empty($phone) && !empty($address)) {
            $totalMoney = 0;
            foreach ($itemsToCheckout as $item) {
                $totalMoney += $item['price'] * $item['quantity'];
            }

            // 1. Lưu đơn hàng
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, fullname, phone, address, note, total_money, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Chờ xử lý')");
            $stmt->execute([$currentUser['id'], $fullname, $phone, $address, $note, $totalMoney, $paymentMethod]);
            $orderId = $pdo->lastInsertId();

            // 2. Lưu chi tiết sản phẩm
            $detailStmt = $pdo->prepare("INSERT INTO order_details (order_id, product_id, product_name, price, quantity, total_price) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($itemsToCheckout as $item) {
                $sub = $item['price'] * $item['quantity'];
                $fullVariantName = $item['name'] . ' (' . ($item['rom'] ?? '256GB') . ' - Màu: ' . ($item['color'] ?? 'Tiêu chuẩn') . ')';
                $detailStmt->execute([$orderId, $item['id'], $fullVariantName, $item['price'], $item['quantity'], $sub]);

                // Trừ kho
                $pdo->prepare("UPDATE products SET quantity = GREATEST(0, quantity - ?) WHERE id = ?")->execute([$item["quantity"], $item["id"]]);
            }

            // NẾU LÀ MUA NGAY: CHỈ XÓA MÓN MUA NGAY, GIỎ HÀNG VẪN NGUYÊN VẸN!
            if ($isBuyNowMode) {
                unset($_SESSION['buy_now_item']);
            } else {
                // Nếu thanh toán cả giỏ thì mới làm rỗng giỏ
                $_SESSION['cart'] = [];
                $pdo->prepare("UPDATE users SET cart_data = NULL WHERE id = ?")->execute([$currentUser['id']]);
            }

            $orderSuccess = true;
        }
    }
}

// Nếu không phải vừa đặt hàng xong mà không có món nào thì về giỏ
if (!$orderSuccess && empty($itemsToCheckout)) {
    header('Location: cart.php');
    exit;
}

$totalMoney = 0;
if (!empty($itemsToCheckout)) {
    foreach ($itemsToCheckout as $item) {
        $totalMoney += $item['price'] * $item['quantity'];
    }
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container my-5">
    <?php if ($orderSuccess): ?>
        <div class="bg-white p-5 rounded-4 shadow-sm text-center col-lg-7 mx-auto border">
            <div style="width: 70px; height: 70px; border-radius: 50%; background: #e8f3ff; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                <i class="fa-solid fa-check text-primary fs-1"></i>
            </div>
            <h3 class="fw-bold text-dark mb-2">Đặt Hàng Thành Công!</h3>
            <p class="text-secondary mb-3">Mã đơn hàng của bạn là:</p>
            <div class="display-6 fw-bold text-primary mb-4">#VP-<?= $orderId ?></div>

            <div class="alert alert-light border rounded-3 text-start small p-3 mb-4">
                <div class="mb-2"><i class="fa-solid fa-truck-fast text-primary me-2"></i>Đơn hàng sẽ được nhân viên V-Phone gọi xác nhận và giao tận nơi.</div>
                <div><i class="fa-solid fa-money-bill-wave text-success me-2"></i>Hình thức thanh toán: <strong>Tiền mặt khi nhận hàng (COD)</strong>.</div>
            </div>

            <a href="index.php" class="btn btn-primary rounded-pill px-4 fw-bold py-2 shadow-sm">
                <i class="fa-solid fa-arrow-left me-2"></i>Tiếp tục mua sắm
            </a>
        </div>
    <?php else: ?>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 class="fw-bold mb-0">
                <i class="fa-solid fa-clipboard-check text-primary me-2"></i>
                <?= $isBuyNowMode ? 'Thanh Toán Mua Ngay (1 Sản Phẩm)' : 'Thanh Toán Toàn Bộ Giỏ Hàng' ?>
            </h4>
            <?php if ($isBuyNowMode): ?>
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                    <i class="fa-solid fa-bolt me-1"></i>Đơn Mua Nhanh
                </span>
            <?php endif; ?>
        </div>

        <form method="POST" action="checkout.php<?= $isBuyNowMode ? '?mode=buy_now' : '' ?>">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="bg-white p-4 rounded-4 shadow-sm border">
                        <h5 class="fw-bold mb-3 border-bottom pb-2">Người Nhận Hàng</h5>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Họ và tên người nhận *</label>
                            <input type="text" name="fullname" class="form-control rounded-pill" required value="<?= htmlspecialchars($currentUser['fullname']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Số điện thoại nhận hàng *</label>
                            <input type="tel" name="phone" class="form-control rounded-pill" required value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Địa chỉ giao hàng tận nơi *</label>
                            <input type="text" name="address" class="form-control rounded-pill" required value="<?= htmlspecialchars($currentUser['address'] ?? '') ?>" placeholder="Số nhà, tên đường, phường/xã...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Ghi chú đơn hàng (Tùy chọn)</label>
                            <textarea name="note" rows="2" class="form-control rounded-3" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao..."></textarea>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3 border-bottom pb-2">Phương Thức Thanh Toán</h5>
                        <div class="form-check p-3 border rounded-3 bg-light d-flex align-items-center mb-2">
                            <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" value="COD" checked id="codPayment">
                            <label class="form-check-label fw-bold" for="codPayment">
                                <i class="fa-solid fa-hand-holding-dollar text-success me-2 fs-5"></i>Thanh toán tiền mặt khi nhận hàng (COD)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Cột phải: Chỉ hiện sản phẩm đang mua -->
                <div class="col-lg-5">
                    <div class="bg-white p-4 rounded-4 shadow-sm border">
                        <h5 class="fw-bold mb-3 border-bottom pb-2">
                            <?= $isBuyNowMode ? 'Sản Phẩm Đặt Mua (1 máy)' : 'Đơn Hàng (' . count($itemsToCheckout) . ' món)' ?>
                        </h5>
                        <div class="d-flex flex-column gap-3 mb-3">
                            <?php foreach ($itemsToCheckout as $item): ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="" style="width: 46px; height: 46px; object-fit: contain;" class="me-2" onerror="this.onerror=null; this.src='assets/images/products/iphone-16.png';">
                                        <div>
                                            <div class="small fw-bold text-truncate" style="max-width: 170px;"><?= htmlspecialchars($item['name']) ?></div>
                                            <small class="text-secondary d-block" style="font-size:0.75rem;">Bản: <?= htmlspecialchars($item['rom'] ?? 'Tiêu chuẩn') ?> | Màu: <?= htmlspecialchars($item['color'] ?? 'Tiêu chuẩn') ?></small>
                                            <small class="text-muted">SL: <?= $item['quantity'] ?></small>
                                        </div>
                                    </div>
                                    <span class="small fw-bold text-danger"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> đ</span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between mb-2 small text-secondary">
                            <span>Phí vận chuyển:</span>
                            <span class="text-success fw-bold">MIỄN PHÍ</span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fs-5 fw-bold">Tổng thanh toán:</span>
                            <span class="fs-4 fw-bold text-danger"><?= number_format($totalMoney, 0, ',', '.') ?> đ</span>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3">
                            <i class="fa-solid fa-lock me-2"></i>XÁC NHẬN ĐẶT HÀNG (COD)
                        </button>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
