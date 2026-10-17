<?php
/**
 * V-Phone Store 2026 - Cụm Nút Liên Hệ Nhanh Nổi Đa Kênh & Trợ Lý Ảo Chatbot AI 24/7
 * Tích hợp: Trợ lý ảo AI tự động trò chuyện, Hotline 1800 6868, Yêu cầu gọi lại 5 phút, Hệ thống showroom
 * Sử dụng bộ SVG vector thiết kế riêng (Không dùng icon mặc định hay emoji)
 */
$currentUser = $_SESSION['user'] ?? null;
$loggedName = $currentUser['fullname'] ?? '';
$loggedPhone = $currentUser['phone'] ?? '';

// Không điền sẵn số Hotline của cửa hàng vào ô điện thoại của khách hàng
if (strpos($loggedPhone, '1800') !== false || strlen($loggedPhone) < 10) {
    $loggedPhone = '';
}
?>

<!-- CỤM NÚT LIÊN HỆ NHANH NỔI ĐA KÊNH -->
<div id="vphoneContactWidget" class="vphone-contact-widget">
    <!-- 1. Bong Bóng Chào Mừng / Gợi Ý Hỗ Trợ (Prompt Bubble) -->
    <div id="vphoneWidgetPrompt" class="vphone-widget-prompt" role="status" aria-live="polite">
        <div class="prompt-content">
            <div class="prompt-ai-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="24" height="24" rx="7" fill="url(#pGrad)"/>
                    <circle cx="8" cy="11" r="1.8" fill="#00FFC2"/>
                    <circle cx="16" cy="11" r="1.8" fill="#00FFC2"/>
                    <path d="M9 16c1.5 1.5 4.5 1.5 6 0" stroke="#00FFC2" stroke-width="1.5" stroke-linecap="round"/>
                    <circle cx="12" cy="4" r="1.5" fill="#00FFC2"/>
                    <line x1="12" y1="5.5" x2="12" y2="7.5" stroke="#00D2FF" stroke-width="1.5"/>
                    <defs>
                        <linearGradient id="pGrad" x1="0" y1="0" x2="24" y2="24" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#00D2FF"/>
                            <stop offset="1" stop-color="#0055B3"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <span class="prompt-text"><strong>Cần tư vấn?</strong> Trò chuyện cùng V-Phone AI!</span>
        </div>
        <button type="button" class="prompt-close" id="btnDismissPrompt" title="Đóng gợi ý" aria-label="Đóng">&times;</button>
    </div>

    <!-- 2. Menu Popout Đa Kênh Liên Hệ -->
    <div id="vphoneContactMenu" class="vphone-contact-menu" aria-hidden="true">
        <!-- Header Menu -->
        <div class="menu-header">
            <div class="menu-header-avatar">
                <img src="assets/images/vphone-icon.png" alt="CSKH V-Phone">
                <span class="status-indicator" title="Đang trực tuyến"></span>
            </div>
            <div class="menu-header-info">
                <div class="menu-title">Tư Vấn & CSKH V-Phone</div>
                <div class="menu-subtitle">
                    <span class="online-dot"></span> Đang trực tuyến sẵn sàng hỗ trợ
                </div>
            </div>
            <button type="button" class="btn-close-menu" id="btnCloseContactMenu" aria-label="Đóng menu">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <!-- Danh sách các kênh hỗ trợ -->
        <div class="menu-channels">
            <!-- 1. Trợ Lý Ảo V-Phone AI (Tự động trò chuyện 24/7) -->
            <button type="button" class="channel-item channel-chatbot" id="btnOpenChatbot" title="Chat với Trợ lý ảo V-Phone AI">
                <div class="channel-icon-wrap chatbot-bg">
                    <svg viewBox="0 0 28 28" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="2" y="5" width="24" height="18" rx="8" fill="white" fill-opacity="0.2"/>
                        <rect x="4" y="7" width="20" height="14" rx="6" stroke="white" stroke-width="1.8"/>
                        <circle cx="10" cy="13" r="1.8" fill="#00FFC2"/>
                        <circle cx="18" cy="13" r="1.8" fill="#00FFC2"/>
                        <path d="M11 17c1.2 1 4.8 1 6 0" stroke="white" stroke-width="1.6" stroke-linecap="round"/>
                        <circle cx="14" cy="3" r="1.5" fill="#00FFC2"/>
                        <line x1="14" y1="4.5" x2="14" y2="7" stroke="white" stroke-width="1.5"/>
                    </svg>
                </div>
                <div class="channel-details">
                    <div class="channel-name">Trợ Lý Ảo V-Phone AI</div>
                    <div class="channel-desc">Chatbot tự động tư vấn máy & báo giá 24/7</div>
                </div>
                <div class="channel-action">
                    <span class="badge-action badge-ai">Chat AI</span>
                </div>
            </button>

            <!-- 2. Hotline 1800 6868 -->
            <a href="tel:18006868" class="channel-item channel-hotline" title="Gọi tổng đài miễn phí 1800 6868">
                <div class="channel-icon-wrap">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </div>
                <div class="channel-details">
                    <div class="channel-name">Hotline 1800 6868</div>
                    <div class="channel-desc">Miễn phí cước cuộc gọi (08:00 - 21:30)</div>
                </div>
                <div class="channel-action">
                    <span class="badge-action badge-call">Gọi ngay</span>
                </div>
            </a>

            <!-- 3. Yêu Cầu Gọi Lại & CSKH -->
            <button type="button" class="channel-item channel-callback" id="btnOpenCallbackModal" title="Yêu cầu gọi lại & gửi phản hồi">
                <div class="channel-icon-wrap callback-bg">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
                        <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
                        <path d="M19 19v1a2 2 0 0 1-2 2H9"/>
                    </svg>
                </div>
                <div class="channel-details">
                    <div class="channel-name">Gửi Yêu Cầu Hỗ Trợ & CSKH</div>
                    <div class="channel-desc">Tư vấn bán hàng, bảo hành & CSKH 5p</div>
                </div>
                <div class="channel-action">
                    <span class="badge-action badge-fast">5 phút</span>
                </div>
            </button>

            <!-- 4. Hộp Thư Phản Hồi Từ CSKH (Xem câu trả lời chuyên viên) -->
            <button type="button" class="channel-item channel-inbox" id="btnOpenInquiriesModal" title="Xem câu trả lời và tư vấn từ CSKH">
                <div class="channel-icon-wrap" style="background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff;">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </div>
                <div class="channel-details">
                    <div class="channel-name">Hộp Thư Phản Hồi CSKH</div>
                    <div class="channel-desc">Xem câu trả lời từ chuyên viên V-Phone</div>
                </div>
                <div class="channel-action">
                    <span class="badge-action badge-ai" style="background: #e0f2fe; color: #0284c7;">Hộp thư</span>
                </div>
            </button>

            <!-- 4. Hệ Thống Showroom & Bản Đồ -->
            <button type="button" class="channel-item channel-showroom" id="btnOpenShowroomModal" title="Xem danh sách showroom & bản đồ chỉ đường">
                <div class="channel-icon-wrap showroom-bg">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
                <div class="channel-details">
                    <div class="channel-name">Hệ Thống Showroom</div>
                    <div class="channel-desc">3 cơ sở chính hãng & chỉ đường Maps</div>
                </div>
                <div class="channel-action">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </div>
            </button>
        </div>

        <!-- Footer Menu -->
        <div class="menu-footer">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="me-1 text-primary" style="vertical-align: -2px;">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
            Phục vụ: <strong>08:00 - 21:30</strong> (Cả T7, CN & Lễ)
        </div>
    </div>

    <!-- 3. Nút Kích Hoạt Nổi Master (Floating Action Button) -->
    <button type="button" id="btnToggleContactWidget" class="vphone-widget-toggle" aria-expanded="false" aria-label="Mở kênh liên hệ nhanh V-Phone">
        <!-- Vòng Sóng Lan Tỏa (Pulsing Ripple Rings) -->
        <span class="pulse-wave pulse-wave-1"></span>
        <span class="pulse-wave pulse-wave-2"></span>

        <!-- Custom Vector Icons (Không dùng icon mặc định) -->
        <div class="toggle-icon-wrap">
            <!-- Icon Trợ Lý AI Tỏa Sáng -->
            <svg viewBox="0 0 36 36" width="30" height="30" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-normal">
                <defs>
                    <linearGradient id="aiOrbGrad" x1="0" y1="0" x2="36" y2="36" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#00FFC2"/>
                        <stop offset="1" stop-color="#FFFFFF"/>
                    </linearGradient>
                </defs>
                <path d="M18 4C9.716 4 3 10.268 3 18c0 3.125 1.096 6.009 2.955 8.358L4 32l6.233-1.636C12.44 31.398 15.14 32 18 32c8.284 0 15-6.268 15-14S26.284 4 18 4z" fill="white" fill-opacity="0.18"/>
                <path d="M18 5.5C10.544 5.5 4.5 11.096 4.5 18c0 2.793.978 5.372 2.639 7.472l-1.42 5.253 5.485-1.44A14.28 14.28 0 0 0 18 30.5c7.456 0 13.5-5.596 13.5-12.5S25.456 5.5 18 5.5z" stroke="url(#aiOrbGrad)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12.5" cy="17.5" r="2" fill="#00FFC2"/>
                <circle cx="18" cy="17.5" r="2" fill="#FFFFFF"/>
                <circle cx="23.5" cy="17.5" r="2" fill="#00FFC2"/>
                <path d="M14 23c1.5 1.5 6.5 1.5 8 0" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round"/>
            </svg>

            <!-- Icon Đóng (Futuristic Close Cross) -->
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="icon-opened">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </div>

        <!-- Huy hiệu thông báo -->
        <span class="toggle-badge">1</span>
    </button>
</div>

<!-- Lớp Phủ Nền Đóng Khi Chạm Ngoài (Mobile Backdrop) -->
<div id="vphoneContactBackdrop" class="vphone-contact-backdrop"></div>

<!-- =========================================================================
     CỬA SỔ CHATBOT TỰ ĐỘNG V-PHONE AI (INTERACTIVE CHATBOT WINDOW)
     ========================================================================= -->
<div id="vphoneChatbotWindow" class="vphone-chatbot-window" aria-hidden="true">
    <!-- Header Chatbot -->
    <div class="chatbot-header">
        <div class="chatbot-avatar-wrap">
            <div class="chatbot-avatar">
                <svg viewBox="0 0 28 28" width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="5" width="24" height="18" rx="8" fill="white" fill-opacity="0.2"/>
                    <rect x="4" y="7" width="20" height="14" rx="6" stroke="white" stroke-width="1.8"/>
                    <circle cx="10" cy="13" r="1.8" fill="#00FFC2"/>
                    <circle cx="18" cy="13" r="1.8" fill="#00FFC2"/>
                    <path d="M11 17c1.2 1 4.8 1 6 0" stroke="white" stroke-width="1.6" stroke-linecap="round"/>
                    <circle cx="14" cy="3" r="1.5" fill="#00FFC2"/>
                    <line x1="14" y1="4.5" x2="14" y2="7" stroke="white" stroke-width="1.5"/>
                </svg>
            </div>
            <span class="chatbot-status-dot" title="Đang trực tuyến"></span>
        </div>
        <div class="chatbot-header-info">
            <div class="chatbot-name">Trợ Lý Ảo V-Phone AI</div>
            <div class="chatbot-status">
                <span class="pulse-indicator"></span> Chuyên gia tư vấn bán hàng 24/7
            </div>
        </div>
        <div class="chatbot-header-actions">
            <button type="button" id="btnResetChatbot" class="chatbot-btn-action" title="Bắt đầu hội thoại mới" aria-label="Làm mới">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M23 4v6h-6"/>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                </svg>
            </button>
            <button type="button" id="btnCloseChatbot" class="chatbot-btn-action" title="Thu nhỏ chat" aria-label="Đóng">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Khung Tin Nhắn (Messages Stream) -->
    <div id="chatbotMessages" class="chatbot-messages">
        <!-- Tin nhắn chào mừng ban đầu từ Bot -->
        <div class="chat-msg chat-msg-bot">
            <div class="chat-msg-avatar">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="24" height="24" rx="7" fill="#0066CC"/>
                    <circle cx="8" cy="11" r="1.5" fill="#00FFC2"/>
                    <circle cx="16" cy="11" r="1.5" fill="#00FFC2"/>
                    <path d="M9 15c1.5 1 4.5 1 6 0" stroke="#00FFC2" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="chat-msg-content">
                <div class="chat-bubble">
                    Xin chào! Em là <strong>Trợ lý ảo AI của V-Phone Store 2026</strong>. ✨<br><br>
                    Em là chuyên viên bán hàng tự động 24/7, có thể giải đáp <strong>mọi câu hỏi liên quan đến mua sắm điện thoại</strong>:<br>
                    • 📱 Bảng giá, cấu hình & so sánh các dòng Flagship mới nhất<br>
                    • 💳 Hướng dẫn làm hồ sơ mua trả góp 0% duyệt online 15 phút<br>
                    • 🛡️ Chính sách bảo hành 1 đổi 1 trong 30 ngày & đồng kiểm mở hộp<br>
                    • 🔄 Định giá thu cũ đổi mới trợ giá tới 2 triệu đồng.<br><br>
                    Anh/chị đang quan tâm đến nội dung nào dưới đây ạ?
                </div>
                <div class="chat-time"><?= date('H:i') ?></div>
            </div>
        </div>

        <!-- Gợi ý câu hỏi nhanh (Quick Chips) -->
        <div class="chatbot-quick-chips" id="chatbotQuickChips">
            <button type="button" class="chip-item" data-query="Tư vấn các dòng iPhone mới nhất">📱 iPhone mới nhất</button>
            <button type="button" class="chip-item" data-query="Tư vấn điện thoại Samsung Galaxy">⚡ Samsung Galaxy</button>
            <button type="button" class="chip-item" data-query="Điện thoại dưới 10 triệu đáng mua nhất">💰 Máy dưới 10 triệu</button>
            <button type="button" class="chip-item" data-query="Chính sách mua trả góp 0%">💳 Trả góp 0%</button>
            <button type="button" class="chip-item" data-query="Chính sách đồng kiểm mở hộp trước khi trả tiền">📦 Kiểm tra hàng trước</button>
            <button type="button" class="chip-item" data-query="Chính sách bảo hành 1 đổi 1">🛡️ Bảo hành 1-1</button>
            <button type="button" class="chip-item" data-query="Xem điện thoại cũ 99% giá tốt">✨ Máy cũ 99%</button>
            <button type="button" class="chip-item" data-query="Hệ thống địa chỉ showroom">📍 Địa chỉ Showroom</button>
            <button type="button" class="chip-item" data-query="Hotline hỗ trợ và gặp tư vấn viên">📞 Hotline CSKH</button>
        </div>

        <!-- Typing Indicator (Đang suy nghĩ & soạn tin nhắn với animation mượt mà) -->
        <div id="chatbotTyping" class="chat-msg chat-msg-bot d-none">
            <div class="chat-msg-avatar">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="24" height="24" rx="7" fill="#0066CC"/>
                    <circle cx="8" cy="11" r="1.5" fill="#00FFC2"/>
                    <circle cx="16" cy="11" r="1.5" fill="#00FFC2"/>
                </svg>
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
    </div>

    <!-- Khung Nhập Tin Nhắn (Footer Input) -->
    <form id="formChatbot" class="chatbot-footer" autocomplete="off">
        <input type="text" id="chatbotInput" class="chatbot-input" placeholder="Nhập câu hỏi (VD: iPhone 18 giá bao nhiêu, có trả góp không?)..." maxlength="300" required>
        <button type="submit" id="btnSendChatbot" class="chatbot-send-btn" aria-label="Gửi tin nhắn">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
        </button>
    </form>
</div>

<!-- =========================================================================
     MODAL 1: TRUNG TÂM CSKH & HỖ TRỢ ĐA KÊNH V-PHONE (GỬI YÊU CẦU & XEM PHẢN HỒI)
     ========================================================================= -->
<div class="modal fade" id="modalCallbackRequest" tabindex="-1" aria-labelledby="modalCallbackLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header border-0 text-white p-3.5 p-sm-4 position-relative" style="background: linear-gradient(135deg, #0066cc 0%, #0284c7 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px; flex-shrink: 0; font-size: 20px;">
                        <i class="fa-solid fa-headset text-primary"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-1 text-white" id="modalCallbackLabel">Trung Tâm CSKH & Hỗ Trợ Đa Kênh</h5>
                        <div class="small text-white text-opacity-90">Bộ phận CSKH, Bán hàng & Bảo hành sẵn sàng tiếp nhận & phản hồi tận tâm</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Đóng" style="opacity: 0.9; cursor: pointer;"></button>
            </div>

            <!-- Modal Nav Tabs (Gửi Yêu Cầu / Hộp Thư Phản Hồi) -->
            <div class="px-4 pt-3 pb-0 bg-light border-bottom">
                <ul class="nav nav-pills nav-fill gap-2" id="supportModalTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill fw-bold py-2 px-3 small d-flex align-items-center justify-content-center gap-2" id="tab-send-inquiry-btn" data-bs-toggle="pill" data-bs-target="#tab-send-inquiry" type="button" role="tab">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Gửi Yêu Cầu Hỗ Trợ</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold py-2 px-3 small d-flex align-items-center justify-content-center gap-2" id="tab-view-replies-btn" data-bs-toggle="pill" data-bs-target="#tab-view-replies" type="button" role="tab">
                            <i class="fa-solid fa-inbox"></i>
                            <span>Hộp Thư Phản Hồi CSKH</span>
                            <span class="badge bg-danger rounded-pill d-none" id="customerInquiriesBadge">0</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Modal Body Content -->
            <div class="modal-body p-3.5 p-sm-4">
                <div class="tab-content" id="supportModalTabContent">
                    <!-- TAB 1: FORM GỬI YÊU CẦU MỚI -->
                    <div class="tab-pane fade show active" id="tab-send-inquiry" role="tabpanel">
                        <div id="callbackAlert" class="alert d-none small py-2 px-3 rounded-3 mb-3"></div>

                        <form id="formCallbackRequest" novalidate>
                            <!-- 1. Chọn bộ phận tiếp nhận -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark d-flex align-items-center gap-1.5 mb-2">
                                    <i class="fa-solid fa-sitemap text-primary"></i>
                                    <span>Chọn bộ phận tiếp nhận yêu cầu của bạn: <span class="text-danger">*</span></span>
                                </label>
                                <div class="row g-2">
                                    <div class="col-12 col-sm-6">
                                        <label class="dept-radio-card p-2.5 rounded-3 border d-flex align-items-center gap-2.5 cursor-pointer w-100 mb-0 h-100" for="dept_cskh">
                                            <input type="radio" name="department" value="cskh" id="dept_cskh" class="form-check-input mt-0 flex-shrink-0" checked>
                                            <div class="min-w-0">
                                                <div class="small fw-bold text-dark text-truncate"><i class="fa-solid fa-headset text-info me-1"></i>CSKH & Khiếu Nại</div>
                                                <small class="text-muted d-block" style="font-size: 0.72rem;">Giải đáp thắc mắc, dịch vụ</small>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label class="dept-radio-card p-2.5 rounded-3 border d-flex align-items-center gap-2.5 cursor-pointer w-100 mb-0 h-100" for="dept_sales">
                                            <input type="radio" name="department" value="sales" id="dept_sales" class="form-check-input mt-0 flex-shrink-0">
                                            <div class="min-w-0">
                                                <div class="small fw-bold text-dark text-truncate"><i class="fa-solid fa-boxes-packing text-primary me-1"></i>Bán Hàng & Báo Giá</div>
                                                <small class="text-muted d-block" style="font-size: 0.72rem;">Tư vấn cấu hình máy, báo giá</small>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label class="dept-radio-card p-2.5 rounded-3 border d-flex align-items-center gap-2.5 cursor-pointer w-100 mb-0 h-100" for="dept_warranty">
                                            <input type="radio" name="department" value="warranty" id="dept_warranty" class="form-check-input mt-0 flex-shrink-0">
                                            <div class="min-w-0">
                                                <div class="small fw-bold text-dark text-truncate"><i class="fa-solid fa-shield-halved text-success me-1"></i>Kỹ Thuật & Bảo Hành</div>
                                                <small class="text-muted d-block" style="font-size: 0.72rem;">Chính sách 1 đổi 1, sửa chữa</small>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label class="dept-radio-card p-2.5 rounded-3 border d-flex align-items-center gap-2.5 cursor-pointer w-100 mb-0 h-100" for="dept_installment">
                                            <input type="radio" name="department" value="installment" id="dept_installment" class="form-check-input mt-0 flex-shrink-0">
                                            <div class="min-w-0">
                                                <div class="small fw-bold text-dark text-truncate"><i class="fa-solid fa-credit-card text-warning me-1"></i>Hỗ Trợ Trả Góp 0%</div>
                                                <small class="text-muted d-block" style="font-size: 0.72rem;">Duyệt hồ sơ nhanh, thẻ tín dụng</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Thông tin người gửi -->
                            <div class="row g-2.5 mb-2.5">
                                <div class="col-12 col-sm-6">
                                    <label for="cbFullName" class="form-label small fw-bold text-dark mb-1">Họ và tên của bạn <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-regular fa-user"></i></span>
                                        <input type="text" class="form-control border-start-0" id="cbFullName" name="fullname" placeholder="Ví dụ: Nguyễn Văn An" value="<?= htmlspecialchars($loggedName) ?>" required>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="cbPhone" class="form-label small fw-bold text-dark mb-1">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-phone"></i></span>
                                        <input type="tel" class="form-control border-start-0" id="cbPhone" name="phone" placeholder="Ví dụ: 0987 654 321" value="<?= htmlspecialchars($loggedPhone) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2.5 mb-2.5">
                                <div class="col-12 col-sm-6">
                                    <label for="cbEmail" class="form-label small fw-bold text-dark mb-1">Email nhận thông báo (Không bắt buộc)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-regular fa-envelope"></i></span>
                                        <input type="email" class="form-control border-start-0" id="cbEmail" name="email" placeholder="email@gmail.com" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="cbTopic" class="form-label small fw-bold text-dark mb-1">Chủ đề quan tâm</label>
                                    <select class="form-select" id="cbTopic" name="topic">
                                        <option value="Tư vấn mua Flagship mới (iPhone, Galaxy, ASUS...)">Tư vấn mua điện thoại Flagship mới</option>
                                        <option value="Hỗ trợ trả góp 0% / Lên đời thu cũ đổi mới">Hỗ trợ trả góp 0% / Lên đời thu cũ</option>
                                        <option value="Kiểm tra tiến trình đơn hàng & Vận chuyển">Kiểm tra tiến trình đơn hàng & Vận chuyển</option>
                                        <option value="Yêu cầu bảo hành 1 đổi 1 & Kỹ thuật máy">Yêu cầu bảo hành 1 đổi 1 & Kỹ thuật máy</option>
                                        <option value="Hỏi giá ưu đãi & Voucher khuyến mãi">Hỏi giá ưu đãi & Voucher khuyến mãi</option>
                                        <option value="Góp ý dịch vụ & Khiếu nại">Góp ý dịch vụ & Khiếu nại</option>
                                        <option value="Khác">Chủ đề khác</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="cbNote" class="form-label small fw-bold text-dark mb-1">Nội dung chi tiết cần hỗ trợ <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="cbNote" name="note" rows="2" placeholder="Ví dụ: Cần tư vấn iPhone 17 bản 256GB màu Xanh, gọi lại sau 17h hoặc phản hồi chi tiết giá lăn bánh..." required></textarea>
                            </div>

                            <div class="p-2.5 px-3 bg-light rounded-3 d-flex align-items-center justify-content-between mb-3 border">
                                <div class="small text-muted" style="font-size: 0.75rem;">
                                    <i class="fa-solid fa-shield-halved text-success me-1"></i>Thông tin được bảo mật 100%
                                </div>
                                <a href="tel:18006868" class="small fw-bold text-primary text-decoration-none" style="font-size: 0.75rem;">
                                    <i class="fa-solid fa-phone me-1"></i>Hotline Miễn Phí 1800 6868
                                </a>
                            </div>

                            <button type="submit" id="btnSubmitCallback" class="btn btn-primary w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>GỬI YÊU CẦU ĐẾN BỘ PHẬN CSKH</span>
                            </button>
                        </form>

                        <!-- Trạng thái sau khi gửi thành công -->
                        <div id="callbackSuccessView" class="text-center py-4 d-none">
                            <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 32px;">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">Đã Nhận Yêu Cầu Thành Công!</h5>
                            <p class="text-muted small mb-4" id="callbackSuccessMsg">Chuyên viên V-Phone sẽ liên hệ lại với bạn trong vòng 5 phút.</p>
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-primary px-4 rounded-pill btn-sm fw-bold d-inline-flex align-items-center gap-1.5" onclick="switchToInquiriesTab()">
                                    <i class="fa-solid fa-inbox"></i>
                                    <span>Xem Hộp Thư Phản Hồi</span>
                                </button>
                                <button type="button" class="btn btn-outline-secondary px-4 rounded-pill btn-sm" data-bs-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: HỘP THƯ PHẢN HỒI TỪ CSKH & CHUYÊN VIÊN -->
                    <div class="tab-pane fade" id="tab-view-replies" role="tabpanel">
                        <!-- Thanh tra cứu SĐT -->
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="row g-2 align-items-center">
                                <div class="col-12 col-sm-8">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-phone"></i></span>
                                        <input type="tel" id="inquirySearchPhone" class="form-control border-start-0" placeholder="Nhập số điện thoại gửi yêu cầu..." value="<?= htmlspecialchars($loggedPhone) ?>">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-4">
                                    <button type="button" class="btn btn-primary btn-sm w-100 fw-bold rounded-pill d-flex align-items-center justify-content-center gap-1.5" id="btnSearchInquiry" onclick="fetchCustomerInquiries()">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        <span>Tra cứu phản hồi</span>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-1.5" style="font-size: 0.72rem;">
                                <i class="fa-solid fa-circle-info me-1 text-primary"></i>Nhập số điện thoại bạn đã dùng khi gửi yêu cầu để kiểm tra câu trả lời từ đội ngũ CSKH.
                            </small>
                        </div>

                        <!-- Khung danh sách câu trả lời của CSKH -->
                        <div id="customerInquiriesFeed" style="max-height: 420px; overflow-y: auto; padding-right: 4px;">
                            <div class="text-center py-4 text-muted small">
                                <i class="fa-regular fa-comments fs-3 text-secondary mb-2 d-block"></i>
                                Đang tải danh sách phản hồi từ đội ngũ CSKH...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL 2: HỆ THỐNG SHOWROOM & BẢN ĐỒ CHỈ ĐƯỜNG
     ========================================================================= -->
<div class="modal fade" id="modalShowrooms" tabindex="-1" aria-labelledby="modalShowroomsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header border-0 bg-dark text-white p-4 position-relative">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white text-warning d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px; flex-shrink: 0;">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#f59e0b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-1 text-white" id="modalShowroomsLabel">Hệ Thống Showroom V-Phone Store</h5>
                        <div class="small text-white text-opacity-75">Trải nghiệm trực tiếp các dòng Flagship hàng đầu 2026</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Đóng" style="opacity: 0.9; cursor: pointer;"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                <div class="row g-3">
                    <!-- Cơ sở 1: TP. Hồ Chí Minh -->
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold">FLAGSHIP STORE</span>
                                    <span class="text-success small fw-semibold"><i class="fa-solid fa-circle me-1" style="font-size: 8px;"></i>Mở cửa</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Showroom TP. Hồ Chí Minh</h6>
                                <p class="small text-muted mb-2">
                                    <i class="fa-solid fa-location-dot text-danger me-1"></i>123 Đường Công Nghệ, P. Bến Nghé, Quận 1
                                </p>
                                <div class="small text-muted mb-1"><i class="fa-regular fa-clock me-1"></i>08:00 - 21:30 (Mỗi ngày)</div>
                                <div class="small text-muted mb-3"><i class="fa-solid fa-phone me-1"></i>1800 6868 (Phím 1)</div>
                            </div>
                            <a href="https://maps.google.com/?q=123+Cong+Nghe+Ben+Nghe+District+1+Ho+Chi+Minh" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold d-flex align-items-center justify-content-center gap-1">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường Maps
                            </a>
                        </div>
                    </div>

                    <!-- Cơ sở 2: Hà Nội -->
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-info bg-opacity-10 text-info fw-bold">SHOWROOM MIỀN BẮC</span>
                                    <span class="text-success small fw-semibold"><i class="fa-solid fa-circle me-1" style="font-size: 8px;"></i>Mở cửa</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Showroom TP. Hà Nội</h6>
                                <p class="small text-muted mb-2">
                                    <i class="fa-solid fa-location-dot text-danger me-1"></i>456 Phố Điện Tử, P. Dịch Vọng Hậu, Q. Cầu Giấy
                                </p>
                                <div class="small text-muted mb-1"><i class="fa-regular fa-clock me-1"></i>08:00 - 21:30 (Mỗi ngày)</div>
                                <div class="small text-muted mb-3"><i class="fa-solid fa-phone me-1"></i>1800 6868 (Phím 2)</div>
                            </div>
                            <a href="https://maps.google.com/?q=456+Pho+Dien+Tu+Cau+Giay+Ha+Noi" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold d-flex align-items-center justify-content-center gap-1">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường Maps
                            </a>
                        </div>
                    </div>

                    <!-- Cơ sở 3: Đà Nẵng -->
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-warning bg-opacity-10 text-warning fw-bold">SHOWROOM MIỀN TRUNG</span>
                                    <span class="text-success small fw-semibold"><i class="fa-solid fa-circle me-1" style="font-size: 8px;"></i>Mở cửa</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Showroom TP. Đà Nẵng</h6>
                                <p class="small text-muted mb-2">
                                    <i class="fa-solid fa-location-dot text-danger me-1"></i>789 Đại Lộ Kỹ Thuật, Quận Hải Châu
                                </p>
                                <div class="small text-muted mb-1"><i class="fa-regular fa-clock me-1"></i>08:00 - 21:30 (Mỗi ngày)</div>
                                <div class="small text-muted mb-3"><i class="fa-solid fa-phone me-1"></i>1800 6868 (Phím 3)</div>
                            </div>
                            <a href="https://maps.google.com/?q=789+Dai+Lo+Ky+Thuat+Hai+Chau+Da+Nang" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold d-flex align-items-center justify-content-center gap-1">
                                <i class="fa-solid fa-diamond-turn-right"></i> Chỉ đường Maps
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-white rounded-3 border d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="small text-muted">
                        <i class="fa-solid fa-circle-check text-success me-1"></i> Đầy đủ chỗ đỗ xe ô tô, trải nghiệm máy thực tế không giới hạn thời gian.
                    </div>
                    <a href="tel:18006868" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                        Hotline 1800 6868
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
