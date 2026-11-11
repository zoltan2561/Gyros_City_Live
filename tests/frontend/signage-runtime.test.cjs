const {test}=require('node:test');const assert=require('node:assert/strict');const vm=require('node:vm');const fs=require('node:fs');
const schedule=require('../../public/signage/schedule.js');
const flush=async()=>{for(let i=0;i<20;i++)await new Promise(r=>setImmediate(r));};

test('catalog polling preserves playlist position through repeated complete cycles',async()=>{
 let now=0;const timers=[],shown=[];
 const node={textContent:'',hidden:false,addEventListener(){},querySelectorAll:()=>[],querySelector:()=>null};
 const manifest={schema_version:1,playback_mode:'continuous',layout_revision:'unchanged',server_time:'2026-11-09T12:00:00Z',default_release_id:1,schedules:[],releases:[{id:1,snapshot:{scenes:['a','b','c'].map(id=>({id,type:'grid',duration:20}))}}]};
 const context={URL,Response,AbortController,structuredClone,setTimeout,clearTimeout,performance:{now:()=>now},Date,location:{href:'http://localhost/signage/'},navigator:{onLine:true},document:{getElementById:()=>node},window:{addEventListener(){}},setInterval:(fn,ms)=>timers.push({fn,ms}),
 GCSignage:{fallback(){},priceFresh:()=>true,cardsForScene:()=>[{}],render(stage,scene){shown.push(scene.id);return{update(){}};}},GCSignageSchedule:schedule,GCSignageStorage:{database:()=>({load:async k=>k==='token'?'device':manifest,save:async()=>{}}),stageRelease:async()=>manifest,blobUrl:async()=>''},fetch:async url=>new Response(JSON.stringify(String(url).endsWith('/catalog')?{schema_version:1,server_time:'2026-11-09T12:00:00Z',valid_until:'2026-11-09T12:30:00Z',products:[]}:manifest))};
 vm.runInNewContext(fs.readFileSync(require.resolve('../../public/signage/player.js'),'utf8'),context);await flush();
 for(now=10000;now<=120000;now+=10000){
  if(now%30000===0){timers.find(t=>t.ms===30000).fn();await flush();}
  timers.find(t=>t.ms===1000).fn();await flush();
 }
 assert.deepEqual(shown,['a','b','c','a','b','c','a']);
});

test('retiring a previous downloaded release does not restart the current playlist',async()=>{
 let now=0;const timers=[],shown=[];
 const node={textContent:'',hidden:false,addEventListener(){},querySelectorAll:()=>[],querySelector:()=>null};
 const current={id:2,snapshot:{scenes:['a','b','c'].map(id=>({id,type:'grid',duration:20}))}};
 let incoming={schema_version:1,playback_mode:'continuous',layout_revision:'with-old-release',server_time:'2026-11-09T12:00:00Z',default_release_id:2,schedules:[],releases:[current,{id:1,snapshot:{scenes:[]}}]};
 const context={URL,Response,AbortController,structuredClone,setTimeout,clearTimeout,performance:{now:()=>now},Date,location:{href:'http://localhost/signage/'},navigator:{onLine:true},document:{getElementById:()=>node},window:{addEventListener(){}},setInterval:(fn,ms)=>timers.push({fn,ms}),
 GCSignage:{fallback(){},priceFresh:()=>true,cardsForScene:()=>[{}],render(stage,scene){shown.push(scene.id);return{update(){}};}},GCSignageSchedule:schedule,GCSignageStorage:{database:()=>({load:async k=>k==='token'?'device':structuredClone(incoming),save:async()=>{}}),stageRelease:async manifest=>manifest,blobUrl:async()=>''},fetch:async url=>new Response(JSON.stringify(String(url).endsWith('/catalog')?{schema_version:1,server_time:'2026-11-09T12:00:00Z',valid_until:'2026-11-09T12:30:00Z',products:[]}:incoming))};
 vm.runInNewContext(fs.readFileSync(require.resolve('../../public/signage/player.js'),'utf8'),context);await flush();
 now=20000;timers.find(t=>t.ms===1000).fn();await flush();
 incoming={...incoming,layout_revision:'old-release-retired',releases:[current]};
 now=30000;timers.find(t=>t.ms===30000).fn();await flush();
 now=40000;timers.find(t=>t.ms===1000).fn();await flush();
 assert.deepEqual(shown,['a','b','c']);
 incoming={...incoming,default_release_id:3,layout_revision:'new-playlist',releases:[current,{id:3,snapshot:{scenes:[{id:'new',type:'grid',duration:20}]}}]};
 now=50000;timers.find(t=>t.ms===30000).fn();await flush();
 assert.deepEqual(shown,['a','b','c']);
 now=60000;timers.find(t=>t.ms===1000).fn();await flush();
 assert.deepEqual(shown,['a','b','c','new']);
});
test('blocked video playback backs off instead of retrying and rebuilding every second',async()=>{
 let now=0,renders=0;const timers=[];
 const video={currentTime:0,ended:false,classList:{contains:()=>true},addEventListener(){},play:async()=>{throw new Error('blocked');}};
 const nodes=new Map();const node=id=>nodes.get(id)||nodes.set(id,{textContent:'',hidden:false,addEventListener(){},querySelectorAll:()=>[video],querySelector:()=>video}).get(id);
 const manifest={schema_version:1,server_time:'2026-11-09T12:00:00Z',layout_revision:'new',default_release_id:1,schedule_valid_until:'2026-11-16T12:00:00Z',schedules:[],releases:[{id:1,snapshot:{scenes:[{id:'ad',type:'media',duration:120,play_to_end:false,media:{duration:6}}],files:[]}}]};
 const context={console,URL,Response,AbortController,structuredClone,setTimeout,clearTimeout,performance:{now:()=>now},Date,location:{href:'http://localhost/signage/'},navigator:{onLine:true,storage:{persist:async()=>true,estimate:async()=>({usage:0})}},document:{getElementById:node,hidden:false,documentElement:{}},window:{addEventListener(){}},setInterval:(fn,ms)=>timers.push({fn,ms}),
 GCSignage:{fallback(){},priceFresh:()=>true,cardsForScene:()=>[],render(){renders++;return{update(){}};}},GCSignageSchedule:schedule,GCSignageStorage:{database:()=>({load:async k=>k==='token'?'device':manifest,save:async()=>{}}),stageRelease:async()=>manifest,blobUrl:async()=>''},fetch:async url=>new Response(JSON.stringify(String(url).endsWith('/catalog')?{schema_version:1,server_time:'2026-11-09T12:00:00Z',valid_until:'2026-11-09T12:30:00Z',products:[],invalid_media:[]}:manifest))};
 vm.runInNewContext(fs.readFileSync(require.resolve('../../public/signage/player.js'),'utf8'),context);await flush();
 for(const time of [1000,2000,3000]){now=time;timers.find(t=>t.ms===1000).fn();await flush();}
 assert.equal(renders,1);
});
test('normal video loops are not mistaken for a stalled player during price polling',async()=>{
 let now=0,renders=0;const listeners={},timers=[];
 const video={currentTime:0,ended:false,classList:{contains:()=>true},addEventListener:(event,fn)=>{listeners[event]=fn;},play:async()=>{}};
 const nodes=new Map();const node=id=>nodes.get(id)||nodes.set(id,{textContent:'',hidden:false,addEventListener(){},querySelectorAll:()=>[video],querySelector:()=>video}).get(id);
 const manifest={schema_version:1,server_time:'2026-11-09T12:00:00Z',layout_revision:'new',default_release_id:1,schedule_valid_until:'2026-11-16T12:00:00Z',schedules:[],releases:[{id:1,snapshot:{scenes:[{id:'ad',type:'media',duration:120,play_to_end:false,media:{duration:6}}],files:[]}}]};
 const context={console,URL,Response,AbortController,structuredClone,setTimeout,clearTimeout,performance:{now:()=>now},Date,
  location:{href:'http://localhost/signage/'},navigator:{onLine:true,storage:{persist:async()=>true,estimate:async()=>({usage:0})}},document:{getElementById:node,hidden:false,documentElement:{}},window:{addEventListener(){}},setInterval:(fn,ms)=>timers.push({fn,ms}),
  GCSignage:{fallback(){},priceFresh:()=>true,cardsForScene:()=>[],render(){renders++;return{update(){}};}},GCSignageSchedule:schedule,
  GCSignageStorage:{database:()=>({load:async k=>k==='token'?'test-device':manifest,save:async()=>{}}),stageRelease:async()=>manifest,blobUrl:async()=>''},
  fetch:async url=>new Response(JSON.stringify(String(url).endsWith('/catalog')?{schema_version:1,server_time:'2026-11-09T12:00:00Z',valid_until:'2026-11-09T12:30:00Z',products:[],invalid_media:[]}:manifest)),
 };
 vm.runInNewContext(fs.readFileSync(require.resolve('../../public/signage/player.js'),'utf8'),context);await flush();assert.equal(renders,1);
 for(const [time,position] of [[10000,4],[20000,2],[30000,0.2]]){now=time;video.currentTime=position;listeners.timeupdate?.();timers.find(t=>t.ms===1000).fn();await flush();}
 assert.equal(renders,1);
});
test('revocation prevents a pending release callback and timers from restarting cached media',async()=>{
 let finishStage,revoked=false,renders=0,falls=0;const events={},timers=[];
 const nodes=new Map();const node=id=>nodes.get(id)||nodes.set(id,{textContent:'',hidden:false,addEventListener(){},querySelectorAll(){return[];},querySelector(){return null;}}).get(id);
 const manifest={schema_version:1,server_time:'2026-11-09T12:00:00Z',layout_revision:'new',default_release_id:1,schedule_valid_until:'2026-11-16T12:00:00Z',schedules:[],releases:[{id:1,snapshot:{scenes:[{id:'ad',type:'media',duration:15,bindings:[]}],files:[]}}]};
 const stored={token:'test-device',manifest};
 const context={console,URL,Response,AbortController,structuredClone,setTimeout,clearTimeout,performance:{now:()=>100},Date,
  location:{href:'http://localhost/signage/'},navigator:{onLine:true,storage:{persist:async()=>true,estimate:async()=>({usage:0})}},
  document:{getElementById:node,hidden:false,documentElement:{requestFullscreen:async()=>{}}},window:{addEventListener:(key,fn)=>{events[key]=fn;}},
  setInterval:(fn,ms)=>{timers.push({fn,ms});},
  GCSignage:{fallback(){falls++;},priceFresh:()=>true,cardsForScene:()=>[],render(){renders++;return{update(){}};}},GCSignageSchedule:schedule,
  GCSignageStorage:{database:()=>({load:async k=>stored[k],save:async(k,v)=>{stored[k]=v;}}),stageRelease:()=>new Promise(r=>{finishStage=r;}),blobUrl:async()=>''},
  fetch:async url=>{
   if(revoked)return new Response('{}',{status:401});
   if(String(url).endsWith('/catalog'))return new Response(JSON.stringify({schema_version:1,server_time:'2026-11-09T12:00:00Z',valid_until:'2026-11-09T12:30:00Z',products:[],invalid_media:[]}));
   return new Response(JSON.stringify(manifest));
  },
 };
 vm.runInNewContext(fs.readFileSync(require.resolve('../../public/signage/player.js'),'utf8'),context);
 await flush();assert.equal(typeof finishStage,'function',node('player-status').textContent);
 revoked=true;events.online();await flush();finishStage(manifest);await flush();
 for(const timer of timers.filter(t=>t.ms===1000))timer.fn();await flush();
 assert.equal(stored.token,null);assert.equal(renders,0);assert(falls>=2);
});

test('ordinary scene transitions keep the advert on screen without a safety-fallback flash',async()=>{
 let now=0,renders=0,falls=0;const timers=[];
 const node={textContent:'',hidden:false,addEventListener(){},querySelectorAll:()=>[],querySelector:()=>null};
 const manifest={schema_version:1,server_time:'2026-11-09T12:00:00Z',default_release_id:1,schedule_valid_until:'2026-11-16T12:00:00Z',schedules:[],releases:[{id:1,snapshot:{scenes:[{id:'a',type:'grid',duration:5},{id:'b',type:'grid',duration:5}]}}]};
 const context={URL,Response,AbortController,structuredClone,setTimeout,clearTimeout,performance:{now:()=>now},Date,location:{href:'http://localhost/signage/'},navigator:{onLine:true},document:{getElementById:()=>node},window:{addEventListener(){}},setInterval:(fn,ms)=>timers.push({fn,ms}),
 GCSignage:{fallback(){falls++;},priceFresh:()=>true,cardsForScene:()=>[{}],render(){renders++;return{update(){}};}},GCSignageSchedule:schedule,GCSignageStorage:{database:()=>({load:async k=>k==='token'?'device':manifest,save:async()=>{}}),stageRelease:async()=>manifest,blobUrl:async()=>''},fetch:async url=>new Response(JSON.stringify(String(url).endsWith('/catalog')?{schema_version:1,server_time:'2026-11-09T12:00:00Z',valid_until:'2026-11-09T12:30:00Z',products:[]}:manifest))};
 vm.runInNewContext(fs.readFileSync(require.resolve('../../public/signage/player.js'),'utf8'),context);await flush();
 assert.equal(renders,1);const before=falls;now=5000;timers.find(t=>t.ms===1000).fn();await flush();
 assert.equal(renders,2);assert.equal(falls,before);
});
