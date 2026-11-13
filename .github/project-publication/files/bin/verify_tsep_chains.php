<?php
// Read-only verification against the installed catalog; --http also checks public pages.
if(PHP_SAPI!=='cli') exit;
require dirname(__DIR__).'/config/init.php'; require CONF.'/db_bootstrap.php';
use app\services\TsepChainCatalog;
$products=TsepChainCatalog::products(json_decode(file_get_contents(ROOT.'/app/data/tsep-chains.json'),true));
$imageMap=is_file(ROOT.'/app/data/tsep-chain-images.json') ? json_decode(file_get_contents(ROOT.'/app/data/tsep-chain-images.json'),true) : ['products'=>[]];
$errors=[]; $specCount=0; $filterCount=0; $httpCount=0;
foreach($products as $expected) {
    $p=R::getRow('SELECT * FROM product WHERE article=?',[$expected['article']]);
    if(!$p) { $errors[]='Missing '.$expected['article']; continue; }
    $expectedImage=$imageMap['products'][$expected['article']]['filename']??'tsep-chain-placeholder.jpg';
    if($p['alias']!==$expected['alias'] || (int)$p['stock_status_id']!==2 || (int)$p['quantity']!==0 || (float)$p['price']!==0.0 || $p['img']!==$expectedImage) $errors[]='Product state '.$p['id'];
    $specs=R::getAssoc('SELECT a.attribute_name,pa.attribute_text FROM product_attribute pa JOIN attribute a ON a.id=pa.attribute_id WHERE pa.product_id=?',[$p['id']]);
    foreach($expected['specs'] as $name=>$value) if(($specs[$name]??null)!==$value) $errors[]='Specification '.$p['id'].' '.$name;
    $specCount+=count($specs);
    $filters=R::getAll('SELECT ag.url_params,av.value FROM attribute_product ap JOIN attribute_value av ON av.id=ap.attr_id JOIN attribute_group ag ON ag.id=av.attr_group_id WHERE ap.product_id=?',[$p['id']]);
    $actual=[]; foreach($filters as $f) $actual[$f['url_params']][]=$f['value'];
    foreach($expected['filters'] as $key=>$values) foreach($values as $value) if(!in_array($value,$actual[$key]??[],true)) $errors[]='Filter '.$p['id'].' '.$key;
    $filterCount+=count($filters);
    if(in_array('--http',$argv,true)) {
        $c=curl_init('https://its-center.ru/product/'.$p['alias']);
        curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30]);
        $html=curl_exec($c); $status=curl_getinfo($c,CURLINFO_HTTP_CODE); curl_close($c);
        if(preg_match('~(?:цеп[ьи]|ценпи)[\s\-–—_]*нн|tsep[-_]nn|Производитель цепей|Обозначение у производителя|Обозначение производителя:|"sku"\s*:\s*"TSEP-~iu',(string)$html)) $errors[]='Supplier data exposed in HTML '.$p['id'];
        if(strpos((string)$html,'/images/product/baseimg/'.$expectedImage)===false) $errors[]='Product image HTML '.$p['id'];
        if($status!==200 || strpos((string)$html,'Цена по запросу')===false || strpos((string)$html,TsepChainCatalog::orderText())===false || strpos((string)$html,'Доставка 3-5 рабочих дней после поступления оплаты по счёту.')!==false) $errors[]='HTTP product '.$p['id'].' status '.$status;
        $d=new DOMDocument(); @$d->loadHTML('<?xml encoding="UTF-8" ?>'.$html); $x=new DOMXPath($d);
        foreach($x->query('//script[@type="application/ld+json"]') as $node) {
            $json=json_decode($node->textContent,true);
            if(($json['@type']??'')==='Product' && (isset($json['offers']) || isset($json['mpn']))) $errors[]='Unpriced schema '.$p['id'];
        }
        $httpCount++;
    }
}
$imageHttp=0;
if(in_array('--http',$argv,true)) foreach($imageMap['assets']??[] as $asset) foreach(['mini','baseimg','original'] as $folder) {
    $url='https://its-center.ru/images/product/'.$folder.'/'.$asset['filename'];
    $c=curl_init($url); curl_setopt_array($c,[CURLOPT_NOBODY=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20]); curl_exec($c);
    if(curl_getinfo($c,CURLINFO_HTTP_CODE)!==200 || strpos((string)curl_getinfo($c,CURLINFO_CONTENT_TYPE),'image/')!==0) $errors[]='Image HTTP '.$url;
    curl_close($c); $imageHttp++;
}
$report=['products'=>count($products),'specifications'=>$specCount,'filter_links'=>$filterCount,'http_product_pages'=>$httpCount,'http_image_files'=>$imageHttp,'errors'=>$errors];
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
exit($errors?1:0);
