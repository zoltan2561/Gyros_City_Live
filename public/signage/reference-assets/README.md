# Gyros City reference artwork

Price-free food/background assets extracted from the three videos supplied by the user. The original MP4/MOV files remain in Downloads and are not part of this repository.

| Source | Duration | SHA-256 |
| --- | --- | --- |
| Orange Black Grunge Burger Prom.MP4 | 19.968 s | F5725381CC62B6B3E551546C69EA4048E3E10CD27989F3A181FCC6FDFA13C386 |
| termékek2026.10.30.sütlac off.MOV | 96.245 s | 025A45EE48DB24A09D0D122217346792046D3FE6863297F96134ABC97262D737 |
| Black and Orange Grunge Food Video Pizza.MP4 | 240.384 s | 67C8D3533F2D3A4443428B32F6D0D586BD780E435174AD971BC42B8D5408CBDB |

Rebuild using the existing Python/Pillow and FFmpeg installation:

```powershell
python scripts/signage/extract_reference_assets.py --burger "C:/Users/yoli9/Downloads/Orange Black Grunge Burger Prom.MP4" --menu "C:/Users/yoli9/Downloads/termékek2026.10.30.sütlac off.MOV" --pizza "C:/Users/yoli9/Downloads/Black and Orange Grunge Food Video Pizza.MP4"
```

The script crops away price/name panels, derives food silhouettes, and keeps the original food pixels. Motion, names, ingredients and prices are rendered by the shared kiosk code. The recreation is visually based on the supplied frames; it is not a frame-for-frame reproduction of the original editor's animation.

`manifest.json` maps approved artwork keys to immutable `name-FULL_SHA256.ext` files. Rebuilds retain previous immutable files so historical and current releases can coexist. Do not overwrite or remove immutable files referenced by a release. Canonical short filenames are extraction inputs/fallbacks; published scenes and product bindings use the immutable versions. The server validates each product artwork key against its item ID. Per-scene descriptors select the matching backgrounds, masks, fonts and decoration; offline packages include their checksums.

The unmodified local fonts are [Bebas Neue](https://github.com/google/fonts/tree/main/ofl/bebasneue) and [Satisfy](https://github.com/google/fonts/tree/main/apache/satisfy). Their supplied licences are included as `bebasneue-OFL.txt` and `satisfy-LICENSE.txt`. They are served locally, without a font CDN. `grunge-mask.svg` supplies the distressed type treatment.
