<?php
require_once 'app/models/UserModel.php';
require_once 'app/services/GoogleAuthService.php';

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

        if ($user) {
            // Kiểm tra trạng thái hoạt động của tài khoản
            if (isset($user['status']) && (int)$user['status'] === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Tài khoản của bạn hiện đang bị tạm khóa hoặc ngưng hoạt động! Vui lòng liên hệ Quản trị tối cao (admin@vphone.vn).'
                ]);
                exit;
            }

            // Nếu tài khoản chỉ đăng ký qua Google và chưa đặt mật khẩu
            if (empty($user['password']) && !empty($user['google_id'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Tài khoản này đã được tạo qua Google. Vui lòng bấm nút "Đăng nhập bằng Google" bên dưới!'
                ]);
                exit;
            }

            if (!empty($user['password']) && password_verify($password, $user['password'])) {
                $userRole = (int)$user['role'];
                $perms = [];
                if (!empty($user['permissions'])) {
                    $decoded = json_decode($user['permissions'], true);
                    $perms = is_array($decoded) ? $decoded : [];
                }
                if ($userRole === 1) {
                    require_once __DIR__ . '/../../admin/includes/permissions.php';
                    $perms = array_keys(VPHONE_PERMISSIONS);
                }

                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => (int)$user['id'],
                    'fullname' => $user['fullname'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'address' => $user['address'],
                    'avatar' => $user['avatar'] ?? '',
                    'role' => $userRole,
                    'staff_role' => $user['staff_role'] ?? ($userRole === 1 ? 'admin' : ($userRole === 2 ? 'staff' : '')),
                    'staff_title' => $user['staff_title'] ?? ($userRole === 1 ? 'Quản trị viên' : ($userRole === 2 ? 'Nhân viên cửa hàng' : 'Khách hàng')),
                    'permissions' => $perms,
                    'auth_provider' => $user['auth_provider'] ?? 'local'
                ];
                if (!empty($user['cart_data'])) { $_SESSION['cart'] = json_decode($user['cart_data'], true) ?: []; }
                $redirect = $_SESSION['post_login_redirect'] ?? null;
                unset($_SESSION['post_login_redirect']);

                if (!$redirect) {
                    if ($userRole === 1) {
                        $redirect = 'admin/index.php';
                    } elseif ($userRole === 2) {
                        $redirect = 'admin/staff_dashboard.php';
                    }
                }

                echo json_encode([
                    'success' => true,
                    'is_admin' => ($userRole === 1),
                    'is_staff' => ($userRole === 2),
                    'role' => $userRole,
                    'message' => 'Đăng nhập thành công!',
                    'redirect' => $redirect
                ]);
                exit;
            }
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
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $newId,
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'address' => 'Chưa cập nhật',
            'avatar' => '',
            'role' => 0,
            'auth_provider' => 'local'
        ];
        $redirect = $_SESSION['post_login_redirect'] ?? null;
        unset($_SESSION['post_login_redirect']);
        echo json_encode(['success' => true, 'message' => 'Đăng ký thành công!', 'redirect' => $redirect]);
        exit;
    }

    /**
     * Xử lý Đăng nhập & Đăng ký bằng Google (Cả GIS Token và Demo Simulator)
     */
    public function googleAuth() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $googleService = new GoogleAuthService();
        $userModel = new UserModel($this->pdo);
        $userModel->ensureGoogleColumns();

        $googleUser = null;

        // Nhận ID Token từ Google Identity Services Client
        $idToken = trim($_POST['credential'] ?? ($_POST['id_token'] ?? ''));
        if (!empty($idToken)) {
            $googleUser = $googleService->verifyIdToken($idToken);
            if (!$googleUser) {
                echo json_encode(['success' => false, 'message' => 'Xác thực Google ID Token không hợp lệ hoặc đã hết hạn!']);
                exit;
            }
        }

        if (!$googleUser || empty($googleUser['email'])) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin xác thực Google hợp lệ!']);
            exit;
        }

        $existingByGoogle = !empty($googleUser['google_id']) ? $userModel->findByGoogleId($googleUser['google_id']) : null;
        $existingByEmail = $userModel->findByEmail($googleUser['email']);

        $user = null;
        $isNewUser = false;

        if ($existingByGoogle) {
            $user = $existingByGoogle;
            if (!empty($googleUser['avatar'])) {
                $savedAvatar = $userModel->saveRemoteAvatar($user['id'], $googleUser['avatar']);
                $user['avatar'] = $savedAvatar;
            }
        } elseif ($existingByEmail) {
            $user = $existingByEmail;
            $userModel->linkGoogleAccount($user['id'], $googleUser['google_id'] ?? '', $googleUser['avatar'] ?? null);
            if (!empty($googleUser['avatar'])) {
                $savedAvatar = $userModel->saveRemoteAvatar($user['id'], $googleUser['avatar']);
                $user['avatar'] = $savedAvatar;
            }
        } else {
            // Đăng ký tài khoản mới bằng Google
            $newId = $userModel->createFromGoogle(
                $googleUser['fullname'] ?: 'Khách hàng Google',
                $googleUser['email'],
                $googleUser['google_id'] ?? ('google_' . time()),
                $googleUser['avatar'] ?? null,
                $googleUser['phone'] ?? ''
            );
            if (!empty($googleUser['avatar'])) {
                $userModel->saveRemoteAvatar($newId, $googleUser['avatar']);
            }
            $user = $userModel->findById($newId);
            $isNewUser = true;
        }

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Lỗi tạo hoặc liên kết tài khoản người dùng!']);
            exit;
        }

        $userRole = (int)($user['role'] ?? 0);
        $perms = [];
        if (!empty($user['permissions'])) {
            $decoded = json_decode($user['permissions'], true);
            $perms = is_array($decoded) ? $decoded : [];
        }
        if ($userRole === 1) {
            require_once __DIR__ . '/../../admin/includes/permissions.php';
            $perms = array_keys(VPHONE_PERMISSIONS);
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'fullname' => $user['fullname'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? '',
            'address' => $user['address'] ?? 'Chưa cập nhật',
            'avatar' => $user['avatar'] ?? '',
            'role' => $userRole,
            'staff_role' => $user['staff_role'] ?? ($userRole === 1 ? 'admin' : ($userRole === 2 ? 'staff' : '')),
            'staff_title' => $user['staff_title'] ?? ($userRole === 1 ? 'Quản trị viên' : ($userRole === 2 ? 'Nhân viên cửa hàng' : 'Khách hàng')),
            'permissions' => $perms,
            'auth_provider' => $user['auth_provider'] ?? 'google'
        ];

        if (!empty($user['cart_data'])) {
            $_SESSION['cart'] = json_decode($user['cart_data'], true) ?: [];
        }

        $redirect = $_SESSION['post_login_redirect'] ?? null;
        unset($_SESSION['post_login_redirect']);

        $welcomeMsg = $isNewUser 
            ? 'Đăng ký tài khoản bằng Google thành công! Chào mừng ' . htmlspecialchars($user['fullname']) . ' đến với V-Phone.'
            : 'Đăng nhập Google thành công! Xin chào ' . htmlspecialchars($user['fullname']) . '.';

        echo json_encode([
            'success' => true,
            'is_admin' => ($user['role'] == 1),
            'is_new' => $isNewUser,
            'message' => $welcomeMsg,
            'redirect' => $redirect,
            'user' => [
                'fullname' => $user['fullname'],
                'email' => $user['email'],
                'avatar' => $user['avatar']
            ]
        ]);
        exit;
    }

    /**
     * Chuyển hướng người dùng sang trang cấp quyền Google OAuth 2.0
     */
    public function googleRedirect() {
        $googleService = new GoogleAuthService();
        if (!$googleService->isConfigured()) {
            header('Location: index.php?show_login=1&auth_error=' . urlencode('Chức năng Google OAuth chưa được cấu hình.'));
            exit;
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;
        if (!empty($_GET['redirect'])) {
            $_SESSION['post_login_redirect'] = $_GET['redirect'];
        }

        $authUrl = $googleService->getAuthUrl($state);
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Nhận callback từ Google OAuth 2.0 Server
     */
    public function googleCallback() {
        if (!empty($_GET['error'])) {
            $err = urlencode('Đăng nhập Google bị hủy hoặc gặp sự cố: ' . $_GET['error']);
            header('Location: index.php?show_login=1&auth_error=' . $err);
            exit;
        }

        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $savedState = $_SESSION['google_oauth_state'] ?? '';
        unset($_SESSION['google_oauth_state']);

        if (empty($code)) {
            $err = urlencode('Không nhận được mã xác thực từ Google!');
            header('Location: index.php?show_login=1&auth_error=' . $err);
            exit;
        }

        $googleService = new GoogleAuthService();
        $tokens = $googleService->exchangeCodeForTokens($code);

        if (!$tokens || empty($tokens['access_token'])) {
            $lastErr = $googleService->getLastError();
            $err = urlencode('Lỗi kết nối Google' . ($lastErr ? ': ' . $lastErr : ' (Không lấy được Access Token)'));
            header('Location: index.php?show_login=1&auth_error=' . $err);
            exit;
        }

        $googleUser = $googleService->getUserInfoFromAccessToken($tokens['access_token']);
        if (!$googleUser || empty($googleUser['email'])) {
            $err = urlencode('Không thể lấy hồ sơ người dùng từ Google!');
            header('Location: index.php?show_login=1&auth_error=' . $err);
            exit;
        }

        $userModel = new UserModel($this->pdo);
        $userModel->ensureGoogleColumns();

        $existingByGoogle = !empty($googleUser['google_id']) ? $userModel->findByGoogleId($googleUser['google_id']) : null;
        $existingByEmail = $userModel->findByEmail($googleUser['email']);

        $user = null;
        if ($existingByGoogle) {
            $user = $existingByGoogle;
            if (!empty($googleUser['avatar'])) {
                $savedAvatar = $userModel->saveRemoteAvatar($user['id'], $googleUser['avatar']);
                $user['avatar'] = $savedAvatar;
            }
        } elseif ($existingByEmail) {
            $user = $existingByEmail;
            $userModel->linkGoogleAccount($user['id'], $googleUser['google_id'] ?? '', $googleUser['avatar'] ?? null);
            if (!empty($googleUser['avatar'])) {
                $savedAvatar = $userModel->saveRemoteAvatar($user['id'], $googleUser['avatar']);
                $user['avatar'] = $savedAvatar;
            }
        } else {
            $newId = $userModel->createFromGoogle(
                $googleUser['fullname'] ?: 'Khách hàng Google',
                $googleUser['email'],
                $googleUser['google_id'] ?? ('google_' . time()),
                $googleUser['avatar'] ?? null
            );
            if (!empty($googleUser['avatar'])) {
                $userModel->saveRemoteAvatar($newId, $googleUser['avatar']);
            }
            $user = $userModel->findById($newId);
        }

        $userRole = (int)($user['role'] ?? 0);
        $perms = [];
        if (!empty($user['permissions'])) {
            $decoded = json_decode($user['permissions'], true);
            $perms = is_array($decoded) ? $decoded : [];
        }
        if ($userRole === 1) {
            require_once __DIR__ . '/../../admin/includes/permissions.php';
            $perms = array_keys(VPHONE_PERMISSIONS);
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'fullname' => $user['fullname'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? '',
            'address' => $user['address'] ?? 'Chưa cập nhật',
            'avatar' => $user['avatar'] ?? '',
            'role' => $userRole,
            'staff_role' => $user['staff_role'] ?? ($userRole === 1 ? 'admin' : ($userRole === 2 ? 'staff' : '')),
            'staff_title' => $user['staff_title'] ?? ($userRole === 1 ? 'Quản trị viên' : ($userRole === 2 ? 'Nhân viên cửa hàng' : 'Khách hàng')),
            'permissions' => $perms,
            'auth_provider' => $user['auth_provider'] ?? 'google'
        ];

        if (!empty($user['cart_data'])) {
            $_SESSION['cart'] = json_decode($user['cart_data'], true) ?: [];
        }

        $redirect = $_SESSION['post_login_redirect'] ?? 'index.php';
        unset($_SESSION['post_login_redirect']);

        if ($user['role'] == 1) {
            header('Location: admin/index.php');
        } elseif ($user['role'] == 2) {
            header('Location: admin/staff_dashboard.php');
        } else {
            header('Location: ' . $redirect);
        }
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

    /**
     * Gửi yêu cầu quên mật khẩu (Sinh mã OTP & Token)
     */
    public function forgotPassword() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $email = strtolower(trim($_POST['email'] ?? ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng nhập địa chỉ email hợp lệ!'
            ]);
            exit;
        }

        $userModel = new UserModel($this->pdo);
        $user = $userModel->findByEmail($email);

        if (!$user) {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy tài khoản nào liên kết với email này trên hệ thống!'
            ]);
            exit;
        }

        // Kiểm tra tài khoản có bị khóa không
        if (isset($user['status']) && (int)$user['status'] === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Tài khoản của bạn đang bị tạm khóa. Vui lòng liên hệ quản trị viên!'
            ]);
            exit;
        }

        // Nếu tài khoản Google chưa từng tạo mật khẩu
        if (empty($user['password']) && !empty($user['google_id'])) {
            echo json_encode([
                'success' => false,
                'is_google' => true,
                'message' => 'Tài khoản này được đăng ký thông qua Google. Bạn có thể bấm nút "Đăng nhập với Google" để vào ngay mà không cần mật khẩu!'
            ]);
            exit;
        }

        // Sinh mã OTP và Token
        $resetData = $userModel->createPasswordReset($email);
        $otp = $resetData['otp'];
        $token = $resetData['token'];

        // Tạo link đặt lại mật khẩu trực tiếp
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/vphone-store/index.php'), '/.\\');
        $scriptDir = ($scriptDir === '' || $scriptDir === '.') ? '' : $scriptDir;
        $resetUrl = $protocol . '://' . $host . $scriptDir . '/index.php?page=reset-password&token=' . urlencode($token);

        require_once __DIR__ . '/../services/MailService.php';
        $mailSent = MailService::sendPasswordResetEmail($email, $user['fullname'] ?: 'Quý khách', $otp, $resetUrl);

        // Lưu email vào session để dùng cho bước tiếp theo
        $_SESSION['reset_password_email'] = $email;

        echo json_encode([
            'success' => true,
            'message' => 'Mã xác nhận OTP đã được gửi đến email ' . htmlspecialchars($email) . '!',
            'email' => $email,
            'otp' => $otp, // Trả về OTP để test nhanh & tiện lợi trên Localhost
            'token' => $token,
            'reset_url' => $resetUrl,
            'mail_sent' => $mailSent
        ]);
        exit;
    }

    /**
     * Xác thực mã OTP đặt lại mật khẩu
     */
    public function verifyResetOtp() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $email = strtolower(trim($_POST['email'] ?? ($_SESSION['reset_password_email'] ?? '')));
        $otp = trim($_POST['otp'] ?? '');

        if (empty($email) || empty($otp)) {
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng nhập đầy đủ email và mã OTP 6 chữ số!'
            ]);
            exit;
        }

        $userModel = new UserModel($this->pdo);
        $resetRecord = $userModel->verifyPasswordResetOtp($email, $otp);

        if (!$resetRecord) {
            echo json_encode([
                'success' => false,
                'message' => 'Mã OTP không chính xác hoặc đã hết hạn (hiệu lực 15 phút)! Vui lòng kiểm tra lại.'
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Xác thực OTP thành công! Vui lòng nhập mật khẩu mới.',
            'token' => $resetRecord['token'],
            'email' => $email
        ]);
        exit;
    }

    /**
     * Thực hiện đổi mật khẩu mới sau khi xác thực OTP hoặc Token
     */
    public function resetPassword() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $email = strtolower(trim($_POST['email'] ?? ($_SESSION['reset_password_email'] ?? '')));
        $otp = trim($_POST['otp'] ?? '');
        $token = trim($_POST['token'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if (empty($newPassword) || mb_strlen($newPassword, 'UTF-8') < 6) {
            echo json_encode([
                'success' => false,
                'message' => 'Mật khẩu mới phải có độ dài từ 6 ký tự trở lên!'
            ]);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode([
                'success' => false,
                'message' => 'Xác nhận mật khẩu không khớp. Vui lòng nhập lại chính xác!'
            ]);
            exit;
        }

        $userModel = new UserModel($this->pdo);

        // Kiểm tra qua Token hoặc qua OTP
        $valid = null;
        if (!empty($token)) {
            $valid = $userModel->verifyPasswordResetToken($token);
            if ($valid) $email = $valid['email'];
        } elseif (!empty($email) && !empty($otp)) {
            $valid = $userModel->verifyPasswordResetOtp($email, $otp);
        }

        if (!$valid) {
            echo json_encode([
                'success' => false,
                'message' => 'Yêu cầu đặt lại mật khẩu không hợp lệ hoặc đã hết hạn! Vui lòng thử lại từ đầu.'
            ]);
            exit;
        }

        $done = $userModel->completePasswordReset($email, $newPassword);
        if (!$done) {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể cập nhật mật khẩu. Vui lòng thử lại!'
            ]);
            exit;
        }

        unset($_SESSION['reset_password_email']);

        echo json_encode([
            'success' => true,
            'message' => 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập ngay với mật khẩu mới.',
            'email' => $email
        ]);
        exit;
    }
}
