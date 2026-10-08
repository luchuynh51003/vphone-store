<?php
require_once 'app/models/ProductModel.php';
require_once 'app/models/OrderModel.php';
require_once 'app/models/UserModel.php';
require_once 'app/models/VoucherModel.php';

class CartController {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function index() {
        if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
        $action = $_POST['action'] ?? ($_GET['action'] ?? '');
        $id = (int)($_GET['id'] ?? 0);
        $key = $_POST['key'] ?? ($_GET['key'] ?? '');
        $rom = trim($_GET['rom'] ?? '');
        $color = trim($_GET['color'] ?? '');
        $image = trim($_GET['img'] ?? '');
        $extra = (int)($_GET['extra'] ?? 0);
        $redirect = $_GET['redirect'] ?? '';
        $isAjax = isset($_GET['ajax']) && $_GET['ajax'] == 1;

        if ($action === 'add' && $id > 0) {
            $prodModel = new ProductModel($this->pdo);
            $prod = $prodModel->getById($id);
            if ($prod) {
                $selectedRom = !empty($rom) ? $rom : $prod['rom'];
                $cList = array_map("trim", explode(",", $prod["colors"] ?? "Đen, Trắng"));
                $selectedColor = !empty($color) ? $color : ($cList[0] ?? 'Tiêu chuẩn');
                $finalPrice = (($prod['sale_price'] > 0 && $prod['sale_price'] < $prod['price']) ? $prod['sale_price'] : $prod['price']) + $extra;

                $cartKey = $id . '_' . $finalPrice . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $selectedRom . $selectedColor);
                if (isset($_SESSION['cart'][$cartKey])) {
                    $_SESSION['cart'][$cartKey]['quantity'] += 1;
                } else {
                    $_SESSION['cart'][$cartKey] = [
                        'cart_key' => $cartKey, 'id' => $prod['id'], 'name' => $prod['name'],
                        'rom' => $selectedRom, 'color' => $selectedColor, 'price' => $finalPrice,
                        'image' => $image !== '' ? $image : $prod['image'], 'quantity' => 1
                    ];
                }
                if (isset($_SESSION['user']['id'])) {
                    (new UserModel($this->pdo))->updateCart($_SESSION['user']['id'], json_encode($_SESSION['cart'], JSON_UNESCAPED_UNICODE));
                }
            }
            if ($redirect === 'checkout') {
                header('Location: index.php?page=checkout');
                exit;
            }
            if ($isAjax) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                $tot = 0; foreach ($_SESSION['cart'] as $it) { $tot += $it['quantity']; }
                echo json_encode(['success' => true, 'cart_count' => $tot, 'product_name' => $prod['name']], JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: index.php?page=cart');
            exit;
        }

        if ($action === 'update' && !empty($key)) {
            $qty = (int)($_POST['quantity'] ?? 1);
            if ($qty > 0 && isset($_SESSION['cart'][$key])) { $_SESSION['cart'][$key]['quantity'] = $qty; }
            if (isset($_SESSION['user']['id'])) { (new UserModel($this->pdo))->updateCart($_SESSION['user']['id'], json_encode($_SESSION['cart'], JSON_UNESCAPED_UNICODE)); }
            header('Location: index.php?page=cart'); exit;
        }

        if ($action === 'delete' && !empty($key)) {
            unset($_SESSION['cart'][$key]);
            if (isset($_SESSION['user']['id'])) { (new UserModel($this->pdo))->updateCart($_SESSION['user']['id'], json_encode($_SESSION['cart'], JSON_UNESCAPED_UNICODE)); }
            header('Location: index.php?page=cart'); exit;
        }

        if ($action === 'clear') {
            $_SESSION['cart'] = [];
            if (isset($_SESSION['user']['id'])) { (new UserModel($this->pdo))->updateCart($_SESSION['user']['id'], json_encode($_SESSION['cart'], JSON_UNESCAPED_UNICODE)); }
            header('Location: index.php?page=cart'); exit;
        }

        $totalMoney = 0;
        foreach ($_SESSION['cart'] as $item) { $totalMoney += $item['price'] * $item['quantity']; }
        $pageTitle = 'Giỏ hàng của bạn - V-Phone';
        require_once 'app/views/cart.php';
    }

    public function checkout() {
        if (!empty($_GET['action']) && $_GET['action'] === 'buy_now' && isset($_GET['id'])) {
            $prodModel = new ProductModel($this->pdo);
            $prod = $prodModel->getById((int)$_GET['id']);

            if ($prod) {
                $rom = trim($_GET['rom'] ?? $prod['rom']);
                $colors = array_map('trim', explode(',', $prod['colors'] ?? 'Đen, Trắng'));
                $color = trim($_GET['color'] ?? ($colors[0] ?? 'Tiêu chuẩn'));
                $extra = (int)($_GET['extra'] ?? 0);
                $basePrice = ($prod['sale_price'] > 0 && $prod['sale_price'] < $prod['price'])
                    ? $prod['sale_price']
                    : $prod['price'];

                $_SESSION['buy_now_item'] = [
                    'id' => $prod['id'],
                    'name' => $prod['name'],
                    'rom' => $rom,
                    'color' => $color,
                    'price' => $basePrice + $extra,
                    'image' => $prod['image'],
                    'quantity' => 1
                ];
            } else {
                header('Location: index.php');
                exit;
            }

            if (isset($_SESSION['user'])) {
                header('Location: index.php?page=checkout&mode=buy_now');
                exit;
            }

            $_SESSION['post_login_redirect'] = 'index.php?page=checkout&mode=buy_now';
            header('Location: index.php?show_login=1&redirect=checkout');
            exit;
        }

        if (!isset($_SESSION['user'])) {
            $_SESSION['post_login_redirect'] = 'index.php?page=checkout';
            header('Location: index.php?show_login=1&redirect=checkout');
            exit;
        }

        $orderModel = new OrderModel($this->pdo);
        $prodModel = new ProductModel($this->pdo);
        $userModel = new UserModel($this->pdo);
        $voucherModel = new VoucherModel($this->pdo);
        $voucherModel->ensureTable();

        $currentUser = $_SESSION['user'];
        $orderSuccess = false;
        $orderId = 0;

        $isBuyNowMode = (isset($_GET['mode']) && $_GET['mode'] === 'buy_now' && !empty($_SESSION['buy_now_item']));
        $itemsToCheckout = $isBuyNowMode ? [ $_SESSION['buy_now_item'] ] : ($_SESSION['cart'] ?? []);

        $subtotal = 0;
        foreach ($itemsToCheckout as $item) { $subtotal += $item['price'] * $item['quantity']; }

        $voucherMessage = '';
        $appliedVoucher = null;
        $discountAmount = 0;
        $voucherState = $_SESSION['applied_voucher'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_voucher'])) {
            $voucherCode = strtoupper(trim($_POST['voucher_code'] ?? ''));
            $voucher = $voucherModel->findApplicable($voucherCode, $subtotal);

            if ($voucher) {
                $_SESSION['applied_voucher'] = ['code' => $voucher['code']];
                $voucherMessage = 'Áp dụng mã ' . $voucher['code'] . ' thành công.';
            } else {
                unset($_SESSION['applied_voucher']);
                $voucherMessage = 'Mã voucher không hợp lệ hoặc đơn hàng chưa đạt giá trị tối thiểu.';
            }

            $modeQuery = $isBuyNowMode ? '&mode=buy_now' : '';
            header('Location: index.php?page=checkout' . $modeQuery . '&voucher_message=' . rawurlencode($voucherMessage));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_voucher'])) {
            unset($_SESSION['applied_voucher']);
            $modeQuery = $isBuyNowMode ? '&mode=buy_now' : '';
            header('Location: index.php?page=checkout' . $modeQuery);
            exit;
        }

        if (isset($_GET['voucher_message'])) {
            $voucherMessage = trim($_GET['voucher_message']);
        }

        if ($voucherState && $subtotal > 0) {
            $appliedVoucher = $voucherModel->findApplicable($voucherState['code'], $subtotal);
            if ($appliedVoucher) {
                $discountAmount = $voucherModel->calculateDiscount($appliedVoucher, $subtotal);
            } else {
                unset($_SESSION['applied_voucher']);
                $voucherMessage = 'Voucher đã hết hạn, hết lượt hoặc không còn đủ điều kiện.';
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($itemsToCheckout)) {
            $fullname = trim($_POST['fullname'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $note = trim($_POST['note'] ?? '');
            $method = $_POST['payment_method'] ?? 'COD';

            if ($appliedVoucher) {
                $discountAmount = $voucherModel->calculateDiscount($appliedVoucher, $subtotal);
                $note = trim($note . ' | Voucher: ' . $appliedVoucher['code'] . ' (giảm ' . number_format($discountAmount, 0, ',', '.') . ' đ)');
            }
            $totalMoney = max(0, $subtotal - $discountAmount);
            $orderId = $orderModel->create($currentUser['id'], $fullname, $phone, $address, $note, $totalMoney, $method);

            foreach ($itemsToCheckout as $item) {
                $sub = $item['price'] * $item['quantity'];
                $fullVariant = $item['name'] . ' (' . ($item['rom'] ?? '256GB') . ' - Màu: ' . ($item['color'] ?? 'Tiêu chuẩn') . ')';
                $orderModel->addDetail($orderId, $item['id'], $fullVariant, $item['price'], $item['quantity'], $sub);
                $prodModel->deductStock($item['id'], $item['quantity']);
            }

            if ($appliedVoucher) {
                $voucherModel->recordUse((int)$appliedVoucher['id']);
                unset($_SESSION['applied_voucher']);
            }

            if ($isBuyNowMode) { unset($_SESSION['buy_now_item']); }
            else { $_SESSION['cart'] = []; $userModel->updateCart($currentUser['id'], null); }
            $orderSuccess = true;
        }

        $totalMoney = max(0, $subtotal - $discountAmount);
        $pageTitle = 'Thanh toán đơn hàng - V-Phone';
        require_once 'app/views/checkout.php';
    }
}
