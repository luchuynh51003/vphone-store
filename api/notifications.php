<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/NotificationModel.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser || empty($currentUser['id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$currentUser['id'];
$notifModel = new NotificationModel($pdo);
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

if ($action === 'mark_all_read') {
    $notifModel->markAllAsRead($userId);
    echo json_encode(['success' => true, 'unread_count' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'mark_read') {
    $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
    if ($id > 0) {
        $notifModel->markAsRead($id, $userId);
    }
    $unreadCount = $notifModel->getUnreadCount($userId);
    echo json_encode(['success' => true, 'unread_count' => $unreadCount], JSON_UNESCAPED_UNICODE);
    exit;
}

// Default: list notifications
$unreadCount = $notifModel->getUnreadCount($userId);
$list = $notifModel->getByUserId($userId, 15);

echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount,
    'notifications' => $list
], JSON_UNESCAPED_UNICODE);

