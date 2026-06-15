<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v) => number_format((float)$v, 2, '.', ' ');
$currency = $curr['symbol_right'] ?? '₽';
?>
<div class="content-header">
  <div class="container-fluid"><div class="row mb-2">
    <div class="col-sm-6"><h1 class="m-0">Продажи менеджеров</h1></div>
    <div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="<?= ADMIN ?>">Главная</a></li><li class="breadcrumb-item active">Продажи менеджеров</li></ol></div>
  </div></div>
</div>

<section class="content">
  <div class="card">
    <div class="card-header">
      <form class="form-inline" method="get">
        <label class="mr-2">Месяц</label>
        <input class="form-control form-control-sm mr-2" type="month" name="month" value="<?= $e($month) ?>">
        <button class="btn btn-sm btn-primary" type="submit">Показать</button>
      </form>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="manager-sales-table" class="table table-bordered table-hover">
          <thead><tr><th>Менеджер</th><th>Заказов</th><th>Сумма</th></tr></thead>
          <tbody>
            <?php foreach ($sales as $row): ?>
              <tr>
                <td><a href="<?= ADMIN ?>/user/edit-customer?id=<?= (int)$row['manager_id'] ?>"><?= $e($row['manager_name']) ?></a></td>
                <td><?= (int)$row['orders_count'] ?></td>
                <td><?= $money($row['sales_sum']) ?> <?= $e($currency) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
<script>$(function(){ $('#manager-sales-table').DataTable({order:[[2,'desc']], pageLength:25}); });</script>
