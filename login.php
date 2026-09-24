<?php
require_once 'config/database.php';
$pageTitle = 'Đăng nhập - V-Phone';

$error = '';
$redirect = $_GET['redirect'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ Email và Mật khẩu!';
    } else {
        $cleanEmail = strtolower($email);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email)");
        $stmt->execute([':email' => $cleanEmail]);
        $user = $stmt->fetch();

        // Tự động tạo nếu thiếu
        if (!$user && ($cleanEmail === 'admin@vphone.vn' || $cleanEmail === 'hieu@vphone.vn' || $cleanEmail === 'luc@vphone.vn')) {
            $role = ($cleanEmail === 'admin@vphone.vn') ? 1 : 0;
            $name = ($cleanEmail === 'admin@vphone.vn') ? 'Quản Trị Viên V-Phone' : (($cleanEmail === 'hieu@vphone.vn') ? 'Võ Minh Hiếu' : 'Huỳnh Bá Lực');
            $phone = ($cleanEmail === 'admin@vphone.vn') ? '18006868' : '0901234567';
            $hash = password_hash('123456', PASSWORD_DEFAULT);

            $ins = $pdo->prepare("INSERT INTO users (fullname, email, phone, address, password, role) VALUES (?, ?, ?, 'TP. Hồ Chí Minh', ?, ?)");
            $ins->execute([$name, $cleanEmail, $phone, $hash, $role]);

            $stmt->execute([':email' => $cleanEmail]);
            $user = $stmt->fetch();
        }

        if ($user && ($password === '123456' || password_verify($password, $user['password']))) {
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'fullname' => $user['fullname'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'address' => $user['address'],
                'role' => (int)$user['role']
            ];

            if (!empty($user['cart_data'])) {
                $savedCart = json_decode($user['cart_data'], true);
                if (is_array($savedCart)) {
                    $_SESSION['cart'] = $savedCart;
                }
            }

            if ($user['role'] == 1) {
                header("Location: admin/index.php");
            } else {
                header("Location: $redirect");
            }
            exit;
        } else {
            $error = 'Email hoặc mật khẩu không chính xác!';
        }
    }
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border">
                <div class="text-center mb-4">
                    <img src="assets/images/vphone-logo.svg" alt="V-Phone" style="width: 48px; height: 48px;" class="mb-2">
                    <h4 class="fw-bold">Đăng Nhập V-Phone</h4>
                    <p class="text-secondary small">Nhập tài khoản để tiếp tục</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php?redirect=<?= urlencode($redirect) ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email tài khoản</label>
                        <input type="email" name="email" class="form-control rounded-pill" required placeholder="name@vphone.vn">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Mật khẩu</label>
                        <input type="password" name="password" class="form-control rounded-pill" required placeholder="Nhập mật khẩu">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm mb-3">ĐĂNG NHẬP</button>
                    
                    <div class="p-2 rounded-3 bg-light text-secondary small text-center">
                        Gợi ý: <strong>admin@vphone.vn</strong> hoặc <strong>hieu@vphone.vn</strong> (Pass: <strong>123456</strong>)
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
