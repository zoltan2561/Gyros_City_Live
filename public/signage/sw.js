'use strict';
const base=new URL('./',self.location.href),project=new URL('../',base);
const prefix='gc-signage-'+encodeURIComponent(base.pathname)+'-',shell=prefix+'shell-v1';
const assets=['player.css','renderer.js','schedule.js','storage.js','player.js'].map(f=>new URL('public/signage/'+f,project).href);
const shellUrls=[base.href,...assets];
self.addEventListener('install',event=>event.waitUntil(caches.open(shell).then(c=>c.addAll(shellUrls)).then(()=>self.skipWaiting())));
self.addEventListener('activate',event=>event.waitUntil(self.clients.claim()));
async function cached(request){
 for(const name of await caches.keys()){
  if(!name.startsWith(prefix))continue;
  const response=await (await caches.open(name)).match(request.url);
  if(response)return response;
 }
 return null;
}
async function range(response,header){
 const bytes=await response.arrayBuffer(),total=bytes.byteLength,match=/^bytes=(\d*)-(\d*)$/.exec(header||'');
 if(!match)return new Response(null,{status:416,headers:{'Content-Range':'bytes */'+total}});
 let start=match[1]?Number(match[1]):Math.max(0,total-Number(match[2])),end=match[2]&&match[1]?Math.min(total-1,Number(match[2])):total-1;
 if(start>end||start>=total||!Number.isSafeInteger(start)||!Number.isSafeInteger(end))return new Response(null,{status:416,headers:{'Content-Range':'bytes */'+total}});
 return new Response(bytes.slice(start,end+1),{status:206,headers:{'Content-Type':response.headers.get('Content-Type')||'application/octet-stream','Content-Length':String(end-start+1),'Content-Range':'bytes '+start+'-'+end+'/'+total,'Accept-Ranges':'bytes'}});
}
self.addEventListener('fetch',event=>{
 const url=new URL(event.request.url);
 if(event.request.method!=='GET'||url.origin!==base.origin)return;
 const isShell=shellUrls.includes(url.href);
 const isMedia=url.pathname.startsWith(new URL('api/v1/media/',base).pathname);
 if(!isShell&&!isMedia)return;
 event.respondWith((async()=>{
  if(isShell){
   try{const response=await fetch(event.request);if(response.ok){(await caches.open(shell)).put(event.request,response.clone());return response;}}catch(e){}
  }
  const response=await cached(event.request);
  if(response)return event.request.headers.has('Range')?range(response,event.request.headers.get('Range')):response;
  return fetch(event.request);
 })());
});
