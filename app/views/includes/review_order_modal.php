<?php
/**
 * Universal Order Review Modal & Floating Delivery Prompt
 * V-Phone Flagship Store
 */
$dbReview = $pdo ?? ($GLOBALS['pdo'] ?? null);
if (!$dbReview) {
    require_once __DIR__ . '/../../../config/database.php';
    $dbReview = $pdo ?? ($GLOBALS['pdo'] ?? null);
}
require_once __DIR__ . '/../../models/ReviewModel.php';

$reviewModelGlobal = null;
$firstUnreviewedItem = null;
$currentUserGlobal = $_SESSION['user'] ?? null;

if ($dbReview instanceof PDO) {
    try {
        $reviewModelGlobal = new ReviewModel($dbReview);
        $uIdGlobal = $currentUserGlobal ? (int)$currentUserGlobal['id'] : null;
        $uPhoneGlobal = $currentUserGlobal['phone'] ?? null;

        // Lấy sản phẩm mới nhất đã giao thành công mà chưa đánh giá
        $unreviewedDeliveredGlobal = [];
        if ($uIdGlobal || $uPhoneGlobal) {
            $unreviewedDeliveredGlobal = $reviewModelGlobal->getDeliveredUnreviewedItems($uIdGlobal, $uPhoneGlobal, 1);
        }
        $firstUnreviewedItem = !empty($unreviewedDeliveredGlobal) ? $unreviewedDeliveredGlobal[0] : null;
    } catch (Throwable $e) {
        error_log("Error in review_order_modal: " . $e->getMessage());
    }
}
?>

<style>
/* ==========================================================================
   DELIVERY REVIEW NOTIFICATION TOAST & UNIVERSAL MODAL
   ========================================================================== */
.vphone-delivery-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 1055;
    max-width: 420px;
    width: calc(100vw - 32px);
    background: #ffffff;
    border: 1px solid rgba(16, 185, 129, 0.35);
    border-radius: 20px;
    box-shadow: 0 16px 40px -10px rgba(16, 185, 129, 0.25), 0 4px 16px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    animation: vphoneToastSlideUp 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    transition: all 0.3s ease;
}

[data-bs-theme="dark"] .vphone-delivery-toast {
    background: #131f37 !important;
    border-color: rgba(16, 185, 129, 0.45) !important;
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.6) !important;
}

@keyframes vphoneToastSlideUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.96);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.vphone-delivery-toast-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    padding: 12px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.vphone-delivery-toast-body {
    padding: 16px 18px 18px;
}

.vphone-toast-thumb {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 3px;
    object-fit: contain;
    flex-shrink: 0;
}

[data-bs-theme="dark"] .vphone-toast-thumb {
    background: #0e172a !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

/* Modal Styling & Dark Theme Adaptability */
.order-review-modal-header {
    background: #ffffff;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
}

[data-bs-theme="dark"] .order-review-modal-header {
    background: #131f37 !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

.review-header-icon-box {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
}

.review-product-preview-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
}

[data-bs-theme="dark"] .review-product-preview-card {
    background: #0e172a !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
}

/* Interactive Star Picker */
.review-star-picker-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
}

[data-bs-theme="dark"] .review-star-picker-card {
    background: #0e172a !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
}

.star-pick-btn {
    background: transparent !important;
    border: none !important;
    padding: 6px 8px !important;
    font-size: 2.2rem !important;
    line-height: 1 !important;
    color: #cbd5e1 !important;
    cursor: pointer !important;
    transition: transform 0.15s ease, color 0.15s ease, filter 0.15s ease !important;
    border-radius: 8px !important;
    outline: none !important;
}

[data-bs-theme="dark"] .star-pick-btn {
    color: #334155 !important;
}

.star-pick-btn.active,
.star-pick-btn.hover-active {
    color: #f59e0b !important;
    filter: drop-shadow(0 2px 8px rgba(245, 158, 11, 0.45)) !important;
    transform: scale(1.12) !important;
}

.star-pick-btn:hover {
    transform: scale(1.22) !important;
}

.star-feedback-badge {
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.28);
    font-size: 0.85rem;
    color: #d97706;
}

[data-bs-theme="dark"] .star-feedback-badge {
    background: rgba(245, 158, 11, 0.16) !important;
    border-color: rgba(245, 158, 11, 0.35) !important;
    color: #fbbf24 !important;
}

/* Quick Suggestion Chips */
.btn-review-tag {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #334155;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 0.78rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    transition: all 0.18s ease;
    user-select: none;
}

.btn-review-tag:hover {
    background: #e0f2fe;
    border-color: #38bdf8;
    color: #0284c7;
    transform: translateY(-1px);
}

[data-bs-theme="dark"] .btn-review-tag {
    background: #1a2948 !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    color: #e2e8f0 !important;
}

[data-bs-theme="dark"] .btn-review-tag:hover {
    background: #24375f !important;
    border-color: #38bdf8 !important;
    color: #38bdf8 !important;
}
</style>

<!-- ========================================================================
     1. FLOATING DELIVERY CELEBRATION REVIEW TOAST
     ======================================================================== -->
<?php if ($firstUnreviewedItem): ?>
    <div id="vphoneDeliveredReviewToast" class="vphone-delivery-toast" style="display: none;" 
         data-order-id="<?= (int)$firstUnreviewedItem['order_id'] ?>" 
         data-product-id="<?= (int)$firstUnreviewedItem['product_id'] ?>" 
         data-product-name="<?= htmlspecialchars($firstUnreviewedItem['product_name']) ?>" 
         data-product-img="<?= htmlspecialchars($firstUnreviewedItem['product_image'] ?? 'assets/images/products/iphone-17-pro.png') ?>">
        <div class="vphone-delivery-toast-header">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-gift text-warning fs-6"></i>
                <div class="fw-bold small text-white lh-1">Đơn hàng #VP-<?= (int)$firstUnreviewedItem['order_id'] ?> đã giao thành công!</div>
            </div>
            <button type="button" class="btn-close btn-close-white shadow-none ms-2" style="font-size: 0.72rem;" onclick="dismissDeliveryReviewToast(<?= (int)$firstUnreviewedItem['order_id'] ?>)" aria-label="Close"></button>
        </div>
        <div class="vphone-delivery-toast-body">
            <div class="d-flex align-items-center gap-3 mb-3">
                <img src="<?= htmlspecialchars($firstUnreviewedItem['product_image'] ?? 'assets/images/products/iphone-17-pro.png') ?>" alt="" class="vphone-toast-thumb">
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-bold text-dark text-truncate small mb-1" title="<?= htmlspecialchars($firstUnreviewedItem['product_name']) ?>">
                        <?= htmlspecialchars($firstUnreviewedItem['product_name']) ?>
                    </div>
                    <div class="text-secondary" style="font-size: 0.76rem; line-height: 1.45;">
                        Bạn hài lòng với máy chứ? Hãy chia sẻ đánh giá để nhận quà tri ân từ V-Phone nhé!
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between gap-2 pt-2 border-top">
                <button type="button" class="btn btn-sm btn-link text-muted text-decoration-none p-0 fw-semibold" style="font-size: 0.78rem;" onclick="dismissDeliveryReviewToast(<?= (int)$firstUnreviewedItem['order_id'] ?>)">
                    Để sau
                </button>
                <button type="button" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3.5 py-1.5 shadow-sm d-inline-flex align-items-center gap-1.5" style="font-size: 0.82rem;" onclick="triggerToastReview()">
                    <i class="fa-solid fa-star text-dark"></i>
                    <span>Đánh Giá Ngay</span>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ========================================================================
     2. UNIVERSAL QUICK REVIEW MODAL (#orderReviewModal)
     ======================================================================== -->
<div class="modal fade" id="orderReviewModal" tabindex="-1" aria-labelledby="orderReviewModalLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <!-- Modal Header (Tương thích Dark/Light Mode hoàn hảo) -->
            <div class="modal-header order-review-modal-header border-0 pb-3 pt-3.5 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="review-header-icon-box flex-shrink-0">
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-body mb-0 fs-6" id="orderReviewModalLabel">Đánh Giá Sản Phẩm</h5>
                        <div class="d-flex align-items-center gap-2 mt-1" style="font-size: 0.74rem;">
                            <span id="reviewModalOrderBadge" class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-0.5 fw-bold">#VP-</span>
                            <span class="text-success fw-bold d-inline-flex align-items-center"><i class="fa-solid fa-shield-halved me-1.5"></i>Đã mua hàng chính hãng</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Review Form -->
            <form id="orderReviewForm" autocomplete="off" onsubmit="handleUniversalReviewSubmit(event)">
                <input type="hidden" name="review_id" id="reviewModalReviewId" value="">
                <input type="hidden" name="order_id" id="reviewModalOrderId" value="">
                <input type="hidden" name="product_id" id="reviewModalProductId" value="">
                <input type="hidden" name="rating" id="reviewModalRatingValue" value="5">

                <div class="modal-body px-4 py-3">
                    <!-- Alert Thông Báo Lỗi / Thành Công -->
                    <div id="reviewModalAlert" class="alert d-none small py-2 rounded-3 mb-3"></div>

                    <!-- Thông Tin Sản Phẩm Đang Đánh Giá -->
                    <div class="review-product-preview-card p-2.5 d-flex align-items-center gap-3 mb-3" id="reviewModalProductPreview">
                        <img src="assets/images/products/iphone-17-pro.png" id="reviewModalProductImg" alt="Product" class="rounded-3 border bg-white" style="width: 52px; height: 52px; object-fit: contain; padding: 2px;">
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-bold text-dark small text-truncate" id="reviewModalProductName">Tên sản phẩm</div>
                            <div class="text-muted d-flex align-items-center gap-1.5" style="font-size: 0.74rem;" id="reviewModalProductSub">
                                <i class="fa-solid fa-circle-check text-success"></i>
                                <span>Bảo hành chính hãng 12 tháng tại V-Phone</span>
                            </div>
                        </div>
                    </div>

                    <!-- KHỐI CHỌN SỐ SAO TƯƠNG TÁC (Đổi màu chuẩn xác 100%) -->
                    <div class="review-star-picker-card text-center my-3 p-3">
                        <label class="form-label small fw-bold text-uppercase text-secondary mb-2 d-block" style="letter-spacing: 0.5px;">
                            Bạn đánh giá sản phẩm này bao nhiêu sao?
                        </label>
                        
                        <div class="vphone-star-interactive d-inline-flex align-items-center justify-content-center gap-2 mb-2" id="vphoneStarInteractive">
                            <button type="button" class="star-pick-btn active" data-star="1" title="1 sao - Rất thất vọng">
                                <i class="fa-solid fa-star"></i>
                            </button>
                            <button type="button" class="star-pick-btn active" data-star="2" title="2 sao - Chưa hài lòng">
                                <i class="fa-solid fa-star"></i>
                            </button>
                            <button type="button" class="star-pick-btn active" data-star="3" title="3 sao - Bình thường">
                                <i class="fa-solid fa-star"></i>
                            </button>
                            <button type="button" class="star-pick-btn active" data-star="4" title="4 sao - Hài lòng">
                                <i class="fa-solid fa-star"></i>
                            </button>
                            <button type="button" class="star-pick-btn active" data-star="5" title="5 sao - Tuyệt vời">
                                <i class="fa-solid fa-star"></i>
                            </button>
                        </div>

                        <!-- Huy hiệu phản hồi số sao động -->
                        <div>
                            <div id="starFeedbackBox" class="star-feedback-badge d-inline-flex align-items-center justify-content-center gap-2 px-3 py-1 rounded-pill">
                                <span class="star-icon-row d-inline-flex align-items-center gap-1 text-warning">
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                </span>
                                <span class="star-text-label fw-bold">Tuyệt vời - Rất hài lòng (5/5)</span>
                            </div>
                        </div>
                    </div>

                    <!-- GỢI Ý ĐÁNH GIÁ NHANH (Icon FontAwesome chuyên nghiệp, cách đều rõ ràng) -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-2 d-flex align-items-center gap-1.5" style="font-size: 0.76rem;">
                            <i class="fa-solid fa-lightbulb text-warning me-1"></i>
                            <span>Bấm để thêm nhanh cảm nhận:</span>
                        </label>
                        <div class="d-flex flex-wrap gap-2" id="reviewQuickTags">
                            <button type="button" class="btn-review-tag" onclick="appendReviewTag('Máy nguyên seal 100%, chất lượng tuyệt vời!')">
                                <i class="fa-solid fa-box-open text-primary me-1.5"></i>
                                <span>Máy nguyên seal</span>
                            </button>
                            <button type="button" class="btn-review-tag" onclick="appendReviewTag('Giao hàng hỏa tốc siêu nhanh, đúng giờ!')">
                                <i class="fa-solid fa-truck-fast text-success me-1.5"></i>
                                <span>Giao hàng siêu nhanh</span>
                            </button>
                            <button type="button" class="btn-review-tag" onclick="appendReviewTag('Pin rất trâu dùng cả ngày thoải mái!')">
                                <i class="fa-solid fa-battery-full text-warning me-1.5"></i>
                                <span>Pin rất trâu</span>
                            </button>
                            <button type="button" class="btn-review-tag" onclick="appendReviewTag('Camera chụp đêm cực kỳ sắc nét, màu sắc chân thực!')">
                                <i class="fa-solid fa-camera text-info me-1.5"></i>
                                <span>Camera chụp nét</span>
                            </button>
                            <button type="button" class="btn-review-tag" onclick="appendReviewTag('Nhân viên tư vấn nhiệt tình, đóng gói rất cẩn thận!')">
                                <i class="fa-solid fa-headset text-danger me-1.5"></i>
                                <span>Nhân viên nhiệt tình</span>
                            </button>
                            <button type="button" class="btn-review-tag" onclick="appendReviewTag('Màn hình mượt mà, cảm ứng rất nhạy!')">
                                <i class="fa-solid fa-mobile-screen-button text-primary me-1.5"></i>
                                <span>Màn hình mượt mà</span>
                            </button>
                        </div>
                    </div>

                    <!-- NỘI DUNG NHẬN XÉT -->
                    <div class="mb-3">
                        <label for="reviewModalComment" class="form-label small fw-bold text-dark mb-1">
                            Nhận xét chi tiết <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control rounded-3 small" id="reviewModalComment" name="comment" rows="3" placeholder="Chia sẻ cảm nhận của bạn về sản phẩm, thiết kế, camera, hiệu năng hoặc dịch vụ giao hàng..." required minlength="6"></textarea>
                        <div class="text-muted text-end mt-1" style="font-size: 0.72rem;">Tối thiểu 6 ký tự</div>
                    </div>

                    <!-- THÔNG TIN NGƯỜI ĐÁNH GIÁ -->
                    <div class="row g-2 mb-1">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.76rem;">Họ và tên</label>
                            <input type="text" class="form-control form-control-sm rounded-pill" id="reviewModalFullname" name="fullname" placeholder="Họ và tên..." value="<?= htmlspecialchars($currentUserGlobal['fullname'] ?? '') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.76rem;">Số điện thoại</label>
                            <input type="tel" class="form-control form-control-sm rounded-pill" id="reviewModalPhone" name="phone" placeholder="Số điện thoại..." value="<?= htmlspecialchars($currentUserGlobal['phone'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer border-0 pt-0 px-4 pb-3.5 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3.5 py-2 fw-semibold text-secondary" data-bs-dismiss="modal">
                        Để sau
                    </button>
                    <button type="submit" id="btnSubmitOrderReview" class="btn btn-sm btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="fa-solid fa-paper-plane" id="reviewSubmitIcon"></i>
                        <span id="reviewSubmitText">Gửi Đánh Giá Ngay</span>
                    </button>
                </div>
            </form>

            <!-- Success State Screen (Hiển thị khi gửi thành công) -->
            <div id="reviewSuccessScreen" class="d-none p-4 text-center py-5">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success mb-3" style="width: 72px; height: 72px;">
                    <i class="fa-solid fa-circle-check fs-1 text-success"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Cảm Ơn Đánh Giá Của Bạn!</h5>
                <p class="text-secondary small mb-4" id="reviewSuccessMessage">
                    Nhận xét của bạn đã được ghi nhận thành công và hiển thị cùng huy hiệu <strong>Khách hàng đã mua hàng</strong>.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold small shadow-sm" data-bs-dismiss="modal">
                        Hoàn Tất
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================
     JAVASCRIPT LOGIC CHO TOAST & UNIVERSAL REVIEW MODAL
     ======================================================================== -->
<script>
let currentSelectedStar = 5;

const STAR_DESC_DICTIONARY = {
    5: 'Tuyệt vời - Rất hài lòng',
    4: 'Hài lòng - Chất lượng tốt',
    3: 'Bình thường - Tạm ổn',
    2: 'Chưa hài lòng - Cần cải thiện',
    1: 'Rất thất vọng'
};

/**
 * Cập nhật giao diện số sao và huy hiệu phản hồi
 */
function renderStarsUI(starCount) {
    const buttons = document.querySelectorAll('#vphoneStarInteractive .star-pick-btn');
    buttons.forEach(btn => {
        const starVal = parseInt(btn.getAttribute('data-star'), 10);
        if (starVal <= starCount) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    const hiddenRating = document.getElementById('reviewModalRatingValue');
    if (hiddenRating) hiddenRating.value = starCount;

    const feedbackBox = document.getElementById('starFeedbackBox');
    if (feedbackBox) {
        let starsHtml = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= starCount) {
                starsHtml += '<i class="fa-solid fa-star text-warning"></i>';
            } else {
                starsHtml += '<i class="fa-regular fa-star text-secondary opacity-50"></i>';
            }
        }

        feedbackBox.innerHTML = `
            <span class="star-icon-row d-inline-flex align-items-center gap-1 me-1 text-warning">
                ${starsHtml}
            </span>
            <span class="star-text-label fw-bold">
                ${STAR_DESC_DICTIONARY[starCount] || STAR_DESC_DICTIONARY[5]} (${starCount}/5)
            </span>
        `;
    }
}

/**
 * Khởi tạo sự kiện rê chuột và chọn số sao
 */
function initInteractiveStarPicker() {
    const buttons = document.querySelectorAll('#vphoneStarInteractive .star-pick-btn');
    buttons.forEach(btn => {
        const starVal = parseInt(btn.getAttribute('data-star'), 10);

        btn.addEventListener('mouseenter', function () {
            buttons.forEach(b => {
                const val = parseInt(b.getAttribute('data-star'), 10);
                if (val <= starVal) {
                    b.classList.add('hover-active');
                } else {
                    b.classList.remove('hover-active');
                }
            });
        });

        btn.addEventListener('mouseleave', function () {
            buttons.forEach(b => b.classList.remove('hover-active'));
        });

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            currentSelectedStar = starVal;
            renderStarsUI(currentSelectedStar);
        });
    });
}

function appendReviewTag(text) {
    const textarea = document.getElementById('reviewModalComment');
    if (!textarea) return;

    if (textarea.value.trim() === '') {
        textarea.value = text;
    } else {
        if (!textarea.value.includes(text)) {
            textarea.value = textarea.value.trim() + ' ' + text;
        }
    }
    textarea.focus();
}

/**
 * Mở modal đánh giá sản phẩm cho đơn hàng cụ thể
 */
window.openProductReviewModal = function(orderId, productId, productName, productImg) {
    const modalEl = document.getElementById('orderReviewModal');
    if (!modalEl) return;

    // Reset Form & Success Screen
    const form = document.getElementById('orderReviewForm');
    const successScreen = document.getElementById('reviewSuccessScreen');
    const alertBox = document.getElementById('reviewModalAlert');
    if (form) form.classList.remove('d-none');
    if (successScreen) successScreen.classList.add('d-none');
    if (alertBox) alertBox.className = 'alert d-none small py-2 rounded-3 mb-3';

    // Reset Review ID (chế độ đánh giá mới)
    document.getElementById('reviewModalReviewId').value = '';
    const labelTitle = document.getElementById('orderReviewModalLabel');
    if (labelTitle) labelTitle.textContent = 'Đánh Giá Sản Phẩm';
    const submitText = document.getElementById('reviewSubmitText');
    if (submitText) submitText.textContent = 'Gửi Đánh Giá Ngay';

    // Populate data
    document.getElementById('reviewModalOrderId').value = orderId || '';
    document.getElementById('reviewModalProductId').value = productId || '';
    document.getElementById('reviewModalOrderBadge').textContent = '#VP-' + orderId;
    document.getElementById('reviewModalProductName').textContent = productName || 'Sản phẩm V-Phone';
    
    if (productImg) {
        document.getElementById('reviewModalProductImg').src = productImg;
    }

    // Reset stars to 5
    currentSelectedStar = 5;
    renderStarsUI(5);
    document.getElementById('reviewModalComment').value = '';

    // Show modal
    if (window.bootstrap && bootstrap.Modal) {
        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();
    }
};

/**
 * Mở modal chỉnh sửa đánh giá đã có (Chỉ sửa 1 lần duy nhất)
 */
window.openEditReviewModal = function(reviewId, orderId, productId, productName, productImg, rating, comment) {
    const modalEl = document.getElementById('orderReviewModal');
    if (!modalEl) return;

    // Reset Form & Success Screen
    const form = document.getElementById('orderReviewForm');
    const successScreen = document.getElementById('reviewSuccessScreen');
    const alertBox = document.getElementById('reviewModalAlert');
    if (form) form.classList.remove('d-none');
    if (successScreen) successScreen.classList.add('d-none');
    if (alertBox) alertBox.className = 'alert d-none small py-2 rounded-3 mb-3';

    // Set hidden fields
    document.getElementById('reviewModalReviewId').value = reviewId || '';
    document.getElementById('reviewModalOrderId').value = orderId || '';
    document.getElementById('reviewModalProductId').value = productId || '';
    document.getElementById('reviewModalOrderBadge').textContent = '#VP-' + orderId;
    
    const labelTitle = document.getElementById('orderReviewModalLabel');
    if (labelTitle) {
        labelTitle.innerHTML = '<i class="fa-solid fa-pen-to-square me-2 text-warning"></i>Sửa Đánh Giá (1 Lần Duy Nhất)';
    }

    document.getElementById('reviewModalProductName').textContent = productName || 'Sản phẩm V-Phone';
    if (productImg) {
        document.getElementById('reviewModalProductImg').src = productImg;
    }

    // Set existing rating and comment
    currentSelectedStar = parseInt(rating, 10) || 5;
    renderStarsUI(currentSelectedStar);
    document.getElementById('reviewModalComment').value = comment || '';

    // Update submit button text
    const submitText = document.getElementById('reviewSubmitText');
    if (submitText) submitText.textContent = 'Cập Nhật Đánh Giá (1 Lần Duy Nhất)';

    // Show modal
    if (window.bootstrap && bootstrap.Modal) {
        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();
    }
};

/**
 * Kích hoạt mở modal từ toast thông báo nổi
 */
function triggerToastReview() {
    const toast = document.getElementById('vphoneDeliveredReviewToast');
    if (!toast) return;

    const orderId = toast.getAttribute('data-order-id');
    const productId = toast.getAttribute('data-product-id');
    const productName = toast.getAttribute('data-product-name');
    const productImg = toast.getAttribute('data-product-img');

    // Ẩn toast
    toast.style.display = 'none';

    window.openProductReviewModal(orderId, productId, productName, productImg);
}

function dismissDeliveryReviewToast(orderId) {
    const toast = document.getElementById('vphoneDeliveredReviewToast');
    if (toast) {
        toast.style.display = 'none';
    }
    try {
        sessionStorage.setItem('dismiss_delivery_toast_' + orderId, '1');
    } catch (e) {}
}

/**
 * Xử lý submit review bằng AJAX
 */
function handleUniversalReviewSubmit(event) {
    event.preventDefault();
    const form = document.getElementById('orderReviewForm');
    const alertBox = document.getElementById('reviewModalAlert');
    const btnSubmit = document.getElementById('btnSubmitOrderReview');
    const submitIcon = document.getElementById('reviewSubmitIcon');
    const submitText = document.getElementById('reviewSubmitText');

    const comment = document.getElementById('reviewModalComment').value.trim();
    if (comment.length < 6) {
        alertBox.className = 'alert alert-warning small py-2 rounded-3 mb-3';
        alertBox.textContent = 'Vui lòng nhập tối thiểu 6 ký tự để đánh giá có ý nghĩa hơn.';
        return;
    }

    const isEditMode = !!document.getElementById('reviewModalReviewId').value;

    btnSubmit.disabled = true;
    submitIcon.className = 'fa-solid fa-spinner fa-spin me-2';
    submitText.textContent = isEditMode ? 'Đang cập nhật...' : 'Đang gửi...';
    alertBox.className = 'alert d-none';

    const formData = new FormData(form);
    formData.append('ajax', '1');

    fetch('index.php?page=review&action=submit', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        btnSubmit.disabled = false;
        submitIcon.className = 'fa-solid fa-paper-plane me-2';
        submitText.textContent = isEditMode ? 'Cập Nhật Đánh Giá (1 Lần Duy Nhất)' : 'Gửi Đánh Giá Ngay';

        if (data.success) {
            // Hiển thị màn hình thành công
            form.classList.add('d-none');
            const successScreen = document.getElementById('reviewSuccessScreen');
            if (successScreen) {
                successScreen.classList.remove('d-none');
                document.getElementById('reviewSuccessMessage').textContent = data.message;
            }

            // Ẩn vphoneDeliveredReviewToast nếu đang hiển thị
            const toast = document.getElementById('vphoneDeliveredReviewToast');
            if (toast) toast.style.display = 'none';

            // Cập nhật các nút đánh giá trên trang hiện tại thành Đã đánh giá
            const productId = formData.get('product_id');
            const orderId = formData.get('order_id');
            const rating = formData.get('rating') || 5;

            // Tìm và cập nhật button trên order_tracking.php nếu có
            const btns = document.querySelectorAll(`[data-product-id="${productId}"]`);
            btns.forEach(btn => {
                if (btn.classList.contains('btn-review-product')) {
                    btn.outerHTML = `<div class="d-inline-flex align-items-center gap-1.5 px-3 py-1 rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25 small fw-bold"><i class="fa-solid fa-circle-check text-success me-1"></i><span>Đã đánh giá (${rating} <i class="fa-solid fa-star text-warning small ms-0.5"></i>)</span></div>`;
                }
            });

            // Nếu vừa cập nhật (sửa lần 1), xóa nút sửa trên trang
            if (isEditMode) {
                const editBtns = document.querySelectorAll(`button[onclick*="openEditReviewModal"][onclick*="${productId}"]`);
                editBtns.forEach(eb => eb.remove());
            }

            // Nếu đang ở trang detail của sản phẩm, reload danh sách đánh giá
            if (typeof window.loadProductReviews === 'function') {
                window.loadProductReviews(productId, 'all');
            }
        } else {
            alertBox.className = 'alert alert-danger small py-2 rounded-3 mb-3';
            alertBox.textContent = data.message || 'Có lỗi xảy ra khi lưu đánh giá.';
        }
    })
    .catch(err => {
        btnSubmit.disabled = false;
        submitIcon.className = 'fa-solid fa-paper-plane me-2';
        submitText.textContent = isEditMode ? 'Cập Nhật Đánh Giá (1 Lần Duy Nhất)' : 'Gửi Đánh Giá Ngay';
        alertBox.className = 'alert alert-danger small py-2 rounded-3 mb-3';
        alertBox.textContent = 'Không thể kết nối đến máy chủ. Vui lòng thử lại sau.';
    });
}

// Khởi chạy khi DOM sẵn sàng
document.addEventListener('DOMContentLoaded', function () {
    // Khởi tạo star picker
    initInteractiveStarPicker();
    renderStarsUI(5);

    const toast = document.getElementById('vphoneDeliveredReviewToast');
    if (toast) {
        const orderId = toast.getAttribute('data-order-id');
        let isDismissed = false;
        try {
            isDismissed = (sessionStorage.getItem('dismiss_delivery_toast_' + orderId) === '1');
        } catch (e) {}

        if (!isDismissed) {
            // Hiện toast nhẹ nhàng sau 1.2 giây
            setTimeout(() => {
                toast.style.display = 'block';
            }, 1200);
        }
    }

    // Kiểm tra URL parameters: nếu có &review=1 thì tự động mở modal CHỈ KHI chưa đánh giá
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('review') === '1') {
        const orderCode = urlParams.get('order_code') || urlParams.get('order_id') || urlParams.get('id');
        
        // Xóa ngay tham số &review=1 khỏi URL thanh địa chỉ để không bị lặp lại khi F5 refresh
        const cleanUrl = window.location.href.replace(/([&?]review=1)/, '');
        window.history.replaceState({}, document.title, cleanUrl);

        if (orderCode) {
            // Gọi AJAX lấy sản phẩm đầu tiên chưa đánh giá của đơn hàng này
            fetch('index.php?page=review&action=get_order_info&order_id=' + encodeURIComponent(orderCode))
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.items && data.items.length > 0) {
                        // Nếu đơn hàng đã hoàn tất toàn bộ đánh giá, TUYỆT ĐỐI KHÔNG tự động hiện modal nữa!
                        if (data.all_reviewed) {
                            return;
                        }

                        // Tìm sản phẩm chưa đánh giá
                        let targetItem = data.items.find(it => !it.is_reviewed);
                        if (!targetItem) {
                            return; // Không còn sản phẩm nào chưa đánh giá -> KHÔNG MỞ!
                        }

                        window.openProductReviewModal(
                            data.order.id,
                            targetItem.product_id,
                            targetItem.product_name,
                            targetItem.product_image
                        );
                    }
                })
                .catch(() => {});
        }
    }
});
</script>
