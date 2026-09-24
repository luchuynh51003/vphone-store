<?php
require_once 'config/database.php';

// Trước khi thoát, lưu lại giỏ hàng hiện tại vào Database của người đó
if (isset($_SESSION['user']['id']) && isset($_SESSION['cart'])) {
    $userId = $_SESSION['user']['id'];
    $cartJson = json_encode($_SESSION['cart'], JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("UPDATE users SET cart_data = ? WHERE id = ?");
    $stmt->execute([$cartJson, $userId]);
}

// XÓA SẠCH TÀI KHOẢN VÀ LÀM RỖNG GIỎ HÀNG VỀ 0
unset($_SESSION['user']);
$_SESSION['cart'] = [];

header('Location: index.php');
exit;
