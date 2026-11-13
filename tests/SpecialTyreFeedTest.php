<?php
require dirname(__DIR__) . '/vendor/autoload.php';
use app\services\SpecialTyreFeedService;
function feedCheck($value, $message) { if (!$value) throw new RuntimeException($message); }
$root = sys_get_temp_dir() . '/special-tyre-feed-' . bin2hex(random_bytes(5));
mkdir($root . '/images/product/unload', 0777, true);
mkdir($root . '/images/product/baseimg', 0777, true);
$files = [];
try {
    foreach (['unload/good.png' => 450, 'unload/small.png' => 449, 'baseimg/fallback.png' => 500] as $file => $size) {
        $image = imagecreatetruecolor($size, $size);
        imagepng($image, $root . '/images/product/' . $file);
        imagedestroy($image);
        $files[] = $root . '/images/product/' . $file;
    }
    $base = ['id'=>1, 'sku'=>'IT001', 'name'=>'Шина & "тест" <450>', 'alias'=>'tyre', 'category_id'=>9,
        'feed_stock'=>1, 'feed_price'=>100, 'price'=>120, 'price_rrs'=>90, 'unload_img'=>'good.png', 'img'=>''];
    $offers = [$base];
    foreach ([['feed_stock'=>0], ['feed_stock'=>-1], ['category_id'=>2], ['category_id'=>36],
        ['feed_price'=>0], ['unload_img'=>'missing.png'], ['unload_img'=>'small.png'],
        ['unload_img'=>'../good.png'], ['sku'=>''], ['alias'=>'']] as $i=>$changes) {
        $offers[] = array_replace($base, ['id'=>$i+2, 'sku'=>'REJECT'.$i], $changes);
    }
    $offers[] = array_replace($base, ['id'=>20,'sku'=>'FALLBACK','unload_img'=>'missing.png','img'=>'fallback.png','price'=>100]);
    $service = new SpecialTyreFeedService($root, 'https://its-center.ru');
    $result = $service->render([['id'=>9,'name'=>'Спецшины & погрузчики'],['id'=>2,'name'=>'ATV'],['id'=>36,'name'=>'Грузовые']], $offers,
        [['product_id'=>1,'attribute_name'=>'Размер "A"','attribute_text'=>'<450 & 500>']]);
    feedCheck($result['offers'] === 2 && $result['skipped'] === 10, 'Strict exclusions');
    $xml = simplexml_load_string($result['xml']);
    feedCheck((string)$xml->shop->offers->offer[0]->name === $base['name'], 'XML text escaping');
    feedCheck((string)$xml->shop->offers->offer[0]->param === '<450 & 500>', 'Attribute values escaped');
    feedCheck(count($xml->shop->categories->category) === 2, 'No ATV/truck categories');
    feedCheck((string)$xml->shop->offers->offer[1]->picture === 'https://its-center.ru/images/product/baseimg/fallback.png', 'Photo fallback');
    feedCheck(!isset($xml->shop->offers->offer[1]->oldprice), 'No invalid old price');
    foreach ($xml->shop->offers->offer as $offer) feedCheck((string)$offer['available'] === 'true', 'Only available offers');
    $target = $root . '/feed.xml';
    $files[] = $target;
    file_put_contents($target, 'previous');
    SpecialTyreFeedService::writeAtomically($target, $result['xml']);
    feedCheck(file_get_contents($target) === $result['xml'], 'Atomic replacement');
    $duplicateRejected = false;
    try { $service->render([['id'=>9,'name'=>'Tyres']], [$base,$base]); } catch (RuntimeException $e) { $duplicateRejected = true; }
    feedCheck($duplicateRejected, 'Duplicate IDs rejected');
    feedCheck($service->render([['id'=>9,'name'=>'Tyres']], [])['offers'] === 0, 'All sold out can clear feed');
    $confirmed=['type'=>'tyre_pneumatic','values'=>['weight_kg'=>['state'=>'known','verification'=>'confirmed','value'=>'12.500000','source_ref'=>'Test specification']],'sizes'=>[],'ratings'=>[]];
    $merged=$service->render([['id'=>9,'name'=>'Tyres']],[$base],[['product_id'=>1,'attribute_id'=>19,'attribute_group_id'=>1,'attribute_name'=>'Вес, кг','attribute_text'=>'100']],[1=>$confirmed]);
    $mergedXml=simplexml_load_string($merged['xml']);$mergedParams=[];foreach($mergedXml->shop->offers->offer->param as $p)$mergedParams[(string)$p['name']]=(string)$p;
    feedCheck(!isset($mergedParams['Вес, кг'])&&in_array('12.5',$mergedParams,true),'Feed consumes confirmed overlay without duplicate legacy value');
    feedCheck((string)$mergedXml->shop->offers->offer->name===$base['name']&&$merged['offers']===1,'Overlay preserves title and inclusion rules');
    echo "Special tyre feed: stock, category, photo, XML, price and atomic publication checks OK\n";
} finally {
    foreach ($files as $file) if (is_file($file)) unlink($file);
    rmdir($root . '/images/product/unload'); rmdir($root . '/images/product/baseimg');
    rmdir($root . '/images/product'); rmdir($root . '/images'); rmdir($root);
}
