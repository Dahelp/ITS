<?php
if(PHP_SAPI!=='cli')exit(1);
require dirname(__DIR__,2).'/app/services/HtmlPageCache.php';
$cache=new \app\services\HtmlPageCache($argv[1]);
if(($argv[2]??'')==='purge'){$cache->invalidate();exit;}
$server=['REQUEST_METHOD'=>'GET','HTTP_HOST'=>'its.local','REQUEST_URI'=>'/category/concurrency-test'];
http_response_code(200);
if($cache->handle($server,[]))exit;
file_put_contents($argv[1].'/renders.txt',"render\n",FILE_APPEND|LOCK_EX);
usleep(350000);
echo '<!doctype html><html><head><title>Cache concurrency</title></head><body>one complete render</body></html>';
if(($argv[2]??'')==='clean'){
    ob_end_clean();
    echo '<!doctype html><html><head><title>Discarded</title></head><body>replacement response</body></html>';
}
