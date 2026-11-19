// Browser compatibility and migration of the saved device pairing.
if (!window.globalThis) window.globalThis=window;
if (!Element.prototype.append) Element.prototype.append=function(...nodes){nodes.forEach(node=>this.appendChild(typeof node==='string'?document.createTextNode(node):node));};
if (!Element.prototype.replaceChildren) Element.prototype.replaceChildren=function(...nodes){this.textContent='';this.append(...nodes);};
if (!NodeList.prototype.forEach) NodeList.prototype.forEach=Array.prototype.forEach;
if (!Promise.allSettled) Promise.allSettled=values=>Promise.all(values.map(value=>Promise.resolve(value).then(v=>({status:'fulfilled',value:v}),reason=>({status:'rejected',reason}))));

window.GCSignageStorage={database(base){
 const namespace='gc-signage-'+new URL(base).pathname;
 const key=namespace+'-device-token';
 function legacyToken(){
  return new Promise(resolve=>{
   const timer=setTimeout(()=>resolve(null),4000);
   function finish(value){clearTimeout(timer);resolve(value);}
   try{
    const open=indexedDB.open(namespace);
    open.onupgradeneeded=()=>open.transaction.abort();
    open.onerror=()=>finish(null);
    open.onsuccess=()=>{
     const db=open.result;
     if(!db.objectStoreNames.contains('state')){db.close();finish(null);return;}
     const read=db.transaction('state','readonly').objectStore('state').get('token');
     read.onsuccess=()=>{db.close();finish(read.result);};
     read.onerror=()=>{db.close();finish(null);};
    };
   }catch(error){finish(null);}
  });
 }
 return {
  async load(){
   let saved=null;try{saved=localStorage.getItem(key);}catch(error){}
   if(saved!==null)return saved||null;
   const token=await legacyToken();
   if(token)try{localStorage.setItem(key,token);}catch(error){}
   return token;
  },
  async save(name,token){localStorage.setItem(key,token||'');}
 };
}};

// Only the former signage worker and its dedicated caches belong to this player.
(async function retireOfflinePlayer(){
 const base=new URL('./',document.baseURI||location.href);
 const prefix='gc-signage-'+encodeURIComponent(base.pathname)+'-';
 try{
  const registration=await navigator.serviceWorker?.getRegistration(base.href);
  if(registration?.scope===base.href && registration.active?.scriptURL===new URL('sw.js',base).href){
   await registration.unregister();
  }
  if(window.caches)for(const name of await caches.keys())if(name.startsWith(prefix))await caches.delete(name);
 }catch(error){/* Online playback is independent of offline storage access. */}
})();
