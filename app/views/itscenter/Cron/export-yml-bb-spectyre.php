<?php 

use ishop\App;
$date = date("Y-m-d H:i:s");
$datetime= date("c", strtotime("".$date.""));
$date_update = date("Y-m-d H:i");
$viewcrons = \R::findOne('cron', 'id = ?', [$_GET["id"]]);
$fd = fopen("cron/".$viewcrons["url_download"]."", 'w+') or die("не удалось создать файл");
    $text = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
              <yml_catalog date=\"".$datetime."\">
              <shop>
                <name>ИТС-Центр</name>
                <company>ООО ИТС-Центр</company>
                <url>".PATH."</url>
				<platform>BSM/Yandex/Market</platform>
				<version>2.5.8</version>
				<categories>";
		
	$category = \R::getAll("SELECT id, name, parent_id FROM `category` WHERE parent_id = '1' AND hide ='show'");
	foreach($category as $cat) {
		if($cat["parent_id"] =="0"){ $parent = ""; }
		else { $parent = "parentId='".$cat["parent_id"]."'"; }
		$text.= "<category id=\"".$cat["id"]."\" ".$parent.">".$cat["name"]."</category>"; 
	}	

	$text.= "</categories>				
				<offers>";

	$offers = \R::getAll("SELECT product.*, product.id AS prod_id, brand.name as vendor FROM product
	JOIN brand ON brand.id = product.brand_id
	JOIN category ON product.category_id = category.id
	AND category.parent_id = '1' AND product.hide ='show' AND product.article != '' AND product.img != '' AND product.price !='0' AND product.stock_status_id !='0'");
	foreach($offers as $offer) {
		
		if($offer["quantity"]==0){ $available = "finished"; }
		else { $available = "count"; }
		if($offer["img"] != "") { $img = "".PATH."/images/product/baseimg/".$offer["img"].""; }
        else { $img = ""; }
		$summands = round($offer["price"] * 0.2 / 1.2);
		$summabeznds = $offer["price"] - $summands;
			$text.= "<offer id=\"".$offer["article"]."\" available=\"".$available."\">                      
                      <price>".$offer["price"]."</price>
					  <price_without_nds>".$summabeznds."</price_without_nds>
					  <nds>20</nds>
					  <quantity>".$offer["quantity"]."</quantity>					  
                      <currencyId>RUR</currencyId>
					  <unitName>Штука</unitName>
					  <b2b_category_id>176711</b2b_category_id>
                      <categoryId>".$offer["category_id"]."</categoryId>
                      <picture>".$img."</picture>					  					  
                      <name>".$offer["name"]."</name>					  
                      <vendor>Китай</vendor>
					  <description></description>";
		$params = \R::getAll("SELECT * FROM attribute JOIN product_attribute ON attribute.id = product_attribute.attribute_id AND product_attribute.product_id = '".$offer["prod_id"]."' ORDER BY attribute.attribute_name");
		foreach($params as $param) {
			$text.= "<param name=\"".$param["attribute_name"]."\">".$param["attribute_text"]."</param>";
		}
		$text.= "<weight>".$offer["weight"]."</weight>
				<dimensions></dimensions>
		</offer>";
	}
	$text.= "</offers>
	  </shop>
	</yml_catalog>";
	
fwrite($fd, $text);
fclose($fd);

$xcol = array_key_last($updt);
if($xcol <= $cnt ){ 
	\R::exec("UPDATE cron SET date_update = '".$date_update."' WHERE id = '".$_GET["id"]."'");
	if($_SESSION['user']['id']) { \R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','49','cron','".$_GET["id"]."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')"); }
	else { \R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','51','cron','".$_GET["id"]."','".date('Y-m-d H:i:s')."','NULL')");  }	
}
$_SESSION['success'] = 'Задание "'.$viewcrons["name"].'" выполнено!';
redirect("".PATH."/admin/cron");
?>