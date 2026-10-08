<?php
require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<div class="container my-4">
    <h4 class="fw-bold mb-3"><i class="fa-solid fa-cart-shopping text-primary me-2"></i>Giỏ Hàng Của Bạn</h4>

    <?php if (empty($_SESSION['cart'])): ?>
        <div class="bg-white p-5 rounded-4 shadow-sm text-center border">
            <i class="fa-solid fa-cart-arrow-down text-muted fs-1 mb-3"></i>
            <h5 class="fw-bold text-dark">Giỏ hàng đang trống!</h5>
            <p class="text-secondary small">Hãy chọn những chiếc điện thoại ưng ý tại trang chủ nhé.</p>
            <a href="index.php" class="btn btn-primary rounded-pill px-4 mt-2">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="bg-white rounded-4 shadow-sm p-4 border">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Sản phẩm</th>
                                    <th scope="col">Đơn giá</th>
                                    <th scope="col" style="width: 130px;">Số lượng</th>
                                    <th scope="col">Thành tiền</th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $cKey => $item): ?>
                                    <?php $subtotal = $item['price'] * $item['quantity']; ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 52px; height: 52px; object-fit: contain;" class="me-3" onerror="this.onerror=null; this.src='assets/images/products/iphone-16.png';">
                                                <div>
                                                    <a href="index.php?page=detail&id=<?= $item['id'] ?>" class="text-decoration-none text-dark fw-bold small d-block"><?= htmlspecialchars($item['name']) ?></a>
                                                    <!-- HIỂN THỊ CẢ BỘ NHỚ LẪN MÀU SẮC RÕ RÀNG -->
                                                    <div class="mt-1 d-flex gap-1 flex-wrap">
                                                        <span class="badge bg-light text-primary border" style="font-size:0.68rem;">Bản: <strong><?= htmlspecialchars($item['rom'] ?? 'Tiêu chuẩn') ?></strong></span>
                                                        <span class="badge bg-light text-dark border" style="font-size:0.68rem;"><i class="fa-solid fa-palette text-primary me-1"></i>Màu: <strong><?= htmlspecialchars($item['color'] ?? 'Tiêu chuẩn') ?></strong></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-danger fw-bold fs-6"><?= number_format($item['price'], 0, ',', '.') ?> đ</td>
                                        <td>
                                            <form action="index.php?page=cart&action=update&key=<?= urlencode($cKey) ?>" method="POST" class="d-flex align-items-center">
                                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="99" class="form-control form-control-sm text-center fw-bold" onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td class="text-primary fw-bold fs-6"><?= number_format($subtotal, 0, ',', '.') ?> đ</td>
                                        <td>
                                            <button type="button" class="text-danger btn btn-sm btn-light rounded-circle" title="Xóa món này" data-bs-toggle="modal" data-bs-target="#cartConfirmModal" data-cart-action="delete" data-cart-key="<?= htmlspecialchars($cKey) ?>" data-cart-name="<?= htmlspecialchars($item['name']) ?>">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="fa-solid fa-arrow-left me-1"></i>Chọn thêm điện thoại khác</a>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#cartConfirmModal" data-cart-action="clear">Xóa toàn bộ</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="bg-white rounded-4 shadow-sm p-4 border">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Tóm Tắt Đơn Hàng</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Tạm tính:</span>
                        <span class="fw-bold"><?= number_format($totalMoney, 0, ',', '.') ?> đ</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-secondary">Phí vận chuyển:</span>
                        <span class="text-success fw-bold">MIỄN PHÍ</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fs-5 fw-bold">Tổng thanh toán:</span>
                        <span class="fs-4 fw-bold text-danger"><?= number_format($totalMoney, 0, ',', '.') ?> đ</span>
                    </div>

                    <a href="index.php?page=checkout" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm py-3">
                        TIẾN HÀNH ĐẶT HÀNG <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
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
