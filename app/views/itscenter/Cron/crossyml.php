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
					$text.= "<category id=\"4\">Фильтры</category>";
	$category = \R::getAll("SELECT id, name, parent_id FROM `category` WHERE hide ='show' AND parent_id = '4'");
	foreach($category as $cat) {
		if($cat["parent_id"] =="0"){ $parent = ""; }
		else { $parent = "parentId='".$cat["parent_id"]."'"; }
		$text.= "<category id=\"".$cat["id"]."\" ".$parent.">".$cat["name"]."</category>"; 
	}	

	$text.= "</categories>				
				<offers>";

	$offers = \R::getAll("SELECT product.*, product.id AS prod_id, plagins_cross_vendor.name as vendor, plagins_cross.cross_name, plagins_cross.cross_abbreviated_name, brand.name as brand_name, category.name as category_name FROM product JOIN category ON category.id = product.category_id JOIN plagins_cross ON plagins_cross.product_id = product.id JOIN plagins_cross_vendor ON plagins_cross_vendor.id = plagins_cross.vendor_id JOIN brand ON brand.id = product.brand_id AND product.hide ='show' AND product.article != '' AND product.img != '' AND product.price !='0' AND product.stock_status_id !='0'");
	foreach($offers as $offer) {
		
		if($offer["quantity"]==0){ $available = "false"; }
		else { $available = "true"; }
		if($offer["img"] != "") { $img = "".PATH."/images/product/baseimg/".$offer["img"].""; }
        else { $img = ""; }
		
		if($offer['category_name']=="Воздушные фильтры") { $brand = "воздушный фильтр"; $brandname2 = "Фильтр воздушный"; }
		if($offer['category_name']=="Гидравлические фильтры") { $brand = "гидравлический фильтр"; $brandname2 = "Фильтр гидравлический"; } 
		if($offer['category_name']=="Масляные фильтры") { $brand = "масляный фильтр"; $brandname2 = "Фильтр масляный"; } 
		if($offer['category_name']=="Фильтры охлаждающей жидкости") { $brand = "фильтр охлаждающей жидкости"; $brandname2 = "Фильтр охлаждающей жидкости"; } 
		if($offer['category_name']=="Топливные фильтры") { $brand = "топливный фильтр"; $brandname2 = "Фильтр топливный"; } 
		if($offer['category_name']=="Фильтры салона (кабины)") { $brand = "фильтр кабины"; $brandname2 = "Фильтра кабины"; } 
		if($offer['category_name']=="Фильтры сапуна") { $brand = "фильтр сапуна"; $brandname2 = "Фильтра сапуна"; }
		if($offer['category_name']=="Фильтры осушители") { $brand = "фильтр осушитель"; $brandname2 = "Фильтра осушитель"; }
		$vendor=str_replace("&","",$offer["vendor"]);
		
		$desc = "Компания ИТС-Центр предлагает купить аналог фильтра ".$vendor." ".$offer["cross_name"]." по низким ценам. Наименование аналога: ".$brand." ".$offer["model"]." ".$offer["brand_name"].". Доставка по всей России транспортными компаниями. До транспортной компании довозим бесплатно, вам останеться только получить заказ в своём городе.";
		  
			$text.= "<offer id=\"".$offer["article"]."\" available=\"".$available."\">
                      <url>".PATH."/cross/".$offer["cross_abbreviated_name"]."</url>
                      <price>".$offer["price"]."</price>
					  <oldprice>".$offer["price_rrs"]."</oldprice>
                      <currencyId>RUR</currencyId>
                      <categoryId>".$offer["category_id"]."</categoryId>
                      <picture>".$img."</picture>					  
					  <store>true</store>
  				      <pickup>true</pickup>
  				      <delivery>true</delivery>
					  <local_delivery_cost></local_delivery_cost>
                      <name>$brandname2 ".$offer["model"]." ".$offer["brand_name"]." аналог для фильтра ".$offer["cross_name"]." ".$vendor."</name>
					  <model>".$offer["model"]."</model>
                      <vendor>".$offer["brand_name"]."</vendor>                     
					  <sales_notes></sales_notes>
					  <description>".$desc."</description>";
					  
		$params = \R::getAll("SELECT * FROM attribute JOIN product_attribute ON attribute.id = product_attribute.attribute_id AND product_attribute.product_id = '".$offer["prod_id"]."' ORDER BY attribute.attribute_name");
		foreach($params as $param) {
			$text.= "<param name=\"".$param["attribute_name"]."\">".$param["attribute_text"]."</param>";
		}
		$text.= "<country_of_origin></country_of_origin>
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