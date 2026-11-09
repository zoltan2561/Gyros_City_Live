# Gyros City TV-kiosk – Implementation Plan
## Codex projekt- és megvalósítási terv · V1 · 2026. november 9.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A Gyros City éttermi TV-in központilag szerkeszthető, folyamatosan lejátszott ételfotók, videók és termékadatokból frissülő digitális étlap jelenjen meg, kiváltva a pendrive-os tartalomcserét.

**Architecture:** A meglévő Gyros_City_Live alkalmazás kapjon TV-admin modult, elkülönített kijelzőlejátszót és szűk, csak a megjelenítéshez szükséges adatokat kiadó eszköz-API-t. A lejátszó előre letöltött médiával működjön, miközben a termékadatokat és az árakat külön frissíti. Egy közös backend és médiatároló elegendő; minden TV saját hozzárendelést kap.

**Tech Stack:** A vizsgált repó Laravel 9 / PHP ^8.0.2 alapú, Blade-felületekkel, Laravel Mix 6 konfigurációval és meglévő PHP/JavaScript tesztekkel. A konkrét telepített PHP-, Laravel- és böngészőverziót az induló felmérés rögzíti. A terv ehhez illeszkedő Blade + CSS + JavaScript modult és FFmpeg-alapú médiafeldolgozást javasol.

**Spec:** E dokumentum 1–8. fejezete a funkcionális és műszaki specifikáció; a 9–11. fejezet a végrehajtási és átadási terv. Önállóan átadható Codexnek.

**Célprojekt:** PRJ-011 / Gyros City. **Tervazonosító:** GCTV-V1-20261109; ez dokumentumazonosító, nem létrehozott feladatkezelő-rekord.

**Eredménycsatorna:** [SzoftLab / #u-gyroscity](https://szoftlabworkspace.slack.com/archives/C0C8Q5J0FCH).

**Állapot:** Tervezési átadás. A leírt képességek megvalósítandó követelmények, a tesztek tervezett ellenőrzések. A terv összeállítása során alkalmazáskód-módosítás, futó rendszer tesztelése és élesítés nem történt. Az itt választott alapértékek tervezői javaslatok, nem korábban jóváhagyott üzleti döntések.

## Global Constraints

- A meglévő Gyros City kódbázishoz és árforráshoz kell illeszkedni; ne induljon új teljes éttermi admin vagy framework-migráció.
- A kioskon megjelenő név és ár stabil katalógusazonosítóhoz kötődjön. Szabad szöveges ár nem lehet az automatikus termékkártya forrása.
- A videóba/képbe beégetett ár statikus. Az automatikus ár HTML/adatréteg legyen.
- A TV nem használhat adminbejelentkezést, és nem kap rendelés-, vevő- vagy fizetési adatot.
- A player elsődleges nézete 1920×1080, 16:9; 1280×720-on is ellenőrizendő. A tényleges TV-k és lejátszók még felmérendők.
- Javasolt V1-alapértékek: katalógusellenőrzés 30 másodperc; online árfrissítés célja legfeljebb 60 másodperc; állapotjelentés 60 másodperc; kapcsolatvesztett jelzés 180 másodperc után.
- Az offline árérvényesség javasolt alapértéke 1800 másodperc, legfeljebb a következő ismert ár-/akcióváltásig. Lejárat után ármentes tartaléktartalom kell.
- A beállítások vázlatból, előnézet után publikálhatók; az új médiakiadás csak teljes letöltés és ellenőrzés után válthat aktívra.
- Az időzítés Europe/Budapest szerint működjön; az API abszolút időpontjai UTC-ben utazzanak.
- A meglévő rendelési/fizetési folyamatot célzott regressziós tesztek védjék. A TV-funkció nem számolhat újra rendelést és nem írhat katalógusárat.

## Review Focus

1. Kötelező fizetős választás, menü vagy több ár egy kártyán: a bemutatott összeállítás és az ár ugyanazt jelentse.
2. Akció időhatára, offline lejárat, pontatlan készülékóra: elavult ár ne maradjon korlátlanul a képernyőn.
3. Félbeszakadt videóletöltés, betelt/törölt cache, hibás videó: a működő lejátszás és a tartalék legyen megőrizhető.
4. Eltűnt vagy elfogyott termék, üres kategória, hiányzó fotó: a layout maradjon olvasható, helytelen ár ne jelenjen meg.
5. Eszközök közötti adatszivárgás, adminjog hiánya, meglévő service worker: a kijelzőmodul legyen elkülönített és visszavonható.

---

## 1. Kiinduló helyzet és ellenőrzött források

### 1.1. Jelenlegi használat

PZ leírása szerint az étteremben lévő TV-k pendrive-ról, előre elkészített videókat játszanak. Az új rendszerben a tartalom távolról módosítható, a termékhez tartozó ár pedig automatikusan átvehető legyen.

Három említett referencia helyett **két videó érkezett meg**. A harmadik nem akadálya a tervnek; beérkezésekor további mintaként kell áttekinteni.

| Referencia | Ellenőrzött technikai adatok | Megjelenési minta |
|---|---|---|
| Orange Black Grunge Burger Prom.MP4 | 19,968 s; 1920×1080; H.264, 30 fps; AAC sztereó; 31 414 094 bájt | 3×2 burgerkártya; sötét, parázsszerű mozgó háttér; sárga név és ár |
| termékek2026.10.30.sütlac off.MOV | 96,245 s; 1280×720; H.264, kb. 30 fps; AAC sztereó; 117 119 584 bájt | Hat termékoldal, 40 kártya; 3×2, 4×2, 4+3 és 2+3 rácsok; magenta címkék; eltérő hátterek |

Az elemzés időbélyeges mintaképekből és médiametaadatokból készült; a hang tartalmát nem vizsgáltuk. A MOV 0 s-es oldalán burgerek, 20 s-nél menük, 44 s-nél gyrosok, 64 s-nél frissensültek, 84 s-nél saláták/köretek/desszertek, 88 s-nél kebabok láthatók. A salátás oldal a minták szerint a többihez képest rövid ideig jelenik meg.

A referenciaárak **nem igazolt aktuális éttermi árak**, ezért azokat nem szabad seedként vagy új árforrásként beírni. A két videóban a név és az ár is a képkockák része. A gyros sajttal/sajt nélkül és a 4/6 darabos nuggets különösen indokolja az egy kártyán megjeleníthető több ár támogatását.

A forrásvideók az eredeti ChatGPT-beszélgetés mellékletei. A Codexnek a munka kezdetén ellenőriznie kell, hogy saját környezetében valóban elérhetők-e; e terv önmagában nem másolja be őket a repóba vagy a Slackbe. A hozzáférés hiánya a végső referencia-egyezés ellenőrzését korlátozza, a modul alapjain végzett munkát nem.

### 1.2. Meglévő alkalmazás

Forrásként a [zoltan2561/Gyros_City_Live](https://github.com/zoltan2561/Gyros_City_Live) repó `main` ágának **f1645a895691c84cc7ae21386298ae991ee11e61** állapotát olvastuk. Ez a 2026. október 23-i kódállapot; az éles környezet verzióját nem igazolja.

Ellenőrzött integrációs pontok:

- `app/Models/Item.php`: termék, termékképek, láthatóság és rendelhetőség.
- `app/Services/CatalogPricing.php`: alapár, aktuális Top Deal, feltétek és extrák.
- `app/Services/CheckoutPricing.php`: külön adószámítás; a termékkártya ára nem nevezhető automatikusan a teljes fizetendő összegnek.
- `app/Support/AdminPermissions.php`: az adminmodulok és controller actionök közös jogosultságtérképe.
- `resources/views/admin/theme/sidebarcontent.blade.php`: adminmenü.
- `app/Helpers/helper.php`: pénznemformázás, időfüggő Top Deal és meglévő képútvonalak.
- A termékképekhez örökölt `ASSETSPATHURL`-alapú útvonalkezelés tartozik. A README szerint a fájlok helye `storage/app/public/admin-assets/images/item/`.
- A PWA-csomag szerepel a függőségek között, de a vizsgált tracked forrásban nincs kész, ellenőrzött offline kiosk-lejátszó. A telepített service workerek külön ellenőrizendők.

Források: [függőségek](https://github.com/zoltan2561/Gyros_City_Live/blob/f1645a895691c84cc7ae21386298ae991ee11e61/composer.json), [katalógusárazás](https://github.com/zoltan2561/Gyros_City_Live/blob/f1645a895691c84cc7ae21386298ae991ee11e61/app/Services/CatalogPricing.php), [termékmodell](https://github.com/zoltan2561/Gyros_City_Live/blob/f1645a895691c84cc7ae21386298ae991ee11e61/app/Models/Item.php), [adminjogok](https://github.com/zoltan2561/Gyros_City_Live/blob/f1645a895691c84cc7ae21386298ae991ee11e61/app/Support/AdminPermissions.php).

## 2. Megközelítés és V1-határ

| Lehetséges megoldás | Előny | Következmény |
|---|---|---|
| **A meglévő admin TV-modulja + böngészős player – javasolt** | Egy termékkatalógus, egy árforrás, a dolgozók meglévő belépése | A jelenlegi kódhoz és jogosultságkezeléshez kell illeszteni |
| Külön signage alkalmazás katalógus-API-val | Később több független ügyfelet is kiszolgálhat | Új admin, szinkronizáció és üzemeltetési feladat |
| Új videó renderelése minden árváltozáskor | A régi USB-módszerhez hasonló eredmény | Lassabb árfrissítés, újrarenderelés és nagy fájlok mozgatása |

A terv az első változatot javasolja, mert a kért TV-kezelést közvetlenül a már használt termékekhez kapcsolja. A felépítés később kiemelhető maradjon, de V1-ben ne készüljön többügyfeles SaaS.

**V1 eredménye:** médiatár, négy jelenettípus, két vizuális téma, TV-nkénti playlist, egyszerű időzítés, automatikus termékadat-frissítés, párosítás, offline tartalék és állapotjelzés.

**Későbbi bővítés:** szabad vásznas szerkesztő, videófal és képkockapontos több-TV szinkron, AI-tartalomgenerálás, érintős rendelés/fizetés, távoli operációsrendszer-parancsok, univerzális Smart-TV alkalmazás, többtelephelyes önálló termék.

## 3. Az admin és a napi használat

Az admin új főmenüje: **TV kijelzők**.

### 3.1. Felületek

1. **Kijelzők:** név, hely, hozzárendelt playlist, felbontás, utolsó kapcsolat, aktív tartalom- és katalógusverzió, letöltési állapot, utolsó hiba.
2. **Médiatár:** videó/fotó feltöltése, bélyegkép, hossz, felbontás, feldolgozás állapota, hol használjuk, beégetett ár jelölése.
3. **Jelenetek és témák:** sablon kiválasztása, termékek és képek hozzárendelése, kinézet, megjelenési idő.
4. **Lejátszási listák:** jelenetsorrend húzással, időtartam, videó végigjátszása, előnézet, vázlat, publikálás, előző megjelenési verzió visszaállítása.
5. **Időzítés:** alapértelmezett lista és heti/időszakos felülírások.

A publikálás előtt látszódjon az érintett TV-k listája, a hibás termékhivatkozás, a hiányzó kép és a még feldolgozás alatt álló videó. Egyetlen TV-n kipróbálható legyen az új tartalom a többi kijelző módosítása nélkül.

### 3.2. Tipikus munkamenet

- Az admin létrehoz egy „Pult bal” kijelzőt, és a TV képernyőjén megjelenő egyszeri kóddal párosítja.
- Készít egy „Burgerek” jelenetet: kiválaszt hat katalógusterméket, egy hátteret és a sötét témát.
- A termékfotó és a név automatikusan betöltődik; kijelzőre szánt, másik fotó is megadható.
- A playlistbe bekerül ez a jelenet, egy reklámvideó és egy gyrosos oldal.
- Előnézet után publikálja a „Pult bal” kijelzőre.
- Később a termékadminban módosítja az árat. A TV automatikusan átveszi, a playlistet és a videókat nem kell újraszerkesztenie.

**Vázlat és élő adat külön kezelendő:** a layout-vázlat csak publikáláskor kerül TV-re; az éles katalógusár változása a már publikált termékkártyákon automatikus.

## 4. Megjelenés és testreszabhatóság

### 4.1. Négy jelenettípus

| Típus | Tartalom | Fő beállítások |
|---|---|---|
| Teljes képernyős média | Feltöltött videó vagy fotó | Teljes kép / kitöltés, fókuszpont, időtartam, végigjátszás |
| Termékrács | 5–8 termék, oldalanként; 3×2 vagy 4×2 alap, 5/7 terméknél rendezett üres helyek | Termékek, sorrend, háttér, címkék, képméret, név-/árméret |
| Kiemelt termék | Egy nagy ételfotó, név, aktuális ár, opcionális rövid felirat | Háttérkép/videó, képhely, szövegelrendezés |
| Osztott képernyő | Egyik oldalon videó/fotó, másikon 1–3 élő termékkártya | Bal/jobb média, javasolt 60/40 arány, terméksorrend |

A termékrács és a kiemelt termék ár nélküli videóhátteret is használhasson. A videó és a termékadat tehát egyszerre is megjelenhet, nem csak egymás után.

**Két induló téma:**

- **Sötét / burger:** fekete–sötétvörös alap, meleg sárga/narancs címkék, enyhe mozgó háttér.
- **Világos / étlap:** fehér vagy szürke alap, magenta címkék, a jelenlegi termékes videó hangulatával.

Állítható háttérszín, média, szövegszín, címkeszín, logó, helyileg kiszolgált betűkészlet, név-/árméret, térköz, kép illesztése, megjelenési idő és egyszerű áttűnés. Nyers HTML/JavaScript beillesztésére nincs szükség.

### 4.2. TV-re tervezett olvashatóság

- 1920×1080-as tervezési alap; alapból 5% belső biztonsági margó.
- Kiinduló méretek: terméknév legalább 32 px, ár legalább 44 px 1080p-n; a cél-TV-n, a valós nézési távolságból ellenőrizni kell.
- Hosszú név legfeljebb két sorban; túlcsorduláskor a szerkesztő jelezzen, ne vágja le csendben.
- Termékfotónál alapértelmezett teljes kép, ne vágja le a burgert vagy a tányért. Dekoratív háttérnél használható kitöltés.
- Az ár pénznemmel jelenjen meg, a meglévő formázási beállítás szerint.
- Mozgás: visszafogott belépés/áttűnés, nincs folyamatosan ugráló ár vagy olvashatatlan ticker.
- A képes jelenet javasolt alapideje 15 másodperc; állítható. A videó alapból egyszer végigfut. Ne legyen az olvashatóságot akadályozó, néhány másodperces véletlen lapváltás.
- V1-ben fekvő TV az igazolandó cél. Az álló mód külön ellenőrzött layout legyen, ha a helyszínen szükség van rá.

## 5. Termékek és árak – az üzleti szabály

### 5.1. Egy hiteles árforrás

A termékkötés tárolja az `item_id` értéket, és ha szükséges, a konkrét feltét-/extra-összeállítást. A képernyő nem a név alapján keresi ki a terméket. Átnevezés után is ugyanarra az ételre mutat.

A jelenlegi kódban:

- `item.price` az alap eladási ár.
- `original_price` az áthúzható referenciaár; `discount_percentage` értékét nem kell még egyszer levonni.
- A Top Deal az idő és a beállítások alapján módosíthatja az aktuális árat.
- A teljes `CatalogPricing::selection()` kötelező feltéteket is ellenőriz, ezért nem általános, üres paraméteres árlekérdező.
- A `Variation` modell léte önmagában nem bizonyít működő variánsárazást az aktuális vásárlási útvonalon.
- Az adó a checkoutban külön is hozzáadódhat. A termékkártya árának és a helyben fizetendő árnak az egyezését valós konfigurációból kell ellenőrizni.

**Megvalósítás:** az alapár/Top Deal számítást közös, mellékhatásmentes metódus szolgáltassa, amelyet a meglévő árazás és a `SignageCatalog` is használ. A forrásban lévő, esetleg szokatlan árazási szabályt ne változtasd meg észrevétlenül a TV-projekt során. Az áthúzott ár és a kedvezmény kezdete/vége ugyanazt jelentse a két felületen.

Az induló árprofil neve legyen **katalógus megjelenítési ár**. A helyszíni pilot előtt egy egyszerű és egy összetett terméken ellenőrizni kell, hogy ez a kívánt éttermi ár-e. Ha külön helyben fogyasztási ár szükséges, azt explicit árprofilhoz kell kötni; nem lehet egy feltételezett adókulccsal vagy másolt kézi ártáblával helyettesíteni.

### 5.2. Több ár, menük és választások

Egy termékkártyának több árazott sora lehessen, például „4 db” és „6 db”, vagy „Alap” és „Sajttal”. Minden sor külön hiteles katalógustételre vagy ellenőrzött összeállításra hivatkozik.

A V1-ben:

- Külön termékként tárolt menü saját `item_id`-t kap; ára nem az összetevők automatikus összege.
- Ha a katalógus feltétekkel oldja meg a különbséget, teljes, érvényes választás kerüljön a kötésbe.
- Kötelező fizetős választás esetén a nem meghatározott összeállítást ne lehessen félrevezető fix árral publikálni. Válassz konkrét konfigurációt.
- „-tól” ár csak igazolt minimumszámítás esetén támogatható; ennek hiányában a V1 konkrét áras sorokat kér.
- Variánsazonosító csak akkor használható, ha az audit bizonyítja, hogy az adott termék tényleges árforrása.
- A végső formázott árat a backend adja; a player ne másolja le az árazási logikát.

### 5.3. Elérhetőség

Az `Item::visibleOnMenu()` és az `is_orderable` együttesen vizsgálandó. Inaktív/törölt kategóriából, eltűnt termékből ne készüljön aktív ajánlat.

A javasolt alapműködés az elfogyott/szüneteltetett termék kihagyása és a rács újrarendezése. Opcionális mód: ár nélküli „Átmenetileg nem elérhető” felirat. Üres jelenetnél következő jelenet vagy ármentes tartalék következzen. A player nem találhat ki saját napi készlet-visszaállítást.

### 5.4. Frissülés és offline érvényesség

A katalógusvégpont a pillanatnyilag ténylegesen számított értékekből képezzen verziót. Az `updated_at` vagy a playlist módosulása önmagában nem elég, mert egy akció az idő múlásával is lejárhat.

- Online: 30 másodperces ellenőrzés; a már látható kártya ára is legfeljebb 60 másodpercen belül frissüljön.
- Az árfrissítés nem várhat egy nagy, új videó letöltésére.
- A teljes katalóguspillanatkép egyszerre cserélődjön; ne keveredjen régi név, új ár és másik variáns.
- Az érvényesség a szerver idejéből, az utolsó hiteles ellenőrzésből, az 1800 másodperces alapkorlátból és a legközelebbi ismert árváltásból származik.
- Nem változott adatokra adott sikeres, hiteles válasz megújíthatja a frissességet; hibás kérés vagy helyi cache-olvasás nem.
- Lejáratkor a player azonnal eltávolítja az áras jelenetet, és előre tárolt, ármentes tartalékra vált. Ez a beégetett áras médiára is vonatkozik.
- Újraindítás és hálózati visszatérés után újraellenőrzés kell. Visszafelé állított vagy nem megbízható készülékóra nem hosszabbíthatja meg az árérvényességet.

Az 1800 másodperc üzleti kompromisszum, adminból állítható 60–86 400 másodperc között; a következő ismert akcióhatár ettől függetlenül rövidíti. Kapcsolat nélkül a központban frissen módosított árat technikailag nem lehet megismerni.

### 5.5. Régi, beégetett áras videók

A médiatár kérdezze meg: „Tartalmaz beégetett árat?” Az itt vizsgált két referencia esetén a válasz igen.

Az ilyen média kézi frissítésű. Publikálásához az admin összeköti a benne szereplő ajánlatokat a katalógussal, és rögzíti az ellenőrzött árpillanatképet. Az érintett ár megváltozása, az ellenőrzött kampány lejárata vagy az offline árérvényesség vége után a player kihagyja a videót, és jelzi a cserét igénylő tartalmat az adminnak.

Ha a videó árai nem térképezhetők fel megbízhatóan, előnézetben használható, élő dinamikus árlistaként nem minősíthető késznek. A hosszú távú megoldás az eredeti, szöveg nélküli fotók és hátterek használata natív termékkártyákon. Az OCR vagy a régi árra rárajzolt új szám nem V1-árforrás.

## 6. Lejátszó, eszközök és időzítés

### 6.1. Eszközkezelés

A párosítás egyszer használatos, 10 percig élő kóddal történjen, próbálkozáskorláttal. A kijelző saját, visszavonható eszközhitelesítőt kapjon. A szerver oldalon a titok csak hash formában tárolható.

A token nem kerülhet URL-be, Slackbe vagy naplóba. A player csak saját publikált hozzárendelését, a hozzá szükséges katalógusadatokat és médiát olvashatja, valamint saját állapotát jelentheti. A tartós tárolás és a média-lekérés hitelesítését a választott playerkörnyezetben kell megvalósítani; adminsession nem használható kerülőútként.

A visszavont eszköz következő hitelesített kérésére azonnal tiltás érkezzen. Kapcsolat nélkül a távoli visszavonásról a player nem értesülhet; ez az offline működés határa.

### 6.2. V1-környezet

Elsőként egy konkrét, felügyelhető Chromium-alapú lejátszókonfigurációt kell igazolni. A TV saját böngészője akkor választható, ha ugyanazokat az automatikus indulási, média- és offline teszteket teljesíti. A terv nem állítja, hogy minden Smart-TV alkalmas.

A TV bekapcsolása, HDMI-bemenete, a készülék alvásának tiltása és az OS-ből induló kiosk mód külön üzemeltetési beállítás. A weboldal önmagában nem garantál áramkimaradás utáni teljes visszaállást.

A videó alapból néma. A `play()` sikerét/hibáját kezelni kell, blokkolás vagy dekódolási hiba esetén tartalékkép következzen. A néma és hangos automatikus lejátszás eltérő böngészőszabályok alá esik. [W1]

### 6.3. Playlist és ütemezés

Minden TV-nek egy alaplistája és több időzített felülírása lehet. V1-szabály:

1. Az adott pillanatban aktív, időzített listák közül a legmagasabb explicit prioritás nyer.
2. Azonos prioritású, átfedő szabályt publikálás előtt hibának kell jelezni.
3. Aktív felülírás hiányában az alaplista következik.
4. Érvényes tartalom hiányában a tartalék jelenet fut.

Kezelni kell heti napokat, dátumintervallumot és éjfélbe átnyúló időablakot. Időzónaváltás helyett a Europe/Budapest szabályai érvényesüljenek, óraátállás-teszttel. A következő 7 nap időzítését és szükséges médiáját lehet előre tárolni; a leírás adjon külön `schedule_valid_until` értéket. Ennek lejárta után a tartalék legyen az alap, ne egy bizonytalanul aktív kampány.

Az azonos playlist több TV-n használható, de V1-ben nem követelmény a képkockapontos szinkron.

### 6.4. Állapotjelzés

60 másodpercenként: playerverzió, aktív/pending kiadás, katalógusverzió, utolsó sikeres szinkron, aktuális jelenet, médiahiba, becsült tárhely és offline státusz. Három perc kapcsolatcsend után az admin „Nincs friss kapcsolat” jelzést mutasson.

Ez alkalmazásállapot, nem bizonyítja, hogy a fizikai TV-panel bekapcsolt. A kezelő lássa, hogy a publikált tartalom már aktív-e vagy még letöltés alatt áll.

## 7. Médiafeldolgozás és offline működés

### 7.1. Feltöltés és feldolgozás

V1-bemenetek: JPEG, PNG, WebP, MP4, MOV. A fájlkiterjesztés mellett a tényleges tartalom is ellenőrizendő. Javasolt, konfigurálható induló korlát: videó 500 MiB / 10 perc, kép 20 MiB. Ez alkalmazásszabály; a tárhely, PHP és proxy limitjeit hozzá kell igazítani.

A feltöltések állapotai: feltöltve → vizsgálat/feldolgozás → kész vagy hibás. Csak kész, ellenőrzött származék publikálható. A konverzió háttérfeladat legyen, ne hosszú admin HTTP-kérés.

Induló lejátszási profil: MP4, H.264, yuv420p, legfeljebb 1920×1080, 30 fps, néma; `+faststart` MP4-indexeléssel. A képarány megőrzendő, a 720p forrást nem kell kötelezően felnagyítani. A `faststart` az indexet a fájl elejére helyezi; a tényleges dekódolhatóságot a célkészüléken kell igazolni. [W5]

Az eredeti feltöltés maradjon meg; a player ellenőrzött származékot kapjon. Legyen poster, hossz, felbontás, méret, checksum, feldolgozási hiba és a hivatkozó jelenetek listája. Használatban lévő médiát ne lehessen úgy eltávolítani, hogy publikált kiadás sérüljön.

FFmpeg/ffprobe és háttérfolyamat elérhetőségét a valódi hoszton fel kell mérni. Ha nincs konverziós környezet, a hiány külön megvalósítási tétel; tetszőleges MOV feltöltését nem szabad kész kompatibilitásként jelenteni.

### 7.2. Elkülönített player és gyorsítótár

Javasolt playerútvonal: `/signage/`, saját service workerrel és `gc-signage-*` nevű cache-ekkel. Az admin és a rendelési oldal nem kerülhet ebbe az offline cache-be. Meglévő globális service workert nem szabad vakon törölni vagy felülírni.

A service workerhez normál kiszolgálásban HTTPS szükséges. [W4] A kiválasztott megoldás töltse le a teljes médiafájlt előre, és kezelje a videó HTTP Range/206 kéréseit. A videó lejátszás közbeni részleges letöltése nem elég offline előkészítésnek. [W2]

Az offline csomag tartalmazza a playert, a témát, a betűkészletet, az ármentes tartalékot és a szükséges képeket/videókat. Külső CDN-függőség nem maradhat a lejátszáshoz szükséges útvonalon.

### 7.3. Biztonságos kiadásváltás

- A `layout_revision` megváltoztathatatlan jelenet-/playlist-/ütemezés-leírást és média-checksumokat azonosít.
- A `catalog_revision` külön változik; árfrissítéshez nem kell videót újratölteni.
- Az új layout összes szükséges fájlja ideiglenes helyre töltődik, ellenőrzéssel.
- Csak teljes, használható csomagnál cserélődik az aktív verziómutató.
- Közben az előző teljes kiadás fut; a jelenlegi termékkötések árai tovább frissülnek.
- Médiacsere normál jelenethatáron történhet. Árlejárat, hibás ajánlat vagy visszavonás azonnali kezelést igényel.
- Rollback csak a megjelenési konfigurációt állítja vissza; nem állíthat vissza régi katalógusárat.
- A tárhely ellenőrzése a régi és az új csomag egyidejű helyigényével számoljon. Kvótahibánál a működő aktív cache maradjon meg.
- Cache-sérüléskor ármentes helyi tartalék következzen, ha megvan. Teljes böngészőadat-törlés utáni, hálózat nélküli indulás csak külön helyi alkalmazáscsomaggal garantálható.

A böngészőtárhely alapból best-effort, tartóssági kérése elutasítható. A státuszban a tényleges tárolási képesség szerepeljen, ne feltétel nélküli offline ígéret. [W3] Ha a kiválasztott eszköz ezen elbukik, a pilothoz tartós helyi playercsomag szükséges.

## 8. Adatmodell, API és fájltérkép

Az alábbiak **új, javasolt komponensek**, nem már létező fájlok. Az audit a repó aktuális szervezéséhez igazíthatja őket a felelősségek megtartásával.

### 8.1. Tartós adatok

| Entitás | Tárolt lényeg |
|---|---|
| SignageScreen | Név, hely, eszközazonosító, tokenhash, hozzárendelés, playerképesség, utolsó heartbeat |
| SignageMedia | Eredeti/származék útvonal, MIME/codec, méret/hossz, checksum, állapot, beégetett árhoz tartozó kötés és ellenőrzés |
| SignageScene | Típus, validált megjelenési konfiguráció, termékkötések, időtartam, téma |
| SignagePlaylist | Rendezett jelenetek, vázlat és publikált verzió |
| SignageSchedule | Kijelző, playlist, hét-/dátum-/időablak, prioritás |
| SignageRelease | Megváltoztathatatlan publikált snapshot, létrehozó, időpont, aktív/pending használat |
| SignageAudit | Ki, mikor, melyik kijelzőn mit publikált/vont vissza/párosított |

A vázlat mentése ne írja át a már publikált snapshotot. A termékeket a meglévő katalógus azonosítja; a signage nem tart fenn szerkeszthető árduplikátumot. Törölt termékre maradt kötést felismerhető hibaként kell kezelni.

### 8.2. Közös interfészek

- `CatalogPricing::baseQuote(Item $item): array`: a meglévő alapár/Top Deal logika mellékhatásmentes közös része; legalább aktuális ár és adóhivatkozás. A selection továbbra is saját választásvalidációt végez.
- `SignageCatalog::snapshot(array $bindings, DateTimeImmutable $now): array`: név, elérhetőség, aktuális/formázott ár, referenciaár, kép, konfigurációjelölés, érvényesség és verzió.
- `SignagePublisher::publish(int $playlistId, array $screenIds, int $actorId): array`: ellenőriz és snapshotot publikál; a nagy fájlok nem ebben a kérésben konvertálódnak.
- `SignageManifest::forScreen(int $screenId, DateTimeImmutable $now): array`: séma-/playerverzió, tartalomverzió, média-checksumok, időzítés, tartalék.
- Player: `stageRelease(manifest)`, `activateRelease(revision)`, `applyCatalog(snapshot)`, `selectScene(now)`. Ezek külön tesztelhető felelősségek.

A `bindings` minden sora: helyi kötésazonosító, `item_id`, opcionális ellenőrzött választások, kijelzési címke és fotófelülírás. A többáras kártya több kötésből áll. A pénzérték az API-n fix pontosságú decimális szöveg és formázott felirat; kliensoldali lebegőpontos újraszámítás nincs.

### 8.3. Eszköz-API

Javasolt útvonalak a `/signage/api/v1/` alatt, önálló eszközguarddal. A konkrét Laravel route-regisztrációt és middleware-csoportot az audit rögzíti; meglévő védelmi middleware nem kapcsolható ki általánosan.

| Végpont | Feladat |
|---|---|
| POST pairing/start | Rövid életű párosítás indítása; az admin oldali megerősítés külön jogosult művelet |
| GET manifest | Kizárólag az eszközhöz publikált leírás |
| GET catalog | Az aktív és engedélyezett pending kiadás kötéseinek aktuális adatai |
| GET media/{hash} | Csak az eszköznek engedélyezett származék; offline tárolás és Range támogatás |
| POST heartbeat | Saját állapotjelentés; limitált méret és mezők |

A párosítás külön eszközoldali nonce-szal kötött állapotlekérdezést is igényel; a rövid kijelzett kód önmagában nem adhat tartós tokent. A tokenes végpontokhoz jogosultsági és készülékizolációs teszt kell. Az API listák és snapshotok sémaverziója kötelező.

### 8.4. Javasolt fájltérkép

| Útvonal | Felelősség |
|---|---|
| app/Services/Signage/SignageCatalog.php | Katalógusadapter és kijelzőárak |
| app/Services/Signage/SignagePublisher.php | Vázlatellenőrzés és publikált snapshot |
| app/Services/Signage/SignageManifest.php | Eszköznek adott teljes leírás |
| app/Services/Signage/SignageScheduleResolver.php | Időzítési prioritások |
| app/Services/Signage/SignageMediaProcessor.php | Ellenőrzött médiafeldolgozás |
| app/Models/Signage*.php | Az új, ténylegesen szükséges entitások |
| app/Http/Controllers/admin/Signage*.php | Meglévő adminba illesztett kezelők |
| app/Http/Controllers/Signage/PlayerController.php, DeviceController.php | Player és eszköz-API |
| app/Http/Middleware/SignageDeviceAuth.php | Korlátozott eszközhozzáférés |
| app/Jobs/ProcessSignageMedia.php | Háttérfeldolgozási feladat |
| resources/views/admin/signage/ | Média, jelenet, lista, eszköz, előnézet |
| resources/views/signage/player.blade.php | Elkülönített player shell |
| public/signage/player.js, renderer.js, storage.js, schedule.js, player.css, sw.js | Lejátszás, megjelenés és offline működés |
| config/signage.php | Limitek és alapértékek |
| database/migrations/*_create_signage_*.php | Additív sémaváltozások |
| tests/Feature/Signage*, tests/Unit/Signage*, tests/frontend/signage-*.test.cjs | Célzott ellenőrzések |
| docs/signage/ | Felmérés, használat, kiadás és üzemeltetés |

Meglévő fájlok érintése: `CatalogPricing.php`, `AdminPermissions.php`, adminmenü, útvonalak, szükség szerint a route provider és az igazolt háttérfolyamat regisztrációja. Az új modulhoz külön engedélyező kapcsoló tartozzon; telepítés után alapból kikapcsolva maradjon a pilotig.

## 9. Codex végrehajtási feladatok

Minden feladat után legyen működő, visszaellenőrizhető részeredmény. A kódváltozás tesztje előbb reprodukálja a hiányt/hibát, utána igazolja a megvalósítást; a forrásfeltáráshoz és dokumentáláshoz nem kell mesterséges teszt.

### T0 – Forrás és környezet rögzítése

**Fájl:** `docs/signage/discovery.md`.

- [ ] Olvasd a repo aktuális AGENTS.md utasításait és a munkafát; rögzítsd az ágat, commitot, futtatókörnyezetet és a már folyamatban lévő módosításokat.
- [ ] Ellenőrizd a tényleges termék-, feltét-, menü-, kép- és árutat, külön a helyszíni és online ár kapcsolatát.
- [ ] Rögzítsd a TV-k számát, modelljét, felbontását, lejátszóeszközét, hálózatát és automatikus indulási lehetőségét. Hiányzó adat mellett a szoftver izolált fejlesztése folytatható.
- [ ] Vizsgáld a hoszt FFmpeg-, worker-, tárhely- és feltöltési képességeit, valamint a telepített service-worker scope-okat.
- [ ] Ellenőrizd a két referencia elérését, az eredeti ár nélküli termékfotókat és az esetleges harmadik videót.
- [ ] Készíts pontos route-/fájltérképet és helyi indítási parancsot; rögzítsd a meglévő tesztek kiinduló állapotát.

**Készfeltétel:** bizonyított technológiai és árforrás-térkép; az ismeretlen hardver- vagy hosztadat konkrét nyitott tételként szerepel. Ne legyen kitalált éles állapot.

### T1 – Katalógusadapter és árparitás

**Fájlok:** `CatalogPricing.php`, új `SignageCatalog.php`; `tests/Feature/SignageCatalogTest.php`, meglévő `CatalogPricingTest.php`.

- [ ] Írj eseteket: normál ár; referenciaár; fix és százalékos Top Deal; a fix levonás határesete; időablak kezdete/vége változatlan updated_at mellett.
- [ ] Emeld ki a közös `baseQuote(Item): array` részt változatlan meglévő viselkedéssel.
- [ ] Valósítsd meg a `snapshot(bindings, now)` interfészt; teszteld a többáras sorokat és a teljes/kötelezően hiányos konfigurációkat.
- [ ] Ellenőrizd: inaktív kategória, elfogyott/szüneteltetett/hiányzó termék, hiányzó kép, hosszú név.
- [ ] Futtasd a katalógus- és kapcsolódó checkout-regressziókat; csak siker után zárd a feladatot.

**Készfeltétel:** a támogatott kötéseknél az ár egyezik az igazolt katalógusúttal; nincs új, kézzel másolt ártábla.

### T2 – TV-adatmodell, adminjogok és párosítás

**Fájlok:** modellek/migrációk; `SignageDeviceAuth.php`; admin/eszköz controllerek; `AdminPermissions.php`; `SignageDeviceAuthTest.php`, `SignageAdminPermissionsTest.php`.

- [ ] Készíts additív migrációkat és explicit kiosztható TV-moduljogot a registry/actions térképben.
- [ ] Teszteld a jogosult/jogosulatlan admin actionöket, beleértve a közvetlen végponthívásokat.
- [ ] Valósítsd meg az egyszeri párosítást, kódlejáratot, próbálkozáskorlátot és tokenvisszavonást.
- [ ] Teszteld, hogy az A eszköz nem olvashatja B hozzárendelését, vázlatát vagy médiáját, és nem hívhat adminvégpontot.
- [ ] Ellenőrizd, hogy a token és az adminsession nem jelenik meg URL-ben vagy állapotnaplóban.

**Készfeltétel:** két izolált próbakijelző párosítható, az új menüpont jogosultság szerint látszik és működik.

### T3 – Médiatár és feldolgozás

**Fájlok:** médiamodell, admin upload, `SignageMediaProcessor.php`, `ProcessSignageMedia.php`; `SignageMediaTest.php`.

- [ ] Teszteld a valódi JPEG/PNG/WebP/MP4/MOV bemeneteket, hibás tartalmat, méret-/hosszkorlátot és sikertelen konverziót.
- [ ] Készíts bélyegképet, normalizált származékot, metadatát, checksumot és visszajelezhető háttérállapotot.
- [ ] Add hozzá a beégetett ár jelölését, az árazott kötéseket és az ellenőrzött pillanatképet.
- [ ] Bizonyítsd, hogy feldolgozatlan média nem publikálható, a hivatkozott média törlése nem töri el az aktív kiadást.
- [ ] A két átadott videó normalizált változatát ténylegesen játszd le a kijelölt célböngészőn.

**Készfeltétel:** mindkét mintafájl kezelhető, a hibák az adminban láthatók, a hosszú feldolgozás nem blokkolja a rendelési HTTP-kéréseket.

### T4 – Sablonok és TV-előnézet

**Fájlok:** admin signage nézetek, player shell, `renderer.js`, `player.css`; `signage-renderer.test.cjs`.

- [ ] Készítsd el a négy jelenettípust és két témát, közös renderelővel az előnézet és a TV között.
- [ ] Állíts össze referencia-közeli, dinamikus burger- és termékoldalt az eredeti fotókkal.
- [ ] Ellenőrizd a többáras kártyát, 5/6/7/8 termék elrendezését, hiányzó képet és legfeljebb kétsoros neveket.
- [ ] Igazold 1920×1080 és 1280×720 méretben a biztonsági margót és a túllógás nélküli megjelenést.
- [ ] Mutass egyszerre videót és élő termékárat; árfrissítéskor a videó ne induljon újra.
- [ ] Készíts képernyőképeket a két referencia hangulatát követő nézetről és az osztott elrendezésről.

**Készfeltétel:** a kinézet adminból állítható, a minták nem beégetett árú képként vannak visszarajzolva.

### T5 – Playlist, publikálás és ütemezés

**Fájlok:** `SignagePublisher.php`, `SignageScheduleResolver.php`, `SignageManifest.php`, admin listanézet; `SignagePublishTest.php`, `SignageScheduleTest.php`.

- [ ] Készíts vázlat → előnézet → publikált snapshot folyamatot, külön célkijelző-választással.
- [ ] Teszteld, hogy a vázlat módosítása nem változtatja az élő listát; két TV eltérő programot kaphat.
- [ ] Valósítsd meg a prioritást, átfedés-ellenőrzést, éjfélbe átnyúló ablakot és Europe/Budapest időzítést.
- [ ] Teszteld az óraátállás körüli időpontokat és a schedule_valid_until lejáratot.
- [ ] A manifest sorolja fel a teljes szükséges médiát, termékkötéseket, fallbacket és sémaverziót.
- [ ] Ellenőrizd, hogy layout-rollback után is a friss katalógusár jelenik meg.

**Készfeltétel:** egy TV változtatása nem érinti a másikat; a publikált/pending/aktív állapotok megkülönböztethetők.

### T6 – Offline player és önálló helyreállás

**Fájlok:** `player.js`, `storage.js`, `schedule.js`, `sw.js`; `signage-player.test.cjs`, `signage-storage.test.cjs`; böngészős tesztforgatókönyv.

- [ ] Valósítsd meg a teljes előtöltést, Range-lejátszást és ellenőrzött, egyetlen mutatóval végzett kiadásváltást.
- [ ] Teszteld a megszakított letöltést és kvótahibát: a korábbi aktív csomag maradjon használható.
- [ ] Különítsd el az árfrissítést a médiafrissítéstől; új videó letöltése közben is frissüljön a látható ár.
- [ ] Teszteld az 1800 másodperces árlejáratot, a korábbi akcióhatárt, a visszaállított órát és az offline újraindítást.
- [ ] Teszteld a beégetett áras videó érvénytelenítését árfrissítés és offline lejárat esetén is.
- [ ] Kezeld a videóhibát, üres listát, nem induló play()-t és az elakadt lejátszást korlátozott újrapróbálással/tartalékkal.
- [ ] Ellenőrizd, hogy a signage cache frissítése nem módosítja a rendelési oldal/PWA működését.

**Készfeltétel:** előre szinkronizált referenciaeszközön hálózat nélkül is újraindul a tárolt player; lejárt ár helyett tartalék látszik. Teljes cache-törlésre a tényleges környezet korlátja dokumentált.

### T7 – Pilot és átadás

**Fájlok:** `docs/signage/operations.md`, `docs/signage/deployment.md`, `docs/signage/acceptance.md`.

- [ ] Igazold a helyszíni árprofilt, a két mintavideót és a tényleges TV-k olvashatóságát.
- [ ] Egy kijelzőn végezz legalább 24 órás vegyes lejátszási próbát, hálózatszakítással és visszatéréssel.
- [ ] Próbáld a böngésző/készülék újraindítását és a tényleges áramkimaradás utáni indulást.
- [ ] Dokumentáld a host-/playerbeállításokat, workerfelügyeletet, média tárhelyét, mentést és visszaállítást.
- [ ] Futtasd a célzott teszteket és az érintett meglévő regressziókat.
- [ ] Adj át adminhasználati leírást és bizonyítékcsomagot; ezután terjeszd ki a már igazolt konfigurációt a többi TV-re az aktuális megbízás szerint.

**Készfeltétel:** az alábbi mátrix igazolt eredményei szerepelnek az átadásban. Teszt, merge, telepítés és helyszíni elfogadás külön állapot.

## 10. Elfogadási mátrix

A célidők tervezett értékek a rögzített pilotkörnyezetben, nem már megmért eredmények.

| Próba | Elvárt eredmény |
|---|---|
| Katalógusár átírása | Az összes érintett online TV-n legfeljebb 60 s alatt változik; nincs videó-újrarenderelés |
| Akció lejárata DB-írás nélkül | A megfelelő ár megjelenik; updated_at változás nem előfeltétel |
| Többáras termék | Minden felirat a helyes tétel/összeállítás árát mutatja |
| Kötelező fizetős választás hiánya | Publikáláskor konkrét hiba; nincs kitalált fix ár |
| Elfogyott/törölt termék | A beállított szabály szerint eltűnik/jelölést kap, hibás ár nem marad |
| MP4 és MOV | Mindkét referencia feldolgozott változata végigjátszható |
| Videó + élő ár | Árfrissítés közben nincs videó-újrakezdés vagy teljes oldalfrissítés |
| Két TV | Eltérő listák, független publikálás; jogosultsági izoláció |
| Megszakított tartalomfrissítés | A régi teljes kiadás fut tovább |
| Új videó töltése + árkorrekció | Az ár 60 s-os célideje nem vár a videó letöltésére |
| Offline újraindítás | Korábban teljesen szinkronizált eszköz betölti a tárolt playert és tartalmat |
| Árérvényesség lejár | 1800 s vagy korábbi akcióhatár után nincs elavult áras jelenet, beégetett videó sem |
| Hálózat visszatér | Kézi frissítés nélkül szinkronizál; új ár legfeljebb 60 s alatt |
| Hibás videó | A player 10 s-on belül következő érvényes jelenetre/tartalékra vált |
| Kevés tárhely | Az új kiadás hibája látszik az adminban, az aktív kiadás nem törlődik |
| Visszaállítás | Korábbi megjelenés, aktuális árak |
| Visszavont token | Online kapcsolatnál tiltás; adminadat továbbra sem hozzáférhető |
| 24 órás üzem | Nincs tartós fekete kép vagy kezelést igénylő fagyás; a hibák visszakereshetők |
| Áram visszatér | A kijelölt készüléken célként 120 s alatt induló kiosk; TV-bemenet és OS is ellenőrzött |
| Meglévő webshop | Az érintett kosár-, ár-, admin- és fizetési regressziók nem romlanak |

Javasolt helyi parancsok a tényleges tesztfájlok elkészülte után: `vendor/bin/phpunit --filter Signage`, `vendor/bin/phpunit --filter CatalogPricingTest`, `node --test tests/frontend/signage-*.test.cjs`, a módosított JS-fájlok `node --check` ellenőrzése, `php artisan view:cache`, `git diff --check`. A meglévő, ismert route:list hibát ne rejtsd el és ne keverd az új modul eredményével.

## 11. Codex dokumentálás és kiadás

A feladat részletei a #u-gyroscity eredeti tervszálában maradjanak. Minden önállóan használható mérföldkőről külön **EREDMÉNY** főbejegyzés készüljön, hivatkozással az eredeti tervre:

- elkészült funkció és érintett fájlok;
- branch/commit, szükség esetén PR;
- futtatott ellenőrzés és valódi eredménye;
- admin- és 1920×1080-as playerképernyőkép, ha felület változott;
- bizonyított árfrissítés, offline működés vagy referencia-lejátszás;
- fennmaradó korlát és következő lépés.

Ne legyen minden apró lépésből külön csatornaposzt. A képernyőképeken és naplókban ne szerepeljen eszköztoken vagy vevőadat.

A migráció additív legyen, kizárólag az új táblákhoz/beállításokhoz. A tartós médiafájlokat a kiadás ne írja felül. A modulkapcsolóval visszaállítható legyen az előző működés; a rollback ne törölje az új médiát, katalógust vagy rendeléseket. Az aktuális megbízásban nem szereplő élesítést külön végrehajtásként kell kezelni, nem a terv publikálásának részeként.

**Első konkrét Codex-kimenet:** T0 felmérés + T1 árparitás + egy 6 termékes, valódi katalógushoz kötött burgernézet demonstrációja izolált környezetben. Ezután a médiatár, eszközkezelés, publikálás és offline player befejezése a fenti feladatsorrend és függőségek szerint. A T1-re épülő renderer és a T2-re épülő médiatár egyeztetett interfészekkel párhuzamosítható.

## 12. Műszaki hivatkozások

A dokumentációt 2026. november 9-én ellenőriztük. A referenciaimplementációk a működési korlátokat támasztják alá; a konkrét csomag bevezetése külön mérlegelendő.

- **[W1]** [MDN – Autoplay guide](https://developer.mozilla.org/en-US/docs/Web/Media/Guides/Autoplay): hangos/néma lejátszás és a play() hibájának kezelése.
- **[W2]** [Chrome Developers / Workbox – Serving cached audio and video](https://developer.chrome.com/docs/workbox/serving-cached-audio-and-video): teljes előtöltés, Range/206 és offline média.
- **[W3]** [MDN – Storage quotas and eviction criteria](https://developer.mozilla.org/en-US/docs/Web/API/Storage_API/Storage_quotas_and_eviction_criteria): best-effort tárolás, tartóssági kérés, kvótahiba és törlés.
- **[W4]** [MDN – Using Service Workers](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API/Using_Service_Workers): HTTPS, scope és verziózott gyorsítótár.
- **[W5]** [FFmpeg – Formats documentation](https://www.ffmpeg.org/ffmpeg-formats.html): MP4 movflags/faststart.
- [CheckoutPricing forrás](https://github.com/zoltan2561/Gyros_City_Live/blob/f1645a895691c84cc7ae21386298ae991ee11e61/app/Services/CheckoutPricing.php): a termékár és a checkoutban hozzáadott tételek megkülönböztetése.
- [ItemController forrás](https://github.com/zoltan2561/Gyros_City_Live/blob/f1645a895691c84cc7ae21386298ae991ee11e61/app/Http/Controllers/admin/ItemController.php): ármentés és termékállapotok.
- [Meglévő katalógusteszt](https://github.com/zoltan2561/Gyros_City_Live/blob/f1645a895691c84cc7ae21386298ae991ee11e61/tests/Feature/CatalogPricingTest.php): újraárazás és elérhetőség.
- [Gyros City projektkezdőlap](https://szoftlabworkspace.slack.com/docs/T0C9G7WPPCY/F0C9GL11S3S) és [GPT/Codex munkarend](https://szoftlabworkspace.slack.com/docs/T0C9G7WPPCY/F0C8Q5QNSTB): célprojekt és eredménydokumentálás.

A videók formai jellemzői és fájladatai a csatolmányok helyi, mintavételes vizsgálatából származnak; aktuális árra vagy éles működésre nem szolgálnak bizonyítékként.
