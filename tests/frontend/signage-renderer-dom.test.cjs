const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');

class Element {
  constructor(tag){this.tagName=tag;this.children=[];this.dataset={};this.className='';this.textContent='';this.parentNode=null;this.style={setProperty(){}};this.classList={add:value=>{this.className+=' '+value;}};}
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
  const context={document:{createElement:tag=>new Element(tag)}};
  vm.runInNewContext(fs.readFileSync(require.resolve('../../public/signage/renderer.js'),'utf8'),context);
  return {api:context.GCSignage,stage:new Element('main')};
}
const scene={type:'split',theme:'dark',title:'Burgerek',bindings:[{id:'a'},{id:'b'}],media:{url:'/video.mp4',mime:'video/mp4'}};
const products=[{id:'a',name:'Burger',image:'/burger.jpg',available:true,formatted_price:'2 000 Ft'}, {id:'b',name:'Fishburger',available:true,formatted_price:'1 790 Ft'}];

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
