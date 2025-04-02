<?php

namespace app\controllers\admin;

use app\models\admin\Product;
use ishop\App;
use app\models\AppModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends AppController {

    public function indexAction(){
		
		if($_POST) {		
		
			if($_POST["format"] == "xls_price_roznica") {
				$date = date('dmY');

				//Создаем экземпляр класса электронной таблицы
				$spreadsheet = new Spreadsheet();
				//Получаем текущий активный лист
				$sheet = $spreadsheet->getActiveSheet();
				$sheet->getColumnDimension('B')->setAutoSize(true);
				$sheet->getColumnDimension('E')->setAutoSize(true);
				// Записываем в ячейку A1 данные
				$sheet->setCellValue('A1', 'ID (артикул)');
				$sheet->setCellValue('B1', 'Номенклатура');
				$sheet->setCellValue('C1', 'Наличие');
				$sheet->setCellValue('D1', 'Цена');
				$sheet->setCellValue('E1', 'Ссылка на товар');
				
				$products = \R::find('product', "article IN (".$_POST["article"].")");

				$i = 2;
				foreach($products as $prod) {
					$pos = $i++;
					
					$sheet->setCellValue('A'.$pos.'', ''.$prod["article"].'');
					$sheet->setCellValue('B'.$pos.'', ''.$prod["name"].'');
					$sheet->setCellValue('C'.$pos.'', ''.$prod["quantity"].'');
					$sheet->setCellValue('D'.$pos.'', ''.$prod["price"].'');
					$sheet->setCellValue('E'.$pos.'', ''.PATH.'/product/'.$prod["alias"].'');
					
					$sheet->getCell('E'.$pos.'')->getHyperlink()->setUrl(''.PATH.'/product/'.$prod["alias"].'');
					$sheet->getCell('E'.$pos.'')->getHyperlink()->setTooltip('Переход в карточку товара на сайт');
					$sheet->getStyle('E'.$pos.'')->applyFromArray(
						array(
							'font' => array(
								'color' => array(
								'rgb' => '0000FF'
								), 
							'underline' => 'single'
							)
						)
					);
				}

				// Выбросим исключение в случае, если не удастся сохранить файл
				$writer = new Xlsx($spreadsheet);
				$writer->save("xls/export-its-".$date.".xlsx");
					
				$_SESSION['success'] = 'Экспорт выполнен! Скачать файл: <a href="'.PATH.'/public/xls/export-its-'.$date.'.xlsx">'.PATH.'/public/xls/export-its-'.$date.'.xlsx</a>';
				redirect("".PATH."/admin/export/index");
			}
			if($_POST["format"] == "xml_price_roznica") {
				
				$products = \R::find('product', "article IN (".$_POST["article"].")");
				$date = date("Y-m-d H:m");
				$dates = date('dmY');
				$fd = fopen("xml/export-its-".$dates.".xml", 'w+') or die("не удалось создать файл");
				$text = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
						  <yml_catalog date=\"".$date."\">
						  <shop>
							<name></name>
							<company></company>
							<url>".PATH."</url>
							<currencies>
							  <currency id=\"RUR\" rate=\"1\"/>
							</currencies>
							<categories>";				

				$category = \R::getAll("SELECT category.id, category.name, category.parent_id FROM `category`, `product` WHERE category.id = product.category_id AND category.hide ='show' AND product.article IN (".$_POST["article"].") GROUP BY category.id");
				foreach($category as $cat) {
					if($cat["parent_id"] =="0"){ $parent = ""; }
					else { $parent = "parentId='".$cat["parent_id"]."'"; }
					$parent_name = \R::findOne('category', "id = ?", [$cat["parent_id"]]);
					$text.= "<category id=\"".$cat["parent_id"]."\">".$parent_name["name"]."</category>"; 
				}
				foreach($category as $cat) {
					if($cat["parent_id"] =="0"){ $parent = ""; }
					else { $parent = "parentId='".$cat["parent_id"]."'"; }
					$text.= "<category id=\"".$cat["id"]."\" ".$parent.">".$cat["name"]."</category>"; 
				}

				$text.= "</categories>				
							<offers>";

				foreach($products as $prod) {
					
					  $quantity = $prod['quantity'];
					  if($quantity==0){ $available = "false"; }
					  else { $available = "true"; }
					  
					  $vendor = \R::findOne('brand', "id = ?", [$prod["brand_id"]]);
				   
						$text.= "<offer id=\"".$prod['article']."\" available=\"".$available."\">
								  <url>".PATH."/product/".$prod["alias"]."</url>
								  <price>".$prod["price"]."</price>					 
								  <currencyId>RUR</currencyId>
								  <categoryId>".$prod["category_id"]."</categoryId>
								  <picture>".PATH."/images/product/baseimg/".$prod["img"]."</picture>
								  <quantity>".$quantity."</quantity>
								  <store>true</store>
								  <pickup>true</pickup>
								  <delivery>true</delivery>
								  <local_delivery_cost></local_delivery_cost>
								  <name>".$prod["name"]."</name>
								  <model>".$prod["model"]."</model>
								  <vendor>".$vendor->name."</vendor>                     
								  <sales_notes></sales_notes>
								  <description></description>";
								  
						$params = \R::getAll("SELECT * FROM attribute JOIN product_attribute ON attribute.id = product_attribute.attribute_id AND product_attribute.product_id = '".$prod["prod_id"]."' ORDER BY attribute.attribute_name");
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
			
			$_SESSION['success'] = 'Экспорт выполнен! Путь к файлу: <a href="'.PATH.'/public/xml/export-its-'.$dates.'.xml">'.PATH.'/public/xml/export-its-'.$dates.'.xml</a>';	
				
			}
		
		}
		
		$this->setMeta('Экспорт товаров');
	}
	
}