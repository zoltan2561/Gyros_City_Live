const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');

function mount(replies){
 const handlers={},timers=[],requests=[];
 const button={disabled:false},status={textContent:''},download={hidden:true,href:'',download:''};
 const form={action:'/admin/signage/playlists/3/mp4',dataset:{exportsUrl:'/admin/signage/exports'},
 querySelector:s=>s==='button'?button:s==='[role=status]'?status:download,
 addEventListener:(event,fn)=>handlers[event]=fn};
 const fetch=async(url,options)=>{requests.push({url,options});const next=replies.shift();if(next instanceof Error)throw next;return{ok:true,status:200,headers:{get:()=> 'application/json'},json:async()=>next};};
 const context={document:{getElementById:()=>form},fetch,FormData:class{},setTimeout:fn=>{timers.push(fn);return timers.length;},clearTimeout(){},console};
 vm.runInNewContext(fs.readFileSync(require.resolve('../../public/signage/export.js'),'utf8'),context);
 const flush=async()=>{for(let i=0;i<10;i++)await Promise.resolve();};
 return{button,status,download,requests,flush,async submit(){await flush();await handlers.submit({preventDefault(){}});await flush();},async tick(){await timers.shift()?.();await flush();}};
}

test('button starts a background job and shows a protected download when polling completes',async()=>{
 assert.ok(fs.existsSync(require('node:path').resolve(__dirname,'../../public/signage/export.js')),'Background export UI must exist');
 const run=mount([{job:null},{id:'job-1',status:'pending',total_scenes:2,scene:0},{id:'job-1',status:'running',total_scenes:2,scene:1},{id:'job-1',status:'complete',filename:'menu.mp4',total_scenes:2,scene:2}]);
 await run.submit();assert.equal(run.button.disabled,true);assert.equal(run.requests[1].options.method,'POST');
 await run.tick();assert.match(run.status.textContent,/1.*2/);
 await run.tick();assert.equal(run.button.disabled,false);assert.equal(run.download.hidden,false);assert.equal(run.download.href,'/admin/signage/exports/job-1/download');
});

test('reopening the admin follows its existing job without another POST',async()=>{
 const run=mount([{job:{id:'job-2',status:'running',total_scenes:3,scene:2}}]);
 await run.flush();assert.equal(run.button.disabled,true);assert.equal(run.requests.length,1);assert.match(run.status.textContent,/2.*3/);
});

test('a temporary polling failure keeps following the existing job',async()=>{
 const run=mount([{job:{id:'job-2',status:'running',total_scenes:1,scene:0}},new Error('offline'),{id:'job-2',status:'complete',filename:'menu.mp4',total_scenes:1,scene:1}]);
 await run.flush();await run.tick();assert.equal(run.button.disabled,true);await run.tick();assert.equal(run.download.hidden,false);
 assert.equal(run.requests.filter(r=>r.options?.method==='POST').length,0);
});

test('a failed worker shows an actionable message and enables another attempt',async()=>{
 const run=mount([{job:{id:'job-2',status:'failed',total_scenes:1,scene:0}}]);
 await run.flush();assert.equal(run.button.disabled,false);assert.equal(run.download.hidden,true);assert.match(run.status.textContent,/újra/);
});

test('a list without a previous export shows readiness instead of a permanent checking message',async()=>{
 const run=mount([{job:null}]);await run.flush();
 assert.equal(run.button.disabled,false);assert.match(run.status.textContent,/Indíts/);
});
