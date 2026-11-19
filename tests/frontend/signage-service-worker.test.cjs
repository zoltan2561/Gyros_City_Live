const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
test('retired offline worker unregisters without installing caches or intercepting requests',async()=>{
 const events={},called=[];
 const self={addEventListener:(key,fn)=>{events[key]=fn;},skipWaiting:async()=>called.push('skip'),registration:{unregister:async()=>called.push('unregister')}};
 vm.runInNewContext(fs.readFileSync(require.resolve('../../public/signage/sw.js'),'utf8'),{self});
 for(const key of ['install','activate']){
  let work;events[key]({waitUntil:promise=>{work=promise;}});await work;
 }
 assert.deepEqual(called,['skip','unregister']);assert.equal(events.fetch,undefined);
});
