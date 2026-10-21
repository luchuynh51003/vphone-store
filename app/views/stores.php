<?php
$pageTitle = 'Hệ Thống Cửa Hàng V-Phone - 15+ Chi Nhánh Toàn Quốc';
require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<style>
/* Custom scoped styles for Stores Page */
html {
    scroll-behavior: smooth;
}
[id] {
    scroll-margin-top: 80px;
}

/* Hero Section */
.stores-hero-section {
    background: linear-gradient(135deg, #04172e 0%, #0a3d62 45%, #0284c7 100%);
    border-radius: 28px;
    position: relative;
    overflow: hidden;
    color: #ffffff;
    box-shadow: 0 12px 36px rgba(10, 61, 98, 0.22);
}

.stores-hero-bg-glow {
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

.stores-hero-bg-glow-2 {
    position: absolute;
    width: 320px;
    height: 320px;
    background: radial-gradient(circle, rgba(250, 204, 21, 0.15) 0%, transparent 70%);
    bottom: -100px;
    left: -60px;
    border-radius: 50%;
    pointer-events: none;
    z-index: 1;
}

.stores-stats-bar {
    background: rgba(0, 0, 0, 0.25);
    border-radius: 20px;
    padding: 16px 20px;
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.stores-stat-item {
    text-align: center;
    border-right: 1px solid rgba(255, 255, 255, 0.15);
}
.stores-stat-item:last-child {
    border-right: none;
}
.stores-stat-number {
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1.1;
    color: #facc15;
}
.stores-stat-label {
    font-size: 0.76rem;
    color: rgba(255, 255, 255, 0.85);
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Hero Map Illustration */
.stores-hero-map-wrap {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: center;
    align-items: center;
}

.stores-map-icon {
    font-size: 180px;
    color: rgba(255, 255, 255, 0.08);
    position: relative;
}

.stores-map-icon::after {
    content: '';
    position: absolute;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.3) 0%, transparent 70%);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    border-radius: 50%;
}

/* Region Filter Pills */
.stores-filter-nav {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 6px 2px 12px 2px;
    scrollbar-width: none;
}
.stores-filter-nav::-webkit-scrollbar { display: none; }

.stores-filter-btn {
    white-space: nowrap;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #334155;
    padding: 10px 22px;
    border-radius: 999px;
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.25s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
    cursor: pointer;
}
.stores-filter-btn:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #0066cc;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 102, 204, 0.12);
}
.stores-filter-btn.active {
    background: #0066cc;
    border-color: #0066cc;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(0, 102, 204, 0.35);
    transform: translateY(-2px);
}

/* Region Section Header */
.stores-region-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e2e8f0;
}
.stores-region-header .region-icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
.stores-region-header h4 {
    margin: 0;
    font-weight: 800;
    color: #1e293b;
    font-size: 1.15rem;
}
.stores-region-header .region-count {
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 600;
}

/* Store Cards */
.store-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 0;
    height: 100%;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    position: relative;
    overflow: hidden;
}
.store-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 30px rgba(0, 102, 204, 0.12);
    border-color: #93c5fd;
}

.store-card-top {
    background: linear-gradient(135deg, #f0f7ff 0%, #e8f4fd 100%);
    padding: 20px 22px 16px 22px;
    border-bottom: 1px solid #e0f2fe;
}

.store-card-body {
    padding: 18px 22px 20px 22px;
}

.store-card .store-name {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 4px;
}

.store-card .store-badge-flagship {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35);
}

.store-info-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 0.88rem;
    color: #475569;
    line-height: 1.5;
}
.store-info-item:last-child {
    margin-bottom: 0;
}
.store-info-item .info-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}
.store-info-item a {
    color: #0066cc;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
}
.store-info-item a:hover {
    color: #0047a3;
    text-decoration: underline;
}

.store-card .btn-directions {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #0066cc;
    color: #ffffff;
    border: none;
    padding: 9px 18px;
    border-radius: 12px;
    font-size: 0.82rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0, 102, 204, 0.25);
}
.store-card .btn-directions:hover {
    background: #0052a3;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 102, 204, 0.35);
}

/* Feature Cards */
.stores-feature-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 28px 24px;
    height: 100%;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    text-align: center;
    position: relative;
    overflow: hidden;
}
.stores-feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #0066cc, #0284c7, #38bdf8);
    opacity: 0;
    transition: opacity 0.3s ease;
}
.stores-feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 30px rgba(0, 102, 204, 0.12);
    border-color: #93c5fd;
}
.stores-feature-card:hover::before {
    opacity: 1;
}
.stores-feature-icon {
    width: 60px;
    height: 60px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin: 0 auto 18px auto;
}

/* Contact CTA */
.stores-cta-section {
    background: linear-gradient(135deg, #04172e 0%, #0a3d62 45%, #0284c7 100%);
    border-radius: 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 12px 36px rgba(10, 61, 98, 0.22);
}
.stores-cta-section::before {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.2) 0%, transparent 70%);
    top: -150px;
    right: -100px;
    border-radius: 50%;
    pointer-events: none;
}

/* Animations */
.store-region-group {
    transition: opacity 0.3s ease, max-height 0.4s ease;
}
.store-region-group.hidden {
    display: none;
}

/* Responsive tweaks */
@media (max-width: 767.98px) {
    .stores-hero-section {
        border-radius: 20px;
    }
    .stores-stat-number {
        font-size: 1.3rem;
    }
    .stores-stat-label {
        font-size: 0.68rem;
    }
    .store-card-top {
        padding: 16px 18px 14px 18px;
    }
    .store-card-body {
        padding: 14px 18px 16px 18px;
    }
    .stores-cta-section {
        border-radius: 18px;
    }
}

/* Dark Mode Enhancements for Stores */
[data-bs-theme="dark"] .stores-filter-btn {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #cbd5e1 !important;
}
[data-bs-theme="dark"] .stores-filter-btn:hover {
    background: #1a2a4a !important;
    border-color: #38bdf8 !important;
    color: #ffffff !important;
}
[data-bs-theme="dark"] .stores-region-header {
    border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .stores-region-header h4 {
    color: #f1f5f9 !important;
}
[data-bs-theme="dark"] .store-card {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35) !important;
}
[data-bs-theme="dark"] .store-card:hover {
    border-color: rgba(56, 189, 248, 0.4) !important;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5) !important;
}
[data-bs-theme="dark"] .stores-feature-card {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35) !important;
}
[data-bs-theme="dark"] .store-card-top {
    border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}

</style>

<div class="container my-3 pb-5">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small bg-white px-3 py-2 rounded-3 border shadow-sm mb-0">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-primary"><i class="fa-solid fa-house me-1"></i>Trang chủ</a></li>
            <li class="breadcrumb-item active text-secondary fw-semibold" aria-current="page">Hệ thống cửa hàng</li>
        </ol>
    </nav>

    <!-- Hero Section -->
    <div class="stores-hero-section p-4 p-md-5 mb-4">
        <div class="stores-hero-bg-glow"></div>
        <div class="stores-hero-bg-glow-2"></div>
        <div class="row align-items-center g-4 position-relative" style="z-index: 2;">
            <div class="col-lg-7">
                <span class="badge bg-white text-primary px-3 py-2 fw-bold rounded-pill mb-3 shadow-sm d-inline-flex align-items-center">
                    <i class="fa-solid fa-location-dot text-primary me-2"></i> HỆ THỐNG CỬA HÀNG CHÍNH HÃNG 2026
                </span>
                <h1 class="display-6 fw-bold mb-3 text-white">
                    Hệ Thống Cửa Hàng V-Phone
                </h1>
                <p class="fs-6 text-light opacity-90 mb-4 lh-base" style="max-width: 600px;">
                    15+ chi nhánh trải dài khắp Việt Nam, sẵn sàng phục vụ bạn. Trải nghiệm trực tiếp các sản phẩm smartphone mới nhất cùng đội ngũ tư vấn chuyên nghiệp.
                </p>

                <div class="stores-stats-bar">
                    <div class="row g-2 align-items-center">
                        <div class="col-4 stores-stat-item">
                            <div class="stores-stat-number">15+</div>
                            <div class="stores-stat-label">Cửa hàng</div>
                        </div>
                        <div class="col-4 stores-stat-item">
                            <div class="stores-stat-number">3</div>
                            <div class="stores-stat-label">Miền Bắc-Trung-Nam</div>
                        </div>
                        <div class="col-4 stores-stat-item">
                            <div class="stores-stat-number">8-21:30</div>
                            <div class="stores-stat-label">Giờ mở cửa</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 text-center d-none d-lg-block">
                <div class="stores-hero-map-wrap">
                    <div class="position-relative d-inline-block">
                        <i class="fa-solid fa-map-location-dot stores-map-icon" style="font-size: 200px; color: rgba(255,255,255,0.08);"></i>
                        <div class="position-absolute" style="top: 22%; left: 30%; animation: pulse-dot 2s infinite;">
                            <span class="d-flex align-items-center justify-content-center bg-warning rounded-circle shadow" style="width: 18px; height: 18px;">
                                <i class="fa-solid fa-circle text-white" style="font-size: 6px;"></i>
                            </span>
                        </div>
                        <div class="position-absolute" style="top: 50%; left: 55%; animation: pulse-dot 2s infinite 0.5s;">
                            <span class="d-flex align-items-center justify-content-center bg-info rounded-circle shadow" style="width: 14px; height: 14px;">
                                <i class="fa-solid fa-circle text-white" style="font-size: 5px;"></i>
                            </span>
                        </div>
                        <div class="position-absolute" style="top: 72%; left: 40%; animation: pulse-dot 2s infinite 1s;">
                            <span class="d-flex align-items-center justify-content-center bg-success rounded-circle shadow" style="width: 16px; height: 16px;">
                                <i class="fa-solid fa-circle text-white" style="font-size: 5px;"></i>
                            </span>
                        </div>
                        <style>
                            @keyframes pulse-dot {
                                0%, 100% { transform: scale(1); opacity: 1; }
                                50% { transform: scale(1.4); opacity: 0.7; }
                            }
                        </style>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Region Filter Navigation -->
    <div class="stores-filter-nav mb-4" id="storeFilterNav">
        <button class="stores-filter-btn active" data-filter="all" onclick="filterStores('all', this)">
            <i class="fa-solid fa-store me-1"></i> Tất cả <span class="badge bg-primary bg-opacity-20 text-white rounded-pill ms-1" style="font-size: 0.72rem;">15</span>
        </button>
        <button class="stores-filter-btn" data-filter="bac" onclick="filterStores('bac', this)">
            <i class="fa-solid fa-mountain-sun me-2 text-danger"></i> Miền Bắc <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill ms-1" style="font-size: 0.72rem;">5</span>
        </button>
        <button class="stores-filter-btn" data-filter="trung" onclick="filterStores('trung', this)">
            <i class="fa-solid fa-umbrella-beach me-2 text-info"></i> Miền Trung <span class="badge bg-info bg-opacity-10 text-info rounded-pill ms-1" style="font-size: 0.72rem;">5</span>
        </button>
        <button class="stores-filter-btn" data-filter="nam" onclick="filterStores('nam', this)">
            <i class="fa-solid fa-city me-2 text-success"></i> Miền Nam <span class="badge bg-success bg-opacity-10 text-success rounded-pill ms-1" style="font-size: 0.72rem;">5</span>
        </button>
    </div>

    <!-- ============================================ -->
    <!-- MIỀN BẮC -->
    <!-- ============================================ -->
    <div class="store-region-group mb-5" data-region="bac" id="region-bac">
        <div class="stores-region-header">
            <div class="region-icon bg-danger bg-opacity-10 text-danger">
                <i class="fa-solid fa-mountain-sun"></i>
            </div>
            <div>
                <h4>Miền Bắc</h4>
                <div class="region-count">5 cửa hàng</div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Store 1: Hoàn Kiếm -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Hoàn Kiếm</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>56 Tràng Tiền, Hoàn Kiếm, Hà Nội</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02438251234">024 3825 1234</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:00 - 21:30 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/56+Tràng+Tiền,+Hoàn+Kiếm,+Hà+Nội" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store 2: Cầu Giấy -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Cầu Giấy</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>148 Xuân Thủy, Cầu Giấy, Hà Nội</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02437955678">024 3795 5678</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:00 - 21:30 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/148+Xuân+Thủy,+Cầu+Giấy,+Hà+Nội" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store 3: Hải Phòng -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Hải Phòng</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>205 Lạch Tray, Ngô Quyền, Hải Phòng</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02253849012">0225 384 9012</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:00 - 21:00 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/205+Lạch+Tray,+Ngô+Quyền,+Hải+Phòng" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store 4: Bắc Ninh -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Bắc Ninh</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>88 Lý Thái Tổ, TP. Bắc Ninh</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02223823456">0222 382 3456</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:30 - 21:00 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/88+Lý+Thái+Tổ,+TP.+Bắc+Ninh" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store 5: Thanh Hóa -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Thanh Hóa</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>125 Phan Chu Trinh, TP. Thanh Hóa</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02373757890">0237 375 7890</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:30 - 21:00 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/125+Phan+Chu+Trinh,+TP.+Thanh+Hóa" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- MIỀN TRUNG -->
    <!-- ============================================ -->
    <div class="store-region-group mb-5" data-region="trung" id="region-trung">
        <div class="stores-region-header">
            <div class="region-icon bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-umbrella-beach"></i>
            </div>
            <div>
                <h4>Miền Trung</h4>
                <div class="region-count">5 cửa hàng</div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Store 6: Đà Nẵng Center -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Đà Nẵng Center</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>78 Nguyễn Văn Linh, Hải Châu, Đà Nẵng</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02363621234">0236 362 1234</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:00 - 21:30 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/78+Nguyễn+Văn+Linh,+Hải+Châu,+Đà+Nẵng" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store 7: Huế -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Huế</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>46 Trần Hưng Đạo, TP. Huế</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02343825678">0234 382 5678</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:30 - 21:00 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/46+Trần+Hưng+Đạo,+TP.+Huế" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store 8: Nha Trang -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Nha Trang</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>152 Trần Phú, Nha Trang, Khánh Hòa</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02583529012">0258 352 9012</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:00 - 21:30 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/152+Trần+Phú,+Nha+Trang,+Khánh+Hòa" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store 9: Quy Nhơn -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Quy Nhơn</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>234 Trần Hưng Đạo, TP. Quy Nhơn</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02563813456">0256 381 3456</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:30 - 21:00 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/234+Trần+Hưng+Đạo,+TP.+Quy+Nhơn" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store 10: Đà Lạt -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Đà Lạt</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>67 Phan Đình Phùng, TP. Đà Lạt</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02633827890">0263 382 7890</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:30 - 21:00 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/67+Phan+Đình+Phùng,+TP.+Đà+Lạt" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- MIỀN NAM -->
    <!-- ============================================ -->
    <div class="store-region-group mb-5" data-region="nam" id="region-nam">
        <div class="stores-region-header">
            <div class="region-icon bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-city"></i>
            </div>
            <div>
                <h4>Miền Nam</h4>
                <div class="region-count">5 cửa hàng</div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Store 11: Nguyễn Trãi (Flagship) -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card" style="border-color: #fbbf24; border-width: 2px;">
                    <div class="store-card-top" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-bottom-color: #fde68a;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="store-name">V-Phone Nguyễn Trãi</div>
                            <span class="store-badge-flagship">
                                <i class="fa-solid fa-crown"></i> FLAGSHIP
                            </span>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>200 Nguyễn Trãi, Q.5, TP.HCM</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02838591234">028 3859 1234</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:00 - 22:00 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/200+Nguyễn+Trãi,+Quận+5,+TP.HCM" target="_blank" rel="noopener" class="btn-directions" style="background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store 12: Quận 7 -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Quận 7</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>1058 Nguyễn Văn Linh, Q.7, TP.HCM</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02854125678">028 5412 5678</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:00 - 21:30 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/1058+Nguyễn+Văn+Linh,+Quận+7,+TP.HCM" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store 13: Thủ Đức -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Thủ Đức</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>356 Võ Văn Ngân, TP. Thủ Đức, TP.HCM</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02837269012">028 3726 9012</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:00 - 21:30 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/356+Võ+Văn+Ngân,+TP.+Thủ+Đức,+TP.HCM" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store 14: Cần Thơ -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Cần Thơ</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>89 Nguyễn Văn Cừ, Ninh Kiều, Cần Thơ</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02923813456">0292 381 3456</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:30 - 21:00 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/89+Nguyễn+Văn+Cừ,+Ninh+Kiều,+Cần+Thơ" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store 15: Biên Hòa -->
            <div class="col-lg-4 col-md-6">
                <div class="store-card">
                    <div class="store-card-top">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="store-name">V-Phone Biên Hòa</div>
                        </div>
                    </div>
                    <div class="store-card-body">
                        <div class="store-info-item">
                            <div class="info-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span>167 Phạm Văn Thuận, TP. Biên Hòa, Đồng Nai</span>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <a href="tel:02513827890">0251 382 7890</a>
                        </div>
                        <div class="store-info-item">
                            <div class="info-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <span>8:30 - 21:00 (Thứ 2 - CN)</span>
                        </div>
                        <div class="mt-3">
                            <a href="https://www.google.com/maps/search/167+Phạm+Văn+Thuận,+TP.+Biên+Hòa,+Đồng+Nai" target="_blank" rel="noopener" class="btn-directions">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- STORE FEATURES SECTION -->
    <!-- ============================================ -->
    <div class="mb-5">
        <div class="text-center mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold rounded-pill px-3 py-1 mb-2">TẠI SAO CHỌN V-PHONE STORE</span>
            <h3 class="fw-bold text-dark mb-1">Trải Nghiệm Mua Sắm Đẳng Cấp</h3>
            <p class="text-secondary small mb-0">Dịch vụ đặc quyền chỉ có tại hệ thống cửa hàng V-Phone trên toàn quốc</p>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="stores-feature-card">
                    <div class="stores-feature-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Trải Nghiệm Trực Tiếp</h5>
                    <p class="text-secondary small mb-0 lh-base">
                        Cầm thử tất cả smartphone trước khi mua. So sánh từng mẫu máy thực tế để chọn sản phẩm phù hợp nhất với nhu cầu của bạn.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="stores-feature-card">
                    <div class="stores-feature-icon bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Tư Vấn Chuyên Nghiệp</h5>
                    <p class="text-secondary small mb-0 lh-base">
                        Đội ngũ nhân viên được đào tạo bài bản, am hiểu sản phẩm. Tư vấn nhiệt tình, không áp lực mua hàng.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="stores-feature-card">
                    <div class="stores-feature-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Bảo Hành Tại Chỗ</h5>
                    <p class="text-secondary small mb-0 lh-base">
                        Sửa chữa nhanh trong vòng 30 phút cho các lỗi phổ biến. Kỹ thuật viên chuyên nghiệp phục vụ tận nơi.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="stores-feature-card">
                    <div class="stores-feature-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Giao Hàng Miễn Phí</h5>
                    <p class="text-secondary small mb-0 lh-base">
                        Free ship bán kính 10km quanh cửa hàng. Đặt online nhận hàng trong 1-2 giờ cho khu vực nội thành.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- CONTACT CTA SECTION -->
    <!-- ============================================ -->
    <div class="stores-cta-section p-4 p-md-5 text-center text-white position-relative">
        <div class="position-relative" style="z-index: 2;">
            <div class="rounded-circle bg-white shadow d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 68px; height: 68px;">
                <i class="fa-solid fa-headset fs-2 text-primary"></i>
            </div>
            <h3 class="fw-bold mb-2">Cần Hỗ Trợ Tìm Cửa Hàng?</h3>
            <p class="opacity-85 mb-4 mx-auto" style="max-width: 520px;">
                Liên hệ tổng đài miễn phí để được tư vấn chi nhánh gần nhất và hỗ trợ đặt lịch hẹn trải nghiệm sản phẩm.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="tel:18006868" class="btn btn-light btn-lg rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-phone-volume text-primary"></i>
                    <span class="text-primary">1800 6868</span>
                    <span class="badge bg-success rounded-pill small ms-1" style="font-size: 0.65rem;">Miễn phí</span>
                </a>
                <a href="index.php?page=about" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-semibold d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-building me-1"></i>
                    <span>Giới thiệu V-Phone</span>
                </a>
            </div>
        </div>
    </div>

</div>

<!-- Region Filter JavaScript -->
<script>
function filterStores(region, btnElement) {
    // Update active button state
    document.querySelectorAll('.stores-filter-btn').forEach(function(btn) {
        btn.classList.remove('active');
    });
    btnElement.classList.add('active');

    // Show/hide region groups
    var groups = document.querySelectorAll('.store-region-group');
    groups.forEach(function(group) {
        if (region === 'all') {
            group.classList.remove('hidden');
            group.style.display = '';
        } else {
            if (group.getAttribute('data-region') === region) {
                group.classList.remove('hidden');
                group.style.display = '';
            } else {
                group.classList.add('hidden');
                group.style.display = 'none';
            }
        }
    });

    // Smooth scroll to the first visible region
    if (region !== 'all') {
        var targetRegion = document.getElementById('region-' + region);
        if (targetRegion) {
            setTimeout(function() {
                targetRegion.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }
}
</script>

<?php require_once 'app/views/includes/footer.php'; ?>
