const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const source=fs.readFileSync(process.argv[2]||'public/js/main.js','utf8');
let start=source.indexOf('  function requestCart(endpoint, data){');let callbackStart=source.indexOf('    success: function(res){',start),end=source.indexOf('    error: function()',callbackStart);
assert(start>=0&&callbackStart>start&&end>callbackStart);
const body=source.slice(callbackStart+'    success: function(res){'.length,end).replace(/},\s*$/,'');
function response(endpoint,{ok=true,feedback=''}={}){const calls={header:0,sync:0,modal:0,notice:[],updated:0};const chain={html(){return this;},find(){return this;},text(){return feedback;},trigger(){calls.updated++;return this;}};const $=()=>chain;$.trim=s=>String(s).trim();
 vm.runInNewContext('(function(res){'+body+'})',{$,document:{},endpoint,data:{id:endpoint==='/cart/addcomplete'?'1-2':'1'},getProductIdsFromModalHtml:()=>ok?{1:1,2:1}:{},isCartModalHtml:()=>ok,updateHeaderFromModalHtml:()=>{calls.header++;},syncCatalogButtonsFromIds:()=>{calls.sync++;},notifyCartWithoutModal:(...args)=>{calls.notice.push(args);},showCartModal:()=>{calls.modal++;}})('<table>fixture</table>');return calls;}
for(const endpoint of ['/cart/add','/cart/addcomplete']){const calls=response(endpoint);assert.equal(calls.header,1);assert.equal(calls.sync,1);assert.equal(calls.modal,0);assert.equal(calls.updated,1);assert.deepEqual(calls.notice,[['Товар добавлен в корзину',false]]);}
let calls=response('/cart/add',{ok:false});assert.equal(calls.modal,0);assert.equal(calls.header,0);assert.equal(calls.notice[0][1],true);
calls=response('/cart/add',{feedback:'Доступно только 2 шт.'});assert.equal(calls.modal,0);assert.equal(calls.notice[0][0],'Доступно только 2 шт.');
start=source.indexOf('  function showCartModal(html){');end=source.indexOf('  var inlineCartFeedbackTimer',start);const window={location:{href:''}};vm.runInNewContext('('+source.slice(start,end).trim()+')',{window,url:p=>p})('legacy');assert.equal(window.location.href,'/cart/view');
for(const file of ['app/widgets/product/card.php','app/views/itscenter/Comparison/index.php','app/views/itscenter/User/wishlist.php','app/views/itscenter/partials/modals.php'])assert(!fs.readFileSync(file,'utf8').includes('data-bs-target="#exampleModalLive"'),file);
assert(!fs.readFileSync('app/views/itscenter/partials/modals.php','utf8').includes('id="exampleModalLive"'));
console.log('Cart add and complete: feedback/header sync without modal; errors, legacy redirect and shared template regressions OK');
