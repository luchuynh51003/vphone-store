<?php
require_once '../app/models/ProductModel.php';
require_once '../app/views/includes/functions.php';
$pageTitle = 'Quản Lý Sản Phẩm - V-Phone Admin';
require_once 'includes/header.php';
$productModel = new ProductModel($pdo);
$productModel->ensureStorageOptionsColumn();
$productModel->ensureColorQuantitiesColumn();

$message = '';
$editId = (int)($_GET['edit_id'] ?? 0);
$editingProduct = null;

// 1. XỬ LÝ XÓA SẢN PHẨM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $delId = (int)($_POST['product_id'] ?? 0);
    $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $del->execute([$delId]);
    $message = 'Đã xóa sản phẩm thành công!';
}

// Thêm hoặc cập nhật thông tin sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $brand_id = (int)$_POST['brand_id'];
    $price = (float)$_POST['price'];
    $sale_price = (float)$_POST['sale_price'];
    $image = trim($_POST['image'] ?? 'assets/images/products/iphone-16-promax.png');
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
            $message = 'Đã cập nhật sản phẩm.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (brand_id, name, price, sale_price, image, screen, cpu, ram, rom, battery, colors, color_quantities, quantity, storage_options, is_featured, is_used, condition_desc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$brand_id, $name, $price, $sale_price, $image, $screen, $cpu, $ram, $rom, $battery, implode(', ', $colorNames), $colorQuantitiesJson, $totalQuantity, $storageJson, $is_featured, $is_used, $condition_desc]);
            $message = 'Đã thêm điện thoại mới thành công!';
        }
        $editId = 0;
    }
}

// Lấy danh sách sản phẩm và thương hiệu
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
/* ===== PRODUCT MODAL STYLES ===== */
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Quản Lý Danh Sách Điện Thoại</h3>
        <p class="text-secondary small mb-0">Thêm mới, sửa giá bán, cập nhật cấu hình và xóa sản phẩm</p>
    </div>
    <button class="btn btn-primary rounded-pill fw-bold shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#addProductModal">
        <i class="fa-solid fa-plus me-2"></i>Thêm Máy Mới
    </button>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 py-2" role="alert">
        <i class="fa-solid fa-check me-2"></i><?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- BẢNG DANH SÁCH ĐIỆN THOẠI -->
<div class="admin-table-card">
    <div class="p-4 border-bottom bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-mobile-screen-button text-primary"></i> Danh Sách Điện Thoại Đang Kinh Doanh
            </h5>
            <small class="text-secondary">Tổng cộng <?= count($products) ?> mẫu máy trong cơ sở dữ liệu</small>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="input-group input-group-sm" style="width: 240px;">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" id="adminProductSearch" class="form-control border-start-0 bg-light" placeholder="Tìm tên máy..." onkeyup="filterAdminProducts()">
            </div>
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
                    <th>Phân Loại</th>
                    <th class="text-end">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr class="product-row" data-name="<?= htmlspecialchars(mb_strtolower($p['name'], 'UTF-8')) ?>">
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
                            <?php if ($p['is_used']): ?>
                                <span class="badge badge-soft-success rounded-pill px-3 py-1">Máy cũ 99%</span>
                            <?php elseif ($p['is_featured']): ?>
                                <span class="badge badge-soft-primary rounded-pill px-3 py-1">Flagship 2026</span>
                            <?php else: ?>
                                <span class="badge badge-soft-secondary rounded-pill px-3 py-1">Mới 100%</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="products.php?edit_id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-light border text-primary rounded-pill px-2 py-1 me-1" title="Sửa sản phẩm" aria-label="Sửa <?= htmlspecialchars($p['name']) ?>">
                                <i class="fa-solid fa-pen-to-square"></i> Sửa
                            </a>
                            <button type="button" class="btn btn-sm btn-light border text-danger rounded-pill px-2 py-1" title="Xóa sản phẩm" aria-label="Xóa <?= htmlspecialchars($p['name']) ?>" data-bs-toggle="modal" data-bs-target="#deleteProductModal" data-product-id="<?= (int)$p['id'] ?>" data-product-name="<?= htmlspecialchars($p['name']) ?>">
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
    document.querySelectorAll('.product-row').forEach(row => {
        const name = row.getAttribute('data-name');
        if (!q || name.includes(q)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<!-- MODAL THÊM / SỬA SẢN PHẨM -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <!-- Header tối -->
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

            <form method="POST" action="products.php">
                <input type="hidden" name="product_id" value="<?= (int)($editingProduct['id'] ?? 0) ?>">

                <div class="modal-body">

                    <!-- SECTION 1: Thông tin cơ bản -->
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

                    <!-- SECTION 2: Giá -->
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

                    <!-- SECTION 3: Ảnh -->
                    <div class="form-section">
                        <div class="section-label"><i class="fa-solid fa-image" style="color:#7c3aed;font-size:.8rem;"></i> Hình ảnh</div>
                        <div class="fm-label">Đường Dẫn Ảnh Sản Phẩm</div>
                        <input type="text" name="image" class="fm-input form-control"
                               placeholder="assets/images/products/ten-anh.png"
                               value="<?= htmlspecialchars($editingProduct['image'] ?? 'assets/images/products/iphone-16-promax.png') ?>">
                    </div>

                    <!-- SECTION 4: Thông số kỹ thuật -->
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

                    <!-- SECTION 5: Màu sắc & tồn kho -->
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

                    <!-- SECTION 6: Dung lượng -->
                    <div class="form-section">
                        <div class="section-label"><i class="fa-solid fa-hdd" style="color:#64748b;font-size:.8rem;"></i> Các mức dung lượng &amp; phụ thu</div>
                        <textarea name="storage_options" class="fm-input form-control font-monospace" rows="4" required
                                  style="border-radius:12px !important;"><?= htmlspecialchars($storageOptionsText) ?></textarea>
                        <div style="font-size:.73rem;color:#94a3b8;margin-top:6px;">
                            Mỗi dòng một mức: <code>dung lượng | phụ thu VNĐ</code> — mức đầu phải có phụ thu 0, ví dụ <code>256 GB | 0</code>
                        </div>
                    </div>

                    <!-- SECTION 7: Nhãn sản phẩm -->
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

                </div><!-- end modal-body -->

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

<!-- MODAL XÁC NHẬN XÓA -->
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
