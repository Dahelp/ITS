<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Заказ товара в 1 клик</title>
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
									<?php $date = date("Y-m-j H:i:s")
										$dayofweek = date('w', strtotime($date));
										if($dayofweek > 0 && $dayofweek < 6){
									?>	
										Ваш заказ в 1 клик на сайте <?=$namecomp?> оформлен. Для согласования заказа с Вами свяжется менеджер в рабочее время ПН-ПТ с 09:00 до 17:00	
									<?php } ?>
									<?php if($dayofweek > 5 && $dayofweek < 8){ ?>
										Ваш заказ в 1 клик на сайте <?=$namecomp?> оформлен. Для согласования заказа с Вами свяжется менеджер в понедельник в рабочее время с 09:00 до 17:00		
									<?php } ?>
									<br><br>
									<b>Название товара:</b> <?=$name_tovar?><br>
									<b>Ф.И.О.:</b> <?=$fio_click?><br>
									<b>Номер телефона:</b> <?=$tell_click?><br>
									<b>E-mail:</b> <?=$email_click?><br>
									<b>Комментарий:</b> <?=$prim_click?><br>
									<b>Время заказа:</b> <?=date("Y-m-j H:i:s")?><br><br><br>
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