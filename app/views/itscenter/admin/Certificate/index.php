<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$statusLabels = ['active' => 'Действует', 'suspended' => 'Приостановлен', 'expired' => 'Истёк', 'archived' => 'Архив'];
?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Сертификаты и декларации</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
          <li class="breadcrumb-item active">Сертификаты</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="row">
    <div class="col-12">
      <div class="menu_btn">
        <a href="<?=ADMIN;?>/certificate/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить документ</a>
      </div>
      <div class="card card-primary card-outline">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-file-signature mr-2"></i>Документы соответствия</h3>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th style="width:70px">ID</th>
                  <th>Тип и номер</th>
                  <th style="width:190px">Срок действия</th>
                  <th style="width:130px;text-align:center">Статус</th>
                  <th style="width:110px;text-align:center">Применение</th>
                  <th style="width:100px;text-align:center">Реестр</th>
                  <th style="width:110px;text-align:center">Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($certificates as $item):
                  $expired = !empty($item['date_end']) && $item['date_end'] < date('Y-m-d');
                  $soon = !$expired && !empty($item['date_end']) && $item['date_end'] <= date('Y-m-d', strtotime('+60 days'));
                  $isActive = $item['status'] === 'active' && !$expired;
                ?>
                  <tr>
                    <td><?= (int)$item['id']; ?></td>
                    <td>
                      <strong><?= $e($item['number']); ?></strong>
                      <br><small class="text-muted"><?= $item['document_type'] === 'certificate' ? 'Сертификат соответствия' : 'Декларация о соответствии'; ?><?= $item['name'] ? ' · '.$e($item['name']) : ''; ?></small>
                    </td>
                    <td class="<?= $expired ? 'text-danger' : ($soon ? 'text-warning' : ''); ?>">
                      <?= $e($item['date_start'] ? date('d.m.Y', strtotime($item['date_start'])) : '—'); ?> — <?= $e($item['date_end'] ? date('d.m.Y', strtotime($item['date_end'])) : 'бессрочно'); ?>
                      <?= $expired ? '<br><strong><i class="fas fa-exclamation-circle mr-1"></i>Истёк</strong>' : ($soon ? '<br><strong><i class="fas fa-clock mr-1"></i>Истекает скоро</strong>' : ''); ?>
                    </td>
                    <td class="text-center"><span class="badge <?= $isActive ? 'bg-success' : 'bg-secondary'; ?>"><?= $e($statusLabels[$item['status']] ?? $item['status']); ?></span></td>
                    <td class="text-center"><span class="badge bg-info" title="Количество правил применения"><i class="fas fa-link mr-1"></i><?= (int)$item['assignments_count']; ?></span></td>
                    <td class="text-center"><a href="<?= $e($item['registry_url']); ?>" target="_blank" rel="noopener noreferrer" title="Открыть официальный реестр"><i class="fas fa-external-link-alt"></i></a></td>
                    <td class="text-center">
                      <a href="<?=ADMIN;?>/certificate/edit?id=<?= (int)$item['id']; ?>" title="Редактировать"><i class="fas fa-pencil-alt"></i></a>
                      <a class="ml-2" href="<?=ADMIN;?>/certificate/delete?id=<?= (int)$item['id']; ?>" title="Переместить в архив" onclick="return confirm('Переместить документ в архив?')"><i class="fas fa-archive text-warning"></i></a>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$certificates): ?>
                  <tr><td colspan="7" class="text-muted text-center py-4"><i class="far fa-folder-open mr-1"></i>Документы ещё не добавлены</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
