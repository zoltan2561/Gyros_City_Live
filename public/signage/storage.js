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
async function stageRelease(manifest,token,options){
 const store=options.store,cacheStorage=options.caches||caches,base=new URL(options.base),fetcher=options.fetch||fetch;
 const prefix='gc-signage-'+encodeURIComponent(base.pathname)+'-release-',name=prefix+manifest.layout_revision+'-'+crypto.randomUUID();
 const current=await store.load('manifest');
 if(current?.layout_revision===manifest.layout_revision){
  const active=await cacheStorage.open(current.cache_name);let complete=true;
  for(const release of manifest.releases)for(const file of release.snapshot.files||[])if(!await active.match(file.url)){complete=false;break;}
  if(complete){await store.save('manifest',{...manifest,cache_name:current.cache_name});return {...manifest,cache_name:current.cache_name};}
 }
 const cache=await cacheStorage.open(name);
 try{
  const files=new Map();for(const release of manifest.releases)for(const file of release.snapshot.files||[])files.set(file.url,file);
  for(const file of files.values()){
   const url=new URL(file.url,base);
   const project=new URL('../',base).pathname;
   if(url.origin!==base.origin||!url.pathname.startsWith(project))throw new Error('Médiaútvonal nem engedélyezett.');
   const response=await fetcher(url.href,{headers:{Authorization:'Bearer '+token},cache:'no-store'});
   if(!response.ok)throw new Error('Média letöltése nem sikerült.');
   const bytes=await response.arrayBuffer();
   if(await digest(bytes)!==file.checksum)throw new Error('Média ellenőrzőösszege eltér.');
   await cache.put(url.href,new Response(bytes,{headers:{'Content-Type':file.mime||'application/octet-stream','Content-Length':String(bytes.byteLength)}}));
  }
  const complete={...manifest,cache_name:name};
  await store.save('manifest',complete); // Atomic active pointer after all verified media.
  // Garbage collection is best effort after the pointer commit; it cannot roll it back.
  try{
   const names=await cacheStorage.keys?.()||[];
   for(const old of names)if(old.startsWith(prefix)&&old!==name&&old!==current?.cache_name)await cacheStorage.delete(old);
  }catch(error){/* A cleanup error must retain the committed active package. */}
  return complete;
 }catch(error){if(name!==current?.cache_name)await cacheStorage.delete(name);throw error;}
}
async function blobUrl(manifest,url,cacheStorage=caches){
 const cache=await cacheStorage.open(manifest.cache_name),response=await cache.match(url);
 if(!response)throw new Error('Hiányzó előtöltött média.');
 return URL.createObjectURL(await response.blob());
}
return {database,digest,stageRelease,blobUrl};
}));
