<?php
require dirname(__DIR__).'/config/init.php';require CONF.'/db_bootstrap.php';require LIBS.'/functions.php';
if(PHP_OS_FAMILY!=='Windows')throw new RuntimeException('Local read-only fixture only');
$config=require CONF.'/config_db.php';if(!preg_match('/host=(?:localhost|127\.0\.0\.1)(?:;|$)/',$config['dsn']))throw new RuntimeException('Local DB only');
$original=R::getRow('SELECT * FROM product WHERE id=363');if(!$original)throw new RuntimeException('Missing fixture');
$context=\app\widgets\product\Product::buildContext([$original]);$context['seoNameByProductId'][363]='Stock test';
foreach([[5,5,[],5],[0,5,[],0],[2,7,[['quantity'=>3,'price'=>100]],5],[-2,0,[],0]]as[$quantity,$reserve,$mods,$expected]){
 $product=$original;$product['quantity']=$quantity;$product['reserve']=$reserve;$case=$context;$case['modificationsByProductId'][363]=$mods;
 ob_start();new \app\widgets\product\Product($product,['value'=>1,'symbol_left'=>'','symbol_right'=>'₽'],'product_tpl.php',$case);$html=ob_get_clean();
 if($expected>0&&!str_contains($html,'В наличии: '.$expected.' шт.'))throw new RuntimeException('Card free stock mismatch');
 if($expected===0&&str_contains($html,'pc-stock--ok'))throw new RuntimeException('Card zero stock mismatch');
}
if(R::getRow('SELECT * FROM product WHERE id=363')!==$original)throw new RuntimeException('Fixture changed');
echo "Real card rendering: API free stock, reserve not deducted twice, variants and zero floor OK; source product untouched\n";
