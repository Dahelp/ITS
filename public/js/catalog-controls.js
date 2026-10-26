(function(){
 'use strict';
 var form=document.querySelector('[data-catalog-controls]');if(!form||form.dataset.ready)return;form.dataset.ready='1';
 var sort=form.querySelector('[data-catalog-sort]');
 try{var params=new URLSearchParams(location.search),saved=localStorage.getItem('catalog-sort');if(!params.has('sort')&&saved&&Array.from(sort.options).some(function(o){return o.value===saved;})&&saved!==sort.value){var url=new URL(location.href);url.searchParams.set('sort',saved);url.searchParams.delete('page');location.replace(url.href);return;}}catch(e){}
 form.addEventListener('submit',function(){try{localStorage.setItem('catalog-sort',sort.value);}catch(e){}});
})();
