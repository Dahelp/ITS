(() => {
    'use strict';
    const {safeMediaUrl} = window.AvitoChatUI;
    function image(container, previewUrl, originalUrl) {
        const preview = safeMediaUrl(previewUrl), original = safeMediaUrl(originalUrl) || preview;
        if (!preview) return;
        const button = document.createElement('button'); button.type = 'button'; button.className = 'avito-image-button';
        button.setAttribute('aria-label', 'Увеличить изображение');
        const img = document.createElement('img'); img.src = preview; img.alt = 'Изображение из сообщения'; img.loading = 'lazy';
        button.append(img); container.append(button);
        button.addEventListener('click', () => {
            const dialog = document.getElementById('avito-image-dialog');
            document.getElementById('avito-image-full').src = original;
            document.getElementById('avito-image-original').href = original;
            dialog.showModal();
        });
    }
    function voice(container, voiceId, fetchVoice) {
        const wrap = document.createElement('div'); wrap.className = 'avito-voice';
        const load = document.createElement('button'); load.type = 'button'; load.className = 'btn btn-sm btn-outline-primary'; load.textContent = '▶ Прослушать';
        const audio = document.createElement('audio'); audio.controls = true; audio.preload = 'none'; audio.hidden = true;
        const status = document.createElement('small'); status.className = 'text-muted d-block'; status.setAttribute('role', 'status');
        wrap.append(load, audio, status); container.append(wrap);
        load.addEventListener('click', async () => {
            load.disabled = true; status.textContent = 'Загружаем голосовое…';
            try {
                const result = await fetchVoice(voiceId), url = safeMediaUrl(result.url);
                if (!wrap.isConnected) return;
                if (!url) throw new Error('Ссылка на голосовое сообщение недоступна.');
                audio.src = url; audio.hidden = false; load.textContent = 'Обновить ссылку'; status.textContent = '';
                try { await audio.play(); } catch (_) {
                    status.textContent = audio.error
                        ? 'Не удалось воспроизвести запись. Обновите ссылку; если ошибка повторится, откройте запись в Авито.'
                        : 'Нажмите ▶ на плеере для воспроизведения.';
                }
            } catch (e) { status.textContent = e.message; }
            finally { load.disabled = false; }
        });
        audio.addEventListener('error', () => {
            status.textContent = 'Не удалось воспроизвести запись. Обновите ссылку; если ошибка повторится, откройте запись в Авито.';
        });
    }
    const dialog = document.getElementById('avito-image-dialog');
    document.getElementById('avito-image-close')?.addEventListener('click', () => dialog.close());
    dialog?.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    dialog?.addEventListener('close', () => document.getElementById('avito-image-full').removeAttribute('src'));
    window.AvitoChatMedia = {image, voice};
})();
