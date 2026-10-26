<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/config/init.php';require CONF.'/db_bootstrap.php';
foreach(['payment_method'=>'VARCHAR(32) NULL DEFAULT NULL','payment_customer_type'=>'TINYINT UNSIGNED NULL DEFAULT NULL']as$name=>$type){
 $exists=R::getRow('SHOW COLUMNS FROM `order` LIKE ?',[$name]);
 if(!$exists){R::exec('ALTER TABLE `order` ADD COLUMN `'.$name.'` '.$type);echo $name." added\n";}else echo $name." already exists\n";
}
