<?php
if(PHP_SAPI!=='cli')exit(1);
$base=getenv('PERFORMANCE_BASE_URL')?:'http://its.local';
if(!in_array($base,['http://its.local','https://its-center.ru'],true))throw new RuntimeException('Unsupported site');
$category='/category/shiny-dlya-vilochnyh-pogruzchikov';$results=[];
foreach([
 [$category.'?page=2',200,$category.'?page=2',false],
 [$category.'?page=999999',404,null,true],
 [$category.'?page[]=2',404,null,true],
 [$category.'?page=2&sort=price_asc',200,$category,true],
 ['/catalog/shiny?page=2',200,'/catalog/shiny',true],
]as[$path,$status,$canonical,$noindex]){
 $ch=curl_init($base.$path);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>40,CURLOPT_FOLLOWLOCATION=>false]);
 if($base==='http://its.local'){curl_setopt($ch,CURLOPT_PROXY,'');curl_setopt($ch,CURLOPT_RESOLVE,['its.local:80:127.0.0.1']);}
 $html=curl_exec($ch);$actual=curl_getinfo($ch,CURLINFO_HTTP_CODE);if($html===false)throw new RuntimeException(curl_error($ch));curl_close($ch);
 if($actual!==$status)throw new RuntimeException('Unexpected status '.$actual.' for '.$path);
 if($canonical!==null){preg_match('~<link rel="canonical" href="([^"]+)"~',$html,$match);$href=html_entity_decode($match[1]??'');if($href!==$base.$canonical)throw new RuntimeException('Canonical mismatch '.$href.' for '.$path);preg_match('~<meta name="robots" content="([^"]+)"~',$html,$robots);if(str_contains($robots[1]??'','noindex')!==$noindex)throw new RuntimeException('Robots mismatch '.$path);}
 $results[]=['path'=>$path,'status'=>$actual,'canonical'=>$canonical,'noindex'=>$noindex];
}
echo 'SEO crawl: self canonical pagination, bounded pages, noindex sort and non-paginated catalog passed'.PHP_EOL;
if(getenv('PERFORMANCE_SEO_REPORT_PATH'))file_put_contents(getenv('PERFORMANCE_SEO_REPORT_PATH'),json_encode($results,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
