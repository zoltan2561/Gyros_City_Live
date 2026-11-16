"""Cut reviewed catalogue photos cleanly out of their backgrounds for the TV clones.

The three supplied videos show every dish as an isolated cut-out. The first rebuild cropped polygons from video
frames (flat, clipped edges) and later kept whole catalogue photographs (rectangular, table still visible).
This script produces the real thing: a smooth alpha cut-out of the complete original photograph.

Dev-only dependencies (never needed on the server or the TV): ``pip install rembg[cpu] pillow numpy``. The u2net
model is downloaded by rembg on first use. Results are immutable ``key-SHA256.webp`` files recorded in
``manifest.json`` and ``reviewed-artwork.json``; old files stay in place for historical releases.

    python scripts/signage/cutout_artwork.py            # build every entry of cutout-sources.json
    python scripts/signage/cutout_artwork.py --only menu-170 burger-60
"""
import argparse
import hashlib
import io
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2] / 'public' / 'signage' / 'reference-assets'
SOURCES = ROOT / 'cutout-sources.json'
MAX_SIDE = 1400
# Hand-drawn polygons (source-pixel coordinates) for parts the model drops because they share the table's colour:
# the kraft-paper chip boxes of menu-170 and menu-136. They are united with the model mask, never used alone.
MANUAL_MASKS = {
    'menu-170': [
        [(207, 136), (250, 175), (290, 205), (305, 215), (330, 213), (345, 205), (420, 160), (447, 140), (437, 225),
         (420, 300), (402, 388), (238, 386), (228, 330), (215, 255), (207, 160)],
    ],
    # The chip box behind the burger of the City Burger Plusz menu.
    'menu-136': [
        [(222, 73), (262, 30), (300, 22), (335, 40), (380, 60), (415, 66), (418, 100), (417, 196), (380, 200), (300, 200),
         (222, 236)],
    ],
}
# The Fish Burger photo shows the whole cutting board; the model keeps board and burger together. Only the burger stays.
FISH_BURGER = [(228, 258), (226, 225), (235, 185), (262, 145), (300, 115), (350, 98), (400, 102), (448, 120), (482, 160),
               (506, 205), (517, 240), (515, 262), (535, 285), (556, 310), (566, 335), (567, 365), (560, 395), (545, 422),
               (515, 442), (480, 456), (430, 469), (380, 474), (330, 472), (292, 461), (262, 443), (244, 424), (230, 400),
               (226, 384), (195, 378), (195, 330), (205, 308), (222, 300), (216, 285), (222, 270)]
KEEP_ONLY = {'menu-65': FISH_BURGER, 'burger-65': FISH_BURGER}


def sha256(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def load_source(name: str):
    from PIL import Image
    path = ROOT / name
    match = re.fullmatch(r'((?:burger|menu|pizza)-[0-9]+)-([a-f0-9]{64})\.webp', name)
    if not match or sha256(path.read_bytes()) != match.group(2):
        raise ValueError('Cut-out source is missing or changed: ' + name)
    return Image.open(path)


def food_mask(photo, key, session):
    """Alpha of the dish. The model sees a colour/contrast boosted copy so kraft paper and plates survive."""
    from PIL import Image, ImageDraw, ImageEnhance, ImageChops
    from rembg import remove
    boosted = ImageEnhance.Contrast(ImageEnhance.Color(photo).enhance(1.6)).enhance(1.5)
    alpha = remove(boosted, session=session, post_process_mask=True).getchannel('A')
    for polygon in MANUAL_MASKS.get(key, []):
        scale = 4
        layer = Image.new('L', (photo.width * scale, photo.height * scale), 0)
        ImageDraw.Draw(layer).polygon([(x * scale, y * scale) for x, y in polygon], fill=255)
        alpha = ImageChops.lighter(alpha, layer.resize(photo.size, Image.LANCZOS))
    if key in KEEP_ONLY:
        from PIL import ImageFilter
        keep = Image.new('L', (photo.width * 4, photo.height * 4), 0)
        ImageDraw.Draw(keep).polygon([(x * 4, y * 4) for x, y in KEEP_ONLY[key]], fill=255)
        keep = keep.resize(photo.size, Image.LANCZOS).filter(ImageFilter.MaxFilter(3))  # the model sets the true edge
        alpha = ImageChops.multiply(alpha, keep)
    return alpha


def cut_with_model(source, key, session):
    from PIL import Image, ImageFilter
    photo = source.convert('RGB')
    if max(photo.size) > 1600:
        photo.thumbnail((1600, 1600), Image.LANCZOS)
    alpha = food_mask(photo, key, session)
    alpha = alpha.filter(ImageFilter.MinFilter(3)).filter(ImageFilter.GaussianBlur(0.8))
    rgba = photo.convert('RGBA')
    rgba.putalpha(alpha)
    return rgba


def round_pizza(source):
    """Video-derived pizza cut-outs have flat, polygonal sides. Keep the largest clean ellipse inside them."""
    import numpy as np
    from PIL import Image
    rgba = source.convert('RGBA')
    alpha = np.asarray(rgba.getchannel('A'), dtype=np.float32) / 255.0
    ys, xs = np.nonzero(alpha > 0.5)
    cx, cy = xs.mean(), ys.mean()
    values, vectors = np.linalg.eigh(np.cov(np.vstack([xs - cx, ys - cy])))
    axes = 2.0 * np.sqrt(values)
    grid_y, grid_x = np.mgrid[0:alpha.shape[0], 0:alpha.shape[1]].astype(np.float32)
    local = np.stack([grid_x - cx, grid_y - cy], axis=-1) @ vectors
    radius = np.sqrt((local[..., 0] / axes[0]) ** 2 + (local[..., 1] / axes[1]) ** 2)
    chosen = 0.88
    for scale in np.arange(1.08, 0.879, -0.01):
        inside = radius <= scale
        if inside.sum() and (inside & (alpha < 0.5)).sum() / inside.sum() < 0.004:
            chosen = float(scale)
            break
    edge = np.clip((chosen - radius) * min(axes) / 1.6 + 0.5, 0.0, 1.0)
    result = rgba.copy()
    result.putalpha(Image.fromarray((np.minimum(alpha, edge) * 255).astype(np.uint8)))
    return result


def finish(rgba):
    from PIL import Image
    box = rgba.getchannel('A').point(lambda v: 255 if v > 8 else 0).getbbox()
    margin = max(2, round(max(rgba.size) * 0.012))
    box = (max(0, box[0] - margin), max(0, box[1] - margin), min(rgba.width, box[2] + margin), min(rgba.height, box[3] + margin))
    rgba = rgba.crop(box)
    if max(rgba.size) > MAX_SIDE:
        rgba.thumbnail((MAX_SIDE, MAX_SIDE), Image.LANCZOS)
    buffer = io.BytesIO()
    rgba.save(buffer, 'WEBP', quality=92, alpha_quality=100, method=6)
    return buffer.getvalue()


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--only', nargs='*', help='limit to these artwork keys')
    arguments = parser.parse_args()
    entries = json.loads(SOURCES.read_text(encoding='utf-8'))
    manifest_path, reviewed_path = ROOT / 'manifest.json', ROOT / 'reviewed-artwork.json'
    manifest = json.loads(manifest_path.read_text(encoding='utf-8'))
    reviewed = json.loads(reviewed_path.read_text(encoding='utf-8'))
    session = None
    for key, entry in entries.items():
        if arguments.only and key not in arguments.only:
            continue
        if not re.fullmatch(r'(burger|menu|pizza)-[0-9]+', key):
            raise ValueError('Unknown artwork key: ' + key)
        source = load_source(entry['source'])
        if entry['method'] == 'rembg':
            if session is None:
                from rembg import new_session
                session = new_session('u2net')
            rgba = cut_with_model(source, key, session)
        elif entry['method'] == 'ellipse':
            rgba = round_pizza(source)
        else:
            raise ValueError('Unknown cut-out method for ' + key)
        data = finish(rgba)
        name = key + '-' + sha256(data) + '.webp'
        path = ROOT / name
        if not path.exists():
            path.write_bytes(data)
        manifest[key] = name
        reviewed[key] = name
        print(key, '<-', entry['source'][:24], entry['method'], '->', name[:24], len(data) // 1024, 'KB')
    manifest_path.write_text(json.dumps(manifest, indent=2) + '\n', encoding='utf-8', newline='\n')
    reviewed_path.write_text(json.dumps(reviewed, indent=2) + '\n', encoding='utf-8', newline='\n')


if __name__ == '__main__':
    main()
