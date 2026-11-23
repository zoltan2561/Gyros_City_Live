# Szerveroldali export indítása Hostinger cronból

Állapot: előkészített kiegészítés, a cronbeállítás külön jóváhagyásra vár.

## Igazolt korlát

Az SSH-ból futó Chromium-renderelő a valódi 42 másodperces műsorkörből 630 másodperces Full HD H.264 videót készített. Az admin webes folyamatából indított gyermekek viszont 8 384 512 KiB kemény virtuálismemória-korlátot örökölnek. Ezt a gyermek nem emelheti meg. A Node WebAssembly-foglalása a kompatibilitási kapcsolóval működik, de a Chromium továbbra is SIGTRAP-pal leáll. A Firefox-próba sem volt stabil, ezért nem kerül a termékbe.

## Előkészített működés

Az admin gombja lefoglalja és privát könyvtárba rögzíti a feladatot. A percenként futó `signage:export-pending` parancs az egyetlen várakozó feladatot indítja el, a HTTP-folyamattól független környezetben. A meglévő zárolás megakadályozza a párhuzamos renderelést. A böngésző ugyanazon állapot- és letöltési végpontokat használja; új szerver vagy ügyfélgépi telepítés nem kell.

## Beállítandó Hostinger cronfeladat

Ütemezés: minden percben (`* * * * *`).

Parancs:

```sh
cd /home/u138558728/domains/gyroscity.eu/public_html && /opt/alt/php82/usr/bin/php -d auto_prepend_file=tests/offline-prepend.php artisan signage:export-pending
```

A cron felvétele és visszaolvasása után egy adminból indított próbával igazolni kell, hogy a scheduler környezetében is elindul a Chromium. Csak a sikeres próba után tekinthető működőnek az automatikus export.

Az adminból történő közvetlen folyamatindítás átadását a webroot melletti privát `.signage-runtime/cron-ready` jelzőfájl kapcsolja be. Ez csak az előzetesen beállított cronfeladat mellett hozható létre; tartalma nem titok. A Windowsos helyi export közvetlen indítása megmarad. Három percig el nem indult feladat hibaállapotba kerül és újraindítható.

## Biztonsági és adathatárok

- Nincs `.env`-, hitelesítőadat-, adatbázisséma- vagy tárhelyszintű memórialimit-módosítás.
- A cron üresjáratban azonnal kilép, nem fut állandó exportdaemon.
- A már folyamatban levő renderelést újabb cronhívás nem indítja újra.
- A beállítás visszavonásakor először a jelzőfájlt kell kikapcsolni, majd az adott cronfeladatot eltávolítani. Kész videót vagy TV-listát nem kell törölni.
