<?php
declare(strict_types=1);if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__);require$root.'/vendor/autoload.php';$command=$argv[1]??'help';
if(!in_array($command,['install','create','run','rollback','status'],true)){echo "install|create plan.json|run batch-id|rollback batch-id|status batch-id\nMutations require --apply; server mutations also --allow-production. --actor=ID required for create/rollback; run supports --limit=N.\n";exit;}
$apply=in_array('--apply',$argv,true);if($command!=='status'&&(!$apply||(PHP_OS_FAMILY!=='Windows'&&!in_array('--allow-production',$argv,true))))throw new RuntimeException('Explicit --apply and server authorization required.');
$options=[];foreach($argv as $arg){if(preg_match('/^--(actor|limit)=(\d+)$/D',$arg,$m))$options[$m[1]]=(int)$m[2];elseif(str_starts_with($arg,'--')&&!in_array($arg,['--apply','--allow-production'],true))throw new RuntimeException('Unknown option: '.$arg);}
$c=require$root.'/config/config_db.php';$db=new PDO($c['dsn'],$c['user'],$c['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$actor=$options['actor']??0;
if(in_array($command,['create','rollback'],true)){$q=$db->prepare('SELECT role FROM user WHERE id=?');$q->execute([$actor]);if($q->fetchColumn()!=='admin')throw new RuntimeException('Actor must be an existing administrator.');}
$migration=new \app\services\CatalogMigration($db);
if($command==='install'){\app\services\CatalogMigration::install($db);echo "Migration journal installed. No product records changed.\n";exit;}
if($command==='create'){$plan=json_decode(file_get_contents($argv[2]??''),true,512,JSON_THROW_ON_ERROR);$batch=$migration->create($plan,$actor);echo json_encode($migration->status($batch),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),"\n";exit;}
$id=$argv[2]??'';if(!preg_match('/^[1-9]\d{0,9}$/D',$id))throw new RuntimeException('Valid batch ID required.');
$r=match($command){'run'=>$migration->run((int)$id,$options['limit']??0),'rollback'=>$migration->rollback((int)$id,$actor),default=>$migration->status((int)$id)};
echo json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),"\n";
