(function (scope) {
    'use strict';
    const zone = 'Europe/Moscow';
    const day = new Intl.DateTimeFormat('en-CA', {timeZone: zone, year: 'numeric', month: '2-digit', day: '2-digit'});
    function date(value) {
        const result = new Date(Number(value) * 1000);
        return Number(value) > 0 && !Number.isNaN(result.getTime()) ? result : null;
    }
    function formatChatDate(value, now = new Date()) {
        const result = date(value);
        if (!result) return '';
        if (day.format(result) === day.format(now)) {
            return result.toLocaleTimeString('ru-RU', {timeZone: zone, hour: '2-digit', minute: '2-digit'});
        }
        return result.toLocaleDateString('ru-RU', {timeZone: zone});
    }
    function fullDate(value) {
        const result = date(value);
        return result ? result.toLocaleString('ru-RU', {timeZone: zone}) + ' (МСК)' : '';
    }
    function chatStatus(chat) {
        if (chat.is_unread === true) return {kind: 'unread', label: 'Непрочитанный чат', symbol: '● Новое'};
        const last = chat.last_message || {};
        if (last.direction !== 'out') return null;
        if (Number(last.read) > 0 || last.isRead === true) return {kind: 'read', label: 'Прочитано собеседником', symbol: '✓✓'};
        return {kind: 'sent', label: 'Отправлено', symbol: '✓'};
    }
    const api = {formatChatDate, fullDate, chatStatus};
    if (typeof module !== 'undefined' && module.exports) module.exports = api;
    else scope.AvitoChatUI = api;
})(globalThis);
