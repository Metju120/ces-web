(()=>{'use strict';
const root=document.getElementById('assessment-root');if(!root)return;
const questionsNode=document.getElementById('assessment-questions'),pts=document.getElementById('assessment-points'),cl=document.getElementById('assessment-class'),status=document.getElementById('assessment-status'),report=document.getElementById('assessment-print-report');
const mailForm=document.getElementById('assessment-mail-form'),mailStatus=document.getElementById('assessment-mail-status'),sendButton=document.getElementById('assessment-send');
const labels=[['class1','Průměrné riziko',1],['class2','Vysoké riziko',2],['class3','Velmi vysoké riziko',5]];
let model=null,allowed=new Map(),token='',ready=false,created=Date.now();
const el=(tag,className,text)=>{const e=document.createElement(tag);if(className)e.className=className;if(text!==undefined)e.textContent=text;return e};
const plain=t=>t.replace(/&deg;/g,'°').replace(/<sup>(.*?)<\/sup>/g,'^$1').replace(/<[^>]+>/g,'');
function total(){const ids=[...questionsNode.querySelectorAll('input[data-score]:checked')].map(i=>i.value);const points=ids.reduce((n,id)=>n+(allowed.get(id)?.score||0),0);return {ids,points,classification:points>=26?'Třída 3':points>=18?'Třída 2':points>=15?'Třída 1':'Bez klasifikace'};}
function update(){
 const score=total();pts.textContent=score.points;cl.textContent=score.classification;
 status.textContent=score.points<15?'Původní stupnice přiřazuje třídu až od 15 bodů.':'Vybráno '+score.ids.length+' charakteristik · průběžný výsledek.';
 let print=el('div');print.append(el('h2','','Klasifikace endodontických případů'),el('p','',score.points+' bodů · '+score.classification));
 for(const group of model.groups){const sec=el('section');sec.append(el('h2','',group.name));for(const q of group.questions){const a=[...allowed.entries()].filter(([id,x])=>x.group===group.name&&x.question===q.name&&score.ids.includes(id));if(!a.length)continue;sec.append(el('h3','',q.name));const ul=el('ul');for(const [,x] of a)ul.append(el('li','',plain(x.label)+' ('+x.score+' b.)'));sec.append(ul)}print.append(sec)}
 report.replaceChildren(...print.childNodes);return score;
}
function render(){
 questionsNode.replaceChildren();allowed=new Map();
 model.groups.forEach((group,g)=>{const section=el('section','assessment-group'),header=el('h2','',group.name);section.append(header);
 group.questions.forEach((q,i)=>{const item=el('div','assessment-question');item.append(el('h3','',(i+1)+'. '+q.name));const cols=el('div','assessment-columns');
 for(const [key,label,score] of labels){const col=el('div','assessment-column');col.append(el('h4','',label+' · '+score+' bod'+(score===1?'':score===2?'y':'ů')));const options=q[key]||[];
 if(!options.length)col.append(el('span','assessment-empty','—'));
 options.forEach((option,j)=>{const id='g'+g+'q'+i+'p'+score+'i'+j;allowed.set(id,{score,label:option.name,group:group.name,question:q.name});const optionLabel=el('label','assessment-option');const input=el('input');input.type='checkbox';input.value=id;input.dataset.score=String(score);input.name='assessment-choice';const text=el('span','',plain(option.name));optionLabel.append(input,text);col.append(optionLabel)});cols.append(col)}
 item.append(cols);section.append(item)});
 questionsNode.append(section)});
 questionsNode.addEventListener('change',update);update();
}
function preview(){const host=location.hostname.toLowerCase();return host==='metju120.github.io'||host==='localhost'&&location.pathname.includes('ces-web');}
async function prepare(){
 if(preview()){mailStatus.textContent='V náhledu GitHub Pages lze vyhodnotit a vytisknout formulář. Odesílání začne fungovat na produkčním hostingu.';return}
 try{const r=await fetch('api/obtiznost.php',{credentials:'same-origin',headers:{Accept:'application/json'},signal:AbortSignal.timeout(10000)});if(!r.ok)throw Error('Server nedostupný');const j=await r.json();if(!j.token)throw Error('Odesílání není aktivní');token=j.token;ready=true;sendButton.disabled=false;mailStatus.textContent='Odesílání je dostupné.'}catch{mailStatus.textContent='Odesílání zatím není dostupné. Hodnocení a tisk můžete použít bez omezení.'}
}
mailForm.addEventListener('submit',async e=>{
 e.preventDefault();if(!ready||!token||!mailForm.reportValidity())return;
 const score=update();if(score.points<15){mailStatus.textContent='Pro odeslání vyberte údaje umožňující přiřadit třídu (alespoň 15 bodů).';mailStatus.className='assessment-mail-error';return}
 const fd=new FormData(mailForm);fd.set('token',token);fd.set('answers',JSON.stringify(score.ids));if(Date.now()-created<3000){mailStatus.textContent='Před odesláním prosím zkontrolujte vyplněná data.';return}
 for(const field of ['file1','file2']){const file=fd.get(field);if(file instanceof File&&file.size>5*1024*1024){mailStatus.textContent='Příloha je větší než 5 MB.';mailStatus.className='assessment-mail-error';return}}
 sendButton.disabled=true;mailStatus.textContent='Odesílám výsledky a přílohy…';mailStatus.className='muted';
 try{const response=await fetch('api/obtiznost.php',{method:'POST',body:fd,credentials:'same-origin',headers:{Accept:'application/json'},signal:AbortSignal.timeout(45000)});const result=await response.json();if(!response.ok||!result.ok)throw Error(result.error||'Odesílání se nezdařilo');mailStatus.className='assessment-mail-ok';mailStatus.textContent='Výsledky byly předány k odeslání. Číslo: '+result.reference+'. Ověřte doručení e-mailu.';ready=false}
 catch(err){mailStatus.className='assessment-mail-error';mailStatus.textContent=err.message||'Odeslání se nezdařilo';sendButton.disabled=false}
});
document.getElementById('assessment-print').addEventListener('click',()=>{if(model){update();window.print()}});
fetch('assets/assessment/questions.json',{headers:{Accept:'application/json'}}).then(r=>{if(!r.ok)throw Error('data');return r.json()}).then(j=>{if(!Array.isArray(j.groups))throw Error('format');model=j;render();prepare()}).catch(()=>{questionsNode.textContent='Hodnoticí kritéria se nepodařilo načíst. Obnovte stránku.';mailStatus.textContent='Odesílání není dostupné.'});
})();