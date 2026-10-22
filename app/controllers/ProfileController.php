<?php
require_once 'app/models/UserModel.php';
require_once 'app/models/OrderModel.php';
require_once 'app/models/AddressModel.php';
require_once 'app/services/MembershipService.php';

class ProfileController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
            $_SESSION['post_login_redirect'] = 'index.php?page=profile';
            header('Location: index.php?show_login=1&redirect=profile');
            exit;
        }

        $userId = (int)$_SESSION['user']['id'];
        $userModel = new UserModel($this->pdo);
        $orderModel = new OrderModel($this->pdo);
        $addressModel = new AddressModel($this->pdo);
        $userModel->ensureAvatarColumn();

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_GET['ajax']) || isset($_POST['ajax']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $action = $_POST['action'] ?? 'update_profile';

            // Xử lý tải ảnh đại diện nhanh qua AJAX hoặc riêng biệt
            if ($action === 'upload_avatar') {
                if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                    $this->respond([
                        'success' => false,
                        'message' => 'Vui lòng chọn tệp hình ảnh hợp lệ!'
                    ], $isAjax, 'index.php?page=profile');
                    return;
                }

                $avatarRes = $this->handleAvatarUpload($_FILES['avatar'], $userId, $userModel);
                if (!$avatarRes['success']) {
                    $this->respond($avatarRes, $isAjax, 'index.php?page=profile');
                    return;
                }

                $avatarPath = $avatarRes['path'];
                $userModel->updateAvatar($userId, $avatarPath);
                $_SESSION['user']['avatar'] = $avatarPath;

                $this->respond([
                    'success' => true,
                    'message' => 'Cập nhật ảnh đại diện thành công!',
                    'avatar' => $avatarPath
                ], $isAjax, 'index.php?page=profile');
                return;
            }

            // Xóa ảnh đại diện
            if ($action === 'remove_avatar') {
                $userModel->cleanOldAvatars($userId);
                $userModel->updateAvatar($userId, null);
                $_SESSION['user']['avatar'] = '';

                $this->respond([
                    'success' => true,
                    'message' => 'Đã gỡ ảnh đại diện, trở về chữ cái đại diện mặc định!',
                    'avatar' => ''
                ], $isAjax, 'index.php?page=profile');
                return;
            }

            // Cập nhật thông tin cá nhân cơ bản
            if ($action === 'update_profile') {
                $fullname = trim($_POST['fullname'] ?? '');
                $email = strtolower(trim($_POST['email'] ?? ''));
                $phone = trim($_POST['phone'] ?? '');
                $address = trim($_POST['address'] ?? '');

                if (empty($fullname) || mb_strlen($fullname, 'UTF-8') < 2) {
                    $this->respond([
                        'success' => false,
                        'message' => 'Họ và tên không được để trống và phải từ 2 ký tự trở lên!'
                    ], $isAjax, 'index.php?page=profile');
                    return;
                }

                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->respond([
                        'success' => false,
                        'message' => 'Địa chỉ email không đúng định dạng hợp lệ!'
                    ], $isAjax, 'index.php?page=profile');
                    return;
                }

                if ($userModel->isEmailTaken($email, $userId)) {
                    $this->respond([
                        'success' => false,
                        'message' => 'Email này đã có tài khoản khác sử dụng. Vui lòng chọn email khác!'
                    ], $isAjax, 'index.php?page=profile');
                    return;
                }

                if (!empty($phone) && !preg_match('/^[0-9]{9,11}$/', preg_replace('/[^0-9]/', '', $phone))) {
                    $this->respond([
                        'success' => false,
                        'message' => 'Số điện thoại không hợp lệ (cần từ 9 - 11 chữ số)!'
                    ], $isAjax, 'index.php?page=profile');
                    return;
                }

                $avatarPath = null;
                // Kiểm tra có tải kèm ảnh mới không
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $avatarRes = $this->handleAvatarUpload($_FILES['avatar'], $userId, $userModel);
                    if (!$avatarRes['success']) {
                        $this->respond($avatarRes, $isAjax, 'index.php?page=profile');
                        return;
                    }
                    $avatarPath = $avatarRes['path'];
                } elseif (!empty($_POST['remove_avatar']) && $_POST['remove_avatar'] == '1') {
                    $curr = $userModel->findById($userId);
                    if (!empty($curr['avatar']) && str_starts_with($curr['avatar'], 'assets/images/users/') && file_exists($curr['avatar'])) {
                        @unlink($curr['avatar']);
                    }
                    $avatarPath = '';
                }

                $updated = $userModel->updateProfile($userId, $fullname, $phone, $address, $email, $avatarPath);

                if ($updated) {
                    $_SESSION['user']['fullname'] = $fullname;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['user']['phone'] = $phone;
                    $_SESSION['user']['address'] = $address;
                    if ($avatarPath !== null) {
                        $_SESSION['user']['avatar'] = $avatarPath;
                    }

                    // Đồng bộ sang địa chỉ mặc định nếu có
                    $defAddr = $addressModel->getDefaultByUserId($userId);
                    if ($defAddr && (!empty($phone) || !empty($address))) {
                        $addressModel->update((int)$defAddr['id'], $userId, $fullname, $phone ?: $defAddr['phone'], $address ?: $defAddr['address'], $defAddr['label'] ?? 'Nhà riêng', true);
                    }

                    $_SESSION['profile_message'] = 'Cập nhật thông tin tài khoản thành công!';
                    $this->respond([
                        'success' => true,
                        'message' => 'Cập nhật thông tin tài khoản thành công!',
                        'user' => [
                            'fullname' => $fullname,
                            'email' => $email,
                            'phone' => $phone,
                            'address' => $address,
                            'avatar' => $_SESSION['user']['avatar'] ?? ''
                        ]
                    ], $isAjax, 'index.php?page=profile');
                    return;
                } else {
                    $this->respond([
                        'success' => false,
                        'message' => 'Có lỗi xảy ra khi lưu thông tin. Vui lòng thử lại!'
                    ], $isAjax, 'index.php?page=profile');
                    return;
                }
            }

            // Thêm địa chỉ & sđt mới
            if ($action === 'add_address') {
                $recipientName = trim($_POST['recipient_name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $label = trim($_POST['label'] ?? 'Nhà riêng');
                $isDefault = !empty($_POST['is_default']);

                if (empty($recipientName)) {
                    $recipientName = $_SESSION['user']['fullname'] ?? 'Khách Hàng';
                }

                if (empty($phone) || !preg_match('/^[0-9]{9,11}$/', preg_replace('/[^0-9]/', '', $phone))) {
                    $this->respond([
                        'success' => false,
                        'message' => 'Số điện thoại nhận hàng không hợp lệ (cần từ 9 - 11 chữ số)!'
                    ], $isAjax, 'index.php?page=profile&tab=addresses');
                    return;
                }

                if (empty($address) || mb_strlen($address, 'UTF-8') < 5) {
                    $this->respond([
                        'success' => false,
                        'message' => 'Địa chỉ nhận hàng cần ghi rõ ràng (tối thiểu 5 ký tự)!'
                    ], $isAjax, 'index.php?page=profile&tab=addresses');
                    return;
                }

                $newId = $addressModel->add($userId, $recipientName, $phone, $address, $label, $isDefault);

                $this->respond([
                    'success' => true,
                    'message' => 'Đã thêm địa chỉ & số điện thoại nhận hàng mới thành công!',
                    'address_id' => $newId
                ], $isAjax, 'index.php?page=profile&tab=addresses');
                return;
            }

            // Cập nhật địa chỉ nhận hàng
            if ($action === 'update_address') {
                $addressId = (int)($_POST['address_id'] ?? 0);
                $recipientName = trim($_POST['recipient_name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $label = trim($_POST['label'] ?? 'Nhà riêng');
                $isDefault = !empty($_POST['is_default']);

                if (empty($recipientName)) {
                    $recipientName = $_SESSION['user']['fullname'] ?? 'Khách Hàng';
                }

                if (empty($phone) || !preg_match('/^[0-9]{9,11}$/', preg_replace('/[^0-9]/', '', $phone))) {
                    $this->respond([
                        'success' => false,
                        'message' => 'Số điện thoại nhận hàng không hợp lệ (cần từ 9 - 11 chữ số)!'
                    ], $isAjax, 'index.php?page=profile&tab=addresses');
                    return;
                }

                if (empty($address) || mb_strlen($address, 'UTF-8') < 5) {
                    $this->respond([
                        'success' => false,
                        'message' => 'Địa chỉ nhận hàng không được để trống!'
                    ], $isAjax, 'index.php?page=profile&tab=addresses');
                    return;
                }

                $ok = $addressModel->update($addressId, $userId, $recipientName, $phone, $address, $label, $isDefault);

                $this->respond([
                    'success' => (bool)$ok,
                    'message' => $ok ? 'Cập nhật địa chỉ nhận hàng thành công!' : 'Không tìm thấy địa chỉ để cập nhật!'
                ], $isAjax, 'index.php?page=profile&tab=addresses');
                return;
            }

            // Chọn 1 địa chỉ làm mặc định
            if ($action === 'set_default_address') {
                $addressId = (int)($_POST['address_id'] ?? 0);
                $ok = $addressModel->setDefault($addressId, $userId);

                $this->respond([
                    'success' => $ok,
                    'message' => $ok ? 'Đã thiết lập địa chỉ này làm mặc định thành công!' : 'Không thể chọn địa chỉ này làm mặc định!'
                ], $isAjax, 'index.php?page=profile&tab=addresses');
                return;
            }

            // Xóa địa chỉ
            if ($action === 'delete_address') {
                $addressId = (int)($_POST['address_id'] ?? 0);
                $res = $addressModel->delete($addressId, $userId);

                $this->respond($res, $isAjax, 'index.php?page=profile&tab=addresses');
                return;
            }

            // Đổi mật khẩu
            if ($action === 'change_password') {
                $currentPassword = trim($_POST['current_password'] ?? '');
                $newPassword = trim($_POST['new_password'] ?? '');
                $confirmPassword = trim($_POST['confirm_password'] ?? '');

                if (empty($currentPassword)) {
                    $this->respond([
                        'success' => false,
                        'message' => 'Vui lòng nhập mật khẩu hiện tại!'
                    ], $isAjax, 'index.php?page=profile&tab=security');
                    return;
                }

                if (!$userModel->verifyPassword($userId, $currentPassword)) {
                    $this->respond([
                        'success' => false,
                        'message' => 'Mật khẩu hiện tại không chính xác!'
                    ], $isAjax, 'index.php?page=profile&tab=security');
                    return;
                }

                if (mb_strlen($newPassword, 'UTF-8') < 6) {
                    $this->respond([
                        'success' => false,
                        'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự!'
                    ], $isAjax, 'index.php?page=profile&tab=security');
                    return;
                }

                if ($newPassword !== $confirmPassword) {
                    $this->respond([
                        'success' => false,
                        'message' => 'Xác nhận mật khẩu mới không trùng khớp!'
                    ], $isAjax, 'index.php?page=profile&tab=security');
                    return;
                }

                $updated = $userModel->updatePassword($userId, $newPassword);
                if ($updated) {
                    $_SESSION['profile_message'] = 'Đổi mật khẩu tài khoản thành công!';
                    $this->respond([
                        'success' => true,
                        'message' => 'Đổi mật khẩu tài khoản thành công!'
                    ], $isAjax, 'index.php?page=profile&tab=security');
                    return;
                } else {
                    $this->respond([
                        'success' => false,
                        'message' => 'Không thể đổi mật khẩu vào lúc này. Vui lòng thử lại!'
                    ], $isAjax, 'index.php?page=profile&tab=security');
                    return;
                }
            }
        }

        $user = $userModel->findById($userId);
        if (!$user) {
            unset($_SESSION['user']);
            header('Location: index.php?show_login=1');
            exit;
        }

        // Cập nhật lại session với avatar mới nhất từ database
        $_SESSION['user']['avatar'] = $user['avatar'] ?? '';

        $userAddresses = $addressModel->getByUserId($userId);
        $userOrders = $orderModel->getByUserId($userId);
        $statusCounts = $orderModel->getStatusCountsByUserId($userId);
        $totalOrders = count($userOrders);

        $membershipService = new MembershipService($this->pdo);
        $totalSpent = $membershipService->getUserSpent($userId);
        $userTier = $membershipService->getUserTier($userId);
        $tierDefinitions = MembershipService::getTierDefinitions();

        $profileMessage = $_SESSION['profile_message'] ?? '';
        $profileError = $_SESSION['profile_error'] ?? '';
        unset($_SESSION['profile_message'], $_SESSION['profile_error']);

        $pageTitle = 'Sổ Địa Chỉ & Thông Tin Tài Khoản - V-Phone Store';
        require_once 'app/views/profile.php';
    }

    private function handleAvatarUpload(array $file, int $userId, UserModel $userModel): array {
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            return [
                'success' => false,
                'message' => 'Ảnh đại diện phải thuộc định dạng: JPG, JPEG, PNG, WEBP hoặc GIF!'
            ];
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'Dung lượng ảnh đại diện không được vượt quá 5MB!'
            ];
        }

        $uploadDir = 'assets/images/users/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $newFilename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $newFilename;

        if (move_uploaded_file($file['tmp_name'], $targetPath) || (PHP_SAPI === 'cli' && @copy($file['tmp_name'], $targetPath))) {
            // Xóa sạch tất cả ảnh cũ/thừa của user này (chỉ giữ lại file vừa tải lên)
            $userModel->cleanOldAvatars($userId, $targetPath);
            return [
                'success' => true,
                'path' => $targetPath
            ];
        }

        return [
            'success' => false,
            'message' => 'Không thể tải ảnh lên máy chủ. Vui lòng thử lại!'
        ];
    }

    private function respond(array $data, bool $isAjax, string $redirectUrl): void {
        if ($isAjax) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($data['success']) {
            $_SESSION['profile_message'] = $data['message'];
        } else {
            $_SESSION['profile_error'] = $data['message'];
        }
        header('Location: ' . $redirectUrl);
        exit;
    }
}
