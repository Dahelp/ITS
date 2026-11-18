const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const api = require('../public/js/checkout-progress.js');
for (const [n, word] of [[1,'товар'],[2,'товара'],[5,'товаров'],[11,'товаров'],[21,'товар'],[24,'товара'],[111,'товаров']]) {
  assert.equal(api.resumeText(n), `Продолжить оформление? У вас ${n} ${word} в корзине`);
}
const source = fs.readFileSync('public/js/checkout-progress.js','utf8');
function visit({qty=2, owner='guest', generation='0', expired=false, blocked=false}={}) {
  const listeners = {};
  const elements = {
    'checkout-site-resume': {hidden:true,dataset:{draftOwner:owner,draftGeneration:generation,cartQty:String(qty)}},
    'checkout-site-resume-text': {},
    'checkout-site-resume-dismiss': {addEventListener:(name,fn)=>listeners.dismiss=fn}
  };
  let removed=false;
  const draft={version:1,owner:'guest',generation:'0',updatedAt:Date.now()-(expired?api.ttl+1000:0),fields:{name:'Иван'},step:3};
  vm.runInNewContext(source, {
    document:{getElementById:id=>elements[id],addEventListener:(name,fn)=>listeners[name]=fn},
    localStorage:{getItem(){if(blocked)throw Error('blocked');return JSON.stringify(draft);},removeItem(){removed=true;}},
    addEventListener:(name,fn)=>listeners[name]=fn
  });
  return {elements,listeners,removed};
}
let page=visit();
assert.equal(page.elements['checkout-site-resume'].hidden,false);
assert.equal(page.elements['checkout-site-resume-text'].textContent,api.resumeText(2));
page.listeners.dismiss();assert.equal(page.elements['checkout-site-resume'].hidden,true);
for (const options of [{qty:0},{owner:'user-1'},{generation:'completed'},{expired:true},{blocked:true}]) {
  assert.equal(visit(options).elements['checkout-site-resume'].hidden,true);
}
assert.equal(visit({qty:0}).removed,true);
page=visit();page.listeners['cart:cleared']();assert.equal(page.elements['checkout-site-resume'].hidden,true);
page=visit();page.listeners.storage({key:'checkout-draft-v1:guest',newValue:null});assert.equal(page.elements['checkout-site-resume'].hidden,true);
console.log('Site checkout reminder: quantity, plurals, dismissal, owner, expiry, completed order, empty cart and unavailable storage OK');
