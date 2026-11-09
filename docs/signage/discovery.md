# GCTV V1 – helyi felmérés

2026-11-09 · Terv: GCTV-V1-20261109 · Állapot: a helyi T0 felmérés elkészült; helyszíni/hosztadatok nyitottak. Megvalósítási eredmények: [acceptance.md](acceptance.md).

Forrás: [eredeti Slack-terv](https://szoftlabworkspace.slack.com/archives/C0C8Q5J0FCH/p1794229779451649), a szál `F0CG5GH4D4Z` melléklete. A változatlan terv helyi másolata: [implementation-plan.md](implementation-plan.md). A terv javaslatokat és tervezett próbákat tartalmaz, nem elkészült funkciókat.

## Munkakörnyezet

- Projekt: `C:\xampp\htdocs\Gyros_City_Live`.
- Kiinduló ág: `main`; commit: `f1645a895691c84cc7ae21386298ae991ee11e61`. Ez egyezik a terv forrásverziójával. Induláskor tiszta munkafa.
- Fejlesztési ág: `feature/gctv-v1-local`; a meglévő helyi checkout marad a tesztelés helye.
- A felhasználó a beszélgetésben megadta az AGENTS.md szabályokat; a projektben és a vizsgált közvetlen szülőkben nem találtam további AGENTS.md fájlt.
- PHP CLI: 8.2.12; Laravel telepített forrás: 9.52.16; PHPUnit: 9.6.20; Node: 24.14.1; Laravel Mix 6 a package.json szerint.
- Apache konfigurált DocumentRoot: `C:/xampp/htdocs`; tervezett helyi URL: `http://localhost/Gyros_City_Live/`.
- Az első vizsgálatkor Apache/MySQL kapcsolat-elutasítás történt. Ezután rejtett folyamatokkal elindultak; a tényleges localhost oldal és a 172 terméket tartalmazó helyi DB ellenőrizve. Az izolált kezdeti böngészőprofilban nem volt service worker.
- A betöltött alkalmazáskörnyezet `local`; adatbázisdriver `mysql`, queue `sync`. Környezeti fájl nem módosult.
- FFmpeg és ffprobe elérhető a helyi PATH-on; FFmpeg 9.0.2. Éles hoszt, workerfelügyelet és tárhely nem vizsgált.
- CLI php.ini: `C:\xampp\php\php.ini`; upload_max_filesize és post_max_size 40M, max_execution_time 0. Ez nem igazolja az Apache PHP-limitjeit; a terv 500 MiB videólimitjéhez külön konfigurációs döntés kell.

## Ár- és katalógusút

1. `app/Models/Item.php`: `item` tábla; `visibleOnMenu()` kizárja az inaktív/törölt kategóriákat. `is_orderable` az item_status=1 és today_unavailable=false feltételeket vizsgálja.
2. `app/Services/CatalogPricing.php::selection()`: a termék alapára és Top Deal után az érvényes feltétek és extrák összege. Kötelező választás esetén az üres konfiguráció hibás. A fix kedvezményt az alapárnál kisebb ár esetén nem vonja le; ezt a meglévő viselkedést meg kell őrizni.
3. `app/Helpers/helper.php::top_deals()`: időfüggő, settings-időzónával és systemaddons-engedéllyel működik. Napi és dátumintervallumos ágai eltérő végpont-inkluzivitást használnak. `updated_at` figyelése nem elegendő.
4. `app/Services/CheckoutPricing.php::quote()`: adót és kiszállítási díjat is hozzáadhat, kupont levonhat. A termékkártya ára önmagában nem bizonyított helyben fizetendő végösszeg.
5. `app/Helpers/helper.php::currency_format()`: a settings alapján formáz; az API ezt a feliratot adja tovább, a kliens nem számol árat.
6. A Variation modell létezik; a vizsgált CatalogPricing nem ebből áraz. Variánsár támogatását a vásárlási úttal külön igazolni kell.
7. Külön menütermék saját item_id-t kapjon. Többáras kártya több konkrét termék-/választáskötésből épüljön, kézzel másolt ár nélkül.

A hat burger azonosítója 60–65; aktuális ára és eredeti fotója ellenőrizve. A 13-as adóhivatkozás 100 Ft-os hamburgerdoboz-csomagolási díj. A feltétek és a pontos választások a meglévő CatalogPricing útján ellenőrzöttek. Helyszíni árprofil továbbra sincs igazolva.

## Képek és referenciák

- `storage/app/public/admin-assets/images/item/` létezik, 194 könyvtárbejegyzéssel. A DB-hivatkozások egyezése még nem ellenőrzött.
- Az Item képkapcsolatai az örökölt ASSETSPATHURL alapján képeznek URL-t. A gyökér-webrootot és a hagyományos public-webrootot külön kezelni kell a signage assetútvonalaknál.
- `public/city/hero-burger.webp` elérhető, de nem helyettesíti a hat valódi termékfotó kötését.
- A projekt `public`, `storage/app/public` és `.playwright-mcp` könyvtáraiban nem találtam MP4/MOV referenciát. A Slack-terv melléklete szöveg, nem tartalmazza az eredeti videókat. A két referencia-lejátszás és a harmadik videó nyitott tétel.

## Integrációs térkép és megvalósítási irány

A terv három lehetőséget mérlegel: meglévő admin + player, külön signage alkalmazás, illetve árváltozásonként újrarenderelt videó. A meglévő admin és külön böngészős player illeszkedik az egyetlen katalógushoz és a helyi teszteléshez; ez a javasolt irány. A külön alkalmazás új adminisztrációt és szinkronizálást hozna; a videórenderelés akadályozná a gyors árfrissítést.

- `app/Support/AdminPermissions.php`: registry, kiosztható modulazonosítók, actiontérkép; új explicit TV-jog szükséges. A meglévő utolsó registry-azonosító 34, új azonosító kiosztása előtt ismét ellenőrizendő.
- `resources/views/admin/theme/sidebarcontent.blade.php`: jogosultsághoz kötött TV kijelzők menüpont.
- `routes/web.php`: meglévő adminútvonalak AdminAuth csoportban. Előnézet ide tartozzon; a publikus player nem használhat adminsessiont.
- `app/Providers/RouteServiceProvider.php`: web és API külön betöltése; a signage API külön eszközguarddal, általános CSRF-kikapcsolás nélkül regisztrálandó.
- `routes/api.php` jelenleg üres. A dedikált signage routefájl megőrzi a modulhatárt.
- `config/queue.php`: jelenleg sync alapbeállítás; a konverziós job nem kerülhet a hosszú HTTP-kérést blokkoló sync sorra. Dedikált worker vagy igazolt alternatíva szükséges.
- A kezdeti tracked forrásban és az izolált böngészőprofilban nem volt service worker. Az elkészült új SW scope-ja kizárólag a projekt `/signage/` útvonala; admin- és rendelési oldal nem kerül bele.
- `.htaccess`: a projektgyökér a webroot; privát kódkönyvtárak és veszélyes tárolt fájltípusok tiltva. Új médiatár nem kerülhet védelem nélküli publikus könyvtárba.

Első termékkód-mérföldkő: T1 közös baseQuote + SignageCatalog; célzott árparitás és időhatártesztek; helyi, hat valódi terméket használó előnézet. Utána a terv szerinti T2–T6. T7 helyszíni pilot és éles telepítés nem része a jelenlegi, helyi munkavégzésnek.

## Indítás és ellenőrzések

Az XAMPP Control Panelben Apache és MySQL indítandó a meglévő helyi konfigurációval; utána a fenti localhost URL ellenőrizhető. Nincs új `.env`, import vagy migrációs lépés ebben a felmérésben.

```powershell
php -d auto_prepend_file=tests/offline-prepend.php vendor/phpunit/phpunit/phpunit --filter 'CatalogPricingTest|CouponDiscountTest|BarionDeliveryMinimumTest|PersistentLoginTest|GuestCartLoginTest'
node --test tests/frontend/*.test.cjs
php -d auto_prepend_file=tests/offline-prepend.php vendor/phpunit/phpunit/phpunit
git diff --check
```

Az offline-prepend a régi MyFatoorah autoload hálózati önfrissítését tiltja le tesztfutás közben.

Az alábbi táblázat az induló állapotot rögzíti. A szolgáltatások elindítása és a megvalósítás utáni végső, sikeres eredmények az elfogadási dokumentumban szerepelnek.

| Ellenőrzés | Megfigyelt eredmény |
|---|---|
| Célzott PHP-regresszió | 63 teszt, 375 állítás, sikeres |
| Meglévő frontend suite | 59 teszt, sikeres |
| Teljes PHP suite | 265 teszt, 1153 állítás, 1 hiba |
| Teljes suite hibája | Feature ExampleTest: `/` 500, a helyi MySQL nem fut; implementáció előtti környezeti hiba |
| Böngészős nyitás | ERR_CONNECTION_REFUSED, Apache nem fut |
| Valódi katalógus olvasása | MySQL kapcsolat elutasítva |

## Nyitott tételek

- A helyi szolgáltatások, termék- és képkötések ellenőrzése és a teljes alapteszt újrafuttatása megtörtént; a helyszíni árprofil maradt nyitott.
- TV-k száma, modellje, tájolása, felbontása, lejátszója, böngészője, hálózata és automatikus indulása.
- Eredeti két videó és opcionális harmadik videó hozzáférése.
- Telepített SW-scope-ok, tartós tárhely és kodektámogatás a célböngészőben.
- Hosztoldali FFmpeg, aszinkron worker, feltöltési limitek és szabad tárhely. A jelenlegi megbízás nem kér éles hosztműveletet.
- A felhasználó jóváhagyta a terv önálló követését. A helyi T1–T6 eredményei az elfogadási dokumentumban szerepelnek; T7 helyszíni pilot nincs késznek jelölve.
