<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Заявки о поступлении товара</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= ADMIN ?>">Главная</a></li>
          <li class="breadcrumb-item active">Заявки о поступлении товара</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="card">
    <div class="card-header"><h3 class="card-title">Список заявок о поступлении товара</h3></div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="availability-table" class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Статус</th>
              <th>Пользователь</th>
              <th>E-mail</th>
              <th>Товар</th>
              <th>Дата заявки</th>
              <th>Дата поступления</th>
              <th>Отправка</th>
              <th style="width:90px">Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($availability as $item): ?>
              <?php
                $isNew = (string)$item['status_nalichiya'] === '0';
                $isSent = (string)$item['status_otpravki'] === '1';
                $productUrl = !empty($item['product_id']) ? ADMIN . '/product/edit?id=' . (int)$item['product_id'] : '';
                $userUrl = !empty($item['user_id']) ? ADMIN . '/user/edit?id=' . (int)$item['user_id'] : '';
              ?>
              <tr class="<?= $isNew ? 'table-warning' : '' ?>">
                <td><?= (int)$item['id'] ?></td>
                <td><?= $isNew ? '<span class="badge badge-danger">Ожидает</span>' : '<span class="badge badge-success">Закрыта</span>' ?></td>
                <td><?php if ($userUrl): ?><a href="<?= $e($userUrl) ?>"><?= $e($item['user_name'] ?: ('#' . $item['user_id'])) ?></a><?php else: ?><span class="text-muted">Гость</span><?php endif; ?></td>
                <td><?= $e($item['email']) ?></td>
                <td><?php if ($productUrl): ?><a href="<?= $e($productUrl) ?>"><?= $e($item['product_name']) ?></a><?php else: ?><?= $e($item['product_name']) ?><?php endif; ?></td>
                <td><?= $e($item['data_create']) ?></td>
                <td><?= $e($item['data_postupleniya']) ?></td>
                <td><?= $isSent ? '<span class="badge badge-success">Отправлено</span>' : '<span class="badge badge-secondary">Не отправлено</span>' ?></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <?php if ($isNew): ?><a class="btn btn-default" title="Обработано" data-toggle="tooltip" href="<?= ADMIN ?>/availability/done?id=<?= (int)$item['id'] ?>"><i class="fas fa-check-circle text-success"></i></a><?php endif; ?>
                    <?php if ($isNew): ?><a class="btn btn-default delete" title="Закрыть" data-toggle="tooltip" href="<?= ADMIN ?>/availability/delete?id=<?= (int)$item['id'] ?>"><i class="fas fa-times-circle text-danger"></i></a><?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<script>
$(function(){
  $('#availability-table').DataTable({order:[[0,'desc']], pageLength:50});
});
</script>
