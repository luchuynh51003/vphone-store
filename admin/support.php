<?php
/**
 * V-Phone Store 2026 - Quản Lý Hỗ Trợ Khách Hàng & CSKH Đa Kênh
 * Phân quyền: Quản trị viên (Admin), Chuyên viên CSKH (support), Nhân viên Bán hàng (orders), Kỹ thuật viên (warranties)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/../app/models/NotificationModel.php';

// Đảm bảo cấu trúc bảng callback_requests đầy đủ
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS callback_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            fullname VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            email VARCHAR(100) NULL,
            topic VARCHAR(100) DEFAULT 'Tư vấn mua máy',
            department VARCHAR(50) DEFAULT 'cskh',
            note TEXT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            reply_message TEXT NULL,
            replied_by INT NULL,
            replied_by_name VARCHAR(100) NULL,
            replied_by_title VARCHAR(100) NULL,
            replied_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (phone),
            INDEX (status),
            INDEX (department)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $cols = $pdo->query("SHOW COLUMNS FROM callback_requests")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('email', $cols)) $pdo->exec("ALTER TABLE callback_requests ADD COLUMN email VARCHAR(100) NULL AFTER phone");
    if (!in_array('department', $cols)) $pdo->exec("ALTER TABLE callback_requests ADD COLUMN department VARCHAR(50) DEFAULT 'cskh' AFTER topic");
    if (!in_array('reply_message', $cols)) $pdo->exec("ALTER TABLE callback_requests ADD COLUMN reply_message TEXT NULL AFTER note");
    if (!in_array('replied_by', $cols)) $pdo->exec("ALTER TABLE callback_requests ADD COLUMN replied_by INT NULL AFTER reply_message");
    if (!in_array('replied_by_name', $cols)) $pdo->exec("ALTER TABLE callback_requests ADD COLUMN replied_by_name VARCHAR(100) NULL AFTER replied_by");
    if (!in_array('replied_by_title', $cols)) $pdo->exec("ALTER TABLE callback_requests ADD COLUMN replied_by_title VARCHAR(100) NULL AFTER replied_by_name");
    if (!in_array('replied_at', $cols)) $pdo->exec("ALTER TABLE callback_requests ADD COLUMN replied_at DATETIME NULL AFTER replied_by_title");
} catch (\Throwable $e) {}

// Xử lý xuất file CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'vphone_cskh_ho_tro_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Mã YC', 'Thời gian gửi', 'Họ và tên', 'Số điện thoại', 'Email', 'Bộ phận', 'Chủ đề', 'Nội dung yêu cầu', 'Trạng thái', 'Nhân viên phản hồi', 'Chức danh', 'Nội dung phản hồi', 'Thời gian phản hồi']);

    $exportQuery = $pdo->query("SELECT * FROM callback_requests ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $deptLabels = [
        'cskh' => 'CSKH & Tư vấn',
        'sales' => 'Bán hàng & Báo giá',
        'warranty' => 'Kỹ thuật & Bảo hành',
        'installment' => 'Trả góp & Thu cũ',
        'shipping' => 'Đơn hàng & Giao nhận'
    ];
    $statusLabels = [
        'pending' => 'Chờ xử lý',
        'processing' => 'Đang xử lý',
        'replied' => 'Đã phản hồi',
        'closed' => 'Đã đóng'
    ];

    foreach ($exportQuery as $r) {
        fputcsv($output, [
            'YC-' . str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT),
            $r['created_at'],
            $r['fullname'],
            $r['phone'],
            $r['email'] ?: 'Chưa có',
            $deptLabels[$r['department'] ?? 'cskh'] ?? 'CSKH & Tư vấn',
            $r['topic'],
            $r['note'] ?: '',
            $statusLabels[$r['status'] ?? 'pending'] ?? 'Chờ xử lý',
            $r['replied_by_name'] ?: 'Chưa phản hồi',
            $r['replied_by_title'] ?: '',
            $r['reply_message'] ?: '',
            $r['replied_at'] ?: ''
        ]);
    }
    fclose($output);
    exit;
}

$pageTitle = 'Hỗ Trợ & CSKH Đa Kênh - V-Phone Admin';

$message = '';
$error = '';
$currentUserId = (int)($_SESSION['user']['id'] ?? 0);
$currentUserName = (string)($_SESSION['user']['fullname'] ?? 'Chuyên viên V-Phone');
$currentUserTitle = getStaffTitleDisplay();
$isAdmin = isCurrentAdmin();
$isStaff = isCurrentStaff();

// XỬ LÝ PHẢN HỒI YÊU CẦU TỪ CSKH / NHÂN VIÊN
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply_support') {
    $reqId = (int)($_POST['request_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? 'replied');
    $replyText = trim($_POST['reply_message'] ?? '');
    $notifyUser = isset($_POST['notify_customer']) ? (int)$_POST['notify_customer'] : 1;

    $reqStmt = $pdo->prepare("SELECT * FROM callback_requests WHERE id = ?");
    $reqStmt->execute([$reqId]);
    $targetReq = $reqStmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetReq) {
        $error = "Không tìm thấy yêu cầu hỗ trợ #$reqId cần xử lý!";
    } elseif (empty($replyText)) {
        $error = "Vui lòng nhập nội dung phản hồi / ghi chú tư vấn cho khách hàng!";
    } else {
        $upStmt = $pdo->prepare("
            UPDATE callback_requests 
            SET status = ?, 
                reply_message = ?, 
                replied_by = ?, 
                replied_by_name = ?, 
                replied_by_title = ?, 
                replied_at = NOW() 
            WHERE id = ?
        ");
        $ok = $upStmt->execute([
            $newStatus,
            $replyText,
            $currentUserId,
            $currentUserName,
            $currentUserTitle,
            $reqId
        ]);

        if ($ok) {
            $message = "Đã cập nhật phản hồi cho yêu cầu <strong>#YC-" . str_pad((string)$reqId, 4, '0', STR_PAD_LEFT) . " (" . htmlspecialchars($targetReq['fullname']) . ")</strong> thành công!";

            // Gửi thông báo đến tài khoản khách hàng nếu có liên kết user_id
            if (!empty($targetReq['user_id']) && $notifyUser) {
                try {
                    $notifModel = new NotificationModel($pdo);
                    $notifTitle = "🎧 CSKH V-Phone đã phản hồi yêu cầu #" . str_pad((string)$reqId, 4, '0', STR_PAD_LEFT);
                    $notifContent = "Chuyên viên {$currentUserName} ({$currentUserTitle}) đã phản hồi yêu cầu của bạn: \"{$replyText}\"";
                    $notifModel->create(
                        (int)$targetReq['user_id'],
                        $targetReq['phone'],
                        null,
                        $notifTitle,
                        $notifContent,
                        null,
                        'support_reply'
                    );
                } catch (\Throwable $e) {}
            }
        } else {
            $error = "Có lỗi xảy ra khi lưu phản hồi. Vui lòng thử lại!";
        }
    }
}

// XỬ LÝ XÓA YÊU CẦU (Chỉ Quản trị viên hoặc Super Admin)
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    if (!$isAdmin) {
        $error = "Từ chối quyền hạn! Chỉ có Quản trị viên mới có quyền xóa yêu cầu hỗ trợ.";
    } else {
        $delStmt = $pdo->prepare("DELETE FROM callback_requests WHERE id = ?");
        $delStmt->execute([$delId]);
        $message = "Đã xóa yêu cầu hỗ trợ #YC-" . str_pad((string)$delId, 4, '0', STR_PAD_LEFT) . " thành công!";
    }
}

// LẤY DANH SÁCH YÊU CẦU & THỐNG KÊ
$allRequests = $pdo->query("SELECT * FROM callback_requests ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Thống kê nhanh
$cntPending = 0;
$cntProcessing = 0;
$cntReplied = 0;
$cntClosed = 0;
foreach ($allRequests as $r) {
    $st = $r['status'] ?? 'pending';
    if ($st === 'pending') $cntPending++;
    elseif ($st === 'processing') $cntProcessing++;
    elseif ($st === 'replied') $cntReplied++;
    elseif ($st === 'closed') $cntClosed++;
}

// Lấy danh sách user_id để liên kết thông tin hạng thành viên
$uMap = [];
$uIds = array_filter(array_column($allRequests, 'user_id'));
if (!empty($uIds)) {
    $inClause = implode(',', array_map('intval', array_unique($uIds)));
    $uRows = $pdo->query("SELECT id, fullname, email, role, avatar FROM users WHERE id IN ($inClause)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($uRows as $ur) {
        $uMap[$ur['id']] = $ur;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-black text-dark mb-1 d-flex align-items-center gap-2">
            <i class="fa-solid fa-headset text-primary"></i>
            <span>Hỗ Trợ & CSKH Đa Kênh</span>
        </h3>
        <p class="text-secondary small mb-0">Tiếp nhận yêu cầu từ nút liên hệ nhanh nổi đa kênh của khách hàng, gọi điện tư vấn và gửi phản hồi giải đáp</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="support.php?export=csv" class="btn btn-outline-success btn-sm rounded-pill fw-bold px-3 py-1.5 shadow-xs d-inline-flex align-items-center gap-1.5">
            <i class="fa-solid fa-file-excel text-success"></i>
            <span>Xuất Excel / CSV</span>
        </a>
        <a href="../index.php" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill fw-bold px-3 py-1.5 shadow-xs d-inline-flex align-items-center gap-1.5">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            <span>Xem Nút Nổi Trên Web</span>
        </a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 py-2.5 px-3 mb-4 shadow-sm d-flex align-items-center" role="alert">
        <i class="fa-solid fa-check-circle me-2 text-success fs-5"></i>
        <div class="flex-grow-1"><?= $message ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 py-2.5 px-3 mb-4 shadow-sm d-flex align-items-center" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2 text-danger fs-5"></i>
        <div class="flex-grow-1"><?= $error ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- THẺ THỐNG KÊ NHANH -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white d-flex flex-column justify-content-between h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-semibold">Chờ Phản Hồi</span>
                <span class="rounded-circle bg-danger bg-opacity-10 text-danger p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-clock-rotate-left small"></i>
                </span>
            </div>
            <div class="fs-4 fw-black text-danger"><?= $cntPending ?></div>
            <small class="text-muted" style="font-size: 0.72rem;">Khách yêu cầu gọi lại / tư vấn</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white d-flex flex-column justify-content-between h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-semibold">Đang Xử Lý</span>
                <span class="rounded-circle bg-warning bg-opacity-10 text-warning p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-phone-volume small"></i>
                </span>
            </div>
            <div class="fs-4 fw-black text-warning"><?= $cntProcessing ?></div>
            <small class="text-muted" style="font-size: 0.72rem;">Đang liên hệ / chuẩn bị máy</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white d-flex flex-column justify-content-between h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-semibold">Đã Phản Hồi</span>
                <span class="rounded-circle bg-success bg-opacity-10 text-success p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-check small"></i>
                </span>
            </div>
            <div class="fs-4 fw-black text-success"><?= $cntReplied ?></div>
            <small class="text-muted" style="font-size: 0.72rem;">Đã tư vấn & gửi phản hồi</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white d-flex flex-column justify-content-between h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-semibold">Tổng Yêu Cầu</span>
                <span class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-comments small"></i>
                </span>
            </div>
            <div class="fs-4 fw-black text-dark"><?= count($allRequests) ?></div>
            <small class="text-muted" style="font-size: 0.72rem;">Tất cả liên hệ từ widget nổi</small>
        </div>
    </div>
</div>

<!-- KHUNG DANH SÁCH YÊU CẦU & BỘ LỌC -->
<div class="admin-table-card">
    <div class="p-3.5 px-4 border-bottom bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 580px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" id="supportSearch" class="form-control border-start-0 bg-light" placeholder="Tìm theo tên khách, SĐT, chủ đề, nội dung lời nhắn..." onkeyup="filterSupportTable()">
            </div>
            <select id="supportStatusFilter" class="form-select form-select-sm bg-light" style="max-width: 160px;" onchange="filterSupportTable()">
                <option value="all">Tất cả trạng thái</option>
                <option value="pending">Chờ phản hồi (<?= $cntPending ?>)</option>
                <option value="processing">Đang xử lý (<?= $cntProcessing ?>)</option>
                <option value="replied">Đã phản hồi (<?= $cntReplied ?>)</option>
                <option value="closed">Đã hoàn tất (<?= $cntClosed ?>)</option>
            </select>
            <select id="supportDeptFilter" class="form-select form-select-sm bg-light" style="max-width: 160px;" onchange="filterSupportTable()">
                <option value="all">Tất cả bộ phận</option>
                <option value="cskh">CSKH & Tư vấn</option>
                <option value="sales">Bán hàng & Báo giá</option>
                <option value="warranty">Kỹ thuật & Bảo hành</option>
                <option value="installment">Trả góp & Thu cũ</option>
                <option value="shipping">Đơn hàng & Giao nhận</option>
            </select>
        </div>
        <div>
            <span class="badge bg-light text-dark border rounded-pill px-3 py-1.5 fw-bold small">
                Hiển thị: <span id="visibleCount"><?= count($allRequests) ?></span> / <?= count($allRequests) ?> yêu cầu
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0" id="supportTable">
            <thead>
                <tr>
                    <th style="width: 80px;">Mã YC</th>
                    <th>Khách Hàng & Liên Hệ</th>
                    <th style="min-width: 170px;">Bộ Phận & Chủ Đề</th>
                    <th style="min-width: 240px;">Lời Nhắn Khách Hàng</th>
                    <th class="text-center" style="width: 130px;">Trạng Thái</th>
                    <th style="min-width: 220px;">Phản Hồi Từ CSKH</th>
                    <th class="text-end pe-3" style="min-width: 170px;">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allRequests)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="mb-2"><i class="fa-solid fa-headset fs-1 text-secondary opacity-50"></i></div>
                            <div class="fw-bold">Chưa có yêu cầu liên hệ nào từ khách hàng</div>
                            <small>Khi khách hàng gửi yêu cầu từ nút liên hệ nhanh nổi đa kênh trên website, danh sách sẽ hiển thị tại đây.</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($allRequests as $r): ?>
                        <?php
                            $st = $r['status'] ?? 'pending';
                            $dept = $r['department'] ?? 'cskh';
                            $hasReply = !empty($r['reply_message']);
                            $linkedUser = !empty($r['user_id']) ? ($uMap[$r['user_id']] ?? null) : null;

                            // Search text blob
                            $searchBlob = mb_strtolower(
                                ($r['fullname'] ?? '') . ' ' . 
                                ($r['phone'] ?? '') . ' ' . 
                                ($r['email'] ?? '') . ' ' . 
                                ($r['topic'] ?? '') . ' ' . 
                                ($r['note'] ?? '') . ' ' . 
                                ($r['replied_by_name'] ?? '') . ' ' . 
                                ($r['reply_message'] ?? ''),
                                'UTF-8'
                            );
                        ?>
                        <tr class="support-row" data-search="<?= htmlspecialchars($searchBlob) ?>" data-status="<?= htmlspecialchars($st) ?>" data-dept="<?= htmlspecialchars($dept) ?>">
                            <td class="fw-bold font-monospace text-secondary">
                                #YC-<?= str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT) ?>
                                <small class="d-block text-muted" style="font-size: 0.68rem;"><?= date('H:i d/m', strtotime($r['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="table-user-cell">
                                    <div class="table-user-avatar" style="width: 40px; height: 40px; background: linear-gradient(135deg, #0284c7, #0066cc); font-weight: 700; font-size: 0.95rem;">
                                        <?= mb_substr(htmlspecialchars($r['fullname']), 0, 1, 'UTF-8') ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                            <span class="user-fullname"><?= htmlspecialchars($r['fullname']) ?></span>
                                            <?php if ($linkedUser): ?>
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 0.65rem;" title="Khách hàng đã có tài khoản trên website">
                                                    <i class="fa-solid fa-user-check me-1"></i>Thành viên
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">Khách vãng lai</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 user-subtext mt-0.5 flex-wrap">
                                            <a href="tel:<?= htmlspecialchars($r['phone']) ?>" class="text-decoration-none text-dark fw-bold font-monospace hover-primary" title="Gọi số này">
                                                <i class="fa-solid fa-phone text-success me-1"></i><?= htmlspecialchars($r['phone']) ?>
                                            </a>
                                            <?php if (!empty($r['email'])): ?>
                                                <span class="opacity-50">&bull;</span>
                                                <span class="text-muted"><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($r['email']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($dept === 'sales'): ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-mobile-screen me-1"></i>Bán Hàng & Báo Giá
                                    </span>
                                <?php elseif ($dept === 'warranty'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-shield-halved me-1"></i>Kỹ Thuật & Bảo Hành
                                    </span>
                                <?php elseif ($dept === 'installment'): ?>
                                    <span class="badge bg-warning bg-opacity-15 text-dark border border-warning-subtle rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-credit-card me-1 text-warning"></i>Trả Góp 0%
                                    </span>
                                <?php elseif ($dept === 'shipping'): ?>
                                    <span class="badge bg-info bg-opacity-15 text-dark border border-info-subtle rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-truck-fast me-1 text-info"></i>Đơn & Vận Chuyển
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-purple bg-opacity-10 text-purple border border-purple-subtle rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.72rem; color: #7c3aed; background: #f5f3ff; border-color: #ddd6fe;">
                                        <i class="fa-solid fa-headset me-1"></i>Bộ phận CSKH
                                    </span>
                                <?php endif; ?>
                                <div class="text-dark fw-semibold small mt-1 text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($r['topic'] ?? '') ?>">
                                    <?= htmlspecialchars($r['topic'] ?: 'Tư vấn mua máy') ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($r['note'])): ?>
                                    <div class="p-2.5 bg-light rounded-3 border small text-dark" style="max-width: 320px; line-height: 1.45;">
                                        <?= nl2br(htmlspecialchars($r['note'])) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic"><i class="fa-regular fa-comment-dots me-1"></i>Khách yêu cầu gọi lại trực tiếp qua SĐT</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($st === 'pending'): ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-bold small d-inline-flex align-items-center gap-1.5">
                                        <span class="pulse-indicator bg-danger" style="width: 7px; height: 7px; border-radius: 50%;"></span>
                                        <span>Chờ xử lý</span>
                                    </span>
                                <?php elseif ($st === 'processing'): ?>
                                    <span class="badge bg-warning bg-opacity-15 text-dark border border-warning-subtle rounded-pill px-2.5 py-1 fw-bold small d-inline-flex align-items-center gap-1.5">
                                        <i class="fa-solid fa-phone-volume text-warning"></i>
                                        <span>Đang liên hệ</span>
                                    </span>
                                <?php elseif ($st === 'replied'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-bold small d-inline-flex align-items-center gap-1.5">
                                        <i class="fa-solid fa-check"></i>
                                        <span>Đã phản hồi</span>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2.5 py-1 fw-bold small">
                                        Đã đóng
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($hasReply): ?>
                                    <div class="p-2.5 bg-success bg-opacity-10 rounded-3 border border-success-subtle small" style="max-width: 320px;">
                                        <div class="d-flex align-items-center gap-1.5 mb-1">
                                            <i class="fa-solid fa-reply text-success" style="font-size: 0.72rem;"></i>
                                            <span class="fw-bold text-dark" style="font-size: 0.75rem;"><?= htmlspecialchars($r['replied_by_name'] ?: 'Nhân viên CSKH') ?></span>
                                            <?php if (!empty($r['replied_by_title'])): ?>
                                                <span class="badge bg-white border text-secondary rounded-pill px-1.5 py-0.5" style="font-size: 0.62rem;"><?= htmlspecialchars($r['replied_by_title']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-dark small lh-sm mb-1"><?= nl2br(htmlspecialchars($r['reply_message'])) ?></div>
                                        <small class="text-muted d-block" style="font-size: 0.65rem;">
                                            <i class="fa-regular fa-clock me-1"></i><?= date('H:i d/m/Y', strtotime($r['replied_at'])) ?>
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted small fst-italic">
                                        <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>Chưa có nhân viên phản hồi
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap pe-3">
                                <div class="d-inline-flex justify-content-end align-items-center gap-1.5">
                                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 py-1 fw-bold shadow-xs d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalReply<?= $r['id'] ?>" title="Gửi phản hồi / Xử lý yêu cầu">
                                        <i class="fa-solid fa-comment-dots"></i>
                                        <span><?= $hasReply ? 'Cập nhật' : 'Phản hồi' ?></span>
                                    </button>

                                    <a href="tel:<?= htmlspecialchars($r['phone']) ?>" class="btn btn-outline-success btn-sm rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Gọi điện cho khách">
                                        <i class="fa-solid fa-phone small"></i>
                                    </a>

                                    <?php if ($isAdmin): ?>
                                        <a href="support.php?delete_id=<?= $r['id'] ?>" class="btn btn-light border text-danger btn-sm rounded-circle d-inline-flex align-items-center justify-content-center hover-danger" style="width: 32px; height: 32px;" title="Xóa yêu cầu này" onclick="return confirm('Bạn có chắc muốn xóa yêu cầu hỗ trợ #YC-<?= str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT) ?>?');">
                                            <i class="fa-solid fa-trash-can small"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- CÁC MODAL PHẢN HỒI YÊU CẦU DÀNH CHO CSKH & CÁC CHỨC VỤ LIÊN QUAN -->
<?php foreach ($allRequests as $r): ?>
    <div class="modal fade" id="modalReply<?= $r['id'] ?>" tabindex="-1" aria-labelledby="modalReplyLabel<?= $r['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <form method="POST" action="support.php" autocomplete="off">
                    <input type="hidden" name="action" value="reply_support">
                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">

                    <div class="modal-header border-0 pb-0 pt-4 px-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2.5 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="fa-solid fa-headset fs-5"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0" id="modalReplyLabel<?= $r['id'] ?>">Xử Lý & Phản Hồi Yêu Cầu #YC-<?= str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT) ?></h5>
                                <small class="text-secondary">Khách hàng: <strong><?= htmlspecialchars($r['fullname']) ?></strong> &bull; SĐT: <a href="tel:<?= htmlspecialchars($r['phone']) ?>" class="fw-bold text-primary font-monospace"><?= htmlspecialchars($r['phone']) ?></a></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <!-- Tóm tắt yêu cầu của khách hàng -->
                        <div class="p-3 bg-light rounded-4 border mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary border rounded-pill px-2.5 py-1 fw-bold small">
                                    <i class="fa-solid fa-tag me-1"></i>Chủ đề: <?= htmlspecialchars($r['topic'] ?: 'Tư vấn mua máy') ?>
                                </span>
                                <small class="text-muted"><i class="fa-regular fa-clock me-1"></i>Gửi lúc: <?= date('H:i d/m/Y', strtotime($r['created_at'])) ?></small>
                            </div>
                            <div class="text-dark small">
                                <strong>Lời nhắn của khách:</strong><br>
                                <span class="fst-italic text-secondary"><?= nl2br(htmlspecialchars($r['note'] ?: 'Khách hàng bấm yêu cầu tư vấn gọi lại nhanh trong 5 phút từ nút liên hệ nổi.')) ?></span>
                            </div>
                            <?php if (!empty($r['email'])): ?>
                                <div class="small text-muted mt-2">
                                    <i class="fa-regular fa-envelope me-1"></i>Email khách: <span class="text-dark fw-medium"><?= htmlspecialchars($r['email']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Cập nhật trạng thái xử lý -->
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Trạng thái xử lý <span class="text-danger">*</span></label>
                                <select name="status" class="form-select rounded-3" id="selectStatus<?= $r['id'] ?>">
                                    <option value="pending" <?= ($r['status'] === 'pending') ? 'selected' : '' ?>>🟡 Chờ xử lý</option>
                                    <option value="processing" <?= ($r['status'] === 'processing') ? 'selected' : '' ?>>🔵 Đang liên hệ / Chuẩn bị thông tin</option>
                                    <option value="replied" <?= ($r['status'] === 'replied' || $r['status'] === 'pending') ? 'selected' : '' ?>>🟢 Đã gọi điện tư vấn / Đã phản hồi</option>
                                    <option value="closed" <?= ($r['status'] === 'closed') ? 'selected' : '' ?>>⚪ Đã giải quyết & Hoàn tất</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Chức vụ & Người phản hồi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-primary"><i class="fa-solid fa-user-check"></i></span>
                                    <input type="text" class="form-control bg-light border-start-0 fw-semibold" value="<?= htmlspecialchars($currentUserName) ?> (<?= htmlspecialchars($currentUserTitle) ?>)" readonly disabled>
                                </div>
                                <small class="text-muted" style="font-size: 0.7rem;">Chức danh của bạn sẽ tự động được hiển thị cho khách hàng</small>
                            </div>
                        </div>

                        <!-- Mẫu phản hồi nhanh tiện dụng -->
                        <div class="mb-3 p-3 bg-light rounded-4 border">
                            <label class="form-label small fw-bold text-dark mb-2 d-flex align-items-center gap-1.5">
                                <i class="fa-solid fa-wand-magic-sparkles text-primary"></i>
                                <span>Chọn Nhanh Mẫu Phản Hồi Tiện Lợi:</span>
                            </label>
                            <div class="d-flex flex-wrap gap-1.5">
                                <button type="button" class="btn btn-sm btn-white border rounded-pill px-2.5 py-1 text-dark small fw-semibold" onclick="setSupportReply(<?= $r['id'] ?>, 'Dạ em chào anh/chị <?= htmlspecialchars(addslashes($r['fullname'])) ?>, chuyên viên V-Phone đã gọi điện tư vấn chi tiết về máy và chính sách ưu đãi thành công. Cảm ơn anh/chị đã quan tâm!')">
                                    <i class="fa-solid fa-phone me-1 text-success"></i>Đã gọi tư vấn thành công
                                </button>
                                <button type="button" class="btn btn-sm btn-white border rounded-pill px-2.5 py-1 text-dark small fw-semibold" onclick="setSupportReply(<?= $r['id'] ?>, 'Dạ em đã kiểm tra tình trạng máy và chính sách bảo hành 1 đổi 1. Kính mời anh/chị ghé showroom V-Phone gần nhất để kỹ thuật viên hỗ trợ kiểm tra trực tiếp ạ!')">
                                    <i class="fa-solid fa-shield-halved me-1 text-info"></i>Hướng dẫn bảo hành
                                </button>
                                <button type="button" class="btn btn-sm btn-white border rounded-pill px-2.5 py-1 text-dark small fw-semibold" onclick="setSupportReply(<?= $r['id'] ?>, 'Dạ hồ sơ trả góp 0% của anh/chị đã được chuyên viên tài chính tiếp nhận và hướng dẫn chuẩn bị CCCD để duyệt hồ sơ online trong 15 phút ạ!')">
                                    <i class="fa-solid fa-credit-card me-1 text-warning"></i>Tư vấn trả góp 0%
                                </button>
                                <button type="button" class="btn btn-sm btn-white border rounded-pill px-2.5 py-1 text-dark small fw-semibold" onclick="setSupportReply(<?= $r['id'] ?>, 'Dạ chuyên viên V-Phone đã liên hệ qua số điện thoại nhưng chưa liên lạc được. Em sẽ gọi lại cho anh/chị sau ít phút nữa ạ!')">
                                    <i class="fa-solid fa-phone-slash me-1 text-danger"></i>Chưa liên lạc được
                                </button>
                            </div>
                        </div>

                        <!-- Khung soạn nội dung phản hồi -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark d-flex align-items-center justify-content-between">
                                <span>Nội dung phản hồi / Ghi chú tư vấn của nhân viên <span class="text-danger">*</span></span>
                                <span class="text-muted small" style="font-size: 0.72rem;">Nội dung này sẽ hiển thị cho khách hàng & đồng nghiệp</span>
                            </label>
                            <textarea name="reply_message" id="replyMessage<?= $r['id'] ?>" class="form-control rounded-3" rows="3" placeholder="Nhập câu trả lời, giải đáp thắc mắc hoặc ghi chú kết quả cuộc gọi tư vấn..." required><?= htmlspecialchars($r['reply_message'] ?? '') ?></textarea>
                        </div>

                        <!-- Tùy chọn gửi thông báo đến tài khoản khách hàng -->
                        <?php if (!empty($r['user_id'])): ?>
                            <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                <input class="form-check-input ms-0 me-2.5" type="checkbox" name="notify_customer" value="1" id="notifyCust<?= $r['id'] ?>" checked>
                                <label class="form-check-label small fw-bold text-dark" for="notifyCust<?= $r['id'] ?>">
                                    <i class="fa-regular fa-bell text-primary me-1"></i> Gửi thông báo phản hồi ngay tới tài khoản website của khách hàng
                                </label>
                                <small class="text-muted d-block mt-0.5 ps-4" style="font-size: 0.72rem;">Khách hàng sẽ nhận được chuông thông báo nổi trên thanh điều hướng website khi đăng nhập.</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold text-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-1.5">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Lưu & Gửi Phản Hồi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
function filterSupportTable() {
    const q = document.getElementById('supportSearch').value.toLowerCase().trim();
    const stFilter = document.getElementById('supportStatusFilter').value;
    const deptFilter = document.getElementById('supportDeptFilter').value;

    let visible = 0;
    document.querySelectorAll('.support-row').forEach(row => {
        const text = row.getAttribute('data-search') || '';
        const st = row.getAttribute('data-status') || '';
        const dept = row.getAttribute('data-dept') || '';

        const matchQ = !q || text.includes(q);
        const matchSt = (stFilter === 'all') || (st === stFilter);
        const matchDept = (deptFilter === 'all') || (dept === deptFilter);

        if (matchQ && matchSt && matchDept) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    const cntEl = document.getElementById('visibleCount');
    if (cntEl) cntEl.textContent = visible;
}

function setSupportReply(reqId, text) {
    const textarea = document.getElementById('replyMessage' + reqId);
    if (textarea) {
        textarea.value = text;
        textarea.focus();
    }
    const select = document.getElementById('selectStatus' + reqId);
    if (select && select.value === 'pending') {
        select.value = 'replied';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
