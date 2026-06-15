<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$folderQuery = $currentFolder === 'inbox' ? '' : ('?folder=' . rawurlencode($currentFolder));
$ajaxParams = array_filter([
    'folder' => $currentFolder === 'inbox' ? null : $currentFolder,
    'seen' => $_GET['seen'] ?? null,
], static fn($v) => $v !== null && $v !== '');
$ajaxUrl = ADMIN . '/mailbox/server-processing' . ($ajaxParams ? '?' . http_build_query($ajaxParams) : '');
?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0"><?= $e($folderTitle) ?></h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= ADMIN ?>">Главная</a></li>
          <li class="breadcrumb-item active"><?= $e($folderTitle) ?></li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="row">
    <div class="col-md-2">
      <?php new \app\widgets\mailbox\Mailbox('mailbox_tpl.php', compact('folderStats', 'currentFolder')); ?>
    </div>
    <div class="col-md-10">
      <div class="card card-primary card-outline">
        <div class="card-header">
          <form id="mailbox-bulk-form" action="<?= ADMIN ?>/mailbox/bulk" method="post" class="form-inline">
            <div class="btn-group btn-group-sm mr-2">
              <button type="button" class="btn btn-default" id="mail-check-all" title="Выбрать письма">
                <i class="far fa-square"></i>
              </button>
              <button type="submit" name="action" value="seen" class="btn btn-default" title="Отметить прочитанными">
                <i class="far fa-envelope-open text-success"></i>
              </button>
              <button type="submit" name="action" value="unseen" class="btn btn-default" title="Отметить непрочитанными">
                <i class="far fa-envelope text-primary"></i>
              </button>
              <?php if ($currentFolder === 'Trash'): ?>
                <button type="submit" name="action" value="restore" class="btn btn-default" title="Вернуть во входящие">
                  <i class="fas fa-undo text-info"></i>
                </button>
                <button type="submit" name="action" value="delete" class="btn btn-default delete" title="Удалить окончательно">
                  <i class="fas fa-times-circle text-danger"></i>
                </button>
              <?php else: ?>
                <button type="submit" name="action" value="trash" class="btn btn-default" title="В удалённые">
                  <i class="far fa-trash-alt text-danger"></i>
                </button>
              <?php endif; ?>
            </div>
            <span id="mailbox-selected" class="text-muted small">Ничего не выбрано</span>
          </form>
          <?php if ($currentFolder === 'Trash'): ?>
            <a href="<?= ADMIN ?>/mailbox/purge-trash" class="btn btn-default btn-sm float-right delete" title="Очистить удалённые письма старше 1 года">
              <i class="fas fa-broom text-danger"></i>
            </a>
          <?php endif; ?>
        </div>
        <div class="card-body p-4">
          <div class="table-responsive">
            <table id="mailbox-table" class="table table-bordered table-hover table-striped" style="width:100%">
              <thead>
                <tr>
                  <th style="width:34px"></th>
                  <th>Email</th>
                  <th>Тема</th>
                  <th style="width:34px"></th>
                  <th></th>
                  <th style="width:110px">Дата</th>
                  <th style="width:82px">Действия</th>
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
$(function(){
  var selected = {};

  function syncSelectedInputs() {
    $('#mailbox-bulk-form input[name="ids[]"]').remove();
    var ids = Object.keys(selected);
    ids.forEach(function(id) {
      $('<input>', {type: 'hidden', name: 'ids[]', value: id}).appendTo('#mailbox-bulk-form');
    });
    $('#mailbox-selected').text(ids.length ? ('Выбрано: ' + ids.length) : 'Ничего не выбрано');
    $('#mail-check-all i').toggleClass('fa-check-square', ids.length > 0).toggleClass('fa-square', ids.length === 0);
  }

  var table = $('#mailbox-table').DataTable({
    processing: true,
    serverSide: true,
    lengthChange: true,
    lengthMenu: [[20, 50, 100], [20, 50, 100]],
    pageLength: 50,
    order: [[0, 'desc']],
    ajax: {url: '<?= $ajaxUrl ?>'},
    columns: [
      {orderable: false, searchable: false},
      null,
      null,
      {orderable: false, searchable: false},
      {visible: false, searchable: false},
      null,
      {orderable: false, searchable: false}
    ],
    createdRow: function(row, data) {
      if (data[4] === '0') {
        $(row).css('font-weight', '700');
      }
    },
    drawCallback: function() {
      $('.mail-check').each(function(){
        this.checked = !!selected[$(this).val()];
      });
    }
  });

  $('#mailbox-table').on('change', '.mail-check', function(){
    if (this.checked) {
      selected[$(this).val()] = true;
    } else {
      delete selected[$(this).val()];
    }
    syncSelectedInputs();
  });

  $('#mail-check-all').on('click', function(){
    var checks = $('.mail-check');
    var shouldSelect = Object.keys(selected).length === 0;
    checks.each(function(){
      this.checked = shouldSelect;
      if (shouldSelect) {
        selected[$(this).val()] = true;
      } else {
        delete selected[$(this).val()];
      }
    });
    syncSelectedInputs();
  });

  $('#mailbox-bulk-form').on('submit', function(e){
    if (!Object.keys(selected).length) {
      e.preventDefault();
      alert('Выберите письма');
    }
  });
});
</script>
