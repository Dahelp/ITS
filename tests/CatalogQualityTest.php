<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CatalogQuality as Q;
use app\services\CatalogLegacyPreview as L;
function qualityCheck($ok,$message){if(!$ok)throw new RuntimeException($message);}
$p=['id'=>357,'sku'=>'IT000007251','name'=>'Колесо в сборе с диском','model'=>'','category_id'=>18,'brand_id'=>1,'hide'=>'show','quantity'=>0,'seo_h1'=>'Колесо','title'=>'Title','description'=>'Description','img'=>'photo.jpg'];
$l=L::forProduct($p,[['attribute_id'=>4,'attribute_text'=>'12-16.5'],['attribute_id'=>61,'attribute_text'=>'цельнолитые'],['attribute_id'=>67,'attribute_text'=>'33x12-20']]);
$r=Q::assess($p,$l);qualityCheck($r['source']==='legacy_proposal'&&$r['status']==='missing','Unsaved proposals and missing required');
qualityCheck($r['required_total']===3&&$r['required_filled']===2,'Required denominator excludes optional fields');
qualityCheck(!$r['seo_gaps'],'Basic filled fields');
$saved=['type'=>'service','values'=>[],'sizes'=>[],'ratings'=>[],'version'=>4];
$l['warnings']=['Old mismatch'];$l['unmapped']=[['code'=>'colour','raw'=>'Legacy','reason'=>'unmapped']];
$r=Q::assess($p,$l,$saved);qualityCheck($r['source']==='saved'&&$r['version']===4&&$r['status']==='clear'&&!$r['issues'],'Saved source wins, old problems do not become current defects');
$draft=['type'=>'unclassified','values'=>[]];$r=Q::assess($p,$l,$draft);qualityCheck($r['status']==='classification'&&$r['required_total']===0,'Unknown profile never appears complete');
$conflict=$l['proposed'];$conflict['values']['tire_size']['verification']='conflict';$r=Q::assess($p,$l,$conflict);qualityCheck($r['status']==='conflict','Explicit conflict priority');
$dupe=L::forProduct($p,[['attribute_id'=>4,'attribute_text'=>'12-16.5'],['attribute_id'=>4,'attribute_text'=>'10-16.5']]);qualityCheck(!isset($dupe['proposed']['values']['tire_size'])&&count($dupe['warnings'])===1,'Distinct duplicate values cannot be chosen automatically');
$same=L::forProduct($p,[['attribute_id'=>4,'attribute_text'=>'12-16.5'],['attribute_id'=>4,'attribute_text'=>'12-16.5']]);qualityCheck(isset($same['proposed']['values']['tire_size'])&&!$same['warnings'],'Exact duplicates are not false conflicts');
$items=[Q::assess($p,$l),Q::assess(array_replace($p,['id'=>2,'quantity'=>4,'seo_h1'=>'']),$l,$saved)];
qualityCheck(count(Q::filter($items,['stock'=>'positive','seo'=>'missing']))===1,'Combined stock and SEO filters');
qualityCheck(count(Q::filter($items,['stock'=>'zero']))===1,'Out-of-stock products remain visible');
qualityCheck(Q::summary($items)['saved']===1&&Q::summary($items)['legacy_proposal']===1,'Source totals');
$mapped=L::forProduct($p,[['attribute_id'=>62,'attribute_text'=>'асфальт, бетон'],['attribute_id'=>15,'attribute_text'=>'Квадроцикл, садовая техника']]);
qualityCheck($mapped['proposed']['values']['surface']['value']===['hard'],'Exact surface alias');
qualityCheck($mapped['proposed']['values']['equipment_application']['value']===['atv','garden'],'All multi values preserved');
qualityCheck($mapped['proposed']['values']['surface']['verification']==='legacy_unverified','Mapping does not confirm facts');
$partial=L::forProduct($p,[['attribute_id'=>62,'attribute_text'=>'грунт, неизвестное покрытие']]);qualityCheck(!isset($partial['proposed']['values']['surface'])&&count($partial['unmapped'])===1,'Unknown multi value cannot be silently dropped');
foreach(\app\services\CatalogProperties::definitions() as $code=>$def)foreach($def['legacy_aliases']??[] as $target)qualityCheck(isset($def['options'][$target]),'Alias references existing option: '.$code);
echo "Quality queue: sources, required denominator, conflicts, duplicate legacy values and combined filters OK\n";
