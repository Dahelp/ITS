<?php
declare(strict_types=1);if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/vendor/autoload.php';
if(count($argv)!==4){fwrite(STDERR,"Usage: catalog_migration_plan.php audit.json quality.json plan.json\n");exit(1);}
$s=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);$q=json_decode(file_get_contents($argv[2]),true,512,JSON_THROW_ON_ERROR);
$ids=array_column(array_filter($q['items'],static fn($r)=>$r['source']==='saved'),'id');
$plan=\app\services\CatalogMigrationPlan::build($s,$ids);file_put_contents($argv[3],json_encode($plan,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));echo json_encode($plan['counts']),"\n";
