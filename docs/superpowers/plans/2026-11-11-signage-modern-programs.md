# Három saját TV-műsor – megvalósítási terv

**Cél:** pontosan hét helyi lejátszási lista: három videóklón, az általános reklám és három szerkeszthető modern változat.

**Megközelítés:** a meglévő Laravel-definíciók, katalóguskötések, admin és közös renderer kap három névvel választható megjelenést. Nincs új csomag, videórenderelés vagy rendelési adatváltozás. A frontend-design alapján választott saját irányok a felhasználó által kért kreatív változatok.

**Vizuális döntések:**
- Burger: sáfránysárga (#f3be3f), barna-fekete (#26190e), nagy, jobb oldali fotó; bal oldali név és egyenes árcímke. Hat termék, egyenként 8 másodperc.
- Étlap: hűvös fehér (#f1f4f0), mélyzöld (#155345), sötét szöveg (#132b24); két oszlopban vízszintes ételsorok. A hat eredeti kategória és kötelező választásaik megmaradnak.
- Pizza: mélyzöld (#153b30), arany (#e5b662), törtfehér információs mező (#f5f6f2); teljes pizzafotó és katalógusból jövő rövid leírás. Egyenként 10 másodperc, a nem rendelhető termékek kihagyásával. Új akcióállítást nem tesz.
- A címek helyi, kiadáshoz kötött Bebas Neue betűt kapnak; törzs és ár Arial. Nincs grunge, parázs vagy általános mozgó lábléc az új műsorokban. Kevés, lassú mozgás; csökkentett mozgás és szünet működik.

**Közös tulajdonosok:** `SignageDemoPrograms` = minták, `SignageSceneDefinition` = megjelenés/elrendezés validálása, `SignageReferenceAssets` = változatlan képek/fontok csomagolása, `renderer.js` = megjelenítés és stabil termékállapot, `modern.css` = saját vizuális változatok. Az admin natív selectje és meglévő mentése marad a szerkesztési út.

## Lépések

- [x] Regressziótesztek: hét külön program, egyedi kötésazonosítók, megfelelő modern elrendezés, termék-/árkötések és offline fontcsomag.
- [x] Három modern definíció, allowlist és szerkeszthető adminválasztás; hét program a helyi előnézetben.
- [x] Közös renderer bővítése, külön CSS és offline shell; árfrissítés/szünet ne indítsa újra a terméket.
- [x] Új listák létrehozása a helyi adatbázisban mentés után, tranzakcióban; a négy meglévő lista, kiadások és kijelzők megőrzésével. Az új minták egyszer kerüljenek be.
- [x] PHP/Node tesztek, lint, 1080p/720p böngészős mérés, vizuális csiszolás és független review. Használati leírás és DESIGN frissítése.

**Korlátok:** csak helyi fejlesztés; nincs commit, push, TV-publikálás vagy élesítés. A `.env`, katalógusadatok és korábbi kiadások változatlanok. Visszaállítás az újonnan létrehozott sorok elkülönítésével lehetséges; régi adatot nem törlünk.
