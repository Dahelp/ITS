<?php
if(PHP_SAPI!=='cli') {http_response_code(403);exit;}
require dirname(__DIR__).'/config/init.php';
$config=new \app\services\AvitoPushConfig();
try {
    $config->setup();
    if(!$config->ready()) {echo "Avito push: disabled; Firebase credentials required.\n";exit(0);}
    $config->credentials();
    echo "Avito push configuration: OK. Worker: php8.2 bin/send_avito_push.php\n";
    echo "Schedule the worker separately after Firebase setup and delivery testing.\n";
} catch(Throwable $e) {fwrite(STDERR,"Avito push setup failed. Check private Firebase configuration.\n");exit(1);}
