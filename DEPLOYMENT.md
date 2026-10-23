# Nasazení webu ČES

## GitHub Pages

Statické stránky fungují v náhledu na GitHub Pages. PHP se zde nespouští a přihláška zde záměrně hlásí nedostupnost přímého odesílání.

## Český hosting – přihláška

1. Nahrát HTML stránky, `style.css`, `app.js`, adresáře `assets/`, `dokumenty/` a `api/` do webového adresáře domény. Složky `.git`, `tests`, `content` a soubory sestavení na hosting nenahrávat.
2. Použít podporovanou aktuální řadu PHP (kód vyžaduje PHP 8.2 nebo novější) a HTTPS. Skript `api/clenstvi.php` musí být vykonáván serverem, nikoli poskytován jako text.
3. Ověřit existenci a doručování adresy `sekretariat@endodont.cz`, nastavení SPF/DKIM pro poštu z webserveru a fungování `mail()`. Český hosting tuto funkci pro formuláře podporuje: https://www.cesky-hosting.cz/clanky/hostingove-zpravy/odesilani-e-mailu-z-webovych-aplikaci/
4. Otevřít `prihlaska.html` a po aktivaci formuláře provést jeden skutečný kontrolní průchod s vlastními údaji. Ověřit příjem na sekretariátu a potvrzení zájemci. Samotný návrat `mail() = true` znamená předání zprávy k odeslání, nikoli důkaz doručení do schránky.

Formulář sám ověří dostupnost serverové části. Neotevírá poštovní aplikaci. Odesílá přihlášku na pevnou adresu sekretariátu a samostatné potvrzení zájemci. Potvrzení není přijetím za člena. Má ochranu CSRF, honeypot, validaci, omezení počtu pokusů a ochranu před opakovaným odesláním téže přihlášky v rámci relace. Formulářová data se nezapisují do databáze ani do logu aplikace; soukromý dočasný soubor obsahuje pouze časy pokusů a denně měněné hashe IP. Odeslání potvrzení může selhat samostatně; v tom případě formulář nedoporučuje opakovat již přijatou žádost.

## Kontroly

- `python3 build.py` – sestaví stránky.
- `node --check app.js` – syntaxe JavaScriptu.
- `php tests/clenstvi.test.php` – 11 testů serverové logiky s nahrazenou poštou; žádný e-mail neodešle. Lze spustit i v lokálním PHP 8.2 WebAssembly runtime.

Ověřeno: serverová validace, adresáti obou e-mailů, chybové stavy, duplicity, omezení pokusů. Skutečné doručení e-mailů na cílovém hostingu zatím ověřeno není; přístup k hostingu není v pracovním prostředí dostupný.

## Zdroje přeneseného obsahu

- Camilleri: místní záloha veřejné stránky ze dne 23. 10. 2026, https://www.endodont.cz/prednasky/od-mta-k-biokeramickym-sealerum. Zachován původní registrační odkaz SimpleShop, program, ceny, tlumočení, kredity a storno podmínky.
- Občasník 2023–2025: veřejný archiv https://casopis.endodont.cz/, PDF a původní náhledy. PDF optimalizována pro web, zachován počet stran a text.
- Občasník 2026: dodaný finální podklad a mockup.

Sponzoři jsou zobrazováni výhradně na `kongres.html`. Výroční hlavička a patička používají původní logo ČES vedle schváleného motivu 1B.2; viz `BRANDING.md`.
