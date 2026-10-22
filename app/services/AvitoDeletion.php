<?php
namespace app\services;

/** Finalizes only an existing user deletion request after a fresh remote confirmation. */
final class AvitoDeletion
{
    public static function reconcile(?AvitoApiClient $client = null): array
    {
        $rows = \R::getAll('SELECT * FROM avito_ad WHERE deletion_requested_at IS NOT NULL ORDER BY id');
        $stats = ['deleted'=>0,'deletions_pending'=>count($rows),'errors'=>[]];
        if (!$rows) return $stats;
        \R::exec("UPDATE avito_ad SET status = 'archived' WHERE deletion_requested_at IS NOT NULL");
        $client ??= new AvitoApiClient();
        try {
            $account = $client->api('GET','/core/v1/accounts/self',['rate_limit_retries'=>0]);
            if (empty($account['id'])) throw new \RuntimeException('Missing account');
        } catch (\Throwable $e) {
            $stats['errors'][] = 'Не удалось проверить снятие объявлений с Авито. Записи сохранены.';
            return $stats;
        }
        foreach ($rows as $row) {
            $itemId = \app\models\admin\Avito::numericAvitoItemId($row);
            if ($itemId <= 0) continue;
            try {
                $remote = $client->api('GET','/core/v1/accounts/'.(int)$account['id'].'/items/'.$itemId.'/', ['rate_limit_retries'=>0]);
                $status = strtolower(trim((string)($remote['status'] ?? '')));
                if ($status === '') throw new \RuntimeException('Missing status');
                \R::exec('UPDATE avito_ad SET avito_publication_status = ?, avito_status_updated_at = CURRENT_TIMESTAMP WHERE id = ? AND deletion_requested_at IS NOT NULL',[$status,$row['id']]);
                if (!\app\models\admin\Avito::deletionIsConfirmed($status)) continue;
                // A private recovery snapshot also makes unattended completion auditable.
                $dir = ROOT.'/tmp/avito-deleted';
                if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) throw new \RuntimeException('Backup directory');
                $file = $dir.'/'.(int)$row['id'].'-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.snapshot';
                if (file_put_contents($file,serialize($row),LOCK_EX) === false) throw new \RuntimeException('Backup failed');
                chmod($file,0600);
                \R::begin();
                try {
                    $deleted = \R::exec('DELETE FROM avito_ad WHERE id = ? AND deletion_requested_at = ?',[$row['id'],$row['deletion_requested_at']]);
                    if ($deleted && in_array('avito_complete_publication',\R::inspect(),true)) {
                        \R::exec('DELETE FROM avito_complete_publication WHERE ad_id = ?',[$row['id']]);
                    }
                    \R::commit();
                    $stats['deleted'] += (int)$deleted;
                } catch (\Throwable $e) { \R::rollback(); throw $e; }
            } catch (\Throwable $e) {
                $stats['errors'][] = 'Не удалось завершить удаление объявления №'.(int)$row['id'].'. Запись сохранена.';
            }
        }
        $stats['deletions_pending'] = (int)\R::getCell('SELECT COUNT(*) FROM avito_ad WHERE deletion_requested_at IS NOT NULL');
        return $stats;
    }
}
