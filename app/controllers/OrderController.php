<?php
require_once 'app/models/OrderModel.php';
require_once 'app/models/ProductModel.php';
require_once 'app/models/UserModel.php';

class OrderController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $orderModel = new OrderModel($this->pdo);
        $prodModel = new ProductModel($this->pdo);

        $currentUser = $_SESSION['user'] ?? null;
        $orderMessage = $_SESSION['order_message'] ?? '';
        $orderError = $_SESSION['order_error'] ?? '';
        unset($_SESSION['order_message'], $_SESSION['order_error']);

        $action = $_POST['action'] ?? ($_GET['action'] ?? '');

        if ($action === 'cancel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $cancelOrderId = (int)($_POST['order_id'] ?? 0);
            $cancelReason = trim($_POST['cancel_reason'] ?? '');
            $cancelPhone = trim($_POST['phone'] ?? '');

            if ($cancelOrderId <= 0) {
                $_SESSION['order_error'] = 'Mã đơn hàng không hợp lệ.';
                header('Location: index.php?page=orders');
                exit;
            }

            $order = $orderModel->getById($cancelOrderId);
            if (!$order) {
                $_SESSION['order_error'] = 'Không tìm thấy đơn hàng cần hủy.';
                header('Location: index.php?page=orders');
                exit;
            }

            $isAuthorized = false;
            if ($currentUser && (int)$order['user_id'] === (int)$currentUser['id']) {
                $isAuthorized = true;
            } elseif (!empty($cancelPhone)) {
                $cleanInputPhone = preg_replace('/[^0-9]/', '', $cancelPhone);
                $cleanOrderPhone = preg_replace('/[^0-9]/', '', $order['phone']);
                if ($cleanInputPhone === $cleanOrderPhone || substr($cleanOrderPhone, -9) === substr($cleanInputPhone, -9)) {
                    $isAuthorized = true;
                }
            }

            if (!$isAuthorized) {
                $_SESSION['order_error'] = 'Bạn không có quyền thực hiện hủy đơn hàng này.';
                header('Location: index.php?page=orders');
                exit;
            }

            $res = $orderModel->cancelOrder($cancelOrderId, $currentUser ? (int)$currentUser['id'] : null, $cancelReason);
            if ($res['success']) {
                $_SESSION['order_message'] = $res['message'];
            } else {
                $_SESSION['order_error'] = $res['message'];
            }

            $redirectUrl = 'index.php?page=orders';
            if (!empty($_POST['redirect_to_tracking'])) {
                $redirectUrl .= '&order_code=' . $cancelOrderId . '&phone=' . rawurlencode($order['phone']);
            }
            header('Location: ' . $redirectUrl);
            exit;
        }

        if ($action === 'reorder' && isset($_GET['order_id'])) {
            $reorderId = (int)$_GET['order_id'];
            $order = $orderModel->getById($reorderId);

            if ($order) {
                if ($currentUser && (int)$order['user_id'] !== (int)$currentUser['id'] && (int)$currentUser['role'] !== 1) {
                    $_SESSION['order_error'] = 'Không thể sao chép đơn hàng không thuộc về bạn.';
                    header('Location: index.php?page=orders');
                    exit;
                }

                $details = $orderModel->getDetailsByOrderId($reorderId);
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }

                $addedCount = 0;
                foreach ($details as $d) {
                    $pId = (int)($d['product_id'] ?? 0);
                    if ($pId <= 0) continue;

                    $prod = $prodModel->getById($pId);
                    if (!$prod) continue;

                    $rom = $prod['rom'];
                    if (preg_match('/\((\d+\s*GB|\d+\s*TB)/i', $d['product_name'], $m)) {
                        $rom = trim($m[1]);
                    }
                    $color = 'Tiêu chuẩn';
                    if (preg_match('/Màu:\s*([^)]+)/u', $d['product_name'], $m)) {
                        $color = trim($m[1]);
                    }

                    $unitPrice = (float)$d['price'];
                    $cartKey = $pId . '_' . $unitPrice . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $rom . $color);

                    if (isset($_SESSION['cart'][$cartKey])) {
                        $_SESSION['cart'][$cartKey]['quantity'] += (int)$d['quantity'];
                    } else {
                        $_SESSION['cart'][$cartKey] = [
                            'cart_key' => $cartKey,
                            'id' => $prod['id'],
                            'name' => $prod['name'],
                            'rom' => $rom,
                            'color' => $color,
                            'price' => $unitPrice,
                            'image' => $d['product_image'] ?? $prod['image'],
                            'quantity' => (int)$d['quantity']
                        ];
                    }
                    $addedCount++;
                }

                if ($currentUser && isset($currentUser['id'])) {
                    (new UserModel($this->pdo))->updateCart($currentUser['id'], json_encode($_SESSION['cart'], JSON_UNESCAPED_UNICODE));
                }

                $_SESSION['cart_message'] = "Đã thêm các sản phẩm từ đơn hàng #VP-$reorderId vào giỏ hàng!";
                header('Location: index.php?page=cart');
                exit;
            }
        }

        $trackedOrder = null;
        $trackedDetails = [];
        $lookupCode = trim($_POST['order_code'] ?? ($_GET['order_code'] ?? ($_GET['id'] ?? ($_GET['order_id'] ?? ''))));
        $lookupPhone = trim($_POST['phone'] ?? ($_GET['phone'] ?? ''));

        if (!empty($lookupCode)) {
            $cleanId = (int)preg_replace('/[^0-9]/', '', $lookupCode);

            if ($cleanId > 0) {
                if ($currentUser) {
                    $order = $orderModel->getById($cleanId);
                    if ($order && ((int)$order['user_id'] === (int)$currentUser['id'] || (int)$currentUser['role'] === 1)) {
                        $trackedOrder = $order;
                        $trackedDetails = $orderModel->getDetailsByOrderId($cleanId);
                    }
                }

                if (!$trackedOrder && !empty($lookupPhone)) {
                    $found = $orderModel->findOrderForTracking($cleanId, $lookupPhone);
                    if ($found) {
                        $trackedOrder = $found;
                        $trackedDetails = $orderModel->getDetailsByOrderId($found['id']);
                    } else {
                        $orderError = "Không tìm thấy đơn hàng #VP-$cleanId với số điện thoại đã nhập. Vui lòng kiểm tra lại!";
                    }
                } elseif (!$trackedOrder && empty($lookupPhone)) {
                    $order = $orderModel->getById($cleanId);
                    if ($order) {
                        $orderError = "Để đảm bảo an toàn thông tin, vui lòng nhập Số điện thoại đã dùng để đặt đơn hàng này.";
                    } else {
                        $orderError = "Không tìm thấy đơn hàng #VP-$cleanId trong hệ thống.";
                    }
                }
            }
        }

        $userOrders = [];
        $userOrderDetails = [];
        $statusCounts = ['all' => 0, 'Chờ xử lý' => 0, 'Đang giao hàng' => 0, 'Đã giao thành công' => 0, 'Đã hủy' => 0];
        $activeTab = $_GET['status'] ?? 'all';
        $searchKeyword = trim($_GET['q'] ?? '');

        if ($currentUser) {
            $userOrders = $orderModel->getByUserId((int)$currentUser['id'], $activeTab);
            $statusCounts = $orderModel->getStatusCountsByUserId((int)$currentUser['id']);

            if (!empty($searchKeyword)) {
                $kw = mb_strtolower($searchKeyword, 'UTF-8');
                $userOrders = array_filter($userOrders, function($o) use ($kw) {
                    $orderText = mb_strtolower('#vp-' . $o['id'] . ' ' . $o['id'] . ' ' . $o['fullname'] . ' ' . $o['phone'] . ' ' . $o['address'], 'UTF-8');
                    return strpos($orderText, $kw) !== false;
                });
            }

            if (!empty($userOrders)) {
                $orderIds = array_column($userOrders, 'id');
                $userOrderDetails = $orderModel->getDetailsForOrders($orderIds);
            }
        }

        $pageTitle = 'Theo dõi đơn hàng & Lịch sử mua hàng - V-Phone';
        require_once 'app/views/order_tracking.php';
    }
}

