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
                // Kiểm tra tồn kho trước khi cho thêm vào giỏ
                if ((int)$prod['quantity'] <= 0) {
                    if ($isAjax) {
                        if (ob_get_length()) ob_clean();
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'error' => 'out_of_stock', 'message' => 'Sản phẩm "' . $prod['name'] . '" hiện đã hết hàng!'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    $_SESSION['cart_error'] = 'Sản phẩm "' . $prod['name'] . '" hiện đã hết hàng!';
                    header('Location: index.php?page=cart');
                    exit;
                }

                $selectedRom = !empty($rom) ? $rom : $prod['rom'];
                $cList = array_map("trim", explode(",", $prod["colors"] ?? "Đen, Trắng"));
                $selectedColor = !empty($color) ? $color : ($cList[0] ?? 'Tiêu chuẩn');

                // Kiểm tra tồn kho của màu được chọn nếu sản phẩm có phân bổ tồn theo màu
                $cQuantities = json_decode($prod['color_quantities'] ?? '', true);
                if (is_array($cQuantities) && !empty($cQuantities)) {
                    foreach ($cQuantities as $cq) {
                        if (isset($cq['color']) && mb_strtolower(trim($cq['color'])) === mb_strtolower(trim($selectedColor))) {
                            if ((int)$cq['quantity'] <= 0) {
                                if ($isAjax) {
                                    if (ob_get_length()) ob_clean();
                                    header('Content-Type: application/json; charset=utf-8');
                                    echo json_encode(['success' => false, 'error' => 'color_out_of_stock', 'message' => 'Màu "' . $selectedColor . '" của ' . $prod['name'] . ' hiện đã hết hàng!'], JSON_UNESCAPED_UNICODE);
                                    exit;
                                }
                                $_SESSION['cart_error'] = 'Màu "' . $selectedColor . '" của ' . $prod['name'] . ' hiện đã hết hàng!';
                                header('Location: index.php?page=cart');
                                exit;
                            }
                            break;
                        }
                    }
                }

                $finalPrice = (($prod['sale_price'] > 0 && $prod['sale_price'] < $prod['price']) ? $prod['sale_price'] : $prod['price']) + $extra;

                $cartKey = $id . '_' . $finalPrice . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $selectedRom . $selectedColor);

                // Kiểm tra tổng số lượng trong giỏ không vượt quá tồn kho
                $currentQtyInCart = 0;
                foreach ($_SESSION['cart'] as $item) {
                    if ((int)$item['id'] === $id) {
                        $currentQtyInCart += $item['quantity'];
                    }
                }
                if ($currentQtyInCart >= (int)$prod['quantity']) {
                    if ($isAjax) {
                        if (ob_get_length()) ob_clean();
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'error' => 'exceed_stock', 'message' => 'Số lượng trong giỏ đã đạt tối đa tồn kho (' . $prod['quantity'] . ' máy)!'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    $_SESSION['cart_error'] = 'Số lượng trong giỏ đã đạt tối đa tồn kho (' . $prod['quantity'] . ' máy)!';
                    header('Location: index.php?page=cart');
                    exit;
                }

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
        if (!empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $k => &$item) {
                if (!isset($item['price']) || (float)$item['price'] <= 0) {
                    $pId = (int)($item['id'] ?? 0);
                    if ($pId > 0) {
                        $pRow = $this->pdo->query("SELECT price, sale_price FROM products WHERE id = " . $pId)->fetch();
                        if ($pRow) {
                            $bPrice = ($pRow['sale_price'] > 0 && $pRow['sale_price'] < $pRow['price']) ? $pRow['sale_price'] : $pRow['price'];
                            $item['price'] = (float)$bPrice;
                        }
                    }
                }
                $itemPrice = (float)($item['price'] ?? 0);
                $itemQty = (int)($item['quantity'] ?? 1);
                $totalMoney += $itemPrice * $itemQty;
            }
            unset($item);
        }
        $pageTitle = 'Giỏ hàng của bạn - V-Phone';
        require_once 'app/views/cart.php';
    }

    public function checkout() {
        $isBuyNowMode = (isset($_GET['mode']) && $_GET['mode'] === 'buy_now') || (!empty($_GET['action']) && $_GET['action'] === 'buy_now');

        if (!empty($_GET['action']) && $_GET['action'] === 'buy_now' && isset($_GET['id'])) {
            $prodModel = new ProductModel($this->pdo);
            $prod = $prodModel->getById((int)$_GET['id']);

            if ($prod) {
                // Kiểm tra tồn kho trước khi cho mua ngay
                if ((int)$prod['quantity'] <= 0) {
                    $_SESSION['cart_error'] = 'Sản phẩm "' . $prod['name'] . '" hiện đã hết hàng, không thể đặt mua!';
                    header('Location: index.php');
                    exit;
                }

                $rom = trim($_GET['rom'] ?? $prod['rom']);
                $colors = array_map('trim', explode(',', $prod['colors'] ?? 'Đen, Trắng'));
                $color = trim($_GET['color'] ?? ($colors[0] ?? 'Tiêu chuẩn'));

                // Kiểm tra tồn kho của màu được chọn
                $cQuantities = json_decode($prod['color_quantities'] ?? '', true);
                if (is_array($cQuantities) && !empty($cQuantities)) {
                    foreach ($cQuantities as $cq) {
                        if (isset($cq['color']) && mb_strtolower(trim($cq['color'])) === mb_strtolower(trim($color))) {
                            if ((int)$cq['quantity'] <= 0) {
                                $_SESSION['cart_error'] = 'Màu "' . $color . '" của ' . $prod['name'] . ' hiện đã hết hàng, vui lòng chọn màu khác!';
                                header('Location: index.php?page=detail&id=' . $prod['id']);
                                exit;
                            }
                            break;
                        }
                    }
                }

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
                    'image' => !empty($_GET['img']) ? trim($_GET['img']) : $prod['image'],
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
            $redirectMode = $isBuyNowMode ? '&mode=buy_now' : '';
            $_SESSION['post_login_redirect'] = 'index.php?page=checkout' . $redirectMode;
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

        if ($isBuyNowMode && !empty($_SESSION['buy_now_item'])) {
            if (!isset($_SESSION['buy_now_item']['price']) || (float)$_SESSION['buy_now_item']['price'] <= 0) {
                $pId = (int)($_SESSION['buy_now_item']['id'] ?? 0);
                if ($pId > 0) {
                    $pRow = (new ProductModel($this->pdo))->getById($pId);
                    if ($pRow) {
                        $bPrice = ($pRow['sale_price'] > 0 && $pRow['sale_price'] < $pRow['price']) ? $pRow['sale_price'] : $pRow['price'];
                        $_SESSION['buy_now_item']['price'] = (float)$bPrice;
                    }
                }
            }
        }
        $itemsToCheckout = ($isBuyNowMode && !empty($_SESSION['buy_now_item'])) ? [ $_SESSION['buy_now_item'] ] : ($_SESSION['cart'] ?? []);
        $itemsToCheckout = array_values(array_filter($itemsToCheckout, static fn($i) => !empty($i) && is_array($i)));

        $subtotal = 0;
        foreach ($itemsToCheckout as $item) {
            $itemPrice = (float)($item['price'] ?? 0);
            $itemQty = (int)($item['quantity'] ?? 1);
            $subtotal += $itemPrice * $itemQty;
        }

        $voucherMessage = '';
        $appliedVoucher = null;
        $discountAmount = 0;
        $voucherState = $_SESSION['applied_voucher'] ?? null;
        $checkoutError = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_voucher'])) {
            if (!empty($_POST['fullname'])) {
                $_SESSION['checkout_form_draft'] = [
                    'fullname' => trim($_POST['fullname']),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'address' => trim($_POST['address'] ?? ''),
                    'note' => trim($_POST['note'] ?? '')
                ];
            }
            $voucherCode = strtoupper(trim($_POST['voucher_code'] ?? ''));
            $voucher = $voucherModel->findApplicable($voucherCode, $subtotal);

            if ($voucher) {
                $_SESSION['applied_voucher'] = ['code' => $voucher['code']];
                $voucherMessage = 'Áp dụng mã ' . $voucher['code'] . ' thành công.';
            } else {
                unset($_SESSION['applied_voucher']);
                $voucherMessage = 'Mã voucher không hợp lệ hoặc đơn hàng chưa đạt giá trị tối thiểu.';
            }

            if ((isset($_POST['ajax']) && $_POST['ajax'] == 1) || (isset($_GET['ajax']) && $_GET['ajax'] == 1)) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                $disc = $voucher ? $voucherModel->calculateDiscount($voucher, $subtotal) : 0;
                echo json_encode([
                    'success' => (bool)$voucher,
                    'message' => $voucherMessage,
                    'code' => $voucher ? $voucher['code'] : '',
                    'discount' => $disc,
                    'discount_formatted' => number_format($disc, 0, ',', '.') . ' đ',
                    'total' => max(0, $subtotal - $disc),
                    'total_formatted' => number_format(max(0, $subtotal - $disc), 0, ',', '.') . ' đ'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $modeQuery = $isBuyNowMode ? '&mode=buy_now' : '';
            header('Location: index.php?page=checkout' . $modeQuery . '&voucher_message=' . rawurlencode($voucherMessage));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_voucher'])) {
            if (!empty($_POST['fullname'])) {
                $_SESSION['checkout_form_draft'] = [
                    'fullname' => trim($_POST['fullname']),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'address' => trim($_POST['address'] ?? ''),
                    'note' => trim($_POST['note'] ?? '')
                ];
            }
            unset($_SESSION['applied_voucher']);

            if ((isset($_POST['ajax']) && $_POST['ajax'] == 1) || (isset($_GET['ajax']) && $_GET['ajax'] == 1)) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => 'Đã gỡ mã giảm giá.',
                    'discount' => 0,
                    'discount_formatted' => '0 đ',
                    'total' => $subtotal,
                    'total_formatted' => number_format($subtotal, 0, ',', '.') . ' đ'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

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

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($itemsToCheckout) && !isset($_POST['apply_voucher']) && !isset($_POST['remove_voucher'])) {
            $fullname = trim($_POST['fullname'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $note = trim($_POST['note'] ?? '');
            $method = $_POST['payment_method'] ?? 'COD';
            $paymentMethod = $method;

            if (empty($fullname) || empty($phone) || empty($address)) {
                $checkoutError = 'Vui lòng điền đầy đủ họ tên, số điện thoại và địa chỉ nhận hàng!';
            } else {
                if ($appliedVoucher) {
                    $discountAmount = $voucherModel->calculateDiscount($appliedVoucher, $subtotal);
                    $note = trim($note . ' | Voucher: ' . $appliedVoucher['code'] . ' (giảm ' . number_format($discountAmount, 0, ',', '.') . ' đ)');
                }
                $totalMoney = max(0, $subtotal - $discountAmount);
                $orderId = $orderModel->create($currentUser['id'], $fullname, $phone, $address, $note, $totalMoney, $method);

                foreach ($itemsToCheckout as $item) {
                    $itemPrice = (float)($item['price'] ?? 0);
                    $itemQty = (int)($item['quantity'] ?? 1);
                    $sub = $itemPrice * $itemQty;
                    $fullVariant = $item['name'] . ' (' . ($item['rom'] ?? '256GB') . ' - Màu: ' . ($item['color'] ?? 'Tiêu chuẩn') . ')';
                    $orderModel->addDetail($orderId, $item['id'], $fullVariant, $itemPrice, $itemQty, $sub);
                    $prodModel->deductStock($item['id'], $itemQty, $item['color'] ?? '');
                }

                if ($appliedVoucher) {
                    $voucherModel->recordUse((int)$appliedVoucher['id']);
                    unset($_SESSION['applied_voucher']);
                }

                if ($isBuyNowMode) { unset($_SESSION['buy_now_item']); }
                else { $_SESSION['cart'] = []; $userModel->updateCart($currentUser['id'], null); }
                unset($_SESSION['checkout_form_draft']);
                $orderSuccess = true;
            }
        }

        $totalMoney = max(0, $subtotal - $discountAmount);
        $pageTitle = 'Thanh toán đơn hàng - V-Phone';
        require_once 'app/views/checkout.php';
    }
}
