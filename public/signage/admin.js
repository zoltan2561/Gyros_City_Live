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
    const backdrop=document.getElementById('scene-backdrop');
    const backgroundOptions=JSON.parse(document.getElementById('signage-background-options')?.textContent||'{}');
    function backgroundPreview(){
        const sample=document.getElementById('scene-background-preview');if(!sample)return;
        sample.dataset.signageBackdrop=backdrop.value;
        for(const [field,variable]of [['bg','bg'],['accent','accent'],['text-color','text']])sample.style.setProperty('--scene-'+variable,document.getElementById('scene-'+field).value);
    }
    function backgroundVisibility(){
        const reference=document.getElementById('scene-design')?.value!=='standard';
        const options=document.getElementById('scene-backdrop-options');if(options)options.hidden=reference;
        if(backdrop)backdrop.disabled=reference;
    }
    backdrop?.addEventListener('change',()=>{
        const preset=backgroundOptions[backdrop.value];
        if(preset){
            document.getElementById('scene-theme').value=preset.theme;
            document.getElementById('scene-bg').value=preset.background;
            document.getElementById('scene-accent').value=preset.accent;
            document.getElementById('scene-text-color').value=preset.text_color;
            document.getElementById('scene-background_media_id').value='';
        }
        backgroundPreview();
    });
    for(const id of ['scene-bg','scene-accent','scene-text-color'])document.getElementById(id)?.addEventListener('input',backgroundPreview);
    backgroundVisibility();backgroundPreview();
    document.getElementById('scene-design')?.addEventListener('change',e=>{
        const types={'reference-burger':'grid','reference-menu':'grid','reference-kebab':'grid','reference-pizza':'slideshow','reference-intro':'featured','modern-burger':'slideshow','modern-menu':'grid','modern-pizza':'slideshow'};
        if(types[e.target.value])document.getElementById('scene-type').value=types[e.target.value];
        const defaults=JSON.parse(document.getElementById('signage-design-options')?.textContent||'{}')[e.target.value];
        if(defaults){
            for(const [field,id]of [['theme','scene-theme'],['background','scene-bg'],['accent','scene-accent'],['text_color','scene-text-color'],['slide_seconds','scene-slide-seconds']])if(defaults[field]!==undefined)document.getElementById(id).value=defaults[field];
            document.getElementById('scene-background_media_id').value='';backgroundPreview();
        }
        if(e.target.value==='reference-pizza')document.getElementById('scene-slide-seconds').value='20';sceneType();
        backgroundVisibility();
    });
    document.getElementById('scene-theme')?.addEventListener('change',e=>{
        const light=e.target.value==='light';document.getElementById('scene-bg').value=light?'#f6f2f2':'#190c0a';
        document.getElementById('scene-accent').value=light?'#a61264':'#ffc747';document.getElementById('scene-text-color').value=light?'#22131f':'#fff3d2';
        backgroundPreview();
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
    document.getElementById('playlist-switch')?.addEventListener('change',e=>{if(e.target.value)window.location.assign(e.target.value);});
    const sceneOptions=JSON.parse(document.getElementById('playlist-options')?.textContent||'[]');
    function playlistState(){
        if(!playlist)return;
        const count=playlist.children.length;
        document.getElementById('playlist-empty').hidden=count>0;
        document.getElementById('playlist-summary').textContent=count+' jelenet · folyamatos ismétlés';
        const save=document.getElementById('playlist-save');save.disabled=count===0;
        save.textContent=document.querySelector('#playlist-form input[name="screen_ids[]"]:checked')?'Mentés és TV-k frissítése':'Lista mentése';
        [...playlist.children].forEach((row,index)=>{
            row.querySelector('[data-action="up"]').disabled=index===0;
            row.querySelector('[data-action="down"]').disabled=index===count-1;
        });
    }
    function addScene(scene){
        const {id,name}=scene;
        const row=document.createElement('li');row.className='list-group-item playlist-row';row.draggable=true;
        const label=document.createElement('span');label.className='playlist-label';
        const title=document.createElement('strong');title.textContent=name;label.append(title);
        if(scene.duration){const duration=document.createElement('small');duration.className='text-muted d-block';duration.textContent=scene.duration+' mp';label.append(duration);}
        const input=document.createElement('input');input.type='hidden';input.name='scene_ids[]';input.value=id;row.append(label,input);
        if(scene.edit_url){const edit=document.createElement('a');edit.className='btn btn-sm btn-outline-primary';edit.href=scene.edit_url;edit.textContent='Tartalom';edit.target='_blank';edit.rel='noopener';edit.setAttribute('aria-label',name+' · Tartalom szerkesztése új lapon');row.append(edit);}
        for(const [action,text]of [['up','Fel'],['down','Le'],['remove','Eltávolítás']]){const b=document.createElement('button');b.type='button';b.className='btn btn-sm btn-outline-secondary';b.dataset.action=action;b.textContent=text;b.setAttribute('aria-label',name+' · '+text);row.append(b);}
        playlist.append(row);playlistState();
    }
    document.getElementById('playlist-scene')?.addEventListener('change',e=>{document.getElementById('add-playlist-scene').disabled=!e.target.value||playlist.children.length>=100;});
    document.getElementById('add-playlist-scene')?.addEventListener('click',()=>{
        const select=document.getElementById('playlist-scene'),scene=sceneOptions.find(s=>String(s.id)===select.value);
        if(scene&&playlist.children.length<100){addScene(scene);select.value='';select.dispatchEvent(new Event('change'));select.focus();}
    });
    const existing=document.getElementById('playlist-initial');if(existing)JSON.parse(existing.textContent).forEach(addScene);
    document.getElementById('playlist-form')?.addEventListener('change',playlistState);playlistState();
    playlist?.addEventListener('click',e=>{
        const action=e.target.dataset.action,row=e.target.closest('li');if(!row||!action)return;
        if(action==='up'&&row.previousElementSibling)row.previousElementSibling.before(row);
        if(action==='down'&&row.nextElementSibling)row.nextElementSibling.after(row);
        if(action==='remove'){row.remove();document.getElementById('playlist-scene').dispatchEvent(new Event('change'));document.getElementById('playlist-scene').focus();}
        playlistState();
        if(e.target.disabled)row.querySelector('[data-action="'+(action==='up'?'down':'up')+'"]').focus();
    });
    let dragged=null;playlist?.addEventListener('dragstart',e=>{dragged=e.target.closest('li');if(e.dataTransfer)e.dataTransfer.setData('text/plain',dragged?.querySelector('input').value||'');});
    playlist?.addEventListener('dragover',e=>e.preventDefault());playlist?.addEventListener('dragend',()=>{dragged=null;});
    playlist?.addEventListener('drop',e=>{e.preventDefault();const target=e.target.closest('li');if(target&&dragged&&target!==dragged){const after=e.clientY>target.getBoundingClientRect().top+target.getBoundingClientRect().height/2;if(after)target.after(dragged);else target.before(dragged);}dragged=null;playlistState();});
    const dialog=document.createElement('dialog');dialog.className='signage-dialog';
    const title=document.createElement('h2');title.className='h5';title.id='signage-confirm-title';title.textContent='Művelet megerősítése';
    const message=document.createElement('p'),actions=document.createElement('div');actions.className='dialog-actions';
    const cancel=document.createElement('button'),accept=document.createElement('button');cancel.type=accept.type='button';cancel.className='btn btn-outline-secondary';accept.className='btn btn-primary';cancel.textContent='Mégse';accept.textContent='Megerősítés';actions.append(cancel,accept);dialog.append(title,message,actions);dialog.setAttribute('aria-labelledby',title.id);document.body.append(dialog);
    let pending=null,trigger=null;cancel.addEventListener('click',()=>dialog.close());dialog.addEventListener('close',()=>{pending=null;trigger?.focus();});
    accept.addEventListener('click',()=>{const form=pending;dialog.close();if(form)send(form);});
    function send(form){form.setAttribute('aria-busy','true');form.querySelectorAll('button[type=submit],button:not([type])').forEach(b=>b.disabled=true);HTMLFormElement.prototype.submit.call(form);}
    document.querySelectorAll('.signage-admin form').forEach(form=>{
    form.addEventListener('input',e=>{
        if(e.target.getAttribute('aria-invalid')==='true'&&e.target.checkValidity()){
            e.target.removeAttribute('aria-invalid');
            if(!form.querySelector('[aria-invalid="true"]'))form.querySelector('.form-validation-error')?.remove();
        }
    });
    form.addEventListener('submit',e=>{
        e.preventDefault();if(form.getAttribute('aria-busy')==='true')return;
        const invalid=[...form.elements].find(el=>el.willValidate&&!el.checkValidity());
        if(invalid){let alert=form.querySelector('.form-validation-error');if(!alert){alert=document.createElement('p');alert.className='form-validation-error text-danger';alert.setAttribute('role','alert');form.prepend(alert);}alert.textContent='Töltsd ki vagy javítsd a megjelölt mezőt.';invalid.setAttribute('aria-invalid','true');invalid.focus();return;}
        if(form.dataset.confirm){pending=form;trigger=e.submitter;message.textContent=form.dataset.confirm;dialog.showModal();cancel.focus();}else send(form);
    });
    });
    document.getElementById('signage-errors')?.focus();
})();
