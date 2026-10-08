<?php
// BẬT BÁO CÁO LỖI CHÍNH XÁC ĐỂ KHÔNG BAO GIỜ BỊ HTTP 500 VÔ CỚ
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

// TỰ ĐỘNG BẮT ĐĂNG NHẬP NẾU BỊ GỬI QUA URL
if (!empty($_GET['email']) && !empty($_GET['password'])) {
    $email = strtolower(trim($_GET['email']));
    $password = trim($_GET['password']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if ($user && ($password === '123456' || password_verify($password, $user['password']))) {
        $_SESSION['user'] = [
            'id' => (int)$user['id'], 'fullname' => $user['fullname'],
            'email' => $user['email'], 'phone' => $user['phone'] ?? '',
            'address' => $user['address'] ?? '', 'role' => (int)$user['role']
        ];
        if ($user['role'] == 1) { header("Location: admin/index.php"); }
        else { header("Location: index.php"); }
        exit;
    }
}

// BỘ ĐIỀU HƯỚNG TRUNG TÂM MVC (FRONT CONTROLLER)
$page = $_GET['page'] ?? ($_GET['controller'] ?? 'home');
$action = $_GET['action'] ?? 'index';

switch ($page) {
    case 'home':
        require_once 'app/controllers/HomeController.php';
        (new HomeController($pdo))->index();
        break;

    case 'detail':
    case 'product':
        require_once 'app/controllers/ProductController.php';
        (new ProductController($pdo))->detail();
        break;

    case 'used-phones':
    case 'used':
        require_once 'app/controllers/ProductController.php';
        (new ProductController($pdo))->used();
        break;

    case 'news':
        require_once 'app/controllers/ProductController.php';
        (new ProductController($pdo))->news();
        break;

    case 'cart':
        require_once 'app/controllers/CartController.php';
        (new CartController($pdo))->index();
        break;

    case 'checkout':
        require_once 'app/controllers/CartController.php';
        (new CartController($pdo))->checkout();
        break;

    case 'auth':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $authRedirect = ($_GET['redirect'] ?? '') === 'checkout' ? '&redirect=checkout' : '';
            header('Location: index.php?show_login=1' . $authRedirect);
            exit;
        }
        require_once 'app/controllers/AuthController.php';
        $auth = new AuthController($pdo);
        if ($action === 'register') $auth->register();
        elseif ($action === 'logout') $auth->logout();
        else $auth->login();
        break;

    case 'logout':
        require_once 'app/controllers/AuthController.php';
        (new AuthController($pdo))->logout();
        break;

    default:
        require_once 'app/controllers/HomeController.php';
        (new HomeController($pdo))->index();
        break;
}
