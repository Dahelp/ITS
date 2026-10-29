<?php
require dirname(__DIR__) . '/config/init.php';
require CONF . '/db_bootstrap.php';

try {
    $stats = \app\services\CompleteAvitoLinker::reconcile();
    echo 'Complete Avito links: ' . json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (\Throwable $e) {
    fwrite(STDERR, 'Complete Avito link error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
