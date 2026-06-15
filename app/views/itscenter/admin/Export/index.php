<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$history = $history ?? [];
$historyTableMissing = !empty($historyTableMissing);
?>
<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Экспорт</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
          <li class="breadcrumb-item active">Экспорт</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<!-- Main content -->
<section class="content">
  <div class="row">
    <div class="col-12">
      <form method="post" action="<?=ADMIN;?>/export" role="form" data-toggle="validator">
        <div class="card">
          <div class="card-header d-flex p-0">
            <h3 class="card-title p-3">Экспорт товаров</h3>
          </div>
          <div class="card-body">
            <div class="form-group row">
              <label class="col-sm-3 col-form-label" for="format">Формат <span class="text-danger">*</span></label>
              <div class="col-sm-9">
                <select class="form-control" name="format" id="format" required>
                  <option value="xls">XLS</option>
                  <option value="csv">CSV</option>
                  <option value="xml">YML/XML</option>
                  <option value="pdf">PDF</option>
                </select>
              </div>
            </div>

            <div class="form-group row">
              <label class="col-sm-3 col-form-label" for="price_type">Цены <span class="text-danger">*</span></label>
              <div class="col-sm-9">
                <select class="form-control" name="price_type" id="price_type" required>
                  <option value="retail">Розница</option>
                  <option value="opt">Опт</option>
                </select>
              </div>
            </div>

            <div class="form-group row">
              <label class="col-sm-3 col-form-label" for="actSelect">Вывод данных <span class="text-danger">*</span></label>
              <div class="col-sm-9">
                <select id="actSelect" class="form-control" name="actSelect" aria-required="true" onchange="Selected(this)" required>
                  <option value="" selected="selected">Выберите что выгружать</option>
                  <option value="5">Все товары</option>
                  <option value="1">Определённую категорию</option>
                  <option value="2">По производителю</option>
                  <option value="4">Категория и производитель</option>
                  <option value="3">Артикул товара</option>
                </select>
              </div>
            </div>

            <div id="Block1" class="form-group row export-filter" style="display: none;">
              <label class="col-sm-3 col-form-label" for="category_id">Категория товаров</label>
              <div class="col-sm-9">
                <select class="form-control" name="category_id" id="category_id">
                  <option value="" selected="selected">Выберите категорию</option>
                  <?php if(!empty($categories)): ?>
                    <?php foreach($categories as $category): ?>
                      <option value="<?=$category['id'];?>"><?=$e($category['name']);?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
            </div>

            <div id="Block2" class="form-group row export-filter" style="display: none;">
              <label class="col-sm-3 col-form-label" for="brand_id">Производитель</label>
              <div class="col-sm-9">
                <select id="brand_id" class="form-control" name="brand_id">
                  <option value="" selected="selected">Выберите производителя</option>
                  <?php if(!empty($brands)): ?>
                    <?php foreach($brands as $brand): ?>
                      <option value="<?=$brand['id'];?>"><?=$e($brand['name']);?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
            </div>

            <div id="Block3" class="form-group row export-filter" style="display: none;">
              <label class="col-sm-3 col-form-label" for="article">Артикул товара</label>
              <div class="col-sm-9">
                <input class="form-control" type="text" name="article" id="article" placeholder="Можно указать несколько артикулов через запятую, пробел или ;">
              </div>
            </div>
          </div>
        </div>
        <div class="box-footer mb-3">
          <button type="submit" class="btn btn-primary">Экспорт</button>
        </div>
      </form>

      <?php if($historyTableMissing): ?>
        <div class="alert alert-warning">
          Таблица истории экспорта еще не создана. Выполните SQL из файла <code>scripts/create_export_history.sql</code>.
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">История созданных файлов</h3>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="export-history-table" class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Файл</th>
                  <th>Формат</th>
                  <th>Товаров</th>
                  <th>Размер</th>
                  <th>Создан</th>
                  <th style="width:70px">Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($history as $file): ?>
                  <?php
                    $fileUrl = PATH . '/' . ltrim((string)$file['file_path'], '/');
                    $sizeKb = round(((int)$file['file_size']) / 1024, 2);
                  ?>
                  <tr>
                    <td><?= (int)$file['id']; ?></td>
                    <td><a href="<?= $e($fileUrl); ?>" target="_blank"><?= $e($file['file_name']); ?></a></td>
                    <td><?= $e($file['format_title'] ?: $file['format']); ?></td>
                    <td><?= (int)$file['products_count']; ?></td>
                    <td><?= $e($sizeKb); ?> КБ</td>
                    <td><?= $e($file['created_at']); ?></td>
                    <td>
                      <a href="<?= $e($fileUrl); ?>" target="_blank" title="Скачать"><i class="fas fa-download"></i></a>
                      <a class="delete ml-2" href="<?= ADMIN; ?>/export/delete?id=<?= (int)$file['id']; ?>" title="Удалить"><i class="fas fa-times-circle text-danger"></i></a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
    </div>
  </div>
</section>
<!-- /.content -->

<script>
function Selected(select) {
  var value = select.value;
  var categoryBlock = document.getElementById('Block1');
  var brandBlock = document.getElementById('Block2');
  var articleBlock = document.getElementById('Block3');
  var categorySelect = document.getElementById('category_id');
  var brandSelect = document.getElementById('brand_id');
  var articleInput = document.getElementById('article');

  categoryBlock.style.display = (value === '1' || value === '4') ? 'flex' : 'none';
  brandBlock.style.display = (value === '2' || value === '4') ? 'flex' : 'none';
  articleBlock.style.display = value === '3' ? 'flex' : 'none';

  categorySelect.required = value === '1' || value === '4';
  brandSelect.required = value === '2' || value === '4';
  articleInput.required = value === '3';

  if(!categorySelect.required) {
    categorySelect.value = '';
  }
  if(!brandSelect.required) {
    brandSelect.value = '';
  }
  if(!articleInput.required) {
    articleInput.value = '';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  Selected(document.getElementById('actSelect'));
  $('#export-history-table').DataTable({pageLength: 25, order: [[0, 'desc']]});
});
</script>
