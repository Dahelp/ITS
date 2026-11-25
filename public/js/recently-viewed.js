/* Keep the existing server-readable history format, without Set-Cookie on GET. */
(function () {
  'use strict';
  var product = document.querySelector('[data-recent-product-id]');
  if (!product) return;
  var id = Number(product.dataset.recentProductId);
  if (!Number.isSafeInteger(id) || id <= 0) return;
  try {
    var cookie = document.cookie.split(';').map(function (part) { return part.trim(); })
      .find(function (part) { return part.indexOf('recentlyViewed=') === 0; });
    var ids = cookie ? cookie.slice('recentlyViewed='.length).split('.').filter(function (value, i, all) {
      return /^\d+$/.test(value) && Number.isSafeInteger(Number(value)) && Number(value) > 0 && all.indexOf(value) === i;
    }) : [];
    if (ids.indexOf(String(id)) !== -1) return;
    ids.push(String(id));
    document.cookie = 'recentlyViewed=' + ids.slice(-30).join('.') + '; Max-Age=86400; Path=/; SameSite=Lax' + (window.location.protocol === 'https:' ? '; Secure' : '');
  } catch (e) { /* History is optional when the browser disallows cookies. */ }
}());
