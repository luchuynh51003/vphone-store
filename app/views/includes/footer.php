<footer class="footer-vphone pt-5 pb-3 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <h4 class="fw-bold text-white d-flex align-items-center mb-3">
                    <img src="assets/images/vphone-logo.svg" alt="V-Phone" style="width: 32px; height: 32px; margin-right: 8px;">
                    V-Phone Store
                </h4>
                <p class="small text-light opacity-75">Hệ thống phân phối điện thoại thông minh chính hãng hàng đầu. Cam kết chất lượng, bảo hành 1 đổi 1 nhanh chóng.</p>
                <div class="p-2 rounded-3 bg-white bg-opacity-10 small text-light mt-3">
                    <i class="fa-solid fa-graduation-cap me-2 text-info"></i>Đồ án: <strong>Huỳnh Bá Lực & Võ Minh Hiếu</strong>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <h6 class="text-uppercase fw-bold text-white mb-3 tracking-wide">Hỗ Trợ Khách Hàng</h6>
                <ul class="list-unstyled small opacity-75 d-flex flex-column gap-2">
                    <li><a href="#" class="text-light text-decoration-none"><i class="fa-solid fa-angle-right me-2 text-info"></i>Chính sách bảo hành toàn diện 12 tháng</a></li>
                    <li><a href="#" class="text-light text-decoration-none"><i class="fa-solid fa-angle-right me-2 text-info"></i>Chính sách đổi trả 1 - 1 trong 30 ngày</a></li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-12">
                <h6 class="text-uppercase fw-bold text-white mb-3 tracking-wide">Tổng Đài Liên Hệ</h6>
                <div class="d-flex flex-column gap-2 small opacity-75">
                    <p class="mb-0"><i class="fa-solid fa-phone-volume me-2 text-info"></i>Hotline: <strong>1800 6868</strong> (Miễn phí 8:00 - 21:30)</p>
                    <p class="mb-0"><i class="fa-solid fa-envelope me-2 text-info"></i>Email: cskh@vphone.vn</p>
                </div>
            </div>
        </div>

        <hr class="border-light opacity-25 my-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center small text-light opacity-50">
            <div>&copy; 2026 <strong>V-Phone Store</strong>. All rights reserved.</div>
            <div>Đồ án Chuyên ngành Công Nghệ Thông Tin</div>
        </div>
    </div>
</footer>

<!-- KHUNG TOAST TRẮNG - XANH DƯƠNG -->
<div id="vphoneLiveToast">
    <div style="display:flex; align-items:center;">
        <div style="width:40px; height:40px; border-radius:50%; background:#e8f3ff; display:flex; align-items:center; justify-content:center; margin-right:12px; flex-shrink:0;">
            <i class="fa-solid fa-check" style="color:#0066cc; font-size:16px;"></i>
        </div>
        <div style="flex-grow:1; min-width:0;">
            <div id="vphoneToastText" style="font-weight:700; font-size:13.5px; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Đã thêm vào giỏ!</div>
            <a href="index.php?page=cart" style="color:#0066cc; text-decoration:none; font-size:12.5px; font-weight:600;">Xem giỏ hàng ngay &rarr;</a>
        </div>
        <button type="button" style="background:none; border:none; color:#94a3b8; font-size:20px; line-height:1; cursor:pointer;" onclick="document.getElementById('vphoneLiveToast').style.display='none'">&times;</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById('formModalLogin');
    const registerForm = document.getElementById('formModalRegister');
    const authAlert = document.getElementById('authAlert');
    const authModalElement = document.getElementById('unifiedAuthModal');

    if (new URLSearchParams(window.location.search).get('show_login') === '1' && authModalElement && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(authModalElement).show();
    }

    [loginForm, registerForm].forEach(form => {
        if (!form) return;

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (authAlert) {
                authAlert.className = 'alert d-none small py-2 rounded-3 mb-3';
                authAlert.textContent = '';
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();

                if (!result.success) {
                    if (authAlert) {
                        authAlert.textContent = result.message || 'Không thể đăng nhập.';
                        authAlert.className = 'alert alert-danger small py-2 rounded-3 mb-3';
                    }
                    return;
                }

                if (result.is_admin) {
                    window.location.href = 'admin/index.php';
                } else if (result.redirect) {
                    window.location.href = result.redirect;
                } else {
                    window.location.reload();
                }
            } catch (error) {
                if (authAlert) {
                    authAlert.textContent = 'Có lỗi kết nối. Vui lòng thử lại.';
                    authAlert.className = 'alert alert-danger small py-2 rounded-3 mb-3';
                }
            }
        });
    });

    const searchInput = document.getElementById("searchInput");
    const searchDropdown = document.getElementById("searchDropdown");
    const searchResultsList = document.getElementById("searchResultsList");

    if (searchInput && searchDropdown && searchResultsList) {
        searchInput.addEventListener("input", function () {
            const query = this.value.trim().toLowerCase();
            if (query.length === 0) {
                searchDropdown.classList.add("d-none");
                searchResultsList.innerHTML = "";
                return;
            }

            searchDropdown.classList.remove("d-none");
            const allProducts = window.STORE_PRODUCTS || [];
            const isSS = (query === 'ss' || query === 'sam');
            const isIP = (query === 'ip');

            const matched = allProducts.filter(p => {
                const name = p.name.toLowerCase();
                if (isSS && name.includes('samsung')) return true;
                if (isIP && name.includes('iphone')) return true;
                return name.includes(query);
            }).slice(0, 6);

            if (matched.length === 0) {
                searchResultsList.innerHTML = `<div style="padding:16px; text-align:center; color:#64748b; font-size:13px;"><i class="fa-solid fa-circle-question me-1 text-primary"></i> Không tìm thấy máy phù hợp.</div>`;
                return;
            }

            let html = "";
            matched.forEach(item => {
                const formattedPrice = new Intl.NumberFormat('vi-VN').format(item.price) + ' đ';
                html += `
                    <a href="index.php?page=detail&id=${item.id}" class="search-item">
                        <img src="${item.image}" alt="${item.name}" class="search-thumb" onerror="this.src='assets/images/products/iphone-16.png';">
                        <div style="flex-grow:1; min-width:0;">
                            <div class="search-name">${item.name}</div>
                            <div class="search-price">${formattedPrice}</div>
                        </div>
                        <i class="fa-solid fa-chevron-right text-muted small ms-2"></i>
                    </a>`;
            });

            html += `<a href="index.php?keyword=${encodeURIComponent(this.value.trim())}" style="display:block; padding:10px; text-align:center; background:#f8fafc; color:#0066cc; font-size:12.5px; font-weight:700; text-decoration:none; border-top:1px solid #f1f5f9;">Xem tất cả kết quả &rarr;</a>`;
            searchResultsList.innerHTML = html;
        });

        document.addEventListener("click", function (e) {
            if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                searchDropdown.classList.add("d-none");
            }
        });
    }
});

// THÊM NHANH VÀO GIỎ HÀNG VỚI TOAST V-PHONE TRÊN MỌI TRANG
window.addToCartDirect = function(btn, productId, productName) {
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Đang thêm...';
    }

    fetch(`index.php?page=cart&action=add&ajax=1&id=${productId}`)
        .then(r => r.json())
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
            const toast = document.getElementById('vphoneLiveToast');
            const toastText = document.getElementById('vphoneToastText');
            if (data.success === false) {
                if (toast && toastText) {
                    toastText.innerText = data.message || 'Không thể thêm vào giỏ!';
                    toast.style.display = 'block';
                    clearTimeout(window.toastTimer);
                    window.toastTimer = setTimeout(() => { toast.style.display = 'none'; }, 3500);
                } else {
                    alert(data.message || 'Không thể thêm vào giỏ!');
                }
                return;
            }
            const badge = document.getElementById('cartBadge');
            if (badge) badge.innerText = data.cart_count;
            if (toast && toastText) {
                toastText.innerText = `Đã thêm "${productName}" vào giỏ!`;
                toast.style.display = 'block';
                clearTimeout(window.toastTimer);
                window.toastTimer = setTimeout(() => { toast.style.display = 'none'; }, 3500);
            }
        })
        .catch(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
            window.location.href = `index.php?page=cart&action=add&id=${productId}`;
        });
};
</script>
</body>
</html>
