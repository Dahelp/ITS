<?php $isEdit = ($mode === 'edit'); ?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0"><?= $isEdit ? 'Редактировать баннер' : 'Добавить баннер' ?></h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins/banners">Баннеры</a></li>
          <li class="breadcrumb-item active"><?= $isEdit ? 'Редактирование' : 'Добавление' ?></li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="row">
    <div class="col-12">
      <form method="post" enctype="multipart/form-data"
            action="<?=ADMIN;?>/plagins/<?= $isEdit ? 'banners-edit?id='.$item->id : 'banners-add'?>" role="form">

        <div class="card">
          <div class="card-header d-flex p-0">
            <h3 class="card-title p-3">Основное</h3>
          </div>
          <div class="card-body">
            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Название *</label>
              <div class="col-sm-10">
                <input type="text" name="name" class="form-control" required
                       value="<?= $isEdit ? h($item->name) : h($_SESSION['form_data']['name'] ?? '') ?>">
              </div>
            </div>

            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Анонс (150 симв)</label>
              <div class="col-sm-10">
                <input type="text" name="description" maxlength="180" class="form-control"
                       value="<?= $isEdit ? h($item->description) : h($_SESSION['form_data']['description'] ?? '') ?>">
              </div>
            </div>

            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Ссылка</label>
              <div class="col-sm-10">
                <input type="url" name="link_url" class="form-control" required
                       value="<?= $isEdit ? h($item->link_url) : h($_SESSION['form_data']['link_url'] ?? '') ?>">
                <div class="form-check mt-2">
                  <input type="checkbox" class="form-check-input" id="tb" name="target_blank"
                         <?= $isEdit ? ($item->target_blank ? 'checked' : '') : 'checked' ?>>
                  <label class="form-check-label" for="tb">Открывать в новой вкладке</label>
                </div>
              </div>
            </div>

            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Кнопка</label>
              <div class="col-sm-4">
                <input type="text" name="btn_text" class="form-control"
                       value="<?= $isEdit ? h($item->btn_text) : 'Подробнее' ?>">
              </div>
              <label class="col-sm-2 col-form-label">Класс кнопки</label>
              <div class="col-sm-4">
                <input type="text" name="btn_color" class="form-control"
                       value="<?= $isEdit ? h($item->btn_color) : 'btn-danger' ?>">
              </div>
            </div>

            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Период показа</label>
              <div class="col-sm-5">
                <input type="datetime-local" name="start_at" class="form-control"
                       value="<?= $isEdit && $item->start_at ? date('Y-m-d\TH:i', strtotime($item->start_at)) : '' ?>">
              </div>
              <div class="col-sm-5">
                <input type="datetime-local" name="end_at" class="form-control"
                       value="<?= $isEdit && $item->end_at ? date('Y-m-d\TH:i', strtotime($item->end_at)) : '' ?>">
              </div>
            </div>

            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Статус</label>
              <div class="col-sm-4">
                <select name="hide" class="form-control">
                  <option value="show" <?= $isEdit && $item->hide=='show' ? 'selected' : '' ?>>Активный</option>
                  <option value="hide" <?= $isEdit && $item->hide=='hide' ? 'selected' : '' ?>>Неактивный</option>
                  <option value="lock" <?= $isEdit && $item->hide=='lock' ? 'selected' : '' ?>>Закрыт от индексации</option>
                </select>
              </div>
              <label class="col-sm-2 col-form-label">Позиция</label>
              <div class="col-sm-4">
                <input type="number" name="position" class="form-control"
                       value="<?= $isEdit ? (int)$item->position : 0 ?>">
              </div>
            </div>

            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Текст акции</label>
              <div class="col-sm-10">
                <textarea name="content" id="editor1" rows="6" class="form-control"><?= $isEdit ? h($item->content) : '' ?></textarea>
              </div>
            </div>

            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Баннер №1 (вертикальный)</label>
              <div class="col-sm-10">
                <?php if($isEdit && $item->img): ?>
                  <img src="/images/banners/baseimg/<?=$item->img?>" style="max-height:120px" class="mb-2"><br>
                <?php endif; ?>
                <input type="file" name="img" accept="image/*" class="form-control-file">
                <small class="text-muted">Рекомендуемый размер: как у твоего вертикального (оставляем 1:1).</small>
              </div>
            </div>

            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Баннер №2 (горизонтальный)</label>
              <div class="col-sm-10">
                <?php if($isEdit && $item->img2): ?>
                  <img src="/images/banners/baseimg/<?=$item->img2?>" style="max-height:120px" class="mb-2"><br>
                <?php endif; ?>
                <input type="file" name="img2" accept="image/*" class="form-control-file">
                <small class="text-muted">Рекомендуемый размер: как у твоего узкого баннера (слайдер/полоса).</small>
              </div>
            </div>

          </div>
        </div>

        <div class="box-footer">
          <button type="submit" class="btn btn-success"><?= $isEdit ? 'Сохранить' : 'Добавить' ?></button>
        </div>

      </form>
      <?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
    </div>
  </div>
</section>
