const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const source=fs.readFileSync('public/js/checkout-progress.js','utf8');
const begin=source.indexOf(' function start(){'),end=source.indexOf(" form.addEventListener('checkout:ready'",begin);
const api=require('../public/js/checkout-progress.js');
function resume(blocked=false){
 const inputs={address:{value:'',tagName:'INPUT',dispatchEvent(){}},checkout_legal_agree:{checked:true}};
 const banner={hidden:false,querySelector:()=>({})};let step=0,notice='',saved=false,removed=false;
 const nodes={'checkout-login-resume':{textContent:JSON.stringify({time:Math.floor(Date.now()/1000),step:3,attachment:true,fields:{address:'Доставка после входа',email:'guest@example.test'}})},'checkout-continue':{addEventListener(){}},'checkout-start-over':{addEventListener(){}}};
 const ctx={started:false,form:{checkoutNavigation:{restoreDelivery(){},goTo(n){step=n;}},addEventListener(){},querySelectorAll(){return[];}},key:'checkout-draft-v1:user-5',owner:'user-5',generation:'0',readDraft:api.readDraft,FIELDS:api.fields,field:n=>inputs[n],banner,resumeText:api.resumeText,progress(){},message:t=>notice=t,save:()=>saved=true,clear(){},resetDraftFields(){},Event,MutationObserver:class{observe(){}},setInterval(){},root:{addEventListener(){}},document:{getElementById:id=>nodes[id],querySelector:()=>({textContent:'2'}),addEventListener(){}},localStorage:{getItem(){if(blocked)throw Error('blocked');return null;},removeItem(){if(blocked)throw Error('blocked');removed=true;}}};
 vm.createContext(ctx);vm.runInContext('('+source.slice(begin,end).trim()+')()',ctx);
 assert.equal(inputs.address.value,'Доставка после входа');assert.equal(inputs.checkout_legal_agree.checked,false);assert.equal(step,3);assert(notice.includes('повторно'));assert(saved);if(!blocked)assert(removed);
}
resume();resume(true);
assert(!source.includes("getElementById('checkout-continue').addEventListener"));
assert(!fs.readFileSync('app/views/itscenter/Cart/view.php','utf8').includes('id="checkout-resume"'));

// Login navigation bypasses order validation, while ordinary submits still validate step 3.
const main=fs.readFileSync('public/js/main.js','utf8');
const a=main.indexOf("  $('#checkoutForm').off('submit.checkout').on('submit.checkout', function(e){"),b=main.indexOf('\n  });',a);
const fnText=main.slice(main.indexOf('function(e){',a),b)+'\n}';let prevented=false,validated=false;
const fn=vm.runInNewContext('('+fnText+')',{$:()=>({attr:()=>1}),validateStep(){validated=true;return false;}});
fn({originalEvent:{submitter:{id:'checkout-login'}},preventDefault(){prevented=true;}});assert(!prevented&&!validated);
fn({originalEvent:{submitter:{id:'btnSubmitOrder'}},preventDefault(){prevented=true;}});assert(prevented);
console.log('CR10 login: resumes fields and step under signed-in owner, resets consent, reminds about attachment, works without storage; login bypasses order validation OK');
