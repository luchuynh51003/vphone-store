<?php
$pageTitle = 'Quản Lý Đánh Giá & Số Sao - V-Phone Admin';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../app/models/ReviewModel.php';
require_once __DIR__ . '/../app/models/ProductModel.php';

$reviewModel = new ReviewModel($pdo);
$prodModel = new ProductModel($pdo);
$allProducts = $prodModel->getAll();

$successMsg = '';
$errorMsg = '';

// Xử lý các thao tác hành động
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    $reviewId = (int)($_POST['review_id'] ?? 0);

    if ($action === 'reply' && $reviewId > 0) {
        $adminReply = trim($_POST['admin_reply'] ?? '');
        if (!empty($adminReply)) {
            $reviewModel->replyReview($reviewId, $adminReply);
            $successMsg = "Đã lưu phản hồi CSKH cho đánh giá #{$reviewId} thành công!";
        } else {
            $errorMsg = "Vui lòng nhập nội dung phản hồi cho khách hàng.";
        }
    } elseif ($action === 'delete' && $reviewId > 0) {
        $reviewModel->deleteReview($reviewId);
        $successMsg = "Đã xóa đánh giá #{$reviewId} thành công!";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $id = (int)($_GET['id'] ?? 0);
    $status = (int)($_GET['status'] ?? 0);
    if ($id > 0) {
        $reviewModel->updateStatus($id, $status);
        $successMsg = $status === 1 ? "Đã duyệt hiển thị đánh giá #{$id}." : "Đã chuyển đánh giá #{$id} về trạng thái Ẩn.";
    }
}

// Bộ lọc & Tìm kiếm
$keyword = trim($_GET['keyword'] ?? '');
$filterRating = (isset($_GET['rating']) && $_GET['rating'] !== '' && $_GET['rating'] !== 'all') ? (int)$_GET['rating'] : null;
$filterStatus = (isset($_GET['status']) && $_GET['status'] !== '' && $_GET['status'] !== 'all') ? (int)$_GET['status'] : null;
$filterProductId = (isset($_GET['product_id']) && (int)$_GET['product_id'] > 0) ? (int)$_GET['product_id'] : null;

$reviews = $reviewModel->getAllReviews($keyword, $filterRating, $filterStatus, $filterProductId);
$stats = $reviewModel->getAdminStats();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="fa-solid fa-star-half-stroke text-warning me-2"></i>Quản Lý Đánh Giá & Số Sao
        </h4>
        <p class="text-secondary small mb-0">Theo dõi, kiểm duyệt và phản hồi đánh giá của khách hàng trên toàn hệ thống V-Phone</p>
    </div>
    <div class="d-flex gap-2">
        <a href="../index.php" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Xem Website
        </a>
    </div>
</div>

<?php if (!empty($successMsg)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($successMsg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($errorMsg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- 5 THẺ THỐNG KÊ KPI TỔNG QUAN -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="admin-kpi-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary small fw-bold text-uppercase">Tổng đánh giá</span>
                <span class="rounded-circle p-2 bg-primary bg-opacity-10 text-primary" style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-comments"></i>
                </span>
            </div>
            <h3 class="fw-bold text-dark mb-0"><?= number_format($stats['total_reviews']) ?></h3>
            <small class="text-muted" style="font-size:0.75rem;">Trên toàn cửa hàng</small>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="admin-kpi-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary small fw-bold text-uppercase">Điểm trung bình</span>
                <span class="rounded-circle p-2 bg-warning bg-opacity-10 text-warning" style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-star"></i>
                </span>
            </div>
            <h3 class="fw-bold text-warning mb-0"><?= number_format($stats['avg_score'], 1) ?> <span class="fs-6 text-muted">/ 5.0</span></h3>
            <small class="text-muted" style="font-size:0.75rem;">Mức độ hài lòng</small>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="admin-kpi-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary small fw-bold text-uppercase">Đánh giá 5 sao</span>
                <span class="rounded-circle p-2 bg-success bg-opacity-10 text-success" style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-face-smile"></i>
                </span>
            </div>
            <h3 class="fw-bold text-success mb-0"><?= number_format($stats['five_star_count']) ?></h3>
            <small class="text-muted" style="font-size:0.75rem;">Khách cực kỳ thích</small>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-6">
        <div class="admin-kpi-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary small fw-bold text-uppercase">Đã mua tại V-Phone</span>
                <span class="rounded-circle p-2 bg-info bg-opacity-10 text-info" style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-shield-check"></i>
                </span>
            </div>
            <h3 class="fw-bold text-info mb-0"><?= number_format($stats['buyer_reviews_count']) ?></h3>
            <small class="text-muted" style="font-size:0.75rem;">Người mua xác thực</small>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-12">
        <div class="admin-kpi-card h-100 border-start <?= $stats['hidden_count'] > 0 ? 'border-danger border-3' : '' ?>">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary small fw-bold text-uppercase">Đang ẩn / Cần duyệt</span>
                <span class="rounded-circle p-2 bg-danger bg-opacity-10 text-danger" style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-eye-slash"></i>
                </span>
            </div>
            <h3 class="fw-bold <?= $stats['hidden_count'] > 0 ? 'text-danger' : 'text-dark' ?> mb-0"><?= number_format($stats['hidden_count']) ?></h3>
            <small class="text-muted" style="font-size:0.75rem;">Chưa hiển thị ngoài web</small>
        </div>
    </div>
</div>

<!-- BỘ LỌC TÌM KIẾM CHI TIẾT -->
<div class="card rounded-4 border-0 shadow-sm mb-4">
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="reviews.php" class="row g-3">
            <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-bold text-secondary mb-1">Tìm kiếm từ khóa:</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="keyword" class="form-control bg-light border-start-0" placeholder="Tên khách, SĐT, nội dung..." value="<?= htmlspecialchars($keyword) ?>">
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-bold text-secondary mb-1">Lọc theo sản phẩm:</label>
                <select name="product_id" class="form-select bg-light">
                    <option value="">-- Tất cả sản phẩm --</option>
                    <?php foreach ($allProducts as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $filterProductId === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-2 col-md-4 col-6">
                <label class="form-label small fw-bold text-secondary mb-1">Số sao:</label>
                <select name="rating" class="form-select bg-light">
                    <option value="all">Tất cả sao</option>
                    <option value="5" <?= $filterRating === 5 ? 'selected' : '' ?>>5 sao (Rất hài lòng)</option>
                    <option value="4" <?= $filterRating === 4 ? 'selected' : '' ?>>4 sao (Hài lòng)</option>
                    <option value="3" <?= $filterRating === 3 ? 'selected' : '' ?>>3 sao (Bình thường)</option>
                    <option value="2" <?= $filterRating === 2 ? 'selected' : '' ?>>2 sao (Chưa ưng ý)</option>
                    <option value="1" <?= $filterRating === 1 ? 'selected' : '' ?>>1 sao (Thất vọng)</option>
                </select>
            </div>

            <div class="col-lg-2 col-md-4 col-6">
                <label class="form-label small fw-bold text-secondary mb-1">Trạng thái:</label>
                <select name="status" class="form-select bg-light">
                    <option value="all">Tất cả</option>
                    <option value="1" <?= $filterStatus === 1 ? 'selected' : '' ?>>Đang hiển thị</option>
                    <option value="0" <?= $filterStatus === 0 ? 'selected' : '' ?>>Đang ẩn</option>
                </select>
            </div>

            <div class="col-lg-1 col-md-4 col-12 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold">
                    <i class="fa-solid fa-filter me-1"></i>Lọc
                </button>
            </div>
        </form>
    </div>
</div>

<!-- BẢNG DANH SÁCH ĐÁNH GIÁ -->
<div class="admin-table-card shadow-sm mb-5">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
        <div class="fw-bold text-dark">
            <i class="fa-solid fa-list me-2 text-primary"></i>Danh Sách Đánh Giá (<?= count($reviews) ?> kết quả)
        </div>
        <div class="small text-secondary">
            Hiển thị mới nhất trước
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 260px;">Sản phẩm</th>
                    <th style="width: 220px;">Khách hàng</th>
                    <th>Đánh giá & Bình luận</th>
                    <th style="width: 140px;">Trạng thái</th>
                    <th style="width: 130px;" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-box-open fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                            Không tìm thấy đánh giá nào phù hợp với bộ lọc hiện tại.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <tr>
                            <td class="fw-bold text-secondary">#<?= $rev['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="../<?= htmlspecialchars($rev['product_image']) ?>" alt="" style="width: 44px; height: 44px; object-fit: contain; border-radius: 8px; background: #fff; border: 1px solid #e2e8f0; padding: 2px;">
                                    <div class="min-w-0">
                                        <a href="../index.php?page=detail&id=<?= $rev['product_id'] ?>#reviews-section" target="_blank" class="fw-bold text-dark text-decoration-none small text-truncate d-block" style="max-width: 190px;" title="<?= htmlspecialchars($rev['product_name']) ?>">
                                            <?= htmlspecialchars($rev['product_name']) ?>
                                        </a>
                                        <div class="text-danger small fw-semibold"><?= number_format($rev['product_price'], 0, ',', '.') ?> đ</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small mb-1"><?= htmlspecialchars($rev['fullname']) ?></div>
                                <?php if (!empty($rev['phone'])): ?>
                                    <div class="text-secondary small" style="font-size: 0.76rem;"><i class="fa-solid fa-phone me-1 text-muted"></i><?= htmlspecialchars($rev['phone']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($rev['email'])): ?>
                                    <div class="text-secondary small text-truncate" style="font-size: 0.76rem; max-width: 200px;"><i class="fa-solid fa-envelope me-1 text-muted"></i><?= htmlspecialchars($rev['email']) ?></div>
                                <?php endif; ?>

                                <?php if (!empty($rev['is_buyer'])): ?>
                                    <span class="badge badge-verified-buyer rounded-pill mt-1">
                                        <i class="fa-solid fa-circle-check me-1"></i>Đã mua tại V-Phone
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border rounded-pill mt-1" style="font-size:0.68rem;">Khách vãng lai</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="text-warning">
                                        <?php for ($i = 0; $i < (int)$rev['rating']; $i++): ?>
                                            <i class="fa-solid fa-star small"></i>
                                        <?php endfor; ?>
                                        <?php for ($i = (int)$rev['rating']; $i < 5; $i++): ?>
                                            <i class="fa-regular fa-star small text-muted opacity-50"></i>
                                        <?php endfor; ?>
                                    </span>
                                    <span class="small fw-bold text-dark"><?= $rev['rating'] ?>/5 sao</span>
                                    <span class="text-muted" style="font-size: 0.75rem;">&bull; <?= htmlspecialchars(formatTimeAgo($rev['created_at'])) ?> (<?= date('d/m/Y H:i', strtotime($rev['created_at'])) ?>)</span>
                                </div>
                                <div class="text-dark small mb-2" style="white-space: pre-line; line-height: 1.5;"><?= htmlspecialchars($rev['comment']) ?></div>

                                <?php if (!empty($rev['admin_reply'])): ?>
                                    <div class="review-admin-reply-box small py-2 px-3 mt-1">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="fw-bold text-primary" style="font-size: 0.78rem;">
                                                <i class="fa-solid fa-headset me-1"></i>Phản hồi từ V-Phone CSKH:
                                            </span>
                                            <span class="text-muted" style="font-size: 0.7rem;"><?= !empty($rev['admin_reply_at']) ? htmlspecialchars(formatTimeAgo($rev['admin_reply_at'])) : '' ?></span>
                                        </div>
                                        <div class="text-secondary"><?= nl2br(htmlspecialchars($rev['admin_reply'])) ?></div>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($rev['status'] == 1): ?>
                                    <span class="badge badge-soft-success rounded-pill px-3 py-1">
                                        <i class="fa-solid fa-circle-check me-1"></i>Đang hiển thị
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-soft-secondary rounded-pill px-3 py-1">
                                        <i class="fa-solid fa-eye-slash me-1"></i>Đang ẩn
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <!-- Nút Ẩn/Hiện -->
                                    <?php if ($rev['status'] == 1): ?>
                                        <a href="reviews.php?action=toggle_status&id=<?= $rev['id'] ?>&status=0" class="btn btn-sm btn-outline-secondary rounded-circle" title="Ẩn đánh giá này" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="fa-solid fa-eye-slash"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="reviews.php?action=toggle_status&id=<?= $rev['id'] ?>&status=1" class="btn btn-sm btn-outline-success rounded-circle" title="Duyệt & hiển thị đánh giá này" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="fa-solid fa-check"></i>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Nút Phản Hồi CSKH -->
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" title="Phản hồi đánh giá của khách" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#adminReplyModal"
                                            data-review-id="<?= $rev['id'] ?>"
                                            data-customer-name="<?= htmlspecialchars($rev['fullname']) ?>"
                                            data-product-name="<?= htmlspecialchars($rev['product_name']) ?>"
                                            data-rating="<?= $rev['rating'] ?>"
                                            data-comment="<?= htmlspecialchars($rev['comment']) ?>"
                                            data-reply="<?= htmlspecialchars($rev['admin_reply'] ?? '') ?>">
                                        <i class="fa-solid fa-reply"></i>
                                    </button>

                                    <!-- Nút Xóa -->
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" title="Xóa đánh giá này" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#adminDeleteReviewModal"
                                            data-review-id="<?= $rev['id'] ?>"
                                            data-customer-name="<?= htmlspecialchars($rev['fullname']) ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PHẢN HỒI CSKH -->
<div class="modal fade" id="adminReplyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom bg-light px-4 py-3">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-headset text-primary"></i> Phản Hồi CSKH V-Phone
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="reviews.php">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="review_id" id="replyReviewId" value="">
                
                <div class="modal-body p-4">
                    <!-- Trích dẫn đánh giá của khách -->
                    <div class="p-3 rounded-4 bg-light border mb-3 small">
                        <div class="fw-bold text-dark" id="replyCustomerAndProd">Khách hàng &bull; Sản phẩm</div>
                        <div class="text-warning my-1" id="replyStarBox"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                        <div class="text-secondary fst-italic" id="replyCommentQuote">"Nội dung nhận xét của khách hàng..."</div>
                    </div>

                    <!-- Mẫu câu trả lời nhanh -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">
                            <i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i>Chọn mẫu câu trả lời nhanh:
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="applyTemplate(1)">
                                Cảm ơn 5 sao VIP
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="applyTemplate(2)">
                                Hỗ trợ bảo hành & 1 đổi 1
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="applyTemplate(3)">
                                Tặng củ sạc & quà VIP
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">
                            Nội dung phản hồi chính thức từ V-Phone Support: <span class="text-danger">*</span>
                        </label>
                        <textarea name="admin_reply" id="replyTextarea" class="form-control rounded-3" rows="4" required placeholder="Nhập lời cảm ơn hoặc giải đáp thắc mắc của khách hàng..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top bg-light px-4 py-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-paper-plane me-1"></i>Lưu & Đăng Phản Hồi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL XÁC NHẬN XÓA ĐÁNH GIÁ -->
<div class="modal fade" id="adminDeleteReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4">
            <div class="modal-body p-0">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:60px; height:60px;">
                    <i class="fa-solid fa-trash fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Xác nhận xóa đánh giá?</h5>
                <p class="text-secondary small mb-4" id="deleteConfirmText">Hành động này sẽ xóa vĩnh viễn đánh giá của khách hàng khỏi hệ thống và cập nhật lại điểm trung bình.</p>
                <form method="POST" action="reviews.php">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="review_id" id="deleteReviewId" value="">
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">Xác nhận xóa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let currentModalCustomerName = '';

document.addEventListener('DOMContentLoaded', function() {
    // Xử lý modal Phản Hồi CSKH
    const replyModal = document.getElementById('adminReplyModal');
    if (replyModal) {
        replyModal.addEventListener('show.bs.modal', function(event) {
            const btn = event.relatedTarget;
            const rId = btn.getAttribute('data-review-id');
            const cName = btn.getAttribute('data-customer-name');
            const pName = btn.getAttribute('data-product-name');
            const rating = parseInt(btn.getAttribute('data-rating') || '5', 10);
            const comment = btn.getAttribute('data-comment');
            const reply = btn.getAttribute('data-reply') || '';

            currentModalCustomerName = cName;
            document.getElementById('replyReviewId').value = rId;
            document.getElementById('replyCustomerAndProd').innerText = `${cName} • ${pName}`;

            let starHtml = '';
            for (let i = 0; i < rating; i++) starHtml += '<i class="fa-solid fa-star me-1"></i>';
            for (let i = rating; i < 5; i++) starHtml += '<i class="fa-regular fa-star me-1 opacity-50"></i>';
            document.getElementById('replyStarBox').innerHTML = starHtml + `<span class="ms-1 fw-bold text-dark">(${rating}/5 sao)</span>`;
            document.getElementById('replyCommentQuote').innerText = `"${comment}"`;
            document.getElementById('replyTextarea').value = reply;
        });
    }

    // Xử lý modal Xóa đánh giá
    const deleteModal = document.getElementById('adminDeleteReviewModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const btn = event.relatedTarget;
            const rId = btn.getAttribute('data-review-id');
            const cName = btn.getAttribute('data-customer-name');
            document.getElementById('deleteReviewId').value = rId;
            document.getElementById('deleteConfirmText').innerText = `Bạn có chắc chắn muốn xóa đánh giá #${rId} của khách hàng "${cName}"?`;
        });
    }
});

function applyTemplate(type) {
    const cName = currentModalCustomerName || 'quý khách';
    const ta = document.getElementById('replyTextarea');
    if (!ta) return;

    if (type === 1) {
        ta.value = `Dạ V-Phone xin chân thành cảm ơn anh/chị ${cName} đã tin tưởng lựa chọn và dành tặng đánh giá 5 sao cho V-Phone Store! Chúc quý khách có những trải nghiệm công nghệ thật tuyệt vời cùng máy ạ.`;
    } else if (type === 2) {
        ta.value = `Dạ V-Phone rất lấy làm tiếc vì sự trải nghiệm chưa trọn vẹn của anh/chị ${cName}. Sản phẩm được bảo hành VIP 1 đổi 1 trong 30 ngày đầu, chuyên viên kỹ thuật V-Phone sẽ liên hệ trực tiếp để kiểm tra và đổi máy mới ngay cho quý khách ạ!`;
    } else if (type === 3) {
        ta.value = `Cảm ơn anh/chị ${cName} đã phản hồi. V-Phone cam kết tặng kèm đầy đủ bộ phụ kiện sạc nhanh chính hãng và dán cường lực VIP cho quý khách. Kính chúc quý khách ngày mới nhiều niềm vui!`;
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
