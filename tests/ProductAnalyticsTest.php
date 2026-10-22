<?php
require dirname(__DIR__).'/vendor/autoload.php';
class_alias(\RedBeanPHP\R::class,'R');R::setup('sqlite::memory:');R::freeze(true);
R::exec('CREATE TABLE product(id INTEGER,category_id INTEGER,article TEXT,hide TEXT,price INTEGER,quantity INTEGER,rest INTEGER,reserve INTEGER,title TEXT,description TEXT,keywords TEXT,content TEXT,img TEXT,new_product INTEGER,hit INTEGER,sale INTEGER)');
$rows=[
 [1,1,'A','show',100,10,10,2,'Unique A','Desc','Keys','Text','a.webp',0,0,0],
 [2,1,'B','show',100,5,5,5,'   ','','','','b.webp',1,0,0],
 [3,1,'C','hide',100,4,4,0,'Duplicate','Desc','','','no_image.jpg',0,0,0],
 [4,1,'D','show',0,3,3,0,'Duplicate','','','','d.webp',0,0,0],
 [5,2,'','show',100,0,0,0,'','','','','',0,0,0],
 [6,2,'E','show',100,2,2,0,'Unique E','Desc','Keys','Text','e.webp',1,0,0],
];
foreach($rows as$row)R::exec('INSERT INTO product VALUES('.implode(',',array_fill(0,count($row),'?')).')',$row);
R::exec('CREATE TABLE avito_ad(id INTEGER,article TEXT,complete_id INTEGER,avito_publication_status TEXT,deletion_requested_at TEXT)');
R::exec("INSERT INTO avito_ad VALUES(1,'A',NULL,'active',NULL),(2,'A',NULL,'old',NULL),(3,'B',25,'active',NULL),(4,'C',NULL,'active','2026-10-22')");
R::exec('CREATE TABLE plagins_inseo(id INTEGER,category_id INTEGER,tip TEXT,hide TEXT,title TEXT,description TEXT,keywords TEXT,content TEXT,name TEXT)');
R::exec("INSERT INTO plagins_inseo VALUES(1,1,'product','show','Template','Desc','Keys','Text','Name'),(2,2,'product','hide','Hidden','Hidden','Hidden','Hidden','Hidden')");
R::exec('CREATE TABLE related_product(product_id INTEGER,related_id INTEGER)');R::exec('INSERT INTO related_product VALUES(1,2),(2,1),(4,999),(5,5)');
R::exec('CREATE TABLE similar_product(product_id INTEGER,similar_id INTEGER)');R::exec('INSERT INTO similar_product VALUES(1,3)');
R::exec('CREATE TABLE contents(id INTEGER)');R::exec('INSERT INTO contents VALUES(10)');
R::exec('CREATE TABLE content_related(content_id INTEGER,related_id INTEGER)');R::exec('INSERT INTO content_related VALUES(10,2),(999,6)');
use app\services\ProductAnalytics as Analytics;
$summary=Analytics::summary();
$expected=['all'=>6,'active'=>5,'stock'=>4,'out_of_stock'=>2,'avito_linked'=>2,'avito_missing'=>4,'avito_missing_stock'=>2,'avito_prepare'=>1,'avito_published'=>1,'seo_complete'=>2,'seo_incomplete'=>4,'meta_uncovered'=>1,'title_missing'=>2,'description_missing'=>3,'image_missing'=>2,'title_duplicate'=>2,'related_missing'=>4,'similar_missing'=>5,'content_missing'=>5,'incoming_missing'=>3,'isolated'=>3,'linked'=>3];
foreach($expected as$key=>$count)if($summary[$key]!==$count)throw new RuntimeException($key.': '.$summary[$key].' != '.$count);
foreach(Analytics::labels()as$key=>$label){$count=(int)R::getCell('SELECT COUNT(*) FROM ('.Analytics::query().') a WHERE '.Analytics::where($key));if($count!==$summary[$key])throw new RuntimeException('Counter/filter mismatch: '.$key);}
if(Analytics::summary(1,'new_product')['all']!==1)throw new RuntimeException('Combined category and badge scope');
if(Analytics::normalize("stock OR 1=1")!=='all'||Analytics::normalize(['stock'])!=='all')throw new RuntimeException('Untrusted filter accepted');
echo "Product analytics: all counters, stock reserves, duplicate ads, set exclusion, templates, valid links and filter parity OK\n";
