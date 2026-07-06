<?php if ($_SESSION['user']['groups'] == 1) { ?>
<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$tasks = [
    'audit' => [
        'title' => 'Проверить существующие редиректы',
        'class' => 'btn-outline-primary',
    ],
    'plan' => [
        'title' => 'Найти недостающие редиректы',
        'class' => 'btn-outline-info',
    ],
    'apply' => [
        'title' => 'Добавить недостающие редиректы',
        'class' => 'btn-success',
    ],
    'cleanup-plan' => [
        'title' => 'Найти лишние /category/shiny/{filter}',
        'class' => 'btn-outline-warning',
    ],
    'cleanup-apply' => [
        'title' => 'Удалить лишние /category/shiny/{filter}',
        'class' => 'btn-danger',
    ],
];
?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">SEO редиректы фильтров</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?= ADMIN; ?>">Главная</a></li>
          <li class="breadcrumb-item active">SEO редиректы фильтров</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Запуск обслуживания</h3></div>
        <div class="card-body">
          <div class="alert alert-info">
            Сначала запустите проверку и план. Применение меняет базу и пишет backup/report в папку <code>tmp/filter-parent-redirects-...</code>.
          </div>

          <div class="d-flex flex-wrap" style="gap: 8px;">
            <?php foreach ($tasks as $task => $meta): ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="task" value="<?= $e($task); ?>">
                <button type="submit" class="btn <?= $e($meta['class']); ?>">
                  <?= $e($meta['title']); ?>
                </button>
              </form>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <?php if (!empty($result)): ?>
        <?php
        $json = json_decode((string)($result['output'] ?? ''), true);
        $outDir = is_array($json) ? ($json['out_dir'] ?? '') : '';
        ?>
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Результат: <?= $e($commandLabel); ?></h3>
          </div>
          <div class="card-body">
            <p>
              Статус:
              <?php if (!empty($result['ok'])): ?>
                <span class="badge badge-success">OK</span>
              <?php else: ?>
                <span class="badge badge-danger">Ошибка</span>
              <?php endif; ?>
              Код: <code><?= (int)($result['code'] ?? 0); ?></code>
            </p>

            <?php if ($outDir): ?>
              <p>Папка отчёта: <code><?= $e($outDir); ?></code></p>
            <?php endif; ?>

            <label>Вывод</label>
            <textarea class="form-control" rows="18" readonly><?= $e($result['output'] ?? ''); ?></textarea>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php } ?>
