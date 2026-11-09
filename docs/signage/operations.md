# TV kijelzők – helyi használat

A fejlesztési változat a `feature/gctv-v1-local` ágon található. Apache, MySQL és a külön signage worker szükséges; FFmpeg és ffprobe legyen a PATH-on. Nincs új npm-csomag vagy buildlépés.

## Megnyitás

- Admin: `http://localhost/Gyros_City_Live/admin/signage`.
- Párosítható lejátszó: `http://localhost/Gyros_City_Live/signage/`.
- Bejelentkezés nélkül használható, csak helyi bemutató: `http://localhost/Gyros_City_Live/signage/demo`.
- Külön műsor: `?program=burger`, `?program=menu`, `?program=pizza`. Kezelősáv nélküli megjelenítéshez `&fullscreen=1`.

A modul alapértelmezetten kikapcsolt. A `local` környezetben, loopback kapcsolaton tesztelhető; ez nem engedélyezi az éles vagy a hálózaton elérhető TV-ket. Az admin tulajdonosa eléri; munkatársnak külön **TV kijelzők** moduljog kell.

## Műsor összeállítása

1. A Médiatárban tölts fel JPEG/PNG/WebP fotót vagy MP4/MOV videót. A feldolgozás kész állapotáig a fájl nem publikálható. A videó néma H.264/yuv420p MP4-változatot és előnézeti képet kap; az eredeti megmarad.
2. A Jelenetek között válassz termékrácsot, kiemelt terméket, termékbemutatót / pizzavetítést, teljes képernyős médiát vagy videó + termékkártyák elrendezést. A termékbemutató legfeljebb 24 kártyát mutat egyenként, 5–20 másodperces idővel. Mentéskor a teljes időt a kártyák számából számítja, így minden termék sorra kerül. A sötét és világos téma mellett a színek, rendszerbetűk, méretek, térköz, logó, képfókusz és médiaoldal is állítható.
3. A terméksorhoz konkrét ételt és minden kötelező választást adj meg. Több ársor azonos **Közös kártyanévvel** kerül egy kártyára. Külön kijelzőfotó is választható. A név és az ár a katalógusból származik.
4. Hozz létre lejátszási listát. A sorrend fel/le gombbal és húzással állítható. A lista és a jelenet szerkesztése vázlatot módosít; a már publikált megjelenés megmarad.
5. Nyisd meg a TV-előnézetet, ellenőrizd az összes jelenetet és a hosszú nevekre adott jelzést. Publikáláskor válaszd ki az érintett TV-ket. Az új csomag csak teljes letöltés és checksum-ellenőrzés után, jelenethatáron válik aktívvá.
6. A TV-n megjelenő nyolcjegyű kódot az admin Kijelzők oldalán add meg. A kód tíz percig él és egyszer használható. A tartós token nem látható az adminban vagy az URL-ben.

A helyi adatbázisban **Bemutató TV** / **TV V1 · helyi bemutató** néven öt jelenetes próbaműsor van, illetve **Videó + ár próba** néven külön, osztott képernyős összeállítás. A mintavideó saját, rövid tesztfájl; nem a hiányzó eredeti referenciavideók egyike. A böngészős QA külön megnevezett próba-vázlatai is megmaradtak.

A helyi `/signage/demo` a megadott három videó kioszkos változatát játssza körbe. A burger egy 20 másodperces, 3×2-es lap; az étlap hat oldala 20/20/20/20/8/8 másodpercig fut; a pizza 40 másodperces nyitóképpel és 20 másodperces egyedi termékbemutatókkal indul. A műsorválasztóval külön is indíthatók; szünet és következő jelenet gomb is van. A `?program=pizza&scene=1` közvetlenül az egyedi pizzákhoz ugrik.

Az étlap a referenciában látható konkrét összeállításokhoz kötött: például sült burgonya és Pepsi/Mirinda, gyrosnál City öntet, a natúr csirkemellhez rizs. A végső árat a közös katalógusszolgáltatás számolja, nem a videó történeti felirata. A tíz referencia-pizzából jelenleg hét rendelhető; a Carbonara (341), Magyaros (338) és Songoku (329) letiltott állapota miatt kimarad. Állapotuk nem változott. A nyitókép az eredeti ajándék-palacsinta feliratot másolja; a helyi ajándékpromóciós tábla üres, rendelési akciót ez a fejlesztés nem aktivált.

Három külön helyi vázlat is elérhető az adminban: **Videómásolat 2026-11-09 · burger / menu / pizza**, listaazonosítók **7 / 8 / 9**. Ezek nincsenek kijelzőre publikálva. Az új **Megjelenés** mezővel a referenciastílus az általános sablonok mellett választható. A megfelelő ételhez automatikusan társul a referenciából származó ármentes kép; külön feltöltött kijelzőfotó ezt felülírja.

A referenciák parázs- és fotómozgást, beúszást, a pizzák betűnként érkező címeket és késleltetett árat kapnak. Az általános sablonok korábbi fejléc/lábléc/kiemelés viselkedése megmarad. Árfrissítéskor a kártya és az éppen látható termék megmarad. A böngésző csökkentett mozgás beállítása minden dekoratív animációt kikapcsol. Az admin lejátszásilista-előnézete is automatikusan körbefut; szüneteltethető és kézzel lapozható. Az egyszer betöltött adminelőnézet az árérvényesség végén tartalékra vált; friss árhoz újra kell tölteni. A helyi demo és az eszközplayer külön, 30 másodpercenként frissíti a katalógust.

A referenciaképek, hátterek, maszkok és fontok fizikai fájlneve teljes SHA-256-ot tartalmaz. A korábbi fájlok megmaradnak, a publikált jelenet saját verziójához kötődik. Az offline csomag ezeket is ellenőrzi és előtölti; az előző és az új kiadás képei nem írják felül egymást. Forrás és újragenerálás: `public/signage/reference-assets/README.md`, `scripts/signage/extract_reference_assets.py`.

## Árak és időzítés

Az árprofil **katalógusár**. A jelenlegi adatbázis a burgerhez külön csomagolási díjat is rendel; a helyben fizetendő árprofilt a pilot előtt igazolni kell. A signage nem ír katalógusárat és nem számol rendelést.

Online katalógusellenőrzés 30 másodpercenként történik, a videóelem megtartásával. Az offline árkorlát kijelzőnként 60–86 400 másodperc, alapból 1800; az ismert akcióhatár ezt rövidíti. Lejárt ár vagy bizonytalan óra esetén ármentes tartalék következik. Offline újraindításkor a tárolt shell és tartalék használható, a korábbi árfrissességet nem tekinti automatikusan hitelesnek.

Beégetett áras médiánál jelöld ezt a feltöltéskor. Nézd végig a teljes fájlt, válaszd ki a benne szereplő ételeket és a pontos összeállításokat tartalmazó mentett jeleneteket, majd igazold az egyezést és az érvényességi határt. Név-, ár-, referenciaár- vagy pénznemváltozáskor a média érvénytelenné válik. A videó képkockái ettől nem módosulnak.

Az időzítés budapesti időt, heti napokat, dátumhatárokat és prioritást használ. A magasabb prioritás nyer; azonos prioritású átfedést nem lehet menteni. Az éjfélbe átnyúló ablak a kezdőnaphoz tartozik. A player legfeljebb hét napra kap érvényes időzítést, utána tartalékra vált.

## Worker és hibakeresés

A helyi átadáskor a signage worker rejtett Windows-folyamatként fut. Kézi indítás a projekt gyökerében:

```powershell
php -d auto_prepend_file=tests/offline-prepend.php artisan queue:work signage --queue=signage --sleep=1 --tries=1 --timeout=1900
```

Naplók: `storage/logs/signage-worker.log`, `storage/logs/signage-worker-error.log`. Ezek nem kerülnek Gitbe. A konverzió a `signage_jobs` táblát használja, nem az alkalmazás alapértelmezett sync sorát. Sikertelen médiánál az adminból kérhető újrapróbálás. Workerleállás után indítsd újra a folyamatot; ez nem telepített automatikus rendszerindítási szolgáltatás.

A készülék percenként jelentkezik; három perc után az admin nem jelez friss kapcsolatot. Ez nem bizonyítja a fizikai TV-panel bekapcsolt állapotát. A hozzáférés visszavonása a következő online kérésnél leállítja a playert; új párosítás szükséges.

Hibás vagy blokkolt videó után a player másik jelenetet vagy tartalékot választ. Ugyanazt a hibás jelenetet legkorábban egy perc múlva próbálja újra; a normál videóloop nem számít elakadásnak.

Teljes böngészőadat-törlés után hálózat nélküli indulás nem garantált. A valódi TV-k kodek-, tárhely-, böngésző- és automatikus indulási képessége még nincs igazolva.
