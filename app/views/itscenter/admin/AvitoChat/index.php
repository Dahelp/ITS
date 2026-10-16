<link rel="stylesheet" href="<?= PATH ?>/css/avito-chat.css?v=20261016-3">
<section class="content-header"><h1>Чат Авито</h1></section>
<section class="content">
    <?php if (\app\models\User::isAdmin()): ?><p><a href="<?= ADMIN ?>/avito">Объявления</a></p><?php endif; ?>
    <?php if (!$allowed): ?>
        <div class="alert alert-info">Для вашей учётной записи доступ к переписке отключён. Администратор может включить его в разделе «Пользователи», в карточке сотрудника.</div>
    <?php else: ?>
        <div id="avito-chat" data-endpoint="<?= htmlspecialchars(ADMIN.'/avito-chat/api', ENT_QUOTES, 'UTF-8') ?>" data-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <p><button id="avito-chat-check" class="btn btn-info" type="button">Проверить подключение к Авито</button> <span id="avito-chat-check-result" role="status"></span></p>
            <div id="avito-chat-error" class="alert alert-danger" role="alert" hidden></div>
            <div class="row">
                <div class="col-md-4"><div class="card"><div class="card-body">
                    <div class="avito-chat-toolbar">
                        <label class="avito-chat-filter" for="avito-chat-unread">
                            <input id="avito-chat-unread" type="checkbox">
                            <span>Только непрочитанные</span>
                        </label>
                        <button id="avito-chat-refresh" class="btn btn-sm btn-default" type="button">Обновить</button>
                    </div>
                    <div id="avito-chat-list" class="list-group" aria-label="Диалоги"></div>
                    <button id="avito-chat-more" class="btn btn-default mt-2" type="button" hidden>Ещё диалоги</button>
                </div></div></div>
                <div class="col-md-8"><div class="card"><div class="card-body">
                    <h2 id="avito-chat-title" class="h5">Выберите диалог</h2>
                    <button id="avito-messages-more" class="btn btn-sm btn-default" type="button" hidden>Предыдущие сообщения</button>
                    <div id="avito-chat-messages" style="height:55vh;overflow:auto;padding:12px" aria-live="polite"></div>
                    <form id="avito-chat-send">
                        <label for="avito-chat-text">Сообщение</label>
                        <textarea id="avito-chat-text" class="form-control" maxlength="1000" rows="3" required disabled></textarea>
                        <button id="avito-chat-submit" class="btn btn-primary mt-2" type="submit" disabled>Отправить</button>
                    </form>
                    <small class="text-muted">Обновление каждые 15 секунд. Сейчас поддерживается отправка текстовых сообщений.</small>
                </div></div></div>
            </div>
        </div>
        <script src="<?= PATH ?>/js/avito-chat-ui.js?v=20261016-2" defer></script>
        <script src="<?= PATH ?>/js/avito-chat.js?v=20261016-2" defer></script>
    <?php endif; ?>
</section>
