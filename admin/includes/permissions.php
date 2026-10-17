<?php
/**
 * V-Phone Store - Hệ Thống Phân Quyền Nhân Sự & Nhân Viên
 * Năm: 2026
 */

if (!defined('VPHONE_PERMISSIONS')) {
    define('VPHONE_PERMISSIONS', [
        'orders' => [
            'name' => 'Quản lý Đơn hàng',
            'desc' => 'Xem, tiếp nhận, chuyển trạng thái đơn (chuẩn bị, giao hàng, thành công, hủy), in hóa đơn VAT',
            'icon' => 'fa-solid fa-boxes-packing',
            'page' => 'orders.php',
            'badge' => 'bg-primary',
            'text_color' => 'text-primary'
        ],
        'products' => [
            'name' => 'Quản lý Sản phẩm & Thương hiệu',
            'desc' => 'Xem danh sách máy, cập nhật giá bán, thông số kỹ thuật, quản lý hãng sản xuất',
            'icon' => 'fa-solid fa-mobile-screen',
            'page' => 'products.php',
            'badge' => 'bg-info text-dark',
            'text_color' => 'text-info'
        ],
        'inventory' => [
            'name' => 'Quản lý Kho & Tồn kho',
            'desc' => 'Kiểm kê tồn kho thực tế, nhận cảnh báo sắp hết hàng, cập nhật số lượng nhập',
            'icon' => 'fa-solid fa-boxes-stacked',
            'page' => 'inventory.php',
            'badge' => 'bg-warning text-dark',
            'text_color' => 'text-warning'
        ],
        'warranties' => [
            'name' => 'Bảo hành & Sửa chữa',
            'desc' => 'Tiếp nhận yêu cầu bảo hành, tra cứu IMEI, cập nhật tiến độ xử lý & bàn giao máy',
            'icon' => 'fa-solid fa-shield-halved',
            'page' => 'warranties.php',
            'badge' => 'bg-success',
            'text_color' => 'text-success'
        ],
        'reviews' => [
            'name' => 'Đánh giá & Phản hồi',
            'desc' => 'Kiểm duyệt nhận xét đánh giá, phản hồi tư vấn khách hàng trên từng dòng máy',
            'icon' => 'fa-solid fa-star-half-stroke',
            'page' => 'reviews.php',
            'badge' => 'bg-danger',
            'text_color' => 'text-danger'
        ],
        'vouchers' => [
            'name' => 'Mã giảm giá & Khuyến mãi',
            'desc' => 'Xem và quản lý các voucher ưu đãi, hỗ trợ áp mã chiết khấu cho khách',
            'icon' => 'fa-solid fa-ticket',
            'page' => 'vouchers.php',
            'badge' => 'bg-secondary',
            'text_color' => 'text-secondary'
        ],
        'news' => [
            'name' => 'Tin tức & Bài viết',
            'desc' => 'Đăng tải và cập nhật bài viết tin tức công nghệ, thủ thuật, khuyến mãi',
            'icon' => 'fa-solid fa-newspaper',
            'page' => 'news.php',
            'badge' => 'bg-dark',
            'text_color' => 'text-dark'
        ],
        'support' => [
            'name' => 'CSKH & Hỗ trợ Đa kênh',
            'desc' => 'Tiếp nhận yêu cầu liên hệ nhanh từ widget nổi đa kênh, gọi điện tư vấn và gửi phản hồi cho khách hàng',
            'icon' => 'fa-solid fa-headset',
            'page' => 'support.php',
            'badge' => 'bg-info text-dark',
            'text_color' => 'text-info'
        ],
    ]);
}

/**
 * Lấy danh sách quyền của một người dùng dưới dạng mảng (Array of permission keys)
 */
function getUserPermissions(array|null $user = null): array {
    if (!$user) {
        $user = $_SESSION['user'] ?? null;
    }
    if (!$user) return [];

    // Super Admin hoặc Admin (role = 1) có mọi quyền
    if ((int)($user['role'] ?? 0) === 1) {
        return array_keys(VPHONE_PERMISSIONS);
    }

    $perms = $user['permissions'] ?? [];
    if (is_string($perms)) {
        $decoded = json_decode($perms, true);
        $perms = is_array($decoded) ? $decoded : [];
    }
    return is_array($perms) ? $perms : [];
}

/**
 * Kiểm tra xem người dùng hiện tại có quyền thực hiện một nhiệm vụ cụ thể hay không
 */
function currentUserCan(string $permissionKey): bool {
    $user = $_SESSION['user'] ?? null;
    if (!$user) return false;

    // Super Admin hoặc Admin có toàn quyền
    if ((int)($user['role'] ?? 0) === 1) {
        return true;
    }

    // Nếu là nhân viên (role = 2)
    if ((int)($user['role'] ?? 0) === 2) {
        $userPerms = getUserPermissions($user);
        return in_array($permissionKey, $userPerms, true);
    }

    return false;
}

/**
 * Kiểm tra xem người dùng hiện tại có phải là Super Admin không
 */
function isCurrentSuperAdmin(): bool {
    $user = $_SESSION['user'] ?? null;
    if (!$user) return false;
    $email = strtolower(trim((string)($user['email'] ?? '')));
    return ($email === 'admin@vphone.vn');
}

/**
 * Kiểm tra người dùng hiện tại có phải Quản trị viên (Admin / Super Admin) không
 */
function isCurrentAdmin(): bool {
    $user = $_SESSION['user'] ?? null;
    if (!$user) return false;
    return (int)($user['role'] ?? 0) === 1;
}

/**
 * Kiểm tra người dùng hiện tại có phải Nhân viên (Staff) không
 */
function isCurrentStaff(): bool {
    $user = $_SESSION['user'] ?? null;
    if (!$user) return false;
    return (int)($user['role'] ?? 0) === 2;
}

/**
 * Lấy nhãn chức danh hiển thị
 */
function getStaffTitleDisplay(array|null $user = null): string {
    if (!$user) $user = $_SESSION['user'] ?? null;
    if (!$user) return 'Khách hàng';

    if (isCurrentSuperAdmin()) {
        return 'Quản Trị Tối Cao';
    }
    if ((int)($user['role'] ?? 0) === 1) {
        return 'Quản trị viên (Admin)';
    }
    if ((int)($user['role'] ?? 0) === 2) {
        return !empty($user['staff_title']) ? $user['staff_title'] : 'Nhân viên cửa hàng';
    }
    return 'Khách hàng';
}
