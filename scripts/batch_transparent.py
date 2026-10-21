import os
import glob
from collections import deque
from PIL import Image

PRODUCTS_DIR = 'assets/images/products'

def process_single_image(img_path, out_path, tol=28, edge_fade=18):
    try:
        im = Image.open(img_path).convert('RGBA')
    except Exception as e:
        print(f"Error opening {img_path}: {e}")
        return False

    w, h = im.size
    pix = im.load()
    
    corners = [pix[0, 0], pix[w-1, 0], pix[0, h-1], pix[w-1, h-1]]
    
    # If all 4 corners already fully transparent (alpha < 20), check if any border has opaque white
    all_corners_transparent = all(c[3] < 20 for c in corners)
    
    # Calculate background color from light corners or borders
    light_pixels = [c for c in corners if c[3] > 30 and (c[0]+c[1]+c[2])/3 > 200]
    if not light_pixels:
        # Sample border pixels to see if there is white
        border_samples = [pix[x, 0] for x in range(0, w, max(1, w//10))] + [pix[x, h-1] for x in range(0, w, max(1, w//10))]
        light_pixels = [c for c in border_samples if c[3] > 30 and (c[0]+c[1]+c[2])/3 > 200]
        
    if all_corners_transparent and not light_pixels:
        # Already fully transparent, nothing to do
        if img_path != out_path:
            im.save(out_path, 'PNG')
        return True

    if light_pixels:
        bg_r = sum(c[0] for c in light_pixels) / len(light_pixels)
        bg_g = sum(c[1] for c in light_pixels) / len(light_pixels)
        bg_b = sum(c[2] for c in light_pixels) / len(light_pixels)
    else:
        bg_r, bg_g, bg_b = 255.0, 255.0, 255.0

    visited = bytearray(w * h)
    q = deque()

    def color_dist(c):
        return max(abs(c[0]-bg_r), abs(c[1]-bg_g), abs(c[2]-bg_b))

    # Seed top and bottom borders
    for x in range(w):
        for y in [0, h-1]:
            idx = y * w + x
            if not visited[idx]:
                visited[idx] = 1
                c = pix[x, y]
                if c[3] < 30:
                    q.append((x, y))
                elif color_dist(c) <= tol:
                    pix[x, y] = (c[0], c[1], c[2], 0)
                    q.append((x, y))
                    
    # Seed left and right borders
    for y in range(h):
        for x in [0, w-1]:
            idx = y * w + x
            if not visited[idx]:
                visited[idx] = 1
                c = pix[x, y]
                if c[3] < 30:
                    q.append((x, y))
                elif color_dist(c) <= tol:
                    pix[x, y] = (c[0], c[1], c[2], 0)
                    q.append((x, y))

    # BFS Flood fill
    while q:
        cx, cy = q.popleft()
        for nx, ny in ((cx+1, cy), (cx-1, cy), (cx, cy+1), (cx, cy-1)):
            if 0 <= nx < w and 0 <= ny < h:
                nidx = ny * w + nx
                if not visited[nidx]:
                    visited[nidx] = 1
                    nc = pix[nx, ny]
                    if nc[3] < 30:
                        q.append((nx, ny))
                        continue
                    d = color_dist(nc)
                    if d <= tol:
                        pix[nx, ny] = (nc[0], nc[1], nc[2], 0)
                        q.append((nx, ny))
                    elif d <= tol + edge_fade:
                        alpha = int(((d - tol) / float(edge_fade)) * 255)
                        pix[nx, ny] = (nc[0], nc[1], nc[2], min(nc[3], alpha))

    im.save(out_path, 'PNG')
    return True

def run():
    files = sorted(os.listdir(PRODUCTS_DIR))
    processed = 0
    skipped = 0
    errors = 0

    for f in files:
        if f.startswith('.'):
            continue
        p = os.path.join(PRODUCTS_DIR, f)
        if not os.path.isfile(p):
            continue

        ext = os.path.splitext(f)[1].lower()
        base = os.path.splitext(f)[0]
        
        if ext in ('.png', '.webp'):
            target_p = os.path.join(PRODUCTS_DIR, base + '.png')
            if process_single_image(p, target_p):
                processed += 1
            else:
                errors += 1
        elif ext in ('.jpg', '.jpeg'):
            # Produce PNG version with transparent bg
            target_p = os.path.join(PRODUCTS_DIR, base + '.png')
            if process_single_image(p, target_p):
                processed += 1
            else:
                errors += 1
        else:
            skipped += 1

    print(f"Batch completed! Processed: {processed}, Skipped: {skipped}, Errors: {errors}")

if __name__ == '__main__':
    run()
