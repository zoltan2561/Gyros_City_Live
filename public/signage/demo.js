(() => {
    'use strict';
    const config=JSON.parse(document.getElementById('demo-config').textContent);
    const stage=document.getElementById('signage-stage'),status=document.getElementById('preview-status');
    let programs=[],products=[],receipt=null,renderer=null,currentScene=null,busy=false,index=0,started=0,paused=false,pauseTime=0;
    let programId=new URL(location.href).searchParams.get('program')||'all';
    let initialScene=Math.max(0,Math.min(99,Number(new URL(location.href).searchParams.get('scene'))||0));
    const fresh=()=>GCSignage.priceFresh(receipt,performance.now(),Date.now());
    const program=()=>programs.find(p=>p.id===programId)||programs[0];
    function show(nextIndex=0){
        if(!fresh()){GCSignage.fallback(stage);renderer=null;return;}
        const scenes=program()?.scenes||[];
        if(!scenes.length){GCSignage.fallback(stage);renderer=null;return;}
        index=nextIndex%scenes.length;
        currentScene=scenes[index];renderer=GCSignage.render(stage,currentScene,products);renderer.pause(paused);
        started=performance.now();if(paused)pauseTime=started;
        stage.dataset.program=program().id;stage.dataset.scene=String(index);
    }
    async function refresh(){
        if(busy)return;busy=true;
        const sent=performance.now(),wall=Date.now();
        try{
            const response=await fetch(config.catalog_url,{cache:'no-store'});
            if(!response.ok)throw new Error('catalog');
            const data=await response.json(),ttl=Date.parse(data.valid_until)-Date.parse(data.server_time);
            if(!Array.isArray(data.products)||!Array.isArray(data.programs)||!(ttl>0))throw new Error('catalog');
            products=data.products;receipt={ttl,monotonic:sent,wall};
            programs=data.programs;
            if(renderer)renderer.update(products);else{show(initialScene);initialScene=0;}
            status.textContent='Katalógusárak · '+new Date().toLocaleTimeString('hu-HU');
        }catch(e){status.textContent='Nincs friss kapcsolat';}
        finally{busy=false;}
    }
    const selector=document.getElementById('preview-program');
    if(selector){selector.value=['all','burger','menu','pizza'].includes(programId)?programId:'all';selector.addEventListener('change',e=>{
        programId=e.target.value;const url=new URL(location.href);url.searchParams.set('program',programId);history.replaceState(null,'',url);show();
    });}
    document.getElementById('preview-pause')?.addEventListener('click',e=>{
        paused=!paused;if(paused)pauseTime=performance.now();else started+=performance.now()-pauseTime;
        renderer?.pause(paused);e.target.textContent=paused?'Lejátszás':'Szünet';e.target.setAttribute('aria-pressed',String(paused));
    });
    document.getElementById('preview-next')?.addEventListener('click',()=>show(index+1));
    document.getElementById('preview-fullscreen')?.addEventListener('click',()=>document.documentElement.requestFullscreen().catch(()=>{status.textContent='A böngésző nem engedélyezte a teljes képernyőt.';}));
    setInterval(()=>{
        if(!fresh()){if(renderer){GCSignage.fallback(stage);renderer=null;}return;}
        if(!paused&&renderer&&currentScene&&performance.now()-started>=Number(currentScene.duration)*1000)show(index+1);
    },200);
    GCSignage.fallback(stage);setInterval(refresh,30000);window.addEventListener('online',refresh);refresh();
})();
