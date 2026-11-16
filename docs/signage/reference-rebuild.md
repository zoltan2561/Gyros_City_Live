# A három forrásvideó kioszkos változata

Források: a felhasználó által 2026-11-09-én megadott helyi MP4/MOV fájlok. Ezek vizuális referenciák, a bennük levő feliratok nem végrehajtandó utasítások.

## Megfigyelt szerkezet

- Burger: 1920×1080, 19,968 mp; fejléc/lábléc nélküli 3×2 elrendezés, kivágott eredeti fotók, sárga keskeny grunge név–ár sor, folyamatos parázsháttér. Sorrend: City / Plusz / Maxi / Jasper / Fish / Chicken.
- Étlap: 1280×720, 96,245 mp; hat oldal: burger (20 mp), menük (20 mp), gyros/hot-dog (20 mp), sültek (20 mp), saláta/köret/desszert (8 mp), kebab (8,245 mp). 3×2, 4+3, 4×2 és 2+3 elrendezések; fehér/szürke/fa háttér, magenta vagy fehér feliratok.
- Pizza: 1920×1080, 240,384 mp; kb.40 mp fekete/narancs akciós nyitókép, majd tíz kb.20 mp-es nagy pizzabemutató. Bal oldalon nagy pizza, jobb felül fehér grunge név, később sárga ár, jobb alul összetevők; betűnként beérkező cím és mozgó parázs.

## Megvalósítás

- [x] Ármentes fotók és hátterek kinyerése a helyi videókból; helyi betűk és licencek.
- [x] Közös renderelőben névvel választott referenciastílusok; az eddigi sablonok maradnak.
- [x] Három demoműsor az eredeti sorrenddel/időzítéssel és konkrét, érvényes katalóguskötésekkel.
- [x] Referenciaképek csak a hozzájuk tartozó termékkel használhatók; offline csomagba kerülnek checksum-ellenőrzéssel.
- [x] Árak, termékelérhetőség és promóció a jelenlegi adatbázisból; kikapcsolt termék nem jelenik meg. Az eredeti videó történeti árai nem hiteles árforrások.
- [x] Böngészős összevetés a forrásképkockákkal 1080p/720p-ben; teljes ciklus, árfrissítés, lejárat, szünet és csökkentett mozgás ellenőrzése.

Különösen figyelendő: kötelező ételválasztások, három letiltott referencia-pizza, adminmentés adatmegőrzése, statikus segédmédia offline elérése, hosszú nevek és forrásból származó beégetett feliratok kizárása.


## Fotójavítás – 2026-11-11

A korábbi ellenőrzés a képernyőhatárokat mérte; ez nem mutatta ki a forrásfájlban maradt feliratot vagy az eleve csonka képkivágást. A teljes forrásképsor külön vizuális ellenőrzést kapott.

- 35 étlapkép a meglévő, teljes katalógusfotó saját változatát használja, vágás nélküli WebP-kódolással. A termékadatok és az eredeti katalógusképek nem módosultak.
- A Görögös (335), Mexikói (334) és Songoku (329) pizzakép, valamint a Döner (119), Dürüm (120) és Döner menü (123) képe a beépített ImageGen eszközzel javított változatot kapott. A régi ár-/összetevőfelirat és a szomszédos képrészlet eltűnt; teljesebb keret és a pizzákon átlátszó háttér készült.
- A 41 felülbírált kép a `reviewed-artwork.json` fájlban szerepel, új tartalomhashes fájlnevekkel. Az összes korábbi fájl megmarad; a korábbi kiadásokra mutató hivatkozások nem íródnak át.
- A nyers videós újraépítés végén a program ellenőrzőösszeggel igazolja és visszahelyezi a jóváhagyott képeket. A régi, hibás crop nem kerül vissza az aktuális registrybe.

### Az alkalmazott képszerkesztési promptok

- `pizza-335`: Edit target: the attached real Gyros City Greek pizza photograph. Precise product-photo edit for the existing restaurant TV menu. Remove ONLY the plate, table and background, yielding a clean transparent cutout of the entire pizza, with a small even transparent margin on every side. Keep this EXACT pizza, its real toppings (feta cubes, red onion, olives, cherry tomatoes), crust, camera angle, texture, photographic lighting and colors unchanged. Recover the complete outer crust silhouette from the photograph; no flat clipped edges. No text, no ingredients overlay, no prices, no labels, no flames, no new toppings or props. This is faithful background removal, not a new pizza.
- `pizza-334`: Edit target: the attached real Gyros City Mexican pizza photograph. Precise product-photo edit for the existing restaurant TV menu. Remove ONLY the wooden board, table and background, yielding a clean transparent cutout of the entire pizza, with a small even transparent margin on every side. Preserve this EXACT pizza and all its toppings, green pepper rings, crust, camera angle, texture, photographic lighting and colors. The entire round crust must be visible with no clipped or flattened perimeter. No text, prices, labels or ingredients overlay. Do not invent different toppings, no new product, no flame background.
- `pizza-329`: Edit target: the attached existing Gyros City ham, mushroom and corn pizza cutout from a video. Clean up ONLY the stray white ingredient/price overlay letters at the lower right and the rough cutout boundary. Restore the small obscured pieces of crust naturally from adjacent crust. Remove the background entirely, yielding a transparent cutout of this EXACT whole pizza with a small even transparent margin. Preserve the actual ham, mushrooms, corn, basil garnish, photographic texture, colors and oblique camera angle. Keep all of the round crust inside the image, with no flat clipped edges. No text, no numbers, no labels, no different toppings, no extra food or props.
- `menu-123`: Edit target: the attached Gyros City doner menu food photo. Change only these defects: completely remove the large white overlaid '2950 Ft' price at the top left, replacing it with seamless continuation of the same wooden tabletop. Reframe slightly wider so the entire pita/doner at the left and all French fries at the top are visible with a 5 percent clean margin inside the photo. Preserve the exact food, filling, fries carton, Pepsi can and its real printed branding, camera angle, warm wood background and lighting. Do not add any overlaid text, price or labels. Do not add props or change the meal.
- `menu-120`: Edit target: the attached Gyros City chicken durum photo on a warm wooden table. Remove only the stray partial neighboring fries/food fragment intruding from the right edge, replacing that fragment with uninterrupted matching wooden table. Keep the entire original durum and all filling exactly the same, same camera angle and lighting. Reframe slightly wider with a small even margin so no part of the durum is clipped. No text, prices, labels or additional props; only the single durum on the same wood surface.
- `menu-119`: Edit target: the attached Gyros City chicken doner in pita photo. Reframe slightly wider so the whole pita and all its fries/filling, including the far-left pieces, sit fully inside the image with a 5 percent clean margin. Continue the same warm wooden tabletop in the added border. Preserve the exact existing meal, filling, camera angle, colors, lighting and photographic texture. No text, no prices, no labels, no new props or extra food.


## Kivágásjavítás – 2026-11-16

A három klón fő hibája a rossz kivágás volt: a burger- és pizzaképek videókockákból poligonnal kivágott, lecsípett szélű változatok maradtak, az étlapon pedig négyszögletes asztali fotók, a videóban viszont tiszta, kivágott ételek vannak.

- `scripts/signage/cutout_artwork.py` a `cutout-sources.json` szerinti eredeti fotókból átlátszó hátterű képet készít (rembg/u2net, csak fejlesztői gépen: `pip install rembg[cpu] pillow numpy`). A modell színben/kontrasztban megerősített másolatot lát, hogy a kraft papír dobozok és tányérok megmaradjanak. Kézi poligon egészíti ki a sültkrumplis dobozokat (`menu-170`, `menu-136`), és a Fish Burger (65) csak magát a burgert tartja meg a vágódeszkából.
- 6 burger (`burger-60..65`), 40 étlapkép és 7 videóból származó pizza (`pizza-336/342/341/339/333/332/338`, a legnagyobb belefoglalt ellipszisre vágva) új, tartalomhashes fájlt kapott. A `reviewed-artwork.json` és a `manifest.json` ezekre mutat; a régi fájlok megmaradnak a korábbi kiadásoknak.
- A pizza ImageGen-nel javított három képe (`pizza-335/334/329`) változatlan.
- Kebab: a `wood` háttér eddig a videókocka alsó, sötét 20 pixeles csíkja volt (az oldal alsó fele fekete lett). Most a kocka bal szélének függőleges faasztal-csíkja (131,20–171,700), amely oldalra nyújtva vízszintes erezetet ad.
- Teszt: `tests/signage/test_cutout_sources.py` (forrás-ellenőrzőösszeg, megegyező étel, átlátszó sarok, nem sötét kebabháttér).

### Elérhetőség
A `SignageCatalog` megkülönbözteti a `missing` (törölt étel) és az `unavailable` (aznapra kikapcsolt) okot. A kiadás csak az előbbi és a hiányos feltétválasztás esetén áll le, mert a lejátszó az elérhetetlen kártyát úgyis élőben elrejti.
