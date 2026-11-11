# TV kijelzők – helyi használat

A fejlesztési változat a `feature/gctv-v1-local` ágon található. Apache, MySQL és a külön signage worker szükséges; FFmpeg és ffprobe legyen a PATH-on. Nincs új npm-csomag vagy buildlépés.

## Megnyitás

- Admin: `http://localhost/Gyros_City_Live/admin/signage`.
- Párosítható lejátszó: `http://localhost/Gyros_City_Live/signage/`.
- Bejelentkezés nélkül használható, csak helyi bemutató: `http://localhost/Gyros_City_Live/signage/demo`.
- Külön műsor: `?program=burger`, `?program=menu`, `?program=pizza`, `?program=general`, `?program=modern-burger`, `?program=modern-menu`, `?program=modern-pizza`. Kezelősáv nélküli megjelenítéshez `&fullscreen=1`.

A modul alapértelmezetten kikapcsolt. A `local` környezetben, loopback kapcsolaton tesztelhető; ez nem engedélyezi az éles vagy a hálózaton elérhető TV-ket. Az admin tulajdonosa eléri; munkatársnak külön **TV kijelzők** moduljog kell.

## Műsor összeállítása

1. A Médiatárban tölts fel JPEG/PNG/WebP fotót vagy MP4/MOV videót. A feldolgozás kész állapotáig a fájl nem publikálható. A videó néma H.264/yuv420p MP4-változatot és előnézeti képet kap; az eredeti megmarad.
2. A Jelenetek között válassz termékrácsot, kiemelt terméket, termékbemutatót / pizzavetítést, teljes képernyős médiát vagy videó + termékkártyák elrendezést. A termékbemutató legfeljebb 24 kártyát mutat egyenként, 5–20 másodperces idővel. Mentéskor a teljes időt a kártyák számából számítja, így minden termék sorra kerül. A sötét és világos téma mellett a színek, rendszerbetűk, méretek, térköz, logó, képfókusz és médiaoldal is állítható.
3. A terméksorhoz konkrét ételt és minden kötelező választást adj meg. Több ársor azonos **Közös kártyanévvel** kerül egy kártyára. Külön kijelzőfotó is választható. A név és az ár a katalógusból származik.
4. A Lejátszási listák kezdőlapon válassz mentett listát vagy kattints az Új lista gombra. Adj hozzá jeleneteket; a sorrend Fel / Le gombbal és húzással állítható, egy jelenet többször is szerepelhet. A Tartalom gomb külön lapon nyitja a jelenet szerkesztőjét. A jelenet mentése után térj vissza, és mentsd a listát is a TV-k frissítéséhez.
5. A Mentett lista előnézete a legutóbb mentett állapotot mutatja. Jelöld ki a frissítendő TV-ket, majd kattints a Mentés és TV-k frissítése gombra. Az aktuálisan ezt a listát használó TV-k alapból ki vannak jelölve. Az új csomag teljes letöltés és checksum-ellenőrzés után, jelenethatáron válik aktívvá. Kijelölt TV nélkül a Lista mentése csak vázlatot ment; a többi TV változatlanul folytatja a műsorát. Hibás tartalom esetén a lista és a TV-k hozzárendelése együtt visszaáll. A korábbi megjelenések külön lenyitható részből visszaállíthatók.
6. A TV-n megjelenő nyolcjegyű kódot az admin Kijelzők oldalán add meg. A kód tíz percig él és egyszer használható. A tartós token nem látható az adminban vagy az URL-ben.

A helyi könyvtárban hét lista és tizennyolc jelenet van: **Burger videómásolat** (7), **Étlap videómásolat** (8), **Pizza videómásolat** (9), **Általános reklám** (5), **Burger · saját modern** (10), **Étlap · saját modern** (11), **Pizza · saját modern** (12). Az általános műsor hat kedvencet mutat egyenként, hét másodpercig; tartalma és sorrendje a közös szerkesztőből módosítható. A régi QA- és próbalisták 2026-11-11-én kikerültek, majd a három modern vázlat külön hozzáadásként készült el. Mindkét helyi művelet előtt a két érintett tábla teljes mentése készült a webrooton és Giten kívül, a helyi `%USERPROFILE%\.codex\backups\Gyros_City_Live\2026-11-11-kiosk-*.json` fájlokba, SHA-256-visszaellenőrzéssel. A korábbi kiadások, TV-hozzárendelések, médiafájlok és időzítések megmaradtak; új TV-kiadás nem készült. Visszaállításhoz a mentett listák és jelenetek sorai használhatók; az időközben készült szerkesztéseket előtte egyeztetni kell.

A helyi `/signage/demo` hét külön műsort kínál, alapból az általános reklámot indítja. Az összevont próbaműsor megszűnt; a korábbi `program=all` link az általános reklámra vezet. A burgerklón egy 20 másodperces, 3×2-es lap; az étlapklón hat oldala 20/20/20/20/8/8 másodpercig fut; a pizzaklón 40 másodperces nyitóképpel és 20 másodperces egyedi termékbemutatókkal indul. A műsorválasztóval külön is indíthatók; szünet és következő jelenet gomb is van. A `?program=pizza&scene=1` közvetlenül az egyedi pizzákhoz ugrik. Ez a demo a mintadefiníciót mutatja; az adminban szerkesztett vázlat a mentett lista saját előnézetében látható.

A három **saját modern** műsor új elrendezés a klónok ételeivel és kötelező összeállításaival. A burger sárga plakátfelületen, teljes katalógusfotóval, nyolc másodpercenként mutatja a hat burgert (48 mp). Az étlap két oszlopban, vízszintes ételsorokkal mutatja a hat kategóriát (20/22/24/24/14/18 mp). A pizza mélyzöld felületen, világos név/leírás/ár mezővel, tíz másodpercenként vált (jelenleg hét rendelhető pizza, 70 mp). Új akciót nem hirdet. A modern **Megjelenés** választása az adminban a megfelelő elrendezést, színeket és léptetési időt állítja be; a tartalom utána ugyanott szerkeszthető. A helyi Bebas Neue font és az ármentes fotók a kiadáshoz kötött offline csomag részei, a saját CSS a shell része. Árfrissítéskor az aktív termék és fotó megmarad; szünet és csökkentett mozgás működik.

Az általános sablon **Reklám háttere** mezője hat lehetőséget ad: Grafit, Parázs, Faasztal, Bordó, Világos, Egyedi egyszínű. Választáskor olvasható alap-színpár és azonnali háttérminta segít; a háttér-, szöveg- és árszín utána külön állítható. A minta árai szemléltetők. A preset választása törli a korábban kiválasztott háttérmédia mezőjét; saját fotó vagy videó ezután újra választható. A videóklónok saját háttere megmarad, ott ez a választó rejtett. Az előnézetben a Háttér mező az éppen látható termék újraindítása nélkül vált; a `?program=general&backdrop=wood` link újratöltés után is megtartja. Az új háttér-CSS a lejátszó offline shelljébe is bekerül.

Az étlap a referenciában látható konkrét összeállításokhoz kötött: például sült burgonya és Pepsi/Mirinda, gyrosnál City öntet, a natúr csirkemellhez rizs. A végső árat a közös katalógusszolgáltatás számolja, nem a videó történeti felirata. A tíz referencia-pizzából jelenleg hét rendelhető; a Carbonara (341), Magyaros (338) és Songoku (329) letiltott állapota miatt kimarad. Állapotuk nem változott. A nyitókép az eredeti ajándék-palacsinta feliratot másolja; a helyi ajándékpromóciós tábla üres, rendelési akciót ez a fejlesztés nem aktivált.

A három videómásolat helyi vázlat, nincs kijelzőre publikálva. A **Megjelenés** mezővel a referenciastílus az általános sablonok mellett választható. A megfelelő ételhez automatikusan társul a referenciából származó ármentes kép; külön feltöltött kijelzőfotó ezt felülírja.

A referenciák parázs- és fotómozgást, beúszást, a pizzák betűnként érkező címeket és késleltetett árat kapnak. Az általános sablonok korábbi fejléc/lábléc/kiemelés viselkedése megmarad. Árfrissítéskor a kártya és az éppen látható termék megmarad. A böngésző csökkentett mozgás beállítása minden dekoratív animációt kikapcsol. Az admin lejátszásilista-előnézete is automatikusan körbefut; szüneteltethető és kézzel lapozható. Az egyszer betöltött adminelőnézet az árérvényesség végén tartalékra vált; friss árhoz újra kell tölteni. A helyi demo és az eszközplayer külön, 30 másodpercenként frissíti a katalógust.

A referenciaképek, hátterek, maszkok és fontok fizikai fájlneve teljes SHA-256-ot tartalmaz. A korábbi fájlok megmaradnak, a publikált jelenet saját verziójához kötődik. Az offline csomag ezeket is ellenőrzi és előtölti; az előző és az új kiadás képei nem írják felül egymást. Forrás és újragenerálás: `public/signage/reference-assets/README.md`, `scripts/signage/extract_reference_assets.py`.

## Folyamatos lejátszás és árak

Az árprofil **katalógusár**. A jelenlegi adatbázis a burgerhez külön csomagolási díjat is rendel; a helyben fizetendő árprofilt a pilot előtt igazolni kell. A signage nem ír katalógusárat és nem számol rendelést.

Online katalógusellenőrzés 30 másodpercenként történik, a videóelem megtartásával. Az offline árkorlát kijelzőnként 60–86 400 másodperc, alapból 1800; az ismert akcióhatár ezt rövidíti. Lejárt ár vagy bizonytalan óra esetén ármentes tartalék következik. Offline újraindításkor a tárolt shell és tartalék használható, a korábbi árfrissességet nem tekinti automatikusan hitelesnek.

Beégetett áras médiánál jelöld ezt a feltöltéskor. Nézd végig a teljes fájlt, válaszd ki a benne szereplő ételeket és a pontos összeállításokat tartalmazó mentett jeleneteket, majd igazold az egyezést és az érvényességi határt. Név-, ár-, referenciaár- vagy pénznemváltozáskor a média érvénytelenné válik. A videó képkockái ettől nem módosulnak.

A TV a hozzá kiválasztott listát sorban, végtelen ciklusban játssza, amíg a lejátszó fut. Nincs időzítési fül vagy a műsort hét nap után leállító határidő. A korábban tárolt időzítések megmaradnak az adatbázisban, de az új, folyamatos műsort nem írják felül. A 30 másodperces háttérfrissítés ugyanannál a műsornál megtartja a jelenetsorrendet; egy új kiadás jelenethatáron lép életbe. Az árak fent leírt lejárata továbbra is érvényes. Már megnyitott böngészős lejátszón a frissített kódhoz egyszeri újratöltés szükséges; a fizikai TV automatikus bekapcsolását ez a modul nem állítja be.

## Worker és hibakeresés

A helyi átadáskor a signage worker rejtett Windows-folyamatként fut. Kézi indítás a projekt gyökerében:

```powershell
php -d auto_prepend_file=tests/offline-prepend.php artisan queue:work signage --queue=signage --sleep=1 --tries=1 --timeout=1900
```

Naplók: `storage/logs/signage-worker.log`, `storage/logs/signage-worker-error.log`. Ezek nem kerülnek Gitbe. A konverzió a `signage_jobs` táblát használja, nem az alkalmazás alapértelmezett sync sorát. Sikertelen médiánál az adminból kérhető újrapróbálás. Workerleállás után indítsd újra a folyamatot; ez nem telepített automatikus rendszerindítási szolgáltatás.

A készülék percenként jelentkezik; három perc után az admin nem jelez friss kapcsolatot. Ez nem bizonyítja a fizikai TV-panel bekapcsolt állapotát. A hozzáférés visszavonása a következő online kérésnél leállítja a playert; új párosítás szükséges.

Hibás vagy blokkolt videó után a player másik jelenetet vagy tartalékot választ. Ugyanazt a hibás jelenetet legkorábban egy perc múlva próbálja újra; a normál videóloop nem számít elakadásnak.

Teljes böngészőadat-törlés után hálózat nélküli indulás nem garantált. A valódi TV-k kodek-, tárhely-, böngésző- és automatikus indulási képessége még nincs igazolva.

Keskeny adminnézetben a mentett listák a **Választott lista** legördülővel válthatók. Az asztali listaválasztó megmarad. A hibás mező kijavításakor a hozzá tartozó kliensoldali hibajelzés megszűnik.
