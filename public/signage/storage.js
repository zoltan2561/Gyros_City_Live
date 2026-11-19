(function(root,factory){const api=factory();if(typeof module==='object'&&module.exports)module.exports=api;else root.GCSignageStorage=api;}(globalThis,function(){
'use strict';
function database(base){
 const name='gc-signage-'+new URL(base).pathname;
 let opened;
 function db(){if(!opened)opened=new Promise((resolve,reject)=>{const req=indexedDB.open(name,1);req.onupgradeneeded=()=>req.result.createObjectStore('state');req.onsuccess=()=>resolve(req.result);req.onerror=()=>reject(req.error);});return opened;}
 return {
  async load(key){const d=await db();return new Promise((resolve,reject)=>{const r=d.transaction('state').objectStore('state').get(key);r.onsuccess=()=>resolve(r.result);r.onerror=()=>reject(r.error);});},
  async save(key,value){const d=await db();return new Promise((resolve,reject)=>{const t=d.transaction('state','readwrite');t.objectStore('state').put(value,key);t.oncomplete=()=>resolve();t.onerror=()=>reject(t.error);t.onabort=()=>reject(t.error||new Error('storage'));});}
 };
}
async function digest(bytes){const hash=await crypto.subtle.digest('SHA-256',bytes);return [...new Uint8Array(hash)].map(b=>b.toString(16).padStart(2,'0')).join('');}
// A RAM package is scoped to this player session and never replaces a saved offline package.
const sessionPackages=new WeakMap();
const maxSessionBytes=32*1024*1024;
async function downloadFiles(files,token,base,fetcher,save){
 for(const file of files.values()){
  const url=new URL(file.url,base);
  const project=new URL('../',base).pathname;
  if(url.origin!==base.origin||!url.pathname.startsWith(project))throw new Error('Médiaútvonal nem engedélyezett.');
  const response=await fetcher(url.href,{headers:{Authorization:'Bearer '+token},cache:'no-store'});
  if(!response.ok)throw new Error('Média letöltése nem sikerült.');
  const bytes=await response.arrayBuffer();
  if(await digest(bytes)!==file.checksum)throw new Error('Média ellenőrzőösszege eltér.');
  await save(url.href,new Blob([bytes],{type:file.mime||'application/octet-stream'}));
 }
}
async function stageRelease(manifest,token,options){
 const store=options.store,cacheStorage=options.caches||caches,base=new URL(options.base),fetcher=options.fetch||fetch;
 const random=crypto.getRandomValues(new Uint8Array(16));
 const suffix=[...random].map(byte=>byte.toString(16).padStart(2,'0')).join('');
 const prefix='gc-signage-'+encodeURIComponent(base.pathname)+'-release-',name=prefix+manifest.layout_revision+'-'+suffix;
 const session=sessionPackages.get(store);
 if(session && session.token===token && session.revision===manifest.layout_revision)return {...manifest,sessionFiles:session.files};
 const files=new Map();for(const release of manifest.releases)for(const file of release.snapshot.files||[])files.set(file.url,file);
 const current=await store.load('manifest');
 if(current?.layout_revision===manifest.layout_revision){
  const active=await cacheStorage.open(current.cache_name);let complete=true;
  for(const release of manifest.releases)for(const file of release.snapshot.files||[])if(!await active.match(file.url)){complete=false;break;}
  if(complete){await store.save('manifest',{...manifest,cache_name:current.cache_name});return {...manifest,cache_name:current.cache_name};}
 }
 try{
  const cache=await cacheStorage.open(name);
  await downloadFiles(files,token,base,fetcher,(url,blob)=>cache.put(url,new Response(blob,{headers:{'Content-Type':blob.type,'Content-Length':String(blob.size)}})));
  const complete={...manifest,cache_name:name};
  await store.save('manifest',complete); // Atomic active pointer after all verified media.
  // Garbage collection is best effort after the pointer commit; it cannot roll it back.
  try{
   const names=await cacheStorage.keys?.()||[];
   for(const old of names)if(old.startsWith(prefix)&&old!==name&&old!==current?.cache_name)await cacheStorage.delete(old);
  }catch(error){/* A cleanup error must retain the committed active package. */}
  sessionPackages.delete(store);
  return complete;
 }catch(error){
  if(name!==current?.cache_name)await cacheStorage.delete(name);
  if(error.name!=='QuotaExceededError'&&!/quota exceeded/i.test(error.message))throw error;
  const total=Array.from(files.values()).reduce((sum,file)=>sum+Number(file.bytes),0);
  if(!Number.isFinite(total)||total>maxSessionBytes)throw error;
  let used=0;
  const verified=new Map();
  await downloadFiles(files,token,base,fetcher,(url,blob)=>{
   used+=blob.size;if(used>maxSessionBytes)throw error;
   verified.set(url,blob);
  });
  sessionPackages.set(store,{token,revision:manifest.layout_revision,files:verified});
  return {...manifest,sessionFiles:verified};
 }
}
async function blobUrl(manifest,url,cacheStorage=caches){
 if(manifest.sessionFiles){
  const blob=manifest.sessionFiles.get(url);
  if(!blob)throw new Error('Hiányzó előtöltött média.');
  return URL.createObjectURL(blob);
 }
 const cache=await cacheStorage.open(manifest.cache_name),response=await cache.match(url);
 if(!response)throw new Error('Hiányzó előtöltött média.');
 return URL.createObjectURL(await response.blob());
}
return {database,digest,stageRelease,blobUrl};
}));
