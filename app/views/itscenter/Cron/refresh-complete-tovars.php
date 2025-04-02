<?php 

use ishop\App;

$date_price = date("Y-m-d");
$date_update = date("Y-m-d H:i:s");
$viewcrons = \R::findOne('cron', 'id = ?', [$_GET["id"]]);

if($viewcrons["alias"]==""){ $fileprod = "".$crons["alias"].""; $cron_id = $crons["id"]; }
else { $fileprod = "".$viewcrons["alias"].""; $cron_id = $viewcrons["id"]; }

$completes = \R::getAll("SELECT product.id, product.price FROM `plagins_complete_product`, `product` WHERE product.id = plagins_complete_product.product_id");

foreach($completes as $complete){
	
	$updtcpl = \R::exec("UPDATE plagins_complete_product SET price = '".$complete["price"]."' WHERE product_id = '".$complete["id"]."'");
	
}

\R::exec("UPDATE cron SET date_update = '".$date_update."' WHERE id = '".$cron_id."'");
$_SESSION['success'] = 'Задание "'.$viewcrons["name"].'" выполнено!';
redirect("".PATH."/admin/cron");