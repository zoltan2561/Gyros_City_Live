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
