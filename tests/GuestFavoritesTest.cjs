const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../public/js/guest-favorites.js'), 'utf8');
function boot(store = new Map(), user = 0) {
    const ready = [], values = {}, requests = [], listeners = {}, alerts = [];
    const link = {dataset: {favoritesUser: String(user), favoritesToken: 'csrf-test'}};
    const notice = {};
    const document = {querySelector: () => link, getElementById: () => notice, body: {}};
    function $(selector) {
        if (typeof selector === 'function') { ready.push(selector); return; }
        const chain = {length: 0, each() { return chain; }, prop(name, value) { values[selector + ':' + name] = value; return chain; },
            text(value) { values[selector] = value; return chain; }, on() { return chain; }};
        return chain;
    }
    $.ajax = options => {
        requests.push(options);
        const response = {done(fn) { if (options.url.endsWith('/merge')) fn({ok: true, count: 9}); return response; },
            fail() { return response; }, always(fn) { fn(); return response; }};
        return response;
    };
    const context = {window: {APP_PATH: '', addEventListener: (name, fn) => { listeners[name] = fn; }, location: {assign(url) { values.redirect = url; }}},
        document, $, localStorage: {getItem: key => store.get(key) || null, setItem(key, value) {store.set(key, value);}, removeItem: key => store.delete(key)},
        alert: message => alerts.push(message), MutationObserver: class { observe() {} }, setTimeout: () => 1, clearTimeout() {}};
    vm.runInNewContext(source, context);
    ready.forEach(fn => fn());
    return {api: context.window.GuestFavorites, values, context, requests, listeners, alerts};
}
const key = 'its-center.favorites.v1', store = new Map();
let app = boot(store);
assert.equal(app.api.isGuest(), true);
app.api.toggle(42);
assert.equal(store.get(key), '[42]');
assert.equal(app.values['#wishlist_kol'], 1);
app = boot(store);
assert.equal(app.values['#wishlist_kol'], 1, 'survives reload');
app.api.toggle(42);
assert.equal(store.get(key), '[]');
store.set(key, '[42,42,-1,"17",null,1.5]');
app = boot(store);
assert.equal(app.values['#wishlist_kol'], 2, 'deduplicates and validates IDs');
store.set(key, '[88]'); app.listeners.storage({key});
assert.equal(app.values['#wishlist_kol'], 1, 'syncs other tabs');
app.context.localStorage.setItem = () => { throw Error('blocked'); };
app.api.toggle(99);
assert.equal(store.get(key), '[88]', 'failed storage does not claim persistence');
assert.equal(app.alerts.length, 1);
store.set(key, 'invalid json');
assert.equal(boot(store).values['#wishlist_kol'], 0);
store.set(key, '[12,13]'); store.set(key + '.save', '1');
app = boot(store, 7);
assert.equal(app.api.isGuest(), false);
assert.equal(app.requests[0].url, '/favorites/merge');
assert.equal(app.requests[0].type, 'POST');
assert.equal(app.requests[0].data.csrf, 'csrf-test');
assert.equal(JSON.stringify(app.requests[0].data.ids), '[12,13]');
assert.equal(store.get(key), '[]');
assert.equal(store.has(key + '.save'), false);
assert.equal(app.values['#wishlist_kol'], 9);
assert.equal(app.values.redirect, '/favorites/');
store.set(key, '[16]');
app = boot(store, 7);
assert.equal(app.requests.length, 0, 'account merge requires explicit save intent');
console.log('Guest favorites: persistence, removal, validation, cross-tab sync, storage failure and account merge passed');
