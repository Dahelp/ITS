<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CatalogLegacyPreview as L;
use app\services\CatalogProperties as P;
use app\services\CatalogMigrationSource as S;
use app\services\CatalogMigrationPlan as M;
function specializedCheck(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);}
function specializedPreview(int $category,array $values):array {
    $a=[];foreach($values as $id=>$value)$a[]=['id'=>$id,'attribute_id'=>$id,'attribute_text'=>$value];
    return S::preview(S::canonical(['id'=>1,'sku'=>'TEST','name'=>'Test','category_id'=>$category,'brand_name'=>'EKKA'],$a,[]));
}
$filter=specializedPreview(10,[21=>'93',31=>'275',38=>'163',53=>'65',28=>'1.1/4-12',50=>'EK-9004',51=>'5 л/мин или 300 л/ч']);
specializedCheck(!$filter['unmapped']&&!$filter['warnings'],'Filter source completely covered');
$v=$filter['proposed']['values'];
specializedCheck($v['filter_outer_diameter_mm']['value']==='93'&&$v['filter_outer_outer_diameter_mm']['value']==='275'&&$v['filter_legacy_diameter_38_mm']['value']==='163'&&$v['filter_inner_outer_diameter_mm']['value']==='65','General, outer, ambiguous legacy and inner diameters remain distinct');
specializedCheck($v['filter_thread']['value']==='1.1/4-12'&&$v['filter_flow_raw']['value']==='5 л/мин или 300 л/ч','Thread and compound flow preserved literally');
specializedCheck(M::decide($filter,false)['decision']==='draft_candidate','Filter allowed as unconfirmed draft');
$bad=specializedPreview(10,[21=>'108/80']);specializedCheck(M::decide($bad,false)['decision']==='hold','Compound diameter cannot silently become a single number');
$chain=specializedPreview(44,[76=>'ЦЕПЬ-НН',77=>'Вилочные погрузчики',79=>'Сота (Зигзаг)',80=>'6/6',81=>'6',82=>'5 или 6 (в зависимости от модели)',83=>'Без шипов',85=>'8.15-15, 28х9-15',95=>'16']);
specializedCheck(!$chain['unmapped']&&!$chain['warnings'],'Chain source completely covered');
$v=$chain['proposed']['values'];
specializedCheck($v['chain_side_caliber_raw']['value']==='5 или 6 (в зависимости от модели)'&&$v['chain_set_weight']['value']==='16'&&!isset($v['weight_kg']),'Variant caliber and set mass never replaced by guesses');
specializedCheck(count($chain['proposed']['sizes'])===2&&$chain['proposed']['sizes'][0]['role']==='compatible'&&str_contains($chain['proposed']['sizes'][0]['source_ref'],'85'),'Explicit compatible sizes and exact source ID preserved');
specializedCheck($v['chain_pattern']['value']===['zigzag'],'Exact chain dictionary');
foreach($v as $row)specializedCheck($row['verification']==='legacy_unverified','No automatic confirmation');
$custom=specializedPreview(44,[85=>'Подбор по размеру шин заказчика']);specializedCheck(!$custom['proposed']['sizes']&&!$custom['unmapped'],'Custom fitment is text, not fake tire size');
$conflict=specializedPreview(44,[80=>'8/6',81=>'6']);specializedCheck(M::decide($conflict,false)['decision']==='hold','Contradictory chain calibers held');
specializedCheck(M::decide($chain,true)['decision']==='skip_saved','Existing drafts protected');
specializedCheck(P::applicability('chain_pattern','filter')==='not_applicable'&&P::applicability('filter_thread','chain')==='not_applicable','Profile fields isolated');
echo "Specialized profiles: separate dimensions, literal variants, compatible sizes, conflicts and draft protection OK\n";
