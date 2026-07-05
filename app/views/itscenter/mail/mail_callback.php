<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ обратного звонка</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;">

<?php
$nameSafe    = htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8');
$phoneSafe   = htmlspecialchars($phone ?? '', ENT_QUOTES, 'UTF-8');
$emailSafe   = htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8');
$commentSafe = nl2br(htmlspecialchars($comment ?? '', ENT_QUOTES, 'UTF-8'));
$titleSafe   = htmlspecialchars($title ?? 'Заказ обратного звонка', ENT_QUOTES, 'UTF-8');
?>

<table style="width:740px;margin:0 auto;background-color:#f4f6f9;font-family:Tahoma, Helvetica, sans-serif;color:#212529;font-size:13px;border:1px solid #eee;" cellspacing="0" cellpadding="0">
    <tr>
        <td style="padding:20px;width:300px;">
            <img src="<?= PATH ?>/images/logo.png" alt="<?= htmlspecialchars($namecomp ?? '', ENT_QUOTES, 'UTF-8') ?>" style="width:260px;height:50px;">
        </td>
        <td style="padding:20px;width:440px;font-weight:bold;" align="right">
            <a href="<?= PATH ?>" style="color:#2C3E50;">Главная</a> |
            <a href="<?= PATH ?>/catalog" style="color:#2C3E50;">Каталог</a> |
            <a href="<?= PATH ?>/services/dostavka" style="color:#2C3E50;">Доставка</a> |
            <a href="<?= PATH ?>/pages/contacts" style="color:#2C3E50;">Контакты</a>
        </td>
    </tr>

    <tr>
        <td colspan="2">
            <table cellspacing="0" cellpadding="0" style="width:700px;background:#ffffff;font-size:13px;" align="center">
                <tr>
                    <td>
                        <table cellspacing="0" cellpadding="0" style="width:660px;padding:20px;font-family:Tahoma, Helvetica, sans-serif;color:#212529;font-size:13px;" align="center">
                            <tr>
                                <td style="padding:20px 0;">
                                    <div style="font-size:18px;font-weight:bold;margin-bottom:20px;color:#2C3E50;">
                                        На сайт <?= htmlspecialchars($namecomp ?? '', ENT_QUOTES, 'UTF-8') ?> поступила новая заявка на обратный звонок
                                    </div>

                                    <table cellspacing="0" cellpadding="8" style="width:100%;border-collapse:collapse;">
                                        <tr>
                                            <td style="width:180px;border:1px solid #e9ecef;background:#f8f9fa;"><b>Тема:</b></td>
                                            <td style="border:1px solid #e9ecef;"><?= $titleSafe ?></td>
                                        </tr>

                                        <?php if (!empty($nameSafe)): ?>
                                            <tr>
                                                <td style="border:1px solid #e9ecef;background:#f8f9fa;"><b>Имя:</b></td>
                                                <td style="border:1px solid #e9ecef;"><?= $nameSafe ?></td>
                                            </tr>
                                        <?php endif; ?>

                                        <tr>
                                            <td style="border:1px solid #e9ecef;background:#f8f9fa;"><b>Телефон:</b></td>
                                            <td style="border:1px solid #e9ecef;"><?= $phoneSafe ?></td>
                                        </tr>

                                        <?php if (!empty($emailSafe)): ?>
                                            <tr>
                                                <td style="border:1px solid #e9ecef;background:#f8f9fa;"><b>Электронная почта:</b></td>
                                                <td style="border:1px solid #e9ecef;"><?= $emailSafe ?></td>
                                            </tr>
                                        <?php endif; ?>

                                        <?php if (!empty($comment ?? '')): ?>
                                            <tr>
                                                <td style="border:1px solid #e9ecef;background:#f8f9fa;vertical-align:top;"><b>Комментарий:</b></td>
                                                <td style="border:1px solid #e9ecef;"><?= $commentSafe ?></td>
                                            </tr>
                                        <?php endif; ?>

                                        <tr>
                                            <td style="border:1px solid #e9ecef;background:#f8f9fa;"><b>Время заявки:</b></td>
                                            <td style="border:1px solid #e9ecef;"><?= date('Y-m-d H:i:s') ?></td>
                                        </tr>
                                    </table>

                                    <br><br>
                                    С уважением, <?= htmlspecialchars($namecomp ?? '', ENT_QUOTES, 'UTF-8') ?><br>
                                    <b>Телефон:</b> <?= htmlspecialchars($tell_site ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td colspan="2" style="padding:20px;"></td>
    </tr>
</table>

</body>
</html>