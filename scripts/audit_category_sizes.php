<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/config/init.php';require CONF.'/db_bootstrap.php';
echo json_encode([
 'category_size_fields'=>array_values(array_filter(array_keys(R::getRow('SELECT * FROM category LIMIT 1')),fn($key)=>preg_match('/size|razmer/i',$key))),
 'related_examples'=>R::getAll("SELECT c.alias,c.name,av.alias AS filter_alias,av.value,COUNT(DISTINCT r.related_attr_value_id) AS related_sizes FROM attribute_value_related r JOIN attribute_value av ON av.id=r.attr_value_id JOIN attribute_value_category_canonical ac ON ac.attr_value_id=av.id AND ac.is_active=1 AND ac.mode='landing' JOIN category c ON c.id=ac.category_id GROUP BY c.id,av.id ORDER BY related_sizes DESC LIMIT 6"),
 'text_examples'=>R::getAll("SELECT alias,name,SUBSTRING(content,GREATEST(1,LOCATE('размер',content)-50),250) AS excerpt FROM category WHERE id NOT IN(43,44,45) AND (content LIKE '%Размеры шин:%' OR content LIKE '%Популярные размеры%' OR content LIKE '%Типоразмеры%') LIMIT 8"),
 'chain_sizes'=>R::getAll("SELECT av.id,av.value,av.alias,ac.category_id FROM attribute_value av JOIN attribute_group ag ON ag.id=av.attr_group_id JOIN attribute_value_category_canonical ac ON ac.attr_value_id=av.id WHERE ag.url_params='size' AND ac.category_id=45 AND ac.is_active=1 AND ac.mode='landing' AND av.hide='show' ORDER BY av.value")
],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
