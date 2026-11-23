# Adminból indított szerveroldali MP4-export

Állapot: a felhasználó jóváhagyta; megvalósítva és tesztelve, éles telepítésre előkészítve.

## Cél

A gyroscity.eu adminjában a kiválasztott, mentett lejátszási lista meglévő MP4-gombja indítsa a videókészítést. A tulajdonos mellett a TV kijelzők modulra jogosult munkatárs is generálhasson és tölthessen le videót saját számítógépére, helyi fejlesztői eszközök nélkül.

## Vizsgált lehetőségek

- A jelenlegi, hosszú HTTP-kérésben végzett export egyszerűbb, de a renderelés és a kódolás végéig nyitott kapcsolatot igényel. Éles használatra nem ezt javaslom.
- A meglévő tárhelyen futó külön háttérfolyamat megtarthatja a jelenlegi renderelőt, és rövid admin-kérésekkel indítható, követhető és letölthető. Ez a javasolt irány, sikeres szerveroldali renderpróba esetén.
- Külön renderelőgép csak akkor szükséges, ha a jelenlegi tárhelyen a tényleges Chromium/FFmpeg-próba vagy az erőforrásmérés meghiúsul. Ilyen gép és hozzáférés nincs kijelölve.

## Ellenőrzött kiinduló állapot

- Az éles checkout tiszta, commitja `a4f6dd7`; a helyi exportot a `53f14ba` commit tartalmazza. A két állapot között más lejátszójavítások is vannak, ezért a telepítendő fájlokat külön kell kijelölni és ellenőrizni.
- Az export vezérlője és gombja jelenleg helyi környezethez és loopback címhez kötött.
- Az éles SSH-környezetben nem található Node.js, FFmpeg vagy Chromium a vizsgált helyeken.
- A tárhely Linux x86_64 / glibc 2.34; a vizsgált fő Chromium-könyvtárak megtalálhatók. Ez önmagában még nem bizonyít futtathatóságot.
- A PHP `proc_open` segítségével sikeresen indított külön próbafolyamatot: `render-process-ok`, kilépési kód 0.
- `setsid` és `nohup` elérhető; `crontab` parancs nem található az SSH-környezetben. A terv nem feltételez új cronbejegyzést.
- A teljes renderelőeszköz-lánc és a webes PHP-ból indított háttérfolyamat működése még nincs igazolva.

## Javasolt működés

1. Az MP4-gomb POST-kérése ellenőrzi a bejelentkezést, a meglévő TV-moduljogot és a CSRF-tokent. A mentett lista és az aktuális katalógus pillanatképe alapján készül export.
2. A szerver privát exportkönyvtárba rögzíti a feladatot, elindítja a korlátozott élettartamú CLI-renderelőt, és gyorsan visszaad egy feladatazonosítót.
3. Egyszerre egy renderelés futhat. Újabb indításkor az admin egyértelmű foglaltsági üzenetet kap; nem indul korlátlan számú folyamat vagy láthatatlan várólista.
4. Az admin állapotot kérdez le, és elkészüléskor letöltési lehetőséget mutat. Az oldal bezárása nem szakítja meg a már elindult munkát; az indító admin visszatérve megtalálja a saját feladatát.
5. A feladatállapot és a letöltés külön végpontja is hitelesítést, moduljogot és a feladat indítójának ellenőrzését igényli. Az MP4 nem kap nyilvános fájl-URL-t.
6. Az eredmény a meglévő Full HD, néma H.264 MP4-formátumot és a legalább tízpercnyi teljes műsorkört tartja meg. A videóba égetett árak később nem frissülnek automatikusan.
7. A folyamat időkorlátot, erőforráskorlátokat, zárolást és felismerhető hibaállapotot kap. A saját ideiglenes fájlok és lejárt eredmények megőrzési szabályát dokumentálni kell.

## Megvalósítás és éles ellenőrzés

- Először a Node.js, Chromium, a képrögzítő FFmpeg és a H.264-képes kódoló elkülönített, webrooton kívüli futtatási próbája szükséges, rendszercsomagok és biztonsági beállítások módosítása nélkül. A nem támogatott Linux-változat miatt valódi renderpróba kell.
- A telepítés nem igényel tervezett adatbázis-migrációt, `.env`- vagy hitelesítőadat-módosítást. Ha a próba mégis ilyen változtatást igényelne, azt külön fel kell tárni.
- Automatizált ellenőrzés: admin- és munkatársjogok, jogosulatlan elérés, más feladatához hozzáférés tiltása, párhuzamos indítás, folyamatindítási hiba, hibás és megszakadt render, valamint letöltés.
- Böngészős próba: tulajdonos és jogosult munkatárs is indítson exportot; állapotkövetés, visszatérés és letöltés ellenőrzése. Az elkészült MP4 időtartamát és kodekjét gépi vizsgálat, tartalmát képkocka-ellenőrzés igazolja.
- Éles kódfelülírás előtt az érintett fájlok és a szükséges adatállapot mentése, utána visszaolvasás és működési próba szükséges. Commit, push és más TV-listák publikálása nem része ennek az engedélynek.

## Nyitott technikai feltétel

A jelenlegi tárhelyen futó megoldás csak a tényleges renderpróba sikerével válik igazolttá. Sikertelen próba esetén külön renderelőgép kiválasztása szükséges; azt a terv nem feltételezi automatikusan.
