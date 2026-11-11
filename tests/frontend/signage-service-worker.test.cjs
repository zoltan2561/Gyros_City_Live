const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');const vm=require('node:vm');
function worker(){
 const handlers={},network=[],shellUrls=[];
 const active={cache_name:'current-release'};
 const cache={match:async request=>String(request.url||request).includes('embers-old-hash.webp')?new Response('HISTORICAL_ARTWORK'):new Response('CURRENT_ARTWORK')};
 const context={URL,Response,Request,indexedDB:{open(){const req={result:{objectStoreNames:{contains:()=>true},close(){},transaction(){return {objectStore(){return {get(){const read={result:active};queueMicrotask(()=>read.onsuccess?.());return read;}};}};}}};queueMicrotask(()=>req.onsuccess?.());return req;}},
 self:{location:{href:'http://localhost/Gyros_City_Live/signage/sw.js'},skipWaiting:async()=>{},addEventListener:(name,fn)=>handlers[name]=fn},
 caches:{keys:async()=>['gc-signage-%2FGyros_City_Live%2Fsignage%2F-release-old','current-release'],open:async name=>name==='current-release'?cache:{addAll:async urls=>shellUrls.push(...urls),match:async()=>new Response('OLD_ARTWORK')}},
 fetch:async request=>{network.push(request);return new Response('NETWORK_ARTWORK');}};
 vm.runInNewContext(fs.readFileSync(require.resolve('../../public/signage/sw.js'),'utf8'),context);
 return {network,shellUrls,async install(){let result;handlers.install({waitUntil:value=>result=value});await result;},async request(cacheMode,name='embers-current-hash.webp'){let result;handlers.fetch({request:new Request('http://localhost/Gyros_City_Live/public/signage/reference-assets/'+name,{cache:cacheMode}),respondWith:value=>result=value});return (await result).text();}};
}

test('offline installation downloads the background stylesheet together with the player shell',async()=>{
 const run=worker();await run.install();
 assert(run.shellUrls.includes('http://localhost/Gyros_City_Live/public/signage/backgrounds.css'));
 assert(run.shellUrls.includes('http://localhost/Gyros_City_Live/public/signage/modern.css'));
 assert(run.shellUrls.includes('http://localhost/Gyros_City_Live/public/signage/player.css'));
 assert(run.shellUrls.includes('http://localhost/Gyros_City_Live/signage/'));
});
test('verified artwork staging bypasses old service-worker caches',async()=>{
 const run=worker();assert.equal(await run.request('no-store'),'NETWORK_ARTWORK');assert.equal(run.network.length,1);
});
test('versioned scene artwork resolves from the committed package instead of the first old cache',async()=>{
 const run=worker();assert.equal(await run.request('default'),'CURRENT_ARTWORK');assert.equal(run.network.length,0);
});
test('a package retaining old and new artwork returns the exact version requested by each scene',async()=>{
 const run=worker();assert.equal(await run.request('default','embers-old-hash.webp'),'HISTORICAL_ARTWORK');assert.equal(await run.request('default'),'CURRENT_ARTWORK');assert.equal(run.network.length,0);
});
