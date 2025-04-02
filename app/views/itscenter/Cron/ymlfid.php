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
		
	$category = \R::getAll("SELECT id, name, parent_id FROM `category` WHERE hide ='show'");
	foreach($category as $cat) {
		if($cat["parent_id"] =="0"){ $parent = ""; }
		else { $parent = "parentId='".$cat["parent_id"]."'"; }
		$text.= "<category id=\"".$cat["id"]."\" ".$parent.">".$cat["name"]."</category>"; 
	}	

	$text.= "</categories>				
				<offers>";

	$offers = \R::getAll("SELECT product.*, product.id AS prod_id, brand.name as vendor FROM product JOIN brand ON brand.id = product.brand_id AND product.hide ='show' AND product.article != '' AND product.img != '' AND product.price !='0' AND product.stock_status_id !='0'");
	foreach($offers as $offer) {
		
		if($offer["quantity"]==0){ $available = "false"; }
		else { $available = "true"; }
		if($offer["img"] != "") { $img = "".PATH."/images/product/baseimg/".$offer["img"].""; }
        else { $img = ""; }
		$desc = "Компания ИТС-Центр является официальным поставщиком продукции ".$offer["vendor"]." и предлагает купить ".$offer["name"]." по низким ценам. Доставка по всей России транспортными компаниями. До транспортной компании довозим бесплатно, вам останеться только получить заказ в своём городе.";
		$desc = strip_tags($desc);
		$desc = substr($desc, 0, 600);
		$desc = rtrim($desc, "!,.-");
		$desc = substr($desc, 0, strrpos($desc, ' '));
		
			$text.= "<offer id=\"".$offer["article"]."\" available=\"".$available."\">
                      <url>".PATH."/product/".$offer["alias"]."</url>
                      <price>".$offer["price"]."</price>";
			if($offer["price_rrs"] =="" or $offer["price_rrs"] =="0") {}else{
				$text.= "<oldprice>".$offer["price_rrs"]."</oldprice>";
			}
			if($offer["category_id"] == 2 or $offer["category_id"] == 9 or $offer["category_id"] == 18 or $offer["category_id"] == 19 or $offer["category_id"] == 20 or $offer["category_id"] == 21 or $offer["category_id"] == 22 or $offer["category_id"] == 23 or $offer["category_id"] == 24 or $offer["category_id"] == 35 or $offer["category_id"] == 36){ $type = "Шины"; }
			if($offer["category_id"] == 26 or $offer["category_id"] == 27 or $offer["category_id"] == 28 or $offer["category_id"] == 29 or $offer["category_id"] == 30){ $type = "Диск колёсный"; }
			if($offer["category_id"] == 10){ $type = "Фильтр воздушный"; }
			if($offer["category_id"] == 11){ $type = "Фильтр гидравлический"; }
			if($offer["category_id"] == 12){ $type = "Фильтр масляный"; }
			if($offer["category_id"] == 13){ $type = "Фильтр топливный"; }
			if($offer["category_id"] == 14){ $type = "Фильтр салона"; }
			if($offer["category_id"] == 15){ $type = "Фильтр осушитель"; }
			if($offer["category_id"] == 16){ $type = "Фильтр охлаждающей жидкости"; }
			if($offer["category_id"] == 17){ $type = "Фильтр сапуна"; }
			if($offer["category_id"] == 31){ $type = "Камера автомобильная"; }
			if($offer["category_id"] == 32){ $type = "Ободная лента"; }
			if($offer["category_id"] == 33){ $type = "Уплотнительное кольцо"; }
			
			$params = \R::getAll("SELECT * FROM attribute JOIN product_attribute ON attribute.id = product_attribute.attribute_id AND product_attribute.product_id = '".$offer["prod_id"]."' ORDER BY attribute.attribute_name");				  
			
			foreach($params as $par) {
				if($par["attribute_id"]==4){ $tipsize[$offer["prod_id"]] = "".$par["attribute_text"]." "; }
				if($par["attribute_id"]==5){ $pr[$offer["prod_id"]] = " ".$par["attribute_text"]."PR"; }
				if($par["attribute_id"]==16){ $tip[$offer["prod_id"]] = " ".$par["attribute_text"].""; }
			}
			if($tipsize){
				$model = "".$tipsize[$offer["prod_id"]]."".$offer["model"]."".$pr[$offer["prod_id"]]."".$tip[$offer["prod_id"]]."";
			}else{
				$model = "".$offer["model"]."";
			}
            $text.= "<currencyId>RUR</currencyId>
                      <categoryId>".$offer["category_id"]."</categoryId>
                      <picture>".$img."</picture>				  
                      <name>".$offer["name"]."</name>
					  <typePrefix>".$type."</typePrefix>
					  <vendor>".$offer["vendor"]."</vendor> 
					  <model>".$model."</model>                                          
					  <sales_notes></sales_notes>
					  <description>".$desc."</description>";
					  
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