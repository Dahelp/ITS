<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }
$root = dirname(__DIR__);
$_SERVER += ['HTTP_HOST' => 'its-center.ru', 'SERVER_NAME' => 'its-center.ru', 'HTTPS' => 'on', 'REQUEST_URI' => '/cron/cli', 'REMOTE_ADDR' => '127.0.0.1', 'DOCUMENT_ROOT' => $root . '/public'];
require $root . '/config/init.php'; require LIBS . '/functions.php'; require CONF . '/db_bootstrap.php';
$args = []; foreach (array_slice($argv, 1) as $arg) if (preg_match('/^--([^=]+)=(.*)$/', $arg, $m)) $args[$m[1]] = $m[2];
$id = (int)($args['id'] ?? 0); $categories = (string)($args['categories'] ?? ''); $mode = (string)($args['mode'] ?? 'shadow');
if ($id < 1 || $categories === '') { fwrite(STDERR, "Usage: php bin/sync_inventory_api.php --id=36 --categories=9,18 --mode=shadow|canary|live [--canary-percent=5] [--limit=100] [--article=11141]\n"); exit(2); }
$lock = fopen(sys_get_temp_dir() . '/its_inventory_api_' . $id . '.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { fwrite(STDERR, "Already running\n"); exit(4); }
try { $stats = (new \app\services\InventorySyncService())->run($id, $categories, $mode, (int)($args['canary-percent'] ?? 5), (int)($args['limit'] ?? 0), (string)($args['article'] ?? '')); echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL; if (($args['require-api'] ?? '0') === '1' && (int)($stats['api'] ?? 0) === 0) exit(5); }
catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . PHP_EOL); exit(1); }
finally { flock($lock, LOCK_UN); fclose($lock); }
