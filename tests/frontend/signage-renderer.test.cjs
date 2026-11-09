const {test} = require('node:test');
const assert = require('node:assert/strict');
const {cardsForScene, priceFresh} = require('../../public/signage/renderer.js');

test('multiple prices stay on one product card and unavailable rows disappear', () => {
  const scene = {bindings:[{id:'plain',card_id:'burger'},{id:'cheese',card_id:'burger'},{id:'missing'}]};
  const products = [{id:'plain',name:'Burger',available:true,formatted_price:'2 000 Ft'},
    {id:'cheese',name:'Burger',available:true,label:'Sajttal',formatted_price:'2 250 Ft'}, {id:'missing',available:false}];
  const cards=cardsForScene(scene, products);
  assert.equal(cards.length,1); assert.equal(cards[0].rows.length,2); assert.equal(cards[0].rows[1].label,'Sajttal');
});

test('five through eight products retain a defined grid and never fabricate missing prices', () => {
  for (const count of [5,6,7,8]) {
    const bindings=Array.from({length:count},(_,i)=>({id:String(i)}));
    const rows=bindings.map(b=>({...b,available:true,name:'Hosszú magyar terméknév',formatted_price:'1 000 Ft'}));
    assert.equal(cardsForScene({bindings},rows).length,count);
  }
  assert.deepEqual(cardsForScene({bindings:[{id:'x'}]},[]),[]);
});

test('freshness expires using monotonic elapsed time and fails closed after a wall clock rollback', () => {
  assert.equal(priceFresh({ttl:1000,monotonic:10,wall:100}, 1009, 1099),true);
  assert.equal(priceFresh({ttl:1000,monotonic:10,wall:100}, 1010, 1100),false);
  assert.equal(priceFresh({ttl:1000,monotonic:10,wall:100}, 11, 99),false);
  assert.equal(priceFresh(null, 10, 100),false);
});
