const menu=document.querySelector('.menu-button'),nav=document.querySelector('.nav');
menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')!=='true';menu.setAttribute('aria-expanded',String(open));nav.classList.toggle('open',open);menu.textContent=open?'Zavřít ×':'Menu ☰'});
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&menu?.getAttribute('aria-expanded')==='true'){menu.click();menu.focus()}});
document.querySelectorAll('[data-filter]').forEach(button=>button.addEventListener('click',()=>{document.querySelectorAll('[data-filter]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));let count=0;document.querySelectorAll('[data-event-type]').forEach(row=>{row.hidden=button.dataset.filter!=='all'&&row.dataset.eventType!==button.dataset.filter;if(!row.hidden)count++});const status=document.querySelector('#filter-status');if(status)status.textContent=`Počet zobrazených akcí: ${count}`}));
function downloadFile(name,text,type){const url=URL.createObjectURL(new Blob([text],{type}));const a=document.createElement('a');a.href=url;a.download=name;a.click();setTimeout(()=>URL.revokeObjectURL(url),1000)}
const escapeICS=s=>s.replace(/\\/g,'\\\\').replace(/\n/g,'\\n').replace(/,/g,'\\,').replace(/;/g,'\\;');
document.querySelectorAll('[data-calendar]').forEach(b=>b.addEventListener('click',()=>{const e=JSON.parse(b.dataset.calendar);const lines=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//CES//Kalendář akcí//CS','CALSCALE:GREGORIAN','BEGIN:VEVENT',`UID:${e.id}-2027@endodont.cz`,'DTSTAMP:20261023T120000Z',`DTSTART;VALUE=DATE:${e.start}`,`DTEND;VALUE=DATE:${e.end}`,`SUMMARY:${escapeICS(e.title)}`,`LOCATION:${escapeICS(e.place)}`,'END:VEVENT','END:VCALENDAR'];const encoder=new TextEncoder();const folded=lines.map(line=>{let result='',part='';for(const char of line){if(encoder.encode(part+char).length>72){result+=part+'\r\n ';part=char}else part+=char}return result+part}).join('\r\n')+'\r\n';downloadFile(e.id+'.ics',folded,'text/calendar;charset=utf-8')}));
const form=document.querySelector('#membership-form');
if(form){
 const submit=form.querySelector('[type="submit"]'),availability=document.querySelector('#form-availability'),result=document.querySelector('#form-result');
 let ready=false,sending=false;
 const endpoint=form.getAttribute('action');
 const showResult=text=>{result.hidden=false;result.textContent=text;result.focus()};
 async function prepareForm(){
  if(location.hostname.endsWith('github.io')){availability.innerHTML='V tomto náhledu není přímé odesílání dostupné. Přihlášku zatím pošlete na <a href="mailto:sekretariat@endodont.cz">sekretariat@endodont.cz</a>.';return}
  try{
   const response=await fetch(endpoint,{credentials:'same-origin',headers:{Accept:'application/json'},signal:AbortSignal.timeout(12000)});
   const data=await response.json();
   if(!response.ok||data.service!=='ces-membership-v1'||typeof data.token!=='string')throw new Error();
   form.elements.token.value=data.token;ready=true;submit.disabled=false;availability.textContent='Přihlášku odešleme přímo sekretariátu. Potvrzení přijde na zadaný e-mail.';
  }catch{availability.innerHTML='Přímé odesílání je nyní nedostupné. Napište prosím na <a href="mailto:sekretariat@endodont.cz">sekretariat@endodont.cz</a>.'}
 }
 prepareForm();
 form.addEventListener('submit',async event=>{
  event.preventDefault();if(!ready||sending||!form.reportValidity())return;
  sending=true;submit.disabled=true;submit.textContent='Odesíláme…';result.hidden=true;
  const fields=Object.fromEntries(new FormData(form));fields.conditions=form.elements.conditions.checked;
  try{
   const response=await fetch(endpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify(fields),signal:AbortSignal.timeout(30000)});
   let data;try{data=await response.json()}catch{throw new Error('Odeslání se nepodařilo ověřit. Zkontrolujte potvrzení v e-mailu nebo kontaktujte sekretariát.')}
   if(!response.ok||data.ok!==true)throw new Error(data.error||'Odeslání se nezdařilo. Zkuste to později nebo napište sekretariátu.');
   cesMetric('membership_submission_accepted',{confirmation:data.confirmation===true});
   showResult(`Přihláška byla předána k odeslání sekretariátu. Evidenční číslo: ${data.reference}. `+(data.confirmation?'Potvrzení bylo předáno k odeslání na váš e-mail. Pokud jej nenajdete, zkontrolujte také spam.':'Potvrzení na váš e-mail se nepodařilo odeslat. Přihlášku znovu neposílejte; pro ověření kontaktujte sekretariát.'));
   form.querySelectorAll('input,textarea,button').forEach(el=>el.disabled=true);submit.textContent='Přihláška odeslána';availability.textContent='Sekretariát vás bude kontaktovat s dalším postupem.';ready=false;
  }catch(error){showResult(error.name==='TimeoutError'?'Spojení vypršelo. Odeslání se mohlo dokončit; ověřte potvrzení v e-mailu, než přihlášku odešlete znovu.':error.name==='Error'?error.message:'Odeslání se nepodařilo ověřit. Zkontrolujte potvrzení v e-mailu nebo kontaktujte sekretariát.');submit.disabled=false;submit.textContent='Odeslat přihlášku'}
  finally{sending=false}
 });
}

// Anonymous conversion hooks. A collector is intentionally not configured yet.
function cesMetric(event,parameters={}){window.dispatchEvent(new CustomEvent('ces:metric',{detail:{event,...parameters}}))}
document.addEventListener('click',event=>{const a=event.target.closest('a');if(!a)return;const url=new URL(a.href,location.href);if(url.hostname==='form.simpleshop.cz')cesMetric('registration_click',{event_id:'camilleri-2027'});else if(url.origin===location.origin&&url.pathname.endsWith('/prihlaska.html'))cesMetric('membership_form_open')});
