<?php
/**
 * V-Phone Store 2026 - AI Sales Consultant Engine (Cyber AI Neural System)
 * Chuyên gia tư vấn bán hàng số thông minh 24/7 - Chuẩn Flagship 2026
 * 100% Vector Icons & Dynamic Vietnam Timezone (GMT+7)
 */
require_once __DIR__ . '/../config/database.php';
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
if ($userMessage === '') {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập câu hỏi.'], JSON_UNESCAPED_UNICODE);
    exit;
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

// Helper format tiền tệ VNĐ
if (!function_exists('formatVND')) {
function formatVND($amount): string {
    return number_format((float)$amount, 0, ',', '.') . ' VNĐ';
}
}

// Helper tìm kiếm sản phẩm theo keyword
if (!function_exists('queryProducts')) {
function queryProducts(PDO $pdo, string $keyword, int $limit = 3): array {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, price, sale_price, ram, rom, screen, battery 
            FROM products 
            WHERE name LIKE ? 
            ORDER BY is_featured DESC, (CASE WHEN sale_price > 0 THEN sale_price ELSE price END) DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, "%{$keyword}%", PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        return [];
    }
}
}

// Helper tìm sản phẩm theo khoảng giá
if (!function_exists('queryProductsByPrice')) {
function queryProductsByPrice(PDO $pdo, ?float $minPrice, ?float $maxPrice, int $limit = 3): array {
    try {
        $sql = "SELECT id, name, price, sale_price, ram, rom, screen FROM products WHERE 1=1";
        $params = [];
        if ($minPrice !== null) {
            $sql .= " AND (CASE WHEN sale_price > 0 THEN sale_price ELSE price END) >= ?";
            $params[] = $minPrice;
        }
        if ($maxPrice !== null) {
            $sql .= " AND (CASE WHEN sale_price > 0 THEN sale_price ELSE price END) <= ?";
            $params[] = $maxPrice;
        }
        $sql .= " ORDER BY (CASE WHEN sale_price > 0 THEN sale_price ELSE price END) DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $pdo->prepare($sql);
        foreach ($params as $idx => $val) {
            $type = is_int($val) ? PDO::PARAM_INT : (is_float($val) ? PDO::PARAM_STR : PDO::PARAM_STR);
            $stmt->bindValue($idx + 1, $val, $type);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        return [];
    }
}
}

// Render danh sách sản phẩm dạng Glassmorphism Card hiện đại không dùng emoji
if (!function_exists('renderProductListHtml')) {
function renderProductListHtml(array $products, string $intro = ''): string {
    if (empty($products)) return '';
    $html = $intro ? ($intro . "<br><br>") : "";
    foreach ($products as $p) {
        $currentPrice = ($p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
        $oldPriceStr = ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) 
            ? " <del class='ai-old-price'>" . formatVND($p['price']) . "</del>" 
            : "";
        $discountBadge = ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']) 
            ? " <span class='ai-discount-pill'>-" . round((1 - $p['sale_price']/$p['price']) * 100) . "%</span>" 
            : "";

        $html .= "<div class='ai-product-card'>";
        $html .= "  <div class='ai-product-card-top'>";
        $html .= "    <a href='index.php?page=product_detail&id={$p['id']}' class='ai-product-card-title'>";
        $html .= "      <span class='ai-card-svg-icon'><svg viewBox='0 0 24 24' width='13' height='13' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><rect x='5' y='2' width='14' height='20' rx='3'/><line x1='12' y1='18' x2='12.01' y2='18'/></svg></span>";
        $html .= "      <span>{$p['name']}</span>";
        $html .= "    </a>";
        if ($discountBadge) {
            $html .= "    {$discountBadge}";
        }
        $html .= "  </div>";
        $html .= "  <div class='ai-product-card-pricing'>";
        $html .= "    <span class='ai-card-current-price'>" . formatVND($currentPrice) . "</span>";
        $html .= "    {$oldPriceStr}";
        $html .= "    <span class='ai-card-vna'>VN/A Chính Hãng</span>";
        $html .= "  </div>";

        $specs = [];
        if (!empty($p['ram'])) $specs[] = "RAM " . $p['ram'];
        if (!empty($p['rom'])) $specs[] = "ROM " . $p['rom'];
        if (!empty($p['screen'])) $specs[] = $p['screen'];
        if (!empty($p['battery'])) $specs[] = $p['battery'];
        if (!empty($specs)) {
            $html .= "  <div class='ai-product-card-specs'>";
            foreach ($specs as $specItem) {
                $html .= "<span class='ai-spec-tag'>{$specItem}</span>";
            }
            $html .= "  </div>";
        }
        $html .= "</div>";
    }
    return $html;
}
}

$reply = '';
$suggestions = [];

// =========================================================================
// 1. CHÀO HỎI / GIAO TIẾP
// =========================================================================
if (preg_match('/^(chao|hello|hi|alo|chao ban|shop oi|ad oi|cho minh hoi|ad$|vphone$)/i', $cleanText)) {
    $reply = "Dạ em chào anh/chị! Em là <strong>V-Phone Cyber AI</strong> - Chuyên viên tư vấn bán hàng số Flagship 2026.<br><br>
    Hệ thống sẵn sàng hỗ trợ anh/chị tức thì:<br>
    • <span class='chat-tech-badge badge-blue'>Báo Giá Flagship</span> Tra cứu bảng giá & cấu hình các dòng máy mới nhất<br>
    • <span class='chat-tech-badge badge-indigo'>Trả Góp 0%</span> Duyệt online 15 phút, kỳ hạn linh hoạt, trả trước từ 0 VNĐ<br>
    • <span class='chat-tech-badge badge-emerald'>Bảo Hành 1-1</span> Đổi mới trong 30 ngày & đồng kiểm mở hộp trước khi thanh toán<br>
    • <span class='chat-tech-badge badge-amber'>Thu Cũ Đổi Mới</span> Định giá trực tuyến, trợ giá thu đổi tới 2.000.000 VNĐ.<br><br>
    Anh/chị đang quan tâm đến dòng điện thoại hoặc dịch vụ nào của V-Phone ạ?";
    $suggestions = ['Bảng giá iPhone mới nhất', 'Samsung Galaxy AI', 'Điện thoại chơi game khủng', 'Thủ tục trả góp 0%', 'Chính sách bảo hành 1-1'];
}

// =========================================================================
// 2. ĐỒNG KIỂM / KIỂM TRA HÀNG TRƯỚC KHI THANH TOÁN
// =========================================================================
elseif (preg_match('/(kiem tra hang|dong kiem|mo hop|xem hang|test may|kiem tra truoc|co duoc kiem tra)/i', $cleanText)) {
    $reply = "Dạ <strong>HOÀN TOÀN ĐƯỢC</strong> ạ! V-Phone Store áp dụng <strong>Chính Sách Đồng Kiểm 100%</strong> minh bạch tuyệt đối:<br><br>
    <span class='chat-tech-badge badge-purple'>Quy Trình Nhận Hàng</span><br>
    • Anh/chị được quyền mở hộp bưu kiện kiểm tra ngoại quan máy, phụ kiện và hóa đơn đi kèm.<br>
    • Bật nguồn test màn hình cảm ứng, kiểm tra đúng màu sắc, đúng số Serial/IMEI.<br>
    • Sau khi hoàn toàn hài lòng mới tiến hành thanh toán tiền mặt (COD) hoặc quét mã VietQR.<br><br>
    Nếu có bất kỳ vấn đề gì không đúng cam kết, anh/chị có quyền từ chối nhận hàng mà <strong>không mất bất kỳ khoản phí nào</strong>!";
    $suggestions = ['Thời gian giao hàng bao lâu', 'Phương thức thanh toán', 'Hotline 1800 6868'];
}

// =========================================================================
// 3. VẬN CHUYỂN, GIAO HÀNG & PHÍ SHIP
// =========================================================================
elseif (preg_match('/(giao hang|van chuyen|ship|bao lau thi toi|phi ship|giao hoa toc|phi van chuyen)/i', $cleanText)) {
    $reply = "Chính sách <strong>Giao Hàng & Vận Chuyển</strong> độc quyền tại V-Phone Store:<br><br>
    • <span class='chat-tech-badge badge-cyan'>Siêu Tốc 2 Giờ</span> Miễn phí hỏa tốc trong nội thành TP.HCM, Hà Nội và Đà Nẵng.<br>
    • <span class='chat-tech-badge badge-blue'>Giao Nhanh Toàn Quốc</span> Từ 1 - 3 ngày làm việc (Miễn phí 100% cước ship cho đơn từ 3 triệu đồng).<br>
    • <span class='chat-tech-badge badge-emerald'>Bảo Hiểm Vận Chuyển</span> Hàng hóa đóng gói túi khí chống sốc chuyên dụng, bảo hiểm 100% giá trị bưu phẩm.<br><br>
    Anh/chị có thể tra cứu đơn hàng trực tuyến tại: <a href='index.php?page=orders' class='text-primary fw-bold text-decoration-none'>Tra Cứu Đơn Hàng Online</a>";
    $suggestions = ['Đồng kiểm mở hộp', 'Chính sách bảo hành 1-1', 'Tra cứu đơn hàng'];
}

// =========================================================================
// 4. PHƯƠNG THỨC THANH TOÁN & HÓA ĐƠN VAT
// =========================================================================
elseif (preg_match('/(thanh toan|chuyen khoan|vietqr|momo|cod|tien mat|hoa don|vat|xuat hoa don)/i', $cleanText)) {
    $reply = "V-Phone Store hỗ trợ đa dạng <strong>Phương Thức Thanh Toán Thuận Tiện</strong>:<br><br>
    1. <span class='chat-tech-badge badge-blue'>COD</span> Thanh toán tiền mặt khi shipper giao tận nơi sau khi đồng kiểm máy.<br>
    2. <span class='chat-tech-badge badge-cyan'>VietQR Tự Động</span> Quét mã QR ngân hàng xác nhận giao dịch tức thì trong 3 giây (nhận thêm ưu đãi tới 1 triệu đồng).<br>
    3. <span class='chat-tech-badge badge-purple'>Ví Điện Tử MoMo</span> Thanh toán tiện lợi, bảo mật chuẩn quốc tế.<br>
    4. <span class='chat-tech-badge badge-indigo'>Thẻ Tín Dụng</span> Hỗ trợ cà thẻ Visa/Mastercard/JCB miễn phí tại tất cả showroom.<br><br>
    <span class='chat-tech-badge badge-emerald'>Hóa Đơn Điện Tử VAT</span> Tất cả sản phẩm xuất kho đều có hóa đơn GTGT/VAT hợp pháp đầy đủ cho cá nhân và công ty!";
    $suggestions = ['Hướng dẫn quét VietQR', 'Chính sách trả góp 0%', 'Chính sách bảo hành 1-1'];
}

// =========================================================================
// 5. TRẢ GÓP 0%
// =========================================================================
elseif (preg_match('/(tra gop|0%|lai suat|the tin dung|cccd|thu tuc tra gop|tra truoc|home credit|hd saison)/i', $cleanText)) {
    $reply = "Chương trình <strong>Mua Điện Thoại Trả Góp 0% Lãi Suất</strong> tại V-Phone cực kỳ linh hoạt:<br><br>
    <span class='chat-tech-badge badge-indigo'>Hình Thức 1: Thẻ Tín Dụng (Visa/Mastercard/JCB)</span><br>
    • Áp dụng hơn 25 ngân hàng lớn: Vietcombank, Techcombank, MB, VPBank, ACB...<br>
    • Lãi suất 0%, kỳ hạn linh hoạt 3, 6, 9, 12 tháng, không phụ phí ẩn.<br>
    • Duyệt online trong 2 phút, không cần nộp giấy tờ.<br><br>
    <span class='chat-tech-badge badge-cyan'>Hình Thức 2: CCCD Gắn Chip (Tài Chính)</span><br>
    • Chỉ cần hình chụp CCCD bản gốc (từ 18 tuổi trở lên).<br>
    • <strong>Trả trước từ 0 đồng</strong> (với máy dưới 15 triệu) hoặc 10 - 20% với Flagship cao cấp.<br>
    • Duyệt hồ sơ online trong 15 phút, không giữ giấy tờ gốc.<br><br>
    Anh/chị chỉ cần bấm nút <strong>'Mua Trả Góp'</strong> tại trang chi tiết sản phẩm để hoàn tất thủ tục!";
    $suggestions = ['Tư vấn iPhone trả góp 0%', 'Samsung trả góp 0%', 'Yêu cầu gọi lại tư vấn'];
}

// =========================================================================
// 6. BẢO HÀNH & ĐỔI TRẢ
// =========================================================================
elseif (preg_match('/(bao hanh|doi tra|1 doi 1|loi|hong|sua chua|roi vo|chinh sach bao hanh)/i', $cleanText)) {
    $reply = "Chính sách <strong>Bảo Hành Độc Quyền V-Phone Care</strong> đảm bảo an tâm trọn đời:<br><br>
    • <span class='chat-tech-badge badge-emerald'>Đổi Mới 1-1 Trong 30 Ngày</span> Nếu máy phát sinh bất kỳ lỗi phần cứng nào từ nhà sản xuất.<br>
    • <span class='chat-tech-badge badge-blue'>Bảo Hành 12 Tháng Toàn Diện</span> Bao gồm cả màn hình, cảm ứng, nguồn, camera, pin và bo mạch.<br>
    • <span class='chat-tech-badge badge-cyan'>Miễn Phí Vận Chuyển 2 Chiều</span> Khách ở xa chỉ cần đóng gói gửi bưu điện, V-Phone thanh toán toàn bộ cước phí.<br><br>
    Tra cứu hạn bảo hành theo IMEI trực tuyến tại: <a href='index.php?page=warranty' class='text-primary fw-bold text-decoration-none'>Tra Cứu Bảo Hành Online</a>";
    $suggestions = ['Tra cứu bảo hành online', 'Showroom tiếp nhận bảo hành', 'Hotline 1800 6868'];
}

// =========================================================================
// 7. THU CŨ ĐỔI MỚI / MÁY CŨ 99%
// =========================================================================
elseif (preg_match('/(may cu|like new|99%|cu|thu cu|doi may|len doi|trade-in)/i', $cleanText)) {
    $prods = queryProducts($pdo, 'Cũ 99%', 3);
    $reply = "Chương trình <strong>Thu Cũ Đổi Mới & Điện Thoại Cũ 99% Zin Keng</strong> tại V-Phone Store:<br><br>";
    $reply .= renderProductListHtml($prods);
    $reply .= "<br><span class='chat-tech-badge badge-amber'>Cam Kết Chất Lượng</span><br>• Đã qua 30 bước kiểm định nghiêm ngặt bởi kỹ thuật viên tay nghề cao.<br>• Pin zin còn trên 90%, ngoại hình tuyển chọn leng keng như mới.<br>• Vẫn được hưởng chính sách <strong>1 đổi 1 trong 30 ngày</strong> và bảo hành 12 tháng đầy đủ!<br><br>Xem kho máy tại: <a href='index.php?page=used-phones' class='text-primary fw-bold text-decoration-none'>Kho Máy Cũ 99% Zin Keng</a>";
    $suggestions = ['Định giá máy cũ lên đời', 'iPhone cũ 99%', 'Samsung cũ 99%'];
}

// =========================================================================
// 8. KHUYẾN MÃI, VOUCHER & QUÀ TẶNG KÈM
// =========================================================================
elseif (preg_match('/(khuyen mai|giam gia|voucher|ma giam|ma code|qua tang|uu dai|sale)/i', $cleanText)) {
    $reply = "<strong>Tổng Hợp Mã Ưu Đãi & Quà Tặng Đang Áp Dụng:</strong><br><br>
    • Mã <code class='text-danger bg-white px-2 py-0.5 rounded border fw-bold'>VPHONE500</code>: Giảm trực tiếp <strong>500.000 VNĐ</strong> cho đơn từ 15 triệu.<br>
    • Mã <code class='text-danger bg-white px-2 py-0.5 rounded border fw-bold'>FLAGSHIP2026</code>: Giảm ngay <strong>1.000.000 VNĐ</strong> khi thanh toán quét VietQR.<br>
    • <span class='chat-tech-badge badge-cyan'>Combo Quà Tặng</span> Củ sạc nhanh chính hãng 30W/65W + Cường lực cao cấp + Ốp lưng chống sốc.<br>
    • <span class='chat-tech-badge badge-amber'>Trợ Giá Thu Đổi</span> Giảm thêm <strong>2.000.000 VNĐ</strong> khi tham gia Thu Cũ Đổi Mới!";
    $suggestions = ['Xem Flagship mới nhất', 'Tư vấn mua trả góp 0%', 'Yêu cầu gọi lại tư vấn'];
}

// =========================================================================
// 9. HỆ THỐNG SHOWROOM & ĐỊA CHỈ
// =========================================================================
elseif (preg_match('/(dia chi|showroom|cua hang|o dau|chi nhanh|thoi gian lam viec|gio mo cua|ban do|map)/i', $cleanText)) {
    $reply = "Hệ thống <strong>Showroom Trải Nghiệm Flagship V-Phone Store</strong>:<br><br>
    • <span class='chat-tech-badge badge-rose'>TP. Hồ Chí Minh</span> 123 Đường Công Nghệ, P. Bến Nghé, Quận 1<br>
    • <span class='chat-tech-badge badge-rose'>TP. Hà Nội</span> 456 Phố Điện Tử, P. Dịch Vọng Hậu, Q. Cầu Giấy<br>
    • <span class='chat-tech-badge badge-rose'>TP. Đà Nẵng</span> 789 Đại Lộ Kỹ Thuật, Quận Hải Châu<br><br>
    <span class='chat-tech-badge badge-blue'>Giờ Hoạt Động</span> 08:00 - 21:30 hàng ngày (kể cả Thứ 7, Chủ Nhật & Lễ Tết).<br>
    Có sẵn bãi đỗ xe ô tô miễn phí và khu vực trải nghiệm máy thực tế không giới hạn!";
    $suggestions = ['Chỉ đường Showroom TP.HCM', 'Chỉ đường Showroom Hà Nội', 'Hotline 1800 6868'];
}

// =========================================================================
// 10. TỔNG ĐÀI / CSKH / GẶP CHUYÊN VIÊN
// =========================================================================
elseif (preg_match('/(cskh|khieu nai|cham soc|hotline|tong dai|sdt|so dien thoai|tu van vien|nhan vien|nguoi that|goi lai|gap nhan vien|chuyen vien|phan hoi)/i', $cleanText)) {
    $reply = "Đội ngũ <strong>CSKH & Chuyên Viên V-Phone</strong> luôn sẵn sàng hỗ trợ anh/chị:<br><br>
    • <span class='chat-tech-badge badge-red'>Hotline Miễn Phí</span> <a href='tel:18006868' class='text-danger fw-bold fs-6 text-decoration-none'>1800 6868</a> (Phục vụ 08:00 - 21:30 hàng ngày)<br>
    &nbsp;&nbsp;- Nhánh 1: Tư vấn bán hàng & báo giá Flagship<br>
    &nbsp;&nbsp;- Nhánh 2: Hỗ trợ kỹ thuật & chính sách bảo hành 1 đổi 1<br>
    &nbsp;&nbsp;- Nhánh 3: Chăm sóc khách hàng & xử lý khiếu nại<br><br>
    • <span class='chat-tech-badge badge-cyan'>Hỗ Trợ Trực Tuyến</span> Anh/chị có thể nhấn <strong>'Gửi Yêu Cầu Hỗ Trợ & CSKH'</strong> tại cụm nút nổi. Đội ngũ chuyên viên sẽ gọi lại hoặc phản hồi bằng văn bản vào <strong>'Hộp Thư Phản Hồi CSKH'</strong> trong vòng 5 phút!";
    $suggestions = ['Gọi ngay 1800 6868', 'Gửi yêu cầu đến CSKH', 'Hộp thư phản hồi CSKH'];
}

// =========================================================================
// 11. TÌM KIẾM THEO TẦM GIÁ / NGÂN SÁCH (BUDGET SEARCH)
// =========================================================================
elseif (preg_match('/(duoi|tam|khoang|tam gia|muc gia|gia re hon|duoi muc)\s*([0-9]+)\s*(trieu|tr|cu|m)/i', $cleanText, $matches) ||
    preg_match('/(tu\s*([0-9]+)\s*(den|-)\s*([0-9]+)\s*(trieu|tr|cu|m))/i', $cleanText, $rangeMatches)) {
    
    $min = null;
    $max = null;

    if (!empty($rangeMatches)) {
        $min = (float)$rangeMatches[2] * 1000000;
        $max = (float)$rangeMatches[4] * 1000000;
        $titlePrice = "từ " . $rangeMatches[2] . " - " . $rangeMatches[4] . " triệu";
    } elseif (!empty($matches)) {
        $max = (float)$matches[2] * 1000000;
        $titlePrice = "dưới " . $matches[2] . " triệu";
    }

    $prods = queryProductsByPrice($pdo, $min, $max, 3);
    if (!empty($prods)) {
        $reply = renderProductListHtml($prods, "Dạ, trong tầm giá <strong>{$titlePrice}</strong>, V-Phone Store xin gợi ý những mẫu điện thoại bán chạy và cấu hình mạnh nhất hiện nay:");
        $reply .= "<br>Tất cả sản phẩm đều được tặng kèm <strong>bảo hành 1 đổi 1 trong 30 ngày</strong> và hỗ trợ <strong>trả góp 0%</strong> duyệt online 15 phút. Anh/chị bấm vào tên máy để xem chi tiết nhé!";
    } else {
        $reply = "Dạ, hiện các mẫu trong mức giá {$titlePrice} đang có nhiều lựa chọn tại kho hàng. Anh/chị có thể tham khảo thêm các dòng Flagship 2026 hoặc máy Cũ 99% đang có giá cực tốt tại V-Phone!";
    }
    $suggestions = ['Tư vấn trả góp 0%', 'Xem điện thoại Cũ 99%', 'Hotline tư vấn 1800 6868'];
}

// =========================================================================
// 12. TƯ VẤN THEO NHU CẦU: CHƠI GAME / GAMING
// =========================================================================
elseif (preg_match('/(choi game|gaming|game|fps|muot ma|cau hinh khung|chien game|rog)/i', $cleanText)) {
    $prods = queryProducts($pdo, 'ROG', 2);
    if (empty($prods)) {
        $prods = queryProducts($pdo, 'Ultra', 2);
    }
    $reply = "Nếu anh/chị cần điện thoại chuyên <strong>Chiến Game / Cấu Hình Khủng</strong>, V-Phone đề xuất các mẫu máy hàng đầu có chip xử lý mạnh nhất, tản nhiệt buồng hơi và màn hình tần số quét 144Hz - 165Hz:<br><br>";
    $reply .= renderProductListHtml($prods);
    $reply .= "<br><span class='chat-tech-badge badge-purple'>Đặc Quyền Game Thủ</span><br>• Tặng củ sạc siêu nhanh 65W/120W.<br>• Giảm 20% khi mua kèm sò lạnh tản nhiệt và tai nghe gaming chuyên dụng.<br>• Hỗ trợ trả góp 0% trả trước từ 0 VNĐ!";
    $suggestions = ['Tư vấn ASUS ROG Phone 8', 'Galaxy S26 Ultra Gaming', 'Chính sách trả góp 0%'];
}

// =========================================================================
// 13. TƯ VẤN THEO NHU CẦU: CHỤP ẢNH / QUAY PHIM
// =========================================================================
elseif (preg_match('/(chup anh|chup hinh|quay phim|quay video|camera|song ao|tiktok|leica)/i', $cleanText)) {
    $prods = queryProducts($pdo, 'Xiaomi 16 Ultra', 1);
    $prods2 = queryProducts($pdo, 'iPhone 18', 1);
    $prods3 = queryProducts($pdo, 'Sony Xperia', 1);
    $all = array_merge($prods, $prods2, $prods3);

    $reply = "Dạ, nếu anh/chị chú trọng <strong>Camera Chụp Ảnh & Quay Phim Đỉnh Cao</strong>, đây là các dòng Flagship sở hữu cảm biến 1-inch và ống kính quang học chuyên nghiệp nhất:<br><br>";
    $reply .= renderProductListHtml($all);
    $reply .= "<br><span class='chat-tech-badge badge-cyan'>Công Nghệ Quang Học</span> Hỗ trợ chống rung quang học OIS thế hệ mới, zoom quang học sắc nét và quay video 4K/8K 120fps chuẩn điện ảnh!";
    $suggestions = ['Xiaomi 16 Ultra Leica', 'iPhone 18 Pro Max Camera', 'Sony Xperia 1 VI'];
}

// =========================================================================
// 14. MÀN HÌNH GẬP / FOLD / FLIP
// =========================================================================
elseif (preg_match('/(man hinh gap|\bfold\b|\bflip\b|tri-fold|3 man|mate xt)/i', $cleanText)) {
    $prods = queryProducts($pdo, 'Fold', 2);
    $prods2 = queryProducts($pdo, 'Mate XT', 1);
    $prods3 = queryProducts($pdo, 'Duo', 1);
    $all = array_merge($prods2, $prods, $prods3);

    $reply = "Dạ, V-Phone Store đang có sẵn các siêu phẩm <strong>Điện Thoại Màn Hình Gập Đẳng Cấp 2026</strong> mang phong cách doanh nhân sang trọng:<br><br>";
    $reply .= renderProductListHtml(array_slice($all, 0, 3));
    $reply .= "<br><span class='chat-tech-badge badge-indigo'>Đặc Quyền Màn Gập</span> Tặng gói bảo hiểm rơi vỡ màn hình 1 năm và dịch vụ thay dán màn hình miễn phí trọn đời tại các showroom V-Phone!";
    $suggestions = ['Huawei Mate XT Gập 3', 'Samsung Z Fold5', 'OPPO Find N5 Duo'];
}

// =========================================================================
// 15. IPHONE / APPLE
// =========================================================================
elseif (preg_match('/(\biphone\b|\bapple\b|\bios\b|\bip\b|\bpromax\b|\bpro max\b)/i', $cleanText)) {
    $prods = queryProducts($pdo, 'iPhone', 3);
    $reply = "Dạ, V-Phone hiện đang phân phối đầy đủ các dòng <strong>Apple iPhone Chính Hãng VN/A</strong> mới nhất:<br><br>";
    $reply .= renderProductListHtml($prods);
    $reply .= "<br><span class='chat-tech-badge badge-blue'>Cam Kết Apple VN/A</span><br>• Máy mới 100% nguyên seal chưa kích hoạt.<br>• Tặng bảo hành 1 đổi 1 trong 30 ngày đầu.<br>• Hỗ trợ thu cũ đổi mới trợ giá tới 2 triệu đồng!";
    $suggestions = ['iPhone 18 Pro Max', 'Chính sách trả góp iPhone', 'Xem iPhone cũ 99%'];
}

// =========================================================================
// 16. SAMSUNG / GALAXY
// =========================================================================
elseif (preg_match('/(\bsamsung\b|\bgalaxy\b|\bs23\b|\bs24\b|\bs26\b|ultra titan)/i', $cleanText)) {
    $prods = queryProducts($pdo, 'Samsung', 3);
    $reply = "Các dòng <strong>Samsung Galaxy Flagship Chính Hãng</strong> đỉnh cao đang có giá cực kỳ tốt tại V-Phone:<br><br>";
    $reply .= renderProductListHtml($prods);
    $reply .= "<br><span class='chat-tech-badge badge-cyan'>Ưu Đãi Samsung</span> Tặng kèm củ sạc nhanh 45W chính hãng Samsung và hỗ trợ trả góp 0% lãi suất duyệt hồ sơ 15 phút!";
    $suggestions = ['Samsung Galaxy S26 Ultra', 'Trả góp Samsung 0%', 'So sánh Samsung vs iPhone'];
}

// =========================================================================
// 17. XIAOMI / OPPO / VIVO / SONY / PIXEL / HUAWEI
// =========================================================================
elseif (preg_match('/(\bxiaomi\b|\bredmi\b|\boppo\b|\bvivo\b|\bsony\b|\bpixel\b|\bhuawei\b|\bhonor\b)/i', $cleanText, $brandMatch)) {
    $brandName = ucfirst($brandMatch[1]);
    $prods = queryProducts($pdo, $brandName, 3);
    $reply = "Dạ, các sản phẩm <strong>{$brandName}</strong> chính hãng tại V-Phone Store:<br><br>";
    $reply .= renderProductListHtml($prods);
    $reply .= "<br><span class='chat-tech-badge badge-emerald'>Bảo Hành Toàn Diện</span> Tất cả sản phẩm đều được bảo hành chính hãng toàn diện 12 tháng và hỗ trợ trả góp 0%!";
    $suggestions = ['Chính sách bảo hành 1-1', 'Thủ tục trả góp 0%', 'Xem khuyến mãi'];
}

// =========================================================================
// 18. TÌM KIẾM TỰ DO / TÊN MÁY TÙY Ý CỦA KHÁCH
// =========================================================================
else {
    $cleanQuery = preg_replace('/[^a-zA-Z0-9\s]/', '', $userMessage);
    $prods = queryProducts($pdo, $cleanQuery, 3);

    if (!empty($prods)) {
        $reply = renderProductListHtml($prods, "Dạ, theo yêu cầu <em>'{$userMessage}'</em> của anh/chị, em tìm thấy các dòng máy phù hợp nhất tại V-Phone Store:");
        $reply .= "<br>Tất cả sản phẩm đều sẵn hàng tại showroom, bảo hành 1 đổi 1 trong 30 ngày và hỗ trợ trả góp 0%. Anh/chị bấm vào tên máy để xem chi tiết nhé!";
        $suggestions = ['Tư vấn trả góp 0%', 'Chính sách bảo hành 1-1', 'Gặp tư vấn viên'];
    } else {
        $reply = "Dạ, câu hỏi của anh/chị rất hay! Em là <strong>V-Phone Cyber AI</strong> chuyên gia tư vấn bán hàng số thông minh.<br><br>
        Để được hỗ trợ chi tiết nhất, anh/chị có thể thử hỏi em về: <em>Bảng giá iPhone 18, điện thoại chơi game, điện thoại dưới 10 triệu, thủ tục trả góp 0%, đồng kiểm hoặc bảo hành 1 đổi 1</em>...<br><br>
        Hoặc anh/chị có thể liên hệ ngay Hotline miễn phí <strong class='text-danger'>1800 6868</strong> để chuyên viên tư vấn trực tiếp ngay ạ!";
        $suggestions = ['Bảng giá Flagship 2026', 'Chính sách trả góp 0%', 'Bảo hành 1 đổi 1', 'Yêu cầu gọi lại tư vấn'];
    }
}

echo json_encode([
    'success' => true,
    'reply' => $reply,
    'suggestions' => $suggestions,
    'timestamp' => date('H:i')
], JSON_UNESCAPED_UNICODE);
