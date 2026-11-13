<?php
require dirname(__DIR__).'/config/init.php';require CONF.'/db_bootstrap.php';
require dirname(__DIR__).'/outputs/yandex-release/files/app/services/SpecialTyreFeedService.php';
if(PHP_OS_FAMILY!=='Windows')throw new RuntimeException('Local read-only test');
$db=R::getPDO();$fingerprint=static function()use($db){$out=[];foreach(['product','product_attribute','attribute_product','cron']as$t){$h=hash_init('sha256');foreach($db->query('SELECT * FROM '.$t)as$row)hash_update($h,json_encode($row));$out[$t]=hash_final($h);}return$out;};
$before=$fingerprint();$fileHashes=[];foreach(glob(WWW.'/cron/*.xml')as$f)$fileHashes[$f]=hash_file('sha256',$f);
$service=new app\services\SpecialTyreFeedService(WWW,'https://its-center.ru');$preview=$service->diagnose();$generated=$service->generate();
$included=count(array_filter($preview['rows'],static fn($r)=>$r['included']));
if($included!==$generated['offers']||$preview['offers']!==$generated['offers'])throw new RuntimeException('Preview/generator mismatch');
foreach($preview['rows']as$r)if(!$r['included']&&!$r['reasons'])throw new RuntimeException('Excluded product without reason');
$feeds=app\services\YandexProductsCabinet::snapshot(WWW);
$error=null;$q='';ob_start();include dirname(__DIR__).'/app/views/itscenter/admin/Plagins/yandex-tovars.php';$html=ob_get_clean();
if(!str_contains($html,'Предпросмотр фида спецшин')||!str_contains($html,'Найдено:'))throw new RuntimeException('Cabinet render');
if($before!==$fingerprint())throw new RuntimeException('Read-only diagnostics changed data');foreach($fileHashes as$f=>$hash)if(hash_file('sha256',$f)!==$hash)throw new RuntimeException('Preview wrote XML');
echo 'Cabinet DB: '.count($preview['rows']).' products, '.$included.' included; generator parity, rendered page, unchanged rows/files OK'.PHP_EOL;
