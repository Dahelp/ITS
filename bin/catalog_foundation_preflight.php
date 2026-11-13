<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=getenv('CATALOG_AUDIT_ROOT')?:dirname(__DIR__);
require $root.'/vendor/autoload.php';
$fail=[];$checks=[];
$check=static function(string $name,bool $ok,string $detail='')use(&$checks,&$fail):void{$checks[]=['check'=>$name,'ok'=>$ok,'detail'=>$detail];if(!$ok)$fail[]=$name;};
$check('PHP >= 8.0',PHP_VERSION_ID>=80000,PHP_VERSION);
foreach(['pdo_mysql','mbstring'] as $ext)$check('extension '.$ext,extension_loaded($ext));
try{
 $c=require $root.'/config/config_db.php';
 $db=new PDO($c['dsn'],$c['user'],$c['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $db->exec('SET TRANSACTION READ ONLY');$db->beginTransaction();
 $v=(string)$db->query('SELECT VERSION()')->fetchColumn();
 $check('MySQL / MariaDB version',version_compare(preg_replace('/[^0-9.].*$/','',$v),'5.7','>='),$v);
 $engine=$db->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='product'")->fetchColumn();
 $check('product InnoDB',strtolower((string)$engine)==='innodb',(string)$engine);
 $column=$db->query("SHOW COLUMNS FROM product LIKE 'id'")->fetch();
 $check('product.id compatible FK',is_array($column)&&preg_match('/^int(?:\\(\\d+\\))? unsigned$/i',$column['Type'])===1&&$column['Key']==='PRI',is_array($column)?$column['Type']:'missing');
 foreach(['product_attribute','attribute','attribute_product','attribute_value','brand'] as $table){
  $q=$db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$q->execute([$table]);$check('table '.$table,(bool)$q->fetchColumn());
 }
 $q=$db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'catalog\\_%'");
 $tables=$q->fetchAll(PDO::FETCH_COLUMN);$checks[]=['check'=>'existing catalog tables','ok'=>true,'detail'=>$tables];
 $expected=['catalog_foundation_version','catalog_type','catalog_property','catalog_property_option','catalog_property_rule','catalog_product_profile','catalog_product_property','catalog_product_property_option','catalog_product_size','catalog_tire_service_rating','catalog_property_revision','catalog_legacy_map'];
 $present=array_values(array_intersect($expected,$tables));
 $check('schema absent or complete',count($present)===0||count($present)===count($expected),'present '.count($present).' / '.count($expected));
 if(in_array('catalog_foundation_version',$tables,true)){
  $version=$db->query('SELECT version FROM catalog_foundation_version WHERE id=1')->fetchColumn();$check('schema version 1',(int)$version===1,(string)$version);
 }
 foreach(['catalog_product_profile','catalog_property_revision'] as $t)if(in_array($t,$tables,true))$checks[]=['check'=>$t.' rows','ok'=>true,'detail'=>(int)$db->query('SELECT COUNT(*) FROM '.$t)->fetchColumn()];
 $db->rollBack();
}catch(Throwable $e){if(isset($db)&&$db->inTransaction())$db->rollBack();$fail[]='database check';$checks[]=['check'=>'database check','ok'=>false,'detail'=>'Failed. Review configuration/permissions privately; credentials are never printed.'];}
echo json_encode(['mode'=>'read_only','ready'=>!$fail,'checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),"\n";
exit($fail?1:0);
