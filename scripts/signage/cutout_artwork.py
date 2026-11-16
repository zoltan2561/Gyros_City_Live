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
    # The cardboard box of the Kebab-tál: front wall and rim are table-coloured.
    'menu-121': [
        [(82, 60), (432, 60), (438, 70), (462, 242), (458, 250), (50, 250), (44, 240), (48, 225), (78, 70)],
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
# White plates are the model's weak spot (a white rim on a light table reads as background): Vega-tál and the cheese
# gyros plate share one photo. The plate polygon is traced by hand; the exact rim is then found by colour (the plate is
# white, the table saturated orange). The fork keeps the model's edge inside its traced outline. Napkin and the knife that
# runs out of the frame are left out on purpose.
GYROS_PLATE = [(150, 300), (165, 290), (547, 222), (1090, 150), (1105, 150), (1112, 160), (1528, 745), (1534, 760),
               (1522, 775), (1500, 780), (410, 962), (392, 962), (380, 950), (160, 312)]
GYROS_FORK = [(46, 382), (52, 372), (70, 375), (80, 370), (98, 366), (108, 364), (125, 366), (135, 364), (140, 368),
              (150, 430), (170, 500), (182, 548), (180, 575), (165, 592), (152, 602), (200, 760), (230, 880), (262, 950),
              (272, 985), (262, 1002), (240, 1008), (215, 1003), (195, 990), (172, 840), (150, 740), (128, 640), (125, 604),
              (105, 590), (85, 575), (70, 548), (68, 500), (60, 440), (50, 400)]
# The cheese pile rises above the plate's back rim; the model's edge is trusted inside this allowance area.
GYROS_CHEESE = [(420, 236), (500, 150), (700, 90), (1000, 75), (1110, 110), (1100, 152), (547, 224)]
# The cheese-sauce gyros plate (menu-128): same set-up, different positions; the knife ends just inside the frame.
SAUCE_PLATE = [(222, 222), (232, 207), (1105, 100), (1120, 102), (1128, 112), (1478, 742), (1481, 758), (1470, 772),
               (1445, 775), (375, 928), (350, 930), (340, 915), (228, 235)]
SAUCE_FORK = [(100, 232), (106, 224), (125, 228), (133, 222), (150, 225), (165, 222), (190, 224), (196, 232), (210, 300),
              (222, 380), (228, 425), (222, 470), (205, 490), (222, 520), (240, 700), (262, 880), (266, 915), (255, 935),
              (225, 945), (188, 935), (178, 880), (175, 700), (168, 520), (155, 490), (125, 470), (108, 440), (102, 380),
              (98, 300)]
SAUCE_KNIFE = [(1230, 166), (1240, 160), (1262, 166), (1330, 290), (1385, 390), (1420, 450), (1500, 600), (1538, 690),
               (1538, 722), (1525, 730), (1495, 724), (1440, 640), (1380, 520), (1340, 410), (1290, 320), (1245, 230)]
# Tray meals (rántott sajt menü, nuggets menü): a white plate on a white tray on a red table. One tray polygon plus the
# plate oval; the can and the fries that rise above the plate are allowed through from the model.
CHEESE_TRAY = [(100, 190), (110, 183), (505, 182), (515, 188), (522, 200), (522, 345), (515, 362), (500, 372), (120, 375),
               (95, 366), (85, 348), (88, 330)]
CHEESE_PLATE = [(72, 290), (80, 255), (110, 215), (160, 190), (235, 178), (320, 185), (385, 215), (402, 260), (395, 310),
                (360, 345), (300, 372), (240, 380), (170, 372), (110, 345), (80, 315)]
NUGGET_TRAY = [(95, 205), (105, 194), (528, 190), (540, 195), (548, 212), (549, 350), (543, 368), (525, 382), (110, 385),
               (78, 372), (68, 355), (75, 330)]
NUGGET_PLATE = [(58, 265), (64, 235), (90, 205), (140, 190), (240, 185), (330, 190), (390, 215), (416, 255), (410, 310),
                (375, 350), (310, 378), (240, 385), (160, 378), (95, 350), (65, 310)]
TRAY_ALLOWED = {'menu-165': [[(376, 45), (451, 45), (451, 245), (376, 245)], [(130, 150), (420, 150), (420, 200), (130, 200)]],
                'menu-248': [[(388, 35), (476, 35), (476, 255), (388, 255)], [(130, 160), (430, 160), (430, 200), (130, 200)]]}
# Round white plates the model drops from the cutlet and fries dishes (rántott szelet, rántott sajt, halrudacska).
CUTLET_PLATE = [(78, 285), (85, 230), (110, 185), (160, 150), (230, 135), (320, 130), (420, 120), (500, 130), (570, 170),
                (615, 230), (634, 290), (625, 330), (580, 365), (520, 395), (450, 420), (370, 438), (300, 438), (220, 430),
                (150, 405), (100, 360), (80, 320)]
CUTLET_MENU_PLATE = [(8, 385), (12, 330), (35, 285), (90, 262), (200, 250), (330, 240), (450, 245), (550, 270), (615, 310),
                     (642, 345), (635, 380), (590, 415), (520, 440), (430, 458), (340, 466), (250, 462), (160, 450), (80, 430),
                     (30, 410)]
CHEESE_PLATE_ROUND = [(94, 210), (100, 180), (130, 145), (190, 115), (260, 98), (340, 90), (420, 92), (490, 105), (540, 135),
                      (560, 185), (562, 225), (545, 265), (500, 295), (430, 325), (360, 342), (300, 347), (230, 340), (160, 318),
                      (115, 280), (96, 240)]
STICKS_PLATE = [(125, 208), (132, 180), (165, 150), (225, 128), (300, 115), (380, 112), (450, 118), (500, 135), (535, 175),
                (545, 225), (530, 265), (490, 300), (430, 328), (360, 345), (300, 348), (230, 338), (170, 310), (135, 265)]
PLATES = {'menu-161': ([CUTLET_PLATE], [], [[(280, 80), (590, 80), (590, 200), (280, 200)]]),
          'menu-167': ([CUTLET_MENU_PLATE], [], [[(462, 20), (604, 20), (604, 262), (462, 262)], [(120, 160), (380, 160), (380, 255), (120, 255)], [(30, 200), (600, 200), (600, 300), (30, 300)]]),
          'menu-158': ([CHEESE_PLATE_ROUND], [], [[(150, 100), (500, 100), (500, 150), (150, 150)]]),
          'menu-160': ([STICKS_PLATE], [], [[(230, 90), (520, 90), (520, 150), (230, 150)]]),
          'menu-165': ([CHEESE_TRAY, CHEESE_PLATE], [], TRAY_ALLOWED['menu-165']), 'menu-248': ([NUGGET_TRAY, NUGGET_PLATE], [], TRAY_ALLOWED['menu-248']),
          'menu-128': ([SAUCE_PLATE], [SAUCE_FORK, SAUCE_KNIFE], []), 'menu-133': ([GYROS_PLATE], [GYROS_FORK], [GYROS_CHEESE]), 'menu-156': ([GYROS_PLATE], [GYROS_FORK], [GYROS_CHEESE])}


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
    if key in PLATES:
        alpha = plate_mask(photo, alpha, key)
    return alpha


def plate_mask(photo, model_alpha, key):
    import numpy as np
    from PIL import Image, ImageDraw
    from scipy import ndimage as ndi
    plate_polygons, extra_polygons, allowed_polygons = PLATES[key]

    def raster(polygons):
        layer = Image.new('L', photo.size, 0)
        for polygon in polygons:
            ImageDraw.Draw(layer).polygon(polygon, fill=255)
        return np.asarray(layer) > 127

    plate, extra, allowed = raster(plate_polygons), raster(extra_polygons), raster(allowed_polygons)
    hsv = np.asarray(photo.convert('HSV'), dtype=np.float32) / 255.0
    whiteish = (hsv[..., 1] < 0.22) & (hsv[..., 2] > 0.62)
    model = np.asarray(model_alpha) > 127
    rim = ndi.binary_dilation(plate, iterations=10)
    result = ndi.binary_erosion(plate, iterations=14) | (rim & whiteish) | (model & ndi.binary_dilation(plate, iterations=4))
    result |= (model & ndi.binary_dilation(extra, iterations=6)) | ndi.binary_erosion(extra, iterations=6) | (model & allowed)
    # A wooden serving board behind the plate is tan (low saturation); breading, cheese and fries are far more saturated.
    # Tan pixels that are not deep inside the plate are board, never food.
    tan = (hsv[..., 0] > 0.05) & (hsv[..., 0] < 0.12) & (hsv[..., 1] > 0.18) & (hsv[..., 1] < 0.58) & (hsv[..., 2] > 0.5)
    result &= ~(tan & ~ndi.binary_erosion(plate, iterations=14))
    result = ndi.binary_fill_holes(ndi.binary_opening(result, iterations=2))
    return Image.fromarray((result * 255).astype(np.uint8))


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
