"""Page metadata and separate preview/production outputs; no network needed."""
from pathlib import Path
import html,json,re
BASE='https://www.endodont.cz/'
PAGES={
'index.html':('Česká endodontická společnost – vzdělávání a kongres','Odborné vzdělávání v endodoncii, kongres ČES, přednášky a členství. Pro zubní lékaře, kteří chtějí rozvíjet své znalosti a sdílet zkušenosti.'),
'camilleri.html':('Josette Camilleri: MTA a biokeramické sealery 2027','Přednáška prof. Josette Camilleri 19. 3. 2027 v Praze. Program, ceny a registrace. Simultánní tlumočení, 5 kreditů ČSK. Pro členy i nečleny.'),
'kongres.html':('19. kongres ČES 2027 – 24.–25. září','19. výroční kongres České endodontické společnosti proběhne 24.–25. září 2027. Uložte si termín; program, místo a registraci připravujeme.'),
'clenstvi.html':('Členství v ČES – přínosy, příspěvek a přihláška','Proč se stát členem České endodontické společnosti: odborná setkávání, sdílení zkušeností a členské ceny vybraných akcí. Podmínky a přihláška.'),
'prihlaska.html':('Online přihláška do České endodontické společnosti','Vyplňte přihlášku do ČES. Sekretariát vás kontaktuje s dalším postupem členství a platebními pokyny.'),
'vzdelavani.html':('Endodoncie: přednášky a odborné vzdělávání','Vzdělávací akce České endodontické společnosti, kongres, Občasník a odborné zdroje pro zubní lékaře. Programy a informace k účasti.'),
'kalendar.html':('Kalendář akcí ČES 2027','Termíny přednášek, kongresu a členských setkání ČES v roce 2027. Přehled míst, podmínek účasti a odkazů na programy.'),
'clenska-schuze.html':('Volební členská schůze ČES – 23. ledna 2027','Členská schůze České endodontické společnosti s volbou orgánů spolku. 23. ledna 2027, Grandior Praha. Program a podklady připravujeme.'),
'vyjezdni-zasedani.html':('Společné zasedání ČES a SES – červen 2027','Výjezdní zasedání ČES a slovenské SES, 18.–20. června 2027, Hotel Horal v Rožnově pod Radhoštěm. Program a podmínky účasti připravujeme.'),
'o-ces.html':('O České endodontické společnosti','Poslání ČES, výbor, stanovy, historie a mezinárodní spolupráce. Společnost podporuje vzdělávání a rozvoj endodoncie od roku 2007.'),
'vybor.html':('Výbor České endodontické společnosti','Výkonný výbor ČES, revizní komise a redakční rada Občasníku. Přehled představitelů a jejich funkcí ve společnosti.'),
'stanovy.html':('Stanovy České endodontické společnosti','Stanovy ČES: účel společnosti, podmínky členství, práva a povinnosti členů, organizační struktura a hospodaření.'),
'historie.html':('Historie České endodontické společnosti','Historie ČES a odborných setkání. Přehled vývoje společnosti, která propojuje zubní lékaře se zájmem o endodoncii.'),
'grant.html':('Grant ČES – podpora vzdělávání a výzkumu','Grant České endodontické společnosti podporuje vzdělávání a výzkum v endodoncii. Informace k dalšímu ročníku zveřejníme po schválení.'),
'ese-ifea.html':('ESE a IFEA – mezinárodní spolupráce ČES','ČES v evropské ESE a světové IFEA. Odborné zdroje, mezinárodní kongresy a Světový endodontický den 16. října.'),
'kontakt.html':('Kontakt a sekretariát ČES','Kontaktní údaje České endodontické společnosti. Sekretariát pro dotazy k členství, odborným akcím a činnosti společnosti.'),
'obtiznost.html':('Obtížnost endodontického případu – hodnoticí formulář ČES','Interaktivní klasifikace obtížnosti endodontického případu. 13 otázek, 84 možností, bodové hodnocení, tisk a možnost odeslání přehledu s přílohami.'),
'materialy.html':('Endodoncie – odborné materiály a doporučení ČES','Archiv odborných dokumentů ČES ke stažení: příprava zubu, výplachový protokol a hodnocení endodontické léčby. U dokumentů zohledněte rok vydání.'),
'ochrana-udaju.html':('Ochrana osobních údajů ČES','Informace o zpracování osobních údajů při podání přihlášky do České endodontické společnosti a kontakt na správce údajů.'),
'obchodni-podminky.html':('Obchodní podmínky a reklamační řád ČES','Obchodní podmínky vzdělávacích akcí České endodontické společnosti, verze 1.1 účinná od 12. 6. 2026. Objednávky, platby, storno a reklamace.'),
'podminky-ochrany-osobnich-udaju.html':('Podmínky ochrany a zpracování osobních údajů ČES','Ochrana a zpracování osobních údajů při registraci na vzdělávací akce ČES. Informace o správci, účelech, uchovávání údajů a právech účastníků.'),
'english.html':('Czech Endodontic Society – education and congress','The Czech Endodontic Society: professional education and collaboration. The 19th Annual Congress takes place on 24–25 September 2027.'),
'obcasnik.html':('Občasník ČES – časopis a archiv 2023–2026','Čtěte Občasník České endodontické společnosti. Aktuální vydání 2026 a archiv ročníků 2023–2025 přímo online nebo jako PDF ke stažení.')}
ORG={'@type':'Organization','@id':BASE+'#organization','name':'Česká endodontická společnost','alternateName':'Czech Endodontic Society','url':BASE,'logo':BASE+'assets/logo-ces.webp','sameAs':['https://www.facebook.com/endodoncie/','https://www.instagram.com/endodont_cz/','https://x.com/endodont_cz']}
def metadata(page,title,desc,production):
 title,desc=PAGES.get(page,(title,desc));url=BASE+('' if page=='index.html' else page)
 asset_base=BASE if production else 'https://metju120.github.io/ces-web/'
 image='assets/o2-universum.jpg' if page=='kongres.html' else 'assets/camilleri.webp' if page=='camilleri.html' else 'assets/casopis/obcasnik-2026-mockup.jpg' if page=='obcasnik.html' else 'assets/kongres-ces-archiv.jpg'
 graph=[ORG,{'@type':'WebPage','@id':url+'#webpage','url':url,'name':title,'description':desc,'inLanguage':'en' if page=='english.html' else 'cs','publisher':{'@id':BASE+'#organization'}}]
 if page!='index.html':graph.append({'@type':'BreadcrumbList','itemListElement':[{'@type':'ListItem','position':1,'name':'Úvod','item':BASE},{'@type':'ListItem','position':2,'name':title,'item':url}]})
 if page=='camilleri.html':
  graph.append({'@type':'Event','name':'Od MTA k biokeramickým sealerům – prof. Josette Camilleri','url':url,'startDate':'2027-03-19T09:00:00+01:00','endDate':'2027-03-19T17:00:00+01:00','eventAttendanceMode':'https://schema.org/OfflineEventAttendanceMode','eventStatus':'https://schema.org/EventScheduled','description':desc,'image':BASE+'assets/camilleri.webp','organizer':{'@id':BASE+'#organization'},'performer':{'@type':'Person','name':'Josette Camilleri'},'location':{'@type':'Place','name':'Ústav molekulární genetiky AV ČR','address':{'@type':'PostalAddress','streetAddress':'Vídeňská 1083','addressLocality':'Praha 4','postalCode':'142 20','addressCountry':'CZ'}},'offers':[{'@type':'Offer','name':name,'price':price,'priceCurrency':'CZK','url':'https://form.simpleshop.cz/0Mr3n/buy/'} for name,price in [('Zubní lékař',7000),('Člen ČES',5000),('Člen SES',6000)]]})
 extra=f'<link rel="canonical" href="{url}"><meta name="robots" content="{"index,follow" if production else "noindex,follow"}"><meta property="og:url" content="{url}"><meta property="og:image" content="{asset_base+image}"><meta property="og:image:alt" content="{html.escape(title,quote=True)}"><meta property="og:site_name" content="Česká endodontická společnost"><meta property="og:locale" content="{"en_GB" if page=="english.html" else "cs_CZ"}"><meta name="twitter:card" content="summary_large_image">'
 extra+='<script type="application/ld+json">'+json.dumps({'@context':'https://schema.org','@graph':graph},ensure_ascii=False).replace('</','<\\/')+'</script>'
 return title,desc,extra
# Only corresponding pages are redirected. Past event details must not become the 2027 congress.
REDIRECTS={
'o-ces':'o-ces.html','o-ces/vykonny-vybor':'vybor.html','o-ces/historie-ces':'historie.html','o-ces/stanovy-ces':'stanovy.html','o-ces/prihlaska':'prihlaska.html','o-ces/kontakt':'kontakt.html','grant-ces':'grant.html','ese':'ese-ifea.html','english-information':'english.html','zpracovani-osobnich-udaju':'ochrana-udaju.html','prednasky':'vzdelavani.html','akce':'kalendar.html','pro-lekare':'vzdelavani.html','pro-lekare/ke-stazeni':'materialy.html','ke-stazeni':'materialy.html','pro-lekare/odkazy':'vzdelavani.html','prednasky/od-mta-k-biokeramickym-sealerum':'camilleri.html'}
def finish_seo(out,production):
 urls=[BASE+('' if p.name=='index.html' else p.name) for p in sorted(out.glob('*.html'))]
 (out/'sitemap.xml').write_text('<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'+''.join('<url><loc>'+html.escape(u)+'</loc></url>' for u in urls)+'</urlset>')
 (out/'robots.txt').write_text('User-agent: *\nDisallow:\n'+('Sitemap: '+BASE+'sitemap.xml\n' if production else '# Preview uses page-level noindex; crawling must remain allowed.\n'))
 if production:
  rules=['RewriteEngine On','RewriteRule ^admin(?:/.*)?$ https://www.endodont.cz/ [R=301,L,QSD]','RewriteCond %{HTTP_HOST} ^endodont\\.cz$ [NC,OR]','RewriteCond %{HTTPS} !=on','RewriteRule ^ https://www.endodont.cz%{REQUEST_URI} [R=301,L,NE]','RewriteRule ^index\\.html$ https://www.endodont.cz/ [R=301,L]']
  redirects={**REDIRECTS,**json.loads((Path(__file__).parent/'content/redirects-archiv.json').read_text())}
  rules += ['RewriteRule ^'+re.escape(old)+'/?$ /'+new+' [R=301,L]' for old,new in redirects.items()]
  for pdf in (Path(__file__).parent/'dokumenty').glob('data-blob-*.pdf'):
   rules.append('RewriteRule ^data/blob/'+re.escape(pdf.name.removeprefix('data-blob-'))+'$ /dokumenty/'+pdf.name+' [R=301,L]')
  (out/'.htaccess').write_text('\n'.join(rules)+'\n')
