<?php
if(PHP_SAPI!=='cli')exit(1);
$root='/home/s/shinaspec/its-center.ru/public_html';$package=__DIR__;
require $root.'/config/init.php';require CONF.'/db_bootstrap.php';require_once LIBS.'/functions.php';
$db=R::getPDO();$tables=[];
foreach(['product','product_attribute','attribute_product','cron','catalog_product_profile','catalog_product_property','catalog_product_size','catalog_name_rule','catalog_name_override']as$t){$h=hash_init('sha256');$n=0;foreach($db->query('SELECT * FROM '.$t)as$row){hash_update($h,json_encode($row,JSON_THROW_ON_ERROR));$n++;}$tables[$t]=['rows'=>$n,'hash'=>hash_final($h)];}
$xmlFiles=[];foreach(glob(WWW.'/cron/*.xml')as$f)$xmlFiles[basename($f)]=hash_file('sha256',$f);
$service=new app\services\SpecialTyreFeedService(WWW,'https://its-center.ru');$feed=$service->generate();
$canonical=preg_replace('/<yml_catalog date="[^"]*">/','<yml_catalog>',$feed['xml']);
$state=['tables'=>$tables,'files'=>$xmlFiles,'offers'=>$feed['offers'],'generated_hash'=>hash('sha256',$canonical)];
if(($argv[1]??'')==='before'){
 file_put_contents($package.'/before.json',json_encode($state,JSON_PRETTY_PRINT));echo 'Baseline: '.$feed['offers']." offers; XML preview only\n";exit;
}
$before=json_decode(file_get_contents($package.'/before.json'),true,512,JSON_THROW_ON_ERROR);
if($before['files']!==$state['files']||$before['generated_hash']!==$state['generated_hash'])throw new RuntimeException('Published files or generated feed changed');
$changed=[];foreach($tables as$t=>$v)if($v!==$before['tables'][$t])$changed[]=$t;
$preview=$service->diagnose();$count=count(array_filter($preview['rows'],static fn($r)=>$r['included']));if($count!==$feed['offers'])throw new RuntimeException('Preview parity');
foreach($preview['rows']as$row)if(!$row['included']&&!$row['reasons'])throw new RuntimeException('Missing exclusion reason');
$feeds=app\services\YandexProductsCabinet::snapshot(WWW);
$html=(static function($feeds,$preview){$error=null;ob_start();include APP.'/views/itscenter/admin/Plagins/yandex-tovars.php';return ob_get_clean();})($feeds,$preview);
if(!str_contains($html,'Предпросмотр фида спецшин')||!str_contains($html,'Найдено:'))throw new RuntimeException('View render failed');
foreach(json_decode(file_get_contents($package.'/manifest.json'),true)as$p=>$v)if(hash_file('sha256',$root.'/'.$p)!==$v['after'])throw new RuntimeException('Installed hash mismatch');
foreach($tables as$t=>$v){$h=hash_init('sha256');$n=0;foreach($db->query('SELECT * FROM '.$t)as$row){hash_update($h,json_encode($row,JSON_THROW_ON_ERROR));$n++;}if(['rows'=>$n,'hash'=>hash_final($h)]!==$v)throw new RuntimeException('Data changed during diagnostics: '.$t);}
$out=['verified_at'=>date(DATE_ATOM),'products_checked'=>count($preview['rows']),'offers'=>$count,'excluded'=>count($preview['rows'])-$count,'reasons'=>$preview['counts'],'feeds'=>$feeds,'diagnostics_read_only'=>true,'source_tables_changed_between_checks'=>$changed,'published_files_unchanged'=>true,'generated_xml_unchanged'=>true,'server_view_rendered'=>true,'receipt'=>json_decode(file_get_contents($package.'/receipt.json'),true)];
file_put_contents($package.'/verification.json',json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
