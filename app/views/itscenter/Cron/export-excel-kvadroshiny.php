<?php 

use ishop\App;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$date = date("Y-m-d H:m");
$date_update = date("Y-m-d H:i");
$viewcrons = \R::findOne('cron', 'id = ?', [$_GET["id"]]);

//Создаем экземпляр класса электронной таблицы
$spreadsheet = new Spreadsheet();
//Получаем текущий активный лист
$sheet = $spreadsheet->getActiveSheet();
$sheet->getColumnDimension('B')->setAutoSize(true);
// Записываем в ячейку A1 данные
$sheet->setCellValue('A1', 'ID (артикул)');
$sheet->setCellValue('B1', 'Номенклатура');
$sheet->setCellValue('C1', 'Наличие');
$sheet->setCellValue('D1', 'Цена');

$products = \R::getAll("SELECT product.article, product.name, product.quantity, product.price FROM product WHERE product.category_id = ? AND product.hide = ?", [2, 'show']);

$i = 2;
foreach($products as $prod) {
	$pos = $i++;
	
	$sheet->setCellValue('A'.$pos.'', ''.$prod["article"].'');
	$sheet->setCellValue('B'.$pos.'', ''.$prod["name"].'');
	$sheet->setCellValue('C'.$pos.'', ''.$prod["quantity"].'');
	$sheet->setCellValue('D'.$pos.'', ''.$prod["price"].'');
}

// Выбросим исключение в случае, если не удастся сохранить файл
$writer = new Xlsx($spreadsheet);
$writer->save("cron/".$viewcrons["url_download"]."");
	
$xcol = array_key_last($updt);
if($xcol <= $cnt ){ 
	\R::exec("UPDATE cron SET date_update = '".$date_update."' WHERE id = '".$_GET["id"]."'");
	if($_SESSION['user']['id']) { \R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','49','cron','".$_GET["id"]."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')"); }
	else { \R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','51','cron','".$_GET["id"]."','".date('Y-m-d H:i:s')."','NULL')");  }	
}
	
$_SESSION['success'] = 'Задание "'.$viewcrons["name"].'" выполнено!';
redirect("".PATH."/admin/cron");