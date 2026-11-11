const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');

class Element {
  constructor(tag){this.tagName=tag;this.children=[];this.dataset={};this.className='';this.textContent='';this.parentNode=null;this.style={setProperty(){}};this.classList={add:(...values)=>{this.className+=' '+values.join(' ');}};}
  append(...nodes){for(const node of nodes){node.remove();node.parentNode=this;this.children.push(node);}}
  replaceChildren(...nodes){for(const node of this.children)node.parentNode=null;this.children=[];this.append(...nodes);}
  remove(){if(this.parentNode){const children=this.parentNode.children;children.splice(children.indexOf(this),1);this.parentNode=null;}}
  insertBefore(node,before){node.remove();node.parentNode=this;const i=this.children.indexOf(before);this.children.splice(i<0?this.children.length:i,0,node);}
  querySelector(selector){return this.querySelectorAll(selector)[0]||null;}
  querySelectorAll(selector){return this.children.flatMap(child=>[...(selector.startsWith('.')?child.className.split(' ').includes(selector.slice(1)):child.tagName===selector)?[child]:[],...child.querySelectorAll(selector)]);}
  get firstElementChild(){return this.children[0]||null;}
  setAttribute(name,value){this[name]=value;}
}
function setup(){
  const timers=new Map();let timerId=0,time=0;
  const context={document:{createElement:tag=>new Element(tag)},performance:{now:()=>time},setInterval:(fn,ms)=>{timers.set(++timerId,{fn,ms});return timerId;},clearInterval:id=>timers.delete(id)};
  vm.runInNewContext(fs.readFileSync(require.resolve('../../public/signage/renderer.js'),'utf8'),context);
  return {api:context.GCSignage,stage:new Element('main'),timers,advance:ms=>time+=ms,tick:()=>[...timers.values()].forEach(t=>{time+=t.ms;t.fn();})};
}
const scene={type:'split',theme:'dark',title:'Burgerek',bindings:[{id:'a'},{id:'b'}],media:{url:'/video.mp4',mime:'video/mp4'}};
const products=[{id:'a',name:'Burger',image:'/burger.jpg',available:true,formatted_price:'2 000 Ft'}, {id:'b',name:'Fishburger',available:true,formatted_price:'1 790 Ft'}];

test('background presets apply only to general scenes and never leak into clones or fallback',()=>{
  const {api,stage}=setup();
  api.render(stage,{...scene,backdrop:'wood'},products);assert.equal(stage.dataset.signageBackdrop,'wood');
  api.render(stage,{...scene,design:'reference-menu',backdrop:'wood'},products);assert.equal(stage.dataset.signageBackdrop,undefined);
  api.render(stage,{...scene,backdrop:'cream'},products);api.fallback(stage);assert.equal(stage.dataset.signageBackdrop,undefined);
  api.render(stage,{...scene,backdrop:'url(invalid)'},products);assert.equal(stage.dataset.signageBackdrop,undefined);
});

test('modern scenes retain active products on price refresh and reset their styling before a clone',()=>{
  const {api,stage}=setup();
  for(const kind of ['burger','menu','pizza']){
    const renderer=api.render(stage,{...scene,type:kind==='menu'?'grid':'slideshow',design:'modern-'+kind,backdrop:'wood'},products);
    assert(stage.className.includes('modern-scene'));assert(stage.className.includes('modern-'+kind));
    assert.equal(stage.dataset.signageBackdrop,undefined);
    const card=stage.querySelector('.food-card'),photo=stage.querySelector('.food-photo');
    renderer.update([{...products[0],formatted_price:'2 100 Ft'},products[1]]);
    assert.equal(stage.querySelector('.food-card'),card);assert.equal(stage.querySelector('.food-photo'),photo);
    assert.equal(card.querySelector('.food-price').textContent,'2 100 Ft');
  }
  api.render(stage,{...scene,design:'reference-menu'},products);
  assert(!stage.className.includes('modern-scene'));assert(!stage.className.includes('modern-pizza'));
});

test('price updates preserve the card, photo and video nodes so entrance motion never restarts',()=>{
  const {api,stage}=setup();const renderer=api.render(stage,scene,products);
  const card=stage.querySelector('.food-card'),photo=stage.querySelector('.food-photo'),video=stage.querySelector('video');
  renderer.update([{...products[0],formatted_price:'2 100 Ft'},products[1]]);
  assert.equal(stage.querySelector('.food-card'),card);
  assert.equal(stage.querySelector('.food-photo'),photo);
  assert.equal(stage.querySelector('video'),video);
  assert.equal(card.querySelector('.food-price').textContent,'2 100 Ft');
});

test('availability changes remove only the unavailable card and keep the surviving card in place',()=>{
  const {api,stage}=setup();const renderer=api.render(stage,scene,products);
  const survivor=stage.querySelectorAll('.food-card')[1];
  renderer.update([{...products[0],available:false},products[1]]);
  assert.equal(stage.querySelectorAll('.food-card').length,1);
  assert.equal(stage.querySelector('.food-card'),survivor);
  renderer.update(products);
  assert.equal(stage.querySelectorAll('.food-card')[1],survivor);
});

test('a failed photo retries on the next refresh without replacing its card or video',()=>{
  const {api,stage}=setup();const renderer=api.render(stage,scene,products);
  const card=stage.querySelector('.food-card'),video=stage.querySelector('video');
  stage.querySelector('.food-photo').onerror();
  assert.equal(stage.querySelector('.food-photo'),null);
  renderer.update(products);
  assert.equal(stage.querySelector('.food-photo')?.src,'/burger.jpg');
  assert.equal(stage.querySelector('.food-card'),card);
  assert.equal(stage.querySelector('video'),video);
});

const slideshow={...scene,type:'slideshow',slide_seconds:7};
const visible=stage=>stage.querySelectorAll('.food-card').filter(e=>!e.hidden);
test('slideshow advances one product at a time and wraps without rebuilding photos',()=>{
  const {api,stage,timers,tick}=setup();api.render(stage,slideshow,products);
  const first=visible(stage)[0],photo=first.querySelector('.food-photo');
  assert.equal(visible(stage).length,1);assert.equal(first.dataset.cardId,'a');
  assert.equal([...timers.values()][0].ms,7000);
  tick();assert.equal(visible(stage)[0].dataset.cardId,'b');
  tick();assert.equal(visible(stage)[0],first);assert.equal(first.querySelector('.food-photo'),photo);
});
test('live price updates retain the active slide and timer; unavailability removes it immediately',()=>{
  const {api,stage,timers,tick}=setup();const renderer=api.render(stage,slideshow,products);tick();
  const timer=[...timers.keys()][0],active=visible(stage)[0];
  renderer.update([products[0],{...products[1],formatted_price:'1 800 Ft'}]);
  assert.equal(visible(stage)[0],active);assert.equal([...timers.keys()][0],timer);
  assert.equal(active.querySelector('.food-price').textContent,'1 800 Ft');
  renderer.update([products[0],{...products[1],available:false}]);
  assert.equal(visible(stage)[0].dataset.cardId,'a');assert.equal(stage.querySelectorAll('.food-card').length,1);
});
test('replacement, explicit disposal and safety fallback stop every old scene timer',()=>{
  const {api,stage,timers}=setup();const old=api.render(stage,slideshow,products);
  api.render(stage,{...scene,type:'grid'},products);assert.equal(timers.size,1);
  old.dispose();assert.equal(timers.size,1);
  api.fallback(stage);assert.equal(timers.size,0);assert.equal(stage.querySelector('.food-price'),null);
  const renderer=api.render(stage,slideshow,products);renderer.dispose();assert.equal(timers.size,0);
});
test('pause stops slide advancement and resume restores one timer without restarting the current card',()=>{
  const {api,stage,timers,tick}=setup();const renderer=api.render(stage,slideshow,products);tick();
  const active=visible(stage)[0];renderer.pause(true);assert.equal(timers.size,0);tick();assert.equal(visible(stage)[0],active);
  renderer.pause(false);renderer.pause(false);assert.equal(timers.size,1);assert.equal(visible(stage)[0],active);
  tick();assert.equal(visible(stage)[0].dataset.cardId,'a');
});
test('resuming a partially elapsed slide preserves its remaining time and the final product gets a full slot',()=>{
  const {api,stage,timers,advance,tick}=setup();const renderer=api.render(stage,slideshow,products);
  advance(6000);renderer.pause(true);advance(30000);renderer.pause(false);
  assert.equal([...timers.values()][0].ms,1000);tick();assert.equal(visible(stage)[0].dataset.cardId,'b');
  assert.equal([...timers.values()][0].ms,7000);tick();assert.equal(visible(stage)[0].dataset.cardId,'a');
});
test('reference pizza prices refresh without replacing animated title letters or the active photograph',()=>{
  const {api,stage,timers}=setup();const rows=products.map(p=>({...p,price:'2500.00',formatted_price:'2,500 HUF',description:'Paradicsom, sajt'}));
  const renderer=api.render(stage,{...slideshow,design:'reference-pizza'},rows);
  const letter=stage.querySelector('.ref-letter'),photo=stage.querySelector('.food-photo'),timer=[...timers.keys()][0];
  renderer.update(rows.map(p=>({...p,price:'2600.00',formatted_price:'2,600 HUF'})));
  assert.equal(stage.querySelector('.ref-letter'),letter);assert.equal(stage.querySelector('.food-photo'),photo);
  assert.equal(stage.querySelector('.food-price').textContent,'2600 Ft');assert.equal([...timers.keys()][0],timer);
  assert.equal(stage.querySelector('.food-description').textContent,'Paradicsom, sajt');
});
