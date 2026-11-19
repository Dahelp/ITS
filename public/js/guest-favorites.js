/* Guest product IDs stay on this device until the customer chooses to save them. */
(function () {
    'use strict';
    var key = 'its-center.favorites.v1', pendingKey = key + '.save';
    var link, guest = true, ids = [], loading = 0;
    function normalize(value) {
        return Array.isArray(value) ? value.map(Number).filter(function (id, i, all) {
            return Number.isSafeInteger(id) && id > 0 && all.indexOf(id) === i;
        }).slice(0, 500) : [];
    }
    function read() { try { return normalize(JSON.parse(localStorage.getItem(key) || '[]')); } catch (e) { return []; } }
    function write(next) {
        try { localStorage.setItem(key, JSON.stringify(next)); ids = next; return true; }
        catch (e) { alert('Браузер не разрешает сохранить избранное. Разрешите локальное хранилище и повторите попытку.'); return false; }
    }
    function status(text) { $('#favorites-status').text(text); }
    var noticeTimer;
    function notify(text) {
        var notice = document.getElementById('favorites-notice');
        if (!notice) {
            notice = document.createElement('div');
            notice.id = 'favorites-notice';
            notice.className = 'alert alert-light border shadow';
            notice.setAttribute('role', 'status');
            notice.setAttribute('aria-live', 'polite');
            notice.style.cssText = 'position:fixed;bottom:24px;right:16px;max-width:calc(100vw - 32px);z-index:1090';
            document.body.appendChild(notice);
        }
        notice.textContent = text;
        notice.hidden = false;
        clearTimeout(noticeTimer);
        noticeTimer = setTimeout(function () { notice.hidden = true; }, 5000);
    }
    function paint() {
        if (!guest) return;
        $('#wishlist_kol').text(ids.length);
        $('.js-wishlist, .btn-wishlist, .btn-wishlist2').each(function () {
            var active = ids.indexOf(Number(this.dataset.id)) !== -1;
            $(this).toggleClass('is-active', active).toggleClass('btn-wishlist2', active)
                .attr('aria-pressed', String(active)).attr('title', active ? 'В избранном' : 'Добавить в избранное');
            $(this).find('i').removeClass('fas far').addClass(active ? 'fas' : 'far');
        });
    }
    function refresh() {
        paint();
        $('#favorites-save').prop('hidden', ids.length === 0);
        if (!$('#favorites-page').length) return;
        var request = ++loading;
        $.ajax({url: (window.APP_PATH || '') + '/favorites/items', type: 'POST', dataType: 'json', data: {ids: ids}})
            .done(function (res) {
                if (request !== loading || !res.ok) return;
                $('#favorites-items').html(res.html);
                status(res.ids.length ? '' : 'В избранном пока нет товаров. Нажмите на сердечко в каталоге.');
                if (guest && JSON.stringify(ids) !== JSON.stringify(normalize(res.ids))) write(normalize(res.ids));
                paint();
                $('#favorites-save').prop('hidden', ids.length === 0);
            }).fail(function () { if (request === loading) status('Не удалось загрузить избранное. Обновите страницу.'); });
    }
    function merge() {
        $('#favorites-save').prop('disabled', true);
        $.ajax({url: (window.APP_PATH || '') + '/favorites/merge', type: 'POST', dataType: 'json',
            data: {ids: ids, csrf: link.dataset.favoritesToken}}).done(function (res) {
            if (!res.ok) return;
            if (write([])) { try { localStorage.removeItem(pendingKey); } catch (e) {} }
            $('#wishlist_kol').text(res.count);
            if ($('#favorites-page').length) { refresh(); status('Товары сохранены в аккаунт.'); }
            else window.location.assign((window.APP_PATH || '') + '/favorites/');
        }).fail(function () {
            status('Не удалось сохранить товары в аккаунт. Повторите попытку.');
            if (!$('#favorites-page').length) alert('Не удалось сохранить избранное в аккаунт. Повторите на странице избранного.');
        }).always(function () { $('#favorites-save').prop('disabled', false); });
    }
    window.GuestFavorites = {
        isGuest: function () { return guest; },
        refresh: refresh,
        toggle: function (id) {
            var next = read(), index = next.indexOf(id);
            if (index < 0 && next.length >= 500) { alert('Можно сохранить до 500 товаров.'); return; }
            if (index < 0) next.push(id); else next.splice(index, 1);
            if (write(next)) { refresh(); notify(index < 0 ? 'Добавлено в избранное. Войдите, чтобы сохранить навсегда.' : 'Товар удалён из избранного.'); }
        }
    };
    $(function () {
        link = document.querySelector('[data-favorites-user]');
        if (!link) return;
        guest = Number(link.dataset.favoritesUser) === 0;
        ids = read();
        $('#favorites-guest-note').prop('hidden', !guest);
        $('#favorites-save').on('click', function () {
            if (guest) {
                try { localStorage.setItem(pendingKey, '1'); } catch (e) { status('Разрешите локальное хранилище и повторите попытку.'); return; }
                window.location.assign((window.APP_PATH || '') + '/user/login');
            } else merge();
        });
        refresh();
        try { if (!guest && ids.length && localStorage.getItem(pendingKey) === '1') merge(); } catch (e) {}
        window.addEventListener('storage', function (event) { if (event.key === key || event.key === null) { ids = read(); refresh(); } });
        // Catalog and cart fragments can be inserted after initial page load.
        new MutationObserver(function (mutations) {
            if (guest && mutations.some(function (m) { return Array.from(m.addedNodes).some(function (n) {
                return n.nodeType === 1 && (n.matches('.js-wishlist, .btn-wishlist, .btn-wishlist2') || n.querySelector('.js-wishlist, .btn-wishlist, .btn-wishlist2'));
            }); })) paint();
        }).observe(document.body, {childList: true, subtree: true});
    });
})();
