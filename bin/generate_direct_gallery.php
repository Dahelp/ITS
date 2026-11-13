<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root = dirname(__DIR__);
$_SERVER += ['HTTP_HOST'=>'its-center.ru','SERVER_NAME'=>'its-center.ru','HTTPS'=>'on','PHP_SELF'=>'/index.php'];
require $root.'/config/init.php';
require LIBS.'/functions.php';
require CONF.'/db_bootstrap.php';
$lock=fopen(sys_get_temp_dir().'/direct-gallery-715980319.lock','c');
if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) exit(1);
$result=\app\services\DirectGallerySelection::generate();
foreach ($result as $key=>$feed) {
    \app\services\SpecialTyreFeedService::writeAtomically(WWW.'/cron/direct-715980319-'.$key.'.xml',$feed['xml']);
    echo json_encode(['group'=>$key,'offers'=>$feed['offers'],'skipped'=>$feed['skipped'],'skus'=>$feed['skus']],JSON_UNESCAPED_UNICODE).PHP_EOL;
}
