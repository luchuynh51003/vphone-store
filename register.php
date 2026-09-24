<?php
require_once 'config/database.php';
$pageTitle = 'Đăng ký tài khoản - V-Phone';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $repassword = $_POST['repassword'] ?? '';

    if (empty($fullname) || empty($email) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ Họ tên, Email và Mật khẩu!';
    } elseif ($password !== $repassword) {
        $error = 'Hai mật khẩu nhập không khớp nhau!';
    } else {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $error = 'Email này đã được sử dụng! Vui lòng dùng email khác.';
        } else {
            // Mã hóa mật khẩu bảo mật password_hash
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (fullname, email, phone, address, password) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$fullname, $email, $phone, $address, $hashedPassword]);

            $success = 'Đăng ký tài khoản thành công! Bạn có thể đăng nhập ngay.';
        }
    }
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border">
                <div class="text-center mb-4">
                    <span class="bg-primary text-white rounded-circle p-3 d-inline-block mb-2"><i class="fa-solid fa-user-plus fs-4"></i></span>
                    <h4 class="fw-bold">Đăng Ký Tài Khoản V-Phone</h4>
                    <p class="text-secondary small">Tạo tài khoản để đặt hàng và theo dõi đơn mua</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success py-2 small">
                        <?= htmlspecialchars($success) ?> <a href="login.php" class="fw-bold">Đăng nhập ngay</a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Họ và tên *</label>
                        <input type="text" name="fullname" class="form-control rounded-pill" required placeholder="Ví dụ: Huỳnh Bá Lực">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email *</label>
                        <input type="email" name="email" class="form-control rounded-pill" required placeholder="name@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control rounded-pill" placeholder="0901234567">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Địa chỉ giao hàng mặc định</label>
                        <input type="text" name="address" class="form-control rounded-pill" placeholder="Số nhà, Tên đường, Quận/Huyện, TP">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mật khẩu *</label>
                        <input type="password" name="password" class="form-control rounded-pill" required placeholder="Tối thiểu 6 ký tự">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Nhập lại mật khẩu *</label>
                        <input type="password" name="repassword" class="form-control rounded-pill" required placeholder="Xác nhận lại mật khẩu">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2">ĐĂNG KÝ NGAY</button>
                </form>

                <div class="text-center mt-4 small">
                    Đã có tài khoản? <a href="login.php" class="text-primary fw-bold text-decoration-none">Đăng nhập tại đây</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
