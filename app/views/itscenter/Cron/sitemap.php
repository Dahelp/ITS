<?php 

use ishop\App;

$date_update = date("Y-m-d H:i:s");
$viewcrons = \R::findOne('cron', 'id = ?', [2]);
$fd = fopen("cron/".$viewcrons["url_download"]."", 'w+') or die("не удалось создать файл");
			$text = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
			<urlset
				xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"
				xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
				xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9
					http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">";
				
				$sm_atgroup = \R::getAll("SELECT id, url_params FROM `attribute_group` WHERE url_params !=''");
				if($sm_atgroup){
					foreach($sm_atgroup as $smatg) {  
					
						$sm_atvalue = \R::getAll("SELECT alias FROM `attribute_value` WHERE attr_group_id = '".$smatg["id"]."' AND hide='show'");
						if($sm_atvalue){
							foreach($sm_atvalue as $atvl) {
								$text.= "<url><loc>".PATH."/".$smatg["url_params"]."/".$atvl['alias']."</loc></url>";
							}
						}       
					}	 
				}
				$sm_product = \R::getAll("SELECT alias FROM `product` WHERE hide='show'");
				if($sm_product){
					foreach($sm_product as $smp) {     
						$text.= "<url><loc>".PATH."/product/".$smp['alias']."</loc></url>";       
					}	 
				}
				$sm_content_type = \R::getAll("SELECT id, param_url FROM `content_type` WHERE hide='show'");
				if($sm_content_type){
					foreach($sm_content_type as $type) { 
						$sm_content = \R::getAll("SELECT alias FROM `contents` WHERE type_id = '".$type["id"]."' AND hide='show'");
						if($sm_content){
							foreach($sm_content as $cont) {
								$text.= "<url><loc>".PATH."/".$type["param_url"]."/".$cont['alias']."</loc></url>";
							}
						}						
					}	 
				}
				$sm_category = \R::getAll("SELECT alias FROM `category` WHERE hide='show'");
				if($sm_category){
					foreach($sm_category as $smc) {     
						$text.= "<url><loc>".PATH."/category/".$smc['alias']."</loc></url>";       
					}	 
				}
				$sm_cross = \R::getAll("SELECT cross_abbreviated_name FROM `plagins_cross`");
				if($sm_cross){
					foreach($sm_cross as $cross) {
						$cross_abbreviated_name = strtolower($cross['cross_abbreviated_name']);
						$text.= "<url><loc>".PATH."/cross/".$cross_abbreviated_name."</loc></url>";       
					}	 
				}
				$sm_technics = \R::getAll("SELECT alias FROM `technics`");
				if($sm_technics){
					foreach($sm_technics as $technics) {     
						$text.= "<url><loc>".PATH."/technics/".$technics['alias']."</loc></url>";       
					}	 
				}
				
			$text.= "</urlset>";
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