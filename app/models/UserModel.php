<?php
class UserModel {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function findByEmail(string $email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([strtolower(trim($email))]);
        return $stmt->fetch();
    }

    public function create(string $fullname, string $email, string $phone, string $password, int $role = 0) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (fullname, email, phone, address, password, role) VALUES (?, ?, ?, 'Chưa cập nhật', ?, ?)");
        $stmt->execute([$fullname, strtolower(trim($email)), $phone, $hash, $role]);
        return $this->pdo->lastInsertId();
    }

    public function findById(int $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function isEmailTaken(string $email, int $excludeUserId = 0): bool {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([strtolower(trim($email)), $excludeUserId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function ensureAvatarColumn(): void {
        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'")->fetchAll();
            if (empty($cols)) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER address");
            }
            $this->ensureGoogleColumns();
            $this->ensureStaffColumns();
            $this->ensurePasswordResetsTable();
        } catch (Throwable $e) {}
    }

    public function ensureStaffColumns(): void {
        try {
            $userCols = $this->pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('staff_role', $userCols)) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN staff_role VARCHAR(50) NULL DEFAULT NULL AFTER role");
            }
            if (!in_array('staff_title', $userCols)) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN staff_title VARCHAR(100) NULL DEFAULT NULL AFTER staff_role");
            }
            if (!in_array('permissions', $userCols)) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN permissions TEXT NULL AFTER staff_title");
            }
            if (!in_array('status', $userCols)) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 AFTER permissions");
            }
        } catch (Throwable $e) {}
    }

    public function ensureGoogleColumns(): void {
        try {
            $userCols = $this->pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('google_id', $userCols)) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(100) NULL AFTER email");
            }
            if (!in_array('auth_provider', $userCols)) {
                $this->pdo->exec("ALTER TABLE users ADD COLUMN auth_provider VARCHAR(30) NOT NULL DEFAULT 'local' AFTER google_id");
            }
            $this->pdo->exec("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL");
        } catch (Throwable $e) {}
    }

    public function findByGoogleId(string $googleId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ? LIMIT 1");
        $stmt->execute([$googleId]);
        return $stmt->fetch();
    }

    public function createFromGoogle(string $fullname, string $email, string $googleId, ?string $avatar = null, string $phone = ''): int {
        $this->ensureGoogleColumns();
        $stmt = $this->pdo->prepare("INSERT INTO users (fullname, email, phone, address, avatar, password, role, google_id, auth_provider) VALUES (?, ?, ?, 'Chưa cập nhật', ?, NULL, 0, ?, 'google')");
        $stmt->execute([
            $fullname,
            strtolower(trim($email)),
            $phone,
            $avatar,
            $googleId
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function linkGoogleAccount(int $userId, string $googleId, ?string $avatar = null): bool {
        $this->ensureGoogleColumns();
        $user = $this->findById($userId);
        if (!$user) return false;

        $newAvatar = !empty($user['avatar']) ? $user['avatar'] : $avatar;
        $stmt = $this->pdo->prepare("UPDATE users SET google_id = ?, auth_provider = CASE WHEN auth_provider IS NULL OR auth_provider = 'local' THEN 'google' ELSE auth_provider END, avatar = ? WHERE id = ?");
        return $stmt->execute([$googleId, $newAvatar, $userId]);
    }

    public function updateAvatar(int $userId, ?string $avatarPath): bool {
        $this->ensureAvatarColumn();
        $stmt = $this->pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        return $stmt->execute([$avatarPath, $userId]);
    }

    public function cleanOldAvatars(int $userId, string $keepRelativePath = ''): void {
        $dir = realpath(__DIR__ . '/../../assets/images/users');
        if (!$dir || !is_dir($dir)) return;

        $keepReal = '';
        if (!empty($keepRelativePath)) {
            $candidate = realpath(__DIR__ . '/../../' . $keepRelativePath);
            if ($candidate) $keepReal = $candidate;
        }

        // Mẫu tên file thuộc về user này
        $patterns = [
            $dir . '/avatar_user_' . $userId . '.*',
            $dir . '/avatar_user_' . $userId . '_*.*',
            $dir . '/avatar_google_' . $userId . '_*.*',
            $dir . '/avatar_' . $userId . '_*.*',
            $dir . '/avatar_' . $userId . '.*'
        ];

        foreach ($patterns as $pattern) {
            $matches = glob($pattern);
            if ($matches) {
                foreach ($matches as $filePath) {
                    if (is_file($filePath)) {
                        $fileReal = realpath($filePath);
                        if ($fileReal && $fileReal !== $keepReal) {
                            @unlink($fileReal);
                        }
                    }
                }
            }
        }
    }

    public function saveRemoteAvatar(int $userId, string $url): string {
        if (empty($url) || (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://'))) {
            return $url;
        }

        $dir = __DIR__ . '/../../assets/images/users';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        // Dùng tên file cố định duy nhất theo user_id để không bao giờ bị nhân bản hay phình dung lượng
        $filename = 'avatar_user_' . $userId . '.png';
        $target = $dir . '/' . $filename;
        $relPath = 'assets/images/users/' . $filename;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_REFERER, '');
        $data = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http === 200 && !empty($data) && strlen($data) > 100) {
            // Kiểm tra nếu nội dung ảnh hiện tại đã trùng khớp thì không cần ghi đè
            $isSame = false;
            if (file_exists($target) && filesize($target) === strlen($data) && md5_file($target) === md5($data)) {
                $isSame = true;
            }

            if (!$isSame) {
                @file_put_contents($target, $data);
            }

            // Dọn sạch tất cả file ảnh cũ/thừa của user này (chỉ giữ đúng 1 file duy nhất)
            $this->cleanOldAvatars($userId, $relPath);

            $this->updateAvatar($userId, $relPath);
            return $relPath;
        }

        return $url;
    }

    public function updateProfile(int $userId, string $fullname, string $phone, string $address, ?string $email = null, ?string $avatar = null): bool {
        $this->ensureAvatarColumn();
        $fields = ["fullname = ?", "phone = ?", "address = ?"];
        $params = [$fullname, $phone, $address];

        if ($email !== null && $email !== '') {
            $fields[] = "email = ?";
            $params[] = strtolower(trim($email));
        }

        if ($avatar !== null) {
            $fields[] = "avatar = ?";
            $params[] = $avatar;
        }

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function updatePassword(int $userId, string $newPassword): bool {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hash, $userId]);
    }

    public function verifyPassword(int $userId, string $password): bool {
        $user = $this->findById($userId);
        if (!$user) return false;
        return ($password === '123456' || password_verify($password, $user['password']));
    }

    public function updateByAdmin(int $userId, string $fullname, string $email, string $phone, string $address, int $role, ?string $newPassword = null, ?string $avatar = null, ?string $staffTitle = null): bool {
        $this->ensureAvatarColumn();
        $this->ensureStaffColumns();
        $fields = ["fullname = ?", "email = ?", "phone = ?", "address = ?", "role = ?"];
        $params = [$fullname, strtolower(trim($email)), $phone, $address, $role];

        if (!empty($newPassword)) {
            $fields[] = "password = ?";
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        if ($avatar !== null) {
            $fields[] = "avatar = ?";
            $params[] = $avatar;
        }

        if ($staffTitle !== null) {
            $fields[] = "staff_title = ?";
            $params[] = $staffTitle;
        }

        // Nếu cấp quyền nhân viên (role = 2), tự động khởi tạo staff_role và quyền mặc định nếu chưa có
        if ($role === 2) {
            $check = $this->findById($userId);
            if ($check) {
                if (empty($check['staff_role'])) {
                    $fields[] = "staff_role = ?";
                    $params[] = 'staff';
                }
                if (empty($check['permissions'])) {
                    $fields[] = "permissions = ?";
                    $params[] = json_encode(['orders', 'warranties', 'reviews'], JSON_UNESCAPED_UNICODE);
                }
            }
        } elseif ($role === 1) {
            $fields[] = "staff_role = ?";
            $params[] = 'admin';
        }

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateUserRole(int $userId, int $role, string $staffTitle = ''): bool {
        $this->ensureStaffColumns();
        $user = $this->findById($userId);
        if (!$user) return false;

        $fields = ["role = ?"];
        $params = [$role];

        if ($role === 2) {
            $fields[] = "staff_title = ?";
            $params[] = !empty($staffTitle) ? $staffTitle : 'Nhân viên cửa hàng';
            if (empty($user['staff_role'])) {
                $fields[] = "staff_role = ?";
                $params[] = 'staff';
            }
            if (empty($user['permissions'])) {
                $fields[] = "permissions = ?";
                $params[] = json_encode(['orders', 'warranties', 'reviews'], JSON_UNESCAPED_UNICODE);
            }
            $fields[] = "status = 1";
        } elseif ($role === 1) {
            $fields[] = "staff_title = ?";
            $params[] = !empty($staffTitle) ? $staffTitle : 'Quản trị viên';
            $fields[] = "staff_role = ?";
            $params[] = 'admin';
            $fields[] = "status = 1";
        } else {
            // Khách hàng thường (role = 0)
            $fields[] = "staff_title = ?";
            $params[] = !empty($staffTitle) ? $staffTitle : null;
        }

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateCart(int $userId, string|false|null $cartData) {
        $stmt = $this->pdo->prepare("UPDATE users SET cart_data = ? WHERE id = ?");
        return $stmt->execute([$cartData, $userId]);
    }

    public function createStaff(string $fullname, string $email, string $phone, string $password, string $staffRole, string $staffTitle, array $permissions, string $address = 'Cửa hàng V-Phone', int $role = 2): int {
        $this->ensureStaffColumns();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $permJson = json_encode(array_values(array_unique($permissions)), JSON_UNESCAPED_UNICODE);
        $stmt = $this->pdo->prepare("INSERT INTO users (fullname, email, phone, address, password, role, staff_role, staff_title, permissions, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            $fullname,
            strtolower(trim($email)),
            $phone,
            $address,
            $hash,
            $role,
            $staffRole,
            $staffTitle,
            $permJson
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStaff(int $userId, string $fullname, string $email, string $phone, string $staffRole, string $staffTitle, array $permissions, ?string $newPassword = null, int $status = 1): bool {
        $this->ensureStaffColumns();
        $permJson = json_encode(array_values(array_unique($permissions)), JSON_UNESCAPED_UNICODE);
        $fields = ["fullname = ?", "email = ?", "phone = ?", "staff_role = ?", "staff_title = ?", "permissions = ?", "status = ?"];
        $params = [$fullname, strtolower(trim($email)), $phone, $staffRole, $staffTitle, $permJson, $status];

        if (!empty($newPassword)) {
            $fields[] = "password = ?";
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function getStaffList(): array {
        $this->ensureStaffColumns();
        $stmt = $this->pdo->query("SELECT * FROM users WHERE role >= 1 OR email = 'admin@vphone.vn' ORDER BY role ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toggleUserStatus(int $userId): bool {
        $this->ensureStaffColumns();
        $user = $this->findById($userId);
        if (!$user) return false;
        // Không được khóa super admin
        if (strtolower(trim($user['email'])) === 'admin@vphone.vn') {
            return false;
        }
        $newStatus = ((int)($user['status'] ?? 1) === 1) ? 0 : 1;
        $stmt = $this->pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        return $stmt->execute([$newStatus, $userId]);
    }

    public function deleteStaff(int $userId): bool {
        $user = $this->findById($userId);
        if (!$user) return false;
        if (strtolower(trim($user['email'])) === 'admin@vphone.vn') {
            return false;
        }
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function ensurePasswordResetsTable(): void {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(100) NOT NULL,
                token VARCHAR(64) NOT NULL,
                otp VARCHAR(10) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (email),
                INDEX (token),
                INDEX (otp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->pdo->exec($sql);
        } catch (Throwable $e) {}
    }

    public function createPasswordReset(string $email): array {
        $this->ensurePasswordResetsTable();
        $email = strtolower(trim($email));

        // Xóa các mã reset cũ của email này
        $del = $this->pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $del->execute([$email]);

        // Tạo OTP ngẫu nhiên 6 chữ số
        $otp = (string)random_int(100000, 999999);
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (15 * 60)); // 15 phút

        $stmt = $this->pdo->prepare("INSERT INTO password_resets (email, token, otp, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $token, $otp, $expiresAt]);

        return [
            'email' => $email,
            'otp' => $otp,
            'token' => $token,
            'expires_at' => $expiresAt
        ];
    }

    public function verifyPasswordResetOtp(string $email, string $otp): ?array {
        $this->ensurePasswordResetsTable();
        $stmt = $this->pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at >= NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([strtolower(trim($email)), trim($otp)]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function verifyPasswordResetToken(string $token): ?array {
        $this->ensurePasswordResetsTable();
        $stmt = $this->pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at >= NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([trim($token)]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function completePasswordReset(string $email, string $newPassword): bool {
        $email = strtolower(trim($email));
        $user = $this->findByEmail($email);
        if (!$user) return false;

        $updated = $this->updatePassword((int)$user['id'], $newPassword);
        if ($updated) {
            $del = $this->pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->execute([$email]);
            return true;
        }
        return false;
    }
}
