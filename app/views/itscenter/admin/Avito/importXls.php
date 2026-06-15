<?php
/** @var array|null $preview */
/** @var array $errors */
/** @var array|null $result */
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">Импорт объявлений из XLSX</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>/avito">Avito</a></li>
          <li class="breadcrumb-item active">Импорт XLSX</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    <!-- Шаг 1: Загрузка файла -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Шаг 1. Загрузка файла</h3>
      </div>
      <div class="card-body">
        <form action="/admin/avito/import-xls" method="post" enctype="multipart/form-data">
          <div class="form-group">
            <label for="file">Файл XLSX с объявлениями (шаблон Авито)</label>
            <input type="file" name="file" id="file" class="form-control" required>
            <small class="form-text text-muted">
              Вкладка «Инструкция» и вкладки «Спр-*» будут пропущены автоматически.
            </small>
          </div>
          <button type="submit" name="do_upload" value="1" class="btn btn-primary">
            <i class="fas fa-upload"></i> Загрузить и показать предпросмотр
          </button>
        </form>
      </div>
    </div>

    <!-- Сообщение об успешном импорте -->
    <?php if (!empty($_SESSION['success'])): ?>
      <div class="alert alert-success mt-3">
        <?=htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8');?>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Сообщение об ошибке -->
    <?php if (!empty($_SESSION['error'])): ?>
      <div class="alert alert-danger mt-3">
        <?=htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8');?>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Шаг 2: Предпросмотр -->
    <?php if (!empty($preview)): ?>
      <div class="card mt-3">
        <div class="card-header bg-info">
          <h3 class="card-title">Шаг 2. Предпросмотр</h3>
        </div>
        <div class="card-body">
          <p>
            Валидных строк: <strong><?=$preview['total_valid'];?></strong>,
            с ошибками: <strong><?=$preview['total_invalid'];?></strong>
          </p>

          <?php foreach ($preview['sheets'] as $sheet): ?>
            <h5 class="mt-3 mb-2">
              Лист: <?=htmlspecialchars($sheet['title'], ENT_QUOTES, 'UTF-8');?>
              (ок: <?=$sheet['rows_valid'];?>, ошибок: <?=$sheet['rows_invalid'];?>)
            </h5>

            <div class="table-responsive">
              <table class="table table-sm table-bordered table-striped">
                <thead>
                <tr>
                  <th>Строка</th>
                  <th>ID</th>
                  <th>Название</th>
                  <th>Категория</th>
                  <th>Тип товара</th>
                  <th>Цена</th>
                  <th>Ошибки</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($sheet['sample'] as $row): ?>
                  <tr class="<?=empty($row['errors']) ? '' : 'table-danger';?>">
                    <td><?=$row['row'];?></td>
                    <td><?=htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars($row['product_type'], ENT_QUOTES, 'UTF-8');?></td>
                    <td><?=htmlspecialchars($row['price'], ENT_QUOTES, 'UTF-8');?></td>
                    <td>
                      <?php if (!empty($row['errors'])): ?>
                        <ul class="mb-0">
                          <?php foreach ($row['errors'] as $msg): ?>
                            <li><?=htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');?></li>
                          <?php endforeach; ?>
                        </ul>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endforeach; ?>

          <?php if ($preview['total_valid'] > 0): ?>
            <form action="/admin/avito/import-xls" method="post" class="mt-3">
              <button type="submit" name="do_import" value="1" class="btn btn-success"
                      onclick="return confirm('Импортировать все валидные строки в БД?');">
                <i class="fas fa-check"></i> Подтвердить импорт
              </button>
            </form>
          <?php else: ?>
            <p class="text-danger mt-3">
              Нет валидных строк для импорта. Исправьте файл и загрузите снова.
            </p>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Ошибки при реальном импорте -->
    <?php if (!empty($errors)): ?>
      <div class="card mt-3">
        <div class="card-header bg-danger">
          <h3 class="card-title">Ошибки при реальном импорте</h3>
        </div>
        <div class="card-body table-responsive">
          <table class="table table-sm table-bordered table-striped">
            <thead>
            <tr>
              <th>Лист</th>
              <th>Строка</th>
              <th>ID</th>
              <th>Название</th>
              <th>Ошибка</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($errors as $err): ?>
              <tr>
                <td><?=htmlspecialchars($err['sheet'], ENT_QUOTES, 'UTF-8');?></td>
                <td><?=htmlspecialchars($err['row'] ?? '-', ENT_QUOTES, 'UTF-8');?></td>
                <td><?=htmlspecialchars($err['id'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
                <td><?=htmlspecialchars($err['title'] ?? '', ENT_QUOTES, 'UTF-8');?></td>
                <td>
                  <?php if (!empty($err['errors'])): ?>
                    <ul class="mb-0">
                      <?php foreach ($err['errors'] as $msg): ?>
                        <li><?=htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');?></li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  </div>
</section>
