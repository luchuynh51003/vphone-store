from PIL import Image, ImageDraw, ImageFont
import os

font_title = ImageFont.truetype('/System/Library/Fonts/Supplemental/Arial Bold.ttf', 24)
font_card_title = ImageFont.truetype('/System/Library/Fonts/Supplemental/Arial Bold.ttf', 18)
font_bold = ImageFont.truetype('/System/Library/Fonts/Supplemental/Arial Bold.ttf', 15)
font_regular = ImageFont.truetype('/System/Library/Fonts/Supplemental/Arial.ttf', 14)
font_small = ImageFont.truetype('/System/Library/Fonts/Supplemental/Arial.ttf', 12)

def make_darkmode_showcase():
    W, H = 1200, 640
    img = Image.new('RGB', (W, H), (15, 23, 42)) # Slate dark #0f172a
    draw = ImageDraw.Draw(img)
    
    # Title Banner
    draw.text((W // 2, 35), "V-PHONE STORE: HỆ THỐNG GIAO DIỆN KÉP LIGHT MODE & CYBER DARK MODE", 
              fill=(255, 255, 255), font=font_title, anchor="mm")
    
    # Left Card: Light Mode (X: 60 to 540)
    draw.rounded_rectangle([60, 75, 540, 580], radius=16, fill=(248, 250, 252), outline=(203, 213, 225), width=2)
    # Header bar
    draw.rounded_rectangle([60, 75, 540, 135], radius=16, fill=(226, 232, 240))
    draw.rectangle([60, 115, 540, 135], fill=(226, 232, 240))
    draw.text((300, 105), "LIGHT MODE (GIAO DIỆN TIÊU CHUẨN)", fill=(15, 23, 42), font=font_card_title, anchor="mm")
    
    # Right Card: Cyber Dark Mode (X: 660 to 1140)
    draw.rounded_rectangle([660, 75, 1140, 580], radius=16, fill=(11, 19, 41), outline=(56, 189, 248), width=2)
    # Header bar
    draw.rounded_rectangle([660, 75, 1140, 135], radius=16, fill=(19, 31, 55))
    draw.rectangle([660, 115, 1140, 135], fill=(19, 31, 55))
    draw.text((900, 105), "CYBER DARK MODE (CHẾ ĐỘ TỐI FLAGSHIP)", fill=(56, 189, 248), font=font_card_title, anchor="mm")
    
    # Phone Image
    phone_path = 'assets/images/products/iphone-16-promax-titan-sa-mac.png'
    if os.path.exists(phone_path):
        phone = Image.open(phone_path).convert('RGBA')
        phone_resized = phone.resize((190, 230), Image.Resampling.LANCZOS)
        
        # Left product container
        draw.rounded_rectangle([110, 155, 490, 550], radius=12, fill=(255, 255, 255), outline=(226, 232, 240), width=1)
        img.paste(phone_resized, (205, 175), phone_resized)
        draw.text((300, 430), "iPhone 16 Pro Max 256GB", fill=(15, 23, 42), font=font_bold, anchor="mm")
        draw.text((300, 460), "Titan Sa Mạc  •  Chip Apple A18 Pro", fill=(100, 116, 139), font=font_regular, anchor="mm")
        draw.text((300, 505), "34.990.000 đ", fill=(2, 132, 199), font=font_card_title, anchor="mm")
        
        # Right product container
        draw.rounded_rectangle([710, 155, 1090, 550], radius=12, fill=(19, 31, 55), outline=(56, 189, 248), width=1)
        img.paste(phone_resized, (805, 175), phone_resized)
        draw.text((900, 430), "iPhone 16 Pro Max 256GB", fill=(241, 245, 249), font=font_bold, anchor="mm")
        draw.text((900, 460), "Titan Sa Mạc  •  Chip Apple A18 Pro", fill=(148, 163, 184), font=font_regular, anchor="mm")
        draw.text((900, 505), "34.990.000 đ", fill=(56, 189, 248), font=font_card_title, anchor="mm")
        
    # Center Toggle Pill (Clean graphical arrows, NO broken unicode box!)
    draw.rounded_rectangle([560, 310, 640, 360], radius=25, fill=(30, 41, 59), outline=(56, 189, 248), width=2)
    # Draw two clean arrows: right arrow and left arrow
    draw.polygon([(585, 330), (595, 325), (595, 335)], fill=(255, 255, 255))
    draw.line([(585, 330), (615, 330)], fill=(255, 255, 255), width=2)
    draw.polygon([(615, 340), (605, 335), (605, 345)], fill=(56, 189, 248))
    draw.line([(585, 340), (615, 340)], fill=(56, 189, 248), width=2)
    
    # Subtitle note at bottom
    draw.text((W // 2, 615), "Ảnh sản phẩm được tách nền trong suốt 100% bằng script Python PIL, hòa quyện tự nhiên trên cả 2 nền màu", 
              fill=(148, 163, 184), font=font_small, anchor="mm")
    
    img.save('reports/darkmode_showcase.png')
    print("make_darkmode_showcase OK!")

make_darkmode_showcase()
