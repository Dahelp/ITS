<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <base href="<?= PATH ?>/">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= htmlspecialchars($title ?? 'Ошибка 404', ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($description ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($keywords ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="<?= htmlspecialchars($robots ?? 'noindex, nofollow', ENT_QUOTES, 'UTF-8') ?>">

    <link rel="icon" href="<?= PATH ?>/images/favicon.ico" type="image/x-icon">

    <link rel="stylesheet" href="<?= PATH ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= PATH ?>/css/style.css">
    <link rel="stylesheet" href="<?= PATH ?>/css/responsive.css">
    <link rel="stylesheet" href="<?= PATH ?>/css/all.min.css">
    <link rel="stylesheet" href="<?= PATH ?>/css/fonts-override.css">
</head>
<body>

<?php
$safeHeader = APP . '/views/' . TEMPLATE . '/partials/header-404.php';
$safeFooter = APP . '/views/' . TEMPLATE . '/partials/footer-404.php';

if (is_file($safeHeader)) {
    require $safeHeader;
}
?>

<?= $content ?>

<?php
if (is_file($safeFooter)) {
    require $safeFooter;
}
?>

<script src="<?= PATH ?>/js/jquery.min.js"></script>
<script src="<?= PATH ?>/js/bootstrap.bundle.min.js"></script>
<script src="<?= PATH ?>/js/main.js"></script>

</body>
</html>