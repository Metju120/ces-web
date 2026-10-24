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
- `python build.py --production`: vytvoří kompletní adresář `release/` pro https://www.endodont.cz/, včetně HTML, assets, dokumentů, PHP, sitemap.xml, robots.txt a .htaccess. Na hosting nahrávat **obsah release/**, nikoli HTML z kořene repozitáře (to je neindexovaný náhled).
- Produkční HTML obsahuje `index,follow`, canonical adresy, vlastní titulky/popisy a sociální metadata. Strukturovaná data Organization a BreadcrumbList; úplný Event u Camilleri. Kongres nemá Event, dokud není známá skutečná adresa místa konání.
- .htaccess obsahuje odpovídající staré adresy pro hlavní sekce, Camilleri a původní PDF. Před nasazením pravidla sloučit se stávající konfigurací hostingu, zachovat funkční subdomény a ověřovací soubory Search Console. Pravidla jsou připravena pro Apache/mod_rewrite a cílový kořen domény, nikoli GitHub Pages.
- Staré jednotlivé kongresy a historické přednášky nepřesměrovávat na aktuální kongres 2027. Jejich texty jsou nyní v archivu, mapování v content/redirects-archiv.json. Převod zachovává programy a zprávy, výrazně označuje minulost a neobsahuje aktivní staré registrační formuláře.
- Po nasazení ověřit HTTPS, bez www → www, 301 konkrétních starých odkazů, dostupnost PDF a registračních/obchodních odkazů; odeslat sitemapu v Search Console a ověřit Camilleri v Rich Results Test.

## Ověřený obsah a otevřené údaje

- Členský příspěvek 5 000 Kč platí pro **2026**, podle členské informace předsedy z 5. 1. 2026. Pro 2027 zatím nepotvrzeno. Interní e-mail ani platební identifikátory nejsou publikované.
- Fotografie assets/kongres-ces-archiv.jpg je původní veřejná fotografie účastníků 10. kongresu, ze zálohy /kongresy-ces/10-vyrocni-kongres-ces; není označena jako fotografie aktuálního ročníku.
- SPONSORS_CONFIRMED=False: zděděná loga jsou uchována, ale veřejně skryta do potvrzení partnerů 2027. Zobrazovat pouze na kongresové stránce.
- Členská schůze je označena pro členy; Camilleri pro členy i nečleny podle zveřejněných cen. U zasedání ČES/SES se neslibuje veřejná účast. Kongres má označení pro zubní lékaře s registrací v přípravě.

## Měření výsledků – připraveno, nesbírá data

app.js emituje pouze v paměti prohlížeče události `ces:metric`: `registration_click`, `membership_form_open`, `membership_submission_accepted`. Neobsahují osobní údaje, nic neodesílají a nevytvářejí cookies. Poslední událost znamená přijetí formuláře serverem, nikoli vznik členství ani ověřené doručení pošty.

Před skutečným měřením připojit zvolený analytický systém a sladit informace o soukromí s jeho nastavením. Dokončené registrace/objednávky ověřovat v SimpleShopu; proklik na tlačítko není objednávka. Search Console vyžaduje přístup k ověřené doméně; není zde připojena.


## Převod na www.endodont.cz a rušení starých subdomén (24. 10. 2026)

**Kanoniální adresa:** https://www.endodont.cz/; bez-www varianta přesměrovává na www s HTTP 301. `seo.py` generuje odpovídající canonical, sitemap, Open Graph/JSON-LD a produkční `.htaccess`. Při nahrání na Český hosting ponechat v administraci již nastavené přesměrování `endodont.cz → www.endodont.cz`; nesmí současně existovat opačné přesměrování.

### Stav záloh – před odstraněním NEÚPLNÉ

- `casopis.endodont.cz`: hlavní obsah Občasníku 2023, 2024, 2025 byl přenesen jako PDF do `dokumenty/`, nově je na webu i vydání 2026. Není ověřeno kompletní převzetí původního PHP prohlížeče a všech historických URL a assetů. Před vypnutím zachovat veřejné URL jednotlivých PDF a `/obcasnik_2023.php`, `/obcasnik_2024.php`, `/obcasnik_2025.php` vhodnými 301 přesměrováními.
- `kongres.endodont.cz`: historický program 18. kongresu ČES 2026 **není** v archivu nového webu úplný; v `content/archiv.json` je u položky pouze `<p>Registrace</p>`. **Právní dokumenty jsou přeneseny** z veřejných verzí 1.1 (účinné od 12. 6. 2026) do `content/obchodni-podminky.html` a `content/podminky-ochrany-osobnich-udaju.html`. Generují se na hlavní doméně se stejnými cestami a `content/camilleri.html` už odkazuje lokálně. Přesměrování původních dvou URL lze zapnout samostatně, ale celý `kongres.` ještě nepřesměrovávat bez zajištěného archivu 2026 a ostatních aktivních návazností. Poznámka ke kontrole: převzaté GDPR znění stále uvádí Mailchimp a původní `kongres.endodont.cz`; prověřit skutečné provozní zpracovatele a aktualizovat po věcném schválení.
- `form.endodont.cz`: funkční aplikace klasifikace obtížnosti endodontického případu, včetně bodování, souhrnu, e-mailového odeslání a příloh, není součástí tohoto repozitáře. `build.py` na ni pouze odkazuje. Potřebujeme skutečný export zdrojových souborů (včetně neveřejných konfigurací a případných databází), popis provozu, test doručování a funkční náhradu na www. Do té doby ponechat subdoménu v provozu.
- Původní hlavní web byl provozován u Poski.com. Záloha souborů z Českého hostingu **nezahrnuje automaticky** programové soubory a databázi Poski. Úplnost archivu hlavního původního webu nebyla prokázána.

### Bezpečný postup a kontroly před smazáním dat

1. Zhotovit samostatnou datovanou offline zálohu každé subdomény a původního hostingu (webové soubory včetně skrytých souborů, SQL exporty, certifikáty pouze podle potřeby, konfigurace bez zveřejňování hesel). Vyžádat export Poski, pokud není k dispozici. Citlivé zálohy neukládat do veřejného GitHub repozitáře.
2. Zálohy stáhnout mimo hosting, rozbalit kontrolní vzorek, ověřit úplnost i právo čtení; nepočítat pouze s tím, že poskytovatel historicky uchovává automatické zálohy. Český hosting: Domény a hosting → Zálohy, zpřístupnění přes SFTP/SSH adresář `backups`.
3. Dodat na www odpovídající archiv kongresu 2026 a funkční ekvivalent klasifikace endodontických případů včetně odeslání. Obchodní podmínky a GDPR pro vzdělávací akce jsou převedeny; u GDPR před ostrým zveřejněním ověřit skutečné služby zpracovatelů.
4. Až poté nastavit 301 přesměrování samostatných subdomén na obsahově odpovídající konkrétní nové URL, na každé subdoméně zvlášť nebo pomocí nastavení hostingu. Produkční `.htaccess` hlavní domény **není** automaticky platný pro oddělené subdomény.
5. Subdomény **nemazat z administrace/DNS**, pokud z nich mají dál fungovat HTTP 301 přesměrování: k přesměrování musí dál existovat platné DNS, HTTPS certifikát a přijímající webserver. Mazat jen nepotřebné staré soubory po ověřené záloze a funkčním redirectu.
6. Ověřit staré odkazy, aktuální formuláře a testovací doručení e-mailů; DNS pošty (MX, SPF, DKIM, DMARC) ponechat. Hlavní doménu přepnout z Poski na Český hosting až po ověření produkčního webu; otestovat www, apex, HTTPS, sitemap, 301 i členství.

**Status k 24. 10. 2026:** kanonická adresa na GitHubu upravena; **subdomény nebyly přesměrovány ani smazány**, žádné staré soubory ani databáze nebyly odstraněny a úplný export starých služeb dosud nebyl doložen.


## Migrace form.endodont.cz (24. 10. 2026)

- Dodaná ZIP záloha `Záloha form.endodont.zip` obsahovala `index.php`, původní českou sadu `resources/questions/cs/questions.json`, `js/questions.js`, `sendEmail.php` a staré mPDF 6/SwiftMailer. Zjištěno **13 otázek, 84 voleb ve 3 skupinách**. Volby se používají jako checkboxy a sčítají 1 / 2 / 5 bodů, nikoliv vzájemně výlučná radio tlačítka.
- Nové řešení: `obtiznost.html`, `content/obtiznost.html`, `assets/assessment/questions.json`, `assessment.js`, `assessment.css`, `api/obtiznost.php`, `api/obtiznost-lib.php`, Composer dompdf. Odesílané skóre server znovu počítá proti uloženým kritériím. Původní rozsahy tříd zachovány.
- Lze tisknout / uložit PDF na GitHub Pages; odeslání PDF přílohy se dvěma soubory zajišťuje PHP na Českém hostingu. GitHub Pages PHP neprovádí. Doručení e-mailů je nutné ověřit na ostrém hostingu a nastavit potřebné PHP limity pro nahrávání příloh.
- Před odstraněním `form.endodont.cz` otestovat novou funkci od začátku do konce, včetně přijetí e-mailu, PDF, obrázku a ochrany proti opakovanému odeslání. Samostatná aplikace na subdoméně zatím nebyla smazána.
