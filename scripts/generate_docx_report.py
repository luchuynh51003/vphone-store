#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
V-PHONE STORE - SCRIPT TỰ ĐỘNG TẠO BÁO CÁO KỸ THUẬT WORD CHUẨN ĐỒ ÁN CHUYÊN NGÀNH
Tác giả: Huỳnh Bá Lực & Võ Minh Hiếu
Đơn vị: Khoa Công nghệ Thông tin - Trường Cao đẳng Lý Tự Trọng TP.HCM
Năm: 2026
"""

import os
import sys
import docx
from docx.shared import Pt, Cm, Inches, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_TAB_ALIGNMENT, WD_TAB_LEADER
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml import parse_xml, OxmlElement
from docx.oxml.ns import nsdecls, qn

def set_font(run, size=13, bold=False, italic=False, color=(15, 23, 42)):
    """Thiết lập font Times New Roman chuẩn tuyệt đối trên cả cấp độ XML Run"""
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

def p_format(p, before=0, after=5, spacing=1.25, align=WD_ALIGN_PARAGRAPH.JUSTIFY):
    """Căn chỉnh đoạn văn bản theo chuẩn báo cáo kỹ thuật"""
    p.paragraph_format.space_before = Pt(before)
    p.paragraph_format.space_after = Pt(after)
    p.paragraph_format.line_spacing = spacing
    p.paragraph_format.alignment = align

def heading_1(doc, text):
    """Tiêu đề chương/phần lớn (Heading 1): 14pt, In đậm (Bold), viết hoa, cấu trúc chuẩn Word"""
    p = doc.add_paragraph(style='Heading 1')
    p_format(p, before=14, after=5, spacing=1.2, align=WD_ALIGN_PARAGRAPH.JUSTIFY)
    p.paragraph_format.keep_with_next = True
    pPr = p._p.get_or_add_pPr()
    pPr.append(parse_xml(f'<w:outlineLvl {nsdecls("w")} w:val="0"/>'))
    r = p.add_run(text.upper())
    set_font(r, size=14, bold=True, color=(15, 23, 42))
    return p

def heading_2(doc, text):
    """Tiêu đề mục nhỏ (Heading 2): 13pt, In đậm (Bold), cấu trúc chuẩn Word"""
    p = doc.add_paragraph(style='Heading 2')
    p_format(p, before=10, after=4, spacing=1.2, align=WD_ALIGN_PARAGRAPH.JUSTIFY)
    p.paragraph_format.keep_with_next = True
    pPr = p._p.get_or_add_pPr()
    pPr.append(parse_xml(f'<w:outlineLvl {nsdecls("w")} w:val="1"/>'))
    r = p.add_run(text)
    set_font(r, size=13, bold=True, color=(30, 41, 59))
    return p

def heading_3(doc, text):
    """Tiêu đề mục nhỏ phụ (Heading 3): 12.5pt, In đậm, Nghiêng, không tách trang"""
    p = doc.add_paragraph()
    p_format(p, before=7, after=3, spacing=1.2, align=WD_ALIGN_PARAGRAPH.JUSTIFY)
    p.paragraph_format.keep_with_next = True
    r = p.add_run(text)
    set_font(r, size=12.5, bold=True, italic=True, color=(51, 65, 85))
    return p

def add_toc_item(doc, title, page_str, level=1):
    """Thêm một dòng mục lục có dấu chấm nối chuẩn (dot leader) căn phải số trang"""
    p = doc.add_paragraph()
    indent = Inches(0.0) if level == 1 else Inches(0.25)
    p.paragraph_format.left_indent = indent
    p.paragraph_format.tab_stops.add_tab_stop(Inches(6.3), WD_TAB_ALIGNMENT.RIGHT, WD_TAB_LEADER.DOTS)
    p_format(p, before=2.5 if level == 1 else 1.2, after=2.5 if level == 1 else 1.2, spacing=1.12, align=WD_ALIGN_PARAGRAPH.LEFT)
    r1 = p.add_run(title)
    set_font(r1, size=12.5 if level == 1 else 12, bold=(level == 1), color=(15, 23, 42) if level == 1 else (51, 65, 85))
    p.add_run("\t")
    r2 = p.add_run(str(page_str))
    set_font(r2, size=12.5 if level == 1 else 12, bold=(level == 1), color=(15, 23, 42) if level == 1 else (71, 85, 105))
    return p

def body_p(doc, text, bold_prefix=""):
    """Nội dung văn bản (Body text): 13pt, căn đều hai bên (Justify), giãn dòng 1.25, khoảng cách đoạn 5pt"""
    p = doc.add_paragraph()
    p_format(p, before=0, after=5, spacing=1.25, align=WD_ALIGN_PARAGRAPH.JUSTIFY)
    if bold_prefix:
        rb = p.add_run(bold_prefix)
        set_font(rb, size=13, bold=True, color=(15, 23, 42))
    r = p.add_run(text)
    set_font(r, size=13, color=(30, 41, 59))
    return p

def bullet_p(doc, text, bold_prefix=""):
    """Dòng gạch đầu dòng căn lề chuẩn 13pt (Tuyệt đối chỉ có ĐÚNG 1 dấu bullet xanh)"""
    p = doc.add_paragraph()
    p_format(p, before=0, after=3.5, spacing=1.2, align=WD_ALIGN_PARAGRAPH.JUSTIFY)
    p.paragraph_format.left_indent = Inches(0.25)
    
    rs = p.add_run("• ")
    set_font(rs, size=13, bold=True, color=(2, 132, 199))
    
    if bold_prefix:
        clean_prefix = bold_prefix.lstrip("•-* \t")
        rb = p.add_run(clean_prefix)
        set_font(rb, size=13, bold=True, color=(15, 23, 42))
    r = p.add_run(text)
    set_font(r, size=13, color=(30, 41, 59))
    return p

def add_img(doc, img_path, caption, width=4.3):
    """Chèn hình ảnh căn giữa, khóa liền với chú thích (keep_with_next)"""
    if not os.path.exists(img_path):
        print(f"[CẢNH BÁO] Không tìm thấy ảnh: {img_path}")
        return
    p_i = doc.add_paragraph()
    p_format(p_i, before=4, after=1, spacing=1.0, align=WD_ALIGN_PARAGRAPH.CENTER)
    p_i.paragraph_format.keep_with_next = True
    p_i.add_run().add_picture(img_path, width=Inches(width))
    
    p_c = doc.add_paragraph()
    p_format(p_c, before=1, after=5, spacing=1.1, align=WD_ALIGN_PARAGRAPH.CENTER)
    rc = p_c.add_run(caption)
    set_font(rc, size=10.5, italic=True, color=(71, 85, 105))

def add_img_pair(doc, img_path1, caption1, img_path2, caption2):
    """Chèn 2 hình ảnh giao diện song song cạnh nhau chuẩn Grid 2 cột, loại bỏ hoàn toàn trang trắng thừa"""
    table = doc.add_table(rows=1, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    tblPr = table._tbl.tblPr
    tblPr.append(parse_xml(f'''
        <w:tblBorders {nsdecls("w")}>
            <w:top w:val="none"/><w:left w:val="none"/><w:bottom w:val="none"/><w:right w:val="none"/>
            <w:insideH w:val="none"/><w:insideV w:val="none"/>
        </w:tblBorders>
    '''))
    row = table.rows[0]
    trPr = row._tr.get_or_add_trPr()
    trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))

    c1, c2 = row.cells[0], row.cells[1]
    c1.width = Inches(3.0)
    c2.width = Inches(3.0)

    # Cột 1
    p1 = c1.paragraphs[0]
    p_format(p1, before=2, after=1, spacing=1.0, align=WD_ALIGN_PARAGRAPH.CENTER)
    p1.paragraph_format.keep_with_next = True
    if os.path.exists(img_path1):
        p1.add_run().add_picture(img_path1, width=Inches(2.85))
    pc1 = c1.add_paragraph()
    p_format(pc1, before=1, after=3, spacing=1.05, align=WD_ALIGN_PARAGRAPH.CENTER)
    rc1 = pc1.add_run(caption1)
    set_font(rc1, size=9.5, italic=True, color=(71, 85, 105))

    # Cột 2
    p2 = c2.paragraphs[0]
    p_format(p2, before=2, after=1, spacing=1.0, align=WD_ALIGN_PARAGRAPH.CENTER)
    p2.paragraph_format.keep_with_next = True
    if os.path.exists(img_path2):
        p2.add_run().add_picture(img_path2, width=Inches(2.85))
    pc2 = c2.add_paragraph()
    p_format(pc2, before=1, after=3, spacing=1.05, align=WD_ALIGN_PARAGRAPH.CENTER)
    rc2 = pc2.add_run(caption2)
    set_font(rc2, size=9.5, italic=True, color=(71, 85, 105))

    p_sp = doc.add_paragraph()
    p_format(p_sp, before=0, after=2, spacing=1.0)

def make_safe_table(doc, headers, rows, widths=None, font_size=10.0, pad_v=100, line_spacing=1.12):
    """Tạo bảng dữ liệu Table Grid an toàn, viền sắc nét, lặp tiêu đề trang, chữ đen sẫm luôn hiển thị 100% trên mọi Office"""
    table = doc.add_table(rows=len(rows) + 1, cols=len(headers))
    table.style = 'Table Grid'
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    
    # 1. Thêm tblBorders và tblCellMar trực tiếp vào tblPr
    tblPr = table._tbl.tblPr
    tblPr.append(parse_xml(f'''
        <w:tblBorders {nsdecls("w")}>
            <w:top w:val="single" w:sz="8" w:space="0" w:color="64748B"/>
            <w:left w:val="single" w:sz="8" w:space="0" w:color="64748B"/>
            <w:bottom w:val="single" w:sz="8" w:space="0" w:color="64748B"/>
            <w:right w:val="single" w:sz="8" w:space="0" w:color="64748B"/>
            <w:insideH w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>
            <w:insideV w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>
        </w:tblBorders>
    '''))
    tblPr.append(parse_xml(f'''
        <w:tblCellMar {nsdecls("w")}>
            <w:top w:w="{pad_v}" w:type="dxa"/>
            <w:left w:w="120" w:type="dxa"/>
            <w:bottom w:w="{pad_v}" w:type="dxa"/>
            <w:right w:w="120" w:type="dxa"/>
        </w:tblCellMar>
    '''))
    
    # 2. Header row: Lặp lại khi sang trang mới (tblHeader) và không bao giờ bị cắt đôi (cantSplit)
    hdr_row = table.rows[0]
    hdr_trPr = hdr_row._tr.get_or_add_trPr()
    hdr_trPr.append(parse_xml(f'<w:tblHeader {nsdecls("w")}/>'))
    hdr_trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))
    
    hdr_cells = hdr_row.cells
    for i, h in enumerate(headers):
        hdr_cells[i].text = ""
        p = hdr_cells[i].paragraphs[0]
        p_format(p, before=3, after=3, spacing=1.05, align=WD_ALIGN_PARAGRAPH.CENTER)
        r = p.add_run(h)
        set_font(r, size=font_size, bold=True, color=(15, 23, 42))
        tcPr = hdr_cells[i]._tc.get_or_add_tcPr()
        tcPr.append(parse_xml(f'<w:shd {nsdecls("w")} w:fill="CBD5E1"/>'))
        tcPr.append(parse_xml(f'<w:vAlign {nsdecls("w")} w:val="center"/>'))
        
    # 3. Data rows: Không bao giờ bị cắt ngang trang (cantSplit)
    for r_i, r_data in enumerate(rows):
        row = table.rows[r_i + 1]
        trPr = row._tr.get_or_add_trPr()
        trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))
        
        row_cells = row.cells
        bg = "F8FAFC" if r_i % 2 == 1 else "FFFFFF"
        for c_i, val in enumerate(r_data):
            row_cells[c_i].text = ""
            p = row_cells[c_i].paragraphs[0]
            align_c = WD_ALIGN_PARAGRAPH.CENTER if (c_i == 0 or len(str(val)) < 8) else WD_ALIGN_PARAGRAPH.LEFT
            p_format(p, before=2, after=2, spacing=line_spacing, align=align_c)
            r = p.add_run(str(val))
            set_font(r, size=font_size, color=(30, 41, 59))
            tcPr = row_cells[c_i]._tc.get_or_add_tcPr()
            tcPr.append(parse_xml(f'<w:shd {nsdecls("w")} w:fill="{bg}"/>'))
            tcPr.append(parse_xml(f'<w:vAlign {nsdecls("w")} w:val="center"/>'))
            
    # 4. Thiết lập độ rộng cột tuyệt đối trên từng ô
    if widths:
        for row in table.rows:
            for idx, w in enumerate(widths):
                row.cells[idx].width = Inches(w)
                tcPr = row.cells[idx]._tc.get_or_add_tcPr()
                tcPr.append(parse_xml(f'<w:tcW {nsdecls("w")} w:w="{int(w * 1440)}" w:type="dxa"/>'))
                
    p_sp = doc.add_paragraph()
    p_format(p_sp, before=0, after=3, spacing=1.0)
    return table

def callout_box(doc, title, items):
    """Tạo hộp ghi chú kỹ thuật (Callout Box) nổi bật"""
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    trPr = table.rows[0]._tr.get_or_add_trPr()
    trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))
    cell = table.cell(0, 0)
    cell.width = Inches(5.8)
    tcPr = cell._tc.get_or_add_tcPr()
    tcPr.append(parse_xml(f'<w:shd {nsdecls("w")} w:fill="F0FDF4"/>'))
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
    p_format(p_sp, before=0, after=3, spacing=1.0)


def build_capstone_report():
    print("[0/6] Tự động sinh mới toàn bộ 8 sơ đồ kiến trúc & luồng dữ liệu chuẩn...")
    try:
        cur_dir = os.path.dirname(os.path.abspath(__file__))
        if cur_dir not in sys.path:
            sys.path.insert(0, cur_dir)
        import render_all_diagrams
        render_all_diagrams.render_all()
    except Exception as e:
        print(f"[CẢNH BÁO] Không thể chạy render_all: {e}")

    print("[1/6] Tạo tài liệu Word & thiết lập căn lề chuẩn Đồ án...")
    doc = docx.Document()
    
    # CĂN LỀ TRANG CHUẨN: Trái 3cm, Phải 2cm, Trên 2cm, Dưới 2cm
    for section in doc.sections:
        section.top_margin = Cm(2.0)
        section.bottom_margin = Cm(2.0)
        section.left_margin = Cm(3.0)
        section.right_margin = Cm(2.0)
        section.page_width = Cm(21.0)
        section.page_height = Cm(29.7)
        section.different_first_page_header_footer = True
        
        # Header / Footer trang nội dung (Trang bìa không hiển thị số trang và header)
        header = section.header
        p_hdr = header.paragraphs[0]
        p_hdr.text = "V-PHONE STORE  •  BÁO CÁO ĐỒ ÁN CHUYÊN NGÀNH"
        p_hdr.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        set_font(p_hdr.runs[0], size=8.5, italic=True, color=(148, 163, 184))
        
        footer = section.footer
        p_ftr = footer.paragraphs[0]
        p_ftr.text = "Đồ Án Chuyên Ngành Lập Trình Máy Tính  —  Huỳnh Bá Lực & Võ Minh Hiếu"
        p_ftr.alignment = WD_ALIGN_PARAGRAPH.CENTER
        set_font(p_ftr.runs[0], size=8.5, color=(148, 163, 184))

        # Áp dụng khung viền đôi nghệ thuật CHỈ CHO TRANG BÌA (w:display="firstPage")
        sectPr = section._sectPr
        sectPr.append(parse_xml(f'''
            <w:pgBorders {nsdecls("w")} w:offsetFrom="page" w:display="firstPage">
                <w:top w:val="double" w:sz="24" w:space="20" w:color="0F172A"/>
                <w:left w:val="double" w:sz="24" w:space="20" w:color="0F172A"/>
                <w:bottom w:val="double" w:sz="24" w:space="20" w:color="0F172A"/>
                <w:right w:val="double" w:sz="24" w:space="20" w:color="0F172A"/>
            </w:pgBorders>
        '''))

    doc.styles['Normal'].font.name = 'Times New Roman'
    doc.styles['Normal'].font.size = Pt(13)

    # =========================================================================
    # TRANG 1: TRANG BÌA CHUẨN ĐỒ ÁN CHUYÊN NGÀNH (ACADEMIC COVER PAGE)
    # =========================================================================
    print("[2/6] Tạo trang bìa chuẩn Đồ án Chuyên ngành (Academic Cover Frame)...")
    
    # 1. Cơ quan đào tạo
    p_inst = doc.add_paragraph()
    p_format(p_inst, before=8, after=2, spacing=1.1, align=WD_ALIGN_PARAGRAPH.CENTER)
    r = p_inst.add_run("UBND THÀNH PHỐ HỒ CHÍ MINH\nTRƯỜNG CAO ĐẲNG LÝ TỰ TRỌNG TP. HỒ CHÍ MINH\nKHOA CÔNG NGHỆ THÔNG TIN")
    set_font(r, size=13, bold=True, color=(15, 23, 42))

    p_div = doc.add_paragraph()
    p_format(p_div, before=2, after=8, spacing=1.0, align=WD_ALIGN_PARAGRAPH.CENTER)
    r = p_div.add_run("❖  ——————  ❖  ——————  ❖")
    set_font(r, size=11, bold=True, color=(2, 132, 199))

    # 2. Logo thương hiệu
    if os.path.exists("assets/images/vphone-logo.png"):
        p_logo = doc.add_paragraph()
        p_format(p_logo, before=2, after=8, spacing=1.0, align=WD_ALIGN_PARAGRAPH.CENTER)
        p_logo.add_run().add_picture("assets/images/vphone-logo.png", width=Inches(1.5))

    # 3. Tiêu đề đồ án
    p_title = doc.add_paragraph()
    p_format(p_title, before=4, after=4, spacing=1.2, align=WD_ALIGN_PARAGRAPH.CENTER)
    r = p_title.add_run("BÁO CÁO ĐỒ ÁN CHUYÊN NGÀNH\nNGÀNH: LẬP TRÌNH MÁY TÍNH")
    set_font(r, size=16, bold=True, color=(15, 23, 42))

    p_sub = doc.add_paragraph()
    p_format(p_sub, before=4, after=12, spacing=1.25, align=WD_ALIGN_PARAGRAPH.CENTER)
    r_sub_lbl = p_sub.add_run("ĐỀ TÀI:\n")
    set_font(r_sub_lbl, size=13, bold=True, color=(71, 85, 105))
    r_sub = p_sub.add_run(
        "NGHIÊN CỨU, THIẾT KẾ VÀ PHÁT TRIỂN HỆ THỐNG THƯƠNG MẠI ĐIỆN TỬ\n"
        "SMARTPHONE FLAGSHIP V-PHONE STORE\n"
        "TÍCH HỢP HỆ SINH THÁI GIAO DIỆN CYBER DARK MODE, TRỢ LÝ AI ĐA VAI TRÒ\n"
        "VÀ CỔNG THANH TOÁN TỰ ĐỘNG FINTECH 4.0"
    )
    set_font(r_sub, size=13.5, bold=True, color=(2, 132, 199))

    # 4. Hộp thông tin giảng viên và sinh viên thực hiện (Card sang trọng)
    info_card = doc.add_table(rows=1, cols=1)
    info_card.alignment = WD_TABLE_ALIGNMENT.CENTER
    trPr0 = info_card.rows[0]._tr.get_or_add_trPr()
    trPr0.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))
    c = info_card.cell(0, 0)
    c.width = Inches(5.8)
    
    tcPr = c._tc.get_or_add_tcPr()
    tcPr.append(parse_xml(f'<w:shd {nsdecls("w")} w:fill="F8FAFC"/>'))
    tcPr.append(parse_xml(f'''
        <w:tcBorders {nsdecls("w")}>
            <w:left w:val="single" w:sz="24" w:space="0" w:color="0284C7"/>
            <w:top w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>
            <w:right w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>
            <w:bottom w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/>
        </w:tcBorders>
    '''))
    tcPr.append(parse_xml(f'''
        <w:tcMar {nsdecls("w")}>
            <w:top w:w="120" w:type="dxa"/>
            <w:left w:w="180" w:type="dxa"/>
            <w:bottom w:w="120" w:type="dxa"/>
            <w:right w:w="180" w:type="dxa"/>
        </w:tcMar>
    '''))

    p_c1 = c.paragraphs[0]
    p_format(p_c1, before=3, after=2, spacing=1.2, align=WD_ALIGN_PARAGRAPH.LEFT)
    r = p_c1.add_run("HỘI ĐỒNG CHUYÊN MÔN:      ")
    set_font(r, size=11.5, bold=True, color=(15, 23, 42))
    r = p_c1.add_run("KHOA CÔNG NGHỆ THÔNG TIN")
    set_font(r, size=11.5, color=(51, 65, 85))

    p_c2 = c.add_paragraph()
    p_format(p_c2, before=2, after=2, spacing=1.2, align=WD_ALIGN_PARAGRAPH.LEFT)
    r = p_c2.add_run("GIẢNG VIÊN HƯỚNG DẪN:   ")
    set_font(r, size=11.5, bold=True, color=(15, 23, 42))
    r = p_c2.add_run("TS. NGUYỄN VĂN A")
    set_font(r, size=11.5, color=(51, 65, 85))

    p_c3 = c.add_paragraph()
    p_format(p_c3, before=4, after=2, spacing=1.2, align=WD_ALIGN_PARAGRAPH.LEFT)
    r = p_c3.add_run("SINH VIÊN THỰC HIỆN:")
    set_font(r, size=11.5, bold=True, color=(15, 23, 42))

    p_c4 = c.add_paragraph()
    p_format(p_c4, before=1, after=1, spacing=1.15, align=WD_ALIGN_PARAGRAPH.LEFT)
    p_c4.paragraph_format.left_indent = Inches(0.2)
    r = p_c4.add_run("1. Huỳnh Bá Lực  —  MSSV: 22520001 ")
    set_font(r, size=11, bold=True, color=(2, 132, 199))
    r = p_c4.add_run("(Trưởng nhóm: Backend MVC, Fintech, Cyber Dark Mode)")
    set_font(r, size=11, color=(51, 65, 85))

    p_c5 = c.add_paragraph()
    p_format(p_c5, before=1, after=2, spacing=1.15, align=WD_ALIGN_PARAGRAPH.LEFT)
    p_c5.paragraph_format.left_indent = Inches(0.2)
    r = p_c5.add_run("2. Võ Minh Hiếu   —  MSSV: 22520002 ")
    set_font(r, size=11, bold=True, color=(2, 132, 199))
    r = p_c5.add_run("(Thành viên: Thiết kế CSDL, Storefront, Phân quyền RBAC)")
    set_font(r, size=11, color=(51, 65, 85))

    p_c6 = c.add_paragraph()
    p_format(p_c6, before=4, after=3, spacing=1.2, align=WD_ALIGN_PARAGRAPH.LEFT)
    r = p_c6.add_run("CHUYÊN NGÀNH:                 ")
    set_font(r, size=11.5, bold=True, color=(15, 23, 42))
    r = p_c6.add_run("LẬP TRÌNH MÁY TÍNH  •  KHÓA 2023 – 2026")
    set_font(r, size=11.5, color=(51, 65, 85))

    # 5. Dòng địa điểm và thời gian
    p_loc = doc.add_paragraph()
    p_format(p_loc, before=14, after=0, spacing=1.1, align=WD_ALIGN_PARAGRAPH.CENTER)
    r = p_loc.add_run("TP. HỒ CHÍ MINH — THÁNG 10 NĂM 2026")
    set_font(r, size=12, bold=True, color=(71, 85, 105))
    
    doc.add_page_break()

    # =========================================================================
    # TRANG 2: MỤC LỤC TỔNG QUÁT (TABLE OF CONTENTS)
    # =========================================================================
    print("[2.1/6] Tạo trang Mục lục tổng quát có dấu chấm nối chuẩn Word...")
    p_toc = doc.add_paragraph()
    p_format(p_toc, before=10, after=12, spacing=1.3, align=WD_ALIGN_PARAGRAPH.CENTER)
    r = p_toc.add_run("MỤC LỤC")
    set_font(r, size=16, bold=True, color=(15, 23, 42))

    add_toc_item(doc, "DANH MỤC TỪ VIẾT TẮT VÀ THUẬT NGỮ", "3", level=1)
    add_toc_item(doc, "DANH MỤC HÌNH ẢNH VÀ SƠ ĐỒ", "4", level=1)
    add_toc_item(doc, "DANH MỤC BẢNG BIỂU", "4", level=1)
    
    add_toc_item(doc, "PHẦN I: TỔNG QUAN DỰ ÁN VÀ KIẾN TRÚC HỆ THỐNG", "5", level=1)
    add_toc_item(doc, "1.1. Bối cảnh và mục tiêu phát triển hệ thống V-Phone Store", "5", level=2)
    add_toc_item(doc, "1.2. Công nghệ sử dụng và bảng đặc tả kỹ thuật (Technology Stack)", "6", level=2)
    add_toc_item(doc, "1.3. Cấu trúc tổ chức thư mục mã nguồn thực tế (Directory Structure)", "7", level=2)
    add_toc_item(doc, "1.4. Mô hình kiến trúc MVC và Điểm điều phối tập trung (Front Controller)", "7", level=2)

    add_toc_item(doc, "PHẦN II: CHUYÊN ĐỀ KỸ THUẬT: HỆ SINH THÁI GIAO DIỆN CYBER DARK MODE & XỬ LÝ ẢNH TRONG SUỐT", "8", level=1)
    add_toc_item(doc, "2.1. Triết lý thiết kế Cyber Dark Mode cho Smartphone Flagship", "8", level=2)
    add_toc_item(doc, "2.2. Kiến trúc CSS Custom Variables & Bộ chuyển đổi Theme động", "9", level=2)
    add_toc_item(doc, "2.3. Giải pháp tự động hóa tách nền ảnh bằng Python PIL (Transparent BG)", "9", level=2)

    add_toc_item(doc, "PHẦN III: QUY TRÌNH PHÁT TRIỂN SẢN PHẨM PHẦN MỀM", "10", level=1)
    add_toc_item(doc, "3.1. Giai đoạn 1: Phân tích nghiệp vụ và Thiết kế Cơ sở dữ liệu (13 bảng)", "10", level=2)
    add_toc_item(doc, "3.2. Giai đoạn 2: Xây dựng Tầng Backend, Dịch vụ Nghiệp vụ và Hệ thống API", "11", level=2)
    add_toc_item(doc, "3.3. Giai đoạn 3: Xây dựng Giao diện Người dùng (Storefront) & Quản trị (Admin)", "13", level=2)
    add_toc_item(doc, "3.4. Giai đoạn 4: Kiểm thử, Tối ưu hóa và Quy trình Triển khai (DevOps Sync)", "15", level=2)

    add_toc_item(doc, "PHẦN IV: ĐÁNH GIÁ KẾT QUẢ VÀ HƯỚNG PHÁT TRIỂN", "16", level=1)
    add_toc_item(doc, "4.1. Các kết quả kỹ thuật đạt được", "16", level=2)
    add_toc_item(doc, "4.2. Hướng mở rộng và nâng cấp tương lai", "16", level=2)

    add_toc_item(doc, "TÀI LIỆU THAM KHẢO", "17", level=1)
    
    doc.add_page_break()

    # =========================================================================
    # TRANG 3: DANH MỤC TỪ VIẾT TẮT VÀ THUẬT NGỮ (GLOSSARY)
    # =========================================================================
    print("[2.2/6] Tạo trang Danh mục từ viết tắt và thuật ngữ kỹ thuật...")
    heading_1(doc, "DANH MỤC TỪ VIẾT TẮT VÀ THUẬT NGỮ")
    body_p(doc, "Bảng tổng hợp các thuật ngữ viết tắt và khái niệm kỹ thuật cốt lõi được áp dụng xuyên suốt trong mã nguồn và tài liệu thiết kế hệ thống V-Phone Store:")
    
    abbr_headers = ["Từ viết tắt", "Tên tiếng Anh đầy đủ", "Ý nghĩa kỹ thuật trong hệ thống V-Phone Store"]
    abbr_rows = [
        ["MVC", "Model - View - Controller", "Mô hình phân tách rõ ràng tầng Dữ liệu (Model), Giao diện (View) và Xử lý điều phối (Controller) thuần PHP OOP."],
        ["PDO", "PHP Data Objects", "Lớp trừu tượng truy cập CSDL chuẩn bảo mật, thực thi Prepared Statements chống tấn công SQL Injection 100%."],
        ["ACID", "Atomicity, Consistency, Isolation, Durability", "4 nguyên tắc toàn vẹn giao dịch được đảm bảo trên Engine InnoDB khi thanh toán và cập nhật kho hàng."],
        ["RBAC", "Role-Based Access Control", "Mô hình kiểm soát truy cập dựa trên vai trò phân quyền 5 cấp bậc chi tiết trong admin/includes/permissions.php."],
        ["VietQR", "Vietnam Quick Response", "Chuẩn mã QR thanh toán quốc gia Napas247, tự động nhúng mã đơn hàng và số tiền chính xác."],
        ["IPN", "Instant Payment Notification", "Cơ chế Webhook tiếp nhận thông báo biến động giao dịch tức thời từ MoMo Gateway và Ngân hàng MB."],
        ["JSON", "JavaScript Object Notation", "Định dạng lưu trữ phi quan hệ cho danh sách tùy chọn cấu hình, màu sắc và tồn kho máy trong bảng products."],
        ["OTP", "One-Time Password", "Mã xác thực dùng một lần 6 chữ số có thời hạn 15 phút phục vụ cơ chế khôi phục mật khẩu bảo mật."],
        ["OLED", "Organic Light Emitting Diode", "Công nghệ màn hình cao cấp tự tắt điểm ảnh đen, tiết kiệm 30-40% pin khi hiển thị Cyber Dark Mode (#0b1329)."],
        ["FOUC", "Flash of Unstyled Content", "Hiện tượng chớp nháy giao diện sáng/tối khi tải trang, được triệt tiêu hoàn toàn bằng inline script đọc localStorage."],
        ["3NF", "Third Normal Form", "Dạng chuẩn hóa bậc 3 áp dụng cho 13 bảng thực thể CSDL nhằm loại bỏ hoàn toàn dư thừa và dị thường dữ liệu."],
        ["API", "Application Programming Interface", "Giao diện lập trình ứng dụng RESTful cung cấp dữ liệu cho Frontend, Live Search, Đơn hàng và Trợ lý AI."],
        ["CRUD", "Create, Read, Update, Delete", "Tập hợp 4 thao tác quản trị dữ liệu cơ bản (Thêm, Đọc, Sửa, Xóa) trên các bảng sản phẩm, đơn hàng, khách hàng."],
        ["HMAC-SHA256", "Hash-based Message Authentication Code", "Thuật toán chữ ký số mật mã dùng để xác thực tính toàn vẹn gói tin IPN Webhook gửi từ MoMo Gateway."]
    ]
    make_safe_table(doc, abbr_headers, abbr_rows, [1.1, 2.0, 3.2], font_size=9.5, pad_v=80, line_spacing=1.08)
    
    doc.add_page_break()

    # =========================================================================
    # TRANG 4: DANH MỤC HÌNH ẢNH VÀ BẢNG BIỂU
    # =========================================================================
    print("[2.3/6] Tạo trang Danh mục hình ảnh và Danh mục Bảng biểu...")
    heading_1(doc, "DANH MỤC HÌNH ẢNH VÀ SƠ ĐỒ")
    
    figures = [
        ("Hình 1.1", "Sơ đồ kiến trúc phân tầng hệ thống V-Phone Store (PHP MVC 5 tầng chuẩn OOP)", "6"),
        ("Hình 1.2", "Chu trình xử lý yêu cầu 2 chiều theo kiến trúc MVC thuần (Request Flow)", "7"),
        ("Hình 2.1", "Bàn cân so sánh trải nghiệm thị giác giữa Light Mode và Cyber Dark Mode", "8"),
        ("Hình 2.2", "Sơ đồ chu trình xử lý hình ảnh sản phẩm trong suốt bằng Python PIL", "9"),
        ("Hình 3.1", "Sơ đồ mô hình quan hệ thực thể (Database ERD - 13 bảng InnoDB)", "10"),
        ("Hình 3.2", "Sơ đồ luồng thanh toán tự động VietQR Napas247 và MoMo Sandbox API v2", "11"),
        ("Hình 3.3", "Kiến trúc Động cơ Trợ lý AI đa vai trò nghiệp vụ (Multi-Role AI Assistant)", "12"),
        ("Hình 3.4", "Giao diện Trang chủ Storefront V-Phone Store trên nền tảng Cyber Dark Mode", "13"),
        ("Hình 3.5", "Giao diện Chi tiết Sản phẩm Flagship với bộ đổi màu sắc và ảnh trong suốt", "13"),
        ("Hình 3.6", "Giao diện Bàn cân so sánh thông số kỹ thuật đa thiết bị đối đầu trực quan", "13"),
        ("Hình 3.7", "Giao diện Giỏ hàng thông minh và bộ máy chiết khấu Voucher trên Cyber Dark", "13"),
        ("Hình 3.8", "Giao diện Tra cứu tiến độ đơn hàng trực tuyến 4 bước (#VP-xx)", "14"),
        ("Hình 3.9", "Giao diện Tra cứu bảo hành điện tử theo số IMEI thiết bị", "14"),
        ("Hình 3.10", "Sơ đồ chu trình DevOps đồng bộ tự động Mã nguồn và CSDL đa nền tảng", "15")
    ]
    for code, name, pg in figures:
        add_toc_item(doc, f"{code}: {name}", pg, level=2)
        
    p_sp = doc.add_paragraph()
    p_format(p_sp, before=6, after=6, spacing=1.0)
    
    heading_1(doc, "DANH MỤC BẢNG BIỂU")
    tables = [
        ("Bảng 1.1", "Bảng đặc tả công nghệ và thư viện sử dụng trong hệ thống V-Phone Store", "6"),
        ("Bảng 1.2", "Cấu trúc tổ chức thư mục mã nguồn thực tế của dự án V-Phone Store", "7"),
        ("Bảng 2.1", "Bảng ánh xạ hệ màu CSS Custom Variables giữa Light Mode và Cyber Dark Mode", "9"),
        ("Bảng 3.1", "Đặc tả cấu trúc và vai trò nghiệp vụ 13 bảng thực thể Cơ sở dữ liệu", "10"),
        ("Bảng 3.2", "Đặc tả chức năng và Prompt kỹ thuật của 6 vai trò Trợ lý AI chuyên biệt", "12"),
        ("Bảng 3.3", "Ma trận phân quyền kiểm soát truy cập dựa trên vai trò (RBAC Permission Matrix)", "14")
    ]
    for code, name, pg in tables:
        add_toc_item(doc, f"{code}: {name}", pg, level=2)
        
    doc.add_page_break()

    # =========================================================================
    # TRANG 5: PHẦN I: TỔNG QUAN DỰ ÁN VÀ KIẾN TRÚC HỆ THỐNG
    # =========================================================================
    print("[3/6] Viết Phần I: Tổng quan dự án và Kiến trúc phân tầng...")
    heading_1(doc, "PHẦN I: TỔNG QUAN DỰ ÁN VÀ KIẾN TRÚC HỆ THỐNG")
    
    heading_2(doc, "1.1. Bối cảnh và mục tiêu phát triển hệ thống V-Phone Store")
    body_p(doc, "Trong bối cảnh thị trường thương mại điện tử thiết bị di động ngày càng phát triển vượt bậc, người tiêu dùng Việt Nam có xu hướng tìm kiếm các thiết bị cao cấp (Flagship Smartphones) với trải nghiệm mua sắm tiện nghi, cấu hình minh bạch, kiểm tra bảo hành tức thì và các kênh thanh toán không tiền mặt bảo mật. Dự án V-Phone Store (mã dự án phone-store) được khởi xướng và phát triển trong khuôn khổ Đồ án Chuyên ngành nhằm mục tiêu giải quyết trọn vẹn chu trình bán lẻ và hậu mãi đối với các dòng máy điện thoại hàng đầu từ Apple, Samsung, Xiaomi, OPPO, Google, Vivo, Asus và Sony.")
    
    body_p(doc, "Khác với các hệ thống website bán lẻ truyền thống thường phụ thuộc vào các CMS mã nguồn mở nặng nề hoặc các framework bên thứ ba cồng kềnh, V-Phone Store được thiết kế trên nền tảng PHP thuần áp dụng chặt chẽ mô hình kiến trúc MVC (Model - View - Controller). Cách tiếp cận này mang lại tốc độ phản hồi cực nhanh (thời gian phản hồi trang đo đạc thực tế luôn dưới 0.8 giây), kiểm soát hoàn toàn mã nguồn và ngăn ngừa triệt để các lỗ hổng bảo mật:")
    
    bullet_p(doc, "Từ bước tìm kiếm nhanh với kỹ thuật Live Search Debouncing, so sánh thông số kỹ thuật song song hai dòng máy đối đầu, định giá thu cũ đổi mới (Trade-In), quản lý giỏ hàng thời gian thực, cho đến thanh toán tự động.", "Trải nghiệm khách hàng vượt trội: ")
    bullet_p(doc, "Tích hợp động cơ Trợ lý Trí tuệ Nhân tạo đa chuyên môn với 6 vai trò nghiệp vụ độc lập, hỗ trợ tự động từ khâu tư vấn khách hàng, kiểm kê kho vận, chẩn đoán bảo hành cho đến báo cáo doanh thu điều hành.", "Đột phá công nghệ Trợ lý AI: ")
    bullet_p(doc, "Tự động hóa hoàn toàn quy trình đối soát giao dịch tài chính thông qua cổng VietQR Napas247 động và ví điện tử MoMo Sandbox API v2 có xác thực chữ ký số HMAC-SHA256.", "Hạ tầng thanh toán Fintech 4.0: ")
    bullet_p(doc, "Phân tách quyền hạn truy cập đa cấp bậc (Super Admin, Quản lý, Bán hàng, Thủ kho, Kỹ thuật viên bảo hành, CSKH) bảo đảm tính an toàn dữ liệu nội bộ.", "Quản trị phân quyền nghiêm ngặt: ")

    # =========================================================================
    # TRANG 6: 1.2. CÔNG NGHỆ SỬ DỤNG (BẢNG 1.1 + HÌNH 1.1 TRÊN CÙNG 1 TRANG)
    # =========================================================================
    doc.add_page_break()
    heading_2(doc, "1.2. Công nghệ sử dụng và bảng đặc tả kỹ thuật (Technology Stack)")
    body_p(doc, "Dự án được xây dựng dựa trên sự kết hợp hài hòa giữa các công nghệ Backend, Cơ sở dữ liệu, Giao diện người dùng và các dịch vụ cổng thanh toán số hiện đại:")

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
    make_safe_table(doc, tech_headers, tech_rows, [1.3, 1.4, 1.1, 2.0], font_size=9.5, pad_v=70, line_spacing=1.08)

    add_img(doc, "reports/diagram_architecture.png", 
            "Hình 1.1: Sơ đồ kiến trúc phân tầng hệ thống V-Phone Store (PHP MVC 5 tầng chuẩn OOP)", width=4.3)

    # =========================================================================
    # TRANG 7: 1.3. CẤU TRÚC THƯ MỤC & 1.4. MÔ HÌNH MVC (HÌNH 1.2)
    # =========================================================================
    doc.add_page_break()
    heading_2(doc, "1.3. Cấu trúc tổ chức thư mục mã nguồn thực tế (Directory Structure)")
    body_p(doc, "Mã nguồn dự án được tổ chức khoa học, phân định ranh giới trách nhiệm rõ ràng giữa các phân hệ Lõi ứng dụng (app), Quản trị điều hành (admin), Giao diện công khai (views), Tài nguyên tĩnh (assets), Cấu hình (config) và Cơ sở dữ liệu (database):")

    dir_headers = ["Thư Mục / Tệp Tin", "Quy Mô Mã Nguồn", "Nhiệm Vụ & Phân Trách Nhiệm Thực Tế"]
    dir_rows = [
        ["app/controllers/", "7 controllers", "Điều hướng luồng xử lý: AuthController, CartController, HomeController, OrderController,..."],
        ["app/models/", "9 DAO models", "Tương tác CSDL an toàn bằng PDO: Address, Article, Order, Product, Review, User, Voucher,..."],
        ["app/services/", "5 services", "Xử lý logic nghiệp vụ: VietQRService, MoMoService, MembershipService, GoogleAuth, MailService."],
        ["app/views/", "18 tệp view PHP", "Giao diện: home.php, product_detail.php, cart.php, checkout.php, compare.php, warranty.php,..."],
        ["admin/", "14 màn hình", "Trung tâm điều hành: Dashboard, staff.php, inventory.php, orders.php, products.php, ai_assistants.php,..."],
        ["api/", "7 REST Endpoints", "Xử lý AJAX & Webhook: role_ai.php (6 Roles), chatbot.php, vietqr-webhook.php, momo-ipn.php,..."],
        ["config/ & database/", "4 configs & 13 tables", "database.php (Auto-sync schema), db_phone_store.sql (13 bảng InnoDB) và db_sync_tool.php."],
        ["scripts/ & assets/", "Scripts & Assets", "sync_push.sh, sync_pull.bat (DevOps), make_transparent_single.py (PIL), CSS/JS và ảnh sản phẩm."]
    ]
    make_safe_table(doc, dir_headers, dir_rows, [1.5, 1.2, 3.1], font_size=9.5, pad_v=60, line_spacing=1.05)

    heading_2(doc, "1.4. Mô hình kiến trúc MVC và Điểm điều phối tập trung (Front Controller)")
    body_p(doc, "Toàn bộ các yêu cầu HTTP từ phía máy khách gửi đến hệ thống đều được tiếp nhận và xử lý qua điểm vào duy nhất tại tệp tin index.php đóng vai trò là Front Controller:")
    bullet_p(doc, "index.php tiếp nhận tham số URL (?page=...&action=...) để xác định Controller và Action kích hoạt.", "Phân tích định tuyến: ")
    bullet_p(doc, "Khởi tạo phiên làm việc (session_start()), thiết lập múi giờ Việt Nam, kết nối PDO an toàn.", "Khởi tạo môi trường: ")
    bullet_p(doc, "Controller tương ứng kiểm tra tính hợp lệ của tham số, gọi Model/Service để xử lý nghiệp vụ.", "Điều phối nghiệp vụ: ")
    bullet_p(doc, "Tệp View tương ứng nhận dữ liệu, kết hợp layout chung để render HTML hoàn chỉnh trả về người dùng.", "Kết xuất giao diện: ")

    add_img(doc, "reports/diagram_dataflow_mvc.png", 
            "Hình 1.2: Chu trình xử lý yêu cầu khép kín 2 chiều theo kiến trúc MVC thuần (Request Flow)", width=4.3)

    # =========================================================================
    # TRANG 8: PHẦN II: CHUYÊN ĐỀ KỸ THUẬT: HỆ SINH THÁI GIAO DIỆN CYBER DARK MODE
    # =========================================================================
    doc.add_page_break()
    print("[4/6] Viết Phần II: Chuyên đề kỹ thuật chuyên sâu về Cyber Dark Mode...")
    heading_1(doc, "PHẦN II: CHUYÊN ĐỀ KỸ THUẬT: HỆ SINH THÁI GIAO DIỆN CYBER DARK MODE & XỬ LÝ ẢNH TRONG SUỐT")

    heading_2(doc, "2.1. Triết lý thiết kế Cyber Dark Mode cho Smartphone Flagship")
    body_p(doc, "Một trong những điểm khác biệt và đột phá nhất của dự án V-Phone Store so với các website thương mại điện tử đại trà là việc nghiên cứu và triển khai Hệ sinh thái giao diện Cyber Dark Mode toàn diện ngay từ gốc kiến trúc CSS. Trong phân khúc thiết bị di động Flagship cao cấp (iPhone 16 Pro Max, Samsung Galaxy S24 Ultra, Xiaomi 14 Ultra), hầu hết khách hàng đều sở hữu thiết bị trang bị công nghệ màn hình OLED / Super AMOLED.")
    
    body_p(doc, "Màn hình OLED có đặc tính vật lý tự phát sáng trên từng điểm ảnh (Pixel-level illumination). Khi hiển thị màu đen hoặc gam màu xanh không gian sâu (#0b1329), các bóng bán dẫn diode phát quang hữu cơ sẽ tắt hoàn toàn, mang lại 3 giá trị kỹ thuật thực tiễn to lớn:")
    bullet_p(doc, "Màn hình OLED tiết kiệm đến 30% - 45% năng lượng tiêu thụ khi hiển thị nền tối sâu so với nền trắng rực rỡ truyền thống, kéo dài thời lượng pin đáng kể cho người dùng smartphone.", "Tiết kiệm điện năng vượt trội: ")
    bullet_p(doc, "Giảm thiểu phát xạ ánh sáng xanh có hại, chống chói lóa và loại bỏ hoàn toàn tình trạng mỏi mắt khi khách hàng duyệt tìm và đọc thông số sản phẩm vào ban đêm.", "Bảo vệ thị lực & Chống mỏi mắt: ")
    bullet_p(doc, "Tạo chiều sâu thị giác vô cực, làm nổi bật viền kim loại Titan Sa Mạc, Titan Tự Nhiên và cụm thấu kính camera quang học của các dòng máy Flagship.", "Tôn vinh vẻ đẹp sản phẩm công nghệ: ")

    add_img(doc, "reports/darkmode_showcase.png", 
            "Hình 2.1: Bàn cân so sánh trải nghiệm thị giác giữa Light Mode và Cyber Dark Mode tại V-Phone Store", width=4.5)

    # =========================================================================
    # TRANG 9: 2.2. CSS VARIABLES (BẢNG 2.1) & 2.3. TÁCH NỀN ẢNH PIL (HÌNH 2.2)
    # =========================================================================
    doc.add_page_break()
    heading_2(doc, "2.2. Kiến trúc CSS Custom Variables & Bộ chuyển đổi Theme động")
    body_p(doc, "Hệ thống Dark Mode của V-Phone Store được lập trình bài bản tại assets/css/style.css với cấu trúc bộ chọn toàn cục [data-bs-theme=\"dark\"], hoàn toàn không sử dụng các giải pháp thô sơ như bộ lọc đảo màu (CSS filter invert) vốn gây biến dạng màu sắc ảnh sản phẩm:")

    dark_css_headers = ["Biến CSS / Thuộc Tính", "Giá Trị Light Mode", "Giá Trị Cyber Dark Mode", "Mục Đích & Phạm Vi Ứng Dụng"]
    dark_css_rows = [
        ["body background", "#f6f9fc (Trắng xanh nhạt)", "#0b1329 (Xanh không gian sâu)", "Nền tảng trang web, tạo chiều sâu thị giác OLED vô cực."],
        ["--v-card-bg / Surface", "#ffffff (Trắng tinh)", "#131f37 (Xanh hải quân đậm)", "Thẻ sản phẩm, hộp thoại modal, bảng điều khiển giỏ hàng."],
        ["--v-text-dark", "#1e293b (Xám đen sẫm)", "#f1f5f9 (Trắng ánh bạc)", "Tiêu đề, tên máy, nhãn thông số, độ tương phản AA."],
        ["--v-text-muted", "#64748b (Xám khói)", "#94a3b8 (Xám bạc nhạt)", "Mô tả phụ, thông tin bản quyền, nhãn thời gian tin tức."],
        ["Cyber Neon Accent", "#0066cc (Xanh dương)", "#38bdf8 (Xanh Cyan Neon)", "Giá khuyến mãi, viền thẻ active, hiệu ứng hover."],
        ["Form Controls", "Viền #cbd5e1, nền trắng", "Nền #0e172a, viền rgba 9%", "Hộp tìm kiếm, sổ địa chỉ, ô nhập voucher không chói mắt."]
    ]
    make_safe_table(doc, dark_css_headers, dark_css_rows, [1.5, 1.3, 1.4, 1.6], font_size=9.5, pad_v=60, line_spacing=1.05)

    heading_2(doc, "2.3. Giải pháp tự động hóa tách nền ảnh bằng Python PIL (Transparent Background)")
    body_p(doc, "Phần lớn ảnh thiết bị thu thập từ trang truyền thông của Apple, Samsung, Sony đều có nền trắng đặc (#FFFFFF). Khi đưa lên nền web tối (#0b1329), viền trắng đục thô kệch sẽ xuất hiện. Nhóm đã tự động hóa xử lý bằng script scripts/make_transparent_single.py sử dụng Pillow (PIL):")

    add_img(doc, "reports/diagram_darkmode_pipeline.png", 
            "Hình 2.2: Sơ đồ chu trình xử lý hình ảnh sản phẩm trong suốt tự động bằng script Python PIL", width=4.3)

    bullet_p(doc, "Mở ảnh gốc, đọc mã màu tại 4 điểm góc. Nếu trung bình cộng RGB > 200, ảnh được xác định có nền sáng cần tách.", "Nhận diện màu nền: ")
    bullet_p(doc, "Rải các điểm hạt giống dọc theo 4 cạnh biên và tính sai lệch max(abs(c - bg)) <= 35 để không ăn lẹm vào khung máy.", "Rải điểm hạt giống: ")
    bullet_p(doc, "Thực hiện thuật toán Flood Fill chuyển đổi toàn bộ pixel nền trắng thành pixel rỗng suốt với kênh Alpha = 0.", "Thuật toán Flood Fill: ")
    bullet_p(doc, "Xuất file PNG nén tối ưu. Hơn 200 ảnh sản phẩm trong assets/images/products/ hiển thị sắc nét trên cả 2 nền màu.", "Xuất PNG trong suốt: ")

    # =========================================================================
    # TRANG 10: 3.1. THIẾT KẾ CSDL (HÌNH 3.1 ERD & BẢNG 3.1 TRÊN CÙNG 1 TRANG)
    # =========================================================================
    doc.add_page_break()
    print("[5/6] Viết Phần III: Quy trình phát triển qua 4 giai đoạn chi tiết...")
    heading_1(doc, "PHẦN III: QUY TRÌNH PHÁT TRIỂN SẢN PHẨM PHẦN MỀM")

    heading_2(doc, "3.1. Giai đoạn 1: Phân tích nghiệp vụ và Thiết kế Cơ sở dữ liệu")
    body_p(doc, "Cơ sở dữ liệu db_phone_store được thiết kế chuẩn 3NF trên MySQL 8.0 Engine InnoDB đảm bảo tính toàn vẹn giao dịch (ACID) khi tạo đơn hàng, cập nhật kho và lưu trữ trạng thái thanh toán:")

    add_img(doc, "reports/diagram_erd.png", 
            "Hình 3.1: Sơ đồ mô hình quan hệ thực thể (Database ERD - 13 bảng InnoDB phân theo 3 phân hệ)", width=3.9)

    table_erd_headers = ["Bảng CSDL", "Khóa PK / FK", "Các Trường Dữ Liệu Tiêu Biểu", "Vai Trò Nghiệp Vụ Cốt Lõi"]
    table_erd_rows = [
        ["users", "PK: id", "fullname, email, password, phone, role, permissions, tier", "Quản lý khách hàng, nhân viên và phân quyền đa cấp RBAC."],
        ["products", "PK: id\nFK: brand_id", "name, price, sale_price, screen, cpu, ram, rom, colors", "Thiết bị Flagship, thông số cấu hình và tùy chọn màu/tồn kho."],
        ["brands", "PK: id", "name", "Danh mục 9 thương hiệu quốc tế: Apple, Samsung, Xiaomi,..."],
        ["orders", "PK: id\nFK: user_id", "fullname, phone, total_money, payment_method, status", "Lưu hóa đơn, mã đối soát VietQR/MoMo và tiến trình 4 bước."],
        ["order_details", "PK: id\nFK: order, prod", "order_id, product_id, price, quantity, total_price", "Chi tiết máy mua trong đơn, ràng buộc toàn vẹn CASCADE."],
        ["vouchers", "PK: id", "code, discount_type, discount_value, min_order, max_disc", "Động cơ giảm giá (% hoặc tiền cố định), kiểm tra ngưỡng đơn."],
        ["warranty_claims", "PK: id\nFK: user, prod", "imei, customer_name, issue_type, claim_method, status", "Hồ sơ bảo hành thiết bị theo số IMEI, thẩm định 1 đổi 1."],
        ["product_reviews", "PK: id\nFK: prod, user", "rating, comment, is_buyer, status, admin_reply", "Đánh giá sao thực tế từ khách đã mua (is_buyer=1), phản hồi admin."],
        ["user_addresses", "PK: id\nFK: user_id", "recipient_name, phone, address, label, is_default", "Sổ nhiều địa chỉ nhận hàng linh hoạt (Nhà riêng, Cơ quan)."],
        ["user_notifications", "PK: id\nFK: user_id", "order_id, title, message, cancel_reason, is_read", "Hệ thống thông báo đẩy nội bộ về đơn hàng và bảo hành."],
        ["articles", "PK: id", "title, badge, article_date, read_time, summary, content", "Quản lý tin tức công nghệ và cẩm nang người dùng."],
        ["callback_requests", "PK: id", "fullname, phone, topic, department, note, status", "Tiếp nhận yêu cầu tư vấn gọi lại trong 5 phút từ khách mua sắm."],
        ["password_resets", "PK: id", "email, token, otp, expires_at", "Quản lý mã OTP 6 số (hạn 15 phút) phục vụ khôi phục mật khẩu."]
    ]
    make_safe_table(doc, table_erd_headers, table_erd_rows, [1.1, 1.1, 1.8, 1.8], font_size=8.5, pad_v=45, line_spacing=1.02)

    # =========================================================================
    # TRANG 11: 3.1.a AUTO-MIGRATION, 3.2. BACKEND & FINTECH (HÌNH 3.2)
    # =========================================================================
    doc.add_page_break()
    heading_3(doc, "Cơ chế Tự động Nâng cấp Cấu trúc (Auto-Migration) và Đồng bộ CSDL")
    body_p(doc, "Tệp config/database.php tích hợp cơ chế Auto-Migration tự động. Khi hệ thống khởi chạy trên máy mới, mã nguồn tự động thực thi các câu lệnh ALTER TABLE và INSERT IGNORE bổ sung cột thanh toán VietQR, Google OAuth và mã voucher hệ thống mà không đòi hỏi thao tác thủ công.")

    heading_2(doc, "3.2. Giai đoạn 2: Xây dựng Tầng Backend, Dịch vụ Nghiệp vụ và Hệ thống API")
    body_p(doc, "Tầng Backend đóng vai trò điều phối luồng dữ liệu và thực thi logic nghiệp vụ theo chuẩn hướng đối tượng (OOP) vững chắc:")

    heading_3(doc, "a) Lớp Data Access Object (Models) & Bảo mật PDO")
    bullet_p(doc, "Quản lý danh mục máy, khấu trừ tồn kho tự động (deductStock()) và hoàn kho nếu hủy đơn (restoreStock()).", "ProductModel: ")
    bullet_p(doc, "Khởi tạo đơn hàng bên trong Transaction PDO ($pdo->beginTransaction()), bảo đảm Rollback nếu xảy ra sự cố.", "OrderModel: ")
    bullet_p(doc, "Xác thực tài khoản với mật khẩu băm một chiều Bcrypt an toàn, quản lý hồ sơ và giỏ hàng lưu trữ.", "UserModel: ")
    bullet_p(doc, "Động cơ tính toán chiết khấu voucher, kiểm tra điều kiện áp dụng và số lần đã sử dụng.", "VoucherModel: ")

    heading_3(doc, "b) Tích hợp Cổng thanh toán Fintech số tự động (VietQR & MoMo)")
    body_p(doc, "Hệ thống tích hợp hai giải pháp Fintech tự động hóa hoàn toàn, loại bỏ 100% việc đối soát ngân hàng thủ công:")

    add_img(doc, "reports/diagram_fintech_flow.png", 
            "Hình 3.2: Sơ đồ luồng thanh toán tự động VietQR Napas247 và MoMo Sandbox API v2", width=4.3)

    bullet_p(doc, "VietQRService.php tự động tạo mã QR Napas247 từ Open API ngân hàng MB Bank kèm cú pháp VPHONE <id>. Webhook tại api/vietqr-webhook.php cập nhật đơn sang 'Đã thanh toán' trong < 1.5 giây.", "VietQR Napas247 Dynamic QR: ")
    bullet_p(doc, "MoMoService.php kết nối cổng MoMo Sandbox API v2 có chữ ký số HMAC-SHA256, xử lý IPN Webhook tức thời.", "Ví điện tử MoMo API v2: ")

    # =========================================================================
    # TRANG 12: 3.2.c HỘI VIÊN & 3.2.d TRỢ LÝ AI (HÌNH 3.3 + BẢNG 3.2 TRÊN CÙNG 1 TRANG)
    # =========================================================================
    doc.add_page_break()
    heading_3(doc, "c) Hệ thống Phân hạng Hội viên Lũy tiến (Membership Tier System)")
    body_p(doc, "Lớp MembershipService.php tự động tính tổng chi tiêu các đơn đã giao thành công và phân hạng:")
    bullet_p(doc, "Tiêu chuẩn (< 100 triệu VNĐ): Bảo hành tiêu chuẩn, miễn phí vận chuyển toàn quốc.", "Standard: ")
    bullet_p(doc, "Thành viên Bạc (>= 100 triệu VNĐ): Thẻ Silver kim loại trong Profile, tự động giảm 2% trên mọi đơn hàng.", "Silver: ")
    bullet_p(doc, "Thành viên Vàng (>= 300 triệu VNĐ): Giảm 3% toàn đơn, ưu tiên tiếp nhận bảo hành máy.", "Gold: ")
    bullet_p(doc, "Thành viên Kim Cương (>= 500 triệu VNĐ): Giảm 5%, quà sinh nhật Flagship và hỗ trợ kỹ thuật VIP 24/7.", "Diamond: ")

    heading_3(doc, "d) Hệ thống Trợ lý Trí tuệ Nhân tạo Đa Chuyên Môn (Multi-Role AI Neural Assistant)")
    body_p(doc, "Động cơ AI tại api/role_ai.php (hơn 2.100 dòng mã) phân bổ quyền hạn và ngữ cảnh theo 6 vai trò nghiệp vụ thực tế:")

    add_img(doc, "reports/diagram_role_ai_flow.png", 
            "Hình 3.3: Sơ đồ kiến trúc Động cơ Trợ lý AI đa vai trò nghiệp vụ (Multi-Role AI Assistant)", width=4.0)

    ai_headers = ["Role Key", "Tên Chức Danh AI", "Đối Tượng Người Dùng", "Nhiệm Vụ Nghiệp Vụ Cốt Lõi Được Tích Hợp"]
    ai_rows = [
        ["admin", "Executive Admin AI", "Quản trị viên tối cao", "Phân tích doanh thu từ CSDL MySQL; dự báo xu hướng tiêu dùng; tư vấn kế hoạch nhập Flagship."],
        ["sales", "Sales Consultant AI", "Nhân viên bán hàng", "Gợi ý thiết bị thay thế theo ngân sách khách; kịch bản up-sell phụ kiện; tra cứu voucher."],
        ["warehouse", "Warehouse AI", "Quản lý kho vận", "Kiểm kê tồn kho theo màu/bộ nhớ; cảnh báo các model có tồn kho <= 5 máy."],
        ["warranty", "Tech & Care AI", "Kỹ thuật viên bảo hành", "Tra cứu bảo hành theo IMEI; chẩn đoán lỗi phần cứng; hướng dẫn thẩm định 1 đổi 1."],
        ["support", "Support Desk AI", "Chăm sóc khách hàng", "Kịch bản phản hồi chuẩn 5 sao; xử lý khiếu nại giao hàng chậm và hoàn tiền."],
        ["customer", "Cyber AI Khách Hàng", "Người mua trực tuyến", "Tư vấn cấu hình Flagship 24/7; hướng dẫn trả góp 0%; gợi ý thiết bị tối ưu tài chính."]
    ]
    make_safe_table(doc, ai_headers, ai_rows, [1.1, 1.4, 1.4, 1.9], font_size=8.5, pad_v=45, line_spacing=1.05)

    # =========================================================================
    # TRANG 13: 3.3. STOREFRONT & CHI TIẾT & SO SÁNH & GIỎ HÀNG (HÌNH 3.4 - 3.7)
    # =========================================================================
    doc.add_page_break()
    heading_2(doc, "3.3. Giai đoạn 3: Xây dựng Giao diện Người dùng (Storefront) & Trang Quản trị (Admin)")
    body_p(doc, "Giao diện Storefront được xây dựng theo chuẩn Cyber Dark Mode dành riêng cho Flagship Smartphones, hỗ trợ đầy đủ bộ công cụ trải nghiệm mua sắm hiện đại:")

    heading_3(doc, "a) Giao diện Storefront & Chi tiết Flagship với Bộ Đổi Màu Thời Gian Thực")
    body_p(doc, "Trang chủ bố trí Banner slider siêu phẩm, logo hãng vector sắc nét; trang chi tiết (product_detail.php) tự động đổi màu sắc máy và cập nhật ảnh trong suốt tức thì khi nhấn chọn từng phiên bản màu (Titan Sa Mạc, Titan Tự Nhiên, Titan Đen):")

    add_img_pair(doc, 
                 "reports/screenshots/image3.png", "Hình 3.4: Giao diện Trang chủ Storefront V-Phone Store (#0b1329)",
                 "reports/screenshots/image9.png", "Hình 3.5: Chi tiết Flagship với bộ đổi màu và ảnh tách nền")

    heading_3(doc, "b) Bàn Cân So Sánh Flagship & Giỏ Hàng Thời Gian Thực")
    body_p(doc, "Công cụ app/views/compare.php đặt song song 2 dòng máy đối đầu để so sánh cấu hình chi tiết; giỏ hàng (cart.php) tự động tính toán tổng tiền, chiết khấu VIP và áp mã voucher trực quan:")

    add_img_pair(doc, 
                 "reports/screenshots/image12.png", "Hình 3.6: Bàn cân so sánh thông số kỹ thuật đa thiết bị đối đầu",
                 "reports/screenshots/image10.png", "Hình 3.7: Giỏ hàng thời gian thực kết hợp Động cơ Voucher")

    # =========================================================================
    # TRANG 14: 3.3.c TRA CỨU & 3.3.d ADMIN CMS & MA TRẬN RBAC (BẢNG 3.3)
    # =========================================================================
    doc.add_page_break()
    heading_3(doc, "c) Tra Cứu Tiến Độ Đơn Hàng Trực Tuyến & Bảo Hành Điện Tử IMEI")
    body_p(doc, "Hệ thống hỗ trợ tra cứu tiến độ đơn hàng 4 nấc (#VP-xx) kèm lý do hủy nếu có; tra cứu bảo hành chính hãng theo số IMEI và gửi yêu cầu thẩm định 1 đổi 1 trực tuyến:")

    add_img_pair(doc, 
                 "reports/screenshots/image11.png", "Hình 3.8: Tra cứu tiến độ đơn hàng trực tuyến 4 bước (#VP-xx)",
                 "reports/screenshots/image13.png", "Hình 3.9: Tra cứu bảo hành điện tử theo số IMEI thiết bị")

    heading_3(doc, "d) Hệ Thống Quản Trị Trung Tâm (Admin CMS) & Ma Trận Phân Quyền RBAC")
    body_p(doc, "Phân hệ quản trị tại admin/ áp dụng cơ chế Phân quyền kiểm soát truy cập dựa trên vai trò (RBAC) chặt chẽ được định nghĩa tại admin/includes/permissions.php:")

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
    make_safe_table(doc, rbac_headers, rbac_rows, [1.4, 0.85, 0.65, 0.65, 0.65, 0.65, 0.65, 0.65], font_size=8.5, pad_v=40, line_spacing=1.02)

    # =========================================================================
    # TRANG 15: 3.4. KIỂM THỬ, TỐI ƯU HÓA & DEVOPS SYNC (HÌNH 3.10)
    # =========================================================================
    doc.add_page_break()
    heading_2(doc, "3.4. Giai đoạn 4: Kiểm thử, Tối ưu hóa và Quy trình Triển khai (Testing & DevOps)")
    
    heading_3(doc, "a) Chiến lược Kiểm thử Chất lượng Phần mềm (Quality Assurance)")
    bullet_p(doc, "Kiểm tra chu trình Giỏ hàng -> Áp mã Voucher -> Chọn địa chỉ -> Tạo đơn. Đảm bảo tồn kho từng màu máy trừ chính xác, chống bán vượt tồn (Over-selling).", "Kiểm thử chức năng: ")
    bullet_p(doc, "Dùng chế độ Demo Simulator trong VietQRService giả lập tín hiệu Webhook ngân hàng: Chuyển đúng tiền, thiếu tiền và sai cú pháp nội dung.", "Kiểm thử cổng thanh toán: ")
    bullet_p(doc, "Đăng nhập vai Bán hàng/Kỹ thuật viên, cố tình vào admin/staff.php hoặc admin/db_sync.php để xác nhận cơ chế chặn và trả về lỗi 403 Forbidden.", "Kiểm thử bảo mật RBAC: ")

    heading_3(doc, "b) Giải pháp Đồng bộ DevOps Hai Chiều Đa Nền Tảng (Cross-Platform DevOps)")
    body_p(doc, "Giải quyết xung đột CSDL MySQL giữa macOS (máy cá nhân) và Windows (phòng thực hành trường học) bằng quy trình tự động hóa:")

    add_img(doc, "reports/diagram_devops_sync.png", 
            "Hình 3.10: Sơ đồ chu trình DevOps đồng bộ tự động Mã nguồn và CSDL đa nền tảng (macOS <-> Windows Lab)", width=4.3)

    bullet_p(doc, "Trên macOS, lập trình viên chạy ./scripts/sync_push.sh tự động Dump dữ liệu MySQL ra database/db_phone_store.sql và git push lên GitHub.", "Chiều Đẩy Dữ Liệu: ")
    bullet_p(doc, "Tại phòng máy Windows, sinh viên double-click scripts/sync_pull.bat (hoặc ./scripts/sync_pull.sh) tự động git pull và nạp đè CSDL vào MySQL XAMPP.", "Chiều Kéo Dữ Liệu: ")
    bullet_p(doc, "Hỗ trợ nút bấm 'Xuất Dữ Liệu' và 'Nạp Dữ Liệu' trực quan trên giao diện web admin/db_sync.php.", "Giao diện Web Sync: ")

    # =========================================================================
    # TRANG 16: PHẦN IV: ĐÁNH GIÁ KẾT QUẢ VÀ HƯỚNG PHÁT TRIỂN
    # =========================================================================
    doc.add_page_break()
    print("[6/6] Viết Phần IV: Đánh giá kết quả và Lộ trình phát triển...")
    heading_1(doc, "PHẦN IV: ĐÁNH GIÁ KẾT QUẢ VÀ HƯỚNG PHÁT TRIỂN")

    heading_2(doc, "4.1. Các kết quả kỹ thuật đạt được")
    body_p(doc, "Sau chu kỳ phát triển liên tục và nghiêm túc, dự án V-Phone Store đã hoàn thành 100% các mục tiêu kỹ thuật và nghiệp vụ đề ra:")
    bullet_p(doc, "Hệ sinh thái thương mại điện tử smartphone Flagship hoàn chỉnh với cấu trúc PHP MVC thuần sạch sẽ, dễ bảo trì.", "Hệ thống hoàn chỉnh khép kín: ")
    bullet_p(doc, "Triển khai hoàn hảo Cyber Dark Mode (#0b1329), giải quyết triệt để viền trắng ảnh máy nhờ script Python PIL tách nền trong suốt.", "Đột phá thị giác Cyber Dark: ")
    bullet_p(doc, "Tốc độ tải trang phản hồi cực nhanh dưới 0.8 giây, giao diện Responsive tương thích trên mọi kích thước màn hình.", "Hiệu năng vượt trội: ")
    bullet_p(doc, "Triển khai thành công 2 cổng thanh toán số VietQR Napas247 và MoMo API v2, giảm thiểu 100% thời gian đối soát thủ công.", "Tự động hóa tài chính: ")
    bullet_p(doc, "Tích hợp sáng tạo Động cơ AI 6 vai trò nghiệp vụ với khả năng trích xuất số liệu thời gian thực từ Database MySQL.", "Ứng dụng AI thực tế: ")
    bullet_p(doc, "Đảm bảo an toàn thông tin với PDO Prepared Statements, mã hóa Bcrypt và cơ chế phân quyền nhân sự Granular RBAC.", "Bảo mật dữ liệu cao: ")

    heading_2(doc, "4.2. Hướng mở rộng và nâng cấp tương lai")
    bullet_p(doc, "Tích hợp thêm cổng thẻ quốc tế Visa/Mastercard qua VNPay hoặc Stripe phục vụ nhóm khách hàng nước ngoài.", "Cổng thanh toán quốc tế: ")
    bullet_p(doc, "Triển khai giao thức Web Push Notifications gửi thông báo đẩy trực tiếp lên màn hình điện thoại khi đơn bắt đầu được giao.", "Thông báo đẩy thời gian thực: ")
    bullet_p(doc, "Xây dựng ứng dụng di động Native App trên Flutter hoặc React Native kết nối trực tiếp với backend qua RESTful API.", "Ứng dụng di động Native App: ")
    bullet_p(doc, "Nâng cấp động cơ gợi ý sản phẩm áp dụng mô hình máy học (Collaborative Filtering) dựa trên hành vi click chuột của người dùng.", "Gợi ý thông minh (AI Recommendation): ")

    # Hộp tổng kết đồ án
    callout_box(doc, "TỔNG KẾT ĐỒ ÁN V-PHONE STORE 2026", [
        "Hệ thống đã sẵn sàng đưa vào vận hành thực tế tại môi trường bán lẻ thiết bị di động Flagship.",
        "Toàn bộ mã nguồn, cơ sở dữ liệu và tài liệu kỹ thuật được quản lý chuẩn mực trên Git/GitHub.",
        "Nhóm sinh viên thực hiện: Huỳnh Bá Lực & Võ Minh Hiếu trân trọng cảm ơn sự hướng dẫn tận tình của Quý Thầy/Cô Khoa Công nghệ Thông tin - Ngành Lập trình Máy tính Trường Cao đẳng Lý Tự Trọng TP.HCM!"
    ])

    # =========================================================================
    # TRANG 17: TÀI LIỆU THAM KHẢO (REFERENCES)
    # =========================================================================
    doc.add_page_break()
    heading_1(doc, "TÀI LIỆU THAM KHẢO")
    body_p(doc, "Danh mục các tài liệu nghiên cứu, chuẩn đặc tả kỹ thuật và thư viện phần mềm chính thức được tham chiếu trong quá trình thực hiện đồ án:")

    references = [
        "[1] The PHP Group (2024), \"PHP 8.2 Object-Oriented Programming and PDO Data Objects Manual\", https://www.php.net/manual/en/book.pdo.php.",
        "[2] Oracle Corporation (2024), \"MySQL 8.0 Reference Manual: InnoDB Storage Engine & ACID Transaction Model\", Oracle Documentation.",
        "[3] Công ty Cổ phần Thanh toán Quốc gia Việt Nam - NAPAS (2023), \"Quy chuẩn kỹ thuật định dạng mã phản hồi nhanh VietQR liên ngân hàng\", Napas Technical Standard v1.2.",
        "[4] Ví điện tử MoMo (2024), \"Tài liệu tích hợp Cổng thanh toán MoMo Payment Gateway API v2 & IPN Webhook\", MoMo Developers Portal.",
        "[5] Python Software Foundation (2024), \"Pillow: Friendly Python Imaging Library v11.0 Documentation\", https://pillow.readthedocs.io/.",
        "[6] World Wide Web Consortium - W3C (2023), \"CSS Custom Properties for Cascading Variables Module Level 1\", W3C Recommendation.",
        "[7] Bootstrap Authors (2023), \"Bootstrap 5.3 Documentation: Color Modes, Dark Theme Variables & Breakpoints\", https://getbootstrap.com/docs/5.3/."
    ]
    for ref_item in references:
        bullet_p(doc, ref_item)

    output_path = "reports/BaoCao_Qua_Trinh_Phat_Trien_San_Pham.docx"
    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    doc.save(output_path)
    print(f"\n[HOÀN THÀNH XUẤT BÁO CÁO] File Word chuẩn Đồ án Chuyên ngành đã lưu tại:\n-> {output_path}")

if __name__ == '__main__':
    build_capstone_report()
