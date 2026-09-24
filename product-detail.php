<?php
require_once 'config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<h2 style='text-align:center; margin-top:50px;'>Không tìm thấy sản phẩm! <a href='index.php'>Quay lại</a></h2>";
    exit;
}

$pageTitle = $product['name'] . ' - V-Phone Chính Hãng';

$basePrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) ? $product['sale_price'] : $product['price'];
$baseOldPrice = $product['price'];
$saving = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) ? ($product['price'] - $product['sale_price']) : 0;

$relStmt = $pdo->prepare("SELECT * FROM products WHERE brand_id = :bid AND id != :id LIMIT 4");
$relStmt->execute([':bid' => $product['brand_id'], ':id' => $id]);
$relatedProducts = $relStmt->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container my-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb py-2 px-3 bg-white rounded-4 shadow-sm mb-0">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-primary"><i class="fa-solid fa-house me-1"></i>Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="index.php?brand_id=<?= $product['brand_id'] ?>" class="text-decoration-none text-primary"><?= htmlspecialchars($product['brand_name'] ?? 'Điện thoại') ?></a></li>
            <li class="breadcrumb-item active text-truncate" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>
</div>

<div class="container my-4">
    <div class="row g-4">
        <!-- Cột trái: Ảnh -->
        <div class="col-lg-5">
            <div class="bg-white p-4 rounded-4 shadow-sm text-center position-relative border">
                <?php if ($product['is_featured']): ?>
                    <span class="badge badge-tech-new position-absolute top-0 start-0 m-3 px-3 py-2 rounded-pill shadow-sm">
                        <i class="fa-solid fa-bolt me-1 text-primary"></i>2026 FLAGSHIP
                    </span>
                <?php endif; ?>

                <div class="detail-img-box my-3 py-3" style="min-height: 360px; display: flex; align-items: center; justify-content: center;">
                    <img src="<?= htmlspecialchars($product['image']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="img-fluid" 
                         style="max-height: 340px; object-fit: contain;">
                </div>

                <div class="d-flex justify-content-center gap-2 mt-2">
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-shield-halved me-1"></i>Bảo hành 12T</span>
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-rotate-left me-1"></i>1 đổi 1 30 ngày</span>
                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fa-solid fa-truck-fast me-1"></i>Giao hỏa tốc 2H</span>
                </div>
            </div>
        </div>

        <!-- Cột phải: Thông tin, chọn bộ nhớ, nút mua -->
        <div class="col-lg-7">
            <div class="bg-white p-4 rounded-4 shadow-sm h-100 d-flex flex-column border">
                <span class="badge badge-tech-new align-self-start px-3 py-1 mb-2 rounded-pill">
                    <?= htmlspecialchars($product['brand_name'] ?? 'CHÍNH HÃNG') ?>
                </span>
                <h3 class="fw-bold text-dark mb-2"><?= htmlspecialchars($product['name']) ?></h3>

                <div class="d-flex align-items-center gap-2 text-warning mb-3 small">
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <span class="text-secondary">(4.9/5 - 128 lượt mua đánh giá)</span>
                </div>

                <!-- Giá tiền nhảy động -->
                <div class="p-3 rounded-4 bg-light my-2 border">
                    <div class="d-flex align-items-baseline gap-3 flex-wrap">
                        <h2 class="text-danger fw-bold mb-0" id="displayPrice"><?= number_format($basePrice, 0, ',', '.') ?> đ</h2>
                        <h5 class="text-decoration-line-through text-secondary mb-0" id="displayOldPrice"><?= number_format($baseOldPrice, 0, ',', '.') ?> đ</h5>
                        <span class="badge bg-danger rounded-pill px-2 py-1" id="displaySavingTag">Tiết kiệm <?= number_format($saving, 0, ',', '.') ?> đ</span>
                    </div>
                    <div class="small text-success mt-2 fw-semibold">
                        <i class="fa-solid fa-circle-check me-1"></i>Sản phẩm sẵn hàng tại hệ thống - Miễn phí vận chuyển toàn quốc
                    </div>
                </div>

                <!-- TÙY CHỌN DUNG LƯỢNG BỘ NHỚ -->
                <div class="my-3">
                    <label class="fw-bold mb-2 small text-uppercase text-secondary">
                        <i class="fa-solid fa-hard-drive me-1 text-primary"></i>Chọn phiên bản bộ nhớ (Bấm để đổi giá):
                    </label>
                    <div class="d-flex gap-2 flex-wrap" id="storageButtonsGroup">
                        <button type="button" class="btn btn-primary rounded-pill px-3 py-2 fw-bold storage-btn active" data-extra="0" data-rom="256 GB">
                            256 GB <small class="fw-normal">(Tiêu chuẩn)</small>
                        </button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-bold storage-btn" data-extra="4000000" data-rom="512 GB">
                            512 GB <small class="text-primary fw-semibold">(+4.000.000 đ)</small>
                        </button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-bold storage-btn" data-extra="9000000" data-rom="1 TB">
                            1 TB <small class="text-primary fw-semibold">(+9.000.000 đ)</small>
                        </button>
                    </div>
                </div>

                <div class="border border-primary border-opacity-25 rounded-4 p-3 my-2 bg-light">
                    <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-gift me-2"></i>Đặc Quyền Mua Hàng Tại V-Phone:</h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-1 text-secondary">
                        <li><i class="fa-solid fa-check text-primary me-2"></i>Gói bảo hành VIP 1 đổi 1 trong 12 tháng tại hệ thống.</li>
                        <li><i class="fa-solid fa-check text-primary me-2"></i>Thu cũ đổi mới trợ giá trực tiếp lên đến <strong>5.000.000 đ</strong>.</li>
                        <li><i class="fa-solid fa-check text-primary me-2"></i>Tặng kèm củ sạc siêu nhanh chính hãng và dán cường lực miễn phí.</li>
                    </ul>
                </div>

                <!-- 2 NÚT HÀNH ĐỘNG -->
                <div class="row g-2 mt-auto pt-3">
                    <div class="col-md-7 col-12">
                        <button type="button" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3" onclick="buyNow(<?= $product['id'] ?>)">
                            <i class="fa-solid fa-bag-shopping me-2"></i>MUA NGAY (GIAO TẬN NƠI)
                        </button>
                    </div>
                    <div class="col-md-5 col-12">
                        <button type="button" class="btn btn-outline-vphone btn-lg w-100 rounded-pill fw-bold py-3" id="btnDetailAddToCart" onclick="addToCartFromDetail(this, <?= $product['id'] ?>)">
                            <i class="fa-solid fa-cart-plus me-2"></i>Thêm vào giỏ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BẢNG THÔNG SỐ KỸ THUẬT -->
    <div class="my-5">
        <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border">
            <h4 class="fw-bold text-dark border-bottom pb-3 mb-4">
                <i class="fa-solid fa-sliders text-primary me-2"></i>Bảng Thông Số Kỹ Thuật Chi Tiết
            </h4>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <table class="table table-striped table-hover align-middle border rounded-3 overflow-hidden">
                        <tbody>
                            <tr>
                                <th class="text-secondary py-3 ps-3 w-40"><i class="fa-solid fa-mobile-screen me-2 text-primary"></i>Kích thước màn hình:</th>
                                <td class="fw-semibold py-3"><?= htmlspecialchars($product['screen']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary py-3 ps-3"><i class="fa-solid fa-microchip me-2 text-primary"></i>Chip xử lý (CPU):</th>
                                <td class="fw-semibold py-3"><?= htmlspecialchars($product['cpu']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary py-3 ps-3"><i class="fa-solid fa-memory me-2 text-primary"></i>Bộ nhớ RAM:</th>
                                <td class="fw-semibold py-3"><?= htmlspecialchars($product['ram']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary py-3 ps-3"><i class="fa-solid fa-hard-drive me-2 text-primary"></i>Bộ nhớ lưu trữ (ROM):</th>
                                <td class="fw-bold text-primary py-3" id="tableRomValue"><?= htmlspecialchars($product['rom']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary py-3 ps-3"><i class="fa-solid fa-battery-full me-2 text-primary"></i>Dung lượng Pin:</th>
                                <td class="fw-semibold py-3"><?= htmlspecialchars($product['battery']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary py-3 ps-3"><i class="fa-solid fa-bolt me-2 text-primary"></i>Công nghệ sạc:</th>
                                <td class="fw-semibold py-3">Sạc siêu nhanh 45W - 120W, Sạc không dây MagSafe / Qi2</td>
                            </tr>
                            <tr>
                                <th class="text-secondary py-3 ps-3"><i class="fa-solid fa-camera me-2 text-primary"></i>Hệ thống Camera:</th>
                                <td class="fw-semibold py-3">Camera AI 200MP, Tele Zoom 5x quang học, Siêu góc rộng Ultra-Wide</td>
                            </tr>
                            <tr>
                                <th class="text-secondary py-3 ps-3"><i class="fa-solid fa-wifi me-2 text-primary"></i>Kết nối mạng:</th>
                                <td class="fw-semibold py-3">5G Siêu tốc, Wi-Fi 7, Bluetooth 5.4, Cổng Type-C Thunderbolt</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const basePrice = <?= (int)$basePrice ?>;
const baseOldPrice = <?= (int)$baseOldPrice ?>;
let currentSelectedRom = "256 GB";
let currentExtraMoney = 0;

function formatCurrency(number) {
    return new Intl.NumberFormat('vi-VN').format(number) + ' đ';
}

document.querySelectorAll('.storage-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.storage-btn').forEach(b => {
            b.classList.remove('btn-primary', 'active');
            b.classList.add('btn-outline-secondary');
        });

        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-primary', 'active');

        currentExtraMoney = parseInt(this.getAttribute('data-extra')) || 0;
        currentSelectedRom = this.getAttribute('data-rom') || "256 GB";

        const newPrice = basePrice + currentExtraMoney;
        const newOldPrice = baseOldPrice + currentExtraMoney;
        const newSaving = newOldPrice - newPrice;

        document.getElementById('displayPrice').innerText = formatCurrency(newPrice);
        document.getElementById('displayOldPrice').innerText = formatCurrency(newOldPrice);
        
        const savingTag = document.getElementById('displaySavingTag');
        if (savingTag) {
            savingTag.innerText = 'Tiết kiệm ' + formatCurrency(newSaving);
        }

        const tableRom = document.getElementById('tableRomValue');
        if (tableRom) {
            tableRom.innerText = currentSelectedRom;
        }
    });
});

// NÚT THÊM GIỎ: TRUYỀN ĐÚNG BỘ NHỚ VÀ SỐ TIỀN TĂNG THÊM
function addToCartFromDetail(btn, productId) {
    if (btn.disabled) return;
    btn.disabled = true;

    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Đang thêm...';

    // Gửi chính xác: id, rom đã chọn, và số tiền extra
    const url = `cart.php?action=add&ajax=1&id=${productId}&rom=${encodeURIComponent(currentSelectedRom)}&extra=${currentExtraMoney}`;

    fetch(url)
        .then(res => res.text())
        .then(rawText => {
            const data = JSON.parse(rawText.trim());
            if (data.success) {
                btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Đã thêm!';
                btn.style.backgroundColor = '#004799';
                btn.style.borderColor = '#004799';
                btn.style.color = '#ffffff';

                const badge = document.getElementById('cartBadge');
                if (badge) {
                    badge.innerText = data.cart_count;
                }

                const toast = document.getElementById('vphoneLiveToast');
                const toastText = document.getElementById('vphoneToastText');
                if (toast && toastText) {
                    toastText.innerText = 'Đã thêm "' + data.product_name + '"';
                    toast.style.display = 'block';

                    clearTimeout(toastTimer);
                    toastTimer = setTimeout(() => {
                        toast.style.display = 'none';
                    }, 3500);
                }

                setTimeout(() => {
                    btn.innerHTML = oldHtml;
                    btn.style.backgroundColor = '';
                    btn.style.borderColor = '';
                    btn.style.color = '';
                    btn.disabled = false;
                }, 1200);
            }
        })
        .catch(err => {
            console.error(err);
            btn.innerHTML = oldHtml;
            btn.disabled = false;
        });
}

function buyNow(productId) {
    window.location.href = `cart.php?action=add&id=${productId}&rom=${encodeURIComponent(currentSelectedRom)}&extra=${currentExtraMoney}&redirect=checkout`;
}
</script>

<?php require_once 'includes/footer.php'; ?>
