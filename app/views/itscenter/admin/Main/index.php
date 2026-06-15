<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v) => number_format((float)$v, 2, '.', ' ');
$currency = $curr['symbol_right'] ?? '₽';
$stockLabels = [];
$stockValues = [];
foreach (($stockSummary['history'] ?? []) as $row) {
    $stockLabels[] = date('d.m', strtotime($row['date_total']));
    $stockValues[] = (int)$row['qty_total'];
}
$stockDelta = (int)($stockSummary['currentQty'] ?? 0) - (int)($stockSummary['prevQty'] ?? 0);
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Панель управления</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right"><li class="breadcrumb-item">Главная</li></ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
          <div class="inner"><h3><?= (int)$countNewOrders ?></h3><p>Новые заказы</p></div>
          <div class="icon"><i class="ion ion-bag"></i></div>
          <a href="/admin/order" class="small-box-footer">Все заказы <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
          <div class="small-box-2">
            <div class="col-6 inner"><h3><?= (int)$countProducts ?></h3><p>Позиций</p></div>
            <div class="col-6 inner"><h3><?= (int)$countInStock ?></h3><p>В наличии, шт.</p></div>
          </div>
          <div class="icon"><i class="ion ion-stats-bars"></i></div>
          <a href="/admin/stock" class="small-box-footer">Отчёт по наличию <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
          <div class="inner"><h3><?= (int)$countUsers ?></h3><p>Клиенты</p></div>
          <div class="icon"><i class="ion ion-person-add"></i></div>
          <a href="/admin/user" class="small-box-footer">Все клиенты <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
          <div class="inner"><h3><?= (int)$countCategories ?></h3><p>Категории</p></div>
          <div class="icon"><i class="ion ion-pie-graph"></i></div>
          <a href="/admin/category" class="small-box-footer">Все категории <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Пользователи онлайн</h3>
            <div class="card-tools"><span class="badge badge-success">Онлайн <?= (int)$countOnlineUsers ?></span></div>
          </div>
          <div class="card-body p-0">
            <?php if ($usersonline): ?>
              <ul class="users-list clearfix">
                <?php foreach ($usersonline as $ouser): ?>
                  <li>
                    <img src="dist/img/user1-128x128.jpg" alt="">
                    <a class="users-list-name" href="/admin/user/edit-customer?id=<?= (int)$ouser['id'] ?>"><?= $e($ouser['name']) ?></a>
                    <span class="users-list-date"><?= date('H:i', (int)$ouser['last_seen']) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div class="p-3 text-muted">Нет активных администраторов за последние 15 минут.</div>
            <?php endif; ?>
          </div>
          <div class="card-footer text-center"><a href="/admin/user/customers">Все сотрудники</a></div>
        </div>

        <div class="small-box bg-info">
          <div class="inner"><h3><?= (int)$countOneClick ?></h3><p>Новые заказы в 1 клик</p></div>
          <div class="icon"><i class="ion ion-bag"></i></div>
          <a href="/admin/oneclick" class="small-box-footer">Все заказы в 1 клик <i class="fas fa-arrow-circle-right"></i></a>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Топ менеджеров по заказам</h3>
          </div>
          <div class="card-body">
            <form class="form-inline mb-3" method="get" action="/admin/">
              <input class="form-control form-control-sm mr-2" type="month" name="month" value="<?= $e($salesMonth) ?>">
              <button class="btn btn-sm btn-primary" type="submit">Показать</button>
            </form>
            <ul class="nav nav-pills flex-column">
              <?php foreach (array_slice($managerSales, 0, 8) as $row): ?>
                <li class="nav-item">
                  <a href="/admin/manager-sales?month=<?= $e($salesMonth) ?>" class="nav-link">
                    <?= $e($row['manager_name']) ?>
                    <span class="float-right"><?= $money($row['sales_sum']) ?> <?= $e($currency) ?></span>
                    <small class="d-block text-muted"><?= (int)$row['orders_count'] ?> заказов</small>
                  </a>
                </li>
              <?php endforeach; ?>
              <?php if (!$managerSales): ?><li class="text-muted">За выбранный месяц продаж нет.</li><?php endif; ?>
            </ul>
          </div>
          <div class="card-footer text-center"><a href="/admin/manager-sales?month=<?= $e($salesMonth) ?>">Все менеджеры</a></div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Последние действия за 24 часа</h3>
            <div class="card-tools"><span class="badge badge-primary"><?= (int)$recentActivityCount ?></span></div>
          </div>
          <div class="card-body p-0">
            <div class="list-group list-group-flush admin-activity-list">
              <?php foreach ($recentActivity as $row): ?>
                <a class="list-group-item list-group-item-action" href="<?= $e($row['activity_url'] ?? '/admin/activity') ?>">
                  <div class="d-flex justify-content-between">
                    <strong><?= $e($row['name_gh'] ?: 'Система') ?></strong>
                    <small><?= date('H:i', strtotime($row['date_modified'])) ?></small>
                  </div>
                  <div><?= $e(trim(($row['actor_name'] ?? '') . ' ' . ($row['name_ah'] ?? ''))) ?></div>
                  <small class="text-muted"><?= $e($row['name_tbl']) ?> #<?= (int)$row['id_tbl'] ?></small>
                </a>
              <?php endforeach; ?>
              <?php if (!$recentActivity): ?><div class="p-3 text-muted">За 24 часа действий нет.</div><?php endif; ?>
            </div>
          </div>
          <div class="card-footer text-center"><a href="/admin/activity">Весь журнал действий</a></div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header border-0">
            <div class="d-flex justify-content-between">
              <h3 class="card-title">Наличие товаров</h3>
              <a href="/admin/stock">Подробный отчёт</a>
            </div>
          </div>
          <div class="card-body">
            <div class="d-flex">
              <p class="d-flex flex-column">
                <span class="text-bold text-lg"><?= (int)$stockSummary['currentQty'] ?> шт.</span>
                <span><?= (int)$stockSummary['productsCount'] ?> товаров, <?= (int)$stockSummary['branchesCount'] ?> складов</span>
              </p>
              <p class="ml-auto d-flex flex-column text-right">
                <span class="<?= $stockDelta >= 0 ? 'text-success' : 'text-danger' ?>">
                  <i class="fas fa-arrow-<?= $stockDelta >= 0 ? 'up' : 'down' ?>"></i> <?= (int)$stockDelta ?> шт.
                </span>
                <span class="text-muted">к предыдущей фиксации</span>
              </p>
            </div>
            <div class="position-relative mb-4"><canvas id="stock-chart" height="220"></canvas></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
$(function(){
  var ctx = $('#stock-chart');
  if (!ctx.length) return;
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($stockLabels, JSON_UNESCAPED_UNICODE) ?>,
      datasets: [{
        data: <?= json_encode($stockValues) ?>,
        borderColor: '#007bff',
        backgroundColor: 'rgba(0,123,255,.08)',
        pointBackgroundColor: '#007bff',
        fill: true
      }]
    },
    options: {
      maintainAspectRatio: false,
      legend: { display: false },
      scales: { yAxes: [{ ticks: { beginAtZero: true } }] }
    }
  });
});
</script>
