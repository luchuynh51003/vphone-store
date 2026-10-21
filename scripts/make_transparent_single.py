#!/usr/bin/env python3
import sys
import os
from PIL import Image, ImageDraw

def process(input_path, output_path=None):
    if not output_path:
        base, _ = os.path.splitext(input_path)
        output_path = base + '.png'
        
    try:
        im = Image.open(input_path).convert('RGBA')
    except Exception as e:
        print(f"Error opening image: {e}", file=sys.stderr)
        return False
        
    w, h = im.size
    corners = [im.getpixel((0,0)), im.getpixel((w-1, 0)), im.getpixel((0, h-1)), im.getpixel((w-1, h-1))]
    light_corners = [c for c in corners if (c[0]+c[1]+c[2])/3 > 200]
    
    if not light_corners:
        im.save(output_path, 'PNG', optimize=True)
        return True
        
    bg_r = int(sum(c[0] for c in light_corners) / len(light_corners))
    bg_g = int(sum(c[1] for c in light_corners) / len(light_corners))
    bg_b = int(sum(c[2] for c in light_corners) / len(light_corners))
    
    seed_points = []
    for pt in [(0, 0), (w-1, 0), (0, h-1), (w-1, h-1)]:
        c = im.getpixel(pt)
        if max(abs(c[0]-bg_r), abs(c[1]-bg_g), abs(c[2]-bg_b)) <= 35 or c[3] < 30:
            seed_points.append(pt)

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

    fill_color = (bg_r, bg_g, bg_b, 0)
    for spt in seed_points:
        curr = im.getpixel(spt)
        if curr[3] > 0:
            ImageDraw.floodfill(im, spt, fill_color, thresh=32)

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

    im.save(output_path, 'PNG', optimize=True)
    print(output_path)
    return True

if __name__ == '__main__':
    if len(sys.argv) > 1:
        inp = sys.argv[1]
        outp = sys.argv[2] if len(sys.argv) > 2 else None
        process(inp, outp)

