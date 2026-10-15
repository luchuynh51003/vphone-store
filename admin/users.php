<?php
require_once __DIR__ . '/../config/database.php';

// Xử lý xuất file CSV danh sách khách hàng
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'vphone_khach_hang_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'Họ và tên', 'Email', 'Số điện thoại', 'Địa chỉ', 'Vai trò', 'Số đơn hàng', 'Tổng chi tiêu (VNĐ)', 'Hạng thành viên', 'Ngày tham gia']);
    
    $uStats = $pdo->query("SELECT user_id, COUNT(*) as cnt, COALESCE(SUM(CASE WHEN status = 'Đã giao thành công' THEN total_money ELSE 0 END), 0) as spent FROM orders WHERE user_id IS NOT NULL GROUP BY user_id")->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($uStats as $s) { $map[$s['user_id']] = $s; }

    $allUsers = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allUsers as $u) {
        $st = $map[$u['id']] ?? ['cnt' => 0, 'spent' => 0];
        $spent = (float)$st['spent'];
        $tier = $spent >= 50000000 ? 'VIP Kim Cương' : ($spent >= 20000000 ? 'VIP Vàng' : ($spent >= 5000000 ? 'VIP Bạc' : 'Thành viên mới'));
        fputcsv($output, [
            $u['id'],
            $u['fullname'],
            $u['email'],
            $u['phone'] ?: 'Chưa có',
            $u['address'] ?: 'Chưa có',
            $u['role'] == 1 ? 'Quản trị viên (Admin)' : 'Khách hàng',
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

// Xử lý cập nhật thông tin khách hàng từ Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $uid = (int)($_POST['user_id'] ?? 0);
    $fullname = trim($_POST['fullname'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = (int)($_POST['role'] ?? 0);
    $newPassword = trim($_POST['new_password'] ?? '');

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
                    if (!empty($existing['avatar']) && str_starts_with($existing['avatar'], 'assets/images/users/') && file_exists(__DIR__ . '/../' . $existing['avatar'])) {
                        @unlink(__DIR__ . '/../' . $existing['avatar']);
                    }
                }
            }
        } elseif (!empty($_POST['remove_avatar']) && $_POST['remove_avatar'] == '1') {
            if (!empty($existing['avatar']) && str_starts_with($existing['avatar'], 'assets/images/users/') && file_exists(__DIR__ . '/../' . $existing['avatar'])) {
                @unlink(__DIR__ . '/../' . $existing['avatar']);
            }
            $avatarPath = '';
        }

        $updated = $userModel->updateByAdmin($uid, $fullname, $email, $phone, $address, $role, $newPassword ?: null, $avatarPath);
        if ($updated) {
            $message = "Đã cập nhật thông tin tài khoản #$uid ($fullname) thành công!";
            // Nếu admin đang sửa chính tài khoản của mình thì đồng bộ session
            if ($uid == $_SESSION['user']['id']) {
                $_SESSION['user']['fullname'] = $fullname;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['phone'] = $phone;
                $_SESSION['user']['address'] = $address;
                $_SESSION['user']['role'] = $role;
                if ($avatarPath !== null) {
                    $_SESSION['user']['avatar'] = $avatarPath;
                }
            }
        } else {
            $error = "Có lỗi xảy ra khi cập nhật dữ liệu. Vui lòng thử lại!";
        }
    }
}

// Chuyển đổi quyền hạn
if (isset($_GET['toggle_role_id'])) {
    $uid = (int)$_GET['toggle_role_id'];
    $u = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $u->execute([$uid]);
    $currRole = $u->fetchColumn();

    if ($currRole !== false) {
        $newRole = ($currRole == 1) ? 0 : 1;
        $up = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $up->execute([$newRole, $uid]);
        $message = "Đã cập nhật quyền tài khoản #$uid thành công!";
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();
// Thống kê đơn hàng và chi tiêu
$userStatsRaw = $pdo->query("SELECT user_id, COUNT(*) as order_count, COALESCE(SUM(CASE WHEN status = 'Đã giao thành công' THEN total_money ELSE 0 END), 0) as total_spent FROM orders WHERE user_id IS NOT NULL GROUP BY user_id")->fetchAll(PDO::FETCH_ASSOC);
$userStats = [];
foreach ($userStatsRaw as $usrStat) {
    $userStats[$usrStat['user_id']] = $usrStat;
}
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
    <div class="alert alert-success alert-dismissible fade show rounded-4 py-2.5 px-3 mb-4 shadow-sm" role="alert">
        <i class="fa-solid fa-check-circle me-2 text-success"></i><?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 py-2.5 px-3 mb-4 shadow-sm" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="admin-table-card">
    <div class="p-3.5 px-4 border-bottom bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 520px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" id="userSearch" class="form-control border-start-0 bg-light" placeholder="Tìm theo tên, email, số điện thoại..." onkeyup="filterUsersTable()">
            </div>
            <select id="userRoleFilter" class="form-select form-select-sm bg-light" style="max-width: 170px;" onchange="filterUsersTable()">
                <option value="all">Tất cả vai trò</option>
                <option value="admin">Quản trị viên (Admin)</option>
                <option value="customer">Khách hàng</option>
            </select>
        </div>
        <div>
            <span class="badge bg-light text-dark border rounded-pill px-3 py-1.5 fw-bold small">
                Tổng cộng: <?= count($users) ?> tài khoản
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0" id="usersTable">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Hình Ảnh & Họ Tên</th>
                    <th>Email</th>
                    <th>Số Điện Thoại</th>
                    <th>Đơn & Chi Tiêu</th>
                    <th>Hạng Thành Viên</th>
                    <th>Vai Trò</th>
                    <th class="text-end" style="min-width: 160px;">Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <?php
                        $st = $userStats[$u['id']] ?? ['order_count' => 0, 'total_spent' => 0];
                        $ordCount = (int)$st['order_count'];
                        $spent = (float)$st['total_spent'];
                        $roleVal = $u['role'] == 1 ? 'admin' : 'customer';
                    ?>
                    <tr class="user-row" data-search="<?= htmlspecialchars(mb_strtolower($u['fullname'] . ' ' . $u['email'] . ' ' . ($u['phone'] ?? ''), 'UTF-8')) ?>" data-role="<?= $roleVal ?>">
                        <td class="fw-bold text-muted">#<?= $u['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($u['avatar']) && file_exists(__DIR__ . '/../' . $u['avatar'])): ?>
                                    <img src="../<?= htmlspecialchars($u['avatar']) ?>" alt="Avatar" class="rounded-circle border flex-shrink-0" style="width: 38px; height: 38px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background: <?= $u['role'] == 1 ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 'linear-gradient(135deg, #0066cc, #0284c7)' ?>; font-weight: 700; font-size: 0.85rem;">
                                        <?= mb_substr(htmlspecialchars($u['fullname']), 0, 1, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($u['fullname']) ?></div>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;"><?= htmlspecialchars($u['address'] ?: 'Chưa cập nhật địa chỉ') ?></small>
                                </div>
                            </div>
                        </td>
                        <td><span class="text-secondary small"><?= htmlspecialchars($u['email']) ?></span></td>
                        <td><span class="fw-medium text-dark small"><?= htmlspecialchars($u['phone'] ?: 'Chưa có') ?></span></td>
                        <td>
                            <div class="small">
                                <span class="fw-bold text-dark"><?= $ordCount ?> đơn</span>
                                <div class="text-danger fw-bold" style="font-size: 0.78rem;"><?= number_format($spent, 0, ',', '.') ?> đ</div>
                            </div>
                        </td>
                        <td>
                            <?php if ($spent >= 50000000): ?>
                                <span class="badge rounded-pill text-white px-2.5 py-1" style="background: linear-gradient(135deg, #8b5cf6, #6366f1); font-size: 0.72rem;">
                                    <i class="fa-solid fa-crown me-1 text-warning"></i>VIP Kim Cương
                                </span>
                            <?php elseif ($spent >= 20000000): ?>
                                <span class="badge rounded-pill text-dark px-2.5 py-1" style="background: #fef08a; border: 1px solid #fde047; font-size: 0.72rem;">
                                    <i class="fa-solid fa-medal me-1 text-warning"></i>VIP Vàng
                                </span>
                            <?php elseif ($spent >= 5000000): ?>
                                <span class="badge rounded-pill text-secondary bg-light border px-2.5 py-1" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-medal me-1"></i>VIP Bạc
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border rounded-pill px-2 py-1" style="font-size: 0.72rem;">
                                    Mới
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['role'] == 1): ?>
                                <span class="badge badge-soft-danger rounded-pill px-3 py-1"><i class="fa-solid fa-shield-halved me-1"></i>Admin</span>
                            <?php else: ?>
                                <span class="badge badge-soft-primary rounded-pill px-3 py-1">Khách Hàng</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <div class="d-flex justify-content-end align-items-center gap-1">
                                <!-- Nút Sửa Thông Tin -->
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1 fw-bold" data-bs-toggle="modal" data-bs-target="#modalEditUser<?= $u['id'] ?>" title="Sửa thông tin khách hàng">
                                    <i class="fa-solid fa-pen-to-square me-1"></i>Sửa
                                </button>

                                <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                                    <a href="users.php?toggle_role_id=<?= $u['id'] ?>" class="btn btn-light border text-secondary btn-sm rounded-pill px-2.5 py-1 fw-bold" onclick="return confirm('Bạn có chắc muốn đổi vai trò cho tài khoản này?')">
                                        <i class="fa-solid fa-repeat me-1"></i>Đổi vai trò
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1.5 small">Bạn</span>
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
                                <?php if (!empty($u['avatar']) && file_exists(__DIR__ . '/../' . $u['avatar'])): ?>
                                    <img src="../<?= htmlspecialchars($u['avatar']) ?>" alt="Avatar" class="rounded-circle border" style="width: 50px; height: 50px; object-fit: cover;">
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
                                <input type="email" name="email" class="form-control border-start-0" value="<?= htmlspecialchars($u['email']) ?>" required>
                            </div>
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

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Vai trò tài khoản</label>
                            <select name="role" class="form-select">
                                <option value="0" <?= $u['role'] == 0 ? 'selected' : '' ?>>Khách Hàng Thường</option>
                                <option value="1" <?= $u['role'] == 1 ? 'selected' : '' ?>>Quản Trị Viên (Admin)</option>
                            </select>
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
<?php endforeach; ?>

<script>
function filterUsersTable() {
    const q = document.getElementById('userSearch').value.toLowerCase().trim();
    const role = document.getElementById('userRoleFilter').value;

    document.querySelectorAll('.user-row').forEach(row => {
        const text = row.getAttribute('data-search') || '';
        const rRole = row.getAttribute('data-role') || '';

        const matchQ = !q || text.includes(q);
        const matchRole = (role === 'all') || (rRole === role);

        if (matchQ && matchRole) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
