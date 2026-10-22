<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/MembershipService.php';

// Xử lý xuất file CSV danh sách khách hàng
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $membershipSvc = new MembershipService($pdo);
    $filename = 'vphone_khach_hang_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'Họ và tên', 'Email', 'Số điện thoại', 'Địa chỉ', 'Vai trò', 'Chức danh', 'Số đơn hàng', 'Tổng chi tiêu (VNĐ)', 'Hạng thành viên', 'Ngày tham gia']);
    
    $uStats = $pdo->query("SELECT user_id, COUNT(*) as cnt, COALESCE(SUM(CASE WHEN (status = 'Đã giao thành công' OR (payment_status = 'Đã thanh toán' AND status != 'Đã hủy')) THEN total_money ELSE 0 END), 0) as spent FROM orders WHERE user_id IS NOT NULL GROUP BY user_id")->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($uStats as $s) { $map[$s['user_id']] = $s; }

    $allUsers = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allUsers as $u) {
        $st = $map[$u['id']] ?? ['cnt' => 0, 'spent' => 0];
        $spent = (float)$st['spent'];
        $tierInfo = $membershipSvc->getTierBySpent($spent, $u['membership_tier'] ?? null);
        $tier = $tierInfo['name'];
        fputcsv($output, [
            $u['id'],
            $u['fullname'],
            $u['email'],
            $u['phone'] ?: 'Chưa có',
            $u['address'] ?: 'Chưa có',
            $u['role'] == 1 ? 'Quản trị viên (Admin)' : ($u['role'] == 2 ? 'Nhân viên (Staff)' : 'Khách hàng'),
            $u['staff_title'] ?: '',
            $st['cnt'],
            $spent,
            $tier,
            $u['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$pageTitle = 'Quản Lý Khách Hàng - V-Phone Admin';
require_once 'includes/header.php';
require_once __DIR__ . '/../app/models/UserModel.php';

$userModel = new UserModel($pdo);
$userModel->ensureAvatarColumn();
$message = '';
$error = '';

// Kiểm tra quyền Quản trị tối cao: Chỉ duy nhất admin@vphone.vn mới có quyền can thiệp vai trò tài khoản
$isSuperAdmin = false;
$currentAdminEmail = '';
if (!empty($_SESSION['user']['id'])) {
    $checkAdminStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $checkAdminStmt->execute([(int)$_SESSION['user']['id']]);
    $currentAdminEmail = strtolower(trim((string)$checkAdminStmt->fetchColumn()));
}
if (empty($currentAdminEmail) && !empty($_SESSION['user']['email'])) {
    $currentAdminEmail = strtolower(trim((string)$_SESSION['user']['email']));
}
$isSuperAdmin = ($currentAdminEmail === 'admin@vphone.vn');

// Xử lý cập nhật thông tin người dùng từ Admin
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $uid = (int)($_POST['user_id'] ?? 0);
    $fullname = trim($_POST['fullname'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $staffTitle = trim($_POST['staff_title'] ?? '');

    $existing = $userModel->findById($uid);
    if (!$existing) {
        $error = "Không tìm thấy tài khoản người dùng cần sửa!";
    } elseif (empty($fullname) || mb_strlen($fullname, 'UTF-8') < 2) {
        $error = "Họ và tên không được để trống và phải từ 2 ký tự trở lên!";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Địa chỉ email không đúng định dạng!";
    } elseif ($userModel->isEmailTaken($email, $uid)) {
        $error = "Email '$email' đã được sử dụng bởi tài khoản khác. Vui lòng nhập email khác!";
    } elseif (!empty($phone) && !preg_match('/^[0-9]{9,11}$/', preg_replace('/[^0-9]/', '', $phone))) {
        $error = "Số điện thoại không hợp lệ (cần từ 9 đến 11 chữ số)!";
    } elseif (!empty($newPassword) && mb_strlen($newPassword, 'UTF-8') < 6) {
        $error = "Mật khẩu mới phải có tối thiểu 6 ký tự!";
    } else {
        $requestedRole = isset($_POST['role']) ? (int)$_POST['role'] : (int)$existing['role'];
        $role = (int)$existing['role'];

        if ($isSuperAdmin) {
            // Super Admin có quyền cấp mọi vai trò (0: Khách, 2: Nhân viên, 1: Quản trị viên)
            $role = in_array($requestedRole, [0, 1, 2]) ? $requestedRole : (int)$existing['role'];
            // Không ai được tự giáng quyền tài khoản gốc admin@vphone.vn
            if (strtolower(trim($existing['email'])) === 'admin@vphone.vn' && $role !== 1) {
                $role = 1;
                $error = "Không thể hạ quyền Quản trị viên của tài khoản Quản trị tối cao (admin@vphone.vn)!";
            }
        } else {
            // Admin thường:
            // 1. Không thể can thiệp tài khoản Quản trị viên khác
            if ((int)$existing['role'] === 1 && $uid !== (int)$_SESSION['user']['id']) {
                $error = "Từ chối quyền hạn! Chỉ có tài khoản Quản trị tối cao (<strong>admin@vphone.vn</strong>) mới có quyền chỉnh sửa tài khoản Quản trị viên khác.";
            }
            // 2. Không thể tự nâng cấp bất kỳ ai lên Admin (role = 1)
            elseif ($requestedRole === 1 && (int)$existing['role'] !== 1) {
                $error = "Từ chối quyền hạn! Chỉ có tài khoản Quản trị tối cao (<strong>admin@vphone.vn</strong>) mới có quyền cấp quyền Quản trị viên (Admin). Bạn chỉ có thể cấp quyền Nhân viên hoặc Khách hàng.";
            }
            // 3. Có thể cấp quyền Nhân viên (role = 2) hoặc Khách hàng (role = 0)
            else {
                if (in_array($requestedRole, [0, 2])) {
                    $role = $requestedRole;
                }
            }
        }

        // Tự động gán chức danh mặc định theo vai trò nếu để trống
        if ($role === 2 && empty($staffTitle)) {
            $staffTitle = 'Nhân viên cửa hàng';
        } elseif ($role === 1 && empty($staffTitle)) {
            $staffTitle = (strtolower(trim($existing['email'])) === 'admin@vphone.vn') ? 'Quản Trị Tối Cao' : 'Quản trị viên';
        }

        // Không cho phép đổi địa chỉ email của tài khoản admin@vphone.vn
        if (strtolower(trim($existing['email'])) === 'admin@vphone.vn' && $email !== 'admin@vphone.vn') {
            $error = "Không được phép thay đổi địa chỉ email của tài khoản Quản trị tối cao (admin@vphone.vn)!";
            $email = 'admin@vphone.vn';
        }

        if (empty($error)) {
            $avatarPath = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExts) && $file['size'] <= 5 * 1024 * 1024) {
                    $uploadDir = '../assets/images/users/';
                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                    $newFilename = 'avatar_' . $uid . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFilename) || (PHP_SAPI === 'cli' && @copy($file['tmp_name'], $uploadDir . $newFilename))) {
                        $avatarPath = 'assets/images/users/' . $newFilename;
                        $userModel->cleanOldAvatars($uid, $avatarPath);
                    }
                }
            } elseif (!empty($_POST['remove_avatar']) && $_POST['remove_avatar'] == '1') {
                $userModel->cleanOldAvatars($uid);
                $avatarPath = '';
            }

            $updated = $userModel->updateByAdmin($uid, $fullname, $email, $phone, $address, $role, $newPassword ?: null, $avatarPath, $staffTitle);
            if ($updated) {
                if (isset($_POST['membership_tier'])) {
                    $userModel->updateMembershipTier($uid, trim($_POST['membership_tier']));
                }
                $message = "Đã cập nhật thông tin tài khoản #$uid (<strong>$fullname</strong>) thành công!";
                // Nếu admin đang sửa chính tài khoản của mình thì đồng bộ session
                if ($uid == $_SESSION['user']['id']) {
                    $_SESSION['user']['fullname'] = $fullname;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['user']['phone'] = $phone;
                    $_SESSION['user']['address'] = $address;
                    $_SESSION['user']['role'] = $role;
                    $_SESSION['user']['staff_title'] = $staffTitle;
                    if ($avatarPath !== null) {
                        $_SESSION['user']['avatar'] = $avatarPath;
                    }
                }
            } else {
                $error = "Có lỗi xảy ra khi cập nhật dữ liệu. Vui lòng thử lại!";
            }
        }
    }
}

// Xử lý đổi vai trò nhanh từ modal "Phân Quyền & Chức Danh"
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_user_role') {
    $uid = (int)($_POST['user_id'] ?? 0);
    $newRole = (int)($_POST['role'] ?? 0);
    $staffTitle = trim($_POST['staff_title'] ?? '');

    $targetUser = $userModel->findById($uid);
    if (!$targetUser) {
        $error = "Không tìm thấy người dùng có ID #$uid!";
    } elseif (strtolower(trim($targetUser['email'])) === 'admin@vphone.vn') {
        $error = "Tài khoản Quản trị tối cao (<strong>admin@vphone.vn</strong>) được bảo vệ cố định và không thể thay đổi vai trò!";
    } elseif (!$isSuperAdmin && (int)$targetUser['role'] === 1) {
        $error = "Từ chối quyền hạn! Chỉ có tài khoản Quản trị tối cao (<strong>admin@vphone.vn</strong>) mới có quyền can thiệp tài khoản Quản trị viên khác.";
    } elseif (!$isSuperAdmin && $newRole === 1) {
        $error = "Từ chối quyền hạn! Chỉ có tài khoản Quản trị tối cao (<strong>admin@vphone.vn</strong>) mới có quyền cấp quyền Quản trị viên (Admin). Bạn chỉ có thể cấp quyền Nhân viên hoặc Khách hàng.";
    } else {
        if ($newRole === 2 && empty($staffTitle)) {
            $staffTitle = 'Nhân viên cửa hàng';
        } elseif ($newRole === 1 && empty($staffTitle)) {
            $staffTitle = 'Quản trị viên';
        }

        $ok = $userModel->updateUserRole($uid, $newRole, $staffTitle);
        if ($ok) {
            $roleLabel = ($newRole === 1) ? "Quản trị viên (Admin)" : (($newRole === 2) ? "Nhân viên (Staff)" : "Khách hàng thường");
            $titleInfo = !empty($staffTitle) ? " &bull; Chức danh: <em>" . htmlspecialchars($staffTitle) . "</em>" : "";
            $message = "Đã cập nhật vai trò tài khoản <strong>#" . htmlspecialchars($targetUser['fullname']) . "</strong> thành <strong>$roleLabel</strong>$titleInfo thành công!";
            if ($uid == $_SESSION['user']['id']) {
                $_SESSION['user']['role'] = $newRole;
                $_SESSION['user']['staff_title'] = $staffTitle;
            }
        } else {
            $error = "Có lỗi xảy ra khi cập nhật vai trò người dùng!";
        }
    }
}

// Chuyển đổi quyền hạn nhanh (Legacy GET toggle)
if (isset($_GET['toggle_role_id'])) {
    $uid = (int)$_GET['toggle_role_id'];
    $u = $pdo->prepare("SELECT id, fullname, email, role, staff_title FROM users WHERE id = ?");
    $u->execute([$uid]);
    $targetUser = $u->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        $error = "Không tìm thấy người dùng có ID #$uid!";
    } elseif (strtolower(trim($targetUser['email'])) === 'admin@vphone.vn') {
        $error = "Tài khoản Quản trị tối cao (<strong>admin@vphone.vn</strong>) được bảo vệ cố định và không thể hạ quyền!";
    } elseif (!$isSuperAdmin && (int)$targetUser['role'] === 1) {
        $error = "Từ chối quyền hạn! Chỉ có tài khoản Quản trị tối cao (<strong>admin@vphone.vn</strong>) mới có quyền can thiệp vai trò Quản trị viên.";
    } else {
        $currRole = (int)$targetUser['role'];
        if ($isSuperAdmin) {
            // Super Admin: chuyển đổi Khách hàng <-> Admin
            $newRole = ($currRole === 1) ? 0 : 1;
            $newTitle = ($newRole === 1) ? 'Quản trị viên' : '';
        } else {
            // Admin thường: chuyển đổi Khách hàng (0) <-> Nhân viên (2)
            $newRole = ($currRole === 2) ? 0 : 2;
            $newTitle = ($newRole === 2) ? ($targetUser['staff_title'] ?: 'Nhân viên cửa hàng') : '';
        }

        $userModel->updateUserRole($uid, $newRole, $newTitle);
        $roleLabel = ($newRole === 1) ? "Quản trị viên (Admin)" : (($newRole === 2) ? "Nhân viên (Staff)" : "Khách hàng thường");
        $message = "Đã cập nhật vai trò tài khoản <strong>#" . htmlspecialchars($targetUser['fullname']) . "</strong> thành <strong>$roleLabel</strong> thành công!";
    }
}

// Xử lý XÓA TÀI KHOẢN NGƯỜI DÙNG
if (isset($_GET['delete_user_id'])) {
    $delId = (int)$_GET['delete_user_id'];
    $stmt = $pdo->prepare("SELECT id, fullname, email, role FROM users WHERE id = ?");
    $stmt->execute([$delId]);
    $delUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$delUser) {
        $error = "Không tìm thấy người dùng có ID #$delId!";
    } elseif (strtolower(trim($delUser['email'])) === 'admin@vphone.vn') {
        $error = "Không thể xóa tài khoản Quản trị tối cao (<strong>admin@vphone.vn</strong>)!";
    } elseif ($delId === (int)($_SESSION['user']['id'] ?? 0)) {
        $error = "Bạn không thể tự xóa tài khoản của chính mình đang đăng nhập!";
    } elseif (!$isSuperAdmin && (int)$delUser['role'] === 1) {
        $error = "Chỉ Super Admin (<strong>admin@vphone.vn</strong>) mới có quyền xóa tài khoản Quản trị viên!";
    } else {
        // Cập nhật các bảng liên quan an toàn (SET NULL hoặc xóa) trong Transaction
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE orders SET user_id = NULL WHERE user_id = ?")->execute([$delId]);
            $pdo->prepare("UPDATE warranty_claims SET user_id = NULL WHERE user_id = ?")->execute([$delId]);
            $pdo->prepare("UPDATE callback_requests SET user_id = NULL WHERE user_id = ?")->execute([$delId]);
            $pdo->prepare("DELETE FROM user_notifications WHERE user_id = ?")->execute([$delId]);
            $pdo->prepare("DELETE FROM user_addresses WHERE user_id = ?")->execute([$delId]);
            $pdo->prepare("DELETE FROM product_reviews WHERE user_id = ?")->execute([$delId]);
            if (!empty($delUser['email'])) {
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$delUser['email']]);
            }
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$delId]);
            $pdo->commit();

            $message = "Đã xóa vĩnh viễn tài khoản <strong>#" . htmlspecialchars($delUser['fullname']) . " (" . htmlspecialchars($delUser['email']) . ")</strong> khỏi hệ thống!";
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Không thể xóa tài khoản do lỗi hệ thống CSDL: " . htmlspecialchars($e->getMessage());
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();
// Thống kê đơn hàng và chi tiêu
$userStatsRaw = $pdo->query("SELECT user_id, COUNT(*) as order_count, COALESCE(SUM(CASE WHEN (status = 'Đã giao thành công' OR (payment_status = 'Đã thanh toán' AND status != 'Đã hủy')) THEN total_money ELSE 0 END), 0) as total_spent FROM orders WHERE user_id IS NOT NULL GROUP BY user_id")->fetchAll(PDO::FETCH_ASSOC);
$userStats = [];
foreach ($userStatsRaw as $usrStat) {
    $userStats[$usrStat['user_id']] = $usrStat;
}
$membershipService = new MembershipService($pdo);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-black text-dark mb-1">Quản Lý Tài Khoản Khách Hàng</h3>
        <p class="text-secondary small mb-0">Xem danh sách thành viên, cập nhật thông tin, hạng VIP chi tiêu và phân quyền quản trị</p>
    </div>
    <div class="d-flex gap-2">
        <a href="users.php?export=csv" class="btn btn-outline-success btn-sm rounded-pill fw-bold px-3">
            <i class="fa-solid fa-file-excel me-1.5"></i>Xuất Excel Khách Hàng (CSV)
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

<?php if ($isSuperAdmin): ?>
    <div class="alert alert-primary bg-primary bg-opacity-10 border border-primary-subtle rounded-4 py-2.5 px-3 mb-4 d-flex align-items-center gap-2 small">
        <i class="fa-solid fa-crown text-warning fs-5"></i>
        <div class="text-dark">
            Bạn đang đăng nhập với quyền <strong>Quản Trị Tối Cao (admin@vphone.vn)</strong>. Bạn có toàn quyền cấp hoặc thu hồi quyền Quản trị viên (Admin), phân quyền Nhân viên (Staff) và đặt chức danh hiển thị.
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning bg-warning bg-opacity-10 border border-warning-subtle rounded-4 py-2.5 px-3 mb-4 d-flex align-items-center gap-2 small">
        <i class="fa-solid fa-shield-halved text-warning fs-5"></i>
        <div class="text-dark">
            Bạn đang đăng nhập với tài khoản Admin: <strong><?= htmlspecialchars($currentAdminEmail) ?></strong>.
            <span class="text-success fw-bold ms-1"><i class="fa-solid fa-user-check me-1"></i>Đặc quyền:</span> Bạn có thể <strong>cấp quyền Nhân viên (Staff)</strong> và đặt <strong>Chức danh hiển thị</strong>. Riêng quyền <strong>Admin</strong> chỉ có Quản trị tối cao (<strong>admin@vphone.vn</strong>) mới có quyền phê duyệt.
        </div>
    </div>
<?php endif; ?>

<div class="admin-table-card">
    <div class="p-3.5 px-4 border-bottom bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 680px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" id="userSearch" class="form-control border-start-0 bg-light" placeholder="Tìm theo tên, email, số điện thoại, chức danh..." onkeyup="filterUsersTable()">
            </div>
            <select id="userRoleFilter" class="form-select form-select-sm bg-light" style="max-width: 155px;" onchange="filterUsersTable()">
                <option value="all">Tất cả vai trò</option>
                <option value="admin">Quản trị viên (Admin)</option>
                <option value="staff">Nhân viên (Staff)</option>
                <option value="customer">Khách hàng</option>
            </select>
            <select id="userTierFilter" class="form-select form-select-sm bg-light" style="max-width: 175px;" onchange="filterUsersTable()">
                <option value="all">Tất cả hạng VIP</option>
                <option value="diamond">VIP Kim Cương (≥ 500tr)</option>
                <option value="gold">VIP Vàng (≥ 300tr)</option>
                <option value="silver">VIP Bạc (≥ 100tr)</option>
                <option value="standard">Tiêu chuẩn (< 100tr)</option>
            </select>
        </div>
        <div>
            <span class="badge bg-light text-dark border rounded-pill px-3 py-1.5 fw-bold small">
                Tổng cộng: <?= count($users) ?> tài khoản
            </span>
        </div>
    </div>

<style>
#usersTable th,
#usersTable td {
    padding: 9px 12px !important;
    vertical-align: middle !important;
}
#usersTable thead th {
    font-size: 0.74rem !important;
    letter-spacing: 0.3px;
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
}
.user-act-btn-group {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
    width: 196px;
}
.user-act-btn-group .btn-act-edit {
    width: 58px;
    height: 29px;
    font-size: 0.74rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    flex-shrink: 0;
}
.user-act-btn-group .btn-act-role {
    width: 98px;
    height: 29px;
    font-size: 0.72rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    flex-shrink: 0;
}
.user-act-btn-group .btn-act-delete {
    width: 28px;
    height: 28px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
[data-bs-theme="dark"] #usersTable thead th {
    background: #0e172a !important;
    color: #94a3b8 !important;
    border-bottom-color: rgba(255, 255, 255, 0.09) !important;
}
[data-bs-theme="dark"] #usersTable tbody td {
    border-bottom-color: rgba(255, 255, 255, 0.06) !important;
    color: #f1f5f9 !important;
}
[data-bs-theme="dark"] #usersTable tbody tr:hover td {
    background-color: #1a2948 !important;
}
</style>

    <div class="table-responsive">
        <table class="table admin-table table-compact align-middle mb-0" id="usersTable">
            <thead>
                <tr>
                    <th style="width: 45px;" class="ps-3 text-center text-nowrap">ID</th>
                    <th style="min-width: 165px;" class="text-nowrap">Hình Ảnh & Họ Tên</th>
                    <th style="min-width: 145px;" class="text-nowrap">Email</th>
                    <th style="min-width: 100px;" class="text-nowrap">Số Điện Thoại</th>
                    <th style="min-width: 105px;" class="text-nowrap">Đơn & Chi Tiêu</th>
                    <th class="text-center text-nowrap" style="width: 100px;">Hạng</th>
                    <th style="min-width: 135px;" class="text-nowrap">Vai Trò & Chức Danh</th>
                    <th class="text-end pe-3 text-nowrap" style="width: 205px;">Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <?php
                        $st = $userStats[$u['id']] ?? ['order_count' => 0, 'total_spent' => 0];
                        $ordCount = (int)$st['order_count'];
                        $spent = (float)$st['total_spent'];
                        $userTier = $membershipService->getTierBySpent($spent, $u['membership_tier'] ?? null);
                        $roleVal = $u['role'] == 1 ? 'admin' : ($u['role'] == 2 ? 'staff' : 'customer');
                        $uAvatar = null;
                        if (!empty($u['avatar'])) {
                            if (str_starts_with($u['avatar'], 'http://') || str_starts_with($u['avatar'], 'https://')) {
                                $uAvatar = $u['avatar'];
                            } elseif (file_exists(__DIR__ . '/../' . $u['avatar'])) {
                                $uAvatar = '../' . $u['avatar'] . '?v=' . filemtime(__DIR__ . '/../' . $u['avatar']);
                            }
                        }
                    ?>
                    <tr class="user-row" data-search="<?= htmlspecialchars(mb_strtolower($u['fullname'] . ' ' . $u['email'] . ' ' . ($u['staff_title'] ?? '') . ' ' . ($u['phone'] ?? ''), 'UTF-8')) ?>" data-role="<?= $roleVal ?>" data-tier="<?= $userTier['key'] ?>">
                        <td class="fw-bold text-muted ps-3 text-center text-nowrap" style="width: 45px;">#<?= $u['id'] ?></td>
                        <td class="text-nowrap">
                            <div class="table-user-cell d-flex align-items-center gap-2">
                                <?php if ($uAvatar): ?>
                                    <img src="<?= htmlspecialchars($uAvatar) ?>" alt="Avatar" referrerpolicy="no-referrer" class="table-user-avatar border rounded-circle flex-shrink-0" style="width: 36px; height: 36px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="table-user-avatar rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center text-white" style="width: 36px; height: 36px; background: <?= $u['role'] == 1 ? 'linear-gradient(135deg, #ef4444, #dc2626)' : ($u['role'] == 2 ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #0066cc, #0284c7)') ?>; font-weight: 700; font-size: 0.85rem;">
                                        <?= mb_substr(htmlspecialchars($u['fullname']), 0, 1, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                                <div class="min-w-0">
                                    <div class="user-fullname text-nowrap" style="max-width: 165px;" title="<?= htmlspecialchars($u['fullname']) ?>"><?= htmlspecialchars($u['fullname']) ?></div>
                                    <div class="user-subtext text-nowrap text-truncate text-muted" style="max-width: 165px;" title="<?= htmlspecialchars($u['address'] ?: 'Chưa cập nhật địa chỉ') ?>"><?= htmlspecialchars($u['address'] ?: 'Chưa cập nhật địa chỉ') ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-nowrap">
                            <div class="d-flex align-items-center gap-1 mb-0.5">
                                <?php if (($u['auth_provider'] ?? '') === 'google' || !empty($u['google_id'])): ?>
                                    <span class="badge bg-white text-danger border rounded-pill px-2 py-0.5 d-inline-flex align-items-center gap-1" style="font-size: 0.68rem;" title="Đăng nhập bằng Google">
                                        <i class="fa-brands fa-google text-danger"></i><span>Google</span>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border rounded-pill px-2 py-0.5 d-inline-flex align-items-center gap-1" style="font-size: 0.68rem;" title="Tài khoản email & mật khẩu">
                                        <i class="fa-solid fa-envelope"></i><span>Email</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="text-secondary small font-monospace d-block text-nowrap" style="font-size: 0.8rem;"><?= htmlspecialchars($u['email']) ?></span>
                        </td>
                        <td class="text-nowrap">
                            <?php if (!empty($u['phone'])): ?>
                                <span class="fw-medium text-dark small font-monospace"><?= htmlspecialchars($u['phone']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small fst-italic">Chưa có</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <div class="small">
                                <span class="fw-bold text-dark d-block"><?= $ordCount ?> đơn</span>
                                <div class="text-danger fw-bold text-nowrap mt-0.5" style="font-size: 0.78rem; white-space: nowrap !important;"><?= number_format($spent, 0, ',', '.') ?>&nbsp;VNĐ</div>
                            </div>
                        </td>
                        <td class="text-center text-nowrap">
                            <?= MembershipService::renderBadge($userTier, 'sm') ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if (strtolower(trim($u['email'])) === 'admin@vphone.vn'): ?>
                                <span class="badge bg-danger text-white rounded-pill px-2.5 py-0.5 shadow-sm d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;" title="Quản trị viên tối cao (Gốc hệ thống)">
                                    <i class="fa-solid fa-crown text-warning"></i><span>Super Admin</span>
                                </span>
                                <div class="text-danger fw-bold mt-1 text-nowrap" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-crown text-warning me-1"></i><?= htmlspecialchars(!empty($u['staff_title']) ? $u['staff_title'] : 'Quản Trị Tối Cao') ?>
                                </div>
                            <?php elseif ($u['role'] == 1): ?>
                                <span class="badge badge-soft-danger rounded-pill px-2.5 py-0.5 d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;"><i class="fa-solid fa-shield-halved"></i><span>Admin</span></span>
                                <div class="text-danger fw-semibold mt-1 text-nowrap" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-shield-halved me-1"></i><?= htmlspecialchars(!empty($u['staff_title']) ? $u['staff_title'] : 'Quản trị viên') ?>
                                </div>
                            <?php elseif ($u['role'] == 2): ?>
                                <span class="badge badge-soft-success rounded-pill px-2.5 py-0.5 fw-bold d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;"><i class="fa-solid fa-id-badge"></i><span>Nhân Viên</span></span>
                                <div class="text-success fw-semibold mt-1 text-nowrap" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-tag me-1"></i><?= htmlspecialchars(!empty($u['staff_title']) ? $u['staff_title'] : 'Nhân viên cửa hàng') ?>
                                </div>
                            <?php else: ?>
                                <span class="badge badge-soft-primary rounded-pill px-2.5 py-0.5 d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;"><i class="fa-regular fa-user"></i><span>Khách Hàng</span></span>
                                <div class="text-muted mt-1 text-nowrap" style="font-size: 0.72rem;">
                                    <i class="fa-regular fa-id-card me-1"></i><?= htmlspecialchars(!empty($u['staff_title']) ? $u['staff_title'] : 'Thành viên') ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap pe-3">
                            <div class="user-act-btn-group">
                                <!-- Slot 1: Sửa (58px) -->
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill btn-act-edit fw-bold" data-bs-toggle="modal" data-bs-target="#modalEditUser<?= $u['id'] ?>" title="Sửa thông tin khách hàng">
                                    <i class="fa-solid fa-pen-to-square"></i><span>Sửa</span>
                                </button>

                                <!-- Slot 2: Đổi vai trò / Cố định (98px) -->
                                <?php if (strtolower(trim($u['email'])) === 'admin@vphone.vn'): ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill btn-act-role fw-bold" title="Tài khoản Quản trị tối cao cố định quyền">
                                        <i class="fa-solid fa-lock"></i><span>Cố định</span>
                                    </span>
                                <?php elseif ((int)$u['role'] === 1 && !$isSuperAdmin && $u['id'] != $_SESSION['user']['id']): ?>
                                    <button type="button" class="btn btn-light border text-muted btn-sm rounded-pill btn-act-role" style="cursor: not-allowed; opacity: 0.65;" disabled title="Chỉ Super Admin mới có quyền đổi vai trò Quản trị viên">
                                        <i class="fa-solid fa-lock"></i><span>Đổi vai trò</span>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-light border text-secondary btn-sm rounded-pill btn-act-role fw-bold hover-primary" data-bs-toggle="modal" data-bs-target="#modalChangeRole<?= $u['id'] ?>" title="Đổi vai trò & Chức danh hiển thị">
                                        <i class="fa-solid fa-repeat text-primary"></i><span>Đổi vai trò</span>
                                    </button>
                                <?php endif; ?>

                                <!-- Slot 3: Xóa / Khóa (28px) -->
                                <?php if (strtolower(trim($u['email'])) === 'admin@vphone.vn'): ?>
                                    <button type="button" class="btn btn-light border text-muted btn-sm rounded-circle btn-act-delete" style="opacity: 0.35; cursor: not-allowed;" disabled title="Tài khoản Quản trị tối cao không thể xóa">
                                        <i class="fa-solid fa-lock" style="font-size: 0.7rem;"></i>
                                    </button>
                                <?php elseif ((int)$u['role'] === 1 && !$isSuperAdmin && $u['id'] != $_SESSION['user']['id']): ?>
                                    <button type="button" class="btn btn-light border text-muted btn-sm rounded-circle btn-act-delete" style="opacity: 0.35; cursor: not-allowed;" disabled title="Không thể xóa Quản trị viên">
                                        <i class="fa-solid fa-lock" style="font-size: 0.7rem;"></i>
                                    </button>
                                <?php elseif ($u['id'] == ($_SESSION['user']['id'] ?? 0)): ?>
                                    <button type="button" class="btn btn-light border text-muted btn-sm rounded-circle btn-act-delete" style="opacity: 0.35; cursor: not-allowed;" disabled title="Tài khoản đang đăng nhập">
                                        <i class="fa-solid fa-user-check" style="font-size: 0.7rem;"></i>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-light border text-danger btn-sm rounded-circle btn-act-delete hover-danger" title="Xóa tài khoản này" onclick="confirmDeleteUser(event, 'users.php?delete_user_id=<?= $u['id'] ?>', '<?= htmlspecialchars(addslashes($u['fullname'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>')">
                                        <i class="fa-solid fa-trash-can" style="font-size: 0.72rem;"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- CÁC MODAL SỬA THÔNG TIN KHÁCH HÀNG -->
<?php foreach ($users as $u): ?>
    <div class="modal fade" id="modalEditUser<?= $u['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $u['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="POST" action="users.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">

                    <div class="modal-header border-0 pb-0 pt-4 px-4">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 me-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="fa-solid fa-user-pen fs-5"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-dark" id="modalLabel<?= $u['id'] ?>">Sửa Thông Tin Khách Hàng #<?= $u['id'] ?></h5>
                                <small class="text-secondary"><?= htmlspecialchars($u['fullname']) ?></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <!-- HÌNH ẢNH ĐẠI DIỆN -->
                        <div class="mb-3 p-3 bg-light rounded-4 border">
                            <label class="form-label small fw-bold text-dark mb-2">Hình ảnh đại diện</label>
                            <div class="d-flex align-items-center gap-3">
                                <?php 
                                    $editAvSrc = null;
                                    if (!empty($u['avatar'])) {
                                        if (str_starts_with($u['avatar'], 'http://') || str_starts_with($u['avatar'], 'https://')) {
                                            $editAvSrc = $u['avatar'];
                                        } elseif (file_exists(__DIR__ . '/../' . $u['avatar'])) {
                                            $editAvSrc = '../' . $u['avatar'] . '?v=' . filemtime(__DIR__ . '/../' . $u['avatar']);
                                        }
                                    }
                                ?>
                                <?php if ($editAvSrc): ?>
                                    <img src="<?= htmlspecialchars($editAvSrc) ?>" alt="Avatar" referrerpolicy="no-referrer" class="rounded-circle border" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="rmAvatar<?= $u['id'] ?>">
                                        <label class="form-check-label small text-danger fw-semibold" for="rmAvatar<?= $u['id'] ?>">Gỡ ảnh này</label>
                                    </div>
                                <?php else: ?>
                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold" style="width: 50px; height: 50px; background: #0066cc; font-size: 1.2rem;">
                                        <?= mb_substr(htmlspecialchars($u['fullname']), 0, 1, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <input type="file" name="avatar" class="form-control form-control-sm rounded-pill" accept="image/*">
                                    <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Tải ảnh mới (JPG, PNG, WEBP, tối đa 5MB)</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Họ và tên <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-primary"></i></span>
                                <input type="text" name="fullname" class="form-control border-start-0" value="<?= htmlspecialchars($u['fullname']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Địa chỉ Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-primary"></i></span>
                                <input type="email" name="email" class="form-control border-start-0 <?= strtolower(trim($u['email'])) === 'admin@vphone.vn' ? 'bg-light' : '' ?>" value="<?= htmlspecialchars($u['email']) ?>" <?= strtolower(trim($u['email'])) === 'admin@vphone.vn' ? 'readonly' : '' ?> required>
                            </div>
                            <?php if (strtolower(trim($u['email'])) === 'admin@vphone.vn'): ?>
                                <small class="text-muted" style="font-size: 0.72rem;"><i class="fa-solid fa-lock me-1"></i>Email Quản trị tối cao cố định của hệ thống.</small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Số điện thoại</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-phone text-primary"></i></span>
                                <input type="tel" name="phone" class="form-control border-start-0" value="<?= htmlspecialchars($u['phone'] ?? '') ?>" placeholder="0901234567">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Địa chỉ nhận hàng</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-location-dot text-primary"></i></span>
                                <input type="text" name="address" class="form-control border-start-0" value="<?= htmlspecialchars($u['address'] ?? '') ?>" placeholder="Nhập địa chỉ giao hàng">
                            </div>
                        </div>

                        <!-- CHỨC DANH HIỂN THỊ -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark d-flex align-items-center justify-content-between">
                                <span><i class="fa-solid fa-award text-primary me-1"></i>Chức danh hiển thị</span>
                                <span class="text-muted" style="font-size: 0.7rem;">Hiển thị dưới huy hiệu vai trò</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-id-badge text-primary"></i></span>
                                <input type="text" name="staff_title" id="editTitleInput<?= $u['id'] ?>" class="form-control border-start-0" value="<?= htmlspecialchars($u['staff_title'] ?? '') ?>" placeholder="Ví dụ: Nhân viên bán hàng, Kỹ thuật viên & Bảo hành...">
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-1.5">
                                <span class="badge bg-light text-secondary border" style="cursor: pointer; font-size: 0.72rem;" onclick="document.getElementById('editTitleInput<?= $u['id'] ?>').value='Nhân viên bán hàng'">Bán hàng</span>
                                <span class="badge bg-light text-secondary border" style="cursor: pointer; font-size: 0.72rem;" onclick="document.getElementById('editTitleInput<?= $u['id'] ?>').value='Kỹ thuật viên & Bảo hành'">Kỹ thuật</span>
                                <span class="badge bg-light text-secondary border" style="cursor: pointer; font-size: 0.72rem;" onclick="document.getElementById('editTitleInput<?= $u['id'] ?>').value='Quản lý kho & Nhập hàng'">Kho hàng</span>
                                <span class="badge bg-light text-secondary border" style="cursor: pointer; font-size: 0.72rem;" onclick="document.getElementById('editTitleInput<?= $u['id'] ?>').value='Chăm sóc khách hàng'">CSKH</span>
                                <?php if ($isSuperAdmin): ?>
                                    <span class="badge bg-light text-danger border" style="cursor: pointer; font-size: 0.72rem;" onclick="document.getElementById('editTitleInput<?= $u['id'] ?>').value='Quản trị viên'">Quản trị viên</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- VAI TRÒ TÀI KHOẢN -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark d-flex align-items-center justify-content-between">
                                <span><i class="fa-solid fa-shield-halved text-primary me-1"></i>Vai trò tài khoản</span>
                                <?php if ($isSuperAdmin): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success" style="font-size: 0.7rem;"><i class="fa-solid fa-crown me-1"></i>Quyền Super Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.7rem;"><i class="fa-solid fa-user-gear me-1"></i>Cấp quyền Nhân viên</span>
                                <?php endif; ?>
                            </label>
                            
                            <?php if ($isSuperAdmin): ?>
                                <?php if (strtolower(trim($u['email'])) === 'admin@vphone.vn'): ?>
                                    <input type="hidden" name="role" value="1">
                                    <input type="text" class="form-control bg-light fw-bold text-danger border" value="Quản Trị Viên Tối Cao (Super Admin)" readonly disabled>
                                    <small class="text-muted" style="font-size: 0.72rem;"><i class="fa-solid fa-lock me-1"></i>Tài khoản gốc của hệ thống không thể thay đổi vai trò.</small>
                                <?php else: ?>
                                    <select name="role" class="form-select">
                                        <option value="0" <?= $u['role'] == 0 ? 'selected' : '' ?>>Khách Hàng Thường</option>
                                        <option value="2" <?= $u['role'] == 2 ? 'selected' : '' ?>>Nhân Viên Cửa Hàng (Staff)</option>
                                        <option value="1" <?= $u['role'] == 1 ? 'selected' : '' ?>>Quản Trị Viên (Admin)</option>
                                    </select>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ((int)$u['role'] === 1): ?>
                                    <input type="hidden" name="role" value="1">
                                    <input type="text" class="form-control bg-light text-danger fw-bold border" value="Quản Trị Viên (Admin)" readonly disabled>
                                    <small class="text-danger" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-lock me-1"></i>Chỉ tài khoản Quản trị tối cao (<strong>admin@vphone.vn</strong>) mới có quyền chỉnh sửa tài khoản Quản trị viên.
                                    </small>
                                <?php else: ?>
                                    <select name="role" class="form-select">
                                        <option value="0" <?= $u['role'] == 0 ? 'selected' : '' ?>>Khách Hàng Thường</option>
                                        <option value="2" <?= $u['role'] == 2 ? 'selected' : '' ?>>Nhân Viên Cửa Hàng (Staff)</option>
                                        <option value="1" disabled class="text-muted">Quản Trị Viên (Admin) - 🔒 Chỉ Super Admin</option>
                                    </select>
                                    <small class="text-success" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-circle-check me-1"></i>Bạn có quyền cấp quyền <strong>Nhân viên</strong> hoặc <strong>Khách hàng</strong>. Cấp quyền Admin yêu cầu Quản trị tối cao.
                                    </small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <!-- HẠNG THÀNH VIÊN VIP -->
                        <?php 
                            $mSt = $userStats[$u['id']] ?? ['order_count' => 0, 'total_spent' => 0];
                            $mSpent = (float)$mSt['total_spent'];
                            $mTier = $membershipService->getTierBySpent($mSpent, $u['membership_tier'] ?? null);
                        ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark d-flex align-items-center justify-content-between">
                                <span><i class="fa-solid fa-crown text-warning me-1"></i>Hạng thành viên VIP</span>
                                <span class="text-danger fw-bold font-monospace" style="font-size: 0.72rem;">Đã chi: <?= number_format($mSpent, 0, ',', '.') ?> VNĐ</span>
                            </label>
                            <select name="membership_tier" class="form-select form-select-sm">
                                <option value="auto" <?= (empty($u['membership_tier']) || $u['membership_tier'] === 'auto') ? 'selected' : '' ?>>Tự động theo chi tiêu (Hiện tại: <?= htmlspecialchars($mTier['short_name']) ?>)</option>
                                <option value="standard" <?= ($u['membership_tier'] === 'standard') ? 'selected' : '' ?>>Thành viên Tiêu Chuẩn (< 100tr)</option>
                                <option value="silver" <?= ($u['membership_tier'] === 'silver') ? 'selected' : '' ?>>Hạng Bạc - Silver VIP (100tr - Giảm 1.5%)</option>
                                <option value="gold" <?= ($u['membership_tier'] === 'gold') ? 'selected' : '' ?>>Hạng Vàng - Gold VIP (300tr - Giảm 3%)</option>
                                <option value="diamond" <?= ($u['membership_tier'] === 'diamond') ? 'selected' : '' ?>>Hạng Kim Cương - Diamond VIP (500tr - Giảm 5%)</option>
                            </select>
                            <small class="text-muted" style="font-size: 0.7rem;">Mặc định tự động nâng hạng theo chi tiêu: Silver 100tr, Gold 300tr, Diamond 500tr.</small>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold text-dark">Đặt lại mật khẩu mới (Tùy chọn)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-key text-warning"></i></span>
                                <input type="password" name="new_password" class="form-control border-start-0" placeholder="Để trống nếu giữ nguyên mật khẩu cũ" minlength="6">
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">Chỉ nhập khi khách hàng yêu cầu đặt lại mật khẩu mới (tối thiểu 6 ký tự).</small>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold text-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Lưu Thay Đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL ĐỔI VAI TRÒ & CHỨC DANH NHANH -->
    <?php if (strtolower(trim($u['email'])) !== 'admin@vphone.vn' && !((int)$u['role'] === 1 && !$isSuperAdmin && $u['id'] != $_SESSION['user']['id'])): ?>
        <div class="modal fade" id="modalChangeRole<?= $u['id'] ?>" tabindex="-1" aria-labelledby="modalRoleLabel<?= $u['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow-lg">
                    <form method="POST" action="users.php">
                        <input type="hidden" name="action" value="change_user_role">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">

                        <div class="modal-header border-0 pb-0 pt-4 px-4">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2.5 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                    <i class="fa-solid fa-user-shield fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalRoleLabel<?= $u['id'] ?>">Phân Quyền & Chức Danh</h5>
                                    <small class="text-secondary">Tài khoản #<?= $u['id'] ?> &bull; <?= htmlspecialchars($u['fullname']) ?></small>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-4">
                            <!-- User info card -->
                            <div class="p-3 bg-light rounded-4 border mb-3 d-flex align-items-center gap-3">
                                <?php 
                                    $mAvSrc = null;
                                    if (!empty($u['avatar'])) {
                                        if (str_starts_with($u['avatar'], 'http://') || str_starts_with($u['avatar'], 'https://')) {
                                            $mAvSrc = $u['avatar'];
                                        } elseif (file_exists(__DIR__ . '/../' . $u['avatar'])) {
                                            $mAvSrc = '../' . $u['avatar'];
                                        }
                                    }
                                ?>
                                <?php if ($mAvSrc): ?>
                                    <img src="<?= htmlspecialchars($mAvSrc) ?>" alt="Avatar" class="rounded-circle border" style="width: 44px; height: 44px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px; background: #0066cc;">
                                        <?= mb_substr(htmlspecialchars($u['fullname']), 0, 1, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                                <div class="min-w-0">
                                    <div class="fw-bold text-dark text-truncate"><?= htmlspecialchars($u['fullname']) ?></div>
                                    <div class="text-secondary small font-monospace text-truncate"><?= htmlspecialchars($u['email']) ?></div>
                                    <div class="text-muted small">Hiện tại: <span class="fw-semibold text-primary"><?= $u['role'] == 1 ? 'Admin' : ($u['role'] == 2 ? 'Nhân Viên' : 'Khách Hàng') ?></span> <?= !empty($u['staff_title']) ? '('.htmlspecialchars($u['staff_title']).')' : '' ?></div>
                                </div>
                            </div>

                            <!-- Role Selection -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark mb-2">Chọn vai trò mới cho tài khoản:</label>
                                <div class="d-flex flex-column gap-2">
                                    <!-- Khách hàng -->
                                    <label class="p-3 border rounded-4 d-flex align-items-center gap-3 cursor-pointer role-option-card <?= $u['role'] == 0 ? 'border-primary bg-primary bg-opacity-10' : 'bg-white' ?>" style="cursor: pointer;">
                                        <input type="radio" name="role" value="0" class="form-check-input mt-0" <?= $u['role'] == 0 ? 'checked' : '' ?> onchange="onRoleChangeSelect(<?= $u['id'] ?>, 0)">
                                        <div class="flex-grow-1">
                                            <div class="fw-bold text-dark small"><i class="fa-solid fa-user me-1.5 text-primary"></i>Khách Hàng Thường</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Mua sắm, tích điểm thành viên, theo dõi đơn hàng</div>
                                        </div>
                                    </label>

                                    <!-- Nhân viên -->
                                    <label class="p-3 border rounded-4 d-flex align-items-center gap-3 cursor-pointer role-option-card <?= $u['role'] == 2 ? 'border-success bg-success bg-opacity-10' : 'bg-white' ?>" style="cursor: pointer;">
                                        <input type="radio" name="role" value="2" class="form-check-input mt-0" <?= $u['role'] == 2 ? 'checked' : '' ?> onchange="onRoleChangeSelect(<?= $u['id'] ?>, 2)">
                                        <div class="flex-grow-1">
                                            <div class="fw-bold text-success small"><i class="fa-solid fa-id-badge me-1.5 text-success"></i>Nhân Viên Cửa Hàng (Staff)</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Được cấp quyền xử lý đơn hàng, kho máy, bảo hành theo nhiệm vụ</div>
                                        </div>
                                        <span class="badge bg-success bg-opacity-25 text-success rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">Phổ biến</span>
                                    </label>

                                    <!-- Quản trị viên (Admin) -->
                                    <?php if ($isSuperAdmin): ?>
                                        <label class="p-3 border rounded-4 d-flex align-items-center gap-3 cursor-pointer role-option-card <?= $u['role'] == 1 ? 'border-danger bg-danger bg-opacity-10' : 'bg-white' ?>" style="cursor: pointer;">
                                            <input type="radio" name="role" value="1" class="form-check-input mt-0" <?= $u['role'] == 1 ? 'checked' : '' ?> onchange="onRoleChangeSelect(<?= $u['id'] ?>, 1)">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-danger small"><i class="fa-solid fa-shield-halved me-1.5 text-danger"></i>Quản Trị Viên (Admin)</div>
                                                <div class="text-muted" style="font-size: 0.72rem;">Toàn quyền quản trị hệ thống V-Phone Store</div>
                                            </div>
                                            <span class="badge bg-danger text-white rounded-pill px-2 py-0.5" style="font-size: 0.68rem;"><i class="fa-solid fa-crown text-warning me-1"></i>Super Admin</span>
                                        </label>
                                    <?php else: ?>
                                        <div class="p-3 border rounded-4 d-flex align-items-center gap-3 bg-light opacity-75">
                                            <input type="radio" name="role" value="1" class="form-check-input mt-0" disabled>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-muted small"><i class="fa-solid fa-shield-halved me-1.5"></i>Quản Trị Viên (Admin)</div>
                                                <div class="text-muted" style="font-size: 0.72rem;">🔒 Chỉ Quản trị tối cao (admin@vphone.vn) mới có quyền cấp Admin</div>
                                            </div>
                                            <span class="badge bg-secondary bg-opacity-25 text-secondary rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">Bảo mật</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Input Chức danh hiển thị -->
                            <div class="mb-2">
                                <label class="form-label small fw-bold text-dark d-flex align-items-center justify-content-between">
                                    <span><i class="fa-solid fa-award text-primary me-1"></i>Chức danh hiển thị</span>
                                    <span class="text-muted" style="font-size: 0.7rem;">Hiển thị trực tiếp trong bảng và danh thiếp</span>
                                </label>
                                <input type="text" name="staff_title" id="roleModalTitle<?= $u['id'] ?>" class="form-control rounded-3" value="<?= htmlspecialchars($u['staff_title'] ?: ($u['role'] == 2 ? 'Nhân viên cửa hàng' : ($u['role'] == 1 ? 'Quản trị viên' : ''))) ?>" placeholder="Ví dụ: Nhân viên bán hàng, Kỹ thuật viên & Bảo hành...">
                                
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    <span class="badge bg-light text-secondary border" style="cursor: pointer; font-size: 0.72rem;" onclick="setRoleModalTitle(<?= $u['id'] ?>, 'Nhân viên bán hàng', 2)">Bán hàng</span>
                                    <span class="badge bg-light text-secondary border" style="cursor: pointer; font-size: 0.72rem;" onclick="setRoleModalTitle(<?= $u['id'] ?>, 'Kỹ thuật viên & Bảo hành', 2)">Kỹ thuật & BH</span>
                                    <span class="badge bg-light text-secondary border" style="cursor: pointer; font-size: 0.72rem;" onclick="setRoleModalTitle(<?= $u['id'] ?>, 'Quản lý kho & Nhập hàng', 2)">Kho máy</span>
                                    <span class="badge bg-light text-secondary border" style="cursor: pointer; font-size: 0.72rem;" onclick="setRoleModalTitle(<?= $u['id'] ?>, 'Chăm sóc khách hàng', 2)">CSKH</span>
                                    <?php if ($isSuperAdmin): ?>
                                        <span class="badge bg-light text-danger border" style="cursor: pointer; font-size: 0.72rem;" onclick="setRoleModalTitle(<?= $u['id'] ?>, 'Quản trị viên', 1)">Quản trị viên</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 pt-0 px-4 pb-4">
                            <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold text-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                                <i class="fa-solid fa-check me-1"></i>Lưu Vai Trò
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<script>
function filterUsersTable() {
    const q = document.getElementById('userSearch').value.toLowerCase().trim();
    const role = document.getElementById('userRoleFilter').value;
    const tier = document.getElementById('userTierFilter') ? document.getElementById('userTierFilter').value : 'all';

    document.querySelectorAll('.user-row').forEach(row => {
        const text = row.getAttribute('data-search') || '';
        const rRole = row.getAttribute('data-role') || '';
        const rTier = row.getAttribute('data-tier') || '';

        const matchQ = !q || text.includes(q);
        const matchRole = (role === 'all') || (rRole === role);
        const matchTier = (tier === 'all') || (rTier === tier);

        if (matchQ && matchRole && matchTier) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function onRoleChangeSelect(uid, roleVal) {
    const titleInput = document.getElementById('roleModalTitle' + uid);
    if (!titleInput) return;
    if (roleVal === 2 && (!titleInput.value || titleInput.value === 'Quản trị viên')) {
        titleInput.value = 'Nhân viên bán hàng';
    } else if (roleVal === 1 && (!titleInput.value || titleInput.value === 'Nhân viên bán hàng' || titleInput.value === 'Nhân viên cửa hàng')) {
        titleInput.value = 'Quản trị viên';
    }
}

function setRoleModalTitle(uid, title, roleVal) {
    const titleInput = document.getElementById('roleModalTitle' + uid);
    if (titleInput) titleInput.value = title;
    const modal = document.getElementById('modalChangeRole' + uid);
    if (modal) {
        const radios = modal.querySelectorAll('input[name="role"]');
        radios.forEach(r => {
            if (parseInt(r.value) === roleVal && !r.disabled) {
                r.checked = true;
            }
        });
    }
}

function confirmToggleRole(e, href, name, isCurrentlyAdmin) {
    e.preventDefault();
    const actionText = isCurrentlyAdmin ? 'hủy quyền Admin (chuyển thành Khách Hàng)' : 'cấp quyền Quản Trị Viên (Admin)';
    if (typeof Swal === 'undefined') {
        if (confirm('Bạn có chắc muốn ' + actionText + ' cho tài khoản ' + name + '?')) {
            window.location.href = href;
        }
        return;
    }
    Swal.fire({
        title: isCurrentlyAdmin ? 'Hủy Quyền Quản Trị?' : 'Cấp Quyền Quản Trị Viên?',
        html: `Bạn có chắc chắn muốn <strong>${actionText}</strong> cho tài khoản <strong>${name}</strong>?`,
        icon: isCurrentlyAdmin ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-check me-1"></i>Xác Nhận',
        cancelButtonText: 'Hủy Bỏ',
        confirmButtonColor: isCurrentlyAdmin ? '#ef4444' : '#0066cc',
        cancelButtonColor: '#94a3b8',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-4 shadow-lg border-0 p-4',
            title: 'fw-bold fs-5 text-dark mb-2',
            confirmButton: (isCurrentlyAdmin ? 'btn btn-danger' : 'btn btn-primary') + ' rounded-pill px-4 py-2 fw-bold shadow-sm',
            cancelButton: 'btn btn-light rounded-pill px-4 py-2 fw-bold text-secondary me-2'
        },
        buttonsStyling: false
    }).then((res) => {
        if (res.isConfirmed) {
            window.location.href = href;
        }
    });
}

function alertNoPermission() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Từ Chối Quyền Hạn!',
            html: 'Chỉ có tài khoản Quản trị tối cao (<strong>admin@vphone.vn</strong>) mới có quyền cấp quyền Admin cho tài khoản khác.',
            confirmButtonText: 'Đã hiểu',
            customClass: {
                popup: 'rounded-4 shadow-lg border-0 p-4',
                title: 'fw-bold fs-5 text-danger mb-2',
                confirmButton: 'btn btn-primary rounded-pill px-4 py-2 fw-bold'
            },
            buttonsStyling: false
        });
    } else {
        alert('Chỉ có tài khoản Quản trị tối cao (admin@vphone.vn) mới có quyền cấp quyền Quản trị viên (Admin)!');
    }
}

function confirmDeleteUser(e, href, name, email) {
    e.preventDefault();
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Xóa Tài Khoản?',
            html: `Bạn có chắc chắn muốn xóa vĩnh viễn tài khoản <strong>${name}</strong> (${email})?<br><small class="text-danger mt-1 d-block"><i class="fa-solid fa-triangle-exclamation me-1"></i>Thao tác này không thể hoàn tác!</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i>Xóa Vĩnh Viễn',
            cancelButtonText: 'Hủy Bỏ',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-4 shadow-lg border-0 p-4',
                title: 'fw-bold fs-5 text-dark mb-2',
                confirmButton: 'btn btn-danger rounded-pill px-4 py-2 fw-bold shadow-sm',
                cancelButton: 'btn btn-light rounded-pill px-4 py-2 fw-bold text-secondary me-2'
            },
            buttonsStyling: false
        }).then((res) => {
            if (res.isConfirmed) {
                window.location.href = href;
            }
        });
    } else {
        if (confirm('CẢNH BÁO: Bạn có chắc chắn muốn xóa vĩnh viễn tài khoản ' + name + ' (' + email + ')?')) {
            window.location.href = href;
        }
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
