<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Уведомления</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= ADMIN ?>">Главная</a></li>
          <li class="breadcrumb-item active">Уведомления</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
          <div class="inner">
            <h3><?= (int)$total ?></h3>
            <p>Новых уведомлений</p>
          </div>
          <div class="icon"><i class="far fa-bell"></i></div>
          <a href="<?= ADMIN ?>/activity" class="small-box-footer">Журнал действий <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Разделы с новыми событиями</h3>
      </div>
      <div class="card-body p-0">
        <?php if ($items): ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Раздел</th>
                  <th style="width:120px">Новых</th>
                  <th style="width:190px">Последнее</th>
                  <th style="width:120px"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                  <tr>
                    <td>
                      <i class="<?= $e($item['icon']) ?> mr-2 text-muted"></i>
                      <?= $e($item['title']) ?>
                    </td>
                    <td><span class="badge badge-warning"><?= (int)$item['count'] ?></span></td>
                    <td><?= !empty($item['date']) ? $e($item['date']) : '<span class="text-muted">нет даты</span>' ?></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="<?= $e($item['url']) ?>">Открыть</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="p-4 text-center text-muted">Новых уведомлений нет.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
