(() => {
'use strict';
const root=document.getElementById('signage-stage'),pairPanel=document.getElementById('pair-panel'),status=document.getElementById('player-status');
const base=new URL('./',document.baseURI||location.href),api=new URL('api/v1/',base);
const store=GCSignageStorage.database(base.href);
let token=null,manifest=null,catalog=null,receipt=null,clock=null,renderer=null,current=null,sceneIndex=-1,started=0,videoProgress=0,videoChecked=0;
let syncing=false,lastError='',nonce=null,cycleId=0,sessionGeneration=0,changing=false,pendingManifest=null;
let pairingGeneration=0,pairingPolling=false;
let retrySync=null;
let previews={},previewRelease=null,checking=false,lastSync=Date.now(),recovering=false;
let controlEnabled=false,controlFrame=null,controlSequence=0,controlSent=0,controlWall=0,lastControl=0,acceptedAt=-1;
const failedScenes=new Map();
function notice(message){status.textContent=message;}
function fallback(){cycleId++;GCSignage.fallback(root);renderer=null;current=null;}
function mediaFailed(scene,message,cycle){
 if(!token||cycle!==cycleId)return;
 lastError=message;failedScenes.set(scene.id,performance.now()+60000);notice(message);fallback();next();
}
function fresh(){return GCSignage.priceFresh(receipt,performance.now(),Date.now());}
function serverNow(){if(!clock||Date.now()<clock.wall)return null;return new Date(clock.server+performance.now()-clock.monotonic);}
function nativeRequest(url,method,headers,body,signal){
 return new Promise((resolve,reject)=>{
  const xhr=new XMLHttpRequest();xhr.open(method,url,true);xhr.timeout=15000;
  for(const key of Object.keys(headers))xhr.setRequestHeader(key,headers[key]);
  const abort=()=>xhr.abort();
  signal?.addEventListener('abort',abort);
  xhr.onloadend=()=>signal?.removeEventListener('abort',abort);
  xhr.onerror=xhr.onabort=xhr.ontimeout=()=>reject(new Error('A TV hálózati kérése megszakadt.'));
  xhr.onload=()=>{try{resolve({response:{ok:xhr.status>=200&&xhr.status<300,status:xhr.status},json:xhr.status===200||xhr.status===201?JSON.parse(xhr.responseText):null});}catch(e){reject(e);}};
  xhr.send(body);
 });
}
async function request(path,method='GET',data,credential=token){
 const controller=typeof AbortController==='function'?new AbortController():null;
 let timer;
 try{
  const headers={'X-Signage-Playback':'online','Content-Type':'application/json','Accept':'application/json'};
  if(credential)headers.Authorization='Bearer '+credential;
  if(path==='manifest'){
   headers['X-Signage-Include']='catalog';headers['X-Signage-Photos']='preview';
   headers['X-Signage-Control']='frame';
   if(previewRelease!=null)headers['X-Signage-Release']=String(previewRelease);
  }
  const url=new URL(path,api),body=data===undefined?undefined:JSON.stringify(data);
  const download=typeof XMLHttpRequest==='function'?nativeRequest(url.href,method,headers,body,controller?.signal):fetch(url,{method,headers,body,cache:'no-store',signal:controller?.signal})
   .then(async response=>({response,json:response.ok?await response.json():null}));
  const timeout=new Promise((resolve,reject)=>{timer=setTimeout(()=>{reject(new Error('A TV-kérés túllépte a 15 másodpercet.'));controller?.abort();},15000);});
  const {response,json}=await Promise.race([download,timeout]);
  if(response.status===401&&token&&credential===token){sessionGeneration++;pairingGeneration++;token=null;receipt=null;clock=null;manifest=null;pendingManifest=null;controlEnabled=false;controlFrame?.remove();controlFrame=null;lastControl=0;acceptedAt=-1;previewRelease=null;previews={};clearTimeout(retrySync);fallback();pairPanel.hidden=false;await store.save('token',null);throw new Error('A kijelző hozzáférését visszavonták.');}
  if(!response.ok){const error=new Error('TV-kérés: '+path+' (HTTP '+response.status+').');error.status=response.status;throw error;}
  return json;
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
function applyCatalog(data,sent,wall,sameRelease){
 const ttl=Date.parse(data.valid_until)-Date.parse(data.server_time);
 if(data.schema_version!==1||!Array.isArray(data.products)||!Number.isFinite(ttl))throw new Error('Érvénytelen katalógus.');
 catalog=data;receipt={ttl:Math.max(0,ttl),monotonic:sent,wall};
 clock={server:Date.parse(data.server_time),monotonic:sent,wall};
 if(current&&!GCSignageSchedule.sceneAllowed(current,{fresh:fresh(),invalid_media:data.invalid_media})){fallback();}
 else if(renderer&&sameRelease)renderer.update(data.products);
}
function acceptManifest(incoming,data,manifestSent,manifestWall){
  if(manifestSent<acceptedAt)return;
  if(incoming.schema_version!==1||!Array.isArray(incoming.releases)||!Number.isFinite(Date.parse(incoming.server_time)))throw new Error('Nem támogatott kiadás.');
  applyCatalog(data,manifestSent,manifestWall,current?.release_id===incoming.default_release_id);
  acceptedAt=manifestSent;controlEnabled=incoming.control_frame===true;
  if(incoming.photo_previews||previewRelease!==incoming.default_release_id){
   previews=incoming.photo_previews||{};previewRelease=incoming.default_release_id;
  }
  lastSync=Date.now();
  if(!current||manifest?.default_release_id===incoming.default_release_id){
   manifest=incoming;pendingManifest=null;
  }else pendingManifest=incoming;
  lastError='';notice('');if(!current||pendingManifest)next();
}
async function synchronize(){
 if(!token||syncing||controlHealthy())return;syncing=true;
 clearTimeout(retrySync);retrySync=null;
 const generation=sessionGeneration;
 try{
  const manifestSent=performance.now(),manifestWall=Date.now(),incoming=await request('manifest','POST');
  const data=incoming.catalog||await request('catalog');
  if(!token||generation!==sessionGeneration)return;
  acceptManifest(incoming,data,manifestSent,manifestWall);
 }catch(e){
  lastError=e.message;if(!current)notice(e.message);
  if(token&&generation===sessionGeneration)retrySync=setTimeout(synchronize,5000);
 }
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
  // An immutable, single-grid release can stay mounted while prices refresh.
  if(scenes.length===1 && scene.type==='grid' && !scene.background_media && current?.release_id===releaseId && current.id===scene.id){
   started=performance.now();return;
  }
  try{
   const cycle=++cycleId;
   renderer=GCSignage.render(root,scene,catalog?.products||[],{tv:true,previews,embedded:controlEnabled});
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
function playbackStatus(){
 return {player_version:'online-4',active_release_id:current?.release_id||null,catalog_revision:catalog?.catalog_revision||'',scene_id:current?.id||'',error:lastError,offline:navigator.onLine===false?1:0};
}
function controlHealthy(){return controlEnabled&&lastControl>0&&Date.now()-lastControl<25000&&Date.now()-lastSync<60000;}
function pollControl(){
 if(!token||!controlEnabled)return;
 if(controlFrame && Date.now()-controlWall<20000)return;
 controlFrame?.remove();
 const frame=document.createElement('iframe'),form=document.createElement('form');
 frame.hidden=true;frame.title='TV műsorfrissítés';frame.name='gc-control-'+(++controlSequence);
 form.hidden=true;form.method='POST';form.action=new URL('control',api).href;form.target=frame.name;
 const fields={...playbackStatus(),known:manifest?.default_release_id,refresh:Date.now()-lastSync>=28000?1:0,sequence:controlSequence};
 for(const key of Object.keys(fields)){
  if(fields[key]==null)continue;
  const input=document.createElement('input');input.type='hidden';input.name=key;input.value=String(fields[key]);form.appendChild(input);
 }
 controlFrame=frame;controlSent=performance.now();controlWall=Date.now();
 document.body.appendChild(frame);document.body.appendChild(form);form.submit();form.remove();
}
window.addEventListener('message',event=>{
 if(!token||!controlFrame||event.source!==controlFrame.contentWindow||event.origin!==base.origin||event.data?.sequence!==controlSequence)return;
 controlFrame.remove();controlFrame=null;
 try{
  if(event.data.manifest)acceptManifest(event.data.manifest,event.data.manifest.catalog,controlSent,controlWall);
  lastControl=Date.now();
 }catch(e){lastError=e.message;lastControl=0;synchronize();}
});
async function heartbeat(){
 if(!token||checking||controlHealthy())return;
 checking=true;
 try{
  const reply=await request('heartbeat','POST',playbackStatus());
  if(token && 'assigned_release_id' in reply && reply.assigned_release_id!==manifest?.default_release_id)synchronize();
 }catch(e){/* A failed heartbeat never renews price freshness. */}
 finally{checking=false;if(token&&Date.now()-lastSync>120000)recoverConnection();}
}
async function recoverConnection(scheduled=false){
 if(recovering)return;recovering=true;
 try{
  // Check the application through the browser's image loader, independently of API requests.
  const key='gc-signage-last-reconnect-'+base.pathname,now=Date.now();
  if(!scheduled&&now-Number(sessionStorage.getItem(key)||0)<300000)return;
  const reachable=await new Promise(resolve=>{
   const probe=new Image(),timer=setTimeout(()=>finish(false),8000);
   function finish(ok){clearTimeout(timer);probe.onload=probe.onerror=null;resolve(ok);}
   probe.onload=()=>finish(true);probe.onerror=()=>finish(false);
   probe.src=new URL('connection.gif?t='+now,base).href;
  });
  // A reload only happens while the server answers, so the TV never lands on a browser error page.
  if(reachable&&token&&(scheduled||Date.now()-lastSync>120000)){sessionStorage.setItem(key,String(now));location.reload();}
 }catch(e){/* Regular synchronization still retries. */}finally{recovering=false;}
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
// TV browsers slowly leak memory over a full day; a fresh page every hour keeps playback stable.
const pageLoaded=Date.now();
setInterval(()=>{if(token&&Date.now()-pageLoaded>=3600000)recoverConnection(true);},60000);
setInterval(synchronize,30000);setInterval(heartbeat,10000);setInterval(pairingPoll,5000);
setInterval(pollControl,8000);
window.addEventListener('online',()=>{synchronize();heartbeat();});window.addEventListener('visibilitychange',()=>{if(!document.hidden)synchronize();});
window.addEventListener('pageshow',()=>{if(token)synchronize();});
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
