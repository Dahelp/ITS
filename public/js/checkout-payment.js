(function(root){
 'use strict';
 function allowed(rules,group,delivery){var options=Object.assign({},rules.methods[String(group)]||{});if(rules.pickupIds.indexOf(String(delivery))<0)delete options.cash;return options;}
 function selected(current,options){if(Object.prototype.hasOwnProperty.call(options,current))return current;var keys=Object.keys(options);return keys.length===1?keys[0]:'';}
 if(typeof module==='object'&&module.exports){module.exports={allowed:allowed,selected:selected};return;}
 var form=document.getElementById('checkoutForm'),data=document.getElementById('checkout-payment-rules');if(!form||!data)return;
 var rules=JSON.parse(data.textContent),input=document.getElementById('checkout-payment-method'),kind=document.getElementById('vidurlface'),delivery=document.getElementById('dostavka_id'),summaries=form.querySelectorAll('[data-payment-summary]'),help=document.getElementById('checkout-payment-help');
 var account=Number(form.dataset.customerGroup)||0,changing=false;
 if(kind&&account){kind.value=String(account);kind.disabled=true;}
 function refresh(){if(changing)return;changing=true;var group=account||Number(kind&&kind.value)||0,options=allowed(rules,group,delivery&&delivery.value),old=input.value,value=selected(old,options);input.textContent='';var blank=document.createElement('option');blank.value='';blank.textContent=group?'Выберите способ оплаты':'Сначала выберите вид клиента';input.appendChild(blank);
  Object.keys(options).forEach(function(code){var option=document.createElement('option');option.value=code;option.textContent=options[code];input.appendChild(option);});input.value=value;input.disabled=!Object.keys(options).length;input.required=form.dataset.currentStep==='3';summaries.forEach(function(summary){summary.textContent=options[value]||'Не выбран';});
  help.textContent=old==='cash'&&group===3&&!options.cash?'Наличные доступны только при самовывозе. Выберите другой способ оплаты.':'Реквизиты и порядок оплаты сообщим при подтверждении заказа.';
  changing=false;if(value!==old)input.dispatchEvent(new Event('change',{bubbles:true}));
 }
 form.addEventListener('change',function(e){if(e.target===kind||e.target===delivery||e.target===input)refresh();});
 form.addEventListener('checkout:ready',refresh);
 new MutationObserver(refresh).observe(form,{attributes:true,attributeFilter:['data-current-step']});refresh();
})(typeof window==='undefined'?globalThis:window);
