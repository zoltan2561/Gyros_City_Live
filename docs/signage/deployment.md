# Telepítési határok és visszaállítás

Ez a változat helyi fejlesztési átadás. Éles telepítés, DNS-, hoszt-, titok- vagy környezeti fájlmódosítás nem történt. A feature branch feltöltése nem jelent telepítést.

## Helyi sémalépések

Kizárólag az alábbi új migrációk tartoznak a modulhoz; a teljes régi migrációs állományt nem kell újrafuttatni:

```powershell
php -d auto_prepend_file=tests/offline-prepend.php artisan migrate --path=database/migrations/2026_11_09_000001_create_signage_tables.php
php -d auto_prepend_file=tests/offline-prepend.php artisan migrate --path=database/migrations/2026_11_09_000002_add_signage_delivery_tracking.php
php -d auto_prepend_file=tests/offline-prepend.php artisan migrate --path=database/migrations/2026_11_09_000003_fix_signage_pairing_expiry.php
php -d auto_prepend_file=tests/offline-prepend.php artisan migrate --path=database/migrations/2026_11_09_000004_add_signage_price_ttl.php
```

A létrejövő táblák `signage_*` nevűek. A későbbi lépések csak ezeket egészítik ki; rendelési, vásárlói, fizetési és katalógustáblát nem módosítanak. A párosítás DATETIME mezője elkerüli a régi MariaDB első kötelező TIMESTAMP mezőjének automatikus ON UPDATE viselkedését. Ezt valódi MariaDB-próba igazolta.

Éles alkalmazás előtt külön jóváhagyás, adatbázismentés, karbantartási/kompatibilitási ellenőrzés és readback szükséges. A `down()` az új modul adatait is törölheti; letiltáshoz nem kell rollbacket futtatni. A dátummezőt javító migráció szándékosan nem állítja vissza az automatikusan változó TIMESTAMP típust.

## Pilot előfeltételei

- A modulkapcsoló alapból hamis. A helyi loopback kivétel nem éles engedély. A pilot bekapcsolása és a választott host beállítása külön feladat.
- FFmpeg/ffprobe, külön felügyelt signage worker és elegendő memória/tárhely szükséges. A worker timeoutja 1900 s, a queue retry_after 2000 s; hosszú konverziót nem a HTTP-kérés végez.
- Alkalmazáslimit: kép 20 MiB, videó 500 MiB / 10 perc. A mért helyi PHP-limit csak 40M. A hoszt/proxy/HTTP-PHP tényleges limitjét külön hozzá kell igazítani; nem történt php.ini-módosítás.
- A `storage/app/signage` tartós, privát média. Mentése és megőrzése a DB-vel együtt szükséges. Git deploy nem írhatja felül vagy törölheti.
- A gyökér-webroot `.htaccess` szabályai védik a kódot és a privát tárolót. A `/signage/` végződő perjele a service worker scope miatt megmarad. Más webroot vagy reverse proxy esetén ezt külön HTTP-próbával igazolni kell.
- A signage API csak eszköztokent fogad; az admin websession/CSRF védelem megmarad. A párosítás és a státusz külön kéréskorlátot kap, több TV nem ugyanazt az alacsony keretet fogyasztja.
- HTTPS vagy a böngésző által megbízhatónak tekintett localhost kell a service workerhez. A scope csak `/signage/`; rendelési és adminoldal nem kerül az offline csomagba.
- A token a player adott helyi profiljának IndexedDB-tárolójában marad az újraindításhoz. Nem kerül URL-be, naplóba vagy telemetriába. A kioskböngésző profilja fizikai hozzáférést igénylő eszköztárolóként kezelendő.

## Visszaállítás

A modul kikapcsolása megtartja a médiát és az új táblákat. A korábbi kiadás az adminból TV-nként visszaállítható; a visszaállított megjelenés is a jelenlegi katalógusárakat kapja. Publikált kiadás által használt média nem archiválható.

A tároló az előző teljes csomagot megtartja új letöltés közben. Kvóta-/letöltési hiba nem állít új aktív mutatót. A pointer commitja utáni cache-takarítás hibája sem törölheti az új aktív csomagot. Az offline shell külön namespace-ben marad.

Valódi host, TV, eredeti referenciavideók, 24 órás üzem és áram-visszatérési próba hiányában éles/pilot-kész állapot nem állítható.

## Éles bevezetés lépésről lépésre – 2026-11-17

A modul kapcsolója `config/signage.php` → `SIGNAGE_ENABLED` környezeti változó (alapérték: kikapcsolt). Ameddig nincs `true`, az admin oldal, a lejátszó és az API 404-et ad, az oldalsáv nem mutatja a menüpontot.

1. **Mentés:** adatbázis-mentés és a `storage/app/signage` mappa mentése.
2. **Kód:** a `feature/gctv-v1-local` ág tartalma a hosztra. Új csomag vagy buildlépés nincs.
3. **Séma:** csak a négy signage migráció (rendelési, vásárlói, fizetési és katalógustáblát nem érintenek):
   ```bash
   php artisan migrate --path=database/migrations/2026_11_09_000001_create_signage_tables.php
   php artisan migrate --path=database/migrations/2026_11_09_000002_add_signage_delivery_tracking.php
   php artisan migrate --path=database/migrations/2026_11_09_000003_fix_signage_pairing_expiry.php
   php artisan migrate --path=database/migrations/2026_11_09_000004_add_signage_price_ttl.php
   ```
4. **Bekapcsolás:** a hoszt `.env` fájljába `SIGNAGE_ENABLED=true`, majd `php artisan config:clear` (és `view:clear`; ha a hoszton `config:cache` fut, újra azt).
5. **Beépített műsorok:** `php artisan signage:install-programs --dry-run`, majd ugyanez kapcsoló nélkül. A parancs a hét műsort (három videómásolat, általános reklám, három saját modern) szerkeszthető listaként és jelenetként hozza létre az élő katalógus ételeivel. A nem létező vagy hiányos feltétű ételt kihagyja és kiírja; az aznapra kikapcsolt étel bent marad. Meglévő listát és jelenetet nem módosít, kijelzőre nem publikál; többször futtatható.
6. **Jog:** a tulajdonos azonnal látja a **TV kijelzők** menüpontot; munkatársnak az Admin szerepkörök között a 35-ös **TV kijelzők** modul kell.
7. **TV:** admin → Kijelzők: új kijelző, majd a TV böngészőjében `https://gyroscity.eu/signage/` megnyitása, a nyolcjegyű kód beírása. Utána Lejátszási listák → kijelölt TV → Mentés és TV-k frissítése.
8. **Próba:** a TV teljes letöltés után vált; az admin tetején a TV-állapotsáv zöld, a Kijelzők lapon „Naprakész, ezt játssza”.

**Visszaállítás:** `SIGNAGE_ENABLED=false` (és `config:clear`) azonnal elrejti a modult; a táblák és a média megmaradnak. A `down()` migrációt ne futtasd. Videó-/fotófeltöltéshez (Médiatár) FFmpeg/ffprobe és külön signage worker kell; a beépített műsorok ezek nélkül is működnek.

