# Helyi végrehajtási napló

## Az alap commitolása

A felhasználó jóváhagyta az elkészült alap commitolását a `feature/gctv-v1-local` ágon. A commit a folyamatos reklámműsort, a három referenciavideó kioszkos változatát, a hozzájuk tartozó ármentes asseteket, regressziós teszteket és dokumentációt foglalja össze. A folytatás külön fejlesztési kör lesz; ez a lépés nem tartalmaz push-t, publikálást vagy élesítést.

## Három forrásvideó alapján újraépített kioszk – 2026-11-09

A felhasználó megadta mindhárom eredeti helyi videót, és a három változat másolatát kérte. A korábbi általános demót a megfigyelt kompozíciókhoz igazítottuk: hat burger egy parázslapon, hat eredeti étlapoldal a megfelelő 3×2/4+3/4×2/2+3 rácsokkal, illetve Pizza Time nyitókép és egyedi nagy pizzák. Fotók/hátterek leválasztva, a történeti árak nem kerültek át a médiába. Helyi fontok és licencek, termékazonosítóhoz kötött referenciaképek, közös renderelőben választható megjelenések készültek.

A három helyi vázlat listaazonosítója 7/8/9; egyik sincs publikálva. A jelenlegi rendelhetőség miatt 7/10 referencia-pizza aktív; termékstátusz és katalógusár nem változott. A nyitó akciós felirat a felhasználó videójának másolata, nem ajándékpromóció-aktiválás. A kép-/fontverziók teljes SHA-256-os fizikai fájlt és jelenethez kötött descriptorokat kaptak, ezért régi és új kiadás együtt is előtölthető. Az eszköz ezeket blob-URL-ekkel használja, a fontcsalád is checksumhoz kötött.

Teljes ellenőrzés: **324 PHP-teszt / 1401 állítás**, **87 JS-teszt**, Blade cache és szigorú UI statikus audit sikeres. Valódi böngészős referenciakép-összevetés, két TV-felbontás, teljes lapozás, rövid árfelfedés, árfrissítési DOM-megőrzés, szünet, lejárat és 13 fájlos offline checksum-próba sikeres. Független reviewer utolsó readbackjében nem maradt fontos találat. Részletek: `acceptance.md`, `reference-rebuild.md` és az assetmappa README-je. A módosítások továbbra is helyiek és nem commitoltak; élesítés nincs.

Terv: [implementation-plan.md](implementation-plan.md), GCTV-V1-20261109. A felhasználó 2026-11-09-én jóváhagyta: „kövesd a tervet és haladj önállóan”. Helyi fejlesztés, külön feature branch, commit/push engedélyezett; élesítés nincs.

Végrehajtás: ugyanebben a munkamenetben, T0–T6 sorrendben. A helyszíni T7 külön, igazolt hardverrel végezhető.

- T0: `9884e1b` a tervmásolat és az első felmérés; az induló környezeti hiba rögzítve.
- T0 kiegészítés: Apache és MySQL rejtett ablakkal elindítva. A kezdőlap böngészőben betölt, az izolált böngészőkontextusban nincs regisztrált service worker. A DB 172 terméket tartalmaz; a hat referencia-burger item_id=60–65. A helyi item táblában nincs has_variation mező; a modell fillable listája nem sémaigazolás.
- Ruling: A meglévő helyi checkout új branchén dolgozom, nem másolok .env-et másik worktree-be — így a kért localhost tesztelés és a meglévő képek megmaradnak.
- Ruling: A TV megjelenítési ára a katalógusár, nem adóval önkényesen újraszámolt végösszeg — helyszíni árprofil igazolásáig ezt az admin jelzi.

## Ellenőrzési terv

T1: új SignageCatalogTest (alapár, Top Deal, időhatár, kötelező választás, elérhetőség, verzió és lejárat), meglévő CatalogPricingTest és kapcsolódó checkout-tesztek. `baseQuote(Item, ?DateTimeInterface)` és a selection opcionális időparamétere közös árforrást ad; a meglévő hívások változatlanok.

T2: külön eszközguard, egyszeri nonce-hoz kötött párosítás, tokenvisszavonás, explicit adminjog; SQLite-alapú integrációs tesztek. Az új migráció kizárólag signage táblákat hoz létre; a down ezek adatait törölné, ezért normál letiltáskor nem futtatandó.

T3: privát média, MIME/ffprobe ellenőrzés, aszinkron konverzió és checksum; hibás fájl, sikertelen feldolgozás és publikált média védelme.

T4: négy közös sablon és két téma; adminelőnézet és TV ugyanazt a renderelőt használja; 1920×1080, 1280×720 és keskeny adminpróba.

T5: vázlat/snapshot, független TV-k, ütemezés és rollback friss árakkal; publikálási és időzítési tesztek.

T6: teljes média-előtöltés és atomi váltás, külön katalógusfrissítés, árlejárat és hibakezelés; JS-futtatási és böngészős ellenőrzések.

T7: a videómellékletek, valódi készülék, 24 órás helyszíni próba és éles hoszt nincs biztosítva; ilyen eredmény nem állítható helyi teszt alapján.

## Megvalósítás és bizonyíték

- T1: baseQuote és SignageCatalog elkészült. Az új tesztek a hiányzó osztály/metódus miatt először hibáztak, majd sikeresek lettek. A hat valódi burger árparitása és képei ellenőrizve.
- T2: saját táblák, 35-ös moduljog, egyszeri nonce-os párosítás, tokenhash, izolált manifest/média és visszavonás elkészült. Első heartbeat előtti kiadásmegőrzéshez külön regresszió készült.
- T3: privát média, külön queue, GD/FFmpeg normalizálás, poster, checksum és hibastátusz. Valódi teszt-MP4/MOV feldolgozva; az eredeti referenciák elfogadása nyitott.
- T4: négy sablon, két téma, állítható betűk/színek/logó/fotók és közös renderelő. Öt adminfül, 390 px-es admin és 5/6/7/8 kártya mindkét TV-méretben sikeres. Hosszú névhez figyelmeztetés jelenik meg.
- T5: szerkeszthető vázlatlista, megváltoztathatatlan snapshot, TV-nkénti publikálás/rollback és prioritásos budapesti ütemezés. Éjfél és DST tesztelve.
- T6: külön árfrissítés, teljes ellenőrzött cache, atomi mutató és hibakezelés. Valódi DB-árfrissítés 23 965 ms, azonos videóelemmel. Az eredeti ár visszaállítása DB-readbackkel igazolt. Offline újraindítás és online visszatérés sikeres.
- T7: nem történt helyszíni pilot vagy élesítés. Nyitott: eredeti videók, fizikai TV-k/indulás, helyszíni árprofil, hoszt és 24 órás üzem.

Final review: független reviewer; a fontos cache-, időzítési és kiadáséletciklus-hibák kijavítva, új tesztekkel. A kvótahiba külön végrehajtott tesztágat kapott. A MariaDB implicit TIMESTAMP ON UPDATE hibája valódi helyi próbából derült ki, külön sémakompatibilitási javítást és readbacket kapott. A normál videóloop miatti watchdog-riasztás futási regresszióval javítva.

A részletes bizonyíték és korlátok: [acceptance.md](acceptance.md). A helyi worker ablak nélkül fut; rendszerindítási szolgáltatás vagy ütemezett feladat nem készült.

Task T1: complete — commit `5cd278b`; az árparitási és kapcsolódó checkout-tesztek sikeresek.

Task T2–T6: helyi megvalósítás — commit `2956f4b`; a teljes PHPUnit-futás 311 teszt / 1357 állítás, a teljes Node-futás 71 teszt, minden teszt sikeres. PHP-lint: 53 fájl, 0 hiba; Blade cache és JS-szintaxis sikeres. Az utolsó megismételt videó + DB-árpróba 23 921 ms és változatlan videóelem; az eredeti 2260 Ft-os ár újra visszaolvasva. A végső offline újraindítás és online helyreállás is sikeres.

## Folyamatos reklámműsor – 2026-11-09

A felhasználó aktív, folyamatosan futó reklámot kért az egyszer beúszó rács helyett, külön egyenkénti pizzabemutatóval. Megvalósult a közös `slideshow` jelenettípus, beállítható diánkénti idő, automatikus teljes jelenethossz, folyamatos fotómozgás/kiemelés/háttérfelirat és görgetett márkafelirat. A demo kilenc jelenetet játszik körbe három külön is választható műsorban; a 14 érvényes pizza 98 másodperces bemutatót kap. A termékadatok olvasása és árfrissítése változatlan közös árforrást használ, üzleti adatot nem írtunk.

A normál eszköz-jelenetváltás már a következő tartalom előkészítése után cserél, köztes tartalékfelvillanás nélkül; lejárat/visszavonás továbbra is azonnal leállítja az áras tartalmat. Független review nyomán javítva: az osztott nézet kártyaszám szerinti CSS-felülírása, a részben eltelt diánál megnyomott szünet hátralévő ideje és a szüneteltetett adminelőnézet árlejárata. Ezek végrehajtott regressziós tesztet kaptak.

Ellenőrzés: teljes PHPUnit 317 teszt / 1374 állítás; a JavaScript- és böngészős eredményeket az acceptance kiegészítése rögzíti. Az eredeti videófájlok továbbra sem állnak rendelkezésre, a harmadik referencia sem érkezett meg: pontos másolati egyezés nem állítható. Fizikai TV-próba és élesítés nem történt. A mostani módosítások helyi, még nem commitolt munkafa-változások a meglévő feature ágon.
