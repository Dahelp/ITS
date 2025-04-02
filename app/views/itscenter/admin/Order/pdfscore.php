<?php 
if($comp["dogovor"] == 1) { $dogovor = "Договор"; }
if($comp["dogovor"] == 2) { $dogovor = "Счет-договор"; }
if($comp["dogovor"] == "") { $dogovor = "Счет-договор"; }
if($user["groups"] == 3){ $poluchatel = "Частное лицо"; $otpravitel = "Частное лицо"; }
if($user["groups"] == 4){ $poluchatel = "".$comp["comp_name"].", ИНН ".$comp["inn"].", ".$comp["url_address"].", ".$user["telefon"].""; $otpravitel = "".$comp["comp_name"].", ИНН ".$comp["inn"].", ".$comp["url_address"].", ".$user["telefon"].""; }
if($comp["nds"] == 1) { $nds_comp = "20%"; }
if($comp["nds"] == 2) { $nds_comp = "без НДС"; }
if($seller['id'] == "1") { $dolzhnost = "Индивидуальный предприниматель"; $rukovod = "Шишкарёв Д. В."; $pech = "pech-1"; $pdp = "pdp-1"; }
if($seller['id'] == "2") { $dolzhnost = "Индивидуальный предприниматель"; $rukovod = "Романов В. М."; $pech = "pech-2"; $pdp = "pdp-2"; }
if($seller['id'] == "3") { $dolzhnost = "Генеральный директор"; $rukovod = "Цыхоня В. А."; $pech = "pech-3"; $pdp = "pdp-3"; }

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
			<body>
				<p class="header">'.$seller['comp_name'].'</p>
				<p class="header_info">ВНИМАНИЕ! ИЗМЕНИЛИСЬ БАНКОВСКИЕ РЕКВИЗИТЫ!</p>
				<table class="details">
					<tbody>
						<tr>
							<td colspan="2" style="border-bottom: none;">'.$seller['bank'].'</td>
							<td>БИК</td>
							<td style="border-bottom: none;">'.$seller['bik'].'</td>
						</tr>
						<tr>
							<td colspan="2" style="border-top: none; font-size: 8px;">Банк получателя</td>
							<td>Корр. Сч.</td>
							<td style="border-top: none;">'.$seller['korschet'].'</td>
						</tr>
						<tr>
							<td width="25%">ИНН '.$seller['inn'].'</td>
							<td width="30%">КПП '.$seller['kpp'].'</td>
							<td width="10%" rowspan="3">Расч. Сч.</td>
							<td width="35%" rowspan="3">'.$seller['raschet'].'</td>
						</tr>
						<tr>
							<td colspan="2" style="border-bottom: none;">'.$seller['comp_name'].'</td>
						</tr>
						<tr>
							<td colspan="2" style="border-top: none; font-size: 8px;">Получатель</td>
						</tr>
					</tbody>
				</table>
				<div class="inform">Отзывы и предложения по работе сотрудников нашей организации Вы можете сообщить<br>по телефону +7 (495) 424-98-90 или по электронной почте info@its50.ru</div>
				<h1>'.$dogovor.' № '.$order['inv'].' от '.$contdate = \ishop\App::contdate($order['date']).' г.</h1>
				<table class="contract">
					<tbody>
						<tr>
							<td width="15%">Поставщик:</td>
							<th width="85%">'.$seller['comp_name'].', ИНН '.$seller['inn'].', '.$seller['url_address'].', тел.:'.$user['telefon'].'</th>
						</tr>
						<tr>
							<td>Грузоотправитель:</td>
							<th>'.$seller['comp_name'].', ИНН '.$seller['inn'].', '.$seller['url_address'].', тел.:'.$user['telefon'].'</th>
						</tr>
						<tr>
							<td>Покупатель:</td>
							<th>'.$poluchatel.'</th>
						</tr>
						<tr>
							<td>Грузополучатель:</td>
							<th>'.$otpravitel.'</th>
						</tr>
					</tbody>
				</table>
				<div class="dogovor1">
					<p>1. Предметом настоящего Счета-Договора является поставка товарно-материальных ценностей (далее – «Товар»):</p>
				</div>
				<table class="list">
					<thead>
						<tr>
							<th width="5%">№</th>
							<th width="8%">Код</th>
							<th width="28%">Товары (работы, услуги)</th>
							<th width="8%">Кол-во</th>
							<th width="4%">Ед.</th>
							<th width="12%">Цена</th>
							<th width="12%">Ставка НДС</th>
							<th width="12%">Сумма НДС</th>
							<th width="12%">Сумма</th>
						</tr>
					</thead>
					<tbody>';					

					$total = $nds = 0;
					$i = 0;
					foreach ($order_products as $item => $row) {
	
						$total += $row['price'] * $row['qty'];
						
						$prod = \R::findOne('product', 'id = ?', [$row['product_id']]);
						$weight += $prod['weight'] * $row['qty'];
						$volume += $prod['volume'] * $row['qty'];
						
						if($comp["nds"] == 1) { $nds += ($row['price'] * 0.2 / 1.2) * $row['qty']; }

						$html .= '
						<tr>
							<td align="center">' . (++$i) . '</td>
							<td align="left">' . $row['article'] . '</td>
							<td align="left">' . $row['name'] . '</td>
							<td align="right">' . $row['qty'] . '</td>
							<td align="left">' . $row['unit'] . '</td>
							<td align="right">' . \ishop\App::format_price($row['price']) . '</td>
							<td align="right">' . $nds_comp . '</td>
							<td align="right">' . ((empty($nds)) ? '' : \ishop\App::format_price($nds)) . '</td>
							<td align="right">' . \ishop\App::format_price($row['price'] * $row['qty']) . '</td>
						</tr>';
					}			 
					if($comp["nds"] == 1) { $itogo = "Итого:<br>В том числе НДС:<br>Итого с НДС:"; $itogo_summ = "" . \ishop\App::format_price($total) . "<br>" . ((empty($nds)) ? '-' : \ishop\App::format_price($nds)) . "<br>" . \ishop\App::format_price($total) . ""; }
					else{ $itogo = "Итого:"; $itogo_summ = "" . \ishop\App::format_price($total) . "<br>без НДС";}
					$ves = "Общий вес: ".$weight."<br>Общий объем: ".$volume."";
					$html .= '
					</tbody>
					<tfoot>
						<tr>
							<th colspan="3">'.$ves.'</th>							
							<th colspan="3">'.$itogo.'</th>						
							<th colspan="3">' . $itogo_summ . '</th>
						</tr>
					</tfoot>
				</table>
				<div class="total">
					<p>Всего наименований ' . count($order_products) . ', на сумму ' . \ishop\App::format_price($total) . ' руб.</p>
					<p><strong>' . \ishop\App::str_price($total) . '</strong></p>
				</div>
				<div class="dogovor2">
					<p>2. Настоящий Счёт-Договор является офертой (предложением о заключении сделки) на основании ст. 435-443 Гражданского кодекса РФ в котором указана номенклатура, количество цена и условия поставки Товара.</p>
					<p>3. Факт оплаты данного Счета-Договора является акцептом (согласием на заключение договора) на данную оферту с момента которого между Поставщиком и Покупателем возникают договорные отношения.</p>
					<p>4. Настоящий Счет-Договор действителен в течение одного банковского дня с момента его выставления. Оплата Товара по данному Счету-договору производится отдельным платежным поручением с обязательным указанием его номера и даты в назначении платежа.</p>
					<p>5. Датой оплаты по настоящему Счету-договору является день зачисления денежных средств, на расчетный счет Поставщика. Поставщик вправе не выполнять поставку товара до зачисления оплаты на расчетный счет. </p>
					<p>6. Стороны договорились, что расчеты на условиях предварительной оплаты, аванса, рассрочки или отсрочки оплаты в рамках настоящего Договора не являются коммерческим кредитом в смысле статьи 823 ГК РФ и основанием для начисления процентов  в соответствии со  статьей 317.1 ГК РФ.</p>
					<p>7. При отсутствии оплаты в указанный срок настоящий Счет-Договор считается аннулированным и наличие заказанного товара на складе не гарантируется. Во избежание аннулирования оплаченного счёта просьба в день оплаты уведомить об этом любым способом. При осуществлении оплаты Счета-договора позднее указанного срока Поставщик оставляет за собой право пересчитать стоимость товара либо вернуть денежные средства Покупателю обратно.</p>
					<p>8. Вывоз товара осуществляется Покупателем самостоятельно со склада Поставщика в течение 5 (Пяти) рабочих дней со дня уведомления о готовности товара к отгрузке.</p>
					<p>9. По согласованию Сторон Поставщик в течение 5 (Пяти) рабочих дней с момента зачисления оплаты на расчетный счет осуществляет доставку товара по адресу, указанному Покупателем в письменной заявке при этом стоимость данной услуги указывается в Счете-Договоре отдельным пунктом.</p>
					<p>10. При не соблюдении срока вывоза товара, указанного в п. 8. Счета-договора, Поставщик оставляется за собой право потребовать от Покупателя оплату за его хранение или отказаться от исполнения условий данного Счета-договора.</p>
					<p>11. При получении товара отсутствие у Покупателя или его уполномоченного представителя документа удостоверяющего его личность, доверенности или печати, является основанием для отказа в его передаче.</p>
					<p>12. Подписание Покупателем или его уполномоченным представителем сопроводительных документов на товар означает согласие Покупателя с его комплектностью, ассортиментом, количеством и надлежащим качеством товара (за исключением скрытых недостатков, которые невозможно установить при приемке). Право собственности на товар и риск случайной гибели переходит к Покупателю в момент его получения и подписания сопроводительных документов на складе Поставщика или Покупателя (при доставке товара силами Поставщика).</p>
					<p>13. При обнаружении скрытых недостатков товара вызов Поставщика для актирования недостатков обязателен. При этом, Покупатель передает Поставщику претензионный товар для проведения экспертизы. При отказе Поставщика принять претензионный товар, Покупатель вправе поручить проведение экспертизы независимому эксперту.</p>
					<p>14. Гарантийный срок на товар - 6 месяцев с даты передачи.</p>
					<p class="dogovor3">Товар на складе зарезервирован на 2 дня</p>
				</div>
				<div class="sign">
					<img class="sign-1" src="'.PATH.'/public/adminlte/'.$pdp.'.gif">
					<img class="sign-2" src="'.PATH.'/public/adminlte/'.$pdp.'.gif">
					<img class="printing" src="'.PATH.'/public/adminlte/'.$pech.'.gif">
					<div class="pdp_1">подпись</div>
					<div class="pdp_2">подпись</div>
					<div class="pdp_3">должность</div>
					<div class="pdp_4">расшифровка подписи</div>
					<div class="pdp_5">расшифровка подписи</div>
					<div class="pdp_6">расшифровка подписи</div>
					<table>
						<tbody>
							<tr>
								<td class="sign_td_1" width="25%">Руководитель</th>
								<td width="31%"><div class="sign_td_2">'. $dolzhnost .'</div></td>
								<td width="22%"><div class="sign_td_2"></div></td>
								<td width="22%"><div class="sign_td_2">'. $rukovod .'</div></td>
							</tr>
							<tr>
								<td class="sign_td_1">Главный (старший) бухгалтер</th>
								<td class="sign_td_1"></td>
								<td><div class="sign_td_2"></div></td>
								<td><div class="sign_td_2">'. $rukovod .'</div></td>
							</tr>
							<tr>
								<td class="sign_td_1">Ответственный</th>
								<td class="sign_td_1"></td>
								<td><div class="sign_td_2"></div></td>
								<td><div class="sign_td_2"></div></td>
							</tr>
						</tbody>
					</table>
				</div>
			</body>
</html>';

$dompdf = new Dompdf\Dompdf();
$dompdf->set_option('isRemoteEnabled', TRUE);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();
 
// Вывод файла в браузер:
$dompdf->stream($order['inv']); 

exit();