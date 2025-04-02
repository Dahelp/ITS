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
										Благодарим за покупку в <?=$namecomp?>!<br>
										При выборе товара легче ориентироваться на отзывы, не так ли?<br>
										Наверняка и Вам есть, что сказать о недавней покупке. Расскажите о своих впечатлениях и помогите другим сделать правильный выбор!<br><br>
										Прикрепите к отзыву фотографию (если есть возможность): так он станет ещё интереснее и полезнее.<br><br>
										Отзыв о компании или о вашей покупке можно оставить на Яндекс пройдя по QR-CODE или ссылке:
									</p>	
										<ul>
											<li><img src="<?=PATH?>/images/qr.png"></li>
											<li><a href="https://ya.cc/t/YyTyHBOt3EkHoK">Ссылка на Яндекс отзывы</a></li>
										</ul>
									<br>
									<p>Так же, отзыв можно оставить непосредственно в товаре, который вы купили на нашем сайте. Для этого необходимо перейти в нужную карточку товара и быть авторизированным на сайте (сделать вход через личный кабинет).</p>
										<ul>
											<li><a href="<?=PATH?>/user/login">Вход в личный кабинет</a></li>
											<li>Переход в карточки товаров которые вы купили.
												<ol>
													<?php foreach($order_product as $item): ?>
													<li><a href="<?=PATH?>/product/<?=$item['alias'] ?>"><?=$item['name'] ?></a></li>
													<?php endforeach;?>
												</ol>
											</li>
										</ul>
									<br><br>
									<p>С уважением, <?=$namecomp?><br><b>Телефон:</b> <?=$tell_site?>
									</p>
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