<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Pultos rendelésnézet – 2026. október 22.

A rendeléslista az elsődleges munkafelület: az összes/feldolgozás/befejezett/törölt kártyák alatta vannak. Rendelésjoggal rendelkező pultos belépéskor a rendeléslistára jut, a többi moduljoga nem változik. Az új `admin-orders-live.js` a meglévő öt másodperces figyelés friss függő rendelésazonosítóit használja: új érkezéskor ugyanazon jogosultságvédett listavégpont HTML-részletét tölti be, a keresés és az oldalszám megtartásával. A lista és a számlálók együtt frissülnek, az új sor rövid zöld kiemelést kap; csökkentett mozgás beállításnál nincs pulzálás. Ezen az oldalon az érkezési felugró ablak helyett a sor és a szöveges jelzés látszik, a hangjelzés megmarad. Aktív állapotválasztó vagy fizetési ablak alatt késlelteti a sorcserét; sikertelen frissítést a következő ellenőrzésnél újrapróbál. Szűrt vagy régebbi oldalon a „Legfrissebb rendelések” linkkel lehet visszatérni az új sorokhoz. A kiadáshoz a `public/city/admin-orders-live.js` fájl is szükséges; migráció nincs. Helyi QA-adatbázison igazolt az oldal újratöltése nélküli új sor és számlálófrissítés; 235 PHP-teszt / 1019 ellenőrzés és 57 JavaScript-teszt sikeres.

Az admin felső „Menü” gombja asztali nézetben elrejti/visszanyitja az oldalsávot, és a tartalom oldalsó margóját is felszabadítja. A választást a böngésző az admin alap-URL-jéhez kötött localStorage-kulcsban őrzi; tiltott tárhely esetén az aktuális oldalon működik a kapcsolás. Mobilon megmarad a külön nyitható panel. A kiadáshoz az `admin-sidebar.css` és `admin-sidebar.js` fájlok is szükségesek a `public/city` mappából. Ellenőrizve a frissítés és oldalváltás utáni állapot, Enterrel kapcsolás, illetve mobilos nyitás/bezárás; 50 JavaScript-teszt sikeres.

Az összetevő neve előtt legalább két kötőjel (`--`, `---` stb.) minden esetben elhagyást jelent, akár a mentett választásokból, akár az extrákból származik. A jelölés elsőbbséget élvez: a tétel a piros „Nem kéri” csoportba kerül, a kötőjelek nélkül. Az előtte és utána álló szóközök nem befolyásolják a besorolást.

Háttérben futó hangfigyelés: az új `public/city/admin-order-alerts-worker.js` külön időzíti az ellenőrzést, hibánál a korábbi ablakidőzítőre áll vissza. A hangfájl előre betöltődik, a **Hangjelzés bekapcsolása** gomb rendelés nélkül is próbahangot indít közvetlenül a kattintásból. A böngésző által várakoztatott `play()` nem blokkolja a következő ellenőrzést; 15 másodperc után újrapróbálható. A háromperces időbélyeg csak sikeresen elindított hang után mentődik. A lapcím is jelzi a függő rendelések számát; fókuszba kerüléskor és kapcsolat-helyreálláskor azonnal ellenőriz. A worker fájlt is ki kell adni, migráció nem szükséges. Ellenőrzés: 47 JavaScript-teszt, 12 kapcsolódó PHP-teszt / 49 ellenőrzés; helyi böngészőben valódi hangfájllal, helyettesített rendelésválasszal és szimulált `hidden` állapottal a próbahang, háttérjelzés és elfogadás utáni leállás sikeres. Ez nem valós operációsrendszer-szintű háttér- vagy alvóállapot-teszt. Az alvó készüléket, bezárt vagy a böngésző által felfüggesztett lapot ez sem ébreszti fel; az [automatikus hanglejátszás](https://developer.chrome.com/blog/autoplay/) és a [háttéridőzítés](https://developer.chrome.com/blog/timer-throttling-in-chrome-88/) böngészőkorlátai megmaradnak.

A vizuális finomítás a darabszámot a terméknév elé helyezi, a választásokat, elhagyásokat és extrákat külön sorokba rendezi, az összesítést pedig külön, legfeljebb 450 px széles táblában mutatja. Nagy képernyőn az oldal szélessége korlátozott; mobilon a mennyiség és a termék kerül előre. A rendelés számítása és mentése változatlan. Helyben 9 kapcsolódó PHP-teszt / 42 ellenőrzés és 360/390/820/1440/2272 px-es, többtételes böngészőpróba sikeres.

A nyitott rendelés állapota a rendeléslista legördülőjéből is módosítható, a meglévő jogosultság- és CSRF-védett végponton. Törlés előtt megerősítést kér; mentés alatt letiltja az ismételt beküldést. Siker után ugyanazt a keresést, oldalszámot és állapotszűrőt tölti újra. Nem igazolható mentésnél ellenőrzésre figyelmeztet; a lezárt és törölt rendelések csak állapotfeliratot mutatnak. Az első oldalon továbbra is 10 rendelés van, az állapotválaszték egy közös lekérdezésből készül.

A részletek kompakt átvétel/fizetés, vásárló és szállítási cím kártyákon látszanak. A megye, ország, tájékozódási pont és irányítószám rejtett, címszerkesztéskor a korábban mentett értékük megmarad. A termék mentett választásai kattintás nélkül jelennek meg: a `---` kezdetű elhagyások piros „Nem kéri”, az `extras_name` tételei zöld „Extra”, az `addons_name` választásai vastag fekete feliratot kapnak. Az elhagyás jelölése mindkét listában elsőbbséget élvez. A besorolás a mentett névlistákból készül, nem a jelenlegi katalógusból. A mennyiség kiemelt, mobilon a termékek külön sorblokkokban olvashatók.

A kiadáshoz a `public/city/admin-orders.css` és `admin-orders.js` fájlok is szükségesek; ehhez a változtatáshoz nem kell migráció vagy npm-build. Ellenőrzés: 221 PHP-teszt / 968 ellenőrzés, 40 JavaScript-teszt; helyi QA-rendelésen listából mentés, hiba-visszajelzés és címszerkesztés, illetve 360/390/820/1440 px-es nézetek ellenőrizve. Élesítés és push nem történt.

## Értékelések és Google-visszajelzés – 2026. október 22.

Az üres vendégoldali nézetek a régi „No Data Found” kép helyett szöveget mutatnak. Kosárnál és üres pénztárnál „A kosarad üres” felirat és az étlapra vezető gomb, termékértékelésnél megfelelő üresállapot-szöveg jelenik meg. Bekapcsolt modulnál a vendég is rákattinthat az étel vagy étterem értékelésére: egy bejelentkezést kérő ablak nyílik meg. A beküldés továbbra is aktív vásárlói fiókhoz kötött; kikapcsolt modulnál sem a gomb, sem az ablak nem jelenik meg. Ellenőrzés helyben: 233 PHP-teszt / 1013 ellenőrzés; vendég gomb → bejelentkezési ablak → belépőoldal, kikapcsolt állapot és üres kosár 360/390/820/1440 px-en. A QA-modul a próba után visszaállt kikapcsoltra. Ehhez a felületi módosításhoz nincs új migráció.

A FoodPRO vásárlói értékelésmodulja átkerült: az **Admin → Értékelések** oldalon étterem- és termékértékelések szűrhetők, jóváhagyhatók, elrejthetők és törölhetők. A tulajdonos kapcsolhatja a modult és az automatikus jóváhagyást; a 29-es moduljoggal rendelkező munkatárs moderálhat. Egy vásárló az éttermet és minden ételt külön, egyszer értékelhet. A csillagszám kötelező, a legfeljebb 255 karakteres szöveg opcionális. Csak közzétett vásárlói termékértékelések számítanak bele az átlagba; a kézzel felvett kiemelt idézetek külön kezelhetők.

**Alapból kikapcsolva.** A `2026_10_22_000001_add_reviews_enabled_to_settings.php` migráció egy `reviews_enabled` mezőt ad a `settings` táblához, 2-es (kikapcsolt) alapértékkel. Nem kapcsol be addont, nem töröl vagy importál véleményt. Migráció előtti hiányzó mezőnél is kikapcsolt marad a vendégoldal. Így a csillagok, értékelési fülek, gombok és űrlapok nem jelennek meg; a közvetlen beküldés 403-at ad. A korábbi vélemények az adminban megmaradnak. Ez egy további kiadási migráció a korábbi ajándékakció- és hírlevélmigráció mellett: az élesítés felmérését ennek megfelelően kell kiegészíteni, általános migrálás vagy helyi adatbázisimport helyett.

A főoldal alján külön Google-felhívás van, a meglévő `google_review_url` beállítással. Ez a belső modultól független, nem ágyaz be Google-szkriptet vagy kitalált csillagátlagot; hiányzó vagy nem HTTPS-link esetén nem jelenik meg. Az új `public/city/admin-reviews.css` a kiadás része, a `city.css` gyorsítótára fájlidővel frissül. Npm-build nem szükséges.

Ellenőrzés: 217 PHP-teszt / 946 ellenőrzés és 34 JavaScript-teszt sikeres; az új `ReviewPortTest` 13 esetet tartalmaz. Elkülönített helyi QA-adatbázison böngészőben igazolva a beküldés → jóváhagyás → megjelenés → kikapcsolás folyamata, a letiltott közvetlen beküldés, a nulla vendégoldali csillag/értékelőfelület és a független Google-gomb. Főoldal, étlap, termékoldal, véleményoldal és admin 360/390/820/1440 px-en túllógás nélkül ellenőrizve. A migráció csak a helyi QA-adatbázison futott, a modul kikapcsolva maradt; éles írás, commit vagy push nem történt.

## FoodPRO hibajavítások átemelése – 2026. október 22.

A kosár feltéteit, extráit és árait a katalógusból ellenőrzi a szerver. Rendeléskor újraszámolja az árakat, adókat, kuponkedvezményt és kiszállítási díjat; ellenőrzi a mennyiségi és összeghatárokat, valamint az aktív fizetési módot. A készpénzes végponton nem lehet online fizetést késznek jelölni. A Barion a tranzakció indításakor mentett környezetet használja, és csak egyező fizetett összegből készít rendelést.

Az admin hozzáférése a fiók és szerepkör aktuális állapotához, illetve moduljogokhoz kötött. A navigáció és a kezdőoldal ugyanezeket a jogokat követi. A rendeléskapcsoló POST + CSRF védelmet használ; a régi telepítő/frissítő webes végpontok mindig 404-et adnak. A korábbi rendeléslista-, hangjelzés-, kosár- és ajándékakció-funkciók megmaradtak. A hírlevélmodult nem írtuk újra.

Ellenőrzés: 204 PHP-teszt / 884 ellenőrzés és 34 JavaScript-teszt sikeres, izolált tesztadatokkal. Böngészőben az admin rendeléskapcsolója kétszer POST + CSRF kéréssel váltott, majd visszaállt. Tesztparancsok a projekt gyökeréből:

```powershell
php -d auto_prepend_file=tests/offline-prepend.php -d allow_url_fopen=0 -d disable_functions=curl_exec,curl_multi_exec,mail,fsockopen,pfsockopen,stream_socket_client vendor/phpunit/phpunit/phpunit --do-not-cache-result
node --test tests/frontend/*.test.cjs
```

A teszt-előtöltő kizárólag a régi MyFatoorah függőség automatikus hálózati frissítését kerüli meg. Az alkalmazás és a vendor változatlan betöltési módot használ élesben. Ehhez az átemeléshez nincs új migráció vagy npm-build. A két új `public/city/admin-ordering-switch.js` és `checkout-guard.js` fájlt is ki kell adni. Telepítéskor a korábbi route/config cache frissítése és a dolgozók moduljogainak ellenőrzése szükséges. Éles telepítés, valódi Barion-fizetés és SMTP-kézbesítési próba nem történt; `.env` és üzemi adatbázis nem változott.

## Megjegyzett bejelentkezés – 2026. október 21.

A vásárlói és az admin/pultos jelszavas belépés automatikusan megjegyzett bejelentkezést kér. A közös `web` guard `remember` ideje 43 200 perc (30 × 24 óra), az OTP-s belépésre is érvényes. A rövidebb szerveres munkamenet lejárata után a titkosított, HttpOnly bejelentkezési süti visszaállítja a hitelesítést. A normál munkamenet saját lejárati beállítása változatlan.

Kijelentkezéskor a megjegyzett süti és a szerveres munkamenet megszűnik; a régi megjegyzett süti nem használható újra. Az admin kijelentkezése is érvényteleníti a munkamenetet és új CSRF-tokent készít. A korábbi kosárátvitel megmarad. A 30 napos süti a következő sikeres belépéskor jön létre. Git deploy után esetleges konfiguráció-cache-t `php artisan config:clear` paranccsal kell törölni, majd egyszer újra bejelentkezni. Migráció vagy `.env`-módosítás nem szükséges.

Ellenőrzés: 185 PHP-teszt / 835 ellenőrzés sikeres; PHP 8.4 alatt 25 kapcsolódó teszt sikeres. Az új izolált tesztek vásárló, tulajdonos és pultos esetén ellenőrzik a süti 30 napos lejáratát, a megjegyzett belépés visszaállítását és kijelentkezés utáni visszavonását; az OTP és elutasított fiókok is lefedettek. Helyi QA-adatbázison valódi böngésző-újraindítás után, kizárólag a megjegyzett sütit visszaadva a profil és a fiók kosara elérhető maradt; kijelentkezés után a régi süti visszautasítva. Tárhelyi telepítés és ottani próba még szükséges.

## Admin termékkezelő – 2026. október 21.

Október 22-i felületi finomítás: az új és a szerkesztő űrlap terméktípus-választója képfájl helyett feliratos, billentyűzettel is kezelhető rádiógombokat használ. A hiányzó ikonok így nem cserélődnek óriási helyőrző képre. Az űrlap tagolt szakaszokat, jól elérhető mentési sávot és korlátozott méretű képgalériát kapott; a képkezelés külön mentése egyértelműen jelölve van. Az új `public/city/admin-item-form.css` a kiadás része, npm-build és migráció nélkül. Helyi QA-böngészőben mindkét űrlap 360/390/820/1440 px-en túllógás nélkül ellenőrizve; a választó kattintással és billentyűzettel is a megfelelő mezőértéket adja. Az `AdminProductsTest` 13 tesztje / 62 ellenőrzése sikeres. Tárhelyi telepítés ebben a körben nem történt.

A külön „Ma nem elérhető” gomb minden termék sorában megmaradt: egy kattintással letiltható mára, az „Újra elérhető” gombbal visszakapcsolható. A meglévő napi elérhetőségi végpontot használja, szerkesztőablak nélkül; a keresés és szűrők megmaradnak.

A terméklistában kép, ár, kategória, szöveges elérhetőség és mai ajánlat látszik. A kereső kategória-, állapot- és ajánlatszűrővel kombinálható; név, ár, módosítás ideje és étlapsorrend szerint rendezhető. A lista 20 soronként lapoz, az Excel/PDF az összes szűrt találatot exportálja. Mobilon a sorok kártyaként jelennek meg. A keresés és szűrők az URL-ben, illetve az adott böngészőlapon megmaradnak frissítés és a részletes szerkesztőből visszatérés után is.

A gyorsszerkesztőben név, kategória, eladási/eredeti ár, aktív/szüneteltetett/mára elfogyott állapot és mai ajánlat menthető. A meglévő kosársorokat, összeállításokat, képeket és leírásokat megőrzi; kategóriaváltáskor a régi alkategóriát kiüríti. A termék slugja nem változik. Az adatellenőrzés és az elavult szerkesztésre adott 409-es válasz megakadályozza a hibás ár és a közben módosított termék felülírását. A részletes szerkesztő megmaradt.

A sorrendezés külön, szűretlen nézetben, a teljes listán működik: fogantyús húzás vagy fel/le gombok, majd kifejezett mentés vagy mégse. A korábbi egymásba ágyazott mentési ciklus helyett minden pozíció egyszer, tranzakcióban mentődik. A képek és a törlési megerősítés a meglévő működést használják.

Ellenőrzés: 172 PHP-teszt / 765 ellenőrzés és 32 JavaScript-teszt sikeres; PHP 8.4 alatt 69 kapcsolódó teszt sikeres. Elkülönített helyi MariaDB QA-adatbázison böngészőben igazolva a kombinált szűrés, lapozás/frissítés, gyors mentés, dupla küldés elleni védelem, hálózati hiba utáni adatmegőrzés/újrapróbálás, párhuzamos szerkesztés, Excel/PDF-letöltés és sorrend mentése/visszavonása. Lista és szerkesztő 360, 390, 820, 1024 és 1440 px-en túllógás nélkül ellenőrizve. Nincs migráció vagy npm-build; a két új `public/city/admin-products.*` fájl a Git kiadás része. A helyi QA-segédek nem kiadási fájlok. Tárhelyi telepítés és ellenőrzés még szükséges; régi útvonal-cache esetén `php artisan route:clear` kell az új mentési végponthoz.

## Belépés utáni kosár és útvonal-cache – 2026. október 21.

A `PreserveGuestCart` közvetlenül a vásárlói vezérlő jelszavas és OTP-s belépési műveletéhez kapcsolódik. Így egy korábbi, a kosárvédelmet még nem tartalmazó útvonal-cache mellett is lefut a vendégkosár átvitele. Két új teszt a javítás előtt mindkét belépésnél elvesző kosár-hozzárendelést mutatott; a javítás után a teljes csomag 158 tesztje, 697 ellenőrzése sikeres. PHP 8.4 alatt a kapcsolódó 40 teszt is sikeres.

Elkülönített helyi QA-adatbázison, valódi böngészőkérésekkel 390 és 1200 px-en ellenőrizve: a kosársorok és extrák megmaradnak belépés, frissítés és profilból visszatérés után; kilépés után a fiók kosara nem látszik a vendégnek. Ez helyi bizonyíték: a bejelentett tesztdomain hibaoka és a javítás ottani működése még nincs igazolva. A tárhelyi Git deploy után `php artisan route:clear` és a vendégkosár → jelszavas belépés → kosár böngészős próba szükséges. Migráció nem kell.

## Rendelési e-mailek – 2026. október 21.

A webes vendégrendelések is kapnak értesítést a rendeléshez megadott érvényes e-mail-címre: rendelés beérkezése, elfogadás, elkészült/átvehető állapot, lezárás, törlés és futárhoz rendelés. A további egyedi státuszok a mentett státusznévvel jelennek meg. Regisztrált vásárlónál a korábbi e-mail-értesítési beállítás megmarad; elsődleges a rendelésben megadott cím. Az admin nem kap e-mailt az új rendelésről vagy a vásárlói lemondásról. A vásárló és a kijelölt futár értesítése megmarad.

Az egységes HTML- és szöveges levél tartalmazza a rendelési számot, tételeket, kiegészítőket, extrákat és a mentett végösszeget. A Barion-visszaigazolás csak a rendelés adatbázis-tranzakciójának sikeres lezárása után készül. Azonos státusz ismételt mentése és az ismételt Barion-callback nem indít újabb ügyféllevelet. A küldési hiba naplózódik, de nem teszi sikertelenné a mentett rendelést; automatikus levél-újraküldés nincs. POS-rendelésekhez ez a webes értesítés nem fut.

Ellenőrzés: `php vendor/phpunit/phpunit/phpunit --filter OrderNotificationTest` (izolált SQLite, hamisított levelezés és memóriabeli MIME-összeállítás; valódi címzetthez nem küld). A levélnézet 360, 390, 600 és 1200 px szélességen vízszintes túllógás nélkül ellenőrizve. A meglévő SMTP-beállítások változatlanok, migráció nem szükséges. A tárhelyi kézbesítés külön, saját tesztcímmel ellenőrizendő a Git deploy után; az izolált teszt nem bizonyít SMTP-kézbesítést vagy minden levelezőkliens megjelenését.

## Korábbi kiadás előtti ellenőrzés – 2026. október 21.

Az alábbi kiadási jelentés a korábbi ellenőrzés pillanatképe; a benne szereplő commitok és tesztszámok az akkori állapothoz tartoznak.

Ellenőrzött példány: `C:\xampp\htdocs\Gyros_City_Live`, helyi alapcommit: `cc48686`. A GitHub `main` az ellenőrzéskor még `96fc3c0`; a mai javítások helyi, nem commitolt módosítások. Git deploy csak a végleges változások külön engedélyezett commitja/push-a és a távoli SHA ellenőrzése után viheti át ezeket.

**Állapot: zárt tesztdomain előkészíthető, a 2026. október 22-i élesítés még nincs jóváhagyható állapotban.** A backup szükséges, de nem helyettesíti az alábbi hibák rendezését és a tárhelyen végzett próbát.

Helyben igazolt eredmények:

- PHP: `php vendor/phpunit/phpunit/phpunit` – 120 teszt, 502 ellenőrzés, sikeres. A Barion környezetválasztás hat új esetéből három a javítás előtt hibázott, utána mind sikeres.
- JavaScript: `node --test tests/frontend/*.test.cjs` – 32/32 sikeres. A PHP/JavaScript szintaktikai ellenőrzés, `composer validate --no-check-publish`, helyi `composer check-platform-reqs --no-dev`, `composer dump-autoload --optimize --no-scripts --strict-psr` és `php artisan view:cache` is sikeres. A helyi PHP 8.2.12; a tárhely PHP-verziója és bővítményei külön ellenőrizendők.
- A helyi adatbázisban a `gift_promotions`, `newsletters`, `newsletter_deliveries` táblák és a két migráció bejegyzése megvan. Mind a 166 hivatkozott termékkép megtalálható. Ez nem igazolja a teszt- vagy éles tárhely állapotát.
- A `.htaccess` javítása után 21 helyi HTTP-ellenőrzés igazolta a privát fájlok tiltását és a nyilvános oldalak/assetek elérhetőségét. Előtte a Git-metaadatok, naplók, QA-fájlok és a `LIVE_source` SQL-mentése is elérhető volt HTTP-n. A mentés saját `.htaccess` fájlja miatt külön URL-tiltás is szükséges volt. A tárhelyen ugyanezt a védelmet tényleges HTTP-kérésekkel ellenőrizni kell.
- Két adminvezérlő névtere és egy route-import a kisbetűs `admin` könyvtárhoz lett igazítva. Natív Linux-futtatás ebben a körben nem történt.
- A Barion-indítás, visszatérés és callback közös környezetválasztást használ: `APP_ENV=production` vagy `prod` esetén `prod`, más környezetben `test`. Hiányzó tesztbeállításnál nincs visszaesés az éles kulcsra. Valódi fizetést ez az ellenőrzés nem indított.

Nyitott kiadási hibák:

1. `composer audit --locked --no-dev --format=json`: 71 advisory-bejegyzés 18 csomagnál, köztük 2 critical és 22 high besorolás. Ezek érintett verziókra vonatkozó jelzések, nem 71 igazolt alkalmazásszintű támadási út. A lockolt Laravel `9.52.16` verziót a [környezetmanipulációs közlemény](https://github.com/laravel/framework/security/advisories/GHSA-gv7v-rgg6-548h) is érinti; a PHPSpreadsheet `1.29.0` verzióhoz is vannak [biztonsági közlemények](https://github.com/PHPOffice/PhpSpreadsheet/security/advisories/GHSA-q4q6-r8wh-5cgh). A frissítések és a fennmaradó kockázatok áttekintése még szükséges. Csak `composer update --dry-run` futott: a több mint száz csomagot érintő teljes frissítés nem lett alkalmazva, a lockfájl nem változott.
2. A helyi böngészős teszt kétszer is hibás, `storage/app/public/` előtag nélküli asset-URL-eket látott a feltöltött kosár körül: 82 hibás erőforráskérés, 8 JavaScript-hiba (`$`/`toastr` nincs definiálva). Egyszerű HTTP-újraolvasáskor az útvonalak helyesek voltak; a kiváltó ok még nincs igazolva. Az 18, 360/390/1440 px-es oldalellenőrzés nem talált oldalszintű vízszintes túlcsordulást, és a vendégkosár megmaradt frissítés/visszalépés után, de a böngészős ellenőrzés összességében HIBÁS. A saját próbatétel törölve lett. A tesztdomainen a teljes böngészős folyamat újraellenőrzendő.
3. `php artisan route:list --except-vendor --json` hiányzó `MyFatoorahController` miatt hibázik. A 365 vezérlős útvonal vizsgálatakor 12 hibás regisztráció volt: MyFatoorah (4), értékelésmodul (5), `/validate_data`, `POST /auth`, `/admin/item/list`. A használat és a kívánt működés tisztázásáig nincs hamis siker-válasz vagy automatikusan törölt útvonal. `route:cache` nem került bekapcsolásra.

A mai, Git-alapú teszttelepítés feltételei:

- Elkülönített, hozzáférésvédett domain és adatbázismásolat; saját szerveroldali környezeti beállítások. `APP_ENV=staging`, `APP_DEBUG=false`, megfelelő `APP_URL`, domainhez illő session-cookie, `ASSETSPATHURL=storage/app/public/`. A helyi `.env`, sessionök és QA-segédek nem másolandók fel. Az éles környezeti fájlt nem írjuk felül.
- A `storage/app/public` képei és régi CSS/JS-állományai, továbbá a `storage/installed` nincsenek Gitben: új tárhelyen külön előkészítendők. A futási könyvtárak legyenek írhatók; a feltöltések a következő Git deploy során is maradjanak meg.
- A tesztoldal ne használjon éles levelezést, értesítésküldést, éles Barion-kulcsot vagy átmásolt automatikus cronfeladatokat. A helyi adatbázisban csak engedélyezett `prod` Barion-beállítás volt: a tesztfizetéshez külön `test` sor, tesztkulcs és a tesztdomainhez tartozó visszatérési/callback URL kell. A callbacknek a kívülről érkező Barion-értesítést is fogadnia kell.
- A `cron/*.php` HTTP-n tiltott; ütemezésük szerveroldali PHP CLI-parancsként ellenőrizendő. A régi `decide_unprocessed.php` éles domainre mutató URL-t tartalmaz, ezért teszttárhelyen nem indítandó el.
- A tárhelyen a lockból történő `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader` és platformellenőrzés szükséges; `composer update` ne legyen deploylépés. `config:cache` a sok közvetlen `env()`-hívás miatt külön kompatibilitási próba nélkül továbbra sem használható.

Holnapi élesítés előtt a nyitott hibák rendezése és a tesztdomainen igazolt rendelés/kosár/belépés/admin/hang/Barion-folyamat szükséges. Közvetlenül a zárás utáni váltás előtt friss adatbázis- és fájlmentés kell, visszaállítási próbával, lezárt folyamatban lévő fizetésekkel. Az éles adatbázist megtartva csak a két megnevezett új migráció jöhet szóba, ha a táblák és a migrációbejegyzések még hiányoznak; általános migrálás vagy helyi dump visszatöltése nem kiadási lépés. Éles szerver, adatbázis, `.env`, GitHub vagy automatikus deploy nem lett módosítva ebben az ellenőrzésben.

## Gyros City frissítés – 2026. október 20.

Az admin rendelési hangfigyelése a `public/city/admin-order-alerts.js` fájlba került. A `getorder` végpont a még el nem fogadott (`status_type=1`), nem POS rendelések azonosítóit adja vissza; nem az olvasatlan jelzőt vagy a darabszám változását figyeli, és éjfélkor sem felejti el a függő rendelést. Az első jelzés után 180 másodpercenként, a következő sikeres öt másodperces ellenőrzéskor újra jelez, amíg a rendelés el nem hagyja ezt az állapotot. A jelzés az értesítő ablak bezárásától független; időpontját a böngésző a webhelyhez és adminhoz kötött localStorage-kulcson őrzi, így az „OK” miatti újratöltés sem törli. Letiltott böngészőtár esetén az aktuális oldalon tovább működik, de újratöltések között nem tudja megőrizni az időpontot.

Lejátszási tiltás vagy hanghiba látható üzenetet és kézi bekapcsoló/újrapróbáló gombot ad, az üzenet szükség esetén oldalfrissítés után is megmarad. A hangfájlt a meglévő általános beállításból veszi, nem igényel migrációt. Hálózati hiba és 15 másodperces kérési időtúllépés után is újrapróbálkozik; régi, nem frissített állapot alapján nem indít új hangot. A figyelés nyitott, futó adminoldalt igényel; alvó készüléknél vagy felfüggesztett böngészőlapnál a pontos időzítés nem garantálható. Visszatéréskor azonnal frissíti az állapotot.

A hangjavításhoz három izolált PHP-teszt először reprodukálta a régi hibákat, majd sikeres lett. Nyolc JavaScript-teszt ellenőrzi a háromperces határt, ismétlést, oldalfrissítést, rendelésazonosító-cserét változatlan darabszám mellett, elfogadást, több rendelést és hibás/tiltott tárhelyet. A QA-adminon gyorsított böngészőórával, helyettesített hanggal és tesztválaszokkal ellenőrizve lett a lejátszási tiltás, a kézi feloldás, az „OK” utáni ismétlés, az elfogadás utáni elnémulás és egy hálózati hiba utáni helyreállás. Ez a lejátszási hívásokat igazolja, a kezelő valódi készülékének hangszóróját nem teszteli.

A pizzaleírások idegen összetevőneveinek magyarázata külön, görgethető ablakban nyílik meg. A korábbi, hover/fókusz/kattintás miatt beragadó tooltip helyett natív gomb indítja; bezárható a két bezárógombbal, az ablakon kívülre kattintva és Escape-pel, majd visszaadja a fókuszt az összetevőnek. A régi oldalelhomályosítás miatt az ablak közvetlenül a body alá kerül.

Az admin rendeléslistája szerveroldalon lapoz, oldalanként 10 rendelést hoz, legújabbal kezdve. A rendelési számra, névre és telefonszámra keresés a teljes, státusz szerint szűrt listában működik; lapozáskor a keresés és a státusz megmarad. Az összesítő számok a teljes online rendelésállományt mutatják. Az oldal nem használ második, csak az aktuális tíz sorban kereső DataTable-lapozást; a kimutatás/export a meglévő kimutatásoldalról érhető el. A státusz- és fizetésimód-neveket egyszerre olvassa be, a listában nem tölt felesleges vevő-/futárkapcsolatokat. Nem igényel migrációt.

Ugyanazzal a helyi, 1098 online rendelést tartalmazó adatbázissal a teljes adminnézet CLI-s előállítása a módosítás előtt 5328 ms / 8838 lekérdezés / 2 357 851 bájt volt. Az első javított mérés 349 ms; a végleges, a közös futárválasztóhoz szükséges névlistát is megtartó változat meleg nézetcache mellett 103 ms / 113 lekérdezés / 79 759 bájt. Ezek helyi szerveroldali mérések, nem éles válaszidőgaranciák. Öt új izolált teszt ellenőrzi a tízes korlátot, rendezést, lapozást, teljes keresést, státuszszűrést és összesítőket. A teljes PHP-csomag 107 tesztje, 474 ellenőrzése sikeres; az összeállító 6 JavaScript-tesztje is sikeres. Böngészőben az adminlapozás, régi rendelés keresése, üres találat és a magyarázat bezárása/újranyitása is ellenőrizve; az admin 360, 390 és 1440 px-en nem okoz oldalszintű vízszintes túllógást. A böngészős rendelési tesztadatok kizárólag az elkülönített QA-adatbázisba kerültek.

Az étlap kategóriaoldala és kategóriaválasztója külön elrendezést kapott (`public/city/menu.css`, `menu.js`): asztali kategóriasáv, mobilon vízszintesen lapozható kategóriák, kategórián belüli névkeresés és tömörebb ételkártyák. A kiválasztott mobil kategória automatikusan látható helyre kerül.

A termékoldal és a felugró összeállító közös nézetet használ (`web/city/builder.blade.php`, `public/city/builder.css`, `builder.js`). Mobilon teljes képernyős ablak, teljes sorban érinthető választók, előre rendezett kötelező csoportok, választási korlátok és élő, extrákat/darabszámot is tartalmazó ár segíti a rendelést. Az összeg és a rendelőgomb mobilon látható marad. Kosárba helyezéskor nincs oldal-újratöltés; a visszajelzésből megnyitható a kosár. Hibánál az összeállítás megmarad, az ismételt koppintás nem indít párhuzamos kosárkérést. A termékképek natívan lapozhatók, a mobil mezők 16 px-esek, az alsó navigáció és a rendelősáv figyelembe veszi a kijelző biztonsági sávját. Ezek az eszközök sem igényelnek Mix-buildet.

Jelszavas és OTP-s belépéskor a `PreserveGuestCart` a hitelesítés előtti valódi munkamenet-azonosítót adja át a `GuestCartTransfer` szolgáltatásnak. Csak az adott vendég saját, még fiókhoz nem rendelt sorait veszi át, az eredeti tételekkel, árakkal, extrákkal és mennyiségekkel együtt; a fiók korábbi kosara is megmarad. Az eltérő összeállítású sorokat nem vonja össze, az ajándékválasztást pedig átviszi a felhasználói kulcsra, majd a meglévő akciószolgáltatás újraellenőrzi. A normál és gyorsrendelési kosár külön marad; új vendég-gyorsrendelés átvételekor a fiók korábbi gyorsrendelési tételei a normál kosárba kerülnek. Elutasított belépés után a vendégkosár követi az esetleges munkamenetváltást. Kilépés új munkamenetet indít, így a fiók tételei nem jelennek meg a kijelentkezett vendégnek. A javítás nem igényel migrációt vagy korábbi kosarak visszamenőleges módosítását.

A pénztár városmezője szerkeszthető, és a meglévő `shipping_area` lista nevei alapján választ területet. Az ékezetek, kis/nagybetűk, irányítószám és a kötőjel/szóköz eltérése nem akadály. A `DeliveryAreaNames` a névből leválasztja a díjat és a megjegyzést; közös terület vesszővel, pontosvesszővel vagy perjellel felsorolt települései is felismerhetők. A `public/city/delivery.js` a kiválasztáskor frissíti a díjat és a végösszeget. Részleges/ismert területhez nem illő névnél törli a régi díjat; több egyezésnél kézi választást kér. A területlista és a díjak változatlanok, nincs geokódolás vagy külső szolgáltatás. A végleges kártyás fizetés továbbra is a szerveren beolvasott területi díjat használja.

Az üres vagy hibás dátum formázása „—” jelet ad az 1970-es dátum helyett. A rendelés részleteiben időzítetlen átvételnél „Elkészülés után”, kiszállításnál „Mielőbbi kiszállítás” látszik; megadott dátumnál a dátum és idősáv megmarad. A nyomtatott átvételi dátum is a beállított átvételi napot használja. Korábbi rendelési adatokat nem módosítottunk.

A főoldal új, mobilon is külön elrendezett hamburgeres nyitóképet kapott. A megjelenés a `public/city/city.css`, a kapcsolódó működés a `public/city/city.js` fájlban van; ezek közvetlenül töltődnek be, nem igényelnek Mix-buildet. A WebP nyitókép a tulajdonos által megadott Gyros City kép alapján készült, generált átdolgozás. A `public/city/facebook-*.jpg` képek a [Gyros City Facebook-oldaláról](https://www.facebook.com/gyroscity) letöltött képi referenciák.

Az adminban kikapcsolt ételek az aktív kategóriák étlapján és keresésében láthatók maradnak, szürkített képpel és „Nem elérhető” jelöléssel. Kosárba tenni, mennyiséget növelni és rendelést leadni ilyen étellel szerveroldalon sem lehet. A tényleges törlés továbbra is eltávolítja az ételt.

### Ajándékakció beállítása

Az **Admin → Ajándékakciók** menüpont az étterem tulajdonosának (`type=1`) érhető el.

1. Add meg az akció vásárlóknak is látható nevét.
2. Válassz egy kategóriát vagy konkrét ételeket, és állítsd be a szükséges darabszámot. A vegyesen választott ételek mennyisége összeadódik.
3. Jelöld be a választható ajándék ételeket, és az ajándék darabszámát. Minden választható íz külön katalógustétel legyen. Fizetős extrák nem adódnak az ajándékhoz.
4. Válaszd ki, hogy rendelésenként egyszer vagy minden teljes darabszám után újra járjon az ajándék. Opcionálisan első és utolsó nap is megadható; mindkettő beleszámít, Europe/Budapest szerint.
5. Kapcsold be és mentsd az akciót. Több aktív szabály együtt is érvényesülhet.

A szükséges darabszám elérésekor ajándékválasztó jelenik meg. A választás a kosárban is módosítható, és munkameneten belül frissítés után is megmarad. A rendszer újraszámol mennyiség- és elérhetőség-változáskor, valamint fizetés/rendelés előtt. A jogosultság elvesztése törli a már nem járó választást. Ha minden beállított ajándék elfogyott, ezt jelzi a felület, a fizetős rendelést tovább engedi.

Az ajándékok szerver által képzett, 0 Ft-os rendelési tételek. Nem kerülnek fizetős kosársorként az adatbázisba, nem generálnak további ajándékot. Készpénzes rendelésnél a meglévő rendeléstétel-mentés, Barionnál a fizetés indításakor elmentett rendelési pillanatkép viszi tovább őket; az admin, rendelésnézet és számla a meglévő tétellistát használja.

### Hírlevélküldés – a FoodPRO meglévő modulja

A modul a FoodPRO `41580d3d21dc2d7311ff7941cb807ef7642db4cb` állapotából került át. A `NewsletterSender`, `Newsletter`, `NewsletterDelivery`, `NewsletterMail`, leiratkozó vezérlő és `newsletter:send` parancs az eredeti megvalósítás. A Gyros Cityhez az adminjogosultságot (jelenleg csak tulajdonos, `type=1`), a menühivatkozásokat és a színeket igazítottuk; nem került át FoodPRO-címzett, levél vagy SMTP-beállítás. A nyilvános feliratkozás ellenőrzése és címnormalizálása is a FoodPRO meglévő megoldását használja.

Az **Admin → Hírlevélküldés** felületen piszkozat, élő előnézet, külön címre küldött próbalevél és a mentett levél megerősített kiküldése érhető el. A meglévő `subscribe` lista érvényes, egyedi címeit használja. Küldés közben ötfős kötegek futnak, amíg az állapotoldal nyitva van; megszakítás után a mentett állapotból folytatható. Ugyanannak a hírlevélnek ugyanaz a cím csak egy kézbesítési sora lehet. A hibás és a bizonytalan, már folyamatban lévő kézbesítést nem próbálja újra automatikusan. A „küldve” a levelezőrendszernek történő átadást jelenti, nem bizonyítja a postaládába érkezést.

Minden címzett külön levelet és saját leiratkozó linket kap. A link megnyitása még nem iratkoztat le; a megerősítő gomb intézi a leiratkozást, és a még függő levelek kihagyását. A tárgy és a szöveg ellenőrzött, az előnézet és a levélsablon nem hajt végre beírt HTML-t. A modul a Gyros City meglévő Laravel levelezési beállításait használja, `.env`-módosítás nélkül.

Az átvett migráció csak a `newsletters` és `newsletter_deliveries` táblákat hozza létre. Meglévő feliratkozót vagy rendelést nem módosít. A helyi telepítéskor a feliratkozólista változatlanságát SHA-256 összehasonlítással ellenőriztük. Éles kiadás előtt adatbázismentés után, külön jóváhagyott telepítéskor futtatható:

```sh
php artisan migrate --path=database/migrations/2026_10_07_000001_create_newsletter_tables.php
```

Visszaállításkor a két új tábla megtartható; a `down()` törölné a hírlevélelőzményeket és kézbesítési állapotokat. Automatikus időzítés nem lett beállítva. Később külön üzemeltetési beállítással a `php artisan newsletter:send --limit=25` parancs dolgozhat fel legfeljebb 25 függő címzettet futásonként; ezt a parancsot éles listára a beépítés során nem futtattuk.

Az átvett 13 funkcióteszt izolált SQLite-adatbázissal és helyettesített levelezővel fut. A teljes adminfelület, mentés, nem mentett módosítás miatti küldéstiltás, próbalevél, kötegelt küldés, frissítés utáni állapot és leiratkozás MariaDB QA-adatbázison, mesterséges címekkel és igazoltan `log` levelezővel is ellenőrizve. Az adminoldalak 360, 390 és 1440 px-en nem lógnak túl. A normál helyi adatbázisban nem jött létre hírlevél vagy kézbesítés; valódi SMTP-kézbesítés nem lett tesztelve.

### A 2026. október 22-i élesítés előkészítése – LIVE_source mentés

A 2026. október 20-án átadott mentésben 63 adatbázistábla van. A helyi adatbázisban ezek oszlopnevei egyeznek a mentéssel; ez nem teljes típus-, index-, adattartalom- vagy éles szerverellenőrzés. A mentésből hiányzó új táblák: `gift_promotions`, `newsletters`, `newsletter_deliveries`. A két új migráció csak ezeket hozza létre; visszavonásuk viszont törölné az új funkciók adatait.

A helyi adatbázis 166 külön termékképfájlt hivatkozik. Ebből 84 hiányzott helyben; ezeket az eredeti nevükön, meglévő fájl felülírása nélkül átmásoltuk a mentésből. Utána mind a 166 megtalálható, és tartalmuk SHA-256 alapján egyezik a mentéssel. Adatbázisrekordot nem módosítottunk.

A képfájlok tényleges helye `storage/app/public/admin-assets/images/item/`; a régi helper és a feltöltés közvetlenül az `ASSETSPATHURL` útvonalat használja. Ezért önmagában a szokásos `public/storage` symlink létrehozása nem elég. A teljes szükséges `storage` tartalomnak, a munkameneteknek, a `storage/installed` jelzőnek és az eredeti képek URL-jeinek is elérhetőnek kell maradniuk az új kiadásból. Az éles `.env`, az `APP_KEY`, az adatbázis-kapcsolat és a session-beállítások megőrzendők.

Javasolt kiadási sorrend (előkészítés, még nem végrehajtott élesítés):

1. A Hostinger tényleges telepítési módját és célkönyvtárát ellenőrizni kell. Külön kiadási mappában készüljön el a rögzített Git-commit, a `composer.lock` szerinti függőségekkel. Az automatikus deploy legyen kikapcsolva a jóváhagyott időpontig. A `LIVE_source`, az SQL, a helyi QA-segédek és a tesztadatok nem kiadási fájlok.
2. A feltöltések és a futás közben változó állományok tartós, deploy által nem cserélt tárhelyet kapjanak. A megosztott storage használatát, jogosultságait, valamint a régi és új képek HTTP-elérését előre próbakiadással kell igazolni. Egy `.gitignore` szabály önmagában nem garantálja, hogy a tárhely deployja megőrzi a fájlokat. A [Hostinger hivatalos útmutatója](https://www.hostinger.com/support/1583302-how-to-deploy-a-git-repository-in-hostinger/) célkönyvtári felülírásra is figyelmeztet repóváltáskor; ebből nem következik általános feltöltésmegőrzési garancia.
3. Élesítés előtt külön próba friss adatbázismásolattal, valódi levélküldés és élő fizetés nélkül: étlap és képek, belépés utáni kosár, területi díj, rendelési dátum, ajándékválasztás és admin rendelésnézet. A jelenlegi teljes `route:list` hibája miatt ne kerüljön ellenőrizetlen `route:cache` a deployba. A kód sok helyen közvetlen `env()`-hívást használ, ezért `config:cache` sem kapcsolható be külön kompatibilitási próba nélkül.
4. Az éjszakai váltás előtt ne legyen aktív rendelés vagy függő kártyás fizetés. Rövid rendelési szünet alatt friss, visszaállítható adatbázis- és fájlmentés kell a webgyökéren kívül. A mostani SQL kizárólag összehasonlítási/próba alap: élesbe visszatölteni tilos, mert az azóta érkezett adatok elvesznének. Az admin-, háttérfolyamat- és fizetési írásokat is figyelembe kell venni a mentés konzisztenciájához.
5. Csak a két megnevezett új migráció fusson az aktuális éles adatbázison, előzetes tábla- és migrációállapot-ellenőrzés után; általános `migrate`, `migrate:fresh`, `db:wipe`, seeder vagy dumpimport ne legyen automatikus deploylépés. Ezután az előre elkészített kódra váltás és a nézetcache frissítése következhet. A pontos váltási parancs a Hostinger könyvtár- és symlink-lehetőségeinek ellenőrzése után rögzíthető.
6. Újranyitás előtt HTTP-ellenőrzés mobilon és asztali méretben, admin belépés, képek és rendelési folyamat, valamint a mentés előtti/utáni rendelésazonosítók és állapotok ellenőrzése kell. A karbantartási middleware jelenleg minden útvonalat tilt, így a Barion `/barion/callback` értesítést is; a függő tranzakciók lezárását és a callback működését a váltás előtt rendezni kell. Teljes karbantartási mód aktív fizetés alatt nem kapcsolható be ellenőrizetlenül.
7. Hibánál az előző kódra kell visszaváltani, a működő adatbázist és feltöltéseket megtartva. Az új táblák maradjanak meg. Újranyitás után beérkezett rendeléseket egy korábbi adatbázismentés visszatöltése elveszítene; ilyen visszaállítás külön egyeztetést igényel.

Cél a rövid váltási szünet; pontos időt és adatvesztés nélküli eredményt csak a tárhelyen végzett próba, mentés-visszaállítási ellenőrzés és a fizetési folyamat igazolása után lehet vállalni. Hostinger-fiókhoz ebben az ellenőrzésben nem kapcsolódtunk, éles módosítás nem történt.

### Az étlap- és ajándékfrissítés telepítése

Az új migráció csak a `gift_promotions` táblát hozza létre; meglévő táblákhoz és rendelésekhez nem nyúl. Előbb készüljön adatbázis-mentés, majd a jóváhagyott kiadás részeként futtatható:

```sh
php artisan migrate --path=database/migrations/2026_10_20_000001_create_gift_promotions_table.php
php artisan view:cache
vendor/bin/phpunit
vendor/bin/phpunit --filter NewsletterPortTest
vendor/bin/phpunit --filter GuestCartLoginTest
node --test tests/frontend/barion-delivery-minimum.test.cjs
node --test tests/frontend/delivery-city.test.cjs
node --test tests/frontend/builder.test.cjs
node --check public/city/city.js
node --check public/city/delivery.js
node --check public/city/menu.js
node --check public/city/builder.js
```

Az új akciótábla hiányában az étlap tovább működik, az admin jelzi a telepítési igényt. Visszaállításnál az új tábla megtartható; a migráció `down()` művelete törölné a beállított akciókat, ezért azt csak külön jóváhagyással és mentés után szabad futtatni. Éles környezetben nincs automatikus akció-létrehozás, adatfeltöltés vagy publikálás. Az importált éles katalógus már tartalmaz pizzákat és desszerteket. Az akció indítása előtt a tényleges ajándéktételeket és választható ízeket az adminban kell ellenőrizni és a szabályhoz rendelni.

Az akció- és fizetési funkciótesztek elkülönített, memóriabeli SQLite-kapcsolaton futnak; a migráció és a felületi próbák MariaDB-n is ellenőrizve lettek. A böngészős mintarendeléshez külön QA-adatbázis készült, valódi rendelés és fizetés nélkül. A `storage/framework/testing` alatti helyi segédek és QA-bejelentkezési router nem kiadási fájlok, nem telepíthetők éles tárhelyre.

Ellenőrzött helyi állapot: 102 PHPUnit-teszt (454 ellenőrzés), 24 JavaScript-teszt, Blade-fordítás, PHP/JS szintaxis és `git diff --check` sikeres. A dátumhibát célzott teszt reprodukálta (`null` → `1970-01-01`), és a javítás után az üres/hibás, illetve a valódi dátumok tesztjei is sikeresek. Böngészőben 360, 390, 820 és 1440 px-es összeállító és termékoldal, valamint 360/390 px-es főoldal, kategóriák, étlap, kosár és pénztár ellenőrizve: nincs vízszintes túllógás. Az összeállítónál hiányzó kötelező választás, maximum elérése/feloldása/törlés, extra és darabszám ára, hálózati hiba utáni megmaradó választás és újbóli próbálkozás, dupla koppintásra egyetlen kérés, kosárban tárolt összeg, vendég pénztárra lépés és ajándékjogosultság is ellenőrizve. A korábbi automatikus településfelismerés/díjváltás/kézi többértelmű választás/elviteli díjnullázás, valamint főoldal-, kosár- és ajándékfunkciók próbái is sikeresek. A kezdeti sütisáv/visszajelzés-ütközést javítottuk és a kosárlinket újra ellenőriztük. Valódi rendelés és élő fizetés nem futott.

Meglévő ellenőrzési korlát: a teljes `php artisan route:list` egy korábbi, hiányzó `App\Http\Controllers\MyFatoorahController` miatt hibázik. Az új akcióútvonalak regisztrációja külön ellenőrizve lett, a hozzájuk tartozó HTTP-funkciótesztek sikeresek.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
