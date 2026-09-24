<?php
require_once 'config/database.php';
$pageTitle = 'Tin Tức & Đánh Giá Công Nghệ - V-Phone Tech';

// Danh sách các bài viết công nghệ nổi bật
$articles = [
    [
        'id' => 1,
        'title' => 'Tổng hợp tin đồn iPhone 18 Pro Max: Chip A20 Pro 2nm và màn hình Dynamic Island thu nhỏ',
        'badge' => 'TIN RÒ RỈ 2026',
        'badge_class' => 'bg-danger',
        'date' => '24/09/2026',
        'read_time' => '5 phút đọc',
        'image' => 'assets/images/products/iphone-18-promax.png',
        'summary' => 'Apple được cho là sẽ ra mắt thế hệ iPhone 18 với tiến trình 2nm siêu tiết kiệm pin, khung viền titan siêu nhẹ và camera tiềm vọng zoom quang học 10x hoàn toàn mới.',
        'content' => 'Theo các nguồn tin uy tín từ chuỗi cung ứng, dòng iPhone 18 Pro Max năm 2026 sẽ đánh dấu bước nhảy vọt lớn nhất của Apple. Máy sẽ sử dụng vi xử lý Apple A20 Pro xây dựng trên tiến trình 2nm của TSMC, mang lại hiệu năng đồ họa tăng 35% trong khi tiết kiệm điện năng tới 40%.\n\nNgoài ra, hệ thống camera tele tiềm vọng sẽ nâng cấp lên cảm biến 48MP hỗ trợ zoom quang học 10x và zoom kỹ thuật số lên tới 100x. Thiết kế mặt trước sẽ tiếp tục tối ưu phần viền màn hình chỉ còn 1.0mm, mang lại trải nghiệm thị giác gần như tràn viền tuyệt đối.'
    ],
    [
        'id' => 2,
        'title' => 'Đánh giá chi tiết Samsung Galaxy Z Tri-Fold: Kỷ nguyên điện thoại gập ba đã đến!',
        'badge' => 'ĐÁNH GIÁ HOT',
        'badge_class' => 'bg-primary',
        'date' => '23/09/2026',
        'read_time' => '7 phút đọc',
        'image' => 'assets/images/products/samsung-tri-fold.png',
        'summary' => 'Chiếc smartphone gập 3 màn hình đầu tiên của Samsung cho phép mở rộng không gian hiển thị lên đến 10.2 inch, biến chiếc điện thoại thành máy tính bảng trong chớp mắt.',
        'content' => 'Galaxy Z Tri-Fold là câu trả lời đanh thép của Samsung dành cho thị trường di động toàn cầu. Với cơ chế gập kép hình chữ Z độc đáo, người dùng có thể sử dụng máy ở dạng màn hình ngoài 6.4 inch như điện thoại thông thường, hoặc mở ra toàn bộ thành màn hình 10.2 inch Dynamic AMOLED 3X.\n\nHệ thống bản lề FlexHinge thế hệ mới được gia cố bằng chất liệu Armor Aluminum siêu bền, cam kết độ bền trên 300.000 lần gập mở. Giao diện One UI 8.0 được tinh chỉnh đặc biệt cho khả năng chạy đa nhiệm cùng lúc 4 ứng dụng trên màn hình lớn.'
    ],
    [
        'id' => 3,
        'title' => 'Bí quyết chọn mua iPhone cũ 99%: Cách kiểm tra pin, màn hình và camera tránh bị lừa',
        'badge' => 'MẸO CÔNG NGHỆ',
        'badge_class' => 'bg-success',
        'date' => '22/09/2026',
        'read_time' => '4 phút đọc',
        'image' => 'assets/images/products/iphone-15-promax.png',
        'summary' => 'Hướng dẫn chi tiết từng bước kiểm tra từ vỏ máy, màn hình TrueTone, 3uTools cho đến dung lượng pin thực tế khi đi mua iPhone cũ đã qua sử dụng.',
        'content' => 'Khi mua iPhone cũ 99%, điều quan trọng nhất là kiểm tra xem máy đã bị thay màn hình hoặc ép kính hay chưa. Bạn hãy vào Cài đặt -> Màn hình & Độ sáng kiểm tra tính năng TrueTone. Sau đó, vuốt thanh tăng giảm độ sáng để xem cảm ứng có mượt không.\n\nTiếp theo, hãy kiểm tra số lần sạc và độ chai pin trong Cài đặt -> Pin. Đối với máy cũ 99%, pin chuẩn thường dao động từ 88% đến 96%. Tại V-Phone, mọi máy cũ đều được kỹ thuật viên kiểm tra 32 bước nghiêm ngặt và cam kết zin 100% kèm bảo hành 6 tháng 1 đổi 1.'
    ],
    [
        'id' => 4,
        'title' => 'So sánh Galaxy S26 Ultra và iPhone 17 Pro Max: Cuộc chiến của các vì sao',
        'badge' => 'SO SÁNH',
        'badge_class' => 'bg-info text-dark',
        'date' => '20/09/2026',
        'read_time' => '6 phút đọc',
        'image' => 'assets/images/products/samsung-s26-ultra.png',
        'summary' => 'Nên chọn Galaxy S26 Ultra với bút S-Pen quyền năng và camera 200MP, hay chọn iPhone 17 Pro Max với sự mượt mà và hệ sinh thái iOS bền bỉ?',
        'content' => 'Cả Galaxy S26 Ultra và iPhone 17 Pro Max đều là những siêu phẩm đứng đầu thế giới hiện nay. Trong khi Samsung chiếm ưu thế hoàn toàn về camera siêu zoom và các tính năng Galaxy AI phục vụ công việc hàng ngày, thì Apple lại ghi điểm ở khả năng quay video ProRes đỉnh cao và giữ giá tốt hơn sau nhiều năm sử dụng.\n\nNếu bạn là người thích vọc vạch công nghệ và cần ghi chú nhanh bằng bút thì S26 Ultra là lựa chọn số 1. Còn nếu bạn cần một thiết bị ổn định tuyệt đối, chơi game mát và đồng bộ tốt với iPad/MacBook thì iPhone 17 Pro Max là chân ái.'
    ],
    [
        'id' => 5,
        'title' => 'Xiaomi 16 Ultra Leica Edition: Đưa máy ảnh chuyên nghiệp vào trong túi quần bạn',
        'badge' => 'TIN MỚI',
        'badge_class' => 'bg-warning text-dark',
        'date' => '18/09/2026',
        'read_time' => '4 phút đọc',
        'image' => 'assets/images/products/xiaomi-16-ultra.png',
        'summary' => 'Cụm camera hợp tác cùng huyền thoại Leica với cảm biến 1 inch thế hệ mới và lớp phủ chống lóa T*, biến Xiaomi 16 Ultra thành quái vật nhiếp ảnh.',
        'content' => 'Xiaomi tiếp tục chứng minh vị thế dẫn đầu về nhiếp ảnh di động khi trang bị cho 16 Ultra cảm biến chính Sony Lytia 1 inch cùng ống kính quang học Leica Summilux. Màu ảnh chụp ra có độ sâu trường ảnh tự nhiên, tương phản cao và tái tạo màu da cực kỳ chân thực, gần như xóa nhòa ranh giới giữa điện thoại và máy ảnh DSLR.'
    ],
    [
        'id' => 6,
        'title' => 'Top 4 điện thoại cũ giá dưới 10 triệu đáng mua nhất cho học sinh, sinh viên 2026',
        'badge' => 'TƯ VẤN MUA',
        'badge_class' => 'bg-secondary',
        'date' => '15/09/2026',
        'read_time' => '5 phút đọc',
        'image' => 'assets/images/products/iphone-11.png',
        'summary' => 'Với ngân sách dưới 10 triệu, bạn hoàn toàn có thể sở hữu iPhone 11 cũ giá 5tr4, iPhone 13 cũ giá 10tr hay Galaxy A55 5G nguyên zin bảo hành dài hạn.',
        'content' => '1. iPhone 11 64GB cũ (5.490.000 đ): Dù đã ra mắt lâu nhưng chip A13 Bionic vẫn lướt TikTok, Facebook và chơi Liên Quân cực mượt.\n\n2. iPhone 13 128GB cũ (10.490.000 đ): Thiết kế vuông vức thời thượng, màn hình tai thỏ nhỏ gọn và thời lượng pin cực trâu.\n\n3. Samsung Galaxy A55 5G cũ (5.990.000 đ): Màn hình 120Hz siêu mượt, hỗ trợ 5G và pin 5000 mAh dùng thoải mái 2 ngày.'
    ]
];

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<!-- BANNER TRANG TIN TỨC CÔNG NGHỆ -->
<div class="container my-4">
    <div class="p-4 p-md-5 rounded-4 text-white shadow-sm" style="background: linear-gradient(135deg, #072a48 0%, #0066cc 60%, #38bdf8 100%);">
        <div class="col-lg-8 py-2">
            <span class="badge bg-white text-primary px-3 py-2 fw-bold rounded-pill mb-3">
                <i class="fa-solid fa-bolt me-1"></i> V-PHONE TECH NEWS
            </span>
            <h1 class="display-5 fw-bold mb-2">Tin Tức & Đánh Giá Công Nghệ</h1>
            <p class="fs-5 opacity-90 mb-0">Cập nhật xu hướng smartphone mới nhất, tin đồn rò rỉ và cẩm nang mẹo công nghệ hữu ích mỗi ngày.</p>
        </div>
    </div>
</div>

<div class="container my-5">
    <!-- BÀI VIẾT TIÊU ĐIỂM (HERO ARTICLE) -->
    <div class="card border-0 rounded-4 shadow-sm overflow-hidden mb-5 bg-white p-3 p-md-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-5 text-center">
                <div class="p-4 bg-light rounded-4 d-flex align-items-center justify-content-center" style="height: 320px;">
                    <img src="<?= htmlspecialchars($articles[0]['image']) ?>" alt="" style="max-height: 280px; object-fit: contain;">
                </div>
            </div>
            <div class="col-lg-7">
                <span class="badge <?= $articles[0]['badge_class'] ?> rounded-pill px-3 py-1 mb-2 fw-bold"><?= $articles[0]['badge'] ?></span>
                <h3 class="fw-bold text-dark mb-3"><?= htmlspecialchars($articles[0]['title']) ?></h3>
                <p class="text-secondary lead fs-6 mb-4"><?= htmlspecialchars($articles[0]['summary']) ?></p>
                <div class="d-flex align-items-center gap-3 text-muted small mb-4">
                    <span><i class="fa-regular fa-calendar me-1"></i><?= $articles[0]['date'] ?></span>
                    <span><i class="fa-regular fa-clock me-1"></i><?= $articles[0]['read_time'] ?></span>
                    <span><i class="fa-solid fa-user-pen me-1"></i>BTV V-Phone</span>
                </div>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" onclick="readArticle(0)">
                    Đọc toàn bộ bài viết <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- DANH SÁCH BÀI VIẾT DẠNG LƯỚI (GRID) -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-newspaper text-primary me-2"></i>Bài Viết Mới Nhất
        </h4>
        <span class="badge bg-white text-secondary border px-3 py-2 rounded-pill"><?= count($articles) ?> bài viết</span>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($articles as $index => $item): ?>
            <?php if ($index == 0) continue; // Bỏ qua bài tiêu điểm vì đã hiển thị ở trên ?>
            <div class="col">
                <div class="card h-100 border-0 rounded-4 shadow-sm bg-white overflow-hidden d-flex flex-column product-card">
                    <div class="p-4 bg-light text-center" style="height: 200px; display:flex; align-items:center; justify-content:center;">
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="" style="max-height: 160px; object-fit: contain;">
                    </div>
                    <div class="card-body p-4 d-flex flex-column">
                        <div>
                            <span class="badge <?= $item['badge_class'] ?> rounded-pill px-2 py-1 mb-2 small fw-bold"><?= $item['badge'] ?></span>
                            <h6 class="card-title fw-bold text-dark text-truncate-2 mb-2" style="font-size: 1rem;"><?= htmlspecialchars($item['title']) ?></h6>
                            <p class="text-secondary small text-truncate-2 mb-3"><?= htmlspecialchars($item['summary']) ?></p>
                        </div>
                        <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?= $item['date'] ?></small>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-bold" onclick="readArticle(<?= $index ?>)">
                                Đọc tiếp <i class="fa-solid fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL ĐỌC BÀI VIẾT CHI TIẾT (READER MODAL CHUẨN TẠP CHÍ) -->
<!-- ========================================================================= -->
<div class="modal fade" id="articleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 pb-0">
                <span class="badge bg-primary rounded-pill px-3 py-1 fw-bold" id="modalArticleBadge">TIN TỨC</span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 p-md-5 pt-2">
                <h3 class="fw-bold text-dark mb-3" id="modalArticleTitle">Tiêu đề bài viết</h3>
                <div class="d-flex align-items-center gap-3 text-muted small mb-4 border-bottom pb-3">
                    <span id="modalArticleDate"><i class="fa-regular fa-calendar me-1"></i>24/09/2026</span>
                    <span id="modalArticleTime"><i class="fa-regular fa-clock me-1"></i>5 phút đọc</span>
                    <span><i class="fa-solid fa-newspaper text-primary me-1"></i>V-Phone Tech Blog</span>
                </div>

                <div class="text-center p-3 bg-light rounded-4 mb-4">
                    <img id="modalArticleImg" src="" alt="" style="max-height: 240px; object-fit: contain;">
                </div>

                <div class="lead fs-6 text-dark" style="line-height: 1.8; white-space: pre-line;" id="modalArticleContent">
                    Nội dung bài viết...
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <a href="index.php" class="btn btn-primary rounded-pill fw-bold px-4">
                        <i class="fa-solid fa-mobile-screen me-2"></i>Xem các mẫu điện thoại này
                    </a>
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const allArticles = <?= json_encode($articles, JSON_UNESCAPED_UNICODE) ?>;
let articleBsModal = null;

function readArticle(index) {
    const item = allArticles[index];
    if (!item) return;

    if (!articleBsModal) {
        articleBsModal = new bootstrap.Modal(document.getElementById('articleModal'));
    }

    document.getElementById('modalArticleBadge').innerText = item.badge;
    document.getElementById('modalArticleTitle').innerText = item.title;
    document.getElementById('modalArticleDate').innerHTML = `<i class="fa-regular fa-calendar me-1"></i>${item.date}`;
    document.getElementById('modalArticleTime').innerHTML = `<i class="fa-regular fa-clock me-1"></i>${item.read_time}`;
    document.getElementById('modalArticleImg').src = item.image;
    document.getElementById('modalArticleContent').innerText = item.content;

    articleBsModal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
