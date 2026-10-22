/**
 * V-Phone Store 2026 - Dedicated Role AI Controller
 * Quản lý Trợ lý AI chuyên biệt cho từng chức vụ
 * Thiết kế gọn gàng, chỉ mở khi người dùng bấm, không rườm rà
 */

(function () {
    'use strict';

    // Cấu hình định danh cho từng chức vụ
    const ROLE_CONFIGS = {
        admin: {
            name: 'V-Phone Executive AI',
            badge: 'Quản Trị Viên',
            icon: 'fa-solid fa-crown',
            gradient: 'linear-gradient(135deg, #ef4444, #8b5cf6)',
            placeholder: 'Hỏi Quản Trị: Doanh thu, đơn chờ duyệt, tồn kho...',
            welcome: 'Dạ xin kính chào <strong>Quản Trị Viên</strong>! Tôi là <strong>Executive AI</strong> cố vấn điều hành số cho ban lãnh đạo V-Phone Store.<br><br>Tôi sẵn sàng hỗ trợ bạn tức thì: <em>Doanh thu hôm nay, tổng kết đơn hàng, phân tích tồn kho và đề xuất chiến lược tăng trưởng</em>.',
            suggestions: [
                'Báo cáo doanh thu hôm nay',
                'Tình trạng đơn hàng chờ duyệt',
                'Cảnh báo tồn kho & máy sắp hết',
                'Top sản phẩm bán chạy nhất',
                'Đề xuất chiến lược tăng trưởng 2026'
            ]
        },
        sales: {
            name: 'V-Phone Sales AI',
            badge: 'Nhân Viên Bán Hàng',
            icon: 'fa-solid fa-briefcase',
            gradient: 'linear-gradient(135deg, #2563eb, #06b6d4)',
            placeholder: 'Hỏi Sales: Tra cứu đơn #VP-12, mã voucher, kịch bản...',
            welcome: 'Chào bạn! Tôi là <strong>V-Phone Sales AI</strong> trợ thủ chốt đơn và tra cứu đơn hàng của bạn.<br><br>Tôi có thể giúp bạn: <em>Tra cứu nhanh đơn hàng qua mã/SĐT, kiểm tra voucher còn hạn, kịch bản up-sell sạc 45W và mẫu tin nhắn gọi xác nhận đơn</em>.',
            suggestions: [
                'Tra cứu đơn hàng mới nhất',
                'Danh sách voucher đang hoạt động',
                'Gợi ý kịch bản up-sell sạc & tai nghe',
                'Mẫu tin nhắn gọi xác nhận đơn',
                'Xử lý khách muốn hủy đơn'
            ]
        },
        warehouse: {
            name: 'V-Phone Warehouse AI',
            badge: 'Quản Lý Kho',
            icon: 'fa-solid fa-boxes-stacked',
            gradient: 'linear-gradient(135deg, #f59e0b, #d97706)',
            placeholder: 'Hỏi Kho: Máy sắp hết (tồn ≤ 5), tổng giá trị kho, IMEI...',
            welcome: 'Chào thủ kho! Tôi là <strong>V-Phone Warehouse AI</strong> chuyên gia kiểm kê và kiểm soát hàng tồn kho.<br><br>Tôi sẵn sàng hỗ trợ: <em>Liệt kê máy sắp hết hàng (tồn ≤ 5), thống kê tổng giá trị kho, quy trình quét IMEI và tiêu chuẩn đóng gói chống sốc</em>.',
            suggestions: [
                'Sản phẩm sắp hết hàng (tồn ≤ 5)',
                'Thống kê tổng giá trị tồn kho',
                'Tồn kho theo từng hãng máy',
                'Quy trình kiểm kê quét IMEI chuẩn',
                'Tiêu chuẩn đóng gói gửi điện thoại'
            ]
        },
        warranty: {
            name: 'V-Phone Tech & Care AI',
            badge: 'Kỹ Thuật Viên',
            icon: 'fa-solid fa-shield-halved',
            gradient: 'linear-gradient(135deg, #10b981, #059669)',
            placeholder: 'Hỏi Kỹ Thuật: Tra cứu IMEI, chẩn đoán lỗi màn/pin, bảng giá...',
            welcome: 'Chào kỹ thuật viên! Tôi là <strong>V-Phone Tech & Care AI</strong> trợ lý chẩn đoán phần cứng và quy trình bảo hành.<br><br>Tôi sẵn sàng hỗ trợ: <em>Tra cứu phiếu bảo hành theo IMEI, phân tích mã lỗi màn hình/pin/FaceID, chính sách 1 đổi 1 Apple/Samsung và bảng giá linh kiện</em>.',
            suggestions: [
                'Tra cứu phiếu bảo hành theo IMEI',
                'Chẩn đoán lỗi sọc màn hình & phôi OLED',
                'Chẩn đoán lỗi chai phồng pin & IC sạc',
                'Chính sách bảo hành Apple VN/A',
                'Bảng giá tham khảo thay linh kiện'
            ]
        },
        support: {
            name: 'V-Phone Support AI',
            badge: 'Chuyên Viên CSKH',
            icon: 'fa-solid fa-headset',
            gradient: 'linear-gradient(135deg, #ec4899, #8b5cf6)',
            placeholder: 'Hỏi CSKH: Yêu cầu gọi lại, trả lời review 1 sao, khiếu nại...',
            welcome: 'Chào bạn! Tôi là <strong>V-Phone Support AI</strong> chuyên viên hỗ trợ kịch bản giao tiếp và phản hồi khách hàng.<br><br>Tôi sẵn sàng hỗ trợ: <em>Kiểm tra yêu cầu gọi lại tư vấn mới, soạn mẫu trả lời đánh giá 1 sao & 5 sao, kịch bản xoa dịu khách giận dữ (L.A.S.T)</em>.',
            suggestions: [
                'Khách hàng yêu cầu gọi lại mới nhất',
                'Đánh giá sản phẩm chưa phản hồi',
                'Mẫu trả lời đánh giá 1 sao chuyên nghiệp',
                'Mẫu cảm ơn đánh giá 5 sao',
                'Kịch bản xoa dịu khách khiếu nại'
            ]
        }
    };

    let activeRole = 'admin';
    let chatHistory = [];
    let isBotTyping = false;

    // Tìm URL API tự động
    function getApiEndpoint() {
        return window.location.pathname.includes('/admin/') ? '../api/role_ai.php' : 'api/role_ai.php';
    }

    function init() {
        const windowEl = document.getElementById('roleAiWindow');
        const trigger = document.getElementById('roleAiFloatingTrigger');
        const headerTrigger = document.getElementById('btnAdminOpenAiHeader');
        const btnClose = document.getElementById('roleAiBtnClose');
        const btnReset = document.getElementById('roleAiBtnReset');
        const form = document.getElementById('roleAiInputForm');
        const input = document.getElementById('roleAiInput');

        if (!windowEl) return;

        // Xác định duy nhất 1 chức vụ được cấp cho người dùng này
        const assigned = windowEl.getAttribute('data-role') || 'admin';
        const isAdmin = windowEl.getAttribute('data-is-admin') === '1';
        activeRole = (!isAdmin) ? (ROLE_CONFIGS[assigned] ? assigned : 'sales') : (ROLE_CONFIGS[assigned] ? assigned : 'admin');

        // Render giao diện khởi đầu của chức vụ
        renderChatView();

        // Mở / Đóng khi bấm nút (Chỉ mở khi người dùng bấm)
        function toggleChat() {
            const isOpen = windowEl.classList.contains('is-open');
            setChatState(!isOpen);
        }

        if (trigger) {
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleChat();
            });
        }

        if (headerTrigger) {
            headerTrigger.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                toggleChat();
            });
        }

        if (btnClose) {
            btnClose.addEventListener('click', function (e) {
                e.stopPropagation();
                setChatState(false);
            });
        }

        // Nút thu nhỏ / mở rộng (Minimize)
        const btnMinimize = document.getElementById('roleAiBtnMinimize');
        if (btnMinimize) {
            btnMinimize.addEventListener('click', function (e) {
                e.stopPropagation();
                const isMin = windowEl.classList.toggle('is-minimized');
                const icon = btnMinimize.querySelector('i');
                if (icon) {
                    icon.className = isMin ? 'fa-solid fa-chevron-up' : 'fa-solid fa-minus';
                }
                btnMinimize.title = isMin ? 'Mở rộng khung chat' : 'Thu nhỏ khung chat';
            });
        }

        // TÍNH NĂNG KÉO THẢ DI CHUYỂN KHUNG CHAT (DRAGGABLE HEADER)
        const headerEl = document.getElementById('roleAiHeader') || windowEl.querySelector('.role-ai-header');
        let isDragging = false;
        let dragStartX = 0, dragStartY = 0;
        let initialLeft = 0, initialTop = 0;

        if (headerEl) {
            headerEl.addEventListener('mousedown', function (e) {
                if (e.target.closest('.role-ai-header-actions')) return;

                isDragging = true;
                dragStartX = e.clientX;
                dragStartY = e.clientY;

                const rect = windowEl.getBoundingClientRect();
                initialLeft = rect.left;
                initialTop = rect.top;

                windowEl.style.bottom = 'auto';
                windowEl.style.right = 'auto';
                windowEl.style.left = initialLeft + 'px';
                windowEl.style.top = initialTop + 'px';
                windowEl.style.transform = 'none';

                document.body.style.userSelect = 'none';
                headerEl.style.cursor = 'grabbing';
            });

            document.addEventListener('mousemove', function (e) {
                if (!isDragging) return;
                const dx = e.clientX - dragStartX;
                const dy = e.clientY - dragStartY;

                let newLeft = initialLeft + dx;
                let newTop = initialTop + dy;

                const winW = windowEl.offsetWidth;
                const winH = windowEl.offsetHeight;
                newLeft = Math.max(10, Math.min(window.innerWidth - winW - 10, newLeft));
                newTop = Math.max(10, Math.min(window.innerHeight - winH - 10, newTop));

                windowEl.style.left = newLeft + 'px';
                windowEl.style.top = newTop + 'px';
            });

            document.addEventListener('mouseup', function () {
                if (isDragging) {
                    isDragging = false;
                    document.body.style.userSelect = '';
                    if (headerEl) headerEl.style.cursor = 'grab';
                }
            });

            // Hỗ trợ kéo cảm ứng trên Mobile/Tablet
            headerEl.addEventListener('touchstart', function (e) {
                if (e.target.closest('.role-ai-header-actions')) return;
                const t = e.touches[0];
                isDragging = true;
                dragStartX = t.clientX;
                dragStartY = t.clientY;
                const rect = windowEl.getBoundingClientRect();
                initialLeft = rect.left;
                initialTop = rect.top;
                windowEl.style.bottom = 'auto';
                windowEl.style.right = 'auto';
                windowEl.style.left = initialLeft + 'px';
                windowEl.style.top = initialTop + 'px';
                windowEl.style.transform = 'none';
            }, { passive: true });

            document.addEventListener('touchmove', function (e) {
                if (!isDragging || !e.touches[0]) return;
                const t = e.touches[0];
                const dx = t.clientX - dragStartX;
                const dy = t.clientY - dragStartY;
                let newLeft = initialLeft + dx;
                let newTop = initialTop + dy;
                const winW = windowEl.offsetWidth;
                const winH = windowEl.offsetHeight;
                newLeft = Math.max(10, Math.min(window.innerWidth - winW - 10, newLeft));
                newTop = Math.max(10, Math.min(window.innerHeight - winH - 10, newTop));
                windowEl.style.left = newLeft + 'px';
                windowEl.style.top = newTop + 'px';
            }, { passive: true });

            document.addEventListener('touchend', function () {
                isDragging = false;
            });
        }

        // Phím Escape đóng chat
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && windowEl.classList.contains('is-open')) {
                setChatState(false);
            }
        });

        // Nút reset cuộc trò chuyện
        if (btnReset) {
            btnReset.addEventListener('click', function () {
                chatHistory = [];
                renderChatView();
            });
        }

        // Submit câu hỏi
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!input || isBotTyping) return;
                const txt = input.value.trim();
                if (txt === '') return;
                input.value = '';
                sendMessage(txt);
            });
        }

        // Khởi tạo bộ điều khiển cuộn & mở rộng gợi ý (Chips Controls)
        setupChipsControls();
    }

    function setupChipsControls() {
        const chipsBar = document.getElementById('roleAiChipsBar');
        const chipsEl = document.getElementById('roleAiChips');
        const btnPrev = document.getElementById('roleAiChipPrev');
        const btnNext = document.getElementById('roleAiChipNext');
        const btnToggle = document.getElementById('roleAiChipToggle');

        if (!chipsEl) return;

        // 1. Lăn chuột dọc -> tự động cuộn ngang mượt mà
        chipsEl.addEventListener('wheel', function (e) {
            if (chipsBar && chipsBar.classList.contains('is-expanded')) return;
            if (e.deltaY !== 0) {
                e.preventDefault();
                chipsEl.scrollLeft += e.deltaY;
                updateArrowStates();
            }
        }, { passive: false });

        // 2. Kéo chuột để cuộn (Drag-to-scroll mượt mà, không kẹt)
        let isDown = false;
        let startX = 0;
        let scrollLeftStart = 0;
        let hasDragged = false;

        chipsEl.addEventListener('mousedown', function (e) {
            if (chipsBar && chipsBar.classList.contains('is-expanded')) return;
            isDown = true;
            hasDragged = false;
            startX = e.pageX - chipsEl.offsetLeft;
            scrollLeftStart = chipsEl.scrollLeft;
            chipsEl.style.cursor = 'grabbing';
        });

        document.addEventListener('mousemove', function (e) {
            if (!isDown) return;
            const x = e.pageX - chipsEl.offsetLeft;
            const walk = (x - startX) * 1.5;
            if (Math.abs(walk) > 4) {
                hasDragged = true;
            }
            chipsEl.scrollLeft = scrollLeftStart - walk;
            updateArrowStates();
        });

        document.addEventListener('mouseup', function () {
            if (isDown) {
                isDown = false;
                chipsEl.style.cursor = 'grab';
            }
        });

        // Ngăn click chip nhầm khi đang kéo chuột cuộn
        chipsEl.addEventListener('click', function (e) {
            if (hasDragged) {
                e.stopPropagation();
                e.preventDefault();
                hasDragged = false;
            }
        }, true);

        // 3. Phím bấm mũi tên < và >
        if (btnPrev) {
            btnPrev.addEventListener('click', function (e) {
                e.stopPropagation();
                chipsEl.scrollBy({ left: -180, behavior: 'smooth' });
                setTimeout(updateArrowStates, 250);
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', function (e) {
                e.stopPropagation();
                chipsEl.scrollBy({ left: 180, behavior: 'smooth' });
                setTimeout(updateArrowStates, 250);
            });
        }

        // 4. Phím mở rộng xem tất cả gợi ý cùng lúc (Toggle Expanded)
        if (btnToggle && chipsBar) {
            btnToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                const isExp = chipsBar.classList.toggle('is-expanded');
                btnToggle.title = isExp ? 'Thu gọn gợi ý' : 'Mở rộng xem tất cả gợi ý';
                const icon = btnToggle.querySelector('i');
                if (icon) {
                    icon.className = isExp ? 'fa-solid fa-chevron-up' : 'fa-solid fa-grip';
                }
            });
        }

        chipsEl.addEventListener('scroll', updateArrowStates);
    }

    function updateArrowStates() {
        const chipsEl = document.getElementById('roleAiChips');
        const btnPrev = document.getElementById('roleAiChipPrev');
        const btnNext = document.getElementById('roleAiChipNext');
        if (!chipsEl || !btnPrev || !btnNext) return;
        const maxScroll = chipsEl.scrollWidth - chipsEl.clientWidth;
        if (maxScroll <= 4) {
            btnPrev.disabled = true;
            btnNext.disabled = true;
        } else {
            btnPrev.disabled = (chipsEl.scrollLeft <= 4);
            btnNext.disabled = (chipsEl.scrollLeft >= maxScroll - 4);
        }
    }

    function setChatState(isOpen) {
        const windowEl = document.getElementById('roleAiWindow');
        const input = document.getElementById('roleAiInput');
        if (!windowEl) return;

        if (isOpen) {
            windowEl.classList.add('is-open');
            windowEl.setAttribute('aria-hidden', 'false');
            scrollChatBottom();
            setTimeout(() => {
                if (input) input.focus();
            }, 200);
        } else {
            windowEl.classList.remove('is-open');
            windowEl.setAttribute('aria-hidden', 'true');
        }
    }

    function renderChatView() {
        const bodyEl = document.getElementById('roleAiBody');
        const input = document.getElementById('roleAiInput');
        const meta = ROLE_CONFIGS[activeRole] || ROLE_CONFIGS['admin'];
        if (!bodyEl) return;

        bodyEl.innerHTML = '';

        // Đồng bộ tiêu đề, orb và badge trên header chatbox
        const titleEl = document.getElementById('roleAiHeaderTitle');
        if (titleEl) {
            titleEl.textContent = meta.name;
        }

        const headerOrbEl = document.getElementById('roleAiHeaderOrb');
        if (headerOrbEl) {
            headerOrbEl.style.background = meta.gradient;
            headerOrbEl.innerHTML = `<i class="${meta.icon}"></i>`;
        }

        const subtitleBadge = document.querySelector('#roleAiHeader .role-ai-header-subtitle .badge');
        if (subtitleBadge) {
            subtitleBadge.textContent = meta.badge;
        }

        if (input) {
            input.placeholder = meta.placeholder;
        }

        // Thêm câu chào mừng nếu chưa có tin nhắn
        if (chatHistory.length === 0) {
            chatHistory.push({
                sender: 'bot',
                text: meta.welcome,
                time: getNowTime()
            });
        }

        chatHistory.forEach(msg => {
            appendMessageBubble(msg.sender, msg.text, msg.time, false);
        });

        // Typing indicator
        const typingEl = document.createElement('div');
        typingEl.id = 'roleAiTyping';
        typingEl.className = 'role-ai-msg msg-bot d-none';
        typingEl.innerHTML = `
            <div class="role-ai-msg-avatar" style="background:${meta.gradient};"><i class="${meta.icon}"></i></div>
            <div class="role-ai-msg-content">
                <div class="role-ai-typing">
                    <span class="role-ai-typing-dot"></span>
                    <span class="role-ai-typing-dot"></span>
                    <span class="role-ai-typing-dot"></span>
                </div>
            </div>
        `;
        bodyEl.appendChild(typingEl);

        renderChips(meta.suggestions);
        scrollChatBottom();
    }

    function getChipIconClass(text) {
        const clean = (text || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/Đ/g, 'D')
            .toLowerCase();

        if (/doanh thu|tai chinh|loi nhuan|tang truong|bao cao/.test(clean)) {
            return 'fa-solid fa-chart-line text-danger';
        }
        if (/kho|ton|imei|nhap|xuat|dong goi|kiem ke/.test(clean)) {
            return 'fa-solid fa-boxes-stacked text-warning';
        }
        if (/don hang|tra cuu don|cho duyet|van don|xac nhan/.test(clean)) {
            return 'fa-solid fa-receipt text-primary';
        }
        if (/voucher|khuyen mai|ma giam|giam gia|chot deal/.test(clean)) {
            return 'fa-solid fa-ticket text-info';
        }
        if (/bao hanh|sua chua|man hinh|phoi oled|pin|linh kien/.test(clean)) {
            return 'fa-solid fa-shield-halved text-success';
        }
        if (/cskh|goi lai|1 sao|5 sao|review|danh gia|khieu nai|last/.test(clean)) {
            return 'fa-solid fa-star text-warning';
        }
        if (/up-?sell|phu kien|sac|tai nghe/.test(clean)) {
            return 'fa-solid fa-bolt text-warning';
        }
        return 'fa-solid fa-wand-magic-sparkles text-primary';
    }

    function renderChips(suggestions) {
        const chipsBar = document.getElementById('roleAiChipsBar');
        const chipsEl = document.getElementById('roleAiChips');
        if (!chipsEl) return;
        chipsEl.innerHTML = '';

        if (!suggestions || suggestions.length === 0) {
            if (chipsBar) chipsBar.style.display = 'none';
            return;
        }

        if (chipsBar) chipsBar.style.display = 'flex';
        chipsEl.scrollLeft = 0;

        suggestions.forEach(s => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'role-ai-chip';
            const iconClass = getChipIconClass(s);
            btn.innerHTML = `<i class="${iconClass}"></i><span>${escapeHtml(s)}</span>`;
            btn.title = 'Bấm để hỏi nhanh: ' + s;
            btn.addEventListener('click', function () {
                sendMessage(s);
            });
            chipsEl.appendChild(btn);
        });

        setTimeout(updateArrowStates, 80);
    }

    function appendMessageBubble(sender, htmlContent, timeStr, shouldScroll = true) {
        const bodyEl = document.getElementById('roleAiBody');
        const typingEl = document.getElementById('roleAiTyping');
        if (!bodyEl) return;

        const meta = ROLE_CONFIGS[activeRole] || ROLE_CONFIGS['admin'];
        const row = document.createElement('div');
        row.className = `role-ai-msg msg-${sender}`;
        const time = timeStr || getNowTime();

        if (sender === 'bot') {
            row.innerHTML = `
                <div class="role-ai-msg-avatar" style="background:${meta.gradient};"><i class="${meta.icon}"></i></div>
                <div class="role-ai-msg-content">
                    <div class="role-ai-bubble">${htmlContent}</div>
                    <span class="role-ai-msg-time">${time}</span>
                </div>
            `;
        } else {
            row.innerHTML = `
                <div class="role-ai-msg-content">
                    <div class="role-ai-bubble">${escapeHtml(htmlContent)}</div>
                    <span class="role-ai-msg-time">${time}</span>
                </div>
            `;
        }

        if (typingEl && bodyEl.contains(typingEl)) {
            bodyEl.insertBefore(row, typingEl);
        } else {
            bodyEl.appendChild(row);
        }

        if (shouldScroll) {
            scrollChatBottom();
        }
    }

    async function sendMessage(text) {
        const input = document.getElementById('roleAiInput');
        const typingEl = document.getElementById('roleAiTyping');

        if (isBotTyping || !text.trim()) return;
        isBotTyping = true;

        const now = getNowTime();
        chatHistory.push({ sender: 'user', text: text, time: now });
        appendMessageBubble('user', text, now);

        if (input) input.disabled = true;

        if (typingEl) {
            typingEl.classList.remove('d-none');
            scrollChatBottom();
        }

        try {
            const windowEl = document.getElementById('roleAiWindow');
            const assigned = windowEl ? (windowEl.getAttribute('data-role') || 'admin') : 'admin';
            const isAdmin = windowEl ? (windowEl.getAttribute('data-is-admin') === '1') : false;
            const finalRole = (!isAdmin) ? assigned : activeRole;

            const res = await fetch(getApiEndpoint(), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    role: finalRole,
                    message: text
                })
            });

            const data = await res.json();
            const delay = 500 + Math.floor(Math.random() * 200);

            setTimeout(() => {
                if (typingEl) typingEl.classList.add('d-none');

                const replyHtml = (data && data.success && data.reply) 
                    ? data.reply 
                    : 'Dạ, hiện hệ thống đang cập nhật dữ liệu. Bạn vui lòng thử lại sau giây lát!';

                const suggestions = (data && data.suggestions) ? data.suggestions : (ROLE_CONFIGS[activeRole]?.suggestions || []);
                const botTime = data.timestamp || getNowTime();

                chatHistory.push({ sender: 'bot', text: replyHtml, time: botTime });
                appendMessageBubble('bot', replyHtml, botTime);
                renderChips(suggestions);

                isBotTyping = false;
                if (input) {
                    input.disabled = false;
                    input.focus();
                }
            }, delay);

        } catch (err) {
            setTimeout(() => {
                if (typingEl) typingEl.classList.add('d-none');
                const errMsg = 'Lỗi kết nối máy chủ. Bạn vui lòng kiểm tra lại kết nối mạng!';
                chatHistory.push({ sender: 'bot', text: errMsg, time: getNowTime() });
                appendMessageBubble('bot', errMsg, getNowTime());
                isBotTyping = false;
                if (input) {
                    input.disabled = false;
                    input.focus();
                }
            }, 500);
        }
    }

    function scrollChatBottom() {
        const bodyEl = document.getElementById('roleAiBody');
        if (bodyEl) {
            bodyEl.scrollTop = bodyEl.scrollHeight;
        }
    }

    function getNowTime() {
        const d = new Date();
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function escapeHtml(string) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return String(string).replace(/[&<>"']/g, s => map[s]);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.openRoleAiChat = function (roleKey, question) {
        const windowEl = document.getElementById('roleAiWindow');
        const assigned = windowEl ? (windowEl.getAttribute('data-role') || 'admin') : 'admin';
        const isAdmin = windowEl ? (windowEl.getAttribute('data-is-admin') === '1') : false;

        // Nhân viên chỉ được phép sử dụng DUY NHẤT chức vụ được cấp phép
        let targetRole = roleKey;
        if (!isAdmin) {
            targetRole = assigned;
        }

        if (targetRole && ROLE_CONFIGS[targetRole]) {
            if (activeRole !== targetRole) {
                activeRole = targetRole;
                chatHistory = []; // Đặt lại lịch sử sạch cho chức vụ
            }
            renderChatView();
        }
        setChatState(true);
        if (question) {
            setTimeout(() => {
                sendMessage(question);
            }, 300);
        }
    };

})();
