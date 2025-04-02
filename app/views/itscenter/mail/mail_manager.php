<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Заказ №<?=$order["inv"]?> на сайте <?=$namecomp?></title>
</head>
<body>
<table style="width:740px;background-color:#f4f6f9;font-family:Tahoma, Helvetica, sans-serif;color:#212529;font-size:13px;border:1px solid #eee">
	<tr>             
		<td style="padding:20px;width:300px"><img src="<?=PATH?>/images/logo.png" alt="<?=$namecomp?>" style="width:260px;height:50px"></td>
		<td style="padding:20px;width:440px;font-weight:bold" align="right"> <a href="<?=PATH?>" style="color:#2C3E50">Главная</a> | <a href="<?=PATH?>/catalog" style="color:#2C3E50">Каталог</a> | <a href="<?=PATH?>/services/dostavka" style="color:#2C3E50">Доставка</a> | <a href="<?=PATH?>/pages/contacts" style="color:#2C3E50">Контакты</a></td>
	</tr>
	<tr>
		<td colspan="2">
			<table cellspacing="0" cellpadding="0" style="width:700px;background: none repeat scroll 0% 0% rgb(255, 255, 255);font-size:13px" align="center">
				<tr>
					<td>						
						<table cellspacing="0" cellpadding="0" style="width:660px;padding:20px;font-family:Tahoma, Helvetica, sans-serif;color:#212529;font-size:13px" align="center">
							<tr>
								<td colspan="4" style="padding:20px 0 20px 0">
									<p>Здравствуйте <?=$user["name"]?>.<br><br>
										Благодарим Вас за заказ!<br>
										Ваш заказ на сайте <?=$namecomp?> оформлен. В ближайшее время с вами свяжутся для подтверждения заказа по email.<br><br>
										<strong>Ваш заказ: № <?=$order["inv"]?> от <?=$order["date"]?>
									</p>
									<table style="border: 1px solid #ddd; border-collapse: collapse; width: 100%;">
										<thead>
										<tr style="background: #f9f9f9;">
											<th style="padding: 8px; border: 1px solid #ddd;">Наименование</th>
											<th style="padding: 8px; border: 1px solid #ddd;">Кол-во</th>
											<th style="padding: 8px; border: 1px solid #ddd;">Цена</th>
											<th style="padding: 8px; border: 1px solid #ddd;">Сумма</th>
										</tr>
										</thead>
										<tbody>
										<?php foreach($order_product as $item): ?>
											<tr>
												<td style="padding: 8px; border: 1px solid #ddd;"><?=$item['name'] ?></td>
												<td style="padding: 8px; border: 1px solid #ddd;"><?=$item['qty'] ?><?php $itog_qty += $item['qty']; ?></td>
												<td style="padding: 8px; border: 1px solid #ddd;"><?=$item['price'] ?></td>
												<td style="padding: 8px; border: 1px solid #ddd;"><?=$item['price'] * $item['qty']?><?php $sum += $item['price'] * $item['qty']; ?></td>
											</tr>
										<?php endforeach;?>
										<tr>
											<td colspan="3" style="padding: 8px; border: 1px solid #ddd;">Итого:</td>
											<td style="padding: 8px; border: 1px solid #ddd;"><?=$itog_qty?></td>
										</tr>
										<tr>
											<td colspan="3" style="padding: 8px; border: 1px solid #ddd;">На сумму:</td>
											<td style="padding: 8px; border: 1px solid #ddd;"><?=$sum?></td>
										</tr>
										</tbody>
									</table>
									<br><br>
									<b>Способ доставки:</b> <?=$dost["name"]?><br>
									<?=$trans["name"]?>
									<b>Город:</b> <?=$bran["branch_name"]?><?=$cit["city_name"]?>
									<?=$user["address"]?>
									<br><br>
									<?=$vid?>
									<?=$nds?>
									<?=$dogovor?>
									<b>Имя:</b> <?=$user["name"]?><br>
									<b>Номер телефона:</b> <?=$user["telefon"]?><br>
									<b>E-mail:</b> <a href="mailto:<?=$user["email"]?>" target="_blank"><?=$user["email"]?></a><br>
									<b>Комментарий:</b> <?=$order["note"]?><br>
									<b>Время заказа:</b> <?=$order["date"]?><br><br><br>
										С уважением, <?=$namecomp?> <br>
									<b>Телефон:</b> <?=$tell_site?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="2" style="padding:20px"></td>
	</tr>
</table>

</body>
</html>