<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Kiadás előtti ellenőrzés – 2026. október 21.

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
