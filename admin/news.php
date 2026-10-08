<?php
require_once '../config/database.php';
require_once '../app/models/ArticleModel.php';

$pageTitle = 'Quản Lý Tin Tức - V-Phone Admin';
require_once 'includes/header.php';

$articleModel = new ArticleModel($pdo);
$defaults = require '../app/data/news_defaults.php';
$articleModel->ensureTable($defaults);
$message = '';
$editId = (int)($_GET['edit_id'] ?? 0);
$editingArticle = null;
$badgeClasses = [
    'bg-danger' => 'Đỏ',
    'bg-primary' => 'Xanh dương',
    'bg-success' => 'Xanh lá',
    'bg-info text-dark' => 'Xanh nhạt',
    'bg-warning text-dark' => 'Vàng',
    'bg-secondary' => 'Xám'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_article'])) {
    $articleId = (int)($_POST['article_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $badge = trim($_POST['badge'] ?? 'TIN MỚI');
    $badgeClass = $_POST['badge_class'] ?? 'bg-primary';
    $articleDate = trim($_POST['article_date'] ?? '');
    $readTime = trim($_POST['read_time'] ?? '5 phút đọc');
    $image = trim($_POST['image'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    if ($title === '' || $articleDate === '' || $image === '' || $summary === '' || $content === '') {
        $message = 'Vui lòng điền tiêu đề, ngày, ảnh, tóm tắt và nội dung.';
    } elseif (!isset($badgeClasses[$badgeClass])) {
        $message = 'Kiểu nhãn không hợp lệ.';
    } else {
        if ($articleId > 0) {
            $stmt = $pdo->prepare('UPDATE articles SET title = ?, badge = ?, badge_class = ?, article_date = ?, read_time = ?, image = ?, summary = ?, content = ?, is_published = ? WHERE id = ?');
            $stmt->execute([$title, $badge, $badgeClass, $articleDate, $readTime, $image, $summary, $content, $isPublished, $articleId]);
            $message = 'Đã cập nhật bài viết.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO articles (title, badge, badge_class, article_date, read_time, image, summary, content, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $badge, $badgeClass, $articleDate, $readTime, $image, $summary, $content, $isPublished]);
            $message = 'Đã thêm bài viết.';
        }
        $editId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_article'])) {
    $stmt = $pdo->prepare('DELETE FROM articles WHERE id = ?');
    $stmt->execute([(int)($_POST['article_id'] ?? 0)]);
    $message = 'Đã xóa bài viết.';
    $editId = 0;
}

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM articles WHERE id = ?');
    $stmt->execute([$editId]);
    $editingArticle = $stmt->fetch() ?: null;
}

$articles = $pdo->query('SELECT * FROM articles ORDER BY id DESC')->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Quản lý tin tức</h3>
        <p class="text-secondary small mb-0">Soạn bài, cập nhật nội dung và bật/tắt hiển thị trên website</p>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
<?php endif; ?>

<section class="bg-white border rounded-4 shadow-sm p-4 mb-4">
    <h5 class="fw-bold mb-3"><?= $editingArticle ? 'Sửa bài viết' : 'Tạo bài viết' ?></h5>
    <form method="POST" action="news.php<?= $editingArticle ? '?edit_id=' . (int)$editingArticle['id'] : '' ?>">
        <input type="hidden" name="article_id" value="<?= (int)($editingArticle['id'] ?? 0) ?>">
        <div class="row g-3">
            <div class="col-lg-8">
                <label for="articleTitle" class="form-label small fw-semibold">Tiêu đề</label>
                <input id="articleTitle" name="title" class="form-control" maxlength="255" required value="<?= htmlspecialchars($editingArticle['title'] ?? '') ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="articleBadge" class="form-label small fw-semibold">Nhãn</label>
                <input id="articleBadge" name="badge" class="form-control" maxlength="80" value="<?= htmlspecialchars($editingArticle['badge'] ?? 'TIN MỚI') ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="badgeClass" class="form-label small fw-semibold">Màu nhãn</label>
                <select id="badgeClass" name="badge_class" class="form-select">
                    <?php foreach ($badgeClasses as $class => $label): ?>
                        <option value="<?= htmlspecialchars($class) ?>" <?= ($editingArticle['badge_class'] ?? 'bg-primary') === $class ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="articleDate" class="form-label small fw-semibold">Ngày hiển thị</label>
                <input id="articleDate" name="article_date" class="form-control" required placeholder="24/09/2026" value="<?= htmlspecialchars($editingArticle['article_date'] ?? date('d/m/Y')) ?>">
            </div>
            <div class="col-md-3">
                <label for="readTime" class="form-label small fw-semibold">Thời gian đọc</label>
                <input id="readTime" name="read_time" class="form-control" value="<?= htmlspecialchars($editingArticle['read_time'] ?? '5 phút đọc') ?>">
            </div>
            <div class="col-md-6">
                <label for="articleImage" class="form-label small fw-semibold">Đường dẫn ảnh</label>
                <input id="articleImage" name="image" class="form-control" required value="<?= htmlspecialchars($editingArticle['image'] ?? '') ?>" placeholder="assets/images/products/iphone-18-promax.png">
            </div>
            <div class="col-12">
                <label for="articleSummary" class="form-label small fw-semibold">Tóm tắt</label>
                <textarea id="articleSummary" name="summary" class="form-control" rows="2" required><?= htmlspecialchars($editingArticle['summary'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label for="articleContent" class="form-label small fw-semibold">Nội dung</label>
                <textarea id="articleContent" name="content" class="form-control" rows="8" required><?= htmlspecialchars($editingArticle['content'] ?? '') ?></textarea>
            </div>
            <div class="col-12 d-flex align-items-center justify-content-between">
                <div class="form-check">
                    <input type="checkbox" id="articlePublished" name="is_published" value="1" class="form-check-input" <?= !isset($editingArticle['is_published']) || $editingArticle['is_published'] ? 'checked' : '' ?>>
                    <label for="articlePublished" class="form-check-label fw-semibold">Xuất bản trên website</label>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($editingArticle): ?><a href="news.php" class="btn btn-light">Hủy sửa</a><?php endif; ?>
                    <button type="submit" name="save_article" value="1" class="btn btn-primary fw-semibold"><i class="fa-solid fa-floppy-disk me-1"></i>Lưu bài viết</button>
                </div>
            </div>
        </div>
    </form>
</section>

<section class="bg-white border rounded-4 shadow-sm p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Bài viết</th><th>Nhãn</th><th>Ngày</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($article['title']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($article['read_time']) ?></small>
                        </td>
                        <td><span class="badge <?= htmlspecialchars($article['badge_class']) ?>"><?= htmlspecialchars($article['badge']) ?></span></td>
                        <td><?= htmlspecialchars($article['article_date']) ?></td>
                        <td><span class="badge <?= $article['is_published'] ? 'bg-success' : 'bg-secondary' ?>"><?= $article['is_published'] ? 'Đã xuất bản' : 'Bản nháp' ?></span></td>
                        <td class="text-end text-nowrap">
                            <a href="news.php?edit_id=<?= (int)$article['id'] ?>" class="btn btn-sm btn-outline-primary" aria-label="Sửa bài viết"><i class="fa-solid fa-pen"></i></a>
                            <form method="POST" action="news.php" class="d-inline" id="deleteArticleForm<?= (int)$article['id'] ?>">
                                <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
                                <input type="hidden" name="delete_article" value="1">
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-danger" aria-label="Xóa bài viết" data-bs-toggle="modal" data-bs-target="#adminDeleteConfirmModal" data-confirm-form="deleteArticleForm<?= (int)$article['id'] ?>" data-confirm-message="Xóa bài viết <?= htmlspecialchars($article['title']) ?>?">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
