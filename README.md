# Web České endodontické společnosti

**Oficiální název spolku:** **Česká endodontická společnost z. s.** (bez čárky před „z. s.“). Stejný zápis používat na všech stránkách, v elektronických formulářích a v dokumentaci. Jiné skloňované podoby názvu zachovávají rovněž zápis právní formy bez čárky.

Výroční web ČES, bílé/modré/stříbrné provedení a schválený motiv 1B.2. GitHub Pages: https://metju120.github.io/ces-web/

## Sestavení

- `python build.py` vytvoří neindexovaný statický náhled v kořeni repozitáře.
- `python build.py --production` připraví kompletní `release/` k nahrání na Český hosting: stránky, styly, obrázky, PDF, PHP formulář, sitemapu a 301 přesměrování.
- Na ostrý hosting použít **obsah release/**. Root HTML z repozitáře mají noindex a jsou určena pro GitHub náhled.
- Python používá standardní knihovnu. Veškeré zdroje jsou nyní v repozitáři; původní scratch záloha není pro sestavení potřebná.

Obsah v `build.py` a `content/`, SEO v `seo.py`, vzhled v `style.css`, interakce v `app.js`. Přihláška používá PHP 8.2+ a mail() na Českém hostingu; na GitHub Pages není odesílání dostupné. Podrobnosti a otevřené kroky: DEPLOYMENT.md. Identita: BRANDING.md.

## Obsah

Přednáška Camilleri, 19. kongres 2027, kalendář, členství, výbor, stanovy, historie, ESE/IFEA, Grant ČES, lokální archiv všech čtyř vydání Občasníku a archiv minulých kongresů/přednášek.

Sponzorské podklady zůstávají uložené; zobrazení pouze na kongresové stránce až po potvrzení partnerství pro 2027. Aktuálně `SPONSORS_CONFIRMED=False`.

## Před spuštěním

Nasazení na Český hosting, ověření skutečného doručení přihlášek, kontrola HTTPS/301 a Search Console. Potvrdit příspěvek 2027, program/místo/registraci kongresu a podmínky společného zasedání. Pro skutečné měření registrací připojit analytický systém a data SimpleShopu; samotné události v JavaScriptu zatím nic nesbírají.

## Historie ČES — článek k 20. výročí

- Jediný redakční zdroj pro web, Občasník ČES a LKS: `content/historie-clanek.md`.
- `python build.py` generuje z článku stránku `historie.html`; `python build.py --production` ji vloží do `release/`.
- Osm upravených historických fotografií je uloženo **přímo v repozitáři** ve složce `assets/historie/`. Pět je již vloženo do stránky (dvě z 3. kongresu, po jedné z 6. a 9. kongresu a workshop Říčany 2018).
- Fotografie jsou od uživatele, názvy kongresů vycházejí z dodaných souborů; u fotografie dvou sedících účastníků uživatel opravil původní označení na 3. kongres. Přesný popis osob není do popisků doplňován z neověřených odhadů.
- Zbývají tři zástupné obrazové pozice: detail Ceny Luďka Peřinky (2018), její předání (2018) a aktuální kongres.
- V článku se fotografie vkládají syntaxí `[[OBRAZEK: assets/historie/fotografie.webp | Popis snímku | Popisek]]`; není-li soubor dostupný, generátor zobrazí bezpečný zástupný rámeček. Neověřené snímky se nepoužijí jako ilustrace jiné události.
- Čestné členství doc. MUDr. Lenky Roubalíkové, Ph.D., je uvedeno od roku 2014 na základě doložení v e-mailové komunikaci ČES.
- Stránka na GitHub Pages je neindexovaný pracovní náhled. Před ostrým publikováním ověřte redakční znění a oprávnění k fotografiím.

Podrobné názvy obrázků a vazbu na zdrojové fotografie uvádí `content/historie-fotografie.md`.

## Hodnocení obtížnosti endodontického případu

- Migrace **13 českých otázek / 84 možností** ze zálohy původního `form.endodont.cz` (metodika Canadian Academy of Endodontics): `assets/assessment/questions.json`.
- Stránka `obtiznost.html` používá `assessment.js` a `assessment.css`. Výsledek je počítán z označených možností (1/2/5 bodů), třídy podle původní stupnice: 15–17 = 1, 18–25 = 2, od 26 = 3; při nižším skóre bez klasifikace. Tisk do PDF funguje v prohlížeči i na GitHub Pages.
- Na PHP hostingu `api/obtiznost.php` ověřuje skóre také na serveru a odesílá PDF přehled a nejvýše dvě anonymizované přílohy e-mailem. Omezení odesílání, CSRF, validace MIME, velikostí a adresátů.
- Pro serverový PDF soubor je využito **dompdf** instalované přes Composer, které je v produkčním balíčku přibalené v `vendor/`. Staré neudržované knihovny mPDF 6 / SwiftMailer nejsou přebírány.
- Před spuštěním ověřit doručení e-mailů a nastavit PHP `upload_max_filesize >= 6M`, `post_max_size >= 16M` a `memory_limit >= 128M`. Původní funkční web na subdoméně nepřesměrovávat, dokud neprojde test výpočtu, tisku, PDF i odeslání dvou příloh na Českém hostingu.
