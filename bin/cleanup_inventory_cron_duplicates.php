<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }
$root = dirname(__DIR__);
$_SERVER += ['HTTP_HOST' => 'its-center.ru', 'SERVER_NAME' => 'its-center.ru', 'HTTPS' => 'on', 'REQUEST_URI' => '/cron/cleanup', 'REMOTE_ADDR' => '127.0.0.1', 'DOCUMENT_ROOT' => $root . '/public'];
require $root . '/config/init.php';
require LIBS . '/functions.php';
require CONF . '/db_bootstrap.php';

$keepId = 36;
$removeIds = [3, 5, 38, 39];
$keep = \R::getRow('SELECT id, url_params FROM cron WHERE id = ? LIMIT 1', [$keepId]);
if (!$keep || ($keep['url_params'] ?? '') !== 'refresh-tovars-server') {
    fwrite(STDERR, "Canonical inventory cron #{$keepId} is missing or invalid\n");
    exit(2);
}

$placeholders = implode(',', array_fill(0, count($removeIds), '?'));
$rows = \R::getAll("SELECT id, name, url_params, categories, alias, url_download FROM cron WHERE id IN ({$placeholders}) ORDER BY id", $removeIds);
foreach ($rows as $row) {
    if (($row['url_params'] ?? '') !== 'refresh-tovars-server') {
        fwrite(STDERR, "Refusing to delete unexpected cron #{$row['id']}\n");
        exit(3);
    }
}

\R::begin();
try {
    $deleted = \R::exec("DELETE FROM cron WHERE id IN ({$placeholders})", $removeIds);
    \R::commit();
} catch (Throwable $e) {
    \R::rollback();
    throw $e;
}

echo json_encode([
    'canonical_id' => $keepId,
    'requested_ids' => $removeIds,
    'found_before_delete' => $rows,
    'deleted' => (int)$deleted,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
