(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.GCSignage = api;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';
    function cardsForScene(scene, products) {
        const index = new Map(products.map(p => [p.id, p]));
        const cards = new Map();
        for (const binding of scene.bindings || []) {
            const row = index.get(binding.id);
            if (!row || !row.available || !row.formatted_price) continue;
            const key = binding.card_id || binding.id;
            if (!cards.has(key)) cards.set(key, {name:binding.title || row.name, image:row.image, rows:[]});
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
        const cards=cardsForScene(scene,products);
        container.replaceChildren(); container.dataset.count=String(cards.length);
        for (const card of cards) {
            const article=element('article','food-card');
            const visual=element('div','food-visual');
            if (card.image) {
                const img=element('img','food-photo'); img.src=card.image; img.alt=''; img.onerror=()=>img.remove(); visual.append(img);
            }
            article.append(visual,element('h2','food-name',card.name));
            const prices=element('div','food-prices');
            for (const row of card.rows) {
                const line=element('div','food-price-row');
                if (row.label) line.append(element('span','food-label',row.label));
                if (row.formatted_reference_price) line.append(element('s','food-reference',row.formatted_reference_price));
                line.append(element('strong','food-price',row.formatted_price)); prices.append(line);
            }
            article.append(prices); container.append(article);
        }
        if (!cards.length) container.append(element('p','empty-scene','Gyros City · Frissen készítjük.'));
    }
    function render(stage, scene, products) {
        stage.replaceChildren(); stage.className='signage-stage theme-'+(scene.theme==='light'?'light':'dark')+' scene-'+scene.type;
        stage.style.setProperty('--scene-bg', /^#[0-9a-f]{6}$/i.test(scene.background || '')?scene.background:'');
        stage.style.setProperty('--scene-accent', /^#[0-9a-f]{6}$/i.test(scene.accent || '')?scene.accent:'');
        stage.style.setProperty('--scene-text', /^#[0-9a-f]{6}$/i.test(scene.text_color || '')?scene.text_color:'');
        stage.style.setProperty('--name-size',String(Number(scene.name_size)||36));
        stage.style.setProperty('--price-size',String(Number(scene.price_size)||50));
        stage.style.setProperty('--card-gap',String(Number(scene.gap)||2));
        stage.style.fontFamily=['Arial','Trebuchet MS','Georgia'].includes(scene.font_family)?scene.font_family:'Arial';
        if(scene.media_side==='right')stage.classList.add('media-right');
        if (scene.background_media) { const bg=mediaElement(scene.background_media,true); if(bg) stage.append(bg); }
        const header=element('header','scene-heading');
        header.append(element('div','scene-brand','GYROS CITY'),element('h1','scene-title',scene.title || 'Frissen készítjük.'));
        if(scene.logo){const logo=mediaElement(scene.logo,false);if(logo){logo.className='scene-logo';header.firstElementChild.replaceWith(logo);}}
        stage.append(header);
        const body=element('div','scene-body');
        if (['media','split'].includes(scene.type)) { const media=mediaElement(scene.media,false); if(media) body.append(media); }
        const foregroundVideo=body.querySelector('video');if(foregroundVideo)foregroundVideo.loop=!scene.play_to_end;
        const grid=element('div','food-grid');
        if (scene.type !== 'media') { updateCards(grid,scene,products); body.append(grid); }
        stage.append(body,element('footer','scene-footer','Helyben készítve. Neked.'));
        if(typeof requestAnimationFrame==='function')requestAnimationFrame(()=>{
            const names=[...stage.querySelectorAll('.food-name')].filter(e=>e.scrollHeight>e.clientHeight+2).map(e=>e.textContent);
            if(names.length)stage.dispatchEvent(new CustomEvent('signage:overflow',{bubbles:true,detail:{names}}));
        });
        return {update(products) { if(scene.type!=='media') updateCards(grid,scene,products); }};
    }
    function fallback(stage) {
        stage.replaceChildren(); stage.className='signage-stage signage-fallback theme-dark';
        stage.append(element('div','fallback-brand','GYROS CITY'),element('p','','Frissen készítjük.'));
    }
    return {cardsForScene, priceFresh, render, fallback};
}));
