<?php
namespace app\services;

/** Reads assigned IDs only; never creates, republishes or changes remote ads. */
final class AvitoIdSync
{
    private const PENDING = "(avito_id IS NULL OR avito_id = '' OR avito_id = '0') AND ad_external_id IS NOT NULL AND TRIM(ad_external_id) != ''";

    public static function synchronize(?AvitoApiClient $client = null): array
    {
        $rows = \R::getAll('SELECT id, ad_external_id FROM avito_ad WHERE '.self::PENDING.' ORDER BY id');
        $stats = ['assigned'=>0, 'pending'=>0, 'checked'=>0, 'errors'=>[], 'checked_at'=>time()];
        $byExternal = [];
        foreach ($rows as $row) $byExternal[(string)$row['ad_external_id']][] = (int)$row['id'];
        if ($rows) $client ??= new AvitoApiClient();
        foreach (array_chunk(array_map('strval', array_keys($byExternal)), 50) as $chunk) {
            try {
                $response = $client->api('GET', '/autoload/v2/items/avito_ids?query='.rawurlencode(implode('|', $chunk)), ['rate_limit_retries'=>0]);
                if (!isset($response['items']) || !is_array($response['items'])) throw new \RuntimeException('Invalid mapping response');
                $stats['checked'] += count($chunk);
                foreach ($response['items'] as $item) {
                    if (!is_array($item)) continue;
                    $external = (string)($item['ad_id'] ?? '');
                    $id = (string)($item['avito_id'] ?? '');
                    // Null means Avito has not assigned an ID yet. Never fabricate one.
                    if (!in_array($external, $chunk, true) || !ctype_digit($id) || (int)$id <= 0) continue;
                    foreach ($byExternal[$external] as $localId) {
                        $stats['assigned'] += (int)\R::exec("UPDATE avito_ad SET avito_id = ? WHERE id = ? AND (avito_id IS NULL OR avito_id = '' OR avito_id = '0')", [$id, $localId]);
                    }
                }
            } catch (\Throwable $e) {
                preg_match('/HTTP\s+(\d{3})/i', $e->getMessage(), $http);
                $stats['errors'][] = 'Не удалось получить номера объявлений из Авито'.(isset($http[1]) ? ' (HTTP '.$http[1].')' : '').'.';
                break;
            }
        }
        $stats['pending'] = (int)\R::getCell('SELECT COUNT(*) FROM avito_ad WHERE '.self::PENDING);
        return $stats;
    }

    /** Shared one-minute throttle prevents simultaneous admin tabs from flooding the API. */
    public static function refreshPending(bool $force = false, ?AvitoApiClient $client = null): array
    {
        $cache = \ishop\Cache::instance();
        if (!$force && is_array($cached = $cache->get('avito-id-sync-v1'))) return $cached;
        $lock = fopen(CACHE.'/avito-id-sync.lock', 'c');
        if (!$lock) throw new \RuntimeException('Не удалось запустить проверку номеров Авито.');
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            return ['busy'=>true, 'assigned'=>0, 'errors'=>[]];
        }
        try {
            if (!$force && is_array($cached = $cache->get('avito-id-sync-v1'))) return $cached;
            $stats = self::synchronize($client);
            $cache->set('avito-id-sync-v1', $stats, 60);
            return $stats;
        } finally {
            flock($lock, LOCK_UN); fclose($lock);
        }
    }
}
