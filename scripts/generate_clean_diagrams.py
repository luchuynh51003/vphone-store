import os
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import matplotlib.patches as patches

os.makedirs('reports', exist_ok=True)
plt.rcParams['font.family'] = 'DejaVu Sans'

def create_clean_architecture():
    fig, ax = plt.subplots(figsize=(10.5, 6.8), dpi=220)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')
    
    ax.text(50, 96, "KIẾN TRÚC PHÂN TẦNG HỆ THỐNG V-PHONE STORE (PHP MVC)", 
            ha='center', va='center', fontsize=13, fontweight='bold', color='#0f172a')
    
    tiers = [
        {"y": 79, "h": 12, "color": "#f0f9ff", "border": "#0284c7", 
         "title": "TẦNG 1: PRESENTATION & STOREFRONT UI (CYBER DARK MODE & LIGHT MODE)", 
         "desc": "Responsive Bootstrap 5.3  •  Cyber Dark Theme (#0b1329)  •  Live Search Debounce  •  CSS Variables"},
        {"y": 62, "h": 12, "color": "#fefce8", "border": "#ca8a04", 
         "title": "TẦNG 2: ROUTING & FRONT CONTROLLER (index.php & CONTROLLERS)", 
         "desc": "index.php Dispatcher  •  AuthController  •  CartController  •  HomeController  •  ProductController  •  OrderController"},
        {"y": 45, "h": 12, "color": "#f0fdf4", "border": "#16a34a", 
         "title": "TẦNG 3: BUSINESS SERVICES, FINTECH & MULTI-ROLE AI ENGINE", 
         "desc": "VietQRService (Dynamic QR Napas247)  •  MoMoService (Sandbox v2)  •  MembershipService  •  Multi-Role AI (6 Roles)"},
        {"y": 28, "h": 12, "color": "#faf5ff", "border": "#9333ea", 
         "title": "TẦNG 4: DATA ACCESS OBJECT TIER (9 PDO MODELS & TRANSACTIONS)", 
         "desc": "ProductModel  •  OrderModel  •  UserModel  •  ReviewModel  •  VoucherModel  •  WarrantyModel  •  AddressModel"},
        {"y": 11, "h": 12, "color": "#f8fafc", "border": "#475569", 
         "title": "TẦNG 5: PERSISTENCE STORAGE ENGINE (MYSQL 8.0 INNODB ACID)", 
         "desc": "13 Bảng quan hệ chuẩn 3NF  •  Ràng buộc khóa ngoại (Foreign Keys)  •  Bảng mã tiếng Việt utf8mb4_unicode_ci"}
    ]
    
    for t in tiers:
        rect = patches.FancyBboxPatch((3, t['y']), 94, t['h'], boxstyle="round,pad=0.8", 
                                      facecolor=t['color'], edgecolor=t['border'], linewidth=1.6)
        ax.add_patch(rect)
        ax.text(50, t['y'] + 7.6, t['title'], ha='center', va='center', fontsize=9.8, fontweight='bold', color='#0f172a')
        ax.text(50, t['y'] + 3.2, t['desc'], ha='center', va='center', fontsize=8.6, color='#334155')
        
    for y_top in [79, 62, 45, 28]:
        ax.annotate('', xy=(50, y_top - 5), xytext=(50, y_top),
                    arrowprops=dict(arrowstyle="->", color="#0284c7", lw=2))
        
    ax.text(50, 4, "Mô hình kiến trúc 5 tầng chuẩn hướng đối tượng (OOP) bảo đảm bảo mật tối đa và phân tách trách nhiệm rõ ràng", 
            ha='center', va='center', fontsize=8.8, style='italic', color='#64748b')
            
    plt.tight_layout()
    fig.savefig('reports/diagram_architecture.png', dpi=220, bbox_inches='tight')
    plt.close()
    print("Created clean diagram_architecture.png")


def create_clean_dataflow():
    fig, ax = plt.subplots(figsize=(10.5, 6.0), dpi=220)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')
    
    ax.text(50, 95, "CHU TRÌNH XỬ LÝ YÊU CẦU THEO KIẾN TRÚC MVC THUẦN (REQUEST FLOW)", 
            ha='center', va='center', fontsize=12.5, fontweight='bold', color='#0f172a')
            
    # Row 1: Request path (Left to Right)
    steps_row1 = [
        {"x": 3, "w": 20, "title": "1. CLIENT BROWSER", "desc": "Người dùng gửi\nHTTP Request\n(?page=...&action=...)", "color": "#e0f2fe", "border": "#0284c7"},
        {"x": 28, "w": 20, "title": "2. ROUTER (index.php)", "desc": "Front Controller\nPhân tích Request\nKhởi tạo phiên Session", "color": "#fef3c7", "border": "#d97706"},
        {"x": 53, "w": 20, "title": "3. CONTROLLER", "desc": "Điều hướng nghiệp vụ\nKiểm tra phân quyền\nGọi Service & Model", "color": "#dcfce7", "border": "#16a34a"},
        {"x": 78, "w": 19, "title": "4. MODEL & PDO", "desc": "Prepared Statements\nTruy vấn CSDL an toàn\nChống SQL Injection", "color": "#ede9fe", "border": "#7c3aed"}
    ]
    
    for s in steps_row1:
        rect = patches.FancyBboxPatch((s['x'], 55), s['w'], 30, boxstyle="round,pad=0.6",
                                      facecolor=s['color'], edgecolor=s['border'], linewidth=1.5)
        ax.add_patch(rect)
        ax.text(s['x'] + s['w']/2, 77, s['title'], ha='center', va='center', fontsize=9.2, fontweight='bold', color='#1e293b')
        ax.text(s['x'] + s['w']/2, 64, s['desc'], ha='center', va='center', fontsize=8.2, color='#334155')
        
    for i in range(3):
        x1 = steps_row1[i]['x'] + steps_row1[i]['w'] + 0.5
        x2 = steps_row1[i+1]['x'] - 0.5
        ax.annotate('', xy=(x2, 70), xytext=(x1, 70),
                    arrowprops=dict(arrowstyle="->", color="#0f172a", lw=2))
        
    # Down arrow to Database
    ax.annotate('', xy=(87.5, 42), xytext=(87.5, 55),
                arrowprops=dict(arrowstyle="->", color="#7c3aed", lw=2))
    ax.text(87.5, 48.5, "SQL", ha='center', va='center', fontsize=8, fontweight='bold', color='#7c3aed')

    # Row 2: Return Response path (Right to Left)
    steps_row2 = [
        {"x": 78, "w": 19, "title": "5. DATABASE", "desc": "MySQL 8.0 InnoDB\nTrả về tập kết quả\n(Recordset / Array)", "color": "#f1f5f9", "border": "#475569"},
        {"x": 53, "w": 20, "title": "6. SERVICE PACK", "desc": "Đóng gói dữ liệu\nÁp dụng Voucher\nTính chiết khấu VIP", "color": "#fef2f2", "border": "#ef4444"},
        {"x": 28, "w": 20, "title": "7. VIEW RENDER", "desc": "app/views/\nRender HTML/CSS\nCyber Dark Mode", "color": "#ecfdf5", "border": "#059669"},
        {"x": 3, "w": 20, "title": "8. CLIENT DISPLAY", "desc": "Trình duyệt hiển thị\nGiao diện mượt mà\nThời gian < 0.8s", "color": "#eff6ff", "border": "#3b82f6"}
    ]
    
    for s in steps_row2:
        rect = patches.FancyBboxPatch((s['x'], 12), s['w'], 30, boxstyle="round,pad=0.6",
                                      facecolor=s['color'], edgecolor=s['border'], linewidth=1.5)
        ax.add_patch(rect)
        ax.text(s['x'] + s['w']/2, 34, s['title'], ha='center', va='center', fontsize=9.2, fontweight='bold', color='#1e293b')
        ax.text(s['x'] + s['w']/2, 21, s['desc'], ha='center', va='center', fontsize=8.2, color='#334155')
        
    for i in range(3):
        x1 = steps_row2[i]['x'] - 0.5
        x2 = steps_row2[i+1]['x'] + steps_row2[i+1]['w'] + 0.5
        ax.annotate('', xy=(x2, 27), xytext=(x1, 27),
                    arrowprops=dict(arrowstyle="->", color="#0284c7", lw=2))
        
    ax.text(50, 4, "Quy trình xử lý khép kín 2 chiều không chồng lấn, bảo đảm hiệu năng cao và phân tách rõ ràng", 
            ha='center', va='center', fontsize=8.5, style='italic', color='#64748b')
            
    plt.tight_layout()
    fig.savefig('reports/diagram_dataflow_mvc.png', dpi=220, bbox_inches='tight')
    plt.close()
    print("Created clean diagram_dataflow_mvc.png")


def create_clean_devops():
    fig, ax = plt.subplots(figsize=(10.5, 5.2), dpi=220)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')
    
    ax.text(50, 94, "QUY TRÌNH ĐỒNG BỘ CODE & CSDL ĐA NỀN TẢNG (CROSS-PLATFORM DEVOPS)", 
            ha='center', va='center', fontsize=12.5, fontweight='bold', color='#0f172a')
            
    stages = [
        {"x": 4, "w": 27, "title": "MÁY DEV CÁ NHÂN\n(macOS XAMPP)", 
         "items": ["Lập trình tính năng mới", "Chạy: ./scripts/sync_push.sh", "db_sync_tool.php tự động Dump CSDL", "Git auto-commit & push"], 
         "color": "#eff6ff", "border": "#3b82f6"},
        {"x": 36.5, "w": 27, "title": "KHO CHỨA TRUNG TÂM\n(GitHub Repository)", 
         "items": ["Lưu trữ toàn bộ mã nguồn", "Quản lý versioning & commit", "Tự động sao lưu CSDL tại:", "database/db_phone_store.sql"], 
         "color": "#f8fafc", "border": "#475569"},
        {"x": 69, "w": 27, "title": "MÁY PHÒNG THỰC HÀNH\n(Windows PC / Trường học)", 
         "items": ["Chạy: sync_pull.bat (1-click)", "Git pull mã nguồn mới nhất", "db_sync_tool.php tự động nạp CSDL", "Sẵn sàng chạy báo cáo đồ án!"], 
         "color": "#fef2f2", "border": "#ef4444"}
    ]
    
    for s in stages:
        rect = patches.FancyBboxPatch((s['x'], 20), s['w'], 62, boxstyle="round,pad=0.6",
                                      facecolor=s['color'], edgecolor=s['border'], linewidth=1.6)
        ax.add_patch(rect)
        ax.text(s['x'] + s['w']/2, 73, s['title'], ha='center', va='center', fontsize=10, fontweight='bold', color='#1e293b')
        
        y_text = 58
        for itm in s['items']:
            ax.text(s['x'] + 1.5, y_text, f"• {itm}", ha='left', va='center', fontsize=8.6, color='#334155')
            y_text -= 7.5
            
    # Arrows
    ax.annotate('', xy=(36, 52), xytext=(31.5, 52),
                arrowprops=dict(arrowstyle="->", color="#3b82f6", lw=2.5))
    ax.text(33.7, 57, "git push\n+ DB export", ha='center', va='center', fontsize=8, fontweight='bold', color='#3b82f6')
    
    ax.annotate('', xy=(68.5, 52), xytext=(64, 52),
                arrowprops=dict(arrowstyle="->", color="#ef4444", lw=2.5))
    ax.text(66.2, 57, "git pull\n+ DB import", ha='center', va='center', fontsize=8, fontweight='bold', color='#ef4444')
    
    ax.text(50, 8, "Giải pháp DevOps tự động hóa giải quyết triệt để xung đột CSDL giữa macOS và Windows phòng thực hành", 
            ha='center', va='center', fontsize=8.8, style='italic', color='#64748b')
            
    plt.tight_layout()
    fig.savefig('reports/diagram_devops_sync.png', dpi=220, bbox_inches='tight')
    plt.close()
    print("Created clean diagram_devops_sync.png")


def create_darkmode_pipeline():
    fig, ax = plt.subplots(figsize=(10.5, 5.5), dpi=220)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')
    
    ax.text(50, 94, "KỸ THUẬT XỬ LÝ HÌNH ẢNH TRONG SUỐT VÀ KIẾN TRÚC CYBER DARK MODE", 
            ha='center', va='center', fontsize=12.5, fontweight='bold', color='#0f172a')
            
    boxes = [
        {"x": 4, "w": 27, "title": "1. ẢNH GỐC TỪ HÃNG\n(Nền trắng đặc thô ráp)", 
         "items": ["Ảnh thu thập từ Apple, Samsung...", "Định dạng JPEG/PNG có nền trắng", "Vấn đề: Lộ khung trắng hình chữ nhật", "khi đặt trên nền Cyber Dark Mode"], 
         "color": "#fef2f2", "border": "#dc2626"},
        {"x": 36.5, "w": 27, "title": "2. SCRIPT PYTHON PIL\n(scripts/make_transparent.py)", 
         "items": ["Quét 4 góc phát hiện màu nền", "Tính sai số RGB <= 35 điểm ảnh", "Thuật toán Flood Fill tách nền trắng", "Chuyển toàn bộ thành kênh Alpha rỗng"], 
         "color": "#fefce8", "border": "#ca8a04"},
        {"x": 69, "w": 27, "title": "3. KẾT QUẢ TRÊN WEB\n(Hòa quyện Cyber Dark)", 
         "items": ["Ảnh PNG trong suốt hoàn hảo", "Tự nhiên trên nền tối #0b1329", "CSS Variables data-bs-theme", "Tối ưu tương phản màn hình OLED"], 
         "color": "#ecfdf5", "border": "#059669"}
    ]
    
    for b in boxes:
        rect = patches.FancyBboxPatch((b['x'], 20), b['w'], 62, boxstyle="round,pad=0.6",
                                      facecolor=b['color'], edgecolor=b['border'], linewidth=1.6)
        ax.add_patch(rect)
        ax.text(b['x'] + b['w']/2, 73, b['title'], ha='center', va='center', fontsize=9.8, fontweight='bold', color='#1e293b')
        
        y_text = 58
        for itm in b['items']:
            ax.text(b['x'] + 1.5, y_text, f"• {itm}", ha='left', va='center', fontsize=8.5, color='#334155')
            y_text -= 7.5
            
    # Connectors
    ax.annotate('', xy=(36, 52), xytext=(31.5, 52),
                arrowprops=dict(arrowstyle="->", color="#ca8a04", lw=2.5))
    ax.annotate('', xy=(68.5, 52), xytext=(64, 52),
                arrowprops=dict(arrowstyle="->", color="#059669", lw=2.5))
                
    ax.text(50, 8, "Công nghệ xử lý hình ảnh tự động giúp sản phẩm Flagship hòa quyện tuyệt đẹp vào giao diện Cyber Dark Mode", 
            ha='center', va='center', fontsize=8.8, style='italic', color='#64748b')
            
    plt.tight_layout()
    fig.savefig('reports/diagram_darkmode_pipeline.png', dpi=220, bbox_inches='tight')
    plt.close()
    print("Created clean diagram_darkmode_pipeline.png")


if __name__ == '__main__':
    create_clean_architecture()
    create_clean_dataflow()
    create_clean_devops()
    create_darkmode_pipeline()
    print("All clean diagrams created!")

def create_clean_erd():
    fig, ax = plt.subplots(figsize=(11, 7.5), dpi=220)
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')
    
    ax.text(50, 96, "MÔ HÌNH QUAN HỆ THỰC THỂ CƠ SỞ DỮ LIỆU (DATABASE ERD - 13 BẢNG INNODB)", 
            ha='center', va='center', fontsize=12.5, fontweight='bold', color='#0f172a')
            
    # 3 Category columns
    cols = [
        {"x": 3, "w": 30, "title": "NHÓM TÀI KHOẢN & NGƯỜI DÙNG", "color": "#eff6ff", "border": "#3b82f6",
         "tables": [
             ("users (Khách hàng, Staff, Admin)", "PK: id | fullname, email, password, phone, role\nstaff_role, permissions, membership_tier"),
             ("user_addresses (Sổ địa chỉ)", "PK: id | FK: user_id -> users(id)\nrecipient_name, phone, address, is_default"),
             ("user_notifications (Thông báo)", "PK: id | FK: user_id | title, message, is_read"),
             ("password_resets (OTP mật khẩu)", "PK: id | email, token, otp, expires_at")
         ]},
        {"x": 35, "w": 31, "title": "NHÓM ĐƠN HÀNG & GIAO DỊCH", "color": "#fefce8", "border": "#ca8a04",
         "tables": [
             ("orders (Đơn hàng mua sắm)", "PK: id | FK: user_id -> users(id)\ntotal_money, payment_method, payment_status\ntransaction_code, paid_at, status (#VP-xx)"),
             ("order_details (Chi tiết máy)", "PK: id | FK: order_id (CASCADE), product_id\nproduct_name, price, quantity, total_price"),
             ("vouchers (Khuyến mãi chiết khấu)", "PK: id | code, discount_type, discount_value\nminimum_order, max_discount, is_active"),
             ("warranty_claims (Bảo hành IMEI)", "PK: id | FK: order_id, product_id\nimei, customer_name, issue_type, status")
         ]},
        {"x": 68, "w": 29, "title": "NHÓM SẢN PHẨM & TƯƠNG TÁC", "color": "#ecfdf5", "border": "#059669",
         "tables": [
             ("products (Điện thoại Flagship)", "PK: id | FK: brand_id -> brands(id)\nname, price, sale_price, colors, screen, cpu\nstorage_options, color_quantities, quantity"),
             ("brands (9 Hãng công nghệ)", "PK: id | name (Apple, Samsung, Xiaomi,...)"),
             ("product_reviews (Đánh giá unbox)", "PK: id | FK: product_id, user_id\nrating (1-5 sao), comment, is_buyer, reply"),
             ("articles & callback_requests", "articles: Tin tức công nghệ, khuyến mãi\ncallback_requests: Khách yêu cầu gọi lại")
         ]}
    ]
    
    for c in cols:
        rect = patches.FancyBboxPatch((c['x'], 8), c['w'], 84, boxstyle="round,pad=0.6",
                                      facecolor=c['color'], edgecolor=c['border'], linewidth=1.5)
        ax.add_patch(rect)
        ax.text(c['x'] + c['w']/2, 88.5, c['title'], ha='center', va='center', fontsize=9.2, fontweight='bold', color='#1e293b')
        
        y_tbl = 78
        for tbl_name, tbl_cols in c['tables']:
            # Inner table card
            t_box = patches.FancyBboxPatch((c['x'] + 1, y_tbl - 15), c['w'] - 2, 17, boxstyle="round,pad=0.4",
                                           facecolor="#ffffff", edgecolor=c['border'], linewidth=1.1)
            ax.add_patch(t_box)
            ax.text(c['x'] + 2, y_tbl, f"TABLE: {tbl_name}", ha='left', va='center', fontsize=8.6, fontweight='bold', color='#0f172a')
            
            y_col_text = y_tbl - 6.5
            for line in tbl_cols.split('\n'):
                ax.text(c['x'] + 2.5, y_col_text, line, ha='left', va='center', fontsize=7.6, color='#334155')
                y_col_text -= 4.2
                
            y_tbl -= 19.5
            
    # Relationship indicators between columns
    ax.annotate('', xy=(35, 78), xytext=(33, 78), arrowprops=dict(arrowstyle="->", color="#3b82f6", lw=2))
    ax.text(34, 80.5, "1 - N", ha='center', va='center', fontsize=7.5, fontweight='bold', color='#3b82f6')
    
    ax.annotate('', xy=(68, 78), xytext=(66, 78), arrowprops=dict(arrowstyle="->", color="#ca8a04", lw=2))
    ax.text(67, 80.5, "N - 1", ha='center', va='center', fontsize=7.5, fontweight='bold', color='#ca8a04')
    
    ax.text(50, 4, "13 bảng quan hệ được thiết kế chuẩn 3NF với Engine InnoDB, bảo đảm toàn vẹn tham chiếu (Foreign Keys) và ACID", 
            ha='center', va='center', fontsize=8.6, style='italic', color='#64748b')
            
    plt.tight_layout()
    fig.savefig('reports/diagram_erd.png', dpi=220, bbox_inches='tight')
    plt.close()
    print("Created clean diagram_erd.png")

if __name__ == '__main__':
    create_clean_erd()
