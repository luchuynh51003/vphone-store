<?php
require_once 'config/database.php';
require_once 'app/models/ArticleModel.php';
$pageTitle = 'Tin Tức & Đánh Giá Công Nghệ - V-Phone Tech';
$articleModel = new ArticleModel($pdo);
$articleDefaults = require 'app/data/news_defaults.php';
$articleModel->ensureTable($articleDefaults);
$articles = $articleModel->getPublished();

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
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
    <?php if (!empty($articles)): ?>
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
    <?php else: ?>
        <div class="bg-white border rounded-4 p-5 text-center text-secondary">Hiện chưa có bài viết được xuất bản.</div>
    <?php endif; ?>
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

<?php require_once 'app/views/includes/footer.php'; ?>
