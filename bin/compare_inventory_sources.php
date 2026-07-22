<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("CLI only\n");
$root = dirname(__DIR__); $_SERVER += ['HTTP_HOST'=>'its-center.ru','SERVER_NAME'=>'its-center.ru','HTTPS'=>'on','REQUEST_URI'=>'/cron/cli','REMOTE_ADDR'=>'127.0.0.1','DOCUMENT_ROOT'=>$root.'/public'];
require $root.'/config/init.php'; require LIBS.'/functions.php'; require CONF.'/db_bootstrap.php';
$args=[]; foreach(array_slice($argv,1) as $arg) if(preg_match('/^--([^=]+)=(.*)$/',$arg,$m)) $args[$m[1]]=$m[2];
$file=(string)($args['file']??$root.'/public/cron/cache_file.csv'); $limit=max(1,min(10000,(int)($args['limit']??100)));
if(!is_file($file)){fwrite(STDERR,"FTP/CSV snapshot not found: $file\n");exit(2);}
$handle=fopen($file,'rb'); $headers=fgetcsv($handle,0,';'); $ftp=[];
while(($row=fgetcsv($handle,0,';'))!==false){$code=\app\services\InventoryApiClient::normalizeArticle((string)($row[0]??''));if($code==='')continue;$ftp[$code]=['rest'=>(int)($row[3]??0),'reserve'=>(int)($row[4]??0)];} fclose($handle);
$products=\R::getAll('SELECT id, article, quantity FROM product WHERE article IS NOT NULL AND article <> ? ORDER BY id LIMIT '.$limit,['']);
$client=new \app\services\InventoryApiClient(); $rows=[]; $summary=['compared'=>0,'api_errors'=>0,'ftp_missing'=>0,'db_api_diff'=>0,'ftp_api_diff'=>0];
foreach($products as $product){$code=\app\services\InventoryApiClient::normalizeArticle((string)$product['article']);$api=$client->fetch($code,false);$apiQty=!empty($api['ok'])?(int)$api['data']['rest']+(int)$api['data']['reserve']:null;$ftpQty=isset($ftp[$code])?$ftp[$code]['rest']+$ftp[$code]['reserve']:null;$dbQty=(int)$product['quantity'];$summary['compared']++;if($apiQty===null)$summary['api_errors']++;if($ftpQty===null)$summary['ftp_missing']++;if($apiQty!==null&&$apiQty!==$dbQty)$summary['db_api_diff']++;if($apiQty!==null&&$ftpQty!==null&&$apiQty!==$ftpQty)$summary['ftp_api_diff']++;$rows[]=['article'=>$code,'ftp'=>$ftpQty,'db'=>$dbQty,'api'=>$apiQty,'api_source'=>$api['source']??'error'];}
echo json_encode(['generated_at'=>date('c'),'file'=>$file,'summary'=>$summary,'rows'=>$rows],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
if (($args['require-api'] ?? '0') === '1' && $summary['api_errors'] >= $summary['compared']) exit(5);
