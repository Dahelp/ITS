<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/vendor/autoload.php';
$root=dirname(__DIR__);
$catalog=json_decode(file_get_contents($root.'/app/data/tsep-chains.json'),true,512,JSON_THROW_ON_ERROR);
$products=\app\services\TsepChainCatalog::products($catalog);
if(count($products)!==55)throw new RuntimeException('Expected 55 products');
$rows=[];
foreach($products as $product) {
 $s=$product['specs'];
 $rows[]=[
  $product['name'],
  $s['Размер шин для цепи'],
  $s['Рисунок цепи'],
  $s['Калибр цепи (рабочая/боковая), мм'],
  'Не указан — уточнить у производителя',
 ];
}
if(count(array_unique(array_column($rows,0)))!==55)throw new RuntimeException('Duplicate names');
$dir=$root.'/outputs/chain-price-request-20261030';if(!is_dir($dir))mkdir($dir,0775,true);
file_put_contents($dir.'/rows.json',json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
echo json_encode(['products'=>count($rows),'special'=>count(array_filter($products,fn($p)=>$p['group']==='special')),'forklift'=>count(array_filter($products,fn($p)=>$p['group']==='forklift'))]).PHP_EOL;
