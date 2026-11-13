<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CatalogMigrationSource as S;use app\services\CatalogMigrationPlan as P;
function sourceCheck($ok,$message){if(!$ok)throw new RuntimeException($message);}
$p=['id'=>'1','sku'=>'S','name'=>'Шина','model'=>'M','category_id'=>'18','brand_id'=>'1','brand_name'=>'EKKA'];$a=[['id'=>'2','attribute_id'=>'8','attribute_text'=>'EKKA'],['id'=>'1','attribute_id'=>'61','attribute_text'=>'цельнолитые']];$f=[['link_id'=>'3','attr_id'=>'4','attr_group_id'=>'2','value'=>'6.50-10']];
$s=S::canonical($p,$a,$f);$reversed=S::canonical(array_replace($p,['id'=>1,'category_id'=>18,'brand_id'=>1,'quantity'=>100]),array_reverse($a),$f);sourceCheck(S::fingerprint($s)===S::fingerprint($reversed),'Stable types/order; stock is outside characteristic source');
$changed=$s;$changed['filters'][0]['value']='7.00-12';sourceCheck(S::fingerprint($s)!==S::fingerprint($changed),'Filter source changes detected');
$unknown=$s;$unknown['attributes'][]=['id'=>10,'attribute_id'=>999,'attribute_text'=>'Uncovered'];sourceCheck(count(S::preview($unknown)['unmapped'])>0,'Uncovered source cannot silently disappear');
$brand=$s;$brand['product']['brand_name']='Another';sourceCheck(count(S::preview($brand)['unmapped'])>0,'Brand disagreement held');
$legacy=S::preview($s);$legacy['proposed']['type']='filter';sourceCheck(P::decide($legacy,false)['decision']==='hold','Unprepared profile held');
echo "Canonical source: stable ordering/types, filter change, uncovered attribute, brand mismatch and migration scope OK\n";
