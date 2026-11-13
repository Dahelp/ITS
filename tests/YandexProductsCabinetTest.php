<?php
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/outputs/yandex-release/files/app/services/SpecialTyreFeedService.php';
use app\services\YandexProductsCabinet;
use app\services\SpecialTyreFeedService;
function cabinetCheck(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);}
$dir=sys_get_temp_dir().'/yandex-cabinet-'.bin2hex(random_bytes(6));mkdir($dir);
try {
 cabinetCheck(YandexProductsCabinet::fileState($dir,'missing.xml')['status']==='Файл отсутствует','Missing feed');
 cabinetCheck(YandexProductsCabinet::fileState($dir,'../escape.xml')['status']==='Недопустимое имя файла','Path traversal');
 file_put_contents($dir.'/feed.xml','<yml_catalog date="2026-11-13 12:00"><shop><offers><offer id="one"/><offer id="two"/></offers></shop></yml_catalog>');
 $s=YandexProductsCabinet::fileState($dir,'feed.xml');cabinetCheck($s['offers']===2&&$s['status']==='XML читается'&&$s['generated']==='2026-11-13 12:00','Actual published file metadata');
 file_put_contents($dir.'/feed.xml','<broken>');cabinetCheck(YandexProductsCabinet::fileState($dir,'feed.xml')['status']==='Ошибка структуры XML','Malformed XML');
 file_put_contents($dir.'/feed.xml','<!DOCTYPE x [<!ENTITY x SYSTEM "file:///etc/passwd">]><yml_catalog>&x;</yml_catalog>');cabinetCheck(YandexProductsCabinet::fileState($dir,'feed.xml')['status']==='Ошибка структуры XML','External entities blocked');
 $service=new SpecialTyreFeedService($dir,'https://example.test');
 $offer=['sku'=>'SKU','name'=>'Tyre','alias'=>'tyre','category_id'=>9,'feed_stock'=>1,'feed_price'=>10,'hide'=>'show','stock_status_id'=>1,'brand_exists'=>1];
 cabinetCheck($service->exclusionReasons($offer,[9=>true],'https://example.test/photo.png')===[],'Eligible offer');
 foreach(['feed_stock'=>0,'feed_price'=>0,'sku'=>'','name'=>'','alias'=>'','hide'=>'hide','stock_status_id'=>2,'brand_exists'=>null,'category_id'=>2]as$key=>$value)cabinetCheck(count($service->exclusionReasons(array_replace($offer,[$key=>$value]),[9=>true],'photo'))===1,'One precise exclusion: '.$key);
 cabinetCheck(count($service->exclusionReasons($offer,[9=>true],null))===1,'Photo rule');
 cabinetCheck(count($service->exclusionReasons(array_replace($offer,['feed_price'=>0,'feed_stock'=>0]),[9=>true],null))===3,'All concurrent reasons');
 echo "Yandex cabinet: file state, XML safety, shared exclusion rules passed\n";
} finally {foreach(glob($dir.'/*')as$f)unlink($f);rmdir($dir);}
