<?php
/**
 * V-Phone Store - Quản Lý Nhân Sự & Phân Quyền
 * Năm: 2026
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/UserModel.php';
require_once __DIR__ . '/includes/permissions.php';

$userModel = new UserModel($pdo);
$userModel->ensureStaffColumns();

// Chỉ có Quản trị viên (Admin) hoặc Super Admin mới được vào trang này
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php?show_login=1');
    exit;
}
if ((int)($_SESSION['user']['role'] ?? 0) !== 1) {
    header('Location: staff_dashboard.php');
    exit;
}

// Xử lý xuất CSV danh sách nhân sự
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'vphone_nhan_su_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Mã NV', 'Họ và tên', 'Email', 'Số điện thoại', 'Vai trò', 'Chức danh', 'Quyền hạn', 'Trạng thái', 'Ngày tạo']);

    $staffListExport = $userModel->getStaffList();
    foreach ($staffListExport as $s) {
        if ((int)$s['role'] === 1) {
            $permsText = 'Toàn quyền hệ thống (Admin)';
        } elseif (!empty($s['permissions'])) {
            $decoded = json_decode($s['permissions'], true);
            $names = [];
            if (is_array($decoded)) {
                foreach ($decoded as $p) {
                    if (isset(VPHONE_PERMISSIONS[$p])) $names[] = VPHONE_PERMISSIONS[$p]['name'];
                }
            }
            $permsText = !empty($names) ? implode(', ', $names) : 'Chưa phân quyền';
        } else {
            $permsText = 'Chưa phân quyền';
        }

        fputcsv($output, [
            'NV-' . str_pad((string)$s['id'], 4, '0', STR_PAD_LEFT),
            $s['fullname'],
            $s['email'],
            $s['phone'] ?: 'Chưa có',
            (int)$s['role'] === 1 ? 'Quản trị viên' : 'Nhân viên',
            $s['staff_title'] ?: '',
            $permsText,
            ((int)($s['status'] ?? 1) === 1) ? 'Đang hoạt động' : 'Tạm khóa',
            $s['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$pageTitle = 'Quản Lý Nhân Sự & Phân Quyền - V-Phone Admin';
$message = '';
$error = '';
$isSuperAdmin = isCurrentSuperAdmin();

// Xử lý THÊM NHÂN VIÊN MỚI
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_staff') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $staffRole = trim($_POST['staff_role'] ?? 'staff');
    $staffTitle = trim($_POST['staff_title'] ?? 'Nhân viên cửa hàng');
    $assignedPerms = $_POST['permissions'] ?? [];
    if (!is_array($assignedPerms)) $assignedPerms = [];

    if (empty($fullname) || mb_strlen($fullname, 'UTF-8') < 2) {
        $error = 'Họ và tên nhân viên không được để trống (tối thiểu 2 ký tự)!';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ email không đúng định dạng!';
    } elseif ($userModel->isEmailTaken($email)) {
        $error = "Email '$email' đã được đăng ký bởi tài khoản khác trong hệ thống!";
    } elseif (empty($password) || mb_strlen($password, 'UTF-8') < 6) {
        $error = 'Mật khẩu khởi tạo phải từ 6 ký tự trở lên!';
    } else {
        $newStaffId = $userModel->createStaff(
            $fullname,
            $email,
            $phone,
            $password,
            $staffRole,
            $staffTitle,
            $assignedPerms,
            'Cửa hàng V-Phone Store',
            2 // role = 2 (Nhân viên)
        );
        if ($newStaffId) {
            $message = "Đã tạo tài khoản nhân viên mới <strong>#NV-" . str_pad((string)$newStaffId, 4, '0', STR_PAD_LEFT) . " ($fullname)</strong> thành công!";
        } else {
            $error = 'Có lỗi xảy ra khi tạo tài khoản nhân viên. Vui lòng thử lại!';
        }
    }
}

// Xử lý CẬP NHẬT NHÂN VIÊN & PHÂN QUYỀN
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_staff') {
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $fullname = trim($_POST['fullname'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $staffRole = trim($_POST['staff_role'] ?? 'staff');
    $staffTitle = trim($_POST['staff_title'] ?? 'Nhân viên cửa hàng');
    $newPassword = trim($_POST['new_password'] ?? '');
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $assignedPerms = $_POST['permissions'] ?? [];
    if (!is_array($assignedPerms)) $assignedPerms = [];

    $targetStaff = $userModel->findById($staffId);
    if (!$targetStaff) {
        $error = 'Không tìm thấy tài khoản nhân viên cần sửa!';
    } elseif (empty($fullname) || mb_strlen($fullname, 'UTF-8') < 2) {
        $error = 'Họ và tên không được để trống!';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ!';
    } elseif ($userModel->isEmailTaken($email, $staffId)) {
        $error = "Email '$email' đã thuộc về tài khoản khác!";
    } elseif (!empty($newPassword) && mb_strlen($newPassword, 'UTF-8') < 6) {
        $error = 'Mật khẩu mới phải có tối thiểu 6 ký tự!';
        // Bảo vệ tài khoản Super Admin không bị khóa hay hạ quyền
        if (strtolower(trim($targetStaff['email'])) === 'admin@vphone.vn') {
            $status = 1;
            $email = 'admin@vphone.vn';
            $assignedPerms = array_keys(VPHONE_PERMISSIONS);
        }

        // Phân quyền bảo mật: Admin thường không thể sửa Admin khác
        if ((int)$targetStaff['role'] === 1 && !$isSuperAdmin && $staffId !== (int)$_SESSION['user']['id']) {
            $error = 'Chỉ có Quản trị tối cao (admin@vphone.vn) mới có quyền chỉnh sửa tài khoản Quản trị viên khác!';
        }

        if (empty($error)) {
            $ok = $userModel->updateStaff(
                $staffId,
                $fullname,
                $email,
                $phone,
                $staffRole,
                $staffTitle,
                $assignedPerms,
                $newPassword ?: null,
                $status
            );

            if ($ok) {
                $message = "Đã cập nhật phân quyền và thông tin cho <strong>$fullname</strong> thành công!";
                // Nếu người dùng đang chỉnh sửa chính mình, đồng bộ session
                if ($staffId === (int)$_SESSION['user']['id']) {
                    $_SESSION['user']['fullname'] = $fullname;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['user']['phone'] = $phone;
                    $_SESSION['user']['staff_title'] = $staffTitle;
                    $_SESSION['user']['permissions'] = $assignedPerms;
                }
            } else {
                $error = 'Có lỗi xảy ra khi cập nhật thông tin nhân viên!';
            }
        }
    }
}

// Xử lý BẬT/TẮT TRẠNG THÁI (Khóa / Kích hoạt)
if (isset($_GET['toggle_status_id'])) {
    $tId = (int)$_GET['toggle_status_id'];
    $tUser = $userModel->findById($tId);
    if ($tUser && strtolower(trim($tUser['email'])) === 'admin@vphone.vn') {
        $error = 'Không thể khóa tài khoản Quản trị tối cao (admin@vphone.vn)!';
    } elseif ($tUser && (int)$tUser['role'] === 1 && !$isSuperAdmin) {
        $error = 'Chỉ có Quản trị tối cao (admin@vphone.vn) mới có quyền khóa/mở tài khoản Quản trị viên!';
    } elseif ($tUser) {
        $userModel->toggleUserStatus($tId);
        $newStText = ((int)($tUser['status'] ?? 1) === 1) ? 'tạm khóa' : 'kích hoạt hoạt động';
        $message = "Đã $newStText tài khoản nhân viên <strong>#" . htmlspecialchars($tUser['fullname']) . "</strong> thành công!";
    }
}

// Xử lý XÓA NHÂN VIÊN
if (isset($_GET['delete_staff_id'])) {
    $dId = (int)$_GET['delete_staff_id'];
    $dUser = $userModel->findById($dId);
    if ($dUser && strtolower(trim($dUser['email'])) === 'admin@vphone.vn') {
        $error = 'Không thể xóa tài khoản Quản trị tối cao!';
    } elseif ($dUser && (int)$dUser['role'] === 1 && !$isSuperAdmin) {
        $error = 'Chỉ có Quản trị tối cao (admin@vphone.vn) mới có quyền xóa tài khoản Quản trị viên!';
    } elseif ($dUser) {
        $userModel->deleteStaff($dId);
        $message = "Đã xóa tài khoản nhân viên <strong>#" . htmlspecialchars($dUser['fullname']) . "</strong> khỏi hệ thống!";
    }
}

// Lấy danh sách nhân sự (role = 1 hoặc role = 2)
$staffList = $userModel->getStaffList();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-black text-dark mb-1 d-flex align-items-center gap-2">
            <i class="fa-solid fa-user-shield text-warning"></i>
            <span>Quản Lý Nhân Sự & Phân Quyền</span>
        </h3>
        <p class="text-secondary small mb-0">Tạo tài khoản cho nhân viên, quản lý chức danh và chỉ định chính xác các nhiệm vụ nhân viên được quyền thao tác</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="staff.php?export=csv" class="btn btn-outline-success btn-sm rounded-pill fw-bold px-3 py-1.5 shadow-xs d-inline-flex align-items-center gap-1.5">
            <i class="fa-solid fa-file-excel text-success"></i>
            <span>Xuất CSV Nhân Sự</span>
        </a>
        <a href="staff_dashboard.php" class="btn btn-outline-primary btn-sm rounded-pill fw-bold px-3 py-1.5 shadow-xs d-inline-flex align-items-center gap-2">
            <i class="fa-solid fa-laptop-file"></i>
            <span>Bàn Làm Việc NV</span>
        </a>
        <button type="button" class="btn btn-primary btn-sm rounded-pill fw-bold px-3 py-1.5 shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalAddStaff">
            <i class="fa-solid fa-user-plus"></i>
            <span>Thêm Nhân Viên</span>
        </button>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 py-2 px-3 mb-3 shadow-xs d-flex align-items-center" role="alert">
        <i class="fa-solid fa-check-circle me-2 text-success fs-5"></i>
        <div class="flex-grow-1 small fw-semibold"><?= $message ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: <?= json_encode($message) ?>,
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true
                });
            }
        });
    </script>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 py-2 px-3 mb-3 shadow-xs d-flex align-items-center" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2 text-danger fs-5"></i>
        <div class="flex-grow-1 small fw-semibold"><?= $error ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: <?= json_encode($error) ?>,
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true
                });
            }
        });
    </script>
<?php endif; ?>

<!-- THẺ THỐNG KÊ NHÂN SỰ -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="text-secondary small fw-semibold mb-1">Tổng Nhân Sự</div>
            <div class="fs-4 fw-black text-dark"><?= count($staffList) ?></div>
            <small class="text-muted" style="font-size: 0.72rem;">Admin & Nhân viên</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="text-secondary small fw-semibold mb-1">Nhân Viên Bán Hàng</div>
            <div class="fs-4 fw-black text-primary">
                <?= count(array_filter($staffList, fn($s) => (int)$s['role'] === 2 && str_contains(json_encode($s['permissions'] ?? ''), 'orders'))) ?>
            </div>
            <small class="text-muted" style="font-size: 0.72rem;">Phụ trách đơn & khách</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="text-secondary small fw-semibold mb-1">Nhân Viên Kho</div>
            <div class="fs-4 fw-black text-warning">
                <?= count(array_filter($staffList, fn($s) => (int)$s['role'] === 2 && (str_contains(json_encode($s['permissions'] ?? ''), 'inventory') || str_contains(json_encode($s['permissions'] ?? ''), 'products')))) ?>
            </div>
            <small class="text-muted" style="font-size: 0.72rem;">Kiểm kê & nhập máy</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="text-secondary small fw-semibold mb-1">Kỹ Thuật & Bảo Hành</div>
            <div class="fs-4 fw-black text-success">
                <?= count(array_filter($staffList, fn($s) => (int)$s['role'] === 2 && str_contains(json_encode($s['permissions'] ?? ''), 'warranties'))) ?>
            </div>
            <small class="text-muted" style="font-size: 0.72rem;">Tiếp nhận sửa chữa</small>
        </div>
    </div>
</div>

<!-- BẢNG DANH SÁCH NHÂN SỰ VÀ CÁC QUYỀN HẠN -->
<div class="admin-table-card">
    <div class="p-3.5 px-4 border-bottom bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 460px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" id="staffSearchInput" class="form-control border-start-0 bg-light" placeholder="Tìm theo tên nhân viên, email, chức danh..." onkeyup="filterStaffTable()">
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border rounded-pill px-3 py-1.5 fw-bold small">
                Danh sách: <?= count($staffList) ?> nhân sự
            </span>
            <button type="button" class="btn btn-primary btn-sm rounded-pill fw-bold px-3 py-1.5 shadow-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalAddStaff">
                <i class="fa-solid fa-user-plus"></i>
                <span>Thêm Nhân Viên</span>
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table table-compact align-middle mb-0" id="staffTable">
            <thead>
                <tr>
                    <th style="width: 95px;" class="text-nowrap ps-3">Mã NV</th>
                    <th style="min-width: 250px;">Nhân Viên & Liên Hệ</th>
                    <th style="min-width: 170px;">Chức Danh</th>
                    <th style="min-width: 240px;">Nhiệm Vụ Được Phân Quyền</th>
                    <th class="text-center" style="width: 110px;">Trạng Thái</th>
                    <th class="text-end pe-3" style="width: 175px;">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staffList as $stf): ?>
                    <?php
                        $stfPerms = [];
                        if ((int)$stf['role'] === 1) {
                            $stfPerms = array_keys(VPHONE_PERMISSIONS);
                        } elseif (!empty($stf['permissions'])) {
                            $decoded = json_decode($stf['permissions'], true);
                            $stfPerms = is_array($decoded) ? $decoded : [];
                        }

                        $stfAvatar = null;
                        if (!empty($stf['avatar'])) {
                            if (str_starts_with($stf['avatar'], 'http://') || str_starts_with($stf['avatar'], 'https://')) {
                                $stfAvatar = $stf['avatar'];
                            } elseif (file_exists(__DIR__ . '/../' . $stf['avatar'])) {
                                $stfAvatar = '../' . $stf['avatar'];
                            }
                        }

                        $isSuper = (strtolower(trim($stf['email'])) === 'admin@vphone.vn');
                        $isActive = ((int)($stf['status'] ?? 1) === 1);
                    ?>
                    <tr class="staff-row" data-search="<?= htmlspecialchars(mb_strtolower($stf['fullname'] . ' ' . $stf['email'] . ' ' . ($stf['staff_title'] ?? '') . ' ' . ($stf['phone'] ?? ''), 'UTF-8')) ?>">
                        <td class="ps-3 text-nowrap">
                            <span class="badge bg-light text-secondary border font-monospace px-2 py-1 fw-bold" style="font-size: 0.72rem;">
                                #NV-<?= str_pad((string)$stf['id'], 4, '0', STR_PAD_LEFT) ?>
                            </span>
                        </td>
                        <td>
                            <div class="table-user-cell">
                                <?php if ($stfAvatar): ?>
                                    <img src="<?= htmlspecialchars($stfAvatar) ?>" alt="Avatar" referrerpolicy="no-referrer" class="table-user-avatar border" style="width: 36px; height: 36px;">
                                <?php else: ?>
                                    <div class="table-user-avatar" style="width: 36px; height: 36px; background: <?= (int)$stf['role'] === 1 ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 'linear-gradient(135deg, #10b981, #059669)' ?>; font-weight: 700; font-size: 0.88rem;">
                                        <?= mb_substr(htmlspecialchars($stf['fullname']), 0, 1, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                                <div class="min-w-0">
                                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                        <span class="user-fullname"><?= htmlspecialchars($stf['fullname']) ?></span>
                                        <?php if ($isSuper): ?>
                                            <span class="badge bg-danger text-white rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.65rem;"><i class="fa-solid fa-crown text-warning me-1"></i>Super Admin</span>
                                        <?php elseif ((int)$stf['role'] === 1): ?>
                                            <span class="badge badge-soft-danger rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.65rem;"><i class="fa-solid fa-shield-halved me-1"></i>Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-soft-success rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.65rem;"><i class="fa-solid fa-id-badge me-1"></i>Nhân Viên</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 user-subtext text-truncate" style="max-width: 250px;">
                                        <span class="text-truncate" title="<?= htmlspecialchars($stf['email']) ?>"><i class="fa-regular fa-envelope me-1 text-muted"></i><?= htmlspecialchars($stf['email']) ?></span>
                                        <?php if (!empty($stf['phone'])): ?>
                                            <span class="opacity-50 flex-shrink-0">&bull;</span>
                                            <span class="text-nowrap flex-shrink-0" title="<?= htmlspecialchars($stf['phone']) ?>"><i class="fa-solid fa-phone me-1 text-muted"></i><?= htmlspecialchars($stf['phone']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.72rem;">
                                <i class="fa-solid fa-briefcase text-secondary me-1"></i><?= htmlspecialchars(!empty($stf['staff_title']) ? $stf['staff_title'] : ((int)$stf['role'] === 1 ? 'Quản trị viên' : 'Nhân viên')) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ((int)$stf['role'] === 1): ?>
                                <span class="badge badge-soft-primary rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-infinity me-1 text-primary"></i>Toàn quyền hệ thống
                                </span>
                            <?php elseif (empty($stfPerms)): ?>
                                <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-lock me-1"></i>Chưa cấp quyền nào
                                </span>
                            <?php else: ?>
                                <div class="d-inline-flex flex-wrap gap-1 align-items-center">
                                    <?php foreach ($stfPerms as $pk): ?>
                                        <?php if (isset(VPHONE_PERMISSIONS[$pk])): ?>
                                            <span class="badge bg-light text-dark border rounded-pill px-2 py-0.5 shadow-xs" style="font-size: 0.68rem; font-weight: 600;">
                                                <i class="<?= VPHONE_PERMISSIONS[$pk]['icon'] ?> <?= VPHONE_PERMISSIONS[$pk]['text_color'] ?> me-1" style="font-size: 0.65rem;"></i><?= VPHONE_PERMISSIONS[$pk]['name'] ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($isActive): ?>
                                <span class="badge badge-soft-success rounded-pill px-2 py-1 fw-bold d-inline-flex align-items-center gap-1.5" style="font-size: 0.72rem;">
                                    <span class="online-dot"></span>Hoạt động
                                </span>
                            <?php else: ?>
                                <span class="badge badge-soft-danger rounded-pill px-2 py-1 fw-bold d-inline-flex align-items-center gap-1.5" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-ban"></i>Tạm khóa
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-3 text-nowrap">
                            <div class="d-flex justify-content-end align-items-center gap-1.5">
                                <?php if ((int)$stf['role'] === 1 && !$isSuperAdmin && $stf['id'] != $_SESSION['user']['id']): ?>
                                    <button type="button" class="btn btn-light border text-muted btn-sm rounded-pill px-2.5 py-1 fw-bold d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" disabled title="Chỉ Quản trị tối cao mới có quyền phân quyền Quản trị viên khác">
                                        <i class="fa-solid fa-lock text-muted"></i>
                                        <span>Quản trị viên</span>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-2.5 py-1 fw-bold shadow-xs d-inline-flex align-items-center gap-1" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalEditStaff<?= $stf['id'] ?>" title="Phân quyền & Sửa thông tin">
                                        <i class="fa-solid fa-user-gear"></i>
                                        <span>Phân quyền</span>
                                    </button>
                                <?php endif; ?>

                                <?php if (!$isSuper && ((int)$stf['role'] !== 1 || $isSuperAdmin)): ?>
                                    <button type="button" class="btn btn-light border btn-sm rounded-circle d-inline-flex align-items-center justify-content-center <?= $isActive ? 'text-secondary hover-danger' : 'text-success hover-success' ?>" style="width: 28px; height: 28px;" title="<?= $isActive ? 'Tạm khóa tài khoản này' : 'Kích hoạt lại tài khoản' ?>" onclick="confirmToggleStaff('staff.php?toggle_status_id=<?= $stf['id'] ?>', <?= $isActive ? 'true' : 'false' ?>, '<?= htmlspecialchars(addslashes($stf['fullname'])) ?>')">
                                        <i class="fa-solid <?= $isActive ? 'fa-lock' : 'fa-lock-open' ?>" style="font-size: 0.72rem;"></i>
                                    </button>

                                    <?php if ($isSuperAdmin): ?>
                                        <button type="button" class="btn btn-light border text-danger btn-sm rounded-circle d-inline-flex align-items-center justify-content-center hover-danger" style="width: 28px; height: 28px;" title="Xóa vĩnh viễn nhân viên này" onclick="confirmDeleteStaff('staff.php?delete_staff_id=<?= $stf['id'] ?>', '<?= htmlspecialchars(addslashes($stf['fullname'])) ?>')">
                                            <i class="fa-solid fa-trash-can" style="font-size: 0.72rem;"></i>
                                        </button>
                                    <?php else: ?>
                                        <span style="width: 28px; display: inline-block;"></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Giữ khoảng cách 64px để nút Phân quyền luôn thẳng hàng tăm tắp với các dòng khác -->
                                    <span style="width: 64px; display: inline-block;"></span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL THÊM NHÂN VIÊN MỚI -->
<div class="modal fade" id="modalAddStaff" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form method="POST" action="staff.php" autocomplete="off">
                <input type="hidden" name="action" value="add_staff">

                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle badge-soft-success p-2.5 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fa-solid fa-user-plus fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0">Thêm Nhân Viên Mới</h5>
                            <small class="text-secondary">Tạo tài khoản và phân công nhiệm vụ cụ thể cho nhân viên</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-dark">Họ và tên nhân viên <span class="text-danger">*</span></label>
                            <input type="text" name="fullname" class="form-control rounded-3" placeholder="Ví dụ: Nguyễn Văn A" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-dark">Email đăng nhập <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control rounded-3" placeholder="nhanvien@vphone.vn" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-dark">Số điện thoại liên hệ</label>
                            <input type="tel" name="phone" class="form-control rounded-3" placeholder="0901234567">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-dark">Mật khẩu khởi tạo <span class="text-danger">*</span></label>
                            <input type="text" name="password" class="form-control rounded-3" value="123456" required>
                            <small class="text-muted" style="font-size: 0.72rem;">Mặc định: 123456 (Nhân viên có thể đổi sau khi đăng nhập)</small>
                        </div>
                    </div>

                    <!-- MẪU CHỨC DANH GỢI Ý ĐỂ TỰ ĐỘNG CHỌN QUYỀN -->
                    <div class="mb-3.5 p-3 rounded-4 bg-light border">
                        <label class="form-label small fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-wand-magic-sparkles text-primary"></i>
                            <span>Chọn Nhanh Mẫu Chức Danh Phổ Biến:</span>
                        </label>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1.5 text-dark fw-semibold shadow-xs d-inline-flex align-items-center gap-1.5" onclick="applyStaffTemplate('add', 'support', 'Chuyên viên CSKH & Tư vấn Đa kênh', ['support', 'orders', 'reviews', 'ai_assistant'])">
                                <i class="fa-solid fa-headset text-info me-1"></i><span>NV CSKH & Hỗ Trợ</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1.5 text-dark fw-semibold shadow-xs d-inline-flex align-items-center gap-1.5" onclick="applyStaffTemplate('add', 'sales', 'Nhân viên Bán hàng & Đơn hàng', ['orders', 'vouchers', 'ai_assistant'])">
                                <i class="fa-solid fa-boxes-packing text-primary me-1"></i><span>NV Bán Hàng</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1.5 text-dark fw-semibold shadow-xs d-inline-flex align-items-center gap-1.5" onclick="applyStaffTemplate('add', 'warehouse', 'Nhân viên Quản lý Kho', ['products', 'inventory', 'ai_assistant'])">
                                <i class="fa-solid fa-boxes-stacked text-warning me-1"></i><span>NV Kho & Tồn Kho</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1.5 text-dark fw-semibold shadow-xs d-inline-flex align-items-center gap-1.5" onclick="applyStaffTemplate('add', 'warranty', 'Kỹ thuật viên & Bảo hành', ['warranties', 'reviews', 'ai_assistant'])">
                                <i class="fa-solid fa-shield-halved text-success me-1"></i><span>NV Bảo Hành</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1.5 text-dark fw-semibold shadow-xs d-inline-flex align-items-center gap-1.5" onclick="applyStaffTemplate('add', 'all', 'Nhân viên Vận hành Toàn diện', ['support', 'orders', 'products', 'inventory', 'warranties', 'reviews', 'vouchers', 'news', 'ai_assistant'])">
                                <i class="fa-solid fa-star text-warning me-1"></i><span>NV Đa Nhiệm (Tất cả quyền)</span>
                            </button>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-12 col-md-6">
                                <label class="small text-muted mb-1">Mã vai trò:</label>
                                <input type="text" name="staff_role" id="addStaffRole" class="form-control form-control-sm rounded-pill" value="sales">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="small text-muted mb-1">Tên chức danh hiển thị:</label>
                                <input type="text" name="staff_title" id="addStaffTitle" class="form-control form-control-sm rounded-pill" value="Nhân viên Bán hàng & Đơn hàng">
                            </div>
                        </div>
                    </div>

                    <!-- DANH SÁCH CHECKBOX PHÂN QUYỀN NHIỆM VỤ -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label small fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-list-check text-success"></i>
                                <span>Tùy chỉnh các nhiệm vụ nhân viên CÓ QUYỀN làm:</span>
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-link btn-sm text-primary text-decoration-none p-0 small" onclick="toggleAllPerms('add', true)">Chọn tất cả</button>
                                <span class="text-muted small">&bull;</span>
                                <button type="button" class="btn btn-link btn-sm text-secondary text-decoration-none p-0 small" onclick="toggleAllPerms('add', false)">Bỏ chọn</button>
                            </div>
                        </div>

                        <div class="row g-2.5">
                            <?php foreach (VPHONE_PERMISSIONS as $pKey => $pInfo): ?>
                                <div class="col-12 col-md-6">
                                    <label class="perm-card-item d-flex align-items-start p-3 rounded-3 border h-100 mb-0" for="perm_add_<?= $pKey ?>">
                                        <input class="form-check-input mt-1 me-3 flex-shrink-0 add-perm-check" type="checkbox" name="permissions[]" value="<?= $pKey ?>" id="perm_add_<?= $pKey ?>" <?= in_array($pKey, ['orders', 'vouchers']) ? 'checked' : '' ?> style="width: 1.15em; height: 1.15em;">
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-center fw-bold text-dark small mb-1">
                                                <i class="<?= $pInfo['icon'] ?> <?= $pInfo['text_color'] ?> fa-fw me-2.5 flex-shrink-0 text-center" style="font-size: 1rem; width: 1.35rem;"></i>
                                                <span class="lh-sm"><?= htmlspecialchars($pInfo['name']) ?></span>
                                            </div>
                                            <small class="text-secondary d-block" style="font-size: 0.72rem; line-height: 1.4;"><?= htmlspecialchars($pInfo['desc']) ?></small>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Tạo Nhân Viên</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CÁC MODAL CHỈNH SỬA PHÂN QUYỀN CHO TỪNG NHÂN SỰ -->
<?php foreach ($staffList as $stf): ?>
    <?php
        $stfPerms = [];
        if (!empty($stf['permissions'])) {
            $decoded = json_decode($stf['permissions'], true);
            $stfPerms = is_array($decoded) ? $decoded : [];
        }
        $isSuper = (strtolower(trim($stf['email'])) === 'admin@vphone.vn');
    ?>
    <div class="modal fade" id="modalEditStaff<?= $stf['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <form method="POST" action="staff.php" autocomplete="off">
                    <input type="hidden" name="action" value="update_staff">
                    <input type="hidden" name="staff_id" value="<?= $stf['id'] ?>">

                    <div class="modal-header border-0 pb-0 pt-4 px-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2.5 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="fa-solid fa-user-gear fs-5"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0">Phân Quyền: <?= htmlspecialchars($stf['fullname']) ?></h5>
                                <small class="text-secondary">Mã NV: #NV-<?= str_pad((string)$stf['id'], 4, '0', STR_PAD_LEFT) ?> &bull; <?= htmlspecialchars($stf['email']) ?></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="fullname" class="form-control rounded-3" value="<?= htmlspecialchars($stf['fullname']) ?>" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Địa chỉ Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control rounded-3 <?= $isSuper ? 'bg-light' : '' ?>" value="<?= htmlspecialchars($stf['email']) ?>" <?= $isSuper ? 'readonly' : '' ?> required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Số điện thoại</label>
                                <input type="tel" name="phone" class="form-control rounded-3" value="<?= htmlspecialchars($stf['phone'] ?? '') ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Mật khẩu mới (Nếu muốn đổi)</label>
                                <input type="text" name="new_password" class="form-control rounded-3" placeholder="Bỏ trống nếu không đổi mật khẩu">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Chức danh hiển thị</label>
                                <input type="text" name="staff_title" id="editStaffTitle<?= $stf['id'] ?>" class="form-control rounded-3" value="<?= htmlspecialchars($stf['staff_title'] ?: 'Nhân viên') ?>" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark">Trạng thái tài khoản</label>
                                <select name="status" class="form-select rounded-3" <?= $isSuper ? 'disabled' : '' ?>>
                                    <option value="1" <?= ((int)($stf['status'] ?? 1) === 1) ? 'selected' : '' ?>>Đang hoạt động</option>
                                    <option value="0" <?= ((int)($stf['status'] ?? 1) === 0) ? 'selected' : '' ?>>Tạm khóa tài khoản</option>
                                </select>
                            </div>
                        </div>

                        <?php if ($isSuper): ?>
                            <div class="alert alert-primary bg-primary bg-opacity-10 border border-primary-subtle rounded-4 p-3 small mb-0">
                                <i class="fa-solid fa-crown text-warning me-1"></i>Đây là tài khoản Quản trị tối cao (Super Admin). Tài khoản này luôn có toàn quyền điều hành hệ thống.
                            </div>
                        <?php else: ?>
                            <!-- MẪU CHỨC DANH GỢI Ý CHO EDIT -->
                            <div class="mb-3.5 p-3 rounded-4 bg-light border">
                                <label class="form-label small fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-wand-magic-sparkles text-primary"></i>
                                    <span>Áp Dụng Mẫu Phân Quyền Nhanh:</span>
                                </label>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1.5 text-dark fw-semibold shadow-xs d-inline-flex align-items-center gap-1.5" onclick="applyStaffTemplate(<?= $stf['id'] ?>, 'support', 'Chuyên viên CSKH & Tư vấn Đa kênh', ['support', 'orders', 'reviews', 'ai_assistant'])">
                                        <i class="fa-solid fa-headset text-info me-1"></i><span>NV CSKH & Hỗ Trợ</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1.5 text-dark fw-semibold shadow-xs d-inline-flex align-items-center gap-1.5" onclick="applyStaffTemplate(<?= $stf['id'] ?>, 'sales', 'Nhân viên Bán hàng & Đơn hàng', ['orders', 'vouchers', 'ai_assistant'])">
                                        <i class="fa-solid fa-boxes-packing text-primary me-1"></i><span>NV Bán Hàng</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1.5 text-dark fw-semibold shadow-xs d-inline-flex align-items-center gap-1.5" onclick="applyStaffTemplate(<?= $stf['id'] ?>, 'warehouse', 'Nhân viên Quản lý Kho', ['products', 'inventory', 'ai_assistant'])">
                                        <i class="fa-solid fa-boxes-stacked text-warning me-1"></i><span>NV Kho</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1.5 text-dark fw-semibold shadow-xs d-inline-flex align-items-center gap-1.5" onclick="applyStaffTemplate(<?= $stf['id'] ?>, 'warranty', 'Kỹ thuật viên & Bảo hành', ['warranties', 'reviews', 'ai_assistant'])">
                                        <i class="fa-solid fa-shield-halved text-success me-1"></i><span>NV Bảo Hành</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1.5 text-dark fw-semibold shadow-xs d-inline-flex align-items-center gap-1.5" onclick="applyStaffTemplate(<?= $stf['id'] ?>, 'all', 'Nhân viên Vận hành Toàn diện', ['support', 'orders', 'products', 'inventory', 'warranties', 'reviews', 'vouchers', 'news', 'ai_assistant'])">
                                        <i class="fa-solid fa-star text-warning me-1"></i><span>Toàn Quyền</span>
                                    </button>
                                </div>
                                <input type="hidden" name="staff_role" id="editStaffRole<?= $stf['id'] ?>" value="<?= htmlspecialchars($stf['staff_role'] ?: 'staff') ?>">
                            </div>

                            <!-- CHECKBOX PHÂN QUYỀN -->
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label small fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-list-check text-success"></i>
                                        <span>Danh sách nhiệm vụ được phép thực hiện:</span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-link btn-sm text-primary text-decoration-none p-0 small" onclick="toggleAllPerms(<?= $stf['id'] ?>, true)">Chọn tất cả</button>
                                        <span class="text-muted small">&bull;</span>
                                        <button type="button" class="btn btn-link btn-sm text-secondary text-decoration-none p-0 small" onclick="toggleAllPerms(<?= $stf['id'] ?>, false)">Bỏ chọn</button>
                                    </div>
                                </div>

                                <div class="row g-2.5">
                                    <?php foreach (VPHONE_PERMISSIONS as $pKey => $pInfo): ?>
                                        <?php $checked = in_array($pKey, $stfPerms) || (int)$stf['role'] === 1; ?>
                                        <div class="col-12 col-md-6">
                                            <label class="perm-card-item d-flex align-items-start p-3 rounded-3 border h-100 mb-0" for="perm_edit_<?= $stf['id'] ?>_<?= $pKey ?>">
                                                <input class="form-check-input mt-1 me-3 flex-shrink-0 edit-perm-check-<?= $stf['id'] ?>" type="checkbox" name="permissions[]" value="<?= $pKey ?>" id="perm_edit_<?= $stf['id'] ?>_<?= $pKey ?>" <?= $checked ? 'checked' : '' ?> style="width: 1.15em; height: 1.15em;">
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="d-flex align-items-center fw-bold text-dark small mb-1">
                                                        <i class="<?= $pInfo['icon'] ?> <?= $pInfo['text_color'] ?> fa-fw me-2.5 flex-shrink-0 text-center" style="font-size: 1rem; width: 1.35rem;"></i>
                                                        <span class="lh-sm"><?= htmlspecialchars($pInfo['name']) ?></span>
                                                    </div>
                                                    <small class="text-secondary d-block" style="font-size: 0.72rem; line-height: 1.4;"><?= htmlspecialchars($pInfo['desc']) ?></small>
                                                </div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="modal-footer border-0 pt-0 pb-4 px-4">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Lưu Phân Quyền</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
function filterStaffTable() {
    const input = document.getElementById('staffSearchInput');
    const filter = input.value.toLowerCase().trim();
    const rows = document.querySelectorAll('.staff-row');
    rows.forEach(r => {
        const text = r.getAttribute('data-search') || '';
        r.style.display = (filter === '' || text.includes(filter)) ? '' : 'none';
    });
}

function applyStaffTemplate(targetId, roleKey, titleText, permsArray) {
    const roleInput = document.getElementById(targetId === 'add' ? 'addStaffRole' : ('editStaffRole' + targetId));
    const titleInput = document.getElementById(targetId === 'add' ? 'addStaffTitle' : ('editStaffTitle' + targetId));
    if (roleInput) roleInput.value = roleKey;
    if (titleInput) titleInput.value = titleText;

    const selector = targetId === 'add' ? '.add-perm-check' : ('.edit-perm-check-' + targetId);
    const checks = document.querySelectorAll(selector);
    checks.forEach(c => {
        c.checked = permsArray.includes(c.value);
    });
}

function toggleAllPerms(targetId, state) {
    const selector = targetId === 'add' ? '.add-perm-check' : ('.edit-perm-check-' + targetId);
    const checks = document.querySelectorAll(selector);
    checks.forEach(c => {
        c.checked = state;
    });
}

function confirmToggleStaff(url, isLocking, staffName) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: isLocking ? 'Tạm khóa tài khoản?' : 'Kích hoạt tài khoản?',
            html: `Bạn có chắc chắn muốn <b>${isLocking ? 'tạm khóa' : 'kích hoạt lại'}</b> tài khoản của nhân viên <b>${staffName}</b>?<br><small class="text-secondary mt-1 d-block">${isLocking ? 'Nhân viên này sẽ bị tạm dừng quyền đăng nhập vào hệ thống.' : 'Nhân viên sẽ có thể đăng nhập và làm việc bình thường.'}</small>`,
            icon: isLocking ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: isLocking ? '#f59e0b' : '#10b981',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: isLocking ? '<i class="fa-solid fa-lock me-1"></i> Tạm khóa ngay' : '<i class="fa-solid fa-lock-open me-1"></i> Kích hoạt ngay',
            cancelButtonText: 'Hủy bỏ',
            customClass: {
                popup: 'rounded-4 shadow-lg border-0 p-4',
                confirmButton: 'rounded-pill px-4 py-2 fw-bold',
                cancelButton: 'rounded-pill px-4 py-2 fw-semibold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
        return false;
    }
    if (confirm(isLocking ? 'Bạn có chắc muốn TẠM KHÓA tài khoản này?' : 'Bạn có chắc muốn KÍCH HOẠT LẠI tài khoản này?')) {
        window.location.href = url;
    }
    return false;
}

function confirmDeleteStaff(url, staffName) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Xóa vĩnh viễn nhân viên?',
            html: `Bạn có chắc chắn muốn xóa nhân viên <b>${staffName}</b>?<br><small class="text-danger mt-1 d-block fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Hành động này không thể hoàn tác và sẽ thu hồi mọi quyền hạn của nhân viên!</small>`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> Xóa vĩnh viễn',
            cancelButtonText: 'Hủy bỏ',
            customClass: {
                popup: 'rounded-4 shadow-lg border-0 p-4',
                confirmButton: 'rounded-pill px-4 py-2 fw-bold',
                cancelButton: 'rounded-pill px-4 py-2 fw-semibold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
        return false;
    }
    if (confirm('CẢNH BÁO: Bạn có chắc chắn muốn xóa vĩnh viễn nhân viên này?')) {
        window.location.href = url;
    }
    return false;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
