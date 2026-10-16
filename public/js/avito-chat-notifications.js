(() => {
    'use strict';
    const root = document.getElementById('avito-chat-notification');
    if (!root) return;
    const badge = document.getElementById('avito-chat-notification-count');
    const link = document.getElementById('avito-chat-notification-link');
    const banner = document.getElementById('avito-chat-notification-banner');
    let running = false, stopped = false;
    const originalTitle = document.title;
    function update(count, more) {
        const label = String(count) + (more ? '+' : '');
        badge.textContent = label; badge.hidden = count === 0;
        link.classList.toggle('avito-has-unread', count > 0);
        link.title = count ? 'Непрочитанные чаты Авито: ' + label : 'Чат Авито: нет непрочитанных';
        banner.textContent = '✉ Авито: непрочитанных чатов — ' + label + '. Открыть →';
        banner.hidden = count === 0;
        document.title = count ? '(' + label + ') Авито · ' + originalTitle : originalTitle;
    }
    async function poll() {
        if (running || stopped || document.hidden) return;
        running = true;
        try {
            const response = await fetch(root.dataset.endpoint, {credentials: 'same-origin', headers: {'Accept': 'application/json'}});
            if (response.status === 403 || response.status === 401 || response.redirected) {
                update(0, false); root.hidden = true; stopped = true; return;
            }
            const result = await response.json();
            if (!response.ok || !Number.isInteger(result.data?.count)) throw new Error('Unread unavailable');
            update(result.data.count, result.data.has_more);
        } catch (_) {
            link.title = 'Не удалось обновить уведомления Авито. Откройте чат для проверки подключения.';
            if (!banner.hidden) banner.textContent = '✉ Авито: есть непрочитанные чаты. Обновление недоступно — открыть →';
        } finally { running = false; }
    }
    document.addEventListener('visibilitychange', poll);
    window.addEventListener('avito-chat-read', poll);
    poll(); setInterval(poll, 30000);
})();
