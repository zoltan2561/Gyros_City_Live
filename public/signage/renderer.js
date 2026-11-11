(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.GCSignage = api;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';
    const grids = new WeakMap();
    const views = new WeakMap();
    const artworkBase=typeof document!=='undefined'&&document.currentScript?.src?new URL('reference-assets/',document.currentScript.src).href:null;
    const fonts=new Map();
    function cardsForScene(scene, products) {
        const index = new Map(products.map(p => [p.id, p]));
        const cards = new Map();
        for (const binding of scene.bindings || []) {
            const row = index.get(binding.id);
            if (!row || !row.available || !row.formatted_price) continue;
            const key = binding.card_id || binding.id;
            if (!cards.has(key)) cards.set(key, {id:key, name:binding.title || row.name, image:row.image, description:row.description||'', rows:[]});
            cards.get(key).rows.push(row);
        }
        return [...cards.values()];
    }
    function priceFresh(receipt, monotonic, wall) {
        return !!receipt && monotonic >= receipt.monotonic && wall >= receipt.wall
            && monotonic - receipt.monotonic < receipt.ttl && wall - receipt.wall < receipt.ttl;
    }
    function element(tag, className, text) {
        const el=document.createElement(tag); el.className=className;
        if (text !== undefined) el.textContent=text;
        return el;
    }
    function referencePrice(row,design){
        if(['modern-burger','modern-menu','modern-pizza'].includes(design)&&row.price!==null&&row.price!==''&&/(?:HUF|Ft)\s*$/i.test(row.formatted_price||'')&&Number.isFinite(Number(row.price)))return Number(row.price).toLocaleString('hu-HU',{maximumFractionDigits:2})+' Ft';
        if(!design?.startsWith('reference-')||row.price===null||row.price===''||!/(?:HUF|Ft)\s*$/i.test(row.formatted_price||'')||!Number.isFinite(Number(row.price)))return row.formatted_price;
        const value=Number(row.price).toLocaleString('hu-HU',{useGrouping:false,maximumFractionDigits:2});
        return design==='reference-burger'?value:(design==='reference-menu'?value+(Number.isInteger(Number(row.price))?'.-':''):value+' Ft');
    }
    function mediaElement(media, background) {
        if (!media || !media.url) return null;
        const video=media.mime && media.mime.startsWith('video/');
        const el=element(video?'video':'img',background?'scene-background':'scene-media');
        el.src=media.url;
        if (video) { el.muted=true; el.autoplay=true; el.loop=!!background; el.playsInline=true; }
        else el.alt='';
        el.style.objectFit=media.fit==='cover'?'cover':'contain';
        el.style.objectPosition=Number(media.focus_x??50)+'% '+Number(media.focus_y??50)+'%';
        return el;
    }
    function updateCards(container, scene, products) {
        const cards=cardsForScene(scene,products), states=grids.get(container)||new Map();
        grids.set(container,states); container.dataset.count=String(cards.length);
        const active=new Set(cards.map(card=>card.id));
        for (const [id,state] of states) if(!active.has(id)){state.article.remove();states.delete(id);}
        if(cards.length)container.querySelector('.empty-scene')?.remove();
        cards.forEach((card,index)=>{
            let state=states.get(card.id);
            if(!state){
                const article=element('article','food-card'),visual=element('div','food-visual');
                const name=element('h2','food-name'),prices=element('div','food-prices'),description=element('p','food-description');
                article.dataset.cardId=card.id;
                if(['reference-burger','reference-menu','reference-kebab'].includes(scene.design)){const caption=element('div','food-caption');caption.append(name,prices);article.append(visual,caption,description);}
                else article.append(visual,name,prices,description);
                article.style.setProperty('--card-order',String(index));
                state={article,visual,name,prices,description,imageSrc:null,priceSignature:null,nameValue:null}; states.set(card.id,state);
            }
            if(state.nameValue!==card.name){
                state.nameValue=card.name;
                if(scene.design==='reference-pizza'){
                    state.name.replaceChildren();[...card.name].forEach((letter,i)=>{const span=element('span','ref-letter',letter===' '?'\u00a0':letter);span.style.setProperty('--letter',String(i));state.name.append(span);});
                }else state.name.textContent=card.name;
            }
            state.description.textContent=card.description;
            if(state.imageSrc!==(card.image||null)){
                state.visual.replaceChildren(); state.imageSrc=card.image||null;
                if(card.image){const img=element('img','food-photo');img.src=card.image;img.alt='';img.onerror=()=>{if(state.visual.firstElementChild===img)state.imageSrc=null;img.remove();};state.visual.append(img);}
            }
            const signature=JSON.stringify(card.rows.map(row=>[row.id,row.label,row.price,row.formatted_reference_price,row.formatted_price]));
            if(state.priceSignature!==signature){
            state.prices.replaceChildren(); state.priceSignature=signature;
            for (const row of card.rows) {
                const line=element('div','food-price-row');
                if (row.label) line.append(element('span','food-label',row.label));
                if (row.formatted_reference_price) line.append(element('s','food-reference',row.formatted_reference_price));
                line.append(element('strong','food-price',referencePrice(row,scene.design))); state.prices.append(line);
            }
            }
            if(container.children[index]!==state.article)container.insertBefore(state.article,container.children[index]||null);
        });
        if (!cards.length&&!container.querySelector('.empty-scene'))container.append(element('p','empty-scene','Gyros City · Frissen készítjük.'));
    }
    function atmosphere(reference=false){
        const layer=element('div','kiosk-atmosphere');layer.setAttribute('aria-hidden','true');
        layer.append(element('span','kiosk-glow kiosk-glow-warm'),element('span','kiosk-glow kiosk-glow-accent'));
        for(let i=0;i<(reference?64:12);i++){
            const spark=element('span','kiosk-ember');
            spark.style.setProperty('--spark-x',String((i*37+11)%100));
            spark.style.setProperty('--spark-drift',String((i*19)%90-45));
            spark.style.setProperty('--spark-duration',(reference?2.6+(i%7)*.4:12+i%7)+'s');
            spark.style.setProperty('--spark-delay',(-i*1.7)+'s');
            spark.style.setProperty('--spark-size',(2+(i*7)%11)+'px');
            layer.append(spark);
        }
        return layer;
    }
    function checkNames(stage){
        if(typeof requestAnimationFrame==='function')requestAnimationFrame(()=>{
            const elements=[...stage.querySelectorAll('.food-name')].filter(e=>e.clientHeight>0);
            if(typeof getComputedStyle==='function')for(const name of elements){
                name.style.fontSize='';
                let size=parseFloat(getComputedStyle(name).fontSize),min=Math.max(18,size*.65);
                while(name.scrollHeight>name.clientHeight+4&&size>min){size=Math.max(min,size-1);name.style.fontSize=size+'px';}
            }
            const names=elements.filter(e=>e.scrollHeight>e.clientHeight+4).map(e=>e.textContent);
            stage.dispatchEvent(new CustomEvent('signage:overflow',{bubbles:true,detail:{names}}));
        });
    }
    function referenceAssets(stage,scene){
        const assets=scene.reference_assets||{},pending=[];
        for(const key of ['embers','smoke','wood','grunge-mask'])stage.style.setProperty('--reference-'+key,assets[key]?.url?'url('+JSON.stringify(assets[key].url)+')':'');
        for(const [key,variable] of [['display-font','--reference-display-font'],['script-font','--reference-script-font']]){
            const asset=assets[key];stage.style.setProperty(variable,'');
            if(!asset?.url||typeof FontFace==='undefined'||!document.fonts)continue;
            let entry=fonts.get(asset.checksum);
            if(!entry){
                const family='GCRef-'+asset.checksum.slice(0,20),face=new FontFace(family,'url('+JSON.stringify(asset.url)+')',{display:'block'});
                document.fonts.add(face);entry={family,promise:face.load()};fonts.set(asset.checksum,entry);
                entry.promise.catch(()=>{if(fonts.get(asset.checksum)===entry)fonts.delete(asset.checksum);document.fonts.delete(face);});
            }
            stage.style.setProperty(variable,entry.family);pending.push(entry.promise);
        }
        return Promise.allSettled(pending);
    }
    function render(stage, scene, products) {
        views.get(stage)?.dispose();
        stage.replaceChildren(); stage.className='signage-stage theme-'+(scene.theme==='light'?'light':'dark')+' scene-'+scene.type;
        const reference=['reference-burger','reference-menu','reference-pizza','reference-intro','reference-kebab'].includes(scene.design);
        const modern=['modern-burger','modern-menu','modern-pizza'].includes(scene.design);
        if(reference)stage.classList.add('reference-scene',scene.design);
        if(modern)stage.classList.add('modern-scene',scene.design);
        delete stage.dataset.signageBackdrop;
        if(!reference&&!modern&&['graphite','embers','wood','wine','cream','solid'].includes(scene.backdrop))stage.dataset.signageBackdrop=scene.backdrop;
        stage.style.setProperty('--scene-bg', /^#[0-9a-f]{6}$/i.test(scene.background || '')?scene.background:'');
        stage.style.setProperty('--scene-accent', /^#[0-9a-f]{6}$/i.test(scene.accent || '')?scene.accent:'');
        stage.style.setProperty('--scene-text', /^#[0-9a-f]{6}$/i.test(scene.text_color || '')?scene.text_color:'');
        stage.style.setProperty('--name-size',String(Number(scene.name_size)||36));
        stage.style.setProperty('--price-size',String(Number(scene.price_size)||50));
        stage.style.setProperty('--card-gap',String(Number(scene.gap)||2));
        stage.style.fontFamily=['Arial','Trebuchet MS','Georgia'].includes(scene.font_family)?scene.font_family:'Arial';
        stage.dataset.paused='false';
        const fontsReady=referenceAssets(stage,scene);
        if(scene.media_side==='right')stage.classList.add('media-right');
        if (scene.background_media) { const bg=mediaElement(scene.background_media,true); if(bg) stage.append(bg); }
        stage.append(atmosphere(reference));
        if(scene.type!=='media')stage.append(element('div','scene-word',scene.title||'GYROS CITY'));
        const header=element('header','scene-heading');
        header.append(element('div','scene-brand','GYROS CITY'),element('h1','scene-title',scene.title || 'Frissen készítjük.'));
        if(scene.logo){const logo=mediaElement(scene.logo,false);if(logo){logo.className='scene-logo';header.firstElementChild.replaceWith(logo);}}
        stage.append(header);
        if(scene.design==='reference-intro'){
            const promo=element('div','reference-promo');promo.append(element('p','promo-eyebrow','MEGÉRI PIZZÁZNI!'),element('div','promo-brand','Gyros City'),element('h1','promo-title','PIZZA TIME!'),element('p','promo-copy','Minimum 2 db pizza rendeléstől\najándék palacsintát adunk'),element('div','promo-cta','RENDELJ MOST'),element('p','promo-site','gyroscity.eu'));
            stage.append(promo);const dessertUrl=scene.reference_assets?.['promo-dessert']?.url||(artworkBase?artworkBase+'promo-dessert.webp':null);if(dessertUrl){const dessert=element('img','promo-dessert');dessert.src=dessertUrl;dessert.alt='';stage.append(dessert);}
        }
        const body=element('div','scene-body');
        if (['media','split'].includes(scene.type)) { const media=mediaElement(scene.media,false); if(media) body.append(media); }
        const foregroundVideo=body.querySelector('video');if(foregroundVideo)foregroundVideo.loop=!scene.play_to_end;
        const grid=element('div','food-grid');
        if (scene.type !== 'media') { updateCards(grid,scene,products); body.append(grid); }
        const footer=element('footer','scene-footer');
        const ticker=element('div','scene-ticker');ticker.setAttribute('aria-hidden','true');
        for(let i=0;i<4;i++)ticker.append(element('span','','GYROS CITY  /  HELYBEN KÉSZÍTVE. NEKED.  /  '));
        if(modern)footer.append(element('span','modern-footer-brand','GYROS CITY'),element('span','modern-footer-site','gyroscity.eu'));
        else footer.append(ticker);
        stage.append(body,footer);
        let activeId=null,disposed=false,timer=null,paused=false;
        const slideshow=scene.type==='slideshow';
        const seconds=slideshow?Math.max(5,Math.min(20,Number(scene.slide_seconds)||7)):3.2;
        const now=()=>typeof performance!=='undefined'?performance.now():Date.now();
        let remaining=seconds*1000,deadline=0;
        stage.style.setProperty('--slide-seconds',seconds+'s');
        stage.style.setProperty('--price-reveal-delay',Math.min(6,Math.max(0,seconds-3))+'s');
        const progress=element('div','slide-progress'),counter=element('div','slide-counter'),next=element('div','slide-next');
        if(slideshow)body.append(counter,next,progress);
        function select(advance=false){
            if(disposed)return;
            const cards=[...grid.children].filter(card=>card.className==='food-card');
            let index=cards.findIndex(card=>card.dataset.cardId===activeId);
            if(advance)index=(index+1)%Math.max(1,cards.length);
            if(index<0)index=0;
            const selected=cards[index],changed=activeId!==(selected?.dataset.cardId||null);
            activeId=selected?.dataset.cardId||null;
            cards.forEach((card,i)=>{card.dataset.focused=String(i===index);if(slideshow)card.hidden=i!==index;});
            if(slideshow){
                counter.textContent=cards.length?String(index+1).padStart(2,'0')+' / '+String(cards.length).padStart(2,'0'):'';
                next.textContent=cards.length>1?'Következik: '+cards[(index+1)%cards.length].querySelector('.food-name').textContent:'';
                if(changed)progress.replaceChildren(element('span','slide-progress-fill'));
            }
            checkNames(stage);
        }
        function start(){if(!disposed&&!paused&&scene.type!=='media'&&typeof setInterval==='function'){
            deadline=now()+remaining;
            timer=setInterval(()=>{stop();select(true);remaining=seconds*1000;start();},remaining);
        }}
        function stop(){if(timer!==null){clearInterval(timer);timer=null;}}
        const view={
            update(products){if(!disposed&&scene.type!=='media'){updateCards(grid,scene,products);select();}},
            pause(value){if(disposed||paused===!!value)return;paused=!!value;stage.dataset.paused=String(paused);if(paused)remaining=Math.max(1,deadline-now());stop();if(!paused)start();},
            dispose(){if(disposed)return;disposed=true;stop();if(views.get(stage)===view)views.delete(stage);}
        };
        views.set(stage,view);select();start();
        fontsReady.then(()=>{if(views.get(stage)===view)checkNames(stage);});
        checkNames(stage);
        return view;
    }
    function fallback(stage) {
        views.get(stage)?.dispose();
        stage.replaceChildren(); stage.className='signage-stage signage-fallback theme-dark';
        delete stage.dataset.signageBackdrop;
        stage.append(element('div','fallback-brand','GYROS CITY'),element('p','','Frissen készítjük.'));
    }
    return {cardsForScene, priceFresh, referencePrice, render, fallback};
}));
