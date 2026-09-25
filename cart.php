<?php
require_once 'config/database.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$key = $_GET['key'] ?? '';
$rom = trim($_GET['rom'] ?? '');
$color = trim($_GET['color'] ?? '');
$extra = isset($_GET['extra']) ? (int)$_GET['extra'] : 0;
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == 1;
$redirect = $_GET['redirect'] ?? '';

function syncUserCartToDB($pdo) {
    if (isset($_SESSION['user']['id'])) {
        $userId = $_SESSION['user']['id'];
        $cartJson = json_encode($_SESSION['cart'], JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("UPDATE users SET cart_data = ? WHERE id = ?");
        $stmt->execute([$cartJson, $userId]);
    }
}

// XỬ LÝ THÊM VÀO GIỎ
if ($action === 'add' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $prod = $stmt->fetch();

    if ($prod) {
        $selectedRom = !empty($rom) ? $rom : $prod['rom'];
        $cList = array_map("trim", explode(",", $prod["colors"] ?? "Đen, Trắng"));
        $selectedColor = !empty($color) ? $color : ($cList[0] ?? 'Tiêu chuẩn');

        $basePrice = ($prod['sale_price'] > 0 && $prod['sale_price'] < $prod['price']) ? $prod['sale_price'] : $prod['price'];
        $finalPrice = $basePrice + $extra;

        // TẠO MÃ DÒNG DUY NHẤT: ID + GIÁ + BỘ NHỚ + MÀU SẮC
        $variantSlug = preg_replace('/[^a-zA-Z0-9]/', '', $selectedRom . $selectedColor);
        $cartKey = $id . '_' . $finalPrice . '_' . $variantSlug;

        if (isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$cartKey] = [
                'cart_key' => $cartKey,
                'id' => $prod['id'],
                'name' => $prod['name'],
                'rom' => $selectedRom,
                'color' => $selectedColor,
                'price' => $finalPrice,
                'image' => $prod['image'],
                'quantity' => 1
            ];
        }

        syncUserCartToDB($pdo);
    }

    if ($redirect === 'checkout') {
        header('Location: checkout.php');
        exit;
    }

    if ($isAjax) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        $totalCount = 0;
        foreach ($_SESSION['cart'] as $item) {
            $totalCount += $item['quantity'];
        }

        echo json_encode([
            'success' => true,
            'cart_count' => $totalCount,
            'product_name' => $prod['name'] . ' - Màu ' . $selectedColor . ' (' . $selectedRom . ')'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
    exit;
}

if ($action === 'update' && !empty($key)) {
    $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($qty > 0 && isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] = $qty;
    }
    syncUserCartToDB($pdo);
    header('Location: cart.php');
    exit;
}

if ($action === 'delete' && !empty($key)) {
    unset($_SESSION['cart'][$key]);
    syncUserCartToDB($pdo);
    header('Location: cart.php');
    exit;
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    syncUserCartToDB($pdo);
    header('Location: cart.php');
    exit;
}

$pageTitle = 'Giỏ hàng của bạn - V-Phone';
$totalMoney = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalMoney += $item['price'] * $item['quantity'];
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container my-4">
    <h4 class="fw-bold mb-3"><i class="fa-solid fa-cart-shopping text-primary me-2"></i>Giỏ Hàng Của Bạn</h4>

    <?php if (empty($_SESSION['cart'])): ?>
        <div class="bg-white p-5 rounded-4 shadow-sm text-center border">
            <i class="fa-solid fa-cart-arrow-down text-muted fs-1 mb-3"></i>
            <h5 class="fw-bold text-dark">Giỏ hàng đang trống!</h5>
            <p class="text-secondary small">Hãy chọn những chiếc điện thoại ưng ý tại trang chủ nhé.</p>
            <a href="index.php" class="btn btn-primary rounded-pill px-4 mt-2">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="bg-white rounded-4 shadow-sm p-4 border">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Sản phẩm</th>
                                    <th scope="col">Đơn giá</th>
                                    <th scope="col" style="width: 130px;">Số lượng</th>
                                    <th scope="col">Thành tiền</th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $cKey => $item): ?>
                                    <?php $subtotal = $item['price'] * $item['quantity']; ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 52px; height: 52px; object-fit: contain;" class="me-3" onerror="this.onerror=null; this.src='assets/images/products/iphone-16.png';">
                                                <div>
                                                    <a href="product-detail.php?id=<?= $item['id'] ?>" class="text-decoration-none text-dark fw-bold small d-block"><?= htmlspecialchars($item['name']) ?></a>
                                                    <!-- HIỂN THỊ CẢ BỘ NHỚ LẪN MÀU SẮC RÕ RÀNG -->
                                                    <div class="mt-1 d-flex gap-1 flex-wrap">
                                                        <span class="badge bg-light text-primary border" style="font-size:0.68rem;">Bản: <strong><?= htmlspecialchars($item['rom'] ?? 'Tiêu chuẩn') ?></strong></span>
                                                        <span class="badge bg-light text-dark border" style="font-size:0.68rem;"><i class="fa-solid fa-palette text-primary me-1"></i>Màu: <strong><?= htmlspecialchars($item['color'] ?? 'Tiêu chuẩn') ?></strong></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-danger fw-bold fs-6"><?= number_format($item['price'], 0, ',', '.') ?> đ</td>
                                        <td>
                                            <form action="cart.php?action=update&key=<?= urlencode($cKey) ?>" method="POST" class="d-flex align-items-center">
                                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="99" class="form-control form-control-sm text-center fw-bold" onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td class="text-primary fw-bold fs-6"><?= number_format($subtotal, 0, ',', '.') ?> đ</td>
                                        <td>
                                            <a href="cart.php?action=delete&key=<?= urlencode($cKey) ?>" class="text-danger btn btn-sm btn-light rounded-circle" title="Xóa món này"><i class="fa-solid fa-trash-can"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="fa-solid fa-arrow-left me-1"></i>Chọn thêm điện thoại khác</a>
                        <a href="cart.php?action=clear" class="btn btn-outline-danger btn-sm rounded-pill" onclick="return confirm('Bạn có chắc muốn làm rỗng toàn bộ giỏ hàng?');">Xóa toàn bộ</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="bg-white rounded-4 shadow-sm p-4 border">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Tóm Tắt Đơn Hàng</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Tạm tính:</span>
                        <span class="fw-bold"><?= number_format($totalMoney, 0, ',', '.') ?> đ</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-secondary">Phí vận chuyển:</span>
                        <span class="text-success fw-bold">MIỄN PHÍ</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fs-5 fw-bold">Tổng thanh toán:</span>
                        <span class="fs-4 fw-bold text-danger"><?= number_format($totalMoney, 0, ',', '.') ?> đ</span>
                    </div>

                    <a href="checkout.php" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3">
                        TIẾN HÀNH ĐẶT HÀNG <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
