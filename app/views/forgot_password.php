<?php
/**
 * Giao diện Quên mật khẩu & Đặt lại mật khẩu V-Phone Store
 */
$pageTitle = $pageTitle ?? 'Quên & Đặt Lại Mật Khẩu - V-Phone Store';
$resetToken = trim($_GET['token'] ?? '');
$targetEmail = trim($_GET['email'] ?? '');
$initialStep = !empty($resetToken) ? 2 : 1;

require_once 'app/views/includes/header.php';
require_once 'app/views/includes/navbar.php';
?>

<div class="container py-5 my-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <!-- Header Card -->
                <div class="card-header border-0 text-white p-4 text-center position-relative" style="background: linear-gradient(135deg, #0066cc 0%, #0284c7 100%);">
                    <div class="rounded-circle bg-white bg-opacity-20 d-inline-flex align-items-center justify-content-center mb-2" style="width: 58px; height: 58px;">
                        <i class="fa-solid fa-shield-halved fs-3 text-white"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Khôi Phục Mật Khẩu</h4>
                    <p class="small text-white-50 mb-0">Hệ thống bảo mật tài khoản khách hàng V-Phone</p>
                </div>

                <div class="card-body p-4 p-md-5 bg-white">
                    <div id="forgotLiveAlert" class="alert d-none small py-2.5 rounded-3 mb-4"></div>

                    <!-- BƯỚC 1: NHẬP EMAIL ĐỂ NHẬN OTP -->
                    <div id="stepRequestEmail" class="<?= $initialStep === 1 ? '' : 'd-none' ?>">
                        <div class="text-center mb-4">
                            <p class="text-secondary small mb-0">
                                Nhập địa chỉ email tài khoản của bạn. Chúng tôi sẽ gửi mã xác thực OTP (6 chữ số) để bạn đặt lại mật khẩu mới an toàn.
                            </p>
                        </div>

                        <form id="formPageForgot" method="POST" action="index.php?page=auth&action=forgot_password">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Email đăng ký tài khoản <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-envelope"></i></span>
                                    <input type="email" name="email" id="inputForgotEmail" class="form-control border-start-0" required placeholder="name@vphone.vn" value="<?= htmlspecialchars($targetEmail) ?>">
                                </div>
                            </div>

                            <button type="submit" id="btnSubmitForgot" class="btn btn-primary w-100 rounded-pill py-2.5 fw-bold shadow-sm mb-3">
                                <i class="fa-solid fa-paper-plane me-2"></i>GỬI MÃ XÁC THỰC
                            </button>

                            <div class="text-center pt-2 border-top">
                                <span class="small text-muted">Bạn đã nhớ mật khẩu? </span>
                                <a href="javascript:void(0)" class="small text-primary fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#unifiedAuthModal">
                                    Đăng nhập ngay
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- BƯỚC 2: NHẬP OTP VÀ MẬT KHẨU MỚI -->
                    <div id="stepResetPassword" class="<?= $initialStep === 2 ? '' : 'd-none' ?>">
                        <div class="text-center mb-4">
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold mb-2">Bước 2: Xác thực & Đổi mật khẩu</span>
                            <p class="text-secondary small mb-0">
                                Mã OTP đã được gửi đến: <strong id="displayTargetEmail" class="text-dark"><?= htmlspecialchars($targetEmail) ?></strong>
                            </p>
                        </div>

                        <form id="formPageResetPassword" method="POST" action="index.php?page=auth&action=reset_password">
                            <input type="hidden" name="email" id="hiddenResetEmail" value="<?= htmlspecialchars($targetEmail) ?>">
                            <input type="hidden" name="token" id="hiddenResetToken" value="<?= htmlspecialchars($resetToken) ?>">

                            <?php if (empty($resetToken)): ?>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Mã OTP (6 chữ số) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-key"></i></span>
                                    <input type="text" name="otp" id="inputResetOtp" class="form-control border-start-0 text-center fw-bold fs-5 letter-spacing-2" required maxlength="6" placeholder="123456" autocomplete="one-time-code">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted" style="font-size: 0.75rem;">Mã có hiệu lực trong 15 phút</small>
                                    <a href="javascript:void(0)" id="btnResendOtp" class="small text-primary text-decoration-none" style="font-size: 0.75rem;">
                                        <i class="fa-solid fa-rotate-right me-1"></i>Gửi lại mã
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Mật khẩu mới <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" name="new_password" id="inputNewPassword" class="form-control border-start-0" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                                    <button class="btn btn-outline-light border text-muted" type="button" onclick="togglePassVisibility('inputNewPassword', this)">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-shield-check"></i></span>
                                    <input type="password" name="confirm_password" id="inputConfirmPassword" class="form-control border-start-0" required minlength="6" placeholder="Nhập lại mật khẩu mới">
                                    <button class="btn btn-outline-light border text-muted" type="button" onclick="togglePassVisibility('inputConfirmPassword', this)">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" id="btnSubmitReset" class="btn btn-success w-100 rounded-pill py-2.5 fw-bold shadow-sm mb-3">
                                <i class="fa-solid fa-circle-check me-2"></i>LƯU MẬT KHẨU MỚI
                            </button>

                            <div class="text-center pt-2 border-top">
                                <a href="javascript:void(0)" id="btnBackToStep1" class="small text-secondary text-decoration-none">
                                    <i class="fa-solid fa-arrow-left me-1"></i>Nhập lại email khác
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- BƯỚC 3: THÀNH CÔNG -->
                    <div id="stepResetSuccess" class="d-none text-center py-3">
                        <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                            <i class="fa-solid fa-check fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Đổi Mật Khẩu Thành Công!</h5>
                        <p class="text-secondary small mb-4">
                            Mật khẩu tài khoản của bạn đã được cập nhật thành công. Bạn có thể đăng nhập ngay bây giờ.
                        </p>
                        <a href="index.php?show_login=1" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#unifiedAuthModal">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>ĐĂNG NHẬP NGAY
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    const icon = btn.querySelector('i');
    if (icon) {
        icon.className = isPassword ? 'fa-solid fa-eye-slash text-primary' : 'fa-solid fa-eye text-muted';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const formForgot = document.getElementById('formPageForgot');
    const formReset = document.getElementById('formPageResetPassword');
    const alertBox = document.getElementById('forgotLiveAlert');
    const step1 = document.getElementById('stepRequestEmail');
    const step2 = document.getElementById('stepResetPassword');
    const stepSuccess = document.getElementById('stepResetSuccess');
    const displayEmail = document.getElementById('displayTargetEmail');
    const hiddenEmail = document.getElementById('hiddenResetEmail');
    const btnBack1 = document.getElementById('btnBackToStep1');
    const btnResend = document.getElementById('btnResendOtp');

    function showAlert(type, message) {
        if (!alertBox) return;
        alertBox.className = `alert alert-${type} small py-2.5 rounded-3 mb-4`;
        alertBox.innerHTML = message;
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Bước 1: Gửi yêu cầu lấy mã OTP
    if (formForgot) {
        formForgot.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitForgot');
            const origHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
            }

            try {
                const response = await fetch(formForgot.action, {
                    method: 'POST',
                    body: new FormData(formForgot),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const res = await response.json();

                if (!res.success) {
                    if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
                    showAlert('danger', '<i class="fa-solid fa-triangle-exclamation me-2"></i>' + (res.message || 'Có lỗi xảy ra!'));
                    return;
                }

                // Thành công: Chuyển sang Bước 2
                if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
                const userEmail = res.email || document.getElementById('inputForgotEmail').value;
                if (displayEmail) displayEmail.textContent = userEmail;
                if (hiddenEmail) hiddenEmail.value = userEmail;

                // Thông báo kèm OTP để test dễ dàng trên Localhost
                let msg = '<i class="fa-solid fa-circle-check me-2"></i>' + res.message;
                if (res.otp) {
                    msg += `<div class="mt-2 p-2 bg-light rounded border text-dark fw-normal"><i class="fa-solid fa-lightbulb text-warning me-1"></i><strong>Mã OTP thử nghiệm nhanh:</strong> <span class="badge bg-success fs-6 ms-1">${res.otp}</span></div>`;
                }
                showAlert('success', msg);

                step1.classList.add('d-none');
                step2.classList.remove('d-none');
                const otpInput = document.getElementById('inputResetOtp');
                if (otpInput) otpInput.focus();

            } catch (err) {
                if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
                showAlert('danger', '<i class="fa-solid fa-triangle-exclamation me-2"></i>Lỗi kết nối máy chủ! Vui lòng thử lại.');
            }
        });
    }

    // Gửi lại mã OTP
    if (btnResend) {
        btnResend.addEventListener('click', function(e) {
            e.preventDefault();
            step2.classList.add('d-none');
            step1.classList.remove('d-none');
            showAlert('info', 'Vui lòng kiểm tra lại email và bấm "Gửi mã xác thực" để nhận mã OTP mới.');
        });
    }

    if (btnBack1) {
        btnBack1.addEventListener('click', function(e) {
            e.preventDefault();
            step2.classList.add('d-none');
            step1.classList.remove('d-none');
        });
    }

    // Bước 2: Đặt lại mật khẩu mới
    if (formReset) {
        formReset.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitReset');
            const origHtml = btn ? btn.innerHTML : '';
            const p1 = document.getElementById('inputNewPassword').value;
            const p2 = document.getElementById('inputConfirmPassword').value;

            if (p1.length < 6) {
                showAlert('danger', 'Mật khẩu mới phải có tối thiểu 6 ký tự!');
                return;
            }
            if (p1 !== p2) {
                showAlert('danger', 'Xác nhận mật khẩu không trùng khớp!');
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu mật khẩu...';
            }

            try {
                const response = await fetch(formReset.action, {
                    method: 'POST',
                    body: new FormData(formReset),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const res = await response.json();

                if (!res.success) {
                    if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
                    showAlert('danger', '<i class="fa-solid fa-triangle-exclamation me-2"></i>' + (res.message || 'Không thể đổi mật khẩu!'));
                    return;
                }

                // Thành công
                showAlert('success', '<i class="fa-solid fa-circle-check me-2"></i>' + res.message);
                step2.classList.add('d-none');
                stepSuccess.classList.remove('d-none');

            } catch (err) {
                if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
                showAlert('danger', '<i class="fa-solid fa-triangle-exclamation me-2"></i>Lỗi kết nối máy chủ! Vui lòng thử lại.');
            }
        });
    }
});
</script>

<?php require_once 'app/views/includes/footer.php'; ?>
