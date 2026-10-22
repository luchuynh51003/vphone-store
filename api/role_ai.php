<?php
/**
 * V-Phone Store 2026 - Multi-Role AI Neural Assistant Engine
 * Hệ thống Chatbox AI Phân Quyền Theo Từng Chức Vụ Chuyên Biệt
 * 
 * Các chức vụ hỗ trợ:
 * 1. admin: Quản Trị Viên (V-Phone Executive AI - Chiến lược & Báo cáo Doanh thu)
 * 2. sales: Nhân viên Bán hàng & Đơn hàng (V-Phone Sales AI - Chốt đơn, Voucher & Up-sell)
 * 3. warehouse: Quản lý Kho & Tồn kho (V-Phone Warehouse AI - Kiểm kê IMEI & Cảnh báo tồn)
 * 4. warranty: Kỹ thuật viên & Bảo hành (V-Phone Tech & Care AI - Chẩn đoán & Tra cứu IMEI)
 * 5. support: Chuyên viên CSKH & Đa kênh (V-Phone Support AI - Kịch bản 5 sao & Xử lý khiếu nại)
 * 6. customer: Khách hàng mua sắm (V-Phone Cyber AI - Tư vấn Flagship & Trả góp 0%)
 */

require_once __DIR__ . '/../config/database.php';
global $pdo;
date_default_timezone_set('Asia/Ho_Chi_Minh');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!$data && !empty($_POST)) {
    $data = $_POST;
}

$userMessage = trim($data['message'] ?? '');
$requestedRole = trim($data['role'] ?? '');

// Xác định vai trò mặc định nếu không truyền lên
if (empty($requestedRole)) {
    if (isset($_SESSION['user'])) {
        $uRole = (int)($_SESSION['user']['role'] ?? 0);
        $sRole = strtolower(trim((string)($_SESSION['user']['staff_role'] ?? '')));
        if ($uRole === 1) {
            $requestedRole = 'admin';
        } elseif ($uRole === 2) {
            if (in_array($sRole, ['support', 'sales', 'warehouse', 'warranty', 'all'])) {
                $requestedRole = ($sRole === 'all') ? 'admin' : $sRole;
            } else {
                $requestedRole = 'sales';
            }
        } else {
            $requestedRole = 'customer';
        }
    } else {
        $requestedRole = 'customer';
    }
}

// Chuẩn hóa role hợp lệ
$validRoles = ['admin', 'sales', 'warehouse', 'warranty', 'support', 'customer'];
if (!in_array($requestedRole, $validRoles, true)) {
    $requestedRole = 'customer';
}

require_once __DIR__ . '/../admin/includes/permissions.php';

// Kiểm tra bảo mật phân quyền:
// Chỉ Super Admin hoặc Admin thường mới có toàn quyền sử dụng các AI nội bộ.
// Nhân viên (role = 2) BẮT BUỘC phải được Admin cấp quyền 'ai_assistant' và CHỈ ĐƯỢC dùng AI chức vụ của mình!
if ($requestedRole !== 'customer') {
    if (!isset($_SESSION['user']) || (int)($_SESSION['user']['role'] ?? 0) < 1) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Bạn cần đăng nhập tài khoản quản trị hoặc nhân sự để sử dụng chức năng này.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $isAdm = isCurrentAdmin();
    if (!$isAdm) {
        // Là nhân viên: Kiểm tra quyền 'ai_assistant' do Admin cấp
        if (!currentUserCan('ai_assistant')) {
            echo json_encode([
                'success' => false,
                'reply' => '<div class="alert alert-warning py-2 px-3 small rounded-3 mb-0 text-dark"><i class="fa-solid fa-lock text-warning me-1"></i> Tài khoản nhân viên của bạn chưa được Quản trị viên cấp quyền sử dụng Trợ lý AI!</div>',
                'suggestions' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Nhân viên chỉ được dùng đúng AI chức vụ của mình, không được can thiệp chức vụ khác
        $myStaffRole = strtolower(trim((string)($_SESSION['user']['staff_role'] ?? 'sales')));
        if ($myStaffRole !== 'all' && $requestedRole !== $myStaffRole) {
            $requestedRole = in_array($myStaffRole, ['sales', 'warehouse', 'warranty', 'support']) ? $myStaffRole : 'sales';
        }
    }
}

/**
 * Hàm chuẩn hóa tiếng Việt không dấu
 */
if (!function_exists('removeVietnameseAccents')) {
function removeVietnameseAccents(string $str): string {
    $str = mb_strtolower($str, 'UTF-8');
    $unicode = [
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
    ];
    foreach ($unicode as $nonAccent => $accent) {
        $str = preg_replace("/($accent)/i", $nonAccent, $str);
    }
    return $str;
}
}

$cleanText = removeVietnameseAccents($userMessage);

if (!function_exists('formatVND')) {
function formatVND($amount): string {
    return number_format((float)$amount, 0, ',', '.') . ' VNĐ';
}
}

/**
 * Helper: Khớp sản phẩm theo ID hoặc tên gọi tiếng Việt
 */
if (!function_exists('matchProductFromText')) {
function matchProductFromText(PDO $pdo, string $cleanText): ?array {
    // 1. Khớp theo ID rõ ràng: #1, id 1, sp 1, san pham 1
    if (preg_match('/(san pham|sp|id|ma)\s*#?(\d+)/i', $cleanText, $idM)) {
        $stmt = $pdo->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
        $stmt->execute([(int)$idM[2]]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($prod) return $prod;
    }

    // 2. Khớp từ khóa trên danh mục sản phẩm
    $all = $pdo->query("SELECT id, name, price, quantity FROM products")->fetchAll(PDO::FETCH_ASSOC);
    $bestScore = 0;
    $matched = null;

    foreach ($all as $p) {
        $pClean = removeVietnameseAccents($p['name']);
        if (str_contains($cleanText, $pClean)) {
            $score = mb_strlen($pClean) * 3;
        } else {
            $words = array_filter(explode(' ', $pClean), fn($w) => mb_strlen($w) >= 2);
            $score = 0;
            $matchCount = 0;
            foreach ($words as $w) {
                if (preg_match('/\b' . preg_quote($w, '/') . '\b/i', $cleanText)) {
                    $score += mb_strlen($w) * 2;
                    $matchCount++;
                } elseif (str_contains($cleanText, $w)) {
                    $score += mb_strlen($w);
                    $matchCount++;
                }
            }
            if ($matchCount < 2 && count($words) >= 3) {
                $score = 0;
            }
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $matched = $p;
        }
    }

    return ($bestScore >= 4) ? $matched : null;
}
}

/**
 * Helper: Thực thi lệnh Nghiệp Vụ Kho (Nhập hàng, Thêm hàng, Xuất kho, Đặt tồn kho)
 */
if (!function_exists('handleInventoryAction')) {
function handleInventoryAction(PDO $pdo, string $cleanText, string $userMessage): ?array {
    $isInvCmd = (
        preg_match('/(nhap hang|them hang|tang hang|them kho|nhap kho|nhap them|tang ton kho|cap nhat ton kho|cap nhat so luong|xuat kho|xuat hang|giam ton kho|giam so luong|tru ton kho)\b/i', $cleanText) ||
        preg_match('/^(nhap|them|xuat|giam)\s+\d+/i', $cleanText)
    );
    if (!$isInvCmd) return null;

    $mode = 'add';
    $qty = 1;

    if (preg_match('/(thanh|dat|set)\s*(\d+)/i', $cleanText, $m)) {
        $mode = 'set';
        $qty = (int)$m[2];
    } elseif (preg_match('/(xuat|giam|tru|bot)\b/i', $cleanText)) {
        $mode = 'sub';
        if (preg_match('/(\d+)\s*(may|chiec|cai|hop|don vi|sp)\b/i', $cleanText, $qm)) {
            $qty = (int)$qm[1];
        } elseif (preg_match('/(xuat|giam|tru|bot)\s*(\d+)/i', $cleanText, $qm)) {
            $qty = (int)$qm[2];
        } elseif (preg_match('/(\d+)/', $cleanText, $qm)) {
            $qty = (int)$qm[1];
        }
    } else {
        $mode = 'add';
        if (preg_match('/(\d+)\s*(may|chiec|cai|hop|don vi|sp)\b/i', $cleanText, $qm)) {
            $qty = (int)$qm[1];
        } elseif (preg_match('/(nhap|them|tang)\s*(\d+)/i', $cleanText, $qm)) {
            $qty = (int)$qm[2];
        } elseif (preg_match('/(\d+)/', $cleanText, $qm)) {
            $qty = (int)$qm[1];
        }
    }

    $product = matchProductFromText($pdo, $cleanText);
    if (!$product) {
        return [
            'reply' => "<div class='p-2.5 rounded bg-warning bg-opacity-10 border border-warning text-dark small'>"
                . "<i class='fa-solid fa-triangle-exclamation text-warning me-1'></i> <strong>Chưa nhận diện được sản phẩm!</strong><br>"
                . "Hệ thống đã nhận lệnh kho nhưng chưa tìm thấy tên máy tương ứng. Bạn vui lòng nêu rõ tên máy, ví dụ: <em>'nhập thêm 20 máy iPhone 18 Pro Max'</em> hoặc <em>'nhập 10 máy cho sản phẩm #1'</em>."
                . "</div>",
            'suggestions' => ['Sản phẩm sắp hết hàng (tồn ≤ 5)', 'Thống kê tổng giá trị tồn kho', 'Tồn kho theo từng hãng máy']
        ];
    }

    $oldQty = (int)$product['quantity'];
    if ($mode === 'add') {
        $newQty = $oldQty + $qty;
        $actionTitle = "NHẬP KHO THÀNH CÔNG";
        $badgeClass = "bg-success";
        $badgeText = "+{$qty} chiếc nhập thêm";
    } elseif ($mode === 'sub') {
        $newQty = max(0, $oldQty - $qty);
        $actionTitle = "XUẤT KHO THÀNH CÔNG";
        $badgeClass = "bg-warning text-dark";
        $badgeText = "-{$qty} chiếc xuất kho";
    } else {
        $newQty = max(0, $qty);
        $actionTitle = "ĐIỀU CHỈNH TỒN KHO THÀNH CÔNG";
        $badgeClass = "bg-info text-dark";
        $badgeText = "Định mức mới: {$newQty} chiếc";
    }

    $up = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
    $up->execute([$newQty, $product['id']]);

    $reply = "<div class='ai-action-card p-2.5 rounded-3 mb-2' style='background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac;'>";
    $reply .= "  <div class='d-flex align-items-center gap-2 mb-2'>";
    $reply .= "    <div style='width:32px; height:32px; border-radius:50%; background:#22c55e; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;'><i class='fa-solid fa-boxes-stacked'></i></div>";
    $reply .= "    <div>";
    $reply .= "      <strong class='text-success d-block' style='font-size:0.9rem;'>{$actionTitle}!</strong>";
    $reply .= "      <small class='text-secondary'>Cơ sở dữ liệu tồn kho thực tế đã được cập nhật.</small>";
    $reply .= "    </div>";
    $reply .= "  </div>";
    $reply .= "  <div class='p-2 rounded bg-white border small shadow-xs mb-2'>";
    $reply .= "    <div class='fw-bold text-dark mb-1'>" . htmlspecialchars($product['name']) . " <span class='badge bg-light text-muted border'>#{$product['id']}</span></div>";
    $reply .= "    <div class='d-flex align-items-center justify-content-between mb-1'>";
    $reply .= "      <span class='text-muted'>Thao tác nghiệp vụ:</span>";
    $reply .= "      <span class='badge {$badgeClass}'>{$badgeText}</span>";
    $reply .= "    </div>";
    $reply .= "    <div class='d-flex align-items-center justify-content-between'>";
    $reply .= "      <span class='text-muted'>Số lượng tồn kho:</span>";
    $reply .= "      <span><span class='text-muted text-decoration-line-through me-1'>{$oldQty} máy</span> &rarr; <strong class='text-success fs-6 ms-1'>{$newQty} chiếc</strong></span>";
    $reply .= "    </div>";
    $reply .= "  </div>";
    $reply .= "  <div class='d-flex gap-2'>";
    $reply .= "    <a href='inventory.php' class='btn btn-sm btn-outline-dark rounded-pill px-3 py-1 text-decoration-none' style='font-size:0.75rem;'><i class='fa-solid fa-boxes-stacked me-1'></i>Xem Quản Lý Kho</a>";
    $reply .= "    <a href='products.php' class='btn btn-sm btn-outline-primary rounded-pill px-3 py-1 text-decoration-none' style='font-size:0.75rem;'><i class='fa-solid fa-mobile-screen me-1'></i>Xem Sản Phẩm</a>";
    $reply .= "  </div>";
    $reply .= "</div>";

    return [
        'reply' => $reply,
        'suggestions' => [
            'Sản phẩm sắp hết hàng (tồn ≤ 5)',
            'Thống kê tổng giá trị tồn kho',
            'Tồn kho theo từng hãng máy'
        ]
    ];
}
}

/**
 * Helper: Thực thi lệnh Cập Nhật Trạng Thái Đơn Hàng (Xác nhận, Giao hàng, Hoàn thành, Hủy)
 */
if (!function_exists('handleOrderStatusAction')) {
function handleOrderStatusAction(PDO $pdo, string $cleanText): ?array {
    $isOrderCmd = preg_match('/(xac nhan don|duyet don|chuyen don|cap nhat don|chuyen trang thai don|hoan thanh don|giao don|huy don)\b/i', $cleanText);
    if (!$isOrderCmd) return null;

    $orderId = 0;
    if (preg_match('/(#?vp-?|#|don\s*hang\s*#?|don\s*#?)([0-9]+)/i', $cleanText, $m)) {
        $orderId = (int)$m[2];
    } elseif (preg_match('/(\d+)/', $cleanText, $m)) {
        $orderId = (int)$m[1];
    }

    if ($orderId <= 0) {
        return [
            'reply' => "<div class='p-2 rounded bg-warning bg-opacity-10 border border-warning text-dark small'>"
                . "<i class='fa-solid fa-triangle-exclamation text-warning me-1'></i> Vui lòng nêu rõ mã đơn hàng cần xử lý (VD: <em>'xác nhận đơn hàng #20'</em> hoặc <em>'chuyển đơn 20 sang đang giao hàng'</em>)."
                . "</div>",
            'suggestions' => ['Tình trạng đơn hàng chờ duyệt', 'Báo cáo doanh thu hôm nay']
        ];
    }

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        return [
            'reply' => "<div class='p-2 rounded bg-danger bg-opacity-10 border border-danger text-danger small'>"
                . "<i class='fa-solid fa-circle-xmark me-1'></i> Không tìm thấy đơn hàng <strong>#VP-{$orderId}</strong> trong hệ thống!"
                . "</div>",
            'suggestions' => ['Tình trạng đơn hàng chờ duyệt', 'Báo cáo doanh thu hôm nay']
        ];
    }

    $oldStatus = $order['status'];
    $newStatus = 'Đang xử lý';

    if (preg_match('/(giao thanh cong|hoan thanh|da giao)/i', $cleanText)) {
        $newStatus = 'Đã giao thành công';
        $badgeColor = 'bg-success';
    } elseif (preg_match('/(dang giao|giao hang|van chuyen)/i', $cleanText)) {
        $newStatus = 'Đang giao hàng';
        $badgeColor = 'bg-primary';
    } elseif (preg_match('/(huy|cancel)/i', $cleanText)) {
        $newStatus = 'Đã hủy';
        $badgeColor = 'bg-danger';
    } else {
        $newStatus = 'Đang xử lý';
        $badgeColor = 'bg-info text-dark';
    }

    $up = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $up->execute([$newStatus, $orderId]);

    $reply = "<div class='ai-action-card p-2.5 rounded-3 mb-2' style='background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac;'>";
    $reply .= "  <div class='d-flex align-items-center gap-2 mb-2'>";
    $reply .= "    <div style='width:32px; height:32px; border-radius:50%; background:#22c55e; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;'><i class='fa-solid fa-clipboard-check'></i></div>";
    $reply .= "    <div>";
    $reply .= "      <strong class='text-success d-block' style='font-size:0.9rem;'>CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG THÀNH CÔNG!</strong>";
    $reply .= "      <small class='text-secondary'>Đã lưu trạng thái mới vào cơ sở dữ liệu.</small>";
    $reply .= "    </div>";
    $reply .= "  </div>";
    $reply .= "  <div class='p-2 rounded bg-white border small shadow-xs mb-2'>";
    $reply .= "    <div class='d-flex align-items-center justify-content-between mb-1'>";
    $reply .= "      <strong>Đơn hàng #VP-{$orderId}</strong>";
    $reply .= "      <span class='badge {$badgeColor}'>{$newStatus}</span>";
    $reply .= "    </div>";
    $reply .= "    <div class='text-muted' style='font-size:0.75rem;'>Khách: <strong>" . htmlspecialchars($order['fullname']) . "</strong> &bull; " . htmlspecialchars($order['phone']) . "</div>";
    $reply .= "    <div class='text-dark fw-bold mt-1'>Tổng tiền: " . formatVND($order['total_money']) . "</div>";
    $reply .= "    <div class='text-muted mt-1' style='font-size:0.72rem;'>Trạng thái trước: <span class='badge bg-light text-secondary border'>{$oldStatus}</span> &rarr; Nay: <span class='badge {$badgeColor}'>{$newStatus}</span></div>";
    $reply .= "  </div>";
    $reply .= "  <div><a href='orders.php?search={$orderId}' class='btn btn-sm btn-outline-primary rounded-pill px-3 py-1 text-decoration-none' style='font-size:0.75rem;'><i class='fa-solid fa-boxes-packing me-1'></i>Xem Đơn Trong Quản Lý Đơn Hàng</a></div>";
    $reply .= "</div>";

    return [
        'reply' => $reply,
        'suggestions' => [
            'Tình trạng đơn hàng chờ duyệt',
            'Danh sách voucher đang hoạt động',
            'Báo cáo doanh thu hôm nay'
        ]
    ];
}
}

/**
 * Helper: Thực thi lệnh Cập Nhật Giá Bán Sản Phẩm
 */
if (!function_exists('handlePriceUpdateAction')) {
function handlePriceUpdateAction(PDO $pdo, string $cleanText): ?array {
    $isPriceCmd = preg_match('/(cap nhat gia|doi gia|tang gia|giam gia|ha gia|set gia|sua gia)\b/i', $cleanText);
    if (!$isPriceCmd) return null;

    $price = 0;
    if (preg_match('/(\d{6,10})/i', $cleanText, $pm)) {
        $price = (float)$pm[1];
    } elseif (preg_match('/(\d+(\.\d+)?)\s*(trieu|tr)/i', $cleanText, $pm)) {
        $price = (float)$pm[1] * 1000000;
    }

    if ($price <= 0) {
        return [
            'reply' => "<div class='p-2 rounded bg-warning bg-opacity-10 border border-warning text-dark small'>"
                . "<i class='fa-solid fa-triangle-exclamation text-warning me-1'></i> Vui lòng nêu rõ mức giá mới (VD: <em>'cập nhật giá iPhone 18 Pro Max thành 36500000'</em> hoặc <em>'đổi giá sản phẩm #1 thành 37 triệu'</em>)."
                . "</div>",
            'suggestions' => ['Top sản phẩm bán chạy nhất', 'Cảnh báo tồn kho & máy sắp hết']
        ];
    }

    $product = matchProductFromText($pdo, $cleanText);
    if (!$product) {
        return [
            'reply' => "<div class='p-2 rounded bg-warning bg-opacity-10 border border-warning text-dark small'>"
                . "<i class='fa-solid fa-triangle-exclamation text-warning me-1'></i> Chưa tìm thấy sản phẩm cần đổi giá. Vui lòng nêu rõ tên máy hoặc mã sản phẩm!"
                . "</div>",
            'suggestions' => ['Top sản phẩm bán chạy nhất', 'Cảnh báo tồn kho & máy sắp hết']
        ];
    }

    $oldPrice = (float)$product['price'];
    $up = $pdo->prepare("UPDATE products SET price = ? WHERE id = ?");
    $up->execute([$price, $product['id']]);

    $reply = "<div class='ai-action-card p-2.5 rounded-3 mb-2' style='background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac;'>";
    $reply .= "  <div class='d-flex align-items-center gap-2 mb-2'>";
    $reply .= "    <div style='width:32px; height:32px; border-radius:50%; background:#22c55e; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;'><i class='fa-solid fa-tag'></i></div>";
    $reply .= "    <div>";
    $reply .= "      <strong class='text-success d-block' style='font-size:0.9rem;'>CẬP NHẬT GIÁ BÁN THÀNH CÔNG!</strong>";
    $reply .= "      <small class='text-secondary'>Giá niêm yết đã được đồng bộ toàn website.</small>";
    $reply .= "    </div>";
    $reply .= "  </div>";
    $reply .= "  <div class='p-2 rounded bg-white border small shadow-xs mb-2'>";
    $reply .= "    <div class='fw-bold text-dark mb-1'>" . htmlspecialchars($product['name']) . " <span class='badge bg-light text-muted border'>#{$product['id']}</span></div>";
    $reply .= "    <div class='d-flex align-items-center justify-content-between'>";
    $reply .= "      <span class='text-muted'>Mức giá:</span>";
    $reply .= "      <span><span class='text-muted text-decoration-line-through me-1'>" . formatVND($oldPrice) . "</span> &rarr; <strong class='text-danger fs-6 ms-1'>" . formatVND($price) . "</strong></span>";
    $reply .= "    </div>";
    $reply .= "  </div>";
    $reply .= "  <div><a href='products.php' class='btn btn-sm btn-outline-primary rounded-pill px-3 py-1 text-decoration-none' style='font-size:0.75rem;'><i class='fa-solid fa-mobile-screen me-1'></i>Xem Trong Danh Sách Sản Phẩm</a></div>";
    $reply .= "</div>";

    return [
        'reply' => $reply,
        'suggestions' => [
            'Báo cáo doanh thu hôm nay',
            'Top sản phẩm bán chạy nhất',
            'Cảnh báo tồn kho & máy sắp hết'
        ]
    ];
}
}

/**
 * Helper: Thực thi lệnh Khóa / Mở Khóa Tài Khoản Nhân Viên
 */
if (!function_exists('handleAccountStatusAction')) {
function handleAccountStatusAction(PDO $pdo, string $cleanText): ?array {
    $isUnlock = preg_match('/(mo khoa tai khoan|mo khoa|kich hoat lai|unblock|mo khoa nhan vien)\b/i', $cleanText);
    $isLock = !$isUnlock && preg_match('/(khoa tai khoan|tam khoa|block|khoa nhan vien)\b/i', $cleanText);
    if (!$isLock && !$isUnlock) return null;

    $allStaff = $pdo->query("SELECT id, fullname, email, role, status FROM users WHERE role = 2")->fetchAll(PDO::FETCH_ASSOC);
    $matched = null;
    foreach ($allStaff as $stf) {
        $cName = removeVietnameseAccents($stf['fullname']);
        $cEmail = strtolower($stf['email']);
        if (str_contains($cleanText, $cEmail) || str_contains($cleanText, $cName)) {
            $matched = $stf;
            break;
        }
        $words = array_filter(explode(' ', $cName), fn($w) => mb_strlen($w) >= 3);
        foreach ($words as $w) {
            if (str_contains($cleanText, $w)) { $matched = $stf; break 2; }
        }
    }

    if (!$matched) {
        return [
            'reply' => "<div class='p-2 rounded bg-warning bg-opacity-10 border border-warning text-dark small'>"
                . "<i class='fa-solid fa-triangle-exclamation text-warning me-1'></i> Vui lòng nêu rõ tên nhân viên hoặc email cần khóa/mở khóa!"
                . "</div>",
            'suggestions' => ['Kiểm tra danh sách nhân viên có quyền AI', 'Báo cáo doanh thu hôm nay']
        ];
    }

    $newStatus = $isLock ? 0 : 1;
    $statusText = $isLock ? "TẠM KHÓA TÀI KHOẢN" : "MỞ KHÓA TÀI KHOẢN";
    $badgeClass = $isLock ? "bg-danger" : "bg-success";
    $badgeStatus = $isLock ? "Đã khóa" : "Đang hoạt động";

    $up = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $up->execute([$newStatus, $matched['id']]);

    $reply = "<div class='ai-action-card p-2.5 rounded-3 mb-2' style='background: linear-gradient(135deg, " . ($isLock ? "#fef2f2, #fff1f2" : "#f0fdf4, #ecfdf5") . "); border: 1px solid " . ($isLock ? "#fecaca" : "#86efac") . ";'>";
    $reply .= "  <div class='d-flex align-items-center gap-2 mb-2'>";
    $reply .= "    <div style='width:32px; height:32px; border-radius:50%; background:" . ($isLock ? "#ef4444" : "#22c55e") . "; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;'><i class='fa-solid " . ($isLock ? "fa-user-lock" : "fa-user-check") . "'></i></div>";
    $reply .= "    <div>";
    $reply .= "      <strong class='" . ($isLock ? "text-danger" : "text-success") . " d-block' style='font-size:0.9rem;'>{$statusText} THÀNH CÔNG!</strong>";
    $reply .= "      <small class='text-secondary'>Trạng thái tài khoản đã được cập nhật.</small>";
    $reply .= "    </div>";
    $reply .= "  </div>";
    $reply .= "  <div class='p-2 rounded bg-white border small shadow-xs mb-2'>";
    $reply .= "    <div class='d-flex align-items-center justify-content-between mb-1'>";
    $reply .= "      <strong>" . htmlspecialchars($matched['fullname']) . "</strong>";
    $reply .= "      <span class='badge {$badgeClass}'>{$badgeStatus}</span>";
    $reply .= "    </div>";
    $reply .= "    <div class='text-muted' style='font-size:0.75rem;'>" . htmlspecialchars($matched['email']) . " &bull; ID: #NV-{$matched['id']}</div>";
    $reply .= "  </div>";
    $reply .= "  <div><a href='staff.php' class='btn btn-sm btn-outline-dark rounded-pill px-3 py-1 text-decoration-none' style='font-size:0.75rem;'><i class='fa-solid fa-users me-1'></i>Xem Danh Sách Nhân Sự</a></div>";
    $reply .= "</div>";

    return [
        'reply' => $reply,
        'suggestions' => [
            'Kiểm tra danh sách nhân viên có quyền AI',
            'Báo cáo doanh thu hôm nay'
        ]
    ];
}
}

/**
 * Helper: Thực thi lệnh Tạo Mã Giảm Giá / Voucher
 */
if (!function_exists('handleVoucherAction')) {
function handleVoucherAction(PDO $pdo, string $cleanText): ?array {
    $isVoucherCmd = preg_match('/(tao voucher|them voucher|tao ma giam gia|them ma giam gia)\b/i', $cleanText);
    if (!$isVoucherCmd) return null;

    $code = '';
    if (preg_match('/(voucher|ma)\s+([A-Za-z0-9_-]+)/i', $cleanText, $cm)) {
        $code = strtoupper(trim($cm[2]));
    } else {
        $code = 'SALE' . date('dm') . rand(10, 99);
    }

    $val = 50000;
    if (preg_match('/giam\s*(\d{4,8})/i', $cleanText, $vm)) {
        $val = (float)$vm[1];
    } elseif (preg_match('/giam\s*(\d+)\s*(k|nghin)/i', $cleanText, $vm)) {
        $val = (float)$vm[1] * 1000;
    }

    $minOrd = 500000;
    if (preg_match('/(don tu|tu|toi thieu)\s*(\d{5,9})/i', $cleanText, $mm)) {
        $minOrd = (float)$mm[2];
    } elseif (preg_match('/(don tu|tu|toi thieu)\s*(\d+)\s*(k|nghin|trieu|tr)/i', $cleanText, $mm)) {
        $mul = in_array(strtolower($mm[3]), ['trieu', 'tr']) ? 1000000 : 1000;
        $minOrd = (float)$mm[2] * $mul;
    }

    $ins = $pdo->prepare("INSERT INTO vouchers (code, discount_type, discount_value, minimum_order, is_active, created_at) VALUES (?, 'fixed', ?, ?, 1, NOW())");
    $ins->execute([$code, $val, $minOrd]);

    $reply = "<div class='ai-action-card p-2.5 rounded-3 mb-2' style='background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac;'>";
    $reply .= "  <div class='d-flex align-items-center gap-2 mb-2'>";
    $reply .= "    <div style='width:32px; height:32px; border-radius:50%; background:#22c55e; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;'><i class='fa-solid fa-ticket'></i></div>";
    $reply .= "    <div>";
    $reply .= "      <strong class='text-success d-block' style='font-size:0.9rem;'>TẠO VOUCHER THÀNH CÔNG!</strong>";
    $reply .= "      <small class='text-secondary'>Mã giảm giá đã được kích hoạt trực tiếp trên web.</small>";
    $reply .= "    </div>";
    $reply .= "  </div>";
    $reply .= "  <div class='p-2 rounded bg-white border small shadow-xs mb-2'>";
    $reply .= "    <div class='d-flex align-items-center justify-content-between mb-1'>";
    $reply .= "      <strong class='text-primary font-monospace fs-6'>{$code}</strong>";
    $reply .= "      <span class='badge bg-success'>Đang hoạt động</span>";
    $reply .= "    </div>";
    $reply .= "    <div class='text-muted' style='font-size:0.75rem;'>Giảm trực tiếp: <strong class='text-danger'>" . formatVND($val) . "</strong> (Đơn từ " . formatVND($minOrd) . ")</div>";
    $reply .= "  </div>";
    $reply .= "  <div><a href='vouchers.php' class='btn btn-sm btn-outline-primary rounded-pill px-3 py-1 text-decoration-none' style='font-size:0.75rem;'><i class='fa-solid fa-ticket me-1'></i>Xem Trong Quản Lý Voucher</a></div>";
    $reply .= "</div>";

    return [
        'reply' => $reply,
        'suggestions' => [
            'Danh sách voucher đang hoạt động',
            'Tra cứu đơn hàng mới nhất',
            'Gợi ý kịch bản up-sell sạc & tai nghe'
        ]
    ];
}
}

/**
 * Helper: Thực thi lệnh Kiểm Duyệt Đánh Giá (Duyệt, Ẩn review)
 */
if (!function_exists('handleReviewAction')) {
function handleReviewAction(PDO $pdo, string $cleanText): ?array {
    $isApprove = preg_match('/(duyet danh gia|duyet review|duyet tat ca|chap nhan danh gia)\b/i', $cleanText);
    $isHidden = preg_match('/(an danh gia|an review|khoa review|xoa danh gia)\b/i', $cleanText);
    if (!$isApprove && !$isHidden) return null;

    if ($isApprove) {
        if (preg_match('/(tat ca|toan bo|het)/i', $cleanText)) {
            $count = (int)$pdo->exec("UPDATE product_reviews SET status = 1 WHERE status = 0");
            $msg = "Đã duyệt tự động <strong>{$count} đánh giá</strong> đang chờ kiểm duyệt.";
        } else {
            preg_match('/(\d+)/', $cleanText, $m);
            $rid = isset($m[1]) ? (int)$m[1] : 0;
            if ($rid > 0) {
                $pdo->prepare("UPDATE product_reviews SET status = 1 WHERE id = ?")->execute([$rid]);
                $msg = "Đã duyệt hiển thị thành công cho đánh giá <strong>#{$rid}</strong>.";
            } else {
                $count = (int)$pdo->exec("UPDATE product_reviews SET status = 1 WHERE status = 0");
                $msg = "Đã duyệt tự động <strong>{$count} đánh giá</strong> đang chờ kiểm duyệt.";
            }
        }
        $title = "DUYỆT ĐÁNH GIÁ THÀNH CÔNG";
    } else {
        preg_match('/(\d+)/', $cleanText, $m);
        $rid = isset($m[1]) ? (int)$m[1] : 0;
        if ($rid > 0) {
            $pdo->prepare("UPDATE product_reviews SET status = 0 WHERE id = ?")->execute([$rid]);
            $msg = "Đã ẩn đánh giá vi phạm <strong>#{$rid}</strong> khỏi sản phẩm.";
        } else {
            $msg = "Vui lòng nêu rõ mã ID đánh giá cần ẩn (VD: <em>'ẩn đánh giá ID 3'</em>).";
        }
        $title = "ẨN ĐÁNH GIÁ THÀNH CÔNG";
    }

    $reply = "<div class='ai-action-card p-2.5 rounded-3 mb-2' style='background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac;'>";
    $reply .= "  <div class='d-flex align-items-center gap-2 mb-2'>";
    $reply .= "    <div style='width:32px; height:32px; border-radius:50%; background:#22c55e; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;'><i class='fa-solid fa-star'></i></div>";
    $reply .= "    <div>";
    $reply .= "      <strong class='text-success d-block' style='font-size:0.9rem;'>{$title}!</strong>";
    $reply .= "      <small class='text-secondary'>Dữ liệu kiểm duyệt đã được lưu vào cơ sở dữ liệu.</small>";
    $reply .= "    </div>";
    $reply .= "  </div>";
    $reply .= "  <div class='p-2 rounded bg-white border small shadow-xs mb-2'>{$msg}</div>";
    $reply .= "  <div><a href='reviews.php' class='btn btn-sm btn-outline-primary rounded-pill px-3 py-1 text-decoration-none' style='font-size:0.75rem;'><i class='fa-solid fa-star-half-stroke me-1'></i>Xem Quản Lý Đánh Giá</a></div>";
    $reply .= "</div>";

    return [
        'reply' => $reply,
        'suggestions' => [
            'Đánh giá sản phẩm chưa phản hồi',
            'Khách hàng yêu cầu gọi lại mới nhất',
            'Mẫu trả lời đánh giá 1 sao chuyên nghiệp'
        ]
    ];
}
}

/**
 * Helper: Thực thi lệnh Tiếp Nhận & Cập Nhật Bảo Hành
 */
if (!function_exists('handleWarrantyAction')) {
function handleWarrantyAction(PDO $pdo, string $cleanText, string $userMessage): ?array {
    $isCreate = preg_match('/(tiep nhan bao hanh|tao phieu bao hanh|lap phieu bao hanh|nhan may bao hanh)\b/i', $cleanText);
    $isUpdate = preg_match('/(cap nhat bao hanh|chuyen trang thai bao hanh|sua xong|da xong|da ban giao)\b/i', $cleanText);
    if (!$isCreate && !$isUpdate) return null;

    if ($isCreate) {
        $imei = '';
        if (preg_match('/([0-9]{10,18})/i', $cleanText, $im)) {
            $imei = $im[1];
        }
        $custName = 'Khách Hàng Tiếp Nhận';
        if (preg_match('/khach\s+([a-zA-ZÀ-ỹ\s]+?)(loi|imei|yeu cau|$)/iu', $userMessage, $nm)) {
            $custName = trim($nm[1]);
        }
        $issue = 'Yêu cầu kiểm tra & bảo hành phần cứng';
        if (preg_match('/(loi|tinh trang|bi)\s*(.+)$/i', $userMessage, $ism)) {
            $issue = trim($ism[2]);
        }

        $ins = $pdo->prepare("INSERT INTO warranty_claims (order_id, product_name, imei, customer_name, customer_phone, issue_type, issue_description, status, created_at, updated_at) VALUES (0, 'Điện thoại nhận bảo hành', ?, ?, '0900000000', 'Lỗi phần cứng', ?, 'Tiếp nhận', NOW(), NOW())");
        $ins->execute([$imei ?: ('IMEI' . date('dHis')), $custName, $issue]);
        $newId = $pdo->lastInsertId();

        $reply = "<div class='ai-action-card p-2.5 rounded-3 mb-2' style='background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac;'>";
        $reply .= "  <div class='d-flex align-items-center gap-2 mb-2'>";
        $reply .= "    <div style='width:32px; height:32px; border-radius:50%; background:#22c55e; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;'><i class='fa-solid fa-shield-halved'></i></div>";
        $reply .= "    <div>";
        $reply .= "      <strong class='text-success d-block' style='font-size:0.9rem;'>TIẾP NHẬN PHIẾU BẢO HÀNH THÀNH CÔNG!</strong>";
        $reply .= "      <small class='text-secondary'>Đã lưu thông tin tiếp nhận vào hệ thống.</small>";
        $reply .= "    </div>";
        $reply .= "  </div>";
        $reply .= "  <div class='p-2 rounded bg-white border small shadow-xs mb-2'>";
        $reply .= "    <div class='d-flex align-items-center justify-content-between mb-1'>";
        $reply .= "      <strong>Phiếu Bảo Hành #BH-{$newId}</strong>";
        $reply .= "      <span class='badge bg-warning text-dark'>Tiếp nhận</span>";
        $reply .= "    </div>";
        $reply .= "    <div class='text-muted' style='font-size:0.75rem;'>Khách: <strong>" . htmlspecialchars($custName) . "</strong> &bull; IMEI: " . htmlspecialchars($imei ?: 'Đang cập nhật') . "</div>";
        $reply .= "    <div class='text-dark mt-1' style='font-size:0.75rem;'>Lỗi mô tả: " . htmlspecialchars($issue) . "</div>";
        $reply .= "  </div>";
        $reply .= "  <div><a href='warranties.php' class='btn btn-sm btn-outline-success rounded-pill px-3 py-1 text-decoration-none' style='font-size:0.75rem;'><i class='fa-solid fa-shield-halved me-1'></i>Xem Quản Lý Bảo Hành</a></div>";
        $reply .= "</div>";
    } else {
        $newStatus = 'Đang sửa chữa';
        if (preg_match('/(da xong|sua xong|hoan thanh)/i', $cleanText)) {
            $newStatus = 'Đã hoàn thành';
        } elseif (preg_match('/(ban giao|tra khach)/i', $cleanText)) {
            $newStatus = 'Đã bàn giao';
        }

        $idOrImei = '';
        if (preg_match('/(\d{10,18})/', $cleanText, $im)) {
            $idOrImei = $im[1];
            $up = $pdo->prepare("UPDATE warranty_claims SET status = ?, updated_at = NOW() WHERE imei = ?");
            $up->execute([$newStatus, $idOrImei]);
        } elseif (preg_match('/(phieu|#)?\s*(\d+)/i', $cleanText, $pm)) {
            $idOrImei = $pm[2];
            $up = $pdo->prepare("UPDATE warranty_claims SET status = ?, updated_at = NOW() WHERE id = ?");
            $up->execute([$newStatus, $idOrImei]);
        }

        $reply = "<div class='ai-action-card p-2.5 rounded-3 mb-2' style='background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac;'>";
        $reply .= "  <div class='d-flex align-items-center gap-2 mb-2'>";
        $reply .= "    <div style='width:32px; height:32px; border-radius:50%; background:#22c55e; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;'><i class='fa-solid fa-shield-halved'></i></div>";
        $reply .= "    <div>";
        $reply .= "      <strong class='text-success d-block' style='font-size:0.9rem;'>CẬP NHẬT TIẾN ĐỘ BẢO HÀNH THÀNH CÔNG!</strong>";
        $reply .= "      <small class='text-secondary'>Trạng thái mới: <strong>{$newStatus}</strong>.</small>";
        $reply .= "    </div>";
        $reply .= "  </div>";
        $reply .= "  <div><a href='warranties.php' class='btn btn-sm btn-outline-success rounded-pill px-3 py-1 text-decoration-none' style='font-size:0.75rem;'><i class='fa-solid fa-shield-halved me-1'></i>Xem Quản Lý Bảo Hành</a></div>";
        $reply .= "</div>";
    }

    return [
        'reply' => $reply,
        'suggestions' => [
            'Tra cứu phiếu bảo hành theo IMEI',
            'Chẩn đoán lỗi sọc màn hình & phôi OLED',
            'Chính sách bảo hành Apple VN/A'
        ]
    ];
}
}

// Thông tin định danh AI cho từng chức vụ
$roleConfigs = [
    'admin' => [
        'name' => 'V-Phone Executive AI',
        'title' => 'Cố Vấn Quản Trị & Chiến Lược Doanh Nghiệp',
        'badge' => 'Quản Trị Viên (Admin)',
        'avatar_icon' => 'fa-solid fa-crown',
        'color' => '#ef4444',
        'gradient' => 'linear-gradient(135deg, #ef4444, #8b5cf6)',
        'welcome' => 'Dạ xin kính chào <strong>Quản Trị Viên</strong>! Tôi là <strong>Executive AI</strong> - Cố vấn điều hành số cho ban lãnh đạo V-Phone Store 2026.<br><br>Tôi sẵn sàng báo cáo tức thì: <em>Doanh thu hôm nay, tổng kết đơn hàng, phân tích tồn kho, rà soát nhân sự & đề xuất chiến lược tăng trưởng</em>.',
        'suggestions' => [
            'Báo cáo doanh thu hôm nay',
            'Tình trạng đơn hàng chờ duyệt',
            'Cảnh báo tồn kho & máy sắp hết',
            'Top sản phẩm bán chạy nhất',
            'Đề xuất chiến lược tăng trưởng 2026'
        ]
    ],
    'sales' => [
        'name' => 'V-Phone Sales AI',
        'title' => 'Trợ Lý Bán Hàng, Đơn Hàng & Chốt Deal',
        'badge' => 'Nhân Viên Bán Hàng',
        'avatar_icon' => 'fa-solid fa-briefcase',
        'color' => '#3b82f6',
        'gradient' => 'linear-gradient(135deg, #2563eb, #06b6d4)',
        'welcome' => 'Chào bạn! Tôi là <strong>V-Phone Sales Pro AI</strong> - Trợ thủ đắc lực giúp bạn chốt deal, tra cứu đơn hàng và gia tăng doanh số.<br><br>Tôi có thể giúp bạn: <em>Tra cứu nhanh đơn hàng qua mã/SĐT, kiểm tra voucher còn hạn, kịch bản up-sell phụ kiện và mẫu tin nhắn gọi xác nhận đơn</em>.',
        'suggestions' => [
            'Tra cứu đơn hàng mới nhất',
            'Danh sách voucher đang hoạt động',
            'Gợi ý kịch bản up-sell sạc & tai nghe',
            'Mẫu tin nhắn gọi xác nhận đơn',
            'Xử lý khách muốn hủy đơn'
        ]
    ],
    'warehouse' => [
        'name' => 'V-Phone Warehouse AI',
        'title' => 'Trợ Lý Quản Trị Kho, Tồn Kho & IMEI',
        'badge' => 'Quản Lý Kho (Thủ Kho)',
        'avatar_icon' => 'fa-solid fa-boxes-stacked',
        'color' => '#f59e0b',
        'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)',
        'welcome' => 'Chào thủ kho! Tôi là <strong>V-Phone Warehouse AI</strong> - Chuyên gia kiểm kê, định mức tồn kho và kiểm soát hàng hóa.<br><br>Tôi sẵn sàng hỗ trợ: <em>Liệt kê máy sắp hết hàng (tồn ≤ 5), thống kê tổng giá trị kho, quy trình quét IMEI chuẩn và tiêu chuẩn đóng gói chống sốc</em>.',
        'suggestions' => [
            'Sản phẩm sắp hết hàng (tồn ≤ 5)',
            'Thống kê tổng giá trị tồn kho',
            'Tồn kho theo từng hãng máy',
            'Quy trình kiểm kê quét IMEI chuẩn',
            'Tiêu chuẩn đóng gói gửi điện thoại'
        ]
    ],
    'warranty' => [
        'name' => 'V-Phone Tech & Care AI',
        'title' => 'Trợ Lý Kỹ Thuật, Sửa Chữa & Bảo Hành',
        'badge' => 'Kỹ Thuật Viên & Bảo Hành',
        'avatar_icon' => 'fa-solid fa-shield-halved',
        'color' => '#10b981',
        'gradient' => 'linear-gradient(135deg, #10b981, #059669)',
        'welcome' => 'Chào kỹ thuật viên! Tôi là <strong>V-Phone Tech & Care AI</strong> - Trợ lý chẩn đoán phần cứng và quy trình bảo hành.<br><br>Tôi sẵn sàng hỗ trợ: <em>Tra cứu phiếu bảo hành theo IMEI, phân tích mã lỗi màn hình/pin/FaceID, chính sách 1 đổi 1 Apple/Samsung và bảng giá linh kiện</em>.',
        'suggestions' => [
            'Tra cứu phiếu bảo hành theo IMEI',
            'Chẩn đoán lỗi sọc màn hình & phôi OLED',
            'Chẩn đoán lỗi chai phồng pin & IC sạc',
            'Chính sách bảo hành Apple VN/A',
            'Bảng giá tham khảo thay linh kiện'
        ]
    ],
    'support' => [
        'name' => 'V-Phone Support AI',
        'title' => 'Trợ Lý CSKH & Xử Lý Phản Hồi 5 Sao',
        'badge' => 'Chuyên Viên CSKH',
        'avatar_icon' => 'fa-solid fa-headset',
        'color' => '#ec4899',
        'gradient' => 'linear-gradient(135deg, #ec4899, #8b5cf6)',
        'welcome' => 'Chào bạn! Tôi là <strong>V-Phone Support AI</strong> - Chuyên viên hỗ trợ kịch bản giao tiếp và nâng cao trải nghiệm khách hàng.<br><br>Tôi sẵn sàng hỗ trợ: <em>Kiểm tra yêu cầu gọi lại tư vấn mới, soạn mẫu trả lời đánh giá 1 sao & 5 sao, kịch bản xoa dịu khách giận dữ và quy trình đổi trả</em>.',
        'suggestions' => [
            'Khách hàng yêu cầu gọi lại mới nhất',
            'Đánh giá sản phẩm chưa phản hồi',
            'Mẫu trả lời đánh giá 1 sao chuyên nghiệp',
            'Mẫu cảm ơn đánh giá 5 sao',
            'Kịch bản xoa dịu khách khiếu nại'
        ]
    ],
    'customer' => [
        'name' => 'V-Phone Cyber AI',
        'title' => 'Chuyên Gia Tư Vấn Bán Hàng Số 24/7',
        'badge' => 'Tư Vấn Khách Hàng',
        'avatar_icon' => 'fa-solid fa-robot',
        'color' => '#6366f1',
        'gradient' => 'linear-gradient(135deg, #6366f1, #3b82f6)',
        'welcome' => 'Dạ em chào anh/chị! Em là <strong>V-Phone Cyber AI</strong> - Chuyên gia tư vấn mua sắm điện thoại chính hãng 24/7.<br><br>Hệ thống sẵn sàng hỗ trợ: <em>Báo giá các dòng iPhone, Samsung, Xiaomi, thủ tục trả góp 0%, chính sách đồng kiểm mở hộp và bảo hành 1 đổi 1</em>.',
        'suggestions' => [
            'Bảng giá iPhone mới nhất',
            'Samsung Galaxy AI cao cấp',
            'Thủ tục trả góp 0% duyệt online',
            'Chính sách bảo hành 1 đổi 1 trong 30 ngày',
            'Đồng kiểm mở hộp trước khi thanh toán'
        ]
    ]
];

$activeConfig = $roleConfigs[$requestedRole] ?? $roleConfigs['customer'];

// Nếu người dùng gửi tin nhắn rỗng -> Trả về câu chào mừng và gợi ý mặc định
if ($userMessage === '' || preg_match('/^(chao|hello|hi|alo|chao bot|start|help|tro giup)$/i', $cleanText)) {
    echo json_encode([
        'success' => true,
        'role' => $requestedRole,
        'role_title' => $activeConfig['name'],
        'role_badge' => $activeConfig['badge'],
        'reply' => $activeConfig['welcome'],
        'suggestions' => $activeConfig['suggestions'],
        'timestamp' => date('H:i')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$reply = '';
$suggestions = [];

// =========================================================================
// XỬ LÝ THEO TỪNG CHỨC VỤ (ROLE ENGINE)
// =========================================================================

switch ($requestedRole) {

    // =====================================================================
    // 1. QUẢN TRỊ VIÊN (ADMIN)
    // =====================================================================
    case 'admin':
        // -----------------------------------------------------------------
        // A. CẤP QUYỀN CHATBOX AI CHO NHÂN SỰ THEO CHỨC VỤ (GRANT ACTION)
        // -----------------------------------------------------------------
        $isGrantAction = (
            preg_match('/(cap|mo|bat|cho phep|kich hoat|phan quyen|them quyen|grant|enable)\b.*(chatbox|boxchat|box chat|chat box|tro ly ai|quyen ai|\bai\b)/i', $cleanText) ||
            preg_match('/(chatbox|boxchat|\bai\b).*cho.*(nhan vien|nhan su|kho|sales|ban hang|bao hanh|ky thuat|cskh|tat ca)/i', $cleanText) ||
            preg_match('/(cap|mo|bat|cho phep|kich hoat)\b.*(tuong ung cho|quyen ai)/i', $cleanText)
        );

        // -----------------------------------------------------------------
        // B. THU HỒI QUYỀN CHATBOX AI (REVOKE ACTION)
        // -----------------------------------------------------------------
        $isRevokeAction = (
            preg_match('/(thu hoi|khoa|tat|huy|xoa|cam|disable|revoke)\b.*(chatbox|boxchat|box chat|chat box|tro ly ai|quyen ai|\bai\b)/i', $cleanText) ||
            preg_match('/(thu hoi|huy quyen)\b.*(chatbox|ai|quyen ai)/i', $cleanText)
        );

        // -----------------------------------------------------------------
        // C. KIỂM TRA / DANH SÁCH PHÂN QUYỀN AI TOÀN HỆ THỐNG (STATUS CHECK)
        // -----------------------------------------------------------------
        $isCheckAction = (
            preg_match('/(kiem tra|danh sach|xem ai|ai co quyen|nhan vien nao co|ai duoc dung|trang thai).*(chatbox|boxchat|quyen ai|\bai\b|tro ly)/i', $cleanText) ||
            preg_match('/(danh sach|kiem tra).*(nhan su|nhan vien).*(chatbox|\bai\b|tro ly)/i', $cleanText) ||
            preg_match('/^(ai co quyen|ai duoc dung|danh sach quyen)$/i', $cleanText)
        );

        // -----------------------------------------------------------------
        // D. PHẢN HỒI KHI QUẢN TRỊ VIÊN HỎI "LÀ THÔNG MINH DỮ CHƯA?" HOẶC ĐẶT CÂU HỎI VỀ ĐỘ THÔNG MINH
        // -----------------------------------------------------------------
        $isSmartQuery = (
            preg_match('/(thong minh du chua|du chua|thong minh chua|sao dot|sao do|sao khong lam|co lam duoc khong|thong minh kieu gi)/i', $cleanText) ||
            preg_match('/^(thong minh|thong minh du chua|du chua|sao vay)$/i', $cleanText)
        );

        if ($isGrantAction) {
            $targetRole = null;
            $targetRoleLabel = '';

            if (preg_match('/(kho|thu kho|quan ly kho|warehouse|ton kho)/i', $cleanText)) {
                $targetRole = 'warehouse';
                $targetRoleLabel = 'Nhân viên Quản lý Kho (Thủ kho)';
            } elseif (preg_match('/(ban hang|sales|kinh doanh|don hang)/i', $cleanText)) {
                $targetRole = 'sales';
                $targetRoleLabel = 'Nhân viên Bán hàng & Đơn hàng';
            } elseif (preg_match('/(bao hanh|ky thuat|sua chua|warranty|tech)/i', $cleanText)) {
                $targetRole = 'warranty';
                $targetRoleLabel = 'Kỹ thuật viên & Bảo hành';
            } elseif (preg_match('/(cskh|cham soc|ho tro|support)/i', $cleanText)) {
                $targetRole = 'support';
                $targetRoleLabel = 'Chuyên viên CSKH & Đa kênh';
            } elseif (preg_match('/(tat ca|toan bo|het|moi nhan vien|cac nhan vien|tat ca nhan su)/i', $cleanText)) {
                $targetRole = 'all';
                $targetRoleLabel = 'Toàn bộ nhân sự hệ thống';
            }

            // Lấy danh sách nhân sự mục tiêu
            if ($targetRole === 'warehouse') {
                $q = $pdo->query("SELECT id, fullname, email, staff_role, permissions FROM users WHERE role = 2 AND (staff_role = 'warehouse' OR email LIKE '%kho%' OR fullname LIKE '%kho%')");
                $targetUsers = $q->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($targetRole === 'sales') {
                $q = $pdo->query("SELECT id, fullname, email, staff_role, permissions FROM users WHERE role = 2 AND (staff_role = 'sales' OR email LIKE '%sales%' OR fullname LIKE '%ban hang%' OR fullname LIKE '%ban%')");
                $targetUsers = $q->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($targetRole === 'warranty') {
                $q = $pdo->query("SELECT id, fullname, email, staff_role, permissions FROM users WHERE role = 2 AND (staff_role IN ('warranty', 'support') OR email LIKE '%baohanh%' OR fullname LIKE '%bao hanh%' OR fullname LIKE '%ky thuat%')");
                $targetUsers = $q->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($targetRole === 'support') {
                $q = $pdo->query("SELECT id, fullname, email, staff_role, permissions FROM users WHERE role = 2 AND (staff_role = 'support' OR email LIKE '%cskh%' OR fullname LIKE '%cham soc%')");
                $targetUsers = $q->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($targetRole === 'all') {
                $q = $pdo->query("SELECT id, fullname, email, staff_role, permissions FROM users WHERE role = 2");
                $targetUsers = $q->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Thử tìm theo tên hoặc email cụ thể
                $allStaffQuery = $pdo->query("SELECT id, fullname, email, staff_role, permissions FROM users WHERE role = 2");
                $allStaffList = $allStaffQuery->fetchAll(PDO::FETCH_ASSOC);
                $targetUsers = [];
                foreach ($allStaffList as $sm) {
                    $cleanName = removeVietnameseAccents(mb_strtolower($sm['fullname'], 'UTF-8'));
                    $cleanMail = mb_strtolower($sm['email'], 'UTF-8');
                    $nameWords = array_filter(explode(' ', $cleanName), fn($w) => mb_strlen($w) >= 3);
                    $matched = false;
                    foreach ($nameWords as $w) {
                        if (str_contains($cleanText, $w)) { $matched = true; break; }
                    }
                    if ($matched || str_contains($cleanText, $cleanMail)) {
                        $targetUsers[] = $sm;
                        $targetRoleLabel = $sm['fullname'];
                        break;
                    }
                }
                // Nếu vẫn không chỉ định ai -> mặc định cấp cho nhân viên kho nếu có từ kho, hoặc toàn bộ
                if (empty($targetUsers)) {
                    $targetRole = 'all';
                    $targetRoleLabel = 'Toàn bộ nhân sự';
                    $q = $pdo->query("SELECT id, fullname, email, staff_role, permissions FROM users WHERE role = 2");
                    $targetUsers = $q->fetchAll(PDO::FETCH_ASSOC);
                }
            }

            if (empty($targetUsers)) {
                $reply = "<div class='p-2 rounded bg-warning bg-opacity-10 border border-warning text-dark small'>";
                $reply .= "<i class='fa-solid fa-triangle-exclamation text-warning me-1'></i> <strong>Chưa tìm thấy nhân sự phù hợp!</strong><br>";
                $reply .= "Hệ thống hiện chưa có tài khoản nhân sự thuộc nhóm <em>" . htmlspecialchars($targetRoleLabel) . "</em>. Bạn có thể vào mục <strong>Nhân sự</strong> để thêm tài khoản.";
                $reply .= "</div>";
                $suggestions = ['Kiểm tra danh sách nhân viên có quyền AI', 'Cấp chatbox cho tất cả nhân viên', 'Báo cáo doanh thu hôm nay'];
            } else {
                $grantedCount = 0;
                $alreadyCount = 0;
                $staffItemsHtml = '';

                foreach ($targetUsers as $u) {
                    $raw = $u['permissions'] ?? '';
                    $perms = [];
                    if (!empty($raw)) {
                        $perms = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
                    }

                    $sRole = strtolower(trim((string)($u['staff_role'] ?? '')));
                    if ($sRole === 'warehouse') {
                        $boxName = 'V-Phone Warehouse AI';
                        $boxIcon = 'fa-solid fa-boxes-stacked';
                        $boxBadge = 'Quản Lý Kho (Thủ kho)';
                        $boxColor = '#f59e0b';
                        $boxDesc = 'Kiểm kê kho, cảnh báo tồn ≤ 5 máy, quy trình quét IMEI chuẩn';
                    } elseif ($sRole === 'warranty') {
                        $boxName = 'V-Phone Tech & Care AI';
                        $boxIcon = 'fa-solid fa-shield-halved';
                        $boxBadge = 'Kỹ Thuật & Bảo Hành';
                        $boxColor = '#10b981';
                        $boxDesc = 'Chẩn đoán phôi OLED, IC sạc, chính sách 1 đổi 1 Apple VN/A';
                    } elseif ($sRole === 'support') {
                        $boxName = 'V-Phone Support AI';
                        $boxIcon = 'fa-solid fa-headset';
                        $boxBadge = 'Chuyên Viên CSKH';
                        $boxColor = '#ec4899';
                        $boxDesc = 'Kịch bản xoa dịu khách, phản hồi đánh giá 1 sao/5 sao';
                    } else {
                        $boxName = 'V-Phone Sales Pro AI';
                        $boxIcon = 'fa-solid fa-briefcase';
                        $boxBadge = 'Nhân Viên Bán Hàng';
                        $boxColor = '#3b82f6';
                        $boxDesc = 'Tra cứu đơn hàng, kịch bản up-sell phụ kiện, gọi điện chốt đơn';
                    }

                    $isNewlyGranted = false;
                    if (!in_array('ai_assistant', $perms, true)) {
                        $perms[] = 'ai_assistant';
                        $newPermsJson = json_encode(array_values(array_unique($perms)), JSON_UNESCAPED_UNICODE);
                        $upStmt = $pdo->prepare("UPDATE users SET permissions = ? WHERE id = ?");
                        $upStmt->execute([$newPermsJson, $u['id']]);
                        $grantedCount++;
                        $isNewlyGranted = true;
                    } else {
                        $alreadyCount++;
                    }

                    $statusTag = $isNewlyGranted 
                        ? "<span class='badge bg-success'><i class='fa-solid fa-bolt me-1'></i>Vừa kích hoạt mới</span>" 
                        : "<span class='badge bg-primary bg-opacity-75'><i class='fa-solid fa-check me-1'></i>Đang hoạt động</span>";

                    $staffItemsHtml .= "<div class='p-2 mb-2 rounded bg-white border shadow-xs'>";
                    $staffItemsHtml .= "  <div class='d-flex align-items-center justify-content-between mb-1'>";
                    $staffItemsHtml .= "    <strong>" . htmlspecialchars($u['fullname']) . "</strong>";
                    $staffItemsHtml .= "    {$statusTag}";
                    $staffItemsHtml .= "  </div>";
                    $staffItemsHtml .= "  <div class='small text-muted mb-1'><i class='fa-regular fa-envelope me-1'></i>" . htmlspecialchars($u['email']) . " &bull; <span class='badge bg-light text-dark border'>{$boxBadge}</span></div>";
                    $staffItemsHtml .= "  <div class='small p-1.5 rounded' style='background:#f8fafc; border:1px dashed #cbd5e1;'>";
                    $staffItemsHtml .= "    <span style='color:{$boxColor}; font-weight:700;'><i class='{$boxIcon} me-1'></i>{$boxName}</span>";
                    $staffItemsHtml .= "    <div class='text-secondary' style='font-size:0.75rem;'>{$boxDesc}</div>";
                    $staffItemsHtml .= "  </div>";
                    $staffItemsHtml .= "</div>";
                }

                $reply = "<div class='ai-action-card p-2.5 rounded-3 mb-2' style='background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac;'>";
                $reply .= "  <div class='d-flex align-items-center gap-2 mb-2'>";
                $reply .= "    <div style='width:32px; height:32px; border-radius:50%; background:#22c55e; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;'><i class='fa-solid fa-check'></i></div>";
                $reply .= "    <div>";
                $reply .= "      <strong class='text-success d-block' style='font-size:0.9rem;'>THI HÀNH LỆNH CẤP QUYỀN AI THÀNH CÔNG!</strong>";
                $reply .= "      <small class='text-secondary'>Đã cập nhật cơ sở dữ liệu phân quyền thời gian thực.</small>";
                $reply .= "    </div>";
                $reply .= "  </div>";
                $reply .= "  <div class='small text-dark mb-2'>Đã xử lý cho <strong>" . count($targetUsers) . " nhân sự</strong> (Cấp mới: <strong>{$grantedCount}</strong>, Đã có sẵn: <strong>{$alreadyCount}</strong>):</div>";
                $reply .= $staffItemsHtml;
                $reply .= "  <div class='small text-muted p-1.5 rounded bg-white border mt-2' style='font-size:0.75rem;'>";
                $reply .= "    <i class='fa-solid fa-circle-info text-primary me-1'></i> <strong>Đặc quyền:</strong> Nhân sự này khi đăng nhập vào hệ thống quản trị sẽ nhìn thấy <strong>DUY NHẤT 1 Chatbox AI chuyên biệt theo đúng vị trí của mình</strong> ở góc dưới, không bị hiển thị thừa các tab của chức vụ khác.";
                $reply .= "  </div>";
                $reply .= "</div>";

                $suggestions = [
                    'Kiểm tra danh sách nhân viên có quyền AI',
                    'Thu hồi chatbox của nhân viên kho',
                    'Báo cáo doanh thu hôm nay',
                    'Cảnh báo tồn kho & máy sắp hết'
                ];
            }
        }
        elseif ($isRevokeAction) {
            $targetRole = null;
            $targetRoleLabel = '';

            if (preg_match('/(kho|thu kho|quan ly kho|warehouse|ton kho)/i', $cleanText)) {
                $targetRole = 'warehouse';
                $targetRoleLabel = 'Nhân viên Quản lý Kho';
            } elseif (preg_match('/(ban hang|sales|kinh doanh|don hang)/i', $cleanText)) {
                $targetRole = 'sales';
                $targetRoleLabel = 'Nhân viên Bán hàng';
            } elseif (preg_match('/(bao hanh|ky thuat|sua chua|warranty|tech)/i', $cleanText)) {
                $targetRole = 'warranty';
                $targetRoleLabel = 'Kỹ thuật viên Bảo hành';
            } elseif (preg_match('/(cskh|cham soc|ho tro|support)/i', $cleanText)) {
                $targetRole = 'support';
                $targetRoleLabel = 'Chuyên viên CSKH';
            } elseif (preg_match('/(tat ca|toan bo|het|moi nhan vien|cac nhan vien|tat ca nhan su)/i', $cleanText)) {
                $targetRole = 'all';
                $targetRoleLabel = 'Toàn bộ nhân sự';
            }

            if ($targetRole === 'warehouse') {
                $q = $pdo->query("SELECT id, fullname, email, staff_role, permissions FROM users WHERE role = 2 AND (staff_role = 'warehouse' OR email LIKE '%kho%' OR fullname LIKE '%kho%')");
            } elseif ($targetRole === 'sales') {
                $q = $pdo->query("SELECT id, fullname, email, staff_role, permissions FROM users WHERE role = 2 AND (staff_role = 'sales' OR email LIKE '%sales%' OR fullname LIKE '%ban hang%' OR fullname LIKE '%ban%')");
            } elseif ($targetRole === 'warranty') {
                $q = $pdo->query("SELECT id, fullname, email, staff_role, permissions FROM users WHERE role = 2 AND (staff_role IN ('warranty', 'support') OR email LIKE '%baohanh%' OR fullname LIKE '%bao hanh%' OR fullname LIKE '%ky thuat%')");
            } elseif ($targetRole === 'support') {
                $q = $pdo->query("SELECT id, fullname, email, staff_role, permissions FROM users WHERE role = 2 AND (staff_role = 'support' OR email LIKE '%cskh%' OR fullname LIKE '%cham soc%')");
            } else {
                $q = $pdo->query("SELECT id, fullname, email, staff_role, permissions FROM users WHERE role = 2");
            }
            $targetUsers = $q->fetchAll(PDO::FETCH_ASSOC);

            $revokedCount = 0;
            $revokedHtml = '';
            foreach ($targetUsers as $u) {
                $raw = $u['permissions'] ?? '';
                $perms = !empty($raw) ? (is_array($raw) ? $raw : (json_decode($raw, true) ?: [])) : [];
                if (in_array('ai_assistant', $perms, true)) {
                    $perms = array_values(array_filter($perms, fn($p) => $p !== 'ai_assistant'));
                    $newPermsJson = json_encode($perms, JSON_UNESCAPED_UNICODE);
                    $upStmt = $pdo->prepare("UPDATE users SET permissions = ? WHERE id = ?");
                    $upStmt->execute([$newPermsJson, $u['id']]);
                    $revokedCount++;
                    $revokedHtml .= "• <strong>" . htmlspecialchars($u['fullname']) . "</strong> (" . htmlspecialchars($u['email']) . ")<br>";
                }
            }

            $reply = "<div class='ai-action-card p-2.5 rounded-3 mb-2' style='background: linear-gradient(135deg, #fef2f2, #fff1f2); border: 1px solid #fecaca;'>";
            $reply .= "  <div class='d-flex align-items-center gap-2 mb-2'>";
            $reply .= "    <div style='width:32px; height:32px; border-radius:50%; background:#ef4444; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;'><i class='fa-solid fa-lock'></i></div>";
            $reply .= "    <div>";
            $reply .= "      <strong class='text-danger d-block' style='font-size:0.9rem;'>ĐÃ THU HỒI QUYỀN CHATBOX AI!</strong>";
            $reply .= "      <small class='text-secondary'>Đã xóa quyền <code>ai_assistant</code> khỏi tài khoản mục tiêu.</small>";
            $reply .= "    </div>";
            $reply .= "  </div>";
            if ($revokedCount > 0) {
                $reply .= "  <div class='small text-dark mb-1'>Đã thu hồi thành công đối với <strong>{$revokedCount} nhân viên</strong>:</div>";
                $reply .= "  <div class='p-2 rounded bg-white border small mb-2'>{$revokedHtml}</div>";
                $reply .= "  <div class='small text-muted' style='font-size:0.75rem;'><i class='fa-solid fa-info-circle me-1 text-danger'></i> Boxchat AI sẽ tự động ẩn và không hiển thị trên màn hình quản trị của các nhân viên này.</div>";
            } else {
                $reply .= "  <div class='small text-muted'>Nhóm nhân sự này hiện chưa được cấp quyền AI nên không có tài khoản nào bị ảnh hưởng.</div>";
            }
            $reply .= "</div>";

            $suggestions = [
                'Cấp chatbox tương ứng cho các nhân viên kho',
                'Kiểm tra danh sách nhân viên có quyền AI',
                'Cấp chatbox cho tất cả nhân viên',
                'Báo cáo doanh thu hôm nay'
            ];
        }
        elseif ($isCheckAction) {
            $q = $pdo->query("SELECT id, fullname, email, role, staff_role, permissions FROM users WHERE role IN (1, 2) ORDER BY role ASC, id ASC");
            $allStaff = $q->fetchAll(PDO::FETCH_ASSOC);

            $reply = "<div class='ai-action-card p-2.5 rounded-3 mb-2' style='background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 1px solid #cbd5e1;'>";
            $reply .= "  <div class='d-flex align-items-center gap-2 mb-2'>";
            $reply .= "    <div style='width:32px; height:32px; border-radius:50%; background:#6366f1; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;'><i class='fa-solid fa-users-gear'></i></div>";
            $reply .= "    <div>";
            $reply .= "      <strong class='text-dark d-block' style='font-size:0.9rem;'>TRẠNG THÁI PHÂN QUYỀN CHATBOX AI TOÀN HỆ THỐNG</strong>";
            $reply .= "      <small class='text-secondary'>Báo cáo quyền truy cập Trợ lý AI theo chức vụ.</small>";
            $reply .= "    </div>";
            $reply .= "  </div>";

            foreach ($allStaff as $u) {
                $isAdmin = ((int)$u['role'] === 1);
                $raw = $u['permissions'] ?? '';
                $perms = !empty($raw) ? (is_array($raw) ? $raw : (json_decode($raw, true) ?: [])) : [];
                $hasAi = $isAdmin || in_array('ai_assistant', $perms, true);

                $sRole = strtolower(trim((string)($u['staff_role'] ?? '')));
                if ($isAdmin) {
                    $boxName = 'V-Phone Executive AI (Quản Trị Viên)';
                    $badgeRole = 'Admin Cấp Cao';
                } elseif ($sRole === 'warehouse') {
                    $boxName = 'V-Phone Warehouse AI (Kho & IMEI)';
                    $badgeRole = 'Thủ Kho';
                } elseif ($sRole === 'warranty') {
                    $boxName = 'V-Phone Tech & Care AI (Bảo Hành)';
                    $badgeRole = 'Kỹ Thuật';
                } elseif ($sRole === 'support') {
                    $boxName = 'V-Phone Support AI (CSKH & Đánh giá)';
                    $badgeRole = 'CSKH';
                } else {
                    $boxName = 'V-Phone Sales Pro AI (Bán Hàng)';
                    $badgeRole = 'Kinh Doanh';
                }

                $statusBadge = $hasAi
                    ? "<span class='badge bg-success'><i class='fa-solid fa-circle-check me-1'></i>Được dùng AI</span>"
                    : "<span class='badge bg-secondary'><i class='fa-solid fa-lock me-1'></i>Chưa cấp</span>";

                $reply .= "  <div class='p-2 mb-1.5 rounded bg-white border small'>";
                $reply .= "    <div class='d-flex align-items-center justify-content-between'>";
                $reply .= "      <strong>" . htmlspecialchars($u['fullname']) . "</strong>";
                $reply .= "      {$statusBadge}";
                $reply .= "    </div>";
                $reply .= "    <div class='text-muted' style='font-size:0.75rem;'>" . htmlspecialchars($u['email']) . " &bull; <span class='badge bg-light text-dark border'>{$badgeRole}</span></div>";
                if ($hasAi) {
                    $reply .= "    <div class='text-primary mt-1' style='font-size:0.75rem;'><i class='fa-solid fa-robot me-1'></i><strong>{$boxName}</strong></div>";
                }
                $reply .= "  </div>";
            }
            $reply .= "</div>";

            $suggestions = [
                'Cấp chatbox tương ứng cho các nhân viên kho',
                'Cấp chatbox cho tất cả nhân viên',
                'Thu hồi chatbox của nhân viên kho',
                'Báo cáo doanh thu hôm nay'
            ];
        }
        elseif ($isSmartQuery) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: linear-gradient(135deg, #faf5ff, #f3e8ff); border: 1px solid #d8b4fe;'>";
            $reply .= "  <div class='d-flex align-items-center gap-2 mb-2'>";
            $reply .= "    <div style='width:34px; height:34px; border-radius:50%; background:#9333ea; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;'><i class='fa-solid fa-brain fs-6'></i></div>";
            $reply .= "    <div>";
            $reply .= "      <strong style='color:#7e22ce; font-size:0.9rem;' class='d-block'>DẠ EM ĐÃ SẴN SÀNG THỰC THI MỆNH LỆNH!</strong>";
            $reply .= "      <small class='text-secondary'>Executive AI đã nâng cấp năng lực điều phối hành động trực tiếp.</small>";
            $reply .= "    </div>";
            $reply .= "  </div>";
            $reply .= "  <div class='small text-dark mb-2'>";
            $reply .= "    Dạ em xin lỗi Quản Trị Viên! Câu trả lời trước đó mang tính thông tin chung. Hiện tại em đã được <strong>liên kết trực tiếp với cơ sở dữ liệu V-Phone</strong> để thực thi ngay các mệnh lệnh điều hành:";
            $reply .= "  </div>";
            $reply .= "  <div class='p-2 rounded bg-white border mb-2 small'>";
            $reply .= "    • <strong>Cấp chatbox AI:</strong> Ra lệnh <em>'Cấp chatbox tương ứng cho các nhân viên kho'</em> hoặc <em>'Cấp chatbox cho nhân viên bán hàng / toàn bộ nhân viên'</em> → Em sẽ cấp quyền <code>ai_assistant</code> và gán đúng Chatbox AI theo vị trí của nhân sự!<br>";
            $reply .= "    • <strong>Thu hồi quyền AI:</strong> Ra lệnh <em>'Thu hồi chatbox của nhân viên kho'</em>.<br>";
            $reply .= "    • <strong>Kiểm tra trạng thái:</strong> Ra lệnh <em>'Kiểm tra danh sách nhân viên có quyền AI'</em>.";
            $reply .= "  </div>";
            $reply .= "  <div class='text-primary fw-semibold small'>";
            $reply .= "    👉 Bạn hãy thử bấm vào các nút gợi ý bên dưới hoặc gõ lại lệnh để trải nghiệm ngay độ thông minh thực chiến nhé!";
            $reply .= "  </div>";
            $reply .= "</div>";

            $suggestions = [
                'Cấp chatbox tương ứng cho các nhân viên kho',
                'Kiểm tra danh sách nhân viên có quyền AI',
                'Cấp chatbox cho tất cả nhân viên',
                'Báo cáo doanh thu hôm nay'
            ];
        }
        // E. THỰC THI LỆNH KHO (NHẬP HÀNG, THÊM HÀNG, XUẤT KHO)
        elseif ($invRes = handleInventoryAction($pdo, $cleanText, $userMessage)) {
            $reply = $invRes['reply'];
            $suggestions = $invRes['suggestions'];
        }
        // F. THỰC THI LỆNH ĐƠN HÀNG (XÁC NHẬN, GIAO HÀNG, HOÀN THÀNH, HỦY)
        elseif ($ordRes = handleOrderStatusAction($pdo, $cleanText)) {
            $reply = $ordRes['reply'];
            $suggestions = $ordRes['suggestions'];
        }
        // G. THỰC THI LỆNH CẬP NHẬT GIÁ BÁN SẢN PHẨM
        elseif ($priceRes = handlePriceUpdateAction($pdo, $cleanText)) {
            $reply = $priceRes['reply'];
            $suggestions = $priceRes['suggestions'];
        }
        // H. THỰC THI LỆNH KHÓA / MỞ KHÓA TÀI KHOẢN NHÂN SỰ
        elseif ($accRes = handleAccountStatusAction($pdo, $cleanText)) {
            $reply = $accRes['reply'];
            $suggestions = $accRes['suggestions'];
        }
        // I. THỰC THI TẠO MÃ GIẢM GIÁ
        elseif ($vouchRes = handleVoucherAction($pdo, $cleanText)) {
            $reply = $vouchRes['reply'];
            $suggestions = $vouchRes['suggestions'];
        }
        elseif (preg_match('/(doanh thu|doanh so|tai chinh|tien|ban duoc bao nhieu|tong tien)/i', $cleanText)) {
            try {
                // Doanh thu hôm nay
                $todayStmt = $pdo->query("SELECT COALESCE(SUM(total_money), 0) AS rev, COUNT(*) AS ord_count FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'Đã hủy'");
                $todayData = $todayStmt->fetch(PDO::FETCH_ASSOC);

                // Doanh thu tháng này
                $monthStmt = $pdo->query("SELECT COALESCE(SUM(total_money), 0) AS rev, COUNT(*) AS ord_count FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status != 'Đã hủy'");
                $monthData = $monthStmt->fetch(PDO::FETCH_ASSOC);

                // Tổng doanh thu từ trước đến nay
                $allTimeStmt = $pdo->query("SELECT COALESCE(SUM(total_money), 0) AS rev, COUNT(*) AS ord_count FROM orders WHERE status != 'Đã hủy'");
                $allTimeData = $allTimeStmt->fetch(PDO::FETCH_ASSOC);

                // Thống kê phương thức thanh toán hôm nay
                $vietqrStmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'Đã thanh toán' AND DATE(created_at) = CURDATE()");
                $vietqrCount = (int)($vietqrStmt->fetchColumn() ?: 0);

                $reply = "<div class='ai-report-box'>";
                $reply .= "<div class='ai-report-header'><i class='fa-solid fa-chart-line text-danger me-2'></i><strong>BÁO CÁO DOANH THU & TÀI CHÍNH THỰC TẾ</strong></div>";
                $reply .= "<div class='ai-stat-grid mt-2 mb-3' style='display:grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 8px;'>";
                $reply .= "  <div class='ai-stat-card p-2 rounded-3' style='background:#fef2f2; border:1px solid #fecaca;'>";
                $reply .= "    <small class='text-muted d-block'>Hôm nay (" . date('d/m') . ")</small>";
                $reply .= "    <strong class='text-danger fs-6'>" . formatVND($todayData['rev']) . "</strong>";
                $reply .= "    <small class='text-secondary d-block'>({$todayData['ord_count']} đơn)</small>";
                $reply .= "  </div>";
                $reply .= "  <div class='ai-stat-card p-2 rounded-3' style='background:#f0fdf4; border:1px solid #bbf7d0;'>";
                $reply .= "    <small class='text-muted d-block'>Tháng " . date('m/Y') . "</small>";
                $reply .= "    <strong class='text-success fs-6'>" . formatVND($monthData['rev']) . "</strong>";
                $reply .= "    <small class='text-secondary d-block'>({$monthData['ord_count']} đơn)</small>";
                $reply .= "  </div>";
                $reply .= "  <div class='ai-stat-card p-2 rounded-3' style='background:#eff6ff; border:1px solid #bfdbfe;'>";
                $reply .= "    <small class='text-muted d-block'>Toàn thời gian</small>";
                $reply .= "    <strong class='text-primary fs-6'>" . formatVND($allTimeData['rev']) . "</strong>";
                $reply .= "    <small class='text-secondary d-block'>({$allTimeData['ord_count']} đơn)</small>";
                $reply .= "  </div>";
                $reply .= "</div>";
                $reply .= "<strong>Nhận định & Khuyến nghị điều hành:</strong><br>";
                $reply .= "• Có <strong>{$vietqrCount} đơn</strong> đã thanh toán chuẩn tự động qua VietQR / Chuyển khoản trong ngày.<br>";
                $reply .= "• Dòng tiền lưu thông ổn định. Khuyến khích quản trị viên duyệt nhanh các đơn đang chờ để shipper giao kịp trong ca chiều!";
                $reply .= "</div>";

                $suggestions = ['Tình trạng đơn hàng chờ duyệt', 'Cảnh báo tồn kho & máy sắp hết', 'Top sản phẩm bán chạy nhất'];
            } catch (\Throwable $e) {
                $reply = "Dạ, hiện chưa thể trích xuất số liệu doanh thu trực tiếp do: " . htmlspecialchars($e->getMessage());
            }
        }
        elseif (preg_match('/(don hang|cho xu ly|tinh trang don|cho duyet|giao hang)/i', $cleanText)) {
            try {
                $stmt = $pdo->query("SELECT status, COUNT(*) AS total FROM orders GROUP BY status");
                $statuses = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                $pending = $statuses['Chờ xử lý'] ?? 0;
                $processing = $statuses['Đang xử lý'] ?? 0;
                $delivering = $statuses['Đang giao hàng'] ?? 0;
                $completed = $statuses['Hoàn thành'] ?? 0;
                $cancelled = $statuses['Đã hủy'] ?? 0;
                $total = array_sum($statuses);

                $reply = "<strong>TỔNG QUAN TÌNH TRẠNG ĐƠN HÀNG TOÀN HỆ THỐNG:</strong><br><br>";
                $reply .= "• <span class='badge bg-warning text-dark px-2 py-1'>Chờ xử lý: {$pending}</span> (Cần sales xác nhận)<br>";
                $reply .= "• <span class='badge bg-info text-dark px-2 py-1'>Đang đóng gói: {$processing}</span><br>";
                $reply .= "• <span class='badge bg-primary px-2 py-1'>Đang giao hàng: {$delivering}</span><br>";
                $reply .= "• <span class='badge bg-success px-2 py-1'>Giao thành công: {$completed}</span><br>";
                $reply .= "• <span class='badge bg-danger px-2 py-1'>Đã hủy: {$cancelled}</span><br>";
                $reply .= "--------------------------------<br>";
                $reply .= "Tổng đơn tích lũy: <strong>{$total} đơn hàng</strong>.<br><br>";
                if ($pending > 0) {
                    $reply .= "<div class='p-2 rounded bg-warning bg-opacity-10 border border-warning text-dark small'>";
                    $reply .= "<i class='fa-solid fa-triangle-exclamation text-warning me-1'></i> Đang có <strong>{$pending} đơn mới</strong> cần nhân viên gọi điện xác nhận. Bạn có thể nhắc nhở đội ngũ kinh doanh vào mục <em>Quản lý Đơn hàng</em> để xử lý ngay!";
                    $reply .= "</div>";
                } else {
                    $reply .= "<span class='text-success fw-bold'><i class='fa-solid fa-check-circle me-1'></i>Tuyệt vời! Hiện không còn đơn nào bị ứ đọng ở trạng thái chờ duyệt.</span>";
                }
                $suggestions = ['Báo cáo doanh thu hôm nay', 'Top sản phẩm bán chạy nhất', 'Cảnh báo tồn kho & máy sắp hết'];
            } catch (\Throwable $e) {
                $reply = "Lỗi truy vấn dữ liệu đơn hàng: " . htmlspecialchars($e->getMessage());
            }
        }
        elseif (preg_match('/(ban chay|top ban|san pham hot|chay nhat)/i', $cleanText)) {
            try {
                $topStmt = $pdo->query("
                    SELECT od.product_name, SUM(od.quantity) AS total_sold, SUM(od.total_price) AS total_rev 
                    FROM order_details od 
                    JOIN orders o ON od.order_id = o.id 
                    WHERE o.status != 'Đã hủy' 
                    GROUP BY od.product_name 
                    ORDER BY total_sold DESC 
                    LIMIT 5
                ");
                $topProds = $topStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($topProds)) {
                    $reply = "<strong>TOP 5 SẢN PHẨM BÁN CHẠY NHẤT HỆ THỐNG:</strong><br><br>";
                    $rank = 1;
                    foreach ($topProds as $tp) {
                        $badgeColor = ($rank === 1) ? 'badge bg-danger' : (($rank === 2) ? 'badge bg-warning text-dark' : 'badge bg-secondary');
                        $reply .= "<div class='d-flex align-items-center justify-content-between p-2 mb-1 rounded bg-light border'>";
                        $reply .= "  <div>";
                        $reply .= "    <span class='{$badgeColor} me-1'>#{$rank}</span> <strong>" . htmlspecialchars($tp['product_name']) . "</strong>";
                        $reply .= "  </div>";
                        $reply .= "  <div class='text-end'>";
                        $reply .= "    <span class='text-primary fw-bold'>" . (int)$tp['total_sold'] . " máy</span><br>";
                        $reply .= "    <small class='text-muted'>" . formatVND($tp['total_rev']) . "</small>";
                        $reply .= "  </div>";
                        $reply .= "</div>";
                        $rank++;
                    }
                    $reply .= "<br><em>Khuyến nghị: Duy trì lượng tồn kho an toàn tối thiểu 10-15 máy cho top sản phẩm dẫn đầu để không bị đứt hàng!</em>";
                } else {
                    $reply = "Hiện chưa có đủ dữ liệu đơn hàng thành công để lập bảng xếp hạng bán chạy.";
                }
                $suggestions = ['Báo cáo doanh thu hôm nay', 'Cảnh báo tồn kho & máy sắp hết', 'Đề xuất chiến lược tăng trưởng 2026'];
            } catch (\Throwable $e) {
                $reply = "Lỗi trích xuất top bán chạy: " . htmlspecialchars($e->getMessage());
            }
        }
        elseif (preg_match('/(ton kho|canh bao|sap het|het hang)/i', $cleanText)) {
            try {
                $lowStmt = $pdo->query("SELECT id, name, quantity, price FROM products WHERE quantity <= 5 ORDER BY quantity ASC LIMIT 6");
                $lowList = $lowStmt->fetchAll(PDO::FETCH_ASSOC);
                $totalLowCount = (int)($pdo->query("SELECT COUNT(*) FROM products WHERE quantity <= 5")->fetchColumn() ?: 0);

                $reply = "<strong>CẢNH BÁO TỒN KHO - DÀNH CHO CẤP QUẢN TRỊ:</strong><br><br>";
                $reply .= "Hiện có <strong class='text-danger'>{$totalLowCount} sản phẩm</strong> đang có mức tồn kho báo động (≤ 5 máy):<br><br>";

                if (!empty($lowList)) {
                    foreach ($lowList as $item) {
                        $statusBadge = ($item['quantity'] == 0) 
                            ? "<span class='badge bg-danger'>HẾT HÀNG (0 máy)</span>" 
                            : "<span class='badge bg-warning text-dark'>Chỉ còn {$item['quantity']} máy</span>";
                        $reply .= "• <strong>" . htmlspecialchars($item['name']) . "</strong>: {$statusBadge} - Giá: " . formatVND($item['price']) . "<br>";
                    }
                    $reply .= "<br><div class='p-2 rounded bg-light border small'>";
                    $reply .= "<i class='fa-solid fa-lightbulb text-warning me-1'></i> <strong>Đề xuất quản trị:</strong> Bạn có thể chỉ đạo bộ phận Thủ kho lập phiếu nhập hàng bổ sung hoặc liên hệ nhà phân phối FPT Synnex/DGW để kịp đáp ứng nhu cầu cuối tuần.";
                    $reply .= "</div>";
                } else {
                    $reply .= "<span class='text-success'>Tất cả sản phẩm đều đang duy trì mức tồn kho an toàn (> 5 máy).</span>";
                }
                $suggestions = ['Báo cáo doanh thu hôm nay', 'Tình trạng đơn hàng chờ duyệt', 'Đề xuất chiến lược tăng trưởng 2026'];
            } catch (\Throwable $e) {
                $reply = "Lỗi kiểm tra kho: " . htmlspecialchars($e->getMessage());
            }
        }
        elseif (preg_match('/(chien luoc|tang truong|tang doanh so|marketing|loi khuyen|de xuat)/i', $cleanText)) {
            $reply = "<strong>CHIẾN LƯỢC KINH DOANH FLAGSHIP 2026 (KHUYẾN NGHỊ TỪ EXECUTIVE AI):</strong><br><br>";
            $reply .= "1. <span class='badge bg-primary'>Đẩy Mạnh Trả Góp 0% Duyệt Tức Thì</span><br>";
            $reply .= "   • Tỷ lệ khách mua qua thẻ tín dụng và CCCD chiếm tới 45% tổng giá trị đơn flagship.<br>";
            $reply .= "   • Tối ưu banner trả trước 0đ tại trang chủ để kích thích tỷ lệ chuyển đổi (CR) tăng 25%.<br><br>";
            $reply .= "2. <span class='badge bg-success'>Gia Tăng Biên Lợi Nhuận Bằng Phụ Kiện (Cross-sell)</span><br>";
            $reply .= "   • Tạo combo <em>'Siêu Cường Lực + Củ sạc 45W GaN'</em> giảm 30% khi mua kèm máy mới.<br>";
            $reply .= "   • Biên lợi nhuận phụ kiện đạt 40-50%, bù đắp cho biên máy cạnh tranh giá.<br><br>";
            $reply .= "3. <span class='badge bg-warning text-dark'>Chính Sách Thu Cũ Đổi Mới (Trade-in) Trợ Giá</span><br>";
            $reply .= "   • Trợ giá thêm 1 - 2 triệu đồng khi khách lên đời iPhone 17 / Galaxy S25.<br>";
            $reply .= "   • Tận dụng kho máy cũ thu vào để bán ra phân khúc 99% sinh lời kép.<br><br>";
            $reply .= "4. <span class='badge bg-danger'>Giữ Chân Khách Hàng Bằng Dịch Vụ 5 Sao</span><br>";
            $reply .= "   • Duy trì cam kết 1 đổi 1 trong 30 ngày và quà tặng sinh nhật qua voucher tự động.";
            $suggestions = ['Báo cáo doanh thu hôm nay', 'Top sản phẩm bán chạy nhất', 'Cảnh báo tồn kho & máy sắp hết'];
        }
        else {
            $reply = "<div class='p-3 rounded-3 bg-light border'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-crown text-danger fs-5'></i>"
                . "<strong class='text-dark'>V-PHONE EXECUTIVE AI - CỐ VẤN ĐIỀU HÀNH 2026</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-2'>Tôi hỗ trợ ban lãnh đạo thực thi các mệnh lệnh điều hành số tức thì:</p>"
                . "<ul class='small text-secondary ps-3 mb-2'>"
                . "<li><code>Cấp quyền AI cho kho</code> hoặc <code>Thu hồi quyền AI</code></li>"
                . "<li><code>Kiểm tra danh sách nhân viên có quyền AI</code></li>"
                . "<li><code>Báo cáo doanh thu hôm nay</code> hoặc <code>Doanh thu tháng này</code></li>"
                . "<li><code>Tình trạng đơn hàng chờ duyệt</code></li>"
                . "<li><code>Cảnh báo tồn kho & máy sắp hết</code></li>"
                . "<li><code>Đổi giá sản phẩm #1 thành 25000000</code></li>"
                . "<li><code>Khóa tài khoản #5</code> hoặc <code>Mở khóa tài khoản #5</code></li>"
                . "</ul>"
                . "<div class='small text-muted fst-italic'>Xin mời bạn gõ lệnh trực tiếp hoặc chọn gợi ý bên dưới.</div>"
                . "</div>";
            $suggestions = [
                'Cấp chatbox tương ứng cho các nhân viên kho',
                'Kiểm tra danh sách nhân viên có quyền AI',
                'Báo cáo doanh thu hôm nay',
                'Tình trạng đơn hàng chờ duyệt',
                'Cảnh báo tồn kho & máy sắp hết'
            ];
        }
        break;

    // =====================================================================
    // 2. NHÂN VIÊN BÁN HÀNG & ĐƠN HÀNG (SALES)
    // =====================================================================
    case 'sales':
        // 1. Thực thi chuyển trạng thái đơn hàng (Xác nhận, Giao hàng, Hoàn thành, Hủy)
        if ($ordRes = handleOrderStatusAction($pdo, $cleanText)) {
            $reply = $ordRes['reply'];
            $suggestions = $ordRes['suggestions'];
        }
        // 2. Thực thi tạo mã voucher mới
        elseif ($vouchRes = handleVoucherAction($pdo, $cleanText)) {
            $reply = $vouchRes['reply'];
            $suggestions = $vouchRes['suggestions'];
        }
        // 3. Tra cứu đơn hàng theo ID hoặc Số điện thoại
        elseif (preg_match('/(don|ma don|kiem tra don|tra cuu don|dh|#vp-?|vp-?)(\s*#?|\s+)([0-9]+)/i', $cleanText, $m) || preg_match('/(0[0-9]{9,10})/', $cleanText, $phoneMatch)) {
            $orderId = isset($m[3]) ? (int)$m[3] : 0;
            $phone = isset($phoneMatch[1]) ? trim($phoneMatch[1]) : '';

            try {
                if ($orderId > 0) {
                    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
                    $stmt->execute([$orderId]);
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM orders WHERE phone = ? ORDER BY id DESC LIMIT 1");
                    $stmt->execute([$phone]);
                }
                $ord = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($ord) {
                    // Lấy chi tiết sản phẩm trong đơn
                    $itemsStmt = $pdo->prepare("SELECT product_name, quantity, price, total_price FROM order_details WHERE order_id = ?");
                    $itemsStmt->execute([$ord['id']]);
                    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

                    $payBadge = ($ord['payment_status'] === 'Đã thanh toán') 
                        ? "<span class='badge bg-success'>Đã thanh toán</span>" 
                        : "<span class='badge bg-warning text-dark'>Chưa thanh toán ({$ord['payment_method']})</span>";

                    $reply = "<strong>KẾT QUẢ TRA CỨU ĐƠN HÀNG #VP-{$ord['id']}</strong>:<br><br>";
                    $reply .= "• Khách hàng: <strong>" . htmlspecialchars($ord['fullname']) . "</strong><br>";
                    $reply .= "• Số điện thoại: <strong>" . htmlspecialchars($ord['phone']) . "</strong><br>";
                    $reply .= "• Địa chỉ: " . htmlspecialchars($ord['address']) . "<br>";
                    $reply .= "• Tình trạng đơn: <span class='badge bg-primary'>" . htmlspecialchars($ord['status']) . "</span><br>";
                    $reply .= "• Thanh toán: {$payBadge}<br>";
                    $reply .= "• Thời gian đặt: " . htmlspecialchars($ord['created_at']) . "<br>";
                    if (!empty($ord['note'])) {
                        $reply .= "• Ghi chú của khách: <em>" . htmlspecialchars($ord['note']) . "</em><br>";
                    }
                    $reply .= "<br><strong>Sản phẩm trong đơn:</strong><br>";
                    foreach ($items as $it) {
                        $reply .= "- " . htmlspecialchars($it['product_name']) . " (SL: {$it['quantity']}) - " . formatVND($it['total_price']) . "<br>";
                    }
                    $reply .= "--------------------------------<br>";
                    $reply .= "Tổng tiền thanh toán: <strong class='text-danger fs-6'>" . formatVND($ord['total_money']) . "</strong><br><br>";
                    $reply .= "<a href='orders.php' class='btn btn-sm btn-primary rounded-pill px-3 py-1 text-white'><i class='fa-solid fa-arrow-up-right-from-square me-1'></i> Mở trang Đơn Hàng</a>";
                } else {
                    $reply = "Không tìm thấy đơn hàng nào khớp với thông tin tra cứu (Mã: #{$orderId}" . ($phone ? " / SĐT: {$phone}" : "") . "). Bạn vui lòng kiểm tra lại mã đơn!";
                }
                $suggestions = ['Danh sách voucher đang hoạt động', 'Mẫu tin nhắn gọi xác nhận đơn', 'Gợi ý kịch bản up-sell sạc & tai nghe'];
            } catch (\Throwable $e) {
                $reply = "Lỗi khi tra cứu đơn hàng: " . htmlspecialchars($e->getMessage());
            }
        }
        elseif (preg_match('/(voucher|ma giam|khuyen mai|code giam)/i', $cleanText)) {
            try {
                $vStmt = $pdo->query("SELECT code, discount_type, discount_value, minimum_order, expires_at FROM vouchers WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= NOW()) ORDER BY id DESC LIMIT 5");
                $vouchers = $vStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($vouchers)) {
                    $reply = "<strong>CÁC MÃ VOUCHER ĐANG HOẠT ĐỘNG (DÙNG ĐỂ CHỐT DEAL):</strong><br><br>";
                    foreach ($vouchers as $v) {
                        $valStr = ($v['discount_type'] === 'percent') ? ($v['discount_value'] . '%') : formatVND($v['discount_value']);
                        $minStr = ($v['minimum_order'] > 0) ? ("Đơn tối thiểu " . formatVND($v['minimum_order'])) : "Mọi đơn hàng";
                        $reply .= "• <code class='text-danger bg-white px-2 py-0.5 rounded border fw-bold fs-6'>{$v['code']}</code>: Giảm <strong>{$valStr}</strong> ({$minStr})<br>";
                    }
                    $reply .= "<br><em>Mẹo bán hàng: Khi khách còn lưỡng lự vì giá, bạn có thể tặng ngay mã giảm giá trên để kích thích khách thanh toán ngay trong 15 phút!</em>";
                } else {
                    $reply = "Hiện không có voucher nào đang kích hoạt trong hệ thống. Bạn có thể kiến nghị Admin tạo thêm mã khuyến mãi.";
                }
                $suggestions = ['Gợi ý kịch bản up-sell sạc & tai nghe', 'Mẫu tin nhắn gọi xác nhận đơn', 'Xử lý khách muốn hủy đơn'];
            } catch (\Throwable $e) {
                $reply = "Lỗi tra cứu voucher: " . htmlspecialchars($e->getMessage());
            }
        }
        elseif (preg_match('/(up-?sell|ban them|phu kien|kem theo|goi them)/i', $cleanText)) {
            $reply = "<strong>KỊCH BẢN UP-SELL PHỤ KIỆN TĂNG GIÁ TRỊ ĐƠN HÀNG (AOV):</strong><br><br>";
            $reply .= "<strong>1. Củ sạc nhanh chính hãng (Apple / Anker 35W - 45W):</strong><br>";
            $reply .= "<em>'Dạ anh/chị ơi, các dòng máy Flagship mới hiện nay trong hộp máy chỉ kèm cáp C-to-C chứ không có củ sạc. Hôm nay bên em đang có ưu đãi trợ giá 30% củ sạc nhanh 45W chuẩn GaN chỉ còn 490k, em lấy kèm luôn vào kiện hàng để anh/chị nhận máy là sạc dùng ngay nhé ạ?'</em><br><br>";
            $reply .= "<strong>2. Combo Kính Cường Lực KingKong + Ốp Lưng Magsafe:</strong><br>";
            $reply .= "<em>'Máy mới màn hình rất đẹp, em hỗ trợ dán sẵn kính cường lực KingKong 9D chống nhìn trộm và tặng ốp chống sốc giảm thêm 150k cho mình luôn nha anh!'</em><br><br>";
            $reply .= "<strong>3. Gói Bảo Hành Vàng 1 Đổi 1 Cả Rơi Vỡ Nước:</strong><br>";
            $reply .= "<em>'Chỉ với 890k (tương đương 2k/ngày), máy của anh/chị được bảo hiểm rơi vỡ đổi máy mới 100% trong 12 tháng không cần chờ thẩm định hãng!'</em>";
            $suggestions = ['Mẫu tin nhắn gọi xác nhận đơn', 'Xử lý khách muốn hủy đơn', 'Danh sách voucher đang hoạt động'];
        }
        elseif (preg_match('/(xac nhan|goi dien|mau tin nhan|kich ban goi|sms|zalo)/i', $cleanText)) {
            $reply = "<strong>MẪU KỊCH BẢN GỌI ĐIỆN & TIN NHẮN XÁC NHẬN ĐƠN HÀNG:</strong><br><br>";
            $reply .= "<strong>1. Kịch bản gọi điện xác nhận (30 giây):</strong><br>";
            $reply .= "<em>'Dạ em chào anh/chị [Tên Khách], em là [Tên Bạn] gọi từ tổng đài V-Phone Store ạ. Em thấy anh/chị vừa đặt thành công đơn hàng [Tên Máy] màu [Màu] trị giá [Số Tiền]. Em gọi xác nhận địa chỉ nhận hàng của mình tại [Địa chỉ]. Đơn của mình sẽ được bưu tá giao trong 1-2 ngày tới, anh/chị được đồng kiểm mở hộp test máy trước khi thanh toán tiền mặt nha anh!'</em><br><br>";
            $reply .= "<strong>2. Mẫu tin nhắn SMS / Zalo gửi khách:</strong><br>";
            $reply .= "<div class='p-2 rounded bg-light border font-monospace small'>";
            $reply .= "[V-Phone Store] Dạ chào bạn {Tên_Khách}, đơn hàng #{Mã_Đơn} mua {Tên_Máy} đã được kiểm định và đóng gói cẩn thận. Bưu tá sẽ liên hệ giao hàng trong 24-48h tới. Bạn được quyền mở hộp đồng kiểm trước khi thanh toán. Hotline hỗ trợ 24/7: 1800 6868.";
            $reply .= "</div>";
            $suggestions = ['Gợi ý kịch bản up-sell sạc & tai nghe', 'Xử lý khách muốn hủy đơn', 'Tra cứu đơn hàng mới nhất'];
        }
        elseif (preg_match('/(huy don|khach doi y|doi tra|tra hang)/i', $cleanText)) {
            $reply = "<strong>QUY TRÌNH & KỊCH BẢN XỬ LÝ KHI KHÁCH MUỐN HỦY ĐƠN:</strong><br><br>";
            $reply .= "<strong>Bước 1: Lắng nghe lý do khách muốn hủy</strong> (do đổi ý, thấy chỗ khác rẻ hơn, hay kẹt tài chính).<br><br>";
            $reply .= "<strong>Bước 2: Xử lý theo từng tình huống:</strong><br>";
            $reply .= "• <em>Nếu khách chê giá cao:</em> 'Dạ V-Phone Store cam kết hàng chính hãng VN/A mới 100% nguyên seal chưa active, đi kèm gói bảo hành 1 đổi 1 trong 30 ngày. Em xin phép áp dụng riêng cho mình voucher VIP giảm thêm 200.000đ để giữ máy cho mình nhé ạ!'<br>";
            $reply .= "• <em>Nếu khách kẹt tài chính:</em> Tư vấn chuyển sang trả góp 0% duyệt qua CCCD chỉ cần trả trước từ 0 đồng.<br>";
            $reply .= "• <em>Nếu khách muốn đổi màu/bản dung lượng:</em> Hỗ trợ đổi sản phẩm ngay trong đơn mà không cần đặt lại.<br><br>";
            $reply .= "<strong>Bước 3:</strong> Nếu khách vẫn kiên quyết hủy, giữ thái độ niềm nở: <em>'Dạ em hiểu ạ, em xin phép hủy đơn này giúp mình. Hẹn gặp lại anh/chị trong các đợt khuyến mãi sắp tới của V-Phone nhé!'</em>";
            $suggestions = ['Tra cứu đơn hàng mới nhất', 'Danh sách voucher đang hoạt động', 'Gợi ý kịch bản up-sell sạc & tai nghe'];
        }
        // NHẬN DIỆN VÀ TỪ CHỐI CÂU HỎI NGOÀI CHUYÊN MÔN BÁN HÀNG:
        // a. Hỏi nghiệp vụ Kho (Nhập kho, kiểm kê, định giá kho)
        elseif (preg_match('/(nhap kho|nhap hang|them hang vao kho|xuat kho|kiem ke imei|dinh gia kho|kiem kho)/i', $cleanText)) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: #fffbeb; border: 1px solid #fde68a;'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-boxes-stacked text-warning fs-5'></i>"
                . "<strong class='text-dark'>YÊU CẦU THUỘC NGHIỆP VỤ QUẢN LÝ KHO (WAREHOUSE AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-2'>Nhân viên Bán Hàng không có thẩm quyền nhập/xuất hoặc điều chỉnh số lượng tồn kho vật lý. Thao tác này thuộc trách nhiệm độc quyền của Thủ Kho.</p>"
                . "<div class='small text-dark fw-bold mb-1'>💼 Nghiệp vụ Bán Hàng bạn có thể thực hiện:</div>"
                . "<ul class='small text-secondary ps-3 mb-0'>"
                . "<li>Tra cứu đơn hàng qua mã đơn: <code>#VP-12</code> hoặc SĐT khách</li>"
                . "<li>Cập nhật trạng thái đơn: <code>Đã xác nhận đơn #12</code> hoặc <code>Hủy đơn #12</code></li>"
                . "<li>Lấy mã voucher ưu đãi: <code>Danh sách voucher đang hoạt động</code></li>"
                . "<li>Kịch bản gọi điện thoại xác nhận & kịch bản up-sell sạc 45W</li>"
                . "</ul>"
                . "</div>";
            $suggestions = ['Tra cứu đơn hàng mới nhất', 'Danh sách voucher đang hoạt động', 'Gợi ý kịch bản up-sell sạc & tai nghe', 'Mẫu tin nhắn gọi xác nhận đơn'];
        }
        // b. Hỏi CSKH (Review, 1 sao, khiếu nại, gọi lại)
        elseif (preg_match('/(danh gia|review|1 sao|2 sao|5 sao|khieu nai|cskh|goi lai|callback)/i', $cleanText)) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: #fffbeb; border: 1px solid #fde68a;'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-headset text-danger fs-5'></i>"
                . "<strong class='text-dark'>YÊU CẦU THUỘC BỘ PHẬN CSKH (SUPPORT AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-0'>Các yêu cầu phản hồi review 1-5 sao hoặc giải quyết khiếu nại khách hàng giận dữ do bộ phận CSKH chuyên trách xử lý theo quy trình ngoại giao.</p>"
                . "</div>";
            $suggestions = ['Tra cứu đơn hàng mới nhất', 'Danh sách voucher đang hoạt động', 'Mẫu tin nhắn gọi xác nhận đơn'];
        }
        // c. Hỏi Kỹ thuật / Sửa chữa / Bảo hành
        elseif (preg_match('/(sua chua|bao hanh|soc man|thay pin|thay man|ep kinh|face id|phoi oled)/i', $cleanText)) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: #fffbeb; border: 1px solid #fde68a;'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-shield-halved text-success fs-5'></i>"
                . "<strong class='text-dark'>YÊU CẦU THUỘC PHÒNG KỸ THUẬT (TECH & CARE AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-0'>Bán hàng chỉ tư vấn chính sách đổi trả, việc thẩm định lỗi phần cứng và chi phí sửa chữa do phòng Kỹ thuật đảm nhiệm.</p>"
                . "</div>";
            $suggestions = ['Tra cứu đơn hàng mới nhất', 'Gợi ý kịch bản up-sell sạc & tai nghe', 'Danh sách voucher đang hoạt động'];
        }
        // d. Lệnh không khớp chuyên môn bán hàng
        else {
            $reply = "<div class='p-3 rounded-3 bg-light border'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-briefcase text-primary fs-5'></i>"
                . "<strong class='text-dark'>TRỢ LÝ BÁN HÀNG & ĐƠN HÀNG (SALES AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-2'>Yêu cầu của bạn chưa khớp với chức năng bán hàng đã thiết lập. Bạn có thể ra lệnh:</p>"
                . "<ul class='small text-secondary ps-3 mb-2'>"
                . "<li><code>Tra cứu đơn #12</code> hoặc <code>Tìm đơn SĐT 0912345678</code></li>"
                . "<li><code>Đã xác nhận đơn #12</code> hoặc <code>Hủy đơn #12</code></li>"
                . "<li><code>Tạo voucher SALE50K giảm 50000 đơn 500000</code></li>"
                . "<li><code>Danh sách voucher đang hoạt động</code></li>"
                . "<li><code>Gợi ý kịch bản up-sell sạc & tai nghe</code></li>"
                . "<li><code>Mẫu tin nhắn gọi xác nhận đơn</code></li>"
                . "</ul>"
                . "<div class='small text-muted fst-italic'>Hoặc nhấp vào các nút gợi ý nhanh bên dưới để thao tác ngay.</div>"
                . "</div>";
            $suggestions = ['Tra cứu đơn hàng mới nhất', 'Danh sách voucher đang hoạt động', 'Gợi ý kịch bản up-sell sạc & tai nghe', 'Mẫu tin nhắn gọi xác nhận đơn'];
        }
        break;

    // =====================================================================
    // 3. QUẢN LÝ KHO & TỒN KHO (WAREHOUSE)
    // =====================================================================
    case 'warehouse':
        // 1. Thực thi nghiệp vụ Kho (Nhập hàng, Thêm hàng, Xuất kho, Điều chỉnh tồn kho)
        if ($invRes = handleInventoryAction($pdo, $cleanText, $userMessage)) {
            $reply = $invRes['reply'];
            $suggestions = $invRes['suggestions'];
        }
        // 2. Cảnh báo hàng sắp hết
        elseif (preg_match('/(sap het|het hang|canh bao|ton thap|ton <= 5|duoi 5)/i', $cleanText)) {
            try {
                $stmt = $pdo->query("SELECT id, name, quantity, price FROM products WHERE quantity <= 5 ORDER BY quantity ASC LIMIT 10");
                $lowItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($lowItems)) {
                    $reply = "<strong>DANH SÁCH MÁY TỒN THẤP & HẾT HÀNG (CẦN NHẬP KHO GẤP):</strong><br><br>";
                    foreach ($lowItems as $it) {
                        $badge = ($it['quantity'] == 0) 
                            ? "<span class='badge bg-danger'>HẾT HÀNG (0 máy)</span>" 
                            : "<span class='badge bg-warning text-dark'>Tồn: {$it['quantity']} máy</span>";
                        $reply .= "• <strong>" . htmlspecialchars($it['name']) . "</strong> - {$badge}<br>";
                    }
                    $reply .= "<br><div class='p-2 rounded bg-light border small'>";
                    $reply .= "<strong>Khuyến nghị định mức nhập:</strong> Mỗi mã máy nên làm đề xuất nhập thêm tối thiểu <strong>10 - 20 máy</strong> để đảm bảo cung ứng trong 2 tuần.";
                    $reply .= "</div>";
                } else {
                    $reply = "<span class='text-success'><i class='fa-solid fa-circle-check me-1'></i>Hiện không có sản phẩm nào chạm ngưỡng tồn thấp (tất cả đều > 5 máy). Tồn kho đang rất an toàn!</span>";
                }
                $suggestions = ['Thống kê tổng giá trị tồn kho', 'Tồn kho theo từng hãng máy', 'Quy trình kiểm kê quét IMEI chuẩn'];
            } catch (\Throwable $e) {
                $reply = "Lỗi truy vấn tồn kho: " . htmlspecialchars($e->getMessage());
            }
        }
        elseif (preg_match('/(gia tri kho|tong gia tri|thong ke kho|tong so luong|dinh gia kho)/i', $cleanText)) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) AS total_models, SUM(quantity) AS total_units, SUM(quantity * price) AS total_val FROM products");
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);

                $reply = "<strong>BÁO CÁO THỐNG KÊ GIÁ TRỊ TỒN KHO TOÀN HỆ THỐNG:</strong><br><br>";
                $reply .= "• Tổng số mẫu máy đang kinh doanh: <strong>" . number_format($stats['total_models']) . " mẫu</strong><br>";
                $reply .= "• Tổng số lượng máy thực tế trong kho: <strong>" . number_format($stats['total_units']) . " chiếc</strong><br>";
                $reply .= "• Tổng giá trị định giá tồn kho: <strong class='text-danger fs-6'>" . formatVND($stats['total_val']) . "</strong><br><br>";
                $reply .= "<em>Lưu ý nghiệp vụ: Thực hiện kiểm kê đối soát vật lý định kỳ vào cuối tuần để đảm bảo số liệu trên phần mềm khớp 100% với thực tế tại kệ trưng bày và kho tổng.</em>";
                $suggestions = ['Sản phẩm sắp hết hàng (tồn ≤ 5)', 'Tồn kho theo từng hãng máy', 'Tiêu chuẩn đóng gói gửi điện thoại'];
            } catch (\Throwable $e) {
                $reply = "Lỗi tính giá trị kho: " . htmlspecialchars($e->getMessage());
            }
        }
        elseif (preg_match('/(hang|thuong hieu|apple|samsung|phan bo)/i', $cleanText)) {
            try {
                $stmt = $pdo->query("
                    SELECT b.name AS brand_name, COUNT(p.id) AS model_count, SUM(p.quantity) AS total_qty 
                    FROM products p 
                    LEFT JOIN brands b ON p.brand_id = b.id 
                    GROUP BY b.name 
                    ORDER BY total_qty DESC
                ");
                $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $reply = "<strong>PHÂN BỔ TỒN KHO THEO THƯƠNG HIỆU:</strong><br><br>";
                foreach ($brands as $b) {
                    $bName = $b['brand_name'] ?: 'Khác';
                    $reply .= "• <strong>{$bName}</strong>: {$b['model_count']} mẫu máy - <strong>" . (int)$b['total_qty'] . " máy trong kho</strong><br>";
                }
                $suggestions = ['Sản phẩm sắp hết hàng (tồn ≤ 5)', 'Thống kê tổng giá trị tồn kho', 'Quy trình kiểm kê quét IMEI chuẩn'];
            } catch (\Throwable $e) {
                $reply = "Lỗi trích xuất theo hãng: " . htmlspecialchars($e->getMessage());
            }
        }
        elseif (preg_match('/(quy trinh|imei|kiem ke|nhap kho|xuat kho)/i', $cleanText)) {
            $reply = "<strong>QUY TRÌNH KIỂM KÊ & QUẢN LÝ IMEI CHUẨN SHOWROOM:</strong><br><br>";
            $reply .= "<strong>1. Quy trình Nhập Kho (Inbound):</strong><br>";
            $reply .= "• Bước 1: Kiểm tra ngoại quan thùng carton từ hãng phân phối, đảm bảo nguyên đai nguyên kiện.<br>";
            $reply .= "• Bước 2: Dùng máy quét mã vạch quét mã Barcode Serial & IMEI trên từng vỏ hộp.<br>";
            $reply .= "• Bước 3: Dán tem niêm phong chống hàng giả độc quyền của V-Phone lên mép seal máy.<br>";
            $reply .= "• Bước 4: Nhập số lượng và lưu danh sách IMEI vào phần mềm kho.<br><br>";
            $reply .= "<strong>2. Quy trình Xuất Kho (Outbound):</strong><br>";
            $reply .= "• Xuất theo nguyên tắc <strong>FIFO (Nhập trước xuất trước)</strong> để tránh tồn đọng máy pin suy hao.<br>";
            $reply .= "• Quét IMEI máy gán trực tiếp vào mã đơn hàng trước khi đóng gói.<br>";
            $reply .= "• Bàn giao bưu tá kèm phiếu xuất kho có chữ ký xác nhận 2 bên.";
            $suggestions = ['Tiêu chuẩn đóng gói gửi điện thoại', 'Sản phẩm sắp hết hàng (tồn ≤ 5)', 'Thống kê tổng giá trị tồn kho'];
        }
        elseif (preg_match('/(dong goi|gui hang|chong soc|ship hang|bao quan)/i', $cleanText)) {
            $reply = "<strong>TIÊU CHUẨN ĐÓNG GÓI SMARTPHONE CHỐNG SỐC 4 LỚP:</strong><br><br>";
            $reply .= "• <strong>Lớp 1:</strong> Hộp nguyên seal của máy bọc màng co nilon bảo vệ.<br>";
            $reply .= "• <strong>Lớp 2:</strong> Quấn tối thiểu 4 vòng xốp hơi (bóng khí) xung quanh thân hộp và 2 đầu máy.<br>";
            $reply .= "• <strong>Lớp 3:</strong> Đặt vào hộp carton 3 lớp cứng cáp chuyên dụng vừa vặn, chèn thêm túi khí nếu còn khe hở.<br>";
            $reply .= "• <strong>Lớp 4:</strong> Dán băng keo niêm phong cảnh báo: <em>'HÀNG ĐIỆN TỬ GIÁ TRỊ CAO - ĐỒNG KIỂM KHI NHẬN'</em>.<br><br>";
            $reply .= "<em>Lưu ý: Luôn bật camera giám sát bàn đóng gói để lưu lại video đóng từng đơn hàng làm bằng chứng đối soát bưu cục khi có sự cố va đập!</em>";
            $suggestions = ['Quy trình kiểm kê quét IMEI chuẩn', 'Sản phẩm sắp hết hàng (tồn ≤ 5)', 'Thống kê tổng giá trị tồn kho'];
        }
        // NHẬN DIỆN VÀ TỪ CHỐI CÂU HỎI NGOÀI CHUYÊN MÔN KHO (KHÔNG TRẢ LỜI NHẢM):
        // a. Hỏi CSKH / Đánh giá review / Khiếu nại / Gọi lại
        elseif (preg_match('/(danh gia|review|1 sao|2 sao|5 sao|cskh|cham soc|khieu nai|goi lai|callback|phan hoi khach|phan nan)/i', $cleanText)) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: #fffbeb; border: 1px solid #fde68a;'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-triangle-exclamation text-warning fs-5'></i>"
                . "<strong class='text-dark'>YÊU CẦU NGOÀI PHẠM VI CHUYÊN MÔN QUẢN LÝ KHO</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-2'>Nội dung về <strong>đánh giá khách hàng / CSKH</strong> thuộc trách nhiệm của bộ phận <strong>Chuyên Viên CSKH (Support AI)</strong>. Bộ phận Kho vận chỉ quản lý hàng hóa vật lý và không xử lý phản hồi trực tiếp với khách hàng.</p>"
                . "<div class='small text-dark fw-bold mb-1'>📦 Nghiệp vụ chuyên môn của Quản Lý Kho:</div>"
                . "<ul class='small text-secondary ps-3 mb-0'>"
                . "<li>Kiểm kê tồn kho, rà soát máy sắp hết (tồn ≤ 5)</li>"
                . "<li>Thực hiện lệnh: <code>Nhập kho [SL] [Máy]</code> hoặc <code>Xuất kho [SL] [Máy]</code></li>"
                . "<li>Báo cáo tổng giá trị tài sản tồn kho & phân bổ theo hãng</li>"
                . "<li>Quy trình quét IMEI và tiêu chuẩn đóng gói chống sốc</li>"
                . "</ul>"
                . "</div>";
            $suggestions = ['Sản phẩm sắp hết hàng (tồn ≤ 5)', 'Thống kê tổng giá trị tồn kho', 'Tồn kho theo từng hãng máy', 'Quy trình kiểm kê quét IMEI chuẩn'];
        }
        // b. Hỏi Bán hàng / Voucher / Giá bán / Kịch bản up-sell
        elseif (preg_match('/(voucher|ma giam|khuyen mai|up-?sell|chot deal|ban them|huy don|doi tra|khiem don|gia ban)/i', $cleanText)) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: #fffbeb; border: 1px solid #fde68a;'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-briefcase text-primary fs-5'></i>"
                . "<strong class='text-dark'>YÊU CẦU THUỘC BỘ PHẬN BÁN HÀNG (SALES AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-2'>Kho chỉ quản lý số lượng tồn thực tế và mã IMEI, không quản lý mã giảm giá voucher hay kịch bản bán hàng. Bạn vui lòng liên hệ nhân viên Bán Hàng.</p>"
                . "<div class='small text-secondary'>👉 Kho có thể kiểm tra tồn kho mã máy này cho bạn nếu cần!</div>"
                . "</div>";
            $suggestions = ['Sản phẩm sắp hết hàng (tồn ≤ 5)', 'Thống kê tổng giá trị tồn kho', 'Tồn kho theo từng hãng máy'];
        }
        // c. Hỏi Kỹ thuật / Sửa chữa / Bảo hành / Lỗi màn hình
        elseif (preg_match('/(bao hanh|sua chua|soc man|thay pin|thay man|ep kinh|face id|phoi oled|loi phan cung)/i', $cleanText)) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: #fffbeb; border: 1px solid #fde68a;'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-shield-halved text-success fs-5'></i>"
                . "<strong class='text-dark'>YÊU CẦU THUỘC BỘ PHẬN KỸ THUẬT & BẢO HÀNH (TECH & CARE AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-0'>Bộ phận Kho không thực hiện chẩn đoán phần cứng hay báo giá sửa chữa linh kiện. Vui lòng bàn giao thiết bị cho Kỹ thuật viên kiểm tra chuyên sâu.</p>"
                . "</div>";
            $suggestions = ['Quy trình kiểm kê quét IMEI chuẩn', 'Tiêu chuẩn đóng gói gửi điện thoại', 'Sản phẩm sắp hết hàng (tồn ≤ 5)'];
        }
        // d. Hỏi Admin / Cấp quyền / Doanh thu / Chiến lược
        elseif (preg_match('/(phan quyen|cap quyen|thu hoi|doanh thu|loi nhuan|chien luoc)/i', $cleanText)) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: #fffbeb; border: 1px solid #fde68a;'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-crown text-danger fs-5'></i>"
                . "<strong class='text-dark'>YÊU CẦU THUỘC THẨM QUYỀN QUẢN TRỊ VIÊN (ADMIN)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-0'>Tài khoản Thủ Kho chỉ được thao tác trong phạm vi kiểm soát kho vận. Số liệu tài chính lãnh đạo và phân quyền hệ thống thuộc thẩm quyền Quản Trị Viên.</p>"
                . "</div>";
            $suggestions = ['Sản phẩm sắp hết hàng (tồn ≤ 5)', 'Thống kê tổng giá trị tồn kho', 'Quy trình kiểm kê quét IMEI chuẩn'];
        }
        // e. Lệnh không khớp chuyên môn kho
        else {
            $reply = "<div class='p-3 rounded-3 bg-light border'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-boxes-stacked text-warning fs-5'></i>"
                . "<strong class='text-dark'>TRỢ LÝ KHO VẬN CHUYÊN NGHIỆP</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-2'>Yêu cầu của bạn chưa khớp với lệnh kho nào đã được lập trình. Để Trợ lý Kho thực thi chính xác, bạn vui lòng nhập đúng chuyên môn:</p>"
                . "<ul class='small text-secondary ps-3 mb-2'>"
                . "<li><code>Thêm 10 iPhone 15 Pro Max</code> hoặc <code>Nhập kho 20 Galaxy S24</code></li>"
                . "<li><code>Xuất kho 2 iPhone 16</code> hoặc <code>Điều chỉnh tồn sp 1 thành 30</code></li>"
                . "<li><code>Sản phẩm sắp hết hàng (tồn ≤ 5)</code></li>"
                . "<li><code>Thống kê tổng giá trị tồn kho</code></li>"
                . "<li><code>Quy trình kiểm kê quét IMEI chuẩn</code></li>"
                . "</ul>"
                . "<div class='small text-muted fst-italic'>Hoặc nhấp vào các nút gợi ý nhanh bên dưới để thao tác ngay.</div>"
                . "</div>";
            $suggestions = ['Sản phẩm sắp hết hàng (tồn ≤ 5)', 'Thống kê tổng giá trị tồn kho', 'Tồn kho theo từng hãng máy', 'Quy trình kiểm kê quét IMEI chuẩn'];
        }
        break;

    // =====================================================================
    // 4. KỸ THUẬT VIÊN & BẢO HÀNH (WARRANTY)
    // =====================================================================
    case 'warranty':
        // 1. Thực thi tiếp nhận hoặc cập nhật tiến độ bảo hành
        if ($warRes = handleWarrantyAction($pdo, $cleanText, $userMessage)) {
            $reply = $warRes['reply'];
            $suggestions = $warRes['suggestions'];
        }
        // 2. Tra cứu phiếu bảo hành theo IMEI, SĐT hoặc ID
        elseif (preg_match('/(imei|tra cuu|phieu|bh|so imei|[0-9]{15})/i', $cleanText, $m) || preg_match('/(0[0-9]{9,10})/', $cleanText, $phoneMatch)) {
            $searchKeyword = '';
            if (preg_match('/([0-9]{14,16})/', $cleanText, $imeiM)) {
                $searchKeyword = $imeiM[1];
            } elseif (isset($phoneMatch[1])) {
                $searchKeyword = $phoneMatch[1];
            } elseif (preg_match('/#?bh-?([0-9]+)/i', $cleanText, $bhIdM)) {
                $searchKeyword = $bhIdM[1];
            }

            try {
                if ($searchKeyword) {
                    $stmt = $pdo->prepare("SELECT * FROM warranty_claims WHERE imei LIKE ? OR customer_phone LIKE ? OR id = ? LIMIT 1");
                    $stmt->execute(["%{$searchKeyword}%", "%{$searchKeyword}%", (int)$searchKeyword]);
                    $claim = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($claim) {
                        $sttBadge = "<span class='badge bg-warning text-dark'>" . htmlspecialchars($claim['status']) . "</span>";
                        if ($claim['status'] === 'Đã hoàn thành') $sttBadge = "<span class='badge bg-success'>Đã hoàn thành</span>";
                        if ($claim['status'] === 'Từ chối') $sttBadge = "<span class='badge bg-danger'>Từ chối bảo hành</span>";

                        $reply = "<strong>THÔNG TIN PHIẾU BẢO HÀNH #BH-{$claim['id']}</strong>:<br><br>";
                        $reply .= "• Khách hàng: <strong>" . htmlspecialchars($claim['customer_name']) . "</strong> (" . htmlspecialchars($claim['customer_phone']) . ")<br>";
                        $reply .= "• Dòng máy: <strong>" . htmlspecialchars($claim['product_name']) . "</strong><br>";
                        $reply .= "• Số IMEI: <code class='text-primary fw-bold'>" . htmlspecialchars($claim['imei'] ?: 'Chưa cập nhật') . "</code><br>";
                        $reply .= "• Tình trạng lỗi: <strong>" . htmlspecialchars($claim['issue_type']) . "</strong><br>";
                        $reply .= "• Mô tả của khách: <em>" . htmlspecialchars($claim['issue_description']) . "</em><br>";
                        $reply .= "• Tiến độ xử lý: {$sttBadge}<br>";
                        if (!empty($claim['admin_note'])) {
                            $reply .= "• Ghi chú kỹ thuật: " . htmlspecialchars($claim['admin_note']) . "<br>";
                        }
                        $reply .= "• Ngày tiếp nhận: " . htmlspecialchars($claim['created_at']) . "<br><br>";
                        $reply .= "<a href='warranties.php' class='btn btn-sm btn-success rounded-pill px-3 py-1 text-white'><i class='fa-solid fa-screwdriver-wrench me-1'></i> Mở trang Bảo Hành</a>";
                    } else {
                        $reply = "Không tìm thấy hồ sơ bảo hành nào với thông tin '{$searchKeyword}'. Bạn vui lòng kiểm tra lại số IMEI hoặc số điện thoại của khách!";
                    }
                } else {
                    $stmt = $pdo->query("SELECT COUNT(*) AS total_pending FROM warranty_claims WHERE status = 'Chờ tiếp nhận'");
                    $cnt = (int)($stmt->fetchColumn() ?: 0);
                    $reply = "Hiện có <strong>{$cnt} phiếu bảo hành</strong> đang ở trạng thái <em>'Chờ tiếp nhận'</em>.<br>Để tra cứu phiếu cụ thể, bạn chỉ cần gõ số IMEI (15 số) hoặc SĐT của khách hàng!";
                }
                $suggestions = ['Chẩn đoán lỗi sọc màn hình & phôi OLED', 'Chẩn đoán lỗi chai phồng pin & IC sạc', 'Chính sách bảo hành Apple VN/A'];
            } catch (\Throwable $e) {
                $reply = "Lỗi tra cứu bảo hành: " . htmlspecialchars($e->getMessage());
            }
        }
        elseif (preg_match('/(man hinh|soc man|chay muc|liet cam ung|am man|vo kinh)/i', $cleanText)) {
            $reply = "<strong>HƯỚNG DẪN CHẨN ĐOÁN LỖI MÀN HÌNH & TIÊU CHÍ BẢO HÀNH:</strong><br><br>";
            $reply .= "<strong>1. Các dạng lỗi thường gặp:</strong><br>";
            $reply .= "• <em>Sọc màn hình (xanh/hồng):</em> Lỗi đứt cổ cáp màn hoặc lỗi phôi OLED. Nếu máy không cấn móp ở khung viền -> <strong>ĐƯỢC BẢO HÀNH 1 ĐỔI 1</strong>.<br>";
            $reply .= "• <em>Chảy mực (đốm đen loang):</em> 99% do tác động lực tì đè hoặc va chạm làm nứt phôi kính trong -> <strong>Từ chối bảo hành đổi mới</strong>, tư vấn khách thay cụm màn trợ giá.<br>";
            $reply .= "• <em>Liệt cảm ứng / Loạn cảm ứng:</em> Lỗi IC cảm ứng trên màn. Nếu kính ngoài không nứt vỡ -> Hỗ trợ bảo hành sửa chữa/thay thế miễn phí.<br><br>";
            $reply .= "<strong>2. Thao tác kiểm tra tại quầy:</strong><br>";
            $reply .= "• Soi đèn nghiêng 45 độ kiểm tra phôi màn hình xem có vết nứt chân chim ngầm hay không.<br>";
            $reply .= "• Test cảm ứng: Giữ 1 icon di chuyển đều khắp 4 góc màn hình để phát hiện điểm mù.";
            $suggestions = ['Bảng giá tham khảo thay linh kiện', 'Chẩn đoán lỗi chai phồng pin & IC sạc', 'Chính sách bảo hành Apple VN/A'];
        }
        elseif (preg_match('/(pin|phong pin|chai pin|sac khong vao|nong may|sup nguon)/i', $cleanText)) {
            $reply = "<strong>CHẨN ĐOÁN LỖI PIN, NGUỒN & HỆ THỐNG SẠC:</strong><br><br>";
            $reply .= "<strong>1. Tiêu chí bảo hành pin:</strong><br>";
            $reply .= "• Dung lượng pin tối đa (Battery Health) dưới 80% trong vòng 12 tháng kể từ ngày kích hoạt.<br>";
            $reply .= "• Số chu kỳ sạc (Cycle Count) dưới 500 chu kỳ.<br>";
            $reply .= "• Pin bị phồng đội màn hình hoặc máy tự động sập nguồn khi pin còn 20 - 30% -> <strong>Thay pin chính hãng miễn phí ngay</strong>.<br><br>";
            $reply .= "<strong>2. Khắc phục lỗi sạc không vào:</strong><br>";
            $reply .= "• Vệ sinh chân sạc Type-C / Lightning bằng cồn 90 độ và chổi chuyên dụng (thường do xơ vải bám kín).<br>";
            $reply .= "• Đo dòng điện sạc bằng máy test công suất USB-C Doctor (dòng chuẩn phải đạt từ 9V-2A hoặc 15V-2A).<br>";
            $reply .= "• Nếu đo dòng = 0.00A: Khả năng lỗi IC sạc (U2 Tristar) hoặc đứt cáp đuôi sạc.";
            $suggestions = ['Chẩn đoán lỗi sọc màn hình & phôi OLED', 'Chính sách bảo hành Apple VN/A', 'Bảng giá tham khảo thay linh kiện'];
        }
        elseif (preg_match('/(chinh sach|apple|samsung care|quy dinh|dieu kien)/i', $cleanText)) {
            $reply = "<strong>CHÍNH SÁCH BẢO HÀNH CHÍNH HÃNG V-PHONE CARE 2026:</strong><br><br>";
            $reply .= "• <span class='badge bg-success'>1 Đổi 1 Trong 30 Ngày</span> Áp dụng cho mọi lỗi phần cứng do nhà sản xuất (màn hình, camera, nguồn, sóng, face id).<br>";
            $reply .= "• <span class='badge bg-primary'>Bảo Hành 12 Tháng Toàn Diện</span> Bảo hành theo chuẩn Apple Authorized / Samsung Service Center.<br>";
            $reply .= "• <span class='badge bg-danger'>Điều Kiện Từ Chối</span><br>";
            $reply .= "  1. Quỳ tím trong khay SIM đã chuyển sang màu đỏ (máy bị ngấm chất lỏng).<br>";
            $reply .= "  2. Vỏ máy có dấu hiệu cấn móp nặng, rơi vỡ, biến dạng cong sườn.<br>";
            $reply .= "  3. Máy có dấu hiệu bị tháo mở ốc hoặc can thiệp bởi bên thứ 3 ngoài hệ thống.<br>";
            $reply .= "  4. Máy chưa thoát tài khoản iCloud hoặc Samsung Account của chủ sở hữu.";
            $suggestions = ['Bảng giá tham khảo thay linh kiện', 'Chẩn đoán lỗi sọc màn hình & phôi OLED', 'Tra cứu phiếu bảo hành theo IMEI'];
        }
        elseif (preg_match('/(gia|bang gia|linh kien|thay man|thay pin|ep kinh)/i', $cleanText)) {
            $reply = "<strong>BẢNG GIÁ THAM KHẢO THAY LINH KIỆN TẠI V-PHONE CARE:</strong><br><br>";
            $reply .= "• <strong>Thay Pin Zin Chính Hãng:</strong><br>";
            $reply .= "  - iPhone 13 / 14 Series: 650.000đ - 850.000đ (Bảo hành 12T)<br>";
            $reply .= "  - iPhone 15 / 16 / 17 Series: 950.000đ - 1.450.000đ<br><br>";
            $reply .= "• <strong>Ép Kính Cường Lực (Màn hiển thị còn nguyên):</strong><br>";
            $reply .= "  - Màn phẳng: 350.000đ - 550.000đ<br>";
            $reply .= "  - Màn cong Samsung Galaxy Ultra: 750.000đ - 1.100.000đ<br><br>";
            $reply .= "• <strong>Thay Cụm Màn Hình Zin Bóc Máy / Mới 100%:</strong><br>";
            $reply .= "  - Dòng màn 120Hz ProMotion: Từ 3.800.000đ - 6.500.000đ (tùy model).<br><br>";
            $reply .= "<em>Đặc quyền khách hàng: Miễn phí vệ sinh máy, dán keo chống nước áp suất và bảo hành 6-12 tháng!</em>";
            $suggestions = ['Chẩn đoán lỗi sọc màn hình & phôi OLED', 'Chính sách bảo hành Apple VN/A', 'Tra cứu phiếu bảo hành theo IMEI'];
        }
        // NHẬN DIỆN VÀ TỪ CHỐI CÂU HỎI NGOÀI CHUYÊN MÔN KỸ THUẬT:
        // a. Hỏi Kho (Nhập kho, kiểm kê, định giá kho)
        elseif (preg_match('/(nhap kho|nhap hang|them hang vao kho|xuat kho|dinh gia kho)/i', $cleanText)) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: #fffbeb; border: 1px solid #fde68a;'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-boxes-stacked text-warning fs-5'></i>"
                . "<strong class='text-dark'>YÊU CẦU THUỘC BỘ PHẬN KHO (WAREHOUSE AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-0'>Kỹ thuật viên chỉ phụ trách thẩm định linh kiện và bảo hành phần cứng, thao tác xuất nhập tồn do Thủ Kho thực hiện.</p>"
                . "</div>";
            $suggestions = ['Tra cứu phiếu bảo hành theo IMEI', 'Chẩn đoán lỗi sọc màn hình & phôi OLED', 'Bảng giá tham khảo thay linh kiện'];
        }
        // b. Hỏi Bán hàng / Voucher / Giá bán điện thoại
        elseif (preg_match('/(voucher|khuyen mai|ban hang|tao don|doanh thu|gia ban dien thoai)/i', $cleanText)) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: #fffbeb; border: 1px solid #fde68a;'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-briefcase text-primary fs-5'></i>"
                . "<strong class='text-dark'>YÊU CẦU THUỘC BỘ PHẬN BÁN HÀNG (SALES AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-0'>Các chương trình voucher khuyến mãi hoặc sửa đổi đơn hàng do nhân viên Bán Hàng phụ trách.</p>"
                . "</div>";
            $suggestions = ['Tra cứu phiếu bảo hành theo IMEI', 'Bảng giá tham khảo thay linh kiện'];
        }
        // c. Hỏi CSKH (Review, 1 sao, khiếu nại, gọi lại)
        elseif (preg_match('/(danh gia|review|1 sao|2 sao|5 sao|cskh|goi lai)/i', $cleanText)) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: #fffbeb; border: 1px solid #fde68a;'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-headset text-danger fs-5'></i>"
                . "<strong class='text-dark'>YÊU CẦU THUỘC BỘ PHẬN CSKH (SUPPORT AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-0'>Việc phản hồi đánh giá và gọi lại chăm sóc khách hàng thuộc chuyên môn của bộ phận CSKH.</p>"
                . "</div>";
            $suggestions = ['Tra cứu phiếu bảo hành theo IMEI', 'Chẩn đoán lỗi chai phồng pin & IC sạc'];
        }
        // d. Lệnh không khớp chuyên môn kỹ thuật
        else {
            $reply = "<div class='p-3 rounded-3 bg-light border'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-shield-halved text-success fs-5'></i>"
                . "<strong class='text-dark'>TRỢ LÝ KỸ THUẬT & BẢO HÀNH (TECH & CARE AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-2'>Câu hỏi của bạn chưa khớp với mã lỗi hoặc quy trình kỹ thuật nào. Bạn có thể tra cứu:</p>"
                . "<ul class='small text-secondary ps-3 mb-2'>"
                . "<li><code>Tra cứu IMEI [15 số IMEI]</code> hoặc <code>Tiếp nhận bảo hành IMEI [Số] lỗi [Mô tả]</code></li>"
                . "<li><code>Chẩn đoán lỗi sọc màn hình & phôi OLED</code></li>"
                . "<li><code>Chẩn đoán lỗi chai phồng pin & IC sạc</code></li>"
                . "<li><code>Bảng giá tham khảo thay linh kiện</code></li>"
                . "<li><code>Chính sách bảo hành Apple VN/A</code></li>"
                . "</ul>"
                . "<div class='small text-muted fst-italic'>Hoặc nhấp vào các nút gợi ý nhanh bên dưới để thao tác ngay.</div>"
                . "</div>";
            $suggestions = ['Tra cứu phiếu bảo hành theo IMEI', 'Chẩn đoán lỗi sọc màn hình & phôi OLED', 'Chẩn đoán lỗi chai phồng pin & IC sạc', 'Bảng giá tham khảo thay linh kiện'];
        }
        break;

    // =====================================================================
    // 5. CHUYÊN VIÊN CSKH & ĐA KÊNH (SUPPORT)
    // =====================================================================
    case 'support':
        // 1. Thực thi duyệt hoặc ẩn đánh giá review
        if ($revRes = handleReviewAction($pdo, $cleanText)) {
            $reply = $revRes['reply'];
            $suggestions = $revRes['suggestions'];
        }
        // 2. Thực thi hoàn thành yêu cầu gọi lại
        elseif (preg_match('/(da goi lai|hoan thanh yeu cau goi|xong yeu cau)\b/i', $cleanText)) {
            preg_match('/(\d+)/', $cleanText, $cm);
            $cid = isset($cm[1]) ? (int)$cm[1] : 0;
            if ($cid > 0) {
                $pdo->prepare("UPDATE callback_requests SET status = 'completed', replied_at = NOW() WHERE id = ?")->execute([$cid]);
                $reply = "<div class='ai-action-card p-2.5 rounded-3 mb-2' style='background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac;'>"
                    . "<div class='d-flex align-items-center gap-2 mb-1'>"
                    . "<i class='fa-solid fa-circle-check text-success fs-5'></i>"
                    . "<strong class='text-success'>ĐÃ HOÀN THÀNH YÊU CẦU GỌI LẠI #{$cid}!</strong>"
                    . "</div>"
                    . "<div class='small text-secondary'>Hệ thống đã lưu trạng thái tư vấn thành công.</div>"
                    . "</div>";
            } else {
                $count = (int)$pdo->exec("UPDATE callback_requests SET status = 'completed', replied_at = NOW() WHERE status = 'pending'");
                $reply = "<div class='ai-action-card p-2.5 rounded-3 mb-2' style='background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac;'>"
                    . "<strong class='text-success'>ĐÃ CẬP NHẬT HOÀN THÀNH {$count} YÊU CẦU GỌI LẠI!</strong>"
                    . "</div>";
            }
            $suggestions = ['Khách hàng yêu cầu gọi lại mới nhất', 'Đánh giá sản phẩm chưa phản hồi', 'Mẫu cảm ơn đánh giá 5 sao'];
        }
        elseif (preg_match('/(goi lai|callback|yeu cau ho tro|lien he moi|lien he lai)/i', $cleanText)) {
            try {
                $stmt = $pdo->query("SELECT id, fullname, phone, topic, note, created_at FROM callback_requests WHERE status = 'pending' ORDER BY id DESC LIMIT 5");
                $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($calls)) {
                    $reply = "<strong>DANH SÁCH KHÁCH HÀNG ĐANG CHỜ GỌI LẠI TƯ VẤN:</strong><br><br>";
                    foreach ($calls as $c) {
                        $topic = $c['topic'] ?: ($c['note'] ?: 'Cần tư vấn mua máy');
                        $reply .= "• <strong class='text-dark'>" . htmlspecialchars($c['fullname']) . "</strong> - SĐT: <code class='text-danger fw-bold'>" . htmlspecialchars($c['phone']) . "</code><br>";
                        $reply .= "  <em>Chủ đề: " . htmlspecialchars($topic) . " (" . htmlspecialchars($c['created_at']) . ")</em><br>";
                    }
                    $reply .= "<br><div class='p-2 rounded bg-light border small'>";
                    $reply .= "<i class='fa-solid fa-phone-volume text-success me-1'></i> <strong>Mẹo CSKH:</strong> Gọi lại trong vòng <strong>5 - 10 phút</strong> giúp tăng tỷ lệ chốt đơn lên tới 80% so với gọi sau 1 giờ!";
                    $reply .= "</div>";
                } else {
                    $reply = "<span class='text-success'><i class='fa-solid fa-circle-check me-1'></i>Tuyệt vời! Hiện không có yêu cầu gọi lại nào đang bị ứ đọng. Tất cả khách hàng đều đã được liên hệ!</span>";
                }
                $suggestions = ['Đánh giá sản phẩm chưa phản hồi', 'Mẫu trả lời đánh giá 1 sao chuyên nghiệp', 'Kịch bản xoa dịu khách khiếu nại'];
            } catch (\Throwable $e) {
                $reply = "Lỗi tra cứu yêu cầu CSKH: " . htmlspecialchars($e->getMessage());
            }
        }
        elseif (preg_match('/(danh gia|review|chua phan hoi|sao|chua tra loi)/i', $cleanText)) {
            try {
                $stmt = $pdo->query("
                    SELECT r.id, r.fullname, r.rating, r.comment, p.name AS prod_name 
                    FROM product_reviews r 
                    LEFT JOIN products p ON r.product_id = p.id 
                    WHERE (r.admin_reply IS NULL OR r.admin_reply = '') 
                    ORDER BY r.rating ASC, r.id DESC 
                    LIMIT 4
                ");
                $revs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($revs)) {
                    $reply = "<strong>CÁC ĐÁNH GIÁ SẢN PHẨM ĐANG CHỜ PHẢN HỒI (ƯU TIÊN SAO THẤP):</strong><br><br>";
                    foreach ($revs as $r) {
                        $stars = str_repeat("⭐", (int)$r['rating']);
                        $reply .= "• <strong>" . htmlspecialchars($r['fullname']) . "</strong> ({$stars}) - Máy: <em>" . htmlspecialchars($r['prod_name']) . "</em><br>";
                        $reply .= "  Nhận xét: <em>\"" . htmlspecialchars($r['comment']) . "\"</em><br><br>";
                    }
                    $reply .= "<a href='reviews.php' class='btn btn-sm btn-danger rounded-pill px-3 py-1 text-white'><i class='fa-solid fa-reply me-1'></i> Vào Quản Lý Đánh Giá</a>";
                } else {
                    $reply = "<span class='text-success'><i class='fa-solid fa-check-circle me-1'></i>Mọi đánh giá của khách hàng đều đã được phản hồi đầy đủ!</span>";
                }
                $suggestions = ['Mẫu trả lời đánh giá 1 sao chuyên nghiệp', 'Mẫu cảm ơn đánh giá 5 sao', 'Kịch bản xoa dịu khách khiếu nại'];
            } catch (\Throwable $e) {
                $reply = "Lỗi tra cứu đánh giá: " . htmlspecialchars($e->getMessage());
            }
        }
        elseif (preg_match('/(1 sao|2 sao|danh gia xau|phan nan|loi|giao cham)/i', $cleanText)) {
            $reply = "<strong>MẪU PHẢN HỒI ĐÁNH GIÁ 1 - 2 SAO (CHUẨN DỊCH VỤ 5 SAO):</strong><br><br>";
            $reply .= "<div class='p-2.5 rounded bg-light border font-monospace small mb-2'>";
            $reply .= "Dạ V-Phone Store xin chào anh/chị {Tên_Khách} ạ!<br><br>
            Trước hết, V-Phone thành thật gửi lời xin lỗi sâu sắc tới anh/chị vì sự bất tiện trong trải nghiệm vừa qua khiến mình chưa hoàn toàn hài lòng. V-Phone luôn đặt sự an tâm của khách hàng lên hàng đầu.<br><br>
            Chuyên viên CSKH bên em đã tiếp nhận thông tin và sẽ chủ động gọi điện qua số điện thoại của mình trong ít phút để kiểm tra và tiến hành đổi máy mới / hỗ trợ tận nhà cho mình ngay ạ.<br><br>
            Nếu anh/chị cần hỗ trợ khẩn cấp, xin vui lòng gọi hotline miễn phí 1800 6868 (Phím 2) để được ưu tiên xử lý tức thì. Rất mong có cơ hội được phục vụ mình tốt hơn!";
            $reply .= "</div>";
            $reply .= "<em>Bí quyết: Luôn nhận lỗi trước, không tranh cãi công khai và đưa ra hướng xử lý trực tiếp!</em>";
            $suggestions = ['Mẫu cảm ơn đánh giá 5 sao', 'Kịch bản xoa dịu khách khiếu nại', 'Khách hàng yêu cầu gọi lại mới nhất'];
        }
        elseif (preg_match('/(5 sao|khen|cam on|danh gia tot)/i', $cleanText)) {
            $reply = "<strong>MẪU PHẢN HỒI ĐÁNH GIÁ 5 SAO (TRI ÂN KHÁCH HÀNG):</strong><br><br>";
            $reply .= "<div class='p-2.5 rounded bg-light border font-monospace small mb-2'>";
            $reply .= "Dạ V-Phone Store xin chân thành cảm ơn anh/chị {Tên_Khách} đã tin tưởng lựa chọn mua sắm siêu phẩm tại hệ thống ạ!<br><br>
            Sự hài lòng của anh/chị chính là nguồn động lực to lớn nhất để đội ngũ V-Phone tiếp tục nâng cao chất lượng dịch vụ mỗi ngày. Chúc anh/chị có những trải nghiệm công nghệ thật tuyệt vời cùng máy mới!<br><br>
            Sản phẩm của mình được bảo hành chính hãng 12 tháng và hỗ trợ kỹ thuật trọn đời tại mọi showroom V-Phone trên toàn quốc. Khi cần hỗ trợ bất kỳ điều gì, anh/chị đừng ngần ngại nhắn tin cho bên em nhé ạ! ❤️";
            $reply .= "</div>";
            $suggestions = ['Mẫu trả lời đánh giá 1 sao chuyên nghiệp', 'Khách hàng yêu cầu gọi lại mới nhất', 'Kịch bản xoa dịu khách khiếu nại'];
        }
        elseif (preg_match('/(khieu nai|gian du|buc minh|xoa diu|last)/i', $cleanText)) {
            $reply = "<strong>KỊCH BẢN 4 BƯỚC XOA DỊU KHÁCH HÀNG GIẬN DỮ (NGUYÊN TẮC L.A.S.T):</strong><br><br>";
            $reply .= "<strong>1. L - Listen (Lắng nghe chân thành):</strong><br>";
            $reply .= "• Tuyệt đối không ngắt lời khi khách đang xả cơn giận. Hãy để khách nói hết.<br>";
            $reply .= "• Sử dụng các từ đệm đồng cảm: <em>'Dạ em hiểu ạ', 'Em rất tiếc khi biết chuyện này'</em>.<br><br>";
            $reply .= "<strong>2. A - Apologize (Xin lỗi về trải nghiệm):</strong><br>";
            $reply .= "• <em>'Em rất đồng cảm với sự bức xúc của anh/chị. Nếu ở vị trí của mình em cũng sẽ cảm thấy như vậy. Em xin chân thành xin lỗi vì đã làm gián đoạn công việc của anh/chị!'</em><br><br>";
            $reply .= "<strong>3. S - Solve (Đưa giải pháp rõ ràng & dứt khoát):</strong><br>";
            $reply .= "• Không đùn đẩy trách nhiệm: <em>'Em là [Tên], em sẽ trực tiếp chịu trách nhiệm theo dõi và giải quyết trường hợp của mình ngay trong ngày hôm nay.'</em><br><br>";
            $reply .= "<strong>4. T - Thank & Compensate (Cảm ơn và bồi thường thiện chí):</strong><br>";
            $reply .= "• Cảm ơn khách đã góp ý và gửi tặng voucher 100k - 200k hoặc phụ kiện miễn phí.";
            $suggestions = ['Mẫu trả lời đánh giá 1 sao chuyên nghiệp', 'Khách hàng yêu cầu gọi lại mới nhất', 'Đánh giá sản phẩm chưa phản hồi'];
        }
        // NHẬN DIỆN VÀ TỪ CHỐI CÂU HỎI NGOÀI CHUYÊN MÔN CSKH:
        // a. Hỏi Kho (Nhập kho, tồn kho, kiểm kê)
        elseif (preg_match('/(nhap kho|nhap hang|them hang vao kho|xuat kho|kiem ke imei|dinh gia kho)/i', $cleanText)) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: #fffbeb; border: 1px solid #fde68a;'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-boxes-stacked text-warning fs-5'></i>"
                . "<strong class='text-dark'>YÊU CẦU THUỘC BỘ PHẬN KHO (WAREHOUSE AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-0'>Bộ phận CSKH không quản lý xuất nhập tồn kho vật lý. Vui lòng liên hệ Thủ Kho để kiểm tra tình trạng hàng hóa.</p>"
                . "</div>";
            $suggestions = ['Khách hàng yêu cầu gọi lại mới nhất', 'Đánh giá sản phẩm chưa phản hồi', 'Mẫu trả lời đánh giá 1 sao chuyên nghiệp'];
        }
        // b. Hỏi Kỹ thuật / Phần cứng / Thay màn pin
        elseif (preg_match('/(sua chua|thay man|ep kinh|soc man|phoi oled)/i', $cleanText)) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: #fffbeb; border: 1px solid #fde68a;'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-shield-halved text-success fs-5'></i>"
                . "<strong class='text-dark'>YÊU CẦU THUỘC PHÒNG KỸ THUẬT (TECH & CARE AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-0'>CSKH chỉ tiếp nhận phản ánh, việc sửa chữa tháo lắp và chẩn đoán phôi màn hình do Kỹ thuật viên phụ trách.</p>"
                . "</div>";
            $suggestions = ['Khách hàng yêu cầu gọi lại mới nhất', 'Kịch bản xoa dịu khách khiếu nại', 'Mẫu trả lời đánh giá 1 sao chuyên nghiệp'];
        }
        // c. Hỏi Bán hàng / Tạo voucher / Đổi giá
        elseif (preg_match('/(tao voucher|doi gia|khuyen mai|up-?sell)/i', $cleanText)) {
            $reply = "<div class='ai-action-card p-3 rounded-3 mb-2' style='background: #fffbeb; border: 1px solid #fde68a;'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-briefcase text-primary fs-5'></i>"
                . "<strong class='text-dark'>YÊU CẦU THUỘC BỘ PHẬN BÁN HÀNG (SALES AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-0'>CSKH tư vấn và xử lý khiếu nại, các yêu cầu tạo voucher hay điều chỉnh giá do Bán Hàng phụ trách.</p>"
                . "</div>";
            $suggestions = ['Khách hàng yêu cầu gọi lại mới nhất', 'Mẫu cảm ơn đánh giá 5 sao'];
        }
        // d. Lệnh không khớp chuyên môn CSKH
        else {
            $reply = "<div class='p-3 rounded-3 bg-light border'>"
                . "<div class='d-flex align-items-center gap-2 mb-2'>"
                . "<i class='fa-solid fa-headset text-danger fs-5'></i>"
                . "<strong class='text-dark'>TRỢ LÝ CSKH & ĐA KÊNH (SUPPORT AI)</strong>"
                . "</div>"
                . "<p class='small text-secondary mb-2'>Yêu cầu của bạn chưa khớp với tác vụ CSKH nào. Bạn có thể sử dụng các lệnh:</p>"
                . "<ul class='small text-secondary ps-3 mb-2'>"
                . "<li><code>Duyệt đánh giá #5</code> hoặc <code>Ẩn đánh giá #5</code></li>"
                . "<li><code>Khách hàng yêu cầu gọi lại mới nhất</code> hoặc <code>Đã gọi lại #2</code></li>"
                . "<li><code>Đánh giá sản phẩm chưa phản hồi</code></li>"
                . "<li><code>Mẫu trả lời đánh giá 1 sao chuyên nghiệp</code></li>"
                . "<li><code>Mẫu cảm ơn đánh giá 5 sao</code></li>"
                . "<li><code>Kịch bản xoa dịu khách khiếu nại (L.A.S.T)</code></li>"
                . "</ul>"
                . "<div class='small text-muted fst-italic'>Hoặc nhấp vào các nút gợi ý nhanh bên dưới để thao tác ngay.</div>"
                . "</div>";
            $suggestions = ['Khách hàng yêu cầu gọi lại mới nhất', 'Đánh giá sản phẩm chưa phản hồi', 'Mẫu trả lời đánh giá 1 sao chuyên nghiệp', 'Kịch bản xoa dịu khách khiếu nại'];
        }
        break;

    // =====================================================================
    // 6. KHÁCH HÀNG MUA SẮM (CUSTOMER)
    // =====================================================================
    case 'customer':
    default:
        // Chuyển hướng sang logic chatbot bán hàng phong phú đã có
        require_once __DIR__ . '/chatbot.php';
        exit;
}

// Trả về JSON chuẩn phản hồi cho UI
echo json_encode([
    'success' => true,
    'role' => $requestedRole,
    'role_title' => $activeConfig['name'],
    'role_badge' => $activeConfig['badge'],
    'role_color' => $activeConfig['color'],
    'role_gradient' => $activeConfig['gradient'],
    'reply' => $reply,
    'suggestions' => $suggestions,
    'timestamp' => date('H:i')
], JSON_UNESCAPED_UNICODE);
