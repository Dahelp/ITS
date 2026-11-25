/* First-touch analytics metadata; never used for identity or permissions. */
(function () {
  'use strict';
  var name = 'its_traffic_source';
  var codes = ['search', 'yandex_direct', 'direct_visit'];
  try {
    var existing = document.cookie.split(';').map(function (part) { return part.trim(); })
      .find(function (part) { return part.indexOf(name + '=') === 0; });
    if (existing) {
      try { if (codes.indexOf(JSON.parse(decodeURIComponent(existing.slice(name.length + 1))).code) !== -1) return; } catch (e) {}
    }
    var query = new URLSearchParams(window.location.search);
    function value(key) { return (query.get(key) || '').trim().toLowerCase(); }
    var source = value('utm_source'), medium = value('utm_medium'), code = 'direct_visit';
    var host = '';
    try { host = new URL(document.referrer).hostname.toLowerCase(); } catch (e) {}
    if (['yandex.', 'google.', 'bing.com', 'mail.ru', 'rambler.ru'].some(function (part) { return host.indexOf(part) !== -1; }) || medium === 'organic') code = 'search';
    if (value('yclid') || ['yandex_direct', 'yadirect', 'direct', 'geoadv_yabs'].indexOf(source) !== -1
      || (source === 'yandex' && ['cpc', 'ppc', 'paid', 'paid_search', 'context', 'display', 'cpm'].indexOf(medium) !== -1)
      || value('utm_referer') === 'yandex_direct') code = 'yandex_direct';
    var data = {code: code, landing_url: window.location.pathname.slice(0, 300), referer: host.slice(0, 100), captured_at: new Date().toISOString()};
    // A session cookie, below common size limits; no complete referrer/query.
    document.cookie = name + '=' + encodeURIComponent(JSON.stringify(data)) + '; Path=/; SameSite=Lax' + (window.location.protocol === 'https:' ? '; Secure' : '');
  } catch (e) { /* Cookie-disabled visitors can still submit forms. */ }
}());
