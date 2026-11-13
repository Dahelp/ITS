<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CatalogSeoAudit as S;
$p=['name'=>'Товар','title'=>'Повтор','description'=>'Описание','seo_h1'=>'Название','content'=>'Текст'];$audit=S::assess($p,[],[]);
$rows=[];foreach([1,2,3]as$id)$rows[]=['id'=>$id,'sku'=>'SKU'.$id,'visible'=>$id!==3,'effective_seo'=>$audit,'seo_fields'=>[]];
$rows[1]['effective_seo']['fields']['title']['value']='ПОВТОР';$r=S::duplicates($rows);
if($r[0]['effective_seo']['duplicates']['title']['count']!==2||$r[0]['effective_seo']['duplicates']['title']['peers'][0]['id']!==2||isset($r[2]['effective_seo']['duplicates']))throw new RuntimeException('Visible duplicate grouping wrong');
$rows[1]['effective_seo']['ambiguous']=true;$r=S::duplicates($rows);if(isset($r[0]['effective_seo']['duplicates']))throw new RuntimeException('Ambiguous template included');
$r=S::assess(array_replace($p,['title'=>'Купить {size}','description'=>'Тест']),[],[]);if(!in_array('seo_placeholder',$r['issues'])||!in_array('seo_stub',$r['issues']))throw new RuntimeException('Markers not detected');
$r=S::assess(array_replace($p,['seo_h1'=>'18.00R25 ** BOTO','title'=>'18.00R25 **PR']),[],[]);if($r['issues'])throw new RuntimeException('Legitimate star rating flagged');
echo "SEO text checks: duplicates, hidden/ambiguous exclusion, peer links, markers and legitimate stars OK\n";
