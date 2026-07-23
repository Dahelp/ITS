<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }
$root = dirname(__DIR__);
$_SERVER += ['HTTP_HOST' => 'its-center.ru', 'SERVER_NAME' => 'its-center.ru', 'HTTPS' => 'on', 'REQUEST_URI' => '/cron/audit', 'REMOTE_ADDR' => '127.0.0.1', 'DOCUMENT_ROOT' => $root . '/public'];
require $root . '/config/init.php';
require LIBS . '/functions.php';
require CONF . '/db_bootstrap.php';

$rows = \R::getAll('SELECT id, name, url_params, categories, hide, date_update FROM cron ORDER BY id');
$viewsDir = APP . '/views/' . TEMPLATE . '/Cron';
$result = [];
foreach ($rows as $row) {
    $task = trim((string)$row['url_params']);
    $handler = $task === 'refresh-tovars-server'
        ? 'inventory_api'
        : (is_file($viewsDir . '/' . $task . '.php') ? 'view' : 'missing');
    $result[] = [
        'id' => (int)$row['id'],
        'name' => (string)$row['name'],
        'task' => $task,
        'categories' => (string)($row['categories'] ?? ''),
        'active' => ($row['hide'] ?? '') === 'show',
        'last_update' => $row['date_update'],
        'handler' => $handler,
    ];
}

echo json_encode([
    'generated_at' => date('c'),
    'count' => count($result),
    'inventory_api_ids' => array_values(array_map(
        static fn(array $row): int => $row['id'],
        array_filter($result, static fn(array $row): bool => $row['handler'] === 'inventory_api')
    )),
    'missing_handler_ids' => array_values(array_map(
        static fn(array $row): int => $row['id'],
        array_filter($result, static fn(array $row): bool => $row['handler'] === 'missing')
    )),
    'tasks' => $result,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
