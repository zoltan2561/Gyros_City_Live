# Android TV lejátszó és TV-vezérlés – későbbi terv

Állapot: **terv, még nincs megvalósítva vagy jóváhagyva az indítása**. Ez a dokumentum nem jelent APK-t, TV-vezérlési integrációt vagy készülékes elfogadást. A meglévő webes signage a kiindulópont; az alkalmazás fejlesztése külön későbbi feladat.

## Cél

A Gyros City kijelzőihez telepíthető Android TV / Google TV alkalmazás, amely a meglévő adminban összeállított műsort teljes képernyőn lejátssza. Az adminból később a TV-k be- és kikapcsolása is vezérelhető legyen, amennyiben a választott hardver ezt igazoltan támogatja.

Korábban Samsung okostévék szerepeltek a helyszíni tervben. A Tizen rendszerű Samsung TV nem futtat Android APK-t közvetlenül: Android TV-s külső lejátszó vagy külön Tizen-megoldás jöhet szóba. A tényleges TV-k és a lehetséges külső lejátszók pontos típusát a döntés előtt fel kell mérni.

## Javasolt lejátszóirány

Elsőként a jelenlegi `/signage/` lejátszó újrahasznosítását érdemes vizsgálni egy Android TV-s alkalmazásban. A WebView-alapú megoldás csak jelölt: a kiválasztott eszközön külön igazolandó a HTTPS, a helyi tárolás, a Service Worker / Cache Storage, a videók, a teljes képernyő és a hosszú futás támogatása. Ha ez nem megfelelő, natív lejátszó vagy más támogatott kioszkmegoldás szükséges; a végleges technikai választás a készülékpróba után történik.

A későbbi megoldás őrizze meg a jelenlegi működés lényegét:

- TV-nkénti párosítás és saját jogosultság; a token ne kerüljön URL-be, naplóba vagy forráskódba.
- Az adminban a TV-hez rendelt kiadás átvétele, folyamatos ismétlés, kapcsolatvesztés utáni helyreállás.
- A jelenlegi offline csomag- és árérvényességi szabályok: lejárt vagy bizonytalan ár esetén ármentes tartalék jelenet.
- Készülékre szabott teljes képernyő, távirányítós használhatóság, alkalmazás- és eszközindulás utáni visszatérés. Az automatikus indulás és a kioszk mód eszköz- és beállításfüggő; nem tekinthető általánosan biztosítottnak.
- Az alkalmazás aláírásának, telepítésének, frissítésének és visszaállításának dokumentált, biztonságos módja. Titkos kulcs nem kerül Gitbe vagy a dokumentációba.

## A TV be- és kikapcsolása

Az adminból indított TV-vezérlés külön képesség a műsorlejátszástól. Lehetséges irány a HDMI-CEC, Samsungnál Anynet+, vagy az adott TV/lejátszó gyártói vezérlése; szükség esetén külön, hálózaton elérhető vezérlő is vizsgálható. A választást a konkrét eszközök és a fizikai próba dönti el.

Az Android HDMI-CEC rendszerfelülete privilegizált hozzáférést igényelhet; egy szokásosan telepített APK-ból küldhető ki- és bekapcsolás nem feltételezhető. A külső vezérlőnek a TV kikapcsolt állapotában is áram alatt és elérhetőnek kell maradnia: a TV USB-portjáról táplált box erre nem feltétlenül alkalmas. Teljes áramtalanítás után külön vizsgálandó, hogyan éleszthető újra a rendszer.

A későbbi adminfelületen a kért parancs, a parancs teljesítésének visszajelzése és a TV tényleges, ellenőrzött állapota legyen megkülönböztetve. A lejátszó heartbeatje önmagában nem bizonyítja, hogy a TV-panel be van kapcsolva. Kétes visszajelzésnél az állapot „ismeretlen”; a parancsok jogosultságát, lejáratát, ismétlését és naplózását a megvalósításkor külön meg kell tervezni. Ütemezés – ha később igény lesz rá – a helyi időzónát és az átállásokat is kezelje.

## Javasolt későbbi lépések és elfogadás

1. **Eszközfelmérés:** mindhárom TV pontos típusa, operációs rendszere, HDMI-CEC/Anynet+ és hálózati készenléti támogatása; a választott Android TV-s box típusa, Android-verziója, tápellátása és tárhelye.
2. **Egykészülékes próba:** teljes képernyős lejátszás, párosítás, árfrissítés, offline tartalék, újracsatlakozás, videók, hosszabb folyamatos futás, újraindítás és áram-visszatérés. Az offline működést az adott WebView/kioszkmegoldáson ténylegesen mérni kell.
3. **Vezérlési próba:** ugyanazon a TV–box páron külön ellenőrizni a be- és kikapcsolást, az áram alatt maradó vezérlőt, a képernyő tényleges állapotát és a sikertelen parancsok visszajelzését.
4. **Csak sikeres próba és külön megbízás után:** az alkalmazás, az esetleges vezérlési végpontok és adminfunkciók megvalósítása, tesztelése, aláírt APK készítése, telepítési és visszaállítási leírással.
5. **Átadás előtt:** minden használt TV-n helyszíni próba, a támogatott hardverek és az ismert korlátok rögzítése. Emulátoros teszt vagy sikeres APK-build önmagában nem bizonyítja a fizikai TV vezérlését.

## Források

- [Android TV alkalmazás létrehozása](https://developer.android.com/training/tv/get-started/create)
- [Android dedikált eszközök és kioszk mód](https://developers.google.com/android/management/policies/dedicated-devices)
- [Android HDMI-CEC szolgáltatás és hozzáférési korlátok](https://source.android.com/docs/devices/tv/hdmi-cec)
- [Google: TV ki- és bekapcsolása, CEC és külön tápellátás](https://support.google.com/chromecast/answer/7498991)
- [Samsung: APK és Tizen TV kompatibilitás](https://us.community.samsung.com/t5/LED-and-OLED-TVs/How-to-use-sideloading-on-my-TV/td-p/3497986)
