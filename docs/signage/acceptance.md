# Helyi elfogadási eredmények

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

Végső teljes eredmény: **311 PHP-teszt / 1357 állítás**, **71 JavaScript-teszt**, minden teszt sikeres. A PHP-lint 53 változott/új PHP-fájlt ellenőrzött, hiba nélkül. A Blade cache, JS-szintaxis és diffellenőrzés is sikeres.

```powershell
php -d auto_prepend_file=tests/offline-prepend.php vendor/phpunit/phpunit/phpunit
node --test tests/frontend/*.test.cjs
php -d auto_prepend_file=tests/offline-prepend.php artisan view:cache
git diff --check
```

A változott PHP-fájlok külön `php -l`, a signage JS-fájlok `node --check` ellenőrzést kaptak. A Blade-tesztek a fordított PHP szintaxisát is vizsgálják. A prémium UI statikus auditja 0 hibát/jelzést adott; a runtime bizonyítékot a valódi böngészős menet adja.

Helyi, Gitből kizárt bizonyítékok a `.playwright-mcp` mappában: `signage-admin-scenes.png`, `signage-admin-390.png`, `signage-preview-{dark,light,featured,split,media}-1080.png`, `signage-split-live-price.png`, `signage-offline-restart.png`. A tesztsegédek nem részei a kiadásnak, a helyi adminsession nem kerül Gitbe vagy kimenetbe.

## Review és javítások

Független kódreview után regresszió védi a shell cache megőrzését, azonos revision melletti cache-helyreállítást, a commit utáni takarítás hibáját, a kezdeti kiadás-visszaigazolást, a lejárt beégetett média elkülönített érvénytelenítését és a visszavonás utáni aszinkron callbacket. A tényleges MariaDB-próba külön feltárta és javíttatta a párosítási lejárat automatikus átírását. A videóloop miatti téves watchdog-riasztást valódi videó + ár mérés és futási teszt alapján javítottuk.

## Nyitott helyszíni elfogadás

- Tényleges TV-k, lejátszók, hálózat, nézési távolság és automatikus indulás.
- Helyben fizetendő árprofil és a külön csomagolási díj üzleti kezelése.
- A két eredeti MP4/MOV és az opcionális harmadik referencia.
- Hosztoldali FFmpeg, workerfelügyelet, feltöltési méret és tartós tároló.
- Legalább 24 órás valós lejátszás, áramkimaradás és a visszatérés idejének mérése.

Merge, deploy és helyszíni pilot nem történt. Ez helyben tesztelhető fejlesztési változat.
