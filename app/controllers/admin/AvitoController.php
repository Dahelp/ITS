<?php

namespace app\controllers\admin;

use ishop\App;
use app\models\admin\SSP;
use app\controllers\admin\AppController;
use app\models\admin\Avito;

class AvitoController extends AppController
{
    public function indexAction()
    {
        Avito::ensureSchema();
        $publicationSummary = Avito::publicationSummary();
        $this->setMeta('AVITO — мои объявления');
        $this->set(compact('publicationSummary'));
        // рендерит app/views/admin/Avito/index.php
    }

    public function syncIdentifiersAction()
    {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        try {
            echo json_encode(\app\services\AvitoIdSync::refreshPending(), JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(502);
            echo json_encode(['errors'=>['Не удалось проверить номера объявлений Авито.']], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function imageAction()
    {
        $this->layout = false;
        $this->view   = false;

        $url = isset($_GET['u']) ? $_GET['u'] : '';
        if (!$url) {
            http_response_code(404);
            exit;
        }

        // Декодируем оригинальный URL Avito
        $url = urldecode($url);

        if (!preg_match('~^https?://([^/]+\.)?avito\.ru/~i', $url)) {
            http_response_code(400);
            exit('Bad url');
        }

        $url = preg_replace('~^http://~i', 'https://', $url);

        // ---- ВАРИАНТ 1: простой прокси без кэша ----
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER         => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 15,
            // маскируемся под обычный браузер
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                                    . 'AppleWebKit/537.36 (KHTML, like Gecko) '
                                    . 'Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                'Referer: https://www.avito.ru/',
            ],
        ]);

        $data        = curl_exec($ch);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($httpCode != 200 || !$data) {
            // если не удалось — вернём 1×1 прозрачный PNG или просто 404
            http_response_code(404);
            exit;
        }

        if (!$contentType) {
            $contentType = 'image/jpeg';
        }

        header('Content-Type: ' . $contentType);
        header('Cache-Control: public, max-age=86400');

        echo $data;
        exit;
    }

    /**
     * DataTables server-side для списка avito_ad
     */
    public function serverProcessingAction()
    {
        Avito::ensureSchema();
        header('Content-Type: application/json; charset=UTF-8');

        $table = <<<SQL
        (
        SELECT 
            a.id,
            a.images_json    AS images_json,    -- JSON с фото
            a.avito_id      AS avito_id,
            a.avito_url     AS avito_url,
            a.article        AS article,        -- артикул из avito_ad
            a.title          AS title,
            a.category       AS category,
            a.manager_name   AS manager_name,
            GREATEST(0, COALESCE(p.quantity, p.rest, 0) - COALESCE(p.reserve, 0)) AS rest,
            a.status         AS status,
            CONCAT(COALESCE(a.avito_publication_status, ''), '|', COALESCE(DATE_FORMAT(a.avito_status_updated_at, '%d.%m.%Y %H:%i'), '')) AS avito_publication_state
        FROM avito_ad a
        LEFT JOIN product p ON p.article = a.article
        ) temp
        SQL;

        $primaryKey = 'id';

        $columns = array(
            // 0: ID
            array('db' => 'id', 'dt' => 0),

            // 1: Фото — первая картинка из images_json
            array(
                'db' => 'images_json',
                'dt' => 1,
                'formatter' => function ($d, $row) {
                    if (!$d) {
                        return '<span class="badge bg-secondary">нет фото</span>';
                    }

                    $images = json_decode($d, true);
                    $first  = '';

                    if (is_array($images) && !empty($images)) {
                        $first = reset($images);
                    }

                    if ($first) {
                        $srcUrl = $first;
                        $host = parse_url($srcUrl, PHP_URL_HOST);
                        $host = $host ? strtolower($host) : '';

                        if ($host && preg_match('~(^|\.)avito\.ru$~', $host)) {
                            $srcUrl = preg_replace('~^http://~i', 'https://', $srcUrl);
                            $srcUrl = rtrim(PATH, '/') . '/avito/image?u=' . rawurlencode($srcUrl);
                        }

                        $src = htmlspecialchars($srcUrl, ENT_QUOTES, 'UTF-8');

                        return '<img src="' . $src . '" loading="lazy" referrerpolicy="no-referrer" onerror="this.onerror=null;this.outerHTML=&quot;&lt;span class=\&quot;badge bg-secondary\&quot;&gt;нет фото&lt;/span&gt;&quot;"'
                                    . ' style="max-width:80px; max-height:60px; object-fit:contain;">';
                    }

                    return '<span class="badge bg-secondary">нет фото</span>';
                }
            ),

            // 2: Avito ID
            array(
                'db' => 'avito_id',
                'dt' => 2,
                'formatter' => function ($value, $row) {
                    $id = trim((string)$value);
                    return ctype_digit($id) && (int)$id > 0
                        ? htmlspecialchars($id, ENT_QUOTES, 'UTF-8')
                        : '<span class="text-muted" title="Авито ещё не присвоил номер объявления">Не присвоен</span>';
                }
            ),

            // 3: Артикул
            array(
                'db' => 'article',
                'dt' => 3,
                'formatter' => function ($d, $row) {
                    return $d ? htmlspecialchars($d, ENT_QUOTES, 'UTF-8') : '';
                }
            ),

            // 4: Название
            array(
                'db' => 'title',
                'dt' => 4,
                'formatter' => function ($d, $row) {
                    return htmlspecialchars($d, ENT_QUOTES, 'UTF-8');
                }
            ),

            // 5: Категория
            array(
                'db' => 'category',
                'dt' => 5,
                'formatter' => function ($d, $row) {
                    return htmlspecialchars($d, ENT_QUOTES, 'UTF-8');
                }
            ),

            // 6: Менеджер
            array(
                'db' => 'manager_name',
                'dt' => 6,
                'formatter' => function ($d, $row) {
                    return $d ? htmlspecialchars($d, ENT_QUOTES, 'UTF-8') : '';
                }
            ),

            // 7: Наличие (product.rest)
            array(
                'db' => 'rest',
                'dt' => 7,
                'formatter' => function ($d, $row) {
                    if ($d === null || $d === '') {
                        return '<span class="text-muted">—</span>';
                    }
                    return (int)$d;
                }
            ),

            // 8: Статус в нашей XML-выгрузке
            array(
                'db' => 'status',
                'dt' => 8,
                'formatter' => function ($d, $row) {
                    if ($d === 'active') {
                        return '<span class="badge badge-primary">В выгрузке</span>';
                    }
                    if ($d === 'archived') {
                        return '<span class="badge badge-secondary">Не выгружается</span>';
                    }
                    return '<span class="badge badge-light">Черновик</span>';
                }
            ),

            // 9: Фактическое состояние объявления на Avito
            array(
                'db' => 'avito_publication_state',
                'dt' => 9,
                'formatter' => function ($d, $row) {
                    [$status, $updatedAt] = array_pad(explode('|', (string)$d, 2), 2, '');
                    [$label, $class] = Avito::avitoPublicationPresentation($status);
                    $html = '<span class="badge badge-' . $class . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
                    if ($updatedAt !== '') {
                        $html .= '<div class="small text-muted mt-1">Проверено: ' . htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8') . '</div>';
                    }
                    return $html;
                }
            ),

            // 10: Готовность объявления
            array(
                'db' => 'id',
                'dt' => 10,
                'formatter' => function ($id, $row) {
                    $adRow = \R::getRow('SELECT * FROM avito_ad WHERE id = ? LIMIT 1', [(int)$id]);
                    $analysis = Avito::analyzeRow($adRow);
                    $class = $analysis['status'] === 'ready' ? 'success' : ($analysis['status'] === 'warning' ? 'warning' : 'danger');
                    $details = [];
                    if ($analysis['missingRequired']) $details[] = 'Обязательные: ' . implode(', ', $analysis['missingRequired']);
                    if ($analysis['provisionalRequired']) $details[] = 'Временно заполнено: ' . implode(', ', $analysis['provisionalRequired']);
                    if ($analysis['warnings']) $details[] = implode('; ', $analysis['warnings']);
                    $title = htmlspecialchars(implode(' | ', $details), ENT_QUOTES, 'UTF-8');
                    return '<span class="badge badge-' . $class . '" title="' . $title . '">' . htmlspecialchars($analysis['label'], ENT_QUOTES, 'UTF-8') . '</span>'
                        . '<div class="small mt-1">Обязательные: <strong>' . $analysis['requiredScore'] . '%</strong> (' . count($analysis['missingRequired']) . ' не заполнено)</div>'
                        . ($analysis['provisionalRequired'] ? '<div class="small text-warning"><strong>Уточнить: ' . count($analysis['provisionalRequired']) . '</strong></div>' : '')
                        . '<div class="small">Доп.: ' . $analysis['optionalScore'] . '% (' . count($analysis['missingOptional']) . ')</div>'
                        . '<div class="small">Текст: ' . $analysis['textScore'] . '%</div>';
                }
            ),

            // 11: Действия
            array(
                'db' => 'id',
                'dt' => 11,
                'formatter' => function ($id, $row) {
                    $id   = (int)$id;
                    $edit = ADMIN . '/avito/edit?id=' . $id;
                    $del  = ADMIN . '/avito/delete?id=' . $id;
                    $exp  = ADMIN . '/avito/export?id=' . $id;
                    $avitoItemId = trim((string)($row['avito_id'] ?? ''));
                    if (!ctype_digit($avitoItemId) || (int)$avitoItemId <= 0) $avitoItemId = '';
                    $avitoUrl = trim((string)($row['avito_url'] ?? ''));
                    if ($avitoUrl === '' && $avitoItemId !== '') {
                        $avitoUrl = self::buildAvitoItemUrl($row, $avitoItemId);
                    }
                    $avitoLink = $avitoUrl !== ''
                        ? '<a target="_blank" rel="noopener" href="' . htmlspecialchars($avitoUrl, ENT_QUOTES, 'UTF-8') . '" title="Открыть/найти объявление на Avito"><i class="fas fa-external-link-alt text-success"></i></a> '
                        : '';
                    $deletionRequested = (bool)\R::getCell(
                        'SELECT deletion_requested_at IS NOT NULL FROM avito_ad WHERE id = ? LIMIT 1',
                        [$id]
                    );
                    $deleteTitle = $deletionRequested ? 'Проверить снятие с Avito и удалить' : 'Снять с Avito и удалить';
                    $deleteConfirm = $deletionRequested
                        ? 'Проверить статус на Avito и удалить локальную запись, если объявление уже снято?'
                        : 'Сначала снять объявление с Avito, а после подтверждения удалить из админки?';

                    return
                        $avitoLink .
                        '<a href="' . $edit . '" title="Редактировать"><i class="fas fa-pencil-alt"></i></a> ' .
                        '<a class="delete" href="' . $del . '" onclick="return confirm(\'' . $deleteConfirm . '\')" title="' . $deleteTitle . '"><i class="fas ' . ($deletionRequested ? 'fa-hourglass-half text-warning' : 'fa-times-circle text-danger') . '"></i></a> ' .
                        '<a href="' . $exp . '" title="Экспорт объявления в XML"><i class="fas fa-file-code"></i></a>';
                }
            ),
        );

        $sql_details = array(
            'user' => App::$app->getProperty('sql_user'),
            'pass' => App::$app->getProperty('sql_pass'),
            'db'   => App::$app->getProperty('sql_db'),
            'host' => App::$app->getProperty('sql_host'),
        );

        try {
            $ssp  = new SSP();
            $data = $ssp::simple($_GET, $sql_details, $table, $primaryKey, $columns);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(array(
                'data'  => array(),
                'error' => 'Server error: ' . $e->getMessage(),
            ), JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Добавление объявления
     */
    public function addAction()
    {
        Avito::ensureSchema();
        $model = new Avito();

        if (!empty($_POST)) {
            // загружаем данные в модель
            $model->load($_POST);

            // берём атрибуты после load()
            $attrs = $model->attributes;

            // нормализуем images_json: textarea -> JSON
            if (isset($attrs['images_json'])) {
                $attrs['images_json'] = $this->normalizeImagesJson($attrs['images_json']);
            }

            // создаём Bean RedBean
            $ad = Avito::createAdBean();

            // UUID только при создании
            $ad->uuid = $this->generateUuidV4();

            // переносим атрибуты модели → в bean
            foreach ($attrs as $k => $v) {
                // пустые строки превращаем в NULL, чтобы не плодить ''
                $ad->$k = ($v === '' ? null : $v);
            }
            Avito::rememberManualFields($ad, array_values(array_diff(
                array_intersect(array_keys($_POST), array_keys($attrs)),
                ['status', 'avito_id', 'avito_url']
            )));

            \R::store($ad);

            $_SESSION['success'] = 'Объявление добавлено. ' . $this->requestAvitoAutoload();
            redirect(ADMIN . '/avito');
        }

        // для view передаём ПУСТОЙ bean
        $ad = new \stdClass();

        $this->setMeta('AVITO — добавить объявление');
        $this->set(compact('ad'));
    }

    /**
     * Редактирование объявления
     */
    public function editAction()
    {
        Avito::ensureSchema();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $ad = \R::load('avito_ad', $id);

        if (!$ad || !$ad->id) {
            $_SESSION['error'] = 'Объявление не найдено';
            redirect(ADMIN . '/avito');
        }

        $model = new Avito();

        if (!empty($_POST)) {
            $before = $ad->export();
            $model->load($_POST);
            $attrs = $model->attributes;

            // нормализуем images_json: textarea -> JSON
            if (isset($attrs['images_json'])) {
                $attrs['images_json'] = $this->normalizeImagesJson($attrs['images_json']);
            }

            // перенос атрибутов в bean
            foreach ($attrs as $k => $v) {
                $ad->$k = ($v === '' ? null : $v);
            }

            $manualFields = [];
            foreach (array_keys($_POST) as $field) {
                if (!array_key_exists($field, $attrs) || in_array($field, ['status', 'avito_id', 'avito_url'], true)) {
                    continue;
                }
                $newValue = $attrs[$field] ?? null;
                if ((string)($before[$field] ?? '') !== (string)$newValue) {
                    $manualFields[] = $field;
                }
            }
            Avito::rememberManualFields($ad, $manualFields);

            // UUID на всякий случай
            if (empty($ad->uuid)) {
                $ad->uuid = $this->generateUuidV4();
            }

            \R::store($ad);

            $_SESSION['success'] = 'Изменения сохранены. ' . $this->requestAvitoAutoload();
            redirect(ADMIN . '/avito/edit?id=' . $ad->id);
        }

        $this->setMeta('AVITO — редактировать объявление');
        $this->set(compact('ad'));
    }

    public function productSearchAction()
    {
        $this->layout = false;
        $this->view   = false;

        header('Content-Type: application/json; charset=utf-8');

        $term = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
        $results = [];

        if ($term !== '') {
            $like = '%' . $term . '%';

            $rows = \R::getAll("
                SELECT id, article, name
                FROM product
                WHERE (name LIKE ? OR article LIKE ?)
                ORDER BY name
                LIMIT 20
            ", [$like, $like]);

            foreach ($rows as $row) {
                $article = trim((string)$row['article']);
                $name    = trim((string)$row['name']);

                if ($article === '' && $name === '') {
                    continue;
                }

                $results[] = [
                    'id'   => $article, // это попадёт в <select name="article">
                    'text' => ($article ? $article . ' — ' : '') . $name,
                    'product_id' => (int)$row['id'],
                ];
            }
        }

        echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function requestAvitoAutoload(): string
    {
        $result = Avito::triggerAutoloadUpload();
        if (!empty($result['triggered'])) {
            return 'Обновление отправлено в автозагрузку Avito; обработка выполняется не мгновенно.';
        }

        return 'Запустить автозагрузку Avito не удалось: ' . (string)($result['message'] ?? 'неизвестная ошибка')
            . '. Данные сохранены на сайте, повторите синхронизацию позже.';
    }


    public function syncProductsAction()
    {
        @set_time_limit(0);

        try {
            $updated = Avito::syncLinkedAdsFromProducts();
            $legacyUpdated = Avito::syncLegacyStatusesFromApi();
            $urlsUpdated = Avito::syncUrlsFromApi();
            $apiStats = Avito::pushLinkedAdsToApi();
            $autoload = Avito::triggerAutoloadUpload();

            $message = 'Avito: синхронизировано объявлений с товарами: ' . (int)$updated
                . ', старых объявлений обновлено по остатку API: ' . (int)$legacyUpdated
                . ', ID/ссылок/статусов получено из API: ' . (int)$urlsUpdated
                . ', цен отправлено: ' . (int)$apiStats['price_success']
                . ', остатков отправлено: ' . (int)$apiStats['stock_success']
                . ', ненулевых отправлено: ' . (int)$apiStats['stock_sent_positive']
                . ', сумма остатков: ' . (int)$apiStats['stock_sent_total']
                . ', активных по статусу: ' . (int)$apiStats['status_active']
                . ', в архив по статусу: ' . (int)$apiStats['status_archived']
                . ', остатков проверено в API: ' . (int)$apiStats['stock_checked']
                . ', с наличием в API: ' . (int)$apiStats['stock_positive']
                . ', несовпадений остатков: ' . (int)$apiStats['stock_mismatch']
                . ', автозагрузка: ' . (string)$autoload['message'];

            $failed = (int)$apiStats['price_failed'] + (int)$apiStats['stock_failed'] + (int)$apiStats['stock_mismatch'];
            if ($failed > 0) {
                $message .= ', ошибок API: ' . $failed;
                if (!empty($apiStats['errors'])) {
                    $message .= '. Первые ошибки: ' . implode(' | ', $apiStats['errors']);
                }
            }

            $_SESSION['success'] = $message;
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Avito: ошибка синхронизации: ' . $e->getMessage();
        }
        redirect(ADMIN . '/avito');
    }

    public function syncStatusesAction()
    {
        @set_time_limit(0);
        try {
            $updated = Avito::syncUrlsFromApi();
            $_SESSION['success'] = 'Статусы публикации Avito обновлены: ' . (int)$updated
                . '. Поле «На Avito» показывает фактическое состояние, полученное из API.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Не удалось обновить статусы Avito: ' . $e->getMessage();
        }
        redirect(ADMIN . '/avito');
    }

    public function applyTemplateAction()
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        try {
            Avito::applyGeneratedTemplateToAd($id);
            $_SESSION['success'] = 'Оформленный шаблон описания применён.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Не удалось применить шаблон: ' . $e->getMessage();
        }
        redirect(ADMIN . '/avito/edit?id=' . $id);
    }
    /**
     * Удаление
     */
    public function deleteAction()
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id) {
            Avito::ensureSchema();
            $row = \R::getRow('SELECT * FROM avito_ad WHERE id = ? LIMIT 1', [$id]);
            if (!$row) {
                $_SESSION['error'] = 'Объявление не найдено';
                redirect(ADMIN . '/avito');
            }

            $itemId = Avito::numericAvitoItemId($row);
            $remoteStatus = (string)($row['avito_publication_status'] ?? '');
            $requested = !empty($row['deletion_requested_at']);
            if ($requested) {
                Avito::syncUrlsFromApi();
                $row = \R::getRow('SELECT * FROM avito_ad WHERE id = ? LIMIT 1', [$id]);
                $itemId = Avito::numericAvitoItemId($row);
                $remoteStatus = (string)($row['avito_publication_status'] ?? '');
            }
            $deletionWaitElapsed = $requested
                && strtotime((string)($row['deletion_requested_at'] ?? '')) <= time() - 600;

            if (Avito::deletionIsConfirmed($remoteStatus)
                || ($deletionWaitElapsed && $itemId <= 0
                    && !in_array(mb_strtolower($remoteStatus, 'UTF-8'), ['active', 'published'], true))) {
                \R::trash(\R::load('avito_ad', $id));
                $_SESSION['success'] = $itemId > 0
                    ? 'Avito подтвердил снятие. Объявление удалено из админки.'
                    : 'Объявление ещё не имело числового Avito ID и удалено из админки.';
                redirect(ADMIN . '/avito');
            }

            if (!$requested) {
                \R::exec("UPDATE avito_ad SET status = 'archived', deletion_requested_at = NOW() WHERE id = ?", [$id]);
                $upload = Avito::triggerAutoloadUpload();
                if (!empty($upload['triggered'])) {
                    $_SESSION['success'] = 'Объявление исключено из XML. Avito начал снятие с публикации. Запись сохранена до подтверждения; позже нажмите жёлтые часы ещё раз.';
                } else {
                    $_SESSION['error'] = 'Объявление исключено из XML, но запуск автозагрузки не подтверждён: '
                        . (string)($upload['message'] ?? 'неизвестная ошибка') . '. Запись не удалена.';
                }
                redirect(ADMIN . '/avito');
            }

            if (Avito::deletionIsConfirmed($remoteStatus)) {
                \R::trash(\R::load('avito_ad', $id));
                $_SESSION['success'] = 'Avito подтвердил снятие. Объявление удалено из админки.';
            } else {
                Avito::triggerAutoloadUpload();
                $_SESSION['error'] = 'Avito ещё не подтвердил снятие (текущий статус: '
                    . ($remoteStatus !== '' ? $remoteStatus : 'не проверен')
                    . '). Запись оставлена в админке; повторите проверку позже.';
            }
        }
        redirect(ADMIN . '/avito');
    }

    /**
     * Экспорт XML (все или конкретное ID)
     * /admin/avito/export       — все
     * /admin/avito/export?id=10 — одно
     */
    public function exportAction()
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $rows = Avito::getFeedRows($id);

        $xml = Avito::buildXml($rows);

        // фиксированное имя файла для Авито
        $filename = 'avito.xml';

        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $xml;
        exit;
        }

    /* ========= helpers ========= */

    private function generateUuidV4()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * textarea с URL (по одному в строке или JSON) → JSON для images_json
     */
    private static function buildAvitoItemUrl(array $row, string $itemId): string
    {
        $city = 'podolsk';
        $category = self::avitoCategorySlug((string)($row['category'] ?? ''));
        $titleSlug = self::avitoSlug((string)($row['title'] ?? ''));

        if ($itemId === '') {
            return '';
        }

        if ($titleSlug === '') {
            return 'https://www.avito.ru/' . $city . '/' . $category . '/' . rawurlencode($itemId);
        }

        return 'https://www.avito.ru/' . $city . '/' . $category . '/' . $titleSlug . '_' . rawurlencode($itemId);
    }

    private static function avitoCategorySlug(string $category): string
    {
        $category = trim(mb_strtolower($category, 'UTF-8'));
        $map = [
            'запчасти и аксессуары' => 'zapchasti_i_aksessuary',
        ];

        return $map[$category] ?? self::avitoSlug($category);
    }

    private static function avitoSlug(string $text): string
    {
        $text = trim(mb_strtolower($text, 'UTF-8'));
        $map = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
            'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
            'х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
        ];
        $text = strtr($text, $map);
        $text = str_replace(['×', '/', '\\'], ['h', '-', '-'], $text);
        $text = preg_replace('~[^a-z0-9_-]+~', '_', $text);
        $text = preg_replace('~_+~', '_', $text);
        $text = preg_replace('~-+~', '-', $text);

        return trim($text, '_-');
    }

    private function normalizeImagesJson($raw)
    {
        if ($raw === '' || $raw === null) {
            return null;
        }

        // если уже JSON — просто аккуратно перекодируем
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        // иначе считаем, что это список ссылок по одной в строке
        $lines = preg_split('~\r\n|\r|\n~', $raw);
        $urls  = [];
        foreach ($lines as $l) {
            $l = trim($l);
            if ($l !== '') {
                $urls[] = $l;
            }
        }

        if (!$urls) {
            return null;
        }

        return json_encode($urls, JSON_UNESCAPED_UNICODE);
    }

/**
 * Построение XML в формате Avito из строк avito_ad
 */
private function buildAvitoXml($rows)
{
    $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Ads/>');
    $xml->addAttribute('formatVersion', '3');
    $xml->addAttribute('target', 'Avito.ru');

    foreach ($rows as $r) {
        $ad = $xml->addChild('Ad');

        // Идентификаторы / статусы
        $idVal = !empty($r['ad_external_id'])
            ? (string)$r['ad_external_id']
            : (string)$r['id'];

        $this->xmlAdd($ad, 'Id', $idVal);
        $this->xmlAdd($ad, 'AvitoId', (string)($r['avito_id'] ?? ''));
        $this->xmlAdd($ad, 'Status',      (string)(isset($r['status']) ? $r['status'] : 'draft'));
        $this->xmlAdd($ad, 'ListingFee',  (string)(isset($r['listing_fee']) ? $r['listing_fee'] : 'Package'));
        $this->xmlAdd($ad, 'AdStatus',    (string)(isset($r['ad_status']) ? $r['ad_status'] : 'Free'));

        // Время
        $this->xmlAdd($ad, 'DateBegin',   (string)(isset($r['date_begin']) ? $r['date_begin'] : ''));
        $this->xmlAdd($ad, 'DateEnd',     (string)(isset($r['date_end']) ? $r['date_end'] : ''));

        // Контакты
        $this->xmlAdd($ad, 'ManagerName',  (string)(isset($r['manager_name']) ? $r['manager_name'] : ''));
        $this->xmlAdd($ad, 'ContactPhone', (string)(isset($r['contact_phone']) ? $r['contact_phone'] : ''));
        $this->xmlAdd($ad, 'ContactMethod',(string)(isset($r['contact_method']) ? $r['contact_method'] : ''));

        // Адрес
        $this->xmlAdd($ad, 'Address',      (string)(isset($r['address']) ? $r['address'] : ''));
        $this->xmlAdd($ad, 'Latitude',     isset($r['latitude'])  && $r['latitude']  !== null ? (string)$r['latitude']  : '');
        $this->xmlAdd($ad, 'Longitude',    isset($r['longitude']) && $r['longitude'] !== null ? (string)$r['longitude'] : '');
        $this->xmlAdd($ad, 'SellerAddressId', (string)(isset($r['seller_address_id']) ? $r['seller_address_id'] : ''));

        // Общие поля
        $this->xmlAdd($ad, 'Category',     (string)(isset($r['category']) ? $r['category'] : ''));
        $this->xmlAdd($ad, 'GoodsType',    (string)(isset($r['goods_type']) ? $r['goods_type'] : ''));
        $this->xmlAdd($ad, 'AdType',       (string)(isset($r['ad_type']) ? $r['ad_type'] : ''));
        $this->xmlAdd($ad, 'ProductType',  (string)(isset($r['product_type']) ? $r['product_type'] : ''));

        $this->xmlAdd($ad, 'Title',        (string)(isset($r['title']) ? $r['title'] : ''));

        /**
         * ОПИСАНИЕ (Description) — ВСЕГДА CDATA
         */
        $descVal = isset($r['description']) ? trim((string)$r['description']) : '';
        if ($descVal === '') {
            $descVal = isset($r['title']) ? trim((string)$r['title']) : '';
        }

        if ($descVal !== '') {
            // нормализуем переносы строк
            $descVal = preg_replace('~\r\n?~', "\n", $descVal);

            // ограничиваем до 7500 символов (лимит Авито)
            $limit = 7500;
            if (function_exists('mb_strlen')) {
                if (mb_strlen($descVal, 'UTF-8') > $limit) {
                    $descVal = mb_substr($descVal, 0, $limit, 'UTF-8');
                }
            } else {
                if (strlen($descVal) > $limit) {
                    $descVal = substr($descVal, 0, $limit);
                }
            }

            // защита от ']]>' внутри CDATA
            $safeDescVal = str_replace(']]>', ']]]]><![CDATA[>', $descVal);

            // создаём <Description><![CDATA[...]]></Description>
            $descNode = $ad->addChild('Description');
            $domDesc  = dom_import_simplexml($descNode);
            $dom      = $domDesc->ownerDocument;
            $cdata    = $dom->createCDATASection($safeDescVal);
            $domDesc->appendChild($cdata);
        }

        // Цена
        if (!empty($r['price_rub'])) {
            $this->xmlAdd($ad, 'Price', (string)((int)$r['price_rub']));
        }

        // Состояние/аудитория
        $this->xmlAdd($ad, 'Condition',  (string)(isset($r['item_condition']) ? $r['item_condition'] : ''));
        $this->xmlAdd($ad, 'TargetAudience', (string)(isset($r['target_audience']) ? $r['target_audience'] : ''));

        // Видео
        $this->xmlAdd($ad, 'VideoURL',      (string)(isset($r['video_url']) ? $r['video_url'] : ''));
        $this->xmlAdd($ad, 'VideoFileURL',  (string)(isset($r['video_file_url']) ? $r['video_file_url'] : ''));

        // Шины
        $this->xmlAdd($ad, 'Brand',            (string)(isset($r['brand']) ? $r['brand'] : ''));
        $this->xmlAdd($ad, 'Model',            (string)(isset($r['model']) ? $r['model'] : ''));
        $this->xmlAdd($ad, 'TireSectionWidth', (string)(isset($r['tire_section_width']) ? $r['tire_section_width'] : ''));
        $this->xmlAdd($ad, 'TireAspectRatio',  (string)(isset($r['tire_aspect_ratio']) ? $r['tire_aspect_ratio'] : ''));
        $this->xmlAdd($ad, 'RimDiameter',      (string)(isset($r['rim_diameter']) ? $r['rim_diameter'] : ''));
        $this->xmlAdd($ad, 'TireType',         (string)(isset($r['tire_type']) ? $r['tire_type'] : ''));
        $this->xmlAdd($ad, 'Quantity',         (string)(isset($r['quantity']) ? $r['quantity'] : ''));
        $this->xmlAdd($ad, 'SpeedIndex',       (string)(isset($r['speed_index']) ? $r['speed_index'] : ''));
        $this->xmlAdd($ad, 'PlyRating',        (string)(isset($r['ply_rating']) ? $r['ply_rating'] : ''));
        $this->xmlAdd($ad, 'Construction',     (string)(isset($r['construction']) ? $r['construction'] : ''));
        $this->xmlAdd($ad, 'TubeType',         (string)(isset($r['tube_type']) ? $r['tube_type'] : ''));
        $this->xmlAdd($ad, 'WheelAxle',        (string)(isset($r['wheel_axle']) ? $r['wheel_axle'] : ''));
        $this->xmlAdd($ad, 'LoadIndex',        (string)(isset($r['load_index']) ? $r['load_index'] : ''));
        $this->xmlAdd($ad, 'ResidualTreadSV',  isset($r['residual_tread_sv']) && $r['residual_tread_sv'] !== null ? (string)$r['residual_tread_sv'] : '');
        $this->xmlAdd($ad, 'Design',           (string)(isset($r['design']) ? $r['design'] : ''));
        $this->xmlAdd($ad, 'VehicleType',      (string)(isset($r['vehicle_type']) ? $r['vehicle_type'] : ''));

        // Доставка/габариты
        $this->xmlAdd($ad, 'DeliverySubsidy', isset($r['delivery_subsidy']) && $r['delivery_subsidy'] !== null ? (string)$r['delivery_subsidy'] : '');
        $this->xmlAdd($ad, 'ReturnPolicy',    (string)(isset($r['return_policy']) ? $r['return_policy'] : ''));
        $this->xmlAdd($ad, 'InternetCalls',   (string)(isset($r['internet_calls']) ? $r['internet_calls'] : ''));
        $this->xmlAdd($ad, 'CallsDevices',    (string)(isset($r['calls_devices_json']) ? $r['calls_devices_json'] : ''));
        $this->xmlAdd($ad, 'DeliveryOptions', (string)(isset($r['delivery_json']) ? $r['delivery_json'] : ''));

        $this->xmlAdd($ad, 'Weight', isset($r['weight_kg']) && $r['weight_kg'] !== null ? (string)$r['weight_kg'] : '');
        $this->xmlAdd($ad, 'Length', isset($r['length_cm']) && $r['length_cm'] !== null ? (string)$r['length_cm'] : '');
        $this->xmlAdd($ad, 'Height', isset($r['height_cm']) && $r['height_cm'] !== null ? (string)$r['height_cm'] : '');
        $this->xmlAdd($ad, 'Width',  isset($r['width_cm'])  && $r['width_cm']  !== null ? (string)$r['width_cm']  : '');

        // Промо
        $this->xmlAdd($ad, 'Promo',             (string)(isset($r['promo']) ? $r['promo'] : ''));
        $this->xmlAdd($ad, 'PromoAutoOptions',  (string)(isset($r['promo_auto_json']) ? $r['promo_auto_json'] : ''));
        $this->xmlAdd($ad, 'PromoManualOptions',(string)(isset($r['promo_manual_json']) ? $r['promo_manual_json'] : ''));

        // Картинки
        $imagesNode = $ad->addChild('Images');
        if (!empty($r['images_json'])) {
            $decoded = json_decode($r['images_json'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $img) {
                    $url = '';
                    if (is_string($img)) {
                        $url = $img;
                    } elseif (is_array($img) && !empty($img['url'])) {
                        $url = $img['url'];
                    }
                    if ($url) {
                        $imgNode = $imagesNode->addChild('Image');
                        $imgNode->addAttribute('url', $url);
                    }
                }
            }
        }
    }

    // ВАЖНО: использовать DOMDocument только для форматирования, CDATA уже есть
    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->formatOutput = true;

    // Получаем XML-строку
    $xmlString = $dom->saveXML();

    // Оборачиваем содержимое Description в <![CDATA[ ... ]]>
    // (если там ещё нет CDATA)
    $xmlString = preg_replace(
        '~<Description>(?!\s*<!\[CDATA\[)(.*?)</Description>~su',
        '<Description><![CDATA[$1]]></Description>',
        $xmlString
    );

    return $xmlString;

}


    private function xmlAdd(\SimpleXMLElement $node, $name, $value)
    {
        $node->addChild($name, $value);
    }

    public function importXlsAction()
    {
        $preview = null;
        $errors  = [];
        $result  = null;

        // Шаг 1: загрузка файла + предпросмотр
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_upload'])) {
            try {
                if (empty($_FILES['file']['name'])) {
                    throw new \RuntimeException('Файл не выбран.');
                }

                $path = Avito::handleUpload($_FILES['file']);
                $_SESSION['avito_import_file'] = $path;

                $preview = Avito::buildPreview($path, 30);

            } catch (\Throwable $e) {
                $_SESSION['error'] = 'Ошибка при загрузке: ' . $e->getMessage();
            }
        }

        // Шаг 2: реальный импорт
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_import'])) {
            $path = $_SESSION['avito_import_file'] ?? null;

            if (!$path || !is_file($path)) {
                $_SESSION['error'] = 'Файл для импорта не найден. Загрузите его ещё раз.';
            } else {
                try {
                    $result = Avito::importFromXlsx($path);

                    unset($_SESSION['avito_import_file']);

                    $_SESSION['success'] = sprintf(
                        'Импорт завершён. Загружено/обновлено %d объявлений, ошибок: %d.',
                        $result['inserted'],
                        count($result['errors'])
                    );

                    $errors  = $result['errors'];
                    $preview = null; // после импорта предпросмотр можно скрыть

                } catch (\Throwable $e) {
                    $_SESSION['error'] = 'Ошибка импорта: ' . $e->getMessage();
                }
            }
        }

        $this->set(compact('preview', 'errors', 'result'));
    }

}
















