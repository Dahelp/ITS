const assert=require('node:assert/strict');const {allowed,selected}=require('../public/js/checkout-payment.js');
const rules={methods:{3:{card:'Карта',qr:'QR',bank_transfer:'Банк',cash:'Наличные'},4:{bank_transfer:'Банк'}},pickupIds:['1']};
assert.deepEqual(Object.keys(allowed(rules,3,1)),['card','qr','bank_transfer','cash']);assert.deepEqual(Object.keys(allowed(rules,3,2)),['card','qr','bank_transfer']);
assert.equal(selected('cash',allowed(rules,3,3)),'');assert.equal(selected('cash',allowed(rules,4,1)),'bank_transfer');assert.equal(selected('qr',allowed(rules,3,1)),'qr');assert.deepEqual(allowed(rules,0,1),{});
assert.equal(selected('',allowed(rules,4,2)),'bank_transfer');assert.equal(selected('',allowed(rules,3,2)),'');assert.equal(selected('x',allowed(rules,0,1)),'');
console.log('Payment UI: delivery/type changes invalidate cash; legal bank auto-selected; physical choice retained OK');
