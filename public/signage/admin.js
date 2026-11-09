(() => {
    'use strict';
    const optionsNode=document.getElementById('signage-catalog-options');
    const data=optionsNode?JSON.parse(optionsNode.textContent):null;
    function choices(row,restore) {
        if(!data)return;
        const target=row.querySelector('.binding-choices'),selected=row.querySelector('.binding-item').value;
        const item=data.items.find(i=>String(i.id)===selected);target.replaceChildren();
        const index=row.dataset.index;
        const previous={addons:restore?JSON.parse(target.dataset.addons||'[]'):[],extras:restore?JSON.parse(target.dataset.extras||'[]'):[]};
        for(const kind of ['addons','extras']) {
            const list=data[kind].filter(c=>kind==='addons'?Object.values(item?.groups||[]).map(String).includes(String(c.addongroup_id)):String(c.item_id)===selected);
            if(!list.length)continue;
            const fieldset=document.createElement('fieldset'),legend=document.createElement('legend');legend.className='h6 mt-3';legend.textContent=kind==='addons'?'Feltétek / választások':'Extrák';fieldset.append(legend);
            for(const choice of list){
                const label=document.createElement('label');label.className='form-check form-check-inline';const input=document.createElement('input');input.type='checkbox';input.className='form-check-input';input.name='bindings['+index+']['+kind+'][]';input.value=choice.id;input.checked=previous[kind].map(String).includes(String(choice.id));
                const span=document.createElement('span');span.className='form-check-label';span.textContent=choice.name+' (+'+choice.price+')';label.append(input,span);fieldset.append(label);
            }
            target.append(fieldset);
        }
    }
    document.querySelectorAll('.binding-row').forEach(r=>choices(r,true));
    function sceneType(){const slides=document.getElementById('scene-type')?.value==='slideshow';const options=document.getElementById('slide-options');if(options)options.hidden=!slides;const duration=document.getElementById('scene-duration');if(duration)duration.readOnly=slides;}
    document.getElementById('scene-type')?.addEventListener('change',sceneType);sceneType();
    document.getElementById('scene-design')?.addEventListener('change',e=>{
        const types={'reference-burger':'grid','reference-menu':'grid','reference-kebab':'grid','reference-pizza':'slideshow','reference-intro':'featured'};
        if(types[e.target.value])document.getElementById('scene-type').value=types[e.target.value];
        if(e.target.value==='reference-pizza')document.getElementById('scene-slide-seconds').value='20';sceneType();
    });
    document.getElementById('scene-theme')?.addEventListener('change',e=>{
        const light=e.target.value==='light';document.getElementById('scene-bg').value=light?'#f6f2f2':'#190c0a';
        document.getElementById('scene-accent').value=light?'#a61264':'#ffc747';document.getElementById('scene-text-color').value=light?'#22131f':'#fff3d2';
    });
    const rows=document.getElementById('binding-rows');
    rows?.addEventListener('change',e=>{if(e.target.matches('.binding-item')){const row=e.target.closest('.binding-row');row.querySelector('.binding-artwork').value='';row.querySelector('input[name$="[title]"]').value='';choices(row,false);}});
    rows?.addEventListener('click',e=>{if(e.target.matches('.remove-binding')){e.target.closest('.binding-row').remove();document.getElementById('add-binding').focus();}});
    let nextIndex=rows?Math.max(0,...[...rows.children].map(r=>Number(r.dataset.index)))+1:0;
    document.getElementById('add-binding')?.addEventListener('click',()=>{
        if(rows.children.length>=24)return;
        const source=document.getElementById('binding-template');const clone=source.content.cloneNode(true);
        clone.querySelectorAll('*').forEach(el=>{for(const attr of [...el.attributes])if(attr.value.includes('INDEX'))el.setAttribute(attr.name,attr.value.replaceAll('INDEX',String(nextIndex)));});
        nextIndex++;rows.append(clone);rows.lastElementChild.querySelector('select').focus();
    });
    const playlist=document.getElementById('playlist-scenes');
    function addScene(id,name){
        const row=document.createElement('li');row.className='list-group-item playlist-row';row.draggable=true;
        const label=document.createElement('span');label.className='playlist-label';label.textContent=name;
        const input=document.createElement('input');input.type='hidden';input.name='scene_ids[]';input.value=id;row.append(label,input);
        for(const [action,text]of [['up','Fel'],['down','Le'],['remove','Eltávolítás']]){const b=document.createElement('button');b.type='button';b.className='btn btn-sm btn-outline-secondary';b.dataset.action=action;b.textContent=text;b.setAttribute('aria-label',name+' · '+text);row.append(b);}
        playlist.append(row);
    }
    document.getElementById('add-playlist-scene')?.addEventListener('click',()=>{const s=document.getElementById('playlist-scene');if(s.value&&playlist.children.length<100)addScene(s.value,s.selectedOptions[0].textContent);});
    const existing=document.getElementById('playlist-initial');if(existing)JSON.parse(existing.textContent).forEach(s=>addScene(s.id,s.name));
    playlist?.addEventListener('click',e=>{const action=e.target.dataset.action,row=e.target.closest('li');if(!row)return;if(action==='up'&&row.previousElementSibling)row.previousElementSibling.before(row);if(action==='down'&&row.nextElementSibling)row.nextElementSibling.after(row);if(action==='remove'){row.remove();document.getElementById('add-playlist-scene').focus();}});
    let dragged=null;playlist?.addEventListener('dragstart',e=>{dragged=e.target.closest('li');});playlist?.addEventListener('dragover',e=>e.preventDefault());playlist?.addEventListener('drop',e=>{e.preventDefault();const target=e.target.closest('li');if(target&&dragged&&target!==dragged)target.before(dragged);dragged=null;});
    const dialog=document.createElement('dialog');dialog.className='signage-dialog';
    const title=document.createElement('h2');title.className='h5';title.id='signage-confirm-title';title.textContent='Művelet megerősítése';
    const message=document.createElement('p'),actions=document.createElement('div');actions.className='dialog-actions';
    const cancel=document.createElement('button'),accept=document.createElement('button');cancel.type=accept.type='button';cancel.className='btn btn-outline-secondary';accept.className='btn btn-primary';cancel.textContent='Mégse';accept.textContent='Megerősítés';actions.append(cancel,accept);dialog.append(title,message,actions);dialog.setAttribute('aria-labelledby',title.id);document.body.append(dialog);
    let pending=null,trigger=null;cancel.addEventListener('click',()=>dialog.close());dialog.addEventListener('close',()=>{pending=null;trigger?.focus();});
    accept.addEventListener('click',()=>{const form=pending;dialog.close();if(form)send(form);});
    function send(form){form.setAttribute('aria-busy','true');form.querySelectorAll('button[type=submit],button:not([type])').forEach(b=>b.disabled=true);HTMLFormElement.prototype.submit.call(form);}
    document.querySelectorAll('.signage-admin form').forEach(form=>form.addEventListener('submit',e=>{
        e.preventDefault();if(form.getAttribute('aria-busy')==='true')return;
        const invalid=[...form.elements].find(el=>el.willValidate&&!el.checkValidity());
        if(invalid){let alert=form.querySelector('.form-validation-error');if(!alert){alert=document.createElement('p');alert.className='form-validation-error text-danger';alert.setAttribute('role','alert');form.prepend(alert);}alert.textContent='Töltsd ki vagy javítsd a megjelölt mezőt.';invalid.setAttribute('aria-invalid','true');invalid.focus();return;}
        if(form.dataset.confirm){pending=form;trigger=e.submitter;message.textContent=form.dataset.confirm;dialog.showModal();cancel.focus();}else send(form);
    }));
    document.getElementById('signage-errors')?.focus();
})();
