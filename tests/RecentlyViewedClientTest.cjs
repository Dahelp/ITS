const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const script=fs.readFileSync('public/js/recently-viewed.js','utf8');
function run(id,cookie=''){
 const document={cookie,querySelector:()=>id===null?null:{dataset:{recentProductId:String(id)}}};
 vm.runInNewContext(script,{document,window:{location:{protocol:'https:'}}});return document.cookie;
}
assert.equal(run(null,'unchanged'),'unchanged');assert.match(run(42),/^recentlyViewed=42;/);
assert.match(run(42,'recentlyViewed=10.20'),/^recentlyViewed=10.20.42;/);
assert.equal(run(20,'recentlyViewed=10.20'),'recentlyViewed=10.20','existing product keeps ordering');
assert.match(run(42,'recentlyViewed=bad.1.1.-2'),/^recentlyViewed=1.42;/);assert.equal(run(0,'unchanged'),'unchanged');
const many=run(100,'recentlyViewed='+Array.from({length:50},(_,i)=>i+1).join('.'));
assert.equal(many.split(';')[0].split('=')[1].split('.').length,30);
assert(many.includes('Max-Age=86400')&&many.includes('SameSite=Lax')&&many.includes('Secure'));
console.log('Recently viewed: compatible cookie, append/dedup, invalid IDs, cap and optional cookies OK');
