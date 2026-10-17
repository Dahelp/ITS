<?php
if(PHP_SAPI!=='cli') { http_response_code(403);exit; }
require dirname(__DIR__).'/config/init.php';
$config=new \app\services\AvitoPushConfig();
if(!$config->ready()) { echo "Avito push: disabled (Firebase configuration required)\n";exit(0); }
try {
    require CONF.'/db_bootstrap.php';
    $sender=new \app\services\FcmPushSender($config->credentials());
    $stats=(new \app\services\AvitoPushQueue($config->directory().'/spool'))->run($sender);
    echo json_encode(['push'=>$stats],JSON_THROW_ON_ERROR).PHP_EOL;
} catch(\Throwable $e) {
    // Never log private keys, device tokens, callback URLs or upstream payloads.
    fwrite(STDERR,"Avito push worker failed. Check private configuration and storage.\n");exit(1);
}
