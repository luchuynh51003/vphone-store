<?php
require_once __DIR__ . '/../models/ReviewModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/NotificationModel.php';

class ReviewController {
    private PDO $pdo;
    private ReviewModel $reviewModel;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->reviewModel = new ReviewModel($pdo);
    }

    public function handle(): void {
        $action = $_GET['action'] ?? ($_POST['action'] ?? 'index');

        if ($action === 'submit') {
            $this->submitReview();
        } elseif ($action === 'get_list') {
            $this->getList();
        } elseif ($action === 'get_order_info') {
            $this->getOrderInfo();
        } elseif ($action === 'get_unreviewed') {
            $this->getUnreviewed();
        } else {
            header('Location: index.php');
            exit;
        }
    }

    public function submitReview(): void {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                  || isset($_GET['ajax']) || isset($_POST['ajax']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Phương thức yêu cầu không hợp lệ.']);
                exit;
            }
            header('Location: index.php');
            exit;
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $orderId = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
        $reviewId = !empty($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
        $rating = (int)($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');

        $currentUser = $_SESSION['user'] ?? null;
        $fullname = trim($_POST['fullname'] ?? ($currentUser['fullname'] ?? ''));
        $email = trim($_POST['email'] ?? ($currentUser['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? ($currentUser['phone'] ?? ''));
        $userId = $currentUser ? (int)$currentUser['id'] : null;

        // Nếu có mã đơn hàng, tự động bổ sung thông tin từ đơn hàng nếu khách chưa điền
        if ($orderId > 0 && (empty($fullname) || empty($phone))) {
            $ordStmt = $this->pdo->prepare("SELECT fullname, phone, user_id FROM orders WHERE id = ?");
            $ordStmt->execute([$orderId]);
            $ordData = $ordStmt->fetch(PDO::FETCH_ASSOC);
            if ($ordData) {
                if (empty($fullname)) $fullname = $ordData['fullname'];
                if (empty($phone)) $phone = $ordData['phone'];
                if (empty($userId) && !empty($ordData['user_id'])) $userId = (int)$ordData['user_id'];
            }
        }

        // Validation
        if ($productId <= 0) {
            $this->respond($isAjax, false, 'Không tìm thấy thông tin sản phẩm cần đánh giá.', $productId);
            return;
        }

        if (empty($fullname)) {
            $this->respond($isAjax, false, 'Vui lòng nhập họ và tên của bạn.', $productId);
            return;
        }

        if ($rating < 1 || $rating > 5) {
            $this->respond($isAjax, false, 'Vui lòng chọn số sao đánh giá hợp lệ từ 1 đến 5 sao.', $productId);
            return;
        }

        if (mb_strlen($comment, 'UTF-8') < 6) {
            $this->respond($isAjax, false, 'Nội dung nhận xét tối thiểu 6 ký tự để cộng đồng có thêm thông tin hữu ích.', $productId);
            return;
        }

        // Tự động kiểm tra khách đã mua hàng hay chưa (hoặc có order_id hợp lệ)
        $isBuyer = ($orderId > 0) ? true : $this->reviewModel->hasUserPurchased($productId, $userId, $phone);

        $savedReviewId = 0;
        $isUpdate = false;

        if ($reviewId > 0) {
            $updateResult = $this->reviewModel->updateReview($reviewId, [
                'rating' => $rating,
                'comment' => $comment,
                'fullname' => $fullname,
                'email' => $email,
                'phone' => $phone
            ]);

            if (!$updateResult['success']) {
                $this->respond($isAjax, false, $updateResult['message'], $productId);
                return;
            }
            $savedReviewId = $reviewId;
            $isUpdate = true;
            $msg = 'Cập nhật đánh giá thành công! Bạn đã hoàn thành lần chỉnh sửa duy nhất.';
        } else {
            $savedReviewId = $this->reviewModel->addReview([
                'product_id' => $productId,
                'user_id' => $userId,
                'order_id' => $orderId,
                'fullname' => $fullname,
                'email' => $email,
                'phone' => $phone,
                'rating' => $rating,
                'comment' => $comment,
                'is_buyer' => $isBuyer ? 1 : 0
            ]);

            if ($savedReviewId === -1) {
                $this->respond($isAjax, false, 'Đánh giá này đã được chỉnh sửa 1 lần trước đó rồi. Mỗi sản phẩm chỉ được sửa đánh giá 1 lần duy nhất!', $productId);
                return;
            }

            if ($savedReviewId <= 0) {
                $this->respond($isAjax, false, 'Có lỗi xảy ra khi lưu đánh giá. Vui lòng thử lại sau ít phút.', $productId);
                return;
            }

            // Kiểm tra xem review này có vừa được cập nhật hay thêm mới
            $checkRev = $this->reviewModel->getReviewById($savedReviewId);
            $isUpdate = !empty($checkRev['is_edited']);

            if ($isUpdate) {
                $msg = 'Cập nhật đánh giá thành công! Bạn đã hoàn thành lần chỉnh sửa duy nhất.';
            } else {
                $msg = 'Cảm ơn bạn đã gửi đánh giá! Nhận xét ' . $rating . ' sao của bạn đã được ghi nhận.';
                if ($isBuyer) {
                    $msg .= ' (Đã gắn huy hiệu Khách hàng đã mua hàng tại V-Phone).';
                }
            }
        }

        // Tự động đánh dấu thông báo giao hàng là đã đọc khi khách đã đánh giá
        if ($orderId > 0) {
            try {
                if ($userId) {
                    $this->pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE order_id = ? AND user_id = ? AND type = 'order_delivered'")->execute([$orderId, $userId]);
                }
            } catch (\Throwable $t) {}
        }

        $summary = $this->reviewModel->getRatingSummary($productId);
        $orderInfo = null;
        if ($orderId > 0) {
            $orderInfo = $this->reviewModel->getOrderReviewDetails($orderId, $userId, $phone);
        }

        $this->respond($isAjax, true, $msg, $productId, [
            'review_id' => $savedReviewId,
            'order_id' => $orderId,
            'product_id' => $productId,
            'rating' => $rating,
            'is_buyer' => $isBuyer,
            'is_edited' => $isUpdate ? 1 : 0,
            'can_edit' => !$isUpdate,
            'summary' => $summary,
            'order_info' => $orderInfo
        ]);
    }

    public function getOrderInfo(): void {
        header('Content-Type: application/json; charset=utf-8');
        $orderId = (int)($_GET['order_id'] ?? ($_POST['order_id'] ?? 0));
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã đơn hàng không hợp lệ.']);
            exit;
        }

        $currentUser = $_SESSION['user'] ?? null;
        $userId = $currentUser ? (int)$currentUser['id'] : null;
        $phone = trim($_GET['phone'] ?? ($_POST['phone'] ?? ($currentUser['phone'] ?? '')));

        $info = $this->reviewModel->getOrderReviewDetails($orderId, $userId, $phone);
        if (!$info) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin đơn hàng hoặc bạn không có quyền truy cập.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'order' => [
                'id' => (int)$info['order']['id'],
                'fullname' => $info['order']['fullname'],
                'phone' => $info['order']['phone'],
                'status' => $info['order']['status'],
                'total_money' => (float)$info['order']['total_money'],
                'created_at' => $info['order']['created_at']
            ],
            'items' => $info['items'],
            'unreviewed_count' => $info['unreviewed_count'],
            'all_reviewed' => $info['all_reviewed']
        ]);
        exit;
    }

    public function getUnreviewed(): void {
        header('Content-Type: application/json; charset=utf-8');
        $currentUser = $_SESSION['user'] ?? null;
        $userId = $currentUser ? (int)$currentUser['id'] : null;
        $phone = trim($_GET['phone'] ?? ($currentUser['phone'] ?? ''));

        if (!$userId && empty($phone)) {
            echo json_encode(['success' => true, 'count' => 0, 'items' => []]);
            exit;
        }

        $items = $this->reviewModel->getDeliveredUnreviewedItems($userId, $phone, 6);
        echo json_encode([
            'success' => true,
            'count' => count($items),
            'items' => $items
        ]);
        exit;
    }

    public function getList(): void {
        header('Content-Type: application/json; charset=utf-8');
        $productId = (int)($_GET['product_id'] ?? 0);
        $starParam = isset($_GET['star']) && $_GET['star'] !== '' && $_GET['star'] !== 'all' ? (int)$_GET['star'] : null;
        $onlyReply = !empty($_GET['only_reply']);

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ.']);
            exit;
        }

        $reviews = $this->reviewModel->getReviewsByProduct($productId, $starParam, $onlyReply);
        $summary = $this->reviewModel->getRatingSummary($productId);

        echo json_encode([
            'success' => true,
            'count' => count($reviews),
            'reviews' => $reviews,
            'summary' => $summary
        ]);
        exit;
    }

    private function respond(bool $isAjax, bool $success, string $message, int $productId = 0, array $extra = []): void {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array_merge([
                'success' => $success,
                'message' => $message
            ], $extra));
            exit;
        }

        if ($success) {
            $_SESSION['flash_success'] = $message;
        } else {
            $_SESSION['flash_error'] = $message;
        }

        $redirect = 'index.php';
        if ($productId > 0) {
            $redirect = 'index.php?page=detail&id=' . $productId . '#reviews-section';
        }
        header('Location: ' . $redirect);
        exit;
    }
}
