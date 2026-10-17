<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/config/init.php';
require CONF.'/db_bootstrap.php';
try {
    \app\services\AvitoMessenger::ensureSchema();
    \app\services\AvitoChatWorkspace::ensureSchema();
    echo "Avito workspace schema: OK\n";
} catch (\Throwable $e) {
    fwrite(STDERR,"Avito workspace schema setup failed.\n"); exit(1);
}
