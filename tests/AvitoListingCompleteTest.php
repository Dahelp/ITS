<?php
require dirname(__DIR__).'/vendor/autoload.php';
define('PATH','https://example.test');
class_alias(\RedBeanPHP\R::class,'R'); R::setup('sqlite::memory:'); R::freeze(true);
R::exec('CREATE TABLE avito_ad(id INTEGER,avito_id TEXT,ad_external_id TEXT,avito_url TEXT,article TEXT,title TEXT,description TEXT,price_rub INTEGER,images_json TEXT,complete_id INTEGER)');
R::exec('CREATE TABLE product(id INTEGER,article TEXT,name TEXT,alias TEXT,price INTEGER,quantity INTEGER,rest INTEGER,reserve INTEGER,img TEXT,unload_img TEXT,hide TEXT)');
R::exec("INSERT INTO product VALUES(1,'A','Tyre A','tyre-a',100,22,22,2,'','','show'),(2,'B','Tyre B','tyre-b',200,15,15,2,'','','show')");
R::exec('CREATE TABLE plagins_complete(id INTEGER,alias TEXT,hide TEXT)');
R::exec("INSERT INTO plagins_complete VALUES(25,'set-a-b','show')");
R::exec('CREATE TABLE plagins_complete_product(complete_id INTEGER,product_id INTEGER,qty INTEGER)');
R::exec('INSERT INTO plagins_complete_product VALUES(25,1,2),(25,2,2)');
R::exec('CREATE TABLE modification(product_id INTEGER,quantity INTEGER)');
R::exec('CREATE TABLE gallery(id INTEGER,product_id INTEGER,img TEXT)');
R::exec("INSERT INTO avito_ad VALUES(140,'2444178586','','','A','Complete','Description',600,'[]',25),(141,'1234567','','','A','Tyre','Description',100,'[]',NULL)");
function listingCheck($ok,$label) { if (!$ok) throw new RuntimeException($label); }
$set=\app\services\AvitoListingCard::find('2444178586');
listingCheck($set['available']===6 && $set['stock']===7 && $set['reserve']===1,'Live component quantities');
listingCheck($set['stock_unit']==='компл.' && $set['product_url']===PATH.'/complete/set-a-b','Set units and link');
$product=\app\services\AvitoListingCard::find('1234567');
listingCheck($product['available']===20 && $product['product_url']===PATH.'/product/tyre-a','Regular product and link');
R::exec('DELETE FROM product WHERE id=2');
listingCheck(\app\services\AvitoListingCard::find('2444178586')['available']===0,'Missing component cannot form a set');
echo "Avito listing: sets, reserve, links, missing components OK\n";
