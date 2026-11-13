<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/config/init.php'; require CONF.'/db_bootstrap.php';
$p=R::getRow('SELECT id,article,name FROM product WHERE article = ?',['11140']);
if (!$p) { throw new RuntimeException('Не найдена шина 11140'); }
$group=R::getRow('SELECT * FROM attribute_group WHERE title = ?',['Протектор']);
if (!$group) { throw new RuntimeException('Не найдена характеристика Протектор'); }
$good=R::getRow('SELECT * FROM attribute_value WHERE attr_group_id = ? AND value = ?',[$group['id'],'P350']);
$bad=R::getRow('SELECT * FROM attribute_value WHERE attr_group_id = ? AND value = ?',[$group['id'],'P377']);
if (!$good || !$bad) { throw new RuntimeException('Не найдены значения P350/P377'); }
$before=R::getAll('SELECT * FROM attribute_product WHERE product_id = ?',[$p['id']]);
$backup=ROOT.'/storage/p350-11140-tread-before-20261016.json';
if (!is_file($backup) && file_put_contents($backup,json_encode(['product'=>$p,'attributes'=>$before],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))===false) {
 throw new RuntimeException('Не сохранена резервная копия');
}
R::begin();
try {
 R::exec('DELETE FROM attribute_product WHERE product_id = ? AND attr_id = ?',[$p['id'],$bad['id']]);
 if (!R::getCell('SELECT product_id FROM attribute_product WHERE product_id = ? AND attr_id = ?',[$p['id'],$good['id']])) {
  R::exec('INSERT INTO attribute_product (product_id,attr_id) VALUES (?,?)',[$p['id'],$good['id']]);
 }
 R::commit();
} catch (Throwable $e) { R::rollback(); throw $e; }
echo json_encode(['product'=>$p,'tread'=>R::getCol('SELECT av.value FROM attribute_product ap JOIN attribute_value av ON av.id=ap.attr_id WHERE ap.product_id = ? AND av.attr_group_id = ?',[$p['id'],$group['id']])],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
