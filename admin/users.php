<?php
$pageTitle = 'Quản Lý Khách Hàng - V-Phone Admin';
require_once 'includes/header.php';

$message = '';

// Chuyển đổi quyền hạn
if (isset($_GET['toggle_role_id'])) {
    $uid = (int)$_GET['toggle_role_id'];
    $u = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $u->execute([$uid]);
    $currRole = $u->fetchColumn();

    $newRole = ($currRole == 1) ? 0 : 1;
    $up = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $up->execute([$newRole, $uid]);
    $message = "Đã cập nhật quyền tài khoản thành công!";
}

$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Quản Lý Tài Khoản Khách Hàng</h3>
        <p class="text-secondary small mb-0">Xem danh sách thành viên đăng ký và phân quyền quản trị</p>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 py-2" role="alert">
        <i class="fa-solid fa-check me-2"></i><?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="admin-table-card">
    <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-users text-primary"></i> Danh Sách Thành Viên & Quản Trị
            </h5>
            <small class="text-secondary">Tổng cộng <?= count($users) ?> tài khoản trong hệ thống V-Phone</small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Họ và Tên</th>
                    <th>Email</th>
                    <th>Số Điện Thoại</th>
                    <th>Địa Chỉ</th>
                    <th>Vai Trò</th>
                    <th class="text-end">Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-bold text-muted">#<?= $u['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background: <?= $u['role'] == 1 ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 'linear-gradient(135deg, #0066cc, #0284c7)' ?>; font-weight: 700; font-size: 0.8rem;">
                                    <?= mb_substr(htmlspecialchars($u['fullname']), 0, 1, 'UTF-8') ?>
                                </div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($u['fullname']) ?></div>
                            </div>
                        </td>
                        <td><span class="text-secondary"><?= htmlspecialchars($u['email']) ?></span></td>
                        <td><span class="fw-medium text-dark"><?= htmlspecialchars($u['phone'] ?: 'Chưa cập nhật') ?></span></td>
                        <td class="small text-secondary text-truncate" style="max-width: 200px;">
                            <?= htmlspecialchars($u['address'] ?: 'Chưa cập nhật') ?>
                        </td>
                        <td>
                            <?php if ($u['role'] == 1): ?>
                                <span class="badge badge-soft-danger rounded-pill px-3 py-1"><i class="fa-solid fa-shield-halved me-1"></i>Admin</span>
                            <?php else: ?>
                                <span class="badge badge-soft-primary rounded-pill px-3 py-1">Khách Hàng</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                                <a href="users.php?toggle_role_id=<?= $u['id'] ?>" class="btn btn-light border text-secondary btn-sm rounded-pill px-3 fw-bold">
                                    <i class="fa-solid fa-repeat me-1"></i>Đổi vai trò
                                </a>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">Bạn</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
