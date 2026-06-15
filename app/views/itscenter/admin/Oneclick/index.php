<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Заказы в 1 клик</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= ADMIN ?>">Главная</a></li>
          <li class="breadcrumb-item active">Заказы в 1 клик</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="card">
    <div class="card-header"><h3 class="card-title">Список заказов в 1 клик</h3></div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="oneclick-table" class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Статус</th>
              <th>Товар</th>
              <th>Имя</th>
              <th>Телефон</th>
              <th>E-mail</th>
              <th>Примечание</th>
              <th>Дата</th>
              <th style="width:110px">Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clicks as $click): ?>
              <?php
                $state = (string)($click['hide'] ?? '0');
                $status = match ($state) {
                    '1' => '<span class="badge badge-info">В обработке</span>',
                    '2' => '<span class="badge badge-success">Обработан</span>',
                    default => '<span class="badge badge-danger">Новый</span>',
                };
                $phone = preg_replace('/\D+/', '', (string)$click['tell_click']);
                $productUrl = !empty($click['product_id']) ? ADMIN . '/product/edit?id=' . (int)$click['product_id'] : '';
              ?>
              <tr class="<?= $state === '0' ? 'table-warning' : '' ?>">
                <td><?= (int)$click['id'] ?></td>
                <td><?= $status ?></td>
                <td><?php if ($productUrl): ?><a href="<?= $e($productUrl) ?>"><?= $e($click['product_name'] ?: $click['name']) ?></a><?php else: ?><?= $e($click['name']) ?><?php endif; ?></td>
                <td><?= $e($click['fio_click']) ?></td>
                <td><a href="tel:<?= $e($phone) ?>"><?= $e($click['tell_click']) ?></a></td>
                <td><?php if (!empty($click['email_click'])): ?><a href="<?= ADMIN ?>/mailbox/answer?email=<?= rawurlencode((string)$click['email_click']) ?>&subject=<?= rawurlencode('Заказ товара в 1 клик на сайте ' . $namecomp) ?>"><?= $e($click['email_click']) ?></a><?php endif; ?></td>
                <td><?= $e($click['prim_click']) ?></td>
                <td><?= $e($click['data_create']) ?></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <?php if ($state === '0'): ?><a class="btn btn-default" title="В обработку" data-toggle="tooltip" href="<?= ADMIN ?>/oneclick/process?id=<?= (int)$click['id'] ?>"><i class="fas fa-phone-volume text-info"></i></a><?php endif; ?>
                    <?php if (empty($click['order_id'])): ?><a class="btn btn-default" title="Создать заказ" data-toggle="tooltip" href="<?= ADMIN ?>/oneclick/create-order?id=<?= (int)$click['id'] ?>"><i class="fas fa-cart-plus text-primary"></i></a><?php else: ?><a class="btn btn-default" title="Открыть заказ #<?= (int)$click['order_id'] ?>" data-toggle="tooltip" href="<?= ADMIN ?>/order/view?id=<?= (int)$click['order_id'] ?>"><i class="fas fa-file-invoice text-primary"></i></a><?php endif; ?>
                    <?php if ($state !== '2'): ?><a class="btn btn-default" title="Обработано" data-toggle="tooltip" href="<?= ADMIN ?>/oneclick/done?id=<?= (int)$click['id'] ?>"><i class="fas fa-check-circle text-success"></i></a><?php endif; ?>
                    <?php if ($state !== '2'): ?><a class="btn btn-default delete" title="Закрыть" data-toggle="tooltip" href="<?= ADMIN ?>/oneclick/delete?id=<?= (int)$click['id'] ?>"><i class="fas fa-times-circle text-danger"></i></a><?php endif; ?>
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
  $('#oneclick-table').DataTable({order:[[0,'desc']], pageLength:50});
});
</script>
