<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CatalogFeedAudit as A;
function auditCheck(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);}
$root=sys_get_temp_dir().'/catalog-feed-audit-'.bin2hex(random_bytes(6));mkdir($root.'/images/product/unload',0777,true);
try{
 foreach(['good.png'=>[450,450],'minimum.png'=>[300,400],'bad.gif'=>[450,450]]as$name=>[$w,$h]){$im=imagecreatetruecolor($w,$h);if(str_ends_with($name,'.gif'))imagegif($im,$root.'/images/product/unload/'.$name);else imagepng($im,$root.'/images/product/unload/'.$name);imagedestroy($im);}
 $offer='<offer id="SKU"><url>https://its-center.ru/product/tyre</url><price>100</price><categoryId>9</categoryId><name>Шина</name><description>Тестовое описание</description><picture>https://its-center.ru/images/product/unload/good.png</picture></offer>';
 $check=static function(string $offers)use($root){$doc=new DOMDocument();$doc->loadXML('<yml_catalog><shop><categories><category id="9">Шины</category></categories><offers>'.$offers.'</offers></shop></yml_catalog>');return A::inspect($doc,$root);};
 $valid=$check($offer);auditCheck(!$valid['errors']&&!$valid['warnings'],'Valid offer');
 auditCheck(($check($offer.$offer)['errors']['duplicate_id']??0)===1,'Repeated ID');
 auditCheck(($check(str_replace('<categoryId>9','<categoryId>99',$offer))['errors']['category_reference']??0)===1,'Missing category');
 auditCheck(($check(str_replace('<price>100','<price>garbage',$offer))['errors']['price']??0)===1,'Nonnumeric price');
 auditCheck(($check(str_replace('<description>Тестовое описание</description>','',$offer))['errors']['description']??0)===1,'Required description omission');
 auditCheck(($check(str_replace('good.png','bad.gif',$offer))['errors']['picture_format']??0)===1,'Merchant image formats');
 $minimum=$check(str_replace('good.png','minimum.png',$offer));auditCheck(!$minimum['errors']&&($minimum['warnings']['picture_quality']??0)===1,'Merchant minimum distinct from internal 450 quality rule');
 auditCheck(($check(str_replace('good.png','missing.png',$offer))['errors']['picture_file']??0)===1,'Missing local file');
 $typed=str_replace(['<offer id="SKU">','<name>Шина</name>'],['<offer id="SKU" type="vendor.model">','<vendor>EKKA</vendor><model>T601</model>'],$offer);auditCheck(!$check($typed)['errors'],'Vendor-model offer without name is valid');
 echo "Feed diagnostics: duplicate IDs, references, numeric prices, typed offers, local photos and distinct minimum/quality limits OK\n";
}finally{foreach(glob($root.'/images/product/unload/*')as$f)unlink($f);rmdir($root.'/images/product/unload');rmdir($root.'/images/product');rmdir($root.'/images');rmdir($root);}
