/**
 * V-Phone Store 2026 - Main Engine
 * Optimized for lightweight performance, instant responsiveness, and reliable notifications
 */

(function () {
    "use strict";

    // --- 1. TOAST NOTIFICATION SYSTEM (Instant & Reliable) ---
    window.toastTimer = null;

    window.showVPhoneToast = function (message, isSuccess = true) {
        const toast = document.getElementById('vphoneLiveToast');
        if (!toast) return;

        const toastText = document.getElementById('vphoneToastText');
        if (toastText) {
            toastText.textContent = message;
        }

        const icon = toast.querySelector('.toast-icon-wrap i') || toast.querySelector('i');
        if (icon) {
            icon.className = isSuccess ? 'fa-solid fa-check text-primary' : 'fa-solid fa-circle-exclamation text-danger';
        }

        // Show toast immediately
        toast.style.display = 'block';

        clearTimeout(window.toastTimer);
        window.toastTimer = setTimeout(() => {
            window.hideVPhoneToast();
        }, 3500);
    };

    window.hideVPhoneToast = function () {
        const toast = document.getElementById('vphoneLiveToast');
        if (!toast) return;
        toast.style.display = 'none';
        clearTimeout(window.toastTimer);
    };

    // --- 2. ADD TO CART DIRECT ---
    window.addToCartDirect = function (btn, productId, productName) {
        const origHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Thêm...';
        }

        fetch(`index.php?page=cart&action=add&ajax=1&id=${productId}`)
            .then(r => r.json())
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }

                if (data.success === false) {
                    window.showVPhoneToast(data.message || 'Không thể thêm vào giỏ!', false);
                    return;
                }

                const badge = document.getElementById('cartBadge');
                if (badge) {
                    badge.textContent = data.cart_count;
                }

                const nameStr = productName ? `"${productName}"` : 'sản phẩm';
                window.showVPhoneToast(`Đã thêm ${nameStr} vào giỏ!`, true);
            })
            .catch(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
                window.location.href = `index.php?page=cart&action=add&id=${productId}`;
            });
    };

    // --- 3. DEBOUNCED SEARCH AUTOCOMPLETE ---
    function initSearch() {
        const searchInput = document.getElementById("searchInput");
        const searchDropdown = document.getElementById("searchDropdown");
        const searchResultsList = document.getElementById("searchResultsList");

        if (!searchInput || !searchDropdown || !searchResultsList) return;

        let searchDebounceTimer = null;

        searchInput.addEventListener("input", function () {
            clearTimeout(searchDebounceTimer);
            const rawVal = this.value;

            searchDebounceTimer = setTimeout(() => {
                const query = rawVal.trim().toLowerCase();
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
                    const name = (p.name || '').toLowerCase();
                    if (isSS && name.includes('samsung')) return true;
                    if (isIP && name.includes('iphone')) return true;
                    return name.includes(query);
                }).slice(0, 6);

                if (matched.length === 0) {
                    searchResultsList.innerHTML = `<div class="p-3 text-center text-muted small"><i class="fa-solid fa-circle-question me-1 text-primary"></i> Không tìm thấy máy phù hợp.</div>`;
                    return;
                }

                let html = "";
                matched.forEach(item => {
                    const formattedPrice = item.price_formatted || (new Intl.NumberFormat('vi-VN').format(item.price) + ' đ');
                    html += `
                        <a href="index.php?page=detail&id=${item.id}" class="search-item">
                            <img src="${item.image}" alt="${escapeHtml(item.name)}" class="search-thumb" onerror="this.src='assets/images/products/iphone-16.png'">
                            <div style="flex-grow:1; min-width:0;">
                                <div class="search-name">${escapeHtml(item.name)}</div>
                                <div class="search-price">${formattedPrice}</div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted small ms-2"></i>
                        </a>`;
                });

                html += `<a href="index.php?keyword=${encodeURIComponent(rawVal.trim())}" style="display:block; padding:10px; text-align:center; background:#f8fafc; color:#0066cc; font-size:12.5px; font-weight:700; text-decoration:none; border-top:1px solid #f1f5f9;">Xem tất cả kết quả &rarr;</a>`;
                searchResultsList.innerHTML = html;
            }, 160);
        });

        document.addEventListener("click", function (e) {
            if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                searchDropdown.classList.add("d-none");
            }
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") {
                searchDropdown.classList.add("d-none");
            }
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    // --- 4. BACK TO TOP (Lightweight) ---
    function initBackToTop() {
        let btn = document.getElementById('btnBackToTop');
        if (!btn && document.body) {
            btn = document.createElement('button');
            btn.id = 'btnBackToTop';
            btn.className = 'btn-back-to-top';
            btn.type = 'button';
            btn.title = 'Lên đầu trang';
            btn.innerHTML = '<i class="fa-solid fa-arrow-up"></i>';
            document.body.appendChild(btn);
        }

        if (!btn) return;

        window.addEventListener('scroll', function () {
            if (window.scrollY > 300) {
                btn.classList.add('show');
            } else {
                btn.classList.remove('show');
            }
        }, { passive: true });

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // --- 5. UNIFIED AUTH MODAL ---
    function initAuthModal() {
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
                const submitBtn = form.querySelector('button[type="submit"]');
                const origHtml = submitBtn ? submitBtn.innerHTML : '';

                if (authAlert) {
                    authAlert.className = 'alert d-none small py-2 rounded-3 mb-3';
                    authAlert.textContent = '';
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();

                    if (!result.success) {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = origHtml;
                        }
                        if (authAlert) {
                            authAlert.textContent = result.message || 'Thao tác không thành công.';
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
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origHtml;
                    }
                    if (authAlert) {
                        authAlert.textContent = 'Có lỗi kết nối. Vui lòng thử lại!';
                        authAlert.className = 'alert alert-danger small py-2 rounded-3 mb-3';
                    }
                }
            });
        });
    }

    // --- DOM READY ---
    document.addEventListener("DOMContentLoaded", function () {
        initSearch();
        initBackToTop();
        initAuthModal();
    });
})();
