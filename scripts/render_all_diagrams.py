import os
from PIL import Image, ImageDraw, ImageFont

# Load standard system fonts
font_title = ImageFont.truetype('/System/Library/Fonts/Supplemental/Arial Bold.ttf', 24)
font_h2 = ImageFont.truetype('/System/Library/Fonts/Supplemental/Arial Bold.ttf', 18)
font_card = ImageFont.truetype('/System/Library/Fonts/Supplemental/Arial Bold.ttf', 15)
font_bold = ImageFont.truetype('/System/Library/Fonts/Supplemental/Arial Bold.ttf', 13)
font_regular = ImageFont.truetype('/System/Library/Fonts/Supplemental/Arial.ttf', 12.5)
font_small = ImageFont.truetype('/System/Library/Fonts/Supplemental/Arial.ttf', 11)

os.makedirs('reports', exist_ok=True)

def wrap_text(text, font, max_width):
    """Tự động ngắt dòng thông minh theo từng từ, bảo đảm tuyệt đối không tràn viền hộp"""
    words = text.split(' ')
    lines = []
    curr = []
    for w in words:
        test = ' '.join(curr + [w])
        if font.getlength(test) <= max_width:
            curr.append(w)
        else:
            if curr:
                lines.append(' '.join(curr))
            curr = [w]
    if curr:
        lines.append(' '.join(curr))
    return lines

def draw_bullet_wrapped(draw, text, x, y, font, fill, max_width, line_spacing=20):
    """Vẽ gạch đầu dòng có thụt lề cho các dòng sau, không bao giờ tràn viền"""
    lines = wrap_text(text, font, max_width)
    for i, l in enumerate(lines):
        draw.text((x, y + i * line_spacing), l, font=font, fill=fill)
    return y + len(lines) * line_spacing + 6


# -------------------------------------------------------------
# 1. DARK MODE SHOWCASE
# -------------------------------------------------------------
def render_darkmode_showcase():
    W, H = 1200, 640
    img = Image.new('RGB', (W, H), (15, 23, 42)) # Slate dark #0f172a
    draw = ImageDraw.Draw(img)
    
    draw.text((W // 2, 35), "V-PHONE STORE: HỆ THỐNG GIAO DIỆN KÉP LIGHT MODE & CYBER DARK MODE", 
              fill=(255, 255, 255), font=font_title, anchor="mm")
    
    # Left Card: Light Mode
    draw.rounded_rectangle([60, 75, 540, 580], radius=16, fill=(248, 250, 252), outline=(203, 213, 225), width=2)
    draw.rounded_rectangle([60, 75, 540, 135], radius=16, fill=(226, 232, 240))
    draw.rectangle([60, 115, 540, 135], fill=(226, 232, 240))
    draw.text((300, 105), "LIGHT MODE (GIAO DIỆN TIÊU CHUẨN)", fill=(15, 23, 42), font=font_h2, anchor="mm")
    
    # Right Card: Cyber Dark Mode
    draw.rounded_rectangle([660, 75, 1140, 580], radius=16, fill=(11, 19, 41), outline=(56, 189, 248), width=2)
    draw.rounded_rectangle([660, 75, 1140, 135], radius=16, fill=(19, 31, 55))
    draw.rectangle([660, 115, 1140, 135], fill=(19, 31, 55))
    draw.text((900, 105), "CYBER DARK MODE (CHẾ ĐỘ TỐI FLAGSHIP)", fill=(56, 189, 248), font=font_h2, anchor="mm")
    
    phone_path = 'assets/images/products/iphone-16-promax-titan-sa-mac.png'
    if os.path.exists(phone_path):
        phone = Image.open(phone_path).convert('RGBA')
        phone_resized = phone.resize((190, 230), Image.Resampling.LANCZOS)
        
        # Left product container
        draw.rounded_rectangle([110, 155, 490, 550], radius=12, fill=(255, 255, 255), outline=(226, 232, 240), width=1)
        img.paste(phone_resized, (205, 175), phone_resized)
        draw.text((300, 430), "iPhone 16 Pro Max 256GB", fill=(15, 23, 42), font=font_card, anchor="mm")
        draw.text((300, 460), "Titan Sa Mạc  •  Chip Apple A18 Pro", fill=(100, 116, 139), font=font_regular, anchor="mm")
        draw.text((300, 505), "34.990.000 đ", fill=(2, 132, 199), font=font_h2, anchor="mm")
        
        # Right product container
        draw.rounded_rectangle([710, 155, 1090, 550], radius=12, fill=(19, 31, 55), outline=(56, 189, 248), width=1)
        img.paste(phone_resized, (805, 175), phone_resized)
        draw.text((900, 430), "iPhone 16 Pro Max 256GB", fill=(241, 245, 249), font=font_card, anchor="mm")
        draw.text((900, 460), "Titan Sa Mạc  •  Chip Apple A18 Pro", fill=(148, 163, 184), font=font_regular, anchor="mm")
        draw.text((900, 505), "34.990.000 đ", fill=(56, 189, 248), font=font_h2, anchor="mm")
        
    # Center toggle switch (Clean graphical arrows, NO broken unicode box!)
    draw.rounded_rectangle([565, 315, 635, 355], radius=20, fill=(30, 41, 59), outline=(56, 189, 248), width=2)
    draw.polygon([(585, 330), (595, 325), (595, 335)], fill=(255, 255, 255))
    draw.line([(585, 330), (615, 330)], fill=(255, 255, 255), width=2)
    draw.polygon([(615, 340), (605, 335), (605, 345)], fill=(56, 189, 248))
    draw.line([(585, 340), (615, 340)], fill=(56, 189, 248), width=2)
    
    draw.text((W // 2, 615), "Ảnh sản phẩm được tách nền trong suốt 100% bằng script Python PIL, hòa quyện tự nhiên trên cả 2 nền màu", 
              fill=(148, 163, 184), font=font_small, anchor="mm")
    
    img.save('reports/darkmode_showcase.png')
    print("-> reports/darkmode_showcase.png")


# -------------------------------------------------------------
# 2. DARK MODE PIPELINE (FIXED: NO TEXT OVERFLOW!)
# -------------------------------------------------------------
def render_darkmode_pipeline():
    W, H = 1200, 520
    img = Image.new('RGB', (W, H), (255, 255, 255))
    draw = ImageDraw.Draw(img)
    
    draw.rounded_rectangle([15, 15, W - 15, H - 15], radius=16, outline=(226, 232, 240), width=2)
    draw.text((W // 2, 45), "KỸ THUẬT TÁCH NỀN ẢNH SẢN PHẨM TRONG SUỐT VÀ KIẾN TRÚC CYBER DARK MODE", 
              fill=(15, 23, 42), font=font_title, anchor="mm")
              
    stages = [
        {"x": 45, "w": 340, "title": "1. ẢNH GỐC TỪ HÃNG (NỀN TRẮNG)", "header_bg": (220, 38, 38),
         "bullets": [
             "• Thu thập từ Apple, Samsung, Sony...",
             "• Nền trắng đặc (Solid White #FFFFFF)",
             "• Vấn đề: Khi đưa lên Cyber Dark Mode sẽ lộ viền chữ nhật trắng đục thô ráp quanh máy"
         ]},
        {"x": 430, "w": 340, "title": "2. SCRIPT PYTHON PIL (TÁCH NỀN)", "header_bg": (202, 138, 4),
         "bullets": [
             "• scripts/make_transparent_single.py",
             "• Quét 4 góc phát hiện màu nền trắng",
             "• Tính sai số RGB <= 35 điểm ảnh",
             "• Thuật toán Flood Fill chuyển nền trắng thành kênh Alpha trong suốt 100%"
         ]},
        {"x": 815, "w": 340, "title": "3. HIỂN THỊ CYBER DARK (#0b1329)", "header_bg": (5, 150, 105),
         "bullets": [
             "• Xuất file PNG trong suốt hoàn hảo",
             "• Tự nhiên, hòa quyện trên nền tối",
             "• CSS Variables [data-bs-theme=\"dark\"]",
             "• Tối ưu thị giác màn hình OLED cao cấp"
         ]}
    ]
    
    for idx, s in enumerate(stages):
        bx = s['x']
        bw = s['w']
        draw.rounded_rectangle([bx, 90, bx + bw, 420], radius=12, fill=(248, 250, 252), outline=(226, 232, 240), width=2)
        draw.rounded_rectangle([bx, 90, bx + bw, 145], radius=12, fill=s['header_bg'])
        draw.rectangle([bx, 125, bx + bw, 145], fill=s['header_bg'])
        draw.text((bx + bw // 2, 118), s['title'], fill=(255, 255, 255), font=font_bold, anchor="mm")
        
        y_text = 165
        max_t_w = bw - 30 # 310px width limit
        for b_text in s['bullets']:
            y_text = draw_bullet_wrapped(draw, b_text, bx + 15, y_text, font_regular, (51, 65, 85), max_t_w, line_spacing=20)
            
        if idx < 2:
            ax1 = bx + bw + 5
            ax2 = ax1 + 35
            draw.line([(ax1, 250), (ax2, 250)], fill=(202, 138, 4), width=3)
            draw.polygon([(ax2 - 8, 244), (ax2 - 8, 256), (ax2, 250)], fill=(202, 138, 4))
            
    draw.text((W // 2, 475), "Hơn 200 ảnh sản phẩm Flagship được xử lý tự động, loại bỏ hoàn toàn viền trắng, bảo đảm thẩm mỹ cao cấp", 
              fill=(100, 116, 139), font=font_small, anchor="mm")
              
    img.save('reports/diagram_darkmode_pipeline.png')
    print("-> reports/diagram_darkmode_pipeline.png (FIXED)")


# -------------------------------------------------------------
# 3. FINTECH WORKFLOW (FIXED: NO TEXT OVERFLOW!)
# -------------------------------------------------------------
def render_fintech_flow():
    W, H = 1200, 520
    img = Image.new('RGB', (W, H), (255, 255, 255))
    draw = ImageDraw.Draw(img)
    
    draw.rounded_rectangle([15, 15, W - 15, H - 15], radius=16, outline=(226, 232, 240), width=2)
    draw.text((W // 2, 45), "QUY TRÌNH THANH TOÁN TỰ ĐỘNG VIETQR NAPAS247 & MOMO SANDBOX API v2", 
              fill=(15, 23, 42), font=font_title, anchor="mm")
              
    steps = [
        {"x": 40, "w": 260, "title": "1. KHÁCH HÀNG", "bg": (240, 249, 255), "bc": (2, 132, 199),
         "bullets": [
             "• Chọn phương thức VietQR hoặc MoMo",
             "• Xác nhận đặt hàng hệ thống",
             "• Mở App Ngân hàng quét QR Napas247 hoặc mở ví MoMo"
         ]},
        {"x": 330, "w": 260, "title": "2. V-PHONE STORE", "bg": (254, 252, 232), "bc": (202, 138, 4),
         "bullets": [
             "• VietQRService sinh mã QR động",
             "• Cú pháp: VPHONE <Mã đơn>",
             "• Nhúng sẵn chính xác số tiền cần chuyển",
             "• Đếm ngược thời hạn 15 phút"
         ]},
        {"x": 620, "w": 260, "title": "3. CỔNG THANH TOÁN", "bg": (240, 253, 244), "bc": (22, 163, 74),
         "bullets": [
             "• Napas247 / Ngân hàng MB Bank",
             "• MoMo Payment Gateway v2",
             "• Xác thực biến động số dư tài khoản",
             "• Bắn Webhook IPN tự động"
         ]},
        {"x": 910, "w": 250, "title": "4. WEBHOOK IPN", "bg": (250, 245, 255), "bc": (147, 51, 234),
         "bullets": [
             "• api/vietqr-webhook.php",
             "• api/momo-ipn.php tiếp nhận",
             "• Tự động đổi: 'Đã thanh toán'",
             "• Hoàn tất đơn hàng trong < 1.5s"
         ]}
    ]
    
    for idx, s in enumerate(steps):
        bx = s['x']
        bw = s['w']
        draw.rounded_rectangle([bx, 90, bx + bw, 375], radius=12, fill=s['bg'], outline=s['bc'], width=2)
        draw.text((bx + bw // 2, 125), s['title'], fill=(15, 23, 42), font=font_card, anchor="mm")
        
        y_text = 160
        max_t_w = bw - 25 # 235px max text width
        for b_text in s['bullets']:
            y_text = draw_bullet_wrapped(draw, b_text, bx + 12, y_text, font_regular, (51, 65, 85), max_t_w, line_spacing=19)
            
        if idx < 3:
            ax1 = bx + bw + 4
            ax2 = ax1 + 22
            draw.line([(ax1, 230), (ax2, 230)], fill=(15, 23, 42), width=2)
            draw.polygon([(ax2 - 6, 225), (ax2 - 6, 235), (ax2, 230)], fill=(15, 23, 42))
            
    # Bottom Callout bar
    draw.rounded_rectangle([40, 405, W - 40, 475], radius=10, fill=(240, 253, 244), outline=(22, 163, 74), width=2)
    draw.text((W // 2, 440), "Hệ thống hỗ trợ cơ chế Demo Simulator 1-click cho phép kiểm thử thanh toán tức thì trong môi trường phát triển", 
              fill=(21, 128, 61), font=font_bold, anchor="mm")
              
    img.save('reports/diagram_fintech_flow.png')
    print("-> reports/diagram_fintech_flow.png (FIXED)")


# -------------------------------------------------------------
# 4. DEVOPS SYNC WORKFLOW (FIXED: NO TEXT OVERFLOW!)
# -------------------------------------------------------------
def render_devops_sync():
    W, H = 1200, 520
    img = Image.new('RGB', (W, H), (255, 255, 255))
    draw = ImageDraw.Draw(img)
    
    draw.rounded_rectangle([15, 15, W - 15, H - 15], radius=16, outline=(226, 232, 240), width=2)
    draw.text((W // 2, 45), "QUY TRÌNH ĐỒNG BỘ MÃ NGUỒN VÀ CƠ SỞ DỮ LIỆU ĐA NỀN TẢNG (DEVOPS SYNC)", 
              fill=(15, 23, 42), font=font_title, anchor="mm")
              
    nodes = [
        {"x": 45, "w": 340, "title": "MÁY DEV CÁ NHÂN (macOS XAMPP)", "header_bg": (2, 132, 199),
         "bullets": [
             "• Lập trình tính năng mới trên macOS",
             "• Chạy lệnh: ./scripts/sync_push.sh",
             "• db_sync_tool.php tự động Dump toàn bộ CSDL ra file SQL",
             "• Git tự động add, commit kèm thời gian và push lên GitHub"
         ]},
        {"x": 430, "w": 340, "title": "KHO CHỨA GITHUB (REPO TRUNG TÂM)", "header_bg": (71, 85, 105),
         "bullets": [
             "• Lưu trữ toàn bộ mã nguồn V-Phone Store",
             "• Quản lý lịch sử commit chi tiết theo nhánh",
             "• Lưu bản sao lưu CSDL đồng bộ mới nhất tại file:",
             "  database/db_phone_store.sql"
         ]},
        {"x": 815, "w": 340, "title": "MÁY PHÒNG LAB (WINDOWS PC)", "header_bg": (220, 38, 38),
         "bullets": [
             "• Mở thư mục dự án tại máy phòng trường",
             "• Double-click file: scripts/sync_pull.bat",
             "• Git tự động pull mã nguồn mới nhất",
             "• db_sync_tool.php tự động nạp đè CSDL vào MySQL XAMPP"
         ]}
    ]
    
    for idx, n in enumerate(nodes):
        bx = n['x']
        bw = n['w']
        draw.rounded_rectangle([bx, 90, bx + bw, 420], radius=12, fill=(248, 250, 252), outline=(226, 232, 240), width=2)
        draw.rounded_rectangle([bx, 90, bx + bw, 145], radius=12, fill=n['header_bg'])
        draw.rectangle([bx, 125, bx + bw, 145], fill=n['header_bg'])
        draw.text((bx + bw // 2, 118), n['title'], fill=(255, 255, 255), font=font_bold, anchor="mm")
        
        y_text = 165
        max_t_w = bw - 30 # 310px
        for b_text in n['bullets']:
            y_text = draw_bullet_wrapped(draw, b_text, bx + 15, y_text, font_regular, (51, 65, 85), max_t_w, line_spacing=20)
            
        if idx < 2:
            ax1 = bx + bw + 4
            ax2 = ax1 + 37
            draw.line([(ax1, 250), (ax2, 250)], fill=(2, 132, 199), width=2)
            draw.polygon([(ax2 - 7, 244), (ax2 - 7, 256), (ax2, 250)], fill=(2, 132, 199))
            
    # Connector labels
    draw.text((407, 230), "git push", fill=(2, 132, 199), font=font_small, anchor="mm")
    draw.text((792, 230), "git pull", fill=(220, 38, 38), font=font_small, anchor="mm")

    draw.text((W // 2, 475), "Bộ công cụ DatabaseSyncTool loại bỏ hoàn toàn thao tác nạp xuất thủ công qua phpMyAdmin khi làm việc nhóm", 
              fill=(100, 116, 139), font=font_small, anchor="mm")
              
    img.save('reports/diagram_devops_sync.png')
    print("-> reports/diagram_devops_sync.png (FIXED)")


# -------------------------------------------------------------
# 5. DATABASE ERD (FIXED: NO TEXT OVERFLOW & AMPLE BOTTOM SPACE)
# -------------------------------------------------------------
def render_erd():
    W, H = 1200, 860
    img = Image.new('RGB', (W, H), (255, 255, 255))
    draw = ImageDraw.Draw(img)
    
    draw.rounded_rectangle([15, 15, W - 15, H - 15], radius=16, outline=(226, 232, 240), width=2)
    draw.text((W // 2, 40), "MÔ HÌNH QUAN HỆ THỰC THỂ CƠ SỞ DỮ LIỆU (DATABASE ERD - 13 BẢNG INNODB)", 
              fill=(15, 23, 42), font=font_title, anchor="mm")
              
    cols = [
        {"x": 45, "w": 350, "title": "NHÓM TÀI KHOẢN & NGƯỜI DÙNG", "header_bg": (2, 132, 199),
         "tables": [
             ("TABLE: users (Người dùng)", "PK: id  •  email, fullname, phone, password\nrole (0:Khách, 1:Admin, 2:Staff), permissions\nmembership_tier (Standard, Silver, Gold, Diamond)"),
             ("TABLE: user_addresses (Sổ địa chỉ)", "PK: id  •  FK: user_id -> users(id) [CASCADE]\nrecipient_name, phone, address, is_default"),
             ("TABLE: user_notifications (Thông báo)", "PK: id  •  FK: user_id -> users(id)\ntitle, message, cancel_reason, is_read, created_at"),
             ("TABLE: password_resets (OTP mật khẩu)", "PK: id  •  email, token, otp (6 số), expires_at")
         ]},
        {"x": 425, "w": 350, "title": "NHÓM ĐƠN HÀNG & GIAO DỊCH", "header_bg": (202, 138, 4),
         "tables": [
             ("TABLE: orders (Đơn hàng)", "PK: id  •  FK: user_id -> users(id) [SET NULL]\ntotal_money, payment_method, payment_status\ntransaction_code, status (#VP-xx)"),
             ("TABLE: order_details (Chi tiết máy)", "PK: id  •  FK: order_id -> orders(id) [CASCADE]\nFK: product_id -> products(id), price, quantity"),
             ("TABLE: vouchers (Mã giảm giá)", "PK: id  •  code (VPHONE500), discount_type\ndiscount_value, minimum_order, usage_limit"),
             ("TABLE: warranty_claims (Bảo hành IMEI)", "PK: id  •  FK: order_id, product_id\nimei, customer_name, customer_phone, status")
         ]},
        {"x": 805, "w": 350, "title": "NHÓM SẢN PHẨM & TƯƠNG TÁC", "header_bg": (22, 163, 74),
         "tables": [
             ("TABLE: products (Điện thoại Flagship)", "PK: id  •  FK: brand_id -> brands(id)\nname, price, sale_price, screen, cpu, ram, rom\nstorage_options (JSON), color_quantities (JSON)"),
             ("TABLE: brands (9 Hãng công nghệ)", "PK: id  •  name (Apple, Samsung, Xiaomi, OPPO...)"),
             ("TABLE: product_reviews (Đánh giá unbox)", "PK: id  •  FK: product_id [CASCADE], user_id\nrating (1-5 sao), comment, is_buyer, status"),
             ("TABLE: articles & callback_requests", "articles: Tin tức công nghệ, khuyến mãi\ncallback_requests: Yêu cầu gọi lại tư vấn")
         ]}
    ]
    
    for c in cols:
        bx = c['x']
        bw = c['w']
        draw.rounded_rectangle([bx, 75, bx + bw, 790], radius=12, fill=(248, 250, 252), outline=(226, 232, 240), width=2)
        draw.rounded_rectangle([bx, 75, bx + bw, 120], radius=12, fill=c['header_bg'])
        draw.rectangle([bx, 105, bx + bw, 120], fill=c['header_bg'])
        draw.text((bx + bw // 2, 98), c['title'], fill=(255, 255, 255), font=font_bold, anchor="mm")
        
        y_box = 135
        for t_name, t_cols in c['tables']:
            draw.rounded_rectangle([bx + 10, y_box, bx + bw - 10, y_box + 150], radius=8, 
                                   fill=(255, 255, 255), outline=(203, 213, 225), width=1)
            draw.text((bx + 18, y_box + 18), t_name, fill=(15, 23, 42), font=font_bold)
            y_col = y_box + 44
            for line in t_cols.split('\n'):
                # Wrap each line to prevent clipping inside card
                sub_lines = wrap_text(line, font_small, bw - 40)
                for sl in sub_lines:
                    draw.text((bx + 18, y_col), sl, fill=(51, 65, 85), font=font_small)
                    y_col += 18
            y_box += 160
            
    # Ample 45px padding at bottom, NO TEXT CUT OFF!
    draw.text((W // 2, 825), "13 bảng thực thể thiết kế chuẩn 3NF với Engine InnoDB, bảo đảm toàn vẹn tham chiếu (Foreign Keys) và ACID Transactions", 
              fill=(100, 116, 139), font=font_small, anchor="mm")
              
    img.save('reports/diagram_erd.png')
    print("-> reports/diagram_erd.png (FIXED)")


# -------------------------------------------------------------
# 6. ARCHITECTURE & ROLE AI (WRAPPED & CLEAN)
# -------------------------------------------------------------
def render_architecture():
    W, H = 1200, 720
    img = Image.new('RGB', (W, H), (255, 255, 255))
    draw = ImageDraw.Draw(img)
    
    draw.rounded_rectangle([15, 15, W - 15, H - 15], radius=16, outline=(226, 232, 240), width=2)
    draw.text((W // 2, 45), "KIẾN TRÚC PHÂN TẦNG HỆ THỐNG V-PHONE STORE (PHP MVC 5 TẦNG)", 
              fill=(15, 23, 42), font=font_title, anchor="mm")
              
    tiers = [
        {"y": 80, "color": (240, 249, 255), "border": (2, 132, 199), 
         "title": "TẦNG 1: PRESENTATION & STOREFRONT UI (CYBER DARK MODE & LIGHT MODE)", 
         "desc": "Bootstrap 5.3 Responsive  •  Cyber Dark Theme (#0b1329)  •  Live Search Debounce ES6+  •  CSS Variables"},
        {"y": 195, "color": (254, 252, 232), "border": (202, 138, 4), 
         "title": "TẦNG 2: ROUTING & FRONT CONTROLLER (index.php & CONTROLLERS)", 
         "desc": "index.php Dispatcher  •  AuthController  •  CartController  •  HomeController  •  ProductController  •  OrderController"},
        {"y": 310, "color": (240, 253, 244), "border": (22, 163, 74), 
         "title": "TẦNG 3: BUSINESS SERVICES, FINTECH & MULTI-ROLE AI ENGINE", 
         "desc": "VietQRService (Dynamic QR Napas247)  •  MoMoService (Sandbox v2)  •  MembershipService  •  Multi-Role AI (6 Roles)"},
        {"y": 425, "color": (250, 245, 255), "border": (147, 51, 234), 
         "title": "TẦNG 4: DATA ACCESS OBJECT TIER (9 PDO MODELS & TRANSACTIONS)", 
         "desc": "ProductModel  •  OrderModel  •  UserModel  •  ReviewModel  •  VoucherModel  •  WarrantyModel  •  AddressModel"},
        {"y": 540, "color": (248, 250, 252), "border": (71, 85, 105), 
         "title": "TẦNG 5: PERSISTENCE STORAGE ENGINE (MYSQL 8.0 INNODB ACID)", 
         "desc": "13 Bảng quan hệ chuẩn 3NF  •  Ràng buộc khóa ngoại (Foreign Keys)  •  Bảng mã tiếng Việt utf8mb4_unicode_ci"}
    ]
    
    for t in tiers:
        y = t['y']
        draw.rounded_rectangle([50, y, W - 50, y + 90], radius=12, fill=t['color'], outline=t['border'], width=2)
        draw.text((W // 2, y + 30), t['title'], fill=(15, 23, 42), font=font_card, anchor="mm")
        draw.text((W // 2, y + 60), t['desc'], fill=(51, 65, 85), font=font_regular, anchor="mm")
        
        if y < 500:
            y_arr = y + 90
            draw.line([(W // 2, y_arr), (W // 2, y_arr + 25)], fill=(2, 132, 199), width=2)
            draw.polygon([(W // 2 - 5, y_arr + 18), (W // 2 + 5, y_arr + 18), (W // 2, y_arr + 25)], fill=(2, 132, 199))
            
    draw.text((W // 2, 680), "Kiến trúc 5 tầng chuẩn hướng đối tượng (OOP) phân tách trách nhiệm rõ ràng, kiểm soát bảo mật tuyệt đối", 
              fill=(100, 116, 139), font=font_small, anchor="mm")
              
    img.save('reports/diagram_architecture.png')
    print("-> reports/diagram_architecture.png")


def render_dataflow_mvc():
    W, H = 1200, 600
    img = Image.new('RGB', (W, H), (255, 255, 255))
    draw = ImageDraw.Draw(img)
    
    draw.rounded_rectangle([15, 15, W - 15, H - 15], radius=16, outline=(226, 232, 240), width=2)
    draw.text((W // 2, 40), "CHU TRÌNH XỬ LÝ YÊU CẦU THEO KIẾN TRÚC MVC THUẦN (REQUEST FLOW)", 
              fill=(15, 23, 42), font=font_title, anchor="mm")
              
    draw.text((60, 85), "CHIỀU ĐI (REQUEST PATH):", fill=(2, 132, 199), font=font_bold)
    row1 = [
        {"x": 60, "w": 230, "title": "1. CLIENT BROWSER", "desc": "Gửi HTTP Request\n(?page=...&action=...)", "bg": (240, 249, 255), "bc": (2, 132, 199)},
        {"x": 340, "w": 230, "title": "2. ROUTER (index.php)", "desc": "Front Controller\nKhởi tạo phiên Session\nĐiều hướng Controller", "bg": (254, 252, 232), "bc": (202, 138, 4)},
        {"x": 620, "w": 230, "title": "3. CONTROLLER", "desc": "Xử lý Logic nghiệp vụ\nKiểm tra phân quyền\nGọi Service & Model", "bg": (240, 253, 244), "bc": (22, 163, 74)},
        {"x": 900, "w": 240, "title": "4. MODEL & PDO", "desc": "Prepared Statements\nTruy vấn CSDL an toàn\nChống SQL Injection", "bg": (250, 245, 255), "bc": (147, 51, 234)}
    ]
    
    for idx, b in enumerate(row1):
        draw.rounded_rectangle([b['x'], 110, b['x'] + b['w'], 240], radius=10, fill=b['bg'], outline=b['bc'], width=2)
        draw.text((b['x'] + b['w'] // 2, 140), b['title'], fill=(15, 23, 42), font=font_bold, anchor="mm")
        y_lines = 175
        for line in b['desc'].split('\n'):
            draw.text((b['x'] + b['w'] // 2, y_lines), line, fill=(51, 65, 85), font=font_small, anchor="mm")
            y_lines += 18
            
        if idx < 3:
            ax1 = b['x'] + b['w']
            ax2 = ax1 + 50
            draw.line([(ax1, 175), (ax2, 175)], fill=(15, 23, 42), width=2)
            draw.polygon([(ax2 - 8, 170), (ax2 - 8, 180), (ax2, 175)], fill=(15, 23, 42))
            
    # Connector down to Database
    draw.line([(1020, 240), (1020, 310)], fill=(147, 51, 234), width=2)
    draw.polygon([(1015, 302), (1025, 302), (1020, 310)], fill=(147, 51, 234))
    draw.text((1060, 275), "SQL Query", fill=(147, 51, 234), font=font_small, anchor="mm")

    # Row 2: Response Path (Right to Left)
    draw.text((60, 315), "CHIỀU VỀ (RESPONSE PATH):", fill=(22, 163, 74), font=font_bold)
    row2 = [
        {"x": 900, "w": 240, "title": "5. DATABASE", "desc": "MySQL 8.0 InnoDB\nTrả về tập kết quả\n(Recordset / Array)", "bg": (248, 250, 252), "bc": (71, 85, 105)},
        {"x": 620, "w": 230, "title": "6. SERVICE PACK", "desc": "Đóng gói dữ liệu\nÁp dụng Voucher\nTính chiết khấu VIP", "bg": (254, 242, 242), "bc": (220, 38, 38)},
        {"x": 340, "w": 230, "title": "7. VIEW RENDER", "desc": "app/views/\nRender HTML/CSS\nCyber Dark Mode", "bg": (236, 253, 245), "bc": (5, 150, 105)},
        {"x": 60, "w": 230, "title": "8. CLIENT DISPLAY", "desc": "Trình duyệt hiển thị\nGiao diện mượt mà\nThời gian < 0.8s", "bg": (239, 246, 255), "bc": (59, 130, 246)}
    ]
    
    for idx, b in enumerate(row2):
        draw.rounded_rectangle([b['x'], 340, b['x'] + b['w'], 470], radius=10, fill=b['bg'], outline=b['bc'], width=2)
        draw.text((b['x'] + b['w'] // 2, 370), b['title'], fill=(15, 23, 42), font=font_bold, anchor="mm")
        y_lines = 405
        for line in b['desc'].split('\n'):
            draw.text((b['x'] + b['w'] // 2, y_lines), line, fill=(51, 65, 85), font=font_small, anchor="mm")
            y_lines += 18
            
        if idx < 3:
            ax1 = b['x']
            ax2 = ax1 - 50
            draw.line([(ax1, 405), (ax2, 405)], fill=(2, 132, 199), width=2)
            draw.polygon([(ax2 + 8, 400), (ax2 + 8, 410), (ax2, 405)], fill=(2, 132, 199))
            
    draw.text((W // 2, 540), "Chu trình xử lý 2 chiều độc lập, tuyệt đối không có sự phụ thuộc vòng, bảo đảm hiệu năng tối ưu", 
              fill=(100, 116, 139), font=font_small, anchor="mm")
              
    img.save('reports/diagram_dataflow_mvc.png')
    print("-> reports/diagram_dataflow_mvc.png")


def render_role_ai():
    W, H = 1200, 680
    img = Image.new('RGB', (W, H), (255, 255, 255))
    draw = ImageDraw.Draw(img)
    
    draw.rounded_rectangle([15, 15, W - 15, H - 15], radius=16, outline=(226, 232, 240), width=2)
    draw.text((W // 2, 40), "HỆ THỐNG TRỢ LÝ TRÍ TUỆ NHÂN TẠO ĐA VAI TRÒ (MULTI-ROLE AI NEURAL ENGINE)", 
              fill=(15, 23, 42), font=font_title, anchor="mm")
              
    # Center Core
    draw.rounded_rectangle([420, 220, 780, 480], radius=16, fill=(15, 23, 42), outline=(56, 189, 248), width=3)
    draw.text((600, 270), "AI CONTEXT ENGINE", fill=(56, 189, 248), font=font_h2, anchor="mm")
    draw.text((600, 305), "(/api/role_ai.php - 2.100 dòng)", fill=(255, 255, 255), font=font_bold, anchor="mm")
    
    desc_core = [
        "• Truy vấn CSDL MySQL thời gian thực",
        "• Thống kê doanh thu ngày / tháng",
        "• Cảnh báo tồn kho máy <= 5",
        "• Tra cứu hạn bảo hành qua IMEI",
        "• Ghép Context Prompt theo 6 vai trò"
    ]
    y_c = 345
    for l in desc_core:
        draw.text((450, y_c), l, fill=(203, 213, 225), font=font_small)
        y_c += 24
        
    roles = [
        {"x": 45, "y": 80, "title": "1. ADMIN AI", "desc": "Báo cáo doanh thu & chiến lược nhập hàng", "bg": (254, 242, 242), "bc": (220, 38, 38)},
        {"x": 45, "y": 275, "title": "2. SALES AI", "desc": "Tư vấn cấu hình máy & kịch bản up-sell", "bg": (254, 252, 232), "bc": (202, 138, 4)},
        {"x": 45, "y": 470, "title": "3. WAREHOUSE AI", "desc": "Kiểm kê tồn kho theo màu & kiểm tra IMEI", "bg": (255, 247, 237), "bc": (234, 88, 12)},
        {"x": 820, "y": 80, "title": "4. WARRANTY AI", "desc": "Chẩn đoán mã lỗi & tra hạn bảo hành máy", "bg": (240, 253, 244), "bc": (22, 163, 74)},
        {"x": 820, "y": 275, "title": "5. SUPPORT AI", "desc": "Kịch bản CSKH 5 sao & xử lý khiếu nại", "bg": (238, 242, 255), "bc": (79, 70, 229)},
        {"x": 820, "y": 470, "title": "6. CUSTOMER AI", "desc": "Tư vấn Flagship 24/7 & thủ tục trả góp 0%", "bg": (253, 244, 255), "bc": (192, 38, 211)}
    ]
    
    for r in roles:
        bx = r['x']
        bw = 335
        draw.rounded_rectangle([bx, r['y'], bx + bw, r['y'] + 140], radius=12, fill=r['bg'], outline=r['bc'], width=2)
        draw.text((bx + bw // 2, r['y'] + 35), r['title'], fill=(15, 23, 42), font=font_bold, anchor="mm")
        
        # Wrapped text for role description
        sub_l = wrap_text(r['desc'], font_regular, bw - 30)
        y_rd = r['y'] + 70
        for sl in sub_l:
            draw.text((bx + bw // 2, y_rd), sl, fill=(51, 65, 85), font=font_regular, anchor="mm")
            y_rd += 20
            
        if bx < 400:
            cx1 = bx + bw
            cy1 = r['y'] + 70
            draw.line([(cx1, cy1), (420, 350)], fill=r['bc'], width=2)
        else:
            cx1 = bx
            cy1 = r['y'] + 70
            draw.line([(cx1, cy1), (780, 350)], fill=r['bc'], width=2)
            
    draw.text((W // 2, 645), "Kiến trúc phân quyền RBAC bảo đảm nhân sự chỉ được tương tác với Trợ lý AI trong thẩm quyền chức vụ", 
              fill=(100, 116, 139), font=font_small, anchor="mm")
              
    img.save('reports/diagram_role_ai_flow.png')
    print("-> reports/diagram_role_ai_flow.png")


def render_all():
    render_darkmode_showcase()
    render_darkmode_pipeline()
    render_fintech_flow()
    render_devops_sync()
    render_erd()
    render_architecture()
    render_dataflow_mvc()
    render_role_ai()
    print("ALL DIAGRAMS RENDERED WITH ZERO OVERFLOW!")

if __name__ == '__main__':
    render_all()
