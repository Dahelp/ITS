<?php
require dirname(__DIR__).'/vendor/autoload.php';
class_alias(\RedBeanPHP\R::class,'R');
R::setup('sqlite::memory:'); R::freeze(true);
$imageRoot = sys_get_temp_dir().'/its-deletion-'.bin2hex(random_bytes(5));
mkdir($imageRoot.'/product/gallery',0777,true); mkdir($imageRoot.'/product/baseimg',0777,true);
file_put_contents($imageRoot.'/product/gallery/remove.jpg','fixture');
file_put_contents($imageRoot.'/product/gallery/rollback.jpg','fixture');
file_put_contents($imageRoot.'/product/baseimg/shared.jpg','fixture');
R::exec('CREATE TABLE product(id INTEGER PRIMARY KEY, article TEXT, name TEXT, img TEXT, unload_img TEXT)');
R::exec("INSERT INTO product VALUES(1,'A','Deleted','shared.jpg',''),(2,'B','Survivor','shared.jpg',''),(4,'D','Rollback','','')");
foreach (['attribute_product','modification','gallery','service_product','product_attribute','product_tags','product_bookmarks','product_wishlists','actions','in_stock','in_stock_history','mail_availability','product_abbreviated','plagins_cross','product_cross','product_cross_context','certificate_assignments'] as $table) {
    R::exec("CREATE TABLE $table(id INTEGER PRIMARY KEY,product_id INTEGER,img TEXT)");
    R::exec("INSERT INTO $table VALUES(1,1,'remove.jpg'),(2,2,'keep.jpg')");
}
R::exec("INSERT INTO gallery VALUES(4,4,'rollback.jpg')");
R::exec('CREATE TABLE related_product(id INTEGER,product_id INTEGER,related_id INTEGER)');
R::exec('INSERT INTO related_product VALUES(1,1,2),(2,2,1),(3,2,2)');
R::exec('CREATE TABLE similar_product(id INTEGER,product_id INTEGER,similar_id INTEGER)');
R::exec('INSERT INTO similar_product VALUES(1,2,1),(2,2,2)');
R::exec('CREATE TABLE content_related(id INTEGER,related_id INTEGER)'); R::exec('INSERT INTO content_related VALUES(1,1),(2,2)');
R::exec('CREATE TABLE plagins_complete(id INTEGER,img TEXT)'); R::exec("INSERT INTO plagins_complete VALUES(10,''),(11,'')");
R::exec('CREATE TABLE plagins_complete_product(id INTEGER,complete_id INTEGER,product_id INTEGER)');
R::exec('INSERT INTO plagins_complete_product VALUES(1,10,1),(2,10,2),(3,11,2)');
R::exec('CREATE TABLE plagins_complete_gallery(id INTEGER,complete_id INTEGER,img TEXT)');
R::exec("INSERT INTO plagins_complete_gallery VALUES(1,10,''),(2,11,'')");
R::exec('CREATE TABLE review(id INTEGER)'); R::exec('INSERT INTO review VALUES(1),(2)');
R::exec('CREATE TABLE review_product(id INTEGER,review_id INTEGER,product_id INTEGER)');
R::exec('INSERT INTO review_product VALUES(1,1,1),(2,2,1),(3,2,2)');
R::exec('CREATE TABLE review_gallery(id INTEGER,review_id INTEGER,img TEXT)'); R::exec("INSERT INTO review_gallery VALUES(1,1,''),(2,2,'')");
R::exec('CREATE TABLE avito_ad(id INTEGER,article TEXT,complete_id INTEGER,status TEXT,stock_quantity INTEGER)');
R::exec("INSERT INTO avito_ad VALUES(1,'A',NULL,'active',5),(2,NULL,10,'active',3),(3,'B',NULL,'active',5)");
foreach (['order_product','chat_session','mail_oneclick','mail_request'] as $table) {
    R::exec("CREATE TABLE $table(id INTEGER,product_id INTEGER,name TEXT,price INTEGER,external INTEGER,mod_id INTEGER)");
    R::exec("INSERT INTO $table VALUES(1,1,'Historical name',123,0,1),(2,2,'Keep',100,0,2)");
}
function deletionCheck($ok,$label) { if (!$ok) throw new RuntimeException($label); }
(new \app\services\ProductDeletion($imageRoot))->delete(1);
deletionCheck(!R::getCell('SELECT id FROM product WHERE id=1'),'Product removed');
foreach (R::inspect() as $table) {
    $cols = R::inspect($table);
    if (isset($cols['product_id'])) deletionCheck(!R::getCell("SELECT COUNT(*) FROM $table WHERE product_id=1"),'No orphan in '.$table);
}
deletionCheck(R::getCell('SELECT COUNT(*) FROM related_product')==1 && R::getCell('SELECT COUNT(*) FROM similar_product')==1,'Reverse links removed');
deletionCheck(!R::getCell('SELECT COUNT(*) FROM plagins_complete_product WHERE complete_id=10'),'Whole composition removed');
deletionCheck(R::getCell('SELECT COUNT(*) FROM plagins_complete WHERE id=11')==1,'Other set intact');
deletionCheck(R::getCell('SELECT COUNT(*) FROM review')==1 && R::getCell('SELECT id FROM review')==2,'Shared review survives');
deletionCheck(R::getCell('SELECT name FROM order_product WHERE id=1')==='Historical name','Order snapshot kept');
deletionCheck(R::getCell('SELECT price FROM order_product WHERE id=1')==123,'Order amount kept');
deletionCheck(R::getCell('SELECT COUNT(*) FROM avito_ad WHERE id IN(1,2) AND status=\'archived\'')==2,'Listings archived');
deletionCheck(!is_file($imageRoot.'/product/gallery/remove.jpg'),'Gallery file removed');
deletionCheck(is_file($imageRoot.'/product/baseimg/shared.jpg'),'Shared file kept');
R::exec("CREATE TRIGGER fail_delete BEFORE DELETE ON product WHEN OLD.id=4 BEGIN SELECT RAISE(ABORT,'test rollback'); END");
try { (new \app\services\ProductDeletion($imageRoot))->delete(4); throw new RuntimeException('Missing failure'); }
catch (\RedBeanPHP\RedException\SQL $expected) {}
deletionCheck(R::getCell('SELECT id FROM product WHERE id=4')==4 && R::getCell('SELECT COUNT(*) FROM gallery WHERE product_id=4')==1,'Transaction rollback');
deletionCheck(is_file($imageRoot.'/product/gallery/rollback.jpg'),'Rollback keeps files');
unlink($imageRoot.'/product/gallery/rollback.jpg'); unlink($imageRoot.'/product/baseimg/shared.jpg');
rmdir($imageRoot.'/product/gallery');rmdir($imageRoot.'/product/baseimg');rmdir($imageRoot.'/product');rmdir($imageRoot);
echo "Product deletion: relations, shared assets, historical snapshots, rollback OK\n";
