<?php
$pageTitle = 'Chính Sách Bảo Hành & Đổi Trả Toàn Diện - V-Phone Store';
require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<style>
/* Custom scoped styles for Warranty Page */
html {
    scroll-behavior: smooth;
}
[id] {
    scroll-margin-top: 80px;
}
.warranty-hero-section {
    background: linear-gradient(135deg, #04172e 0%, #0a3d62 45%, #0284c7 100%);
    border-radius: 28px;
    position: relative;
    overflow: hidden;
    color: #ffffff;
    box-shadow: 0 12px 36px rgba(10, 61, 98, 0.22);
}

.warranty-hero-bg-glow {
    position: absolute;
    width: 480px;
    height: 480px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.35) 0%, rgba(14, 165, 233, 0.08) 50%, transparent 75%);
    top: -120px;
    right: -80px;
    border-radius: 50%;
    pointer-events: none;
    z-index: 1;
}

.warranty-hero-phone-wrap {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: center;
    align-items: center;
}

.warranty-hero-phone-img {
    max-height: 310px;
    filter: drop-shadow(0 20px 32px rgba(0, 0, 0, 0.55));
    transition: transform 0.3s ease;
    display: block;
}
.warranty-hero-phone-img:hover {
    transform: translateY(-6px);
}

.warranty-glass-badge {
    position: absolute;
    bottom: 8px;
    right: 14px;
    background: rgba(15, 23, 42, 0.85);
    -webkit-backdrop-filter: blur(14px);
    backdrop-filter: blur(14px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 16px;
    padding: 10px 16px;
    color: #ffffff;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.4);
    z-index: 3;
}

.warranty-feature-link {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.12) !important;
    border: 1px solid rgba(255, 255, 255, 0.22) !important;
    color: #ffffff !important;
    font-size: 0.86rem;
    font-weight: 600;
    padding: 10px 16px;
    border-radius: 14px;
    text-decoration: none;
    cursor: pointer;
    backdrop-filter: blur(8px);
    transition: all 0.2s ease;
    height: 100%;
}
.warranty-feature-link:hover {
    background: rgba(255, 255, 255, 0.22) !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.45) !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
}

.warranty-stats-bar {
    background: rgba(0, 0, 0, 0.25);
    border-radius: 20px;
    padding: 16px 20px;
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.warranty-stat-item {
    text-align: center;
    border-right: 1px solid rgba(255, 255, 255, 0.15);
}
.warranty-stat-item:last-child {
    border-right: none;
}
.warranty-stat-number {
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1.1;
    color: #facc15;
}
.warranty-stat-label {
    font-size: 0.76rem;
    color: rgba(255, 255, 255, 0.85);
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Nav quick bar */
.warranty-quick-nav {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 6px 2px 12px 2px;
    scrollbar-width: none;
}
.warranty-quick-nav::-webkit-scrollbar { display: none; }
.warranty-nav-btn {
    white-space: nowrap;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #334155;
    padding: 8px 18px;
    border-radius: 999px;
    font-size: 0.86rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
}
.warranty-nav-btn:hover {
    background: #0066cc;
    border-color: #0066cc;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 102, 204, 0.25);
}

/* Lookup Card */
.warranty-lookup-card {
    background: #ffffff;
    border: 2px solid #e0f2fe;
    border-radius: 24px;
    box-shadow: 0 8px 25px rgba(2, 132, 199, 0.08);
    transition: border-color 0.2s ease;
}
.warranty-lookup-card:focus-within {
    border-color: #0066cc;
}

/* Feature Cards */
.warranty-feature-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 24px;
    height: 100%;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    position: relative;
    overflow: hidden;
}
.warranty-feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 30px rgba(0, 102, 204, 0.12);
    border-color: #93c5fd;
}
.warranty-feature-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 16px;
}

/* Step Timeline */
.step-item-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 22px;
    text-align: center;
    position: relative;
    height: 100%;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
}
.step-number-badge {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0066cc 0%, #0284c7 100%);
    color: #ffffff;
    font-weight: 800;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px auto;
    box-shadow: 0 6px 14px rgba(0, 102, 204, 0.35);
}

/* Table Enhancements */
.warranty-table thead th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    border-bottom: 2px solid #e2e8f0;
}
.warranty-table tbody td {
    padding: 16px 14px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.warranty-table tbody tr:hover {
    background-color: #f8fbff;
}

/* Soft Badges */
.badge-soft-blue { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-weight: 700; }
.badge-soft-green { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-weight: 700; }
.badge-soft-amber { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-weight: 700; }
.badge-soft-purple { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; font-weight: 700; }

/* Accordion FAQs */
.warranty-accordion .accordion-item {
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    margin-bottom: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}
.warranty-accordion .accordion-button {
    font-weight: 700;
    color: #1e293b;
    padding: 18px 22px;
    background: #ffffff;
    font-size: 0.95rem;
}
.warranty-accordion .accordion-button:not(.collapsed) {
    background: #f0f7ff;
    color: #0066cc;
    box-shadow: none;
}
.warranty-accordion .accordion-body {
    padding: 18px 22px;
    font-size: 0.9rem;
    color: #475569;
    line-height: 1.65;
    background: #ffffff;
}

/* Dark Mode Enhancements for Warranty */
[data-bs-theme="dark"] .warranty-nav-btn {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #cbd5e1 !important;
}
[data-bs-theme="dark"] .warranty-nav-btn:hover {
    background: #1a2a4a !important;
    border-color: #38bdf8 !important;
    color: #ffffff !important;
}
[data-bs-theme="dark"] .warranty-lookup-card {
    background: #131f37 !important;
    border-color: rgba(56, 189, 248, 0.25) !important;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4) !important;
}
[data-bs-theme="dark"] .warranty-feature-card {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35) !important;
}
[data-bs-theme="dark"] .step-item-card {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3) !important;
}
[data-bs-theme="dark"] .warranty-table thead th {
    background: #0e172a !important;
    color: #94a3b8 !important;
    border-bottom-color: rgba(255, 255, 255, 0.09) !important;
}
[data-bs-theme="dark"] .warranty-table tbody td {
    border-bottom-color: rgba(255, 255, 255, 0.06) !important;
    color: #f1f5f9 !important;
}
[data-bs-theme="dark"] .warranty-table tbody tr:hover {
    background-color: rgba(56, 189, 248, 0.08) !important;
}
[data-bs-theme="dark"] .warranty-accordion .accordion-item {
    border-color: rgba(255, 255, 255, 0.09) !important;
    background: #131f37 !important;
}
[data-bs-theme="dark"] .warranty-accordion .accordion-button {
    background: #131f37 !important;
    color: #f1f5f9 !important;
}
[data-bs-theme="dark"] .warranty-accordion .accordion-button:not(.collapsed) {
    background: #1a2a4a !important;
    color: #38bdf8 !important;
}
[data-bs-theme="dark"] .warranty-accordion .accordion-body {
    background: #131f37 !important;
    color: #cbd5e1 !important;
}

</style>

<div class="container my-3 pb-5">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small bg-white px-3 py-2 rounded-3 border shadow-sm mb-0">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-primary"><i class="fa-solid fa-house me-1"></i>Trang chủ</a></li>
            <li class="breadcrumb-item active text-secondary fw-semibold" aria-current="page">Chính sách bảo hành & đổi trả</li>
        </ol>
    </nav>

    <!-- Hero Banner with Mockup Presentation -->
    <div class="warranty-hero-section p-4 p-md-5 mb-4">
        <div class="warranty-hero-bg-glow"></div>
        <div class="row align-items-center g-4 position-relative" style="z-index: 2;">
            <div class="col-lg-7">
                <span class="badge bg-white text-primary px-3 py-2 fw-bold rounded-pill mb-3 shadow-sm d-inline-flex align-items-center">
                    <i class="fa-solid fa-certificate text-primary me-2"></i> TIÊU CHUẨN BẢO HÀNH CHÍNH HÃNG 2026
                </span>
                <h1 class="display-6 fw-bold mb-3 text-white">
                    Chính Sách Bảo Hành & Đổi Trả Toàn Diện
                </h1>
                <p class="fs-6 text-light opacity-90 mb-4 lh-base" style="max-width: 600px;">
                    Cam kết 100% điện thoại chính hãng tại hệ thống V-Phone Store. Bảo hành 12 tháng tiêu chuẩn, lỗi là đổi mới 1 - 1 trong 30 ngày đầu tiên.
                </p>

                <!-- Symmetrical 2x2 Grid of Feature Links -->
                <div class="row g-2 mb-4" style="max-width: 620px;">
                    <div class="col-sm-6">
                        <a href="#doi-tra" class="warranty-feature-link">
                            <i class="fa-solid fa-arrows-rotate text-warning fs-5"></i>
                            <span>1 Đổi 1 trong 30 ngày</span>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="#bang-thoi-han" class="warranty-feature-link">
                            <i class="fa-solid fa-shield-halved text-info fs-5"></i>
                            <span>Chính hãng 12 tháng</span>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="#dieu-kien" class="warranty-feature-link">
                            <i class="fa-solid fa-microchip text-success fs-5"></i>
                            <span>Máy cũ BH cả Nguồn & Màn</span>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="#quy-trinh" class="warranty-feature-link">
                            <i class="fa-solid fa-truck-fast text-warning fs-5"></i>
                            <span>Free ship 2 chiều bảo hành</span>
                        </a>
                    </div>
                </div>

                <div class="warranty-stats-bar">
                    <div class="row g-2 align-items-center">
                        <div class="col-3 warranty-stat-item">
                            <div class="warranty-stat-number">30</div>
                            <div class="warranty-stat-label">Ngày 1 đổi 1</div>
                        </div>
                        <div class="col-3 warranty-stat-item">
                            <div class="warranty-stat-number">12T</div>
                            <div class="warranty-stat-label">Bảo hành chính hãng</div>
                        </div>
                        <div class="col-3 warranty-stat-item">
                            <div class="warranty-stat-number">100%</div>
                            <div class="warranty-stat-label">Zin linh kiện</div>
                        </div>
                        <div class="col-3 warranty-stat-item">
                            <div class="warranty-stat-number">30P</div>
                            <div class="warranty-stat-label">Giám định lỗi</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 text-center d-none d-lg-block">
                <div class="warranty-hero-phone-wrap">
                    <img src="assets/images/products/iphone-16-promax.png" alt="V-Phone Warranty" class="warranty-hero-phone-img img-fluid" onerror="this.src='assets/images/vphone-logo.png';">
                    <div class="warranty-glass-badge">
                        <div class="d-flex align-items-center gap-2 text-start">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-circle-check fs-6"></i>
                            </div>
                            <div>
                                <div class="fw-bold small text-white">V-Phone Care</div>
                                <div class="small text-light opacity-75" style="font-size: 11px;">Bảo hành điện tử theo IMEI</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Pills -->
    <div class="warranty-quick-nav mb-4">
        <?php if ($currentUser && !empty($userWarrantyDevices)): ?>
            <a href="#thiet-bi-cua-toi" class="warranty-nav-btn text-primary fw-bold border-primary shadow-sm"><i class="fa-solid fa-mobile-screen-button me-1"></i>Máy của bạn (<?= count($userWarrantyDevices) ?>)</a>
        <?php endif; ?>
        <a href="#tra-cuu" class="warranty-nav-btn"><i class="fa-solid fa-magnifying-glass me-1 text-primary"></i>Tra cứu bảo hành</a>
        <a href="#cam-ket" class="warranty-nav-btn"><i class="fa-solid fa-award me-1 text-warning"></i>4 Cam kết vàng</a>
        <a href="#bang-thoi-han" class="warranty-nav-btn"><i class="fa-solid fa-table me-1 text-info"></i>Thời hạn bảo hành</a>
        <a href="#dieu-kien" class="warranty-nav-btn"><i class="fa-solid fa-clipboard-check me-1 text-success"></i>Điều kiện áp dụng</a>
        <a href="#doi-tra" class="warranty-nav-btn"><i class="fa-solid fa-arrows-rotate me-1 text-danger"></i>Chính sách đổi trả</a>
        <a href="#quy-trinh" class="warranty-nav-btn"><i class="fa-solid fa-list-check me-1 text-primary"></i>Quy trình 4 bước</a>
        <a href="#faq" class="warranty-nav-btn"><i class="fa-solid fa-circle-question me-1 text-secondary"></i>Câu hỏi thường gặp</a>
    </div>

    <?php if ($currentUser && !empty($userWarrantyDevices)): ?>
        <!-- User Delivered Devices Under Warranty (Automatically Loaded) -->
        <div id="thiet-bi-cua-toi" class="card border-0 rounded-4 shadow-sm mb-5 bg-white p-4 p-md-5 border-top border-4 border-primary">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold rounded-pill px-3 py-1">
                            <i class="fa-solid fa-circle-check me-1"></i> ĐÃ TỰ ĐỘNG KÍCH HOẠT TỪ ĐƠN GIAO THÀNH CÔNG
                        </span>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1 small fw-bold">
                            <?= count($userWarrantyDevices) ?> Thiết bị
                        </span>
                    </div>
                    <h3 class="fw-bold text-dark mb-1">Thiết Bị Của Bạn Đang Được Bảo Hành</h3>
                    <p class="text-secondary small mb-0">
                        Xin chào <strong><?= htmlspecialchars($currentUser['fullname']) ?></strong>, hệ thống tự động ghi nhận các thiết bị đã giao thành công vào danh sách bảo hành điện tử chính hãng của bạn.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php?page=orders" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-2 fw-semibold">
                        <i class="fa-solid fa-truck-fast me-1"></i> Quản lý đơn hàng
                    </a>
                </div>
            </div>

            <div class="row g-4">
                <?php foreach ($userWarrantyDevices as $dev): ?>
                    <div class="col-lg-6">
                        <div class="card h-100 rounded-4 border p-3 p-md-4 shadow-sm position-relative overflow-hidden" style="background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-3 bg-white p-2 border shadow-sm flex-shrink-0 text-center" style="width: 86px; height: 86px; display: flex; align-items: center; justify-content: center;">
                                    <img src="<?= htmlspecialchars($dev['product_image']) ?>" alt="<?= htmlspecialchars($dev['product_name']) ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-1">
                                        <span class="badge bg-light text-primary border rounded-pill small">Đơn #VP-<?= (int)$dev['order_id'] ?></span>
                                        <?php if (!$dev['is_expired']): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1 small fw-bold">
                                                <i class="fa-solid fa-circle-check me-1"></i>Còn hạn (<?= $dev['days_remaining'] ?> ngày)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-1 small fw-bold">
                                                <i class="fa-solid fa-circle-exclamation me-1"></i>Hết hạn bảo hành
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= htmlspecialchars($dev['product_name']) ?>">
                                        <?= htmlspecialchars($dev['product_name']) ?>
                                    </h6>
                                    <div class="text-secondary small mb-2">
                                        IMEI điện tử: <code class="bg-white border px-2 py-0.5 rounded text-dark fw-bold"><?= $dev['imei'] ?></code>
                                        <button class="btn btn-sm btn-link p-0 text-muted ms-1" type="button" onclick="navigator.clipboard.writeText('<?= $dev['imei'] ?>'); alert('Đã sao chép IMEI: <?= $dev['imei'] ?>');" title="Sao chép IMEI">
                                            <i class="fa-regular fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3 opacity-25">

                            <!-- Warranty Progress & Details -->
                            <div class="small mb-3">
                                <div class="d-flex justify-content-between align-items-center text-secondary mb-1">
                                    <span>Ngày kích hoạt: <strong class="text-dark"><?= $dev['purchase_date'] ?></strong></span>
                                    <span>Hết hạn: <strong class="<?= $dev['is_expired'] ? 'text-danger' : 'text-primary' ?>"><?= $dev['expire_date'] ?></strong></span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar <?= $dev['is_expired'] ? 'bg-danger' : 'bg-success' ?>" role="progressbar" style="width: <?= $dev['percent_used'] ?>%;"></div>
                                </div>
                                <div class="text-muted mt-2" style="font-size: 11.5px;">
                                    <i class="fa-solid fa-shield-halved text-primary me-1"></i> Gói bảo hành: <strong>Chính hãng <?= $dev['warranty_months'] ?> tháng</strong> (1 đổi 1 trong 30 ngày đầu)
                                </div>
                            </div>

                            <?php 
                            $claim = $dev['claim'] ?? null;
                            $isActiveClaim = $claim && in_array($claim['status'], ['Chờ tiếp nhận', 'Đang kiểm tra']);
                            $isResolvedClaim = $claim && in_array($claim['status'], ['Đã đổi mới', 'Đã sửa chữa', 'Từ chối']);
                            ?>

                            <?php if ($isActiveClaim): ?>
                                <!-- Active Pending Warranty Claim Box -->
                                <div class="rounded-3 p-3 mb-3 border <?php if ($claim['status'] === 'Chờ tiếp nhận'): ?>bg-warning bg-opacity-10 border-warning border-opacity-50<?php else: ?>bg-info bg-opacity-10 border-info border-opacity-50<?php endif; ?>">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="badge <?php if ($claim['status'] === 'Chờ tiếp nhận'): ?>bg-warning text-dark<?php else: ?>bg-info text-white<?php endif; ?> fw-bold rounded-pill px-3 py-1">
                                            <i class="fa-solid <?php if ($claim['status'] === 'Chờ tiếp nhận'): ?>fa-clock<?php else: ?>fa-screwdriver-wrench<?php endif; ?> me-1"></i>
                                            <?= htmlspecialchars($claim['status']) ?>
                                        </span>
                                        <span class="small fw-bold text-muted">Mã yêu cầu: <span class="text-dark">#BH-<?= $claim['id'] ?></span></span>
                                    </div>
                                    <div class="small text-dark mb-1">
                                        <i class="fa-solid fa-circle-exclamation text-warning me-1"></i> <strong>Sự cố:</strong> <?= htmlspecialchars($claim['issue_type']) ?>
                                    </div>
                                    <?php if (!empty($claim['issue_description'])): ?>
                                        <div class="small text-secondary mb-2 fst-italic">"<?= htmlspecialchars($claim['issue_description']) ?>"</div>
                                    <?php endif; ?>
                                    <div class="small text-muted d-flex flex-wrap align-items-center gap-2" style="font-size: 11.5px;">
                                        <span><i class="fa-regular fa-calendar-check me-1"></i> <?= date('d/m/Y H:i', strtotime($claim['created_at'])) ?></span>
                                        <span>&bull;</span>
                                        <span><i class="fa-solid fa-truck-ramp-box me-1"></i> <?= htmlspecialchars($claim['claim_method']) ?></span>
                                    </div>
                                    <?php if (!empty($claim['admin_note'])): ?>
                                        <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 small text-primary fw-medium">
                                            <i class="fa-solid fa-user-gear me-1"></i><strong>Kỹ thuật viên phản hồi:</strong> <?= htmlspecialchars($claim['admin_note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Actions when claim is pending: submit button is hidden -->
                                <div class="d-flex gap-2">
                                    <div class="btn btn-light btn-sm rounded-pill fw-bold text-secondary flex-grow-1 py-2 border text-center" style="pointer-events: none; background: #f8fafc;">
                                        <i class="fa-solid fa-circle-check text-success me-1"></i> Đã gửi yêu cầu bảo hành
                                    </div>
                                    <a href="tel:18006868" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold px-3 py-2" title="Tổng đài hỗ trợ">
                                        <i class="fa-solid fa-phone me-1"></i> 1800 6868
                                    </a>
                                    <a href="index.php?page=orders&order_code=<?= (int)$dev['order_id'] ?>&phone=<?= rawurlencode($dev['phone']) ?>" class="btn btn-outline-secondary btn-sm rounded-pill fw-semibold px-3 py-2">
                                        <i class="fa-solid fa-file-invoice me-1"></i> Đơn hàng
                                    </a>
                                </div>

                            <?php elseif ($isResolvedClaim): ?>
                                <!-- Resolved Warranty Claim Box -->
                                <div class="rounded-3 p-3 mb-3 border bg-light">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="badge <?php if ($claim['status'] === 'Đã đổi mới'): ?>bg-success<?php elseif ($claim['status'] === 'Đã sửa chữa'): ?>bg-primary<?php else: ?>bg-danger<?php endif; ?> text-white fw-bold rounded-pill px-2.5 py-1 small">
                                            Lần bảo hành trước: <?= htmlspecialchars($claim['status']) ?> (#BH-<?= $claim['id'] ?>)
                                        </span>
                                        <span class="small text-muted" style="font-size: 11px;"><?= date('d/m/Y', strtotime($claim['updated_at'] ?? $claim['created_at'])) ?></span>
                                    </div>
                                    <?php if (!empty($claim['admin_note'])): ?>
                                        <div class="small text-muted mt-1" style="font-size: 12px;"><i class="fa-solid fa-check-double text-success me-1"></i>Ghi chú: <?= htmlspecialchars($claim['admin_note']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex gap-2">
                                    <?php if (!$dev['is_expired']): ?>
                                        <button type="button" class="btn btn-primary btn-sm rounded-pill fw-bold flex-grow-1 py-2 shadow-sm" onclick="openClaimModal('<?= htmlspecialchars($dev['product_name'], ENT_QUOTES) ?>', '<?= $dev['imei'] ?>', <?= (int)$dev['order_id'] ?>, <?= (int)$dev['detail_id'] ?>, <?= (int)$dev['product_id'] ?>)">
                                            <i class="fa-solid fa-screwdriver-wrench me-1"></i> Gửi yêu cầu bảo hành mới
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary btn-sm rounded-pill fw-bold flex-grow-1 py-2 disabled" disabled>
                                            <i class="fa-solid fa-ban me-1"></i> Đã hết hạn bảo hành
                                        </button>
                                    <?php endif; ?>
                                    <a href="index.php?page=orders&order_code=<?= (int)$dev['order_id'] ?>&phone=<?= rawurlencode($dev['phone']) ?>" class="btn btn-outline-secondary btn-sm rounded-pill fw-semibold px-3 py-2">
                                        <i class="fa-solid fa-file-invoice me-1"></i> Chi tiết đơn
                                    </a>
                                </div>

                            <?php else: ?>
                                <!-- Initial State: No claim yet -->
                                <div class="d-flex gap-2">
                                    <?php if (!$dev['is_expired']): ?>
                                        <button type="button" class="btn btn-primary btn-sm rounded-pill fw-bold flex-grow-1 py-2 shadow-sm" onclick="openClaimModal('<?= htmlspecialchars($dev['product_name'], ENT_QUOTES) ?>', '<?= $dev['imei'] ?>', <?= (int)$dev['order_id'] ?>, <?= (int)$dev['detail_id'] ?>, <?= (int)$dev['product_id'] ?>)">
                                            <i class="fa-solid fa-screwdriver-wrench me-1"></i> Gửi yêu cầu bảo hành
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary btn-sm rounded-pill fw-bold flex-grow-1 py-2 disabled" disabled>
                                            <i class="fa-solid fa-ban me-1"></i> Đã hết hạn bảo hành
                                        </button>
                                    <?php endif; ?>
                                    <a href="index.php?page=orders&order_code=<?= (int)$dev['order_id'] ?>&phone=<?= rawurlencode($dev['phone']) ?>" class="btn btn-outline-secondary btn-sm rounded-pill fw-semibold px-3 py-2">
                                        <i class="fa-solid fa-file-invoice me-1"></i> Chi tiết đơn
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Toggle manual search if needed -->
            <div id="tra-cuu" class="mt-4 pt-3 border-top text-center">
                <button class="btn btn-link text-decoration-none text-secondary small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseManualSearch">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Bạn cần tra cứu thêm đơn hàng hoặc thiết bị khác? Bấm vào đây
                </button>
                <div class="collapse mt-3" id="collapseManualSearch">
                    <div class="warranty-lookup-card p-3 p-md-4 text-start">
                        <form action="index.php" method="GET" class="row g-2 align-items-center">
                            <input type="hidden" name="page" value="orders">
                            <div class="col-md-5 col-sm-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-hashtag text-primary"></i></span>
                                    <input type="text" name="order_code" class="form-control border-start-0 py-2" placeholder="Mã đơn (Ví dụ: VP-10 hoặc 10)" required>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-phone text-success"></i></span>
                                    <input type="text" name="phone" class="form-control border-start-0 py-2" placeholder="Số điện thoại đặt hàng">
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-12">
                                <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                    <i class="fa-solid fa-search"></i>
                                    <span>Tra Cứu</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($currentUser): ?>
        <!-- Logged in but no delivered orders yet -->
        <div id="thiet-bi-cua-toi" class="card border-0 rounded-4 shadow-sm mb-5 bg-white p-4 p-md-5 text-center">
            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px;">
                <i class="fa-solid fa-box-open fs-2"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2">Chưa Có Thiết Bị Giao Thành Công</h4>
            <p class="text-secondary small mb-4 mx-auto" style="max-width: 580px;">
                Xin chào <strong><?= htmlspecialchars($currentUser['fullname']) ?></strong>, tài khoản của bạn hiện chưa có thiết bị nào ở trạng thái <em>"Đã giao thành công"</em>. Khi đơn hàng được giao tận tay bạn, sản phẩm sẽ <strong>tự động xuất hiện tại đây</strong> kèm số IMEI và thời hạn bảo hành.
            </p>
            <div class="d-flex justify-content-center gap-2 flex-wrap mb-4">
                <a href="index.php?page=orders" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                    <i class="fa-solid fa-truck-fast me-1"></i> Xem đơn hàng đang xử lý
                </a>
                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold">
                    <i class="fa-solid fa-mobile-screen me-1"></i> Mua sắm smartphone
                </a>
            </div>

            <!-- Manual search form for guests/other numbers -->
            <div id="tra-cuu" class="pt-3 border-top text-start">
                <div class="fw-bold text-dark small mb-2"><i class="fa-solid fa-magnifying-glass me-1 text-primary"></i>Hoặc tra cứu mã đơn hàng thủ công:</div>
                <form action="index.php" method="GET" class="row g-2 align-items-center">
                    <input type="hidden" name="page" value="orders">
                    <div class="col-md-5 col-sm-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-hashtag text-primary"></i></span>
                            <input type="text" name="order_code" class="form-control border-start-0 py-2" placeholder="Mã đơn (Ví dụ: VP-10 hoặc 10)" required>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-phone text-success"></i></span>
                            <input type="text" name="phone" class="form-control border-start-0 py-2" placeholder="Số điện thoại đặt hàng">
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-12">
                        <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="fa-solid fa-search"></i>
                            <span>Kiểm Tra Ngay</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Guest User: Prompt to login for automatic devices or search manually -->
        <div id="tra-cuu" class="mb-5">
            <div class="card border-0 rounded-4 shadow-sm p-4 mb-3" style="background: linear-gradient(135deg, #eff6ff 0%, #e0f2fe 100%); border: 1px solid #bfdbfe !important;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                            <i class="fa-solid fa-user-shield fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Đã Mua Điện Thoại Tại V-Phone?</h5>
                            <p class="text-secondary small mb-0">Đăng nhập tài khoản để <strong>tự động xem danh mục thiết bị</strong> đang được bảo hành điện tử mà không cần tra cứu thủ công.</p>
                        </div>
                    </div>
                    <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" type="button" data-bs-toggle="modal" data-bs-target="#unifiedAuthModal">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Đăng Nhập Ngay
                    </button>
                </div>
            </div>

            <!-- Manual Search for Guests -->
            <div class="warranty-lookup-card p-4">
                <div class="row align-items-center g-3">
                    <div class="col-lg-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-4 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
                                <i class="fa-solid fa-magnifying-glass fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-1">Tra Cứu Hạn Bảo Hành</h5>
                                <p class="text-secondary small mb-0">Nhập mã đơn hàng hoặc số điện thoại mua hàng để kiểm tra.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <form action="index.php" method="GET" class="row g-2 align-items-center">
                            <input type="hidden" name="page" value="orders">
                            <div class="col-md-5 col-sm-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-hashtag text-primary"></i></span>
                                    <input type="text" name="order_code" class="form-control border-start-0 py-2" placeholder="Mã đơn (Ví dụ: VP-10 hoặc 10)" required>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-phone text-success"></i></span>
                                    <input type="text" name="phone" class="form-control border-start-0 py-2" placeholder="Số điện thoại đặt hàng">
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-12">
                                <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                    <i class="fa-solid fa-search"></i>
                                    <span>Kiểm Tra Ngay</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 4 Pillars Section -->
    <div id="cam-ket" class="mb-5">
        <div class="text-center mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold rounded-pill px-3 py-1 mb-2">ĐẶC QUYỀN KHÁCH HÀNG</span>
            <h3 class="fw-bold text-dark mb-1">4 Trụ Cột Cam Kết Bảo Hành V-Phone</h3>
            <p class="text-secondary small mb-0">Tiêu chuẩn dịch vụ dẫn đầu bảo đảm quyền lợi tối đa khi mua smartphone</p>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="warranty-feature-card">
                    <div class="warranty-feature-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </div>
                    <span class="badge badge-soft-amber rounded-pill px-2 py-1 small mb-2">30 Ngày Đầu</span>
                    <h5 class="fw-bold text-dark mb-2">Lỗi 1 Đổi 1 Tại Chỗ</h5>
                    <p class="text-secondary small mb-0 lh-base">
                        Sản phẩm gặp lỗi phần cứng từ nhà sản xuất trong 30 ngày đầu tiên được đổi ngay máy mới 100% nguyên seal cùng dòng máy hoặc hoàn tiền 100%.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="warranty-feature-card">
                    <div class="warranty-feature-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                    <span class="badge badge-soft-blue rounded-pill px-2 py-1 small mb-2">Chính Hãng 100%</span>
                    <h5 class="fw-bold text-dark mb-2">Bảo Hành 12 Tháng</h5>
                    <p class="text-secondary small mb-0 lh-base">
                        Toàn bộ sản phẩm Apple, Samsung, Xiaomi, OPPO... được bảo hành điện tử chính hãng tại tất cả các trung tâm bảo hành ủy quyền trên toàn quốc.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="warranty-feature-card">
                    <div class="warranty-feature-icon bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-microchip"></i>
                    </div>
                    <span class="badge badge-soft-green rounded-pill px-2 py-1 small mb-2">Bảo Hành Toàn Diện</span>
                    <h5 class="fw-bold text-dark mb-2">Nguồn & Màn Hình Máy Cũ</h5>
                    <p class="text-secondary small mb-0 lh-base">
                        Máy Like New 99% được bảo hành 6 tháng toàn diện bao gồm cả <strong>Nguồn, Màn hình, Cảm biến Face ID và Vân tay</strong> mà không phụ thu bất kỳ khoản phí nào.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="warranty-feature-card">
                    <div class="warranty-feature-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <span class="badge badge-soft-purple rounded-pill px-2 py-1 small mb-2">1 Đổi 1 Trong 12T</span>
                    <h5 class="fw-bold text-dark mb-2">Phụ Kiện Đổi Mới</h5>
                    <p class="text-secondary small mb-0 lh-base">
                        Củ sạc siêu nhanh, cáp sạc, pin dự phòng chính hãng đi kèm hoặc mua lẻ bị lỗi kỹ thuật không sạc được đổi ngay sản phẩm mới 100% suốt 12 tháng.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Warranty Term Table -->
    <div id="bang-thoi-han" class="card border-0 rounded-4 shadow-sm mb-5 bg-white overflow-hidden">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold rounded-pill px-3 py-1 mb-2">DANH MỤC CHI TIẾT</span>
                    <h4 class="fw-bold text-dark mb-1">Bảng Thời Hạn & Phạm Vi Bảo Hành Theo Dòng Máy</h4>
                    <p class="text-secondary small mb-0">Áp dụng cho mọi khách hàng đặt mua online hoặc mua trực tiếp tại hệ thống V-Phone Store</p>
                </div>
                <a href="tel:18006868" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-2 fw-bold d-inline-flex align-items-center gap-1">
                    <i class="fa-solid fa-phone-volume text-primary"></i>
                    <span>Tổng đài miễn phí: 1800 6868 (Nhánh 2)</span>
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table warranty-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Nhóm Sản Phẩm</th>
                            <th>Thời Hạn Bảo Hành</th>
                            <th>Chính Sách 1 Đổi 1</th>
                            <th>Linh Kiện Áp Dụng Bảo Hành</th>
                            <th class="pe-4">Hình Thức Tiếp Nhận</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-brands fa-apple fs-5 text-dark"></i>
                                    <div>
                                        <div class="fw-bold text-dark">iPhone / iPad Mới (Apple VN/A)</div>
                                        <div class="text-muted" style="font-size: 11.5px;">Chính hãng Apple phân phối tại Việt Nam</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-blue px-3 py-2 rounded-pill">12 Tháng Chính Hãng</span></td>
                            <td><span class="badge badge-soft-green px-3 py-2 rounded-pill">30 Ngày Đầu</span></td>
                            <td class="text-secondary small">
                                Toàn bộ phần cứng: Màn hình, Mainboard, Camera, Pin chai tự nhiên &gt; 20%, phụ kiện trong hộp.
                            </td>
                            <td class="pe-4">
                                <span class="badge bg-light text-dark border">Bảo hành điện tử qua số IMEI / Serial</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-brands fa-android fs-5 text-success"></i>
                                    <div>
                                        <div class="fw-bold text-dark">Samsung, Xiaomi, Pixel, OPPO Mới</div>
                                        <div class="text-muted" style="font-size: 11.5px;">Máy mới 100% nguyên seal nhà sản xuất</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-blue px-3 py-2 rounded-pill">12 Tháng Chính Hãng</span></td>
                            <td><span class="badge badge-soft-green px-3 py-2 rounded-pill">30 Ngày Đầu</span></td>
                            <td class="text-secondary small">
                                Toàn bộ máy, cụm camera, màn hình AMOLED/OLED, chip vi xử lý, củ sạc nhanh và phụ kiện nguyên hộp.
                            </td>
                            <td class="pe-4">
                                <span class="badge bg-light text-dark border">Tại TTBH Hãng hoặc tại V-Phone</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-mobile-screen-button fs-5 text-danger"></i>
                                    <div>
                                        <div class="fw-bold text-dark">Điện Thoại Like New 99% (Máy Cũ)</div>
                                        <div class="text-muted" style="font-size: 11.5px;">Zin 100%, kiểm định 32 bước nghiêm ngặt</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-purple px-3 py-2 rounded-pill">6 Tháng Toàn Diện</span></td>
                            <td><span class="badge badge-soft-green px-3 py-2 rounded-pill">30 Ngày Đầu</span></td>
                            <td class="text-secondary small">
                                Bảo hành toàn bộ phần cứng bao gồm cả <strong>Nguồn, Màn hình cảm ứng, Face ID / Vân tay</strong> và Pin.
                            </td>
                            <td class="pe-4">
                                <span class="badge bg-light text-dark border">Bảo hành trực tiếp tại V-Phone</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-plug fs-5 text-warning"></i>
                                    <div>
                                        <div class="fw-bold text-dark">Phụ Kiện Chính Hãng</div>
                                        <div class="text-muted" style="font-size: 11.5px;">Củ sạc nhanh, Cáp sạc, Tai nghe, Sạc dự phòng</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-amber px-3 py-2 rounded-pill">12 Tháng</span></td>
                            <td><span class="badge badge-soft-green px-3 py-2 rounded-pill">12 Tháng (Đổi Mới)</span></td>
                            <td class="text-secondary small">
                                Lỗi mạch chip sạc, đứt ngầm bên trong, không nhận sạc, chập chờn do lỗi kỹ thuật của nhà sản xuất.
                            </td>
                            <td class="pe-4">
                                <span class="badge bg-light text-dark border">1 Đổi 1 phụ kiện mới ngay</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-shield-halved fs-5 text-primary"></i>
                                    <div>
                                        <div class="fw-bold text-primary">Gói Mở Rộng VIP (V-Care+)</div>
                                        <div class="text-muted" style="font-size: 11.5px;">Đặc quyền nâng cao khi khách hàng đăng ký thêm</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">12 - 24 Tháng</span></td>
                            <td><span class="badge badge-soft-green px-3 py-2 rounded-pill">Đổi máy tương đương</span></td>
                            <td class="text-secondary small">
                                Mở rộng bảo hành cho cả sự cố bất cẩn: <strong>Rơi vỡ màn hình, Vô nước, Chập điện</strong> (Trợ giá tới 80%).
                            </td>
                            <td class="pe-4">
                                <span class="badge bg-primary text-white">Đặc quyền VIP V-Care</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Conditions: Valid vs Ineligible -->
    <div id="dieu-kien" class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm h-100 bg-white p-4 border-start border-4 border-success">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fa-solid fa-circle-check fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Đủ Điều Kiện Bảo Hành Miễn Phí</h5>
                        <div class="text-success small fw-semibold">Áp dụng cho mọi thiết bị phân phối bởi V-Phone</div>
                    </div>
                </div>

                <div class="d-flex flex-column gap-3 small text-secondary">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-check text-success mt-1"></i>
                        <div><strong>Còn thời hạn bảo hành:</strong> Căn cứ vào số Serial/IMEI, Hóa đơn điện tử hoặc Số điện thoại lưu trên hệ thống V-Phone.</div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-check text-success mt-1"></i>
                        <div><strong>Tem niêm phong nguyên vẹn:</strong> Tem bảo hành của V-Phone (đối với máy Like New) hoặc của hãng không bị rách rời, cạo sửa, tẩy xóa.</div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-check text-success mt-1"></i>
                        <div><strong>IMEI trùng khớp 100%:</strong> Số IMEI hiển thị trên máy (bấm <code class="text-dark bg-light px-1">*#06#</code>) trùng khớp hoàn toàn với vỏ hộp và dữ liệu đơn hàng.</div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-check text-success mt-1"></i>
                        <div><strong>Lỗi phần cứng do nhà sản xuất:</strong> Sản phẩm phát sinh lỗi linh kiện tự nhiên như sập nguồn, camera lỗi cảm biến, loa rè, mất sóng, sọc màn hình tự nhiên...</div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-check text-success mt-1"></i>
                        <div><strong>Đã thoát tài khoản bảo mật:</strong> Quý khách vui lòng đăng xuất tài khoản cá nhân (iCloud, Google Account, Mi Cloud, Samsung Account) trước khi gửi bảo hành.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm h-100 bg-white p-4 border-start border-4 border-danger">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Trường Hợp Từ Chối Bảo Hành Miễn Phí</h5>
                        <div class="text-danger small fw-semibold">V-Phone hỗ trợ sửa chữa với chi phí trợ giá 20% - 30%</div>
                    </div>
                </div>

                <div class="d-flex flex-column gap-3 small text-secondary">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-xmark text-danger mt-1"></i>
                        <div><strong>Rơi vỡ, biến dạng vật lý:</strong> Máy bị cong sườn, cấn móp nặng, nứt vỡ kính màn hình hoặc nắp lưng do va chạm, tì đè vật lý.</div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-xmark text-danger mt-1"></i>
                        <div><strong>Vào nước, ẩm ướt chất lỏng:</strong> Máy có dấu hiệu ngâm nước, ẩm mốc, oxy hóa bo mạch, quỳ tím đổi màu đỏ (kể cả máy có chuẩn kháng nước IP68).</div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-xmark text-danger mt-1"></i>
                        <div><strong>Tự ý can thiệp phần cứng:</strong> Máy đã bị tháo mở, sửa chữa hoặc thay thế linh kiện tại các cơ sở bên ngoài không ủy quyền của V-Phone/hãng.</div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-xmark text-danger mt-1"></i>
                        <div><strong>Can thiệp phần mềm trái phép:</strong> Thiết bị bị can thiệp sâu: Root máy, Jailbreak, Unlock Bootloader hoặc nạp ROM tùy chỉnh sai dẫn đến chết máy (Brick).</div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-xmark text-danger mt-1"></i>
                        <div><strong>Dính khóa tài khoản:</strong> Máy dính tài khoản iCloud / Google FRP Lock mà khách hàng không cung cấp được mật khẩu để kiểm tra xuất xưởng.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Return & Exchange Policy (#doi-tra) -->
    <div id="doi-tra" class="card border-0 rounded-4 shadow-sm mb-5 bg-white p-4 p-md-5">
        <div class="text-center mb-4">
            <span class="badge bg-success bg-opacity-10 text-success fw-bold rounded-pill px-3 py-1 mb-2">QUY CHUẨN ĐỔI TRẢ</span>
            <h3 class="fw-bold text-dark mb-2">Chính Sách Đổi Trả Sản Phẩm 1 - 1</h3>
            <p class="text-secondary small mb-0">Cam kết bảo vệ quyền lợi tối đa: Lỗi là đổi mới, không làm mất thời gian chờ đợi</p>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="p-4 rounded-4 bg-light h-100 border text-center">
                    <div class="display-6 fw-bold text-primary mb-2">30 Ngày</div>
                    <h5 class="fw-bold text-dark mb-2">Lỗi Kỹ Thuật Nhà Sản Xuất</h5>
                    <p class="text-secondary small mb-3">
                        Đổi ngay máy mới 100% nguyên seal cùng dòng máy. Hoàn tiền 100% nếu hết sản phẩm thay thế hoặc đổi sang dòng máy khác bù trừ tiền theo giá niêm yết.
                    </p>
                    <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-bold">Miễn Phí 100%</span>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 rounded-4 bg-light h-100 border text-center">
                    <div class="display-6 fw-bold text-warning mb-2">1 - 7 Ngày</div>
                    <h5 class="fw-bold text-dark mb-2">Đổi Trả Theo Nhu Cầu</h5>
                    <p class="text-secondary small mb-3">
                        Máy hoạt động bình thường nhưng khách hàng muốn đổi dòng máy khác hoặc trả lại. Yêu cầu máy nguyên vẹn, đầy đủ hộp, phụ kiện, hóa đơn và quà tặng kèm.
                    </p>
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold">Phí khấu trừ 10%</span>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-4 rounded-4 bg-light h-100 border text-center">
                    <div class="display-6 fw-bold text-info mb-2">8 - 30 Ngày</div>
                    <h5 class="fw-bold text-dark mb-2">Đổi Trả Theo Nhu Cầu</h5>
                    <p class="text-secondary small mb-3">
                        Thu hồi máy theo nhu cầu khách hàng khi không còn nhu cầu sử dụng. Từ ngày thứ 31 trở đi, định giá theo chương trình <strong>Thu Cũ Đổi Mới</strong> trợ giá cao nhất.
                    </p>
                    <span class="badge bg-info text-dark rounded-pill px-3 py-1 fw-bold">Phí khấu trừ 20%</span>
                </div>
            </div>
        </div>

        <div class="alert alert-info border-0 rounded-3 mt-4 small mb-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-info fs-5 flex-shrink-0 text-primary"></i>
            <div><strong>Lưu ý:</strong> Khi tiến hành đổi trả, quý khách vui lòng mang đầy đủ máy, vỏ hộp, củ sạc, cáp sạc, quà tặng kèm theo và thông tin đơn hàng để được nhân viên hỗ trợ nhanh nhất.</div>
        </div>
    </div>

    <!-- 4 Steps Timeline -->
    <div id="quy-trinh" class="card border-0 rounded-4 shadow-sm mb-5 bg-white p-4 p-md-5">
        <div class="text-center mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold rounded-pill px-3 py-1 mb-2">QUY TRÌNH CHUẨN MỰC</span>
            <h3 class="fw-bold text-dark mb-2">Quy Trình 4 Bước Tiếp Nhận & Xử Lý Bảo Hành</h3>
            <p class="text-secondary small mb-0">Nhanh gọn - Chuyên nghiệp - Minh bạch trong từng khâu xử lý</p>
        </div>

        <div class="row g-3">
            <div class="col-lg-3 col-md-6">
                <div class="step-item-card">
                    <div class="step-number-badge">1</div>
                    <h6 class="fw-bold text-dark mb-2">Tiếp Nhận Máy</h6>
                    <p class="text-secondary small mb-0">
                        Mang máy trực tiếp đến cửa hàng V-Phone hoặc gửi chuyển phát nhanh miễn phí ship 2 chiều. Nhân viên tiếp nhận và lập biên nhận.
                    </p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="step-item-card">
                    <div class="step-number-badge">2</div>
                    <h6 class="fw-bold text-dark mb-2">Giám Định 32 Bước</h6>
                    <p class="text-secondary small mb-0">
                        Kỹ thuật viên kiểm tra 32 tiêu chí phần cứng & phần mềm trong <strong>15 - 30 phút</strong>. Xác định nguyên nhân lỗi trước mặt khách.
                    </p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="step-item-card">
                    <div class="step-number-badge">3</div>
                    <h6 class="fw-bold text-dark mb-2">Xử Lý & Đổi Mới</h6>
                    <p class="text-secondary small mb-0">
                        Đổi mới ngay (trong 30 ngày) hoặc sửa chữa / chuyển trung tâm hãng (3 - 7 ngày). <strong>Cho mượn máy dùng tạm</strong> nếu giữ máy &gt; 48h.
                    </p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="step-item-card">
                    <div class="step-number-badge">4</div>
                    <h6 class="fw-bold text-dark mb-2">Bàn Giao & Nghiệm Thu</h6>
                    <p class="text-secondary small mb-0">
                        Khách hàng kiểm tra hoạt động toàn diện của máy, ký biên bản nghiệm thu và kích hoạt thời gian bảo hành bổ sung.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQs Section -->
    <div id="faq" class="card border-0 rounded-4 shadow-sm mb-5 bg-white p-4 p-md-5">
        <div class="text-center mb-4">
            <span class="badge bg-warning bg-opacity-10 text-warning text-dark fw-bold rounded-pill px-3 py-1 mb-2">GIẢI ĐÁP NHANH</span>
            <h3 class="fw-bold text-dark mb-2">Câu Hỏi Thường Gặp Về Bảo Hành</h3>
            <p class="text-secondary small mb-0">Những thắc mắc phổ biến nhất của khách hàng khi cần hỗ trợ bảo hành tại V-Phone</p>
        </div>

        <div class="accordion warranty-accordion" id="accordionWarrantyFaq">
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading1">
                    <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1">
                        1. Tôi mua hàng online từ xa thì khi cần bảo hành máy sẽ làm như thế nào?
                    </button>
                </h2>
                <div id="faqCollapse1" class="accordion-collapse collapse" data-bs-parent="#accordionWarrantyFaq">
                    <div class="accordion-body">
                        Rất đơn giản! Quý khách chỉ cần liên hệ Hotline miễn phí <strong>1800 6868 (Nhánh 2)</strong> hoặc nhắn tin qua Fanpage/Zalo V-Phone. Bộ phận CSKH sẽ hỗ trợ bạn đóng gói và gửi qua chuyển phát nhanh. <strong>V-Phone hỗ trợ toàn bộ 100% cước phí vận chuyển cả 2 chiều</strong> đối với thiết bị thuộc diện bảo hành hợp lệ.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading2">
                    <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2">
                        2. Điện thoại Like New (máy cũ) có được bảo hành màn hình và nguồn không?
                    </button>
                </h2>
                <div id="faqCollapse2" class="accordion-collapse collapse" data-bs-parent="#accordionWarrantyFaq">
                    <div class="accordion-body">
                        <strong>CÓ!</strong> Đây là cam kết chất lượng độc quyền tại V-Phone Store. Tất cả các sản phẩm Like New 99% bán ra đều được bảo hành toàn diện 6 tháng bao gồm cả <strong>Nguồn, Màn hình cảm ứng và Cảm biến vân tay / Face ID</strong> mà hoàn toàn không bắt buộc quý khách phải mua thêm gói phụ phí bảo hành nào khác.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading3">
                    <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3">
                        3. Trong thời gian gửi máy bảo hành, tôi có được hỗ trợ máy dùng tạm không?
                    </button>
                </h2>
                <div id="faqCollapse3" class="accordion-collapse collapse" data-bs-parent="#accordionWarrantyFaq">
                    <div class="accordion-body">
                        Chắc chắn có! Để đảm bảo công việc và liên lạc của quý khách không bị gián đoạn, nếu sản phẩm cần gửi về hãng kiểm tra hoặc sửa chữa kéo dài quá <strong>48 giờ</strong>, V-Phone sẵn sàng <strong>cung cấp một chiếc smartphone dự phòng miễn phí</strong> để quý khách sử dụng tạm trong suốt thời gian chờ đợi.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading4">
                    <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4">
                        4. Làm cách nào để tôi kiểm tra ngày kích hoạt và thời hạn bảo hành của máy?
                    </button>
                </h2>
                <div id="faqCollapse4" class="accordion-collapse collapse" data-bs-parent="#accordionWarrantyFaq">
                    <div class="accordion-body">
                        Quý khách có thể kiểm tra trực tiếp qua ô <strong>"Tra Cứu Hạn Bảo Hành Trực Tuyến"</strong> ở đầu trang này bằng cách nhập Mã đơn hàng hoặc Số điện thoại. Ngoài ra, quý khách cũng có thể tra cứu theo số IMEI trực tiếp trên website của hãng hoặc gọi hotline 1800 6868 để chuyên viên tra cứu nhanh trong 30 giây.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading5">
                    <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse5">
                        5. Thời gian xử lý máy bảo hành thường mất bao lâu?
                    </button>
                </h2>
                <div id="faqCollapse5" class="accordion-collapse collapse" data-bs-parent="#accordionWarrantyFaq">
                    <div class="accordion-body">
                        - Nếu máy lỗi trong 30 ngày đầu: <strong>Đổi máy mới ngay lập tức</strong> trong vòng 15 - 30 phút sau khi kỹ thuật viên giám định xong.<br>
                        - Nếu máy cần sửa chữa thay thế linh kiện tại cửa hàng: Từ <strong>1 đến 3 ngày làm việc</strong>.<br>
                        - Nếu máy gửi về trung tâm bảo hành ủy quyền của Hãng (Apple, Samsung...): Thông thường từ <strong>5 đến 7 ngày làm việc</strong> (không tính Thứ 7, Chủ Nhật và ngày lễ).
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact & Service Centers -->
    <div class="p-4 p-md-5 rounded-4 text-white shadow-sm" style="background: linear-gradient(135deg, #071e36 0%, #0f3d64 60%, #0369a1 100%);">
        <div class="row g-4 align-items-center">
            <div class="col-lg-7">
                <span class="badge bg-white text-primary px-3 py-1 fw-bold rounded-pill mb-2">HỖ TRỢ TRỰC TIẾP</span>
                <h3 class="fw-bold text-white mb-2">Trung Tâm Tiếp Nhận & Chăm Sóc Khách Hàng</h3>
                <p class="text-light opacity-90 small mb-4">
                    Đội ngũ kỹ thuật viên tay nghề cao của V-Phone luôn sẵn sàng hỗ trợ bạn từ 8:00 đến 21:30 hàng ngày.
                </p>
                <div class="d-flex flex-column gap-2 small opacity-90">
                    <div><i class="fa-solid fa-location-dot text-warning me-2"></i><strong>TP. Hồ Chí Minh:</strong> 123 Đường Công Nghệ, Phường Bến Nghé, Quận 1</div>
                    <div><i class="fa-solid fa-location-dot text-warning me-2"></i><strong>TP. Hà Nội:</strong> 456 Phố Điện Tử, Phường Dịch Vọng Hậu, Quận Cầu Giấy</div>
                    <div><i class="fa-solid fa-location-dot text-warning me-2"></i><strong>TP. Đà Nẵng:</strong> 789 Đại Lộ Kỹ Thuật, Quận Hải Châu</div>
                    <div><i class="fa-solid fa-envelope text-warning me-2"></i><strong>Email kỹ thuật & bảo hành:</strong> baohanh@vphone.vn / cskh@vphone.vn</div>
                </div>
            </div>
            <div class="col-lg-5 text-center text-lg-end">
                <div class="bg-white bg-opacity-10 p-4 rounded-4 border border-white border-opacity-15 text-center">
                    <div class="small text-light opacity-75 mb-1">TỔNG ĐÀI BẢO HÀNH MIỄN PHÍ</div>
                    <div class="display-6 fw-bold text-warning mb-2">1800 6868</div>
                    <div class="text-light opacity-75 small mb-3">(Nhấn phím 2 - Hỗ trợ kỹ thuật & tiếp nhận bảo hành)</div>
                    <a href="tel:18006868" class="btn btn-warning rounded-pill px-4 fw-bold text-dark w-100 py-2 shadow-sm d-inline-flex align-items-center justify-content-center gap-2">
                        <i class="fa-solid fa-phone-volume"></i>
                        <span>GỌI TỔNG ĐÀI NGAY</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal Gửi Yêu Cầu Hỗ Trợ Bảo Hành -->
<div class="modal fade" id="warrantyClaimModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-dark mb-0">Yêu Cầu Hỗ Trợ Bảo Hành</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formClaimWarranty" onsubmit="submitClaimWarranty(event)">
                    <input type="hidden" id="claimOrderIdInput" name="order_id" value="">
                    <input type="hidden" id="claimDetailIdInput" name="detail_id" value="">
                    <input type="hidden" id="claimProductIdInput" name="product_id" value="">
                    <input type="hidden" id="claimProductNameInput" name="product_name" value="">
                    <input type="hidden" id="claimImeiInput" name="imei" value="">

                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="fw-bold text-dark small" id="claimProductName">iPhone 17 Pro</div>
                        <div class="text-secondary small mt-1">
                            Mã IMEI: <strong class="text-dark" id="claimProductImei">358921094821034</strong> | Đơn: <strong class="text-primary" id="claimOrderId">#VP-1</strong>
                        </div>
                    </div>

                    <?php if (!$currentUser): ?>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Họ tên của bạn *</label>
                            <input type="text" name="customer_name" class="form-control rounded-3 small" placeholder="Nguyễn Văn A" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold">Số điện thoại liên hệ *</label>
                            <input type="tel" name="customer_phone" class="form-control rounded-3 small" placeholder="0901234567" required>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Vấn đề hoặc lỗi thiết bị đang gặp *</label>
                        <select class="form-select rounded-3 small" required id="claimIssueType" name="issue_type">
                            <option value="">-- Chọn loại sự cố gặp phải --</option>
                            <option value="nguon">Lỗi nguồn, không bật lên được hoặc sạc không vào</option>
                            <option value="manhinh">Lỗi màn hình: sọc màn, chết điểm, liệt cảm ứng</option>
                            <option value="camera_loa">Lỗi camera, loa rè, mic nói đầu bên kia không nghe</option>
                            <option value="song_wifi">Lỗi kết nối: mất sóng di động, chập chờn wifi / bluetooth</option>
                            <option value="pin">Pin tụt đột ngột, phồng pin hoặc máy nóng bất thường</option>
                            <option value="khac">Lỗi phần cứng hoặc phần mềm khác</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mô tả cụ thể triệu chứng lỗi</label>
                        <textarea class="form-control rounded-3 small" rows="3" id="claimIssueDesc" name="issue_description" placeholder="Ví dụ: Máy cắm sạc nhưng không lên nguồn từ tối qua..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Hình thức gửi thiết bị</label>
                        <div class="d-flex flex-column gap-2 small">
                            <label class="form-check p-2 border rounded-3 d-flex align-items-center gap-2 mb-0 bg-light">
                                <input class="form-check-input ms-0 me-2" type="radio" name="claim_method" value="store" checked>
                                <span><strong>Mang trực tiếp tới cửa hàng:</strong> 15-30 phút kiểm tra lấy ngay hoặc mượn máy dùng tạm.</span>
                            </label>
                            <label class="form-check p-2 border rounded-3 d-flex align-items-center gap-2 mb-0 bg-light">
                                <input class="form-check-input ms-0 me-2" type="radio" name="claim_method" value="shipping">
                                <span><strong>Gửi bưu điện hỏa tốc:</strong> V-Phone thanh toán 100% phí ship 2 chiều.</span>
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-danger d-none py-2 small rounded-3 mb-3" id="claimErrorAlert"></div>

                    <div class="alert alert-success d-none py-2 small rounded-3 mb-3" id="claimSuccessAlert">
                        <i class="fa-solid fa-check-circle me-1"></i> Yêu cầu bảo hành đã được ghi nhận! Kỹ thuật viên V-Phone sẽ liên hệ qua điện thoại trong 15 phút.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light rounded-pill fw-semibold px-4 w-50" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4 w-50 shadow-sm" id="btnSubmitClaim">Gửi Yêu Cầu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openClaimModal(prodName, imei, orderId, detailId, productId) {
    document.getElementById('claimProductName').textContent = prodName;
    document.getElementById('claimProductImei').textContent = imei;
    document.getElementById('claimOrderId').textContent = '#VP-' + orderId;

    document.getElementById('claimProductNameInput').value = prodName;
    document.getElementById('claimImeiInput').value = imei;
    document.getElementById('claimOrderIdInput').value = orderId;
    document.getElementById('claimDetailIdInput').value = detailId || 0;
    document.getElementById('claimProductIdInput').value = productId || 0;

    document.getElementById('claimIssueType').value = '';
    const descInput = document.getElementById('claimIssueDesc');
    if (descInput) descInput.value = '';

    const errAlert = document.getElementById('claimErrorAlert');
    if (errAlert) errAlert.classList.add('d-none');
    const succAlert = document.getElementById('claimSuccessAlert');
    if (succAlert) succAlert.classList.add('d-none');

    const btn = document.getElementById('btnSubmitClaim');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Gửi Yêu Cầu';

    const modalEl = document.getElementById('warrantyClaimModal');
    if (modalEl && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

function submitClaimWarranty(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitClaim');
    const errAlert = document.getElementById('claimErrorAlert');
    const succAlert = document.getElementById('claimSuccessAlert');

    if (errAlert) errAlert.classList.add('d-none');
    if (succAlert) succAlert.classList.add('d-none');

    const form = document.getElementById('formClaimWarranty');
    const formData = new FormData(form);

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang gửi...';

    fetch('index.php?page=warranty-claim', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            succAlert.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> ' + (data.message || 'Yêu cầu bảo hành đã được tiếp nhận thành công!');
            succAlert.classList.remove('d-none');
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Đã gửi thành công';
            setTimeout(() => {
                window.location.reload();
            }, 1200);
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Gửi Yêu Cầu';
            if (errAlert) {
                errAlert.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> ' + (data.message || 'Có lỗi xảy ra, vui lòng thử lại.');
                errAlert.classList.remove('d-none');
            } else {
                alert(data.message || 'Có lỗi xảy ra, vui lòng thử lại.');
            }
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Gửi Yêu Cầu';
        if (errAlert) {
            errAlert.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> Không thể kết nối đến máy chủ. Vui lòng thử lại sau.';
            errAlert.classList.remove('d-none');
        } else {
            alert('Không thể kết nối đến máy chủ. Vui lòng thử lại sau.');
        }
    });
}
</script>

<?php require_once 'app/views/includes/footer.php'; ?>
