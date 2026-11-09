const {test}=require('node:test');
const assert=require('node:assert/strict');
const vm=require('node:vm');
const fs=require('node:fs');
const {priceFresh}=require('../../public/signage/renderer.js');

function preview(){
 let now=0,falls=0;const rendered=[],events={},timer=[];
 const sceneButtons=[0,1].map(index=>({dataset:{scene:String(index)},setAttribute(){},addEventListener:(event,fn)=>events['scene-'+index]=fn}));
 const stage={dataset:{},addEventListener(){},querySelector(){return null;},querySelectorAll(){return[];}};
 const pause={addEventListener:(event,fn)=>events.pause=fn,setAttribute(){},textContent:''};
 const data={snapshot:{scenes:[{id:'a',duration:5},{id:'b',duration:5}]},catalog:{server_time:'2026-11-09T12:00:00Z',valid_until:'2026-11-09T12:00:10Z',products:[]}};
 const context={Date,performance:{now:()=>now},setInterval:fn=>timer.push(fn),document:{getElementById:id=>id==='preview-data'?{textContent:JSON.stringify(data)}:id==='signage-stage'?stage:id==='preview-pause'?pause:null,querySelectorAll:()=>sceneButtons},
 GCSignage:{priceFresh,fallback(){falls++;},render(stage,scene){rendered.push(scene.id);return{pause(){}};}}};
 vm.runInNewContext(fs.readFileSync(require.resolve('../../public/signage/preview.js'),'utf8'),context);
 return {rendered,events,advance(ms){now+=ms;timer.forEach(fn=>fn());},pause(){events.pause({target:pause});},get falls(){return falls;}};
}

test('admin playlist preview automatically advances while its catalogue is fresh',()=>{
 const run=preview();assert.deepEqual(run.rendered,['a']);run.advance(5000);assert.deepEqual(run.rendered,['a','b']);
});
test('admin preview removes expired prices during pause and cannot restore them with a scene button',()=>{
 const run=preview();run.pause();run.advance(11000);assert.equal(run.falls,1);assert.deepEqual(run.rendered,['a']);
 run.events['scene-1']();assert.equal(run.falls,2);assert.deepEqual(run.rendered,['a']);
});
