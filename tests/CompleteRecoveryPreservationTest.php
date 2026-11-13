<?php
// Isolated integration of restored publication logic with later ad metadata and linker.
// No database, network, orders or live advertisements are touched.
$root=dirname(__DIR__);$stage=$root.'/outputs/complete-recovery-20261113/files';
require $stage.'/app/services/CompleteAvito.php';require $stage.'/app/services/CompleteAvitoLinker.php';
class R {
 public static $ads=[],$writes=[],$components=[],$completes=[];
 public static function inspect(){return['avito_ad','plagins_complete'];}
 public static function getCell($sql,$args=[]){return true;}
 public static function getAll($sql,$args=[]){
  if(str_contains($sql,'FROM plagins_complete_product c LEFT JOIN product'))return self::$components;
  if(str_contains($sql,'SELECT id, name, alias, img FROM plagins_complete'))return self::$completes;
  return self::$ads;
 }
 public static function exec($sql,$args=[]){self::$writes[]=[$sql,$args];return 1;}
}
function preserveCheck($ok,$message){if(!$ok)throw new RuntimeException($message);}
R::$components=[['product_id'=>31,'qty'=>2,'price'=>9900,'discount'=>0,'rest'=>17],['product_id'=>30,'qty'=>2,'price'=>11300,'discount'=>0,'rest'=>24]];
R::$ads=[['id'=>343,'complete_id'=>28,'review_status'=>'confirmed','deletion_requested_at'=>null,'images_json'=>'["first.webp","second.webp"]','product_type'=>'Сохранённый тип','price_rub'=>62000,'manual_fields_json'=>'["price_rub"]','name'=>'Комплект','hide'=>'show','img'=>'set.webp','category_id'=>2]];
\app\services\CompleteAvito::sync();preserveCheck(count(R::$writes)===1,'Later photos or product type were overwritten');$args=R::$writes[0][1];preserveCheck($args[0]===62000&&$args[1]===8,'Manual price or whole-set stock was lost');
R::$writes=[];R::$ads[0]['deletion_requested_at']='2026-11-13 00:00:00';\app\services\CompleteAvito::sync();preserveCheck(R::$writes[0][1][1]===0&&R::$writes[0][1][3]==='archived','Deletion request was overridden');
R::$completes=[['id'=>25,'name'=>'Комплект P350 29x9-14','alias'=>'p350','img'=>'p350.webp']];
R::$ads=[['id'=>140,'complete_id'=>null,'ad_external_id'=>'2444178586','article'=>'','title'=>'','description'=>'/complete/p350','images_json'=>'','manual_fields_json'=>'["complete_id"]']];R::$writes=[];
\app\services\CompleteAvitoLinker::reconcile();preserveCheck(!R::$writes,'Explicit unlink was undone by automatic matching');
R::$ads[0]['manual_fields_json']='[]';\app\services\CompleteAvitoLinker::reconcile();preserveCheck(count(R::$writes)===1&&R::$writes[0][1]===[25,140],'Later legacy matching stopped working');
echo "Recovery preservation OK: manual price, multiple photos, product type, deletion priority, explicit unlink and automatic legacy matching\n";
