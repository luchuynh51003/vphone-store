<?php
$pageTitle = 'Quản Lý Tồn Kho - V-Phone Admin';
require_once 'includes/header.php';
require_once '../app/models/ProductModel.php';
(new ProductModel($pdo))->ensureColorQuantitiesColumn();

$message = '';

// Xử lý cập nhật tồn kho chi tiết theo màu từ Modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_color_stock'])) {
    $prodId = (int)$_POST['product_id'];
    $colorNames = $_POST['color_names'] ?? [];
    $colorQtys = $_POST['color_qtys'] ?? [];

    $newColorList = [];
    $totalQty = 0;
    foreach ($colorNames as $i => $cName) {
        $cName = trim($cName);
        if ($cName === '') continue;
        $qty = max(0, (int)($colorQtys[$i] ?? 0));
        $newColorList[] = ['color' => $cName, 'quantity' => $qty];
        $totalQty += $qty;
    }

    $jsonCQ = json_encode($newColorList, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("UPDATE products SET color_quantities = ?, quantity = ? WHERE id = ?");
    $stmt->execute([$jsonCQ, $totalQty, $prodId]);
    $message = "Đã cập nhật tồn kho chi tiết theo màu thành công (Tổng: $totalQty máy)!";
}

// Xử lý cập nhật số lượng tồn kho (Nhập thêm hàng nhanh)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $prodId = (int)$_POST['product_id'];
    $addQty = (int)$_POST['add_quantity'];
    $newQty = isset($_POST['set_quantity']) ? (int)$_POST['set_quantity'] : -1;

    $prodStmt = $pdo->prepare("SELECT quantity, colors, color_quantities FROM products WHERE id = ?");
    $prodStmt->execute([$prodId]);
    $curProd = $prodStmt->fetch();

    if ($curProd) {
        $finalQty = $newQty >= 0 ? $newQty : max(0, (int)$curProd['quantity'] + $addQty);
        $pColors = array_values(array_filter(array_map('trim', explode(',', $curProd['colors'] ?? ''))));
        $pCount = count($pColors);

        $savedCQ = json_decode($curProd['color_quantities'] ?? '', true);
        if ((!is_array($savedCQ) || empty($savedCQ)) && $pCount > 0) {
            // Tự động phân bổ đều vào các màu
            $base = intdiv($finalQty, $pCount);
            $rem = $finalQty % $pCount;
            $autoCQ = [];
            foreach ($pColors as $idx => $c) {
                $autoCQ[] = ['color' => $c, 'quantity' => $base + ($idx < $rem ? 1 : 0)];
            }
            $stmt = $pdo->prepare("UPDATE products SET quantity = ?, color_quantities = ? WHERE id = ?");
            $stmt->execute([$finalQty, json_encode($autoCQ, JSON_UNESCAPED_UNICODE), $prodId]);
        } else {
            $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $stmt->execute([$finalQty, $prodId]);
        }
        $message = "Đã cập nhật số lượng tồn kho thành $finalQty máy!";
    }
}

// Tự động đồng bộ các sản phẩm đã có tổng tồn kho nhưng chưa phân bổ màu
$uninitProds = $pdo->query("SELECT id, quantity, colors, color_quantities FROM products WHERE quantity > 0 AND (color_quantities IS NULL OR color_quantities = '' OR color_quantities = '[]')")->fetchAll();
foreach ($uninitProds as $uProd) {
    $uColors = array_values(array_filter(array_map('trim', explode(',', $uProd['colors'] ?? ''))));
    $uCount = count($uColors);
    if ($uCount > 0) {
        $uTotal = (int)$uProd['quantity'];
        $uBase = intdiv($uTotal, $uCount);
        $uRem = $uTotal % $uCount;
        $autoCQ = [];
        foreach ($uColors as $idx => $c) {
            $autoCQ[] = ['color' => $c, 'quantity' => $uBase + ($idx < $uRem ? 1 : 0)];
        }
        $uStmt = $pdo->prepare("UPDATE products SET color_quantities = ? WHERE id = ?");
        $uStmt->execute([json_encode($autoCQ, JSON_UNESCAPED_UNICODE), $uProd['id']]);
    }
}

// Tính tổng tồn, sắp hết và hết hàng trong một lượt quét.
$stockStats = $pdo->query("SELECT
    COALESCE(SUM(quantity), 0) AS total_stock,
    COALESCE(SUM(CASE WHEN quantity > 0 AND quantity <= 5 THEN 1 ELSE 0 END), 0) AS low_stock,
    COALESCE(SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END), 0) AS out_of_stock
    FROM products")->fetch();
$totalStock = (int)$stockStats['total_stock'];
$lowStock   = (int)$stockStats['low_stock'];
$outOfStock = (int)$stockStats['out_of_stock'];

// Lọc sản phẩm
$filter = $_GET['filter'] ?? 'all';
$querySql = "SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id";
if ($filter === 'low') {
    $querySql .= " WHERE p.quantity > 0 AND p.quantity <= 5";
} elseif ($filter === 'out') {
    $querySql .= " WHERE p.quantity = 0";
}
$querySql .= " ORDER BY p.quantity ASC, p.id DESC";
$stockList = $pdo->query($querySql)->fetchAll();
?>

<style>
/* ===== INVENTORY PAGE STYLES ===== */
.inv-kpi-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px 22px;
    border: 1.5px solid #e9ecef;
    transition: all .2s ease;
    text-decoration: none;
    display: block;
    cursor: pointer;
}
.inv-kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.09); }
.inv-kpi-card.active-all  { border-color: #0066cc; background: linear-gradient(135deg,#f0f6ff,#fff); }
.inv-kpi-card.active-low  { border-color: #f59e0b; background: linear-gradient(135deg,#fffbeb,#fff); }
.inv-kpi-card.active-out  { border-color: #ef4444; background: linear-gradient(135deg,#fff5f5,#fff); }

.inv-kpi-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
}
.inv-kpi-value { font-size: 2rem; font-weight: 800; line-height: 1.1; }

/* Table */
.inv-table thead th {
    background: #f8fafc;
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748b;
    border-bottom: 2px solid #e9ecef;
    padding: 12px 14px;
    white-space: nowrap;
}
.inv-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background .15s;
}
.inv-table tbody tr:hover { background: #f8fafc; }
.inv-table td { padding: 12px 14px; vertical-align: middle; }

/* Product name cell */
.prod-name { font-weight: 700; color: #1e293b; font-size: .9rem; line-height: 1.3; }
.prod-sub  { font-size: .75rem; color: #94a3b8; margin-top: 2px; }

/* Brand pill */
.brand-chip {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 600;
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    white-space: nowrap;
}

/* Stock number */
.stock-num {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1;
}

/* Status badges */
.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 999px;
    font-size: .75rem; font-weight: 700;
    white-space: nowrap;
}
.status-ok   { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.status-low  { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
.status-out  { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }

/* Color chips */
.color-chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 8px;
    font-size: .72rem; font-weight: 600;
    background: #f8fafc; color: #374151;
    border: 1px solid #e5e7eb;
    margin: 2px 2px 2px 0;
}
.color-chip.unset { background: #fffbeb; color: #92400e; border-color: #fde68a; }

/* Quick stock form */
.qty-input {
    width: 66px !important;
    text-align: center;
    font-weight: 700;
    font-size: .88rem;
    border-radius: 10px !important;
    border: 1.5px solid #d1d5db;
    padding: 5px 6px;
}
.qty-input:focus { border-color: #0066cc; box-shadow: 0 0 0 3px rgba(0,102,204,.12); outline: none; }
.btn-save-stock {
    background: #0066cc; color: #fff;
    border: none; border-radius: 10px;
    padding: 6px 14px; font-size: .8rem; font-weight: 700;
    cursor: pointer; transition: background .15s;
}
.btn-save-stock:hover { background: #0052a3; }
.btn-add-stock {
    border-radius: 10px; border: 1.5px solid;
    padding: 5px 11px; font-size: .78rem; font-weight: 700;
    cursor: pointer; background: #fff; transition: all .15s;
}
.btn-add-10  { color: #16a34a; border-color: #86efac; }
.btn-add-10:hover  { background: #f0fdf4; }
.btn-add-50  { color: #0284c7; border-color: #7dd3fc; }
.btn-add-50:hover  { background: #f0f9ff; }

/* Filter tabs */
.inv-filter-tab {
    padding: 6px 18px; border-radius: 10px;
    font-size: .82rem; font-weight: 600;
    border: 1.5px solid #e9ecef; background: #fff;
    text-decoration: none; color: #64748b;
    transition: all .15s;
}
.inv-filter-tab:hover { background: #f8fafc; color: #1e293b; }
.inv-filter-tab.tab-all.active  { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
.inv-filter-tab.tab-low.active  { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.inv-filter-tab.tab-out.active  { background: #fff1f2; color: #be123c; border-color: #fecdd3; }
</style>

<!-- PAGE HEADER -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Quản Lý Tồn Kho
        </h3>
        <p class="text-secondary small mb-0">Theo dõi số lượng máy, cảnh báo hết hàng và nhập hàng nhanh</p>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 py-2 mb-4" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- KPI CARDS -->
<div class="row g-3 mb-4">
    <!-- Tổng tồn -->
    <div class="col-lg-4 col-md-6">
        <a href="inventory.php?filter=all" class="inv-kpi-card <?= $filter === 'all' ? 'active-all' : '' ?>">
            <div class="d-flex align-items-center gap-3">
                <div class="inv-kpi-icon" style="background:#eff6ff; color:#2563eb;">
                    <i class="fa-solid fa-warehouse"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="text-secondary small fw-semibold mb-1" style="font-size:.72rem; text-transform:uppercase; letter-spacing:.05em;">Tổng trong kho</div>
                    <div class="inv-kpi-value text-primary"><?= number_format($totalStock) ?></div>
                    <div class="text-muted" style="font-size:.76rem; margin-top:2px;">chiếc máy sẵn sàng</div>
                </div>
                <?php if ($filter === 'all'): ?>
                    <i class="fa-solid fa-chevron-right text-primary opacity-50"></i>
                <?php endif; ?>
            </div>
        </a>
    </div>
    <!-- Sắp hết -->
    <div class="col-lg-4 col-md-6">
        <a href="inventory.php?filter=low" class="inv-kpi-card <?= $filter === 'low' ? 'active-low' : '' ?>">
            <div class="d-flex align-items-center gap-3">
                <div class="inv-kpi-icon" style="background:#fffbeb; color:#d97706;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="text-secondary small fw-semibold mb-1" style="font-size:.72rem; text-transform:uppercase; letter-spacing:.05em;">Sắp hết hàng (&le;5)</div>
                    <div class="inv-kpi-value" style="color:#d97706;"><?= $lowStock ?></div>
                    <div style="font-size:.76rem; margin-top:2px; color:#b45309; font-weight:600;">dòng máy cần nhập gấp</div>
                </div>
                <?php if ($filter === 'low'): ?>
                    <i class="fa-solid fa-chevron-right" style="color:#d97706; opacity:.5;"></i>
                <?php endif; ?>
            </div>
        </a>
    </div>
    <!-- Hết hàng -->
    <div class="col-lg-4 col-md-6">
        <a href="inventory.php?filter=out" class="inv-kpi-card <?= $filter === 'out' ? 'active-out' : '' ?>">
            <div class="d-flex align-items-center gap-3">
                <div class="inv-kpi-icon" style="background:#fff1f2; color:#e11d48;">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="text-secondary small fw-semibold mb-1" style="font-size:.72rem; text-transform:uppercase; letter-spacing:.05em;">Hết sạch hàng</div>
                    <div class="inv-kpi-value text-danger"><?= $outOfStock ?></div>
                    <div style="font-size:.76rem; margin-top:2px; color:#be123c; font-weight:600;">dòng máy tồn kho = 0</div>
                </div>
                <?php if ($filter === 'out'): ?>
                    <i class="fa-solid fa-chevron-right text-danger opacity-50"></i>
                <?php endif; ?>
            </div>
        </a>
    </div>
</div>

<!-- INVENTORY TABLE -->
<div class="admin-table-card">
    <!-- Table header toolbar -->
    <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f1f5f9;">
        <div>
            <span class="fw-bold text-dark" style="font-size:.95rem;">Kiểm Kê Tồn Kho</span>
            <span class="text-muted ms-2" style="font-size:.82rem;"><?= count($stockList) ?> sản phẩm</span>
        </div>
        <div class="d-flex gap-2">
            <a href="inventory.php?filter=all" class="inv-filter-tab tab-all <?= $filter === 'all' ? 'active' : '' ?>">
                Tất cả <span class="ms-1 badge rounded-pill" style="font-size:.65rem; background:<?= $filter==='all'?'#2563eb':'#e9ecef' ?>; color:<?= $filter==='all'?'#fff':'#64748b' ?>;"><?= $totalStock ?></span>
            </a>
            <a href="inventory.php?filter=low" class="inv-filter-tab tab-low <?= $filter === 'low' ? 'active' : '' ?>">
                <i class="fa-solid fa-triangle-exclamation me-1" style="font-size:.7rem;"></i>Sắp hết
                <span class="ms-1 badge rounded-pill" style="font-size:.65rem; background:<?= $filter==='low'?'#d97706':'#fde68a' ?>; color:<?= $filter==='low'?'#fff':'#92400e' ?>;"><?= $lowStock ?></span>
            </a>
            <a href="inventory.php?filter=out" class="inv-filter-tab tab-out <?= $filter === 'out' ? 'active' : '' ?>">
                <i class="fa-solid fa-circle-xmark me-1" style="font-size:.7rem;"></i>Hết hàng
                <span class="ms-1 badge rounded-pill" style="font-size:.65rem; background:<?= $filter==='out'?'#e11d48':'#fecdd3' ?>; color:<?= $filter==='out'?'#fff':'#be123c' ?>;"><?= $outOfStock ?></span>
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table inv-table mb-0">
            <thead>
                <tr>
                    <th style="width:56px;">Ảnh</th>
                    <th>Tên Điện Thoại</th>
                    <th>Hãng</th>
                    <th>Giá Bán</th>
                    <th style="text-align:center;">Tồn Kho</th>
                    <th>Tình Trạng</th>
                    <th>Màu / Tồn</th>
                    <th style="min-width:230px;">Nhập Hàng Nhanh</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stockList as $p): ?>
                    <?php
                        $productColors = array_values(array_filter(array_map('trim', explode(',', $p['colors'] ?? ''))));
                        $savedColorQuantities = json_decode($p['color_quantities'] ?? '', true);
                        $colorQuantitiesByName = [];
                        if (is_array($savedColorQuantities)) {
                            foreach ($savedColorQuantities as $cq) {
                                if (isset($cq['color'], $cq['quantity'])) {
                                    $colorQuantitiesByName[$cq['color']] = (int)$cq['quantity'];
                                }
                            }
                        }
                        $unassignedColors = array_diff($productColors, array_keys($colorQuantitiesByName));
                        $qty = (int)$p['quantity'];
                    ?>
                    <tr>
                        <!-- Ảnh -->
                        <td>
                            <div style="width:44px; height:44px; background:#f8fafc; border-radius:10px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                                <img src="../<?= htmlspecialchars($p['image']) ?>" alt=""
                                     style="max-width:40px; max-height:40px; object-fit:contain;">
                            </div>
                        </td>

                        <!-- Tên -->
                        <td>
                            <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="prod-sub"><?= $p['rom'] ?> | <?= $p['screen'] ?></div>
                        </td>

                        <!-- Hãng -->
                        <td>
                            <span class="brand-chip"><?= htmlspecialchars($p['brand_name'] ?? 'Khác') ?></span>
                        </td>

                        <!-- Giá -->
                        <td>
                            <span class="fw-bold" style="color:#ef4444; font-size:.88rem; white-space:nowrap;">
                                <?= number_format($qty > 0 || $p['sale_price'] > 0 ? ($p['sale_price'] > 0 ? $p['sale_price'] : $p['price']) : $p['price'], 0, ',', '.') ?>đ
                            </span>
                        </td>

                        <!-- Số lượng tồn -->
                        <td style="text-align:center;">
                            <div class="stock-num <?= $qty == 0 ? 'text-danger' : ($qty <= 5 ? 'text-warning' : 'text-success') ?>">
                                <?= $qty ?>
                            </div>
                            <div style="font-size:.7rem; color:#94a3b8; margin-top:1px;">máy</div>
                        </td>

                        <!-- Tình trạng -->
                        <td>
                            <?php if ($qty == 0): ?>
                                <span class="status-badge status-out">
                                    <i class="fa-solid fa-circle-xmark"></i> Hết hàng
                                </span>
                            <?php elseif ($qty <= 5): ?>
                                <span class="status-badge status-low">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Sắp hết
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-ok">
                                    <i class="fa-solid fa-circle-check"></i> Dồi dào
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Màu & tồn theo màu -->
                        <td style="min-width:170px;">
                            <div>
                                <?php foreach ($productColors as $color): ?>
                                    <?php if (array_key_exists($color, $colorQuantitiesByName)): ?>
                                        <span class="color-chip">
                                            <?= htmlspecialchars($color) ?>
                                            <span style="background:#e2e8f0; border-radius:6px; padding:1px 5px; font-size:.68rem; font-weight:800;">
                                                <?= $colorQuantitiesByName[$color] ?>
                                            </span>
                                        </span>
                                    <?php else: ?>
                                        <span class="color-chip unset">
                                            <?= htmlspecialchars($color) ?>
                                            <span style="font-size:.65rem; opacity:.7;">chưa nhập</span>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if (!$productColors): ?>
                                    <span style="font-size:.76rem; color:#94a3b8;">Chưa khai báo</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($productColors): ?>
                                <button type="button" class="btn btn-link p-0 text-decoration-none d-inline-flex align-items-center"
                                        style="font-size:.73rem; color:#0066cc; margin-top:4px;"
                                        onclick='openColorStockModal(<?= (int)$p['id'] ?>, <?= json_encode($p['name'], JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($productColors, JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($colorQuantitiesByName, JSON_UNESCAPED_UNICODE) ?>)'>
                                    <i class="fa-solid fa-pen-to-square me-1"></i><?= $unassignedColors ? 'Nhập tồn theo màu' : 'Chỉnh tồn theo màu' ?>
                                </button>
                            <?php endif; ?>
                        </td>

                        <!-- Thao tác nhập hàng -->
                        <td>
                            <form method="POST" action="inventory.php" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="update_stock" value="1">
                                <input type="number" name="set_quantity"
                                       value="<?= $qty ?>" min="0" max="9999"
                                       class="qty-input"
                                       title="Đặt số lượng chính xác">
                                <button type="submit" class="btn-save-stock" title="Lưu">
                                    <i class="fa-solid fa-floppy-disk me-1"></i>Lưu
                                </button>
                                <button type="submit" name="add_quantity" value="10"
                                        class="btn-add-stock btn-add-10" title="Nhập thêm 10 máy">
                                    +10
                                </button>
                                <button type="submit" name="add_quantity" value="50"
                                        class="btn-add-stock btn-add-50" title="Nhập thêm 50 máy">
                                    +50
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($stockList)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div style="color:#94a3b8;">
                                <i class="fa-solid fa-box-open fa-2x mb-3"></i>
                                <p class="mb-0 fw-semibold">Không có sản phẩm nào trong danh mục này</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL CẬP NHẬT TỒN KHO THEO MÀU -->
<div class="modal fade" id="colorStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="modal-title fw-bold text-primary mb-0">
                    <i class="fa-solid fa-boxes-stacked me-2"></i>Phân Bổ Tồn Kho Theo Màu
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="inventory.php">
                <input type="hidden" name="save_color_stock" value="1">
                <input type="hidden" name="product_id" id="modalColorStockProductId" value="0">
                <div class="modal-body p-4 pt-3">
                    <div class="p-3 bg-light rounded-4 border mb-3">
                        <div class="fw-bold text-dark mb-1" id="modalColorStockProdName">Tên điện thoại</div>
                        <div class="small text-secondary">
                            Tổng tồn kho: <span class="fw-bold text-primary" id="modalColorStockTotalCalc">0</span> máy
                        </div>
                    </div>

                    <label class="form-label small fw-bold text-uppercase text-secondary mb-2">
                        <i class="fa-solid fa-palette me-1 text-primary"></i>Số lượng từng màu:
                    </label>

                    <div id="modalColorStockRows" class="d-flex flex-column gap-2 mb-3">
                        <!-- Dynamic rows -->
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-3">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Lưu Tồn Kho Màu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let colorStockModalInstance = null;

function recalculateColorTotal() {
    let sum = 0;
    document.querySelectorAll('.modal-color-qty-input').forEach(input => {
        sum += parseInt(input.value || 0, 10);
    });
    const totalEl = document.getElementById('modalColorStockTotalCalc');
    if (totalEl) totalEl.innerText = sum;
}

function openColorStockModal(productId, productName, colors, currentQuantities) {
    document.getElementById('modalColorStockProductId').value = productId;
    document.getElementById('modalColorStockProdName').innerText = productName;

    const rowsBox = document.getElementById('modalColorStockRows');
    rowsBox.innerHTML = '';

    colors.forEach(col => {
        const qty = currentQuantities[col] !== undefined ? currentQuantities[col] : 0;
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center justify-content-between p-2 rounded-3 bg-white border';
        row.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <input type="hidden" name="color_names[]" value="${col}">
                <span class="fw-semibold text-dark small">${col}</span>
            </div>
            <div style="width: 110px;">
                <input type="number" name="color_qtys[]" min="0" max="9999" value="${qty}"
                       class="form-control form-control-sm text-center fw-bold modal-color-qty-input"
                       oninput="recalculateColorTotal()">
            </div>
        `;
        rowsBox.appendChild(row);
    });

    recalculateColorTotal();

    const modalEl = document.getElementById('colorStockModal');
    if (!colorStockModalInstance) {
        colorStockModalInstance = new bootstrap.Modal(modalEl);
    }
    colorStockModalInstance.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
