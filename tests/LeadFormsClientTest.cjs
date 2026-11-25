const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const events = {}, windowEvents = {}, requests = [], timers = new Map();
let timerId = 0, submitted = 0;
function node() { return {dataset:{},style:{},setAttribute(k,v){this[k]=v;},getAttribute(k){return this[k]||null;}}; }
function makeForm(scope) {
  const form = Object.assign(node(), {classList:{add(){}}, matches:()=>true, appendChild(n){this.status=n;}});
  const phone = Object.assign(node(), {value:'',message:'',willValidate:true,
    matches:()=>true,closest:()=>form,setCustomValidity(v){this.message=v;},
    checkValidity(){return !this.message;},get validationMessage(){return this.message;},
    insertAdjacentElement(){},focus(){this.focused=true;}});
  form.token={value:'',dataset:{leadScope:scope}};
  form.button=Object.assign(node(),{disabled:false,form});
  form.phone=phone; form.elements=[phone];
  form.querySelector=s=>s.includes('lead_token') ? form.token : form.status || null;
  form.querySelectorAll=s=>s.includes('submit') ? [form.button] : [phone];
  form.requestSubmit=()=>{if(!submit(form).defaultPrevented) submitted++;};
  return form;
}
const form=makeForm('callback'), second=makeForm('oneclick'), same=makeForm('callback');
const targetQuantity={value:'1'};
const document={
  addEventListener(name,fn){(events[name] ||= []).push(fn);},
  createElement:node,querySelector:()=>targetQuantity,querySelectorAll:()=>[form,second,same]
};
vm.runInNewContext(fs.readFileSync('public/js/lead-forms.js','utf8'),{
  document,window:{addEventListener(name,fn){windowEvents[name]=fn;}},AbortController,
  fetch(url,options){return new Promise((resolve,reject)=>requests.push({url,options,resolve,reject}));},
  setTimeout(fn,ms){const id=++timerId;timers.set(id,{fn,ms});return id;},clearTimeout(id){timers.delete(id);}
});
function submit(target=form){
  const event={target,submitter:target.button,defaultPrevented:false,preventDefault(){this.defaultPrevented=true;}};
  events.submit.forEach(fn=>fn(event));return event;
}
function focus(target){events.focusin.forEach(fn=>fn({target:{closest:()=>target}}));}
async function tick(){await new Promise(resolve=>setImmediate(resolve));}
function zeroTimers(){for(const [id,t] of timers) if(t.ms===0){timers.delete(id);t.fn();}}
function answer(index,token='a'.repeat(64)){requests[index].resolve({ok:true,json:async()=>({ok:true,token})});}
(async()=>{
  assert.equal(requests.length,0,'page load must not request a session');
  for(const value of ['+7 (9','+7 (999) 999-99-99','+79123456789abc','+79123456789\n','']){
    form.phone.value=value;assert.equal(submit().defaultPrevented,true,value);
    assert.equal(form.dataset.sending,undefined);
  }
  assert.equal(requests.length,0,'invalid submission does not fetch token');
  for(const value of ['+7 (912) 345-67-89','8 (912) 345-67-89']){
    form.phone.value=value;events.input[0]({target:form.phone});assert.equal(form.phone.message,'');
  }
  focus(form);focus(same);focus(second);await tick();
  assert.equal(requests.length,1,'same scope coalesces; other scope waits for first cookie');
  assert.equal(requests[0].options.credentials,'same-origin');
  assert.equal(requests[0].options.cache,'no-store');
  assert.equal(requests[0].options.headers['X-Requested-With'],'XMLHttpRequest');
  answer(0);await tick();
  assert.equal(form.token.value,'a'.repeat(64));assert.equal(same.token.value,form.token.value);
  assert.equal(requests.length,2);answer(1,'b'.repeat(64));await tick();
  assert.equal(second.token.value,'b'.repeat(64));
  events.click[0]({target:{closest:()=>({closest:()=>null,parentElement:{querySelector:()=>({value:'4'})}})}});
  assert.equal(targetQuantity.value,'4');
  assert.equal(submit().defaultPrevented,false);assert.equal(submit().defaultPrevented,true);
  zeroTimers();assert.equal(form.button.disabled,true);
  windowEvents.pageshow({persisted:true});assert.equal(form.dataset.sending,undefined);
  assert.equal(form.button.disabled,false);assert.equal(form.token.value,'','BFCache token invalidated');
  assert.equal(submit().defaultPrevented,true);assert.equal(submit().defaultPrevented,true);
  await tick();assert.equal(requests.length,3,'double click must not duplicate token request');
  requests[2].reject(new Error('offline'));await tick();
  assert.equal(form.button.disabled,false);assert.equal(form.dataset.tokenLoading,undefined);
  assert.equal(form.phone.value,'8 (912) 345-67-89','network failure preserves form');
  assert.equal(form.status.hidden,false);assert.match(form.status.textContent,/повторите попытку/);
  assert.equal(submit().defaultPrevented,true);await tick();answer(3);await tick();
  assert.equal(submitted,1,'successful retry resumes exactly one normal submission');
  zeroTimers();assert.equal(form.button.disabled,true);
  windowEvents.pageshow({persisted:false});form.token.value='c'.repeat(64);
  const blocked=submit();blocked.preventDefault();zeroTimers();
  assert.equal(form.dataset.sending,undefined,'another validator can cancel without permanent lock');
  console.log('Lead forms client: no eager request, validation, scoped/serial tokens, double submit, retry, quantity and BFCache OK');
})().catch(error=>{console.error(error);process.exitCode=1;});
