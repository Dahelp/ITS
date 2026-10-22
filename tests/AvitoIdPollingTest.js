const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const requests = [], intervals = [], events = {};
const status = {dataset: {url:'/admin/avito/sync-identifiers'}, textContent:''};
let reloads = 0;
function $(arg) {
    if (typeof arg === 'function') return arg();
    return {DataTable: () => ({ajax: {reload: (callback, reset) => {assert.equal(reset,false); reloads++;}}})};
}
$.fn = {dataTable: {isDataTable: () => true}};
$.ajax = options => {
    assert.equal(options.url,'/admin/avito/sync-identifiers');
    const request = {done(fn){this.success=fn;return this;},fail(fn){this.failure=fn;return this;},always(fn){this.complete=fn;return this;}};
    requests.push(request);return request;
};
const document = {hidden:false,getElementById:()=>status,addEventListener:(name,fn)=>{events[name]=fn;}};
vm.runInNewContext(fs.readFileSync(require.resolve('../public/js/avito-id-sync.js'),'utf8'),{document,window:{jQuery:$,setInterval:(fn,ms)=>{assert.equal(ms,60000);intervals.push(fn);}}});
assert.equal(requests.length,1);
intervals[0]();assert.equal(requests.length,1,'No overlapping requests');
requests[0].success({assigned:1,pending:1,checked_at:10,errors:[]});requests[0].complete();
assert.equal(reloads,1);assert.match(status.textContent,/Ожидают.*1/);
intervals[0]();requests[1].success({assigned:1,pending:1,checked_at:10,errors:[]});requests[1].complete();
assert.equal(reloads,1,'Cached results do not repeatedly reload the table');
document.hidden=true;intervals[0]();assert.equal(requests.length,2,'Background tab makes no request');
document.hidden=false;events.visibilitychange();
requests[2].success({assigned:1,pending:1,checked_at:11,errors:['HTTP 429']});requests[2].complete();
assert.equal(reloads,2,'Partial successes still refresh IDs');assert.match(status.textContent,/429/);
intervals[0]();requests[3].failure();requests[3].complete();assert.match(status.textContent,/Не удалось/);
intervals[0]();requests[4].success({assigned:1,pending:0,checked_at:12,errors:[]});requests[4].complete();
assert.equal(reloads,3);assert.match(status.textContent,/Все номера/);
console.log('Avito ID polling: retry, visibility, locking, cached results, partial errors OK');
