<!doctype html>
<html lang="hu"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Gyros City · TV előnézet</title>
<link rel="stylesheet" href="{{ url('public/signage/player.css') }}?v=1"></head>
<body><main id="signage-stage" class="signage-stage"></main>
@unless(request()->boolean('fullscreen'))
<aside class="preview-controls" aria-label="Előnézet beállításai">
<label for="preview-theme">Téma</label><select id="preview-theme"><option value="dark">Sötét / burger</option><option value="light">Világos / étlap</option></select>
<button type="button" id="preview-fullscreen">Teljes képernyő</button>
<button type="button" id="preview-replay">Beúszás újra</button>
<p>Katalógusárak. A helyszíni árprofil még ellenőrizendő.</p></aside>
@endunless
<div class="preview-status" id="preview-status" role="status">Katalógus betöltése…</div>
<script src="{{ url('public/signage/renderer.js') }}?v=1"></script>
<script>
(() => {
    const scene=@json($scene), stage=document.getElementById('signage-stage'), status=document.getElementById('preview-status');
    let renderer=null,products=[],receipt=null,busy=false;
    GCSignage.fallback(stage);
    async function refresh() {
        if(busy)return; busy=true;
        try {
            const response=await fetch(@json(route('signage.demo', ['catalog'=>1])),{cache:'no-store'});
            if(!response.ok)throw new Error('catalog');
            const data=await response.json();
            const ttl=Date.parse(data.valid_until)-Date.parse(data.server_time);
            if(!Array.isArray(data.products)||!(ttl>0))throw new Error('catalog');
            products=data.products; receipt={ttl,monotonic:performance.now(),wall:Date.now()};
            if(renderer)renderer.update(products); else renderer=GCSignage.render(stage,scene,products);
            status.textContent='Árak frissítve · '+new Date().toLocaleTimeString('hu-HU');
        } catch(e) {status.textContent='Nincs friss kapcsolat';}
        finally {busy=false;}
    }
    function replay(){
        if(GCSignage.priceFresh(receipt,performance.now(),Date.now()))renderer=GCSignage.render(stage,scene,products);
        else{GCSignage.fallback(stage);renderer=null;}
    }
    document.getElementById('preview-theme')?.addEventListener('change',e=>{scene.theme=e.target.value;replay();});
    document.getElementById('preview-replay')?.addEventListener('click',replay);
    document.getElementById('preview-fullscreen')?.addEventListener('click',()=>document.documentElement.requestFullscreen().catch(()=>{status.textContent='A teljes képernyőt a böngésző nem engedélyezte.';}));
    setInterval(()=>{if(!GCSignage.priceFresh(receipt,performance.now(),Date.now())){GCSignage.fallback(stage);renderer=null;}},1000);
    setInterval(refresh,30000); window.addEventListener('online',refresh); refresh();
})();
</script></body></html>
