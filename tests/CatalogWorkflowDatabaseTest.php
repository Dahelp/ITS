<?php
require dirname(__DIR__).'/config/init.php';require CONF.'/db_bootstrap.php';require_once LIBS.'/functions.php';
require dirname(__DIR__).'/outputs/stage8-release/files/app/services/CatalogQuality.php';
if(PHP_OS_FAMILY!=='Windows')throw new RuntimeException('Local fixtures only');$config=require CONF.'/config_db.php';if(!preg_match('/host=(?:localhost|127\.0\.0\.1)(?:;|$)/',$config['dsn']))throw new RuntimeException('Local DB only');
$db=R::getPDO();$service=new app\services\CatalogWorkflow($db);
function workflowCheck(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);}
$tables=['catalog_property_revision','catalog_dictionary_revision','catalog_facet_binding_revision','catalog_naming_revision','catalog_review_event','catalog_review_issue','catalog_category_display'];$created=[];
try {
 $schemas=[
 'catalog_property_revision'=>'id INT,product_id INT,actor_id INT,changed_at DATETIME',
 'catalog_dictionary_revision'=>'id INT,property_code VARCHAR(64),actor_id INT,changed_at DATETIME',
 'catalog_facet_binding_revision'=>'id INT,group_id INT,actor_id INT,changed_at DATETIME',
 'catalog_naming_revision'=>'id INT,scope VARCHAR(80),actor_id INT,changed_at DATETIME',
 'catalog_review_issue'=>'id INT,product_id INT',
 'catalog_review_event'=>'id INT,issue_id INT,actor_id INT,created_at DATETIME',
 'catalog_category_display'=>'category_id INT,version INT,updated_by INT,updated_at DATETIME',
 ];
 foreach($schemas as$t=>$schema){$db->exec('CREATE TEMPORARY TABLE '.$t.' ('.$schema.')');$created[]=$t;}
 for($i=1;$i<=60;$i++)$db->exec("INSERT INTO catalog_property_revision VALUES($i,357,1,'2026-11-12 12:00:00')");
 $db->exec("INSERT INTO catalog_dictionary_revision VALUES(1,'construction',1,'2026-11-13 12:00:00')");
 $db->exec("INSERT INTO catalog_facet_binding_revision VALUES(1,4,1,'2026-11-13 12:00:00')");
 $db->exec("INSERT INTO catalog_naming_revision VALUES(1,'product:357',1,'2026-11-13 12:00:00'),(2,'product:358',1,'2026-11-13 12:00:00'),(3,'type:*',1,'2026-11-13 12:00:00')");
 $db->exec('INSERT INTO catalog_review_issue VALUES(1,357),(2,358)');$db->exec("INSERT INTO catalog_review_event VALUES(1,1,1,'2026-11-13 12:00:00'),(2,2,1,'2026-11-13 12:00:00')");
 $db->exec("INSERT INTO catalog_category_display VALUES(9,2,1,'2026-11-13 12:00:00')");
 $all=$service->history();workflowCheck($all['total']===68&&count($all['items'])===50&&$all['pages']===2,'Unified counts/pagination');
 $_SESSION=['user'=>['id'=>1,'role'=>'admin']];$historyHtml=(static function($history){$kind='';$productId=0;ob_start();include dirname(__DIR__).'/outputs/stage8-release/files/app/views/itscenter/admin/CatalogWorkflow/history.php';return ob_get_clean();})($all);
 workflowCheck(str_contains($historyHtml,ADMIN.'/catalog-workflow/history?')&&!str_contains($historyHtml,'href="?'),'History pagination ignores admin base href');
 $product=$service->history('',357);workflowCheck($product['total']===62,'Product scope excludes unrelated and global events');
 workflowCheck(count($service->history('',357,2)['items'])===12,'Second product history page');
 workflowCheck($service->history('',358)['total']===2,'Other product isolation');
 workflowCheck($service->history('dictionary',357)['total']===0,'Global dictionary events excluded from product scope');
 workflowCheck($service->history('facets')['items'][0]['url']==='/catalog-facet-bindings?group=4','Facet editor actual parameter');
 workflowCheck($service->history('display')['items'][0]['url']==='/catalog-display?id=9','Display editor actual parameter');
 $names=$service->history('names')['items'];workflowCheck(in_array('/catalog-names?mode=rules&type=%2A',array_column($names,'url'),true),'Global names link');
 try{$service->history("names' UNION SELECT");throw new Exception('Bad kind accepted');}catch(InvalidArgumentException $e){}
 echo "Workflow history: source union, global/product isolation, 50-row paging, editor links OK\n";
} finally {foreach(array_reverse($created)as$t)$db->exec('DROP TEMPORARY TABLE IF EXISTS '.$t);}
$fake=[['effective_seo'=>['issues'=>['effective_title']]],['effective_seo'=>['issues'=>[]]]];workflowCheck(count(app\services\CatalogQuality::filter($fake,['seo'=>'effective']))===1,'Summary effective SEO filter');
$fingerprint=static function()use($db){$out=[];foreach(['product','product_attribute','attribute_product','attribute_value_category_canonical','catalog_product_profile','catalog_product_property','catalog_review_issue','catalog_review_event','catalog_dictionary_revision','catalog_naming_revision','catalog_facet_binding_revision']as$t){$h=hash_init('sha256');foreach($db->query('SELECT * FROM '.$t)as$row)hash_update($h,json_encode($row));$out[$t]=hash_final($h);}return$out;};$before=$fingerprint();
$quality=app\services\CatalogQuality::fromDatabase($db);$overview=$service->overview($quality);workflowCheck($overview['summary']['total']===count($quality['items']),'Dashboard uses same quality calculation');workflowCheck(count(app\services\CatalogQuality::filter($quality['items'],['seo'=>'effective']))===$quality['summary']['effective_seo_gaps'],'Summary count matches linked SEO queue');
$_SESSION=['user'=>['id'=>1,'role'=>'admin'],'catalog_properties_csrf'=>'fixture-token'];$_GET=[];$_SERVER['REQUEST_URI']='/admin/catalog-workflow';
$render=static function(string $file,array $data):string {extract($data);ob_start();include dirname(__DIR__).'/outputs/stage8-release/files/app/views/itscenter/admin/CatalogWorkflow/'.$file.'.php';return ob_get_clean();};
$html=$render('index',['overview'=>$overview,'q'=>'<script>alert(1)</script>','products'=>[],'matched'=>0,'feeds'=>[]]);workflowCheck(!str_contains($html,'<script>alert(1)</script>')&&str_contains($html,'Рабочий центр каталога'),'Escaped overview render');
$item=$quality['items'][0];$links=app\services\CatalogWorkflow::productLinks($item['id'],$item['sku'],$item['name'],$item['alias']);$history=$service->history('',$item['id']);$types=app\services\CatalogProperties::types();
workflowCheck(str_contains($render('product',compact('item','links','history','types')),'Итоговые SEO-поля'),'Product work card render');
workflowCheck(str_contains($render('history',['kind'=>'','productId'=>0,'history'=>$service->history()]),'История работы'),'Unified history render');
workflowCheck(str_contains($render('landings',['landings'=>$overview['landings']]),'Проверка SEO-посадочных'),'Landing queue render');
$feedHtml=(static function(){ $feeds=[];$preview=null;$error=null;ob_start();include dirname(__DIR__).'/outputs/stage8-release/files/app/views/itscenter/admin/Plagins/yandex-tovars.php';return ob_get_clean();})();workflowCheck(str_contains($feedHtml,'href="'.ADMIN.'/plagins/yandex-tovars?preview=1"'),'Feed preview ignores admin base href');
$controller=(new ReflectionClass(app\controllers\admin\CatalogWorkflowController::class))->newInstanceWithoutConstructor();$guard=new ReflectionMethod($controller,'guard');$_SERVER['REQUEST_METHOD']='GET';$guard->invoke($controller);
$_SESSION['user']['role']='manager';try{$guard->invoke($controller);throw new Exception('Manager accepted');}catch(RuntimeException $e){workflowCheck($e->getCode()===403,'Manager blocked');}
$_SESSION['user']['role']='admin';$_SERVER['REQUEST_METHOD']='POST';$_POST=['_csrf'=>'fixture-token'];try{$guard->invoke($controller);throw new Exception('POST accepted');}catch(RuntimeException $e){workflowCheck($e->getCode()===405,'Read-only route blocks valid POST');}
workflowCheck($before===$fingerprint(),'Workflow changed persisted data');echo 'Workflow DB/views/access: '.count($quality['items'])." products, live landing audit, escaping, GET-only/admin, unchanged source data OK\n";
