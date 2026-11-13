<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CatalogProperties;
use app\services\CatalogSize;
$p=json_decode(file_get_contents(dirname(__DIR__).'/outputs/catalog-foundation-pilot-stage2a-20261111.json'),true,512,JSON_THROW_ON_ERROR);
if($p['mode']!=='dry_run_only'||count($p['items'])!==12)throw new RuntimeException('Expected 12 dry-run products');
$ids=[];
foreach($p['items'] as $item){
 if(isset($ids[$item['product_id']]))throw new RuntimeException('Duplicate product');
 $ids[$item['product_id']]=true;
 $n=CatalogProperties::normalize($item['proposed']);
 if($item['sku']==='IT000007251'){
  if($n['values']['tire_size']['value']!=='12-16.5'||$n['values']['rim_diameter_in']['value']!=='16.5')throw new RuntimeException('Primary geometry changed');
  if(!CatalogSize::matches('33x12-20','12-16.5',$n['sizes']))throw new RuntimeException('Confirmed alternate missing');
 }
}
echo "12 unique pilot proposals validate; confirmed alternative preserves primary size and rim diameter OK\n";
