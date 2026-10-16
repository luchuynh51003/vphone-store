<?php
$product = $product ?? [];
$basePrice = $basePrice ?? 0;
$baseOldPrice = $baseOldPrice ?? 0;
$saving = $saving ?? 0;
$colorList = $colorList ?? [];
$colorStockMap = $colorStockMap ?? [];
$firstAvailableColor = null;
foreach ($colorList as $c) {
    if (!isset($colorStockMap[$c]) || $colorStockMap[$c] > 0) {
        $firstAvailableColor = $c;
        break;
    }
}
if (!$firstAvailableColor && !empty($colorList)) {
    $firstAvailableColor = $colorList[0];
}
require_once 'app/views/includes/functions.php';
$initialImage = getProductColorImage((int)$product['id'], $firstAvailableColor, $product['image']);
$storageOptions = json_decode($product['storage_options'] ?? '', true);
if (!is_array($storageOptions) || !$storageOptions) {
    $storageOptions = getStorageTiers($product['name'] ?? '', $product['rom'] ?? '256 GB');
}

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<div class="container my-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb py-2 px-3 bg-white rounded-4 shadow-sm mb-0">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-primary"><i class="fa-solid fa-house me-1"></i>Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="index.php?brand_id=<?= $product['brand_id'] ?>" class="text-decoration-none text-primary"><?= htmlspecialchars($product['brand_name'] ?? 'Điện thoại') ?></a></li>
            <li class="breadcrumb-item active text-truncate"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>
</div>

<div class="container my-4">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="bg-white p-4 rounded-4 shadow-sm text-center position-relative border">
                <?php if (!empty($product['is_used'])): ?>
                    <span class="badge badge-used-phone position-absolute top-0 start-0 m-3 px-3 py-2 rounded-pill shadow-sm" style="z-index: 5;" title="Hàng Cũ Like New 99%">
                        <i class="fa-solid fa-recycle me-1"></i><?= htmlspecialchars(!empty($product['condition_desc']) && $product['condition_desc'] !== 'Mới 100%' ? $product['condition_desc'] : 'Hàng Cũ Like New 99%') ?>
                    </span>
                <?php elseif (!empty($product['is_featured'])): ?>
                    <span class="badge badge-flagship position-absolute top-0 start-0 m-3 px-3 py-2 rounded-pill shadow-sm" style="z-index: 5;" title="Flagship 2026">
                        <i class="fa-solid fa-crown text-warning me-1"></i>Flagship 2026
                    </span>
                <?php endif; ?>

                <?php if ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']): ?>
                    <?php $percent = round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>
                    <span class="badge badge-sale-percent position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill shadow-sm" style="z-index: 5;">
                        -<?= $percent ?>%
                    </span>
                <?php endif; ?>

                <div class="detail-img-box my-3 py-3" style="min-height: 360px; display: flex; align-items: center; justify-content: center;">
                    <img id="detailMainImg" src="<?= htmlspecialchars($initialImage) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid" style="max-height: 340px; object-fit: contain; transition: all 0.25s ease;">
                </div>

                <div class="d-flex justify-content-center gap-2 mt-2">
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-shield-halved me-1"></i>Bảo hành 12T</span>
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-rotate-left me-1"></i>1 đổi 1 30 ngày</span>
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-truck-fast me-1"></i>Giao nhanh 2H</span>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="bg-white p-4 rounded-4 shadow-sm h-100 d-flex flex-column border">
                <?php if (!empty($product['is_used'])): ?>
                    <span class="badge badge-used-phone align-self-start px-3 py-1 mb-2 rounded-pill">
                        <i class="fa-solid fa-recycle me-1"></i><?= htmlspecialchars($product['brand_name'] ?? 'CHÍNH HÃNG') ?> CŨ 99%
                    </span>
                <?php elseif (!empty($product['is_featured'])): ?>
                    <span class="badge badge-flagship align-self-start px-3 py-1 mb-2 rounded-pill">
                        <i class="fa-solid fa-crown text-warning me-1"></i>FLAGSHIP 2026 - <?= htmlspecialchars($product['brand_name'] ?? 'CHÍNH HÃNG') ?>
                    </span>
                <?php else: ?>
                    <span class="badge badge-tech-new align-self-start px-3 py-1 mb-2 rounded-pill">
                        <?= htmlspecialchars($product['brand_name'] ?? 'CHÍNH HÃNG') ?>
                    </span>
                <?php endif; ?>
                <h3 class="fw-bold text-dark mb-2"><?= htmlspecialchars($product['name']) ?></h3>

                <div class="d-flex align-items-center gap-2 mb-3 small flex-wrap">
                    <?= renderStarRating((float)($ratingSummary['avg_rating'] ?? 5.0), 'small', true) ?>
                    <span class="text-secondary">&bull;</span>
                    <a href="#reviews-section" class="text-decoration-none text-primary fw-semibold" onclick="document.getElementById('reviews-section')?.scrollIntoView({behavior: 'smooth'}); return false;">
                        (<?= (int)($ratingSummary['total_reviews'] ?? 0) ?> đánh giá thực tế)
                    </a>
                    <?php if (!empty($userHasPurchased)): ?>
                        <span class="badge badge-verified-buyer rounded-pill ms-1" title="Bạn đã mua sản phẩm này trong lịch sử đơn hàng">
                            <i class="fa-solid fa-circle-check me-1"></i>Bạn đã mua máy này
                        </span>
                    <?php endif; ?>
                </div>

                <div class="p-3 rounded-4 bg-light my-2 border">
                    <div class="d-flex align-items-baseline gap-3 flex-wrap">
                        <h2 class="text-danger fw-bold mb-0" id="displayPrice"><?= number_format($basePrice, 0, ',', '.') ?> đ</h2>
                        <h5 class="text-decoration-line-through text-secondary mb-0" id="displayOldPrice"><?= number_format($baseOldPrice, 0, ',', '.') ?> đ</h5>
                        <span class="badge bg-danger rounded-pill px-2 py-1" id="displaySavingTag">Tiết kiệm <?= number_format($saving, 0, ',', '.') ?> đ</span>
                    </div>
                    <?php if ((int)$product['quantity'] > 0): ?>
                    <div class="small text-success mt-2 fw-semibold">
                        <i class="fa-solid fa-circle-check me-1"></i>Còn <?= $product['quantity'] ?> máy sẵn tại kho V-Phone
                    </div>
                    <?php else: ?>
                    <div class="small text-danger mt-2 fw-semibold">
                        <i class="fa-solid fa-circle-xmark me-1"></i>Sản phẩm hiện đã hết hàng
                    </div>
                    <?php endif; ?>
                </div>

                <div class="my-2">
                    <label class="fw-bold mb-2 small text-uppercase text-secondary">
                        <i class="fa-solid fa-palette me-1 text-primary"></i>Chọn màu sắc: <span class="text-primary" id="chosenColorText"><?= htmlspecialchars($firstAvailableColor ?? ($colorList[0] ?? 'Tiêu chuẩn')) ?></span>
                    </label>
                    <div class="d-flex gap-2 flex-wrap" id="colorButtonsGroup">
                        <?php foreach ($colorList as $idx => $cName): ?>
                            <?php
                                $isOutOfStockColor = isset($colorStockMap[$cName]) && $colorStockMap[$cName] <= 0;
                                $isActive = ($cName === $firstAvailableColor && !$isOutOfStockColor);
                            ?>
                            <?php if ($isOutOfStockColor): ?>
                                <button type="button" class="btn btn-sm rounded-pill fw-bold px-3 py-2 color-pill-btn btn-outline-secondary opacity-50" disabled title="Màu này tạm hết hàng" style="cursor: not-allowed; text-decoration: line-through;">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: <?= getColorHexValue($cName) ?>; display: inline-block; margin-right: 6px; border: 1px solid rgba(0,0,0,0.15); vertical-align: middle;"></span>
                                    <?= htmlspecialchars($cName) ?> <span class="badge bg-secondary ms-1" style="font-size:0.6rem;">Hết</span>
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm rounded-pill fw-bold px-3 py-2 color-pill-btn <?= $isActive ? 'btn-primary active' : 'btn-outline-secondary' ?>" data-color="<?= htmlspecialchars($cName) ?>" onclick="selectDetailColor(this, '<?= htmlspecialchars(addslashes($cName)) ?>')">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: <?= getColorHexValue($cName) ?>; display: inline-block; margin-right: 6px; border: 1px solid rgba(0,0,0,0.15); vertical-align: middle;"></span>
                                    <?= htmlspecialchars($cName) ?>
                                    <?php if (isset($colorStockMap[$cName])): ?>
                                        <span class="badge bg-light text-primary border ms-1" style="font-size:0.6rem;"><?= $colorStockMap[$cName] ?></span>
                                    <?php endif; ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="my-2">
                    <label class="fw-bold mb-2 small text-uppercase text-secondary">
                        <i class="fa-solid fa-hard-drive me-1 text-primary"></i>Chọn dung lượng bộ nhớ:
                    </label>
                    <div class="d-flex gap-2 flex-wrap" id="storageButtonsGroup">
                        <?php foreach ($storageOptions as $index => $option): ?>
                            <button type="button" class="btn btn-sm <?= $index === 0 ? 'btn-primary active' : 'btn-outline-secondary' ?> rounded-pill px-3 py-2 fw-bold storage-btn" data-extra="<?= (int)$option['extra'] ?>" data-rom="<?= htmlspecialchars($option['rom']) ?>" onclick="selectDetailRom(this, '<?= htmlspecialchars(addslashes($option['rom'])) ?>', <?= (int)$option['extra'] ?>)">
                                <?= htmlspecialchars($option['label'] ?? $option['rom']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="row g-2 mt-auto pt-3">
                    <?php if ((int)$product['quantity'] > 0): ?>
                    <div class="col-md-7 col-12">
                        <button type="button" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3" onclick="buyNowFromDetail(<?= $product['id'] ?>)">
                            <i class="fa-solid fa-bag-shopping me-2"></i>MUA NGAY (GIAO TẬN NƠI)
                        </button>
                    </div>
                    <div class="col-md-5 col-12">
                        <button type="button" class="btn btn-outline-vphone btn-lg w-100 rounded-pill fw-bold py-3" onclick="addToCartFromDetail(this, <?= $product['id'] ?>)">
                            <i class="fa-solid fa-cart-plus me-2"></i>Thêm vào giỏ
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="col-12">
                        <button type="button" class="btn btn-secondary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3" disabled>
                            <i class="fa-solid fa-ban me-2"></i>SẢN PHẨM ĐÃ HẾT HÀNG
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mt-3 p-3 rounded-4 bg-light border">
                    <div class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1">
                        <i class="fa-solid fa-gift text-danger"></i> Ưu đãi đặc quyền khi mua tại V-Phone:
                    </div>
                    <ul class="list-unstyled mb-0 small text-secondary d-flex flex-column gap-1">
                        <li><i class="fa-solid fa-circle-check text-success me-1"></i> Tặng gói bảo hành VIP <strong>1 đổi 1 trong 30 ngày</strong> đầu tiên.</li>
                        <li><i class="fa-solid fa-circle-check text-success me-1"></i> Giảm thêm tới <strong>200.000đ</strong> khi nhập mã voucher trong giỏ hàng.</li>
                        <li><i class="fa-solid fa-circle-check text-success me-1"></i> Miễn phí giao hàng hỏa tốc trong 2 giờ nội thành.</li>
                        <li><i class="fa-solid fa-circle-check text-success me-1"></i> Trợ giá thu cũ đổi mới lên đến <strong>2.000.000đ</strong>.</li>
                    </ul>
                    <div class="mt-2 pt-2 border-top">
                        <a href="index.php?page=warranty" target="_blank" class="small text-primary text-decoration-none fw-semibold d-inline-flex align-items-center">
                            <i class="fa-solid fa-shield-halved me-1"></i> Xem chi tiết chính sách bảo hành & đổi trả <i class="fa-solid fa-arrow-right ms-1" style="font-size:10px;"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-lg-7">
            <div class="bg-white p-4 rounded-4 shadow-sm border h-100">
                <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-microchip text-primary"></i> Thông Số Kỹ Thuật Chi Tiết
                </h5>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0 small">
                        <tbody>
                            <tr>
                                <td class="text-secondary fw-semibold" style="width: 35%;"><i class="fa-solid fa-mobile-screen me-2 text-primary"></i>Màn hình:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($product['screen'] ?? '6.7 inch AMOLED 120Hz') ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary fw-semibold"><i class="fa-solid fa-microchip me-2 text-primary"></i>Chip CPU:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($product['cpu'] ?? 'Snapdragon / Apple Bionic') ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary fw-semibold"><i class="fa-solid fa-memory me-2 text-primary"></i>RAM:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($product['ram'] ?? '8 GB') ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary fw-semibold"><i class="fa-solid fa-hard-drive me-2 text-primary"></i>Bộ nhớ trong:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($product['rom'] ?? '256 GB') ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary fw-semibold"><i class="fa-solid fa-battery-full me-2 text-primary"></i>Pin & Sạc:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($product['battery'] ?? '5000 mAh, sạc nhanh') ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary fw-semibold"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Tình trạng máy:</td>
                                <td>
                                    <span class="badge badge-soft-success rounded-pill px-3 py-1 fw-bold">
                                        <?= htmlspecialchars($product['condition_desc'] ?? 'Mới 100% nguyên hộp') ?>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="bg-white p-4 rounded-4 shadow-sm border h-100 d-flex flex-column justify-content-between">
                <div>
                    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-award text-warning"></i> Cam Kết Chất Lượng V-Phone
                    </h5>
                    <div class="d-flex flex-column gap-3 small">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary flex-shrink-0" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-box-check fs-6"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Hàng chính hãng 100%</div>
                                <div class="text-secondary">Nguyên seal, đầy đủ phụ kiện và hóa đơn bảo hành điện tử chính hãng.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success flex-shrink-0" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-rotate-left fs-6"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Lỗi là đổi mới trong 30 ngày</div>
                                <div class="text-secondary">Áp dụng cho mọi sản phẩm phát sinh lỗi từ nhà sản xuất.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-3 p-2 bg-info bg-opacity-10 text-info flex-shrink-0" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-truck-fast fs-6"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Giao nhanh hỏa tốc toàn quốc</div>
                                <div class="text-secondary">Kiểm tra máy trước khi thanh toán tiền, yên tâm tuyệt đối.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex flex-wrap justify-content-between align-items-center text-secondary small gap-2">
                    <div>Hotline hỗ trợ kỹ thuật: <strong class="text-primary fs-6">1800 6868</strong></div>
                    <a href="index.php?page=warranty" target="_blank" class="text-primary text-decoration-none fw-semibold">
                        <i class="fa-solid fa-shield-halved me-1"></i>Chính sách bảo hành &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================
     KHU VỰC ĐÁNH GIÁ & SỐ SAO SẢN PHẨM (REVIEWS & RATING)
     ======================================================== -->
<div class="container my-5" id="reviews-section">
    <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border">
        <!-- TIÊU ĐỀ KHU VỰC ĐÁNH GIÁ -->
        <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 border-bottom mb-4 gap-2">
            <div>
                <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-star-half-stroke text-warning"></i> Đánh Giá & Nhận Xét Từ Khách Hàng
                </h4>
                <div class="text-secondary small">Nhận xét khách quan từ người dùng thực tế tại V-Phone Store</div>
            </div>
            <button type="button" class="btn btn-primary rounded-pill fw-bold px-4 py-2 shadow-sm" onclick="toggleReviewForm()">
                <i class="fa-solid fa-pen-to-square me-2"></i>Viết Đánh Giá Ngay
            </button>
        </div>

        <!-- THẺ TỔNG QUAN ĐIỂM SỐ & PHÂN BỐ SAO -->
        <div class="row g-4 align-items-center mb-4 p-4 rounded-4 bg-light border">
            <!-- Cột điểm số lớn -->
            <div class="col-lg-3 col-md-4 text-center border-end-md">
                <div class="display-4 fw-bold text-warning lh-1 mb-2" id="summaryAvgRating">
                    <?= number_format((float)($ratingSummary['avg_rating'] ?? 5.0), 1) ?>
                </div>
                <div class="mb-2" id="summaryStarsDisplay">
                    <?= renderStarRating((float)($ratingSummary['avg_rating'] ?? 5.0), 'fs-5') ?>
                </div>
                <div class="text-secondary small fw-semibold" id="summaryTotalReviewsText">
                    Dựa trên <span id="displayTotalReviewCount"><?= (int)($ratingSummary['total_reviews'] ?? 0) ?></span> lượt đánh giá
                </div>
            </div>

            <!-- Cột thanh tiến trình phân bố số sao -->
            <div class="col-lg-6 col-md-8">
                <div class="d-flex flex-column gap-2" id="starBreakdownContainer">
                    <?php for ($star = 5; $star >= 1; $star--): ?>
                        <?php
                            $cnt = (int)($ratingSummary['stars_count'][$star] ?? 0);
                            $pct = (int)($ratingSummary['stars_percent'][$star] ?? 0);
                        ?>
                        <div class="d-flex align-items-center gap-2 small">
                            <span class="text-secondary fw-semibold text-nowrap" style="width: 50px;">
                                <?= $star ?> <i class="fa-solid fa-star text-warning" style="font-size: 0.8rem;"></i>
                            </span>
                            <div class="review-bar-container">
                                <div class="review-bar-fill" id="barFill<?= $star ?>" style="width: <?= $pct ?>%;"></div>
                            </div>
                            <span class="text-muted text-end fw-semibold text-nowrap" style="width: 75px; font-size: 0.75rem;" id="barCount<?= $star ?>">
                                <?= $cnt ?> (<?= $pct ?>%)
                            </span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Cột kêu gọi hành động -->
            <div class="col-lg-3 col-12 text-center text-lg-start ps-lg-4 border-start-lg">
                <div class="small fw-bold text-dark mb-1">Bạn đã sử dụng sản phẩm này?</div>
                <p class="text-secondary small mb-3">Đánh giá của bạn sẽ giúp cộng đồng có cái nhìn khách quan và chính xác nhất.</p>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-bold px-3 py-2 w-100" onclick="toggleReviewForm(true)">
                    <i class="fa-solid fa-comment-dots me-1"></i>Chia sẻ trải nghiệm
                </button>
            </div>
        </div>

        <!-- FORM GỬI ĐÁNH GIÁ (ẨN/HIỆN THÔNG MINH) -->
        <div id="reviewFormCollapse" class="card rounded-4 border-primary border-2 shadow-sm mb-4" style="display: none; background: #fafcff;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-primary mb-0">
                        <i class="fa-solid fa-pen-nib me-2"></i>Gửi Đánh Giá Của Bạn Cho "<?= htmlspecialchars($product['name']) ?>"
                    </h5>
                    <button type="button" class="btn-close" onclick="toggleReviewForm(false)" aria-label="Đóng"></button>
                </div>

                <?php if (!empty($userHasPurchased)): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 small rounded-3 mb-3">
                        <i class="fa-solid fa-circle-check fs-5 text-success"></i>
                        <div>
                            Hệ thống ghi nhận bạn <strong>đã mua sản phẩm này tại V-Phone</strong>. Đánh giá của bạn sẽ được ưu tiên hiển thị với huy hiệu <strong>"Đã mua tại V-Phone"</strong>!
                        </div>
                    </div>
                <?php endif; ?>

                <form id="productReviewForm" onsubmit="submitReviewAjax(event)">
                    <input type="hidden" name="action" value="submit">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                    <input type="hidden" name="ajax" value="1">

                    <!-- CHỌN SỐ SAO TƯƠNG TÁC -->
                    <div class="mb-3 text-center py-3 bg-white rounded-3 border">
                        <label class="form-label small fw-bold text-uppercase text-secondary mb-2 d-block">
                            Bạn chấm sản phẩm này mấy sao?
                        </label>
                        <div class="star-picker" id="starPicker">
                            <input type="radio" id="star5" name="rating" value="5" checked onchange="updateStarDesc(5)">
                            <label for="star5" title="5 sao - Tuyệt vời"><i class="fa-solid fa-star"></i></label>
                            
                            <input type="radio" id="star4" name="rating" value="4" onchange="updateStarDesc(4)">
                            <label for="star4" title="4 sao - Hài lòng"><i class="fa-solid fa-star"></i></label>
                            
                            <input type="radio" id="star3" name="rating" value="3" onchange="updateStarDesc(3)">
                            <label for="star3" title="3 sao - Bình thường"><i class="fa-solid fa-star"></i></label>
                            
                            <input type="radio" id="star2" name="rating" value="2" onchange="updateStarDesc(2)">
                            <label for="star2" title="2 sao - Không hài lòng"><i class="fa-solid fa-star"></i></label>
                            
                            <input type="radio" id="star1" name="rating" value="1" onchange="updateStarDesc(1)">
                            <label for="star1" title="1 sao - Rất tệ"><i class="fa-solid fa-star"></i></label>
                        </div>
                        <div class="fw-bold text-warning small mt-1" id="starDescText"><i class="fa-solid fa-star me-1"></i>Tuyệt vời / Rất hài lòng (5 sao)</div>
                    </div>

                    <!-- GỢI Ý NHANH NỘI DUNG (CHIPS) -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            <i class="fa-solid fa-lightbulb text-warning me-1"></i>Bấm để thêm nhanh cảm nhận:
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="tag-suggestion-chip" onclick="addReviewTag('Giao hàng hỏa tốc cực nhanh, đúng 2 tiếng!')">+ Giao hàng hỏa tốc</span>
                            <span class="tag-suggestion-chip" onclick="addReviewTag('Máy nguyên seal chính hãng, phụ kiện đầy đủ.')">+ Máy nguyên seal</span>
                            <span class="tag-suggestion-chip" onclick="addReviewTag('Pin rất trâu, dùng cả ngày thoải mái.')">+ Pin trâu</span>
                            <span class="tag-suggestion-chip" onclick="addReviewTag('Màn hình 120Hz siêu mượt, màu sắc sống động.')">+ Màn hình đẹp</span>
                            <span class="tag-suggestion-chip" onclick="addReviewTag('Camera chụp đêm cực kỳ sắc nét.')">+ Camera nét</span>
                            <span class="tag-suggestion-chip" onclick="addReviewTag('Nhân viên CSKH tư vấn hỗ trợ rất nhiệt tình.')">+ CSKH nhiệt tình</span>
                        </div>
                    </div>

                    <!-- NỘI DUNG BÌNH LUẬN -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">
                            Nhận xét chi tiết của bạn: <span class="text-danger">*</span>
                        </label>
                        <textarea id="reviewCommentInput" name="comment" class="form-control rounded-3" rows="4" required minlength="6" placeholder="Chia sẻ cảm nhận về thiết kế, thời lượng pin, màn hình, hiệu năng hoặc dịch vụ giao hàng tại V-Phone..."></textarea>
                    </div>

                    <!-- THÔNG TIN NGƯỜI ĐÁNH GIÁ -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary mb-1">Họ và tên: <span class="text-danger">*</span></label>
                            <input type="text" name="fullname" class="form-control rounded-pill" required value="<?= htmlspecialchars($_SESSION['user']['fullname'] ?? '') ?>" placeholder="Ví dụ: Nguyễn Văn A">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary mb-1">Số điện thoại (Bảo mật):</label>
                            <input type="tel" name="phone" class="form-control rounded-pill" value="<?= htmlspecialchars($_SESSION['user']['phone'] ?? '') ?>" placeholder="0901234567 (Để xác thực đơn hàng)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary mb-1">Email nhận phản hồi:</label>
                            <input type="email" name="email" class="form-control rounded-pill" value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>" placeholder="name@example.com">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light rounded-pill px-4" onclick="toggleReviewForm(false)">Hủy bỏ</button>
                        <button type="submit" id="btnSubmitReview" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                            <i class="fa-solid fa-paper-plane me-2"></i>Gửi Đánh Giá Ngay
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- BỘ LỌC ĐÁNH GIÁ THEO SỐ SAO -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 pb-3 border-bottom">
            <div class="d-flex align-items-center gap-2 flex-wrap" id="reviewFilterGroup">
                <span class="small fw-bold text-secondary me-1"><i class="fa-solid fa-filter me-1 text-primary"></i>Lọc theo:</span>
                <button type="button" class="btn btn-sm btn-filter-review active btn-primary text-white" data-star="all" onclick="filterReviewsByStar('all', this)">
                    Tất cả (<span id="filterCountAll"><?= (int)($ratingSummary['total_reviews'] ?? 0) ?></span>)
                </button>
                <button type="button" class="btn btn-sm btn-filter-review btn-outline-secondary" data-star="5" onclick="filterReviewsByStar(5, this)">
                    5 Sao (<span id="filterCount5"><?= (int)($ratingSummary['stars_count'][5] ?? 0) ?></span>)
                </button>
                <button type="button" class="btn btn-sm btn-filter-review btn-outline-secondary" data-star="4" onclick="filterReviewsByStar(4, this)">
                    4 Sao (<span id="filterCount4"><?= (int)($ratingSummary['stars_count'][4] ?? 0) ?></span>)
                </button>
                <button type="button" class="btn btn-sm btn-filter-review btn-outline-secondary" data-star="3" onclick="filterReviewsByStar(3, this)">
                    3 Sao (<span id="filterCount3"><?= (int)($ratingSummary['stars_count'][3] ?? 0) ?></span>)
                </button>
                <button type="button" class="btn btn-sm btn-filter-review btn-outline-secondary" data-star="2" onclick="filterReviewsByStar(2, this)">
                    2 Sao (<span id="filterCount2"><?= (int)($ratingSummary['stars_count'][2] ?? 0) ?></span>)
                </button>
                <button type="button" class="btn btn-sm btn-filter-review btn-outline-secondary" data-star="1" onclick="filterReviewsByStar(1, this)">
                    1 Sao (<span id="filterCount1"><?= (int)($ratingSummary['stars_count'][1] ?? 0) ?></span>)
                </button>
                <button type="button" class="btn btn-sm btn-filter-review btn-outline-secondary" data-star="reply" onclick="filterReviewsWithReply(this)">
                    Có phản hồi CSKH
                </button>
            </div>
        </div>

        <!-- DANH SÁCH CÁC BÀI ĐÁNH GIÁ -->
        <div id="reviewsListContainer">
            <?php if (empty($reviews)): ?>
                <div id="noReviewsNotice" class="text-center py-5">
                    <div class="rounded-circle bg-light d-inline-flex p-4 text-warning mb-3">
                        <i class="fa-solid fa-star fa-3x"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Chưa có đánh giá nào cho sản phẩm này</h5>
                    <p class="text-secondary small mb-3">Hãy là người đầu tiên chia sẻ cảm nhận về chiếc điện thoại này bạn nhé!</p>
                    <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold" onclick="toggleReviewForm(true)">
                        <i class="fa-solid fa-pen-to-square me-1"></i>Viết đánh giá đầu tiên
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                    <?php
                        $cInitials = mb_strtoupper(mb_substr(trim($rev['fullname']), 0, 1, 'UTF-8'), 'UTF-8');
                        $hasAdminReply = !empty($rev['admin_reply']);
                    ?>
                    <div class="review-card-item" data-rating="<?= (int)$rev['rating'] ?>" data-has-reply="<?= $hasAdminReply ? '1' : '0' ?>">
                        <div class="d-flex align-items-start gap-3">
                            <div class="review-avatar-circle">
                                <?= $cInitials ?>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($rev['fullname']) ?></span>
                                        <?php if (!empty($rev['is_buyer'])): ?>
                                            <span class="badge badge-verified-buyer rounded-pill">
                                                <i class="fa-solid fa-circle-check me-1"></i>Đã mua tại V-Phone
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-muted small" style="font-size: 0.78rem;">
                                        <i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars(formatTimeAgo($rev['created_at'])) ?>
                                    </span>
                                </div>

                                <div class="mb-2">
                                    <?= renderStarRating((float)$rev['rating'], 'small') ?>
                                </div>

                                <div class="text-dark small lh-base" style="white-space: pre-line;"><?= htmlspecialchars($rev['comment']) ?></div>

                                <?php if ($hasAdminReply): ?>
                                    <div class="review-admin-reply-box small">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <span class="fw-bold text-primary">
                                                <i class="fa-solid fa-headset me-1"></i>Phản hồi từ V-Phone CSKH:
                                            </span>
                                            <?php if (!empty($rev['admin_reply_at'])): ?>
                                                <span class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars(formatTimeAgo($rev['admin_reply_at'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-secondary"><?= nl2br(htmlspecialchars($rev['admin_reply'])) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
window.PRODUCT_COLOR_MAP = <?= json_encode(getAllProductColorMap(), JSON_UNESCAPED_UNICODE) ?>;
window.CURRENT_PRODUCT_ID = <?= (int)$product['id'] ?>;
const basePrice = <?= (int)$basePrice ?>;
const baseOldPrice = <?= (int)$baseOldPrice ?>;
let currentSelectedRom = <?= json_encode($storageOptions[0]['rom'] ?? '256 GB', JSON_UNESCAPED_UNICODE) ?>;
let currentExtraMoney = <?= (int)($storageOptions[0]['extra'] ?? 0) ?>;
let currentSelectedColor = "<?= htmlspecialchars($firstAvailableColor ?? ($colorList[0] ?? 'Tiêu chuẩn')) ?>";
const originalImgPath = "<?= htmlspecialchars($product['image']) ?>";

function resolveColorImage(colorName) {
    const color = (colorName || '').trim();
    if (window.PRODUCT_COLOR_MAP && window.CURRENT_PRODUCT_ID) {
        const pMap = window.PRODUCT_COLOR_MAP[window.CURRENT_PRODUCT_ID];
        if (pMap && pMap[color]) {
            return pMap[color];
        }
    }
    return originalImgPath;
}

function selectDetailColor(btn, colorName) {
    document.querySelectorAll('.color-pill-btn').forEach(b => {
        b.className = "btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-2 color-pill-btn";
    });
    btn.className = "btn btn-sm btn-primary active rounded-pill fw-bold px-3 py-2 color-pill-btn";

    currentSelectedColor = colorName;
    document.getElementById('chosenColorText').innerText = colorName;

    const img = document.getElementById('detailMainImg');
    const newSrc = resolveColorImage(colorName);

    img.style.transition = 'opacity 0.22s cubic-bezier(0.16, 1, 0.3, 1), transform 0.22s cubic-bezier(0.16, 1, 0.3, 1)';
    img.style.opacity = '0.2';
    img.style.transform = 'scale(0.96)';
    setTimeout(() => {
        img.src = newSrc;
        img.onload = () => {
            img.style.opacity = '1';
            img.style.transform = 'scale(1)';
        };
    }, 100);
}

function selectDetailRom(btn, romName, extra) {
    document.querySelectorAll('.storage-btn').forEach(b => {
        b.className = "btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-2 storage-btn";
    });
    btn.className = "btn btn-sm btn-primary active rounded-pill fw-bold px-3 py-2 storage-btn";

    currentSelectedRom = romName;
    currentExtraMoney = extra;

    const newP = basePrice + extra;
    const newOldP = baseOldPrice + extra;
    document.getElementById('displayPrice').innerText = new Intl.NumberFormat('vi-VN').format(newP) + ' đ';
    document.getElementById('displayOldPrice').innerText = new Intl.NumberFormat('vi-VN').format(newOldP) + ' đ';
}

function buyNowFromDetail(productId) {
    const selectedImage = resolveColorImage(currentSelectedColor);
    window.location.href = `index.php?page=checkout&action=buy_now&id=${productId}&color=${encodeURIComponent(currentSelectedColor)}&rom=${encodeURIComponent(currentSelectedRom)}&extra=${currentExtraMoney}&img=${encodeURIComponent(selectedImage)}`;
}

function addToCartFromDetail(btn, productId) {
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Đang thêm...';
    }
    const selectedImage = resolveColorImage(currentSelectedColor);
    const url = `index.php?page=cart&action=add&ajax=1&id=${productId}&color=${encodeURIComponent(currentSelectedColor)}&rom=${encodeURIComponent(currentSelectedRom)}&extra=${currentExtraMoney}&img=${encodeURIComponent(selectedImage)}`;
    fetch(url).then(r => r.json()).then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
        if (data.success === false) {
            if (typeof window.showVPhoneToast === 'function') {
                window.showVPhoneToast(data.message || 'Không thể thêm vào giỏ hàng!', false);
            } else {
                alert(data.message || 'Không thể thêm vào giỏ hàng!');
            }
            return;
        }
        const badge = document.getElementById('cartBadge');
        if (badge) {
            badge.innerText = data.cart_count;
            badge.classList.remove('badge-bounce');
            void badge.offsetWidth;
            badge.classList.add('badge-bounce');
        }
        if (typeof window.showVPhoneToast === 'function') {
            window.showVPhoneToast(`Đã thêm vào giỏ: Màu ${currentSelectedColor} (${currentSelectedRom})`, true);
        }
    }).catch(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
        window.location.href = `index.php?page=cart&action=add&id=${productId}&color=${encodeURIComponent(currentSelectedColor)}&rom=${encodeURIComponent(currentSelectedRom)}&extra=${currentExtraMoney}&img=${encodeURIComponent(selectedImage)}`;
    });
}

// --- XỬ LÝ HỆ THỐNG ĐÁNH GIÁ (REVIEWS & RATING) ---
function toggleReviewForm(forceState) {
    const formBox = document.getElementById('reviewFormCollapse');
    if (!formBox) return;
    const isVisible = formBox.style.display !== 'none';
    const shouldOpen = (typeof forceState === 'boolean') ? forceState : !isVisible;
    if (shouldOpen) {
        formBox.style.display = 'block';
        formBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        formBox.style.display = 'none';
    }
}

function updateStarDesc(val) {
    const descMap = {
        5: 'Tuyệt vời / Rất hài lòng',
        4: 'Hài lòng / Tốt',
        3: 'Tạm ổn / Bình thường',
        2: 'Chưa ưng ý / Không hài lòng',
        1: 'Rất tệ / Thất vọng'
    };
    const el = document.getElementById('starDescText');
    if (el) {
        let stars = '';
        for (let i = 0; i < val; i++) stars += '<i class="fa-solid fa-star me-0.5"></i>';
        el.innerHTML = stars + ' ' + (descMap[val] || '') + ` (${val} sao)`;
    }
}

function addReviewTag(text) {
    const input = document.getElementById('reviewCommentInput');
    if (!input) return;
    if (input.value.trim().length > 0) {
        input.value = input.value.trim() + ' ' + text;
    } else {
        input.value = text;
    }
    input.focus();
}

function filterReviewsByStar(star, btn) {
    document.querySelectorAll('.btn-filter-review').forEach(b => {
        b.className = 'btn btn-sm btn-filter-review btn-outline-secondary';
    });
    btn.className = 'btn btn-sm btn-filter-review active btn-primary text-white';

    const items = document.querySelectorAll('.review-card-item');
    let matchedCount = 0;
    items.forEach(item => {
        const itemRating = item.getAttribute('data-rating');
        if (star === 'all' || itemRating === String(star)) {
            item.style.display = 'block';
            matchedCount++;
        } else {
            item.style.display = 'none';
        }
    });

    let noNotice = document.getElementById('noFilterMatchNotice');
    if (matchedCount === 0) {
        if (!noNotice) {
            noNotice = document.createElement('div');
            noNotice.id = 'noFilterMatchNotice';
            noNotice.className = 'text-center py-4 text-muted small';
            noNotice.innerHTML = '<i class="fa-solid fa-filter-circle-xmark fa-2x mb-2 d-block"></i>Chưa có đánh giá nào thuộc tiêu chí lọc này.';
            document.getElementById('reviewsListContainer').appendChild(noNotice);
        }
        noNotice.style.display = 'block';
    } else if (noNotice) {
        noNotice.style.display = 'none';
    }
}

function filterReviewsWithReply(btn) {
    document.querySelectorAll('.btn-filter-review').forEach(b => {
        b.className = 'btn btn-sm btn-filter-review btn-outline-secondary';
    });
    btn.className = 'btn btn-sm btn-filter-review active btn-primary text-white';

    const items = document.querySelectorAll('.review-card-item');
    let matchedCount = 0;
    items.forEach(item => {
        const hasReply = item.getAttribute('data-has-reply') === '1';
        if (hasReply) {
            item.style.display = 'block';
            matchedCount++;
        } else {
            item.style.display = 'none';
        }
    });

    let noNotice = document.getElementById('noFilterMatchNotice');
    if (matchedCount === 0) {
        if (!noNotice) {
            noNotice = document.createElement('div');
            noNotice.id = 'noFilterMatchNotice';
            noNotice.className = 'text-center py-4 text-muted small';
            noNotice.innerHTML = '<i class="fa-solid fa-filter-circle-xmark fa-2x mb-2 d-block"></i>Chưa có bài đánh giá nào có phản hồi CSKH.';
            document.getElementById('reviewsListContainer').appendChild(noNotice);
        }
        noNotice.style.display = 'block';
    } else if (noNotice) {
        noNotice.style.display = 'none';
    }
}

function submitReviewAjax(event) {
    event.preventDefault();
    const form = event.target;
    const btn = document.getElementById('btnSubmitReview');
    const origHtml = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Đang gửi...';

    const formData = new FormData(form);

    fetch('index.php?page=review&action=submit&ajax=1', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = origHtml;

        if (data.success === false) {
            if (typeof window.showVPhoneToast === 'function') {
                window.showVPhoneToast(data.message || 'Lỗi gửi đánh giá!', false);
            } else {
                alert(data.message || 'Lỗi gửi đánh giá!');
            }
            return;
        }

        if (typeof window.showVPhoneToast === 'function') {
            window.showVPhoneToast(data.message || 'Gửi đánh giá thành công!', true);
        }

        const fullname = form.querySelector('[name="fullname"]').value.trim();
        const comment = form.querySelector('[name="comment"]').value.trim();
        const rating = parseInt(form.querySelector('[name="rating"]:checked').value, 10);
        const isBuyer = data.is_buyer ? true : false;
        const initial = fullname.charAt(0).toUpperCase();

        let starHtml = '';
        for (let i = 0; i < rating; i++) starHtml += '<i class="fa-solid fa-star"></i>';
        for (let i = rating; i < 5; i++) starHtml += '<i class="fa-regular fa-star text-muted opacity-50"></i>';

        const newCard = document.createElement('div');
        newCard.className = 'review-card-item shadow-sm border-primary border-opacity-25';
        newCard.setAttribute('data-rating', rating);
        newCard.setAttribute('data-has-reply', '0');
        newCard.innerHTML = `
            <div class="d-flex align-items-start gap-3">
                <div class="review-avatar-circle" style="background: linear-gradient(135deg, #10b981, #059669);">
                    ${initial}
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-bold text-dark fs-6">${escapeHtml(fullname)}</span>
                            ${isBuyer ? '<span class="badge badge-verified-buyer rounded-pill"><i class="fa-solid fa-circle-check me-1"></i>Đã mua tại V-Phone</span>' : ''}
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill small" style="font-size:0.68rem;">Vừa gửi</span>
                        </div>
                        <span class="text-muted small" style="font-size: 0.78rem;">
                            <i class="fa-regular fa-clock me-1"></i>Vừa xong
                        </span>
                    </div>
                    <div class="mb-2">
                        <span class="star-rating text-warning small">${starHtml}</span>
                    </div>
                    <div class="text-dark small lh-base" style="white-space: pre-line;">${escapeHtml(comment)}</div>
                </div>
            </div>
        `;

        const container = document.getElementById('reviewsListContainer');
        const emptyNotice = document.getElementById('noReviewsNotice');
        if (emptyNotice) emptyNotice.remove();
        container.prepend(newCard);

        if (data.summary) {
            const avgEl = document.getElementById('summaryAvgRating');
            if (avgEl) avgEl.innerText = Number(data.summary.avg_rating).toFixed(1);
            const totalEl = document.getElementById('displayTotalReviewCount');
            if (totalEl) totalEl.innerText = data.summary.total_reviews;
            const filterAllEl = document.getElementById('filterCountAll');
            if (filterAllEl) filterAllEl.innerText = data.summary.total_reviews;

            for (let s = 1; s <= 5; s++) {
                const bCount = document.getElementById('barCount' + s);
                const bFill = document.getElementById('barFill' + s);
                const fCount = document.getElementById('filterCount' + s);
                const c = data.summary.stars_count[s] || 0;
                const p = data.summary.stars_percent[s] || 0;
                if (bCount) bCount.innerText = `${c} (${p}%)`;
                if (bFill) bFill.style.width = p + '%';
                if (fCount) fCount.innerText = c;
            }
        }

        form.querySelector('[name="comment"]').value = '';
        toggleReviewForm(false);
        newCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        form.submit();
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}
</script>

<?php require_once 'app/views/includes/footer.php'; ?>
