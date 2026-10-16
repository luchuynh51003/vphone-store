<?php
require_once __DIR__ . '/../models/ReviewModel.php';
require_once __DIR__ . '/../models/ProductModel.php';

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
        $rating = (int)($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');

        $currentUser = $_SESSION['user'] ?? null;
        $fullname = trim($_POST['fullname'] ?? ($currentUser['fullname'] ?? ''));
        $email = trim($_POST['email'] ?? ($currentUser['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? ($currentUser['phone'] ?? ''));
        $userId = $currentUser ? (int)$currentUser['id'] : null;

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

        // Tự động kiểm tra khách đã mua hàng hay chưa
        $isBuyer = $this->reviewModel->hasUserPurchased($productId, $userId, $phone);

        $newReviewId = $this->reviewModel->addReview([
            'product_id' => $productId,
            'user_id' => $userId,
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'rating' => $rating,
            'comment' => $comment,
            'is_buyer' => $isBuyer ? 1 : 0
        ]);

        if ($newReviewId > 0) {
            $summary = $this->reviewModel->getRatingSummary($productId);
            $msg = 'Cảm ơn bạn đã gửi đánh giá! Nhận xét ' . $rating . ' sao của bạn đã được ghi nhận.';
            if ($isBuyer) {
                $msg .= ' (Đã gắn huy hiệu Khách hàng đã mua hàng tại V-Phone).';
            }
            $this->respond($isAjax, true, $msg, $productId, [
                'review_id' => $newReviewId,
                'is_buyer' => $isBuyer,
                'summary' => $summary
            ]);
        } else {
            $this->respond($isAjax, false, 'Có lỗi xảy ra khi lưu đánh giá. Vui lòng thử lại sau ít phút.', $productId);
        }
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
