<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }
$root = dirname(__DIR__);
$_SERVER += ['HTTP_HOST' => 'its-center.ru', 'SERVER_NAME' => 'its-center.ru', 'HTTPS' => 'on', 'REQUEST_URI' => '/cron/cli', 'REMOTE_ADDR' => '127.0.0.1', 'DOCUMENT_ROOT' => $root . '/public'];
require $root . '/config/init.php'; require LIBS . '/functions.php'; require CONF . '/db_bootstrap.php';
$args = []; foreach (array_slice($argv, 1) as $arg) if (preg_match('/^--([^=]+)=(.*)$/', $arg, $m)) $args[$m[1]] = $m[2];
$id = (int)($args['id'] ?? 0); $categories = (string)($args['categories'] ?? ''); $mode = (string)($args['mode'] ?? 'shadow');
if ($id < 1 || $categories === '') { fwrite(STDERR, "Usage: php bin/sync_inventory_api.php --id=36 --categories=9,18 --mode=shadow|canary|live [--canary-percent=5] [--limit=100] [--article=11141]\n"); exit(2); }
$lock = fopen(\app\services\CatalogTaskStatus::lockPath($id,'refresh-tovars-server'), 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { fwrite(STDERR, "Already running\n"); exit(4); }
$statusStore=new \app\services\CatalogTaskStatus(ROOT.'/storage/catalog-task-status');$statusRun=$statusStore->start($id,'refresh-tovars-server',$mode);
register_shutdown_function(static function()use($statusStore,$statusRun,$id):void {try{if(($statusStore->read($id)['state']??'')==='running')$statusStore->finish($id,$statusRun,'interrupted');}catch(\Throwable $ignored){}});
try { $stats = (new \app\services\InventorySyncService())->run($id, $categories, $mode, (int)($args['canary-percent'] ?? 5), (int)($args['limit'] ?? 0), (string)($args['article'] ?? '')); $noFresh=($stats['api']+$stats['cache']+$stats['modifications_api']+$stats['modifications_cache'])===0&&($stats['seen']+$stats['modifications_seen'])>0; $requireMissing=($args['require-api']??'0')==='1'&&(int)($stats['api']??0)===0; $statusStore->finish($id,$statusRun,$noFresh?'error':((($stats['db_fallback']??0)>0||$requireMissing)?'partial':'success'),$stats); if($noFresh)exit(5); echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL; if (($args['require-api'] ?? '0') === '1' && (int)($stats['api'] ?? 0) === 0) exit(5); }
catch (Throwable $e) { try{$statusStore->finish($id,$statusRun,'error',is_array($stats??null)?$stats:[]);}catch(\Throwable $ignored){} fwrite(STDERR, $e->getMessage() . PHP_EOL); exit(1); }
finally { flock($lock, LOCK_UN); fclose($lock); }
