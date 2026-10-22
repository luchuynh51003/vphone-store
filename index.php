<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

$page = $_GET['page'] ?? ($_GET['controller'] ?? 'home');
$action = $_GET['action'] ?? 'index';

switch ($page) {
    case 'home':
        require_once 'app/controllers/HomeController.php';
        (new HomeController($pdo))->index();
        break;

    case 'detail':
    case 'product':
    case 'san-pham':
        require_once 'app/controllers/ProductController.php';
        (new ProductController($pdo))->detail();
        break;

    case 'compare':
    case 'so-sanh':
        require_once 'app/controllers/ProductController.php';
        (new ProductController($pdo))->compare();
        break;

    case 'used-phones':
    case 'used_phones':
    case 'used':
    case 'may-cu':
        require_once 'app/controllers/ProductController.php';
        (new ProductController($pdo))->used();
        break;

    case 'news':
    case 'tin-tuc':
        require_once 'app/controllers/ProductController.php';
        (new ProductController($pdo))->news();
        break;

    case 'about':
    case 'about-us':
    case 'gioi-thieu':
        require_once 'app/controllers/HomeController.php';
        (new HomeController($pdo))->about();
        break;

    case 'stores':
    case 'store':
    case 'cua-hang':
    case 'he-thong-cua-hang':
        require_once 'app/controllers/HomeController.php';
        (new HomeController($pdo))->stores();
        break;

    case 'warranty':
    case 'chinh-sach-bao-hanh':
    case 'bao-hanh':
        require_once 'app/controllers/HomeController.php';
        (new HomeController($pdo))->warranty();
        break;

    case 'warranty-claim':
        require_once 'app/controllers/HomeController.php';
        (new HomeController($pdo))->submitClaim();
        break;

    case 'review':
    case 'reviews':
        require_once 'app/controllers/ReviewController.php';
        (new ReviewController($pdo))->handle();
        break;

    case 'cart':
        require_once 'app/controllers/CartController.php';
        (new CartController($pdo))->index();
        break;

    case 'checkout':
        require_once 'app/controllers/CartController.php';
        (new CartController($pdo))->checkout();
        break;

    case 'vietqr-webhook':
    case 'payment-webhook':
        require_once 'api/vietqr-webhook.php';
        exit;

    case 'momo-ipn':
        require_once 'api/momo-ipn.php';
        exit;

    case 'orders':
    case 'order':
    case 'tracking':
    case 'tra-cuu':
    case 'don-hang':
        require_once 'app/controllers/OrderController.php';
        (new OrderController($pdo))->index();
        break;

    case 'profile':
    case 'account':
        require_once 'app/controllers/ProfileController.php';
        (new ProfileController($pdo))->index();
        break;

    case 'auth':
        require_once 'app/controllers/AuthController.php';
        $auth = new AuthController($pdo);
        if ($action === 'google_redirect') {
            $auth->googleRedirect();
        } elseif ($action === 'google_callback') {
            $auth->googleCallback();
        } elseif ($action === 'google') {
            $auth->googleAuth();
        } elseif ($action === 'forgot_password') {
            $auth->forgotPassword();
        } elseif ($action === 'verify_otp') {
            $auth->verifyResetOtp();
        } elseif ($action === 'reset_password') {
            $auth->resetPassword();
        } elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $authRedirect = ($_GET['redirect'] ?? '') === 'checkout' ? '&redirect=checkout' : '';
            header('Location: index.php?show_login=1' . $authRedirect);
            exit;
        } elseif ($action === 'register') {
            $auth->register();
        } elseif ($action === 'logout') {
            $auth->logout();
        } else {
            $auth->login();
        }
        break;

    case 'forgot-password':
    case 'quen-mat-khau':
    case 'reset-password':
        $pageTitle = 'Quên & Đặt Lại Mật Khẩu - V-Phone Store';
        require_once 'app/views/forgot_password.php';
        break;

    case 'google-login':
        require_once 'app/controllers/AuthController.php';
        (new AuthController($pdo))->googleRedirect();
        break;

    case 'google-callback':
        require_once 'app/controllers/AuthController.php';
        (new AuthController($pdo))->googleCallback();
        break;

    case 'google-auth':
        require_once 'app/controllers/AuthController.php';
        (new AuthController($pdo))->googleAuth();
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
