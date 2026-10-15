<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/WarrantyModel.php';

$warrantyModel = new WarrantyModel($pdo);

// Xử lý xuất file CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'vphone_bao_hanh_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    // Ghi UTF-8 BOM để Excel đọc đúng tiếng Việt
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Mã YC', 'Mã Đơn', 'Khách Hàng', 'Số Điện Thoại', 'Tên Sản Phẩm', 'Số IMEI', 'Loại Sự Cố', 'Mô Tả Triệu Chứng', 'Hình Thức Gửi', 'Trạng Thái', 'Ghi Chú Kỹ Thuật Viên', 'Ngày Tạo', 'Cập Nhật Lần Cuối']);
    
    $exportClaims = $warrantyModel->getAllClaims('all', '');
    foreach ($exportClaims as $c) {
        fputcsv($output, [
            '#BH-' . $c['id'],
            '#VP-' . $c['order_id'],
            $c['customer_name'],
            $c['customer_phone'],
            $c['product_name'],
            $c['imei'],
            $c['issue_type'],
            $c['issue_description'] ?? '',
            $c['claim_method'],
            $c['status'],
            $c['admin_note'] ?? '',
            $c['created_at'],
            $c['updated_at']
        ]);
    }
    fclose($output);
    exit;
}

$pageTitle = 'Quản Lý Bảo Hành & Đổi Trả - V-Phone Admin';
require_once 'includes/header.php';

$message = '';
$msgType = 'success';

// Xử lý cập nhật trạng thái yêu cầu
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['update_claim'])) {
    $claimId = (int)($_POST['claim_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $adminNote = trim($_POST['admin_note'] ?? '');
    
    if ($claimId > 0 && !empty($status)) {
        if ($warrantyModel->updateStatus($claimId, $status, $adminNote)) {
            $message = "Đã cập nhật yêu cầu bảo hành <strong>#BH-$claimId</strong> sang trạng thái <strong>'$status'</strong> thành công!";
            $msgType = 'success';
        } else {
            $message = "Không thể cập nhật yêu cầu #BH-$claimId. Vui lòng thử lại.";
            $msgType = 'danger';
        }
    }
}

// Xử lý xóa yêu cầu bảo hành
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['delete_claim'])) {
    $claimId = (int)($_POST['claim_id'] ?? 0);
    if ($claimId > 0) {
        if ($warrantyModel->deleteClaim($claimId)) {
            $message = "Đã xóa hồ sơ bảo hành <strong>#BH-$claimId</strong> thành công!";
            $msgType = 'warning';
        } else {
            $message = "Không thể xóa hồ sơ #BH-$claimId. Vui lòng kiểm tra lại.";
            $msgType = 'danger';
        }
    }
}

// Lọc và tìm kiếm
$filterStatus = $_GET['status'] ?? 'all';
$searchKeyword = trim($_GET['search'] ?? '');

$statusCounts = $warrantyModel->getStatusCounts();
$claims = $warrantyModel->getAllClaims($filterStatus, $searchKeyword);
?>

<div class="mb-4">
    <!-- Tiêu đề trang & các nút thao tác nhanh -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold rounded-pill px-3 py-1">
                    <i class="fa-solid fa-shield-halved me-1"></i>V-PHONE CARE CENTER
                </span>
                <?php if ($statusCounts['Chờ tiếp nhận'] > 0): ?>
                    <span class="badge bg-danger rounded-pill px-2.5 py-1 fw-bold">
                        <?= $statusCounts['Chờ tiếp nhận'] ?> Yêu cầu mới cần tiếp nhận
                    </span>
                <?php endif; ?>
            </div>
            <h3 class="fw-black text-dark mb-1">Quản Lý Yêu Cầu Bảo Hành & Đổi Trả</h3>
            <p class="text-secondary small mb-0">Tiếp nhận yêu cầu bảo hành từ khách hàng, phân công giám định 32 bước và ghi nhận kết quả xử lý</p>
        </div>
        <div class="d-flex gap-2">
            <a href="warranties.php?export=csv" class="btn btn-outline-success btn-sm rounded-pill fw-bold px-3 d-flex align-items-center gap-1.5 shadow-sm">
                <i class="fa-solid fa-file-excel"></i>
                <span>Xuất Excel (CSV)</span>
            </a>
            <a href="../index.php?page=warranty" target="_blank" class="btn btn-primary btn-sm rounded-pill fw-bold px-3 d-flex align-items-center gap-1.5 shadow-sm">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>Trang Tra Cứu Bảo Hành</span>
            </a>
        </div>
    </div>

    <!-- Thông báo thao tác -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4 d-flex align-items-center gap-2" role="alert">
            <i class="fa-solid fa-circle-check fs-5"></i>
            <div><?= $message ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- 5 Thẻ Thống Kê KPI Trạng Thái -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 rounded-4 shadow-sm h-100 p-3 bg-white border-top border-4 border-primary">
                <div class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.72rem;">Tổng yêu cầu</div>
                <div class="h3 fw-black text-dark mb-0"><?= number_format($statusCounts['all']) ?></div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">Hồ sơ toàn hệ thống</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 rounded-4 shadow-sm h-100 p-3 bg-white border-top border-4 border-warning">
                <div class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.72rem;">Chờ tiếp nhận</div>
                <div class="h3 fw-black text-warning mb-0"><?= number_format($statusCounts['Chờ tiếp nhận']) ?></div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">Cần phản hồi ngay</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 rounded-4 shadow-sm h-100 p-3 bg-white border-top border-4 border-info">
                <div class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.72rem;">Đang kiểm tra</div>
                <div class="h3 fw-black text-info mb-0"><?= number_format($statusCounts['Đang kiểm tra']) ?></div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">Giám định 32 bước</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 rounded-4 shadow-sm h-100 p-3 bg-white border-top border-4 border-success">
                <div class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.72rem;">Đã đổi máy mới</div>
                <div class="h3 fw-black text-success mb-0"><?= number_format($statusCounts['Đã đổi mới']) ?></div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">1 Đổi 1 trong 30 ngày</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 rounded-4 shadow-sm h-100 p-3 bg-white border-top border-4 border-primary">
                <div class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.72rem;">Đã sửa chữa</div>
                <div class="h3 fw-black text-primary mb-0"><?= number_format($statusCounts['Đã sửa chữa']) ?></div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">Thay linh kiện zin</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 rounded-4 shadow-sm h-100 p-3 bg-white border-top border-4 border-danger">
                <div class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.72rem;">Từ chối bảo hành</div>
                <div class="h3 fw-black text-danger mb-0"><?= number_format($statusCounts['Từ chối']) ?></div>
                <div class="text-muted small mt-1" style="font-size: 0.72rem;">Vào nước / Rơi vỡ</div>
            </div>
        </div>
    </div>

    <!-- Thanh Lọc Trạng Thái & Tìm Kiếm -->
    <div class="card border-0 rounded-4 shadow-sm bg-white mb-4 p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <!-- Filter Pills -->
            <div class="d-flex flex-wrap gap-2">
                <a href="warranties.php?status=all<?= !empty($searchKeyword) ? '&search=' . urlencode($searchKeyword) : '' ?>" 
                   class="btn btn-sm rounded-pill px-3 fw-bold <?= ($filterStatus === 'all') ? 'btn-dark' : 'btn-light' ?>">
                    Tất cả (<?= $statusCounts['all'] ?>)
                </a>
                <a href="warranties.php?status=Chờ tiếp nhận<?= !empty($searchKeyword) ? '&search=' . urlencode($searchKeyword) : '' ?>" 
                   class="btn btn-sm rounded-pill px-3 fw-bold <?= ($filterStatus === 'Chờ tiếp nhận') ? 'btn-warning text-dark' : 'btn-light' ?>">
                    <i class="fa-solid fa-clock me-1"></i>Chờ tiếp nhận (<?= $statusCounts['Chờ tiếp nhận'] ?>)
                </a>
                <a href="warranties.php?status=Đang kiểm tra<?= !empty($searchKeyword) ? '&search=' . urlencode($searchKeyword) : '' ?>" 
                   class="btn btn-sm rounded-pill px-3 fw-bold <?= ($filterStatus === 'Đang kiểm tra') ? 'btn-info text-white' : 'btn-light' ?>">
                    <i class="fa-solid fa-screwdriver-wrench me-1"></i>Đang kiểm tra (<?= $statusCounts['Đang kiểm tra'] ?>)
                </a>
                <a href="warranties.php?status=Đã đổi mới<?= !empty($searchKeyword) ? '&search=' . urlencode($searchKeyword) : '' ?>" 
                   class="btn btn-sm rounded-pill px-3 fw-bold <?= ($filterStatus === 'Đã đổi mới') ? 'btn-success' : 'btn-light' ?>">
                    <i class="fa-solid fa-arrows-rotate me-1"></i>Đã đổi mới (<?= $statusCounts['Đã đổi mới'] ?>)
                </a>
                <a href="warranties.php?status=Đã sửa chữa<?= !empty($searchKeyword) ? '&search=' . urlencode($searchKeyword) : '' ?>" 
                   class="btn btn-sm rounded-pill px-3 fw-bold <?= ($filterStatus === 'Đã sửa chữa') ? 'btn-primary' : 'btn-light' ?>">
                    <i class="fa-solid fa-wrench me-1"></i>Đã sửa chữa (<?= $statusCounts['Đã sửa chữa'] ?>)
                </a>
                <a href="warranties.php?status=Từ chối<?= !empty($searchKeyword) ? '&search=' . urlencode($searchKeyword) : '' ?>" 
                   class="btn btn-sm rounded-pill px-3 fw-bold <?= ($filterStatus === 'Từ chối') ? 'btn-danger' : 'btn-light' ?>">
                    <i class="fa-solid fa-ban me-1"></i>Từ chối (<?= $statusCounts['Từ chối'] ?>)
                </a>
            </div>

            <!-- Ô tìm kiếm -->
            <form action="warranties.php" method="GET" class="d-flex align-items-center gap-2" style="min-width: 280px;">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="IMEI, Tên máy, SĐT, Khách..." value="<?= htmlspecialchars($searchKeyword) ?>">
                    <?php if (!empty($searchKeyword)): ?>
                        <a href="warranties.php?status=<?= urlencode($filterStatus) ?>" class="btn btn-light border" title="Xóa tìm kiếm"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                    <button class="btn btn-primary fw-bold" type="submit">Tìm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bảng Danh Sách Yêu Cầu Bảo Hành -->
    <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0" style="font-size: 0.88rem;">
                <thead class="bg-light text-secondary text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <tr>
                        <th class="ps-4 py-3" style="width: 85px;">Mã YC</th>
                        <th style="width: 100px;">Đơn Hàng</th>
                        <th>Khách Hàng</th>
                        <th>Thiết Bị & IMEI</th>
                        <th>Sự Cố Phản Ánh</th>
                        <th>Hình Thức</th>
                        <th>Ngày Gửi</th>
                        <th>Trạng Thái</th>
                        <th class="pe-4 text-end" style="width: 140px;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($claims)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px;">
                                    <i class="fa-solid fa-shield-halved fs-3 text-secondary"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Không Có Yêu Cầu Bảo Hành Nào</h6>
                                <p class="small text-secondary mb-0">Chưa có bản ghi nào khớp với điều kiện lọc hiện tại.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($claims as $c): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark">
                                    <span class="badge bg-light text-dark border">#BH-<?= $c['id'] ?></span>
                                </td>
                                <td>
                                    <a href="orders.php?order_code=<?= (int)$c['order_id'] ?>" class="fw-bold text-primary text-decoration-none">
                                        #VP-<?= (int)$c['order_id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['customer_name']) ?></div>
                                    <div class="small text-muted">
                                        <a href="tel:<?= htmlspecialchars($c['customer_phone']) ?>" class="text-decoration-none text-secondary">
                                            <i class="fa-solid fa-phone text-success small me-1"></i><?= htmlspecialchars($c['customer_phone']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($c['product_name']) ?>">
                                        <?= htmlspecialchars($c['product_name']) ?>
                                    </div>
                                    <div class="small">
                                        IMEI: <code class="bg-light border px-1.5 py-0.5 rounded text-dark fw-bold"><?= htmlspecialchars($c['imei']) ?></code>
                                        <button class="btn btn-sm btn-link p-0 text-muted ms-1" type="button" onclick="navigator.clipboard.writeText('<?= $c['imei'] ?>'); alert('Đã sao chép IMEI: <?= $c['imei'] ?>');" title="Sao chép">
                                            <i class="fa-regular fa-copy small"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <div class="badge bg-light text-dark border fw-semibold mb-1">
                                        <?= htmlspecialchars($c['issue_type']) ?>
                                    </div>
                                    <?php if (!empty($c['issue_description'])): ?>
                                        <div class="small text-muted text-truncate" style="max-width: 240px;" title="<?= htmlspecialchars($c['issue_description']) ?>">
                                            "<?= htmlspecialchars($c['issue_description']) ?>"
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border small">
                                        <i class="fa-solid <?= ($c['claim_method'] === 'shipping' || strpos($c['claim_method'], 'bưu điện') !== false) ? 'fa-truck-fast text-warning' : 'fa-store text-primary' ?> me-1"></i>
                                        <?= htmlspecialchars($c['claim_method']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small text-dark fw-semibold"><?= date('d/m/Y', strtotime($c['created_at'])) ?></div>
                                    <div class="text-muted" style="font-size: 0.72rem;"><?= date('H:i', strtotime($c['created_at'])) ?></div>
                                </td>
                                <td>
                                    <?php 
                                    $st = $c['status'];
                                    $badgeClass = 'bg-secondary text-white';
                                    $icon = 'fa-circle-info';
                                    if ($st === 'Chờ tiếp nhận') {
                                        $badgeClass = 'bg-warning text-dark';
                                        $icon = 'fa-clock';
                                    } elseif ($st === 'Đang kiểm tra') {
                                        $badgeClass = 'bg-info text-white';
                                        $icon = 'fa-screwdriver-wrench';
                                    } elseif ($st === 'Đã đổi mới') {
                                        $badgeClass = 'bg-success text-white';
                                        $icon = 'fa-arrows-rotate';
                                    } elseif ($st === 'Đã sửa chữa') {
                                        $badgeClass = 'bg-primary text-white';
                                        $icon = 'fa-wrench';
                                    } elseif ($st === 'Từ chối') {
                                        $badgeClass = 'bg-danger text-white';
                                        $icon = 'fa-ban';
                                    }
                                    ?>
                                    <span class="badge <?= $badgeClass ?> rounded-pill px-2.5 py-1.5 fw-bold d-inline-flex align-items-center gap-1">
                                        <i class="fa-solid <?= $icon ?>"></i>
                                        <span><?= htmlspecialchars($st) ?></span>
                                    </span>
                                    <?php if (!empty($c['admin_note'])): ?>
                                        <div class="text-muted text-truncate mt-1" style="max-width: 140px; font-size: 0.72rem;" title="<?= htmlspecialchars($c['admin_note']) ?>">
                                            <i class="fa-regular fa-comment-dots me-0.5"></i> <?= htmlspecialchars($c['admin_note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Nút Xử lý / Cập nhật -->
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1 fw-bold" 
                                                onclick="openUpdateModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)" 
                                                title="Cập nhật trạng thái">
                                            <i class="fa-solid fa-pen-to-square me-1"></i>Xử lý
                                        </button>
                                        
                                        <!-- Nút Xem chi tiết -->
                                        <button type="button" class="btn btn-light btn-sm rounded-circle" style="width: 31px; height: 31px;"
                                                onclick="openViewModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)"
                                                title="Xem đầy đủ thông tin">
                                            <i class="fa-regular fa-eye text-secondary"></i>
                                        </button>

                                        <!-- Nút Xóa -->
                                        <form method="POST" class="d-inline" id="formDeleteClaim<?= $c['id'] ?>" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn hồ sơ bảo hành #BH-<?= $c['id'] ?> này?');">
                                            <input type="hidden" name="delete_claim" value="1">
                                            <input type="hidden" name="claim_id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn btn-light btn-sm rounded-circle text-danger" style="width: 31px; height: 31px;" title="Xóa hồ sơ">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Cập Nhật Trạng Thái & Ghi Chú Kỹ Thuật -->
<div class="modal fade" id="updateClaimModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 550px;">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-user-gear fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Xử Lý Hồ Sơ Bảo Hành <span id="modalClaimCode" class="text-primary">#BH-0</span></h5>
                        <small class="text-muted" id="modalClaimSubInfo">Khách hàng: ...</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="warranties.php">
                <input type="hidden" name="update_claim" value="1">
                <input type="hidden" name="claim_id" id="modalClaimIdInput" value="0">
                <input type="hidden" name="status_filter" value="<?= htmlspecialchars($filterStatus) ?>">

                <div class="modal-body p-4">
                    <!-- Info Snippet -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark small" id="modalProductName">Tên máy</span>
                            <span class="badge bg-light text-primary border" id="modalOrderId">#VP-0</span>
                        </div>
                        <div class="small text-secondary">
                            IMEI: <code class="bg-white border px-1.5 py-0.5 rounded text-dark fw-bold" id="modalImei">35...</code>
                        </div>
                        <div class="small text-dark mt-2 pt-2 border-top">
                            <i class="fa-solid fa-circle-exclamation text-warning me-1"></i>
                            <strong>Sự cố:</strong> <span id="modalIssueType">...</span>
                        </div>
                        <div class="small text-muted fst-italic mt-1" id="modalIssueDesc">...</div>
                    </div>

                    <!-- Chọn trạng thái mới -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Trạng Thái Xử Lý Tiếp Theo *</label>
                        <select class="form-select rounded-3" name="status" id="modalStatusSelect" required>
                            <option value="Chờ tiếp nhận">⏳ Chờ tiếp nhận (Đang xếp hàng kỹ thuật)</option>
                            <option value="Đang kiểm tra">🔍 Đang kiểm tra (Kỹ thuật viên giám định 32 bước)</option>
                            <option value="Đã đổi mới">🔄 Đã đổi mới (Lỗi NSX trong 30 ngày - Đổi máy mới 100%)</option>
                            <option value="Đã sửa chữa">🛠️ Đã sửa chữa (Thay thế linh kiện chính hãng xong)</option>
                            <option value="Từ chối">❌ Từ chối bảo hành (Rơi vỡ, vô nước, can thiệp ngoài)</option>
                        </select>
                    </div>

                    <!-- Ghi chú của kỹ thuật viên -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Ghi Chú Kỹ Thuật Viên / Kết Quả Giám Định</label>
                        <textarea class="form-control rounded-3 small" name="admin_note" id="modalAdminNoteInput" rows="3" placeholder="Nhập tình trạng máy, số seri linh kiện thay thế hoặc lý do từ chối..."></textarea>
                        
                        <!-- Quick Suggestion Buttons -->
                        <div class="mt-2 d-flex flex-wrap gap-1.5" style="font-size: 11px;">
                            <span class="text-muted small me-1 align-self-center">Gợi ý nhanh:</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2 rounded-pill" onclick="appendNote('Đã tiếp nhận máy trực tiếp tại V-Phone Care. Bàn giao KTV kiểm tra.');">Đã nhận máy</button>
                            <button type="button" class="btn btn-outline-success btn-sm py-0.5 px-2 rounded-pill" onclick="appendNote('Xác nhận lỗi phần cứng NSX. Đã đổi ngay máy mới 100% nguyên seal.');">Đổi mới 1-1</button>
                            <button type="button" class="btn btn-outline-primary btn-sm py-0.5 px-2 rounded-pill" onclick="appendNote('Đã thay thế linh kiện chính hãng. Kiểm tra 32 tiêu chí hoàn tất, sẵn sàng trả khách.');">Thay linh kiện</button>
                            <button type="button" class="btn btn-outline-danger btn-sm py-0.5 px-2 rounded-pill" onclick="appendNote('Từ chối bảo hành miễn phí: Máy có dấu hiệu ngấm chất lỏng, quỳ tím đổi màu.');">Vào nước</button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4 shadow-sm">
                        <i class="fa-solid fa-check me-1"></i>Lưu Cập Nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xem Chi Tiết Toàn Bộ Hồ Sơ -->
<div class="modal fade" id="viewClaimModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark mb-0">
                    <i class="fa-solid fa-file-lines text-primary me-2"></i>Chi Tiết Hồ Sơ Bảo Hành <span id="viewClaimId">#BH-0</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <span class="text-secondary small fw-bold text-uppercase d-block mb-1">Khách hàng</span>
                            <div class="fw-bold text-dark" id="viewCustomerName">-</div>
                            <div class="small text-muted" id="viewCustomerPhone">-</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <span class="text-secondary small fw-bold text-uppercase d-block mb-1">Đơn hàng gốc</span>
                            <div class="fw-bold text-primary" id="viewOrderCode">-</div>
                            <div class="small text-muted" id="viewCreatedAt">-</div>
                        </div>
                    </div>
                </div>

                <div class="p-3 border rounded-3 mb-3">
                    <span class="text-secondary small fw-bold text-uppercase d-block mb-1">Thông tin thiết bị</span>
                    <div class="fw-bold text-dark fs-6" id="viewProductName">-</div>
                    <div class="small text-secondary mt-1">
                        Mã IMEI: <code class="bg-light border px-2 py-0.5 rounded text-dark fw-bold" id="viewImei">-</code>
                    </div>
                </div>

                <div class="p-3 border rounded-3 mb-3">
                    <span class="text-secondary small fw-bold text-uppercase d-block mb-1">Sự cố kỹ thuật</span>
                    <div class="fw-bold text-dark mb-1" id="viewIssueType">-</div>
                    <div class="small text-secondary" id="viewIssueDescription">-</div>
                    <div class="small text-muted mt-2 pt-2 border-top">
                        Hình thức tiếp nhận: <strong class="text-dark" id="viewClaimMethod">-</strong>
                    </div>
                </div>

                <div class="p-3 rounded-3 border bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary small fw-bold text-uppercase">Trạng thái hiện tại</span>
                        <span class="badge bg-primary rounded-pill px-3 py-1 fw-bold" id="viewStatusBadge">-</span>
                    </div>
                    <div class="small text-secondary" id="viewAdminNote">Chưa có ghi chú kỹ thuật.</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4 w-100" data-bs-dismiss="modal">Đóng Cửa Sổ</button>
            </div>
        </div>
    </div>
</div>

<script>
function openUpdateModal(claim) {
    document.getElementById('modalClaimCode').textContent = '#BH-' + claim.id;
    document.getElementById('modalClaimSubInfo').textContent = 'Khách: ' + claim.customer_name + ' (' + claim.customer_phone + ')';
    document.getElementById('modalClaimIdInput').value = claim.id;
    document.getElementById('modalProductName').textContent = claim.product_name;
    document.getElementById('modalOrderId').textContent = '#VP-' + claim.order_id;
    document.getElementById('modalImei').textContent = claim.imei;
    document.getElementById('modalIssueType').textContent = claim.issue_type;
    document.getElementById('modalIssueDesc').textContent = claim.issue_description ? '"' + claim.issue_description + '"' : 'Không có mô tả chi tiết.';
    
    document.getElementById('modalStatusSelect').value = claim.status || 'Chờ tiếp nhận';
    document.getElementById('modalAdminNoteInput').value = claim.admin_note || '';

    const modalEl = document.getElementById('updateClaimModal');
    if (modalEl && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

function openViewModal(claim) {
    document.getElementById('viewClaimId').textContent = '#BH-' + claim.id;
    document.getElementById('viewCustomerName').textContent = claim.customer_name;
    document.getElementById('viewCustomerPhone').textContent = claim.customer_phone;
    document.getElementById('viewOrderCode').textContent = '#VP-' + claim.order_id;
    document.getElementById('viewCreatedAt').textContent = 'Ngày gửi: ' + claim.created_at;
    document.getElementById('viewProductName').textContent = claim.product_name;
    document.getElementById('viewImei').textContent = claim.imei;
    document.getElementById('viewIssueType').textContent = claim.issue_type;
    document.getElementById('viewIssueDescription').textContent = claim.issue_description || 'Không có mô tả chi tiết từ khách.';
    document.getElementById('viewClaimMethod').textContent = claim.claim_method;
    document.getElementById('viewStatusBadge').textContent = claim.status;
    document.getElementById('viewAdminNote').textContent = claim.admin_note ? ('Ghi chú: ' + claim.admin_note) : 'Chưa có ghi chú kỹ thuật viên.';

    const modalEl = document.getElementById('viewClaimModal');
    if (modalEl && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

function appendNote(text) {
    const noteInput = document.getElementById('modalAdminNoteInput');
    if (!noteInput) return;
    if (noteInput.value.trim() === '') {
        noteInput.value = text;
    } else {
        noteInput.value += ' ' + text;
    }
    noteInput.focus();
}
</script>

<?php require_once 'includes/footer.php'; ?>
