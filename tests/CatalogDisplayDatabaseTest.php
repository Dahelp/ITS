<?php
require dirname(__DIR__).'/config/init.php';require CONF.'/db_bootstrap.php';
use app\services\CatalogDisplay as D;
if(PHP_OS_FAMILY!=='Windows')throw new RuntimeException('Local fixture only');
$config=require CONF.'/config_db.php';if(!preg_match('/host=(?:localhost|127\.0\.0\.1)(?:;|$)/',$config['dsn']))throw new RuntimeException('Local DB only');
$db=R::getPDO();$service=new D($db);
function displayExpect($ok,$message){if(!$ok)throw new RuntimeException($message);}
try{
 $db->exec('CREATE TEMPORARY TABLE catalog_category_display (category_id INT UNSIGNED PRIMARY KEY,columns_count TINYINT UNSIGNED,groups_json TEXT NULL,version INT UNSIGNED,updated_by INT,updated_at DATETIME) ENGINE=InnoDB');
 $db->exec('CREATE TEMPORARY TABLE category(id INT PRIMARY KEY)');$db->exec('INSERT INTO category VALUES(10),(11)');
 $db->exec('CREATE TEMPORARY TABLE attribute_group(id INT PRIMARY KEY)');$db->exec('INSERT INTO attribute_group VALUES(1),(2),(3)');
 displayExpect($service->read(10)['columns']===4,'Default four columns');
 $service->save(0,0,3,null,1);displayExpect($service->read(10)['columns']===3,'Global inheritance');
 $service->save(10,0,4,[3,1],1);$a=$service->read(10);
 displayExpect($a['groups']===[3,1]&&$a['columns']===4&&$a['version']===1,'Persist ordered category selection');
 displayExpect($service->read(11)['groups']===null&&$service->read(11)['columns']===3,'Category isolation');
 try{$service->save(10,0,3,[1],1);throw new LogicException('Stale save accepted');}catch(RuntimeException$e){displayExpect($e->getCode()===409,'Conflict response');}
 displayExpect($service->read(10)===$a&&!$db->inTransaction(),'Stale form cannot overwrite');
 foreach([[99],[1,1]]as$bad){try{$service->save(10,1,4,$bad,1);throw new LogicException('Invalid groups accepted');}catch(InvalidArgumentException$e){}}
 $service->save(10,1,0,[],1);displayExpect($service->read(10)['groups']===[]&&$service->read(10)['columns']===3,'Explicit hidden panel, inherited grid');
 $service->save(10,2,0,null,1);displayExpect($service->read(10)['groups']===null,'Restore automatic groups');
 displayExpect(D::orderedGroups([1=>'Brand',2=>'Size',3=>'Type'],[3,1,9])===[3=>'Type',1=>'Brand'],'Stable explicit order');
 echo "Catalog display: persistence, category isolation, global inheritance, explicit empty, automatic reset, stale forms and invalid groups OK\n";
}finally{foreach(['attribute_group','category','catalog_category_display']as$table)$db->exec('DROP TEMPORARY TABLE IF EXISTS '.$table);}
