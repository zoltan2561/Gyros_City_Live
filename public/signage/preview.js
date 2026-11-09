(() => {
    'use strict';
    const data=JSON.parse(document.getElementById('preview-data').textContent),stage=document.getElementById('signage-stage');
    const receipt={ttl:Date.parse(data.catalog.valid_until)-Date.parse(data.catalog.server_time),monotonic:performance.now(),wall:Date.now()};
    const fresh=()=>GCSignage.priceFresh(receipt,performance.now(),Date.now());
    stage.addEventListener('signage:overflow',e=>{let warning=document.getElementById('preview-warning');if(!e.detail.names.length){warning?.remove();return;}if(!warning){warning=document.createElement('p');warning.id='preview-warning';warning.className='preview-warning';warning.setAttribute('role','alert');document.body.append(warning);}warning.textContent='A terméknév túllóg. Csökkentsd a betűméretet vagy módosítsd a kártyát: '+e.detail.names.join(', ');});
    let index=0,renderer=null,started=0,paused=false,pauseTime=0;
    function show(nextIndex){
        if(!fresh()){GCSignage.fallback(stage);renderer=null;return;}
        index=nextIndex%data.snapshot.scenes.length;const scene=data.snapshot.scenes[index];if(!scene)return;
        renderer=GCSignage.render(stage,scene,data.catalog.products);renderer.pause(paused);started=performance.now();if(paused)pauseTime=started;
        stage.dataset.scene=String(index);
        document.querySelectorAll('[data-scene]').forEach(b=>b.setAttribute('aria-pressed',String(Number(b.dataset.scene)===index)));
        const video=stage.querySelector('video.scene-media');if(video&&scene.play_to_end)video.addEventListener('ended',()=>{if(!paused)show(index+1);},{once:true});
    }
    document.querySelectorAll('[data-scene]').forEach(b=>b.addEventListener('click',()=>show(Number(b.dataset.scene))));
    document.getElementById('preview-pause')?.addEventListener('click',e=>{
        paused=!paused;if(paused)pauseTime=performance.now();else started+=performance.now()-pauseTime;
        renderer?.pause(paused);stage.querySelectorAll('video').forEach(v=>paused?v.pause():v.play().catch(()=>{}));
        e.target.textContent=paused?'Lejátszás':'Szünet';e.target.setAttribute('aria-pressed',String(paused));
    });
    setInterval(()=>{
        if(!fresh()){if(renderer){GCSignage.fallback(stage);renderer=null;}return;}
        if(paused)return;const scene=data.snapshot.scenes[index];if(!scene)return;
        const duration=scene.play_to_end&&scene.media?.duration?scene.media.duration+10:Number(scene.duration||15);
        if(performance.now()-started>=duration*1000)show(index+1);
    },200);
    show(0);
})();
