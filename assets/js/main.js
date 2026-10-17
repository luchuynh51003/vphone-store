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
        const authAlert = document.getElementById('authAlert');

        const urlParams = new URLSearchParams(window.location.search);
        const authError = urlParams.get('auth_error');
        if (authError) {
            if (authAlert) {
                authAlert.textContent = decodeURIComponent(authError);
                authAlert.className = 'alert alert-danger small py-2 rounded-3 mb-3';
            }
            if (authModalElement && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(authModalElement).show();
            }
        }

        // Hàm gửi dữ liệu xác thực Google lên máy chủ (dành cho Google Identity Services / One Tap nếu có)
        async function sendGoogleAuthRequest(formData, triggerBtn) {
            const origHtml = triggerBtn ? triggerBtn.innerHTML : '';
            if (triggerBtn) {
                triggerBtn.disabled = true;
                triggerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
            }

            if (authAlert) {
                authAlert.className = 'alert d-none small py-2 rounded-3 mb-3';
                authAlert.textContent = '';
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
                    if (authAlert) {
                        authAlert.textContent = result.message || 'Xác thực Google không thành công.';
                        authAlert.className = 'alert alert-danger small py-2 rounded-3 mb-3';
                    }
                    return;
                }

                if (authAlert) {
                    authAlert.textContent = result.message || 'Thành công!';
                    authAlert.className = 'alert alert-success small py-2 rounded-3 mb-3';
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
                if (authAlert) {
                    authAlert.textContent = 'Lỗi kết nối máy chủ khi xử lý tài khoản Google!';
                    authAlert.className = 'alert alert-danger small py-2 rounded-3 mb-3';
                }
            }
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

    // --- 7. FLOATING CONTACT WIDGET (Cụm Nút Liên Hệ Nhanh Đa Kênh) ---
    function initFloatingContactWidget() {
        const widget = document.getElementById('vphoneContactWidget');
        const toggleBtn = document.getElementById('btnToggleContactWidget');
        const closeBtn = document.getElementById('btnCloseContactMenu');
        const backdrop = document.getElementById('vphoneContactBackdrop');
        const promptBubble = document.getElementById('vphoneWidgetPrompt');
        const dismissPromptBtn = document.getElementById('btnDismissPrompt');
        const btnOpenCallback = document.getElementById('btnOpenCallbackModal');
        const btnOpenInquiries = document.getElementById('btnOpenInquiriesModal');
        const btnOpenShowroom = document.getElementById('btnOpenShowroomModal');
        const formCallback = document.getElementById('formCallbackRequest');
        const callbackAlert = document.getElementById('callbackAlert');
        const callbackSuccessView = document.getElementById('callbackSuccessView');
        const callbackSuccessMsg = document.getElementById('callbackSuccessMsg');
        const btnSubmitCallback = document.getElementById('btnSubmitCallback');

        if (!widget || !toggleBtn) return;

        function openWidget() {
            widget.classList.add('is-open');
            toggleBtn.setAttribute('aria-expanded', 'true');
            if (backdrop) backdrop.classList.add('is-active');
            if (promptBubble) promptBubble.classList.add('d-none');
        }

        function closeWidget() {
            widget.classList.remove('is-open');
            toggleBtn.setAttribute('aria-expanded', 'false');
            if (backdrop) backdrop.classList.remove('is-active');
        }

        // Toggle Widget
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (widget.classList.contains('is-open')) {
                closeWidget();
            } else {
                openWidget();
            }
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                closeWidget();
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                closeWidget();
            });
        }

        // Keyboard escape to close
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && widget.classList.contains('is-open')) {
                closeWidget();
            }
        });

        // Prompt Bubble Handling
        if (promptBubble) {
            if (sessionStorage.getItem('vphone_contact_prompt_dismissed') === '1') {
                promptBubble.classList.add('d-none');
            } else {
                // Auto hide prompt after 12 seconds or on interaction
                setTimeout(() => {
                    if (promptBubble && !promptBubble.classList.contains('d-none')) {
                        promptBubble.style.opacity = '0';
                        setTimeout(() => promptBubble.classList.add('d-none'), 300);
                    }
                }, 12000);
            }

            promptBubble.addEventListener('click', function (e) {
                if (e.target.closest('#btnDismissPrompt')) return;
                openChatbot();
            });

            if (dismissPromptBtn) {
                dismissPromptBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    promptBubble.classList.add('d-none');
                    sessionStorage.setItem('vphone_contact_prompt_dismissed', '1');
                });
            }
        }

        // --- AUTOMATED CHATBOT CONTROLLER ---
        const chatbotWindow = document.getElementById('vphoneChatbotWindow');
        const btnOpenChatbot = document.getElementById('btnOpenChatbot');
        const btnCloseChatbot = document.getElementById('btnCloseChatbot');
        const btnResetChatbot = document.getElementById('btnResetChatbot');
        const formChatbot = document.getElementById('formChatbot');
        const chatbotInput = document.getElementById('chatbotInput');
        const chatbotMessages = document.getElementById('chatbotMessages');
        const chatbotTyping = document.getElementById('chatbotTyping');

        function openChatbot() {
            closeWidget();
            if (chatbotWindow) {
                chatbotWindow.classList.add('is-open');
                chatbotWindow.setAttribute('aria-hidden', 'false');
                scrollChatToBottom();
                if (chatbotInput) setTimeout(() => chatbotInput.focus(), 200);
                sessionStorage.setItem('vphone_chatbot_open', '1');
            }
        }

        function closeChatbot() {
            if (chatbotWindow) {
                chatbotWindow.classList.remove('is-open');
                chatbotWindow.setAttribute('aria-hidden', 'true');
                sessionStorage.setItem('vphone_chatbot_open', '0');
            }
        }

        function scrollChatToBottom() {
            if (chatbotMessages) {
                chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
            }
        }

        function getCurrentTime() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            return `${h}:${m}`;
        }

        const botAvatarSvg = `
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="24" height="24" rx="7" fill="#0066CC"/>
                <circle cx="8" cy="11" r="1.5" fill="#00FFC2"/>
                <circle cx="16" cy="11" r="1.5" fill="#00FFC2"/>
                <path d="M9 15c1.5 1 4.5 1 6 0" stroke="#00FFC2" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        `;

        function appendChatMessage(sender, htmlContent, suggestions = [], onStreamDone = null) {
            if (!chatbotMessages) return null;

            const msgDiv = document.createElement('div');
            msgDiv.className = sender === 'user' ? 'chat-msg chat-msg-user' : 'chat-msg chat-msg-bot';

            const timeStr = getCurrentTime();

            if (sender === 'user') {
                msgDiv.innerHTML = `
                    <div class="chat-msg-content">
                        <div class="chat-bubble">${escapeHtml(htmlContent)}</div>
                        <div class="chat-time">${timeStr}</div>
                    </div>
                `;
                insertMessageToDom(msgDiv);
                scrollChatToBottom();
                return msgDiv;
            } else {
                msgDiv.innerHTML = `
                    <div class="chat-msg-avatar">
                        ${botAvatarSvg}
                    </div>
                    <div class="chat-msg-content">
                        <div class="chat-bubble"></div>
                        <div class="chat-time">${timeStr}</div>
                    </div>
                `;
                insertMessageToDom(msgDiv);
                scrollChatToBottom();

                // Stream typewriter reveal effect
                const bubbleEl = msgDiv.querySelector('.chat-bubble');
                streamHtml(bubbleEl, htmlContent, () => {
                    // Append suggestion chips after typing finished
                    if (Array.isArray(suggestions) && suggestions.length > 0) {
                        const chipsDiv = document.createElement('div');
                        chipsDiv.className = 'chatbot-quick-chips';
                        suggestions.forEach((s, idx) => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'chip-item';
                            btn.setAttribute('data-query', s);
                            btn.textContent = s;
                            btn.style.animationDelay = (idx * 0.06) + 's';
                            chipsDiv.appendChild(btn);
                        });
                        insertMessageToDom(chipsDiv);
                    }
                    scrollChatToBottom();
                    if (typeof onStreamDone === 'function') onStreamDone();
                });

                return msgDiv;
            }
        }

        function insertMessageToDom(element) {
            if (chatbotTyping && chatbotMessages.contains(chatbotTyping)) {
                chatbotMessages.insertBefore(element, chatbotTyping);
            } else {
                chatbotMessages.appendChild(element);
            }
        }

        function streamHtml(targetElement, fullHtml, onComplete) {
            if (!targetElement) return;

            // Tokenize HTML so tags aren't broken while typing
            const tokens = fullHtml.match(/<[^>]+>|[^<>\s]+|\s+/g) || [fullHtml];
            let index = 0;
            targetElement.innerHTML = '';

            const interval = setInterval(() => {
                if (index < tokens.length) {
                    // Fast responsive step
                    const step = (tokens.length > 80) ? 3 : 2;
                    for (let i = 0; i < step && index < tokens.length; i++) {
                        targetElement.innerHTML += tokens[index];
                        index++;
                    }
                    scrollChatToBottom();
                } else {
                    clearInterval(interval);
                    targetElement.innerHTML = fullHtml; // Final exact markup
                    scrollChatToBottom();
                    if (typeof onComplete === 'function') onComplete();
                }
            }, 25);
        }

        let isBotBusy = false;

        async function handleSendToChatbot(userText) {
            const text = (userText || '').trim();
            if (!text || isBotBusy) return;

            isBotBusy = true;
            appendChatMessage('user', text);

            if (chatbotInput) chatbotInput.disabled = true;

            // Show typing indicator
            if (chatbotTyping) {
                chatbotTyping.classList.remove('d-none');
                scrollChatToBottom();
            }

            try {
                const res = await fetch('api/chatbot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text })
                });
                const data = await res.json();

                // Natural, realistic thinking delay (1.2s - 1.6s) so it feels authentic & thoughtful
                const naturalThinkingDelay = 1200 + Math.floor(Math.random() * 400);

                setTimeout(() => {
                    if (chatbotTyping) chatbotTyping.classList.add('d-none');

                    const replyContent = (data && data.success && data.reply) 
                        ? data.reply 
                        : 'Dạ, hiện em chưa thể xử lý yêu cầu này lúc này. Anh/chị có thể liên hệ tổng đài miễn phí <strong>1800 6868</strong> để được hỗ trợ tức thì ạ!';
                    
                    const suggestions = (data && data.suggestions) ? data.suggestions : [];

                    appendChatMessage('bot', replyContent, suggestions, () => {
                        isBotBusy = false;
                        if (chatbotInput) {
                            chatbotInput.disabled = false;
                            chatbotInput.focus();
                        }
                    });

                }, naturalThinkingDelay);

            } catch (e) {
                setTimeout(() => {
                    if (chatbotTyping) chatbotTyping.classList.add('d-none');
                    appendChatMessage('bot', 'Có lỗi kết nối máy chủ. Anh/chị vui lòng thử lại hoặc gọi hotline <strong>1800 6868</strong> nhé!', [], () => {
                        isBotBusy = false;
                        if (chatbotInput) {
                            chatbotInput.disabled = false;
                            chatbotInput.focus();
                        }
                    });
                }, 1000);
            }
        }

        if (btnOpenChatbot) {
            btnOpenChatbot.addEventListener('click', function () {
                openChatbot();
            });
        }

        if (btnCloseChatbot) {
            btnCloseChatbot.addEventListener('click', function () {
                closeChatbot();
            });
        }

        if (btnResetChatbot) {
            btnResetChatbot.addEventListener('click', function () {
                if (!chatbotMessages || isBotBusy) return;
                chatbotMessages.innerHTML = `
                    <div class="chat-msg chat-msg-bot">
                        <div class="chat-msg-avatar">
                            ${botAvatarSvg}
                        </div>
                        <div class="chat-msg-content">
                            <div class="chat-bubble">
                                Cuộc trò chuyện đã được làm mới! Em là <strong>Trợ lý ảo AI của V-Phone Store</strong>. ✨<br><br>
                                Em có thể tư vấn các dòng Flagship 2026, so sánh giá bán, hỗ trợ trả góp 0%, đồng kiểm và bảo hành 1 đổi 1. Anh/chị cần hỗ trợ gì ạ?
                            </div>
                            <div class="chat-time">${getCurrentTime()}</div>
                        </div>
                    </div>
                    <div class="chatbot-quick-chips">
                        <button type="button" class="chip-item" data-query="Tư vấn các dòng iPhone mới nhất">📱 iPhone mới nhất</button>
                        <button type="button" class="chip-item" data-query="Tư vấn điện thoại Samsung Galaxy">⚡ Samsung Galaxy</button>
                        <button type="button" class="chip-item" data-query="Điện thoại dưới 10 triệu đáng mua nhất">💰 Máy dưới 10 triệu</button>
                        <button type="button" class="chip-item" data-query="Chính sách mua trả góp 0%">💳 Trả góp 0%</button>
                        <button type="button" class="chip-item" data-query="Chính sách đồng kiểm mở hộp trước khi trả tiền">📦 Kiểm tra hàng trước</button>
                        <button type="button" class="chip-item" data-query="Chính sách bảo hành 1 đổi 1">🛡️ Bảo hành 1-1</button>
                        <button type="button" class="chip-item" data-query="Hotline hỗ trợ và gặp tư vấn viên">📞 Hotline CSKH</button>
                    </div>
                    <div id="chatbotTyping" class="chat-msg chat-msg-bot d-none">
                        <div class="chat-msg-avatar">
                            ${botAvatarSvg}
                        </div>
                        <div class="chat-msg-content">
                            <div class="chat-bubble typing-bubble">
                                <span class="typing-status-text">V-Phone AI đang phân tích & soạn câu trả lời</span>
                                <span class="typing-dot"></span>
                                <span class="typing-dot"></span>
                                <span class="typing-dot"></span>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        if (formChatbot) {
            formChatbot.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!chatbotInput || isBotBusy) return;
                const txt = chatbotInput.value.trim();
                if (!txt) return;
                chatbotInput.value = '';
                handleSendToChatbot(txt);
            });
        }

        // Delegate click for suggestion chips
        if (chatbotMessages) {
            chatbotMessages.addEventListener('click', function (e) {
                if (isBotBusy) return;
                const chip = e.target.closest('.chip-item');
                if (chip) {
                    const query = chip.getAttribute('data-query') || chip.textContent.trim();
                    if (query) {
                        handleSendToChatbot(query);
                    }
                }
            });
        }

        // Open Support / Callback Modal from Widget Menu (Default Tab: Gửi Yêu Cầu)
        if (btnOpenCallback) {
            btnOpenCallback.addEventListener('click', function () {
                closeWidget();
                closeChatbot();
                const modalEl = document.getElementById('modalCallbackRequest');
                if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                    const bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                    const sendTab = document.getElementById('tab-send-inquiry-btn');
                    if (sendTab && window.bootstrap && window.bootstrap.Tab) {
                        window.bootstrap.Tab.getOrCreateInstance(sendTab).show();
                    }
                }
            });
        }

        // Open Support Modal directly on Tab 2: Hộp Thư Phản Hồi CSKH
        if (btnOpenInquiries) {
            btnOpenInquiries.addEventListener('click', function () {
                closeWidget();
                closeChatbot();
                const modalEl = document.getElementById('modalCallbackRequest');
                if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                    const bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                    if (window.switchToInquiriesTab) {
                        window.switchToInquiriesTab();
                    }
                }
            });
        }

        // Open Showroom Modal from Widget Menu
        if (btnOpenShowroom) {
            btnOpenShowroom.addEventListener('click', function () {
                closeWidget();
                closeChatbot();
                const modalEl = document.getElementById('modalShowrooms');
                if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                    const bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                }
            });
        }

        // --- GLOBAL HELPER: Chuyển sang Tab Hộp Thư Phản Hồi ---
        window.switchToInquiriesTab = function () {
            const repliesTabBtn = document.getElementById('tab-view-replies-btn');
            if (repliesTabBtn && window.bootstrap && window.bootstrap.Tab) {
                const bsTab = window.bootstrap.Tab.getOrCreateInstance(repliesTabBtn);
                bsTab.show();
            }
            if (window.fetchCustomerInquiries) {
                window.fetchCustomerInquiries();
            }
        };

        // --- GLOBAL HELPER: Tra cứu lịch sử & câu trả lời từ CSKH ---
        window.fetchCustomerInquiries = async function (explicitPhone) {
            const feed = document.getElementById('customerInquiriesFeed');
            const phoneInput = document.getElementById('inquirySearchPhone');
            const phone = explicitPhone || (phoneInput ? phoneInput.value.trim() : '');
            const badge = document.getElementById('customerInquiriesBadge');

            if (feed) {
                feed.innerHTML = `
                    <div class="text-center py-4 text-muted small">
                        <span class="spinner-border spinner-border-sm text-primary mb-2 d-inline-block" role="status"></span>
                        <div>Đang tải câu trả lời từ đội ngũ CSKH V-Phone...</div>
                    </div>
                `;
            }

            try {
                const res = await fetch(`api/callback-request.php?action=my_inquiries&phone=${encodeURIComponent(phone)}`);
                const data = await res.json();

                if (!feed) return;

                if (!data || !data.success || !Array.isArray(data.inquiries) || data.inquiries.length === 0) {
                    feed.innerHTML = `
                        <div class="text-center py-4 text-muted small">
                            <i class="fa-regular fa-comment-dots fs-3 text-secondary mb-2 d-block opacity-50"></i>
                            <div class="fw-semibold text-dark mb-1">Chưa có yêu cầu nào cho số điện thoại này</div>
                            <p class="text-muted small mb-3">Bạn hãy gửi yêu cầu ở Tab <strong>"Gửi Yêu Cầu Hỗ Trợ"</strong>, chuyên viên CSKH sẽ tiếp nhận và gửi câu trả lời ngay tại đây!</p>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="document.getElementById('tab-send-inquiry-btn').click()">
                                <i class="fa-solid fa-paper-plane me-1"></i>Gửi Yêu Cầu Mới
                            </button>
                        </div>
                    `;
                    if (badge) badge.classList.add('d-none');
                    return;
                }

                const inquiries = data.inquiries;
                if (badge) {
                    badge.textContent = inquiries.length;
                    badge.classList.remove('d-none');
                }

                let html = '';
                inquiries.forEach(item => {
                    const isReplied = (item.status === 'replied' && item.reply_message);
                    const isProcessing = (item.status === 'processing');

                    let statusBadge = '';
                    if (isReplied) {
                        statusBadge = '<span class="badge bg-success bg-opacity-10 text-success rounded-pill fw-bold" style="font-size: 0.7rem;"><i class="fa-solid fa-check-double me-1"></i>Đã phản hồi</span>';
                    } else if (isProcessing) {
                        statusBadge = '<span class="badge bg-warning bg-opacity-15 text-warning rounded-pill fw-bold text-dark" style="font-size: 0.7rem;"><i class="fa-solid fa-spinner fa-spin me-1"></i>Đang xử lý</span>';
                    } else {
                        statusBadge = '<span class="badge bg-info bg-opacity-10 text-info rounded-pill fw-bold" style="font-size: 0.7rem;"><i class="fa-solid fa-clock-rotate-left me-1"></i>Chờ tiếp nhận</span>';
                    }

                    let deptColor = 'text-primary';
                    if (item.department === 'warranty') deptColor = 'text-success';
                    if (item.department === 'installment') deptColor = 'text-warning';
                    if (item.department === 'cskh') deptColor = 'text-info';

                    html += `
                        <div class="card border rounded-3 mb-3 shadow-xs overflow-hidden" style="transition: border-color 0.2s;">
                            <div class="card-header bg-light border-bottom p-2.5 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-dark rounded-pill fw-bold" style="font-size: 0.68rem;">#YC-${item.id}</span>
                                    <span class="small fw-bold ${deptColor}"><i class="fa-solid fa-sitemap me-1"></i>${escapeHtml(item.dept_title || 'CSKH')}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    ${statusBadge}
                                    <small class="text-muted" style="font-size: 0.7rem;"><i class="fa-regular fa-clock me-1"></i>${escapeHtml(item.created_at_formatted || '')}</small>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <div class="fw-bold text-dark small mb-1"><i class="fa-solid fa-circle-question text-primary me-1.5"></i>${escapeHtml(item.topic || 'Yêu cầu tư vấn')}</div>
                                ${item.note ? `<div class="p-2.5 rounded-3 bg-light border text-secondary small mb-2" style="font-size: 0.8rem; line-height: 1.45;">${escapeHtml(item.note)}</div>` : ''}

                                ${isReplied ? `
                                    <div class="mt-2.5 p-3 rounded-3" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-1 border-bottom" style="border-color: #dcfce7 !important;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 11px;">
                                                    <i class="fa-solid fa-headset"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-success small lh-1">${escapeHtml(item.replied_by_name || 'Bộ phận CSKH V-Phone')}</div>
                                                    <small class="text-muted" style="font-size: 0.68rem;">${escapeHtml(item.replied_by_title || 'Chuyên viên Tư vấn & CSKH')}</small>
                                                </div>
                                            </div>
                                            <small class="text-muted" style="font-size: 0.7rem;"><i class="fa-solid fa-check text-success me-1"></i>${escapeHtml(item.replied_at_formatted || '')}</small>
                                        </div>
                                        <div class="text-dark small lh-base" style="white-space: pre-line; font-size: 0.85rem;">${escapeHtml(item.reply_message)}</div>
                                    </div>
                                ` : `
                                    <div class="mt-2 p-2.5 rounded-3 bg-light border text-muted small d-flex align-items-center gap-2" style="font-size: 0.78rem;">
                                        <i class="fa-solid fa-hourglass-half text-warning flex-shrink-0"></i>
                                        <span>Chuyên viên thuộc <strong>${escapeHtml(item.dept_title || 'Bộ phận CSKH')}</strong> đã tiếp nhận và đang soạn phản hồi cho bạn...</span>
                                    </div>
                                `}
                            </div>
                        </div>
                    `;
                });

                feed.innerHTML = html;

            } catch (err) {
                if (feed) {
                    feed.innerHTML = `
                        <div class="text-center py-4 text-danger small">
                            <i class="fa-solid fa-circle-exclamation fs-3 mb-2 d-block"></i>
                            Không thể tải câu trả lời lúc này. Vui lòng kiểm tra lại kết nối và thử lại!
                        </div>
                    `;
                }
            }
        };

        // Handle Quick Callback & CSKH AJAX Form Submission
        if (formCallback) {
            formCallback.addEventListener('submit', async function (e) {
                e.preventDefault();

                const nameInput = document.getElementById('cbFullName');
                const phoneInput = document.getElementById('cbPhone');
                const fullname = nameInput ? nameInput.value.trim() : '';
                const phone = phoneInput ? phoneInput.value.trim() : '';

                if (callbackAlert) {
                    callbackAlert.className = 'alert d-none';
                    callbackAlert.textContent = '';
                }

                if (!fullname) {
                    if (callbackAlert) {
                        callbackAlert.className = 'alert alert-danger small py-2 px-3 rounded-3 mb-3';
                        callbackAlert.textContent = 'Vui lòng nhập họ và tên của bạn.';
                    }
                    if (nameInput) nameInput.focus();
                    return;
                }

                const cleanPhone = phone.replace(/[^0-9+]/g, '');
                // Chấp nhận số điện thoại từ 8 đến 11 chữ số
                if (cleanPhone.length < 8 || cleanPhone.length > 12) {
                    if (callbackAlert) {
                        callbackAlert.className = 'alert alert-danger small py-2 px-3 rounded-3 mb-3';
                        callbackAlert.textContent = 'Vui lòng nhập số điện thoại hợp lệ (8 - 11 chữ số).';
                    }
                    if (phoneInput) phoneInput.focus();
                    return;
                }

                const origBtnHtml = btnSubmitCallback ? btnSubmitCallback.innerHTML : '';
                if (btnSubmitCallback) {
                    btnSubmitCallback.disabled = true;
                    btnSubmitCallback.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Đang chuyển yêu cầu đến CSKH...';
                }

                try {
                    const formData = new FormData(formCallback);
                    const res = await fetch('api/callback-request.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (btnSubmitCallback) {
                        btnSubmitCallback.disabled = false;
                        btnSubmitCallback.innerHTML = origBtnHtml;
                    }

                    if (data.success) {
                        formCallback.classList.add('d-none');
                        if (callbackSuccessView) {
                            callbackSuccessView.classList.remove('d-none');
                        }
                        if (callbackSuccessMsg && data.message) {
                            callbackSuccessMsg.textContent = data.message;
                        }
                        if (window.showVPhoneToast) {
                            window.showVPhoneToast('Đã gửi yêu cầu đến CSKH! Chuyên viên sẽ phản hồi trong 5 phút.', true);
                        }

                        // Đồng bộ số điện thoại tra cứu & nạp trước danh sách phản hồi
                        const inquiryPhoneInput = document.getElementById('inquirySearchPhone');
                        if (inquiryPhoneInput) inquiryPhoneInput.value = cleanPhone;
                        if (window.fetchCustomerInquiries) {
                            window.fetchCustomerInquiries(cleanPhone);
                        }
                    } else {
                        if (callbackAlert) {
                            callbackAlert.className = 'alert alert-danger small py-2 px-3 rounded-3 mb-3';
                            callbackAlert.textContent = data.message || 'Có lỗi xảy ra, vui lòng thử lại!';
                        }
                    }
                } catch (err) {
                    if (btnSubmitCallback) {
                        btnSubmitCallback.disabled = false;
                        btnSubmitCallback.innerHTML = origBtnHtml;
                    }
                    if (callbackAlert) {
                        callbackAlert.className = 'alert alert-danger small py-2 px-3 rounded-3 mb-3';
                        callbackAlert.textContent = 'Lỗi kết nối máy chủ khi gửi yêu cầu. Vui lòng thử lại sau.';
                    }
                }
            });

            // Modal Lifecycle Event Handlers
            const callbackModalEl = document.getElementById('modalCallbackRequest');
            if (callbackModalEl) {
                // Khi mở modal, nếu đã có SĐT thì nạp trước danh sách phản hồi ngầm
                callbackModalEl.addEventListener('shown.bs.modal', function () {
                    const inquiryPhoneInput = document.getElementById('inquirySearchPhone');
                    if (inquiryPhoneInput && inquiryPhoneInput.value.trim().length >= 8) {
                        if (window.fetchCustomerInquiries) {
                            window.fetchCustomerInquiries(inquiryPhoneInput.value.trim());
                        }
                    }
                });

                // Reset modal state when closed
                callbackModalEl.addEventListener('hidden.bs.modal', function () {
                    formCallback.classList.remove('d-none');
                    if (callbackSuccessView) callbackSuccessView.classList.add('d-none');
                    if (callbackAlert) {
                        callbackAlert.className = 'alert d-none';
                        callbackAlert.textContent = '';
                    }
                });
            }
        }
    }

    // --- DOM READY ---
    document.addEventListener("DOMContentLoaded", function () {
        initSearch();
        initBackToTop();
        initAuthModal();
        initGoogleAuth();
        initFloatingContactWidget();
    });
})();
