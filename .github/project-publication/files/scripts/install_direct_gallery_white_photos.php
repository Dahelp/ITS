<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root=dirname(__DIR__);
require $root.'/config/init.php';
require LIBS.'/functions.php';
require CONF.'/db_bootstrap.php';
$articles=[10437,10438,10439,10680,12097,11012,11007,11009];
$rows=R::getAll('SELECT id,article,img,unload_img FROM product WHERE article IN ('.R::genSlots($articles).')',$articles);
if(count($rows)!==8)throw new RuntimeException('Expected exactly eight products');
foreach($rows as $row){
    $file='direct-gallery-'.$row['article'].'-white-v1.png';
    $path=WWW.'/images/product/unload/'.$file;
    $size=is_file($path)?getimagesize($path):false;
    if(!$size||$size[0]<450||$size[1]<450||filesize($path)>10*1024*1024)throw new RuntimeException('Invalid photo: '.$file);
    if(trim((string)$row['unload_img'])!==''&&$row['unload_img']!==$file)throw new RuntimeException('Photo changed since audit: '.$row['article']);
}
$backup=ROOT.'/storage/direct-gallery-715980319-photos-before-20261109.json';
if(!is_file($backup)&&file_put_contents($backup,json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),LOCK_EX)===false)throw new RuntimeException('Cannot save backup');
R::begin();
try{
    foreach($rows as $row){
        $file='direct-gallery-'.$row['article'].'-white-v1.png';
        R::exec('UPDATE product SET unload_img=? WHERE id=?',[$file,$row['id']]);
    }
    R::commit();
}catch(Throwable $e){R::rollback();throw $e;}
echo 'Updated eight export photos; original product photos preserved'.PHP_EOL;
