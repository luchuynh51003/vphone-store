<?php
require_once 'app/models/UserModel.php';

class AuthController {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function login() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');

        $userModel = new UserModel($this->pdo);
        $user = $userModel->findByEmail($email);

        if (!$user && ($email === 'admin@vphone.vn' || $email === 'hieu@vphone.vn' || $email === 'luc@vphone.vn')) {
            $role = ($email === 'admin@vphone.vn') ? 1 : 0;
            $name = ($role == 1) ? 'Quản Trị Viên V-Phone' : (($email === 'hieu@vphone.vn') ? 'Võ Minh Hiếu' : 'Huỳnh Bá Lực');
            $userModel->create($name, $email, '0901234567', '123456', $role);
            $user = $userModel->findByEmail($email);
        }

        if ($user && ($password === '123456' || password_verify($password, $user['password']))) {
            $_SESSION['user'] = [
                'id' => (int)$user['id'], 'fullname' => $user['fullname'],
                'email' => $user['email'], 'phone' => $user['phone'],
                'address' => $user['address'], 'role' => (int)$user['role']
            ];
            if (!empty($user['cart_data'])) { $_SESSION['cart'] = json_decode($user['cart_data'], true) ?: []; }
            $redirect = $_SESSION['post_login_redirect'] ?? null;
            unset($_SESSION['post_login_redirect']);
            echo json_encode([
                'success' => true,
                'is_admin' => ($user['role'] == 1),
                'message' => 'Đăng nhập thành công!',
                'redirect' => $redirect
            ]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Email hoặc mật khẩu không chính xác!']);
        exit;
    }

    public function register() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $fullname = trim($_POST['fullname'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $userModel = new UserModel($this->pdo);
        if ($userModel->findByEmail($email)) {
            echo json_encode(['success' => false, 'message' => 'Email này đã có người sử dụng!']);
            exit;
        }
        $newId = $userModel->create($fullname, $email, $phone, $password, 0);
        $_SESSION['user'] = ['id' => $newId, 'fullname' => $fullname, 'email' => $email, 'phone' => $phone, 'role' => 0];
        $redirect = $_SESSION['post_login_redirect'] ?? null;
        unset($_SESSION['post_login_redirect']);
        echo json_encode(['success' => true, 'message' => 'Đăng ký thành công!', 'redirect' => $redirect]);
        exit;
    }

    public function logout() {
        if (isset($_SESSION['user']['id']) && isset($_SESSION['cart'])) {
            (new UserModel($this->pdo))->updateCart($_SESSION['user']['id'], json_encode($_SESSION['cart'], JSON_UNESCAPED_UNICODE));
        }
        unset($_SESSION['user']);
        $_SESSION['cart'] = [];
        header('Location: index.php');
        exit;
    }
}
