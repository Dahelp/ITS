<?php
if(PHP_SAPI!=='cli')exit(1);
$root='/home/s/shinaspec/its-center.ru/public_html';
require $root.'/config/init.php';require CONF.'/db_bootstrap.php';
use app\services\CatalogFacets as F;
use app\services\CatalogSelection as S;
$db=R::getPDO();$fingerprint=static function()use($db){$out=[];foreach(['attribute_product','attribute_value_category_canonical','catalog_category_display','catalog_facet_binding','catalog_facet_value_map']as$t){$h=hash_init('sha256');foreach($db->query('SELECT * FROM '.$t)as$row)hash_update($h,json_encode($row));$out[$t]=hash_final($h);}return$out;};$before=$fingerprint();
$category=R::getRow("SELECT id,alias FROM category WHERE alias='shiny-dlya-minipogruzchikov'");
$base='https://its-center.ru/category/'.$category['alias'];$id=(int)$category['id'];
$ids=static function(string $alias,int $group):int {return(int)R::getCell("SELECT id FROM attribute_value WHERE alias=? AND attr_group_id=? AND hide='show'",[$alias,$group]);};
$brand=$ids('ekka',4);$size=$ids('12-16.5',2);$other=$ids('10-16.5',2);if(!$brand||!$size||!$other)throw new RuntimeException('Fixture aliases not present');
$cases=[
 ['category',$base,[],[],true],
 ['brand',$base.'/ekka',[$brand],[],true],
 ['brand-and-size',$base.'/ekka?filter='.$size,[$brand,$size],[],false],
 ['brand-and-two-sizes',$base.'/ekka?filter='.$size.','.$other,[$brand,$size,$other],[],false],
 ['direct-OR-redirect',$base.'/12-16.5?filter='.$other,[$size,$other],[],false],
 ['in-stock',$base.'?in_stock=1',[],['in_stock'=>'1'],false],
 ['price-sort',$base.'/ekka?sort=price_asc',[$brand],['sort'=>'price_asc'],false],
 ['unknown-id',$base.'?filter=99999999',[99999999],[],false],
 // Preserve the site's existing pagination policy: noindex, canonical first page.
 ['page-two',$base.'?page=2',[],[],false],
 ['unknown-alias',$base.'/stage7-nonexistent-filter',[],[],false,404],
];
$maps=R::getAll("SELECT s.id,s.category_id,s.attr_value_id,c.alias AS category_alias,av.alias AS filter_alias,s.seo_h1,s.canonical_url,s.robots FROM attribute_value_category_canonical s JOIN category c ON c.id=s.category_id JOIN attribute_value av ON av.id=s.attr_value_id WHERE s.is_active=1 AND s.mode='landing' AND c.hide='show' AND av.hide='show' ORDER BY (c.id=$id) DESC,s.id");
$relation=app\services\CatalogFacetBridge::relationSql();$edges=R::getAll("SELECT DISTINCT ap.attr_id,p.category_id FROM $relation ap JOIN product p ON p.id=ap.product_id WHERE p.hide='show'");$productsByAttribute=[];foreach($edges as$edge)$productsByAttribute[(int)$edge['attr_id']][(int)$edge['category_id']]=true;
$tree=[];$children=[];foreach(R::getAll('SELECT id,parent_id FROM category')as$c)$children[(int)$c['parent_id']][]=(int)$c['id'];$descendants=static function(int $cid,array $seen=[])use(&$descendants,$children):array {if(isset($seen[$cid]))return[];$seen[$cid]=true;$out=[$cid];foreach($children[$cid]??[]as$child)$out=array_merge($out,$descendants($child,$seen));return$out;};$stale=[];$landings=[];$sampledCategories=[];
foreach($maps as$map){$cid=(int)$map['category_id'];if(!isset($tree[$cid]))$tree[$cid]=$descendants($cid);$exists=false;foreach($tree[$cid]as$child)if(isset($productsByAttribute[(int)$map['attr_value_id']][$child])){$exists=true;break;}if(!$exists){$stale[]=['id'=>(int)$map['id'],'url'=>'https://its-center.ru/category/'.$map['category_alias'].'/'.$map['filter_alias'],'reason'=>'Нет видимых товаров с этим значением фильтра в категории'];continue;}if(count($landings)<10&&!isset($sampledCategories[$cid])){$landings[]=$map;$sampledCategories[$cid]=true;}}
foreach($landings as$i=>$l)$cases[]=['SEO-landing-'.($i+1),'https://its-center.ru/category/'.$l['category_alias'].'/'.$l['filter_alias'],null,[],true,200,$l];
$results=[];$failed=[];
foreach($cases as$c){
 [$name,$url,$filter,$query,$index]=$c;$summary=null;$curl=curl_init($url);curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>4,CURLOPT_TIMEOUT=>20,CURLOPT_USERAGENT=>'ITS-Center-Stage7-QA/1.0']);$html=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$effective=curl_getinfo($curl,CURLINFO_EFFECTIVE_URL);$redirects=curl_getinfo($curl,CURLINFO_REDIRECT_COUNT);curl_close($curl);
 $doc=new DOMDocument();$old=libxml_use_internal_errors(true);$doc->loadHTML('<?xml encoding="UTF-8">'.($html?:''));libxml_clear_errors();libxml_use_internal_errors($old);$xp=new DOMXPath($doc);
 $text=static fn($q)=>trim((string)$xp->evaluate('string('.$q.')'));
 $h1=$text('//h1');$canonical=$text('//link[@rel="canonical"]/@href');$robots=$text('//meta[@name="robots"]/@content');$title=$text('//title');$description=$text('//meta[@name="description"]/@content');$errors=[];
 if($status!==($c[5]??200))$errors[]='HTTP '.$status;
 if($status===200){
  if($xp->query('//h1')->length!==1||!$h1||!$title||!$description||!$canonical)$errors[]='Missing/duplicate SEO fields';
  if(!$index&&!str_contains($robots,'noindex'))$errors[]='Refined URL must be noindex';
  if($index&&str_contains($robots,'noindex')&&empty($c[6]['robots']))$errors[]='Unexpected noindex';
  $summary=$text('//*[contains(concat(" ",normalize-space(@class)," ")," catalog-controls__summary ")]');
  if($filter!==null){$where=F::predicate($filter).' '.S::where(S::state($query));$expected=(int)R::getCell("SELECT COUNT(*) FROM product WHERE hide='show' AND category_id=$id $where");if(!preg_match('/Найдено:\s*(\d+)/u',$summary,$m)||(int)$m[1]!==$expected)$errors[]='Result count mismatch';}
  $clean=strtok($effective,'?');$expectedCanonical=$c[6]['canonical_url']??$clean;if(!$expectedCanonical)$expectedCanonical=$clean;
  if($name==='page-two')$expectedCanonical=$base;
  if($canonical!==$expectedCanonical)$errors[]='Canonical mismatch';
  if(!empty($c[6]['seo_h1'])&&trim(html_entity_decode(strip_tags($c[6]['seo_h1']),ENT_QUOTES|ENT_HTML5,'UTF-8'))!==$h1)$errors[]='Custom H1 mismatch';
  if($name==='direct-OR-redirect'&&(!$redirects||strtok($effective,'?')!==$base))$errors[]='Ambiguous size landing was not redirected';
 }
 $results[]=['name'=>$name,'url'=>$url,'http'=>$status,'effective_url'=>$effective,'redirects'=>$redirects,'h1'=>$h1,'title'=>$title,'description'=>$description,'canonical'=>$canonical,'robots'=>$robots,'summary'=>$summary??null,'errors'=>$errors];if($errors)$failed[]=$name;
}
$config=['total'=>(int)R::getCell('SELECT COUNT(*) FROM attribute_value_category_canonical'),'active'=>(int)R::getCell('SELECT COUNT(*) FROM attribute_value_category_canonical WHERE is_active=1'),'orphan_references'=>(int)R::getCell('SELECT COUNT(*) FROM attribute_value_category_canonical s LEFT JOIN category c ON c.id=s.category_id LEFT JOIN attribute_value a ON a.id=s.attr_value_id WHERE c.id IS NULL OR a.id IS NULL')];
$out=['checked_at'=>date(DATE_ATOM),'cases'=>$results,'seo_config'=>$config,'landing_maps_without_visible_products'=>$stale,'configuration_unchanged'=>$before===$fingerprint(),'failed'=>$failed];echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;exit($failed?1:0);
