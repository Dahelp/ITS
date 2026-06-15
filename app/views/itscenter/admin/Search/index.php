<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Поиск</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= ADMIN ?>">Главная</a></li>
          <li class="breadcrumb-item active">Поиск</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="card">
    <div class="card-header">
      <form class="input-group" method="get" action="<?= ADMIN ?>/search">
        <input class="form-control" type="search" name="q" value="<?= $e($query) ?>" placeholder="Введите товар, номер заказа, клиента, телефон или компанию">
        <div class="input-group-append">
          <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
        </div>
      </form>
    </div>
    <div class="card-body">
      <?php if ($query === ''): ?>
        <div class="text-muted">Введите запрос для поиска по товарам, технике, пользователям, компаниям, заказам и заявкам.</div>
      <?php elseif (!$results): ?>
        <div class="alert alert-warning">По запросу “<?= $e($query) ?>” ничего не найдено.</div>
      <?php else: ?>
        <div class="mb-3 text-muted">Найдено: <?= count($results) ?></div>
        <div class="table-responsive">
          <table id="admin-search-table" class="table table-bordered table-hover">
            <thead>
              <tr>
                <th style="width:180px">Тип</th>
                <th>Название</th>
                <th>Детали</th>
                <th style="width:90px"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($results as $item): ?>
                <tr>
                  <td><span class="badge badge-light border"><?= $e($item['type']) ?></span></td>
                  <td><?= $e($item['name']) ?></td>
                  <td class="text-muted"><?= $e($item['subtitle'] ?? '') ?></td>
                  <td>
                    <a class="btn btn-xs btn-outline-primary" href="<?= $e($item['url']) ?>">
                      <i class="fas fa-external-link-alt"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<script>
$(function(){
  $('#admin-search-table').DataTable({order:[[0,'asc']], pageLength:25});
});
</script>
