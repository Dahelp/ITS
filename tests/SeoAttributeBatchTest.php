<?php
require dirname(__DIR__).'/vendor/autoload.php';
class R {
    public static int $cells=0,$batches=0;
    public static function getCell($sql,$params=[]){self::$cells++;return ['brand'=>'EKKA','size'=>'6.50-10','zero'=>'0','duplicate'=>'Original'][($params[1]??'')]??'';}
    public static function getAll($sql,$params=[]){
        if(str_contains($sql,'FROM plagins_inseo'))return[['category_id'=>3,'name'=>'{brand} {size} {zero} {missing}']];
        if(str_contains($sql,'FROM product_attribute')){self::$batches++;return[['product_id'=>1,'url_params'=>'brand','attribute_text'=>'EKKA'],['product_id'=>1,'url_params'=>'size','attribute_text'=>'6.50-10'],['product_id'=>1,'url_params'=>'zero','attribute_text'=>'0']];}
        return[];
    }
}
function batchCheck($value,$message){if(!$value)throw new RuntimeException($message);}
$expected=\ishop\App::seoreplace('{brand} {size} {zero} {missing}',1);R::$cells=0;
$map=['brand'=>'EKKA','size'=>'6.50-10','zero'=>'0'];
batchCheck(\ishop\App::seoreplace('{brand} {size} {zero} {missing}',1,$map)===$expected,'same missing/zero placeholder semantics');
batchCheck(R::$cells===0,'batch resolves known and missing attributes without SQL');
batchCheck(\ishop\App::seoreplace('{duplicate}',1,['duplicate'=>[]])==='Original'&&R::$cells===1,'ambiguous keys preserve old SQL result');
$context=\app\widgets\product\Product::buildContext([['id'=>1,'category_id'=>3,'name'=>'Fallback']]);
batchCheck($context['seoNameByProductId'][1]===$expected&&R::$batches===1&&R::$cells===1,'widget executes one attribute batch, no per-placeholder reads');
echo "SEO attributes: one batch, identical zero/missing values, original duplicate-key resolver passed\n";
