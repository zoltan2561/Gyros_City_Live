import hashlib
import json
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'scripts' / 'signage'))
from reviewed_artwork import apply_reviewed_artwork


class ReviewedArtworkTest(unittest.TestCase):
    def test_reviewed_photo_survives_rebuild_without_overwriting_an_old_release(self):
        with tempfile.TemporaryDirectory() as folder:
            directory = Path(folder)
            old = 'menu-123-' + hashlib.sha256(b'old-price').hexdigest() + '.webp'
            clean = 'menu-123-' + hashlib.sha256(b'clean-photo').hexdigest() + '.webp'
            (directory / old).write_bytes(b'old-price')
            (directory / clean).write_bytes(b'clean-photo')
            (directory / 'reviewed-artwork.json').write_text(json.dumps({'menu-123': clean}))
            extracted = {'menu-123': old, 'other': 'unchanged'}
            result = apply_reviewed_artwork(extracted, directory)
            self.assertEqual(clean, result['menu-123'])
            self.assertEqual('unchanged', result['other'])
            self.assertEqual(old, extracted['menu-123'])
            self.assertEqual(b'old-price', (directory / old).read_bytes())

    def test_modified_reviewed_photo_cannot_silently_enter_an_offline_package(self):
        with tempfile.TemporaryDirectory() as folder:
            directory = Path(folder)
            name = 'pizza-335-' + hashlib.sha256(b'original').hexdigest() + '.webp'
            (directory / name).write_bytes(b'changed')
            (directory / 'reviewed-artwork.json').write_text(json.dumps({'pizza-335': name}))
            with self.assertRaises(ValueError):
                apply_reviewed_artwork({}, directory)

    def test_a_reviewed_file_cannot_be_assigned_to_another_food(self):
        with tempfile.TemporaryDirectory() as folder:
            directory = Path(folder)
            (directory / 'reviewed-artwork.json').write_text(json.dumps({'pizza-335': 'pizza-334-' + 'a' * 64 + '.webp'}))
            with self.assertRaises(ValueError):
                apply_reviewed_artwork({}, directory)


if __name__ == '__main__':
    unittest.main()
