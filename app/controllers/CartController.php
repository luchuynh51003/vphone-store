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
        $action = $_GET['action'] ?? ($_POST['action'] ?? '');

        // API kiểm tra trạng thái thanh toán tự động (Auto-polling)
        if ($action === 'check_payment') {
            require_once 'app/services/VietQRService.php';
            $vietqr = new VietQRService($this->pdo);
            $checkId = (int)($_GET['order_id'] ?? ($_POST['order_id'] ?? 0));
            $res = $vietqr->checkPaymentStatus($checkId);
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // API giả lập khách chuyển khoản thành công (Demo Simulator)
        if ($action === 'simulate_payment') {
            require_once 'app/services/VietQRService.php';
            $vietqr = new VietQRService($this->pdo);
            $checkId = (int)($_GET['order_id'] ?? ($_POST['order_id'] ?? 0));
            $res = $vietqr->simulatePayment($checkId);
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Action kích hoạt thanh toán MoMo Sandbox (từ trang tracking hoặc checkout)
        if ($action === 'pay_momo') {
            $orderId = (int)($_GET['order_id'] ?? ($_POST['order_id'] ?? 0));
            header('Location: index.php?page=checkout&step=momo_sandbox&order_id=' . $orderId);
            exit;
        }

        // Action xác nhận giả lập thanh toán MoMo thành công 1-click
        if ($action === 'momo_confirm' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderId = (int)($_POST['order_id'] ?? 0);
            require_once 'app/models/OrderModel.php';
            require_once 'app/services/MoMoService.php';
            $momoService = new MoMoService($this->pdo);
            $simTransId = 'MOMO_' . date('His') . rand(1000, 9999);
            $momoService->markAsPaid($orderId, $simTransId);
            header('Location: index.php?page=checkout&step=momo_return&orderId=VP_' . $orderId . '&resultCode=0&transId=' . $simTransId);
            exit;
        }

        // Trang Cổng MoMo Sandbox Developer (kèm nút giả lập xác nhận 1-click)
        if (isset($_GET['step']) && $_GET['step'] === 'momo_sandbox') {
            $orderId = (int)($_GET['order_id'] ?? 0);
            require_once 'app/models/OrderModel.php';
            require_once 'app/services/MoMoService.php';
            $orderModel = new OrderModel($this->pdo);
            $momoService = new MoMoService($this->pdo);
            $order = $orderModel->getById($orderId);
            if (!$order) {
                header('Location: index.php?page=cart');
                exit;
            }
            $momoRes = $momoService->createPayment($orderId, $order['total_money']);
            $momoPayUrl = $momoRes['payUrl'] ?? '';
            $momoQrCode = $momoRes['qrCodeUrl'] ?? '';
            $pageTitle = 'Cổng thanh toán MoMo Sandbox - Đơn hàng #VP-' . $orderId;
            require_once 'app/views/momo_payment.php';
            exit;
        }

        // Trang quét mã VietQR tự động
        if (isset($_GET['step']) && $_GET['step'] === 'vietqr') {
            $orderId = (int)($_GET['order_id'] ?? 0);
            require_once 'app/models/OrderModel.php';
            require_once 'app/services/VietQRService.php';
            $orderModel = new OrderModel($this->pdo);
            $vietqrService = new VietQRService($this->pdo);
            $order = $orderModel->getById($orderId);
            if (!$order) {
                header('Location: index.php?page=cart');
                exit;
            }
            $currentUser = $_SESSION['user'] ?? null;
            if ($currentUser && (int)$order['user_id'] !== (int)$currentUser['id'] && (int)$currentUser['role'] !== 1) {
                header('Location: index.php?page=orders');
                exit;
            }
            $qrUrl = $vietqrService->generateQrUrl($orderId, $order['total_money']);
            $transferContent = $vietqrService->getTransferContent($orderId);
            $bankConfig = $vietqrService->getConfig();
            $orderDetails = $orderModel->getDetailsByOrderId($orderId);
            $pageTitle = 'Cổng thanh toán VietQR tự động - Đơn hàng #VP-' . $orderId;
            require_once 'app/views/vietqr_payment.php';
            exit;
        }

        // Xử lý khi khách quay lại từ Cổng MoMo Sandbox
        if (isset($_GET['step']) && $_GET['step'] === 'momo_return') {
            require_once 'app/models/OrderModel.php';
            require_once 'app/services/MoMoService.php';
            $momoService = new MoMoService($this->pdo);
            $orderModel = new OrderModel($this->pdo);

            $rawOrderId = $_GET['orderId'] ?? '';
            $orderId = $momoService->extractOrderId($rawOrderId);
            $resultCode = (int)($_GET['resultCode'] ?? -1);
            $transId = (string)($_GET['transId'] ?? ($_GET['requestId'] ?? time()));
            $momoMessage = $_GET['message'] ?? '';

            $order = $orderModel->getById($orderId);
            $orderSuccess = false;
            $checkoutError = '';
            $paymentMethod = 'Ví điện tử MoMo';

            if ($order) {
                if ($resultCode === 0) {
                    $momoService->markAsPaid($orderId, $transId, (float)($order['total_money'] ?? 0));
                    $orderSuccess = true;
                } else {
                    $checkoutError = 'Giao dịch MoMo không thành công: ' . htmlspecialchars($momoMessage ?: 'Bạn đã hủy thanh toán trên MoMo.');
                }
            } else {
                $checkoutError = 'Không tìm thấy thông tin đơn hàng từ phản hồi MoMo.';
            }

            $currentUser = $_SESSION['user'] ?? null;
            $deliveryPhone = $order['phone'] ?? '';
            $pageTitle = $orderSuccess ? 'Thanh toán MoMo thành công - V-Phone' : 'Kết quả thanh toán MoMo - V-Phone';
            require_once 'app/views/checkout.php';
            exit;
        }

        $isBuyNowMode = (isset($_GET['mode']) && $_GET['mode'] === 'buy_now') || (!empty($_GET['action']) && $_GET['action'] === 'buy_now');

        if (!empty($_GET['action']) && $_GET['action'] === 'buy_now' && isset($_GET['id'])) {
            $prodModel = new ProductModel($this->pdo);
            $prod = $prodModel->getById((int)$_GET['id']);

            if ($prod) {
                if ((int)$prod['quantity'] <= 0) {
                    $_SESSION['cart_error'] = 'Sản phẩm "' . $prod['name'] . '" hiện đã hết hàng, không thể đặt mua!';
                    header('Location: index.php');
                    exit;
                }

                $rom = trim($_GET['rom'] ?? $prod['rom']);
                $colors = array_map('trim', explode(',', $prod['colors'] ?? 'Đen, Trắng'));
                $color = trim($_GET['color'] ?? ($colors[0] ?? 'Tiêu chuẩn'));

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

        require_once 'app/models/AddressModel.php';
        $addressModel = new AddressModel($this->pdo);

        $currentUser = $_SESSION['user'];
        $userAddresses = $addressModel->getByUserId((int)$currentUser['id']);
        $defaultAddress = $addressModel->getDefaultByUserId((int)$currentUser['id']);
        if ($defaultAddress) {
            if (empty($currentUser['phone'])) $currentUser['phone'] = $defaultAddress['phone'];
            if (empty($currentUser['address'])) $currentUser['address'] = $defaultAddress['address'];
        }

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
                    'discount_formatted' => number_format($disc, 0, ',', '.') . ' VNĐ',
                    'total' => max(0, $subtotal - $disc),
                    'total_formatted' => number_format(max(0, $subtotal - $disc), 0, ',', '.') . ' VNĐ'
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
                    'discount_formatted' => '0 VNĐ',
                    'total' => $subtotal,
                    'total_formatted' => number_format($subtotal, 0, ',', '.') . ' VNĐ'
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
                    $note = trim($note . ' | Voucher: ' . $appliedVoucher['code'] . ' (giảm ' . number_format($discountAmount, 0, ',', '.') . ' VNĐ)');
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

                // Nếu chọn phương thức VietQR, chuyển ngay sang Cổng thanh toán VietQR tự động
                if ($method === 'Chuyển khoản QR VietQR') {
                    header('Location: index.php?page=checkout&step=vietqr&order_id=' . $orderId);
                    exit;
                }

                // Nếu chọn phương thức MoMo Sandbox, chuyển sang Cổng MoMo Sandbox (có mã QR & nút xác nhận 1-click)
                if ($method === 'Ví điện tử MoMo') {
                    header('Location: index.php?page=checkout&step=momo_sandbox&order_id=' . $orderId);
                    exit;
                }

                $orderSuccess = true;
            }
        }

        $totalMoney = max(0, $subtotal - $discountAmount);
        $pageTitle = 'Thanh toán đơn hàng - V-Phone';
        require_once 'app/views/checkout.php';
    }
}
