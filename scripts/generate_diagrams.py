import os
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import matplotlib.patches as patches

os.makedirs('reports', exist_ok=True)
plt.rcParams['font.family'] = 'DejaVu Sans'
plt.rcParams['font.size'] = 9.5

def create_architecture_diagram():
    fig, ax = plt.subplots(figsize=(10, 6.2), dpi=220)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')
    
    ax.text(50, 96, "KIẾN TRÚC PHÂN TẦNG HỆ THỐNG V-PHONE STORE (PHP MVC)", 
            ha='center', va='center', fontsize=12.5, fontweight='bold', color='#0f172a')
    
    tiers = [
        {"y": 79, "h": 12, "color": "#e0f2fe", "border": "#0284c7", "title": "1. PRESENTATION TIER (FRONTEND UI & STOREFRONT)", 
         "desc": "Responsive Bootstrap 5.3 | Cyber Dark Mode / Light Mode | JS Live Search Debounce | FontAwesome Pro"},
        {"y": 63, "h": 12, "color": "#fef3c7", "border": "#d97706", "title": "2. ROUTING & CONTROLLER TIER (MVC FRONT CONTROLLER)", 
         "desc": "index.php Router | AuthController | CartController | HomeController | ProductController | OrderController"},
        {"y": 47, "h": 12, "color": "#dcfce7", "border": "#16a34a", "title": "3. SERVICE & FINTECH INTEGRATION TIER", 
         "desc": "VietQRService (Dynamic QR) | MoMoService (API v2) | MembershipService (VIP) | Multi-Role AI Neural Engine"},
        {"y": 31, "h": 12, "color": "#ede9fe", "border": "#7c3aed", "title": "4. DATA ACCESS OBJECT TIER (DAO / MODELS)", 
         "desc": "ProductModel | OrderModel | UserModel | ReviewModel | VoucherModel | WarrantyModel | AddressModel | NotificationModel"},
        {"y": 15, "h": 12, "color": "#f1f5f9", "border": "#475569", "title": "5. PERSISTENCE STORAGE TIER (DATABASE ENGINE)", 
         "desc": "MySQL 8.0+ / MariaDB 10.4+ | Engine InnoDB (ACID) | 13 Bảng Quan Hệ Khóa Ngoại | Bảng Mã utf8mb4_unicode_ci"}
    ]
    
    for t in tiers:
        rect = patches.FancyBboxPatch((4, t['y']), 92, t['h'], boxstyle="round,pad=0.8", 
                                      facecolor=t['color'], edgecolor=t['border'], linewidth=1.5)
        ax.add_patch(rect)
        ax.text(50, t['y'] + 7.5, t['title'], ha='center', va='center', fontsize=10, fontweight='bold', color='#1e293b')
        ax.text(50, t['y'] + 3.2, t['desc'], ha='center', va='center', fontsize=8.5, color='#334155')
        
    for y_arr in [78.5, 62.5, 46.5, 30.5]:
        ax.annotate('', xy=(50, y_arr - 2.5), xytext=(50, y_arr + 0.5),
                    arrowprops=dict(arrowstyle="->", color="#0284c7", lw=1.8))
        
    ax.text(50, 5, "Mô hình kiến trúc 5 tầng hướng đối tượng đảm bảo tính module hóa, bảo mật và khả năng mở rộng cao", 
            ha='center', va='center', fontsize=8.5, style='italic', color='#64748b')
    
    plt.tight_layout()
    fig.savefig('reports/diagram_architecture.png', dpi=220, bbox_inches='tight')
    plt.close()
    print("Created reports/diagram_architecture.png")


def create_directory_diagram():
    fig, ax = plt.subplots(figsize=(10.5, 6.5), dpi=220)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')
    
    ax.text(50, 96, "CƠ CẤU TỔ CHỨC THƯ MỤC MÃ NGUỒN DỰ ÁN PHONE-STORE", 
            ha='center', va='center', fontsize=12.5, fontweight='bold', color='#0f172a')
    
    # Root box
    root_rect = patches.FancyBboxPatch((35, 84), 30, 8, boxstyle="round,pad=0.5", 
                                       facecolor="#1e293b", edgecolor="#0f172a", linewidth=1.5)
    ax.add_patch(root_rect)
    ax.text(50, 88, "vphone-store/ (Mã nguồn dự án)", ha='center', va='center', fontsize=10.5, fontweight='bold', color='#ffffff')
    
    modules = [
        {"x": 3, "y": 48, "w": 21, "h": 28, "color": "#eff6ff", "border": "#3b82f6", "title": "app/\n(Lõi MVC)", 
         "items": ["controllers/ (7 files)", "models/ (9 files)", "services/ (5 files)", "views/ (18 files)", "data/"]},
        {"x": 26, "y": 48, "w": 22, "h": 28, "color": "#fef2f2", "border": "#ef4444", "title": "admin/\n(Quản trị & RBAC)", 
         "items": ["index.php (Dashboard)", "staff.php (Nhân sự)", "inventory.php (Kho)", "orders.php (Đơn hàng)", "ai_assistants.php (AI Hub)", "includes/permissions.php"]},
        {"x": 50, "y": 48, "w": 23, "h": 28, "color": "#ecfdf5", "border": "#10b981", "title": "api/\n(AJAX & Webhook)", 
         "items": ["role_ai.php (6 Roles)", "chatbot.php (Bán hàng)", "vietqr-webhook.php", "momo-ipn.php", "search.php (Live Search)", "notifications.php"]},
        {"x": 75, "y": 48, "w": 22, "h": 28, "color": "#faf5ff", "border": "#a855f7", "title": "config/ & database/\n(Hạ tầng dữ liệu)", 
         "items": ["database.php (Auto-sync)", "vietqr.php (Cấu hình MB)", "momo.php (Sandbox v2)", "db_phone_store.sql (13 bảng)", "db_sync_tool.php"]}
    ]
    
    for m in modules:
        rect = patches.FancyBboxPatch((m['x'], m['y']), m['w'], m['h'], boxstyle="round,pad=0.5", 
                                      facecolor=m['color'], edgecolor=m['border'], linewidth=1.4)
        ax.add_patch(rect)
        ax.text(m['x'] + m['w']/2, m['y'] + m['h'] - 4, m['title'], ha='center', va='center', 
                fontsize=9.5, fontweight='bold', color='#1e293b')
        
        y_text = m['y'] + m['h'] - 9
        for itm in m['items']:
            ax.text(m['x'] + 1.2, y_text, f"• {itm}", ha='left', va='center', fontsize=7.8, color='#334155')
            y_text -= 3.6
            
    # Connecting lines from Root
    for target_x in [13.5, 37, 61.5, 86]:
        ax.plot([50, target_x], [84, 76.5], color='#64748b', lw=1.2, linestyle='--')
        
    # Bottom modules (assets, scripts, index.php)
    bottom_modules = [
        {"x": 5, "y": 14, "w": 28, "h": 24, "color": "#fffbeb", "border": "#f59e0b", "title": "assets/ (Tài nguyên tĩnh)", 
         "items": ["css/ (style.css, role_ai.css)", "js/ (main.js, role_ai.js)", "images/products/ (Flagship)", "images/brands/ (9 Hãng)", "images/vphone-logo.png"]},
        {"x": 36, "y": 14, "w": 28, "h": 24, "color": "#f8fafc", "border": "#64748b", "title": "scripts/ (DevOps Tự động)", 
         "items": ["sync_push.sh (Đẩy code+DB)", "sync_pull.sh (Kéo code+DB)", "sync_pull.bat (Windows Lab)", "make_transparent_single.py (PIL)"]},
        {"x": 67, "y": 14, "w": 28, "h": 24, "color": "#f0fdfa", "border": "#14b8a6", "title": "index.php & Entry Points", 
         "items": ["index.php (Front Controller Router)", "Phân tích ?page= & ?action=", "Xử lý Session & Cookie", "Bảo vệ truy cập tập trung"]}
    ]
    
    for bm in bottom_modules:
        rect = patches.FancyBboxPatch((bm['x'], bm['y']), bm['w'], bm['h'], boxstyle="round,pad=0.5", 
                                      facecolor=bm['color'], edgecolor=bm['border'], linewidth=1.4)
        ax.add_patch(rect)
        ax.text(bm['x'] + bm['w']/2, bm['y'] + bm['h'] - 3.8, bm['title'], ha='center', va='center', 
                fontsize=9.2, fontweight='bold', color='#1e293b')
        y_text = bm['y'] + bm['h'] - 8.5
        for itm in bm['items']:
            ax.text(bm['x'] + 1.2, y_text, f"• {itm}", ha='left', va='center', fontsize=7.8, color='#334155')
            y_text -= 3.6
            
    ax.text(50, 4, "Cấu trúc thư mục phân tách độc lập giữa Tầng Ứng Dụng (App), Quản Trị (Admin), API và Tài nguyên (Assets)", 
            ha='center', va='center', fontsize=8.5, style='italic', color='#64748b')
    
    plt.tight_layout()
    fig.savefig('reports/diagram_directory_structure.png', dpi=220, bbox_inches='tight')
    plt.close()
    print("Created reports/diagram_directory_structure.png")


def create_erd_diagram():
    fig, ax = plt.subplots(figsize=(11, 7.2), dpi=220)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')
    
    ax.text(50, 97, "MÔ HÌNH QUAN HỆ THỰC THỂ CƠ SỞ DỮ LIỆU (DATABASE ERD - 13 BẢNG INNODB)", 
            ha='center', va='center', fontsize=12, fontweight='bold', color='#0f172a')
    
    tables = [
        # Col 1
        {"x": 3, "y": 66, "w": 28, "h": 26, "name": "users", "color": "#dbeafe", "border": "#1d4ed8",
         "cols": ["PK id (INT)", "fullname, email, phone", "password (Bcrypt hash)", "role (0:Khách, 1:Admin, 2:Staff)", "staff_role, permissions (JSON)", "membership_tier, cart_data"]},
        {"x": 3, "y": 36, "w": 28, "h": 24, "name": "user_addresses", "color": "#eff6ff", "border": "#3b82f6",
         "cols": ["PK id (INT)", "FK user_id -> users(id)", "recipient_name, phone", "address, label, is_default"]},
        {"x": 3, "y": 9, "w": 28, "h": 22, "name": "user_notifications", "color": "#eff6ff", "border": "#3b82f6",
         "cols": ["PK id (INT)", "FK user_id, order_id", "title, message, type", "is_read, cancel_reason"]},
        
        # Col 2
        {"x": 36, "y": 66, "w": 28, "h": 26, "name": "orders", "color": "#fef3c7", "border": "#b45309",
         "cols": ["PK id (INT)", "FK user_id -> users(id)", "fullname, phone, address", "total_money, payment_method", "payment_status, transaction_code", "status, paid_at, created_at"]},
        {"x": 36, "y": 36, "w": 28, "h": 24, "name": "order_details", "color": "#fef3c7", "border": "#b45309",
         "cols": ["PK id (INT)", "FK order_id -> orders(id) CASCADE", "FK product_id -> products(id)", "product_name, price, quantity", "total_price"]},
        {"x": 36, "y": 9, "w": 28, "h": 22, "name": "warranty_claims", "color": "#ecfdf5", "border": "#047857",
         "cols": ["PK id (INT)", "FK user_id, order_id, product_id", "imei, customer_name, phone", "issue_type, status, admin_note"]},
        
        # Col 3
        {"x": 69, "y": 66, "w": 28, "h": 26, "name": "products", "color": "#dcfce7", "border": "#15803d",
         "cols": ["PK id (INT)", "FK brand_id -> brands(id)", "name, price, sale_price, image", "screen, cpu, ram, rom, battery", "colors, storage_options (JSON)", "quantity, color_quantities (JSON)"]},
        {"x": 69, "y": 42, "w": 28, "h": 18, "name": "brands", "color": "#dcfce7", "border": "#15803d",
         "cols": ["PK id (INT)", "name (Apple, Samsung,...)"]},
        {"x": 69, "y": 17, "w": 28, "h": 20, "name": "product_reviews", "color": "#fae8ff", "border": "#86198f",
         "cols": ["PK id (INT)", "FK product_id -> products(id)", "FK user_id, order_id", "rating (1-5), comment, is_buyer", "status, admin_reply"]},
    ]
    
    for t in tables:
        rect = patches.FancyBboxPatch((t['x'], t['y']), t['w'], t['h'], boxstyle="round,pad=0.4",
                                      facecolor=t['color'], edgecolor=t['border'], linewidth=1.4)
        ax.add_patch(rect)
        # Header banner
        hdr = patches.Rectangle((t['x'], t['y'] + t['h'] - 4.5), t['w'], 4.5, 
                                facecolor=t['border'], edgecolor='none')
        ax.add_patch(hdr)
        ax.text(t['x'] + t['w']/2, t['y'] + t['h'] - 2.3, f"TABLE: {t['name']}", 
                ha='center', va='center', fontsize=9, fontweight='bold', color='#ffffff')
        
        y_col = t['y'] + t['h'] - 7.5
        for col in t['cols']:
            ax.text(t['x'] + 1, y_col, f"• {col}", ha='left', va='center', fontsize=7.4, color='#1e293b')
            y_col -= 3.2
            
    # Foreign key relationship connectors
    # users -> orders
    ax.annotate("", xy=(36, 79), xytext=(31, 79), arrowprops=dict(arrowstyle="->", color="#1d4ed8", lw=1.5))
    # users -> user_addresses
    ax.annotate("", xy=(17, 60), xytext=(17, 66), arrowprops=dict(arrowstyle="->", color="#1d4ed8", lw=1.5))
    # orders -> order_details
    ax.annotate("", xy=(50, 60), xytext=(50, 66), arrowprops=dict(arrowstyle="->", color="#b45309", lw=1.5))
    # products -> order_details
    ax.annotate("", xy=(64, 48), xytext=(69, 72), arrowprops=dict(arrowstyle="->", color="#15803d", lw=1.5, connectionstyle="arc3,rad=-0.2"))
    # brands -> products
    ax.annotate("", xy=(83, 66), xytext=(83, 60), arrowprops=dict(arrowstyle="->", color="#15803d", lw=1.5))
    # products -> product_reviews
    ax.annotate("", xy=(83, 37), xytext=(83, 42), arrowprops=dict(arrowstyle="->", color="#86198f", lw=1.5))
    # orders -> warranty_claims
    ax.annotate("", xy=(50, 31), xytext=(50, 36), arrowprops=dict(arrowstyle="->", color="#047857", lw=1.5))
    
    ax.text(50, 3, "Sơ đồ liên kết khóa ngoại (Foreign Keys) bảo đảm tính toàn vẹn dữ liệu (Referential Integrity) theo chuẩn InnoDB", 
            ha='center', va='center', fontsize=8.5, style='italic', color='#64748b')
    
    plt.tight_layout()
    fig.savefig('reports/diagram_erd.png', dpi=220, bbox_inches='tight')
    plt.close()
    print("Created reports/diagram_erd.png")


def create_mvc_dataflow_diagram():
    fig, ax = plt.subplots(figsize=(10.5, 5.5), dpi=220)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')
    
    ax.text(50, 95, "CHU TRÌNH XỬ LÝ YÊU CẦU THEO KIẾN TRÚC MVC THUẦN (REQUEST FLOW)", 
            ha='center', va='center', fontsize=12, fontweight='bold', color='#0f172a')
            
    steps = [
        {"x": 3, "w": 16, "title": "1. CLIENT BROWSER", "desc": "Người dùng gửi\nHTTP Request\n(?page=...&action=...)", "color": "#e0f2fe", "border": "#0284c7"},
        {"x": 22.5, "w": 16, "title": "2. ROUTER\n(index.php)", "desc": "Front Controller\nPhân tích Request\nĐiều hướng Controller", "color": "#fef3c7", "border": "#d97706"},
        {"x": 42, "w": 16, "title": "3. CONTROLLER\n(app/controllers)", "desc": "Xử lý Logic nghiệp vụ\nKiểm tra phân quyền\nGọi Service & Model", "color": "#dcfce7", "border": "#16a34a"},
        {"x": 61.5, "w": 16, "title": "4. MODEL & PDO\n(app/models)", "desc": "Truy vấn CSDL an toàn\nPDO Prepared Stmt\nMySQL 8.0 InnoDB", "color": "#ede9fe", "border": "#7c3aed"},
        {"x": 81, "w": 16, "title": "5. VIEW RENDER\n(app/views)", "desc": "Tổng hợp dữ liệu\nRender HTML/CSS/JS\nPhản hồi người dùng", "color": "#ffe4e6", "border": "#e11d48"}
    ]
    
    for s in steps:
        rect = patches.FancyBboxPatch((s['x'], 38), s['w'], 44, boxstyle="round,pad=0.6",
                                      facecolor=s['color'], edgecolor=s['border'], linewidth=1.5)
        ax.add_patch(rect)
        ax.text(s['x'] + s['w']/2, 74, s['title'], ha='center', va='center', fontsize=9, fontweight='bold', color='#1e293b')
        ax.text(s['x'] + s['w']/2, 54, s['desc'], ha='center', va='center', fontsize=8, color='#334155')
        
    for i in range(4):
        x1 = steps[i]['x'] + steps[i]['w'] + 0.5
        x2 = steps[i+1]['x'] - 0.5
        ax.annotate('', xy=(x2, 60), xytext=(x1, 60),
                    arrowprops=dict(arrowstyle="->", color="#0f172a", lw=2))
        
    # Return response arrow from View back to Client Browser
    ax.annotate('', xy=(11, 35), xytext=(89, 35),
                arrowprops=dict(arrowstyle="->", color="#0284c7", lw=1.8, linestyle='dashed',
                                connectionstyle="arc3,rad=0.25"))
    ax.text(50, 18, "HTTP Response: Trả về mã HTML hoàn chỉnh cùng giao diện Cyber Dark Mode (< 0.8s)", 
            ha='center', va='center', fontsize=8.5, fontweight='bold', color='#0284c7')
    
    ax.text(50, 5, "Mô hình Front Controller tập trung toàn bộ điểm vào tại index.php, đảm bảo an toàn và kiểm soát phiên chặt chẽ", 
            ha='center', va='center', fontsize=8.2, style='italic', color='#64748b')
            
    plt.tight_layout()
    fig.savefig('reports/diagram_dataflow_mvc.png', dpi=220, bbox_inches='tight')
    plt.close()
    print("Created reports/diagram_dataflow_mvc.png")


def create_fintech_flow_diagram():
    fig, ax = plt.subplots(figsize=(10.5, 5.8), dpi=220)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')
    
    ax.text(50, 95, "QUY TRÌNH THANH TOÁN TỰ ĐỘNG VIETQR NAPAS247 & MOMO SANDBOX API v2", 
            ha='center', va='center', fontsize=12, fontweight='bold', color='#0f172a')
            
    boxes = [
        {"x": 3, "y": 42, "w": 20, "h": 40, "color": "#e0f2fe", "border": "#0284c7", "title": "1. KHÁCH HÀNG", 
         "desc": "• Chọn VietQR / MoMo\n• Xác nhận đặt hàng\n• Mở App Ngân hàng\n• Quét mã QR thanh toán"},
        {"x": 26, "y": 42, "w": 22, "h": 40, "color": "#fef3c7", "border": "#d97706", "title": "2. V-PHONE STORE", 
         "desc": "• VietQRService sinh mã\n• Cú pháp: VPHONE <id>\n• Render QR Napas247\n• Đếm ngược 15 phút"},
        {"x": 51, "y": 42, "w": 22, "h": 40, "color": "#ecfdf5", "border": "#10b981", "title": "3. CỔNG THANH TOÁN", 
         "desc": "• Napas247 / MB Bank\n• MoMo Payment Gateway\n• Xác thực giao dịch\n• Bắn Webhook biến động"},
        {"x": 76, "y": 42, "w": 21, "h": 40, "color": "#ede9fe", "border": "#7c3aed", "title": "4. WEBHOOK IPN", 
         "desc": "• /api/vietqr-webhook\n• /api/momo-ipn\n• Cập nhật 'Đã thanh toán'\n• Hoàn tất đơn hàng"}
    ]
    
    for b in boxes:
        rect = patches.FancyBboxPatch((b['x'], b['y']), b['w'], b['h'], boxstyle="round,pad=0.6",
                                      facecolor=b['color'], edgecolor=b['border'], linewidth=1.5)
        ax.add_patch(rect)
        ax.text(b['x'] + b['w']/2, b['y'] + b['h'] - 6, b['title'], ha='center', va='center', fontsize=9.2, fontweight='bold', color='#1e293b')
        ax.text(b['x'] + 1.2, b['y'] + b['h'] - 22, b['desc'], ha='left', va='center', fontsize=8, color='#334155')
        
    for i in range(3):
        x1 = boxes[i]['x'] + boxes[i]['w'] + 0.5
        x2 = boxes[i+1]['x'] - 0.5
        ax.annotate('', xy=(x2, 62), xytext=(x1, 62),
                    arrowprops=dict(arrowstyle="->", color="#0f172a", lw=2))
        
    # Return auto confirmation
    ax.annotate('', xy=(13, 38), xytext=(86.5, 38),
                arrowprops=dict(arrowstyle="->", color="#10b981", lw=1.8, linestyle='dashed',
                                connectionstyle="arc3,rad=0.25"))
    ax.text(50, 20, "Tự động kích hoạt Webhook: Màn hình trình duyệt nhận tín hiệu realtime chuyển trạng thái thành công!", 
            ha='center', va='center', fontsize=8.5, fontweight='bold', color='#047857')
            
    ax.text(50, 6, "Hệ thống hỗ trợ cơ chế Demo Simulator cho phép kiểm thử thanh toán 1-click tức thì trong môi trường phát triển", 
            ha='center', va='center', fontsize=8.2, style='italic', color='#64748b')
            
    plt.tight_layout()
    fig.savefig('reports/diagram_fintech_flow.png', dpi=220, bbox_inches='tight')
    plt.close()
    print("Created reports/diagram_fintech_flow.png")


def create_role_ai_diagram():
    fig, ax = plt.subplots(figsize=(10.5, 6.2), dpi=220)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')
    
    ax.text(50, 96, "HỆ THỐNG TRỢ LÝ TRÍ TUỆ NHÂN TẠO ĐA VAI TRÒ (MULTI-ROLE AI NEURAL ENGINE)", 
            ha='center', va='center', fontsize=12, fontweight='bold', color='#0f172a')
            
    # Central Engine
    c_rect = patches.FancyBboxPatch((35, 35), 30, 30, boxstyle="round,pad=0.8",
                                    facecolor="#1e293b", edgecolor="#0f172a", linewidth=2)
    ax.add_patch(c_rect)
    ax.text(50, 56, "AI NEURAL CONTEXT\nENGINE\n(/api/role_ai.php)", ha='center', va='center', 
            fontsize=10.5, fontweight='bold', color='#38bdf8')
    ax.text(50, 43, "Truy vấn CSDL Realtime:\n• Doanh thu ngày/tháng\n• Cảnh báo tồn kho <= 5\n• Tra cứu IMEI bảo hành\n• Phân loại Prompt nghiệp vụ", 
            ha='center', va='center', fontsize=7.6, color='#cbd5e1')
            
    # 6 Roles around
    roles = [
        {"x": 4, "y": 68, "title": "ADMIN AI", "sub": "Báo cáo doanh thu & chiến lược", "color": "#fee2e2", "border": "#dc2626"},
        {"x": 4, "y": 38, "title": "SALES AI", "sub": "Tư vấn chốt đơn & Up-sell", "color": "#fef3c7", "border": "#d97706"},
        {"x": 4, "y": 8, "title": "WAREHOUSE AI", "sub": "Kiểm kê tồn kho & IMEI", "color": "#ffedd5", "border": "#ea580c"},
        {"x": 68, "y": 68, "title": "WARRANTY AI", "sub": "Chẩn đoán lỗi & Tra IMEI", "color": "#ecfdf5", "border": "#059669"},
        {"x": 68, "y": 38, "title": "SUPPORT AI", "sub": "Kịch bản CSKH & Khiếu nại", "color": "#e0e7ff", "border": "#4f46e5"},
        {"x": 68, "y": 8, "title": "CUSTOMER AI", "sub": "Tư vấn Flagship 24/7 & Trả góp", "color": "#fae8ff", "border": "#c026d3"},
    ]
    
    for r in roles:
        rect = patches.FancyBboxPatch((r['x'], r['y']), 28, 22, boxstyle="round,pad=0.6",
                                      facecolor=r['color'], edgecolor=r['border'], linewidth=1.4)
        ax.add_patch(rect)
        ax.text(r['x'] + 14, r['y'] + 15, r['title'], ha='center', va='center', fontsize=9.2, fontweight='bold', color='#1e293b')
        ax.text(r['x'] + 14, r['y'] + 7.5, r['sub'], ha='center', va='center', fontsize=7.8, color='#334155')
        
    # Connectors
    # Left roles to center
    ax.annotate('', xy=(35, 58), xytext=(32, 76), arrowprops=dict(arrowstyle="<->", color="#dc2626", lw=1.4))
    ax.annotate('', xy=(35, 50), xytext=(32, 49), arrowprops=dict(arrowstyle="<->", color="#d97706", lw=1.4))
    ax.annotate('', xy=(35, 42), xytext=(32, 22), arrowprops=dict(arrowstyle="<->", color="#ea580c", lw=1.4))
    # Right roles to center
    ax.annotate('', xy=(65, 58), xytext=(68, 76), arrowprops=dict(arrowstyle="<->", color="#059669", lw=1.4))
    ax.annotate('', xy=(65, 50), xytext=(68, 49), arrowprops=dict(arrowstyle="<->", color="#4f46e5", lw=1.4))
    ax.annotate('', xy=(65, 42), xytext=(68, 22), arrowprops=dict(arrowstyle="<->", color="#c026d3", lw=1.4))
    
    ax.text(50, 4, "Kiến trúc AI phân quyền theo ma trận RBAC: Nhân viên chỉ được tương tác với AI đúng thẩm quyền nghiệp vụ", 
            ha='center', va='center', fontsize=8.5, style='italic', color='#64748b')
            
    plt.tight_layout()
    fig.savefig('reports/diagram_role_ai_flow.png', dpi=220, bbox_inches='tight')
    plt.close()
    print("Created reports/diagram_role_ai_flow.png")


def create_devops_diagram():
    fig, ax = plt.subplots(figsize=(10.5, 5.8), dpi=220)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')
    
    ax.text(50, 95, "QUY TRÌNH ĐỒNG BỘ MÃ NGUỒN VÀ CƠ SỞ DỮ LIỆU ĐA NỀN TẢNG (DEVOPS SYNC)", 
            ha='center', va='center', fontsize=12, fontweight='bold', color='#0f172a')
            
    nodes = [
        {"x": 4, "y": 42, "w": 26, "h": 42, "color": "#eff6ff", "border": "#3b82f6", "title": "MÔI TRƯỜNG DEV CÁ NHÂN\n(macOS XAMPP)", 
         "desc": "• Lập trình tính năng mới\n• Chạy ./scripts/sync_push.sh\n• db_sync_tool.php tự động Dump CSDL ra file SQL\n• Git auto-commit & push"},
        {"x": 37, "y": 42, "w": 26, "h": 42, "color": "#f1f5f9", "border": "#475569", "title": "KHO CHỨA TRUNG TÂM\n(GitHub Repository)", 
         "desc": "• Lưu trữ toàn bộ mã nguồn\n• Versioning lịch sử commit\n• Tự động lưu bản sao lưu CSDL:\n  database/db_phone_store.sql\n• Branch: main"},
        {"x": 70, "y": 42, "w": 26, "h": 42, "color": "#fef2f2", "border": "#ef4444", "title": "MÁY THỰC HÀNH PHÒNG LAB\n(Windows PC / Trường học)", 
         "desc": "• Double click sync_pull.bat\n• Git pull mã nguồn mới nhất\n• db_sync_tool.php tự động nạp CSDL vào MySQL\n• Sẵn sàng báo cáo đồ án!"}
    ]
    
    for n in nodes:
        rect = patches.FancyBboxPatch((n['x'], n['y']), n['w'], n['h'], boxstyle="round,pad=0.6",
                                      facecolor=n['color'], edgecolor=n['border'], linewidth=1.5)
        ax.add_patch(rect)
        ax.text(n['x'] + n['w']/2, n['y'] + n['h'] - 6, n['title'], ha='center', va='center', fontsize=9, fontweight='bold', color='#1e293b')
        ax.text(n['x'] + 1.2, n['y'] + n['h'] - 23, n['desc'], ha='left', va='center', fontsize=7.8, color='#334155')
        
    # Push Arrow
    ax.annotate('', xy=(36.5, 67), xytext=(30.5, 67),
                arrowprops=dict(arrowstyle="->", color="#3b82f6", lw=2.2))
    ax.text(33.5, 71, "git push\n+ DB export", ha='center', va='center', fontsize=7.8, fontweight='bold', color='#3b82f6')
    
    # Pull Arrow
    ax.annotate('', xy=(69.5, 67), xytext=(63.5, 67),
                arrowprops=dict(arrowstyle="->", color="#ef4444", lw=2.2))
    ax.text(66.5, 71, "git pull\n+ DB import", ha='center', va='center', fontsize=7.8, fontweight='bold', color='#ef4444')
    
    # Bottom Note Box
    note_rect = patches.FancyBboxPatch((15, 12), 70, 20, boxstyle="round,pad=0.5",
                                       facecolor="#ecfdf5", edgecolor="#10b981", linewidth=1.2)
    ax.add_patch(note_rect)
    ax.text(50, 25, "GIẢI QUYẾT TRIỆT ĐỂ BÀI TOÁN XUNG ĐỘT CƠ SỞ DỮ LIỆU KHI LÀM VIỆC NHÓM", 
            ha='center', va='center', fontsize=8.8, fontweight='bold', color='#047857')
    ax.text(50, 18, "Bộ công cụ DatabaseSyncTool cho phép đồng bộ dữ liệu hai chiều chỉ với một lệnh duy nhất, loại bỏ hoàn toàn thao tác nạp xuất SQL thủ công qua phpMyAdmin.", 
            ha='center', va='center', fontsize=8, color='#065f46')
            
    plt.tight_layout()
    fig.savefig('reports/diagram_devops_sync.png', dpi=220, bbox_inches='tight')
    plt.close()
    print("Created reports/diagram_devops_sync.png")

if __name__ == '__main__':
    create_architecture_diagram()
    create_directory_diagram()
    create_erd_diagram()
    create_mvc_dataflow_diagram()
    create_fintech_flow_diagram()
    create_role_ai_diagram()
    create_devops_diagram()
    print("All 7 diagrams generated successfully!")
