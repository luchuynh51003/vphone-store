<?php
/**
 * V-Phone Store 2026 - Trung Tâm Trợ Lý AI Theo Từng Chức Vụ (AI Command Center)
 * Phiên bản UI Hiện Đại, Đẳng Cấp, Chuẩn Flagship 2026
 */
$pageTitle = 'Trung Tâm Trợ Lý AI Theo Chức Vụ - V-Phone Store';
require_once __DIR__ . '/includes/header.php';

// Kiểm tra quyền hạn: Chỉ Admin hoặc Nhân viên được cấp quyền 'ai_assistant' mới được truy cập
if (!isCurrentAdmin() && !currentUserCan('ai_assistant')) {
    echo '<div class="container-fluid p-4"><div class="alert alert-warning rounded-4 shadow-sm p-4 text-center my-4 mx-auto" style="max-width:540px;">';
    echo '<i class="fa-solid fa-lock fs-2 text-warning mb-2 d-block"></i>';
    echo '<h5 class="fw-bold">Bạn chưa được cấp quyền sử dụng Trợ lý AI!</h5>';
    echo '<p class="text-secondary small mb-3">Tính năng Trợ lý AI chuyên môn chỉ khả dụng khi được Quản trị viên cấp quyền trong mục <strong>Nhân sự & Phân quyền</strong>.</p>';
    echo '<a href="staff_dashboard.php" class="btn btn-primary rounded-pill px-4">Quay về Bàn làm việc</a>';
    echo '</div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$currentUser = $_SESSION['user'] ?? [];
$userId = (int)($currentUser['id'] ?? 0);
$userRoleVal = (int)($currentUser['role'] ?? 0);
$userStaffRole = strtolower(trim((string)($currentUser['staff_role'] ?? '')));
$staffTitle = getStaffTitleDisplay();
$isAdm = isCurrentAdmin();
$activeAssignedRole = in_array($userStaffRole, ['sales', 'warehouse', 'warranty', 'support']) ? $userStaffRole : 'sales';

// Quy định danh sách Card được phép hiển thị:
// - Admin có toàn quyền: Hiển thị 5 card nội bộ (admin, sales, warehouse, warranty, support)
// - Nhân viên (Staff): CHỈ hiển thị duy nhất 1 card tương ứng với chức vụ được cấp phép!
$allowedCards = [];
if ($isAdm) {
    $allowedCards = ['admin', 'sales', 'warehouse', 'warranty', 'support'];
} else {
    $allowedCards = [$activeAssignedRole];
}

$canDisplayAiCard = function(string $cardRole) use ($allowedCards): bool {
    return in_array($cardRole, $allowedCards, true);
};

$totalAiVisible = count($allowedCards);

/** @var PDO $pdo */
global $pdo;

// Dữ liệu thống kê nhanh theo thời gian thực cho từng chức vụ
$statTodayRev = (float)($pdo->query("SELECT COALESCE(SUM(total_money), 0) FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'Đã hủy'")->fetchColumn() ?: 0);
$statPendingOrders = (int)($pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Chờ xử lý', 'Đang xử lý')")->fetchColumn() ?: 0);
$statLowStock = (int)($pdo->query("SELECT COUNT(*) FROM products WHERE quantity <= 5")->fetchColumn() ?: 0);
$statActiveVouchers = (int)($pdo->query("SELECT COUNT(*) FROM vouchers WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= NOW())")->fetchColumn() ?: 0);
$statPendingWarranties = 0;
try {
    $statPendingWarranties = (int)($pdo->query("SELECT COUNT(*) FROM warranty_claims WHERE status = 'Chờ tiếp nhận'")->fetchColumn() ?: 0);
} catch (\Throwable $e) {}
$statPendingCallbacks = 0;
try {
    $statPendingCallbacks = (int)($pdo->query("SELECT COUNT(*) FROM callback_requests WHERE status = 'pending'")->fetchColumn() ?: 0);
} catch (\Throwable $e) {}
?>

<style>
.ai-hub-hero {
    background: linear-gradient(135deg, #0b1329 0%, #1e1b4b 50%, #0f172a 100%);
    border-radius: 24px;
    color: #ffffff;
    padding: 32px 36px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.3);
    margin-bottom: 28px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.ai-hub-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 450px;
    height: 450px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(168, 85, 247, 0.25) 0%, rgba(99, 102, 241, 0.1) 50%, transparent 70%);
    pointer-events: none;
}
.ai-role-card {
    background: #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    height: 100%;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
}
.ai-role-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 32px -6px rgba(15, 23, 42, 0.1);
    border-color: rgba(99, 102, 241, 0.3);
}
.ai-role-orb {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: #ffffff;
    flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
}
.ai-feature-pill {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
    margin-right: 4px;
    margin-bottom: 6px;
}
.ai-feature-pill i {
    margin-right: 6px !important;
}
.ai-hub-hero .btn i {
    margin-right: 8px !important;
}
[data-bs-theme="dark"] .ai-role-card {
    background: #131f37 !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    color: #f1f5f9 !important;
}
[data-bs-theme="dark"] .ai-feature-pill {
    background: #1e293b;
    color: #cbd5e1;
}
</style>

<div class="container-fluid px-3 px-md-4 py-3">

    <!-- BREADCRUMBS -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang Quản Trị</a></li>
            <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Trung Tâm Trợ Lý AI Theo Chức Vụ</li>
        </ol>
    </nav>

    <!-- HERO BANNER -->
    <div class="ai-hub-hero">
        <div class="row align-items-center">
            <div class="col-12 col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 text-white small fw-bold mb-3 border border-white border-opacity-15">
                    <i class="fa-solid fa-wand-magic-sparkles text-warning me-1.5"></i>
                    <span>Cyber Neural AI Assistant Ecosystem 2026</span>
                </div>
                <h2 class="fw-bold mb-2 fs-3 text-white">Trung Tâm Trợ Lý AI Phân Quyền Theo Từng Chức Vụ</h2>
                <p class="text-white-50 mb-3" style="max-width: 680px; font-size: 0.92rem; line-height: 1.6;">
                    Mỗi vị trí trong hệ thống V-Phone Store đều sở hữu một hộp chatbox AI chuyên biệt, được huấn luyện nghiệp vụ chuẩn mực, kết nối dữ liệu CSDL thời gian thực để hỗ trợ tối đa hiệu suất làm việc 24/7.
                </p>
                <div class="d-flex flex-wrap gap-2.5">
                    <?php if ($isAdm): ?>
                        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center shadow-sm" onclick="window.openRoleAiChat('admin', 'Báo cáo doanh thu hôm nay')">
                            <i class="fa-solid fa-crown me-2"></i><span>Mở Box AI Quản Trị</span>
                        </button>
                        <button type="button" class="btn btn-outline-light rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center" onclick="window.openRoleAiChat('sales', 'Danh sách voucher đang hoạt động')">
                            <i class="fa-solid fa-briefcase me-2"></i><span>Box AI Bán Hàng</span>
                        </button>
                        <button type="button" class="btn btn-outline-light rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center" onclick="window.openRoleAiChat('warehouse', 'Sản phẩm sắp hết hàng (tồn ≤ 5)')">
                            <i class="fa-solid fa-boxes-stacked me-2"></i><span>Box AI Kho</span>
                        </button>
                    <?php elseif ($activeAssignedRole === 'warehouse'): ?>
                        <button type="button" class="btn btn-warning text-dark rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center shadow-sm" onclick="window.openRoleAiChat('warehouse', 'Sản phẩm sắp hết hàng (tồn ≤ 5)')">
                            <i class="fa-solid fa-boxes-stacked me-2"></i><span>Mở Trợ Lý AI Kho</span>
                        </button>
                        <button type="button" class="btn btn-outline-light rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center" onclick="window.openRoleAiChat('warehouse', 'Thống kê tổng giá trị tồn kho')">
                            <i class="fa-solid fa-coins me-2"></i><span>Định Giá Tồn Kho</span>
                        </button>
                        <button type="button" class="btn btn-outline-light rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center" onclick="window.openRoleAiChat('warehouse', 'Quy trình kiểm kê quét IMEI chuẩn')">
                            <i class="fa-solid fa-barcode me-2"></i><span>Quét IMEI Chuẩn</span>
                        </button>
                    <?php elseif ($activeAssignedRole === 'sales'): ?>
                        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center shadow-sm" onclick="window.openRoleAiChat('sales', 'Danh sách voucher đang hoạt động')">
                            <i class="fa-solid fa-briefcase me-2"></i><span>Mở Trợ Lý AI Bán Hàng</span>
                        </button>
                        <button type="button" class="btn btn-outline-light rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center" onclick="window.openRoleAiChat('sales', 'Tra cứu đơn hàng mới nhất')">
                            <i class="fa-solid fa-receipt me-2"></i><span>Tra Cứu Đơn Hàng</span>
                        </button>
                        <button type="button" class="btn btn-outline-light rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center" onclick="window.openRoleAiChat('sales', 'Mẫu tin nhắn gọi xác nhận đơn')">
                            <i class="fa-solid fa-clipboard-list me-2"></i><span>Mẫu Gọi Xác Nhận</span>
                        </button>
                    <?php elseif ($activeAssignedRole === 'warranty'): ?>
                        <button type="button" class="btn btn-success rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center shadow-sm" onclick="window.openRoleAiChat('warranty', 'Chẩn đoán lỗi sọc màn hình & phôi OLED')">
                            <i class="fa-solid fa-shield-halved me-2"></i><span>Mở Trợ Lý AI Kỹ Thuật</span>
                        </button>
                        <button type="button" class="btn btn-outline-light rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center" onclick="window.openRoleAiChat('warranty', 'Tra cứu phiếu bảo hành theo IMEI')">
                            <i class="fa-solid fa-barcode me-2"></i><span>Tra Cứu IMEI</span>
                        </button>
                        <button type="button" class="btn btn-outline-light rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center" onclick="window.openRoleAiChat('warranty', 'Bảng giá tham khảo thay linh kiện')">
                            <i class="fa-solid fa-screwdriver-wrench me-2"></i><span>Bảng Giá Linh Kiện</span>
                        </button>
                    <?php elseif ($activeAssignedRole === 'support'): ?>
                        <button type="button" class="btn text-white rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center shadow-sm" style="background: linear-gradient(135deg, #ec4899, #8b5cf6);" onclick="window.openRoleAiChat('support', 'Khách hàng yêu cầu gọi lại mới nhất')">
                            <i class="fa-solid fa-headset me-2"></i><span>Mở Trợ Lý AI CSKH</span>
                        </button>
                        <button type="button" class="btn btn-outline-light rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center" onclick="window.openRoleAiChat('support', 'Mẫu trả lời đánh giá 1 sao chuyên nghiệp')">
                            <i class="fa-solid fa-star me-2"></i><span>Mẫu Trả Lời 1 Sao</span>
                        </button>
                        <button type="button" class="btn btn-outline-light rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center" onclick="window.openRoleAiChat('support', 'Kịch bản xoa dịu khách khiếu nại')">
                            <i class="fa-solid fa-heart me-2"></i><span>Xử Lý Khiếu Nại (L.A.S.T)</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-12 col-lg-4 mt-4 mt-lg-0 text-lg-end">
                <div class="p-3.5 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-15 text-start d-inline-block w-100" style="max-width: 320px;">
                    <div class="d-flex align-items-center mb-2 text-warning fw-bold small">
                        <i class="fa-solid fa-user-shield me-2"></i><span>Chức Vụ Hiện Tại Của Bạn</span>
                    </div>
                    <div class="fw-bold fs-5 text-white mb-1"><?= htmlspecialchars($_SESSION['user']['fullname'] ?? 'Quản Trị Viên') ?></div>
                    <div class="badge rounded-pill bg-primary bg-opacity-75 text-white mb-2"><?= htmlspecialchars($staffTitle) ?></div>
                    <div class="small text-white-50 pt-2 border-top border-white border-opacity-10">
                        <?= $isAdm ? 'Toàn quyền sử dụng và điều phối tất cả các trợ lý AI chuyên môn!' : 'Hệ thống đã khóa và chỉ kích hoạt đúng Trợ lý AI theo phân quyền của bạn.' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DANH SÁCH BOX CHATBOX AI ĐƯỢC PHÂN QUYỀN -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-layer-group text-primary me-2"></i>
                <?= $isAdm ? 'Danh Sách 5 Chatbox AI Chuyên Biệt Theo Phòng Ban' : 'Trợ Lý AI Chuyên Môn Của Bạn (Theo Phân Quyền)' ?>
            </h5>
            <small class="text-muted">
                <?= $isAdm ? 'Nhấp vào "Trò chuyện ngay" trên bất kỳ phòng ban nào để mở trực tiếp chatbox tương ứng' : 'Hệ thống chỉ hiển thị duy nhất Trợ lý AI theo chức vụ đã được Quản trị viên phân quyền cho bạn' ?>
            </small>
        </div>
        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold">
            <span class="role-ai-trigger-dot me-1"></span> <?= $totalAiVisible ?>/<?= $totalAiVisible ?> AI Trực Tuyến <?= $isAdm ? '(Toàn Quyền Quản Trị)' : '(Đã Cấp Quyền)' ?>
        </span>
    </div>

    <div class="row g-3 g-lg-4 mb-4">

        <!-- 1. QUẢN TRỊ VIÊN (ADMIN) -->
        <?php if ($canDisplayAiCard('admin')): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="ai-role-card">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="ai-role-orb" style="background: linear-gradient(135deg, #ef4444, #8b5cf6);">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill small mb-1">Chức vụ: Quản Trị Viên</span>
                        <h5 class="fw-bold text-dark mb-0 fs-6">V-Phone Executive AI</h5>
                        <small class="text-secondary">Cố vấn điều hành & Chiến lược kinh doanh</small>
                    </div>
                </div>
                <p class="small text-muted mb-3" style="line-height: 1.5;">
                    Trợ lý cao cấp hỗ trợ ban lãnh đạo: Báo cáo doanh thu thời gian thực, tổng kết đơn hàng, phân tích biên lợi nhuận, rà soát nhân sự và đưa ra định hướng tăng trưởng.
                </p>
                <div class="mb-3">
                    <span class="ai-feature-pill"><i class="fa-solid fa-chart-line text-danger me-1.5"></i>Doanh thu ngày: <?= number_format($statTodayRev, 0, ',', '.') ?> VNĐ</span>
                    <span class="ai-feature-pill"><i class="fa-solid fa-boxes-packing text-primary me-1.5"></i>Đơn chờ: <?= $statPendingOrders ?> đơn</span>
                    <span class="ai-feature-pill"><i class="fa-solid fa-triangle-exclamation text-warning me-1.5"></i>Cảnh báo kho: <?= $statLowStock ?> máy</span>
                </div>
                <div class="mt-auto pt-2 border-top d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 py-1.5 fw-bold flex-grow-1" onclick="window.openRoleAiChat('admin', 'Báo cáo doanh thu hôm nay')">
                        <i class="fa-solid fa-comments me-1"></i> Trò Chuyện Ngay
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="Hỏi nhanh chiến lược 2026" onclick="window.openRoleAiChat('admin', 'Đề xuất chiến lược tăng trưởng 2026')">
                        <i class="fa-solid fa-lightbulb text-warning"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 2. NHÂN VIÊN BÁN HÀNG & ĐƠN HÀNG (SALES) -->
        <?php if ($canDisplayAiCard('sales')): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="ai-role-card">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="ai-role-orb" style="background: linear-gradient(135deg, #2563eb, #06b6d4);">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill small mb-1">Chức vụ: NV Bán Hàng</span>
                        <h5 class="fw-bold text-dark mb-0 fs-6">V-Phone Sales Pro AI</h5>
                        <small class="text-secondary">Trợ thủ chốt deal, đơn hàng & voucher</small>
                    </div>
                </div>
                <p class="small text-muted mb-3" style="line-height: 1.5;">
                    Trợ thủ bán hàng đắc lực: Tra cứu tức thì thông tin đơn qua mã đơn hoặc SĐT khách, tra cứu kho voucher chốt deal, gợi ý up-sell củ sạc 45W, kịch bản gọi xác nhận.
                </p>
                <div class="mb-3">
                    <span class="ai-feature-pill"><i class="fa-solid fa-ticket text-info me-1.5"></i><?= $statActiveVouchers ?> voucher hoạt động</span>
                    <span class="ai-feature-pill"><i class="fa-solid fa-phone-volume text-success me-1.5"></i>Kịch bản gọi xác nhận</span>
                    <span class="ai-feature-pill"><i class="fa-solid fa-bolt text-warning me-1.5"></i>Up-sell phụ kiện</span>
                </div>
                <div class="mt-auto pt-2 border-top d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 fw-bold flex-grow-1" onclick="window.openRoleAiChat('sales', 'Danh sách voucher đang hoạt động')">
                        <i class="fa-solid fa-comments me-1"></i> Trò Chuyện Ngay
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="Mẫu gọi xác nhận đơn" onclick="window.openRoleAiChat('sales', 'Mẫu tin nhắn gọi xác nhận đơn')">
                        <i class="fa-solid fa-clipboard-list text-primary"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 3. QUẢN LÝ KHO & TỒN KHO (WAREHOUSE) -->
        <?php if ($canDisplayAiCard('warehouse')): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="ai-role-card">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="ai-role-orb" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning-subtle rounded-pill small mb-1">Chức vụ: Quản Lý Kho</span>
                        <h5 class="fw-bold text-dark mb-0 fs-6">V-Phone Warehouse AI</h5>
                        <small class="text-secondary">Kiểm kê IMEI, định mức & cảnh báo tồn</small>
                    </div>
                </div>
                <p class="small text-muted mb-3" style="line-height: 1.5;">
                    Trợ lý quản trị kho vận: Cảnh báo ngay các sản phẩm chạm đáy tồn kho (≤ 5 máy), định giá tổng tài sản kho, quy trình quét IMEI xuất nhập và tiêu chuẩn đóng gói chống sốc.
                </p>
                <div class="mb-3">
                    <span class="ai-feature-pill"><i class="fa-solid fa-bell text-danger me-1.5"></i><?= $statLowStock ?> máy chạm đáy</span>
                    <span class="ai-feature-pill"><i class="fa-solid fa-barcode text-dark me-1.5"></i>Quy trình quét IMEI</span>
                    <span class="ai-feature-pill"><i class="fa-solid fa-shield text-success me-1.5"></i>Đóng gói 4 lớp</span>
                </div>
                <div class="mt-auto pt-2 border-top d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 py-1.5 fw-bold text-dark flex-grow-1" onclick="window.openRoleAiChat('warehouse', 'Sản phẩm sắp hết hàng (tồn ≤ 5)')">
                        <i class="fa-solid fa-comments me-1"></i> Trò Chuyện Ngay
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="Thống kê định giá kho" onclick="window.openRoleAiChat('warehouse', 'Thống kê tổng giá trị tồn kho')">
                        <i class="fa-solid fa-coins text-warning"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 4. KỸ THUẬT VIÊN & BẢO HÀNH (WARRANTY) -->
        <?php if ($canDisplayAiCard('warranty')): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="ai-role-card">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="ai-role-orb" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill small mb-1">Chức vụ: Kỹ Thuật Viên</span>
                        <h5 class="fw-bold text-dark mb-0 fs-6">V-Phone Tech & Care AI</h5>
                        <small class="text-secondary">Chẩn đoán phần cứng, IMEI & sửa chữa</small>
                    </div>
                </div>
                <p class="small text-muted mb-3" style="line-height: 1.5;">
                    Trợ lý chẩn đoán kỹ thuật: Tra cứu phiếu bảo hành theo số IMEI/SĐT, phân tích nguyên nhân lỗi sọc màn, chai phồng pin, quy định bảo hành Apple VN/A và báo giá linh kiện.
                </p>
                <div class="mb-3">
                    <span class="ai-feature-pill"><i class="fa-solid fa-clock-rotate-left text-warning me-1.5"></i><?= $statPendingWarranties ?> ca chờ tiếp nhận</span>
                    <span class="ai-feature-pill"><i class="fa-solid fa-mobile-screen text-primary me-1.5"></i>Chẩn đoán phôi OLED</span>
                    <span class="ai-feature-pill"><i class="fa-solid fa-award text-success me-1.5"></i>Apple Care VN/A</span>
                </div>
                <div class="mt-auto pt-2 border-top d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3 py-1.5 fw-bold flex-grow-1" onclick="window.openRoleAiChat('warranty', 'Chẩn đoán lỗi sọc màn hình & phôi OLED')">
                        <i class="fa-solid fa-comments me-1"></i> Trò Chuyện Ngay
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="Bảng giá thay linh kiện" onclick="window.openRoleAiChat('warranty', 'Bảng giá tham khảo thay linh kiện')">
                        <i class="fa-solid fa-screwdriver-wrench text-success"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 5. CHUYÊN VIÊN CSKH & ĐA KÊNH (SUPPORT) -->
        <?php if ($canDisplayAiCard('support')): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="ai-role-card">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="ai-role-orb" style="background: linear-gradient(135deg, #ec4899, #8b5cf6);">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="badge bg-pink bg-opacity-10 text-danger border border-danger-subtle rounded-pill small mb-1" style="background:#fdf2f8; color:#db2777 !important;">Chức vụ: Chuyên Viên CSKH</span>
                        <h5 class="fw-bold text-dark mb-0 fs-6">V-Phone Support AI</h5>
                        <small class="text-secondary">Chăm sóc khách hàng & Xử lý phản hồi 5 sao</small>
                    </div>
                </div>
                <p class="small text-muted mb-3" style="line-height: 1.5;">
                    Trợ lý giao tiếp & trải nghiệm khách hàng: Quản lý hàng chờ yêu cầu gọi lại, soạn mẫu trả lời đánh giá 1 sao - 5 sao và kịch bản xoa dịu khách hàng khiếu nại (L.A.S.T).
                </p>
                <div class="mb-3">
                    <span class="ai-feature-pill"><i class="fa-solid fa-phone text-primary me-1.5"></i><?= $statPendingCallbacks ?> khách chờ gọi lại</span>
                    <span class="ai-feature-pill"><i class="fa-solid fa-star text-warning me-1.5"></i>Mẫu phản hồi review</span>
                    <span class="ai-feature-pill"><i class="fa-solid fa-heart text-danger me-1.5"></i>Xử lý khiếu nại 5*</span>
                </div>
                <div class="mt-auto pt-2 border-top d-flex gap-2">
                    <button type="button" class="btn btn-sm text-white rounded-pill px-3 py-1.5 fw-bold flex-grow-1" style="background: linear-gradient(135deg, #ec4899, #8b5cf6);" onclick="window.openRoleAiChat('support', 'Khách hàng yêu cầu gọi lại mới nhất')">
                        <i class="fa-solid fa-comments me-1"></i> Trò Chuyện Ngay
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="Mẫu trả lời 1 sao" onclick="window.openRoleAiChat('support', 'Mẫu trả lời đánh giá 1 sao chuyên nghiệp')">
                        <i class="fa-solid fa-comment-dots text-danger"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 6. KHÁCH HÀNG MUA SẮM (CUSTOMER - CHỈ HIỂN THỊ KHI QUẢN TRỊ VIÊN CẦN KIỂM TRA BOT NGOÀI WEB) -->
        <?php if ($canDisplayAiCard('customer')): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="ai-role-card">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="ai-role-orb" style="background: linear-gradient(135deg, #6366f1, #3b82f6);">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="badge bg-indigo bg-opacity-10 text-primary border border-primary-subtle rounded-pill small mb-1" style="background:#eef2ff; color:#4f46e5 !important;">Chức vụ: Tư Vấn Khách Hàng</span>
                        <h5 class="fw-bold text-dark mb-0 fs-6">V-Phone Cyber AI</h5>
                        <small class="text-secondary">Chuyên gia tư vấn Flagship & Mua sắm 24/7</small>
                    </div>
                </div>
                <p class="small text-muted mb-3" style="line-height: 1.5;">
                    Trợ lý trực tuyến trên website bán lẻ: Báo giá các dòng iPhone, Galaxy Ultra, Xiaomi, tư vấn thủ tục trả góp 0%, chính sách đồng kiểm mở hộp và bảo hành 1 đổi 1 trong 30 ngày.
                </p>
                <div class="mb-3">
                    <span class="ai-feature-pill"><i class="fa-solid fa-tag text-success me-1.5"></i>Bảng giá Flagship 2026</span>
                    <span class="ai-feature-pill"><i class="fa-solid fa-credit-card text-info me-1.5"></i>Trả góp 0% duyệt 15p</span>
                    <span class="ai-feature-pill"><i class="fa-solid fa-box-open text-warning me-1.5"></i>Đồng kiểm 100%</span>
                </div>
                <div class="mt-auto pt-2 border-top d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1.5 fw-bold flex-grow-1" style="background: linear-gradient(135deg, #6366f1, #3b82f6); border:none;" onclick="window.openRoleAiChat('customer', 'Bảng giá iPhone mới nhất')">
                        <i class="fa-solid fa-comments me-1"></i> Trò Chuyện Ngay
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="Hỏi trả góp 0%" onclick="window.openRoleAiChat('customer', 'Thủ tục trả góp 0% duyệt online')">
                        <i class="fa-solid fa-hand-holding-dollar text-primary"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- HƯỚNG DẪN SỬ DỤNG TRỢ LÝ AI THEO CHỨC VỤ -->
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-light">
        <h6 class="fw-bold text-dark mb-2 d-flex align-items-center">
            <i class="fa-solid fa-circle-info text-primary me-2"></i>
            <span>Quy Chuẩn Bảo Mật & Phân Quyền Trợ Lý AI:</span>
        </h6>
        <div class="row g-3 small text-secondary mt-1">
            <div class="col-12 col-md-4">
                <strong class="text-dark d-block mb-1"><i class="fa-solid fa-shield-check text-success me-1.5"></i>Phân Quyền Chặt Chẽ</strong>
                Tài khoản nhân sự chỉ nhìn thấy và sử dụng duy nhất Trợ lý AI thuộc phòng ban được cấp phép. Trợ lý AI của chức vụ khác sẽ bị ẩn hoàn toàn để đảm bảo tính an toàn dữ liệu.
            </div>
            <div class="col-12 col-md-4">
                <strong class="text-dark d-block mb-1"><i class="fa-solid fa-bullseye text-danger me-1.5"></i>Chuyên Môn Hóa Nghiệp Vụ</strong>
                Mỗi AI chỉ trả lời và thực thi đúng nhiệm vụ của phòng ban đó (Kho vận, Bán hàng, Kỹ thuật, CSKH). Nếu nhận câu hỏi ngoài chuyên môn, AI sẽ hướng dẫn chuyển giao đúng bộ phận.
            </div>
            <div class="col-12 col-md-4">
                <strong class="text-dark d-block mb-1"><i class="fa-solid fa-database text-info me-1.5"></i>Dữ Liệu Thời Gian Thực</strong>
                Tất cả các số liệu doanh thu, đơn hàng, tồn kho và voucher đều được truy vấn trực tiếp từ cơ sở dữ liệu MySQL mới nhất trong giây.
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
