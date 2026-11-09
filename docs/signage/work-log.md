# Helyi végrehajtási napló

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
