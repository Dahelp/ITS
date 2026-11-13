<?php
require dirname(__DIR__).'/vendor/autoload.php';
if(PHP_OS_FAMILY!=='Windows')throw new RuntimeException('Local-only test');
$c=require dirname(__DIR__).'/config/config_db.php';if(!preg_match('/host=(localhost|127\.0\.0\.1)(;|$)/',$c['dsn']))throw new RuntimeException('Local DB only');
$db=new PDO($c['dsn'],$c['user'],$c['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$fingerprint=static function()use($db){$out=[];foreach(['product','product_attribute','attribute_product','plagins_inseo','catalog_product_profile','catalog_product_property','catalog_property_revision'] as $t){$rows=array_map(static fn($r)=>json_encode($r),$db->query('SELECT * FROM '.$t)->fetchAll(PDO::FETCH_ASSOC));sort($rows);$out[$t]=hash('sha256',implode("\n",$rows));}return $out;};
$before=$fingerprint();$count=(int)$db->query('SELECT COUNT(*) FROM product')->fetchColumn();$result=\app\services\CatalogQuality::fromDatabase($db);
if($before!==$fingerprint())throw new RuntimeException('Quality queue changed data');
if($result['summary']['total']!==$count||count($result['items'])!==$count)throw new RuntimeException('Products excluded');
if(!$result['categories'])throw new RuntimeException('Categories absent');
if($db->inTransaction())throw new RuntimeException('Read transaction leaked');
echo "Local DB: all ".$count." products analysed, categories loaded, old/new tables unchanged, read transaction closed OK\n";
