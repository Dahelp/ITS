(() => {
    'use strict';
    window.AvitoChatTeam = {init({api, error, insert}) {
        const el = id => document.getElementById(id);
        const assignee = el('avito-chat-assignee'), picker = el('avito-template-select');
        const note = el('avito-note-text'), noteDrafts = new Map();
        let chat = '', version = 0, pending = false, dirtyAssignment = false, templates = [], editId = 0;
        function selectedTemplate() { return templates.find(item => String(item.id) === picker.value); }
        function controls() {
            el('avito-chat-assign').disabled = pending || !chat;
            assignee.disabled = pending || !chat;
            note.disabled = pending || !chat; el('avito-note-save').disabled = pending || !chat;
            const template = selectedTemplate();
            picker.disabled = pending || !chat;
            el('avito-template-insert').disabled = pending || !template;
            el('avito-template-edit').disabled = pending || !template?.can_edit;
            el('avito-template-delete').disabled = pending || !template?.can_edit;
            el('avito-template-form').querySelector('button[type=submit]').disabled = pending || !chat;
        }
        function option(select, value, label) {
            const node = document.createElement('option'); node.value = String(value); node.textContent = label; select.append(node);
        }
        async function refresh() {
            if (!chat || pending) return;
            const requested = chat, generation = ++version;
            try {
                const state = await api('workspace', {chat: requested});
                if (requested !== chat || generation !== version) return;
                if (!dirtyAssignment) {
                    assignee.replaceChildren(); option(assignee, 0, 'Не назначен');
                    state.managers.forEach(manager => option(assignee, manager.id, manager.name));
                    if (state.assignee && !state.managers.some(manager => Number(manager.id) === Number(state.assignee))) option(assignee, state.assignee, 'Сотрудник больше недоступен');
                    assignee.value = String(state.assignee || 0);
                }
                const notes = el('avito-chat-notes'); notes.replaceChildren();
                state.notes.forEach(item => {
                    const row = document.createElement('div'); row.className = 'avito-note';
                    const author = document.createElement('small'); author.className = 'text-muted';
                    author.textContent = (item.author || 'Удалённый сотрудник') + ' · ' + window.AvitoChatUI.fullDate(item.created_at);
                    const body = document.createElement('div'); body.textContent = item.body; body.style.whiteSpace = 'pre-wrap'; row.append(author, body); notes.append(row);
                });
                if (!state.notes.length) notes.textContent = 'Заметок пока нет.';
                const previous = picker.value; templates = state.templates;
                picker.replaceChildren(); option(picker, '', 'Выберите шаблон');
                templates.forEach(template => option(picker, template.id, template.title));
                picker.value = templates.some(item => String(item.id) === previous) ? previous : '';
                controls();
            } catch (e) { if (requested === chat) error(e.message); }
        }
        async function write(operation, values, complete) {
            if (pending || !chat) return;
            const requested = chat; pending = true; controls();
            el('avito-template-form').querySelector('button[type=submit]').disabled = true;
            try {
                await api(operation, {...values, chat: requested}, true);
                if (operation === 'note') noteDrafts.delete(requested);
                if (requested === chat) { complete?.(); error(''); }
            }
            catch (e) { error(e.message); }
            finally {
                pending = false; controls(); el('avito-template-form').querySelector('button[type=submit]').disabled = false;
                refresh();
            }
        }
        assignee.addEventListener('change', () => { dirtyAssignment = true; });
        el('avito-chat-assign').addEventListener('click', () => write('assign', {manager: assignee.value}, () => { dirtyAssignment = false; }));
        el('avito-note-form').addEventListener('submit', event => {
            event.preventDefault(); if (!note.value.trim()) return;
            write('note', {body: note.value}, () => { note.value = ''; noteDrafts.delete(chat); });
        });
        picker.addEventListener('change', controls);
        el('avito-template-insert').addEventListener('click', () => { const item = selectedTemplate(); if (item) insert(item.body); });
        function newTemplate() {
            editId = 0; el('avito-template-title').value = ''; el('avito-template-body').value = ''; el('avito-template-editor-title').textContent = 'Новый шаблон';
        }
        el('avito-template-new').addEventListener('click', newTemplate);
        el('avito-template-edit').addEventListener('click', () => {
            const item = selectedTemplate(); if (!item?.can_edit) return;
            editId = item.id; el('avito-template-title').value = item.title; el('avito-template-body').value = item.body;
            el('avito-template-editor-title').textContent = 'Редактирование: ' + item.title;
        });
        el('avito-template-form').addEventListener('submit', event => {
            event.preventDefault(); write('template-save', {id: editId, title: el('avito-template-title').value, body: el('avito-template-body').value}, newTemplate);
        });
        el('avito-template-delete').addEventListener('click', () => {
            const item = selectedTemplate(); if (item?.can_edit) write('template-delete', {id: item.id}, newTemplate);
        });
        controls();
        return {
            select(value) {
                if (chat) noteDrafts.set(chat, note.value);
                chat = value; version++; dirtyAssignment = false; note.value = noteDrafts.get(chat) || '';
                el('avito-chat-workspace').hidden = !chat; controls(); refresh();
            },
            refresh,
        };
    }};
})();
