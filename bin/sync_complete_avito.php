<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/config/init.php';
require CONF.'/db_bootstrap.php';
$stats = \app\services\CompleteAvitoPublication::run();
echo json_encode($stats, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
exit(empty($stats['errors']) ? 0 : 1);
