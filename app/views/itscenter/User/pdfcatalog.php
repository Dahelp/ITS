<?php
$products = \R::getAll("SELECT `product`.`article`, `product`.`model`, `product`.`name`, `product`.`quantity`, `product`.`alias`, `product`.`opt_price`, `product`.`price`, `brand`.`name` as vendor FROM `product` JOIN `brand` ON `product`.`brand_id` = `brand`.`id` WHERE product.category_id IN ('10', '11', '12', '13', '14', '15', '16', '17') AND product.hide = ?".$sql_where_brand."", ['show']);
					
$html = '<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
				<style type="text/css">
					* {font-family: "DejaVu Sans", sans-serif;font-size: 14px;line-height: 14px;padding:0;margin:15px}
					table {margin: 0 0 10px 0;width: 100%;border-collapse: collapse;border-spacing: 0;}
					.header {margin: 0 0 0 0;padding: 0 0 10px 0;font-size: 14px;line-height: 14px;text-align: center;font-weight:bold}
					.header_info {margin: 0 0 0 0;padding: 0 0 5px 0;font-size: 9px;line-height: 12px;text-align: center;letter-spacing:3px;font-weight:bold}
					.inform{font-size:8px;line-height: 8px;text-align:center;margin:0;padding:0}
					h1 {margin: 0 0 10px 0;padding: 10px 0;border-bottom: 2px solid #000;font-weight: bold;font-size: 14px;}					

					/* Реквизиты банка */
					.details td {padding: 3px 2px;border: 1px solid #000000;font-size: 10px;line-height: 10px;vertical-align: top;}			 

					/* Поставщик/Покупатель */
					.contract th {padding: 3px 0;vertical-align: top;text-align: left;font-size: 9px;line-height: 9px;}
					.contract td {padding: 3px 0;font-size: 9px;}	 

					/* Наименование товара, работ, услуг */
					.list thead, .list tbody  {border: 2px solid #000;}
					.list thead th {padding: 2px 0;border: 1px solid #000;vertical-align: middle;text-align: center;font-size: 10px;}
					.list tbody td {padding: 0 2px;border: 1px solid #000;vertical-align: middle;font-size: 8px;line-height: 8px;}
					.list tfoot th {padding: 3px 2px;border: none;text-align: right;font-size: 9px;line-height: 9px;}			 

					/* Сумма */
					.total {margin: -10px 0 10px 0;}
					.total p {margin: 0;padding: 0;font-size: 9px;line-height: 9px;}					
					.total strong{margin: 0;padding: 0;font-size: 9px;line-height: 9px;}
					
					/*Договор*/
					.dogovor1 {padding:0;margin:0}
					.dogovor1 p{font-size:8px;line-height: 8px;padding:0;margin:0}
					.dogovor2 {margin: 0 0 5px 0;padding: 0 0 5px 0;border-bottom: 2px solid #000;}
					.dogovor2 p{font-size:8px;line-height: 8px;padding:0;margin:0}
					.dogovor2 p.dogovor3 {font-weight:bold;font-size: 10px;line-height: 10px;padding:10px 0 0 0}
					
					/* Руководитель, бухгалтер */
					.sign {position: relative;margin:0;padding:0}
					.sign table {width: 100%;margin:0;padding:0}					
					.sign_td_1 {height:20px;text-align: left;font-weight:bold;font-size: 9px;}
					.sign_td_2 {height:20px;font-weight:bold;font-size: 9px;text-align: center;border-bottom: 1px solid #000;margin:0 10px;padding:0 0 3px 0}
					.pdp_1 {position: absolute;top:2px;left:460px;font-size: 8px;font-weight:normal;}
					.pdp_2 {position: absolute;top:26px;left:460px;font-size: 8px;font-weight:normal;}
					.pdp_3 {position: absolute;top:2px;left:260px;font-size: 8px;font-weight:normal;}
					.pdp_4 {position: absolute;top:2px;left:585px;font-size: 8px;font-weight:normal;}
					.pdp_5 {position: absolute;top:26px;left:585px;font-size: 8px;font-weight:normal;}
					.pdp_6 {position: absolute;top:51px;left:585px;font-size: 8px;font-weight:normal;}
					.sign-1 {position: absolute;left: 425px;top: -34px;width:100px}
					.sign-2 {position: absolute;left: 425px;top: -7px;width:100px}
					.printing {position: absolute;left: 570px;top: -20px;width:120px}
				</style>
			</head>
			<body>';
				
					foreach ($products as $item => $row) {
	
						$prod = \R::findOne('product', 'id = ?', [$row['product_id']]);

						$html .= '<div>' . $row['name'] . '</div>';
						
					}			 
					
			$html .= '</body>
</html>';

$dompdf = new Dompdf\Dompdf();
$dompdf->set_option('isRemoteEnabled', TRUE);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();
 
// Вывод файла в браузер:
$dompdf->stream('Каталог'); 

exit();		
	
?>