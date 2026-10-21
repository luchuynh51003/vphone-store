<?php
$pageTitle = 'Giới Thiệu V-Phone Store - Hệ Thống Smartphone Chính Hãng';
require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<style>
/* Custom scoped styles for About Page */
html {
    scroll-behavior: smooth;
}
[id] {
    scroll-margin-top: 80px;
}

/* Hero Section */
.about-hero-section {
    background: linear-gradient(135deg, #04172e 0%, #0a3d62 45%, #0284c7 100%);
    border-radius: 28px;
    position: relative;
    overflow: hidden;
    color: #ffffff;
    box-shadow: 0 12px 36px rgba(10, 61, 98, 0.22);
}
.about-hero-bg-glow {
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
.about-hero-bg-glow-2 {
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
.about-stats-bar {
    background: rgba(0, 0, 0, 0.25);
    border-radius: 20px;
    padding: 16px 20px;
    border: 1px solid rgba(255, 255, 255, 0.12);
}
.about-stat-item {
    text-align: center;
    border-right: 1px solid rgba(255, 255, 255, 0.15);
}
.about-stat-item:last-child {
    border-right: none;
}
.about-stat-number {
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1.1;
    color: #facc15;
}
.about-stat-label {
    font-size: 0.76rem;
    color: rgba(255, 255, 255, 0.85);
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* About Story Section */
.about-story-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    box-shadow: 0 8px 25px rgba(2, 132, 199, 0.08);
    overflow: hidden;
}
.about-story-accent {
    width: 60px;
    height: 4px;
    background: linear-gradient(90deg, #0066cc, #0284c7);
    border-radius: 2px;
    margin-bottom: 16px;
}

/* Vision & Mission */
.about-vm-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 32px;
    height: 100%;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}
.about-vm-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 30px rgba(0, 102, 204, 0.12);
    border-color: #93c5fd;
}
.about-vm-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}
.about-vm-card.vision::before {
    background: linear-gradient(90deg, #0066cc, #38bdf8);
}
.about-vm-card.mission::before {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
}
.about-vm-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 18px;
}

/* Core Values */
.about-value-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 28px;
    height: 100%;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    position: relative;
    overflow: hidden;
}
.about-value-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 30px rgba(0, 102, 204, 0.12);
    border-color: #93c5fd;
}
.about-value-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 16px;
}

/* Team Section */
.about-team-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 28px 24px;
    text-align: center;
    height: 100%;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
}
.about-team-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 30px rgba(0, 102, 204, 0.12);
    border-color: #93c5fd;
}
.about-team-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin: 0 auto 16px auto;
    box-shadow: 0 6px 18px rgba(0, 102, 204, 0.2);
}

/* Timeline Milestones */
.about-timeline {
    position: relative;
    padding-left: 36px;
}
.about-timeline::before {
    content: '';
    position: absolute;
    left: 14px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(180deg, #0066cc 0%, #38bdf8 50%, #0284c7 100%);
    border-radius: 3px;
}
.about-timeline-item {
    position: relative;
    margin-bottom: 28px;
    padding-left: 24px;
}
.about-timeline-item:last-child {
    margin-bottom: 0;
}
.about-timeline-dot {
    position: absolute;
    left: -28px;
    top: 4px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0066cc 0%, #0284c7 100%);
    border: 3px solid #ffffff;
    box-shadow: 0 4px 12px rgba(0, 102, 204, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
}
.about-timeline-dot i {
    color: #ffffff;
    font-size: 10px;
}
.about-timeline-year {
    font-size: 0.8rem;
    font-weight: 800;
    color: #0066cc;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}
.about-timeline-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 16px 20px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.about-timeline-card:hover {
    transform: translateX(6px);
    box-shadow: 0 8px 20px rgba(0, 102, 204, 0.1);
}

/* Partners Section */
.about-partner-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100px;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}
.about-partner-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px rgba(0, 102, 204, 0.1);
    border-color: #93c5fd;
}
.about-partner-card img {
    max-height: 48px;
    max-width: 100%;
    object-fit: contain;
    filter: grayscale(40%);
    opacity: 0.85;
    transition: filter 0.3s ease, opacity 0.3s ease;
}
.about-partner-card:hover img {
    filter: grayscale(0%);
    opacity: 1;
}

/* CTA Section */
.about-cta-section {
    background: linear-gradient(135deg, #04172e 0%, #0a3d62 45%, #0284c7 100%);
    border-radius: 24px;
    position: relative;
    overflow: hidden;
    color: #ffffff;
    box-shadow: 0 12px 36px rgba(10, 61, 98, 0.22);
}
.about-cta-glow {
    position: absolute;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.25) 0%, transparent 70%);
    top: -100px;
    right: -50px;
    border-radius: 50%;
    pointer-events: none;
}

/* Soft Badges */
.badge-soft-blue { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-weight: 700; }
.badge-soft-green { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-weight: 700; }
.badge-soft-amber { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-weight: 700; }
.badge-soft-purple { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; font-weight: 700; }

/* Dark Mode Enhancements for About */
[data-bs-theme="dark"] .about-story-card,
[data-bs-theme="dark"] .about-vm-card,
[data-bs-theme="dark"] .about-value-card,
[data-bs-theme="dark"] .about-team-card {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.09) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35) !important;
}
[data-bs-theme="dark"] .about-stats-bar {
    background: rgba(14, 23, 42, 0.8) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
[data-bs-theme="dark"] div[style*="linear-gradient(135deg, #eff6ff 0%, #e0f2fe 100%)"] {
    background: linear-gradient(135deg, #0f1d38 0%, #13274c 100%) !important;
    border-color: rgba(56, 189, 248, 0.25) !important;
}

</style>

<div class="container my-3 pb-5">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small bg-white px-3 py-2 rounded-3 border shadow-sm mb-0">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-primary"><i class="fa-solid fa-house me-1"></i>Trang chủ</a></li>
            <li class="breadcrumb-item active text-secondary fw-semibold" aria-current="page">Giới thiệu về V-Phone</li>
        </ol>
    </nav>

    <!-- ==================== HERO SECTION ==================== -->
    <div class="about-hero-section p-4 p-md-5 mb-4">
        <div class="about-hero-bg-glow"></div>
        <div class="about-hero-bg-glow-2"></div>
        <div class="position-relative text-center" style="z-index: 2;">
            <span class="badge bg-white text-primary px-3 py-2 fw-bold rounded-pill mb-3 shadow-sm d-inline-flex align-items-center">
                <i class="fa-solid fa-building text-primary me-2"></i> THÀNH LẬP NĂM 2020
            </span>
            <h1 class="display-5 fw-bold mb-3 text-white">
                <i class="fa-solid fa-mobile-screen-button me-2 opacity-75"></i>V-Phone Store
            </h1>
            <p class="fs-5 text-light opacity-90 mb-4 lh-base mx-auto" style="max-width: 700px;">
                Hệ thống phân phối smartphone chính hãng hàng đầu Việt Nam
            </p>

            <div class="about-stats-bar mx-auto" style="max-width: 680px;">
                <div class="row g-2 align-items-center">
                    <div class="col-3 about-stat-item">
                        <div class="about-stat-number">6+</div>
                        <div class="about-stat-label">Năm kinh nghiệm</div>
                    </div>
                    <div class="col-3 about-stat-item">
                        <div class="about-stat-number">50K+</div>
                        <div class="about-stat-label">Khách hàng tin tưởng</div>
                    </div>
                    <div class="col-3 about-stat-item">
                        <div class="about-stat-number">15+</div>
                        <div class="about-stat-label">Cửa hàng toàn quốc</div>
                    </div>
                    <div class="col-3 about-stat-item">
                        <div class="about-stat-number">99.8%</div>
                        <div class="about-stat-label">Phản hồi tích cực</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== GIỚI THIỆU V-PHONE ==================== -->
    <div id="gioi-thieu" class="about-story-card p-4 p-md-5 mb-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold rounded-pill px-3 py-1 mb-3">VỀ CHÚNG TÔI</span>
                <div class="about-story-accent"></div>
                <h3 class="fw-bold text-dark mb-3">Giới Thiệu V-Phone Store</h3>
                <p class="text-secondary lh-lg mb-3">
                    <strong class="text-dark">V-Phone Store</strong> ra đời từ niềm đam mê công nghệ và khát vọng mang đến cho người Việt trải nghiệm smartphone chính hãng với chất lượng dịch vụ vượt trội. Chúng tôi hiểu rằng điện thoại không chỉ là một thiết bị — mà là người bạn đồng hành trong cuộc sống hàng ngày.
                </p>
                <p class="text-secondary lh-lg mb-4">
                    Với sứ mệnh trở thành cầu nối tin cậy giữa các thương hiệu công nghệ hàng đầu thế giới và người tiêu dùng Việt Nam, V-Phone Store cam kết mang đến sản phẩm <strong class="text-dark">100% chính hãng</strong>, giá cả minh bạch và dịch vụ hậu mãi tận tâm nhất.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge badge-soft-blue rounded-pill px-3 py-2"><i class="fa-solid fa-check me-1"></i>Apple Authorized</span>
                    <span class="badge badge-soft-green rounded-pill px-3 py-2"><i class="fa-solid fa-check me-1"></i>Samsung Partner</span>
                    <span class="badge badge-soft-amber rounded-pill px-3 py-2"><i class="fa-solid fa-check me-1"></i>Xiaomi Authorized</span>
                    <span class="badge badge-soft-purple rounded-pill px-3 py-2"><i class="fa-solid fa-check me-1"></i>OPPO Dealer</span>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="rounded-4 p-4 text-center" style="background: linear-gradient(135deg, #eff6ff 0%, #e0f2fe 100%); border: 1px solid #bfdbfe;">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                            <i class="fa-solid fa-store fs-1"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-primary mb-2">V-Phone Store</h4>
                    <p class="text-secondary small mb-3">Hệ thống phân phối smartphone chính hãng</p>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="rounded-3 bg-white p-3 border shadow-sm">
                                <div class="fw-bold text-primary fs-4">6+</div>
                                <div class="text-secondary small">Năm kinh nghiệm</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 bg-white p-3 border shadow-sm">
                                <div class="fw-bold text-success fs-4">50K+</div>
                                <div class="text-secondary small">Khách hàng</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 bg-white p-3 border shadow-sm">
                                <div class="fw-bold text-warning fs-4">15+</div>
                                <div class="text-secondary small">Cửa hàng</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 bg-white p-3 border shadow-sm">
                                <div class="fw-bold text-info fs-4">99.8%</div>
                                <div class="text-secondary small">Hài lòng</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TẦM NHÌN & SỨ MỆNH ==================== -->
    <div id="tam-nhin" class="mb-5">
        <div class="text-center mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold rounded-pill px-3 py-1 mb-2">ĐỊNH HƯỚNG PHÁT TRIỂN</span>
            <h3 class="fw-bold text-dark mb-1">Tầm Nhìn & Sứ Mệnh</h3>
            <p class="text-secondary small mb-0">Kim chỉ nam dẫn dắt mọi hoạt động của V-Phone Store</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="about-vm-card vision">
                    <div class="about-vm-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                    <span class="badge badge-soft-blue rounded-pill px-3 py-1 mb-3">TẦM NHÌN</span>
                    <h4 class="fw-bold text-dark mb-3">Dẫn Đầu Thị Trường Bán Lẻ Smartphone</h4>
                    <p class="text-secondary lh-lg mb-0">
                        Trở thành hệ thống bán lẻ smartphone <strong class="text-dark">#1 Việt Nam</strong> về chất lượng dịch vụ, nơi mỗi khách hàng đều được trải nghiệm mua sắm đẳng cấp, minh bạch và tin cậy tuyệt đối.
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="about-vm-card mission">
                    <div class="about-vm-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-rocket"></i>
                    </div>
                    <span class="badge badge-soft-amber rounded-pill px-3 py-1 mb-3">SỨ MỆNH</span>
                    <h4 class="fw-bold text-dark mb-3">Kiến Tạo Trải Nghiệm Tối Ưu</h4>
                    <p class="text-secondary lh-lg mb-0">
                        Kiến tạo trải nghiệm mua sắm smartphone tối ưu — từ tư vấn chuyên nghiệp, sản phẩm chính hãng, giá cả hợp lý đến dịch vụ hậu mãi toàn diện, giúp khách hàng luôn an tâm và hài lòng.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== GIÁ TRỊ CỐT LÕI ==================== -->
    <div id="gia-tri" class="mb-5">
        <div class="text-center mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold rounded-pill px-3 py-1 mb-2">NỀN TẢNG VỮNG CHẮC</span>
            <h3 class="fw-bold text-dark mb-1">Giá Trị Cốt Lõi</h3>
            <p class="text-secondary small mb-0">4 cam kết vàng làm nên thương hiệu V-Phone Store</p>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="about-value-card">
                    <div class="about-value-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                    <span class="badge badge-soft-blue rounded-pill px-2 py-1 small mb-2">Cam kết #1</span>
                    <h5 class="fw-bold text-dark mb-2">Chính Hãng 100%</h5>
                    <p class="text-secondary small mb-0 lh-base">
                        Cam kết chỉ phân phối sản phẩm chính hãng từ Apple, Samsung, Xiaomi, OPPO và các thương hiệu uy tín hàng đầu thế giới.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="about-value-card">
                    <div class="about-value-icon bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <span class="badge badge-soft-green rounded-pill px-2 py-1 small mb-2">Cam kết #2</span>
                    <h5 class="fw-bold text-dark mb-2">Dịch Vụ Tận Tâm</h5>
                    <p class="text-secondary small mb-0 lh-base">
                        Đội ngũ tư vấn chuyên nghiệp, nhiệt huyết, hỗ trợ khách hàng 24/7 qua mọi kênh liên lạc — luôn sẵn sàng phục vụ.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="about-value-card">
                    <div class="about-value-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <span class="badge badge-soft-amber rounded-pill px-2 py-1 small mb-2">Cam kết #3</span>
                    <h5 class="fw-bold text-dark mb-2">Giá Cạnh Tranh</h5>
                    <p class="text-secondary small mb-0 lh-base">
                        Cam kết giá tốt nhất thị trường, hoàn tiền chênh lệch nếu khách hàng tìm thấy nơi bán rẻ hơn với cùng sản phẩm chính hãng.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="about-value-card">
                    <div class="about-value-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <span class="badge badge-soft-purple rounded-pill px-2 py-1 small mb-2">Cam kết #4</span>
                    <h5 class="fw-bold text-dark mb-2">Bảo Hành Toàn Diện</h5>
                    <p class="text-secondary small mb-0 lh-base">
                        Bảo hành 12 tháng chính hãng, 1 đổi 1 trong 30 ngày đầu tiên nếu sản phẩm gặp lỗi từ nhà sản xuất.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== ĐỘI NGŨ LÃNH ĐẠO ==================== -->
    <div id="doi-ngu" class="mb-5">
        <div class="text-center mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold rounded-pill px-3 py-1 mb-2">CON NGƯỜI V-PHONE</span>
            <h3 class="fw-bold text-dark mb-1">Đội Ngũ Lãnh Đạo</h3>
            <p class="text-secondary small mb-0">Những người kiến tạo nên hệ thống V-Phone Store</p>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="about-team-card">
                    <div class="about-team-avatar bg-primary text-white">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Huỳnh Bá Lực</h5>
                    <span class="badge badge-soft-blue rounded-pill px-3 py-1 small mb-3">CEO & Founder</span>
                    <p class="text-secondary small mb-0 lh-base">
                        Người sáng lập V-Phone Store, định hướng chiến lược phát triển toàn hệ thống.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="about-team-card">
                    <div class="about-team-avatar bg-info text-white">
                        <i class="fa-solid fa-code"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Võ Minh Hiếu</h5>
                    <span class="badge badge-soft-green rounded-pill px-3 py-1 small mb-3">CTO & Co-Founder</span>
                    <p class="text-secondary small mb-0 lh-base">
                        Dẫn dắt đội ngũ công nghệ, xây dựng nền tảng bán hàng trực tuyến hiện đại.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="about-team-card">
                    <div class="about-team-avatar bg-success text-white">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Nguyễn Thị Mai</h5>
                    <span class="badge badge-soft-amber rounded-pill px-3 py-1 small mb-3">COO</span>
                    <p class="text-secondary small mb-0 lh-base">
                        Quản lý vận hành chuỗi cửa hàng, đảm bảo chất lượng dịch vụ đồng nhất.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="about-team-card">
                    <div class="about-team-avatar bg-warning text-white">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Trần Văn Đức</h5>
                    <span class="badge badge-soft-purple rounded-pill px-3 py-1 small mb-3">CMO</span>
                    <p class="text-secondary small mb-0 lh-base">
                        Phụ trách chiến lược marketing, xây dựng thương hiệu V-Phone trên toàn quốc.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== THÀNH TỰU & CỘT MỐC ==================== -->
    <div id="thanh-tuu" class="card border-0 rounded-4 shadow-sm mb-5 bg-white p-4 p-md-5">
        <div class="row align-items-start g-4">
            <div class="col-lg-4">
                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold rounded-pill px-3 py-1 mb-3">HÀNH TRÌNH PHÁT TRIỂN</span>
                <h3 class="fw-bold text-dark mb-3">Thành Tựu & Cột Mốc</h3>
                <p class="text-secondary lh-lg mb-4">
                    Từ một cửa hàng nhỏ với niềm đam mê công nghệ, V-Phone Store đã không ngừng lớn mạnh và trở thành hệ thống phân phối smartphone đáng tin cậy trên toàn quốc.
                </p>
                <div class="rounded-3 p-3 border" style="background: linear-gradient(135deg, #eff6ff 0%, #e0f2fe 100%); border-color: #bfdbfe !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                            <i class="fa-solid fa-trophy fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark small">Top 10 Nhà Bán Lẻ Uy Tín</div>
                            <div class="text-secondary small">Bình chọn bởi người tiêu dùng 2024</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="about-timeline">
                    <div class="about-timeline-item">
                        <div class="about-timeline-dot"><i class="fa-solid fa-star"></i></div>
                        <div class="about-timeline-year">2020</div>
                        <div class="about-timeline-card">
                            <h6 class="fw-bold text-dark mb-1"><i class="fa-solid fa-flag text-primary me-2"></i>Thành lập V-Phone Store</h6>
                            <p class="text-secondary small mb-0">Khởi đầu hành trình với cửa hàng đầu tiên tại TP. Hồ Chí Minh, chuyên phân phối smartphone chính hãng.</p>
                        </div>
                    </div>

                    <div class="about-timeline-item">
                        <div class="about-timeline-dot"><i class="fa-solid fa-star"></i></div>
                        <div class="about-timeline-year">2021</div>
                        <div class="about-timeline-card">
                            <h6 class="fw-bold text-dark mb-1"><i class="fa-solid fa-code-branch text-success me-2"></i>Mở rộng 5 chi nhánh</h6>
                            <p class="text-secondary small mb-0">Nhân rộng mô hình kinh doanh thành công, mở thêm 5 chi nhánh tại các thành phố lớn.</p>
                        </div>
                    </div>

                    <div class="about-timeline-item">
                        <div class="about-timeline-dot"><i class="fa-solid fa-star"></i></div>
                        <div class="about-timeline-year">2022</div>
                        <div class="about-timeline-card">
                            <h6 class="fw-bold text-dark mb-1"><i class="fa-solid fa-users text-info me-2"></i>Đạt 10,000 khách hàng</h6>
                            <p class="text-secondary small mb-0">Cột mốc 10,000 khách hàng tin tưởng lựa chọn V-Phone Store, khẳng định chất lượng dịch vụ.</p>
                        </div>
                    </div>

                    <div class="about-timeline-item">
                        <div class="about-timeline-dot"><i class="fa-solid fa-star"></i></div>
                        <div class="about-timeline-year">2023</div>
                        <div class="about-timeline-card">
                            <h6 class="fw-bold text-dark mb-1"><i class="fa-solid fa-globe text-warning me-2"></i>Ra mắt nền tảng bán hàng online</h6>
                            <p class="text-secondary small mb-0">Triển khai website thương mại điện tử, mở rộng kênh bán hàng trực tuyến phục vụ khách hàng toàn quốc.</p>
                        </div>
                    </div>

                    <div class="about-timeline-item">
                        <div class="about-timeline-dot"><i class="fa-solid fa-star"></i></div>
                        <div class="about-timeline-year">2024</div>
                        <div class="about-timeline-card">
                            <h6 class="fw-bold text-dark mb-1"><i class="fa-solid fa-award text-danger me-2"></i>Top 10 nhà bán lẻ smartphone uy tín</h6>
                            <p class="text-secondary small mb-0">Vinh dự được người tiêu dùng bình chọn vào Top 10 nhà bán lẻ smartphone uy tín nhất Việt Nam.</p>
                        </div>
                    </div>

                    <div class="about-timeline-item">
                        <div class="about-timeline-dot"><i class="fa-solid fa-star"></i></div>
                        <div class="about-timeline-year">2025</div>
                        <div class="about-timeline-card">
                            <h6 class="fw-bold text-dark mb-1"><i class="fa-solid fa-map-location-dot text-primary me-2"></i>Mở rộng 15 chi nhánh toàn quốc</h6>
                            <p class="text-secondary small mb-0">Phủ sóng 15 chi nhánh trải dài từ Bắc vào Nam, phục vụ hàng chục ngàn khách hàng mỗi tháng.</p>
                        </div>
                    </div>

                    <div class="about-timeline-item">
                        <div class="about-timeline-dot"><i class="fa-solid fa-star"></i></div>
                        <div class="about-timeline-year">2026</div>
                        <div class="about-timeline-card">
                            <h6 class="fw-bold text-dark mb-1"><i class="fa-solid fa-wand-magic-sparkles text-purple me-2" style="color: #7e22ce;"></i>Ra mắt V-Phone 2.0 với AI Shopping</h6>
                            <p class="text-secondary small mb-0">Tích hợp trí tuệ nhân tạo vào nền tảng mua sắm, mang đến trải nghiệm cá nhân hóa và thông minh hơn bao giờ hết.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== ĐỐI TÁC CHÍNH HÃNG ==================== -->
    <div id="doi-tac" class="mb-5">
        <div class="text-center mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold rounded-pill px-3 py-1 mb-2">HỢP TÁC CHIẾN LƯỢC</span>
            <h3 class="fw-bold text-dark mb-1">Đối Tác Chính Hãng</h3>
            <p class="text-secondary small mb-0">V-Phone Store tự hào là đại lý ủy quyền của các thương hiệu công nghệ hàng đầu thế giới</p>
        </div>

        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-3">
                <div class="about-partner-card">
                    <img src="assets/images/brands/apple.png" alt="Apple" title="Apple">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="about-partner-card">
                    <img src="assets/images/brands/samsung.png" alt="Samsung" title="Samsung">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="about-partner-card">
                    <img src="assets/images/brands/xiaomi.png" alt="Xiaomi" title="Xiaomi">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="about-partner-card">
                    <img src="assets/images/brands/oppo.png" alt="OPPO" title="OPPO">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="about-partner-card">
                    <img src="assets/images/brands/vivo.png" alt="Vivo" title="Vivo">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="about-partner-card">
                    <img src="assets/images/brands/google.png" alt="Google" title="Google">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="about-partner-card">
                    <img src="assets/images/brands/asus.png" alt="ASUS" title="ASUS">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="about-partner-card">
                    <img src="assets/images/brands/sony.png" alt="Sony" title="Sony">
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== CTA SECTION ==================== -->
    <div class="about-cta-section p-4 p-md-5 text-center">
        <div class="about-cta-glow"></div>
        <div class="position-relative" style="z-index: 2;">
            <div class="rounded-circle bg-white shadow d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px;">
                <i class="fa-solid fa-phone-volume fs-3 text-primary"></i>
            </div>
            <h3 class="fw-bold text-white mb-2">Bạn Cần Tư Vấn?</h3>
            <p class="text-light opacity-90 mb-4 mx-auto" style="max-width: 520px;">
                Liên hệ ngay với V-Phone Store để được tư vấn miễn phí về sản phẩm và dịch vụ. Chúng tôi luôn sẵn sàng hỗ trợ bạn!
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="index.php?page=stores" class="btn btn-light btn-lg rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-location-dot text-primary"></i>
                    <span>Hệ Thống Cửa Hàng</span>
                </a>
                <a href="tel:18006868" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-bold d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-phone me-1"></i>
                    <span>Hotline: 1800 6868</span>
                </a>
            </div>
        </div>
    </div>

</div>

<?php require_once 'app/views/includes/footer.php'; ?>
