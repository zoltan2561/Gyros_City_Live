(() => {
    'use strict';
    const data=JSON.parse(document.getElementById('preview-data').textContent),stage=document.getElementById('signage-stage');
    stage.addEventListener('signage:overflow',e=>{let warning=document.getElementById('preview-warning');if(!e.detail.names.length){warning?.remove();return;}if(!warning){warning=document.createElement('p');warning.id='preview-warning';warning.className='preview-warning';warning.setAttribute('role','alert');document.body.append(warning);}warning.textContent='A terméknév túllóg. Csökkentsd a betűméretet vagy módosítsd a kártyát: '+e.detail.names.join(', ');});
    function show(index){GCSignage.render(stage,data.snapshot.scenes[index],data.catalog.products);}
    document.querySelectorAll('[data-scene]').forEach(b=>b.addEventListener('click',()=>show(Number(b.dataset.scene))));
    show(0);
})();
