import hashlib
import json
import re
import sys
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2] / 'public' / 'signage' / 'reference-assets'
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'scripts' / 'signage'))
from reviewed_artwork import apply_reviewed_artwork


class CutoutSourcesTest(unittest.TestCase):
    def setUp(self):
        self.sources = json.loads((ROOT / 'cutout-sources.json').read_text(encoding='utf-8'))
        self.reviewed = json.loads((ROOT / 'reviewed-artwork.json').read_text(encoding='utf-8'))
        self.manifest = json.loads((ROOT / 'manifest.json').read_text(encoding='utf-8'))

    def test_every_source_is_an_unchanged_photo_of_the_same_dish(self):
        for key, entry in self.sources.items():
            item = re.fullmatch(r'(?:burger|menu|pizza)-([0-9]+)', key).group(1)
            match = re.fullmatch(r'(?:burger|menu|pizza)-' + item + r'-([a-f0-9]{64})\.webp', entry['source'])
            self.assertIsNotNone(match, key + ' points at another dish')
            self.assertEqual(match.group(1), hashlib.sha256((ROOT / entry['source']).read_bytes()).hexdigest(), key)
            self.assertIn(entry['method'], ('rembg', 'ellipse'))

    def test_every_cutout_is_the_reviewed_and_published_artwork_of_its_dish(self):
        for key in self.sources:
            self.assertEqual(self.reviewed[key], self.manifest[key], key)
            self.assertNotEqual(self.sources[key]['source'], self.manifest[key], key + ' was never cut out')
        self.assertEqual(self.manifest, {**self.manifest, **apply_reviewed_artwork(self.manifest, ROOT)})

    def test_published_artwork_has_a_transparent_background(self):
        try:
            from PIL import Image
        except ImportError:
            self.skipTest('Pillow is not installed')
        for key in self.sources:
            image = Image.open(ROOT / self.manifest[key]).convert('RGBA')
            alpha = image.getchannel('A')
            self.assertEqual(0, alpha.getpixel((0, 0)), key + ' keeps its background in the corner')
            self.assertGreater(sum(alpha.histogram()[201:]), image.width * image.height * 0.25, key)


class WhitePlateTest(unittest.TestCase):
    def test_the_whole_white_plate_survives_the_cut_out(self):
        try:
            from PIL import Image
        except ImportError:
            self.skipTest('Pillow is not installed')
        manifest = json.loads((ROOT / 'manifest.json').read_text(encoding='utf-8'))
        # Landmarks on the lower and right plate rim: a white rim on a light table is what the model tends to drop.
        landmarks = {'menu-133': [(.5, .72), (.55, .78), (.9, .55)], 'menu-156': [(.5, .72), (.55, .78), (.9, .55)],
                     'menu-128': [(.57, .73), (.5, .78), (.88, .6)]}
        for key, points in landmarks.items():
            alpha = Image.open(ROOT / manifest[key]).convert('RGBA').getchannel('A')
            for x, y in points:
                self.assertGreater(alpha.getpixel((int(x * alpha.width), int(y * alpha.height))), 200, key + ' lost its plate rim')


class TraysPlatesAndBoxesTest(unittest.TestCase):
    """Rántott sajt menü, nuggets menü, halrudacska, rántott sajt and the kebab tál lost plates, trays or a box wall."""

    def setUp(self):
        try:
            from PIL import Image
        except ImportError:
            self.skipTest('Pillow is not installed')
        self.Image = Image
        self.manifest = json.loads((ROOT / 'manifest.json').read_text(encoding='utf-8'))

    def white_share(self, key):
        image = self.Image.open(ROOT / self.manifest[key]).convert('RGBA')
        pixels = list(image.getdata()) if not hasattr(image, 'get_flattened_data') else list(image.get_flattened_data())
        solid = [p for p in pixels if p[3] > 200]
        return sum(1 for r, g, b, _ in solid if min(r, g, b) > 205) / max(len(solid), 1)

    def test_white_plates_and_trays_are_kept(self):
        for key, minimum in {'menu-158': .2, 'menu-160': .15, 'menu-248': .2}.items():
            self.assertGreater(self.white_share(key), minimum, key + ' lost its plate or tray')

    def test_the_tray_front_edge_of_the_cheese_and_nugget_menus_survives(self):
        for key in ('menu-165', 'menu-248'):
            alpha = self.Image.open(ROOT / self.manifest[key]).convert('RGBA').getchannel('A')
            for x, y in ((.5, .88), (.5, .93)):
                self.assertGreater(alpha.getpixel((int(x * alpha.width), int(y * alpha.height))), 200, key)

    def test_the_kebab_box_keeps_its_front_wall(self):
        image = self.Image.open(ROOT / self.manifest['menu-121'])
        self.assertLess(image.width / image.height, 2.25)  # contents only is about 2.34 wide-to-high


class KebabTableTest(unittest.TestCase):
    def test_kebab_background_is_the_whole_table_not_the_dark_bottom_edge_of_the_frame(self):
        try:
            from PIL import Image
        except ImportError:
            self.skipTest('Pillow is not installed')
        manifest = json.loads((ROOT / 'manifest.json').read_text(encoding='utf-8'))
        image = Image.open(ROOT / manifest['wood']).convert('L')
        self.assertGreater(image.height, image.width)  # a vertical strip is stretched sideways into horizontal wood grain
        pixels = image.resize((1, 8), Image.BOX)
        for row in range(8):
            self.assertGreater(pixels.getpixel((0, row)), 70, 'row ' + str(row) + ' of the table texture is black')


if __name__ == '__main__':
    unittest.main()
