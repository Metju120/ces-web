# Nasazení webu ČES

## GitHub Pages

Statické stránky fungují v náhledu na GitHub Pages. PHP se zde nespouští a přihláška zde záměrně hlásí nedostupnost přímého odesílání.

## Český hosting – přihláška

1. Nahrát HTML stránky, `style.css`, `app.js`, adresáře `assets/`, `dokumenty/` a `api/` do webového adresáře domény. Složky `.git`, `tests`, `content` a soubory sestavení na hosting nenahrávat. Použít produkční sestavení release/ popsané níže.
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

Sponzoři jsou zobrazováni výhradně na `kongres.html`. Výroční hlavička spojuje původní logo ČES s motivem 1B.2 vlevo; patička je bez výročního motivu; viz `BRANDING.md`.

## ESE a IFEA

Společná podsekce pod O ČES: `ese-ifea.html`. ESE vychází z místní zálohy původní veřejné stránky a zachovává přehled bienálních kongresů. Podmínky individuálního členství odkazují na aktuální oficiální web. IFEA je představena podle oficiálního webu, bez neveřejné komunikace nebo slibů členských výhod.

Ověřené veřejné zdroje (23. 10. 2026):
- https://www.endodont.cz/ese
- https://www.e-s-e.eu/our-mission/
- https://www.e-s-e.eu/join-us/society-members-directory/czech-endodontic-society-6/
- https://www.ifeaendo.org/
- https://www.ifeaendo.org/member-associations (seznam dostupný ve výsledcích vyhledávání; přímé načtení nástrojem jednou vypršelo).

### Světový endodontický den

Veřejná sekce IFEA obsahuje připomínku 16. října a české heslo „Zachovejte svůj zub. Neztraťte svůj úsměv.“ — přesné finální znění podle Martinovy komunikace z 12. 10. 2026. Osvětový účel potvrzuje zpráva IFEA z 3. 10. 2024; program sympozia 2026 potvrzuje členská pozvánka z 12. 10. 2026 a veřejná stránka https://www.ifeaendo.org/symposium/. Interní organizační komunikace, soukromé kontakty a přístupové odkazy pro zástupce se nezveřejňují. Letošní sympozium je popsáno v minulém čase, bez neplatné výzvy k registraci.

## Finální sestavení a SEO (24. 10. 2026)

- `python build.py`: GitHub preview, každá stránka má `noindex,follow`. Robots dovoluje načtení, aby vyhledávače mohly noindex přečíst.
- `python build.py --production`: vytvoří kompletní adresář `release/` pro https://endodont.cz/, včetně HTML, assets, dokumentů, PHP, sitemap.xml, robots.txt a .htaccess. Na hosting nahrávat **obsah release/**, nikoli HTML z kořene repozitáře (to je neindexovaný náhled).
- Produkční HTML obsahuje `index,follow`, canonical adresy, vlastní titulky/popisy a sociální metadata. Strukturovaná data Organization a BreadcrumbList; úplný Event u Camilleri. Kongres nemá Event, dokud není známá skutečná adresa místa konání.
- .htaccess obsahuje odpovídající staré adresy pro hlavní sekce, Camilleri a původní PDF. Před nasazením pravidla sloučit se stávající konfigurací hostingu, zachovat funkční subdomény a ověřovací soubory Search Console. Pravidla jsou připravena pro Apache/mod_rewrite a cílový kořen domény, nikoli GitHub Pages.
- Staré jednotlivé kongresy a historické přednášky nepřesměrovávat na aktuální kongres 2027. Jejich texty jsou nyní v archivu, mapování v content/redirects-archiv.json. Převod zachovává programy a zprávy, výrazně označuje minulost a neobsahuje aktivní staré registrační formuláře.
- Po nasazení ověřit HTTPS, www → bez www, 301 konkrétních starých odkazů, dostupnost PDF a registračních/obchodních odkazů; odeslat sitemapu v Search Console a ověřit Camilleri v Rich Results Test.

## Ověřený obsah a otevřené údaje

- Členský příspěvek 5 000 Kč platí pro **2026**, podle členské informace předsedy z 5. 1. 2026. Pro 2027 zatím nepotvrzeno. Interní e-mail ani platební identifikátory nejsou publikované.
- Fotografie assets/kongres-ces-archiv.jpg je původní veřejná fotografie účastníků 10. kongresu, ze zálohy /kongresy-ces/10-vyrocni-kongres-ces; není označena jako fotografie aktuálního ročníku.
- SPONSORS_CONFIRMED=False: zděděná loga jsou uchována, ale veřejně skryta do potvrzení partnerů 2027. Zobrazovat pouze na kongresové stránce.
- Členská schůze je označena pro členy; Camilleri pro členy i nečleny podle zveřejněných cen. U zasedání ČES/SES se neslibuje veřejná účast. Kongres má označení pro zubní lékaře s registrací v přípravě.

## Měření výsledků – připraveno, nesbírá data

app.js emituje pouze v paměti prohlížeče události `ces:metric`: `registration_click`, `membership_form_open`, `membership_submission_accepted`. Neobsahují osobní údaje, nic neodesílají a nevytvářejí cookies. Poslední událost znamená přijetí formuláře serverem, nikoli vznik členství ani ověřené doručení pošty.

Před skutečným měřením připojit zvolený analytický systém a sladit informace o soukromí s jeho nastavením. Dokončené registrace/objednávky ověřovat v SimpleShopu; proklik na tlačítko není objednávka. Search Console vyžaduje přístup k ověřené doméně; není zde připojena.
