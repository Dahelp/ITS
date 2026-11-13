<?php
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/outputs/stage9-release/files/app/services/InventorySyncService.php';
use app\services\CatalogTaskStatus as S;
function statusCheck(bool $yes,string $message):void{if(!$yes)throw new RuntimeException($message);}
$dir=sys_get_temp_dir().'/catalog-task-test-'.bin2hex(random_bytes(6));$service=new S($dir);$id=random_int(100000000,999999999);$lock=null;
try{
 statusCheck($service->observed($id)['state']==='unknown'&&!is_dir($dir),'GET does not create storage');
 $run=$service->start($id,'refresh-tovars-server');$path=S::lockPath($id,'refresh-tovars-server');$lock=fopen($path,'c');flock($lock,LOCK_EX|LOCK_NB);statusCheck($service->observed($id)['state']==='running','Held shared lock');
 $other=fopen($path,'c');statusCheck(!flock($other,LOCK_EX|LOCK_NB),'Concurrent run cannot acquire inventory lock');fclose($other);
 flock($lock,LOCK_UN);fclose($lock);$lock=null;statusCheck($service->observed($id)['state']==='interrupted','Abrupt exit detected without writing during GET');
 $service->finish($id,$run,'success',['updated'=>0,'api'=>10,'token'=>'secret','xml'=>'private']);$first=$service->read($id);statusCheck($first['state']==='success'&&!isset($first['stats']['token'])&&!isset($first['stats']['xml']),'Whitelisted receipt');statusCheck(!empty($first['last_success_at']),'No-change success recorded');
 $shadow=$service->start($id,'refresh-tovars-server','shadow');$service->finish($id,$run,'error');statusCheck($service->read($id)['run_id']===$shadow&&$service->read($id)['state']==='running','Stale finisher cannot overwrite a later run');$service->finish($id,$shadow,'success');statusCheck($service->read($id)['last_success_at']===$first['last_success_at'],'Shadow does not claim a new live sync');
 $failed=$service->start($id,'refresh-tovars-server');$service->finish($id,$failed,'partial',['db_fallback'=>2]);statusCheck($service->read($id)['last_success_at']===$first['last_success_at'],'Partial data preserves last full success');
 $base=['quantity'=>16,'inventory_quantity'=>21,'inventory_rest'=>21,'reserve'=>8,'price'=>100,'opt_price'=>90,'spec_price'=>80,'wait'=>'','wait_date'=>''];$data=['rest'=>21,'reserve'=>8,'price_rozn'=>100,'price_opt'=>90,'price_spec'=>80,'wait'=>'','wait_date'=>''];
 statusCheck(!app\services\InventorySyncService::productNeedsUpdate($base,$data),'Permanent stock adjustment does not cause double subtraction');
 foreach(['price_spec'=>81,'wait'=>'5','wait_date'=>'2026-12-01','rest'=>20,'reserve'=>7,'price_rozn'=>101,'price_opt'=>91]as$key=>$value)statusCheck(app\services\InventorySyncService::productNeedsUpdate($base,array_replace($data,[$key=>$value])),'Standalone source change: '.$key);
 statusCheck(!app\services\InventorySyncService::productNeedsUpdate(array_replace($base,['wait'=>null,'wait_date'=>'2026-12-01']),array_replace($data,['wait'=>0,'wait_date'=>'2026-12-01T00:00:00'])),'Integer wait/null and DATE normalization prevent perpetual updates');
 echo "Task receipts/locks: success without changes, shadow/live distinction, partial/error persistence, stale run guard; inventory special price and waiting fields OK\n";
}finally{if($lock){flock($lock,LOCK_UN);fclose($lock);}if(isset($path)&&is_file($path))unlink($path);if(is_dir($dir)){foreach(glob($dir.'/*')as$f)unlink($f);rmdir($dir);}}
