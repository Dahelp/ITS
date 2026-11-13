<?php
if(PHP_SAPI!=='cli') exit;
require dirname(__DIR__).'/config/init.php'; require CONF.'/db_bootstrap.php';
$map=json_decode(file_get_contents(ROOT.'/app/data/chain-category-images.json'),true,512,JSON_THROW_ON_ERROR);
$errors=[]; $imageChecks=0;
function categoryImageHttp($path,$head=false) {
 $c=curl_init('https://its-center.ru'.$path); curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_NOBODY=>$head]);
 $body=curl_exec($c); $status=curl_getinfo($c,CURLINFO_HTTP_CODE); $type=curl_getinfo($c,CURLINFO_CONTENT_TYPE); curl_close($c);
 return [$status,$body,$type];
}
foreach($map['categories'] as $asset) {
 $image=R::getCell('SELECT img FROM category WHERE alias=?',[$asset['alias']]);
 if($image!==$asset['filename']) $errors[]='Category binding '.$asset['alias'];
 foreach(['baseimg','mini'] as $folder) {
    [$status,,$type]=categoryImageHttp('/images/category/'.$folder.'/'.$asset['filename'],true);
    if($status!==200 || strpos((string)$type,'image/')!==0) $errors[]='Image HTTP '.$asset['filename'];
    $imageChecks++;
 }
}
foreach(['/catalog'=>[0],'/catalog/cepi-protivoskolzheniya'=>[1,2]] as $path=>$indexes) {
 [$status,$html]=categoryImageHttp($path);
 if($status!==200) $errors[]='Catalog HTTP '.$path;
 foreach($indexes as $index) if(strpos((string)$html,'/images/category/baseimg/'.$map['categories'][$index]['filename'])===false) $errors[]='Image not rendered '.$path;
}
echo json_encode(['category_bindings'=>3,'http_images'=>$imageChecks,'catalog_pages'=>2,'errors'=>$errors],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
exit($errors?1:0);
