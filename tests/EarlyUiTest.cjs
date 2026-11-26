const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const layout=fs.readFileSync('app/views/itscenter/layouts/watches.php','utf8');
const script=layout.match(/<script id="early-ui">([\s\S]*?)<\/script>/)[1];
function run(cookie){const names=new Set(),events={};vm.runInNewContext(script,{document:{cookie,querySelectorAll(){return[];},documentElement:{classList:{add(...items){items.forEach(x=>names.add(x));},remove(x){names.delete(x);}}}},window:{addEventListener(name,fn){events[name]=fn;}}});return{names,events};}
let guest=run('');assert(guest.names.has('ui-early')&&guest.names.has('facets-early'));assert(!guest.names.has('has-cookie-consent'));
assert(run('other=1; cookie_accepted=true; third=2').names.has('has-cookie-consent'));
assert(!run('not_cookie_accepted=true').names.has('has-cookie-consent'));
assert(!run('cookie_accepted=').names.has('has-cookie-consent'));
guest.events.error({target:{src:'https://its-center.ru/js/catalog-facets.js?v=1'}});assert(!guest.names.has('facets-early'),'failed enhancement must expose native form');
guest=run('');guest.events.error({filename:'https://its-center.ru/js/catalog-facets.js',target:{}});assert(!guest.names.has('facets-early'),'script errors also preserve native form');
assert(layout.includes('html.ui-early:not(.has-cookie-consent) #cookie-bar.cookie-bar'));
assert(layout.includes('html.facets-early .catalog-facets:not(.is-open) .catalog-facets__panel'));
console.log('Early UI: first-paint consent state, JS-only mobile panel, script-error native form fallback passed');
