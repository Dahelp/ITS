<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Заявка #<?= (int)$item['id'] ?></h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= ADMIN ?>">Главная</a></li>
          <li class="breadcrumb-item"><a href="<?= ADMIN ?>/callback">Обратные звонки</a></li>
          <li class="breadcrumb-item active">Заявка</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="card">
    <div class="card-header"><h3 class="card-title"><?= $e($item['topic'] ?: 'Обратный звонок') ?></h3></div>
    <div class="card-body">
      <dl class="row">
        <dt class="col-sm-3">Телефон</dt><dd class="col-sm-9"><a href="tel:<?= $e(preg_replace('/\D+/', '', (string)$item['phone'])) ?>"><?= $e($item['phone']) ?></a></dd>
        <dt class="col-sm-3">Клиент</dt><dd class="col-sm-9"><?= $e($item['user_name'] ?: 'Гость') ?></dd>
        <dt class="col-sm-3">E-mail</dt><dd class="col-sm-9"><?= $e($item['user_email'] ?: '') ?></dd>
        <dt class="col-sm-3">Дата создания</dt><dd class="col-sm-9"><?= $e($item['date_create']) ?></dd>
        <dt class="col-sm-3">Дата изменения</dt><dd class="col-sm-9"><?= $e($item['date_modified']) ?></dd>
      </dl>
    </div>
    <div class="card-footer">
      <a class="btn btn-secondary" href="<?= ADMIN ?>/callback">Назад</a>
      <a class="btn btn-info" href="<?= ADMIN ?>/callback/process?id=<?= (int)$item['id'] ?>">В обработку</a>
      <a class="btn btn-success" href="<?= ADMIN ?>/callback/done?id=<?= (int)$item['id'] ?>">Обработано</a>
      <a class="btn btn-danger delete" href="<?= ADMIN ?>/callback/delete?id=<?= (int)$item['id'] ?>">Закрыть</a>
    </div>
  </div>
</section>
