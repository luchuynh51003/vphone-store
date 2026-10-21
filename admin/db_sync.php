<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../database/db_sync_tool.php';

// Chỉ Quản trị viên cấp cao mới có quyền thao tác sao lưu/khôi phục CSDL
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 1) {
    echo "<div class='container py-5 text-center'><div class='alert alert-danger'>Bạn không có quyền truy cập chức năng này. Chỉ Quản trị viên tối cao mới có thể quản lý CSDL.</div></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$syncTool = new DatabaseSyncTool($pdo);
$message = null;
$messageType = 'success';

// Xử lý hành động từ form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'export') {
        $res = $syncTool->export();
        if ($res['success']) {
            $message = "<strong>Đã xuất CSDL thành công!</strong> File lưu tại: <code>database/db_phone_store.sql</code> ({$res['size_formatted']}, {$res['table_count']} bảng, {$res['total_rows']} dòng dữ liệu).<br><i class=\"fa-solid fa-arrow-right text-primary me-1\"></i> Bây giờ bạn chỉ cần <strong>commit & push lên GitHub</strong> để mang sang máy khác!";
            $messageType = 'success';
        } else {
            $message = "Lỗi khi xuất: " . htmlspecialchars($res['message']);
            $messageType = 'danger';
        }
    } elseif ($action === 'import') {
        $res = $syncTool->import();
        if ($res['success']) {
            $message = "<strong>Đã nạp toàn bộ CSDL từ Git vào máy này thành công!</strong><br><i class=\"fa-solid fa-chart-simple text-info me-1\"></i> Thống kê hiện tại: <strong>{$res['orders_count']}</strong> đơn hàng, <strong>{$res['products_count']}</strong> sản phẩm, <strong>{$res['users_count']}</strong> tài khoản người dùng.<br><i class=\"fa-solid fa-arrow-right text-primary me-1\"></i> Bạn có thể vào xem đơn hàng vừa tạo ngay lập tức!";
            $messageType = 'success';
        } else {
            $message = "Lỗi khi nạp: " . htmlspecialchars($res['message']);
            $messageType = 'danger';
        }
    } elseif ($action === 'download_sql') {
        $info = $syncTool->getBackupInfo();
        if ($info['exists']) {
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="db_phone_store_' . date('Ymd_His') . '.sql"');
            header('Content-Length: ' . filesize($info['file']));
            readfile($info['file']);
            exit;
        }
    }
}

// Lấy thông tin thống kê hiện tại
$currentOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$currentProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$currentUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$currentReviews = (int)$pdo->query("SELECT COUNT(*) FROM product_reviews")->fetchColumn();
$backupInfo = $syncTool->getBackupInfo();

// Lấy 3 đơn hàng mới nhất hiện tại để người dùng đối chiếu
$latestOrders = $pdo->query("SELECT id, fullname, phone, total_money, status, created_at FROM orders ORDER BY id DESC LIMIT 3")->fetchAll();
?>

<div class="container-fluid px-3 px-xl-4 py-3">
    <!-- TIÊU ĐỀ TRANG -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-secondary">Quản trị</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Đồng bộ CSDL Git</li>
                </ol>
            </nav>
            <h4 class="fw-black text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fa-solid fa-database text-primary"></i>
                Đồng Bộ Cơ Sở Dữ Liệu (Mac ⇄ Máy Trường)
            </h4>
            <small class="text-muted">Chuyển dữ liệu đơn hàng, sản phẩm, tài khoản qua lại giữa các máy thông qua GitHub</small>
        </div>

        <div class="d-flex gap-2">
            <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="download_sql">
                <button type="submit" class="btn btn-outline-secondary rounded-pill px-3 py-2 small fw-semibold" <?= !$backupInfo['exists'] ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-download me-1.5"></i>Tải file .SQL về máy
                </button>
            </form>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-start gap-2">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-danger' ?> fs-5 mt-0.5"></i>
                <div><?= $message ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- THẺ TRẠNG THÁI HIỆN TẠI -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Đơn hàng trong máy</small>
                        <h3 class="fw-black text-danger my-1"><?= $currentOrders ?></h3>
                        <small class="text-muted">Bao gồm cả đơn mới tạo</small>
                    </div>
                    <div class="rounded-circle p-3 bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-cart-shopping fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Sản phẩm trong kho</small>
                        <h3 class="fw-black text-primary my-1"><?= $currentProducts ?></h3>
                        <small class="text-muted">Điện thoại & phụ kiện</small>
                    </div>
                    <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-mobile-screen-button fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Tài khoản khách & admin</small>
                        <h3 class="fw-black text-success my-1"><?= $currentUsers ?></h3>
                        <small class="text-muted">Đã đăng ký</small>
                    </div>
                    <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-users fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">File Git Backup</small>
                        <div class="fw-bold text-dark my-1" style="font-size: 1.1rem;">
                            <?= $backupInfo['exists'] ? $backupInfo['size_formatted'] : 'Chưa có' ?>
                        </div>
                        <small class="text-muted text-truncate d-block" style="max-width: 170px;">
                            <?= $backupInfo['exists'] ? $backupInfo['updated_at'] : 'database/db_phone_store.sql' ?>
                        </small>
                    </div>
                    <div class="rounded-circle p-3 bg-info bg-opacity-10 text-info">
                        <i class="fa-brands fa-git-alt fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KHU VỰC HÀNH ĐỘNG CHÍNH: 2 BƯỚC ĐỒNG BỘ -->
    <div class="row g-4 mb-4">
        <!-- BƯỚC 1: XUẤT DATABASE RA GIT (DÙNG TRÊN MÁY VỪA THAO TÁC / MÁY MAC) -->
        <div class="col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm h-100 overflow-hidden" style="border-top: 4px solid #3b82f6 !important;">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 fw-bold">Bước 1: Trên máy Mac</span>
                        <span class="text-muted small">Trước khi rời máy</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">
                        <i class="fa-solid fa-file-export text-primary me-1.5"></i>
                        Xuất Database ra Git (Export)
                    </h5>
                    <p class="text-secondary small mb-4">
                        Bấm nút bên dưới để đóng gói toàn bộ dữ liệu hiện tại (đơn hàng vừa mua, sản phẩm, tài khoản...) vào file <code>database/db_phone_store.sql</code>. Sau đó bạn commit & push lên GitHub.
                    </p>

                    <div class="bg-light p-3 rounded-3 mb-4 font-monospace small border">
                        <div class="text-muted">// Sau khi bấm nút xuất, gõ 3 lệnh này trên terminal máy Mac:</div>
                        <div class="text-primary fw-bold mt-1">git add database/db_phone_store.sql</div>
                        <div class="text-primary fw-bold">git commit -m "Update database"</div>
                        <div class="text-primary fw-bold">git push</div>
                    </div>

                    <div class="mt-auto">
                        <form method="POST">
                            <input type="hidden" name="action" value="export">
                            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm py-2.5 d-flex align-items-center justify-content-center gap-2">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>1-Click: Xuất Toàn Bộ Database Ra Git</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- BƯỚC 2: NẠP DATABASE VÀO MÁY TRƯỜNG (DÙNG KHI MỞ TRÊN MÁY KHÁC) -->
        <div class="col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm h-100 overflow-hidden" style="border-top: 4px solid #10b981 !important;">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-success text-white rounded-pill px-2.5 py-1 fw-bold">Bước 2: Trên máy trường</span>
                        <span class="text-muted small">Sau khi kéo code</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">
                        <i class="fa-solid fa-file-import text-success me-1.5"></i>
                        Nạp Database từ Git vào MySQL (Import)
                    </h5>
                    <p class="text-secondary small mb-4">
                        Sau khi bạn chạy lệnh <code>git pull</code> trên máy trường để kéo code mới về, bấm nút bên dưới để nạp đè toàn bộ đơn hàng và dữ liệu từ máy Mac vào MySQL của máy trường.
                    </p>

                    <div class="bg-light p-3 rounded-3 mb-4 font-monospace small border">
                        <div class="text-muted">// Trên máy trường (Windows/Mac), chỉ cần kéo code về:</div>
                        <div class="text-success fw-bold mt-1">git pull</div>
                        <div class="text-muted mt-1">// Rồi mở trang này và bấm nút màu xanh bên dưới!</div>
                    </div>

                    <div class="mt-auto">
                        <form method="POST" onsubmit="return confirm('Xác nhận nạp đè dữ liệu từ file Git vào MySQL máy này? Toàn bộ dữ liệu sẽ đồng bộ y hệt máy trước.');">
                            <input type="hidden" name="action" value="import">
                            <button type="submit" class="btn btn-success btn-lg w-100 rounded-pill fw-bold shadow-sm py-2.5 d-flex align-items-center justify-content-center gap-2 text-white">
                                <i class="fa-solid fa-arrows-rotate"></i>
                                <span>1-Click: Nạp Dữ Liệu Từ Git Vào Máy Này</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BẢNG ĐỐI CHIẾU ĐƠN HÀNG MỚI NHẤT TRONG MÁY HIỆN TẠI -->
    <div class="card border-0 rounded-4 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pt-3 pb-2 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-clock-rotate-left text-secondary me-1.5"></i>
                    Đơn hàng mới nhất trong máy này (Dùng để kiểm tra đối chiếu)
                </h6>
                <small class="text-muted">Nếu bạn thấy đơn hàng vừa mua trên máy Mac xuất hiện ở đây nghĩa là 2 máy đã đồng bộ 100%</small>
            </div>
            <a href="orders.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                Xem tất cả đơn <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Tổng thanh toán</th>
                            <th>Trạng thái</th>
                            <th class="pe-4">Thời gian đặt</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($latestOrders)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Chưa có đơn hàng nào trong máy này. Hãy bấm "Nạp Dữ Liệu Từ Git" nếu máy trước đã có đơn!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($latestOrders as $o): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary">#VP-<?= $o['id'] ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($o['fullname']) ?></td>
                                    <td><?= htmlspecialchars($o['phone']) ?></td>
                                    <td class="text-danger fw-bold"><?= number_format($o['total_money'], 0, ',', '.') ?> VNĐ</td>
                                    <td>
                                        <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                                            <?= htmlspecialchars($o['status']) ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-muted"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- HƯỚNG DẪN CÁCH 2: DÙNG CLOUD DATABASE (TÙY CHỌN DÀNH CHO BẠN) -->
    <div class="card border-0 rounded-4 shadow-sm bg-gradient p-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff;">
        <div class="d-flex align-items-start gap-3">
            <div class="rounded-circle p-3 bg-primary bg-opacity-25 text-primary flex-shrink-0">
                <i class="fa-solid fa-cloud fs-3 text-info"></i>
            </div>
            <div>
                <h5 class="fw-bold text-white mb-2">Cách 2 (Cao cấp hơn): Dùng chung 1 Cơ Sở Dữ Liệu Online (Cloud MySQL)</h5>
                <p class="text-white-50 small mb-3">
                    Nếu bạn không muốn mỗi lần làm xong phải bấm "Xuất" rồi sang máy khác bấm "Nạp", bạn có thể tạo 1 Database MySQL miễn phí trên mạng (ví dụ: <strong>Aiven</strong>, <strong>Clever Cloud</strong> hoặc <strong>TiDB Cloud</strong>).
                </p>
                <div class="row g-2 small text-white-50">
                    <div class="col-md-4">
                        <strong class="text-white d-block mb-1"><i class="fa-solid fa-check text-success me-1"></i>1. Dữ liệu đồng bộ tức thì:</strong>
                        Mua hàng trên Mac, máy trường chỉ cần bấm F5 là thấy ngay, không cần push/pull file SQL.
                    </div>
                    <div class="col-md-4">
                        <strong class="text-white d-block mb-1"><i class="fa-solid fa-check text-success me-1"></i>2. Cấu hình duy nhất 1 lần:</strong>
                        Chỉ cần thay đổi <code>$host, $username, $password</code> trong file <code>config/database.php</code> trỏ tới Cloud.
                    </div>
                    <div class="col-md-4">
                        <strong class="text-white d-block mb-1"><i class="fa-solid fa-wifi text-warning me-1"></i>3. Yêu cầu duy nhất:</strong>
                        Máy trường cần có kết nối mạng Internet để truy cập vào Cloud Database.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
