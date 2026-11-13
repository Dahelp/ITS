<?php
if(PHP_SAPI!=='cli') exit;
require dirname(__DIR__).'/config/init.php'; require CONF.'/db_bootstrap.php';
$plan=json_decode(file_get_contents(ROOT.'/app/data/chain-seo-content.json'),true,512,JSON_THROW_ON_ERROR);
$pages=[];
foreach($plan['categories'] as $alias=>$row) $pages[]=['url'=>'https://its-center.ru/'.($alias==='cepi-protivoskolzheniya'?'catalog/':'category/').$alias,'h1'=>$row['h1'],'title'=>$row['title'],'description'=>$row['description'],'kind'=>'category'];
foreach($plan['products'] as $row) $pages[]=['url'=>'https://its-center.ru/product/'.$row['alias'],'h1'=>$row['rendered']['h1']??$row['rendered']['name'],'title'=>$row['rendered']['title'],'description'=>$row['rendered']['description'],'kind'=>'product'];
foreach($plan['landings'] as $row) $pages[]=['url'=>$row['canonical_url'],'h1'=>$row['seo_h1'],'title'=>$row['title'],'description'=>$row['description'],'kind'=>'filter'];
$errors=[];$checked=[];$titles=[];$descriptions=[];
foreach($pages as $expected) {
 $c=curl_init($expected['url']);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>35]);$html=curl_exec($c);$status=curl_getinfo($c,CURLINFO_HTTP_CODE);curl_close($c);
 if($status!==200) {$errors[]=$expected['url'].' HTTP '.$status;continue;}
 $d=new DOMDocument();@$d->loadHTML('<?xml encoding="UTF-8" ?>'.$html);$x=new DOMXPath($d);
 $title=trim($x->query('//title')->item(0)->textContent??'');
 $h1=trim(preg_replace('/\s+/u',' ',$x->query('//h1')->item(0)->textContent??''));
 $description=$x->query('//meta[@name="description"]')->item(0);$description=$description?$description->getAttribute('content'):'';
 $canonical=$x->query('//link[@rel="canonical"]')->item(0);$canonical=$canonical?$canonical->getAttribute('href'):'';
 foreach(['title'=>$title,'h1'=>$h1,'description'=>$description] as $field=>$actual) if($actual!==$expected[$field]) $errors[]=$expected['url'].' mismatch '.$field;
 if($canonical!==$expected['url']) $errors[]=$expected['url'].' canonical mismatch: '.$canonical;
 if($x->query('//h1')->length!==1) $errors[]=$expected['url'].' H1 count';
 if(strpos((string)$html,'Каталог шин ИТС-Центр')!==false || strpos((string)$html,'{chain_')!==false) $errors[]=$expected['url'].' generic or unresolved copy';
 if(preg_match('~(?:цеп[ьи]|ценпи)[\s\-–—_]*нн|tsep[-_]nn|Производитель цепей|Обозначение у производителя~iu',(string)$html)) $errors[]=$expected['url'].' supplier disclosed';
 if(strpos((string)$html,'под заказ')===false) $errors[]=$expected['url'].' order terms missing';
 if($expected['kind']==='product') {
    $body=$x->query('//div[@id="pills-opisanie"]')->item(0);
    if(!$body || mb_strlen(trim($body->textContent))<1400) $errors[]=$expected['url'].' missing detailed copy';
 }
 $titles[]=$title;$descriptions[]=$description;$checked[]=['url'=>$expected['url'],'title'=>$title,'description'=>$description,'h1'=>$h1,'canonical'=>$canonical,'status'=>$status];
}
if(count(array_unique($titles))!==count($titles)) $errors[]='Duplicate title';
if(count(array_unique($descriptions))!==count($descriptions)) $errors[]='Duplicate description';
foreach($plan['products'] as $row) {
 $p=R::getRow('SELECT title,description,keywords,seo_h1,stock_status_id,quantity,brand_id FROM product WHERE article=?',[$row['article']]);
 if($p['title']!=='' || $p['description']!=='' || $p['seo_h1']!==($plan['templates'][$row['group']]['h1']??'') || $p['keywords']!=='') $errors[]='InSEO overridden '.$row['article'];
 if((int)$p['stock_status_id']!==2 || (int)$p['quantity']!==0 || (int)$p['brand_id']!==0) $errors[]='Product business state '.$row['article'];
}
$report=['checked_pages'=>count($checked),'categories'=>3,'products'=>55,'filter_pages'=>30,'unique_titles'=>count(array_unique($titles)),'unique_descriptions'=>count(array_unique($descriptions)),'errors'=>$errors];
$dir=ROOT.'/storage/chain-seo-publication';if(!is_dir($dir))mkdir($dir,0775,true);
file_put_contents($dir.'/verification.json',json_encode(['summary'=>$report,'pages'=>$checked],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($errors?1:0);
