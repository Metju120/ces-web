# Web České endodontické společnosti

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

- Jediný redakční zdroj pro web i pro Občasník ČES a LKS: `content/historie-clanek.md`.
- `python build.py` generuje z článku stránku `historie.html`; při nasazení `python build.py --production` vzniká soubor v `release/`.
- Zástupné fotografie mají tvar `[[FOTO: téma | pracovní popisek]]`. Před zveřejněním nahraďte ověřenými fotografiemi v `assets/historie/` a zápisem `[[OBRAZEK: assets/historie/fotografie.webp | Popis snímku | Popisek s případným uvedením autora]]`.
- Připravené pozice: první workshopy (2008), detail Ceny Luďka Peřinky (2018), její slavnostní předání (2018), současný výroční kongres.
- Historická fotografie a přednášející musí být ověřeni před ostrým publikováním; stránka na GitHub Pages zůstává neindexovaným náhledem.
