<?php

namespace app\models\admin;

use app\models\AppModel;

class PlaginsCross extends AppModel{

    public $attributes = [
        'cross_id' => '',
        'product_id' => '',
		'vendor_id' => '',
		'cross_name' => '',
        'cross_abbreviated_name' => '',		
        'tip_cross' => '',
		'equipment_vendor' => '',
    ];

    public $rules = [
        'required' => [
            ['cross_id'],            
        ],        
    ];

	public function checkUnique(){
        $attribute = \R::findOne('plagins_cross', 'cross_name = ?', [$this->attributes['cross_name']]);
        if($attribute){
            if($attribute->cross_name == $this->attributes['cross_name']){
                $this->errors['unique'][] = 'Это название кросса уже существует';
            }
            return false;
        }
        return true;
    }
	
	public function exportCrossXml($product_id = null){
		
		$date = date("Y-m-d H:m");
		$fd = fopen("xml/export_cross.xml", 'w+') or die("не удалось создать файл");
		$text = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
					<yml_catalog date=\"".$date."\">
						<shop>
							<offers>";
		if($product_id) { $cross = \R::getAll("SELECT * FROM `product`, `plagins_cross`, `plagins_cross_vendor` WHERE product.id = plagins_cross.product_id AND plagins_cross.vendor_id = plagins_cross_vendor.id AND product.article = '".$product_id."'"); }
		else{ $cross = \R::getAll("SELECT * FROM `product`, `plagins_cross`, `plagins_cross_vendor` WHERE product.id = plagins_cross.product_id AND plagins_cross.vendor_id = plagins_cross_vendor.id"); }
		foreach($cross as $item) {					
			$text .= "<offer id=\"".$item["cross_id"]."\" available=\"true\">
						  <goods>".$item["article"]."</goods>				 
						  <manufacturer>".$item["name"]."</manufacturer>
						  <name>".$item["cross_name"]."</name>
						  <abbreviated>".$item["cross_abbreviated_name"]."</abbreviated>
						  <tip>".$item["tip_cross"]."</tip>
						  <equipment>".$item["equipment_vendor"]."</equipment>
					</offer>";
		}
					  
		$text .= "</offers>
            </shop>
        </yml_catalog>";
		fwrite($fd, $text);
		fclose($fd);
		$_SESSION['success'] = 'Кросс-номера в файл '.PATH.'/xml/export_cross.xml';

	}
	public function exportCrossCsv($product_id = null){
		
		$col = 1;
		if(!empty($product_id)) { 
		$cross = \R::getAll("SELECT * FROM `product`, `plagins_cross`, `plagins_cross_vendor` WHERE product.id = plagins_cross.product_id AND plagins_cross.vendor_id = plagins_cross_vendor.id AND product.article IN (".$product_id.")"); }
		else{ $cross = \R::getAll("SELECT * FROM `product`, `plagins_cross`, `plagins_cross_vendor` WHERE product.id = plagins_cross.product_id AND plagins_cross.vendor_id = plagins_cross_vendor.id"); }
		
		$csv_file .= '"Код";"Код номенклатуры";"Наименование";"Сокращенное наименование";"Производитель";"Часть фильтра";"Производитель техники"'."\r\n";		
		
		$fp = fopen("xls/export_cross.csv", "w+") or die("не удалось создать файл");
		fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
		
		foreach($cross as $item) {
			$col  = $col + 1;
			$csv_file .= '"'.$item["cross_id"].'";"'.$item["article"].'";"'.$item["name"].'";"'.$item["cross_name"].'";"'.$item["cross_abbreviated_name"].'";"'.$item["tip_cross"].'";"'.$item["equipment_vendor"].'"'."\r\n";
		}
		$fwrite_pro = fwrite($fp,trim($csv_file)); // записываем в файл строки
		fclose($fp); // закрываем файл
		$_SESSION['success'] = 'Кросс-номера в файл '.PATH.'/xls/export_cross.csv';
	}
	
}