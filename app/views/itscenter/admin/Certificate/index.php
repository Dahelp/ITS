<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="content-header"><div class="container-fluid"><div class="row mb-2">
  <div class="col-sm-6"><h1 class="m-0">Сертификаты и декларации</h1></div>
  <div class="col-sm-6"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li><li class="breadcrumb-item active">Сертификаты</li></ol></div>
</div></div></div>
<section class="content"><div class="row"><div class="col-12">
  <div class="mb-3"><a href="<?=ADMIN;?>/certificate/add" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить документ</a></div>
  <div class="card"><div class="card-header"><h3 class="card-title">Документы соответствия</h3></div><div class="card-body table-responsive">
    <table class="table table-bordered table-hover"><thead><tr><th>Тип и номер</th><th>Срок</th><th>Статус</th><th>Назначения</th><th>Реестр</th><th>Действия</th></tr></thead><tbody>
    <?php foreach ($certificates as $item):
      $expired = !empty($item['date_end']) && $item['date_end'] < date('Y-m-d');
      $soon = !$expired && !empty($item['date_end']) && $item['date_end'] <= date('Y-m-d', strtotime('+60 days'));
    ?>
      <tr>
        <td><strong><?= $e($item['number']); ?></strong><br><small><?= $item['document_type'] === 'certificate' ? 'Сертификат соответствия' : 'Декларация о соответствии'; ?><?= $item['name'] ? ' · '.$e($item['name']) : ''; ?></small></td>
        <td class="<?= $expired ? 'text-danger' : ($soon ? 'text-warning' : ''); ?>"><?= $e($item['date_start'] ?: '—'); ?> — <?= $e($item['date_end'] ?: 'бессрочно'); ?><?= $expired ? '<br><strong>Истёк</strong>' : ($soon ? '<br><strong>Истекает скоро</strong>' : ''); ?></td>
        <td><span class="badge <?= $item['status'] === 'active' && !$expired ? 'badge-success' : 'badge-secondary'; ?>"><?= $e($item['status']); ?></span></td>
        <td><span class="badge badge-info"><?= (int)$item['assignments_count']; ?></span></td>
        <td><a href="<?= $e($item['registry_url']); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt"></i> Проверить</a></td>
        <td><a href="<?=ADMIN;?>/certificate/edit?id=<?= (int)$item['id']; ?>"><i class="fas fa-pencil-alt"></i></a> <a href="<?=ADMIN;?>/certificate/delete?id=<?= (int)$item['id']; ?>" onclick="return confirm('Переместить документ в архив?')"><i class="fas fa-archive text-danger"></i></a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$certificates): ?><tr><td colspan="6" class="text-muted text-center">Документы ещё не добавлены</td></tr><?php endif; ?>
    </tbody></table>
  </div></div>
</div></div></section>
