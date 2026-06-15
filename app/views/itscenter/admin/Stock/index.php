<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$num = static fn($v) => number_format((float)$v, 0, '.', ' ');
$labels = [];
$values = [];
$statusNames = [
    0 => 'Нет в наличии',
    1 => 'В наличии',
    2 => 'Под заказ',
    3 => 'Ожидается поступление',
];
foreach (($summary['history'] ?? []) as $row) {
    $labels[] = date('d.m', strtotime($row['date_total']));
    $values[] = (int)$row['qty_total'];
}
$sourceTitle = ($summary['source'] ?? '') === 'in_stock' ? 'таблица in_stock' : 'карточки товаров';
$historyDelta = (int)($summary['historyDelta'] ?? 0);
?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Наличие товаров</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= ADMIN ?>">Главная</a></li>
          <li class="breadcrumb-item active">Наличие товаров</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $e($_SESSION['success']); unset($_SESSION['success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
  <?php endif; ?>

  <?php if ((int)($summary['inStockRows'] ?? 0) <= 0): ?>
    <div class="alert alert-warning">
      Таблица <code>in_stock</code> сейчас пустая, поэтому текущий остаток рассчитан по <code>product.quantity</code>.
      История графика продолжает браться из <code>in_stock_history_total</code>.
    </div>
  <?php elseif (empty($summary['hasStockIndexes'])): ?>
    <div class="alert alert-warning">
      Для быстрого расчета складов нужен индекс <code>idx_stock_product_branch_id</code>. Пока складской разрез отключен, чтобы не создавать нагрузку на сайт.
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-3">
      <div class="info-box">
        <span class="info-box-icon bg-success"><i class="fas fa-boxes"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Текущий остаток</span>
          <span class="info-box-number"><?= $num($summary['currentQty']) ?> шт.</span>
          <span class="text-muted">Источник: <?= $e($sourceTitle) ?></span>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="info-box">
        <span class="info-box-icon bg-info"><i class="fas fa-cubes"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Позиций с остатком</span>
          <span class="info-box-number"><?= $num($summary['productsCount']) ?></span>
          <span class="text-muted">Активные товары</span>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="info-box">
        <span class="info-box-icon bg-primary"><i class="fas fa-warehouse"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Складов в in_stock</span>
          <span class="info-box-number"><?= $num($summary['branchesCount']) ?></span>
          <span class="text-muted"><?= $num($summary['inStockRows']) ?> строк</span>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="info-box">
        <span class="info-box-icon <?= $historyDelta >= 0 ? 'bg-success' : 'bg-danger' ?>"><i class="fas fa-chart-line"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">К последней истории</span>
          <span class="info-box-number"><?= $historyDelta >= 0 ? '+' : '' ?><?= $num($historyDelta) ?> шт.</span>
          <span class="text-muted"><?= $e($summary['lastDate'] ?: '-') ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><h3 class="card-title">История общего остатка</h3></div>
        <div class="card-body"><canvas id="stock-report-chart" height="240"></canvas></div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Статусы товаров</h3></div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead><tr><th>Статус</th><th class="text-right">Товаров</th><th class="text-right">Остаток</th></tr></thead>
            <tbody>
              <?php foreach ($statusRows as $row): $sid = (int)$row['stock_status_id']; ?>
                <tr>
                  <td><?= $e($statusNames[$sid] ?? ('Статус #' . $sid)) ?></td>
                  <td class="text-right" data-order="<?= (int)$row['products_count'] ?>"><?= $num($row['products_count']) ?></td>
                  <td class="text-right" data-order="<?= (int)$row['quantity'] ?>"><?= $num($row['quantity']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3 class="card-title">Контроль таблиц</h3></div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-7">in_stock актуально</dt><dd class="col-5 text-right"><?= $num($summary['inStockQty']) ?> шт.</dd>
            <dt class="col-7">in_stock сырая сумма</dt><dd class="col-5 text-right"><?= $num($summary['rawInStockQty'] ?? $summary['inStockQty']) ?> шт.</dd>
            <dt class="col-7">Дубли in_stock</dt><dd class="col-5 text-right"><?= ($summary['duplicateStockRows'] === null) ? 'не считались' : $num($summary['duplicateStockRows']) ?></dd>
            <dt class="col-7">product.quantity</dt><dd class="col-5 text-right"><?= $num($summary['productQty']) ?> шт.</dd>
            <dt class="col-7">Последняя история</dt><dd class="col-5 text-right"><?= $num($summary['latestHistoryQty']) ?> шт.</dd>
          </dl>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header p-0 border-bottom-0">
      <ul class="nav nav-tabs" id="stock-tabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#stock-branches-pane" role="tab">Остатки по складам</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#stock-products-pane" role="tab">Товары с остатком</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#stock-history-pane" role="tab">Последние изменения остатков</a></li>
      </ul>
    </div>
    <div class="card-body">
      <div class="tab-content" id="stock-tabs-content">
        <div class="tab-pane fade show active" id="stock-branches-pane" role="tabpanel">
          <?php if ($branches): ?>
            <div class="table-responsive">
              <table id="stock-branches-table" class="table table-bordered table-hover stock-tab-table" style="width:100%">
                <thead><tr><th>Склад</th><th>Позиций</th><th>Остаток</th><th>Дата</th></tr></thead>
                <tbody>
                  <?php foreach ($branches as $row): ?>
                    <tr>
                      <td>
                        <?= $e($row['branch_name'] ?: ('Склад #' . (int)$row['branch_id'])) ?>
                        <?php if (!empty($row['tbl'])): ?><small class="text-muted d-block"><?= $e($row['tbl']) ?></small><?php endif; ?>
                      </td>
                      <td data-order="<?= (int)$row['products_count'] ?>"><?= $num($row['products_count']) ?></td>
                      <td data-order="<?= (int)$row['quantity'] ?>"><?= $num($row['quantity']) ?></td>
                      <td><?= $e($row['date_scheduling']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info mb-0">Данные по складам недоступны или ожидают подготовки индексов.</div>
          <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="stock-products-pane" role="tabpanel">
          <div class="table-responsive">
            <table id="stock-table" class="table table-bordered table-hover stock-tab-table" style="width:100%">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Артикул</th>
                  <th>Товар</th>
                  <th>Статус</th>
                  <th>Остаток</th>
                  <th>Резерв</th>
                  <th>Ожидается</th>
                  <th>Дата</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $row): $sid = (int)($row['stock_status_id'] ?? 0); ?>
                  <tr>
                    <td><?= (int)$row['product_id'] ?></td>
                    <td><?= $e($row['article']) ?></td>
                    <td><a href="<?= ADMIN ?>/product/edit?id=<?= (int)$row['product_id'] ?>"><?= $e($row['name']) ?></a></td>
                    <td><?= $e($statusNames[$sid] ?? ('Статус #' . $sid)) ?></td>
                    <td data-order="<?= (int)$row['quantity'] ?>"><?= $num($row['quantity']) ?></td>
                    <td data-order="<?= (int)($row['reserve'] ?? 0) ?>"><?= $num($row['reserve'] ?? 0) ?></td>
                    <td data-order="<?= (int)($row['wait'] ?? 0) ?>"><?= $num($row['wait'] ?? 0) ?></td>
                    <td><?= $e($row['date_scheduling']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="tab-pane fade" id="stock-history-pane" role="tabpanel">
          <div class="d-flex justify-content-end mb-3">
            <form action="<?= ADMIN ?>/stock/clear-history" method="post" onsubmit="return confirm('Очистить историю остатков старше 1 года?');">
              <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-trash-alt"></i> Очистить старше 1 года
              </button>
            </form>
          </div>
          <div class="table-responsive">
            <table id="stock-history-table" class="table table-bordered table-hover stock-tab-table" style="width:100%">
              <thead><tr><th>Дата</th><th>ID</th><th>Артикул</th><th>Товар</th><th>Остаток в истории</th><th>Текущий остаток</th><th>Цена</th></tr></thead>
              <tbody>
                <?php foreach ($historyRows as $row): ?>
                  <tr>
                    <td><?= $e($row['date_ish']) ?></td>
                    <td><?= (int)$row['product_id'] ?></td>
                    <td><?= $e($row['article']) ?></td>
                    <td><a href="<?= ADMIN ?>/product/edit?id=<?= (int)$row['product_id'] ?>"><?= $e($row['name']) ?></a></td>
                    <td data-order="<?= (int)$row['qty'] ?>"><?= $num($row['qty']) ?></td>
                    <td data-order="<?= (int)($row['current_quantity'] ?? 0) ?>"><?= $num($row['current_quantity'] ?? 0) ?></td>
                    <td data-order="<?= (float)$row['price'] ?>"><?= $num($row['price']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
  #stock-tabs-content,
  #stock-tabs-content .dataTables_wrapper,
  .stock-tab-table {
    width: 100% !important;
  }
</style>

<script>
$(function(){
  var stockProductsTable = $('#stock-table').DataTable({
    processing: true,
    serverSide: true,
    autoWidth: false,
    pageLength: 50,
    lengthMenu: [[20, 50, 100, 200], [20, 50, 100, 200]],
    order: [[4, 'desc']],
    ajax: adminpath + '/stock/products',
    columnDefs: [
      {targets: [0, 4, 5, 6], type: 'num'}
    ]
  });
  var stockHistoryTable = $('#stock-history-table').DataTable({order:[[0,'desc']], pageLength:50, autoWidth:false});
  var stockBranchesTable = $('#stock-branches-table').DataTable({order:[[2,'desc']], pageLength:25, autoWidth:false});

  $('a[data-toggle="pill"]').on('shown.bs.tab', function () {
    setTimeout(function () {
      $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
      stockProductsTable.columns.adjust();
      stockHistoryTable.columns.adjust();
      stockBranchesTable.columns.adjust();
    }, 50);
  });

  var chart = $('#stock-report-chart');
  if (chart.length) {
    var stockValues = <?= json_encode($values) ?>;
    var minStock = stockValues.length ? Math.min.apply(null, stockValues) : 0;
    var maxStock = stockValues.length ? Math.max.apply(null, stockValues) : 0;
    var padding = Math.max(10, Math.ceil((maxStock - minStock) * 0.2));
    new Chart(chart, {
      type: 'line',
      data: {
        labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
          data: stockValues,
          borderColor: '#28a745',
          backgroundColor: 'rgba(40,167,69,.08)',
          pointBackgroundColor: '#28a745',
          fill: true
        }]
      },
      options: {
        maintainAspectRatio: false,
        legend: {display: false},
        tooltips: {callbacks: {label: function(item) { return Number(item.yLabel || 0).toLocaleString('ru-RU') + ' шт.'; }}},
        scales: {yAxes: [{ticks: {suggestedMin: Math.max(0, minStock - padding), suggestedMax: maxStock + padding}}]}
      }
    });
  }
});
</script>
