const assert=require('node:assert/strict');
const fs=require('node:fs'),vm=require('node:vm');
const code=fs.readFileSync('public/js/traffic-source.js','utf8');
function run(search='',referrer='',cookie=''){
  const document={cookie,referrer};
  const location={search,pathname:'/first',protocol:'https:'};
  vm.runInNewContext(code,{document,window:{location},URL,URLSearchParams});
  return document.cookie;
}
function data(cookie){return JSON.parse(decodeURIComponent(cookie.split(';')[0].split('=').slice(1).join('=')));}
assert.equal(data(run('?yclid=123')).code,'yandex_direct');
assert.equal(data(run('?utm_source=yandex&utm_medium=cpc')).code,'yandex_direct');
assert.equal(data(run('','https://www.google.com/search?q=test')).code,'search');
assert.equal(data(run()).code,'direct_visit');
const first=run('?yclid=123');
assert.equal(run('','',first),first,'navigation preserves first touch');
assert.equal(data(run('','','its_traffic_source=broken')).code,'direct_visit');
assert.equal(data(run('?secret=hidden','https://google.com/search?q=hidden')).landing_url,'/first');
assert(!run('?secret=hidden','https://google.com/search?q=hidden').includes('hidden'),'query data omitted');
assert(first.includes('SameSite=Lax') && first.includes('Secure') && !first.includes('Max-Age'),'browser-session cookie');
console.log('Traffic source client: direct/search/ads, first touch, malformed cookie and query minimisation OK');
