<?php
if(PHP_SAPI!=='cli')exit(1);
$root=realpath($argv[1]??'');if(!$root)throw new RuntimeException('Invalid audit root');
@mkdir(__DIR__.'/reports',0700,true);
require $root.'/config/init.php';$cfg=require $root.'/config/config_db.php';
$db=new PDO($cfg['dsn'],$cfg['user'],$cfg['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
try{$db->exec('SET SESSION TRANSACTION READ ONLY');}catch(Throwable$ignored){}
$data=['at'=>gmdate('c'),'read_only'=>true,'php'=>PHP_VERSION,'redis_extension'=>extension_loaded('redis'),'redis_configured'=>getenv('ITS_REDIS_HOST')!==false,'memcached_extension'=>extension_loaded('memcached'),'opcache'=>function_exists('opcache_get_status')?boolval(opcache_get_status(false)):false,'indexes'=>[]];
foreach(['product','category','attribute_product','attribute_value','attribute_group','attribute_category','modification','url_alias','actions','plagins_banner']as$table){
 try{$data['indexes'][$table]=$db->query('SHOW INDEX FROM `'.$table.'`')->fetchAll();}catch(Throwable$e){$data['indexes'][$table]=['unavailable'=>true];}
}
file_put_contents(__DIR__.'/reports/database-audit.json',json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
$paths=['/category/shiny-dlya-vilochnyh-pogruzchikov','/category/shiny-dlya-vilochnyh-pogruzchikov/6.50-10','/product/6-50-10-12pr-ttf-qh207-134a5-superguider-shina-dlya-vilochnogo-pogruzchika'];
foreach($paths as$i=>$url){
 $report=__DIR__.'/reports/profile-'.$i.'.json';
 $command=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/profile-worker.php').' '.escapeshellarg($root).' '.escapeshellarg($url).' '.escapeshellarg($report);
 exec($command.' 2>/dev/null',$ignored,$status);
 if(!is_file($report))file_put_contents($report,json_encode(['url'=>$url,'read_only'=>true,'profile_error'=>true,'exit'=>$status]));
}
echo "Read-only database/index/profile audit completed\n";
