(() => {
    'use strict';
    const root = document.getElementById('avito-chat');
    if (!root) return;
    const el = id => document.getElementById(id);
    const list = el('avito-chat-list'), messages = el('avito-chat-messages');
    const text = el('avito-chat-text'), submit = el('avito-chat-submit');
    let selected = '', chatOffset = 0, messageOffset = 0, busy = false, refreshing = false;
    let chatVersion = 0, messageVersion = 0, history = new Map();
    const drafts = new Map();
    const {formatChatDate, fullDate} = window.AvitoChatUI;
    el('avito-chat-unread').checked = new URLSearchParams(location.search).get('unread') === '1';
    function error(message) {
        el('avito-chat-error').textContent = message || '';
        el('avito-chat-error').hidden = !message;
    }
    async function api(operation, values = {}, post = false) {
        const url = new URL(root.dataset.endpoint, location.href);
        url.searchParams.set('operation', operation);
        const options = {credentials: 'same-origin', headers: {'Accept': 'application/json'}};
        if (post) {
            options.method = 'POST';
            options.body = new URLSearchParams({...values, csrf: root.dataset.csrf});
        } else Object.entries(values).forEach(([key, value]) => url.searchParams.set(key, value));
        const response = await fetch(url, options);
        let body;
        try { body = await response.json(); }
        catch (_) { throw new Error('Не удалось получить ответ. Проверьте вход в админку.'); }
        if (!response.ok || body.error) throw new Error(body.error || 'Ошибка запроса.');
        return body.data;
    }
    function safeUrl(value) {
        try { const url = new URL(value); return url.protocol === 'https:' ? url.href : ''; }
        catch (_) { return ''; }
    }
    function renderMessages() {
        const nearBottom = messages.scrollHeight - messages.scrollTop - messages.clientHeight < 60;
        const previousHeight = messages.scrollHeight, previousTop = messages.scrollTop;
        messages.replaceChildren();
        [...history.values()].sort((a, b) => Number(a.created) - Number(b.created)).forEach(message => {
            const row = document.createElement('div');
            row.className = 'p-2 mb-2 rounded ' + (message.direction === 'out' ? 'bg-light text-right' : 'border');
            row.style.whiteSpace = 'pre-wrap';
            const content = message.content || {};
            const body = document.createElement('div');
            body.textContent = content.text || content.link?.text || content.item?.title || content.location?.text ||
                ({image: 'Изображение', voice: 'Голосовое сообщение', call: 'Звонок', deleted: 'Сообщение удалено'}[message.type]) || 'Сообщение: ' + (message.type || 'неизвестный тип');
            row.append(body);
            const sizes = content.image?.sizes || {};
            const imageUrl = safeUrl(sizes['640x480'] || sizes['1280x960'] || Object.values(sizes)[0]);
            if (imageUrl) {
                const img = document.createElement('img'); img.src = imageUrl; img.alt = 'Изображение из сообщения';
                img.style.maxWidth = '100%'; img.style.maxHeight = '300px'; img.loading = 'lazy'; row.append(img);
            }
            const linkUrl = safeUrl(content.link?.url || content.item?.item_url);
            if (linkUrl) {
                const link = document.createElement('a'); link.href = linkUrl; link.target = '_blank'; link.rel = 'noopener noreferrer';
                link.textContent = 'Открыть ссылку'; row.append(link);
            }
            const time = document.createElement('small'); time.className = 'text-muted d-block';
            time.textContent = formatChatDate(message.created); time.title = fullDate(message.created); row.append(time);
            messages.append(row);
        });
        if (nearBottom) messages.scrollTop = messages.scrollHeight;
        else messages.scrollTop = previousTop + Math.max(0, messages.scrollHeight - previousHeight);
    }
    async function loadMessages(older = false) {
        if (!selected) return;
        const chat = selected, version = ++messageVersion;
        const offset = older ? messageOffset : 0;
        const data = await api('messages', {chat, offset});
        if (chat !== selected || version !== messageVersion) return;
        const rows = Array.isArray(data) ? data : (data.messages || []);
        rows.forEach(row => history.set(row.id, row));
        if (older || !messageOffset) messageOffset = offset + rows.length;
        if (older || offset === 0 && messageOffset <= 100) el('avito-messages-more').hidden = rows.length < 100;
        renderMessages();
        // Read receipts are sent only while the conversation is visible.
        if (!document.hidden && !older) {
            await api('read', {chat}, true);
            window.dispatchEvent(new Event('avito-chat-read'));
        }
    }
    async function selectChat(chat, title) {
        if (busy) return;
        if (selected) drafts.set(selected, text.value);
        selected = chat; ++messageVersion; history = new Map(); messageOffset = 0;
        list.querySelectorAll('[data-chat-id]').forEach(button => button.classList.toggle('avito-chat-selected', button.dataset.chatId === selected));
        text.value = drafts.get(chat) || '';
        messages.replaceChildren(); el('avito-chat-title').textContent = title;
        el('avito-messages-more').hidden = true; text.disabled = true; submit.disabled = true;
        try {
            await loadMessages();
            if (selected === chat) { text.disabled = false; submit.disabled = busy; error(''); }
        } catch (e) { error(e.message); }
    }
    async function loadChats(more = false) {
        const version = ++chatVersion, offset = more ? chatOffset : 0;
        const data = await api('chats', {offset, unread: el('avito-chat-unread').checked ? 1 : 0});
        if (version !== chatVersion) return;
        const rows = data.chats || [];
        if (!more) list.replaceChildren();
        rows.forEach(chat => {
            const button = document.createElement('button'); button.type = 'button'; button.className = 'list-group-item list-group-item-action';
            button.dataset.chatId = chat.id;
            button.classList.toggle('avito-chat-selected', chat.id === selected);
            const title = chat.context?.value?.title || 'Диалог ' + chat.id;
            const heading = document.createElement('span'); heading.className = 'avito-chat-heading';
            const label = document.createElement('span'); label.className = 'avito-chat-label'; label.textContent = title;
            const time = document.createElement('time'), timestamp = chat.last_message?.created || chat.updated || chat.created;
            time.className = 'avito-chat-date'; time.textContent = formatChatDate(timestamp); time.title = fullDate(timestamp);
            if (time.textContent) time.dateTime = new Date(Number(timestamp) * 1000).toISOString();
            heading.append(label, time); button.append(heading);
            const preview = document.createElement('span'); preview.className = 'avito-chat-preview';
            preview.textContent = chat.last_message?.content?.text || ({image: 'Изображение', voice: 'Голосовое сообщение'}[chat.last_message?.type]) || '';
            button.append(preview);
            button.addEventListener('click', () => selectChat(chat.id, title)); list.append(button);
        });
        if (!list.children.length) list.textContent = 'Диалогов нет.';
        chatOffset = offset + rows.length; el('avito-chat-more').hidden = rows.length < 50;
    }
    async function refresh() {
        if (refreshing || document.hidden || busy) return;
        refreshing = true;
        try { await loadChats(); if (selected) await loadMessages(); error(''); }
        catch (e) { error(e.message); }
        finally { refreshing = false; }
    }
    el('avito-chat-send').addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || !selected || !text.value.trim()) return;
        const chat = selected, draft = text.value;
        busy = true; submit.disabled = true; text.disabled = true;
        try {
            await api('send', {chat, text: draft}, true);
            text.value = ''; error('');
            drafts.delete(chat);
            try { if (selected === chat) await loadMessages(); }
            catch (_) { error('Сообщение отправлено, но обновить историю не удалось. Нажмите «Обновить».'); }
        } catch (e) {
            error(e.message + ' При обрыве связи проверьте историю перед повторной отправкой.');
        } finally { busy = false; submit.disabled = !selected; text.disabled = !selected; }
    });
    el('avito-chat-refresh').addEventListener('click', refresh);
    el('avito-chat-check').addEventListener('click', async event => {
        const button = event.currentTarget, status = el('avito-chat-check-result');
        button.disabled = true; status.textContent = 'Проверяем аккаунт, диалоги и историю…';
        try {
            const result = await api('check');
            status.textContent = 'Подключение работает. Диалоги доступны.' +
                (result.history ? ' Чтение истории проверено.' : ' Диалогов пока нет, история не проверена.');
        } catch (e) { status.textContent = e.message; }
        finally { button.disabled = false; }
    });
    el('avito-chat-unread').addEventListener('change', () => loadChats().catch(e => error(e.message)));
    el('avito-chat-more').addEventListener('click', () => loadChats(true).catch(e => error(e.message)));
    el('avito-messages-more').addEventListener('click', () => loadMessages(true).catch(e => error(e.message)));
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    refresh(); setInterval(refresh, 15000);
})();
