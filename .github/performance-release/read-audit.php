<?php
if(PHP_SAPI!=='cli')exit(1);
$root=realpath($argv[1]??'');if(!$root)throw new RuntimeException('Invalid audit root');
@mkdir(__DIR__.'/reports',0700,true);
require $root.'/config/init.php';$cfg=require $root.'/config/config_db.php';
$db=new PDO($cfg['dsn'],$cfg['user'],$cfg['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
try{$db->exec('SET SESSION TRANSACTION READ ONLY');}catch(Throwable$ignored){}
$data=['at'=>gmdate('c'),'read_only'=>true,'php'=>PHP_VERSION,'redis_extension'=>extension_loaded('redis'),'redis_configured'=>getenv('ITS_REDIS_HOST')!==false,'memcached_extension'=>extension_loaded('memcached'),'opcache'=>function_exists('opcache_get_status')?boolval(opcache_get_status(false)):false,'indexes'=>[]];
foreach(['product_attribute','related_product','product','category','attribute_product','attribute_value','attribute_group','attribute_category','modification','url_alias','actions','plagins_banner']as$table){
 try{$data['indexes'][$table]=$db->query('SHOW INDEX FROM `'.$table.'`')->fetchAll();}catch(Throwable$e){$data['indexes'][$table]=['unavailable'=>true];}
}
file_put_contents(__DIR__.'/reports/database-audit.json',json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
$logs=['available'=>false,'googlebot_user_agent_verified'=>false,'sample_bytes_limit'=>1048576,'requests'=>0,'googlebot_requests'=>0,'googlebot_status'=>[]];
foreach(array_unique(array_merge(glob($root.'/logs/*access*')?:[],glob(dirname($root).'/logs/*access*')?:[]))as$log){
 if(!is_file($log)||!is_readable($log)||str_ends_with($log,'.gz'))continue;
 $handle=fopen($log,'rb');if(!$handle)continue;$logs['available']=true;
 if(filesize($log)>1048576){fseek($handle,-1048576,SEEK_END);fgets($handle);}
 while(($line=fgets($handle))!==false){$logs['requests']++;if(stripos($line,'Googlebot')!==false){$logs['googlebot_requests']++;if(preg_match('~"\s+(\d{3})\s+~',$line,$match)){$status=$match[1];$logs['googlebot_status'][$status]=($logs['googlebot_status'][$status]??0)+1;}}}
 fclose($handle);break;
}
file_put_contents(__DIR__.'/reports/crawler-log-summary.json',json_encode($logs,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
$paths=['/category/shiny-dlya-vilochnyh-pogruzchikov','/category/shiny-dlya-vilochnyh-pogruzchikov/6.50-10','/product/6-50-10-12pr-ttf-qh207-134a5-superguider-shina-dlya-vilochnogo-pogruzchika'];
foreach($paths as$i=>$url){
 $report=__DIR__.'/reports/profile-'.$i.'.json';
 $command=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/profile-worker.php').' '.escapeshellarg($root).' '.escapeshellarg($url).' '.escapeshellarg($report);
 exec($command.' 2>/dev/null',$ignored,$status);
 if(!is_file($report))file_put_contents($report,json_encode(['url'=>$url,'read_only'=>true,'profile_error'=>true,'exit'=>$status]));
}
echo "Read-only database/index/profile audit completed\n";
