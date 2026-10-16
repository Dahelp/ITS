<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= $this->getMeta() ?>
    <link rel="stylesheet" href="<?= PATH ?>/adminlte/dist/css/adminlte.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-light bg-white border-bottom">
        <span>Чат Авито · <?= h($_SESSION['user']['name'] ?? '') ?></span>
        <a href="<?= PATH ?>/user/logout">Выйти</a>
        <ul class="navbar-nav"><?php require APP.'/views/'.TEMPLATE.'/admin/AvitoChat/_notification.php'; ?></ul>
    </nav>
    <main class="container-fluid py-3"><?= $content ?></main>
</body>
</html>
