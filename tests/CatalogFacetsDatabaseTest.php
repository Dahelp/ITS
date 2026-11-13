<?php
require dirname(__DIR__).'/config/init.php'; require CONF.'/db_bootstrap.php';
use app\services\CatalogFacets as F;
use app\services\CatalogSelection as S;
if(PHP_OS_FAMILY!=='Windows')throw new RuntimeException('Local fixture only');
$config=require CONF.'/config_db.php';
if(!preg_match('/host=(?:localhost|127\.0\.0\.1)(?:;|$)/',$config['dsn']))throw new RuntimeException('Local DB only');
$db=R::getPDO();
function expectFacet($yes,$message){if(!$yes)throw new RuntimeException($message);}
expectFacet(F::ids(['12','12','3x4',['5'],'0','-2','9'])===[12,9],'Strict untrusted IDs');
$schemas=[
 'attribute_value'=>'id INT,attr_group_id INT,value VARCHAR(100),alias VARCHAR(100),hide VARCHAR(10)',
 'attribute_group'=>'id INT,title VARCHAR(100)',
 'attribute_category'=>'group_id INT,category_id INT',
 'attribute_product'=>'product_id INT,attr_id INT',
 'product'=>'id INT,category_id INT,hide VARCHAR(10),quantity INT,reserve INT',
 'modification'=>'product_id INT,quantity INT',
];
try {
 foreach($schemas as$table=>$schema)$db->exec("CREATE TEMPORARY TABLE $table ($schema)");
 $db->exec("INSERT INTO attribute_group VALUES (1,'Brand'),(2,'Size')");
 $db->exec("INSERT INTO attribute_category VALUES (1,10),(2,10)");
 $db->exec("INSERT INTO attribute_value VALUES (1,1,'EKKA','ekka','show'),(2,1,'Hengruida','hengruida','show'),(3,2,'6.50-10','6-50-10','show'),(4,2,'7.00-12','7-00-12','show')");
 $db->exec("INSERT INTO product VALUES (1,10,'show',5,5),(2,10,'show',0,5),(3,10,'show',2,0),(4,11,'show',1,0),(5,10,'hide',1,0)");
 $db->exec('INSERT INTO attribute_product VALUES(1,1),(1,3),(1,3),(2,2),(2,3),(3,2),(3,4),(4,1),(4,4),(5,1),(5,4)');
 $query=function($ids)use($db){return array_map('intval',$db->query("SELECT id FROM product WHERE hide='show' AND category_id=10".F::predicate($ids).' ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));};
 expectFacet($query([1,2])===[1,2,3],'OR within brand');
 expectFacet($query([1,2,3])===[1,2],'AND between brand and size, duplicate links ignored');
 expectFacet($query([1,4])===[],'Empty combination valid');
 expectFacet($query([1,999])===[],'Unknown ID cannot widen selection');
 $groups=F::options('10',[1,3],S::state([]));$counts=[];
 foreach($groups as$g)foreach($g['values']as$v)$counts[(int)$v['id']]=(int)$v['count'];
 expectFacet($counts===[1=>1,2=>1,3=>1,4=>0],'Disjunctive distinct counts, zeros retained, hidden/category excluded');
 $ordered=F::options('10',[],S::state([]),[2,1]);
 expectFacet(array_column($ordered,'id')===[2,1],'Per-category group ordering');
 expectFacet(count(F::options('10',[],S::state([]),[2]))===1,'Per-category group visibility');
 expectFacet(F::options('10',[],S::state([]),[])===[],'Explicitly hidden facet panel');
 $groups=F::options('10',[3],S::state(['in_stock'=>'1']));$counts=[];
 foreach($groups as$g)foreach($g['values']as$v)$counts[(int)$v['id']]=(int)$v['count'];
 expectFacet($counts[1]===1 && $counts[2]===0 && $counts[3]===1 && $counts[4]===1,'Stock scope: free quantity retained despite reserve');
 echo "CT08: OR/AND, duplicate links, zero options, unknown IDs, category/visibility isolation, disjunctive counts and net stock OK\n";
} finally {foreach(array_reverse(array_keys($schemas))as$table)$db->exec('DROP TEMPORARY TABLE IF EXISTS '.$table);}
