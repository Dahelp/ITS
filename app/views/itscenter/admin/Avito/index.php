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
        <a href="<?=ADMIN;?>/avito/add" class="btn btn-primary">
            <i class="fa fa-fw fa-plus"></i> Добавить объявление
        </a>
        <a href="<?=ADMIN;?>/avito/export" class="btn btn-success">
            <i class="fas fa-file-code"></i> Экспорт XML
        </a>
        <a href="<?=ADMIN;?>/avito/import-xls" class="btn btn-warning">
            <i class="fas fa-file-excel"></i> Импорт XLSX
        </a>
    </div>
      <div class="card">
        <div class="card-header d-flex p-0">
          <h3 class="card-title p-3">Мои объявления</h3>
        </div>
        <div class="card-body">
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
                <th>Статус</th>
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
  // Фото (1) и Действия (9) не экспортируем
  var hideFromExport = [1, 9];

  var table = $('#avito-table').DataTable({
    processing: true,
    serverSide: true,
    stateSave: true,
    lengthChange: true,
    lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "Все"]],
    aoColumnDefs: [
      { 'bSortable': false, 'aTargets': [1, 9] } // фото и действия не сортируются
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
      // data[8] — «Статус» (после добавления новых колонок)
      if (data[8] === 'Черновик') $(row).css('background-color', '#fff7da');
      if (data[8] === 'Архив')    $(row).css('background-color', '#d7d6d6');
    }
  });
});
</script>

