(function(root){
 'use strict';
 function generatePassword(crypto){
  if(!crypto||!crypto.getRandomValues)throw new Error('Secure randomness unavailable');
  function index(n){var value=new Uint32Array(1),limit=Math.floor(4294967296/n)*n;do{crypto.getRandomValues(value);}while(value[0]>=limit);return value[0]%n;}
  var groups=['ABCDEFGHJKLMNPQRSTUVWXYZ','abcdefghijkmnopqrstuvwxyz','23456789','!@#$%'],pool=groups.join('');
  var chars=groups.map(function(group){return group[index(group.length)];});
  while(chars.length<18)chars.push(pool[index(pool.length)]);
  for(var i=chars.length-1;i>0;i--){var j=index(i+1),old=chars[i];chars[i]=chars[j];chars[j]=old;}
  return chars.join('');
 }
 if(typeof module==='object'&&module.exports){module.exports={generatePassword:generatePassword};return;}
 document.querySelectorAll('[data-password-field]').forEach(function(wrapper){
  var input=wrapper.querySelector('input'),toggle=wrapper.querySelector('[data-password-toggle]'),generate=wrapper.querySelector('[data-password-generate]'),status=wrapper.querySelector('[data-password-status]');
  if(!input||!toggle)return;
  function reveal(show){input.type=show?'text':'password';toggle.textContent=show?'Скрыть':'Показать';toggle.setAttribute('aria-pressed',String(show));}
  toggle.addEventListener('click',function(){reveal(input.type==='password');});
  if(generate){generate.disabled=!(root.crypto&&root.crypto.getRandomValues);generate.addEventListener('click',function(){input.value=generatePassword(root.crypto);reveal(true);input.dispatchEvent(new Event('input',{bubbles:true}));if(status)status.textContent='Пароль сгенерирован. Сохраните его в менеджере паролей.';input.focus();});}
 });
})(typeof window==='undefined'?globalThis:window);
