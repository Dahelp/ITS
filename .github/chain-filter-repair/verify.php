<?php
/** HTTP-only acceptance checks; does not bootstrap or write the database. */
if(PHP_SAPI!=='cli')exit(1);

$base='https://its-center.ru';$results=[];$errors=[];
function chainRepairHttp($url) {
    $ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>30,CURLOPT_HEADER=>true]);
    $response=curl_exec($ch);if($response===false)throw new RuntimeException(curl_error($ch));
    $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$length=(int)curl_getinfo($ch,CURLINFO_HEADER_SIZE);curl_close($ch);
    $headers=substr($response,0,$length);preg_match('/^Location:\s*(.+)$/mi',$headers,$m);
    return ['status'=>$status,'location'=>trim($m[1]??''),'html'=>substr($response,$length)];
}
function chainRepairAliases($html) {
    $dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    $aliases=[];foreach($dom->getElementsByTagName('a') as $a) {
        $path=parse_url($a->getAttribute('href'),PHP_URL_PATH);
        if(preg_match('~(?:^|/)product/(cepi-[^/?#]+)~',(string)$path,$m))$aliases[$m[1]]=true;
    }
    $aliases=array_keys($aliases);sort($aliases);return $aliases;
}
$expectedBySize=json_decode(file_get_contents(__DIR__.'/expected.json'),true,512,JSON_THROW_ON_ERROR);
foreach(['8.15-15'=>'8-15-15','28x9-15'=>'28x9-15','10-16.5'=>'10-16.5','12-16.5'=>'12-16.5','4.5-12'=>'chain-size-63ef430bfce5'] as $size=>$alias) {
    $category=in_array($size,['8.15-15','28x9-15'],true)?'cepi-dlya-vilochnyh-pogruzchikov':'cepi-dlya-spectehniki';
    $expected=$expectedBySize[$size];sort($expected);
    $url=$base.'/category/'.$category.'/'.$alias;$page=chainRepairHttp($url);
    $found=chainRepairAliases($page['html']);$ok=$page['status']===200&&$found===$expected;
    $results[]=['url'=>$url,'status'=>$page['status'],'expected_products'=>count($expected),'found_products'=>count($found),'ok'=>$ok];
    if(!$ok)$errors[]=$url;
    if($category==='cepi-dlya-spectehniki') {
        foreach(['','?sort=price_desc&perpage=48'] as $query) {
            $old=$base.'/category/cepi-dlya-vilochnyh-pogruzchikov/'.$alias.$query;
            $response=chainRepairHttp($old);$location=$response['location'];
            if(strpos($location,'/')===0)$location=$base.$location;
            $ok=$response['status']===301&&$location===$url.$query;
            $results[]=['url'=>$old,'status'=>$response['status'],'location'=>$location,'ok'=>$ok];
            if(!$ok)$errors[]=$old;
        }
    }
}
echo json_encode(['checks'=>$results,'errors'=>$errors],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($errors?1:0);
