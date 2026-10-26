<?php
if(PHP_SAPI!=='cli')exit;
if(getenv('CATALOG_DB_FIXTURES')!=='1'){echo "Set CATALOG_DB_FIXTURES=1 to run isolated database fixtures.\n";exit;}
require dirname(__DIR__).'/config/init.php';require CONF.'/db_bootstrap.php';
use app\services\CatalogSelection as Listing;
$fixturePrefix='codex_listing_'.bin2hex(random_bytes(4)).'_';
$fixtureTables=['product','modification','actions','review_product','review','order_product'];
function fixtureSql($sql){global $fixturePrefix,$fixtureTables;return preg_replace_callback('~\b(product|modification|actions|review_product|review|order_product)\b~',fn($m)=>$fixturePrefix.$m[1],$sql);}
function fixtureExec($sql){return R::exec(fixtureSql($sql));}
function fixtureCol($sql){return R::getCol(fixtureSql($sql));}
try{
 // Isolated randomly named fixture tables; removed in finally.
 fixtureExec('CREATE TABLE product (id INT,price DECIMAL(12,2),price_rrs DECIMAL(12,2),quantity INT,reserve INT,hit INT,new_product INT,sale INT,name VARCHAR(100))');
 fixtureExec('CREATE TABLE modification (product_id INT,quantity INT,price DECIMAL(12,2))');
 fixtureExec('CREATE TABLE actions (id INT,product_id INT,type_id INT,znachenie DECIMAL(12,2),hide VARCHAR(10),date_end DATETIME)');
 fixtureExec('CREATE TABLE review_product (product_id INT,review_id INT)');fixtureExec('CREATE TABLE review (id INT,point INT)');fixtureExec('CREATE TABLE order_product (product_id INT,order_id INT)');
 fixtureExec("INSERT INTO product VALUES(1,1000,2000,4,4,1,0,0,'A'),(2,1000,0,0,2,0,1,1,'B'),(3,1000,0,2,0,1,0,0,'C'),(4,0,0,0,0,0,0,0,'D')");
 fixtureExec('INSERT INTO modification VALUES(2,8,1200)');fixtureExec("INSERT INTO actions VALUES(1,2,1,20,'show',DATE_ADD(NOW(),INTERVAL 1 DAY)),(2,3,2,300,'show',DATE_ADD(NOW(),INTERVAL 1 DAY)),(3,1,1,90,'show',DATE_SUB(NOW(),INTERVAL 1 DAY))");
 fixtureExec('INSERT INTO review VALUES(1,3),(2,5)');fixtureExec('INSERT INTO review_product VALUES(1,1),(2,2)');fixtureExec('INSERT INTO order_product VALUES(3,1),(3,2),(3,2),(1,3)');
 $expected=['stock'=>[2,3,4,1],'price_asc'=>[3,1,2,4],'price_desc'=>[2,1,3,4],'rating'=>[2,1,4,3],'popular'=>[3,1,4,2],'newest'=>[2,4,3,1],'discount'=>[1,3,4,2],'name'=>[1,2,3,4]];
 foreach($expected as$sort=>$want){$ids=array_map('intval',fixtureCol('SELECT product.id FROM product '.Listing::order(Listing::state(['sort'=>$sort]))));if($ids!==$want)throw new RuntimeException($sort.' '.json_encode($ids));}
 $state=Listing::state(['status'=>'discount','in_stock'=>'1']);$ids=array_map('intval',fixtureCol('SELECT product.id FROM product WHERE 1=1 '.Listing::where($state).' '.Listing::order($state)));if($ids!==[3])throw new RuntimeException('Discount + stock filter');
 fixtureExec('DELETE FROM modification');
 $active=Listing::state(['status'=>'discount','sort'=>'price_asc']);
 $ids=array_map('intval',fixtureCol('SELECT product.id FROM product WHERE 1=1 '.Listing::where($active).' '.Listing::order($active)));if($ids!==[3,2,1])throw new RuntimeException('Percentage discount price order');
 echo '8 fixture sorts OK: active percentage/fixed discounts, expired promotions, RRP, zero price, reserve, modification stock, ratings and distinct order counts'.PHP_EOL;
}catch(Throwable$e){echo get_class($e).': '.$e->getMessage().PHP_EOL;$failed=true;}finally{foreach($fixtureTables as$table)R::exec('DROP TABLE IF EXISTS '.$fixturePrefix.$table);}if(!empty($failed))exit(1);
