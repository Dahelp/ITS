<?php
namespace app\services;

/** Autoload removes absent ads; a stock update alone does not archive an ad. */
class CompleteAvitoPublication
{
    public static function ensureSchema(): void
    {
        \R::exec("CREATE TABLE IF NOT EXISTS avito_complete_publication (
            ad_id INT UNSIGNED NOT NULL PRIMARY KEY,
            state_hash CHAR(64) NOT NULL,
            data_sent_hash CHAR(64) NULL,
            confirmed_hash CHAR(64) NULL,
            requested_at DATETIME NULL,
            next_attempt_at DATETIME NULL,
            remote_status VARCHAR(32) NULL,
            last_error TEXT NULL,
            checked_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function fingerprint(array $ad): string
    {
        return hash('sha256', json_encode([
            (int)$ad['complete_id'], (string)$ad['avito_id'],
            CompleteAvito::canPublish($ad), (int)$ad['stock_quantity'], (int)$ad['price_rub'],
        ]));
    }

    /** Transport step, separated from persistence so failure/recovery paths can be tested. */
    public static function reconcile(array $ad, array $state, AvitoApiClient $client, int $accountId, int $now): array
    {
        $hash = self::fingerprint($ad);
        if (($state['state_hash'] ?? '') !== $hash) {
            $state = ['state_hash'=>$hash, 'data_sent_hash'=>null, 'confirmed_hash'=>null,
                'requested_at'=>null, 'next_attempt_at'=>null];
        }
        $state += ['remote_status'=>null, 'last_error'=>null];
        $state['needs_upload'] = false;
        if (!empty($state['next_attempt_at']) && strtotime($state['next_attempt_at']) > $now) { return $state; }
        // Periodically verify even a completed request, without posting data again.
        if (($state['confirmed_hash'] ?? '') === $hash && !empty($state['checked_at'])
            && strtotime($state['checked_at']) > $now - 1800) { return $state; }
        $state['last_error'] = null;
        $state['checked_at'] = date('Y-m-d H:i:s', $now);
        $state['next_attempt_at'] = null;
        $active = CompleteAvito::canPublish($ad);
        $itemId = (int)$ad['avito_id'];
        if ($itemId <= 0) {
            if (!$active) {
                // A new archived draft has never been submitted and has nothing to remove.
                $state['remote_status'] = 'unpublished';
                $state['confirmed_hash'] = $hash;
            } else {
                $state['last_error'] = 'Нет числового ID объявления Авито';
            }
            return $state;
        }
        try {
            $remote = $client->api('GET', '/core/v1/accounts/'.$accountId.'/items/'.$itemId.'/', ['rate_limit_retries'=>0]);
            $state['remote_status'] = (string)($remote['status'] ?? '');
        } catch (\Throwable $e) {
            $state['remote_status'] = null;
            $state['last_error'] = 'Проверка публикации: '.$e->getMessage();
        }
        $remoteMatches = $active ? $state['remote_status'] === 'active'
            : in_array($state['remote_status'], ['old', 'removed'], true);
        if ($active && ($state['data_sent_hash'] ?? '') !== $hash) {
            try {
                $price = $client->api('POST', '/core/v1/items/'.$itemId.'/update_price',
                    ['json'=>['price'=>(int)$ad['price_rub']], 'rate_limit_retries'=>0]);
                if (empty($price['result']['success'])) { throw new \RuntimeException('Цена не подтверждена'); }
                $stock = $client->api('PUT', '/stock-management/1/stocks',
                    ['json'=>['stocks'=>[['item_id'=>$itemId, 'quantity'=>(int)$ad['stock_quantity']]]], 'rate_limit_retries'=>0]);
                $ok = false;
                foreach (($stock['stocks'] ?? []) as $row) {
                    if ((int)($row['item_id'] ?? 0) === $itemId && !empty($row['success'])) { $ok = true; }
                }
                if (!$ok) { throw new \RuntimeException('Остаток не подтверждён'); }
                $state['data_sent_hash'] = $hash;
            } catch (\Throwable $e) {
                $state['last_error'] = trim(($state['last_error'] ?? '').' Данные: '.$e->getMessage());
            }
        }
        if ($remoteMatches && (!$active || ($state['data_sent_hash'] ?? '') === $hash)) {
            $state['confirmed_hash'] = $hash;
            $state['requested_at'] = null;
            return $state;
        }
        // A completed state must become pending again if Avito disagrees with it.
        $state['confirmed_hash'] = null;
        if (!$remoteMatches && (empty($state['requested_at']) || strtotime($state['requested_at']) <= $now - 1800)) {
            $state['needs_upload'] = true;
        }
        if ($state['last_error']) { $state['next_attempt_at'] = date('Y-m-d H:i:s', $now + 300); }
        return $state;
    }

    public static function run(?AvitoApiClient $client = null): array
    {
        $stats = ['checked'=>0, 'confirmed'=>0, 'pending'=>0, 'autoload_requested'=>false, 'errors'=>[]];
        // Inventory CLI does not bootstrap the web registry.
        if (!\ishop\App::$app) {
            \ishop\App::$app = \ishop\Registry::instance();
            foreach ((require CONF.'/params.php') as $k=>$v) { \ishop\App::$app->setProperty($k,$v); }
        }
        CompleteAvito::ensureSchema();
        self::ensureSchema();
        CompleteAvito::sync();
        if (!(int)\R::getCell("SELECT GET_LOCK('its_avito_complete_publication', 0)")) {
            return $stats + ['busy'=>true];
        }
        try {
            $ads = \R::getAll('SELECT * FROM avito_ad WHERE complete_id IS NOT NULL');
            if (!$ads) { return $stats; }
            $client = $client ?: new AvitoApiClient();
            $account = $client->api('GET', '/core/v1/accounts/self', ['rate_limit_retries'=>0]);
            $accountId = (int)($account['id'] ?? 0);
            if ($accountId <= 0) { throw new \RuntimeException('Не удалось определить аккаунт Авито'); }
            $upload = [];
            foreach ($ads as $ad) {
                $state = \R::getRow('SELECT * FROM avito_complete_publication WHERE ad_id = ?', [$ad['id']]);
                $state = self::reconcile($ad, $state, $client, $accountId, time());
                self::store((int)$ad['id'], $state);
                $stats['checked']++;
                if (($state['confirmed_hash'] ?? '') === $state['state_hash']) { $stats['confirmed']++; }
                else { $stats['pending']++; }
                if (!empty($state['last_error'])) { $stats['errors'][] = '#'.$ad['avito_id'].': '.$state['last_error']; }
                if ($state['needs_upload']) { $upload[(int)$ad['id']] = $state['state_hash']; }
            }
            if ($upload) {
                try {
                    $profile = $client->api('GET', '/autoload/v2/profile', ['rate_limit_retries'=>0]);
                    $configured = false;
                    foreach (($profile['feeds_data'] ?? []) as $feed) {
                        if (rtrim((string)($feed['feed_url'] ?? ''), '/') === 'https://its-center.ru/avito/refresh-tovars') { $configured = true; }
                    }
                    if (empty($profile['autoload_enabled']) || !$configured) {
                        throw new \RuntimeException('Автозагрузка не подключена к файлу сайта');
                    }
                    $client->api('POST', '/autoload/v1/upload', ['rate_limit_retries'=>0]);
                    $stats['autoload_requested'] = true;
                    foreach ($upload as $adId=>$hash) {
                        \R::exec('UPDATE avito_complete_publication SET requested_at = NOW() WHERE ad_id = ? AND state_hash = ?', [$adId,$hash]);
                    }
                } catch (\Throwable $e) {
                    $stats['errors'][] = 'Автозагрузка: '.$e->getMessage();
                    foreach ($upload as $adId=>$hash) {
                        \R::exec('UPDATE avito_complete_publication SET last_error = ?, next_attempt_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE ad_id = ? AND state_hash = ?', [$e->getMessage(),$adId,$hash]);
                    }
                }
            }
        } catch (\Throwable $e) {
            $stats['errors'][] = $e->getMessage();
        } finally {
            \R::getCell("SELECT RELEASE_LOCK('its_avito_complete_publication')");
        }
        return $stats;
    }

    private static function store(int $id, array $state): void
    {
        \R::exec('INSERT INTO avito_complete_publication
            (ad_id,state_hash,data_sent_hash,confirmed_hash,requested_at,next_attempt_at,remote_status,last_error,checked_at)
            VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE state_hash=VALUES(state_hash),
            data_sent_hash=VALUES(data_sent_hash),confirmed_hash=VALUES(confirmed_hash),requested_at=VALUES(requested_at),
            next_attempt_at=VALUES(next_attempt_at),remote_status=VALUES(remote_status),last_error=VALUES(last_error),checked_at=VALUES(checked_at)',
            [$id,$state['state_hash'],$state['data_sent_hash'] ?? null,$state['confirmed_hash'] ?? null,
             $state['requested_at'] ?? null,$state['next_attempt_at'] ?? null,$state['remote_status'] ?? null,
             $state['last_error'] ?? null,$state['checked_at'] ?? null]);
    }
}
