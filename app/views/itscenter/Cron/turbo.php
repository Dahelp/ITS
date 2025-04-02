<?php 

use ishop\App;

$date = date("Y-m-d H:i:s");
$datetime= date("c", strtotime("".$date.""));
$date_update = date("Y-m-d H:i");
$fd = fopen("xml/turbo.xml", 'w+') or die("не удалось создать файл");
    $text = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
              <yml_catalog date=\"".$datetime."\">
              <shop>
                <name></name>
                <company></company>
                <url>".PATH."</url>
                <currencies>
                  <currency id=\"RUR\" rate=\"1\"/>
                </currencies>
				<categories>";
		
	$category = \R::getAll("SELECT id, name, parent_id FROM `category` WHERE hide ='show'");
	foreach($category as $cat) {
		if($cat["parent_id"] =="0"){ $parent = ""; }
		else { $parent = "parentId='".$cat["parent_id"]."'"; }
		$text.= "<category id=\"".$cat["id"]."\" ".$parent.">".$cat["name"]."</category>"; 
	}	

	$text.= "</categories>				
				<offers>";

	$offers = \R::getAll("SELECT product.*, product.id AS prod_id, brand.name as vendor FROM product JOIN brand ON brand.id = product.brand_id AND product.hide ='show' AND product.article != '' AND product.img != '' AND product.price !='0'");
	foreach($offers as $offer) {
		
		if($offer["quantity"]==0){ $available = "false"; }
		else { $available = "true"; }
		if($offer["img"] != "") { $img = "".PATH."/images/product/baseimg/".$offer["img"].""; }
        else { $img = ""; }
		$desc = "Компания ИТС-Центр является официальным поставщиком продукции ".$offer["vendor"]." и предлагает купить ".$offer["name"]." по низким ценам. Доставка по всей России транспортными компаниями. До транспортной компании довозим бесплатно, вам останеться только получить заказ в своём городе.";
		  
			$text.= "<offer id=\"".$offer["article"]."\" available=\"".$available."\">
                      <url>".PATH."/product/".$offer["alias"]."</url>
                      <price>".$offer["price"]."</price>					 
                      <currencyId>RUR</currencyId>
                      <categoryId>".$offer["category_id"]."</categoryId>
                      <picture>".$img."</picture>
					  <quantity>".$offer["quantity"]."</quantity>
					  <store>true</store>
  				      <pickup>true</pickup>
  				      <delivery>true</delivery>
					  <local_delivery_cost></local_delivery_cost>
                      <name>".$offer["name"]."</name>
					  <model>".$offer["model"]."</model>
                      <vendor>".$offer["vendor"]."</vendor>                     
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
		
}
redirect();
?>