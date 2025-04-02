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
                <currencies>
                  <currency id=\"RUR\" rate=\"1\"/>
                </currencies>
				<categories>";
		
	$category = \R::getAll("SELECT category.id, category.name, category.parent_id FROM `category`, `plagins_complete` WHERE category.id = plagins_complete.category_id AND category.hide ='show' GROUP BY category.id");
	foreach($category as $cat) {
		if($cat["parent_id"] =="0"){ $parent = ""; }
		else { $parent = "parentId='".$cat["parent_id"]."'"; }
		$text.= "<category id=\"".$cat["id"]."\" ".$parent.">".$cat["name"]."</category>"; 
	}	

	$text.= "</categories>				
				<offers>";

	$offers = \R::getAll("SELECT * FROM plagins_complete WHERE hide ='show'");
	foreach($offers as $offer) {
		
		$prods = \R::getAll("SELECT * FROM plagins_complete_product WHERE complete_id=?", [$offer["id"]]);

		foreach($prods as $prod) {
			$price_complete[$offer["id"]] += $prod["price"]*$prod["qty"];
			$discount_complete[$offer["id"]] += $prod["discount"]*$prod["qty"];
			$quant = \R::findOne('product', 'id = ?', [$prod["product_id"]]);

			if($quant["quantity"]>=$prod["qty"]) {
				$quantity = 1;
			}else{
				$quantity = 0;
			}
			
			$itg_qty[$offer["id"]] += $quantity;		
		}
		$itog_price_complete[$offer["id"]] = $price_complete[$offer["id"]]-$discount_complete[$offer["id"]];
		if($discount_complete[$offer["id"]] != 0) { $old_price_complete[$offer["id"]] = $price_complete[$offer["id"]]; }else{ $old_price_complete[$offer["id"]] = ""; }
		if($itg_qty[$offer["id"]] == count($prods)) { $available = "true"; }else{ $available = "false"; }
		
		if($offer["img"] != "") { $img = "".PATH."/images/complete/baseimg/".$offer["img"].""; }
        else { $img = ""; }
		$desc = "Компания ИТС-Центр является официальным поставщиком продукции ".$offer["vendor"]." и предлагает купить ".$offer["name"]." по низким ценам. Доставка по всей России транспортными компаниями. До транспортной компании довозим бесплатно, вам останеться только получить заказ в своём городе.";
			if($available == "true") {
			$text.= "<offer id=\"C".$offer["id"]."\" available=\"".$available."\">
                      <url>".PATH."/complete/".$offer["alias"]."</url>
                      <price>".$itog_price_complete[$offer["id"]]."</price>
					  <oldprice>".$old_price_complete[$offer["id"]]."</oldprice>
                      <currencyId>RUR</currencyId>
                      <categoryId>".$offer["category_id"]."</categoryId>
                      <picture>".$img."</picture>					  
					  <store>true</store>
  				      <pickup>true</pickup>
  				      <delivery>true</delivery>					  
                      <name>".$offer["name"]."</name>
					  <model></model>
                      <vendor>EKKA</vendor>                     
					  <sales_notes></sales_notes>
					  <description>".$desc."</description>
					  <country_of_origin></country_of_origin>
		</offer>";
			}
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