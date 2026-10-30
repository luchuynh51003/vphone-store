import os
import sys
import docx
from docx.shared import Pt, Cm, Inches, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import parse_xml, OxmlElement
from docx.oxml.ns import nsdecls, qn

def set_cell_background(cell, fill_hex):
    tcPr = cell._tc.get_or_add_tcPr()
    tcPr.append(parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>'))

def set_table_borders(table, color="CCCCCC"):
    tblPr = table._tbl.tblPr
    borders = parse_xml(
        f'<w:tblBorders {nsdecls("w")}>'
        f'<w:top w:val="single" w:sz="6" w:space="0" w:color="{color}"/>'
        f'<w:bottom w:val="single" w:sz="6" w:space="0" w:color="{color}"/>'
        f'<w:insideH w:val="single" w:sz="4" w:space="0" w:color="{color}"/>'
        f'<w:insideV w:val="none"/>'
        f'<w:left w:val="none"/>'
        f'<w:right w:val="none"/>'
        f'</w:tblBorders>'
    )
    tblPr.append(borders)

def set_font(run, size=13, bold=False, italic=False, color=(15, 23, 42)):
    run.font.name = 'Times New Roman'
    run.font.size = Pt(size)
    run.bold = bold
    run.italic = italic
    run.font.color.rgb = RGBColor(*color)
    rPr = run._r.get_or_add_rPr()
    rFonts = OxmlElement('w:rFonts')
    rFonts.set(qn('w:ascii'), 'Times New Roman')
    rFonts.set(qn('w:hAnsi'), 'Times New Roman')
    rFonts.set(qn('w:cs'), 'Times New Roman')
    rFonts.set(qn('w:eastAsia'), 'Times New Roman')
    rPr.append(rFonts)

def p_format(p, before=0, after=6, spacing=1.3, align=WD_ALIGN_PARAGRAPH.JUSTIFY):
    p.paragraph_format.space_before = Pt(before)
    p.paragraph_format.space_after = Pt(after)
    p.paragraph_format.line_spacing = spacing
    p.paragraph_format.alignment = align

def generate_report():
    print("[1/5] Khởi tạo tài liệu Word chuẩn...")
    doc = docx.Document()
    
    # 1. CĂN LỀ TRANG CHUẨN (Margins: Trái 3cm, Phải 2cm, Trên 2cm, Dưới 2cm)
    for section in doc.sections:
        section.top_margin = Cm(2.0)
        section.bottom_margin = Cm(2.0)
        section.left_margin = Cm(3.0)
        section.right_margin = Cm(2.0)
        section.page_width = Cm(21.0)
        section.page_height = Cm(29.7)
        
        # Header / Footer
        header = section.header
        p_hdr = header.paragraphs[0]
        p_hdr.text = "V-PHONE STORE  •  BÁO CÁO KỸ THUẬT QUÁ TRÌNH PHÁT TRIỂN SẢN PHẨM"
        p_hdr.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        p_hdr.runs[0].font.name = "Times New Roman"
        p_hdr.runs[0].font.size = Pt(8.5)
        p_hdr.runs[0].font.italic = True
        p_hdr.runs[0].font.color.rgb = RGBColor(148, 163, 184)
        
        footer = section.footer
        p_ftr = footer.paragraphs[0]
        p_ftr.text = "Đồ Án Chuyên Ngành Kỹ Thuật Phần Mềm  —  Huỳnh Bá Lực & Võ Minh Hiếu"
        p_ftr.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_ftr.runs[0].font.name = "Times New Roman"
        p_ftr.runs[0].font.size = Pt(8.5)
        p_ftr.runs[0].font.color.rgb = RGBColor(148, 163, 184)

    # Đặt style Normal mặc định
    style_normal = doc.styles['Normal']
    style_normal.font.name = 'Times New Roman'
    style_normal.font.size = Pt(13)

    def heading_1(text):
        p = doc.add_paragraph()
        p_format(p, before=18, after=6, spacing=1.3, align=WD_ALIGN_PARAGRAPH.JUSTIFY)
        r = p.add_run(text.upper())
        set_font(r, size=14, bold=True, color=(15, 23, 42))
        return p

    def heading_2(text):
        p = doc.add_paragraph()
        p_format(p, before=12, after=4, spacing=1.3, align=WD_ALIGN_PARAGRAPH.JUSTIFY)
        r = p.add_run(text)
        set_font(r, size=13, bold=True, color=(30, 41, 59))
        return p

    def heading_3(text):
        p = doc.add_paragraph()
        p_format(p, before=8, after=3, spacing=1.3, align=WD_ALIGN_PARAGRAPH.JUSTIFY)
        r = p.add_run(text)
        set_font(r, size=13, bold=True, italic=True, color=(51, 65, 85))
        return p

    def body_p(text, bold_prefix=""):
        p = doc.add_paragraph()
        p_format(p, before=0, after=6, spacing=1.3, align=WD_ALIGN_PARAGRAPH.JUSTIFY)
        if bold_prefix:
            rb = p.add_run(bold_prefix)
            set_font(rb, size=13, bold=True, color=(15, 23, 42))
        r = p.add_run(text)
        set_font(r, size=13, color=(30, 41, 59))
        return p

    def bullet_p(text, bold_prefix=""):
        p = doc.add_paragraph()
        p_format(p, before=0, after=4, spacing=1.3, align=WD_ALIGN_PARAGRAPH.JUSTIFY)
        p.paragraph_format.left_indent = Inches(0.25)
        rs = p.add_run("• ")
        set_font(rs, size=13, bold=True, color=(2, 132, 199))
        if bold_prefix:
            rb = p.add_run(bold_prefix)
            set_font(rb, size=13, bold=True, color=(15, 23, 42))
        r = p.add_run(text)
        set_font(r, size=13, color=(30, 41, 59))
        return p

    def add_img(img_path, caption, width=5.5):
        if not os.path.exists(img_path):
            print(f"Warning: Image {img_path} not found.")
            return
        p_i = doc.add_paragraph()
        p_format(p_i, before=8, after=3, spacing=1.0, align=WD_ALIGN_PARAGRAPH.CENTER)
        p_i.add_run().add_picture(img_path, width=Inches(width))
        
        p_c = doc.add_paragraph()
        p_format(p_c, before=2, after=8, spacing=1.2, align=WD_ALIGN_PARAGRAPH.CENTER)
        rc = p_c.add_run(caption)
        set_font(rc, size=12, italic=True, color=(71, 85, 105))

    def make_table(headers, rows, widths=None):
        table = doc.add_table(rows=len(rows) + 1, cols=len(headers))
        table.alignment = WD_TABLE_ALIGNMENT.CENTER
        set_table_borders(table, "CBD5E1")
        
        hdr_cells = table.rows[0].cells
        for i, h in enumerate(headers):
            hdr_cells[i].text = ""
            p = hdr_cells[i].paragraphs[0]
            p_format(p, before=4, after=4, spacing=1.1, align=WD_ALIGN_PARAGRAPH.CENTER)
            r = p.add_run(h)
            set_font(r, size=11.5, bold=True, color=(255, 255, 255))
            set_cell_background(hdr_cells[i], "1E293B")
            
        for r_i, r_data in enumerate(rows):
            row_cells = table.rows[r_i + 1].cells
            bg = "F8FAFC" if r_i % 2 == 1 else "FFFFFF"
            for c_i, val in enumerate(r_data):
                row_cells[c_i].text = ""
                p = row_cells[c_i].paragraphs[0]
                align_c = WD_ALIGN_PARAGRAPH.CENTER if (c_i == 0 or len(str(val)) < 8) else WD_ALIGN_PARAGRAPH.LEFT
                p_format(p, before=3, after=3, spacing=1.15, align=align_c)
                r = p.add_run(str(val))
                set_font(r, size=11, color=(30, 41, 59))
                set_cell_background(row_cells[c_i], bg)
                
        if widths:
            for row in table.rows:
                for idx, w in enumerate(widths):
                    row.cells[idx].width = Inches(w)
                    
        p_sp = doc.add_paragraph()
        p_format(p_sp, before=0, after=4, spacing=1.0)
        return table

    def callout_box(title, items):
        table = doc.add_table(rows=1, cols=1)
        table.alignment = WD_TABLE_ALIGNMENT.CENTER
        cell = table.cell(0, 0)
        cell.width = Inches(5.8)
        set_cell_background(cell, "F0FDF4")
        tcPr = cell._tc.get_or_add_tcPr()
        tcPr.append(parse_xml(
            f'<w:tcBorders {nsdecls("w")}>'
            f'<w:left w:val="single" w:sz="24" w:space="0" w:color="16A34A"/>'
            f'<w:top w:val="none"/><w:bottom w:val="none"/><w:right w:val="none"/>'
            f'</w:tcBorders>'
        ))
        
        p = cell.paragraphs[0]
        p_format(p, before=4, after=3, spacing=1.2, align=WD_ALIGN_PARAGRAPH.LEFT)
        r = p.add_run(" " + title)
        set_font(r, size=12.5, bold=True, color=(21, 128, 61))
        
        for item in items:
            pi = cell.add_paragraph()
            p_format(pi, before=1, after=2, spacing=1.2, align=WD_ALIGN_PARAGRAPH.JUSTIFY)
            rb = pi.add_run("• ")
            set_font(rb, size=12, bold=True, color=(21, 128, 61))
            rt = pi.add_run(item)
            set_font(rt, size=12, color=(30, 41, 59))
            
        p_sp = doc.add_paragraph()
        p_format(p_sp, before=0, after=4, spacing=1.0)

    # ==========================================
    # TRANG TIÊU ĐỀ BÁO CÁO (COVER / TITLE PAGE)
    # ==========================================
    print("[2/5] Đang tạo trang bìa và thông tin tác giả...")
    p_inst = doc.add_paragraph()
    p_format(p_inst, before=10, after=2, spacing=1.2, align=WD_ALIGN_PARAGRAPH.CENTER)
    r = p_inst.add_run("UBND THÀNH PHỐ HỒ CHÍ MINH\nTRƯỜNG CAO ĐẲNG LÝ TỰ TRỌNG TP. HỒ CHÍ MINH\nKHOA CÔNG NGHỆ THÔNG TIN")
    set_font(r, size=12.5, bold=True, color=(71, 85, 105))

    p_div = doc.add_paragraph()
    p_format(p_div, before=4, after=18, spacing=1.0, align=WD_ALIGN_PARAGRAPH.CENTER)
    r = p_div.add_run("——— *** ———")
    set_font(r, size=12, bold=True, color=(148, 163, 184))

    # Logo
    if os.path.exists("assets/images/vphone-logo.png"):
        p_logo = doc.add_paragraph()
        p_format(p_logo, before=6, after=12, spacing=1.0, align=WD_ALIGN_PARAGRAPH.CENTER)
        p_logo.add_run().add_picture("assets/images/vphone-logo.png", width=Inches(1.8))

    # Tiêu đề báo cáo lớn (16pt - 18pt, In đậm, Căn giữa)
    p_title = doc.add_paragraph()
    p_format(p_title, before=10, after=8, spacing=1.3, align=WD_ALIGN_PARAGRAPH.CENTER)
    r = p_title.add_run("BÁO CÁO KỸ THUẬT\nQUÁ TRÌNH PHÁT TRIỂN SẢN PHẨM PHẦN MỀM")
    set_font(r, size=18, bold=True, color=(15, 23, 42))

    p_sub = doc.add_paragraph()
    p_format(p_sub, before=4, after=24, spacing=1.3, align=WD_ALIGN_PARAGRAPH.CENTER)
    r = p_sub.add_run("DỰ ÁN: HỆ THỐNG THƯƠNG MẠI ĐIỆN TỬ SMARTPHONE FLAGSHIP V-PHONE STORE\nTÍCH HỢP TRỢ LÝ TRÍ TUỆ NHÂN TẠO ĐA VAI TRÒ & CỔNG THANH TOÁN SỐ TỰ ĐỘNG 4.0")
    set_font(r, size=13.5, bold=True, color=(2, 132, 199))

    # Bảng thông tin tác giả và đồ án
    info_table = doc.add_table(rows=6, cols=2)
    info_table.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(info_table, "E2E8F0")
    
    info_data = [
        ("Đề tài nghiên cứu:", "Xây dựng Website Thương Mại Điện Tử V-Phone Store"),
        ("Kiến trúc hệ thống:", "Mô hình MVC (Model - View - Controller) thuần hướng đối tượng bằng PHP"),
        ("Cơ sở dữ liệu:", "MySQL 8.0+ / MariaDB 10.4+ InnoDB Engine (13 bảng quan hệ)"),
        ("Sinh viên thực hiện:", "Huỳnh Bá Lực (Lead Dev) & Võ Minh Hiếu (Core Dev)"),
        ("Phân công nhiệm vụ:", "Huỳnh Bá Lực: Kiến trúc MVC, Fintech VietQR/MoMo, Cyber Dark Mode\nVõ Minh Hiếu: Thiết kế CSDL Flagship, Phân hệ Sản phẩm, Phân quyền RBAC"),
        ("Thời gian thực hiện:", "Năm học 2026")
    ]
    
    for idx, (label, val) in enumerate(info_data):
        c1 = info_table.rows[idx].cells[0]
        c2 = info_table.rows[idx].cells[1]
        c1.width = Inches(2.2)
        c2.width = Inches(3.6)
        
        p1 = c1.paragraphs[0]
        p_format(p1, before=3, after=3, spacing=1.2, align=WD_ALIGN_PARAGRAPH.LEFT)
        r1 = p1.add_run(label)
        set_font(r1, size=11.5, bold=True, color=(30, 41, 59))
        
        p2 = c2.paragraphs[0]
        p_format(p2, before=3, after=3, spacing=1.2, align=WD_ALIGN_PARAGRAPH.JUSTIFY)
        r2 = p2.add_run(val)
        set_font(r2, size=11.5, color=(51, 65, 85))
        
        set_cell_background(c1, "F8FAFC")
        set_cell_background(c2, "FFFFFF")

    p_loc = doc.add_paragraph()
    p_format(p_loc, before=24, after=0, spacing=1.2, align=WD_ALIGN_PARAGRAPH.CENTER)
    r = p_loc.add_run("TP. HỒ CHÍ MINH — NĂM 2026")
    set_font(r, size=12, bold=True, color=(71, 85, 105))
    
    doc.add_page_break()

    # ==========================================
    # PHẦN I: TỔNG QUAN DỰ ÁN VÀ KIẾN TRÚC HỆ THỐNG
    # ==========================================
    print("[3/5] Đang viết Phần I: Tổng quan dự án và Kiến trúc hệ thống...")
    heading_1("PHẦN I: TỔNG QUAN DỰ ÁN VÀ KIẾN TRÚC HỆ THỐNG")
    
    heading_2("1.1. Bối cảnh và mục tiêu phát triển hệ thống V-Phone Store")
    body_p("Trong bối cảnh thị trường thương mại điện tử thiết bị di động ngày càng phát triển vượt bậc, người tiêu dùng Việt Nam có xu hướng tìm kiếm các thiết bị cao cấp (Flagship Smartphones) với trải nghiệm mua sắm tiện nghi, cấu hình minh bạch, kiểm tra bảo hành tức thì và các kênh thanh toán không tiền mặt bảo mật. Dự án V-Phone Store (mã dự án phone-store) được khởi xướng và phát triển nhằm mục tiêu giải quyết trọn vẹn chu trình bán lẻ và hậu mãi đối với các dòng máy điện thoại hàng đầu từ Apple, Samsung, Xiaomi, OPPO, Google, Vivo, Asus và Sony.")
    
    body_p("Khác với các hệ thống website bán lẻ truyền thống thường phụ thuộc vào các CMS mã nguồn mở nặng nề hoặc các framework bên thứ ba cồng kềnh, V-Phone Store được thiết kế trên nền tảng PHP thuần áp dụng chặt chẽ mô hình kiến trúc MVC (Model - View - Controller). Cách tiếp cận này mang lại tốc độ phản hồi cực nhanh (thời gian phản hồi trang đo đạc thực tế luôn dưới 0.8 giây), kiểm soát hoàn toàn mã nguồn và ngăn ngừa triệt để các lỗ hổng bảo mật.")
    
    bullet_p("Tối ưu hóa hành trình mua sắm số: Từ bước tìm kiếm nhanh với kỹ thuật Live Search Debouncing, so sánh thông số kỹ thuật song song hai dòng máy đối đầu, định giá thu cũ đổi mới (Trade-In), quản lý giỏ hàng thời gian thực, cho đến thanh toán tự động.", "• Trải nghiệm khách hàng vượt trội: ")
    bullet_p("Tích hợp động cơ Trợ lý Trí tuệ Nhân tạo đa chuyên môn với 6 vai trò nghiệp vụ độc lập, hỗ trợ tự động từ khâu tư vấn khách hàng, kiểm kê kho vận, chẩn đoán bảo hành cho đến báo cáo doanh thu điều hành.", "• Đột phá công nghệ Trợ lý AI: ")
    bullet_p("Tự động hóa hoàn toàn quy trình đối soát giao dịch tài chính thông qua cổng VietQR Napas247 động và ví điện tử MoMo Sandbox API v2 có xác thực chữ ký số HMAC-SHA256.", "• Hạ tầng thanh toán Fintech 4.0: ")
    bullet_p("Phân tách quyền hạn truy cập đa cấp bậc (Super Admin, Quản lý, Nhân viên Bán hàng, Thủ kho, Kỹ thuật viên bảo hành, Chăm sóc khách hàng) bảo đảm tính an toàn dữ liệu nội bộ.", "• Quản trị phân quyền nghiêm ngặt: ")

    heading_2("1.2. Công nghệ sử dụng và đặc tả kỹ thuật (Technology Stack)")
    body_p("Dự án được xây dựng dựa trên sự kết hợp hài hòa giữa các công nghệ Backend, Cơ sở dữ liệu, Giao diện người dùng và các dịch vụ cổng thanh toán số hiện đại nhất hiện nay:")

    tech_headers = ["Phân Tầng", "Công Nghệ Sử Dụng", "Phiên Bản", "Đặc Điểm Kỹ Thuật Nổi Bật"]
    tech_rows = [
        ["Core Backend", "PHP Pure OOP (MVC)", "8.1 / 8.2 (Hỗ trợ 7.4+)", "Mô hình MVC hướng đối tượng, PDO Prepared Statements, Session Native an toàn"],
        ["Database", "MySQL / MariaDB", "8.0+ / 10.4+", "Engine InnoDB hỗ trợ giao dịch ACID, quan hệ khóa ngoại (Foreign Keys), utf8mb4"],
        ["Frontend UI", "HTML5, CSS3, ES6+", "Chuẩn 2026", "Bootstrap 5.3, Font Awesome 6 Pro, CSS Variables Cyber Dark Mode & Light Mode"],
        ["Fintech VietQR", "VietQR Open API", "Napas247 v2", "Sinh mã QR động kèm cú pháp VPHONE <id>, Webhook đối soát số dư tức thì"],
        ["Fintech MoMo", "MoMo Payment API", "Sandbox v2", "Mã hóa bảo mật chữ ký HMAC-SHA256, xử lý bất đồng bộ IPN Webhook"],
        ["Xác thực (Auth)", "Google OAuth 2.0 & OTP", "RFC 6749", "Đăng nhập một chạm SSO qua Google API, mã hóa mật khẩu một chiều Bcrypt"],
        ["DevOps & Tooling", "Git, Apache, Python PIL", "XAMPP macOS/Win", "Công cụ DatabaseSyncTool tự động xuất/nạp SQL, script tách nền ảnh Dark Mode"]
    ]
    make_table(tech_headers, tech_rows, [1.3, 1.4, 1.1, 2.0])

    add_img("reports/diagram_architecture.png", 
            "Hình 1.1: Sơ đồ kiến trúc phân tầng hệ thống V-Phone Store (PHP MVC 5 tầng)")

    heading_2("1.3. Cấu trúc tổ chức thư mục mã nguồn thực tế (Directory Structure)")
    body_p("Mã nguồn dự án được tổ chức khoa học, phân định ranh giới trách nhiệm rõ ràng giữa các phân hệ Lõi ứng dụng (app), Quản trị điều hành (admin), Giao diện công khai (views), Tài nguyên tĩnh (assets), Cấu hình (config) và Cơ sở dữ liệu (database):")

    add_img("reports/diagram_directory_structure.png", 
            "Hình 1.2: Sơ đồ cấu trúc cây thư mục mã nguồn dự án V-Phone Store")

    dir_headers = ["Thư Mục / Tệp Tin", "Quy Mô Mã Nguồn", "Nhiệm Vụ & Phân Trách Nghiệm Thực Tế"]
    dir_rows = [
        ["app/controllers/", "7 controllers", "Điều hướng luồng xử lý: AuthController, CartController, HomeController, OrderController, ProductController, ProfileController, ReviewController."],
        ["app/models/", "9 DAO models", "Tương tác CSDL an toàn bằng PDO: AddressModel, ArticleModel, NotificationModel, OrderModel, ProductModel, ReviewModel, UserModel, VoucherModel, WarrantyModel."],
        ["app/services/", "5 services", "Xử lý logic nghiệp vụ phức tạp: VietQRService, MoMoService, MembershipService, GoogleAuthService, MailService."],
        ["app/views/", "18 tệp view PHP", "Giao diện người dùng: home.php, product_detail.php, cart.php, checkout.php, compare.php, stores.php, warranty.php, order_tracking.php, profile.php,..."],
        ["admin/", "14 màn hình quản trị", "Trung tâm điều hành: index.php (Dashboard), staff.php, inventory.php, orders.php, products.php, warranties.php, ai_assistants.php, db_sync.php,..."],
        ["api/", "7 REST Endpoints", "Xử lý bất đồng bộ AJAX & Webhook: role_ai.php (6 AI Roles), chatbot.php, vietqr-webhook.php, momo-ipn.php, search.php, notifications.php, callback-request.php."],
        ["config/", "4 tệp cấu hình", "Thiết lập môi trường: database.php (Auto-sync schema), vietqr.php, momo.php, google.php."],
        ["database/", "CSDL & Tool đồng bộ", "db_phone_store.sql (13 bảng InnoDB) và công cụ di trú tự động db_sync_tool.php."],
        ["scripts/", "Shell & Python scripts", "sync_push.sh, sync_pull.sh, sync_pull.bat (đồng bộ đa máy), make_transparent_single.py (PIL xử lý ảnh trong suốt)."],
        ["assets/", "Tài nguyên giao diện", "CSS tuỳ biến (style.css, role_ai.css), JS (main.js, role_ai.js), kho ảnh sản phẩm Flagship sắc nét phân cấp theo màu và hãng."]
    ]
    make_table(dir_headers, dir_rows, [1.5, 1.2, 3.1])

    heading_2("1.4. Mô hình kiến trúc MVC và Điểm điều phối tập trung (Front Controller)")
    body_p("Toàn bộ các yêu cầu HTTP từ phía máy khách gửi đến hệ thống đều được tiếp nhận và xử lý qua điểm vào duy nhất tại tệp tin index.php đóng vai trò là Front Controller:")
    bullet_p("index.php tiếp nhận tham số qua URL (?page=...&action=...) hoặc phân tích URI để xác định Controller và phương thức (Action) cần kích hoạt.", "1. Phân tích định tuyến (Routing): ")
    bullet_p("Khởi tạo phiên làm việc (session_start()), thiết lập múi giờ Việt Nam (Asia/Ho_Chi_Minh), nạp tệp cấu hình config/database.php kết nối cơ sở dữ liệu qua đối tượng PDO duy nhất.", "2. Khởi tạo môi trường: ")
    bullet_p("Controller tương ứng được khởi tạo, nhận kết nối PDO, kiểm tra tính hợp lệ của tham số và quyền truy cập người dùng.", "3. Điều phối nghiệp vụ: ")
    bullet_p("Controller triệu gọi các phương thức trong tầng Model hoặc Service để truy vấn/thao tác dữ liệu, sau đó đóng gói kết quả truyền sang tầng View.", "4. Xử lý dữ liệu: ")
    bullet_p("Tệp View PHP tương ứng nạp các dữ liệu được truyền, kết hợp với các thành phần giao diện chung (header.php, navbar.php, footer.php) để render mã HTML hoàn chỉnh trả về trình duyệt.", "5. Kết xuất giao diện: ")

    # ==========================================
    # PHẦN II: QUY TRÌNH PHÁT TRIỂN SẢN PHẨM PHẦN MỀM
    # ==========================================
    print("[4/5] Đang viết Phần II: Quy trình phát triển sản phẩm qua 4 giai đoạn...")
    heading_1("PHẦN II: QUY TRÌNH PHÁT TRIỂN SẢN PHẨM PHẦN MỀM")

    # Giai đoạn 1
    heading_2("2.1. Giai đoạn 1: Phân tích nghiệp vụ và Thiết kế Cơ sở dữ liệu")
    body_p("Cơ sở dữ liệu của dự án được đặt tên là db_phone_store, được nhóm phát triển thiết kế chuẩn hóa theo dạng chuẩn 3 (3NF) trên nền tảng lưu trữ MySQL 8.0 với Engine InnoDB. Việc sử dụng Engine InnoDB là quyết định sống còn nhằm hỗ trợ đầy đủ các giao dịch an toàn (ACID Transactions) khi thực hiện tạo đơn hàng, trừ số lượng tồn kho và cập nhật doanh thu.")

    add_img("reports/diagram_erd.png", 
            "Hình 2.1: Sơ đồ mô hình quan hệ thực thể (Database ERD - 13 bảng InnoDB)")

    body_p("Dưới đây là bảng tổng hợp đặc tả chi tiết 13 bảng thực thể trong hệ thống:")

    table_erd_headers = ["Tên Bảng CSDL", "Khóa Chính / Khóa Ngoại", "Các Trường Dữ Liệu Tiêu Biểu", "Vai Trò Nghiệp Vụ Cốt Lõi"]
    table_erd_rows = [
        ["users", "PK: id", "fullname, email, password, phone, address, role, staff_role, permissions, membership_tier", "Quản lý khách hàng, nhân viên và ban quản trị. Phân quyền đa cấp và lưu trữ hạng thành viên VIP."],
        ["products", "PK: id\nFK: brand_id -> brands(id)", "name, price, sale_price, screen, cpu, ram, rom, colors, storage_options, color_quantities", "Lưu trữ thiết bị điện thoại Flagship, thông số cấu hình, danh sách phiên bản màu và tồn kho theo màu."],
        ["brands", "PK: id", "name", "Danh mục 9 thương hiệu quốc tế: Apple, Samsung, Xiaomi, OPPO, Google, Vivo, Asus, Sony, Huawei."],
        ["orders", "PK: id\nFK: user_id -> users(id)", "fullname, phone, address, total_money, payment_method, payment_status, transaction_code, status", "Lưu trữ đơn hàng, phương thức thanh toán, mã đối soát VietQR/MoMo và tiến trình giao hàng 4 bước."],
        ["order_details", "PK: id\nFK: order_id, product_id", "order_id, product_id, product_name, price, quantity, total_price", "Chi tiết từng sản phẩm trong đơn, có ràng buộc CASCADE xóa theo đơn và SET NULL khi sản phẩm bị xóa."],
        ["vouchers", "PK: id", "code, discount_type, discount_value, minimum_order, max_discount, usage_limit, is_active", "Động cơ mã giảm giá, hỗ trợ chiết khấu theo phần trăm (%) hoặc số tiền cố định, kiểm tra đơn tối thiểu."],
        ["warranty_claims", "PK: id\nFK: user_id, order_id, product_id", "imei, customer_name, customer_phone, issue_type, issue_description, claim_method, status", "Hồ sơ bảo hành thiết bị theo số IMEI, theo dõi tiến độ thẩm định, đổi trả 1 đổi 1 và biên bản bàn giao."],
        ["product_reviews", "PK: id\nFK: product_id, user_id", "order_id, fullname, email, rating, comment, is_buyer, is_edited, status, admin_reply", "Đánh giá sao và nhận xét từ khách hàng thực tế (đã xác thực mua hàng is_buyer = 1), hỗ trợ admin phản hồi."],
        ["user_addresses", "PK: id\nFK: user_id -> users(id)", "recipient_name, phone, address, label, is_default", "Sổ lưu trữ nhiều địa chỉ nhận hàng linh hoạt (Nhà riêng, Văn phòng), có cờ đánh dấu địa chỉ mặc định."],
        ["user_notifications", "PK: id\nFK: user_id", "phone, order_id, title, message, cancel_reason, type, is_read", "Hệ thống thông báo đẩy nội bộ về trạng thái đơn hàng, xác nhận bảo hành và ưu đãi thành viên."],
        ["articles", "PK: id", "title, badge, badge_class, article_date, read_time, image, summary, content, is_published", "Quản lý bài viết tin tức công nghệ, thủ thuật smartphone và tin khuyến mãi ra mắt Flagship mới."],
        ["callback_requests", "PK: id", "fullname, phone, email, topic, department, note, reply_message, replied_by, status", "Tiếp nhận yêu cầu tư vấn gọi lại trong 5 phút từ khách hàng truy cập website, phân công chuyên viên CSKH."],
        ["password_resets", "PK: id", "email, token, otp, expires_at", "Quản lý mã OTP 6 chữ số và token ngẫu nhiên bảo mật phục vụ quy trình khôi phục mật khẩu tài khoản."]
    ]
    make_table(table_erd_headers, table_erd_rows, [1.3, 1.4, 1.6, 1.5])

    heading_3("Cơ chế Tự động Nâng cấp Cấu trúc (Auto-Migration) và Đồng bộ CSDL")
    body_p("Một điểm sáng tạo nổi bật trong mã nguồn tại tệp config/database.php là cơ chế Auto-Migration tự động. Khi hệ thống khởi chạy, nếu phát hiện cơ sở dữ liệu trên máy trạm mới chưa có đủ các cột hoặc dữ liệu khởi tạo của các tính năng mới (chẳng hạn các cột thanh toán VietQR payment_status, transaction_code trong bảng orders, các cột google_id, auth_provider trong bảng users, hoặc các mã voucher hệ thống như VPHONE500, FLAGSHIP2026), mã nguồn sẽ tự động thực thi các câu lệnh ALTER TABLE và INSERT IGNORE nhằm đảm bảo hệ thống luôn tương thích 100% mà không đòi hỏi lập trình viên phải can thiệp thủ công.")

    body_p("Song song đó, lớp DatabaseSyncTool tại database/db_sync_tool.php cung cấp khả năng tự động trích xuất (export) toàn bộ lược đồ và dữ liệu ra file database/db_phone_store.sql với bảng mã utf8mb4_unicode_ci và múi giờ chuẩn +07:00, tạo tiền đề cho quá trình tự động hóa DevOps ở Giai đoạn 4.")

    # Giai đoạn 2
    heading_2("2.2. Giai đoạn 2: Xây dựng Tầng Backend, Dịch vụ Nghiệp vụ và Hệ thống API")
    body_p("Tầng Backend đóng vai trò là khối óc điều phối toàn bộ luồng dữ liệu và thực thi logic nghiệp vụ của hệ thống. Nhóm phát triển đã xây dựng kiến trúc backend theo chuẩn hướng đối tượng (OOP) vững chắc với các thành phần chuyên biệt:")

    add_img("reports/diagram_dataflow_mvc.png", 
            "Hình 2.2: Sơ đồ luồng xử lý yêu cầu và luân chuyển dữ liệu chuẩn MVC")

    heading_3("a) Lớp Data Access Object (Models) & Bảo mật PDO")
    body_p("Tất cả các truy vấn cơ sở dữ liệu được bao bọc hoàn toàn bên trong 9 lớp Model chuyên trách tại thư mục app/models/. Hệ thống tuân thủ nghiêm ngặt nguyên tắc sử dụng PDO Prepared Statements với tham số ràng buộc (Parameter Binding), triệt tiêu hoàn toàn 100% nguy cơ tấn công SQL Injection:")
    bullet_p("Quản lý danh mục máy, thực hiện lấy thông tin máy theo slug, phân loại theo hãng, khấu trừ số lượng tồn kho tự động khi phát sinh đơn hàng (deductStock()) và hoàn lại kho nếu đơn bị hủy (restoreStock()).", "• ProductModel: ")
    bullet_p("Khởi tạo đơn hàng bên trong một Transaction của PDO ($pdo->beginTransaction()), ghi nhận đồng thời thông tin hóa đơn và các dòng chi tiết order_details, đảm bảo nếu có bất kỳ lỗi nào xảy ra thì toàn bộ giao dịch sẽ được Rollback an toàn.", "• OrderModel: ")
    bullet_p("Xác thực thông tin đăng nhập, đăng ký tài khoản với mật khẩu băm một chiều Bcrypt (password_hash(..., PASSWORD_BCRYPT)), quản lý hồ sơ nhân viên và cập nhật giỏ hàng lưu trữ vào trường cart_data.", "• UserModel: ")
    bullet_p("Động cơ tính toán chiết khấu voucher, kiểm tra điều kiện áp dụng về thời hạn sử dụng, ngưỡng đơn hàng tối thiểu và cập nhật số lần đã sử dụng (used_count).", "• VoucherModel: ")
    bullet_p("Thẩm định điều kiện khách hàng đã thực sự mua và nhận máy thành công (hasUserPurchased()) trước khi cho phép gửi đánh giá sao kèm nội dung nhận xét.", "• ReviewModel: ")

    heading_3("b) Tích hợp Cổng thanh toán Fintech số tự động (VietQR & MoMo)")
    body_p("V-Phone Store loại bỏ phương thức chuyển khoản thủ công truyền thống vốn đòi hỏi nhân viên phải kiểm tra sao kê ngân hàng bằng tay. Thay vào đó, hệ thống tích hợp hai giải pháp Fintech tự động hóa hoàn toàn:")

    add_img("reports/diagram_fintech_flow.png", 
            "Hình 2.3: Sơ đồ luồng thanh toán tự động VietQR Napas247 và MoMo Sandbox API v2")

    bullet_p("Dịch vụ VietQRService.php tự động tạo đường dẫn mã QR chuẩn Napas247 từ Open API ngân hàng MB Bank với giao diện compact2. Mã QR được nhúng sẵn chính xác số tài khoản (0388888899), tên thụ hưởng (V-PHONE STORE), số tiền cần thanh toán đến từng đồng và nội dung chuyển khoản định danh duy nhất theo cú pháp VPHONE <Mã đơn hàng>. Đi kèm với đó là Webhook lắng nghe tại api/vietqr-webhook.php nhận thông báo biến động số dư tức thì, tự động cập nhật đơn sang 'Đã thanh toán' trong thời gian dưới 1.5 giây mà không cần con người can thiệp.", "1. Cổng VietQR Napas247 Dynamic QR: ")
    bullet_p("Dịch vụ MoMoService.php kết nối trực tiếp cổng MoMo Developer Sandbox qua chuẩn API v2. Hệ thống tạo chuỗi chữ ký số bảo mật bằng thuật toán HMAC-SHA256 kết hợp giữa partnerCode, accessKey, orderId, amount và secretKey. Khi khách hàng quét ví MoMo thành công, cổng thanh toán sẽ kích hoạt Webhook IPN (Instant Payment Notification) gửi về api/momo-ipn.php để hoàn tất giao dịch.", "2. Ví điện tử MoMo API v2: ")

    heading_3("c) Hệ thống Phân hạng Hội viên Lũy tiến (Membership Tier System)")
    body_p("Lớp dịch vụ MembershipService.php chịu trách nhiệm điều khiển cơ chế phân hạng khách hàng thân thiết. Hệ thống liên tục tổng hợp tổng chi tiêu thực tế từ các đơn hàng đã giao thành công và tự động thăng hạng theo 4 nấc thang:")
    bullet_p("Tổng chi tiêu lũy kế dưới 100.000.000 VNĐ. Khách hàng được hưởng chính sách bảo hành tiêu chuẩn và miễn phí vận chuyển toàn quốc.", "• Thành Viên Tiêu Chuẩn (Standard): ")
    bullet_p("Tổng chi tiêu từ 100.000.000 VNĐ trở lên. Nhận thẻ VIP Bạc ánh kim loại trong trang Profile cá nhân, tự động giảm 2% trên mọi đơn hàng và nhận bộ voucher độc quyền.", "• Thành Viên Bạc (Silver): ")
    bullet_p("Tổng chi tiêu từ 300.000.000 VNĐ trở lên. Thẻ VIP Vàng đẳng cấp, giảm 3% toàn đơn và ưu tiên tiếp nhận bảo hành thiết bị.", "• Thành Viên Vàng (Gold): ")
    bullet_p("Tổng chi tiêu từ 500.000.000 VNĐ trở lên. Hạng VIP cao nhất, chiết khấu trực tiếp 5%, tặng quà sinh nhật Flagship và đường dây nóng hỗ trợ kỹ thuật 24/7.", "• Thành Viên Kim Cương (Diamond): ")

    heading_3("d) Hệ thống Trợ lý Trí tuệ Nhân tạo Đa Chuyên Môn (Multi-Role AI Neural Assistant)")
    body_p("Đặc tính kỹ thuật đột phá nhất của dự án là Động cơ Trí tuệ Nhân tạo đa chuyên môn được xây dựng tại api/role_ai.php (hơn 2.100 dòng mã nguồn). Khác biệt hoàn toàn với các chatbot thương mại điện tử thông thường chỉ trả lời các câu hỏi đóng chung chung, AI của V-Phone Store phân bổ quyền hạn và ngữ cảnh theo 6 vai trò nghiệp vụ thực tế:")

    add_img("reports/diagram_role_ai_flow.png", 
            "Hình 2.4: Sơ đồ kiến trúc Động cơ Trợ lý AI đa vai trò (Multi-Role AI Neural Assistant)")

    ai_headers = ["Role Key", "Tên Chức Danh AI", "Đối Tượng Người Dùng", "Nhiệm Vụ Nghiệp Vụ Cốt Lõi Được Tích Hợp"]
    ai_rows = [
        ["admin", "Executive Admin AI", "Quản trị viên tối cao", "Phân tích doanh thu hôm nay, tuần này, tháng này từ CSDL; dự báo xu hướng tiêu dùng; tư vấn kế hoạch nhập hàng Flagship."],
        ["sales", "Sales Consultant AI", "Nhân viên bán hàng", "Gợi ý thiết bị thay thế theo ngân sách khách hàng, cung cấp kịch bản up-sell phụ kiện sạc/ốp, tra cứu voucher còn lượt dùng."],
        ["warehouse", "Warehouse AI", "Quản lý kho vận", "Kiểm kê số lượng tồn kho theo phiên bản màu sắc/bộ nhớ, đưa ra danh sách cảnh báo các mã máy có số lượng tồn <= 5 để nhập hàng."],
        ["warranty", "Tech & Care AI", "Kỹ thuật viên bảo hành", "Tra cứu thời hạn bảo hành thiết bị qua số IMEI, chẩn đoán lỗi màn hình/pin/nguồn, hướng dẫn quy trình thẩm định 1 đổi 1."],
        ["support", "Support Desk AI", "Chăm sóc khách hàng", "Cung cấp kịch bản phản hồi khách hàng chuẩn 5 sao, tiếp nhận xử lý yêu cầu hoàn tiền, khiếu nại giao hàng chậm."],
        ["customer", "Cyber AI Khách Hàng", "Người mua sắm trực tuyến", "Trợ lý tư vấn cấu hình máy Flagship 24/7, hướng dẫn thủ tục trả góp 0%, đề xuất model điện thoại tối ưu theo tài chính."]
    ]
    make_table(ai_headers, ai_rows, [1.1, 1.4, 1.4, 1.9])

    body_p("Cơ chế vận hành của AI Engine bao gồm 3 bước: (1) Nhận diện định danh vai trò và kiểm tra ma trận phân quyền RBAC; (2) Thực thi các câu lệnh SQL trực tiếp vào MySQL để lấy số liệu thực tế thời gian thực (doanh thu, đơn hàng, tồn kho, hồ sơ IMEI); (3) Tổng hợp dữ liệu thực tế vào khối Context Prompt nghiệp vụ để tạo ra câu trả lời chi tiết, chính xác tuyệt đối theo tình trạng cửa hàng.")

    # Giai đoạn 3
    heading_2("2.3. Giai đoạn 3: Xây dựng Giao diện Người dùng (Storefront) & Trang Quản trị (Admin)")
    body_p("Giao diện người dùng của V-Phone Store được thiết kế theo phong cách hiện đại, tinh tế dành riêng cho phân khúc thiết bị di động Flagship cao cấp, đảm bảo tính thẩm mỹ, tiện ích và tương thích trên mọi kích thước màn hình:")

    heading_3("a) Giao diện Storefront cho Khách hàng & Chế độ Cyber Dark Mode")
    body_p("Hệ thống hỗ trợ chuyển đổi mượt mà giữa hai giao diện Light Mode và Cyber Dark Mode thông qua hệ thống CSS Custom Variables tại assets/css/style.css. Chế độ Cyber Dark Mode được nghiên cứu đặc biệt với gam màu tối sâu (#0b0f19, #111827), giúp khách hàng duyệt web ban đêm không bị mỏi mắt và tiết kiệm điện năng đối với các thiết bị sử dụng màn hình OLED.")

    add_img("reports/screenshots/image3.png", 
            "Hình 2.5: Giao diện Trang chủ Storefront V-Phone Store chuẩn Flagship")

    body_p("Trang chủ hệ thống (app/views/home.php) bố trí Banner slider nổi bật các siêu phẩm công nghệ, danh mục thương hiệu quốc tế với logo vector, danh sách sản phẩm bán chạy kèm nhãn giảm giá và thanh tìm kiếm trực quan.")

    heading_3("b) Trang Chi tiết Sản phẩm & Bộ Đổi Màu Sắc Thời Gian Thực")
    body_p("Tại trang chi tiết sản phẩm (app/views/product_detail.php), khách hàng được trải nghiệm tính năng độc đáo: khi nhấn chọn từng phiên bản màu sắc (chẳng hạn Titan Sa Mạc, Titan Tự Nhiên, Titan Đen, Titan Trắng trên iPhone 16 Pro Max), hình ảnh sản phẩm lớn cùng tên phiên bản màu sẽ tự động cập nhật ngay lập tức mà không cần tải lại trang nhờ cơ chế ánh xạ tại app/data/product_color_map.php kết hợp JavaScript DOM:")

    add_img("reports/screenshots/image9.png", 
            "Hình 2.6: Giao diện Chi tiết Sản phẩm Flagship với bộ đổi màu sắc thời gian thực")

    heading_3("c) Bàn Cân So Sánh Cấu Hình Flagship Đối Đầu (Compare Tool)")
    body_p("Để hỗ trợ người dùng đưa ra quyết định mua hàng chính xác nhất, nhóm phát triển đã xây dựng công cụ so sánh cấu hình tại app/views/compare.php. Người dùng có thể chọn song song hai mẫu điện thoại bất kỳ (ví dụ: iPhone 16 Pro Max đối đầu Samsung Galaxy S24 Ultra) để đặt lên bàn cân chi tiết:")

    add_img("reports/screenshots/image12.png", 
            "Hình 2.7: Giao diện Bàn cân so sánh thông số kỹ thuật và giá bán giữa 2 dòng máy Flagship")

    body_p("Bảng so sánh thể hiện trực quan từng thông số vi xử lý CPU, công nghệ màn hình, tần số quét, cụm camera trước/sau, dung lượng pin, chuẩn sạc nhanh và tính toán mức chênh lệch tài chính giữa hai máy.")

    heading_3("d) Giỏ Hàng Thời Gian Thực & Quy Trình Thanh Toán Đa Bước")
    body_p("Quy trình mua hàng được thiết kế tối giản nhưng chặt chẽ qua hai bước: Giỏ hàng (app/views/cart.php) và Thanh toán (app/views/checkout.php):")

    add_img("reports/screenshots/image10.png", 
            "Hình 2.8: Giao diện Giỏ hàng thời gian thực kết hợp Động cơ Voucher chiết khấu")

    bullet_p("Giỏ hàng được quản lý thông qua PHP Native Session, tự động tính toán tổng tiền tạm tính, chiết khấu thành viên VIP và áp dụng mã giảm giá voucher với thông báo lỗi rõ ràng nếu chưa đạt ngưỡng đơn tối thiểu.", "• Giỏ hàng thông minh: ")
    bullet_p("Khách hàng có thể lưu nhiều địa chỉ nhận hàng trong sổ địa chỉ (user_addresses), chọn phương thức thanh toán linh hoạt giữa VietQR Napas247, ví MoMo hoặc COD.", "• Sổ địa chỉ & Thanh toán: ")

    heading_3("e) Tra Cứu Tiến Độ Đơn Hàng & Bảo Hành Điện Tử IMEI")
    body_p("Nhằm nâng cao tính minh bạch sau bán hàng, V-Phone Store cung cấp hai công cụ tra cứu công khai trực tuyến:")

    add_img("reports/screenshots/image11.png", 
            "Hình 2.9: Giao diện Tra cứu tiến độ đơn hàng trực tuyến 4 bước (#VP-xx)")

    add_img("reports/screenshots/image13.png", 
            "Hình 2.10: Giao diện Tra cứu bảo hành điện tử theo số IMEI thiết bị")

    bullet_p("Tra cứu nhanh bằng mã đơn hàng (#VP-xx) và số điện thoại, hiển thị sơ đồ trạng thái 4 nấc: Tiếp nhận -> Đang xử lý -> Đang giao hàng -> Hoàn tất. Nếu đơn hàng bị hủy, lý do hủy chi tiết từ ban quản trị sẽ được hiển thị minh bạch.", "• Tra cứu đơn hàng: ")
    bullet_p("Khách hàng nhập số IMEI để tra cứu thời hạn bảo hành chính hãng, gói bảo hiểm rơi vỡ và gửi yêu cầu thẩm định 1 đổi 1 trực tuyến khi thiết bị gặp sự cố kỹ thuật.", "• Tra cứu bảo hành IMEI: ")

    heading_3("f) Hệ Thống Quản Trị Trung Tâm (Admin CMS) & Ma Trận Phân Quyền RBAC")
    body_p("Phân hệ quản trị tại thư mục admin/ được xây dựng chuyên nghiệp phục vụ công tác điều hành vận hành cửa hàng. Hệ thống áp dụng cơ chế Phân quyền kiểm soát truy cập dựa trên vai trò (Role-Based Access Control) được khai báo tại admin/includes/permissions.php:")

    rbac_headers = ["Module Quản Trị", "Permission Key", "Super Admin", "Quản Lý", "Bán Hàng", "Thủ Kho", "Kỹ Thuật", "CSKH"]
    rbac_rows = [
        ["Quản lý Đơn hàng", "orders", "Toàn quyền", "Toàn quyền", "Toàn quyền", "Không", "Không", "Xem"],
        ["Quản lý Sản phẩm", "products", "Toàn quyền", "Toàn quyền", "Xem", "Xem", "Không", "Không"],
        ["Quản lý Kho & IMEI", "inventory", "Toàn quyền", "Toàn quyền", "Không", "Toàn quyền", "Không", "Không"],
        ["Bảo hành & Sửa chữa", "warranties", "Toàn quyền", "Toàn quyền", "Không", "Không", "Toàn quyền", "Xem"],
        ["Đánh giá & Phản hồi", "reviews", "Toàn quyền", "Toàn quyền", "Toàn quyền", "Không", "Không", "Toàn quyền"],
        ["Mã giảm giá Voucher", "vouchers", "Toàn quyền", "Toàn quyền", "Toàn quyền", "Không", "Không", "Không"],
        ["Tin tức & Bài viết", "news", "Toàn quyền", "Toàn quyền", "Không", "Không", "Không", "Không"],
        ["Hỗ trợ & Callback", "support", "Toàn quyền", "Toàn quyền", "Không", "Không", "Không", "Toàn quyền"],
        ["Trợ lý AI chuyên môn", "ai_assistant", "Toàn quyền", "Toàn quyền", "Toàn quyền", "Toàn quyền", "Toàn quyền", "Toàn quyền"],
        ["Quản lý Nhân sự", "staff", "Toàn quyền", "Không", "Không", "Không", "Không", "Không"],
        ["Đồng bộ Cơ sở dữ liệu", "db_sync", "Toàn quyền", "Không", "Không", "Không", "Không", "Không"]
    ]
    make_table(rbac_headers, rbac_rows, [1.3, 1.0, 0.65, 0.65, 0.65, 0.65, 0.65, 0.65])

    # Giai đoạn 4
    heading_2("2.4. Giai đoạn 4: Kiểm thử, Tối ưu hóa và Quy trình Triển khai (Testing & DevOps)")
    body_p("Giai đoạn 4 tập trung vào việc bảo đảm chất lượng phần mềm, tối ưu hóa tài nguyên media và giải quyết triệt để bài toán đồng bộ mã nguồn kèm dữ liệu giữa các môi trường làm việc khác nhau của nhóm phát triển:")

    heading_3("a) Chiến lược Kiểm thử Chất lượng Phần mềm (Quality Assurance)")
    body_p("Nhóm phát triển đã tiến hành kiểm thử toàn diện trên cả hai môi trường Localhost và Staging:")
    bullet_p("Kiểm tra tính đúng đắn của chu trình Giỏ hàng -> Áp mã Voucher -> Chọn địa chỉ -> Tạo đơn hàng. Đảm bảo số lượng tồn kho của từng màu máy được trừ chính xác và không có tình trạng bán vượt quá số lượng tồn (Over-selling).", "• Kiểm thử chức năng (Functional Testing): ")
    bullet_p("Sử dụng chế độ Demo Simulator được tích hợp sẵn trong VietQRService để giả lập tín hiệu Webhook ngân hàng bắn về với các kịch bản: Chuyển khoản đúng số tiền, chuyển khoản thiếu số tiền và chuyển khoản sai cú pháp nội dung.", "• Kiểm thử cổng thanh toán (Payment Testing): ")
    bullet_p("Kiểm thử tính năng đăng nhập với vai trò Nhân viên Bán hàng và Kỹ thuật viên, cố tình truy cập vào các đường dẫn quản trị bị cấm (như admin/staff.php hoặc admin/db_sync.php) để xác nhận hệ thống chặn truy cập và chuyển hướng về trang thông báo lỗi 403 Forbidden chính xác.", "• Kiểm thử bảo mật phân quyền (RBAC Testing): ")

    heading_3("b) Tự động hóa Xử lý Hình ảnh Sản phẩm bằng Python PIL (Transparent Background)")
    body_p("Một thách thức kỹ thuật lớn trong quá trình phát triển chế độ Cyber Dark Mode là hầu hết các ảnh sản phẩm từ hãng đều có phông nền trắng đặc. Khi hiển thị trên nền tối, các bức ảnh này xuất hiện viền hình chữ nhật thô ráp, làm giảm tính thẩm mỹ của trang web. Nhóm đã viết script Python scripts/make_transparent_single.py sử dụng thư viện Pillow để tự động quét 4 góc ảnh, tìm vùng màu nền trắng/xám sáng và thực hiện thuật toán Flood Fill tách nền thành định dạng PNG trong suốt hoàn toàn, giúp sản phẩm hòa quyện tuyệt đẹp vào nền tối của giao diện.")

    heading_3("c) Giải pháp Đồng bộ DevOps Hai Chiều Đa Nền Tảng (Cross-Platform DevOps)")
    body_p("Do đặc thù nhóm phát triển làm việc luân phiên trên hai môi trường: máy tính cá nhân macOS (dùng XAMPP) và máy tính phòng thực hành trường học chạy hệ điều hành Windows, vấn đề xung đột dữ liệu cơ sở dữ liệu MySQL và đường dẫn thư mục thường xuyên phát sinh. Nhóm đã xây dựng bộ giải pháp DevOps tự động hóa hoàn chỉnh:")

    add_img("reports/diagram_devops_sync.png", 
            "Hình 2.11: Sơ đồ chu trình DevOps đồng bộ tự động Mã nguồn và CSDL đa nền tảng")

    bullet_p("Khi lập trình viên hoàn thành một tính năng trên macOS, chỉ cần chạy lệnh ./scripts/sync_push.sh. Script sẽ tự động gọi db_sync_tool.php xuất toàn bộ dữ liệu MySQL ra file database/db_phone_store.sql, sau đó tự động thêm thời gian commit và đẩy (git push) lên GitHub repository.", "1. Chiều Đẩy Dữ Liệu (Push Pipeline): ")
    bullet_p("Khi đến lớp học trên máy tính Windows, sinh viên chỉ cần double-click chuột vào file scripts/sync_pull.bat (hoặc chạy ./scripts/sync_pull.sh trên Linux/macOS). Script sẽ tự động kéo (git pull) toàn bộ mã nguồn mới nhất, nạp đè file database/db_phone_store.sql vào MySQL của XAMPP và thông báo sẵn sàng chạy ứng dụng.", "2. Chiều Kéo Dữ Liệu (Pull Pipeline): ")
    bullet_p("Hỗ trợ nút bấm 'Xuất Dữ Liệu' và 'Nạp Dữ Liệu' trực tiếp trên trình duyệt web tại trang admin/db_sync.php dành cho người dùng không muốn thao tác qua cửa sổ dòng lệnh Terminal.", "3. Giao diện Web Sync trực quan: ")

    # ==========================================
    # PHẦN III: ĐÁNH GIÁ KẾT QUẢ VÀ HƯỚNG PHÁT TRIỂN
    # ==========================================
    print("[5/5] Đang viết Phần III: Đánh giá kết quả và Hướng phát triển...")
    heading_1("PHẦN III: ĐÁNH GIÁ KẾT QUẢ VÀ HƯỚNG PHÁT TRIỂN")

    heading_2("3.1. Các kết quả kỹ thuật đạt được")
    body_p("Sau chu kỳ phát triển liên tục và nghiêm túc, dự án V-Phone Store đã hoàn thành 100% các mục tiêu kỹ thuật và nghiệp vụ đề ra:")
    bullet_p("Xây dựng thành công hệ sinh thái thương mại điện tử chuyên sâu smartphone Flagship với cấu trúc PHP MVC thuần sạch sẽ, dễ bảo trì và mở rộng.", "1. Hệ thống hoàn chỉnh khép kín: ")
    bullet_p("Tốc độ tải trang phản hồi cực nhanh dưới 0.8 giây, giao diện Responsive tương thích trên mọi kích thước màn hình từ điện thoại, máy tính bảng đến máy tính để bàn.", "2. Hiệu năng vượt trội: ")
    bullet_p("Triển khai thành công 2 cổng thanh toán số tự động VietQR Napas247 và MoMo API v2, giảm thiểu 100% thời gian đối soát thủ công của ban quản trị.", "3. Tự động hóa tài chính: ")
    bullet_p("Tích hợp sáng tạo Động cơ AI 6 vai trò nghiệp vụ với khả năng trích xuất số liệu thời gian thực từ Database, mở ra trải nghiệm hỗ trợ thông minh cho cả khách hàng lẫn nhân viên nội bộ.", "4. Ứng dụng Trí tuệ Nhân tạo thực tế: ")
    bullet_p("Đảm bảo an toàn bảo mật thông tin với PDO Prepared Statements, mã hóa mật khẩu Bcrypt và cơ chế phân quyền nhân sự Granular RBAC.", "5. Bảo mật dữ liệu cao: ")

    heading_2("3.2. Hướng mở rộng và nâng cấp tương lai")
    body_p("Nhằm nâng tầm hệ thống đáp ứng quy mô chuỗi bán lẻ hàng trăm cửa hàng trên toàn quốc, nhóm phát triển đã vạch ra lộ trình nâng cấp trong các giai đoạn tiếp theo:")
    bullet_p("Tích hợp thêm cổng thẻ quốc tế Visa/Mastercard qua VNPay hoặc Stripe phục vụ nhóm khách hàng người nước ngoài.", "• Cổng thanh toán quốc tế: ")
    bullet_p("Triển khai giao thức Web Push Notifications (Service Workers) gửi thông báo đẩy trực tiếp lên màn hình điện thoại khách hàng khi đơn hàng bắt đầu được giao.", "• Thông báo đẩy thời gian thực: ")
    bullet_p("Xây dựng ứng dụng di động V-Phone Store Mobile App trên nền tảng Flutter hoặc React Native, kết nối trực tiếp với backend hiện tại thông qua hệ thống RESTful API.", "• Ứng dụng di động Native App: ")
    bullet_p("Nâng cấp động cơ gợi ý sản phẩm áp dụng mô hình máy học (Collaborative Filtering) dựa trên lịch sử xem máy và hành vi click chuột của người dùng.", "• Động cơ gợi ý thông minh (AI Recommendation): ")

    # Hộp tổng kết đồ án
    callout_box("TỔNG KẾT ĐỒ ÁN V-PHONE STORE 2026", [
        "Hệ thống đã sẵn sàng đưa vào vận hành thực tế tại môi trường bán lẻ thiết bị di động Flagship.",
        "Toàn bộ mã nguồn, cơ sở dữ liệu và tài liệu kỹ thuật được quản lý chuẩn mực trên Git/GitHub.",
        "Nhóm sinh viên thực hiện: Huỳnh Bá Lực & Võ Minh Hiếu trân trọng cảm ơn sự hướng dẫn tận tình của Quý Thầy/Cô Bộ môn Kỹ thuật Phần mềm!"
    ])

    # Lưu file
    output_path = "reports/BaoCao_Qua_Trinh_Phat_Trien_San_Pham.docx"
    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    doc.save(output_path)
    print(f"\n[HOÀN THÀNH] File báo cáo kỹ thuật Word đã được xuất thành công tại:\n-> {output_path}")

if __name__ == '__main__':
    generate_report()
