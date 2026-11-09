# Helyi elfogadási eredmények

## A három rendelkezésre álló videó kioszkos változata

2026-11-09, újabb kör: mindhárom helyi forrásvideó metaadata, idővonala és mintaképkockái ellenőrizve. Eredeti ételfotók/hátterek alapján újraépített 20 mp-es burgerlap, hatoldalas 96 mp-es étlap és nyitóképes pizzaműsor. A videók teljes editoranimációjának képkockánkénti azonossága nem állítható; a mozgás webes újraépítés.

- Teljes PHP: **324 teszt / 1401 állítás**, teljes JavaScript: **87 teszt**, minden teszt sikeres. Parancsok: `php -d auto_prepend_file=tests/offline-prepend.php vendor/phpunit/phpunit/phpunit`, `node --test tests/frontend/*.test.cjs`.
- Blade cache sikeres. A prémium UI statikus auditja szigorú módban 0 hibát/jelzést adott. A Python-assetgenerátor szintaxisa és a valódi három forrásfájllal végrehajtott generálás sikeres.
- Playwright: 1920×1080 és 1280×720, minden referenciaoldal; a hét aktív pizza és mind a hat étlapoldal körbefutása; valós idejű fotómozgás; szünet utáni maradék idő; árlejárat. Nincs levágott név vagy JavaScript-oldalhiba.
- Ötmásodperces pizzadián az ár láthatóvá vált a következő termék előtt. Árfrissítéskor ugyanaz a fotó és ugyanazok a cím-betűelemek maradtak; a font betöltve. A szintetikus ár csak az izolált böngészős tesztben változott, üzleti árat nem írt.
- A gyrosoldal bejelentkezett adminmentése után a közös kártya két ársora és a referenciastílus megmaradt. A három helyi adminvázlat (listák 7/8/9) csomagépítése sikeres; nem történt publikálás vagy kijelző-hozzárendelés.
- Valódi service worker, hálózat nélküli 13 referenciafájl-letöltés: minden kép/font/háttér checksum egyezett a kijelölt csomaggal, korábbi ütköző cache mellett is. Külön regresszió védi a verziók fizikai változatlanságát és a régi/új jelenetekhez tartozó külön assetválasztást.
- Független review: az eltérő assetverziók és rövid pizzadiák árfelfedési hibái javítva, végső readbackben nem maradt fontos találat.
- Eltérések az üzleti állapot miatt: a Carbonara, Magyaros és Songoku le van tiltva, ezért az aktív körben hét pizza van. Az eredeti nyitókép akciószövege másolva, rendelési ajándékakció nem aktiválva. Fizikai TV-s/24 órás pilot és élesítés továbbra sem történt.

Képernyőképek és QA-segédek az ignorált `.playwright-mcp/reference-copy-*`, `signage-reference-*.cjs` fájlokban. A következő szakaszok a korábbi fejlesztési körök történeti eredményei.

2026-11-09 · GCTV-V1-20261109 · Windows / XAMPP PHP 8.2.12 / Laravel 9.52.16 / MariaDB 10.4.32 / helyi Chromium.

## Bizonyított helyi működés

| Terület | Eredmény |
|---|---|
| Valódi katalógus | A 60–65 azonosítójú hat burger aktuális ára és eredeti fotója megjelenik |
| Árparitás | Közös baseQuote; fix/százalékos Top Deal, fix kedvezmény határesete, kötelező választás, időalapú verzió és lejárat tesztelve |
| Adminfolyamat | Jelenet → lista → előnézet → cél-TV → publikálás → párosítás végigfutott |
| Felületek | Öt adminfül, négy sablon, két téma; keskeny admin 390 px-en ellenőrizve |
| Elrendezések | 5/6/7/8 kártya 1920×1080 és 1280×720 méretben; nincs oldalszintű túllógás; hosszú névhez figyelmeztetés |
| Videó + ár | Tényleges helyi DB-ármódosítás 23 965 ms alatt látható lett; a videó DOM-eleme megmaradt |
| Árpróba visszaállítása | A 60-as termék 2260 Ft-os eredeti ára visszaállítva és DB-ből visszaolvasva; a módosítás csak helyi volt |
| Offline újraindítás | Előtöltés után hálózat nélkül újratöltött a shell és az ármentes tartalék; visszakapcsolódáskor automatikus helyreállás |
| Média | Valódi, mesterséges teszt-MP4 és MOV FFmpeg-feldolgozása; privát tárolás, poster, méret, felbontás és checksum ellenőrizve |
| Worker | Külön signage_jobs soron egy tényleges, hatmásodperces próbavideó feldolgozása sikeres; a helyi worker rejtett folyamatként elindítva |
| Hozzáférés | Külön moduljog, egyszeri nonce-os tokenátadás, visszavonás, A/B TV médiaizoláció és Range/206 tesztelve |
| Kiadások | Vázlat nem írja át a snapshotot; első heartbeat előtti új publikálás megőrzi a futó kiadás kötéseit |
| Időzítés | Prioritás, azonos prioritású átfedés, éjfél, az utolsó nap hajnalba átnyúlása és őszi óraátállás tesztelve |
| Hibák | Megszakított letöltés, valódi cache.put-kvótahiba, hiányzó cache, GC-hiba, visszavont token és normál videóloop regressziói sikeresek |

A sikeres teljes böngészős menetben nem jelentkezett JavaScript-hiba. A két eredeti referencia nincs a helyi fájlok között: a mesterséges tesztvideó nem bizonyítja ezek lejátszhatóságát vagy a pontos referencia-egyezést.

## Ellenőrzési parancsok

A V1-alap átadásakor: **311 PHP-teszt / 1357 állítás**, **71 JavaScript-teszt**, minden teszt sikeres. A PHP-lint 53 változott/új PHP-fájlt ellenőrzött, hiba nélkül. A Blade cache, JS-szintaxis és diffellenőrzés is sikeres.

```powershell
php -d auto_prepend_file=tests/offline-prepend.php vendor/phpunit/phpunit/phpunit
node --test tests/frontend/*.test.cjs
php -d auto_prepend_file=tests/offline-prepend.php artisan view:cache
git diff --check
```

A változott PHP-fájlok külön `php -l`, a signage JS-fájlok `node --check` ellenőrzést kaptak. A Blade-tesztek a fordított PHP szintaxisát is vizsgálják. A prémium UI statikus auditja 0 hibát/jelzést adott; a runtime bizonyítékot a valódi böngészős menet adja.

Helyi, Gitből kizárt bizonyítékok a `.playwright-mcp` mappában: `signage-admin-scenes.png`, `signage-admin-390.png`, `signage-preview-{dark,light,featured,split,media}-1080.png`, `signage-split-live-price.png`, `signage-offline-restart.png`. A tesztsegédek nem részei a kiadásnak, a helyi adminsession nem kerül Gitbe vagy kimenetbe.

## Review és javítások

### Éttermi TV megjelenés finomítása

A következő helyi körben parázsfény/szikrák, magenta háttérmozgás, lépcsőzetes beúszás, kontrasztos árcímkék és előnézeti újrajátszás készült. A kártyák stabil DOM-elemet kaptak; az ár-, név- és elérhetőség-frissítés nem indítja újra a meglévő kártyák animációját. A fotó átmeneti hibája után a következő frissítés újrapróbálja a betöltést.

Ellenőrzés: **74 teljes frontendteszt**, **4 célzott PHP-/Blade-teszt / 17 állítás**, JS-szintaxis és diffellenőrzés sikeres. Valódi böngészőben a két téma, 5/6/7/8 kártya 1080p/720p méretben, a beúszási sorrend, a folyamatos háttérmozgás, azonos kártya/fotó/videó/animáció árfrissítés után és a csökkentett mozgás kikapcsolása igazolt. JavaScript-hiba és normál terméknevekre téves túlcsordulás-jelzés nem jelentkezett. Képek: `.playwright-mcp/signage-polished-{dark,light,split}.png`. Ez a kör nem érintett adatbázissémát vagy éles környezetet.

Független kódreview után regresszió védi a shell cache megőrzését, azonos revision melletti cache-helyreállítást, a commit utáni takarítás hibáját, a kezdeti kiadás-visszaigazolást, a lejárt beégetett média elkülönített érvénytelenítését és a visszavonás utáni aszinkron callbacket. A tényleges MariaDB-próba külön feltárta és javíttatta a párosítási lejárat automatikus átírását. A videóloop miatti téves watchdog-riasztást valódi videó + ár mérés és futási teszt alapján javítottuk.

## Nyitott helyszíni elfogadás

- Tényleges TV-k, lejátszók, hálózat, nézési távolság és automatikus indulás.
- Helyben fizetendő árprofil és a külön csomagolási díj üzleti kezelése.
- A két eredeti MP4/MOV és az opcionális harmadik referencia.
- Hosztoldali FFmpeg, workerfelügyelet, feltöltési méret és tartós tároló.
- Legalább 24 órás valós lejátszás, áramkimaradás és a visszatérés idejének mérése.

Merge, deploy és helyszíni pilot nem történt. Ez helyben tesztelhető fejlesztési változat.
# Folyamatos reklám kiegészítés – 2026-11-09

- Teljes PHP: `php -d auto_prepend_file=tests/offline-prepend.php vendor/phpunit/phpunit/phpunit` → **317 teszt, 1374 állítás, sikeres**.
- Teljes JavaScript: `node --test tests/frontend/*.test.cjs` → **82 teszt, sikeres**. Új regresszió: diaváltás/körbefutás, árfrissítés közbeni DOM- és időzítőmegőrzés, eltűnő termék, időzítő-megszüntetés, részben eltelt dia szüneteltetése, automatikus adminelőnézet és lejárat szünet közben.
- `artisan view:cache` és `git diff --check` sikeres. Az új PHP-validáció és demoprogram-szűrés a teljes tesztfutás része.
- Valódi helyi HTTP-katalógus: burger 2 jelenet, világos étlap 6 oldal, pizza 1 jelenet / 14 termék. A teljes műsor 9 jelenet. Sem a katalógusárak, sem a termékképek nem módosultak.
- Playwright: 1920×1080 és 1280×720 minden demojelenet, nincs kilógó termékkártya vagy levágott név. Mind a 14 különböző pizza sorra került, majd a sor elölről indult. Automatikus burger- és étlapváltás, szünet/folytatás, csökkentett mozgás és szintetikus rövid árérvényesség miatti tartalék igazolt.
- Bejelentkezett adminelőnézet automatikus lapozása, 2/3 kártyás osztott nézet egyoszlopos geometriája és szünet közbeni árlejárata böngészőben sikeres. Nincs JavaScript-oldalhiba.
- Független review után mindhárom talált regresszió javítva és újraellenőrizve; fennmaradó fontos hibát a reviewer nem jelzett.
- Képernyőképek és helyi QA-script az ignorált `.playwright-mcp/signage-advert-*` fájlokban. Ezek nem helyettesítik az eredeti három videóval végzett vizuális összevetést vagy a valódi TV-s pilotot. Forrásvideók hiányában pontos másolat nem igazolt; élesítés és új üzletiadat-írás nem történt.
