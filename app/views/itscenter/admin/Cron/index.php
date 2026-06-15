<?php if ($_SESSION['user']['groups'] == 1) { ?>
<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$phpBin = '/usr/local/bin/php8.2';
$projectRoot = '/home/s/shinaspec/its-center.ru/public_html';
$runner = $projectRoot . '/public/cron/run_task_cli.php';
?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">CRON задания</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= ADMIN; ?>">Главная</a></li>
          <li class="breadcrumb-item active">Список CRON заданий</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="menu_btn mb-3">
        <a href="<?= ADMIN; ?>/cron/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить задание</a>
      </div>

      <div class="alert alert-info">
        Запускайте задания только через CLI на хостинге. HTTP-запуск через сайт отключен, чтобы тяжелые задачи не блокировали страницы и не приводили к 5xx.
      </div>

      <div class="card">
        <div class="card-header"><h3 class="card-title">Список CRON заданий</h3></div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="cron-table" class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Название</th>
                  <th>Системное имя</th>
                  <th>Категории</th>
                  <th>Последний запуск</th>
                  <th>CLI-команда</th>
                  <th style="width:70px">Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($crons as $cron): ?>
                  <?php
                    $command = $phpBin . ' ' . $runner . ' --id=' . (int)$cron['id'];
                    if (($cron['url_params'] ?? '') === 'refresh-tovars-server') {
                        $command .= ' --limit=20 --pause-ms=250';
                    }
                    $command .= ' > ' . $projectRoot . '/public/cron/logs/cron_' . (int)$cron['id'] . '.log 2>&1';
                  ?>
                  <tr>
                    <td><?= (int)$cron['id']; ?></td>
                    <td><?= $e($cron['name']); ?></td>
                    <td><code><?= $e($cron['url_params']); ?></code></td>
                    <td><?= $e($cron['categories']); ?></td>
                    <td><?= $e($cron['date_update']); ?></td>
                    <td>
                      <textarea class="form-control form-control-sm cron-command" rows="2" readonly><?= $e($command); ?></textarea>
                    </td>
                    <td>
                      <a href="<?= ADMIN; ?>/cron/edit?id=<?= (int)$cron['id']; ?>"><i class="fas fa-pencil-alt"></i></a>
                      <a class="delete ml-2" href="<?= ADMIN; ?>/cron/delete?id=<?= (int)$cron['id']; ?>"><i class="fas fa-times-circle text-danger"></i></a>
                    </td>
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
.cron-command{
  min-width: 520px;
  font-family: Consolas, Monaco, monospace;
  font-size: 12px;
  resize: vertical;
}
</style>
<script>
$(function(){
  $('#cron-table').DataTable({pageLength:50, order:[[0,'asc']]});
});
</script>

<?php } else { ?>
<div class="alert alert-warning alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
  <h5><i class="icon fas fa-exclamation-triangle"></i> Доступ закрыт!</h5>
  На этой странице есть ограничения доступа. Обратитесь к администратору.
</div>
<?php } ?>
