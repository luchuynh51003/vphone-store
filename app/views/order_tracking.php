<?php
$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
$trackedOrder = $trackedOrder ?? null;
$trackedDetails = $trackedDetails ?? [];
$userOrders = $userOrders ?? [];
$userOrderDetails = $userOrderDetails ?? [];
$statusCounts = $statusCounts ?? ['all' => 0, 'Chờ xử lý' => 0, 'Đang giao hàng' => 0, 'Đã giao thành công' => 0, 'Đã hủy' => 0];
$activeTab = $activeTab ?? 'all';
$orderMessage = $orderMessage ?? '';
$orderError = $orderError ?? '';
$pageTitle = $trackedOrder ? 'Theo Dõi Đơn Hàng #VP-' . $trackedOrder['id'] . ' - V-Phone' : 'Theo Dõi & Lịch Sử Đơn Hàng - V-Phone';

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
require_once 'app/views/includes/functions.php';
?>

<style>
/* ==========================================================================
   V-PHONE LUXURY ORDER TRACKING STYLES (2026 EDITION)
   ========================================================================== */
.order-tracking-page {
    padding-top: 28px;
    padding-bottom: 64px;
}

/* THẺ HERO / HEADER */
.tracking-header-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    padding: 24px 28px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
    margin-bottom: 24px;
}

/* BADGE TRẠNG THÁI CAO CẤP */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    border-radius: 9999px;
    font-weight: 700;
    font-size: 0.88rem;
    letter-spacing: 0.2px;
}
.status-pill-pending {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}
.status-pill-shipping {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.status-pill-delivered {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.status-pill-cancelled {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}
.status-pill .pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}
.status-pill-pending .pulse-dot { background: #d97706; }
.status-pill-shipping .pulse-dot { background: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.25); }
.status-pill-delivered .pulse-dot { background: #16a34a; box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.25); }
.status-pill-cancelled .pulse-dot { background: #dc2626; }

/* TIẾN TRÌNH STEPPER HIỆN ĐẠI */
.tracking-stepper-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    padding: 32px 28px 24px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
    margin-bottom: 24px;
}

.timeline-track-wrap {
    position: relative;
    padding: 20px 0 10px;
}
.timeline-track-line {
    position: absolute;
    top: 45px;
    left: 8%;
    right: 8%;
    height: 4px;
    background: #e2e8f0;
    border-radius: 4px;
    z-index: 1;
}
.timeline-track-progress {
    height: 100%;
    background: linear-gradient(90deg, #10b981 0%, #0066cc 100%);
    border-radius: 4px;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
.timeline-step {
    position: relative;
    z-index: 2;
    text-align: center;
    flex: 1;
}
.step-icon-bubble {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #f8fafc;
    color: #94a3b8;
    border: 3px solid #ffffff;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    transition: all 0.3s ease;
}
.timeline-step.completed .step-icon-bubble {
    background: #10b981;
    color: #ffffff;
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
}
.timeline-step.active .step-icon-bubble {
    background: #0066cc;
    color: #ffffff;
    box-shadow: 0 0 0 6px rgba(0, 102, 204, 0.18), 0 8px 20px rgba(0, 102, 204, 0.3);
    transform: scale(1.08);
}
.timeline-step.cancelled .step-icon-bubble {
    background: #ef4444;
    color: #ffffff;
    box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35);
}
.step-title-text {
    font-weight: 700;
    font-size: 0.92rem;
    margin-top: 14px;
    color: #1e293b;
}
.timeline-step.completed .step-title-text { color: #059669; }
.timeline-step.active .step-title-text { color: #0066cc; font-weight: 800; }
.step-sub-text {
    font-size: 0.76rem;
    color: #64748b;
    margin-top: 3px;
    line-height: 1.35;
}

/* THẺ THÔNG TIN GIAO NHẬN & THANH TOÁN */
.info-card-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 22px;
    height: 100%;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.info-card-box:hover {
    border-color: #cce3ff;
    box-shadow: 0 6px 18px rgba(0, 102, 204, 0.06);
}
.info-header-tag {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #0066cc;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* KHUNG DANH SÁCH SẢN PHẨM */
.order-products-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    margin-bottom: 24px;
}
.product-row-item {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 18px 24px;
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.2s ease;
}
.product-row-item:last-child {
    border-bottom: none;
}
.product-row-item:hover {
    background-color: #fbfdff;
}

/* ẢNH THIẾT BỊ ĐIỆN THOẠI CHUẨN XÁC, SẮC NÉT */
.prod-thumb-container {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 6px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    position: relative;
    overflow: hidden;
}
.prod-thumb-container img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.3s ease;
}
.product-row-item:hover .prod-thumb-container img {
    transform: scale(1.08);
}

.variant-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f1f5f9;
    color: #334155;
    font-size: 0.76rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 9999px;
    border: 1px solid #e2e8f0;
}
.color-dot-swatch {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 1px solid rgba(0,0,0,0.15);
    display: inline-block;
    flex-shrink: 0;
}

/* THẺ TỔNG KẾT CHI PHÍ */
.order-cost-summary-box {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 24px 28px;
}

/* THẺ ĐƠN HÀNG TRONG DANH SÁCH "ĐƠN HÀNG CỦA TÔI" */
.order-history-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    box-shadow: 0 3px 14px rgba(15, 23, 42, 0.03);
    margin-bottom: 18px;
    overflow: hidden;
    transition: all 0.25s ease;
}
.order-history-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 8px 24px rgba(0, 102, 204, 0.08);
    transform: translateY(-2px);
}
.order-history-card-header {
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    padding: 16px 22px;
}
.order-history-card-body {
    padding: 16px 22px;
}
.order-history-card-footer {
    background: #fcfdfe;
    border-top: 1px solid #f1f5f9;
    padding: 16px 22px;
}

/* THANH TRA CỨU NHANH GỌN GÀNG */
.compact-lookup-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 20px 24px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
    margin-bottom: 28px;
}

@media (max-width: 768px) {
    .order-tracking-page { padding-top: 16px; }
    .tracking-header-card { padding: 18px; border-radius: 18px; }
    .tracking-stepper-box { padding: 22px 14px; border-radius: 18px; }
    .timeline-track-line { top: 38px; left: 10%; right: 10%; }
    .step-icon-bubble { width: 42px; height: 42px; font-size: 0.95rem; }
    .step-title-text { font-size: 0.78rem; margin-top: 10px; }
    .step-sub-text { display: none; }
    .prod-thumb-container { width: 64px; height: 64px; }
    .product-row-item { padding: 14px; gap: 12px; }
}
</style>

<div class="order-tracking-page">
    <div class="container">

        <!-- BREADCRUMB ĐIỀU HƯỚNG -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted"><i class="fa-solid fa-house me-1"></i>Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="index.php?page=orders" class="text-decoration-none text-muted">Theo dõi đơn hàng</a></li>
                <?php if ($trackedOrder): ?>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Đơn hàng #VP-<?= $trackedOrder['id'] ?></li>
                <?php else: ?>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Lịch sử đặt hàng</li>
                <?php endif; ?>
            </ol>
        </nav>

        <!-- THÔNG BÁO FLASH MESSAGE -->
        <?php if (!empty($orderMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 py-3 px-4 mb-4 shadow-sm border-0 d-flex align-items-center gap-3" role="alert" style="background:#ecfdf5; border-left: 4px solid #10b981 !important;">
                <i class="fa-solid fa-circle-check text-success fs-4"></i>
                <div class="flex-grow-1">
                    <strong class="d-block text-dark">Thành công!</strong>
                    <span class="small text-secondary"><?= htmlspecialchars($orderMessage) ?></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($orderError)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 py-3 px-4 mb-4 shadow-sm border-0 d-flex align-items-center gap-3" role="alert" style="background:#fef2f2; border-left: 4px solid #ef4444 !important;">
                <i class="fa-solid fa-triangle-exclamation text-danger fs-4"></i>
                <div class="flex-grow-1">
                    <strong class="d-block text-dark">Thông báo từ hệ thống</strong>
                    <span class="small text-secondary"><?= htmlspecialchars($orderError) ?></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- ===================================================================== -->
        <!-- VIEW 1: CHI TIẾT ĐƠN HÀNG ĐANG THEO DÕI (KHI CÓ $trackedOrder) -->
        <!-- ===================================================================== -->
        <?php if ($trackedOrder): ?>
            <?php
            $st = $trackedOrder['status'];
            $isCancelled = ($st === 'Đã hủy');
            $isPending = ($st === 'Chờ xử lý');
            $isShipping = ($st === 'Đang giao hàng');
            $isDelivered = ($st === 'Đã giao thành công');

            // Tính phần trăm vạch tiến trình
            $progressPct = 12;
            if ($isShipping) $progressPct = 65;
            elseif ($isDelivered) $progressPct = 100;
            elseif ($isCancelled) $progressPct = 100;

            $trackingCode = 'VPX-' . date('Y', strtotime($trackedOrder['created_at'])) . '-' . str_pad((string)$trackedOrder['id'], 6, '0', STR_PAD_LEFT);
            ?>

            <!-- HEADER CHI TIẾT ĐƠN HÀNG -->
            <div class="tracking-header-card">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <a href="index.php?page=orders" class="btn btn-sm btn-light border rounded-pill px-3 py-1 text-secondary fw-semibold">
                                <i class="fa-solid fa-arrow-left me-1"></i>Danh sách đơn hàng
                            </a>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                                <i class="fa-solid fa-shield-halved me-1"></i>Vận đơn chính hãng
                            </span>
                        </div>
                        <h3 class="fw-black text-dark mb-1 d-flex align-items-center gap-2">
                            <span>Chi Tiết Đơn Hàng</span>
                            <span class="text-primary" style="letter-spacing: 0.5px;">#VP-<?= $trackedOrder['id'] ?></span>
                        </h3>
                        <div class="text-muted small">
                            <i class="fa-regular fa-clock me-1 text-primary"></i>Ngày đặt mua: <strong><?= date('d/m/Y - H:i', strtotime($trackedOrder['created_at'])) ?></strong>
                            <span class="mx-2">•</span>
                            <i class="fa-solid fa-barcode me-1 text-muted"></i>Mã vận đơn: <span class="fw-mono text-dark fw-bold"><?= $trackingCode ?></span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <?php if ($isPending): ?>
                            <div class="status-pill status-pill-pending">
                                <span class="pulse-dot"></span>
                                <i class="fa-solid fa-hourglass-half"></i>
                                <span>Chờ xử lý</span>
                            </div>
                        <?php elseif ($isShipping): ?>
                            <div class="status-pill status-pill-shipping">
                                <span class="pulse-dot"></span>
                                <i class="fa-solid fa-truck-fast"></i>
                                <span>Đang giao hàng</span>
                            </div>
                        <?php elseif ($isDelivered): ?>
                            <div class="status-pill status-pill-delivered">
                                <span class="pulse-dot"></span>
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Đã giao thành công</span>
                            </div>
                        <?php elseif ($isCancelled): ?>
                            <div class="status-pill status-pill-cancelled">
                                <span class="pulse-dot"></span>
                                <i class="fa-solid fa-circle-xmark"></i>
                                <span>Đã hủy đơn hàng</span>
                            </div>
                        <?php else: ?>
                            <div class="status-pill status-pill-pending">
                                <span><?= htmlspecialchars($st) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Nút in phiếu giao hàng -->
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold small shadow-sm" onclick="window.print()" title="In thông tin đơn hàng">
                            <i class="fa-solid fa-print me-1"></i>In đơn
                        </button>
                    </div>
                </div>
            </div>

            <!-- TIẾN TRÌNH STEPPER VẬN CHUYỂN TRỰC QUAN -->
            <div class="tracking-stepper-box">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-route text-primary fs-5"></i>
                        <span>Tiến Trình Vận Chuyển Hỏa Tốc</span>
                    </h5>
                    <?php if (!$isCancelled): ?>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 fw-bold small">
                            <i class="fa-solid fa-bolt me-1 text-warning"></i>Giao bởi V-Phone Express
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($isCancelled): ?>
                    <div class="alert alert-danger rounded-4 p-4 border-0 d-flex align-items-start gap-3 mb-0" style="background:#fef2f2;">
                        <div style="width:48px; height:48px; border-radius:50%; background:#fee2e2; color:#ef4444; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="fa-solid fa-ban fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-danger mb-1 fs-6">Đơn Hàng Đã Bị Hủy Bỏ</h6>
                            <p class="text-secondary small mb-2">
                                <?= !empty($trackedOrder['note']) && strpos($trackedOrder['note'], 'Khách hủy') !== false 
                                    ? htmlspecialchars($trackedOrder['note']) 
                                    : 'Đơn hàng #VP-' . $trackedOrder['id'] . ' đã được hủy. Số lượng sản phẩm đã tự động hoàn lại kho cho các khách hàng khác.' ?>
                            </p>
                            <a href="index.php?page=orders&action=reorder&order_id=<?= $trackedOrder['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                                <i class="fa-solid fa-rotate-right me-1"></i>Đặt lại đơn hàng này
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="timeline-track-wrap">
                        <div class="timeline-track-line">
                            <div class="timeline-track-progress" style="width: <?= $progressPct ?>%;"></div>
                        </div>

                        <div class="d-flex justify-content-between position-relative">
                            <!-- Cột 1: Đặt đơn -->
                            <div class="timeline-step completed">
                                <div class="step-icon-bubble">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                                <div class="step-title-text">Đặt Hàng Thành Công</div>
                                <div class="step-sub-text">Hệ thống đã tiếp nhận đơn #VP-<?= $trackedOrder['id'] ?></div>
                            </div>

                            <!-- Cột 2: Chuẩn bị hàng -->
                            <div class="timeline-step <?= ($isShipping || $isDelivered) ? 'completed' : ($isPending ? 'active' : '') ?>">
                                <div class="step-icon-bubble">
                                    <?php if ($isShipping || $isDelivered): ?>
                                        <i class="fa-solid fa-check"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-box-open"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-title-text">Kho Chuẩn Bị Máy</div>
                                <div class="step-sub-text">Kiểm tra tem seal, IMEI & đóng hộp</div>
                            </div>

                            <!-- Cột 3: Vận chuyển -->
                            <div class="timeline-step <?= $isDelivered ? 'completed' : ($isShipping ? 'active' : '') ?>">
                                <div class="step-icon-bubble">
                                    <?php if ($isDelivered): ?>
                                        <i class="fa-solid fa-check"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-truck-fast"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-title-text">Đang Giao Hỏa Tốc</div>
                                <div class="step-sub-text">Shipper V-Phone Express đang phát</div>
                            </div>

                            <!-- Cột 4: Hoàn tất -->
                            <div class="timeline-step <?= $isDelivered ? 'completed' : '' ?>">
                                <div class="step-icon-bubble">
                                    <i class="fa-solid fa-house-circle-check"></i>
                                </div>
                                <div class="step-title-text">Giao Hàng Thành Công</div>
                                <div class="step-sub-text">Đã giao tận tay & kích hoạt bảo hành</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- THÔNG TIN NGƯỜI NHẬN & VẬN CHUYỂN -->
            <div class="row g-4 mb-4">
                <!-- Cột 1: Địa chỉ giao nhận -->
                <div class="col-lg-6">
                    <div class="info-card-box">
                        <div class="info-header-tag text-danger">
                            <i class="fa-solid fa-location-dot"></i>
                            <span>Địa Chỉ Nhận Hàng</span>
                        </div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width: 46px; height: 46px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; flex-shrink: 0;">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0 fs-6"><?= htmlspecialchars($trackedOrder['fullname']) ?></h6>
                                <a href="tel:<?= htmlspecialchars($trackedOrder['phone']) ?>" class="text-primary text-decoration-none small fw-bold">
                                    <i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($trackedOrder['phone']) ?>
                                </a>
                            </div>
                        </div>
                        <div class="p-3 bg-light rounded-3 small text-secondary mb-2">
                            <i class="fa-solid fa-map-pin text-danger me-2"></i>
                            <strong class="text-dark">Địa chỉ:</strong> <?= htmlspecialchars($trackedOrder['address']) ?>
                        </div>
                        <?php if (!empty($trackedOrder['note'])): ?>
                            <div class="p-2 px-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3 small text-dark">
                                <i class="fa-solid fa-note-sticky text-warning me-1"></i>
                                <strong>Ghi chú:</strong> <?= htmlspecialchars($trackedOrder['note']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cột 2: Thanh toán & Vận chuyển -->
                <div class="col-lg-6">
                    <div class="info-card-box">
                        <div class="info-header-tag text-success">
                            <i class="fa-solid fa-credit-card"></i>
                            <span>Thanh Toán & Vận Chuyển</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <span class="small text-muted">Phương thức thanh toán:</span>
                            <span class="badge bg-light text-dark border rounded-pill px-3 py-1 fw-bold">
                                <i class="fa-solid fa-wallet me-1 text-primary"></i><?= htmlspecialchars($trackedOrder['payment_method']) ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <span class="small text-muted">Đơn vị vận chuyển:</span>
                            <span class="small fw-bold text-primary">
                                <i class="fa-solid fa-bolt text-warning me-1"></i>V-Phone Express™ (Hỏa tốc)
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <span class="small text-muted">Mã vận đơn:</span>
                            <span class="small fw-mono text-dark fw-bold"><?= $trackingCode ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted">Cước phí giao hàng:</span>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill fw-bold">Miễn phí toàn quốc</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DANH SÁCH SẢN PHẨM TRONG ĐƠN HÀNG (VỚI HÌNH ẢNH MÁY RÕ NÉT) -->
            <div class="order-products-box">
                <div class="p-3 px-4 bg-white border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-mobile-screen-button text-primary"></i>
                        <span>Sản Phẩm Trong Đơn Hàng</span>
                        <span class="badge bg-light border text-secondary rounded-pill small"><?= count($trackedDetails) ?> sản phẩm</span>
                    </h5>
                    <span class="text-success small fw-semibold">
                        <i class="fa-solid fa-shield-check me-1"></i>Bảo hành chính hãng 12 tháng
                    </span>
                </div>

                <div class="order-products-list">
                    <?php foreach ($trackedDetails as $item): ?>
                        <?php
                        $rawName = $item['product_name'];
                        $baseName = preg_replace('/\s*\(.*$/u', '', $rawName);
                        $variantRom = '';
                        if (preg_match('/\((\d+\s*GB|\d+\s*TB)/i', $rawName, $m)) {
                            $variantRom = trim($m[1]);
                        }
                        $variantColor = '';
                        if (preg_match('/Màu:\s*([^)]+)/u', $rawName, $m)) {
                            $variantColor = trim($m[1]);
                        }
                        $colorHex = !empty($variantColor) ? getColorHexValue($variantColor) : '#64748b';
                        $imgUrl = !empty($item['product_image']) ? $item['product_image'] : ((isset($orderModel)) ? $orderModel->resolveDetailImage($item) : 'assets/images/products/iphone-17-pro.png');
                        ?>

                        <div class="product-row-item">
                            <!-- Ảnh điện thoại thực tế -->
                            <div class="prod-thumb-container">
                                <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($baseName) ?>" loading="lazy">
                            </div>

                            <!-- Tên máy & Chi tiết cấu hình -->
                            <div class="flex-grow-1 min-w-0">
                                <h6 class="fw-bold text-dark mb-1 fs-6 text-truncate" title="<?= htmlspecialchars($rawName) ?>">
                                    <?= htmlspecialchars($baseName) ?>
                                </h6>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <?php if (!empty($variantRom)): ?>
                                        <span class="variant-tag">
                                            <i class="fa-solid fa-microchip text-primary"></i><?= htmlspecialchars($variantRom) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($variantColor)): ?>
                                        <span class="variant-tag">
                                            <span class="color-dot-swatch" style="background-color: <?= $colorHex ?>;"></span>
                                            <?= htmlspecialchars($variantColor) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge bg-light text-muted border rounded-pill small">x<?= (int)$item['quantity'] ?> máy</span>
                                </div>
                                <small class="text-muted d-block" style="font-size: 0.78rem;">
                                    <i class="fa-solid fa-check text-success me-1"></i>Hàng chính hãng New Seal 100% • 1 đổi 1 trong 30 ngày
                                </small>
                            </div>

                            <!-- Đơn giá & Thành tiền -->
                            <div class="text-end flex-shrink-0">
                                <small class="text-muted d-block">Đơn giá: <?= number_format((float)$item['price'], 0, ',', '.') ?> đ</small>
                                <div class="fs-5 fw-black text-danger">
                                    <?= number_format((float)$item['total_price'], 0, ',', '.') ?> đ
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- TỔNG KẾT CHI PHÍ -->
                <div class="order-cost-summary-box">
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="d-flex flex-wrap gap-2">
                                <!-- Nút mua lại đơn này -->
                                <a href="index.php?page=orders&action=reorder&order_id=<?= $trackedOrder['id'] ?>" class="btn btn-outline-primary rounded-pill px-4 fw-bold small">
                                    <i class="fa-solid fa-cart-plus me-1"></i>Mua lại đơn hàng này
                                </a>

                                <!-- Nút hủy đơn (chỉ khi Chờ xử lý) -->
                                <?php if ($isPending): ?>
                                    <button type="button" class="btn btn-outline-danger rounded-pill px-4 fw-bold small" 
                                            data-bs-toggle="modal" data-bs-target="#cancelOrderModal" 
                                            data-order-id="<?= $trackedOrder['id'] ?>"
                                            data-order-phone="<?= htmlspecialchars($trackedOrder['phone']) ?>">
                                        <i class="fa-solid fa-ban me-1"></i>Hủy đơn hàng
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6 text-md-end">
                            <div class="d-flex justify-content-between justify-content-md-end gap-md-4 align-items-center mb-1">
                                <span class="text-muted small">Phí vận chuyển:</span>
                                <span class="text-success fw-bold small">MIỄN PHÍ</span>
                            </div>
                            <div class="d-flex justify-content-between justify-content-md-end gap-md-4 align-items-center">
                                <span class="fw-bold text-dark fs-6">Tổng Thanh Toán:</span>
                                <span class="display-6 fw-black text-danger" style="font-size: 1.85rem;">
                                    <?= number_format((float)$trackedOrder['total_money'], 0, ',', '.') ?> đ
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>


        <!-- ===================================================================== -->
        <!-- VIEW 2: KHUNG TRA CỨU ĐƠN HÀNG GỌN GÀNG (LOOKUP) -->
        <!-- ===================================================================== -->
        <div class="compact-lookup-box">
            <div class="row align-items-center">
                <div class="col-lg-4 mb-3 mb-lg-0">
                    <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-truck-fast text-primary"></i>
                        <span>Tra Cứu Bằng Mã Vận Đơn</span>
                    </h5>
                    <p class="text-muted small mb-0">Nhập mã đơn #VP-xx và số điện thoại để kiểm tra tiến trình giao hàng</p>
                </div>

                <div class="col-lg-8">
                    <form action="index.php" method="GET" class="row g-2 align-items-center">
                        <input type="hidden" name="page" value="orders">

                        <div class="col-12 col-sm-5">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted rounded-start-pill ps-3">
                                    <i class="fa-solid fa-receipt"></i>
                                </span>
                                <input type="text" name="order_code" class="form-control bg-light border-start-0 rounded-end-pill py-2 small" 
                                       placeholder="Mã đơn (VD: #VP-1)" 
                                       value="<?= isset($_GET['order_code']) ? htmlspecialchars($_GET['order_code']) : '' ?>" required>
                            </div>
                        </div>

                        <div class="col-12 col-sm-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted rounded-start-pill ps-3">
                                    <i class="fa-solid fa-mobile-screen-button"></i>
                                </span>
                                <input type="tel" name="phone" class="form-control bg-light border-start-0 rounded-end-pill py-2 small" 
                                       placeholder="<?= $currentUser ? 'Số điện thoại' : 'SĐT nhận hàng *' ?>" 
                                       value="<?= isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ($currentUser['phone'] ?? '') ?>" 
                                       <?= $currentUser ? '' : 'required' ?>>
                            </div>
                        </div>

                        <div class="col-12 col-sm-3">
                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span>Tra Cứu</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!-- ===================================================================== -->
        <!-- VIEW 3: DANH SÁCH "ĐƠN HÀNG CỦA TÔI" (CHO TÀI KHOẢN ĐÃ ĐĂNG NHẬP) -->
        <!-- ===================================================================== -->
        <?php if ($currentUser): ?>
            <div id="myOrdersListSection" class="mt-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h4 class="fw-black text-dark mb-1 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-boxes-packing text-primary"></i>
                            <span>Đơn Hàng Của Tôi</span>
                        </h4>
                        <p class="text-secondary small mb-0">Quản lý và kiểm tra lịch sử đặt mua smartphone của bạn tại V-Phone</p>
                    </div>

                    <!-- Ô tìm kiếm đơn của tôi -->
                    <form action="index.php" method="GET" class="d-flex gap-2">
                        <input type="hidden" name="page" value="orders">
                        <?php if ($activeTab !== 'all'): ?>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($activeTab) ?>">
                        <?php endif; ?>
                        <div class="input-group input-group-sm" style="width: 260px;">
                            <input type="text" name="q" class="form-control rounded-start-pill ps-3 bg-white" placeholder="Tìm tên máy, mã #VP-..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                            <button class="btn btn-primary rounded-end-pill px-3" type="submit">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TABS LỌC TRẠNG THÁI HIỆN ĐẠI -->
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="index.php?page=orders&status=all" class="btn btn-sm rounded-pill fw-bold px-3 py-2 <?= $activeTab === 'all' ? 'btn-primary shadow-sm' : 'btn-white bg-white border text-secondary' ?>">
                        Tất cả <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $statusCounts['all'] ?></span>
                    </a>
                    <a href="index.php?page=orders&status=Chờ xử lý" class="btn btn-sm rounded-pill fw-bold px-3 py-2 <?= $activeTab === 'Chờ xử lý' ? 'btn-warning text-dark shadow-sm' : 'btn-white bg-white border text-secondary' ?>">
                        ⏳ Chờ xử lý <span class="badge bg-dark text-white ms-1 rounded-pill"><?= $statusCounts['Chờ xử lý'] ?></span>
                    </a>
                    <a href="index.php?page=orders&status=Đang giao hàng" class="btn btn-sm rounded-pill fw-bold px-3 py-2 <?= $activeTab === 'Đang giao hàng' ? 'btn-info text-white shadow-sm' : 'btn-white bg-white border text-secondary' ?>">
                        🚚 Đang giao <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $statusCounts['Đang giao hàng'] ?></span>
                    </a>
                    <a href="index.php?page=orders&status=Đã giao thành công" class="btn btn-sm rounded-pill fw-bold px-3 py-2 <?= $activeTab === 'Đã giao thành công' ? 'btn-success shadow-sm' : 'btn-white bg-white border text-secondary' ?>">
                        ✅ Đã giao <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $statusCounts['Đã giao thành công'] ?></span>
                    </a>
                    <a href="index.php?page=orders&status=Đã hủy" class="btn btn-sm rounded-pill fw-bold px-3 py-2 <?= $activeTab === 'Đã hủy' ? 'btn-danger shadow-sm' : 'btn-white bg-white border text-secondary' ?>">
                        ❌ Đã hủy <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $statusCounts['Đã hủy'] ?></span>
                    </a>
                </div>

                <!-- DANH SÁCH THẺ ĐƠN HÀNG CỦA TÔI -->
                <?php if (empty($userOrders)): ?>
                    <div class="text-center py-5 bg-white rounded-4 border shadow-sm p-4">
                        <div style="width: 76px; height: 76px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                            <i class="fa-solid fa-inbox text-secondary fs-2 opacity-50"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Chưa Có Đơn Hàng Nào</h5>
                        <p class="text-muted small mb-3">Bạn chưa có đơn hàng nào trong mục này.</p>
                        <a href="index.php" class="btn btn-primary rounded-pill px-4 fw-bold py-2 shadow-sm">
                            <i class="fa-solid fa-store me-2"></i>Khám phá điện thoại ngay
                        </a>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($userOrders as $ord): ?>
                            <?php 
                            $oId = (int)$ord['id'];
                            $details = $userOrderDetails[$oId] ?? [];
                            $isPendingOrd = ($ord['status'] === 'Chờ xử lý');
                            $isShippingOrd = ($ord['status'] === 'Đang giao hàng');
                            $isDeliveredOrd = ($ord['status'] === 'Đã giao thành công');
                            $isCancelledOrd = ($ord['status'] === 'Đã hủy');
                            ?>
                            <div class="order-history-card">
                                <!-- Header đơn hàng -->
                                <div class="order-history-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fs-5 fw-black text-primary">#VP-<?= $oId ?></span>
                                        <span class="text-muted small">
                                            <i class="fa-regular fa-calendar me-1"></i><?= date('d/m/Y - H:i', strtotime($ord['created_at'])) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <?php if ($isPendingOrd): ?>
                                            <span class="status-pill status-pill-pending py-1 px-3 small">
                                                <span class="pulse-dot"></span>Chờ xử lý
                                            </span>
                                        <?php elseif ($isShippingOrd): ?>
                                            <span class="status-pill status-pill-shipping py-1 px-3 small">
                                                <span class="pulse-dot"></span>Đang giao
                                            </span>
                                        <?php elseif ($isDeliveredOrd): ?>
                                            <span class="status-pill status-pill-delivered py-1 px-3 small">
                                                <span class="pulse-dot"></span>Đã giao
                                            </span>
                                        <?php elseif ($isCancelledOrd): ?>
                                            <span class="status-pill status-pill-cancelled py-1 px-3 small">
                                                <span class="pulse-dot"></span>Đã hủy
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-3 py-1"><?= htmlspecialchars($ord['status']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Body: Danh sách sản phẩm kèm ảnh máy thực tế -->
                                <div class="order-history-card-body">
                                    <?php foreach ($details as $d): ?>
                                        <?php
                                        $rawName = $d['product_name'];
                                        $baseName = preg_replace('/\s*\(.*$/u', '', $rawName);
                                        $variantRom = '';
                                        if (preg_match('/\((\d+\s*GB|\d+\s*TB)/i', $rawName, $m)) {
                                            $variantRom = trim($m[1]);
                                        }
                                        $variantColor = '';
                                        if (preg_match('/Màu:\s*([^)]+)/u', $rawName, $m)) {
                                            $variantColor = trim($m[1]);
                                        }
                                        $colorHex = !empty($variantColor) ? getColorHexValue($variantColor) : '#64748b';
                                        $imgUrl = !empty($d['product_image']) ? $d['product_image'] : ((isset($orderModel)) ? $orderModel->resolveDetailImage($d) : 'assets/images/products/iphone-17-pro.png');
                                        ?>

                                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom-dashed">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="prod-thumb-container" style="width: 64px; height: 64px;">
                                                    <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($baseName) ?>" loading="lazy">
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark small mb-1"><?= htmlspecialchars($baseName) ?></div>
                                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                                        <?php if (!empty($variantRom)): ?>
                                                            <span class="variant-tag" style="font-size:0.72rem; padding: 2px 8px;">
                                                                <?= htmlspecialchars($variantRom) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($variantColor)): ?>
                                                            <span class="variant-tag" style="font-size:0.72rem; padding: 2px 8px;">
                                                                <span class="color-dot-swatch" style="background-color: <?= $colorHex ?>; width:8px; height:8px;"></span>
                                                                <?= htmlspecialchars($variantColor) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <span class="badge bg-light text-muted border rounded-pill" style="font-size: 0.72rem;">x<?= (int)$d['quantity'] ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold text-dark small"><?= number_format((float)$d['total_price'], 0, ',', '.') ?> đ</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Footer: Tổng tiền & Thao tác -->
                                <div class="order-history-card-footer d-flex flex-wrap justify-content-between align-items-center gap-3">
                                    <div>
                                        <small class="text-muted d-block" style="font-size: 0.78rem;">Tổng thanh toán:</small>
                                        <div class="fs-5 fw-black text-danger">
                                            <?= number_format((float)$ord['total_money'], 0, ',', '.') ?> đ
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2">
                                        <!-- Theo dõi chi tiết -->
                                        <a href="index.php?page=orders&order_code=<?= $oId ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                                            <i class="fa-solid fa-truck-ramp-box me-1"></i>Theo dõi chi tiết
                                        </a>

                                        <!-- Mua lại -->
                                        <a href="index.php?page=orders&action=reorder&order_id=<?= $oId ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold">
                                            <i class="fa-solid fa-cart-plus me-1"></i>Mua lại
                                        </a>

                                        <!-- Hủy đơn nếu Chờ xử lý -->
                                        <?php if ($isPendingOrd): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" 
                                                    data-bs-toggle="modal" data-bs-target="#cancelOrderModal" 
                                                    data-order-id="<?= $oId ?>" 
                                                    data-order-phone="<?= htmlspecialchars($ord['phone']) ?>">
                                                <i class="fa-solid fa-ban me-1"></i>Hủy đơn
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- GỢI Ý ĐĂNG NHẬP CHO KHÁCH CHƯA ĐĂNG NHẬP -->
            <div class="bg-white rounded-4 border p-4 p-md-5 text-center shadow-sm mb-5">
                <div style="width: 76px; height: 76px; border-radius: 50%; background: #e8f3ff; color: #0066cc; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class="fa-solid fa-circle-user fs-1"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2">Đăng Nhập Thành Viên V-Phone</h4>
                <p class="text-secondary small mx-auto mb-4" style="max-width: 480px;">
                    Đăng nhập tài khoản để tự động lưu trữ và theo dõi toàn bộ các đơn hàng đã đặt cùng chế độ bảo hành chính hãng.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#unifiedAuthModal">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Đăng Nhập Ngay
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold py-2">
                        Tiếp Tục Mua Sắm
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL XÁC NHẬN HỦY ĐƠN HÀNG -->
<!-- ========================================================================= -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Xác Nhận Hủy Đơn Hàng</h5>
                        <small class="text-muted" id="cancelModalOrderCodeText">Đơn hàng #VP-</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="index.php?page=orders" method="POST">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="order_id" id="modalCancelOrderId" value="">
                <input type="hidden" name="phone" id="modalCancelPhone" value="">
                <input type="hidden" name="redirect_to_tracking" value="<?= $trackedOrder ? '1' : '0' ?>">

                <div class="modal-body p-4">
                    <p class="small text-secondary mb-3">
                        Bạn có chắc chắn muốn hủy đơn hàng này? Sau khi hủy, hệ thống sẽ tự động hoàn lại tồn kho máy cho các khách hàng khác.
                    </p>

                    <label class="form-label small fw-bold text-dark mb-1">Lý do hủy đơn:</label>
                    <select class="form-select rounded-pill small mb-2" id="selectCancelReason" onchange="document.getElementById('inputCancelReason').value = this.value">
                        <option value="Đổi ý không muốn mua nữa">Đổi ý không muốn mua nữa</option>
                        <option value="Muốn thay đổi màu sắc / phiên bản bộ nhớ khác">Muốn đổi màu sắc / bộ nhớ khác</option>
                        <option value="Tìm thấy giá tốt hơn ở nơi khác">Tìm thấy giá tốt hơn ở nơi khác</option>
                        <option value="Đặt nhầm địa chỉ hoặc số điện thoại nhận máy">Đặt nhầm địa chỉ / SĐT nhận máy</option>
                        <option value="Thời gian giao hàng chưa phù hợp">Thời gian giao hàng chưa phù hợp</option>
                        <option value="Lý do khác">Lý do khác</option>
                    </select>

                    <textarea name="cancel_reason" id="inputCancelReason" class="form-control rounded-4 small" rows="2" placeholder="Ghi chú thêm lý do...">Đổi ý không muốn mua nữa</textarea>
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-3 fw-semibold small" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold small shadow-sm">
                        <i class="fa-solid fa-ban me-1"></i>Xác Nhận Hủy Đơn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cancelModal = document.getElementById('cancelOrderModal');
    if (cancelModal) {
        cancelModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;
            const orderId = button.getAttribute('data-order-id');
            const phone = button.getAttribute('data-order-phone');

            document.getElementById('modalCancelOrderId').value = orderId || '';
            document.getElementById('modalCancelPhone').value = phone || '';
            const codeText = document.getElementById('cancelModalOrderCodeText');
            if (codeText) codeText.textContent = 'Đơn hàng #VP-' + orderId;
        });
    }
});
</script>

<?php require_once 'app/views/includes/footer.php'; ?>
