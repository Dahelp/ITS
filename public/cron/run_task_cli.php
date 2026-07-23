<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$root = dirname(__DIR__, 2);
$public = $root . '/public';
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

ini_set('error_log', $logDir . '/cron_cli_fatal.log');

function cron_cli_arg(array $argv, string $name, ?string $default = null): ?string
{
    $prefix = '--' . $name . '=';
    foreach ($argv as $arg) {
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
    }
    return $default;
}

function cron_cli_log(string $message): void
{
    @file_put_contents(__DIR__ . '/logs/cron_cli.log', date('c') . ' | ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$id = (int)(cron_cli_arg($argv ?? [], 'id', '0'));
$limit = (int)(cron_cli_arg($argv ?? [], 'limit', '20'));
$pauseMs = (int)(cron_cli_arg($argv ?? [], 'pause-ms', '250'));
$maxSeconds = (int)(cron_cli_arg($argv ?? [], 'max-seconds', '0'));

if ($id <= 0) {
    fwrite(STDERR, "Usage: php run_task_cli.php --id=36 [--limit=20] [--pause-ms=250] [--max-seconds=0]\n");
    exit(2);
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'its-center.ru';
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'its-center.ru';
$_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? 'on';
$_SERVER['REQUEST_SCHEME'] = $_SERVER['REQUEST_SCHEME'] ?? 'https';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/cron/cli';
$_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF'] ?? '/cron/run_task_cli.php';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SERVER['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'] ?? $public;
$_GET['id'] = $id;
$_SESSION['user']['id'] = 0;

foreach (($argv ?? []) as $arg) {
    if (strpos($arg, '--') !== 0 || strpos($arg, '=') === false) {
        continue;
    }

    [$rawName, $value] = explode('=', substr($arg, 2), 2);
    $name = str_replace('-', '_', trim($rawName));

    if ($name !== '' && !in_array($name, ['id', 'limit', 'pause_ms', 'max_seconds'], true)) {
        $_GET[$name] = $value;
    }
}

require_once $root . '/config/init.php';
require_once LIBS . '/functions.php';
require_once CONF . '/db_bootstrap.php';

chdir($public);

$cron = \R::getRow('SELECT * FROM cron WHERE id = ? LIMIT 1', [$id]);
if (!$cron) {
    fwrite(STDERR, "Cron task not found: {$id}\n");
    exit(3);
}

$task = trim((string)($cron['url_params'] ?? ''));
$categories = trim((string)(cron_cli_arg($argv ?? [], 'categories', (string)($cron['categories'] ?? ''))));
$limit = max(1, min(100, $limit));
$pauseMs = max(0, min(5000, $pauseMs));

$lockPath = sys_get_temp_dir() . '/its_cron_task_' . $id . '.lock';
$lock = @fopen($lockPath, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Task {$id} is already running\n");
    exit(4);
}

$startedAt = time();
cron_cli_log("START id={$id} task={$task}");
fwrite(STDOUT, "START id={$id} task={$task}\n");

try {
    if ($task === 'refresh-tovars-server') {
        $stats = (new \app\services\InventorySyncService())->run($id, '*', 'live');
        $exportDirectories = [ROOT . '/public/xls/nalichie'];
        $mirrorDirectory = trim((string)getenv('INVENTORY_CSV_MIRROR_DIR'));
        if ($mirrorDirectory !== '') $exportDirectories[] = $mirrorDirectory;
        $stats['csv_exports'] = (new \app\services\InventoryCsvExportService())->export($exportDirectories);
        $json = json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        fwrite(STDOUT, $json . PHP_EOL);
        cron_cli_log("DONE_API id={$id} stats={$json}");
        exit(0);
    }

    if ($task === '') {
        throw new RuntimeException('url_params is empty');
    }

    $safeTask = basename(str_replace('\\', '/', $task));
    if ($safeTask !== $task || !preg_match('~^[a-z0-9][a-z0-9\-_]*$~i', $safeTask)) {
        throw new RuntimeException('bad task name: ' . $task);
    }

    $view = APP . '/views/' . TEMPLATE . '/Cron/' . $safeTask . '.php';
    if (!is_file($view)) {
        throw new RuntimeException('cron view not found: ' . $safeTask . '.php');
    }

    require $view;

    $now = date('Y-m-d H:i:s');
    \R::exec('UPDATE cron SET date_update = ? WHERE id = ?', [$now, $id]);
    \app\services\admin\AdminActivityLogger::cron($id, false, null, $now);

    fwrite(STDOUT, "DONE id={$id} task={$task}\n");
    cron_cli_log("DONE id={$id} task={$task}");
    exit(0);
} catch (Throwable $e) {
    cron_cli_log("ERROR id={$id} task={$task} " . $e->getMessage());
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
} finally {
    if ($lock) {
        @flock($lock, LOCK_UN);
        @fclose($lock);
    }
}
