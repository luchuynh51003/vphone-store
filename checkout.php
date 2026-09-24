<?php
require_once 'config/database.php';
$pageTitle = 'Thanh toán đơn hàng - V-Phone';

if (!isset($_SESSION['user'])) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}

$currentUser = $_SESSION['user'];
$orderSuccess = false;
$orderId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_SESSION['cart'])) {
        $fullname = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $paymentMethod = $_POST['payment_method'] ?? 'COD';

        if (!empty($fullname) && !empty($phone) && !empty($address)) {
            $totalMoney = 0;
            foreach ($_SESSION['cart'] as $item) {
                $totalMoney += $item['price'] * $item['quantity'];
            }

            $stmt = $pdo->prepare("INSERT INTO orders (user_id, fullname, phone, address, note, total_money, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Chờ xử lý')");
            $stmt->execute([$currentUser['id'], $fullname, $phone, $address, $note, $totalMoney, $paymentMethod]);
            $orderId = $pdo->lastInsertId();

            // Lưu từng món riêng biệt kèm dung lượng và giá tiền chuẩn xác
            $detailStmt = $pdo->prepare("INSERT INTO order_details (order_id, product_id, product_name, price, quantity, total_price) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($_SESSION['cart'] as $item) {
                $sub = $item['price'] * $item['quantity'];
                $detailStmt->execute([$orderId, $item["id"], $item["name"], $item["price"], $item["quantity"], $sub]); $pdo->prepare("UPDATE products SET quantity = GREATEST(0, quantity - ?) WHERE id = ?")->execute([$item["quantity"], $item["id"]]);
            }

            $_SESSION['cart'] = [];
            $clearDb = $pdo->prepare("UPDATE users SET cart_data = NULL WHERE id = ?");
            $clearDb->execute([$currentUser['id']]);

            $orderSuccess = true;
        }
    }
}

if (!$orderSuccess && empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$totalMoney = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
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
            <p class="text-secondary mb-3">Cảm ơn bạn đã mua hàng tại V-Phone. Mã đơn hàng của bạn là:</p>
            <div class="display-6 fw-bold text-primary mb-4">#VP-<?= $orderId ?></div>

            <div class="alert alert-light border rounded-3 text-start small p-3 mb-4">
                <div class="mb-2"><i class="fa-solid fa-truck-fast text-primary me-2"></i>Đơn hàng sẽ được nhân viên V-Phone gọi xác nhận và giao trong vòng 2H - 24H.</div>
                <div><i class="fa-solid fa-money-bill-wave text-success me-2"></i>Hình thức thanh toán: <strong>Tiền mặt khi nhận hàng (COD)</strong>.</div>
            </div>

            <a href="index.php" class="btn btn-primary rounded-pill px-4 fw-bold py-2 shadow-sm">
                <i class="fa-solid fa-arrow-left me-2"></i>Tiếp tục mua sắm
            </a>
        </div>
    <?php else: ?>
        <h4 class="fw-bold mb-4"><i class="fa-solid fa-clipboard-check text-primary me-2"></i>Thông Tin Giao Hàng & Đặt Hàng</h4>
        <form method="POST" action="checkout.php">
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
                            <input type="text" name="address" class="form-control rounded-pill" required value="<?= htmlspecialchars($currentUser['address'] ?? '') ?>" placeholder="Số nhà, tên đường, phường/xã, quận/huyện...">
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

                <div class="col-lg-5">
                    <div class="bg-white p-4 rounded-4 shadow-sm border">
                        <h5 class="fw-bold mb-3 border-bottom pb-2">Đơn Hàng Của Bạn (<?= count($_SESSION['cart']) ?> món)</h5>
                        <div class="d-flex flex-column gap-3 mb-3" style="max-height: 280px; overflow-y: auto;">
                            <?php foreach ($_SESSION['cart'] as $item): ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="" style="width: 44px; height: 44px; object-fit: contain;" class="me-2">
                                        <div>
                                            <div class="small fw-bold text-truncate" style="max-width: 170px;"><?= htmlspecialchars($item['name']) ?></div>
                                            <small class="text-secondary">SL: <?= $item['quantity'] ?></small>
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

