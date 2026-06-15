<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="content-header">
  <div class="container-fluid"><div class="row mb-2">
    <div class="col-sm-6"><h1 class="m-0">Журнал действий</h1></div>
    <div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="<?= ADMIN ?>">Главная</a></li><li class="breadcrumb-item active">Журнал действий</li></ol></div>
  </div></div>
</div>

<section class="content">
  <div class="card">
    <div class="card-header">
      <form class="form-inline" method="get">
        <label class="mr-2">Период</label>
        <input class="form-control form-control-sm mr-2" type="date" name="from" value="<?= $e($dateFrom) ?>">
        <input class="form-control form-control-sm mr-2" type="date" name="to" value="<?= $e($dateTo) ?>">
        <button class="btn btn-sm btn-primary" type="submit">Показать</button>
      </form>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="activity-table" class="table table-bordered table-hover">
          <thead><tr><th>Дата</th><th>Раздел</th><th>Действие</th><th>Пользователь</th><th>Объект</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($activity as $row): ?>
              <tr>
                <td><?= $e($row['date_modified']) ?></td>
                <td><?= $e($row['name_gh'] ?: 'Система') ?></td>
                <td><?= $e($row['name_ah'] ?: '') ?></td>
                <td><?= $e($row['actor_name'] ?: '') ?></td>
                <td><?= $e($row['name_tbl']) ?> #<?= (int)$row['id_tbl'] ?></td>
                <td><a class="btn btn-xs btn-outline-primary" href="<?= $e($row['activity_url'] ?? ADMIN . '/activity') ?>">Открыть</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
<script>$(function(){ $('#activity-table').DataTable({order:[[0,'desc']], pageLength:50}); });</script>
