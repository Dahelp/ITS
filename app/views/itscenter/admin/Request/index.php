<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Заявки о товаре</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= ADMIN ?>">Главная</a></li>
          <li class="breadcrumb-item active">Заявки о товаре</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Заявки о товаре по заказ</h3>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="request-table" class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Статус</th>
              <th>Товар</th>
              <th>Клиент</th>
              <th>Телефон</th>
              <th>E-mail</th>
              <th>Комментарий</th>
              <th>Дата</th>
              <th>Заказ</th>
              <th style="width:110px">Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($requests as $row): ?>
              <?php
                $state = (string)($row['hide'] ?? '0');
                $isNew = $state === '0';
                $productUrl = !empty($row['product_id']) ? ADMIN . '/product/edit?id=' . (int)$row['product_id'] : '';
                $userUrl = !empty($row['user_id']) ? ADMIN . '/user/edit?id=' . (int)$row['user_id'] : '';
                $orderUrl = !empty($row['order_id']) ? ADMIN . '/order/view?id=' . (int)$row['order_id'] : '';
                $status = match ($state) {
                    '1' => '<span class="badge badge-info">В обработке</span>',
                    '2' => '<span class="badge badge-success">Обработана</span>',
                    default => '<span class="badge badge-danger">Новая</span>',
                };
              ?>
              <tr class="<?= $isNew ? 'table-warning' : '' ?>">
                <td><?= (int)$row['id'] ?></td>
                <td><?= $status ?></td>
                <td><?php if ($productUrl): ?><a href="<?= $e($productUrl) ?>"><?= $e($row['product_name'] ?: $row['name']) ?></a><?php else: ?><?= $e($row['name']) ?><?php endif; ?></td>
                <td><?php if ($userUrl): ?><a href="<?= $e($userUrl) ?>"><?= $e($row['user_name'] ?: $row['fio']) ?></a><?php else: ?><?= $e($row['fio']) ?><?php endif; ?></td>
                <td><?= $e($row['tell']) ?></td>
                <td><?= $e($row['email'] ?: $row['user_email']) ?></td>
                <td><?= $e($row['note']) ?></td>
                <td><?= $e($row['data_create']) ?></td>
                <td><?php if ($orderUrl): ?><a href="<?= $e($orderUrl) ?>">#<?= (int)$row['order_id'] ?></a><?php endif; ?></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <?php if ($state === '0'): ?><a class="btn btn-default" title="В обработку" data-toggle="tooltip" href="<?= ADMIN ?>/request/process?id=<?= (int)$row['id'] ?>"><i class="fas fa-phone-volume text-info"></i></a><?php endif; ?>
                    <?php if (empty($row['order_id'])): ?><a class="btn btn-default" title="Создать заказ" data-toggle="tooltip" href="<?= ADMIN ?>/request/create-order?id=<?= (int)$row['id'] ?>"><i class="fas fa-cart-plus text-primary"></i></a><?php else: ?><a class="btn btn-default" title="Открыть заказ #<?= (int)$row['order_id'] ?>" data-toggle="tooltip" href="<?= ADMIN ?>/order/view?id=<?= (int)$row['order_id'] ?>"><i class="fas fa-file-invoice text-primary"></i></a><?php endif; ?>
                    <?php if ($state !== '2'): ?><a class="btn btn-default" title="Обработано" data-toggle="tooltip" href="<?= ADMIN ?>/request/done?id=<?= (int)$row['id'] ?>"><i class="fas fa-check-circle text-success"></i></a><?php endif; ?>
                    <?php if ($state !== '2'): ?><a class="btn btn-default delete" title="Закрыть" data-toggle="tooltip" href="<?= ADMIN ?>/request/delete?id=<?= (int)$row['id'] ?>"><i class="fas fa-times-circle text-danger"></i></a><?php endif; ?>
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
  $('#request-table').DataTable({order:[[0,'desc']], pageLength:50});
});
</script>
