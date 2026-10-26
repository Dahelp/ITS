const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const source=fs.readFileSync(process.argv[2]||'public/js/main.js','utf8');
const start=source.indexOf('  function requestCart(endpoint, data){'),begin=source.indexOf('    success: function(res){',start),end=source.indexOf('    error: function()',begin);
assert(start>=0&&begin>start&&end>begin);
const body=source.slice(begin+'    success: function(res){'.length,end).replace(/},\s*$/,'');
function response(endpoint,{ok=true,feedback='',table=false,qty=0}={}){const calls={header:0,sync:0,notice:[],updated:0,cleared:0};const chain={length:1,html(){return this;},find(){return this;},text(){return feedback;},toggle(){return this;},trigger(){calls.updated++;return this;}};const $=()=>chain;$.trim=s=>String(s).trim();
 vm.runInNewContext('(function(res){'+body+'})',{$,Event,window:{location:{}},url:p=>p,document:{dispatchEvent:e=>{if(e.type==='cart:cleared')calls.cleared++;}},endpoint,data:{id:endpoint==='/cart/addcomplete'?'1-2':'1'},isCartTableHtml:()=>table,readCartTableState:()=>({qty}),syncAllFromCartTable:()=>{},syncCatalogButtons:()=>{},refreshOrderItemsReadonly:()=>{},getProductIdsFromCartStateHtml:()=>ok?{1:1,2:1}:{},isCartStateHtml:()=>ok,updateHeaderFromCartStateHtml:()=>{calls.header++;},syncCatalogButtonsFromIds:()=>{calls.sync++;},showCartFeedback:(...args)=>{calls.notice.push(args);}})('fixture');return calls;}
for(const endpoint of ['/cart/add','/cart/addcomplete']){const calls=response(endpoint);assert.equal(calls.header,1);assert.equal(calls.sync,1);assert.equal(calls.updated,1);assert.deepEqual(calls.notice,[['Товар добавлен в корзину',false]]);}
let calls=response('/cart/add',{ok:false});assert.equal(calls.header,0);assert.equal(calls.notice[0][1],true);assert.equal(calls.cleared,0);
calls=response('/cart/add',{feedback:'Доступно только 2 шт.'});assert.equal(calls.notice[0][0],'Доступно только 2 шт.');
calls=response('/cart/clear',{table:true,qty:0});assert.equal(calls.cleared,1);assert.equal(calls.notice[0][0],'Корзина очищена');
calls=response('/cart/quantity',{table:true,qty:1});assert.equal(calls.cleared,0);
for(const retired of ['showCartModal','showAddedNotice','notifyCartWithoutModal','js-cart-modal-close','/cart/plusmodal','/cart/minusmodal','cartAddedNotice'])assert(!source.includes(retired),retired);
assert.equal((source.match(/function showCartFeedback\(/g)||[]).length,1);
for(const file of ['app/widgets/product/card.php','app/views/itscenter/Comparison/index.php','app/views/itscenter/User/wishlist.php','app/views/itscenter/partials/modals.php'])assert(!fs.readFileSync(file,'utf8').includes('data-bs-target="#exampleModalLive"'),file);
console.log('Single cart response/notification, add/complete, errors, confirmed clear event and retired modal code regressions OK');
