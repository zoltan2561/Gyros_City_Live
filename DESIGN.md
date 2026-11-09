# Gyros City felületi alapelvek

A meglévő magyar Laravel/Blade admin Bootstrap-alapú felülete és közös admin.theme.default váza marad az admin forrása. A TV külön, távolról olvasható megjelenítés; a rendelési oldal arculata nem változik.

TV: `public/signage/player.css` birtokolja a --scene-bg, --scene-accent, --scene-text tokeneket. Sötét téma: #190c0a, #ffc747, #fff3d2; világos téma: #f6f2f2, #a61264, #22131f. Rendszerbetűk: Arial törzs és ár, Impact/Arial Narrow cím; nincs CDN vagy kötelező hálózati betűletöltés. A karakteres elem a nagy, meleg sárga ár és az eredeti ételfotó; dekoráció nem takarhatja az ételt.

1920×1080 és 1280×720, 5% biztonsági margó; a TV-n nincs adminvezérlő. Az előnézet ugyanazt a renderelőt használja, külön kezelősávval. A sötét és világos téma a terv két referenciáját követi; az eredeti videók hiányában pontos vizuális egyezés nem állítható.

Natív gombok, linkek és selectek, látható fókusz; a select operációsrendszer-felugrója elfogadott. A játékos árfrissítése a videóelem megtartásával történik. Lejárt ár esetén teljes ármentes tartalék jelenet. Magyar feliratok; árformázás a backend meglévő beállításaiból.

Éttermi TV-mozgás: a sötét témában lassú parázsfény és apró felszálló szikrák, a világosban finom magenta fényfoltok és körív. A fejléc és a média lágyan érkezik, a termékkártyák 85 ms-os lépcsőkkel úsznak be. A termék és az ár ezután nyugalomban marad; a háttér tovább mozog. Az ár kontrasztos sárga vagy magenta címkét kap. Minden effekt CSS-transzformációt/áttetszőséget használ, külső média vagy könyvtár nélkül.

A kártya stabil katalóguskötéshez tartozó DOM-eleme árfrissítéskor megmarad; az ár, név, fotó és elérhetőség célzottan frissül. A beúszás csak új jelenetnél vagy újonnan megjelenő kártyánál indul. A prefers-reduced-motion beállítás kikapcsolja a mozgást. A teljes képernyős média előtt nincs parázsréteg.
