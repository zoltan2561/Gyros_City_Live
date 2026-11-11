const {test}=require('node:test');const assert=require('node:assert/strict');
const {selectRelease,sceneAllowed}=require('../../public/signage/schedule.js');
const {stageRelease}=require('../../public/signage/storage.js');
const {createHash}=require('node:crypto');
test('continuous playback keeps repeating the selected release without a schedule deadline',()=>{
 const manifest={playback_mode:'continuous',default_release_id:1,schedule_valid_until:'2026-11-01T00:00:00Z',schedules:[{release_id:2,priority:1}]};
 assert.equal(selectRelease(manifest,new Date('2027-11-11T12:00:00Z')),1);
});
test('offline schedule expires and overnight Budapest schedule selects the right release',()=>{
 const manifest={default_release_id:1,schedule_valid_until:'2026-11-11T00:00:00Z',schedules:[{release_id:2,days:[1],starts_on:'2026-11-01',ends_on:'2026-11-30',start_time:'22:00',end_time:'02:00',priority:1}]};
 assert.equal(selectRelease(manifest,new Date('2026-11-10T00:30:00+01:00')),2);
 assert.equal(selectRelease(manifest,new Date('2026-11-10T02:00:00+01:00')),1);
 assert.equal(selectRelease(manifest,new Date('2026-11-11T00:00:00Z')),null);
});
test('expired prices and changed baked-price media cannot be selected',()=>{
 const scene={type:'media',media:{checksum:'old',baked_prices:true}};
 assert.equal(sceneAllowed(scene,{fresh:false,invalid_media:[]}),false);
 assert.equal(sceneAllowed(scene,{fresh:true,invalid_media:['old']}),false);
 assert.equal(sceneAllowed({type:'grid'},{fresh:false,invalid_media:[]}),false);
 assert.equal(sceneAllowed({type:'media',media:{baked_prices:false}},{fresh:false,invalid_media:[]}),true);
});
test('interrupted download or quota failure leaves the active package untouched',async()=>{
 let active='old',deleted=[];
 const store={load:async()=>({layout_revision:'old'}),save:async(k,v)=>{active=v.layout_revision;}};
 const caches={open:async()=>({put:async()=>{throw new Error('quota');}}),delete:async name=>deleted.push(name)};
 const manifest={layout_revision:'new',releases:[{snapshot:{files:[{url:'http://localhost/signage/test',checksum:'bad'}]}}]};
 await assert.rejects(stageRelease(manifest,'token',{store,caches,base:'http://localhost/signage/',fetch:async()=>{throw new Error('interrupted');}}));
 assert.equal(active,'old');assert.equal(deleted.length,1);
});

test('missing cache at the same revision is repaired without removing the offline shell',async()=>{
 const bytes=new TextEncoder().encode('photo'),hash=createHash('sha256').update(bytes).digest('hex');
 const manifest={layout_revision:'same',releases:[{snapshot:{files:[{url:'http://localhost/signage/photo',checksum:hash,mime:'image/png'}]}}]};
 const current={...manifest,cache_name:'gc-signage-%2Fsignage%2F-release-old'},shell='gc-signage-%2Fsignage%2F-shell-v1';
 let saved=null,fetches=0,deleted=[];
 const store={load:async()=>current,save:async(k,v)=>{saved=v;}};
 const caches={open:async()=>({match:async()=>undefined,put:async()=>{}}),keys:async()=>[shell,current.cache_name],delete:async n=>deleted.push(n)};
 await stageRelease(manifest,'token',{store,caches,base:'http://localhost/signage/',fetch:async()=>{fetches++;return new Response(bytes);}});
 assert.equal(fetches,1);assert.equal(saved.layout_revision,'same');assert.notEqual(saved.cache_name,current.cache_name);assert(!deleted.includes(shell));
});

test('garbage collection failure cannot delete the newly committed package',async()=>{
 const manifest={layout_revision:'new',releases:[]};let active=null,deleted=[];
 const store={load:async()=>null,save:async(k,v)=>{active=v;}};
 const caches={open:async()=>({}),keys:async()=>{throw new Error('cleanup failed');},delete:async n=>deleted.push(n)};
 await stageRelease(manifest,'token',{store,caches,base:'http://localhost/signage/'});
 assert.equal(active.layout_revision,'new');assert(!deleted.includes(active.cache_name));
});

test('a real quota failure preserves the previous active pointer and cache',async()=>{
 const bytes=new TextEncoder().encode('photo'),hash=createHash('sha256').update(bytes).digest('hex');
 const manifest={layout_revision:'new',releases:[{snapshot:{files:[{url:'http://localhost/signage/photo',checksum:hash}]}}]};
 const current={layout_revision:'old',cache_name:'old-cache'};let active=current,deleted=[];
 const store={load:async()=>current,save:async(k,v)=>{active=v;}};
 const caches={open:async()=>({put:async()=>{throw new Error('QuotaExceededError');}}),delete:async n=>deleted.push(n)};
 await assert.rejects(stageRelease(manifest,'token',{store,caches,base:'http://localhost/signage/',fetch:async()=>new Response(bytes)}));
 assert.equal(active,current);assert(!deleted.includes('old-cache'));
});
