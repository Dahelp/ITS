(function(root){
 'use strict';
 var FIELDS=['dostavka_id','transport_id','branch_id','city_name','address','groups','nds','dogovor','name','telefon','email','form_callback','note','payment_method'];
 var TTL=24*60*60*1000;
 function readDraft(raw,owner,generation,now){
  try{var d=JSON.parse(raw);if(!d||d.version!==1||d.owner!==owner||d.generation!==generation||!Number.isFinite(d.updatedAt)||now-d.updatedAt>TTL||d.updatedAt>now+300000||!d.fields||Array.isArray(d.fields))return null;
   var fields={};FIELDS.forEach(function(k){if(typeof d.fields[k]==='string')fields[k]=d.fields[k].slice(0,k==='note'?5000:2000);});
   return{version:1,owner:owner,generation:generation,updatedAt:d.updatedAt,step:Math.max(1,Math.min(3,parseInt(d.step,10)||1)),fields:fields,hadAttachment:d.hadAttachment===true};
  }catch(e){return null;}
 }
 var api={readDraft:readDraft,fields:FIELDS,ttl:TTL};
 if(typeof module==='object'&&module.exports){module.exports=api;return;}
 var form=document.getElementById('checkoutForm');if(!form)return;
 var owner=form.dataset.draftOwner||'guest',generation=form.dataset.draftGeneration||'0',key='checkout-draft-v1:'+owner;
 var dirty=false,restoring=false,started=false,attachment=false,suppressProgress=false;
 var hint=document.getElementById('checkout-form-notice'),banner=document.getElementById('checkout-resume'),savedStep=1;
 try{var initialRaw=localStorage.getItem(key);if(initialRaw&&!readDraft(initialRaw,owner,generation,Date.now())){localStorage.removeItem(key);sessionStorage.removeItem('checkout_delivery_state_v1');sessionStorage.removeItem('checkout_step');}}catch(e){}
 function message(text){if(hint){hint.textContent=text;hint.hidden=!text;}}
 function field(name){return form.elements.namedItem(name);}
 function snapshot(){var fields={};FIELDS.forEach(function(name){var input=field(name);if(input&&!input.disabled)fields[name]=String(input.value||'').slice(0,name==='note'?5000:2000);});var file=field('rekvizity');return{version:1,owner:owner,generation:generation,updatedAt:Date.now(),step:Math.max(1,Math.min(3,parseInt(form.dataset.currentStep,10)||1)),fields:fields,hadAttachment:attachment||!!(file&&file.files&&file.files.length)};}
 function clear(){try{localStorage.removeItem(key);}catch(e){}try{sessionStorage.removeItem('checkout_delivery_state_v1');sessionStorage.removeItem('checkout_step');}catch(e){}dirty=false;if(banner)banner.hidden=true;}
 function resetDraftFields(){restoring=true;attachment=false;savedStep=1;FIELDS.forEach(function(name){var input=field(name);if(!input)return;if(input.tagName==='SELECT'){input.value='';if(input.selectedIndex<0)input.selectedIndex=0;}else input.value='';input.dispatchEvent(new Event('change',{bubbles:true}));});var file=field('rekvizity');if(file)file.value='';var consent=field('checkout_legal_agree');if(consent)consent.checked=false;suppressProgress=true;if(form.checkoutNavigation)form.checkoutNavigation.goTo(1);clear();restoring=false;message('');}
 function save(){if(!dirty||restoring)return;if(!document.querySelector('.cart-qty')||Number(document.querySelector('.cart-qty').textContent)<=0){clear();return;}try{localStorage.setItem(key,JSON.stringify(snapshot()));dirty=false;}catch(e){message('Браузер не разрешил сохранить черновик. Не закрывайте страницу до завершения заказа.');}}
 function progress(){var step=parseInt(form.dataset.currentStep,10)||1;var label='Шаг '+step+' из 3 — '+['Корзина','Получение','Контакты и оплата'][step-1];document.getElementById('checkout-progress-label').textContent=label;var meter=document.getElementById('checkout-progress');meter.setAttribute('aria-valuenow',step);meter.setAttribute('aria-valuetext',label);document.getElementById('checkout-progress-fill').style.width=Math.round(step/3*100)+'%';form.querySelectorAll('[data-checkout-go]').forEach(function(button){var n=Number(button.dataset.checkoutGo);button.classList.toggle('is-active',n===step);button.classList.toggle('is-complete',n<step);if(n===step)button.setAttribute('aria-current','step');else button.removeAttribute('aria-current');});if(suppressProgress){suppressProgress=false;return;}if(!restoring){dirty=true;save();}}
 function start(){if(started||!form.checkoutNavigation)return;started=true;var raw=null;try{raw=localStorage.getItem(key);}catch(e){}var draft=readDraft(raw,owner,generation,Date.now());
  if(!document.querySelector('.cart-qty')||Number(document.querySelector('.cart-qty').textContent)<=0){clear();return;}
  if(raw&&!draft)clear();
  restoring=true;
  if(draft){attachment=draft.hadAttachment;savedStep=draft.step;var consent=field('checkout_legal_agree');if(consent)consent.checked=false;
   FIELDS.forEach(function(name){var input=field(name);if(!input||!Object.prototype.hasOwnProperty.call(draft.fields,name))return;var value=draft.fields[name];if(input.tagName==='SELECT'&&!Array.from(input.options).some(function(o){return o.value===value;}))return;input.value=value;input.dispatchEvent(new Event('change',{bubbles:true}));});
   form.checkoutNavigation.restoreDelivery();form.checkoutNavigation.goTo(1);
   banner.hidden=false;document.getElementById('checkout-continue').textContent='Продолжить оформление';
   message(attachment?'Прикрепите реквизиты повторно: браузер не сохраняет файлы.':'');
  }
  dirty=false;progress();restoring=false;
  form.addEventListener('input',function(e){if(!restoring&&FIELDS.indexOf(e.target.name)>=0)dirty=true;});
  form.addEventListener('change',function(e){if(restoring)return;if(e.target.type==='file'){attachment=!!e.target.files.length;dirty=true;}else if(FIELDS.indexOf(e.target.name)>=0){dirty=true;save();}});
  form.querySelectorAll('[data-checkout-go]').forEach(function(button){button.addEventListener('click',function(){form.checkoutNavigation.goTo(Number(button.dataset.checkoutGo));});});
  document.getElementById('checkout-continue').addEventListener('click',function(){banner.hidden=true;form.checkoutNavigation.goTo(savedStep);});
  document.getElementById('checkout-start-over').addEventListener('click',function(){banner.hidden=true;form.checkoutNavigation.goTo(1);});
  document.addEventListener('cart:cleared',resetDraftFields);
  root.addEventListener('storage',function(event){if(event.key===key&&event.newValue===null)resetDraftFields();});
  new MutationObserver(function(){if(form.dataset.currentStep)progress();}).observe(form,{attributes:true,attributeFilter:['data-current-step']});
  setInterval(save,5000);root.addEventListener('pagehide',save);document.addEventListener('visibilitychange',function(){if(document.hidden)save();});
 }
 form.addEventListener('checkout:ready',start);if(form.checkoutNavigation)start();
})(typeof window==='undefined'?globalThis:window);
