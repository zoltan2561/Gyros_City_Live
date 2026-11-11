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

## Egyszerű listakezelés és képhatárok – 2026-11-11

- Teljes PHP: `php -d auto_prepend_file=tests/offline-prepend.php vendor/phpunit/phpunit/phpunit` → **340 teszt, 1465 állítás, sikeres**, PHP 8.2.12 alatt. Új esetek: kiválasztott TV-k frissítése egy mentéssel, változatlan korábbi kiadás, hibás meglévő/új lista teljes visszagörgetése, elavult szerkesztés, vázlatmentés, alapértelmezett lista és TV-kijelölés visszaállítása validáció után.
- JavaScript: `node --test tests/frontend/*.test.cjs` → **89 teszt, sikeres**. A két teljes lejátszási kört és a közben végzett 30 másodperces frissítést ellenőrző új teszt először kimutatta a lista elejére visszaugrást, majd a javítással sikeres lett. A folyamatos mód a korábbi időzítési határidő után is a kijelölt kiadást választja.
- Chrome: az összes referenciaoldal és mind a hét elérhető pizza; **507 kép-/szövegminta felbontásonként**, 1920×1080 és 1280×720 méretben, nincs kilógó fotó vagy név. A folyamatos animáció eleje/közepe/vége és a csökkentett mozgás is ellenőrizve. A referencia-nyitókép pizzája és desszertje a képernyőn belül marad.
- Playwright: tényleges Blade/CSS és admin.js alapján készült, csak olvasó adatbázis-tranzakcióból renderelt admin; 1440 px és 390 px szélesség. Hozzáadás, Fel/Le sorrend, eltávolítás, kötelező név, TV-kijelölés, gombfelirat és mentési kérés sikeres; nincs JS-oldalhiba vagy vízszintes kilógás a listakezelőben. A böngészős küldés elfogott kérés, a backend írásai külön, memóriabeli SQLite-on tesztelve. Valódi TV-hozzárendelés nem változott.
- PHP-lint, a három módosított JS-fájl szintaxisellenőrzése, Blade-fordítási teszt és `git diff --check` sikeres. Független, csak olvasó kódreview nem talált érdemi hibát.
- QA-képek és tesztsegédek: az ignorált `.playwright-mcp/signage-simple-admin-*` fájlok. A tényleges TV-panel overscan-beállítása, automatikus indulása és hosszú helyszíni futása továbbra is külön ellenőrzendő. Commit, push és élesítés nem történt.

## Második debug- és vizuális kör – 2026-11-11

1. **Lejátszás – javítva.** A korábbi, letöltött kiadás elengedése megváltoztatta a csomag revízióját, és az aktuális listát egyszer újraindította. A player most a folyamatos műsor változatlan, immutábilis kiadásazonosítóját is figyeli. Az új teszt először a,b,a sorrenddel hibázott, majd a javítás után a,b,c lett; ugyanaz a teszt igazolja, hogy egy tényleges új kiadás csak a következő jelenethatáron indul.
2. **Admin – javítva és ellenőrizve.** A javított névmezőn megmaradó aria-invalid és kliensoldali hibaüzenet megszűnik. Mobilon a kilenc hosszú bejegyzés helyett natív listaválasztó látszik; az asztali nézet megmarad. Friss, csak olvasó Blade-renderből végzett Playwright-próba: hozzáadás, Fel/Le, húzás, Enter billentyűvel sorrendmódosítás, teljes kiürítés és visszatöltés, határgombok, hibás név javítása, TV-kijelölés és elfogott mentési kérés sikeres. Nincs oldalhiba vagy vízszintes kilógás. Képek: az ignorált .playwright-mcp/signage-simple-admin-desktop.png és signage-simple-admin-mobile.png.
3. **TV-kép – igazítva és ellenőrizve.** A burgeres lap hosszabb Plusz/Maxi nevei az árat külön sorba tolták, ezért a fotók magassága eltért. 3.2vw feliratmérettel minden név és ár azonos sorban marad. Az összes referenciaoldal és mind a hét pizza újraellenőrizve 1920×1080 és 1280×720 méretben, normál és csökkentett mozgással: összesen **2028 kép-/szövegminta**, kilógás és oldalhiba nélkül. Friss, megnyitott és ellenőrzött képek: az ignorált .playwright-mcp/signage-debug-burger.png, signage-debug-menu.png, signage-debug-intro.png és signage-debug-pizza.png.

Ellenőrző parancsok: a teljes PHP-csomag **340 teszt / 1465 állítás**, a teljes frontend Node-csomag **90 sikeres teszt**. A módosított JS-fájlok szintaxisa, a Blade-fordítás és a git diff --check sikeres. A második, független kódreview sem talált érdemi hibát. A böngészős teszt a tényleges nézetet és kódot használja, de a mentést elfogja; valódi TV-hozzárendelés nem módosult. Ez továbbra is helyi ellenőrzés, fizikai TV-s mérés és élesítés nélkül.

## Videós feliratmaradványok és eleve csonka forrásképek – 2026-11-11

A felhasználó képernyőképén a Döner menü fotójában látható 2950 Ft nem a dinamikus felirat volt, hanem a kivágott videóképen maradt. Három pizzaképen összetevő-felirat is bennmaradt. A korábbi viewport-geometria ellenőrzése ezeket a képpontokba égett hibákat nem mutatta ki.

- Teljes forráskép-audit után 35 étlapfotót a már meglévő, teljes eredeti termékfotó saját WebP-változata váltott fel; a katalógus eredeti képei és termékadatai nem módosultak.
- Hat kép beépített ImageGen-javítást kapott: pizza-335, pizza-334, pizza-329, menu-119, menu-120, menu-123. Az ár-/összetevőfeliratok és szomszédos képrészletek kikerültek; a hiányos keret bővült. A pizzáknál az átlátszó háttér is ellenőrzött. Részletes promptok: docs/signage/reference-rebuild.md.
- A reviewed-artwork.json 41 új, tartalomhashes fájlt rögzít. A videós újraépítés végén ellenőrzött felülbírálás érvényesül, ezért a nyers, hibás crop nem írja vissza a tisztított képet. Az előző registry mind a 63 fájlja változatlan ellenőrzőösszeggel megmaradt; az aktuális registry összes fájlja is ellenőrzött.
- A tényleges Görögös/Mexikói pizzadiák és a kebaboldal megnyitva: a fotókban nincs videós ár-/összetevőmaradvány; a külön DOM-feliratok továbbra is a katalógusárat mutatják. A teljes hatoldalas étlap aktuális képei is ellenőrizve.
- Böngészős mátrix: 1920×1080 és 1280×720, normál/csökkentett mozgással, 2028 kép-/szövegminta, kilógás és JS-oldalhiba nélkül. Képek az ignorált .playwright-mcp/signage-clean-menu-page-0.png ... signage-clean-menu-page-5.png, signage-clean-pizza-335.png, signage-clean-pizza-334.png fájlokban.
- Teljes PHP: 341 teszt / 1671 állítás; teljes frontend Node: 90 teszt; új Python kép-registry tesztek: 3 sikeres. A Python-teszt a segédmodul létrehozása előtt hiányzó modul miatt hibázott, utána minden eset sikeres lett. A runtime PHP-integráció mind a 41 jóváhagyott fotó pinelését, MIME-típusát és csomagellenőrzőösszegét ellenőrzi.

Ez helyi forráskép-javítás. Katalógusár, elérhetőség és valódi TV-hozzárendelés nem módosult. A demo újratöltéskor az új képeket használja; korábban publikált, immutábilis kiadás csak a listából indított kifejezett TV-frissítéssel változik. Commit, push és élesítés nem történt.

## Négy műsor és választható hátterek – 2026-11-11

- A helyi könyvtár tranzakciós takarítása után négy lista / tíz jelenet maradt: burger-, étlap-, pizzaklón és általános reklám. Öt régi lista és kilenc felesleges jelenet kikerült. A két érintett tábla mentése Giten és webrooton kívül készült; a visszaolvasott SHA-256 egyezett. A kiadások, TV-hozzárendelések, médiák és időzítések ellenőrzőösszege változatlan.
- Az általános műsor hat terméket mutat hétmásodperces léptetéssel. Hat háttér menthető: grafit, parázs, faasztal, bordó, világos, egyedi egyszínű. Külön színállítás és közös CSS-ből készült háttérminta is van. A klónok nem kapják meg a presetet; a korábbi, preset nélküli jelenetek megjelenése megmarad.
- Playwright: hat háttér váltása megtartja az aktív kártyát és fotót; az URL újratöltése visszaállítja a választást. A korábbi `program=all` link az általános reklámra vezet. Az egyszínű háttér ténylegesen `background-image:none`. Nincs JS-oldalhiba.
- Tényleges Blade/admin.js csak olvasó renderből: hat-hat preset próba 1440 és 390 px szélességen; a beküldendő mező, automatikus színek, egyedi szín és klónnál letiltott választó megfelelő, vízszintes kilógás nélkül. Az egyszerű listakezelő hozzáadási, sorrendezési, törlési és elfogott mentési próbája is sikeres a kitakarított könyvtárral. A böngészős próbák nem publikáltak TV-re.
- Négy műsor összes oldala és terméke 1920×1080 / 1280×720, normál / csökkentett mozgás mellett: 2244 kép-/szövegminta, kilógás és levágott név nélkül. A hat háttér és az admin két nézetének képei megnyitva, vizuálisan ellenőrizve; segédek az ignorált `.playwright-mcp/signage-background-*` fájlok.
- Teljes PHP: `php -d auto_prepend_file=tests/offline-prepend.php vendor/phpunit/phpunit/phpunit` – 345 teszt / 1706 állítás sikeres. Az új regressziótesztek először a hiányzó műsor és háttérkezelés miatt hibáztak, majd javítás után zöldek. Frontend: teljes csomag 91 sikeres teszt, majd az új offline-telepítési teszttel a négy service-worker teszt is sikeres (összesen 92 külön frontendteszt). PHP-lint, JS-szintaxis és `git diff --check` sikeres. A független review által jelzett egyszínűháttér-hiba javítva és böngészőben ellenőrizve.

Helyi átadás; fizikai TV-s próba, commit, push és élesítés nem történt. Az adminvázlatok külön előnézetet használnak; a bejelentkezés nélküli demo a mintadefiníciókat mutatja.

## Hét lista, három saját modern változat – 2026-11-11

- Három új szerkeszthető vázlat: Burger · saját modern (10, egy jelenet), Étlap · saját modern (11, hat jelenet), Pizza · saját modern (12, egy jelenet). Összesen hét lista / tizennyolc jelenet. Előzetes, Giten/webrooton kívüli mentés visszaellenőrizve; a négy meglévő lista, korábbi jeleneteik, kiadások és kijelzők megmaradtak. A létrehozás megismétlése nem hozott új sort.
- A burger sáfránysárga plakátelrendezést és teljes katalógusfotókat, az étlap két oszlopba rendezett vízszintes sorokat, a pizza mélyzöld hátteret és világos információs mezőt kapott. Az étlap „Sajttal” jelölése a tényleges 129-es terméknevet követi. A modern műsorok saját CSS-t és a csomaghoz kötött helyi fontot használnak; nem tesznek új akcióállítást.
- A hét mentett lista valódi előnézeti/kiadási csomagja csak olvasó tranzakcióban hibamentesen összeáll. Az első próba jelezte a három nem rendelhető pizzát az új vázlatban; az új minták az elérhetőség szerint szűrt definícióból készülnek, az új pizzás vázlat hét terméket tartalmaz (70 mp). Katalógusállapot nem változott, TV-re publikálás nem történt.
- Teljes PHP: 348 teszt / 1803 állítás; teljes Node: 93 sikeres teszt. Az új tesztek a hiányzó modern programok/stílusok/offline CSS miatt először hibáztak, majd zöldek lettek. PHP-lint, JS-szintaxis és `git diff --check` rendben.
- Playwright: az összes hét műsor oldalai/termékei 1920×1080 és 1280×720, normál és csökkentett mozgással: 4248 kép-/szövegminta, kilógás és levágott terméknév nélkül. A független review által talált háromkártyás oszlopszám-öröklési hiba javítva; 1–8 látható kártyával mindkét felbontásban 16 további próba sikeres. Árfrissítés megtartja a kártyát és fotót, mind a hét demo választható, az általános háttérválasztó csak az általános műsornál látszik.
- Admin: tényleges Blade/admin.js csak olvasó renderből, 1440 és 390 px szélességen a három modern megjelenés mentésre kerülő értékei megfelelőek; automatikus elrendezés, színek és 8/10 mp-es léptetés működik. Hat eset, oldalhiba és vízszintes kilógás nélkül. Képek megnyitva és ellenőrizve az ignorált `.playwright-mcp/signage-modern-*` fájlokból.

Helyi fejlesztés; fizikai TV-s próba, commit, push és élesítés nélkül.
