<link rel="stylesheet" href="<?= PATH ?>/css/avito-chat.css?v=20261021-1">
<section class="content-header"><h1>Чат Авито</h1></section>
<section class="content">
    <div class="d-flex flex-wrap align-items-center mb-3">
        <?php if (\app\models\User::isAdmin()): ?>
            <a class="btn btn-primary mr-2 mb-2" href="<?= ADMIN ?>/avito"><i class="fas fa-list"></i> К списку объявлений</a>
        <?php endif; ?>
        <?php if ($allowed): ?>
            <button id="avito-chat-check" class="btn btn-info mr-2 mb-2" type="button">Проверить подключение к Авито</button>
            <span id="avito-chat-check-result" class="mb-2" role="status"></span>
        <?php endif; ?>
    </div>
    <?php if (!$allowed): ?>
        <div class="alert alert-info">Для вашей учётной записи доступ к переписке отключён. Администратор может включить его в разделе «Пользователи», в карточке сотрудника.</div>
    <?php else: ?>
        <div id="avito-chat" data-endpoint="<?= htmlspecialchars(ADMIN.'/avito-chat/api', ENT_QUOTES, 'UTF-8') ?>" data-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" data-max-image-bytes="<?= (int)$maxImageBytes ?>" data-max-document-bytes="<?= (int)$maxDocumentBytes ?>">
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
                    <label class="sr-only" for="avito-chat-search">Поиск по загруженным диалогам</label>
                    <input id="avito-chat-search" class="form-control mb-2" type="search" placeholder="Поиск по загруженным диалогам">
                    <div id="avito-chat-list" class="list-group" aria-label="Диалоги"></div>
                    <small id="avito-chat-search-empty" class="text-muted" hidden>Совпадений нет. Загрузите ещё диалоги или измените запрос.</small>
                    <button id="avito-chat-more" class="btn btn-default mt-2" type="button" hidden>Ещё диалоги</button>
                </div></div></div>
                <div class="col-md-4"><div class="card"><div class="card-body">
                    <h2 id="avito-chat-title" class="h5">Выберите диалог</h2>
                    <div id="avito-chat-workspace" class="avito-chat-workspace" hidden>
                        <div class="avito-photo-actions">
                            <label for="avito-chat-assignee" class="mb-0">Ответственный</label>
                            <select id="avito-chat-assignee" class="form-control form-control-sm" disabled></select>
                            <button id="avito-chat-assign" class="btn btn-sm btn-outline-primary" type="button" disabled>Сохранить</button>
                        </div>
                        <details>
                            <summary>Внутренние заметки — покупатель их не видит</summary>
                            <small class="text-muted">Показаны последние 100 заметок.</small>
                            <div id="avito-chat-notes" class="avito-chat-notes"></div>
                            <form id="avito-note-form">
                                <label class="sr-only" for="avito-note-text">Внутренняя заметка</label>
                                <textarea id="avito-note-text" class="form-control" maxlength="5000" rows="2" placeholder="Информация для коллег" required disabled></textarea>
                                <button id="avito-note-save" class="btn btn-sm btn-secondary mt-2" type="submit" disabled>Добавить заметку</button>
                            </form>
                        </details>
                    </div>
                    <button id="avito-messages-more" class="btn btn-sm btn-default" type="button" hidden>Предыдущие сообщения</button>
                    <div id="avito-chat-messages" style="height:55vh;overflow:auto;padding:12px" aria-live="polite"></div>
                    <form id="avito-chat-send">
                        <div class="avito-template-picker">
                            <label for="avito-template-select">Шаблон ответа</label>
                            <div class="avito-photo-actions">
                                <select id="avito-template-select" class="form-control form-control-sm" disabled><option value="">Выберите шаблон</option></select>
                                <button id="avito-template-insert" class="btn btn-sm btn-outline-primary" type="button" disabled>Вставить в сообщение</button>
                            </div>
                        </div>
                        <label for="avito-chat-text">Сообщение</label>
                        <textarea id="avito-chat-text" class="form-control" maxlength="1000" rows="3" required disabled></textarea>
                        <button id="avito-chat-submit" class="btn btn-primary mt-2" type="submit" disabled>Отправить</button>
                    </form>
                    <details class="avito-template-editor">
                        <summary>Управление шаблонами ответов</summary>
                        <div class="avito-photo-actions mt-2">
                            <button id="avito-template-new" class="btn btn-sm btn-default" type="button">Новый</button>
                            <button id="avito-template-edit" class="btn btn-sm btn-default" type="button" disabled>Изменить выбранный</button>
                            <button id="avito-template-delete" class="btn btn-sm btn-outline-danger" type="button" disabled>Убрать из списка</button>
                        </div>
                        <form id="avito-template-form">
                            <p id="avito-template-editor-title">Новый шаблон</p>
                            <label for="avito-template-title">Название</label>
                            <input id="avito-template-title" class="form-control mb-2" maxlength="120" required>
                            <label for="avito-template-body">Текст ответа</label>
                            <textarea id="avito-template-body" class="form-control" rows="3" maxlength="1000" required></textarea>
                            <button class="btn btn-sm btn-primary mt-2" type="submit">Сохранить шаблон</button>
                        </form>
                    </details>
                    <div class="avito-photo-compose">
                        <label for="avito-chat-image">Фотография</label>
                        <div class="avito-photo-actions">
                            <input type="file" id="avito-chat-image" accept="image/jpeg,image/png,image/gif,image/bmp,.bmp" disabled>
                            <button id="avito-chat-image-send" class="btn btn-outline-primary" type="button" disabled>Отправить фото</button>
                        </div>
                        <small class="text-muted">JPG, PNG, GIF, BMP · до <?= h(round($maxImageBytes / 1048576, 1)) ?> МБ. Фото отправляется отдельным сообщением.</small>
                        <div id="avito-photo-preview" class="avito-photo-preview" hidden>
                            <img id="avito-photo-preview-image" alt="Предпросмотр выбранной фотографии">
                            <span id="avito-photo-name"></span>
                            <button id="avito-photo-remove" class="btn btn-sm btn-default" type="button">Убрать</button>
                        </div>
                        <div id="avito-photo-status" class="text-muted" role="status"></div>
                    </div>
                    <div class="avito-document-compose">
                        <label for="avito-chat-document">Счёт или документ</label>
                        <div class="avito-photo-actions">
                            <input type="file" id="avito-chat-document" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" disabled>
                            <button id="avito-document-upload" class="btn btn-outline-primary" type="button" disabled>Подготовить ссылку</button>
                        </div>
                        <small class="text-muted">PDF или DOCX · до <?= h(round($maxDocumentBytes / 1048576, 1)) ?> МБ. Ссылка действует 7 дней. Скачать сможет любой, у кого есть ссылка.</small>
                        <div id="avito-document-prepared" class="avito-document-prepared" hidden>
                            <strong id="avito-document-name"></strong>
                            <a id="avito-document-link" target="_blank" rel="noopener noreferrer">Проверить документ</a>
                            <small id="avito-document-expires"></small>
                            <div class="avito-photo-actions mt-2">
                                <button id="avito-document-send" class="btn btn-primary" type="button" disabled>Отправить ссылку в чат</button>
                                <button id="avito-document-revoke" class="btn btn-outline-danger" type="button" disabled>Отозвать ссылку</button>
                            </div>
                        </div>
                        <div id="avito-document-status" class="text-muted" role="status"></div>
                    </div>
                    <small class="text-muted">Обновление каждые 15 секунд. Нажмите на изображение для увеличения, на «Прослушать» — для голосового.</small>
                </div></div></div>
                <div class="col-md-4">
                    <div id="avito-listing-card" class="card avito-listing-card" hidden>
                        <div class="card-body">
                            <div id="avito-listing-loading" class="text-muted">Загружаем объявление…</div>
                            <div id="avito-listing-content" hidden>
                                <img id="avito-listing-main-image" class="avito-listing-main-image" alt="Фото товара" hidden>
                                <div id="avito-listing-gallery" class="avito-listing-gallery" aria-label="Галерея товара"></div>
                                <h2 id="avito-listing-title" class="h5 mt-3"></h2>
                                <div class="avito-listing-facts">
                                    <strong id="avito-listing-price"></strong>
                                    <span id="avito-listing-stock" class="badge badge-success"></span>
                                </div>
                                <small id="avito-listing-stock-details" class="text-muted d-block mb-3"></small>
                                <div id="avito-listing-description" class="avito-listing-description"></div>
                                <div class="avito-listing-links mt-3">
                                    <a id="avito-listing-product" class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer">Открыть товар</a>
                                    <a id="avito-listing-edit" class="btn btn-primary" target="_blank" rel="noopener noreferrer">Открыть и редактировать объявление</a>
                                </div>
                            </div>
                            <div id="avito-listing-empty" class="text-muted" hidden>Для этого диалога связанное объявление не найдено.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <dialog id="avito-image-dialog" class="avito-image-dialog" aria-label="Просмотр фотографии">
            <div class="avito-image-dialog-toolbar">
                <a id="avito-image-original" target="_blank" rel="noopener noreferrer">Открыть оригинал</a>
                <button id="avito-image-close" class="btn btn-default" type="button">Закрыть ×</button>
            </div>
            <img id="avito-image-full" alt="Фотография из переписки">
        </dialog>
        <script src="<?= PATH ?>/js/avito-chat-ui.js?v=20261017-1" defer></script>
        <script src="<?= PATH ?>/js/avito-chat-media.js?v=20261017-1" defer></script>
        <script src="<?= PATH ?>/js/avito-chat-team.js?v=20261017-5" defer></script>
        <script src="<?= PATH ?>/js/avito-chat.js?v=20261021-1" defer></script>
    <?php endif; ?>
</section>
