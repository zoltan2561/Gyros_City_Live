"""Keep reviewed, text-free artwork when rebuilding raw video extractions."""
import hashlib
import json
import re
from pathlib import Path


def apply_reviewed_artwork(manifest: dict, directory: Path) -> dict:
    result = dict(manifest)
    index = directory / 'reviewed-artwork.json'
    if not index.exists():
        return result
    reviewed = json.loads(index.read_text(encoding='utf-8'))
    for key, filename in reviewed.items():
        if not re.fullmatch(r'(burger|menu|pizza)-[0-9]+', key):
            raise ValueError('Unknown reviewed artwork key: ' + key)
        match = re.fullmatch(re.escape(key) + r'-([a-f0-9]{64})\.webp', filename)
        if not match:
            raise ValueError('Reviewed photo does not match its food: ' + key)
        path = directory / filename
        if not path.is_file() or hashlib.sha256(path.read_bytes()).hexdigest() != match.group(1):
            raise ValueError('Reviewed artwork is missing or changed: ' + key)
        result[key] = filename
    return result
