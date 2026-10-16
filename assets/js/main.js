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
        let searchAbortCtrl = null;

        // Background preload if not already available
        if (!window.GLOBAL_SEARCH_PRODUCTS || window.GLOBAL_SEARCH_PRODUCTS.length === 0) {
            fetch('api/search.php?all=1')
                .then(r => r.json())
                .then(data => {
                    if (Array.isArray(data) && data.length > 0) {
                        window.GLOBAL_SEARCH_PRODUCTS = data;
                        if (!window.STORE_PRODUCTS || window.STORE_PRODUCTS.length === 0) {
                            window.STORE_PRODUCTS = data;
                        }
                    }
                })
                .catch(() => {});
        }

        function renderSearchResults(matched, rawVal) {
            if (!matched || matched.length === 0) {
                searchResultsList.innerHTML = `<div class="p-3 text-center text-muted small"><i class="fa-solid fa-circle-question me-1 text-primary"></i> Không tìm thấy máy phù hợp.</div>`;
                return;
            }

            let html = "";
            matched.slice(0, 6).forEach(item => {
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
        }

        function filterLocalProducts(query) {
            const allProducts = window.GLOBAL_SEARCH_PRODUCTS || window.STORE_PRODUCTS || [];
            if (!Array.isArray(allProducts) || allProducts.length === 0) return null;

            const isSS = (query === 'ss' || query === 'sam');
            const isIP = (query === 'ip');

            return allProducts.filter(p => {
                const name = (p.name || '').toLowerCase();
                const brand = (p.brand_name || '').toLowerCase();
                if (isSS && (name.includes('samsung') || brand.includes('samsung'))) return true;
                if (isIP && (name.includes('iphone') || brand.includes('apple'))) return true;
                return name.includes(query) || brand.includes(query);
            });
        }

        function triggerSearch(rawVal) {
            const query = rawVal.trim().toLowerCase();
            if (query.length === 0) {
                if (searchAbortCtrl) {
                    searchAbortCtrl.abort();
                    searchAbortCtrl = null;
                }
                searchDropdown.classList.add("d-none");
                searchResultsList.innerHTML = "";
                return;
            }

            searchDropdown.classList.remove("d-none");

            // 1. Try local filtering first (instant 0ms response)
            const localMatched = filterLocalProducts(query);
            if (localMatched && localMatched.length > 0) {
                renderSearchResults(localMatched, rawVal);
                return;
            }

            // 2. Query live API fallback
            if (searchAbortCtrl) {
                searchAbortCtrl.abort();
            }
            searchAbortCtrl = new AbortController();

            fetch(`api/search.php?keyword=${encodeURIComponent(rawVal.trim())}`, { signal: searchAbortCtrl.signal })
                .then(res => res.json())
                .then(data => {
                    renderSearchResults(data, rawVal);
                })
                .catch(err => {
                    if (err.name === 'AbortError') return;
                    if (localMatched !== null) {
                        renderSearchResults(localMatched, rawVal);
                    } else {
                        searchResultsList.innerHTML = `<div class="p-3 text-center text-muted small"><i class="fa-solid fa-circle-question me-1 text-primary"></i> Không tìm thấy máy phù hợp.</div>`;
                    }
                });
        }

        searchInput.addEventListener("input", function () {
            clearTimeout(searchDebounceTimer);
            const val = this.value;
            searchDebounceTimer = setTimeout(() => {
                triggerSearch(val);
            }, 120);
        });

        searchInput.addEventListener("focus", function () {
            if (this.value.trim().length > 0) {
                triggerSearch(this.value);
            }
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

    // --- 6. GOOGLE AUTHENTICATION ---
    function initGoogleAuth() {
        const authModalElement = document.getElementById('unifiedAuthModal');
        const googleModalElement = document.getElementById('googleChooserModal');
        const googleAuthAlert = document.getElementById('googleAuthAlert');
        const googleButtons = document.querySelectorAll('.btn-google-auth-trigger');
        const demoButtons = document.querySelectorAll('.btn-select-demo-google');
        const btnToggleCustom = document.getElementById('btnToggleCustomGoogle');
        const boxCustomForm = document.getElementById('boxCustomGoogleForm');
        const formCustomGoogle = document.getElementById('formCustomGoogle');

        const urlParams = new URLSearchParams(window.location.search);
        const authError = urlParams.get('auth_error');
        if (authError) {
            const authAlert = document.getElementById('authAlert');
            if (authAlert) {
                authAlert.textContent = decodeURIComponent(authError);
                authAlert.className = 'alert alert-danger small py-2 rounded-3 mb-3';
            }
            if (authModalElement && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(authModalElement).show();
            }
        }

        if (urlParams.get('google_mode') === 'demo' && googleModalElement && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(googleModalElement).show();
        }

        // Bật / tắt form nhập Gmail tùy ý
        if (btnToggleCustom && boxCustomForm) {
            btnToggleCustom.addEventListener('click', function () {
                boxCustomForm.classList.toggle('d-none');
                if (!boxCustomForm.classList.contains('d-none')) {
                    const nameInput = document.getElementById('customGoogleName');
                    if (nameInput) nameInput.focus();
                }
            });
        }

        // Xử lý khi nhấn nút Đăng nhập / Đăng ký Google
        googleButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const config = window.vphoneGoogleConfig || {};
                
                // Nếu đã cấu hình Client ID và không bật demo_mode: chuyển thẳng đến Google OAuth thật
                if (config.is_configured && !config.is_demo) {
                    window.location.href = 'index.php?page=google-login';
                    return;
                }

                // Chế độ mô phỏng / dùng thử nhanh: Mở bảng chọn tài khoản Google
                if (authModalElement && window.bootstrap) {
                    const authModalInstance = bootstrap.Modal.getInstance(authModalElement);
                    if (authModalInstance) {
                        authModalInstance.hide();
                    }
                }

                if (googleModalElement && window.bootstrap) {
                    const googleModalInstance = bootstrap.Modal.getOrCreateInstance(googleModalElement);
                    googleModalInstance.show();
                }
            });
        });

        // Hàm gửi dữ liệu xác thực Google lên máy chủ
        async function sendGoogleAuthRequest(formData, triggerBtn) {
            const origHtml = triggerBtn ? triggerBtn.innerHTML : '';
            if (triggerBtn) {
                triggerBtn.disabled = true;
                triggerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
            }

            if (googleAuthAlert) {
                googleAuthAlert.className = 'alert d-none small py-2 rounded-3 mb-3';
                googleAuthAlert.textContent = '';
            }

            try {
                const response = await fetch('index.php?page=auth&action=google', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();

                if (!result.success) {
                    if (triggerBtn) {
                        triggerBtn.disabled = false;
                        triggerBtn.innerHTML = origHtml;
                    }
                    if (googleAuthAlert) {
                        googleAuthAlert.textContent = result.message || 'Xác thực Google không thành công.';
                        googleAuthAlert.className = 'alert alert-danger small py-2 rounded-3 mb-3';
                    }
                    return;
                }

                if (googleAuthAlert) {
                    googleAuthAlert.textContent = result.message || 'Thành công!';
                    googleAuthAlert.className = 'alert alert-success small py-2 rounded-3 mb-3';
                }

                setTimeout(() => {
                    if (result.is_admin) {
                        window.location.href = 'admin/index.php';
                    } else if (result.redirect) {
                        window.location.href = result.redirect;
                    } else {
                        window.location.reload();
                    }
                }, 400);

            } catch (err) {
                if (triggerBtn) {
                    triggerBtn.disabled = false;
                    triggerBtn.innerHTML = origHtml;
                }
                if (googleAuthAlert) {
                    googleAuthAlert.textContent = 'Lỗi kết nối máy chủ khi xử lý tài khoản Google!';
                    googleAuthAlert.className = 'alert alert-danger small py-2 rounded-3 mb-3';
                }
            }
        }

        // Chọn tài khoản demo có sẵn
        demoButtons.forEach(dBtn => {
            dBtn.addEventListener('click', function () {
                const demoId = this.getAttribute('data-demo-id');
                const fd = new FormData();
                fd.append('demo_user', demoId);
                sendGoogleAuthRequest(fd, this);
            });
        });

        // Submit form Google tùy ý
        if (formCustomGoogle) {
            formCustomGoogle.addEventListener('submit', function (e) {
                e.preventDefault();
                const name = document.getElementById('customGoogleName')?.value.trim() || '';
                const email = document.getElementById('customGoogleEmail')?.value.trim() || '';
                const phone = document.getElementById('customGooglePhone')?.value.trim() || '';

                if (!email) return;

                const fd = new FormData();
                fd.append('demo_user', 'custom');
                fd.append('custom_name', name);
                fd.append('custom_email', email);
                fd.append('custom_phone', phone);

                const submitBtn = formCustomGoogle.querySelector('button[type="submit"]');
                sendGoogleAuthRequest(fd, submitBtn);
            });
        }

        // Khởi tạo Google Identity Services (GIS) nếu Client ID thực tế được cung cấp
        const config = window.vphoneGoogleConfig || {};
        if (window.google && window.google.accounts && window.google.accounts.id && config.is_configured && config.client_id) {
            try {
                window.google.accounts.id.initialize({
                    client_id: config.client_id,
                    callback: function (response) {
                        if (response && response.credential) {
                            const fd = new FormData();
                            fd.append('credential', response.credential);
                            sendGoogleAuthRequest(fd, null);
                        }
                    }
                });
            } catch (e) {}
        }
    }

    // --- DOM READY ---
    document.addEventListener("DOMContentLoaded", function () {
        initSearch();
        initBackToTop();
        initAuthModal();
        initGoogleAuth();
    });
})();
