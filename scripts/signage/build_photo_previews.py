"""Build small transparent previews for the TV's versioned product artwork."""

import base64
import json
import re
from io import BytesIO
from pathlib import Path

from PIL import Image


root = Path(__file__).resolve().parents[2]
assets = root / "public/signage/reference-assets"
previews = {}
for photo in sorted(assets.glob("*.webp")):
    if not re.fullmatch(r"(?:menu|burger|pizza)-\d+-[0-9a-f]{64}", photo.stem):
        continue
    with Image.open(photo) as original:
        image = original.convert("RGBA")
        image.thumbnail((140, 110), Image.Resampling.LANCZOS)
        buffer = BytesIO()
        image.quantize(colors=64).save(buffer, format="PNG", optimize=True)
        previews[photo.name] = "data:image/png;base64," + base64.b64encode(buffer.getvalue()).decode("ascii")

target = root / "resources/signage/photo-previews.json"
target.parent.mkdir(parents=True, exist_ok=True)
target.write_text(json.dumps(previews, indent=1) + "\n", encoding="utf-8")
