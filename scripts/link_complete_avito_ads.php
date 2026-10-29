<?php
require dirname(__DIR__) . '/config/init.php';

$stats = \app\services\CompleteAvitoLinker::reconcile();
echo 'Complete Avito links: ' . json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
