(() => {
'use strict';
const root=document.getElementById('signage-stage'),pairPanel=document.getElementById('pair-panel'),status=document.getElementById('player-status');
const base=new URL('./',location.href),api=new URL('api/v1/',base);
const store=GCSignageStorage.database(base.href);
let token=null,manifest=null,catalog=null,receipt=null,clock=null,renderer=null,current=null,sceneIndex=-1,started=0,videoProgress=0,videoChecked=0;
let syncing=false,lastError='',nonce=null,cycleId=0,sessionGeneration=0,changing=false,pendingManifest=null;
let pairingGeneration=0,pairingPolling=false;
const failedScenes=new Map();
function notice(message){status.textContent=message;}
function fallback(){cycleId++;GCSignage.fallback(root);renderer=null;current=null;}
function mediaFailed(scene,message,cycle){
 if(!token||cycle!==cycleId)return;
 lastError=message;failedScenes.set(scene.id,performance.now()+60000);notice(message);fallback();next();
}
function fresh(){return GCSignage.priceFresh(receipt,performance.now(),Date.now());}
function serverNow(){if(!clock||Date.now()<clock.wall)return null;return new Date(clock.server+performance.now()-clock.monotonic);}
async function request(path,method='GET',data,credential=token){
 const controller=typeof AbortController==='function'?new AbortController():null,timer=setTimeout(()=>controller?.abort(),15000);
 try{
  const response=await fetch(new URL(path,api),{method,headers:{'X-Signage-Playback':'online','Content-Type':'application/json','Accept':'application/json',...(credential?{Authorization:'Bearer '+credential}:{})},body:data===undefined?undefined:JSON.stringify(data),cache:'no-store',signal:controller?.signal});
  if(response.status===401&&token&&credential===token){sessionGeneration++;pairingGeneration++;token=null;receipt=null;clock=null;manifest=null;pendingManifest=null;fallback();pairPanel.hidden=false;await store.save('token',null);throw new Error('A kijelző hozzáférését visszavonták.');}
  if(!response.ok){const error=new Error('Nincs friss kapcsolat.');error.status=response.status;throw error;}
  return await response.json();
 }finally{clearTimeout(timer);}
}
async function pair(){
 const generation=++pairingGeneration;nonce=null;
 document.getElementById('pair-code').textContent='…';notice('Párosítási kód kérése…');
 try{
  const p=await request('pairing/start','POST',{},null);if(generation!==pairingGeneration||token)return;nonce=p.nonce;
  document.getElementById('pair-code').textContent=p.code;notice('A párosítási kód 10 percig érvényes.');
 }catch(e){if(generation===pairingGeneration&&!token)notice('Nem sikerült kódot kérni. Ellenőrizd a kapcsolatot, majd próbáld újra.');}
}
async function pairingPoll(){
 if(token||!nonce||pairingPolling)return;
 const generation=pairingGeneration,requestedNonce=nonce;pairingPolling=true;
 try{
  const p=await request('pairing/status','POST',{},requestedNonce);
  if(generation!==pairingGeneration||nonce!==requestedNonce||token)return;
  if(p.status==='paired'){
   if(typeof p.token!=='string'||!p.token)throw new Error('Érvénytelen párosítás.');
   token=p.token;nonce=null;
   try{await store.save('token',token);}catch(e){token=null;notice('A párosítás nem menthető a készülékre. Ellenőrizd a böngésző tárhelyét, majd kérj új kódot.');return;}
   pairPanel.hidden=true;await synchronize();
  }else notice('Várakozás a párosításra. A kód 10 percig érvényes.');
 }catch(e){
  if(generation!==pairingGeneration||nonce!==requestedNonce||token)return;
  if(e.status===404){nonce=null;notice('A kód lejárt vagy már felhasználták. Kérj új párosítási kódot.');}
  else notice('A kapcsolat megszakadt. A párosítást automatikusan újra ellenőrizzük.');
 }finally{pairingPolling=false;}
}
async function applyCatalog(){
 const sent=performance.now(),wall=Date.now();
 const data=await request('catalog');const ttl=Date.parse(data.valid_until)-Date.parse(data.server_time);
 if(data.schema_version!==1||!Array.isArray(data.products)||!Number.isFinite(ttl))throw new Error('Érvénytelen katalógus.');
 catalog=data;receipt={ttl:Math.max(0,ttl),monotonic:sent,wall};
 clock={server:Date.parse(data.server_time),monotonic:sent,wall};
 if(current&&!GCSignageSchedule.sceneAllowed(current,{fresh:fresh(),invalid_media:data.invalid_media})){fallback();}
 else if(renderer)renderer.update(data.products);
}
async function synchronize(){
 if(!token||syncing)return;syncing=true;
 const generation=sessionGeneration;
 try{
  await applyCatalog(); // Always independent of large media downloads.
  const manifestSent=performance.now(),manifestWall=Date.now(),incoming=await request('manifest');
  if(incoming.schema_version!==1||!Array.isArray(incoming.releases)||!Number.isFinite(Date.parse(incoming.server_time)))throw new Error('Nem támogatott kiadás.');
  clock={server:Date.parse(incoming.server_time),monotonic:manifestSent,wall:manifestWall};
  if(!token||generation!==sessionGeneration)return;
  if(!current||manifest?.default_release_id===incoming.default_release_id){
   manifest=incoming;pendingManifest=null;
  }else pendingManifest=incoming;
  lastError='';notice('');if(!current)next();
 }catch(e){lastError=e.message;notice(e.message);}
 finally{syncing=false;}
}
async function next(){
 if(!token||changing)return;changing=true;
 try{
 if(pendingManifest){manifest=pendingManifest;pendingManifest=null;sceneIndex=-1;}
 const now=serverNow();if(!manifest||!now){fallback();return;}
 const releaseId=GCSignageSchedule.selectRelease(manifest,now),release=manifest.releases.find(r=>r.id===releaseId);
 if(!release){fallback();return;}
 const scenes=release.snapshot.scenes||[];
 const state={fresh:fresh(),invalid_media:catalog?.invalid_media||[]};
 for(let n=0;n<scenes.length;n++){
  sceneIndex=(sceneIndex+1)%scenes.length;const scene=scenes[sceneIndex];
  if((failedScenes.get(scene.id)||0)>performance.now())continue;
  if(!GCSignageSchedule.sceneAllowed(scene,state))continue;
  if(scene.type!=='media'&&!GCSignage.cardsForScene(scene,catalog?.products||[]).length)continue;
  try{
   const cycle=++cycleId;
   renderer=GCSignage.render(root,scene,catalog?.products||[]);
   current={...scene,release_id:releaseId};
   started=performance.now();videoChecked=started;videoProgress=0;
   notice('');heartbeat();
   root.querySelectorAll('video').forEach(video=>{
    let observedTime=video.currentTime;
    video.addEventListener('timeupdate',()=>{if(cycle===cycleId&&video.currentTime!==observedTime){observedTime=video.currentTime;videoChecked=performance.now();}});
    video.addEventListener('error',()=>mediaFailed(scene,'Videóhiba',cycle),{once:true});
    Promise.resolve(video.play()).catch(()=>mediaFailed(scene,'A videó lejátszása nem indult el.',cycle));
    if(video.classList.contains('scene-media')&&scene.play_to_end)video.addEventListener('ended',()=>next(),{once:true});
   });
   return;
  }catch(e){lastError=e.message;}
 }
 fallback();notice(lastError||'Jelenleg nincs lejátszható jelenet.');
 }finally{changing=false;}
}
async function heartbeat(){
 if(!token)return;
 try{
  await request('heartbeat','POST',{player_version:'online-1',active_release_id:current?.release_id||null,catalog_revision:catalog?.catalog_revision||'',scene_id:current?.id||'',error:lastError,offline:!navigator.onLine});
 }catch(e){/* A failed heartbeat never renews price freshness. */}
}
setInterval(()=>{
 if(!token||!manifest)return;
 const now=serverNow();if(!now){fallback();return;}
 if(current&&(!GCSignageSchedule.sceneAllowed(current,{fresh:fresh(),invalid_media:catalog?.invalid_media||[]})||GCSignageSchedule.selectRelease(manifest,now)!==current.release_id)){fallback();next();return;}
 if(!current){next();return;}
 const video=root.querySelector('video.scene-media');
 const limit=current.play_to_end&&current.media?.duration?(current.media.duration+10)*1000:Number(current.duration||15)*1000;
 if(performance.now()-started>=limit){next();return;}
 if(video&&!video.ended&&performance.now()-videoChecked>=10000)mediaFailed(current,'A videó elakadt.',cycleId);
},1000);
setInterval(synchronize,30000);setInterval(heartbeat,60000);setInterval(pairingPoll,5000);
window.addEventListener('online',()=>{synchronize();heartbeat();});window.addEventListener('visibilitychange',()=>{if(!document.hidden)synchronize();});
document.getElementById('new-code').addEventListener('click',pair);
document.getElementById('player-fullscreen').addEventListener('click',()=>{
 const message='A böngésző nem engedélyezte a teljes képernyőt.';
 try{const open=document.documentElement.requestFullscreen;if(typeof open!=='function'){notice(message);return;}const result=open.call(document.documentElement);if(result&&typeof result.catch==='function')result.catch(()=>notice(message));}catch(e){notice(message);}
});
(async()=>{
 fallback();
 try{
  notice('Mentett TV-párosítás betöltése…');
  token=await store.load('token');
  // A restart never trusts a saved price receipt or an uncertain device clock.
  if(token){pairPanel.hidden=true;notice('Kapcsolódás a műsorhoz…');heartbeat();await synchronize();}else{pairPanel.hidden=false;await pair();}
 }catch(e){notice('A helyi tároló nem használható. Ellenőrizd a böngésző beállításait.');}
})();
})();
