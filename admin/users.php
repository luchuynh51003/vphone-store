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

<div class="card border-0 rounded-4 shadow-sm bg-white p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Họ và Tên</th>
                    <th>Email</th>
                    <th>Số Điện Thoại</th>
                    <th>Địa Chỉ</th>
                    <th>Vai Trò</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-bold text-secondary"><?= $u['id'] ?></td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($u['fullname']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phone'] ?? 'Chưa cập nhật') ?></td>
                        <td class="small text-secondary"><?= htmlspecialchars($u['address'] ?? 'Chưa cập nhật') ?></td>
                        <td>
                            <?php if ($u['role'] == 1): ?>
                                <span class="badge bg-danger rounded-pill px-3 py-1"><i class="fa-solid fa-shield-halved me-1"></i>Admin</span>
                            <?php else: ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1">Khách Hàng</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                                <a href="users.php?toggle_role_id=<?= $u['id'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                    Đổi vai trò
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">Tài khoản của bạn</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
