<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\TsepChainCatalog;
function expectChain($condition, $message) { if(!$condition) throw new RuntimeException($message); }
$source=json_decode(file_get_contents(dirname(__DIR__).'/app/data/tsep-chains.json'),true,512,JSON_THROW_ON_ERROR);
$products=TsepChainCatalog::products($source);
expectChain(count($source['series'])===9 && count($products)===55,'Complete source coverage');
expectChain(array_count_values(array_column($products,'group'))===['special'=>4,'forklift'=>51],'Category coverage');
expectChain(count(array_unique(array_column($products,'article')))===55,'Unique internal articles');
expectChain(count(array_unique(array_column($products,'alias')))===55,'Unique product URLs');
$dualSize=0; $unknownWeight=0;
foreach($products as $p) {
    expectChain(TsepChainCatalog::isChain($p) && TsepChainCatalog::priceOnRequest($p),'Price pending');
    expectChain(mb_strlen($p['name'])<=255 && strlen($p['article'])<=30,'Legacy field lengths');
    expectChain(preg_match('/^[a-z0-9-]+$/D',$p['alias'])===1,'Product URL must match router');
    expectChain(strpos($p['alias'],'tsep-nn')===false && mb_stripos($p['name'],'ЦЕПЬ-НН')===false,'Public name and URL do not identify supplier');
    expectChain(strpos($p['content'],'Обозначение производителя:')===false,'Private manufacturer code is not in public description');
    expectChain(count($p['specs'])>=15,'Specifications populated');
    expectChain(count($p['filters'])>=7,'Filters populated');
    expectChain(strpos($p['content'],'<img')===false && strpos($p['content'],'tsep-nn@')===false,'No supplier images or contacts');
    if($p['group']==='special') {
        expectChain(!isset($p['filters']['size']),'Do not invent special equipment sizes from notation examples');
        expectChain(!isset($p['specs']['Вес комплекта цепей, кг']),'Do not invent weights');
    } else {
        expectChain(isset($p['specs']['Обозначение у производителя']), 'Supplier designation for price matching');
        if(count($p['filters']['size'])===2) { $dualSize++; expectChain($p['filters']['size']===['8.15-15','28x9-15'],'Alternative sizes remain searchable'); }
        if($p['weight']==='') $unknownWeight++;
    }
    if($p['model']==='Лесенка 6 мм' && $p['specs']['Размер шин для цепи']==='18х7-8') {
        expectChain($p['weight']==='6.1','Weight belongs to the correct size');
        expectChain($p['specs']['Калибр боковой цепи, мм']==='5 или 6 (в зависимости от модели)','Actual side caliber ambiguity retained');
        expectChain($p['specs']['Минимальный зазор A, мм']==='30','Correct clearance');
    }
}
expectChain($dualSize===4 && $unknownWeight>0,'Incomplete source facts are handled explicitly');
expectChain(!TsepChainCatalog::priceOnRequest(['article'=>'TSEP-TEST','price'=>1000]),'Future price import enables prices');
expectChain(!TsepChainCatalog::isChain(['article'=>'OTHER']),'Other catalog unaffected');
$visible=TsepChainCatalog::publicSpecifications([
 ['attribute_name'=>'Производитель цепей','attribute_text'=>'private'],
 ['attribute_name'=>'Обозначение у производителя','attribute_text'=>'private'],
 ['attribute_name'=>'Рисунок цепи','attribute_text'=>'Сота'],
]);
expectChain(count($visible)===1 && $visible[0]['attribute_name']==='Рисунок цепи','Manufacturer fields hidden in public specifications');
expectChain(count(TsepChainCatalog::publicSpecifications([['name'=>'Производитель цепей'],['name'=>'Калибр цепи']]))===1,'JSON-LD hides the same private fields');
echo "OK: 9 series, 55 products, size matching, weights, filters, placeholders and future prices\n";
