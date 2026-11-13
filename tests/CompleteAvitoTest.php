<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CompleteAvito;
function expectSet($expected, $actual, $label) {
 if ($expected !== $actual) { throw new RuntimeException($label.': '.json_encode($actual)); }
}
$rows = [
 ['product_id'=>31,'qty'=>2,'price'=>9900,'discount'=>0,'rest'=>17],
 ['product_id'=>30,'qty'=>2,'price'=>11300,'discount'=>0,'rest'=>24],
];
expectSet(['price'=>42400,'stock'=>8,'pieces'=>4], CompleteAvito::calculate($rows), 'staggered set');
$rows[0]['rest']=1;
expectSet(0,CompleteAvito::calculate($rows)['stock'],'incomplete pair');
$rows[0]['rest']=17; $rows[0]['discount']=100;
expectSet(42200,CompleteAvito::calculate($rows)['price'],'per tire discount');
$rows[0]['hide']='hide';
expectSet(0,CompleteAvito::calculate($rows)['stock'],'hidden component');
expectSet(['price'=>0,'stock'=>0,'pieces'=>0],CompleteAvito::calculate([]),'empty set');
expectSet(['price'=>400,'stock'=>2,'pieces'=>4],CompleteAvito::calculate([
 ['product_id'=>1,'qty'=>4,'price'=>100,'discount'=>0,'rest'=>11]
]),'one article four tires');
echo "CompleteAvito calculation tests passed\n";
