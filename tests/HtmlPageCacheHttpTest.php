<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$base=getenv('PERFORMANCE_BASE_URL')?:'http://its.local';
if(!in_array($base,['http://its.local','https://its-center.ru'],true))throw new RuntimeException('Unsupported smoke target');
$report=[];$jar=tempnam(sys_get_temp_dir(),'page-cache-');
function cacheCheck($ok,$message){if(!$ok)throw new RuntimeException($message);}
function pageRequest(string $path,array $extra=[],bool $session=false,string $method='GET',array $post=[]):array{
 global $base,$jar,$report;$headers=[];$ch=curl_init($base.$path);
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>40,CURLOPT_HTTPHEADER=>$extra,CURLOPT_FOLLOWLOCATION=>false,
  CURLOPT_HEADERFUNCTION=>static function($ch,$line)use(&$headers){$p=explode(':',$line,2);if(count($p)===2)$headers[strtolower(trim($p[0]))]=trim($p[1]);return strlen($line);}]);
 if($base==='http://its.local'){curl_setopt($ch,CURLOPT_PROXY,'');curl_setopt($ch,CURLOPT_RESOLVE,['its.local:80:127.0.0.1']);}
 if($session){curl_setopt($ch,CURLOPT_COOKIEFILE,$jar);curl_setopt($ch,CURLOPT_COOKIEJAR,$jar);}
 if($method==='HEAD')curl_setopt($ch,CURLOPT_NOBODY,true);
 if($method==='POST'){curl_setopt($ch,CURLOPT_POST,true);curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($post));}
 $body=curl_exec($ch);if($body===false)throw new RuntimeException(curl_error($ch));
 $status=curl_getinfo($ch,CURLINFO_HTTP_CODE);$time=curl_getinfo($ch,CURLINFO_STARTTRANSFER_TIME);curl_close($ch);
 $report[]=['path'=>$path,'method'=>$method,'status'=>$status,'cache'=>$headers['x-page-cache']??null,'ttfb_s'=>$time,'sets_cookie'=>isset($headers['set-cookie'])];
 return compact('status','headers','body','time');
}
try{
 $paths=['/catalog/shiny','/category/shiny-dlya-vilochnyh-pogruzchikov','/category/shiny-dlya-vilochnyh-pogruzchikov/6.50-10'];
 if($base==='https://its-center.ru')$paths[]='/product/6-50-10-12pr-ttf-qh207-134a5-superguider-shina-dlya-vilochnogo-pogruzchika';
 foreach($paths as$path){
  $first=pageRequest($path);cacheCheck($first['status']===200,'canonical page works: '.$path);
  $next=pageRequest($path);
  cacheCheck(($next['headers']['x-page-cache']??'')==='HIT','warm page is served before DB: '.$path);
  cacheCheck($next['status']===200&&empty($next['headers']['set-cookie']),'HIT has no visitor cookie');
  cacheCheck(!str_contains($next['headers']['cache-control']??'','no-store'),'HIT remains public/revalidatable');
  cacheCheck(hash('sha256',$first['body'])===hash('sha256',$next['body']),'cache preserves complete HTML byte-for-byte');
 }
 $r=pageRequest('/catalog/shiny',[],false,'HEAD');cacheCheck(($r['headers']['x-page-cache']??'')==='HIT'&&$r['body']==='','HEAD reuses representation without body');
 $r=pageRequest('/catalog/shiny',['Cookie: its_traffic_source=metadata; cookie_accepted=true']);cacheCheck(($r['headers']['x-page-cache']??'')==='HIT','analytics do not create personal cache variants');
 foreach([['Cookie: recentlyViewed=1'],['Cookie: currency=RUB'],['Cookie: private_state=1'],['X-Requested-With: XMLHttpRequest'],['Cache-Control: no-cache']]as$headers){$r=pageRequest('/catalog/shiny',$headers);cacheCheck(($r['headers']['x-page-cache']??'')==='BYPASS','personal/AJAX/revalidation bypass');}
 $r=pageRequest('/catalog/shiny?sort=price');cacheCheck(($r['headers']['x-page-cache']??'')==='BYPASS','query variations bypass rather than reuse wrong page');
 $r=pageRequest('/catalog/shiny?lead_form=1');cacheCheck(($r['headers']['x-page-cache']??'')==='BYPASS'&&str_contains($r['headers']['cache-control']??'','private'),'no-JS form setup never enters shared cache');
 pageRequest('/session/lead-token',['X-Requested-With: XMLHttpRequest','Origin: '.$base],true,'POST',['scope'=>'callback']);
 $r=pageRequest('/catalog/shiny',[],true);cacheCheck(($r['headers']['x-page-cache']??'')==='BYPASS'&&str_contains($r['headers']['cache-control']??'','no-store'),'real session never receives guest HIT');
 foreach([1,2]as$_){$r=pageRequest('/category/page-cache-nonexistent-test');cacheCheck($r['status']===404&&($r['headers']['x-page-cache']??'')!=='HIT','404 is never stored');}
 echo "HTML cache HTTP: canonical HITs, exact HTML, HEAD, cookies, AJAX, query/CSRF/session isolation and uncached errors OK\n";
}finally{
 @unlink($jar);
 if(getenv('PERFORMANCE_CACHE_REPORT_PATH'))file_put_contents(getenv('PERFORMANCE_CACHE_REPORT_PATH'),json_encode(['at'=>gmdate('c'),'base'=>$base,'requests'=>$report],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}
