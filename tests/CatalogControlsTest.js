const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const main=fs.readFileSync('public/js/main.js','utf8');
const start=main.indexOf('    function buildUrl(categoryAlias, mainAlias, extraIds)');
const end=main.indexOf('    function getSelectedFiltersFromUI()',start);
assert(start>=0&&end>start);
const build=vm.runInNewContext('('+main.slice(start,end).trim()+')',{URL,URLSearchParams,window:{location:{origin:'https://example.test',search:'?status=hit&sort=price_desc&in_stock=1&perpage=48&page=3'}}});
const url=new URL(build('tyres','6-50-10',[42,42,10]),'https://example.test');
assert.equal(url.pathname,'/category/tyres/6-50-10');
assert.equal(url.searchParams.get('filter'),'42,10');
for(const[key,value]of Object.entries({status:'hit',sort:'price_desc',in_stock:'1',perpage:'48'}))assert.equal(url.searchParams.get(key),value);
assert(!url.searchParams.has('page'));
const script=fs.readFileSync('public/js/catalog-controls.js','utf8');
function run(search,saved){let replaced='',submit;const options=['stock','price_asc','price_desc'].map(value=>({value}));const sort={value:'stock',options};const form={dataset:{},querySelector:()=>sort,addEventListener:(_e,fn)=>{submit=fn;}};const storage={getItem:()=>saved,setItem:(_k,value)=>{saved=value;}};vm.runInNewContext(script,{document:{querySelector:()=>form},URL,URLSearchParams,location:{search,href:'https://example.test/category/tyres'+search,replace:url=>{replaced=url;}},localStorage:storage});return{replaced,submit,sort,readSaved:()=>saved};}
let state=run('?status=hit&page=4','price_desc');assert(state.replaced.includes('status=hit'));assert(state.replaced.includes('sort=price_desc'));assert(!state.replaced.includes('page='));
state=run('?sort=price_asc','price_desc');assert.equal(state.replaced,'');state.sort.value='price_asc';state.submit();assert.equal(state.readSaved(),'price_asc');
console.log('Catalog filter URL preservation, page reset, stored sort and explicit URL priority OK');
