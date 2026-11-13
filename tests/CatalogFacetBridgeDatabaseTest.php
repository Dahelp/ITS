<?php
require dirname(__DIR__).'/config/init.php';require CONF.'/db_bootstrap.php';
use app\services\CatalogFacetBridge as B;use app\services\CatalogFacets as F;use app\services\CatalogSelection as S;use app\services\CatalogDictionary as D;
if(PHP_OS_FAMILY!=='Windows')throw new RuntimeException('Local only');$c=require CONF.'/config_db.php';if(!preg_match('/host=(localhost|127\.0\.0\.1)(;|$)/',$c['dsn']))throw new RuntimeException('Local DB only');$db=R::getPDO();
function bridgeCheck($ok,$m){if(!$ok)throw new RuntimeException($m);}function bridgeReject(callable$f,$m){try{$f();}catch(InvalidArgumentException|RuntimeException$e){return;}throw new RuntimeException($m);}
$schemas=[
'attribute_group'=>'id INT PRIMARY KEY,title VARCHAR(100)',
'attribute_category'=>'group_id INT,category_id INT',
'attribute_value'=>'id INT PRIMARY KEY,attr_group_id INT,value VARCHAR(100),alias VARCHAR(100),hide VARCHAR(10)',
'attribute_product'=>'product_id INT,attr_id INT',
'product'=>'id INT PRIMARY KEY,category_id INT,hide VARCHAR(10),quantity INT,reserve INT',
'modification'=>'product_id INT,quantity INT',
'catalog_product_profile'=>'product_id INT PRIMARY KEY,type_id INT,version INT',
'catalog_product_property'=>'id INT PRIMARY KEY,product_id INT,property_id INT,value_state VARCHAR(24),verification VARCHAR(24),numeric_value DECIMAL(16,6),bool_value INT,text_value TEXT,source_ref TEXT',
'catalog_product_property_option'=>'value_id INT,option_id INT',
'catalog_product_size'=>'id INT,product_id INT,normalized_size VARCHAR(80),role VARCHAR(24),verification VARCHAR(24),source_ref TEXT,conditions_text TEXT',
'catalog_tire_service_rating'=>'product_id INT,sequence_no INT,raw_marking VARCHAR(255),load_single INT,load_dual INT,speed_symbol VARCHAR(4),operating_mode VARCHAR(32),pressure_kpa DECIMAL(10,3),verification VARCHAR(24),source_ref TEXT',
'catalog_facet_binding'=>'group_id INT PRIMARY KEY,property_code VARCHAR(64),mode VARCHAR(16),version INT,updated_by INT,updated_at DATETIME',
'catalog_facet_value_map'=>'group_id INT,source_value VARCHAR(255) COLLATE utf8mb4_bin,attr_id INT,PRIMARY KEY(group_id,source_value)',
'catalog_facet_binding_revision'=>'id INT AUTO_INCREMENT PRIMARY KEY,group_id INT,actor_id INT,before_json TEXT,after_json TEXT,changed_at DATETIME'];
try{
 foreach($schemas as$t=>$body)$db->exec('CREATE TEMPORARY TABLE '.$t.' ('.$body.') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 $db->exec("INSERT INTO attribute_group VALUES(1,'Surface'),(2,'Size')");$db->exec('INSERT INTO attribute_category VALUES(1,10),(2,10)');
 $db->exec("INSERT INTO attribute_value VALUES(1,1,'Hard','hard','show'),(2,1,'Soft','soil','show'),(3,2,'6.50-10','6-50-10','show'),(4,2,'7.00-12','7-00-12','show'),(5,2,'Hidden','hidden','hide')");
 $db->exec("INSERT INTO product VALUES(1,10,'show',5,5),(2,10,'show',0,0),(3,10,'show',2,0),(4,11,'show',1,0),(5,10,'hide',1,0)");$db->exec('INSERT INTO attribute_product VALUES(1,1),(1,3),(1,3),(2,2),(2,3),(3,2),(3,4),(4,1),(4,4),(5,1),(5,4)');
 $sourceBefore=$db->query('SELECT * FROM attribute_product')->fetchAll(PDO::FETCH_ASSOC);
 $type=(int)$db->query("SELECT id FROM catalog_type WHERE code='tyre_pneumatic'")->fetchColumn();$size=(int)$db->query("SELECT id FROM catalog_property WHERE code='tire_size'")->fetchColumn();$surface=(int)$db->query("SELECT id FROM catalog_property WHERE code='surface'")->fetchColumn();
 $db->exec("INSERT INTO catalog_product_profile VALUES(1,$type,1),(2,$type,1),(3,$type,1)");
 $db->exec("INSERT INTO catalog_product_property VALUES(1,1,$size,'known','confirmed',NULL,NULL,'7.00-12','doc'),(2,2,$size,'known','candidate',NULL,NULL,'7.00-12','doc'),(3,3,$size,'known','confirmed',NULL,NULL,'8.00-12','doc'),(4,1,$surface,'known','confirmed',NULL,NULL,NULL,'doc')");
 $q=$db->prepare('SELECT id FROM catalog_property_option WHERE property_id=? AND code=?');foreach(['hard','soil']as$option){$q->execute([$surface,$option]);$id=(int)$q->fetchColumn();bridgeCheck($id>0,'Fixture option missing');$db->exec("INSERT INTO catalog_product_property_option VALUES(4,$id)");}
 $service=new B($db);$dv=(new D($db))->version();$row=static fn($v,$id)=>['source'=>$v,'attr_id'=>(string)$id];
 $service->save(2,0,$dv,'tire_size','preview',[$row('7.00-12',4)],1);
 $query=static function($ids)use($db){return array_map('intval',$db->query("SELECT id FROM product WHERE category_id=10 AND hide='show'".F::predicate($ids).' ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));};
 bridgeCheck($query([3])===[1,2],'Preview must preserve old selection');$preview=$service->preview();bridgeCheck(($preview['overrides'][2][1]??null)===[4]&&count($preview['differences'])===1&&count($preview['issues'])===1,'Preview exact difference/missing mapping');
 $service->save(2,1,$dv,'tire_size','active',[$row('7.00-12',4)],1);B::reset();
 bridgeCheck($query([3])===[2],'Confirmed value replaces old group; draft unchanged');bridgeCheck($query([4])===[1,3],'Confirmed new + unmapped fallback');bridgeCheck($query([3,4])===[1,2,3],'OR sizes');bridgeCheck($query([1,4])===[1],'AND across groups');
 $service->save(1,0,$dv,'surface','active',[$row('hard',1),$row('soil',2)],1);B::reset();bridgeCheck($query([2,4])===[1,3],'Confirmed multi option union');
 $groups=F::options('10',[],S::state([]));$counts=[];foreach($groups as$g)foreach($g['values']as$v)$counts[$v['id']]=$v['count'];bridgeCheck((int)$counts[3]===1&&(int)$counts[4]===2&&(int)$counts[2]===3,'Projected disjunctive counts');
 $match=B::columnCondition('p');$ids=$db->query("SELECT DISTINCT p.id FROM attribute_value av JOIN product p ON ($match) WHERE av.alias='7-00-12' AND p.category_id=10 AND p.hide='show' ORDER BY p.id")->fetchAll(PDO::FETCH_COLUMN);bridgeCheck(array_map('intval',$ids)===[1,3],'Landing applicability uses projection');
 $service->save(1,1,$dv,'surface','active',[$row('hard',1)],1);B::reset();bridgeCheck($query([2])===[2,3],'Incomplete multivalue falls back as whole group');
 bridgeReject(fn()=>$service->save(2,1,$dv,'tire_size','active',[$row('7.00-12',4)],1),'Stale mapping accepted');bridgeReject(fn()=>$service->save(2,2,$dv,'tire_size','active',[$row('7.00-12',1)],1),'Cross-group mapping accepted');bridgeReject(fn()=>$service->save(2,2,$dv,'tire_size','active',[$row('7.00-12',5)],1),'Hidden mapping accepted');bridgeReject(fn()=>$service->save(2,2,$dv+1,'tire_size','active',[$row('7.00-12',4)],1),'Stale dictionary accepted');
 $service->save(2,2,$dv,'tire_size','off',[$row('7.00-12',4)],1);B::reset();bridgeCheck($query([3])===[1,2],'Off restores old links immediately');bridgeCheck($sourceBefore===$db->query('SELECT * FROM attribute_product')->fetchAll(PDO::FETCH_ASSOC),'Old links changed');
 echo "Facet bridge DB: preview/active/off, confirmed-only, missing-map fallback, atomic multi-values, OR/AND, counts, landings, versions, group isolation and unchanged links OK\n";
}finally{if($db->inTransaction())$db->rollBack();foreach(array_reverse(array_keys($schemas))as$t)$db->exec('DROP TEMPORARY TABLE IF EXISTS '.$t);B::reset();}
