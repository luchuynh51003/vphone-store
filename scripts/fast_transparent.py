import os
import sys
import glob
import time
from concurrent.futures import ProcessPoolExecutor
from PIL import Image, ImageDraw

PRODUCTS_DIR = 'assets/images/products'

def process_image_file(fname):
    p = os.path.join(PRODUCTS_DIR, fname)
    if not os.path.isfile(p):
        return (fname, 'not_found')
    
    ext = os.path.splitext(fname)[1].lower()
    base = os.path.splitext(fname)[0]
    target_png = os.path.join(PRODUCTS_DIR, base + '.png')

    try:
        im = Image.open(p).convert('RGBA')
    except Exception as e:
        return (fname, f'open_error: {e}')

    w, h = im.size
    
    # Check corners
    corners = [im.getpixel((0,0)), im.getpixel((w-1, 0)), im.getpixel((0, h-1)), im.getpixel((w-1, h-1))]
    
    # If all 4 corners already transparent
    if all(c[3] < 20 for c in corners):
        # Already transparent, just ensure saved as PNG if it was webp or something
        if target_png != p or ext == '.webp':
            im.save(target_png, 'PNG', optimize=True)
        return (fname, 'already_transparent')

    # Detect background color from light corners
    light_corners = [c for c in corners if (c[0]+c[1]+c[2])/3 > 200]
    if not light_corners:
        # Check border points
        border_pts = [(w//4, 0), (3*w//4, 0), (w//4, h-1), (3*w//4, h-1), (0, h//4), (w-1, h//4)]
        light_corners = [im.getpixel(pt) for pt in border_pts if (im.getpixel(pt)[0]+im.getpixel(pt)[1]+im.getpixel(pt)[2])/3 > 200]
        
    if not light_corners:
        return (fname, 'no_light_bg')

    bg_r = int(sum(c[0] for c in light_corners) / len(light_corners))
    bg_g = int(sum(c[1] for c in light_corners) / len(light_corners))
    bg_b = int(sum(c[2] for c in light_corners) / len(light_corners))

    # Floodfill from multiple boundary points that match the background
    seed_points = []
    # 4 corners
    for pt in [(0, 0), (w-1, 0), (0, h-1), (w-1, h-1)]:
        c = im.getpixel(pt)
        if max(abs(c[0]-bg_r), abs(c[1]-bg_g), abs(c[2]-bg_b)) <= 35 or c[3] < 30:
            seed_points.append(pt)

    # Edge midpoints and quarters
    for fx in [0.25, 0.5, 0.75]:
        x = int(fx * (w - 1))
        for y in [0, h - 1]:
            c = im.getpixel((x, y))
            if max(abs(c[0]-bg_r), abs(c[1]-bg_g), abs(c[2]-bg_b)) <= 30 or c[3] < 30:
                seed_points.append((x, y))

    for fy in [0.25, 0.5, 0.75]:
        y = int(fy * (h - 1))
        for x in [0, w - 1]:
            c = im.getpixel((x, y))
            if max(abs(c[0]-bg_r), abs(c[1]-bg_g), abs(c[2]-bg_b)) <= 30 or c[3] < 30:
                seed_points.append((x, y))

    # Fill transparent
    fill_color = (bg_r, bg_g, bg_b, 0)
    for spt in seed_points:
        curr = im.getpixel(spt)
        if curr[3] > 0:  # Not yet filled
            ImageDraw.floodfill(im, spt, fill_color, thresh=32)

    # Clean border pixels if any white pixel remains directly on boundary
    pix = im.load()
    for x in range(w):
        for y in [0, h-1]:
            c = pix[x, y]
            if max(abs(c[0]-bg_r), abs(c[1]-bg_g), abs(c[2]-bg_b)) <= 32:
                pix[x, y] = (c[0], c[1], c[2], 0)
    for y in range(h):
        for x in [0, w-1]:
            c = pix[x, y]
            if max(abs(c[0]-bg_r), abs(c[1]-bg_g), abs(c[2]-bg_b)) <= 32:
                pix[x, y] = (c[0], c[1], c[2], 0)

    # Save to PNG
    im.save(target_png, 'PNG', optimize=True)
    return (fname, 'converted')

def main():
    t0 = time.time()
    files = [f for f in sorted(os.listdir(PRODUCTS_DIR)) if not f.startswith('.') and os.path.isfile(os.path.join(PRODUCTS_DIR, f))]
    image_files = [f for f in files if os.path.splitext(f)[1].lower() in ('.png', '.jpg', '.jpeg', '.webp')]
    print(f"Total images to process: {len(image_files)}", flush=True)

    with ProcessPoolExecutor(max_workers=4) as executor:
        results = list(executor.map(process_image_file, image_files))

    counts = {}
    for fname, status in results:
        counts[status] = counts.get(status, 0) + 1

    dt = round(time.time() - t0, 2)
    print(f"Done in {dt}s! Results: {counts}", flush=True)

if __name__ == '__main__':
    main()
