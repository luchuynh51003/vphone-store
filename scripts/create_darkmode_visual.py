from PIL import Image, ImageDraw, ImageFont
import os

width, height = 1200, 680
canvas = Image.new('RGB', (width, height), (15, 23, 42)) # Slate dark background
draw = ImageDraw.Draw(canvas)

# Title
# Try to load a nice font or default
font_large = None
font_med = None
font_small = None
try:
    font_large = ImageFont.truetype('/System/Library/Fonts/Helvetica.ttc', 30)
    font_med = ImageFont.truetype('/System/Library/Fonts/Helvetica.ttc', 22)
    font_small = ImageFont.truetype('/System/Library/Fonts/Helvetica.ttc', 17)
except Exception:
    font_large = ImageFont.load_default()
    font_med = font_large
    font_small = font_large

draw.text((width//2, 35), "V-PHONE STORE: HỆ THỐNG GIAO DIỆN KÉP LIGHT MODE & CYBER DARK MODE", 
          fill=(255, 255, 255), font=font_large, anchor="mm")

# Left: Light Mode Box
draw.rectangle([50, 75, 560, 620], fill=(246, 249, 252), outline=(203, 213, 225), width=2)
draw.rectangle([50, 75, 560, 130], fill=(226, 232, 240))
draw.text((305, 102), "LIGHT MODE (GIAO DIỆN BAN NGÀY TIÊU CHUẨN)", fill=(15, 23, 42), font=font_med, anchor="mm")

# Right: Dark Mode Box
draw.rectangle([640, 75, 1150, 620], fill=(11, 19, 41), outline=(56, 189, 248), width=2)
draw.rectangle([640, 75, 1150, 130], fill=(19, 31, 55))
draw.text((895, 102), "CYBER DARK MODE (CHẾ ĐỘ TỐI CÔNG NGHỆ FLAGSHIP)", fill=(56, 189, 248), font=font_med, anchor="mm")

# Load Phone Image
phone_path = 'assets/images/products/iphone-16-promax-titan-sa-mac.png'
if os.path.exists(phone_path):
    phone = Image.open(phone_path).convert('RGBA')
    phone_resized = phone.resize((210, 260), Image.Resampling.LANCZOS)
    
    # Left inner card (Light)
    draw.rectangle([110, 155, 500, 580], fill=(255, 255, 255), outline=(226, 232, 240), width=1)
    canvas.paste(phone_resized, (200, 175), phone_resized)
    draw.text((305, 455), "iPhone 16 Pro Max 256GB", fill=(15, 23, 42), font=font_med, anchor="mm")
    draw.text((305, 490), "Titan Sa Mạc • Chip Apple A18 Pro", fill=(100, 116, 139), font=font_small, anchor="mm")
    draw.text((305, 530), "34.990.000 đ", fill=(2, 132, 199), font=font_large, anchor="mm")
    
    # Right inner card (Cyber Dark)
    draw.rectangle([700, 155, 1090, 580], fill=(19, 31, 55), outline=(56, 189, 248), width=1)
    canvas.paste(phone_resized, (790, 175), phone_resized)
    draw.text((895, 455), "iPhone 16 Pro Max 256GB", fill=(241, 245, 249), font=font_med, anchor="mm")
    draw.text((895, 490), "Titan Sa Mạc • Chip Apple A18 Pro", fill=(148, 163, 184), font=font_small, anchor="mm")
    draw.text((895, 530), "34.990.000 đ", fill=(56, 189, 248), font=font_large, anchor="mm")

# Center toggle pill
draw.rectangle([565, 330, 635, 370], fill=(30, 41, 59), outline=(56, 189, 248), width=1)
draw.text((600, 350), "⇄", fill=(255, 255, 255), font=font_large, anchor="mm")

# Bottom note
draw.text((width//2, 655), "Ảnh sản phẩm được tách nền trong suốt 100% bằng script Python PIL, hòa quyện tự nhiên trên cả 2 nền màu", 
          fill=(148, 163, 184), font=font_small, anchor="mm")

canvas.save('reports/darkmode_showcase.png')
print("Created reports/darkmode_showcase.png successfully!")
