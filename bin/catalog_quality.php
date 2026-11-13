<?php
declare(strict_types=1);if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/vendor/autoload.php';
$path=$argv[1]??'';$out=$argv[2]??'';
if(!is_file($path)||$out===''){fwrite(STDERR,"Usage: catalog_quality.php audit-snapshot.json report.json\nNo database connection or product writes.\n");exit(1);}
$start=microtime(true);$snapshot=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
$report=\app\services\CatalogQuality::fromSnapshot($snapshot);$report['categories']=$snapshot['category']??[];
file_put_contents($out,json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
echo json_encode(['summary'=>$report['summary'],'dictionary_values'=>count($report['dictionary_review']),'seconds'=>round(microtime(true)-$start,2),'source_generated_at'=>$report['source_generated_at']],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),"\n";
