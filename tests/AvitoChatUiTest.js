const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const {formatChatDate, fullDate, chatStatus, safeMediaUrl} = require('../public/js/avito-chat-ui.js');
const unix = iso => Date.parse(iso) / 1000;
assert.equal(safeMediaUrl('https://media.example.test/a.mp4'), 'https://media.example.test/a.mp4');
for (const url of ['javascript:alert(1)', 'http://media.example.test/a', 'https://user:pass@media.example.test/a', undefined]) assert.equal(safeMediaUrl(url), '');
assert.equal(chatStatus({is_unread: true, last_message: {direction: 'in'}}).kind, 'unread');
assert.equal(chatStatus({is_unread: false, last_message: {direction: 'in'}}), null, 'Missing read field must not label an incoming chat unread');
assert.equal(chatStatus({last_message: {direction: 'out', read: 1792183320}}).kind, 'read');
assert.equal(chatStatus({last_message: {direction: 'out'}}).kind, 'sent');
const now = new Date('2026-10-16T22:00:00Z'); // 17 October, 01:00 Moscow.
assert.equal(formatChatDate(unix('2026-10-16T21:05:00Z'), now), '00:05');
assert.equal(formatChatDate(unix('2026-10-16T20:59:00Z'), now), '16.10.2026');
assert.equal(formatChatDate(unix('2025-10-17T12:00:00Z'), now), '17.10.2025');
assert.equal(formatChatDate(undefined, now), '');
assert.equal(formatChatDate('invalid', now), '');
assert.match(fullDate(unix('2026-10-16T21:05:00Z')), /17\.10\.2026.*00:05:00.*МСК/);

async function testNotifications() {
    const nodes = new Map();
    const makeNode = id => ({dataset: {endpoint: '/admin/avito-chat/api?operation=unread'}, hidden: false, textContent: '', title: '', classList: {toggle() {}}});
    for (const id of ['avito-chat-notification', 'avito-chat-notification-count', 'avito-chat-notification-link', 'avito-chat-notification-banner']) nodes.set(id, makeNode(id));
    const docEvents = {}, winEvents = {}, intervals = [];
    const document = {title: 'Товары', hidden: false, getElementById: id => nodes.get(id), addEventListener: (name, fn) => { (docEvents[name] ||= []).push(fn); }};
    let eventRevision = 1;
    let response = {ok: true, status: 200, json: async () => ({data: {count: 3, has_more: false}})}, calls = 0;
    const context = {
        document,
        URL, location: {href:'https://its-center.ru/admin'}, Event,
        window: {addEventListener: (name, fn) => { winEvents[name] = fn; }, dispatchEvent: event => winEvents[event.type]?.()},
        fetch: async (url, options) => {
            if (String(url).includes('operation=events')) return {ok:true,status:200,json:async () => ({data:{registered:true,revision:eventRevision}})};
            calls++;
            assert.equal(url, '/admin/avito-chat/api?operation=unread');
            assert.equal(options.credentials, 'same-origin');
            assert.ok(!options.method || options.method === 'GET', 'Notification does not mark messages read');
            return response;
        },
        setInterval: (fn, delay) => { intervals.push(fn); assert.ok([30000,5000].includes(delay)); },
    };
    vm.runInNewContext(fs.readFileSync(require.resolve('../public/js/avito-chat-notifications.js'), 'utf8'), context);
    await new Promise(setImmediate);
    assert.equal(nodes.get('avito-chat-notification-banner').hidden, false);
    assert.equal(nodes.get('avito-chat-notification-count').textContent, '3');
    assert.match(document.title, /^\(3\) Авито/);
    eventRevision++;
    const beforeWebhook = calls;
    await intervals[1](); await new Promise(setImmediate);
    assert.equal(calls, beforeWebhook+1, 'Webhook revision triggers immediate unread refresh');
    await intervals[1](); await new Promise(setImmediate);
    assert.equal(calls, beforeWebhook+1, 'Unchanged local revision does not query Avito');
    response = {ok: true, status: 200, json: async () => ({data: {count: 0, has_more: false}})};
    await winEvents['avito-chat-read']();
    assert.equal(nodes.get('avito-chat-notification-banner').hidden, true);
    assert.equal(document.title, 'Товары');
    document.hidden = true;
    const before = calls;
    await intervals[0]();
    assert.equal(calls, before, 'No polling in hidden tabs');
    document.hidden = false;
    response = {ok: true, status: 200, json: async () => ({data: {count: 50, has_more: true}})};
    for (const callback of docEvents.visibilitychange) await callback();
    assert.equal(nodes.get('avito-chat-notification-count').textContent, '50+');
    response = {ok: false, status: 403};
    await intervals[0]();
    assert.equal(nodes.get('avito-chat-notification').hidden, true);
    assert.equal(nodes.get('avito-chat-notification-banner').hidden, true);
    assert.equal(document.title, 'Товары');
    const stoppedCalls = calls;
    await intervals[0]();
    assert.equal(calls, stoppedCalls, 'Revoked access stops polling');
}
testNotifications().then(() => console.log('AvitoChatUi: OK')).catch(error => { console.error(error); process.exitCode = 1; });
