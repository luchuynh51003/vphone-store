<?php
$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
$trackedOrder = $trackedOrder ?? null;
$trackedDetails = $trackedDetails ?? [];
$trackedNotification = $trackedNotification ?? null;
$userOrders = $userOrders ?? [];
$userOrderDetails = $userOrderDetails ?? [];
$userCancelledNotifs = $userCancelledNotifs ?? [];
$statusCounts = $statusCounts ?? ['all' => 0, 'Chờ xử lý' => 0, 'Đang xử lý' => 0, 'Đang giao hàng' => 0, 'Đã giao thành công' => 0, 'Đã hủy' => 0];
$activeTab = $activeTab ?? 'all';
$orderMessage = $orderMessage ?? '';
$orderError = $orderError ?? '';
$pageTitle = $trackedOrder ? 'Theo Dõi Đơn Hàng #VP-' . $trackedOrder['id'] . ' - V-Phone' : ($trackedNotification ? 'Thông Báo Hủy Đơn #VP-' . $trackedNotification['order_id'] . ' - V-Phone' : 'Theo Dõi & Lịch Sử Đơn Hàng - V-Phone');

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
require_once 'app/views/includes/functions.php';
?>

<style>
/* ==========================================================================
   ORDER TRACKING & HISTORY - MODERN SPACIOUS FLAGSHIP STYLING
   ========================================================================== */
.order-tracking-page {
    padding-top: 24px;
    padding-bottom: 80px;
    min-height: 85vh;
}

/* Header Tracking Card */
.tracking-header-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 24px;
    padding: 26px 32px;
    box-shadow: 0 4px 24px rgba(15, 23, 42, 0.04);
    margin-bottom: 28px;
}

/* Stepper Box */
.tracking-stepper-box {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 24px;
    padding: 32px 32px 26px;
    box-shadow: 0 4px 24px rgba(15, 23, 42, 0.04);
    margin-bottom: 28px;
}

.timeline-track-wrap {
    position: relative;
    padding: 24px 0 12px;
}
.timeline-track-line {
    position: absolute;
    top: 50px;
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
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    transition: all 0.3s ease;
}
.timeline-step.completed .step-icon-bubble {
    background: #10b981;
    color: #ffffff;
    box-shadow: 0 6px 18px rgba(16, 185, 129, 0.35);
}
.timeline-step.active .step-icon-bubble {
    background: #0066cc;
    color: #ffffff;
    box-shadow: 0 0 0 6px rgba(0, 102, 204, 0.18), 0 8px 22px rgba(0, 102, 204, 0.35);
    transform: scale(1.08);
}
.timeline-step.cancelled .step-icon-bubble {
    background: #ef4444;
    color: #ffffff;
    box-shadow: 0 6px 18px rgba(239, 68, 68, 0.35);
}
.step-title-text {
    font-weight: 700;
    font-size: 0.95rem;
    margin-top: 14px;
    color: #1e293b;
}
.timeline-step.completed .step-title-text { color: #059669; }
.timeline-step.active .step-title-text { color: #0066cc; font-weight: 800; }
.step-sub-text {
    font-size: 0.78rem;
    color: #64748b;
    margin-top: 3px;
    line-height: 1.4;
}

/* Info Cards (Address & Payment) */
.info-card-box {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 22px;
    padding: 24px 28px;
    height: 100%;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.info-card-box:hover {
    border-color: #bfdbfe;
    box-shadow: 0 8px 24px rgba(0, 102, 204, 0.07);
}
.info-header-tag {
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Tracked Products Box */
.order-products-box {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 24px;
    box-shadow: 0 4px 24px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    margin-bottom: 32px;
}
.product-row-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px 28px;
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.2s ease;
}
.product-row-item:last-child {
    border-bottom: none;
}
.product-row-item:hover {
    background-color: #fbfdff;
}

.prod-thumb-container {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
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
.product-row-item:hover .prod-thumb-container img,
.order-product-item:hover .prod-thumb-container img {
    transform: scale(1.08);
}

.variant-tag, .variant-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f1f5f9;
    color: #334155;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 9999px;
    border: 1px solid #e2e8f0;
}
.color-dot-swatch {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 1px solid rgba(0,0,0,0.18);
    display: inline-block;
    flex-shrink: 0;
}

.order-cost-summary-box {
    background: #f8fafc;
    border-top: 1.5px solid #e2e8f0;
    padding: 24px 32px;
}

/* Compact Lookup Box */
.compact-lookup-box {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 24px;
    padding: 26px 32px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
    margin-bottom: 36px;
    transition: border-color 0.2s ease;
}
.compact-lookup-box:hover {
    border-color: #cbd5e1;
}
.lookup-icon-circle {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    color: #0066cc;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

/* Segmented Filter Navigation Bar */
.order-filter-nav-wrap {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 20px;
    padding: 8px;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
    display: flex;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    white-space: nowrap;
    scrollbar-width: none;
    margin-bottom: 32px;
}
.order-filter-nav-wrap::-webkit-scrollbar {
    display: none;
}
.order-tab-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 14px;
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    color: #64748b;
    background: transparent;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    flex-shrink: 0;
}
.order-tab-item:hover {
    color: #0f172a;
    background: #f1f5f9;
    transform: translateY(-1px);
}
.order-tab-item.active-all {
    background: linear-gradient(135deg, #0066cc 0%, #0284c7 100%);
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(0, 102, 204, 0.28);
}
.order-tab-item.active-pending {
    background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(217, 119, 6, 0.28);
}
.order-tab-item.active-processing {
    background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.28);
}
.order-tab-item.active-shipping {
    background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.28);
}
.order-tab-item.active-delivered {
    background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(22, 163, 74, 0.28);
}
.order-tab-item.active-cancelled {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.28);
}
.order-tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 22px;
    padding: 0 7px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 800;
}
.order-tab-item[class*="active-"] .order-tab-count {
    background: rgba(255, 255, 255, 0.28);
    color: #ffffff;
}
.order-tab-item:not([class*="active-"]) .order-tab-count {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

/* Order History Cards */
.order-history-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 24px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
    margin-bottom: 28px;
    overflow: hidden;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.order-history-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 12px 32px rgba(0, 102, 204, 0.09);
    transform: translateY(-3px);
}
.order-history-card-header {
    background: #fcfdfe;
    border-bottom: 1.5px solid #f1f5f9;
    padding: 20px 28px;
}
.order-code-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eff6ff;
    color: #0066cc;
    font-weight: 800;
    font-size: 1.05rem;
    padding: 6px 14px;
    border-radius: 12px;
    border: 1.5px solid #bfdbfe;
    letter-spacing: 0.3px;
}
.order-history-card-body {
    padding: 24px 28px;
}
.order-product-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 16px 0;
    border-bottom: 1px dashed #e2e8f0;
    transition: background-color 0.15s ease;
}
.order-product-item:first-child {
    padding-top: 0;
}
.order-product-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.order-history-card-footer {
    background: #fcfdfe;
    border-top: 1.5px solid #f1f5f9;
    padding: 20px 28px;
}

.order-history-list-wrap {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

@media (max-width: 768px) {
    .order-tracking-page { padding-top: 16px; padding-bottom: 48px; }
    .tracking-header-card { padding: 20px; border-radius: 18px; margin-bottom: 20px; }
    .tracking-stepper-box { padding: 22px 16px; border-radius: 18px; margin-bottom: 20px; }
    .timeline-track-line { top: 40px; left: 10%; right: 10%; }
    .step-icon-bubble { width: 44px; height: 44px; font-size: 1rem; }
    .step-title-text { font-size: 0.8rem; margin-top: 10px; }
    .step-sub-text { display: none; }
    .prod-thumb-container { width: 68px; height: 68px; }
    .compact-lookup-box { padding: 20px; border-radius: 18px; margin-bottom: 24px; }
    .order-filter-nav-wrap { border-radius: 16px; padding: 6px; margin-bottom: 24px; }
    .order-tab-item { padding: 8px 14px; font-size: 0.82rem; }
    .order-history-card { border-radius: 18px; margin-bottom: 20px; }
    .order-history-card-header { padding: 16px 18px; }
    .order-history-card-body { padding: 18px; }
    .order-history-card-footer { padding: 16px 18px; }
    .order-product-item { gap: 14px; }
}

/* Dark Mode Enhancements for Order Tracking & History */
[data-bs-theme="dark"] .order-tracking-page {
    background-color: #0b1329 !important;
}
[data-bs-theme="dark"] .compact-lookup-box {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4) !important;
}
[data-bs-theme="dark"] .lookup-icon-circle {
    background: rgba(2, 132, 199, 0.2) !important;
    color: #38bdf8 !important;
}
[data-bs-theme="dark"] .order-filter-nav-wrap {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3) !important;
}
[data-bs-theme="dark"] .order-tab-item {
    color: #cbd5e1 !important;
}
[data-bs-theme="dark"] .order-tab-item:hover {
    color: #ffffff !important;
    background: #1e2f50 !important;
}
[data-bs-theme="dark"] .order-tab-item:not([class*="active-"]) .order-tab-count {
    background: #0e172a !important;
    color: #94a3b8 !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .order-history-card {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35) !important;
}
[data-bs-theme="dark"] .order-history-card:hover {
    border-color: rgba(56, 189, 248, 0.4) !important;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.5) !important;
}
[data-bs-theme="dark"] .order-history-card-header {
    background: #0e172a !important;
    border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .order-code-badge {
    background: rgba(56, 189, 248, 0.15) !important;
    color: #38bdf8 !important;
    border-color: rgba(56, 189, 248, 0.3) !important;
}
[data-bs-theme="dark"] .order-product-item {
    border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .order-history-card-footer {
    background: #0e172a !important;
    border-top-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .tracking-header-card {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35) !important;
}
[data-bs-theme="dark"] .tracking-stepper-box {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35) !important;
}
[data-bs-theme="dark"] .timeline-track-line {
    background: rgba(255, 255, 255, 0.1) !important;
}
[data-bs-theme="dark"] .step-icon-bubble {
    background: #0e172a !important;
    color: #94a3b8 !important;
    border-color: #131f37 !important;
}
[data-bs-theme="dark"] .step-title-text {
    color: #f1f5f9 !important;
}
[data-bs-theme="dark"] .step-sub-text {
    color: #94a3b8 !important;
}
[data-bs-theme="dark"] .info-card-box {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
}
[data-bs-theme="dark"] .order-products-box {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
}
[data-bs-theme="dark"] .order-products-box .bg-white {
    background: #0e172a !important;
    border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .product-row-item {
    border-bottom-color: rgba(255, 255, 255, 0.06) !important;
}
[data-bs-theme="dark"] .product-row-item:hover {
    background-color: #1a2a4a !important;
}
[data-bs-theme="dark"] .prod-thumb-container {
    background: #0e172a !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .prod-thumb-container img {
    filter: drop-shadow(0 4px 10px rgba(0,0,0,0.5));
}
[data-bs-theme="dark"] .variant-tag,
[data-bs-theme="dark"] .variant-chip {
    background: #0e172a !important;
    color: #cbd5e1 !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .order-cost-summary-box {
    background: #0e172a !important;
    border-top-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] div[style*="background: #ffffff; border-color: #fee2e2"] {
    background: rgba(239, 68, 68, 0.12) !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
}
[data-bs-theme="dark"] div[style*="background: #fff5f5"] {
    background: rgba(239, 68, 68, 0.18) !important;
    border-color: rgba(239, 68, 68, 0.35) !important;
}

</style>

<div class="order-tracking-page">
    <div class="container">

        
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted"><i class="fa-solid fa-house me-1"></i>Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="index.php?page=orders" class="text-decoration-none text-muted">Theo dõi đơn hàng</a></li>
                <?php if ($trackedOrder): ?>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Đơn hàng #VP-<?= $trackedOrder['id'] ?></li>
                <?php elseif ($trackedNotification): ?>
                    <li class="breadcrumb-item active text-danger fw-bold" aria-current="page">Đơn hàng #VP-<?= (int)$trackedNotification['order_id'] ?> (Đã hủy)</li>
                <?php else: ?>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Lịch sử đặt hàng</li>
                <?php endif; ?>
            </ol>
        </nav>

        
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

        <?php if ($trackedNotification): ?>
            <!-- THÔNG BÁO ĐƠN HÀNG ĐÃ BỊ HỦY / XÓA BỞI CỬA HÀNG -->
            <div class="tracking-header-card border-danger border-opacity-25 mb-4 shadow-sm" style="background: linear-gradient(180deg, #fff5f5 0%, #ffffff 100%);">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-3 border-bottom border-danger-subtle">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <a href="index.php?page=orders" class="btn btn-sm btn-light border rounded-pill px-3 py-1 text-secondary fw-semibold">
                                <i class="fa-solid fa-arrow-left me-1"></i>Danh sách đơn hàng
                            </a>
                            <span class="badge bg-danger rounded-pill px-3 py-1 fw-bold">
                                <i class="fa-solid fa-ban me-1"></i>Đơn hàng đã hủy bởi cửa hàng
                            </span>
                        </div>
                        <h3 class="fw-black text-danger mb-1 d-flex align-items-center gap-2">
                            <span>Thông Báo Hủy Đơn Hàng</span>
                            <span class="text-dark" style="letter-spacing: 0.5px;">#VP-<?= (int)$trackedNotification['order_id'] ?></span>
                        </h3>
                        <div class="text-muted small">
                            <i class="fa-regular fa-clock me-1 text-danger"></i>Thời gian xử lý: <strong><?= date('d/m/Y - H:i', strtotime($trackedNotification['created_at'])) ?></strong>
                            <?php if (!empty($trackedNotification['phone'])): ?>
                                <span class="mx-2">•</span>
                                <i class="fa-solid fa-phone me-1 text-muted"></i>SĐT nhận tin: <span class="fw-bold text-dark"><?= htmlspecialchars($trackedNotification['phone']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3.5 py-2 fs-6 shadow-sm fw-bold">
                            <i class="fa-solid fa-circle-xmark me-1"></i>Đã hủy & thu hồi đơn
                        </span>
                    </div>
                </div>

                <div class="rounded-4 p-4 mb-3 border shadow-sm" style="background: #ffffff; border-color: #fee2e2 !important;">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 mt-1" style="width: 44px; height: 44px; background: #fee2e2; color: #dc2626; border: 1.5px solid #fecaca;">
                            <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <span class="badge bg-danger text-white rounded-pill px-3 py-1 fw-bold shadow-sm" style="font-size: 0.78rem; letter-spacing: 0.3px;">
                                    <i class="fa-solid fa-ban me-1"></i>LÝ DO HỦY ĐƠN HÀNG
                                </span>
                                <span class="text-secondary small fw-medium">Thông báo chính thức từ V-Phone Store</span>
                            </div>
                            
                            <?php if (!empty($trackedNotification['cancel_reason'])): ?>
                                <div class="p-3 rounded-3 mt-2" style="background: #fff5f5; border: 1.5px solid #fecaca;">
                                    <div class="fw-bold" style="color: #991b1b; font-size: 1rem; line-height: 1.55;">
                                        <?= htmlspecialchars($trackedNotification['cancel_reason']) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php
                        $trackedCleanMsg = $trackedNotification['message'];
                        if (!empty($trackedNotification['cancel_reason'])) {
                            $trackedCleanMsg = trim(preg_replace('/Lý do:\s*.*?\n/iu', '', $trackedCleanMsg));
                        }
                    ?>
                    <div class="p-3 rounded-3 bg-light border text-secondary small" style="white-space: pre-line; line-height: 1.6;">
                        <?= htmlspecialchars($trackedCleanMsg) ?>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 bg-light rounded-4">
                    <div class="small text-muted d-flex align-items-center gap-2">
                        <i class="fa-solid fa-headset text-primary fs-5"></i>
                        <span>Cần trợ giúp hoặc tư vấn sản phẩm tương đương? Gọi tổng đài miễn phí: <strong class="text-danger">1800 6868</strong></span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="index.php" class="btn btn-primary rounded-pill px-4 fw-bold">
                            <i class="fa-solid fa-cart-shopping me-1"></i>Xem sản phẩm khác
                        </a>
                        <a href="index.php?page=orders" class="btn btn-outline-secondary rounded-pill px-3 fw-semibold">
                            <i class="fa-solid fa-boxes-packing me-1"></i>Đơn hàng của tôi
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($trackedOrder): ?>
            <?php
            $st = $trackedOrder['status'];
            $isCancelled = ($st === 'Đã hủy');
            $isPending = ($st === 'Chờ xử lý');
            $isProcessing = ($st === 'Đang xử lý');
            $isWaitingPayment = ($st === 'Chờ thanh toán');
            $isShipping = ($st === 'Đang giao hàng');
            $isDelivered = ($st === 'Đã giao thành công');

            $progressPct = 15;
            if ($isProcessing) $progressPct = 42;
            elseif ($isShipping) $progressPct = 72;
            elseif ($isDelivered) $progressPct = 100;
            elseif ($isCancelled) $progressPct = 100;

            $trackingCode = 'VPX-' . date('Y', strtotime($trackedOrder['created_at'])) . '-' . str_pad((string)$trackedOrder['id'], 6, '0', STR_PAD_LEFT);
            ?>

            
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
                        <?= renderOrderStatusPill($st, true, 'px-3.5 py-2 fs-6 shadow-sm') ?>

                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold small shadow-sm" onclick="window.print()" title="In thông tin đơn hàng">
                            <i class="fa-solid fa-print me-1"></i>In đơn
                        </button>
                    </div>
                </div>
            </div>

            
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
                            
                            <div class="timeline-step completed">
                                <div class="step-icon-bubble">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                                <div class="step-title-text">Đặt Hàng Thành Công</div>
                                <div class="step-sub-text">Hệ thống đã tiếp nhận đơn #VP-<?= $trackedOrder['id'] ?></div>
                            </div>

                            
                            <div class="timeline-step <?= ($isShipping || $isDelivered) ? 'completed' : (($isPending || $isProcessing || $isWaitingPayment) ? 'active' : '') ?>">
                                <div class="step-icon-bubble">
                                    <?php if ($isShipping || $isDelivered): ?>
                                        <i class="fa-solid fa-check"></i>
                                    <?php elseif ($isProcessing): ?>
                                        <i class="fa-solid fa-boxes-packing"></i>
                                    <?php elseif ($isWaitingPayment): ?>
                                        <i class="fa-solid fa-qrcode"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-title-text"><?= $isProcessing ? 'Đang Đóng Gói Máy' : 'Kho Chuẩn Bị Máy' ?></div>
                                <div class="step-sub-text">Kiểm tra tem seal, IMEI & đóng hộp</div>
                            </div>

                            
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

                            
                            <div class="timeline-step <?= $isDelivered ? 'completed' : '' ?>">
                                <div class="step-icon-bubble">
                                    <i class="fa-solid fa-circle-check"></i>
                                </div>
                                <div class="step-title-text">Giao Hàng Thành Công</div>
                                <div class="step-sub-text">Đã giao tận tay & kích hoạt bảo hành</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            
            <div class="row g-4 mb-4">
                
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

                
                <div class="col-lg-6">
                    <div class="info-card-box">
                        <div class="info-header-tag text-success">
                            <i class="fa-solid fa-credit-card"></i>
                            <span>Thanh Toán & Vận Chuyển</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom flex-wrap gap-2">
                            <span class="small text-muted">Phương thức thanh toán:</span>
                            <div class="d-flex align-items-center gap-1.5 flex-wrap justify-content-end">
                                <span class="badge bg-light text-dark border rounded-pill px-3 py-1 fw-bold">
                                    <i class="fa-solid fa-wallet me-1 text-primary"></i><?= htmlspecialchars($trackedOrder['payment_method']) ?>
                                </span>
                                <?php 
                                    $pStatus = $trackedOrder['payment_status'] ?? 'Chưa thanh toán';
                                    $isPaid = ($pStatus === 'Đã thanh toán');
                                    $isVietQR = ($trackedOrder['payment_method'] === 'Chuyển khoản QR VietQR');
                                    $isMoMo = ($trackedOrder['payment_method'] === 'Ví điện tử MoMo');
                                ?>
                                <?php if ($isPaid): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-bold">
                                        <i class="fa-solid fa-circle-check me-1"></i>Đã thanh toán
                                    </span>
                                <?php elseif ($isVietQR && $trackedOrder['status'] !== 'Đã hủy'): ?>
                                    <a href="index.php?page=checkout&step=vietqr&order_id=<?= (int)$trackedOrder['id'] ?>" class="btn btn-sm btn-danger rounded-pill px-2.5 py-0.5 fw-bold shadow-sm" style="font-size:0.75rem;">
                                        <i class="fa-solid fa-qrcode me-1"></i>Quét mã thanh toán ngay
                                    </a>
                                <?php elseif ($isMoMo && $trackedOrder['status'] !== 'Đã hủy'): ?>
                                    <a href="index.php?page=checkout&action=pay_momo&order_id=<?= (int)$trackedOrder['id'] ?>" class="btn btn-sm text-white rounded-pill px-2.5 py-1 fw-bold shadow-sm" style="font-size:0.75rem; background: linear-gradient(135deg, #d82d8b 0%, #a50064 100%);">
                                        <i class="fa-solid fa-wallet me-1"></i>Thanh toán MoMo ngay
                                    </a>
                                    <a href="index.php?page=checkout&step=vietqr&order_id=<?= (int)$trackedOrder['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0.5 fw-bold" style="font-size:0.72rem;" title="Chuyển sang quét VietQR">
                                        <i class="fa-solid fa-qrcode me-1"></i>VietQR
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2.5 py-1 small">
                                        <?= htmlspecialchars($pStatus) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($trackedOrder['transaction_code'])): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="small text-muted">Mã giao dịch VietQR:</span>
                                <span class="small fw-mono text-success fw-bold"><?= htmlspecialchars($trackedOrder['transaction_code']) ?></span>
                            </div>
                        <?php endif; ?>
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

            
            <div class="order-products-box">
                <div class="p-3 px-4 bg-white border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-mobile-screen-button text-primary"></i>
                        <span>Sản Phẩm Trong Đơn Hàng</span>
                        <span class="badge bg-light border text-secondary rounded-pill small"><?= count($trackedDetails) ?> sản phẩm</span>
                    </h5>
                    <a href="index.php?page=warranty" target="_blank" class="text-success small fw-semibold text-decoration-none">
                        <i class="fa-solid fa-shield-halved me-1"></i>Bảo hành chính hãng 12 tháng <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size:11px;"></i>
                    </a>
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
                            
                            <div class="prod-thumb-container">
                                <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($baseName) ?>" loading="lazy">
                            </div>

                            
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

                            
                            <div class="text-end flex-shrink-0">
                                <small class="text-muted d-block">Đơn giá: <?= number_format((float)$item['price'], 0, ',', '.') ?> VNĐ</small>
                                <div class="fs-5 fw-black text-danger">
                                    <?= number_format((float)$item['total_price'], 0, ',', '.') ?> VNĐ
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                
                <div class="order-cost-summary-box">
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="d-flex flex-wrap gap-2">
                                
                                <a href="index.php?page=orders&action=reorder&order_id=<?= $trackedOrder['id'] ?>" class="btn btn-outline-primary rounded-pill px-4 fw-bold small">
                                    <i class="fa-solid fa-cart-plus me-1"></i>Mua lại đơn hàng này
                                </a>

                                
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
                                    <?= number_format((float)$trackedOrder['total_money'], 0, ',', '.') ?> VNĐ
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($trackedOrder): ?>
            <div class="text-center my-5 position-relative">
                <hr class="border-secondary opacity-25">
                <span class="position-absolute top-50 start-50 translate-middle bg-white px-4 py-1.5 rounded-pill border text-muted small fw-bold text-uppercase shadow-sm" style="letter-spacing: 0.5px;">
                    <i class="fa-solid fa-magnifying-glass me-1.5 text-primary"></i>Tra Cứu Hoặc Xem Danh Sách Đơn Hàng Khác
                </span>
            </div>
        <?php endif; ?>

        <!-- ================================================================
             HỘP TRA CỨU ĐƠN HÀNG BẰNG MÃ VẬN ĐƠN
             ================================================================ -->
        <div class="compact-lookup-box">
            <div class="row align-items-center gy-3">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="lookup-icon-circle">
                            <i class="fa-solid fa-truck-fast"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Tra Cứu Vận Đơn</h5>
                            <p class="text-secondary small mb-0">Nhập mã đơn #VP-xx & SĐT để theo dõi</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <form action="index.php" method="GET" class="row g-2 align-items-center">
                        <input type="hidden" name="page" value="orders">

                        <div class="col-12 col-sm-5">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted rounded-start-pill ps-3 pe-2">
                                    <i class="fa-solid fa-receipt text-primary"></i>
                                </span>
                                <input type="text" name="order_code" class="form-control bg-light border-start-0 rounded-end-pill py-2.5 small" 
                                       placeholder="Mã đơn (VD: #VP-1)" 
                                       value="<?= isset($_GET['order_code']) ? htmlspecialchars($_GET['order_code']) : '' ?>" required>
                            </div>
                        </div>

                        <div class="col-12 col-sm-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted rounded-start-pill ps-3 pe-2">
                                    <i class="fa-solid fa-mobile-screen-button text-primary"></i>
                                </span>
                                <input type="tel" name="phone" class="form-control bg-light border-start-0 rounded-end-pill py-2.5 small" 
                                       placeholder="<?= $currentUser ? 'Số điện thoại' : 'SĐT nhận hàng *' ?>" 
                                       value="<?= isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ($currentUser['phone'] ?? '') ?>" 
                                       <?= $currentUser ? '' : 'required' ?>>
                            </div>
                        </div>

                        <div class="col-12 col-sm-3">
                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span>Tra Cứu</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================================================================
             DANH SÁCH ĐƠN HÀNG CỦA TÔI (CHO THÀNH VIÊN ĐÃ ĐĂNG NHẬP)
             ================================================================ -->
        <?php if ($currentUser): ?>
            <div id="myOrdersListSection" class="mt-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #0066cc; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                            <i class="fa-solid fa-boxes-packing"></i>
                        </div>
                        <div>
                            <h4 class="fw-black text-dark mb-1">Đơn Hàng Của Tôi</h4>
                            <p class="text-secondary small mb-0">Quản lý và kiểm tra lịch sử đặt mua smartphone chính hãng tại V-Phone</p>
                        </div>
                    </div>

                    <!-- Ô tìm kiếm đơn hàng nhanh -->
                    <form action="index.php" method="GET" class="d-flex gap-2">
                        <input type="hidden" name="page" value="orders">
                        <?php if ($activeTab !== 'all'): ?>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($activeTab) ?>">
                        <?php endif; ?>
                        <div class="input-group" style="width: 290px;">
                            <input type="text" name="q" class="form-control rounded-start-pill ps-3.5 bg-white py-2 small" placeholder="Tìm tên máy, mã #VP-..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                            <button class="btn btn-primary rounded-end-pill px-3.5" type="submit">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- THANH ĐIỀU HƯỚNG BỘ LỌC TRẠNG THÁI (SEGMENTED FILTER BAR) -->
                <div class="order-filter-nav-wrap">
                    <a href="index.php?page=orders&status=all" class="order-tab-item <?= $activeTab === 'all' ? 'active-all' : '' ?>">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Tất cả</span>
                        <span class="order-tab-count"><?= $statusCounts['all'] ?></span>
                    </a>
                    <a href="index.php?page=orders&status=Chờ xử lý" class="order-tab-item <?= $activeTab === 'Chờ xử lý' ? 'active-pending' : '' ?>">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Chờ xử lý</span>
                        <span class="order-tab-count"><?= $statusCounts['Chờ xử lý'] ?></span>
                    </a>
                    <a href="index.php?page=orders&status=Đang xử lý" class="order-tab-item <?= $activeTab === 'Đang xử lý' ? 'active-processing' : '' ?>">
                        <i class="fa-solid fa-boxes-packing"></i>
                        <span>Đang chuẩn bị</span>
                        <span class="order-tab-count"><?= $statusCounts['Đang xử lý'] ?></span>
                    </a>
                    <a href="index.php?page=orders&status=Đang giao hàng" class="order-tab-item <?= $activeTab === 'Đang giao hàng' ? 'active-shipping' : '' ?>">
                        <i class="fa-solid fa-truck-fast"></i>
                        <span>Đang giao</span>
                        <span class="order-tab-count"><?= $statusCounts['Đang giao hàng'] ?></span>
                    </a>
                    <a href="index.php?page=orders&status=Đã giao thành công" class="order-tab-item <?= $activeTab === 'Đã giao thành công' ? 'active-delivered' : '' ?>">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Đã giao</span>
                        <span class="order-tab-count"><?= $statusCounts['Đã giao thành công'] ?></span>
                    </a>
                    <a href="index.php?page=orders&status=Đã hủy" class="order-tab-item <?= $activeTab === 'Đã hủy' ? 'active-cancelled' : '' ?>">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Đã hủy</span>
                        <span class="order-tab-count"><?= $statusCounts['Đã hủy'] ?></span>
                    </a>
                </div>

                <!-- DANH SÁCH THẺ ĐƠN HÀNG -->
                <?php 
                    $showCancelledSection = ($activeTab === 'all' || $activeTab === 'Đã hủy') && !empty($userCancelledNotifs);
                    $hasAnyOrdersToShow = !empty($userOrders) || $showCancelledSection;
                ?>
                <?php if (!$hasAnyOrdersToShow): ?>
                    <div class="text-center py-5 bg-white rounded-4 border shadow-sm p-4">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                            <i class="fa-solid fa-inbox text-secondary fs-2 opacity-50"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Chưa Có Đơn Hàng Nào</h5>
                        <p class="text-muted small mb-3">Bạn chưa có đơn hàng nào trong mục này.</p>
                        <a href="index.php" class="btn btn-primary rounded-pill px-4 fw-bold py-2.5 shadow-sm">
                            <i class="fa-solid fa-store me-2"></i>Khám phá điện thoại ngay
                        </a>
                    </div>
                <?php else: ?>
                    <div class="order-history-list-wrap">
                        <?php if ($showCancelledSection): ?>
                            <!-- DANH SÁCH CÁC ĐƠN HÀNG ĐÃ BỊ HỦY / XÓA BỞI CỬA HÀNG -->
                            <div class="p-3 bg-danger bg-opacity-10 rounded-4 border border-danger border-opacity-25 d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-2 text-danger fw-bold">
                                    <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                                    <span>Đơn hàng đã được cửa hàng xử lý hủy & thu hồi (<?= count($userCancelledNotifs) ?>)</span>
                                </div>
                                <span class="badge bg-danger rounded-pill px-3 py-1 small">Có thông báo lý do</span>
                            </div>
                            <?php foreach ($userCancelledNotifs as $cNotif): ?>
                                <div class="order-history-card border-danger border-opacity-25 shadow-sm" style="background: linear-gradient(180deg, #fffafa 0%, #ffffff 100%);">
                                    <div class="order-history-card-header d-flex flex-wrap justify-content-between align-items-center gap-3" style="background: #fef2f2;">
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <span class="order-code-badge border-danger border-opacity-50 text-danger bg-white">
                                                <i class="fa-solid fa-receipt opacity-75"></i>
                                                <span>#VP-<?= (int)$cNotif['order_id'] ?></span>
                                            </span>
                                            <div class="vr opacity-25 d-none d-sm-block" style="height: 20px;"></div>
                                            <span class="text-secondary small fw-medium">
                                                <i class="fa-regular fa-clock me-1 text-danger"></i>Ngày hủy: <?= date('d/m/Y - H:i', strtotime($cNotif['created_at'])) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="badge bg-danger rounded-pill px-3 py-1.5 fw-bold shadow-sm">
                                                <i class="fa-solid fa-ban me-1"></i>Đã hủy bởi Store
                                            </span>
                                        </div>
                                    </div>
                                    <div class="order-history-card-body p-3.5">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: #fee2e2; color: #ef4444;">
                                                <i class="fa-solid fa-circle-exclamation fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($cNotif['title']) ?></h6>
                                                <?php if (!empty($cNotif['cancel_reason'])): ?>
                                                    <div class="p-2.5 px-3 rounded-3 my-2" style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2);">
                                                        <div class="d-flex align-items-center gap-1.5 text-danger fw-bold small">
                                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                                            <span>Lý do hủy đơn từ cửa hàng:</span>
                                                        </div>
                                                        <div class="text-dark fw-medium mt-1 small" style="line-height: 1.45;">
                                                            <?= htmlspecialchars($cNotif['cancel_reason']) ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php
                                                    $cNotifMsg = $cNotif['message'];
                                                    if (!empty($cNotif['cancel_reason'])) {
                                                        $cNotifMsg = trim(preg_replace('/Lý do:\s*.*?\n/iu', '', $cNotifMsg));
                                                    }
                                                ?>
                                                <p class="text-secondary small mb-0" style="white-space: pre-line; line-height: 1.5;"><?= htmlspecialchars($cNotifMsg) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-history-card-footer d-flex flex-wrap justify-content-between align-items-center gap-3 bg-light">
                                        <span class="small text-muted">
                                            <i class="fa-solid fa-headset me-1 text-primary"></i>Hỗ trợ: 1800 6868
                                        </span>
                                        <div class="d-flex gap-2">
                                            <a href="index.php?page=orders&order_code=<?= (int)$cNotif['order_id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold">
                                                <i class="fa-solid fa-circle-info me-1"></i>Xem chi tiết hủy
                                            </a>
                                            <a href="index.php" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                                                <i class="fa-solid fa-cart-shopping me-1"></i>Đặt hàng khác
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php foreach ($userOrders as $ord): ?>
                            <?php 
                            $oId = (int)$ord['id'];
                            $details = $userOrderDetails[$oId] ?? [];
                            $isPendingOrd = ($ord['status'] === 'Chờ xử lý');
                            ?>
                            <div class="order-history-card">
                                <!-- HEADER THẺ ĐƠN HÀNG -->
                                <div class="order-history-card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <span class="order-code-badge">
                                            <i class="fa-solid fa-receipt opacity-75"></i>
                                            <span>#VP-<?= $oId ?></span>
                                        </span>
                                        <div class="vr opacity-25 d-none d-sm-block" style="height: 20px;"></div>
                                        <span class="text-secondary small fw-medium">
                                            <i class="fa-regular fa-clock me-1 text-primary"></i><?= date('d/m/Y - H:i', strtotime($ord['created_at'])) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <?= renderOrderStatusPill($ord['status'], true, 'py-1.5 px-3.5') ?>
                                    </div>
                                </div>

                                <!-- BODY THẺ ĐƠN HÀNG: SẢN PHẨM -->
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

                                        <div class="order-product-item">
                                            <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
                                                <div class="prod-thumb-container">
                                                    <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($baseName) ?>" loading="lazy">
                                                </div>
                                                <div class="flex-grow-1 min-w-0">
                                                    <h6 class="fw-bold text-dark mb-1.5 fs-6 text-truncate" title="<?= htmlspecialchars($rawName) ?>">
                                                        <?= htmlspecialchars($baseName) ?>
                                                    </h6>
                                                    <div class="d-flex flex-wrap gap-1.5 align-items-center mb-1">
                                                        <?php if (!empty($variantRom)): ?>
                                                            <span class="variant-chip">
                                                                <i class="fa-solid fa-microchip text-primary"></i><?= htmlspecialchars($variantRom) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($variantColor)): ?>
                                                            <span class="variant-chip">
                                                                <span class="color-dot-swatch" style="background-color: <?= $colorHex ?>;"></span>
                                                                <?= htmlspecialchars($variantColor) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small">x<?= (int)$d['quantity'] ?> máy</span>
                                                    </div>
                                                    <small class="text-muted d-block" style="font-size: 0.78rem;">
                                                        <i class="fa-solid fa-shield-halved text-success me-1"></i>Hàng chính hãng New Seal 100% • Bảo hành 12 tháng
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="text-end flex-shrink-0 ps-3">
                                                <small class="text-muted d-block" style="font-size: 0.78rem;">Đơn giá: <?= number_format((float)$d['price'], 0, ',', '.') ?> VNĐ</small>
                                                <div class="fs-5 fw-black text-danger mt-0.5">
                                                    <?= number_format((float)$d['total_price'], 0, ',', '.') ?> VNĐ
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- FOOTER THẺ ĐƠN HÀNG: TỔNG TIỀN VÀ THAO TÁC -->
                                <div class="order-history-card-footer d-flex flex-wrap justify-content-between align-items-center gap-3">
                                    <div class="d-flex align-items-center flex-wrap gap-2.5">
                                        <span class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Tổng thanh toán:</span>
                                        <span class="fs-4 fw-black text-danger" style="letter-spacing: -0.5px;">
                                            <?= number_format((float)$ord['total_money'], 0, ',', '.') ?> VNĐ
                                        </span>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2.5 py-1 small fw-bold">
                                            <i class="fa-solid fa-truck-fast me-1"></i>Miễn phí vận chuyển
                                        </span>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <?php if ($ord['payment_method'] === 'Chuyển khoản QR VietQR' && ($ord['payment_status'] ?? '') !== 'Đã thanh toán' && $ord['status'] !== 'Đã hủy'): ?>
                                            <a href="index.php?page=checkout&step=vietqr&order_id=<?= $oId ?>" class="btn btn-sm btn-danger rounded-pill px-3.5 py-2 fw-bold shadow-sm">
                                                <i class="fa-solid fa-qrcode me-1.5"></i>Thanh toán VietQR
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($ord['payment_method'] === 'Ví điện tử MoMo' && ($ord['payment_status'] ?? '') !== 'Đã thanh toán' && $ord['status'] !== 'Đã hủy'): ?>
                                            <a href="index.php?page=checkout&action=pay_momo&order_id=<?= $oId ?>" class="btn btn-sm text-white rounded-pill px-3.5 py-2 fw-bold shadow-sm" style="background: linear-gradient(135deg, #d82d8b 0%, #a50064 100%);">
                                                <i class="fa-solid fa-wallet me-1.5"></i>Thanh toán MoMo
                                            </a>
                                        <?php endif; ?>
                                        <a href="index.php?page=orders&order_code=<?= $oId ?>" class="btn btn-sm btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm">
                                            <i class="fa-solid fa-route me-1.5"></i>Theo dõi chi tiết
                                        </a>

                                        <!-- Nút Mua Lại -->
                                        <a href="index.php?page=orders&action=reorder&order_id=<?= $oId ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3.5 py-2 fw-semibold">
                                            <i class="fa-solid fa-cart-plus me-1.5"></i>Mua lại
                                        </a>

                                        <!-- Nút Hủy Đơn nếu còn Chờ Xử Lý -->
                                        <?php if ($isPendingOrd): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3.5 py-2 fw-bold" 
                                                    data-bs-toggle="modal" data-bs-target="#cancelOrderModal" 
                                                    data-order-id="<?= $oId ?>" 
                                                    data-order-phone="<?= htmlspecialchars($ord['phone']) ?>">
                                                <i class="fa-solid fa-ban me-1.5"></i>Hủy đơn
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
