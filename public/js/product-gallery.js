(function () {
  'use strict';
  function init(root) {
    if (root.dataset.pgReady) return;
    root.dataset.pgReady = '1';
    var photos = Array.from(root.querySelectorAll('[data-pg-photo]'));
    var thumbs = Array.from(root.querySelectorAll('[data-pg-thumb]'));
    if (!photos.length) return;
    var index = 0, scale = 1, x = 0, y = 0, opener, oldOverflow, imageSize;
    var pointers = new Map(), gesture, suppressClick = false;
    var dialog = document.createElement('dialog');
    dialog.className = 'pg-viewer';
    dialog.setAttribute('aria-label', 'Просмотр фотографий товара');
    dialog.innerHTML = '<div class="pg-viewer-inner"><div class="pg-viewer-toolbar">' +
      '<p class="pg-viewer-title"></p><button type="button" data-pg-close aria-label="Закрыть просмотр">×</button>' +
      '<button type="button" data-pg-minus aria-label="Уменьшить">−</button><button type="button" data-pg-reset aria-label="Сбросить увеличение">100%</button><button type="button" data-pg-plus aria-label="Увеличить">+</button></div>' +
      '<div class="pg-viewport"><img class="pg-zoom-image" alt="" draggable="false">' +
      '<button type="button" class="pg-viewer-nav pg-viewer-nav--prev" data-pg-prev aria-label="Предыдущее фото">‹</button>' +
      '<button type="button" class="pg-viewer-nav pg-viewer-nav--next" data-pg-next aria-label="Следующее фото">›</button>' +
      '<span class="pg-viewer-status" role="status"></span></div><div class="pg-viewer-footer"><span>Масштаб: + / − · Перемещение: потяните фото</span><span data-pg-counter aria-live="polite"></span></div></div>';
    document.body.appendChild(dialog);
    var viewport = dialog.querySelector('.pg-viewport'), image = dialog.querySelector('img');
    var status = dialog.querySelector('.pg-viewer-status');
    function isOpen() { return dialog.open; }
    function paint() {
      if (imageSize) {
        var boundX = Math.max(0, (imageSize.width * scale - viewport.clientWidth) / 2);
        var boundY = Math.max(0, (imageSize.height * scale - viewport.clientHeight) / 2);
        x = Math.max(-boundX, Math.min(boundX, x)); y = Math.max(-boundY, Math.min(boundY, y));
      }
      image.style.transform = 'translate(' + x + 'px,' + y + 'px) scale(' + scale + ')';
      viewport.classList.toggle('is-zoomed', scale > 1);
      dialog.querySelector('[data-pg-reset]').textContent = Math.round(scale * 100) + '%';
      dialog.querySelector('[data-pg-minus]').disabled = scale <= 1;
      dialog.querySelector('[data-pg-plus]').disabled = scale >= 2;
    }
    function zoom(value) { scale = Math.max(1, Math.min(2, value)); if (scale === 1) { x = 0; y = 0; } paint(); }
    function measure() { imageSize = { width: image.clientWidth, height: image.clientHeight }; paint(); }
    function select(next) {
      index = (next + photos.length) % photos.length;
      photos.forEach(function (photo, i) { photo.hidden = i !== index; });
      thumbs.forEach(function (thumb, i) { thumb.setAttribute('aria-pressed', String(i === index)); });
      root.querySelector('[data-pg-count]').textContent = (index + 1) + ' / ' + photos.length;
      if (thumbs[index]) {
        var strip = thumbs[index].parentElement;
        var left = thumbs[index].offsetLeft - strip.offsetLeft;
        if (left < strip.scrollLeft) strip.scrollLeft = left;
        else if (left + thumbs[index].offsetWidth > strip.scrollLeft + strip.clientWidth) strip.scrollLeft = left + thumbs[index].offsetWidth - strip.clientWidth;
      }
      if (isOpen()) {
        scale = 1; x = 0; y = 0; imageSize = null; paint();
        var source = photos[index].querySelector('img');
        image.alt = source ? source.alt : 'Фото товара';
        dialog.querySelector('.pg-viewer-title').textContent = image.alt;
        dialog.querySelector('[data-pg-counter]').textContent = (index + 1) + ' / ' + photos.length;
        status.hidden = false; status.textContent = 'Загрузка фото…';
        image.style.visibility = 'hidden'; image.src = photos[index].href;
      }
    }
    image.addEventListener('load', function () { status.hidden = true; image.style.visibility = ''; measure(); });
    image.addEventListener('error', function () { status.hidden = false; status.textContent = 'Не удалось загрузить фото. Выберите другое изображение.'; });
    function open(photo) {
      if (typeof dialog.showModal !== 'function') return false;
      opener = photo; oldOverflow = document.documentElement.style.overflow;
      dialog.showModal(); document.documentElement.style.overflow = 'hidden';
      select(Number(photo.dataset.pgIndex)); dialog.querySelector('[data-pg-close]').focus(); return true;
    }
    function close() { dialog.close(); }
    dialog.addEventListener('close', function () {
      document.documentElement.style.overflow = oldOverflow || '';
      pointers.clear(); gesture = null; viewport.classList.remove('is-dragging');
      if (opener) photos[index].focus({ preventScroll: true });
    });
    photos.forEach(function (photo) { photo.addEventListener('click', function (event) {
      if (suppressClick) { event.preventDefault(); return; }
      if (!event.ctrlKey && !event.metaKey && !event.shiftKey && open(photo)) event.preventDefault();
    }); });
    thumbs.forEach(function (thumb, i) { thumb.addEventListener('click', function () { select(i); }); });
    [root, dialog].forEach(function (container) {
      container.querySelectorAll('[data-pg-prev]').forEach(function (button) { button.hidden = photos.length < 2; button.addEventListener('click', function () { select(index - 1); }); });
      container.querySelectorAll('[data-pg-next]').forEach(function (button) { button.hidden = photos.length < 2; button.addEventListener('click', function () { select(index + 1); }); });
    });
    dialog.querySelector('[data-pg-close]').addEventListener('click', close);
    dialog.querySelector('[data-pg-plus]').addEventListener('click', function () { zoom(scale + .25); });
    dialog.querySelector('[data-pg-minus]').addEventListener('click', function () { zoom(scale - .25); });
    dialog.querySelector('[data-pg-reset]').addEventListener('click', function () { zoom(1); });
    dialog.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') { event.preventDefault(); select(index + (event.key === 'ArrowRight' ? 1 : -1)); }
      if (event.key === '+' || event.key === '=') { event.preventDefault(); zoom(scale + .25); }
      if (event.key === '-') { event.preventDefault(); zoom(scale - .25); }
      if (event.key === '0') { event.preventDefault(); zoom(1); }
    });
    viewport.addEventListener('dblclick', function (event) { if (!event.target.closest('button')) zoom(scale > 1 ? 1 : 2); });
    viewport.addEventListener('wheel', function (event) { event.preventDefault(); zoom(scale + (event.deltaY < 0 ? .1 : -.1)); }, { passive: false });
    function distance() { var p = Array.from(pointers.values()); return Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y); }
    viewport.addEventListener('pointerdown', function (event) {
      if (event.target.closest('button') || event.button > 0) return;
      viewport.setPointerCapture(event.pointerId); pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
      if (pointers.size === 1) gesture = { startX: event.clientX, startY: event.clientY, x: x, y: y, moved: false, zoomed: scale > 1 };
      if (pointers.size === 2) { gesture.pinch = distance(); gesture.scale = scale; gesture.zoomed = true; }
      viewport.classList.add('is-dragging');
    });
    viewport.addEventListener('pointermove', function (event) {
      if (!pointers.has(event.pointerId) || !gesture) return;
      pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
      var dx = event.clientX - gesture.startX, dy = event.clientY - gesture.startY;
      if (Math.abs(dx) + Math.abs(dy) > 8) gesture.moved = true;
      if (pointers.size === 2 && gesture.pinch) zoom(gesture.scale * distance() / Math.max(1, gesture.pinch));
      else if (scale > 1) { x = gesture.x + dx; y = gesture.y + dy; paint(); }
    });
    function finish(event) {
      if (!pointers.has(event.pointerId)) return;
      pointers.delete(event.pointerId);
      if (!pointers.size && gesture) {
        var dx = event.clientX - gesture.startX, dy = event.clientY - gesture.startY;
        if (event.type !== 'pointercancel' && !gesture.zoomed && Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.3) select(index + (dx < 0 ? 1 : -1));
        gesture = null; viewport.classList.remove('is-dragging');
      } else if (pointers.size === 1 && gesture) {
        var remaining = Array.from(pointers.values())[0]; gesture = { startX: remaining.x, startY: remaining.y, x: x, y: y, zoomed: true };
      }
    }
    viewport.addEventListener('pointerup', finish); viewport.addEventListener('pointercancel', finish);
    // Horizontal swipes on the card preserve normal vertical page scrolling.
    var start;
    root.querySelector('.pg-stage').addEventListener('touchstart', function (event) {
      if (event.touches.length === 1) start = { x: event.touches[0].clientX, y: event.touches[0].clientY };
    }, { passive: true });
    root.querySelector('.pg-stage').addEventListener('touchend', function (event) {
      if (!start || !event.changedTouches.length) return;
      var dx = event.changedTouches[0].clientX - start.x, dy = event.changedTouches[0].clientY - start.y;
      start = null;
      if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.3) {
        suppressClick = true; select(index + (dx < 0 ? 1 : -1)); setTimeout(function () { suppressClick = false; }, 350);
      }
    }, { passive: true });
    window.addEventListener('resize', function () { if (isOpen()) measure(); });
  }
  function ready() { document.querySelectorAll('[data-product-gallery]').forEach(init); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
})();
