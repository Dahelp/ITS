(() => {
    'use strict';
    const root = document.getElementById('avito-chat');
    if (!root) return;
    const el = id => document.getElementById(id);
    const list = el('avito-chat-list'), messages = el('avito-chat-messages');
    const text = el('avito-chat-text'), submit = el('avito-chat-submit');
    const photoInput = el('avito-chat-image'), photoSend = el('avito-chat-image-send');
    const docInput = el('avito-chat-document'), docUpload = el('avito-document-upload');
    const documentDrafts = new Map();
    let documentFile = null;
    let photoFile = null, photoPreviewUrl = '', messageRows = new Map();
    let selected = '', chatOffset = 0, messageOffset = 0, busy = false, refreshing = false;
    let chatVersion = 0, messageVersion = 0, history = new Map();
    const drafts = new Map();
    const {formatChatDate, fullDate, chatStatus, safeMediaUrl} = window.AvitoChatUI;
    el('avito-chat-unread').checked = new URLSearchParams(location.search).get('unread') === '1';
    const team = window.AvitoChatTeam.init({api, error, insert(body) {
        if (busy || !selected || text.disabled) return;
        const next = text.value ? text.value + '\n' + body : body;
        if ([...next].length > 1000) { error('Сообщение с шаблоном превышает 1000 символов.'); return; }
        text.value = next; drafts.set(selected, next); text.focus();
    }});
    function filterChats() {
        const query = el('avito-chat-search').value.trim().toLocaleLowerCase('ru'); let matches = 0;
        list.querySelectorAll('[data-chat-id]').forEach(button => {
            button.hidden = query !== '' && !button.dataset.search.includes(query);
            if (!button.hidden) matches++;
        });
        el('avito-chat-search-empty').hidden = !query || matches > 0;
    }
    el('avito-chat-search').addEventListener('input', filterChats);
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
            options.body = values instanceof FormData ? values : new URLSearchParams(values);
            options.body.set('csrf', root.dataset.csrf);
        } else Object.entries(values).forEach(([key, value]) => url.searchParams.set(key, value));
        const response = await fetch(url, options);
        let body;
        try { body = await response.json(); }
        catch (_) { throw new Error('Не удалось получить ответ. Проверьте вход в админку.'); }
        if (!response.ok || body.error) throw new Error(body.error || 'Ошибка запроса.');
        return body.data;
    }
    function composeDisabled(disabled) {
        text.disabled = disabled; submit.disabled = disabled;
        photoInput.disabled = disabled; photoSend.disabled = disabled || !photoFile;
        el('avito-photo-remove').disabled = disabled;
        docInput.disabled = disabled; docUpload.disabled = disabled || !documentFile;
        const draft = documentDrafts.get(selected);
        el('avito-document-send').disabled = disabled || !draft || draft.sent || draft.revoked || !!documentFile;
        el('avito-document-revoke').disabled = disabled || !draft || draft.revoked;
    }
    function renderDocument() {
        const draft = documentDrafts.get(selected);
        el('avito-document-prepared').hidden = !draft || draft.revoked;
        if (draft) {
            el('avito-document-name').textContent = draft.name;
            el('avito-document-link').href = draft.url;
            el('avito-document-expires').textContent = 'Доступен до ' + fullDate(draft.expires_at);
        }
        el('avito-document-status').textContent = draft?.revoked ? 'Доступ по ссылке отозван.' : draft?.sent ? 'Ссылка отправлена в чат.' : '';
    }
    async function revokeDocument(token, chat) {
        if (busy) return false;
        busy = true; composeDisabled(true);
        try {
            await api('revoke-document', {token, chat}, true);
            const draft = documentDrafts.get(chat);
            if (draft?.token === token) { draft.revoked = true; renderDocument(); }
            error(''); return true;
        } catch (e) { error(e.message); return false; }
        finally { busy = false; composeDisabled(!selected); }
    }
    function clearPhoto() {
        if (photoPreviewUrl) URL.revokeObjectURL(photoPreviewUrl);
        photoPreviewUrl = ''; photoFile = null; photoInput.value = '';
        el('avito-photo-preview-image').removeAttribute('src');
        el('avito-photo-preview').hidden = true;
        el('avito-photo-name').textContent = ''; photoSend.disabled = true;
    }
    function renderMessages() {
        const nearBottom = messages.scrollHeight - messages.scrollTop - messages.clientHeight < 60;
        const previousHeight = messages.scrollHeight, previousTop = messages.scrollTop;
        const ordered = [...history.values()].sort((a, b) => Number(a.created) - Number(b.created));
        const visibleIds = new Set(ordered.map(message => message.id));
        for (const [id, entry] of messageRows) {
            if (!visibleIds.has(id)) { entry.node.remove(); messageRows.delete(id); }
        }
        ordered.forEach((message, index) => {
            const signature = JSON.stringify([message.created, message.direction, message.type, message.content]);
            const existing = messageRows.get(message.id);
            // Keep unchanged DOM nodes so periodic refresh does not interrupt audio.
            if (existing?.signature === signature) {
                if (messages.children[index] !== existing.node) messages.insertBefore(existing.node, messages.children[index] || null);
                return;
            }
            const row = document.createElement('div');
            row.className = 'p-2 mb-2 rounded ' + (message.direction === 'out' ? 'bg-light text-right' : 'border');
            row.style.whiteSpace = 'pre-wrap';
            const content = message.content || {};
            const body = document.createElement('div');
            body.textContent = content.text || content.link?.text || content.item?.title || content.location?.text ||
                ({image: 'Изображение', voice: 'Голосовое сообщение', call: 'Звонок', deleted: 'Сообщение удалено'}[message.type]) || 'Сообщение: ' + (message.type || 'неизвестный тип');
            row.append(body);
            const documentToken = message.direction === 'out' && typeof content.text === 'string'
                ? content.text.match(/https:\/\/its-center\.ru\/avito-document\/download\?token=([a-f0-9]{64})(?![a-f0-9])/)
                : null;
            if (documentToken) {
                const open = document.createElement('a'); open.href = 'https://its-center.ru/avito-document/download?token=' + documentToken[1];
                open.textContent = 'Скачать документ'; open.target = '_blank'; open.rel = 'noopener noreferrer'; open.className = 'd-block'; row.append(open);
                const revoke = document.createElement('button'); revoke.type = 'button'; revoke.className = 'btn btn-sm btn-outline-danger mt-2'; revoke.textContent = 'Отозвать ссылку';
                revoke.addEventListener('click', async () => {
                    revoke.disabled = true;
                    if (await revokeDocument(documentToken[1], selected)) revoke.textContent = 'Ссылка отозвана';
                    else revoke.disabled = false;
                }); row.append(revoke);
            }
            const sizes = content.image?.sizes || {};
            const imageUrl = safeMediaUrl(sizes['640x480'] || sizes['1280x960'] || Object.values(sizes)[0]);
            if (imageUrl) {
                window.AvitoChatMedia.image(row, imageUrl, sizes['1280x960'] || imageUrl);
            }
            if (content.voice?.voice_id) {
                window.AvitoChatMedia.voice(row, content.voice.voice_id, voiceId => api('voice', {voice_id: voiceId}));
            }
            const linkUrl = safeMediaUrl(content.link?.url || content.item?.item_url);
            if (linkUrl) {
                const link = document.createElement('a'); link.href = linkUrl; link.target = '_blank'; link.rel = 'noopener noreferrer';
                link.textContent = 'Открыть ссылку'; row.append(link);
            }
            const time = document.createElement('small'); time.className = 'text-muted d-block';
            time.textContent = formatChatDate(message.created); time.title = fullDate(message.created); row.append(time);
            if (existing) existing.node.replaceWith(row);
            if (messages.children[index] !== row) messages.insertBefore(row, messages.children[index] || null);
            messageRows.set(message.id, {node: row, signature});
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
            if (selected === chat) {
                list.querySelectorAll('[data-chat-id]').forEach(button => {
                    if (button.dataset.chatId !== chat) return;
                    button.classList.remove('avito-chat-unread');
                    button.querySelector('.avito-chat-status-unread')?.remove();
                });
            }
            window.dispatchEvent(new Event('avito-chat-read'));
        }
    }
    async function selectChat(chat, title) {
        if (busy) return;
        if (selected !== chat) {
            clearPhoto(); el('avito-photo-status').textContent = '';
            documentFile = null; docInput.value = '';
        }
        if (selected) drafts.set(selected, text.value);
        selected = chat; ++messageVersion; history = new Map(); messageOffset = 0;
        team.select(chat);
        renderDocument();
        list.querySelectorAll('[data-chat-id]').forEach(button => button.classList.toggle('avito-chat-selected', button.dataset.chatId === selected));
        text.value = drafts.get(chat) || '';
        messages.replaceChildren(); messageRows = new Map(); el('avito-chat-title').textContent = title;
        el('avito-messages-more').hidden = true; composeDisabled(true);
        try {
            await loadMessages();
            if (selected === chat) { composeDisabled(busy); error(''); }
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
            button.classList.toggle('avito-chat-unread', chat.is_unread === true);
            const title = chat.context?.value?.title || 'Диалог ' + chat.id;
            const heading = document.createElement('span'); heading.className = 'avito-chat-heading';
            const label = document.createElement('span'); label.className = 'avito-chat-label'; label.textContent = title;
            const time = document.createElement('time'), timestamp = chat.last_message?.created || chat.updated || chat.created;
            time.className = 'avito-chat-date'; time.textContent = formatChatDate(timestamp); time.title = fullDate(timestamp);
            if (time.textContent) time.dateTime = new Date(Number(timestamp) * 1000).toISOString();
            const meta = document.createElement('span'); meta.className = 'avito-chat-meta'; meta.append(time);
            const status = chatStatus(chat);
            if (status) {
                const mark = document.createElement('span');
                mark.className = 'avito-chat-status avito-chat-status-' + status.kind;
                mark.textContent = status.symbol; mark.title = status.label; mark.setAttribute('aria-label', status.label);
                meta.append(mark);
            }
            heading.append(label, meta); button.append(heading);
            const preview = document.createElement('span'); preview.className = 'avito-chat-preview';
            preview.textContent = chat.last_message?.content?.text || ({image: 'Изображение', voice: 'Голосовое сообщение'}[chat.last_message?.type]) || '';
            button.append(preview);
            if (chat.assigned_to) {
                const owner = document.createElement('small'); owner.className = 'text-muted d-block mt-1'; owner.textContent = 'Ответственный: ' + chat.assigned_to; button.append(owner);
            }
            button.dataset.search = (title + ' ' + preview.textContent + ' ' + (chat.users || []).map(user => user.name || '').join(' ')).toLocaleLowerCase('ru');
            button.addEventListener('click', () => selectChat(chat.id, title)); list.append(button);
        });
        if (!list.children.length) list.textContent = 'Диалогов нет.';
        chatOffset = offset + rows.length; el('avito-chat-more').hidden = rows.length < 50;
        filterChats();
    }
    async function refresh() {
        if (refreshing || document.hidden || busy) return;
        refreshing = true;
        try {
            if (!el('avito-chat-search').value.trim()) await loadChats();
            if (selected) { await loadMessages(); team.refresh(); }
            error('');
        }
        catch (e) { error(e.message); }
        finally { refreshing = false; }
    }
    el('avito-chat-send').addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || !selected || !text.value.trim()) return;
        const chat = selected, draft = text.value;
        busy = true; composeDisabled(true);
        try {
            await api('send', {chat, text: draft}, true);
            text.value = ''; error('');
            drafts.delete(chat);
            try { if (selected === chat) await loadMessages(); }
            catch (_) { error('Сообщение отправлено, но обновить историю не удалось. Нажмите «Обновить».'); }
        } catch (e) {
            error(e.message + ' При обрыве связи проверьте историю перед повторной отправкой.');
        } finally { busy = false; composeDisabled(!selected); }
    });
    photoInput.addEventListener('change', () => {
        const file = photoInput.files[0]; clearPhoto();
        if (!file) return;
        if (!/\.(jpe?g|png|gif|bmp)$/i.test(file.name)) {
            error('Выберите фотографию JPG, PNG, GIF или BMP.'); return;
        }
        if (!file.size || file.size > Number(root.dataset.maxImageBytes)) {
            error('Фотография превышает указанный лимит размера или файл пуст.'); return;
        }
        photoFile = file; photoPreviewUrl = URL.createObjectURL(file);
        el('avito-photo-preview-image').src = photoPreviewUrl;
        el('avito-photo-name').textContent = file.name;
        el('avito-photo-preview').hidden = false;
        el('avito-photo-status').textContent = ''; photoSend.disabled = !selected || busy; error('');
    });
    el('avito-photo-remove').addEventListener('click', clearPhoto);
    docInput.addEventListener('change', () => {
        documentFile = null;
        const file = docInput.files[0];
        if (file && !/\.(pdf|docx)$/i.test(file.name)) { error('Выберите PDF или DOCX.'); docInput.value = ''; }
        else if (file && (!file.size || file.size > Number(root.dataset.maxDocumentBytes))) {
            error('Документ пуст или превышает указанный лимит размера.'); docInput.value = '';
        } else { documentFile = file || null; error(''); }
        composeDisabled(busy || !selected);
    });
    docUpload.addEventListener('click', async () => {
        if (busy || !selected || !documentFile) return;
        const chat = selected, payload = new FormData(); payload.set('chat', chat); payload.set('document', documentFile);
        busy = true; composeDisabled(true); el('avito-document-status').textContent = 'Загружаем документ…';
        try {
            const draft = await api('upload-document', payload, true);
            documentDrafts.set(chat, draft); documentFile = null; docInput.value = '';
            renderDocument(); error('');
        } catch (e) { el('avito-document-status').textContent = ''; error(e.message); }
        finally { busy = false; composeDisabled(!selected); }
    });
    el('avito-document-send').addEventListener('click', async () => {
        const chat = selected, draft = documentDrafts.get(chat);
        if (busy || !draft || draft.sent || draft.revoked) return;
        busy = true; composeDisabled(true); el('avito-document-status').textContent = 'Отправляем ссылку…';
        try {
            await api('send-document', {chat, token: draft.token}, true);
            draft.sent = true; renderDocument(); error('');
            try { await loadMessages(); }
            catch (_) { error('Ссылка отправлена. Обновить историю не удалось — нажмите «Обновить».'); }
        } catch (e) { el('avito-document-status').textContent = ''; error(e.message + ' При обрыве связи проверьте историю перед повторной отправкой.'); }
        finally { busy = false; composeDisabled(!selected); }
    });
    el('avito-document-revoke').addEventListener('click', () => {
        const draft = documentDrafts.get(selected);
        if (draft) revokeDocument(draft.token, selected);
    });
    photoSend.addEventListener('click', async () => {
        if (busy || !selected || !photoFile) return;
        const chat = selected, payload = new FormData();
        payload.set('chat', chat); payload.set('image', photoFile);
        busy = true; composeDisabled(true); el('avito-photo-status').textContent = 'Загружаем и отправляем фото…';
        try {
            await api('send-image', payload, true);
            clearPhoto(); error(''); el('avito-photo-status').textContent = 'Фотография отправлена.';
            try { if (selected === chat) await loadMessages(); }
            catch (_) { error('Фото отправлено, но обновить историю не удалось. Нажмите «Обновить».'); }
        } catch (e) {
            el('avito-photo-status').textContent = '';
            error(e.message + ' При обрыве связи проверьте историю перед повторной отправкой.');
        } finally { busy = false; composeDisabled(!selected); }
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
    window.addEventListener('avito-chat-event', refresh);
    refresh(); setInterval(refresh, 15000);
})();
