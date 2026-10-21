<!-- Content Header -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">AVITO — мои объявления</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
          <li class="breadcrumb-item active">AVITO</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="menu_btn">
        <a href="<?=ADMIN;?>/avito-chat" class="btn btn-primary"><i class="fas fa-comments"></i> Чат Авито</a>
        <a href="<?=ADMIN;?>/avito/add" class="btn btn-primary">
            <i class="fa fa-fw fa-plus"></i> Добавить объявление
        </a>
        <a href="<?=ADMIN;?>/avito/export" class="btn btn-success">
            <i class="fas fa-file-code"></i> Экспорт XML
        </a>
        <a href="<?=ADMIN;?>/avito/import-xls" class="btn btn-warning">
            <i class="fas fa-file-excel"></i> Импорт XLSX
        </a>
        <a href="<?=ADMIN;?>/avito/sync-products" class="btn btn-info">
            <i class="fas fa-sync-alt"></i> Синхронизировать с товарами
        </a>
        <a href="<?=ADMIN;?>/avito/sync-statuses" class="btn btn-outline-info">
            <i class="fas fa-cloud-download-alt"></i> Обновить статусы Avito
        </a>
        <a target="_blank" href="<?=PATH;?>/avito/refresh-tovars" class="btn btn-secondary">
            <i class="fas fa-rss"></i> XML-фид
        </a>
    </div>
      <div class="card">
        <div class="card-header d-flex p-0">
          <h3 class="card-title p-3">Мои объявления</h3>
        </div>
        <div class="card-body">
          <?php $summary = $publicationSummary ?? []; ?>
          <div class="row mb-3">
            <div class="col-lg-2 col-6"><div class="small-box bg-light"><div class="inner"><h3><?= (int)($summary['total'] ?? 0); ?></h3><p>Всего в базе</p></div></div></div>
            <div class="col-lg-2 col-6"><div class="small-box bg-primary"><div class="inner"><h3><?= (int)($summary['feed_active'] ?? 0); ?></h3><p>В XML-выгрузке</p></div></div></div>
            <div class="col-lg-2 col-6"><div class="small-box bg-success"><div class="inner"><h3><?= (int)($summary['avito_published'] ?? 0); ?></h3><p>Опубликовано на Avito</p></div></div></div>
            <div class="col-lg-2 col-6"><div class="small-box bg-secondary"><div class="inner"><h3><?= (int)($summary['avito_not_published'] ?? 0); ?></h3><p>Не опубликовано</p></div></div></div>
            <div class="col-lg-2 col-6"><div class="small-box bg-warning"><div class="inner"><h3><?= (int)($summary['avito_unchecked'] ?? 0); ?></h3><p>Не проверено API</p></div></div></div>
            <div class="col-lg-2 col-6"><div class="small-box bg-info"><div class="inner"><h3><?= (int)($summary['with_avito_id'] ?? 0); ?></h3><p>Связано по Avito ID</p></div></div></div>
          </div>
          <?php if (!empty($summary['without_avito_id'])): ?>
            <div class="alert alert-warning py-2"><strong><?= (int)$summary['without_avito_id']; ?></strong> объявлений пока без числового Avito ID — их фактический статус нельзя сверить с API.</div>
          <?php endif; ?>
          <div class="table-responsive">
            <table id="avito-table" class="table table-bordered display" width="100%">
              <thead>
              <tr>
                <th>ID</th>
                <th>Фото</th>
                <th>Avito ID</th>
                <th>Артикул</th>
                <th>Название</th>
                <th>Категория</th>
                <th>Менеджер</th>
                <th>Наличие</th>
                <th>В нашей выгрузке</th>
                <th>На Avito</th>
                <th>Готовность Avito</th>
                <th>Действия</th>
              </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<script>
$(function () {
  // Фото (1) и Действия (11) не экспортируем
  var hideFromExport = [1, 11];

  var table = $('#avito-table').DataTable({
    processing: true,
    serverSide: true,
    stateSave: true,
    stateLoadParams: function (settings, data) {
      if (!data.columns || data.columns.length !== 12) {
        return false;
      }
    },
    lengthChange: true,
    lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "Все"]],
    aoColumnDefs: [
      { 'bSortable': false, 'aTargets': [1, 9, 10, 11] }
    ],
    dom: '<"blok-bottoms"B><"blok-table"lfrtip>',
    ajax: { url: adminpath + '/avito/server-processing' },
    buttons: [
      {
        extend: 'copyHtml5',
        exportOptions: {
          columns: function(idx){
            var isVisible      = table.column(idx).visible();
            var isNotForExport = $.inArray(idx, hideFromExport) !== -1;
            return isVisible && !isNotForExport;
          }
        },
        text: 'Копировать'
      },
      { extend: 'csv',
        exportOptions: { columns: function(idx){
          var v = table.column(idx).visible();
          return v && $.inArray(idx, hideFromExport) === -1;
        }}
      },
      { extend: 'excel',
        exportOptions: { columns: function(idx){
          var v = table.column(idx).visible();
          return v && $.inArray(idx, hideFromExport) === -1;
        }}
      },
      { extend: 'pdf',
        exportOptions: { columns: function(idx){
          var v = table.column(idx).visible();
          return v && $.inArray(idx, hideFromExport) === -1;
        }}
      },
      { extend: 'print',
        exportOptions: { columns: function(idx){
          var v = table.column(idx).visible();
          return v && $.inArray(idx, hideFromExport) === -1;
        }}
      },
      { extend: 'colvis', text: 'Настроить колонки' }
    ],
    createdRow: function(row, data){
      if (data[8].indexOf('Черновик') !== -1) $(row).css('background-color', '#fff7da');
      if (data[8].indexOf('Не выгружается') !== -1) $(row).css('background-color', '#d7d6d6');
    }
  });
});
</script>



