<?php

namespace app\models\admin;

use app\models\AppModel;
use app\services\admin\AdminActivityLogger;
use ishop\App;
use GuzzleHttp\Client;

class Cron extends AppModel
{
    public $attributes = [
        'name' => '',
        'alias' => '',
        'url_params' => '',
        'url_download' => '',
        'hide' => '',
        'categories' => '',
    ];

    public $rules = [
        'required' => [
            ['name'],
        ],
    ];

    public function checkUnique()
    {
        if ($this->attributes['alias'] != "") {
            $cron = \R::findOne('cron', 'alias = ?', [$this->attributes['alias']]);
            if ($cron) {
                if ($cron->alias == $this->attributes['alias']) {
                    $this->errors['unique'][] = 'CRON с таким url уже существует';
                }
                return false;
            }
        }
        return true;
    }

    public static function writeLog(string $text, int $cron_id = 0): void
    {
        $path = WWW . '/storage/logs/update_debug.log';
        $logLine = "[" . date('Y-m-d H:i:s') . "] " . ($cron_id ? "ID {$cron_id} — " : "") . $text . "\n";
        file_put_contents($path, $logLine, FILE_APPEND | LOCK_EX);
    }

    private static function isCliDebug(): bool
    {
        // 1) ENV: CRON_DEBUG=1
        if (!empty(getenv('CRON_DEBUG'))) return true;

        // 2) CLI: --debug
        if (PHP_SAPI === 'cli' && !empty($_SERVER['argv'])) {
            return in_array('--debug', $_SERVER['argv'], true);
        }

        return false;
    }

    private static function logInfo(string $msg, int $cronId = 0): void
    {
        // info-шки пишем только в debug
        if (!self::isCliDebug()) return;
        self::writeLog($msg, $cronId);
    }

    private static function logError(string $msg, int $cronId = 0): void
    {
        // ошибки пишем всегда
        self::writeLog($msg, $cronId);
    }

    /**
     * IndexNow отправка одним клиентом (чтобы не создавать client на каждый товар)
     */
    public static function updateIndexNow(string $alias, ?Client $client = null): void
    {
        $verification_yandex = App::options('option_verification_yandex');
        $verification_bing   = App::options('option_verification_bing');

        if (!$client) $client = new Client();

        $url = self::buildProductUrl($alias);

        try {
            $client->request('GET', 'https://yandex.com/indexnow', [
                'query' => ['url' => $url, 'key' => $verification_yandex],
            ]);
        } catch (\Throwable $e) {}

        try {
            $client->request('GET', 'https://www.bing.com/indexnow', [
                'query' => ['url' => $url, 'key' => $verification_bing],
            ]);
        } catch (\Throwable $e) {}
    }

    /**
     * Нормализует URL (убирает двойные слеши, кроме https://)
     */
    private static function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';

        // убираем пробелы
        $url = preg_replace('~\s+~u', '', $url);

        // Разрешаем ТОЛЬКО абсолютные http(s)
        if (!preg_match('~^https?://~i', $url)) return '';

        $p = @parse_url($url);
        if (!$p || empty($p['host'])) return '';

        $scheme = strtolower($p['scheme'] ?? 'https');
        if ($scheme !== 'http' && $scheme !== 'https') return '';

        $host = strtolower($p['host']);

        // Разрешаем только наш домен
        if ($host !== 'its-center.ru' && $host !== 'www.its-center.ru') return '';

        $path = $p['path'] ?? '/';
        $path = '/' . ltrim($path, '/');
        $path = preg_replace('~/+~', '/', $path);

        // Блокируем мусорные пути
        if (strpos($path, '/home/') === 0 || strpos($path, '/cron/') === 0 || strpos($path, 'public_html') !== false) {
            return '';
        }

        $out = $scheme . '://' . $host . $path;
        if (!empty($p['query'])) $out .= '?' . $p['query'];

        return $out;
    }

    /**
     * Строит канонический URL товара (CLI-safe, без PATH).
     */
    public static function buildProductUrl(string $alias): string
    {
        $alias = strtolower(ltrim(trim($alias), '/'));
        return 'https://its-center.ru/product/' . $alias;
    }

    /**
     * Producer: добавить URL в очередь (UPSERT, dedupe по url)
     * - при повторном изменении сбрасывает статус в pending (0)
     * - сбрасывает attempts/last_error/sent_at, чтобы гарантировать переотправку
     */
    public static function enqueueIndexNow(string $url): void
    {
        $url = trim($url);

        // ЖЕСТКАЯ защита: никакие /home/, public_html, cron не пропускаем
        if ($url === '' ||
            stripos($url, '/home/') !== false ||
            stripos($url, 'public_html') !== false ||
            stripos($url, '/cron/') !== false
        ) {
            return;
        }

        // Разрешаем только наши каноничные URL
        if (!preg_match('~^https://(www\.)?its-center\.ru/product/[a-z0-9\-_/]+$~i', $url)) {
            return;
        }

        if (strlen($url) > 512) return;

        $now = date('Y-m-d H:i:s');

        try {
            $existingId = (int)\R::getCell(
                "SELECT id FROM indexnow_queue WHERE url = ? ORDER BY id DESC LIMIT 1",
                [$url]
            );

            if ($existingId > 0) {
                \R::exec(
                    "UPDATE indexnow_queue
                     SET updated_at = ?, status = 0, attempts = 0, sent_at = NULL, last_error = NULL
                     WHERE id = ?",
                    [$now, $existingId]
                );
                return;
            }

            \R::exec(
                "INSERT INTO indexnow_queue (url, created_at, updated_at, status, attempts, sent_at, last_error)
                VALUES (?, ?, ?, 0, 0, NULL, NULL)
                ON DUPLICATE KEY UPDATE
                    updated_at = VALUES(updated_at),
                    status     = 0,
                    attempts   = 0,
                    sent_at    = NULL,
                    last_error = NULL",
                [$url, $now, $now]
            );
        } catch (\Throwable $e) {
            // silently
        }
    }

    public static function updateModification(string $article, int $quantity, float $price_rozn, float $price_spec, float $price_opt, string $date_price): void
    {
        $mod = \R::findOne('modification', 'article = ?', [$article]);
        if (!$mod) return;

        $update_data = [
            'quantity'   => $quantity,
            'spec_price' => $price_spec,
            'opt_price'  => $price_opt,
        ];

        $action = \R::findOne('actions', 'product_id = ? AND date_end > ?', [$mod['id'], date('Y-m-d H:i:s')]);
        if (!$action) {
            $update_data['price'] = $price_rozn;
        }

        $set_clause = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($update_data)));
        $update_data['id'] = $mod['id'];
        \R::exec("UPDATE modification SET $set_clause WHERE id = :id", $update_data);
    }

    public static function finalizeCronUpdate(int $id, string $date_update, string $date_price): void
    {
        \R::exec("UPDATE cron SET date_update = ? WHERE id = ?", [$date_update, $id]);

        $isCli = (PHP_SAPI === 'cli');

        $adminId = null;
        if (!$isCli && isset($_SESSION['user']['id']) && is_numeric($_SESSION['user']['id'])) {
            $adminId = (int)$_SESSION['user']['id'];
        }

        AdminActivityLogger::cron($id, $adminId !== null, $adminId, $date_update);

        $inStockDate   = \R::findOne('in_stock_history_total', 'date_total = ?', [$date_price]);
        $countInStock  = (int)\R::getCell("SELECT COALESCE(SUM(quantity), 0) FROM product WHERE hide = 'show'");

        if ($inStockDate) {
            \R::exec(
                "UPDATE in_stock_history_total SET qty_total = ? WHERE date_total = ?",
                [$countInStock, $date_price]
            );
        } else {
            \R::exec(
                "INSERT INTO in_stock_history_total (date_total, qty_total) VALUES (?, ?)",
                [$date_price, $countInStock]
            );
        }
    }

    /**
     * Старый метод (не трогаю), но он у тебя с findOne actions и IndexNow внутри.
     * Если он используется где-то ещё — оставляем.
     */
    public static function updateProduct(array $product, array $res, string $date_price, string $date_update): void
    {
        // API 1C: на витрине доступен только свободный остаток.
        // Резерв сохраняем отдельно, но не прибавляем к quantity.
        $quantity = max(0, (int)$res["rest"]);
        $stock_status_id = ($res["rest"] != "0") ? "1" : "0";
        $pssql_id = $product["id"];

        $action = \R::findOne('actions', 'product_id = ? AND date_end > ?', [$pssql_id, $date_update]);

        $update_data = [
            'spec_price'      => $res["price_spec"],
            'opt_price'       => $res["price_opt"],
            'price'           => $res["price_rozn"],
            'quantity'        => $quantity,
            'rest'            => $res["rest"],
            'reserve'         => $res["reserve"],
            'wait'            => $res["wait"],
            'wait_date'       => $res["wait_date"],
            'data_edit_price' => $date_update,
            'data_edit_all'   => $date_update,
        ];

        if ($action || $product["stock_status_id"] != "0") {
            if ($product["stock_status_id"] == "2" || $product["stock_status_id"] == "3") {
                $update_data['price'] = $res["price_rozn"];
                $update_data['opt_price'] = $res["price_opt"];
                $update_data['spec_price'] = $res["price_spec"];
                $update_data['data_edit_price'] = $date_price;
            }

            if ($product["stock_status_id"] == "0" && $res["wait"] != "") {
                $update_data['stock_status_id'] = "3";
            } else {
                $update_data['stock_status_id'] = $stock_status_id;
            }
        } else {
            $update_data['price'] = $res["price_rozn"];
            $update_data['opt_price'] = $res["price_opt"];
            $update_data['spec_price'] = $res["price_spec"];
            $update_data['data_edit_price'] = $date_price;
            $update_data['stock_status_id'] = ($res["wait"] != "") ? "3" : $stock_status_id;
        }

        $set_clause = implode(', ', array_map(fn($key) => "$key = :$key", array_keys($update_data)));
        $update_data['id'] = $pssql_id;
        $affected = \R::exec("UPDATE product SET $set_clause WHERE id = :id", $update_data);

        file_put_contents(ROOT . '/storage/logs/update_debug.log', "UPDATE for product_id={$pssql_id}, affected={$affected}\n", FILE_APPEND);

        if ($affected > 0) {
            file_put_contents(ROOT . '/storage/logs/update_debug.log', "✅ Обновлён товар ID={$product['id']}\n", FILE_APPEND);

            self::saveStockHistory($pssql_id, $quantity, (float)$res["price_rozn"], $date_price);
            self::updateIndexNow((string)$product['alias']);
        }
    }

    /**
     * Быстрая версия: сравнивает текущие значения и обновляет только если реально изменилось.
     * ВАЖНО: НЕ делает HTTP (IndexNow). Возвращает alias для отправки после commit.
     *
     * Требования:
     * - $productRow должен приходить УЖЕ с текущими полями продукта (prefetch в processFileBatch)
     * - $hasAction приходит как bool (батчом посчитан в processFileBatch), без EXISTS внутри
     *
     * @return string|null alias если было изменение, иначе null
     */
    public static function updateProductFromFileFast(
        array $fields,
        array $productRow,   // ожидаем: id, alias, stock_status_id, opt_price, price, quantity, rest, reserve, wait, wait_date, data_edit_price, data_edit_all
        bool $hasAction,
        string $date_price,
        string $date_update,
        int $cron_id
    ): ?string {
        $productId = (int)($productRow['id'] ?? 0);
        if ($productId <= 0) {
            self::logError("❌ [FILE] Некорректный productId", $cron_id);
            return null;
        }

        $rest     = (int)($fields['Свободно'] ?? 0);
        $reserve  = (int)($fields['Резерв'] ?? 0);
        $quantity = $rest + $reserve;

        $opt_price_raw  = (string)($fields['ЦенаОпт'] ?? '0');
        $rozn_price_raw = (string)($fields['ЦенаРозн'] ?? '0');

        $opt_price  = (float)str_replace(',', '.', $opt_price_raw);
        $rozn_price = (float)str_replace(',', '.', $rozn_price_raw);

        $wait      = (string)($fields['Ожидается'] ?? '');
        $wait_date = (string)($fields['Дата'] ?? '');

        $newStockStatusId = ($quantity > 0 ? 1 : 0);

        // новые значения, которые хотим проставить
        $update = [
            'opt_price'        => $opt_price,
            'price'            => $rozn_price,
            'quantity'         => $quantity,
            'rest'             => $rest,
            'reserve'          => $reserve,
            'wait'             => $wait,
            'wait_date'        => $wait_date,
            'data_edit_price'  => $date_update,
            'data_edit_all'    => $date_update,
            'stock_status_id'  => $newStockStatusId,
        ];

        // Твоя логика: если нет акции или stock_status_id != 0 — фиксируем price/date_price
        // NB: сохраняю поведение как было, только hasAction теперь приходит извне
        if (!$hasAction || (string)($productRow['stock_status_id'] ?? '') !== "0") {
            $update['price'] = $rozn_price;
            $update['data_edit_price'] = $date_price; // DATE
        }

        // --- compare с текущими значениями из $productRow (БЕЗ getRow) ---
        $current = $productRow;

        $changed = false;

        foreach (['quantity','rest','reserve','stock_status_id'] as $k) {
            if ((int)($current[$k] ?? 0) !== (int)($update[$k] ?? 0)) { $changed = true; break; }
        }

        if (!$changed) {
            foreach (['wait','wait_date','data_edit_price','data_edit_all'] as $k) {
                if ((string)($current[$k] ?? '') !== (string)($update[$k] ?? '')) { $changed = true; break; }
            }
        }

        if (!$changed) {
            if (abs((float)($current['opt_price'] ?? 0) - (float)$update['opt_price']) > 0.00001) $changed = true;
            else if (abs((float)($current['price'] ?? 0) - (float)$update['price']) > 0.00001) $changed = true;
        }

        if (!$changed) {
            self::syncProductBranchStock($productId, $fields, $date_price);
            return null;
        }

        // --- UPDATE только при изменениях ---
        $set = [];
        $params = [];
        foreach ($update as $k => $v) {
            $set[] = "{$k} = :{$k}";
            $params[$k] = $v;
        }
        $params['id'] = $productId;

        \R::exec('UPDATE product SET ' . implode(', ', $set) . ' WHERE id = :id', $params);

        // История остатков — оставляем как у тебя (тихо)
        self::syncProductBranchStock($productId, $fields, $date_price);
        self::saveStockHistory($productId, $quantity, $rozn_price, $date_price);

        $alias = (string)($productRow['alias'] ?? '');
        if ($alias !== '') {
            // DB-only, быстро, без HTTP
            self::enqueueIndexNow(self::buildProductUrl($alias));
            return $alias;
        }

        return null;
    }

    private static function fieldValue(array $fields, array $names, $default = ''): string
    {
        foreach ($names as $name) {
            if (array_key_exists($name, $fields)) {
                return trim((string)$fields[$name]);
            }
        }

        return (string)$default;
    }

    private static function syncProductBranchStock(int $productId, array $fields, string $date): void
    {
        $branches = \R::getAll(
            "SELECT branch_id, tbl
             FROM branch_office
             ORDER BY branch_id"
        );

        if (!$branches) {
            return;
        }

        $fieldMap = [
            'f' => ['Климовск', 'РљР»РёРјРѕРІСЃРє'],
            'rkl' => ['КлимовскР', 'РљР»РёРјРѕРІСЃРєР '],
            'g' => ['Краснодар', 'РљСЂР°СЃРЅРѕРґР°СЂ'],
            'rkr' => ['КраснодарР', 'РљСЂР°СЃРЅРѕРґР°СЂР '],
            'k' => ['Воронеж', 'Р’РѕСЂРѕРЅРµР¶'],
            'rv' => ['ВоронежР', 'Р’РѕСЂРѕРЅРµР¶Р '],
            'l' => ['СанктПетербург', 'РЎР°РЅРєС‚РџРµС‚РµСЂР±СѓСЂРі'],
            'rspb' => ['СанктПетербургР', 'РЎР°РЅРєС‚РџРµС‚РµСЂР±СѓСЂРіР '],
            'ek' => ['Екатеринбург', 'Р•РєР°С‚РµСЂРёРЅР±СѓСЂРі'],
            'ekr' => ['ЕкатеринбургР', 'Р•РєР°С‚РµСЂРёРЅР±СѓСЂРіР '],
            'r' => ['Резерв', 'Р РµР·РµСЂРІ'],
        ];

        foreach ($branches as $branch) {
            $branchId = (int)($branch['branch_id'] ?? 0);
            $tbl = trim((string)($branch['tbl'] ?? ''));
            if ($branchId <= 0 || $tbl === '') {
                continue;
            }

            $quantityRaw = self::fieldValue($fields, $fieldMap[$tbl] ?? [$tbl], '0');
            $quantity = (int)str_replace(',', '.', $quantityRaw);

            $stockId = (int)\R::getCell(
                'SELECT stock_id FROM in_stock WHERE product_id = ? AND branch_id = ? LIMIT 1',
                [$productId, $branchId]
            );

            if ($stockId > 0) {
                \R::exec(
                    'UPDATE in_stock SET quantity = ?, date_scheduling = ? WHERE stock_id = ?',
                    [$quantity, $date, $stockId]
                );
            } else {
                \R::exec(
                    'INSERT INTO in_stock (branch_id, product_id, quantity, date_scheduling) VALUES (?, ?, ?, ?)',
                    [$branchId, $productId, $quantity, $date]
                );
            }
        }
    }

    public static function saveStockHistory(int $productId, int $quantity, float $price, string $date): void
    {
        try {
            \R::exec(
                "INSERT INTO `in_stock_history` (`product_id`, `date_ish`, `qty`, `price`) VALUES (?, ?, ?, ?)",
                [$productId, $date, $quantity, $price]
            );
        } catch (\Exception $e) {
            // молча
        }
    }

    public static function processFileBatch(int $id, string $categories, int $offset = 0, int $limit = 50): array
    {
        $offset = max(0, $offset);
        $limit  = max(1, min(500, $limit));

        if ($id <= 0 || trim($categories) === '') {
            self::logError("❌ [FILE] Нет ID или категорий", $id);
            return ['done' => true, 'error' => 'missing_id_or_categories'];
        }

        $cat_arr = array_values(array_filter(array_map(
            static fn($v) => (int)$v,
            explode(',', urldecode($categories))
        )));

        if (empty($cat_arr)) {
            self::logError("❌ [FILE] Пустые категории", $id);
            return ['done' => true, 'error' => 'empty_categories'];
        }

        // ---- Lock ----
        $lockPath = sys_get_temp_dir() . "/refresh_tovars_from_file_{$id}.lock";
        $lockFp   = @fopen($lockPath, 'c');
        if (!$lockFp) {
            self::logError("❌ [FILE] Не удалось создать lock: {$lockPath}", $id);
            return ['done' => false, 'error' => 'lock_open_failed'];
        }
        if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
            return ['busy' => true, 'retry_after_ms' => 1500];
        }

        try {
            $cacheFile    = WWW . '/cron/cache_file_' . $id . '.csv';
            $filteredFile = WWW . '/cron/filtered_file_' . $id . '.csv';
            $metaFile     = WWW . '/cron/filtered_file_' . $id . '.meta.json';

            $dl = [
                'ok' => true,
                'path' => $cacheFile,
                'bytes' => (int)@filesize($cacheFile),
                'mtime' => (int)@filemtime($cacheFile),
                'url' => '',
            ];

            if ($offset === 0 || !file_exists($cacheFile)) {
                $dl = self::downloadCronFile($id, $cacheFile);
                if (empty($dl['ok'])) {
                    self::logError("ID {$id} - [FILE] Download failed: " . ($dl['error'] ?? 'unknown'), $id);
                    return ['done' => true, 'error' => 'download_failed'];
                }
                self::logInfo("ID {$id} - [FILE] Download ok: bytes={$dl['bytes']} mtime={$dl['mtime']} url={$dl['url']}", $id);
            }

            $needRebuildFiltered = !file_exists($filteredFile);

            if (!$needRebuildFiltered && file_exists($metaFile)) {
                $meta = json_decode((string)file_get_contents($metaFile), true);
                $srcMtime = (int)($meta['source_mtime'] ?? 0);
                $srcBytes = (int)($meta['source_bytes'] ?? 0);

                $curMtime = (int)@filemtime($cacheFile);
                $curBytes = (int)@filesize($cacheFile);

                if ($curMtime !== $srcMtime || $curBytes !== $srcBytes) {
                    $needRebuildFiltered = true;
                }
            } else {
                if (!$needRebuildFiltered) $needRebuildFiltered = true;
            }

            if ($needRebuildFiltered) {
                @unlink($filteredFile);
                @unlink($metaFile);
            }

            $date_price  = date('Y-m-d');
            $date_update = date('Y-m-d H:i:s');

            // ---- 1) Подготовить filtered_file.csv (тяжёлый шаг, делаем один раз) ----
            if (!file_exists($filteredFile)) {
                if (!file_exists($cacheFile)) {
                    self::logError("ID {$id} — ❌ [FILE] Не найден cache_file.csv", $id);
                    return ['done' => true, 'error' => 'cache_file_missing'];
                }

                $inClause = implode(',', $cat_arr);
                $articles = \R::getCol("SELECT article FROM product WHERE category_id IN ({$inClause})");

                $articles_normalized = [];
                foreach ($articles as $a) {
                    if ($a === null) continue;
                    $a = trim((string)$a);
                    if ($a === '') continue;

                    $norm = ctype_digit($a) ? ltrim($a, '0') : $a;
                    if ($norm !== '') $articles_normalized[$norm] = true;
                }

                self::logInfo("ID {$id} — ℹ️ [FILE] Получено товаров из базы: " . count($articles_normalized), $id);

                $in  = fopen($cacheFile, 'rb');
                $out = fopen($filteredFile, 'wb');

                if (!$in || !$out) {
                    if ($in) fclose($in);
                    if ($out) fclose($out);
                    self::logError("ID {$id} — ❌ [FILE] Не удалось открыть файлы для чтения/записи", $id);
                    return ['done' => true, 'error' => 'file_open_failed'];
                }

                // BOM in source
                $bom = fread($in, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    fseek($in, 0);
                }

                $header = fgetcsv($in, 0, ';');
                if (!$header) {
                    fclose($in); fclose($out);
                    @unlink($filteredFile);
                    self::logError("ID {$id} — ❌ [FILE] Пустой заголовок в cache_file.csv", $id);
                    return ['done' => true, 'error' => 'empty_header'];
                }

                // Конвертим заголовки в UTF-8
                $headerUtf8 = array_map(
                    static fn($h) => trim((string)mb_convert_encoding((string)$h, 'UTF-8', 'CP1251')),
                    $header
                );
                fputcsv($out, $headerUtf8, ';');

                $matched = 0;

                while (($row = fgetcsv($in, 0, ';')) !== false) {
                    $rowUtf8 = array_map(
                        static fn($val) => mb_convert_encoding((string)$val, 'UTF-8', 'CP1251'),
                        $row
                    );

                    $assoc = array_combine($headerUtf8, array_pad($rowUtf8, count($headerUtf8), ''));
                    if (!$assoc) continue;

                    $code = trim((string)($assoc['НоменклатураКод'] ?? ''));
                    if ($code === '') continue;

                    $norm = ctype_digit($code) ? ltrim($code, '0') : $code;
                    if ($norm !== '' && isset($articles_normalized[$norm])) {
                        fputcsv($out, $rowUtf8, ';');
                        $matched++;
                    }
                }

                fclose($in); fclose($out);

                if ($matched === 0) {
                    @unlink($filteredFile);
                    self::logError("ID {$id} — ❌ [FILE] filtered_file.csv пуст", $id);
                    return ['done' => true, 'error' => 'filtered_empty'];
                }

                file_put_contents($metaFile, json_encode([
                    'total_all'    => $matched,
                    'created_at'   => date('c'),
                    'source'       => basename($cacheFile),
                    'source_url'   => $dl['url'] ?? null,
                    'source_mtime' => (int)@filemtime($cacheFile),
                    'source_bytes' => (int)@filesize($cacheFile),
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

                self::logInfo("ID {$id} — ✅ [FILE] filtered_file.csv создан: {$matched} строк", $id);
            }

            // ---- 2) meta total_all ----
            $totalAll = null;
            if (file_exists($metaFile)) {
                $meta = json_decode((string)file_get_contents($metaFile), true);
                if (is_array($meta) && isset($meta['total_all'])) {
                    $totalAll = (int)$meta['total_all'];
                }
            }

            if (!file_exists($filteredFile)) {
                self::logError("ID {$id} — ❌ [FILE] Нет filtered_file.csv", $id);
                return ['done' => true, 'error' => 'filtered_missing'];
            }

            // ---- 3) Быстрый доступ к строкам: SplFileObject + seek ----
            $csv = new \SplFileObject($filteredFile, 'rb');
            $csv->setFlags(
                \SplFileObject::READ_CSV |
                \SplFileObject::SKIP_EMPTY |
                \SplFileObject::DROP_NEW_LINE
            );
            $csv->setCsvControl(';');

            // header в line 0
            $csv->rewind();
            $header = $csv->current();
            if (!is_array($header) || empty($header)) {
                self::logError("ID {$id} — ❌ [FILE] Пустой заголовок filtered_file.csv", $id);
                return ['done' => true, 'error' => 'filtered_header_empty'];
            }

            // offset — количество обработанных строк БЕЗ заголовка => линия = offset+1
            $startLine = $offset + 1;
            $csv->seek($startLine);

            // ---- 4) Собираем строки батча + articles ----
            $rows = [];
            $articlesNorm = [];
            $articlesList = [];

            $processed = 0;
            while (!$csv->eof() && $processed < $limit) {
                $row = $csv->current();
                $csv->next();

                if (!is_array($row) || $row === [null] || $row === false) continue;

                $row = array_pad($row, count($header), '');
                $assoc = array_combine($header, $row);
                if (!$assoc) continue;

                $code = trim((string)($assoc['article'] ?? $assoc['НоменклатураКод'] ?? ''));
                if ($code === '') continue;

                $norm = ctype_digit($code) ? ltrim($code, '0') : $code;
                if ($norm === '') continue;

                $rows[] = [$assoc, $norm];
                if (!isset($articlesNorm[$norm])) {
                    $articlesNorm[$norm] = true;
                    $articlesList[] = $norm;
                }

                $processed++;
            }

            if ($processed === 0) {
                $newOffset = $offset;
                self::writeLog("ID {$id} — ✅ [FILE] DONE (EOF) offset={$newOffset} total_all=" . ($totalAll ?? '-'), $id);
                return [
                    'done'        => true,
                    'offset'      => $newOffset,
                    'total_all'   => $totalAll ?? $newOffset,
                    'date_price'  => $date_price,
                    'date_update' => $date_update,
                ];
            }

            // ---- 5) Prefetch товаров батча (с текущими полями для compare) ----
            $productMap  = [];
            $productIds  = []; // для батча actions

            if (!empty($articlesList)) {
                $placeholders = implode(',', array_fill(0, count($articlesList), '?'));

                $sql = "
                    SELECT
                        id, article, alias, stock_status_id,
                        opt_price, price, quantity, rest, reserve,
                        wait, wait_date, data_edit_price, data_edit_all
                    FROM product
                    WHERE article IN ($placeholders)
                    OR (article REGEXP '^[0-9]+$' AND TRIM(LEADING '0' FROM article) IN ($placeholders))
                ";
                $params = array_merge($articlesList, $articlesList);

                $list = \R::getAll($sql, $params);
                foreach ($list as $p) {
                    $a = trim((string)($p['article'] ?? ''));
                    if ($a === '') continue;

                    $norm = ctype_digit($a) ? ltrim($a, '0') : $a;
                    if ($norm === '') continue;

                    $pid = (int)$p['id'];

                    $productMap[$norm] = [
                        'id'              => $pid,
                        'alias'           => (string)($p['alias'] ?? ''),
                        'stock_status_id' => (int)($p['stock_status_id'] ?? 0),

                        // текущие поля для сравнения (убираем getRow внутри updateProductFromFileFast)
                        'opt_price'       => (float)($p['opt_price'] ?? 0),
                        'price'           => (float)($p['price'] ?? 0),
                        'quantity'        => (int)($p['quantity'] ?? 0),
                        'rest'            => (int)($p['rest'] ?? 0),
                        'reserve'         => (int)($p['reserve'] ?? 0),
                        'wait'            => (string)($p['wait'] ?? ''),
                        'wait_date'       => (string)($p['wait_date'] ?? ''),
                        'data_edit_price' => (string)($p['data_edit_price'] ?? ''),
                        'data_edit_all'   => (string)($p['data_edit_all'] ?? ''),
                    ];

                    $productIds[$pid] = true;
                }
            }

            // ---- 5.1) Prefetch активных акций по product_id (батч) ----
            $hasActionMap = []; // productId => 1
            if (!empty($productIds)) {
                $ids = array_keys($productIds);
                $ph  = implode(',', array_fill(0, count($ids), '?'));

                $rowsAct = \R::getCol(
                    "SELECT DISTINCT product_id
                    FROM actions
                    WHERE product_id IN ($ph) AND date_end > ?",
                    array_merge($ids, [$date_update])
                );

                foreach ($rowsAct as $pid) {
                    $hasActionMap[(int)$pid] = 1;
                }
            }

            // ---- 6) Транзакция на батч ----
            $aliasesToPing = [];

            \R::begin();
            try {
                foreach ($rows as [$assoc, $norm]) {
                    $pRow = $productMap[$norm] ?? null;
                    if (!$pRow) {
                        self::logError("❌ [FILE] Товар не найден по артикулу: {$norm}", $id);
                        continue;
                    }

                    $pid = (int)($pRow['id'] ?? 0);
                    $hasAction = !empty($hasActionMap[$pid]);

                    // ВАЖНО: updateProductFromFileFast должен быть обновлён под новую сигнатуру:
                    // (fields, productRow(with current fields), hasAction(bool), date_price, date_update, cron_id)
                    $alias = self::updateProductFromFileFast($assoc, $pRow, $hasAction, $date_price, $date_update, $id);

                    if ($alias) {
                        $aliasesToPing[$alias] = true; // уникальные
                    }
                }

                \R::commit();
            } catch (\Throwable $e) {
                \R::rollback();
                throw $e;
            }

            $newOffset = $offset + $processed;

            $done = false;
            if ($totalAll !== null) {
                $done = ($newOffset >= $totalAll);
            }

            if ($done) {
                self::writeLog("ID {$id} — ✅ [FILE] DONE offset={$newOffset} total_all=" . ($totalAll ?? '-'), $id);
                return [
                    'done'        => true,
                    'offset'      => $newOffset,
                    'total_all'   => $totalAll ?? $newOffset,
                    'date_price'  => $date_price,
                    'date_update' => $date_update,
                ];
            }

            // 1 строка прогресса всегда
            self::writeLog("ID {$id} — ▶️ [FILE] Offset {$offset} -> {$newOffset} | batch={$processed}", $id);

            return [
                'next'      => true,
                'done'      => false,
                'offset'    => $newOffset,
                'total'     => $processed,
                'total_all' => $totalAll,
            ];
        } finally {
            @flock($lockFp, LOCK_UN);
            @fclose($lockFp);
        }
    }

    public static function downloadCronFile(int $cronId, ?string $targetFile = null): array
    {
        $cronId = (int)$cronId;
        if ($cronId <= 0) return ['ok' => false, 'error' => 'bad cronId'];

        // Берём URL ИЗ cron.alias (как ты и используешь в админке)
        $cron = \R::getRow("SELECT id, alias FROM cron WHERE id = ? LIMIT 1", [$cronId]);
        if (!$cron) return ['ok' => false, 'error' => "cron not found id={$cronId}"];

        $url = (string)($cron['alias'] ?? '');
        $url = trim($url);

        // часто копируют с пробелами/кавычками
        $url = trim($url, " \t\n\r\0\x0B\"'");

        if ($url === '') return ['ok' => false, 'error' => 'cron.alias is empty'];
        if (!preg_match('~^https?://~i', $url)) {
            return ['ok' => false, 'error' => "cron.alias must be http(s) url, got: {$url}"];
        }

        // Куда сохраняем
        $dir = WWW . '/cron';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $cacheFile = $targetFile ?: ($dir . '/cache_file_' . $cronId . '.csv');
        $tmpFile   = $cacheFile . '.tmp';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'ITS-CronDownloader/1.0',
            // если сервер иногда режет gzip — можно включить:
            CURLOPT_ENCODING => '',
        ]);

        $body  = curl_exec($ch);
        $errNo = curl_errno($ch);
        $err   = curl_error($ch);
        $code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errNo || $body === false) return ['ok' => false, 'error' => "curl {$errNo}: {$err}"];
        if ($code < 200 || $code >= 300) return ['ok' => false, 'error' => "http code {$code}"];

        $bytes = strlen($body);
        if ($bytes < 100) return ['ok' => false, 'error' => "download too small bytes={$bytes}"];

        if (@file_put_contents($tmpFile, $body, LOCK_EX) === false) {
            return ['ok' => false, 'error' => "cannot write tmp {$tmpFile}"];
        }
        @chmod($tmpFile, 0644);

        if (!@rename($tmpFile, $cacheFile)) {
            @unlink($tmpFile);
            return ['ok' => false, 'error' => "cannot rename tmp to {$cacheFile}"];
        }

        return [
            'ok'    => true,
            'path'  => $cacheFile,
            'bytes' => (int)@filesize($cacheFile),
            'mtime' => (int)@filemtime($cacheFile),
            'url'   => $url,
        ];
    }

}
