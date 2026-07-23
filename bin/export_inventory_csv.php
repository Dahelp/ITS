<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }
$root = dirname(__DIR__);
$_SERVER += ['HTTP_HOST' => 'its-center.ru', 'SERVER_NAME' => 'its-center.ru', 'HTTPS' => 'on', 'REQUEST_URI' => '/cron/cli', 'REMOTE_ADDR' => '127.0.0.1', 'DOCUMENT_ROOT' => $root . '/public'];
require $root . '/config/init.php';
require LIBS . '/functions.php';
require CONF . '/db_bootstrap.php';

$args = [];
foreach (array_slice($argv, 1) as $arg) if (preg_match('/^--([^=]+)=(.*)$/', $arg, $m)) $args[$m[1]] = $m[2];
$outputDir = (string)($args['output-dir'] ?? $root . '/public/xls/nalichie');
$directories = [$outputDir];
if (!empty($args['mirror-dir'])) $directories[] = (string)$args['mirror-dir'];

$lock = fopen(sys_get_temp_dir() . '/its_inventory_csv_export.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { fwrite(STDERR, "Already running\n"); exit(4); }
try {
    $stats = (new \app\services\InventoryCsvExportService())->export($directories);
    echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
