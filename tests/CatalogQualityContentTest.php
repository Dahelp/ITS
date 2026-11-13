<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CatalogQuality as Q;
$root=sys_get_temp_dir().'/its-quality-'.bin2hex(random_bytes(5));mkdir($root.'/images/product/baseimg',0775,true);mkdir($root.'/images/product/unload',0775,true);
file_put_contents($root.'/images/product/baseimg/good.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l9sAAAAASUVORK5CYII='));file_put_contents($root.'/images/product/baseimg/broken.jpg','not an image');
$p=['id'=>1,'sku'=>'TEST','name'=>'Test','alias'=>'test','category_id'=>1,'brand_id'=>1,'quantity'=>2,'hide'=>'show','seo_h1'=>'H1','title'=>'Title','description'=>'Description','content'=>'<p>Описание</p>','img'=>'good.png'];$l=['proposed'=>['type'=>'service','values'=>[]]];
try{
 $a=Q::assess($p,$l,null,$root);if($a['seo_gaps'])throw new RuntimeException('Valid image/text flagged');
 foreach(['missing.jpg','broken.jpg','../good.png']as$name){$a=Q::assess(array_replace($p,['img'=>$name]),$l,null,$root);if(!in_array('photo_file',$a['seo_fields'],true))throw new RuntimeException('Bad photo not detected');}
 $a=Q::assess(array_replace($p,['content'=>'<p><br></p>','title'=>'']),$l,null,$root);if(count(Q::filter([$a],['seo'=>'content','stock'=>'positive']))!==1||count(Q::filter([$a],['seo'=>'photo_file']))!==0)throw new RuntimeException('Specific filters wrong');
 if(Q::summary([$a])['seo_fields']['title']!==1)throw new RuntimeException('Summary wrong');echo "Quality content/photo checks, path containment, specific filters and counts OK\n";
}finally{unlink($root.'/images/product/baseimg/good.png');unlink($root.'/images/product/baseimg/broken.jpg');rmdir($root.'/images/product/baseimg');rmdir($root.'/images/product/unload');rmdir($root.'/images/product');rmdir($root.'/images');rmdir($root);}
