<?php
$cronId = (int)($_GET['id'] ?? 0);
$cron = \R::getRow('SELECT * FROM cron WHERE id = ?', [$cronId]);
if (!$cron || ($cron['url_params'] ?? '') !== 'ymlfid-specshiny') {
    throw new \RuntimeException('Special tyre feed cron task not found');
}
$fileName = (string)$cron['url_download'];
if (!preg_match('/^[a-zA-Z0-9_-]+\.xml$/D', $fileName)) {
    throw new \RuntimeException('Invalid feed filename');
}
$result = (new \app\services\SpecialTyreFeedService(WWW, PATH))->generate();
\app\services\SpecialTyreFeedService::writeAtomically(WWW . '/cron/' . $fileName, $result['xml']);
// The CLI runner records completion and history after this view returns.
if (PHP_SAPI === 'cli') {
    echo 'FEED file=' . $fileName . ' offers=' . $result['offers'] . ' skipped=' . $result['skipped'] . PHP_EOL;
    return;
}
$now = date('Y-m-d H:i:s');
\R::exec('UPDATE cron SET date_update = ? WHERE id = ?', [$now, $cronId]);
\app\services\admin\AdminActivityLogger::cron($cronId, true, $_SESSION['user']['id'] ?? null, $now);
$_SESSION['success'] = 'Фид спецшин обновлён: ' . $result['offers'] . ' товаров.';
redirect(PATH . '/admin/cron');
