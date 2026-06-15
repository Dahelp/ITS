<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Обратные звонки</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= ADMIN ?>">Главная</a></li>
          <li class="breadcrumb-item active">Обратные звонки</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="card">
    <div class="card-header"><h3 class="card-title">Список заявок</h3></div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="callback-table" class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Статус</th>
              <th>Тема</th>
              <th>Клиент</th>
              <th>Телефон</th>
              <th>Дата</th>
              <th style="width:110px">Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($callback as $item): ?>
              <?php
                $statusId = (string)($item['status'] ?? '0');
                $isClosed = (string)($item['hide'] ?? 'show') !== 'show';
                $status = match ($statusId) {
                    '1' => '<span class="badge badge-info">В обработке</span>',
                    '2' => '<span class="badge badge-success">Обработана</span>',
                    default => '<span class="badge badge-danger">Новая</span>',
                };
                if ($isClosed) {
                    $status = '<span class="badge badge-secondary">Закрыта</span>';
                }
              ?>
              <tr class="<?= !$isClosed && $statusId === '0' ? 'table-warning' : '' ?>">
                <td><?= (int)$item['id'] ?></td>
                <td><?= $status ?></td>
                <td><?= $e($item['topic'] ?: 'Обратный звонок') ?></td>
                <td><?= !empty($item['user_id']) ? '<a href="' . ADMIN . '/user/edit?id=' . (int)$item['user_id'] . '">' . $e($item['user_name'] ?: ('#' . $item['user_id'])) . '</a>' : '<span class="text-muted">Гость</span>' ?></td>
                <td><a href="tel:<?= $e(preg_replace('/\D+/', '', (string)$item['phone'])) ?>"><?= $e($item['phone']) ?></a></td>
                <td><?= $e($item['date_create']) ?></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a class="btn btn-default" title="Открыть" data-toggle="tooltip" href="<?= ADMIN ?>/callback/view?id=<?= (int)$item['id'] ?>"><i class="fas fa-eye text-primary"></i></a>
                    <?php if (!$isClosed && $statusId === '0'): ?><a class="btn btn-default" title="В обработку" data-toggle="tooltip" href="<?= ADMIN ?>/callback/process?id=<?= (int)$item['id'] ?>"><i class="fas fa-phone-volume text-info"></i></a><?php endif; ?>
                    <?php if (!$isClosed): ?><a class="btn btn-default" title="Обработано" data-toggle="tooltip" href="<?= ADMIN ?>/callback/done?id=<?= (int)$item['id'] ?>"><i class="fas fa-check-circle text-success"></i></a><?php endif; ?>
                    <?php if (!$isClosed): ?><a class="btn btn-default delete" title="Закрыть" data-toggle="tooltip" href="<?= ADMIN ?>/callback/delete?id=<?= (int)$item['id'] ?>"><i class="fas fa-times-circle text-danger"></i></a><?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<script>
$(function(){
  $('#callback-table').DataTable({order:[[0,'desc']], pageLength:50});
});
</script>
