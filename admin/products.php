<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/ProductModel.php';
require_once __DIR__ . '/../app/views/includes/functions.php';

// Xử lý xuất file CSV nếu có yêu cầu
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'vphone_san_pham_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    // Ghi UTF-8 BOM để Excel đọc đúng tiếng Việt
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'Tên sản phẩm', 'Hãng', 'Giá niêm yết (VNĐ)', 'Giá bán (VNĐ)', 'Tồn kho', 'Màu sắc', 'Bộ nhớ ROM', 'Phân loại', 'Ngày tạo']);
    $prods = $pdo->query("SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($prods as $p) {
        $cat = $p['is_used'] ? 'Máy cũ 99%' : ($p['is_featured'] ? 'Flagship 2026' : 'Mới 100%');
        fputcsv($output, [
            $p['id'],
            $p['name'],
            $p['brand_name'] ?? 'Khác',
            $p['price'],
            $p['sale_price'] > 0 ? $p['sale_price'] : $p['price'],
            $p['quantity'],
            $p['colors'],
            $p['rom'],
            $cat,
            $p['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$pageTitle = 'Quản Lý Sản Phẩm - V-Phone Admin';
require_once 'includes/header.php';
$productModel = new ProductModel($pdo);
$productModel->ensureStorageOptionsColumn();
$productModel->ensureColorQuantitiesColumn();

$message = '';
$editId = (int)($_GET['edit_id'] ?? 0);
$editingProduct = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['delete_product'])) {
    $delId = (int)($_POST['product_id'] ?? 0);
    $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $del->execute([$delId]);
    $message = 'Đã xóa sản phẩm thành công!';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['save_product'])) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $brand_id = (int)$_POST['brand_id'];
    $price = (float)$_POST['price'];
    $sale_price = (float)$_POST['sale_price'];
    $image = trim($_POST['image'] ?? 'assets/images/products/iphone-16-promax.png');

    // Xử lý upload ảnh trực tiếp từ máy tính
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_file'];
        $uploadDir = '../assets/images/products/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext, $allowed) && $file['size'] <= 10 * 1024 * 1024) {
            $newFileName = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFileName)) {
                $image = 'assets/images/products/' . $newFileName;
            }
        }
    }

    $screen = trim($_POST['screen'] ?? '6.7 inch AMOLED');
    $cpu = trim($_POST['cpu'] ?? 'Snapdragon / Apple Bionic');
    $ram = trim($_POST['ram'] ?? '8 GB');
    $rom = trim($_POST['rom'] ?? '256 GB');
    $battery = trim($_POST['battery'] ?? '5000 mAh');
    $colors = trim($_POST['colors'] ?? 'Đen, Trắng');
    $colorNames = array_values(array_unique(array_filter(array_map('trim', explode(',', $colors)), static fn($color) => $color !== '')));
    $submittedColorQuantities = $_POST['color_quantities'] ?? [];
    $colorQuantities = [];
    $totalQuantity = 0;
    $colorQuantitiesValid = count($colorNames) > 0 && is_array($submittedColorQuantities) && count($submittedColorQuantities) === count($colorNames);
    if ($colorQuantitiesValid) {
        foreach ($colorNames as $index => $color) {
            $quantity = filter_var($submittedColorQuantities[$index] ?? null, FILTER_VALIDATE_INT);
            if ($quantity === false || $quantity < 0) {
                $colorQuantitiesValid = false;
                break;
            }
            $colorQuantities[] = ['color' => $color, 'quantity' => $quantity];
            $totalQuantity += $quantity;
        }
    }
    $colorQuantitiesJson = json_encode($colorQuantities, JSON_UNESCAPED_UNICODE);
    $storageText = trim($_POST['storage_options'] ?? '');
    $storageOptions = [];
    $storageOptionsValid = true;
    foreach (preg_split('/\R/', $storageText) as $storageLine) {
        $storageLine = trim($storageLine);
        if ($storageLine === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $storageLine, 2));
        $extra = isset($parts[1]) ? filter_var($parts[1], FILTER_VALIDATE_INT) : false;
        if (count($parts) !== 2 || $parts[0] === '' || $extra === false || $extra < 0) {
            $storageOptionsValid = false;
            break;
        }
        $storageOptions[] = [
            'rom' => $parts[0],
            'extra' => $extra,
            'label' => $parts[0] . ($extra === 0 ? ' (Tiêu chuẩn)' : ' (+' . number_format($extra / 1000000, 1, '.', '') . 'tr)')
        ];
    }
    if (!$storageOptions || $storageOptions[0]['extra'] !== 0) {
        $storageOptionsValid = false;
    }
    $storageJson = json_encode($storageOptions, JSON_UNESCAPED_UNICODE);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_used = isset($_POST['is_used']) ? 1 : 0;
    $condition_desc = $is_used ? 'Đẹp 99%' : 'Mới 100%';

    if (!$colorQuantitiesValid) {
        $message = 'Vui lòng nhập số lượng không âm cho từng màu.';
    } elseif (!$storageOptionsValid) {
        $message = 'Dung lượng cần nhập mỗi dòng theo dạng: 256 GB | 0. Mức đầu tiên phải có phụ thu 0.';
    } elseif (!empty($name) && $price > 0) {
        if ($productId > 0) {
            $stmt = $pdo->prepare("UPDATE products SET brand_id = ?, name = ?, price = ?, sale_price = ?, image = ?, screen = ?, cpu = ?, ram = ?, rom = ?, battery = ?, colors = ?, color_quantities = ?, quantity = ?, storage_options = ?, is_featured = ?, is_used = ?, condition_desc = ? WHERE id = ?");
            $stmt->execute([$brand_id, $name, $price, $sale_price, $image, $screen, $cpu, $ram, $rom, $battery, implode(', ', $colorNames), $colorQuantitiesJson, $totalQuantity, $storageJson, $is_featured, $is_used, $condition_desc, $productId]);
            $message = 'Đã cập nhật sản phẩm thành công!';
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (brand_id, name, price, sale_price, image, screen, cpu, ram, rom, battery, colors, color_quantities, quantity, storage_options, is_featured, is_used, condition_desc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$brand_id, $name, $price, $sale_price, $image, $screen, $cpu, $ram, $rom, $battery, implode(', ', $colorNames), $colorQuantitiesJson, $totalQuantity, $storageJson, $is_featured, $is_used, $condition_desc]);
            $message = 'Đã thêm điện thoại mới thành công!';
        }
        $editId = 0;
    }
}

$brands = $pdo->query("SELECT * FROM brands ORDER BY id ASC")->fetchAll();
$products = $pdo->query("SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id ORDER BY p.id DESC")->fetchAll();
if ($editId > 0) {
    $editStmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $editStmt->execute([$editId]);
    $editingProduct = $editStmt->fetch() ?: null;
}
$editingStorageOptions = json_decode($editingProduct['storage_options'] ?? '', true);
if (!is_array($editingStorageOptions) || !$editingStorageOptions) {
    $editingStorageOptions = $editingProduct
        ? getStorageTiers($editingProduct['name'], $editingProduct['rom'])
        : getStorageTiers('', '256 GB');
}
$storageOptionsText = implode("\n", array_map(static fn($option) => $option['rom'] . ' | ' . (int)$option['extra'], $editingStorageOptions));
$formColors = array_values(array_unique(array_filter(array_map('trim', explode(',', $editingProduct['colors'] ?? 'Đen, Trắng')), static fn($color) => $color !== '')));
$savedColorQuantities = json_decode($editingProduct['color_quantities'] ?? '', true);
$formColorQuantities = [];
$hasSavedColorQuantities = is_array($savedColorQuantities) && count($savedColorQuantities) > 0;
if (is_array($savedColorQuantities)) {
    foreach ($savedColorQuantities as $savedColorQuantity) {
        if (isset($savedColorQuantity['color'], $savedColorQuantity['quantity'])) {
            $formColorQuantities[$savedColorQuantity['color']] = (int)$savedColorQuantity['quantity'];
        }
    }
}
$legacyQuantity = (int)($editingProduct['quantity'] ?? 20);
$legacyColorCount = max(count($formColors), 1);
foreach ($formColors as $index => $color) {
    if (!array_key_exists($color, $formColorQuantities)) {
        $formColorQuantities[$color] = $hasSavedColorQuantities
            ? 0
            : intdiv($legacyQuantity, $legacyColorCount) + ($index < $legacyQuantity % $legacyColorCount ? 1 : 0);
    }
}
?>
<style>
#addProductModal .modal-body {
    overflow-y: auto;
    max-height: calc(90vh - 130px);
    padding: 0 !important;
}
#addProductModal .modal-content {
    border-radius: 20px !important;
    border: 0 !important;
    overflow: hidden;
}
#addProductModal .modal-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
    padding: 20px 28px;
    border: 0;
}
#addProductModal .modal-header .btn-close {
    filter: invert(1) grayscale(1) brightness(2);
    opacity: .7;
}
.form-section {
    padding: 20px 28px;
    border-bottom: 1px solid #f1f5f9;
}
.form-section:last-child { border-bottom: 0; }
.section-label {
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #94a3b8;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #f1f5f9;
}
.fm-label {
    font-size: .75rem;
    font-weight: 700;
    color: #374151;
    margin-bottom: 5px;
}
.fm-label .req { color: #ef4444; margin-left: 2px; }
.fm-input {
    border: 1.5px solid #e5e7eb;
    border-radius: 12px !important;
    padding: 9px 14px;
    font-size: .88rem;
    color: #1e293b;
    transition: border-color .15s, box-shadow .15s;
    width: 100%;
    background: #fff;
}
.fm-input:focus {
    border-color: #0066cc;
    box-shadow: 0 0 0 3px rgba(0,102,204,.1);
    outline: none;
}
select.fm-input { appearance: auto; }
.color-quantity-row {
    background: #f8fafc;
    border-radius: 12px;
    padding: 12px 14px;
    border: 1.5px solid #e9ecef !important;
    display: flex;
    align-items: flex-end;
    gap: 10px;
}
.color-quantity-row:hover { border-color: #cbd5e1 !important; }
.btn-remove-color {
    background: #fff0f0; color: #e11d48;
    border: 1.5px solid #fecdd3; border-radius: 10px;
    padding: 8px 12px; cursor: pointer;
    transition: all .15s; flex-shrink: 0;
}
.btn-remove-color:hover { background: #ffe4e6; border-color: #fda4af; }
.btn-add-color {
    background: #eff6ff; color: #2563eb;
    border: 1.5px dashed #bfdbfe; border-radius: 12px;
    padding: 9px 16px; font-size: .82rem; font-weight: 700;
    cursor: pointer; width: 100%; transition: all .15s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.btn-add-color:hover { background: #dbeafe; border-color: #93c5fd; }
.feature-toggle {
    background: #f8fafc;
    border: 1.5px solid #e9ecef;
    border-radius: 12px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: all .15s;
}
.feature-toggle:hover { border-color: #cbd5e1; background: #f1f5f9; }
.feature-toggle input { width: 18px; height: 18px; cursor: pointer; flex-shrink: 0; accent-color: #0066cc; }
#addProductModal .modal-footer {
    background: #f8fafc;
    border-top: 1px solid #e9ecef;
    padding: 16px 28px;
}
.btn-modal-cancel {
    background: #fff; color: #374151;
    border: 1.5px solid #d1d5db; border-radius: 12px;
    padding: 9px 22px; font-size: .88rem; font-weight: 600;
    cursor: pointer; transition: all .15s;
}
.btn-modal-cancel:hover { background: #f3f4f6; }
.btn-modal-save {
    background: linear-gradient(135deg, #0066cc, #0052a3);
    color: #fff; border: 0; border-radius: 12px;
    padding: 9px 28px; font-size: .88rem; font-weight: 700;
    cursor: pointer; transition: all .15s;
    box-shadow: 0 4px 12px rgba(0,102,204,.25);
}
.btn-modal-save:hover { background: linear-gradient(135deg, #0052a3, #004080); }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-black text-dark mb-1">Quản Lý Danh Sách Điện Thoại</h3>
        <p class="text-secondary small mb-0">Thêm mới, sửa giá bán, tải ảnh lên từ máy tính, cập nhật cấu hình và tồn kho</p>
    </div>
    <div class="d-flex gap-2">
        <a href="products.php?export=csv" class="btn btn-outline-success rounded-pill fw-bold btn-sm px-3">
            <i class="fa-solid fa-file-excel me-1.5"></i>Xuất Excel (CSV)
        </a>
        <button class="btn btn-primary rounded-pill fw-bold shadow-sm px-3.5 btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal" onclick="resetProductModal()">
            <i class="fa-solid fa-plus me-1.5"></i>Thêm Máy Mới
        </button>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 py-2.5 px-3 mb-4 shadow-sm" role="alert">
        <i class="fa-solid fa-check-circle me-2 text-success"></i><?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- BỘ LỌC THÔNG MINH: TÌM KIẾM, HÃNG SẢN XUẤT, TÌNH TRẠNG KHO -->
<div class="card border-0 rounded-4 shadow-sm p-3 mb-3 bg-white">
    <div class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" id="adminProductSearch" class="form-control border-start-0 bg-light" placeholder="Tìm tên điện thoại, chip CPU, RAM, ROM..." onkeyup="filterAdminProducts()">
            </div>
        </div>
        <div class="col-md-3 col-6">
            <select id="adminProductBrandFilter" class="form-select form-select-sm bg-light" onchange="filterAdminProducts()">
                <option value="all">Tất cả thương hiệu (<?= count($brands) ?>)</option>
                <?php foreach ($brands as $b): ?>
                    <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 col-6">
            <select id="adminProductStockFilter" class="form-select form-select-sm bg-light" onchange="filterAdminProducts()">
                <option value="all">Tất cả trạng thái kho</option>
                <option value="in_stock">Còn hàng (> 5 máy)</option>
                <option value="low_stock">Sắp hết hàng (1 - 5 máy)</option>
                <option value="out_of_stock">Hết hàng (0 máy)</option>
                <option value="featured">⭐ Flagship 2026</option>
                <option value="used">♻️ Máy cũ 99%</option>
            </select>
        </div>
    </div>
</div>

<div class="admin-table-card">
    <div class="p-3.5 px-4 border-bottom bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-mobile-screen-button text-primary"></i> Danh Sách Điện Thoại
            </h5>
            <small class="text-secondary">Hiển thị <span id="visibleProductsCount"><?= count($products) ?></span> / <?= count($products) ?> mẫu máy</small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table admin-table align-middle mb-0" id="adminProductsTable">
            <thead>
                <tr>
                    <th style="width: 70px;">Ảnh</th>
                    <th>Tên Điện Thoại</th>
                    <th>Hãng</th>
                    <th>Màu & Dung Lượng</th>
                    <th>Giá Bán</th>
                    <th>Kho & Phân Loại</th>
                    <th class="text-end">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <?php
                        $stockStatus = 'in_stock';
                        if ($p['quantity'] == 0) $stockStatus = 'out_of_stock';
                        elseif ($p['quantity'] <= 5) $stockStatus = 'low_stock';
                    ?>
                    <tr class="product-row"
                        data-name="<?= htmlspecialchars(mb_strtolower($p['name'] . ' ' . ($p['cpu'] ?? '') . ' ' . ($p['ram'] ?? '') . ' ' . ($p['rom'] ?? ''), 'UTF-8')) ?>"
                        data-brand="<?= (int)$p['brand_id'] ?>"
                        data-stock="<?= $stockStatus ?>"
                        data-featured="<?= $p['is_featured'] ? '1' : '0' ?>"
                        data-used="<?= $p['is_used'] ? '1' : '0' ?>">
                        <td>
                            <div class="p-1 rounded-3 bg-light border d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                <img src="../<?= htmlspecialchars($p['image']) ?>" alt="" style="max-width: 44px; max-height: 44px; object-fit: contain;">
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($p['name']) ?></div>
                            <small class="text-secondary">
                                <i class="fa-solid fa-microchip me-1 text-muted"></i><?= htmlspecialchars($p['cpu'] ?? 'Snapdragon') ?> |
                                <i class="fa-solid fa-memory me-1 text-muted"></i><?= htmlspecialchars($p['ram']) ?> - <?= htmlspecialchars($p['rom']) ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge badge-soft-primary rounded-pill px-3 py-1">
                                <?= htmlspecialchars($p['brand_name'] ?? 'Khác') ?>
                            </span>
                        </td>
                        <td class="small" style="min-width: 220px;">
                            <div class="d-flex flex-wrap gap-1 mb-1">
                                <?php foreach (array_filter(array_map('trim', explode(',', $p['colors'] ?? ''))) as $color): ?>
                                    <span class="badge bg-light text-dark border rounded-pill"><?= htmlspecialchars($color) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                <?php
                                    $rowStorageOptions = json_decode($p['storage_options'] ?? '', true);
                                    if (!is_array($rowStorageOptions) || !$rowStorageOptions) {
                                        $rowStorageOptions = getStorageTiers($p['name'], $p['rom']);
                                    }
                                ?>
                                <?php foreach ($rowStorageOptions as $storageOption): ?>
                                    <span class="badge bg-white text-secondary border rounded-pill" style="font-size: 0.72rem;">
                                        <?= htmlspecialchars($storageOption['rom']) ?><?= (int)$storageOption['extra'] > 0 ? ' (+' . number_format($storageOption['extra'] / 1000000, 1) . 'tr)' : '' ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <div class="text-danger fw-bold fs-6">
                                <?= number_format($p['sale_price'] > 0 ? $p['sale_price'] : $p['price'], 0, ',', '.') ?> đ
                            </div>
                            <?php if ($p['sale_price'] > 0 && $p['sale_price'] < $p['price']): ?>
                                <small class="text-muted text-decoration-line-through"><?= number_format($p['price'], 0, ',', '.') ?> đ</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="mb-1">
                                <?php if ($p['quantity'] == 0): ?>
                                    <span class="badge bg-danger rounded-pill px-2 py-1">Hết hàng</span>
                                <?php elseif ($p['quantity'] <= 5): ?>
                                    <span class="badge bg-warning text-dark rounded-pill px-2 py-1">Còn <?= $p['quantity'] ?> máy</span>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1">Kho: <?= $p['quantity'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($p['is_used']): ?>
                                    <span class="badge badge-soft-success rounded-pill px-2 py-0.5" style="font-size: 0.72rem;"><i class="fa-solid fa-recycle me-1"></i>Máy cũ 99%</span>
                                <?php elseif ($p['is_featured']): ?>
                                    <span class="badge badge-soft-primary rounded-pill px-2 py-0.5" style="font-size: 0.72rem;"><i class="fa-solid fa-crown text-warning me-1"></i>Flagship</span>
                                <?php else: ?>
                                    <span class="badge badge-soft-secondary rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">Mới 100%</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="products.php?edit_id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-light border text-primary rounded-pill px-2.5 py-1 me-1" title="Sửa sản phẩm" aria-label="Sửa <?= htmlspecialchars($p['name']) ?>">
                                <i class="fa-solid fa-pen-to-square"></i> Sửa
                            </a>
                            <button type="button" class="btn btn-sm btn-light border text-danger rounded-circle" style="width: 32px; height: 32px; padding: 0;" title="Xóa sản phẩm" aria-label="Xóa <?= htmlspecialchars($p['name']) ?>" data-bs-toggle="modal" data-bs-target="#deleteProductModal" data-product-id="<?= (int)$p['id'] ?>" data-product-name="<?= htmlspecialchars($p['name']) ?>">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterAdminProducts() {
    const q = document.getElementById('adminProductSearch').value.toLowerCase().trim();
    const brand = document.getElementById('adminProductBrandFilter').value;
    const stock = document.getElementById('adminProductStockFilter').value;

    let count = 0;
    document.querySelectorAll('.product-row').forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const rBrand = row.getAttribute('data-brand') || '';
        const rStock = row.getAttribute('data-stock') || '';
        const rFeat = row.getAttribute('data-featured') || '0';
        const rUsed = row.getAttribute('data-used') || '0';

        const matchQuery = !q || name.includes(q);
        const matchBrand = (brand === 'all') || (rBrand === brand);

        let matchStock = true;
        if (stock === 'in_stock') matchStock = (rStock === 'in_stock');
        else if (stock === 'low_stock') matchStock = (rStock === 'low_stock');
        else if (stock === 'out_of_stock') matchStock = (rStock === 'out_of_stock');
        else if (stock === 'featured') matchStock = (rFeat === '1');
        else if (stock === 'used') matchStock = (rUsed === '1');

        if (matchQuery && matchBrand && matchStock) {
            row.style.display = '';
            count++;
        } else {
            row.style.display = 'none';
        }
    });
    const cEl = document.getElementById('visibleProductsCount');
    if (cEl) cEl.textContent = count;
}
</script>

<!-- MODAL THÊM / SỬA SẢN PHẨM (HỖ TRỢ FILE UPLOAD) -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:40px;height:40px;background:rgba(255,255,255,.12);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-mobile-screen-button" style="color:#fff;font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" style="color:#fff;">
                            <?= $editingProduct ? 'Chỉnh Sửa Sản Phẩm' : 'Thêm Điện Thoại Mới' ?>
                        </h5>
                        <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;">
                            <?= $editingProduct ? htmlspecialchars($editingProduct['name']) : 'Điền đầy đủ thông tin bên dưới' ?>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- FORM MULTIPART ĐỂ UPLOAD FILE ẢNH -->
            <form method="POST" action="products.php" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?= (int)($editingProduct['id'] ?? 0) ?>">

                <div class="modal-body">

                    <div class="form-section">
                        <div class="section-label"><i class="fa-solid fa-tag" style="color:#0066cc;font-size:.8rem;"></i> Thông tin cơ bản</div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="fm-label">Tên Điện Thoại <span class="req">*</span></div>
                                <input type="text" name="name" class="fm-input form-control" required
                                       placeholder="Ví dụ: iPhone 18 Pro Max 512GB"
                                       value="<?= htmlspecialchars($editingProduct['name'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <div class="fm-label">Hãng Sản Xuất <span class="req">*</span></div>
                                <select name="brand_id" class="fm-input form-select">
                                    <?php foreach ($brands as $b): ?>
                                        <option value="<?= $b['id'] ?>" <?= (int)($editingProduct['brand_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($b['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-label"><i class="fa-solid fa-circle-dollar-to-slot" style="color:#16a34a;font-size:.8rem;"></i> Giá bán</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="fm-label">Giá Gốc Niêm Yết (VNĐ) <span class="req">*</span></div>
                                <input type="number" name="price" class="fm-input form-control" required
                                       placeholder="35000000"
                                       value="<?= htmlspecialchars((string)($editingProduct['price'] ?? '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <div class="fm-label">Giá Bán Khuyến Mãi (VNĐ)</div>
                                <input type="number" name="sale_price" class="fm-input form-control"
                                       placeholder="32000000"
                                       value="<?= htmlspecialchars((string)($editingProduct['sale_price'] ?? '')) ?>">
                            </div>
                        </div>
                    </div>

                    <!-- KHU VỰC HÌNH ẢNH: TẢI FILE TỪ MÁY HOẶC NHẬP LINK -->
                    <div class="form-section">
                        <div class="section-label"><i class="fa-solid fa-image" style="color:#7c3aed;font-size:.8rem;"></i> Hình ảnh sản phẩm</div>
                        <div class="row g-3 align-items-center">
                            <div class="col-md-3 text-center">
                                <div class="p-2 border rounded-4 bg-light d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px;">
                                    <img id="productImagePreview" src="../<?= htmlspecialchars($editingProduct['image'] ?? 'assets/images/products/iphone-16-promax.png') ?>" alt="Preview" style="max-width: 86px; max-height: 86px; object-fit: contain;">
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Ảnh đại diện</small>
                            </div>
                            <div class="col-md-9">
                                <div class="mb-2">
                                    <label class="fm-label d-flex align-items-center justify-content-between">
                                        <span>Tải Ảnh Từ Máy Tính <span class="badge bg-primary bg-opacity-10 text-primary small">Upload File</span></span>
                                        <small class="text-muted">JPG, PNG, WEBP, GIF tối đa 10MB</small>
                                    </label>
                                    <input type="file" name="image_file" class="form-control" accept="image/*" onchange="previewUploadedImage(this)">
                                </div>
                                <div>
                                    <label class="fm-label">Hoặc nhập đường dẫn ảnh nội bộ / URL:</label>
                                    <input type="text" name="image" id="productImagePath" class="fm-input form-control"
                                           placeholder="assets/images/products/ten-anh.png"
                                           value="<?= htmlspecialchars($editingProduct['image'] ?? 'assets/images/products/iphone-16-promax.png') ?>"
                                           oninput="previewPathImage(this.value)">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-label"><i class="fa-solid fa-microchip" style="color:#0891b2;font-size:.8rem;"></i> Thông số kỹ thuật</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="fm-label">RAM</div>
                                <input type="text" name="ram" class="fm-input form-control"
                                       value="<?= htmlspecialchars($editingProduct['ram'] ?? '12 GB') ?>">
                            </div>
                            <div class="col-md-4">
                                <div class="fm-label">Bộ Nhớ ROM</div>
                                <input type="text" name="rom" class="fm-input form-control"
                                       value="<?= htmlspecialchars($editingProduct['rom'] ?? '256 GB') ?>">
                            </div>
                            <div class="col-md-4">
                                <div class="fm-label">Dung Lượng Pin</div>
                                <input type="text" name="battery" class="fm-input form-control"
                                       value="<?= htmlspecialchars($editingProduct['battery'] ?? '5000 mAh') ?>">
                            </div>
                            <div class="col-md-6">
                                <div class="fm-label">Chipset CPU</div>
                                <input type="text" name="cpu" class="fm-input form-control"
                                       value="<?= htmlspecialchars($editingProduct['cpu'] ?? 'Snapdragon 8 Gen 5') ?>">
                            </div>
                            <div class="col-md-6">
                                <div class="fm-label">Màn Hình</div>
                                <input type="text" name="screen" class="fm-input form-control"
                                       value="<?= htmlspecialchars($editingProduct['screen'] ?? '6.8 inch 120Hz') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="section-label mb-0" style="flex:1;">
                                <i class="fa-solid fa-palette" style="color:#f59e0b;font-size:.8rem;"></i> Màu sắc &amp; số lượng tồn
                            </div>
                        </div>
                        <input type="hidden" id="productColors" name="colors" value="<?= htmlspecialchars(implode(', ', $formColors)) ?>">
                        <div id="colorQuantities" class="d-flex flex-column gap-2 mb-3">
                            <?php foreach ($formColors as $color): ?>
                                <div class="color-quantity-row">
                                    <div class="flex-grow-1">
                                        <div class="fm-label">Tên màu</div>
                                        <input type="text" class="fm-input form-control color-name"
                                               value="<?= htmlspecialchars($color) ?>" required>
                                    </div>
                                    <div style="width:130px;">
                                        <div class="fm-label">Số lượng</div>
                                        <input type="number" name="color_quantities[]" class="fm-input form-control"
                                               min="0" value="<?= (int)$formColorQuantities[$color] ?>" required>
                                    </div>
                                    <button type="button" class="btn-remove-color remove-color" title="Xóa màu" aria-label="Xóa màu">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="addColorButton" class="btn-add-color">
                            <i class="fa-solid fa-plus"></i> Thêm màu
                        </button>
                    </div>

                    <div class="form-section">
                        <div class="section-label"><i class="fa-solid fa-hdd" style="color:#64748b;font-size:.8rem;"></i> Các mức dung lượng &amp; phụ thu</div>
                        <textarea name="storage_options" class="fm-input form-control font-monospace" rows="4" required
                                  style="border-radius:12px !important;"><?= htmlspecialchars($storageOptionsText) ?></textarea>
                        <div style="font-size:.73rem;color:#94a3b8;margin-top:6px;">
                            Mỗi dòng một mức: <code>dung lượng | phụ thu VNĐ</code> — mức đầu phải có phụ thu 0, ví dụ <code>256 GB | 0</code>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-label"><i class="fa-solid fa-certificate" style="color:#f59e0b;font-size:.8rem;"></i> Nhãn sản phẩm</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="feature-toggle" for="isFeat">
                                    <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="isFeat"
                                           <?= !empty($editingProduct['is_featured']) ? 'checked' : '' ?>>
                                    <div>
                                        <div style="font-size:.85rem;font-weight:700;color:#1e293b;">⭐ Flagship 2026</div>
                                        <div style="font-size:.73rem;color:#64748b;">Hiển thị badge nổi bật trên website</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="feature-toggle" for="isUsed">
                                    <input class="form-check-input" type="checkbox" name="is_used" value="1" id="isUsed"
                                           <?= !empty($editingProduct['is_used']) ? 'checked' : '' ?>>
                                    <div>
                                        <div style="font-size:.85rem;font-weight:700;color:#1e293b;">♻️ Hàng cũ Like New 99%</div>
                                        <div style="font-size:.73rem;color:#64748b;">Đánh dấu là hàng đã qua sử dụng</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="save_product" value="1" class="btn-modal-save">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Lưu Sản Phẩm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-body p-4 text-center">
                <div class="d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width:60px;height:60px;background:#fff1f2;border-radius:18px;">
                    <i class="fa-solid fa-trash" style="color:#e11d48;font-size:1.4rem;"></i>
                </div>
                <h5 class="fw-bold mb-1" id="deleteProductTitle">Xóa sản phẩm?</h5>
                <p class="text-secondary small mb-4" id="deleteProductMessage"></p>
                <form method="POST" action="products.php" class="d-flex justify-content-center gap-2">
                    <input type="hidden" name="product_id" id="deleteProductId">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="delete_product" value="1" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-trash me-1"></i>Xóa
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Xem trước ảnh khi người dùng chọn file từ máy
function previewUploadedImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('productImagePreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Xem trước ảnh khi người dùng gõ đường dẫn URL
function previewPathImage(path) {
    if (path.trim() !== '') {
        const fullSrc = path.startsWith('http') ? path : '../' + path;
        document.getElementById('productImagePreview').src = fullSrc;
    }
}

function resetProductModal() {
    // Nếu bấm thêm mới thì reload sạch form nếu trước đó đang sửa
    <?php if ($editingProduct): ?>
    window.location.href = 'products.php';
    <?php endif; ?>
}

document.addEventListener('DOMContentLoaded', function () {
    const colorsInput = document.getElementById('productColors');
    const quantitiesContainer = document.getElementById('colorQuantities');
    const addColorButton = document.getElementById('addColorButton');
    const productForm = colorsInput.closest('form');

    function updateColorRows() {
        const rows = quantitiesContainer.querySelectorAll('.color-quantity-row');
        rows.forEach(function (row) {
            row.querySelector('.remove-color').disabled = rows.length === 1;
        });
    }

    addColorButton?.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'color-quantity-row';

        const nameField = document.createElement('div');
        nameField.className = 'flex-grow-1';
        const nameLabel = document.createElement('div');
        nameLabel.className = 'fm-label';
        nameLabel.textContent = 'Tên màu';
        const nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.className = 'fm-input form-control color-name';
        nameInput.required = true;
        nameField.append(nameLabel, nameInput);

        const quantityField = document.createElement('div');
        quantityField.style.width = '130px';
        const quantityLabel = document.createElement('div');
        quantityLabel.className = 'fm-label';
        quantityLabel.textContent = 'Số lượng';
        const quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.name = 'color_quantities[]';
        quantityInput.className = 'fm-input form-control';
        quantityInput.min = '0';
        quantityInput.value = '0';
        quantityInput.required = true;
        quantityField.append(quantityLabel, quantityInput);

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn-remove-color remove-color';
        removeButton.title = 'Xóa màu';
        removeButton.setAttribute('aria-label', 'Xóa màu');
        removeButton.innerHTML = '<i class="fa-solid fa-trash"></i>';

        row.append(nameField, quantityField, removeButton);
        quantitiesContainer.append(row);
        updateColorRows();
        nameInput.focus();
    });

    quantitiesContainer.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.remove-color');
        if (removeButton && quantitiesContainer.querySelectorAll('.color-quantity-row').length > 1) {
            removeButton.closest('.color-quantity-row').remove();
            updateColorRows();
        }
    });

    productForm.addEventListener('submit', function (event) {
        const colorInputs = [...quantitiesContainer.querySelectorAll('.color-name')];
        colorInputs.forEach(input => input.setCustomValidity(''));
        const colors = colorInputs.map(input => input.value.trim());
        const normalizedColors = colors.map(color => color.toLocaleLowerCase());
        const duplicateIndex = normalizedColors.findIndex((color, index) => normalizedColors.indexOf(color) !== index);
        if (duplicateIndex !== -1) {
            event.preventDefault();
            colorInputs[duplicateIndex].setCustomValidity('Tên màu này đã được nhập.');
            colorInputs[duplicateIndex].reportValidity();
            return;
        }
        colorsInput.value = colors.join(', ');
    });

    updateColorRows();

    const deleteModal = document.getElementById('deleteProductModal');
    deleteModal?.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        document.getElementById('deleteProductId').value = trigger.dataset.productId;
        document.getElementById('deleteProductMessage').textContent = `Sản phẩm "${trigger.dataset.productName}" sẽ bị xóa khỏi cửa hàng.`;
    });

    <?php if ($editingProduct): ?>
    bootstrap.Modal.getOrCreateInstance(document.getElementById('addProductModal')).show();
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>
