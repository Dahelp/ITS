const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const input = {value:'Я'.repeat(65), attrs:{'data-title-limit':'50'}, events:{}, classList:{toggle(){}},
    getAttribute(k){return this.attrs[k];}, setAttribute(k,v){this.attrs[k]=v;},
    setCustomValidity(v){this.validation=v;}, addEventListener(k,fn){this.events[k]=fn;}};
const counter={classList:{toggle(){}}}, error={};
vm.runInNewContext(fs.readFileSync('public/js/avito-title-counter.js','utf8'),{document:{getElementById(id){return {'avito-title':input,'avito-title-count':counter,'avito-title-error':error}[id];}}});
assert.equal(counter.textContent,'65/50');
assert.equal(input.value.length,65,'The original name must remain intact');
assert.ok(input.validation);assert.equal(error.hidden,false);
input.value='Я'.repeat(49)+'🙂';input.events.input();
assert.equal(counter.textContent,'50/50');assert.equal(input.validation,'');assert.equal(error.hidden,true);
input.value+='a';input.events.input();
assert.equal(counter.textContent,'51/50');assert.ok(input.validation);
console.log('Title counter: initial overflow, editing, Unicode, validation and no truncation OK');
