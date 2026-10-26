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
		<td style="padding:20px;width:440px;font-weight:bold" align="right">
            <a href="<?=PATH?>" style="color:#2C3E50">Главная</a> |
            <a href="<?=PATH?>/catalog" style="color:#2C3E50">Каталог</a> |
            <a href="<?=PATH?>/services/dostavka" style="color:#2C3E50">Доставка</a> |
            <a href="<?=PATH?>/pages/contacts" style="color:#2C3E50">Контакты</a>
        </td>
	</tr>
	<tr>
		<td colspan="2">
			<table cellspacing="0" cellpadding="0" style="width:700px;background:#fff;font-size:13px" align="center">
				<tr>
					<td>
						<table cellspacing="0" cellpadding="0" style="width:660px;padding:20px;font-family:Tahoma, Helvetica, sans-serif;color:#212529;font-size:13px" align="center">
							<tr>
								<td colspan="4" style="padding:20px 0 20px 0">
                                    На сайт <?=$namecomp?> поступила заявка на заказ товара.<br><br>
									<b>Количество:</b> <?= (int)$quantity ?><br>
                                    <?php if ($unitPrice !== null): ?><b>Цена за единицу при заявке:</b> <?= htmlspecialchars((string)$unitPrice, ENT_QUOTES, 'UTF-8') ?> ₽<br><?php endif; ?>
                                    <b>Название товара:</b> <?=htmlspecialchars((string)$name_tovar, ENT_QUOTES, 'UTF-8')?><br>
									<b>Ф.И.О.:</b> <?=htmlspecialchars((string)$fio, ENT_QUOTES, 'UTF-8')?><br>
									<b>Номер телефона:</b> <?=htmlspecialchars((string)$tell, ENT_QUOTES, 'UTF-8')?><br>
									<b>Электронная почта:</b> <?=htmlspecialchars((string)$email, ENT_QUOTES, 'UTF-8')?><br>
									<b>Комментарий:</b> <?=htmlspecialchars((string)$note, ENT_QUOTES, 'UTF-8')?><br>
									<b>Время заказа:</b> <?=date('Y-m-d H:i:s')?><br><br><br>
									С уважением, <?=$namecomp?><br>
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