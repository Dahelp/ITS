<?php
// app/views/admin/Order/stat_product.php
// Ожидаемые переменные: $orders, $product, $productId, $count_sales, $curr

$dataSet = [];

if (!empty($orders)) {
    foreach ($orders as $item) {

        $orderId = (int)($item['id'] ?? 0);

        $inv = $item['inv'] ?? '';
        $orderLink = $item['order_link'] ?? (ADMIN . "/order/view?id=" . $orderId);
        $invHtml = "<a target='_blank' href='".htmlspecialchars($orderLink)."'>".htmlspecialchars($inv)."</a>";

        // Контакт
        $contactName  = $item['contact_name'] ?? ($item['user_name'] ?? '');
        $contactEmail = $item['contact_email'] ?? ($item['user_email'] ?? '');
        $contactLink  = $item['contact_link'] ?? (ADMIN . "/user/edit?id=" . (int)($item['user_id'] ?? 0));

        $contactHtml = $contactLink
            ? "<a href='".htmlspecialchars($contactLink)."'>".htmlspecialchars($contactName)."</a>"
            : htmlspecialchars($contactName);

        // Компания / email
        if (!empty($item['comp_short_name'])) {
            $companyInfo = htmlspecialchars($item['comp_short_name'])
                . (!empty($item['inn']) ? (" (" . htmlspecialchars($item['inn']) . ")") : '');
        } else {
            $companyInfo = htmlspecialchars($contactEmail);
        }

        $contactBlock = $contactHtml . "<br />" . $companyInfo;

        // Кол-во по позиции
        $qtyItem = (int)($item['qty_item'] ?? 0);

        // Сумма заказа
        $sum = (float)($item['order_sum'] ?? 0);
        $symL = $curr['symbol_left'] ?? '';
        $symR = $curr['symbol_right'] ?? '';
        $sumHtml = htmlspecialchars($symL) . " " . $sum . " " . htmlspecialchars($symR);

        // Дата заказа
        $date = \ishop\App::contdatetime($item['date'] ?? '');

        // Источник / Ответственный
        $source  = htmlspecialchars($item['source'] ?? '—');
        $manager = htmlspecialchars($item['manager_name'] ?? 'Не назначен');

        // Действия
        $option = "<a target='_blank' href='".htmlspecialchars($orderLink)."'><i class='fas fa-fw fa-eye'></i></a>";

        $dataSet[] = [
            (string)$orderId,
            $invHtml,
            $contactBlock,
            (string)$qtyItem,
            $sumHtml,
            (string)$date,
            $source,
            $manager,
            $option,
        ];
    }
}
?>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-8">
        <h1 class="m-0">Продажи по позиции</h1>
        <div class="text-muted" style="margin-top:6px;">
          <strong><?= htmlspecialchars($product['title'] ?? '') ?></strong>
          <span class="ml-2">— покупок: <strong><?= (int)$count_sales ?></strong></span>
        </div>
      </div>
      <div class="col-sm-4">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= ADMIN; ?>">Главная</a></li>
          <li class="breadcrumb-item"><a href="<?= ADMIN; ?>/product">Товары</a></li>
          <li class="breadcrumb-item active">Продажи позиции</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="row">
    <div class="col-md-12">

      <div class="menu_btn">
        <a href="<?= ADMIN; ?>/product/edit?id=<?= (int)$productId ?>" class="btn btn-secondary">
          <i class="fas fa-fw fa-arrow-left"></i> Вернуться в карточку товара
        </a>
      </div>

      <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Список заказов по позиции: <?= htmlspecialchars($product['name'] ?? '') ?>
            </h3>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table id="example" class="table table-bordered display" width="100%"></table>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<script>
(function(){
  // Если jQuery или DataTables не подключены — покажем это сразу.
  if (typeof window.jQuery === 'undefined') {
    console.error('jQuery не загружен');
    return;
  }
  if (!jQuery.fn || typeof jQuery.fn.DataTable === 'undefined') {
    console.error('DataTables не загружен');
    return;
  }

  var dataSet = <?= json_encode($dataSet, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  jQuery(function($){
    $('#example').DataTable({
      lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "Все"]],
      aaSorting: [[0, "desc"]],
      stateSave: true,
      aoColumnDefs: [
        { bSortable: false, aTargets: [8] }
      ],
      data: dataSet,
      columns: [
        { title: "ID" },
        { title: "Номер заказа" },
        { title: "Контакт" },
        { title: "Кол-во (по позиции)" },
        { title: "Сумма заказа" },
        { title: "Дата заказа" },
        { title: "Источник" },
        { title: "Ответственный" },
        { title: "Действия", width: "60px" }
      ]
    });
  });
})();
</script>
