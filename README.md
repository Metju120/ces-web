# Web České endodontické společnosti

První veřejná verze webu pro výroční rok 2027. Vzhled A2: bílá, modrá a stříbrná, původní logo ČES.

## Provoz

Statické HTML, CSS a JavaScript. Žádné závislosti, databáze ani PHP. Kořen repozitáře lze nahrát na Český hosting nebo zveřejnit na GitHub Pages (větev `main`, kořen `/`). Není nastavena vlastní doména: náhled nemění živý web ani DNS.

Zdroj stránek je v `build.py`; změny událostí v seznamu `E`. Generování: `python3 build.py`. Pro opětovné generování archivních stránek jsou potřebné původní texty a PDF ze zálohy; veřejné výsledné HTML/PDF je již součástí repozitáře. CSS v `style.css`, interakce v `app.js`.

## Obsah první verze

- Úvodní stránka, 19. kongres, vzdělávání a filtrovatelný kalendář.
- 23. 1. 2027: volební členská schůze, Grandior Praha.
- 19. 3. 2027: prof. Josette Camilleri, Praha.
- 18.–20. 6. 2027: společné výjezdní zasedání ČES a SES, Hotel Horal, Rožnov pod Radhoštěm.
- 17.–18. 9. 2027: 19. výroční kongres ČES.
- Výbor, komise, stanovy a historie ze zálohy původního veřejného webu.
- Archivní odborné dokumenty, kontakt, informace o členství.
- Přihláška: validace polí a příprava e-mailu / stažení textu. **Neodesílá sama a neukládá data.** Přímé bezpečné odesílání bude vyžadovat napojení na server.
- Každá akce má stažení celodenní události ICS; časy nejsou zatím stanoveny.

## Před ostrým spuštěním

Ověřit aktuálnost stanov a členů výboru, potvrdit partnery pro rok 2027, doplnit aktuální registrace a časový program. Doplnit úplné informace k nakládání s členskými údaji podle skutečného procesu. Rozhodnout o přímém odesílání přihlášek na hostingu. Výroční grafika zůstává oddělená od loga a lze ji po roce 2027 snadno změnit.

## Propojené služby

- Občasník ČES: https://casopis.endodont.cz/ – hlavní menu, úvod a vzdělávání.
- Hodnocení obtížnosti případů: https://form.endodont.cz/ – úvod a vzdělávání.
- refer.endodont.cz při kontrole 23. 10. 2026 vracel HTTP 500; zatím není v navigaci.
