<?php
$totalMoney = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $itPrice = (float)($item['price'] ?? 0);
        $itQty = (int)($item['quantity'] ?? 1);
        $totalMoney += $itPrice * $itQty;
    }
}
require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<div class="container my-4">
    <!-- TIẾN TRÌNH ĐƠN HÀNG STEPPER CHUẨN HIỆN ĐẠI (1 2 3 KHÔNG LẶP SỐ) -->
    <div class="d-flex justify-content-center mb-4">
        <div class="checkout-stepper-wrapper">
            <!-- Bước 1: Giỏ hàng -->
            <div class="step-badge step-active">
                <span class="step-circle"><i class="fa-solid fa-cart-shopping"></i></span>
                <span class="step-text">Giỏ hàng</span>
            </div>

            <div class="step-connector"></div>

            <!-- Bước 2: Thanh toán -->
            <div class="step-badge step-pending">
                <span class="step-circle">2</span>
                <span class="step-text">Thanh toán</span>
            </div>

            <div class="step-connector"></div>

            <!-- Bước 3: Hoàn tất -->
            <div class="step-badge step-pending">
                <span class="step-circle">3</span>
                <span class="step-text">Hoàn tất</span>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-cart-shopping text-primary me-2"></i>Giỏ Hàng Của Bạn</h4>
        <span class="badge bg-white text-secondary border px-3 py-2 rounded-pill"><?= !empty($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?> sản phẩm</span>
    </div>

    <?php if (!empty($_SESSION['cart_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 py-2" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['cart_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['cart_error']); ?>
    <?php endif; ?>

    <?php if (empty($_SESSION['cart'])): ?>
        <div class="bg-white p-5 rounded-4 shadow-sm text-center border">
            <div class="d-inline-flex p-4 rounded-circle bg-light text-primary mb-3">
                <i class="fa-solid fa-cart-arrow-down fs-1"></i>
            </div>
            <h5 class="fw-bold text-dark">Giỏ hàng đang trống!</h5>
            <p class="text-secondary small mb-4">Bạn chưa chọn sản phẩm nào. Hãy khám phá các dòng Flagship 2026 ngay hôm nay.</p>
            <a href="index.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                <i class="fa-solid fa-store me-1"></i>Khám phá điện thoại ngay
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="bg-white rounded-4 shadow-sm p-4 border">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="min-width: 260px;">Sản phẩm</th>
                                    <th scope="col">Đơn giá</th>
                                    <th scope="col" style="width: 140px;">Số lượng</th>
                                    <th scope="col">Thành tiền</th>
                                    <th scope="col" style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $cKey => $item): ?>
                                    <?php 
                                        $itemPrice = (float)($item['price'] ?? 0);
                                        $itemQty = (int)($item['quantity'] ?? 1);
                                        $subtotal = $itemPrice * $itemQty;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="p-2 bg-light rounded-3 me-3 border d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; flex-shrink: 0;">
                                                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;" onerror="this.onerror=null; this.src='assets/images/products/iphone-16.png';">
                                                </div>
                                                <div>
                                                    <a href="index.php?page=detail&id=<?= $item['id'] ?>" class="text-decoration-none text-dark fw-bold small d-block mb-1"><?= htmlspecialchars($item['name']) ?></a>
                                                    <!-- HIỂN THỊ CẢ BỘ NHỚ LẪN MÀU SẮC RÕ RÀNG -->
                                                    <div class="d-flex gap-1 flex-wrap">
                                                        <span class="badge bg-light text-primary border" style="font-size:0.68rem;">Dung lượng: <strong><?= htmlspecialchars($item['rom'] ?? 'Tiêu chuẩn') ?></strong></span>
                                                        <span class="badge bg-light text-dark border" style="font-size:0.68rem;"><i class="fa-solid fa-palette text-primary me-1"></i>Màu: <strong><?= htmlspecialchars($item['color'] ?? 'Tiêu chuẩn') ?></strong></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-danger fw-bold fs-6"><?= number_format($itemPrice, 0, ',', '.') ?> đ</td>
                                        <td>
                                            <form action="index.php?page=cart&action=update&key=<?= urlencode($cKey) ?>" method="POST" class="d-flex align-items-center">
                                                <div class="input-group input-group-sm" style="width: 110px;">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="adjustCartQuantity(this, -1)">-</button>
                                                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="99" class="form-control text-center fw-bold px-1" onchange="this.form.submit()">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="adjustCartQuantity(this, 1)">+</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="text-primary fw-bold fs-6"><?= number_format($subtotal, 0, ',', '.') ?> đ</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-light text-danger rounded-circle p-2" title="Xóa món này" data-bs-toggle="modal" data-bs-target="#cartConfirmModal" data-cart-action="delete" data-cart-key="<?= htmlspecialchars($cKey) ?>" data-cart-name="<?= htmlspecialchars($item['name']) ?>">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="fa-solid fa-arrow-left me-1"></i>Chọn thêm điện thoại khác</a>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#cartConfirmModal" data-cart-action="clear"><i class="fa-solid fa-trash-can me-1"></i>Xóa toàn bộ</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="bg-white rounded-4 shadow-sm p-4 border mb-3">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark"><i class="fa-solid fa-receipt text-primary me-2"></i>Tóm Tắt Đơn Hàng</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Tạm tính (<?= count($_SESSION['cart']) ?> món):</span>
                        <span class="fw-bold text-dark"><?= number_format($totalMoney, 0, ',', '.') ?> đ</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-secondary">Phí vận chuyển:</span>
                        <span class="text-success fw-bold"><i class="fa-solid fa-check me-1"></i>MIỄN PHÍ</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4 align-items-baseline">
                        <span class="fs-6 fw-bold text-dark">Tổng thanh toán:</span>
                        <span class="fs-4 fw-bold text-danger"><?= number_format($totalMoney, 0, ',', '.') ?> đ</span>
                    </div>

                    <a href="index.php?page=checkout" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3 d-flex align-items-center justify-content-center gap-2">
                        <span>TIẾN HÀNH ĐẶT HÀNG</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <div class="bg-white rounded-4 p-3 border shadow-sm">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <i class="fa-solid fa-shield-halved text-primary fs-4"></i>
                        <small class="text-secondary"><strong>Bảo hành chính hãng 12 tháng</strong> cam kết 1 đổi 1 trong 30 ngày.</small>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-truck-fast text-success fs-4"></i>
                        <small class="text-secondary"><strong>Giao hỏa tốc 2H</strong> miễn phí vận chuyển trên toàn quốc.</small>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="cartConfirmModal" tabindex="-1" aria-labelledby="cartConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-body p-4 p-md-5 text-center">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10 text-danger mx-auto mb-3" style="width: 58px; height: 58px;">
                    <i class="fa-solid fa-trash-can fs-4"></i>
                </div>
                <h5 class="fw-bold mb-2" id="cartConfirmTitle">Xác nhận xóa</h5>
                <p class="text-secondary mb-4" id="cartConfirmMessage">Bạn có chắc muốn xóa sản phẩm này?</p>
                <form method="POST" action="index.php?page=cart" class="d-flex justify-content-center gap-2">
                    <input type="hidden" name="action" id="cartConfirmAction">
                    <input type="hidden" name="key" id="cartConfirmKey">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Giữ lại</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4" id="cartConfirmSubmit">Xóa sản phẩm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function adjustCartQuantity(btn, delta) {
    const input = delta < 0 ? btn.nextElementSibling : btn.previousElementSibling;
    if (!input) return;
    const newVal = parseInt(input.value || 1, 10) + delta;
    if (newVal >= 1 && newVal <= 99) {
        input.value = newVal;
        btn.form.submit();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const confirmModal = document.getElementById('cartConfirmModal');
    if (!confirmModal) return;

    confirmModal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        const action = trigger.dataset.cartAction;
        const isClear = action === 'clear';

        document.getElementById('cartConfirmAction').value = action;
        document.getElementById('cartConfirmKey').value = trigger.dataset.cartKey || '';
        document.getElementById('cartConfirmTitle').textContent = isClear ? 'Xóa toàn bộ giỏ hàng?' : 'Xóa sản phẩm?';
        document.getElementById('cartConfirmMessage').textContent = isClear
            ? 'Tất cả sản phẩm trong giỏ sẽ bị xóa. Bạn có chắc muốn tiếp tục?'
            : `Xóa "${trigger.dataset.cartName}" khỏi giỏ hàng?`;
        document.getElementById('cartConfirmSubmit').textContent = isClear ? 'Xóa toàn bộ' : 'Xóa sản phẩm';
    });
});
</script>

<?php require_once 'app/views/includes/footer.php'; ?>
