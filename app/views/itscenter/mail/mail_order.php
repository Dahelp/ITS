<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Заказ №<?=$order_prefix?>на сайте <?=$namecomp?></title>
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
									<p>Здравствуйте <?=$uname?>.<br><br>
										Благодарим Вас за заказ!<br>
										<?php 
											$dayofweek = date('w', strtotime($date));
											if($dayofweek > 0 && $dayofweek < 6){
										?>	
											Ваш заказ на сайте <?=$namecomp?> оформлен. Для согласования заказа с Вами свяжется менеджер в рабочее время ПН-ПТ с 09:00 до 17:00	
										<?php } ?>
										<?php if($dayofweek > 5 && $dayofweek < 8){ ?>
											Ваш заказ на сайте <?=$namecomp?> оформлен. Для согласования заказа с Вами свяжется менеджер в понедельник в рабочее время с 09:00 до 17:00		
										<?php } ?>
										<br><br>
										<strong>Ваш заказ: № <?=$order_prefix?><?=$order_id?> от <?=$date?>
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
										<?php foreach($_SESSION['cart'] as $item): ?>
											<tr>
												<td style="padding: 8px; border: 1px solid #ddd;"><?=$item['name'] ?></td>
												<td style="padding: 8px; border: 1px solid #ddd;"><?=$item['qty'] ?></td>
												<td style="padding: 8px; border: 1px solid #ddd;"><?=$item['price'] ?></td>
												<td style="padding: 8px; border: 1px solid #ddd;"><?=$item['price'] * $item['qty'] ?></td>
											</tr>
										<?php endforeach;?>
										<tr>
											<td colspan="3" style="padding: 8px; border: 1px solid #ddd;">Итого:</td>
											<td style="padding: 8px; border: 1px solid #ddd;"><?=$_SESSION['cart.qty'] ?></td>
										</tr>
										<tr>
											<td colspan="3" style="padding: 8px; border: 1px solid #ddd;">На сумму: (Применён промокод: <?=$_SESSION['promocart']?>)</td>
											<td style="padding: 8px; border: 1px solid #ddd;"><?= $_SESSION['cart.currency']['symbol_left'] . $_SESSION['cart.sum'] . " {$_SESSION['cart.currency']['symbol_right']}" ?></td>
										</tr>
										</tbody>
									</table>
									<br><br>
									<b>Способ доставки:</b> <?=$dostavka_name?><br>
									<?=$transport_company?>
									<b>Город:</b> <?=$branch_name?><?=$city_name?>
									<?=$address?>
									<br><br>
									<?=$vid?>
									<?=$compname?>
									<?=$nds?>
									<?=$dogovor?>
									<b>Имя:</b> <?=$uname?><br>
									<b>Номер телефона:</b> <?=$telefon?><br>
									<b>E-mail:</b> <a href="mailto:<?=$user_email?>" target="_blank"><?=$user_email?></a><br>
									<b>Комментарий:</b> <?=$note?><br>
									<b>Время заказа:</b> <?=$date?><br><br><br>
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