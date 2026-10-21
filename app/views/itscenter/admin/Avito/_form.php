<?php
/** @var \RedBeanPHP\OODBBean $ad */
/** @var bool $isEdit */

// подстрахуемся от undefined, но без \R::dispense в шаблоне
if (!isset($ad) || !is_object($ad)) {
    $ad = new stdClass();
}

// безопасные значения по умолчанию
$status         = isset($ad->status) ? $ad->status : 'draft';
$category       = isset($ad->category) ? $ad->category : 'Запчасти и аксессуары';
$goods_type     = isset($ad->goods_type) ? $ad->goods_type : 'Шины, диски и колёса';
$ad_type        = !empty($ad->ad_type) ? $ad->ad_type : 'Товар от производителя';
$product_type   = isset($ad->product_type) ? $ad->product_type : 'Шины для грузовиков и спецтехники';
$item_condition = isset($ad->item_condition) ? $ad->item_condition : 'Новое';
$contact_method = isset($ad->contact_method) ? $ad->contact_method : 'По телефону и в сообщениях';
$internet_calls = isset($ad->internet_calls) ? $ad->internet_calls : 'Нет';
$review_status  = isset($ad->review_status) && $ad->review_status ? $ad->review_status : 'confirmed';
$linkedProductId = !empty($ad->article)
    ? (int)\R::getCell('SELECT id FROM product WHERE article = ? LIMIT 1', [(string)$ad->article])
    : 0;
$avitoAnalysis  = !empty($isEdit) && !empty($ad->id)
    ? \app\models\admin\Avito::analyzeRow($ad->export())
    : null;

// подготовка картинок из images_json -> textarea
$imgsVal = '';
if (!empty($ad->images_json)) {
    $decoded = json_decode($ad->images_json, true);
    if (is_array($decoded)) {
        $list = [];
        foreach ($decoded as $item) {
            if (is_string($item)) {
                $list[] = $item;
            } elseif (is_array($item) && !empty($item['url'])) {
                $list[] = $item['url'];
            }
        }
        $imgsVal = implode("\n", $list);
    }
}

// своя функция экранирования с уникальным именем, чтобы не конфликтовать
if (!function_exists('avito_h')) {
    function avito_h($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}
?>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">AVITO объявления</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>/avito">Мои объявления AVITO</a></li>
          <?php if (!empty($isEdit) && !empty($ad->id)): ?>
            <li class="breadcrumb-item active">Редактирование объявления #<?= (int)$ad->id; ?></li>
          <?php else: ?>
            <li class="breadcrumb-item active">Добавить объявление</li>
          <?php endif; ?>
        </ol>
      </div>
    </div>
  </div>
</div>
<!-- /.content-header -->

<section class="content">
  <div class="row">
    <div class="col-12">

      <!-- menu_btn как у товара -->
      <div class="menu_btn mb-3">
        <a href="<?=ADMIN;?>/avito" class="btn btn-primary">
          <i class="fal fa-reply-all"></i> К списку объявлений
        </a>

        <?php if (!empty($isEdit) && !empty($ad->id)): ?>
          <a target="_blank" href="<?=ADMIN;?>/avito/export?id=<?= (int)$ad->id ?>" class="btn btn-success">
            <i class="fad fa-file-export"></i> Выгрузить XML этого объявления
          </a>
        <?php else: ?>
          <a target="_blank" href="<?=ADMIN;?>/avito/export" class="btn btn-success">
            <i class="fad fa-file-export"></i> Выгрузить XML всех объявлений
          </a>
        <?php endif; ?>

        <?php if (!empty($isEdit) && !empty($ad->id) && $linkedProductId): ?>
          <a href="<?=ADMIN;?>/avito/apply-template?id=<?= (int)$ad->id ?>"
             class="btn btn-warning"
             onclick="return confirm('Заменить текущее описание оформленным шаблоном из товара? Остальные поля объявления не изменятся.');">
            <i class="fas fa-magic"></i> Применить оформленный шаблон
          </a>
        <?php endif; ?>

        <a id="avito-open-product"
           class="btn btn-outline-primary<?=$linkedProductId ? '' : ' d-none'?>"
           href="<?=$linkedProductId ? ADMIN . '/product/edit?id=' . $linkedProductId : '#'?>"
           target="_blank" rel="noopener">
          <i class="fas fa-external-link-alt"></i> Открыть товар
        </a>
      </div>

      <form method="post" id="avito-form">
        <!-- Карта с табами как у товара -->
        <div class="card">
          <div class="card-header d-flex p-0">
            <h3 class="card-title p-3">
              <?php if (!empty($isEdit) && !empty($ad->id)): ?>
                Редактировать объявление AVITO
              <?php else: ?>
                Добавить объявление AVITO
              <?php endif; ?>
            </h3>
            <ul class="nav nav-pills ml-auto p-2">
              <li class="nav-item"><a class="nav-link active" href="#tab-main" data-toggle="tab">Основное</a></li>
              <li class="nav-item"><a class="nav-link" href="#tab-contacts" data-toggle="tab">Контакты</a></li>
              <li class="nav-item"><a class="nav-link" href="#tab-tires" data-toggle="tab">Характеристики товара</a></li>
              <li class="nav-item"><a class="nav-link" href="#tab-delivery" data-toggle="tab">Доставка/габариты</a></li>
              <li class="nav-item"><a class="nav-link" href="#tab-promo" data-toggle="tab">Промо</a></li>
              <li class="nav-item"><a class="nav-link" href="#tab-media" data-toggle="tab">Медиа</a></li>
            </ul>
          </div>

          <div class="card-body">
            <p>
              <span class="text-danger">*</span> — обязательные поля для корректной автозагрузки на Avito.
            </p>

            <?php if ($avitoAnalysis): ?>
              <?php
                $analysisClass = $avitoAnalysis['status'] === 'ready' ? 'success' : ($avitoAnalysis['status'] === 'warning' ? 'warning' : 'danger');
                $missingRequiredText = implode(', ', array_values($avitoAnalysis['missingRequired']));
                $provisionalRequiredText = implode('; ', array_values($avitoAnalysis['provisionalRequired'] ?? []));
                $missingOptionalText = implode(', ', array_values($avitoAnalysis['missingOptional']));
              ?>
              <div class="alert alert-<?=$analysisClass?>">
                <h5><i class="fas fa-clipboard-check"></i> Проверка Avito: <?=avito_h($avitoAnalysis['label'])?></h5>
                <div class="row">
                  <div class="col-md-4"><strong>Обязательные:</strong> <?=$avitoAnalysis['requiredScore']?>%<br>Не заполнено: <?=count($avitoAnalysis['missingRequired'])?></div>
                  <div class="col-md-4"><strong>Дополнительные:</strong> <?=$avitoAnalysis['optionalScore']?>%<br>Не заполнено: <?=count($avitoAnalysis['missingOptional'])?></div>
                  <div class="col-md-4"><strong>Качество текста:</strong> <?=$avitoAnalysis['textScore']?>%</div>
                </div>
                <?php if ($missingRequiredText): ?><div class="mt-2"><strong>Нужно исправить:</strong> <?=avito_h($missingRequiredText)?></div><?php endif; ?>
                <?php if ($provisionalRequiredText): ?><div class="mt-2"><strong>Заполнено временно:</strong> <?=avito_h($provisionalRequiredText)?>. Проверьте справочник Avito позже и замените «Другое» точным значением.</div><?php endif; ?>
                <?php if ($missingOptionalText): ?><div class="mt-1"><strong>Можно дополнить:</strong> <?=avito_h($missingOptionalText)?></div><?php endif; ?>
                <?php if ($avitoAnalysis['warnings']): ?>
                  <div class="mt-1"><strong>Предупреждения:</strong> <?=avito_h(implode('; ', $avitoAnalysis['warnings']))?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="tab-content">

              <!-- ================== ТАБ: ОСНОВНОЕ ================== -->
              <div class="tab-pane active" id="tab-main">
                <div class="box-body">

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Статус объявления</label>
                    <div class="col-sm-9">
                      <?php
                        $statusesMap = [
                            'draft'    => 'Черновик',
                            'active'   => 'Активно',
                            'archived' => 'Архив',
                        ];
                      ?>
                      <select class="form-control" name="status">
                        <?php foreach ($statusesMap as $val => $label): ?>
                          <option value="<?=$val?>" <?php if ($status === $val) echo 'selected'; ?>>
                            <?=$label?>
                          </option>
                        <?php endforeach; ?>
                      </select>

                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Ручная проверка Avito</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="review_status">
                        <option value="needs_review" <?=$review_status === 'needs_review' ? 'selected' : ''?>>Требует ручной проверки</option>
                        <option value="confirmed" <?=$review_status === 'confirmed' ? 'selected' : ''?>>Проверено и подтверждено</option>
                      </select>
                      <small class="form-text text-muted">Новое автоматически созданное объявление остаётся в архиве, пока вы его не проверите и не подтвердите.</small>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">AvitoId (техническое)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="avito_id" value="<?=avito_h($ad->avito_id ?? '')?>">
                      <small class="form-text text-muted">Заполняется Авито после первой выгрузки / синхронизации.</small>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Внешний ID объявления (Id) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <input required class="form-control" name="ad_external_id" value="<?=avito_h($ad->ad_external_id ?? '')?>">
                      <small class="form-text text-muted">Уникальный идентификатор объявления в вашей базе (тег &lt;Id&gt;).</small>
                    </div>
                  </div>

                  <!-- Привязка к товару сайта -->
                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="avito-article">
                      Товар на сайте / Артикул
                    </label>
                    <div class="col-sm-9">
                      <select name="article"
                              class="form-control"
                              id="avito-article"
                              style="width: 100%;">
                          <?php if (!empty($ad->article)): ?>
                              <option value="<?=avito_h($ad->article)?>" selected>
                                  <?=avito_h($ad->article)?> (артикул уже привязан)
                              </option>
                          <?php else: ?>
                              <option value="">-- начните вводить название или артикул товара --</option>
                          <?php endif; ?>
                      </select>

                      <small class="form-text text-muted">
                        Введите часть <strong>названия</strong> или <strong>артикула</strong> товара.
                        В БД в <code>avito_ad.article</code> будет сохранён именно артикул.
                      </small>
                    </div>
                  </div>
                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Категория (Category) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <input class="form-control" name="category" value="<?=avito_h($category)?>">
                      <small class="form-text text-muted">Например: «Запчасти и аксессуары».</small>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Вид товара (GoodsType) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <select class="form-control" name="goods_type" id="avito-goods-type">
                        <?php foreach (['Шины, диски и колёса', 'Запчасти'] as $v): ?>
                          <option value="<?=$v?>" <?=$goods_type === $v ? 'selected' : ''?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Вид объявления (AdType)</label>
                    <div class="col-sm-9">
                      <?php $adTypes = ['Товар от производителя']; ?>
                      <select class="form-control" name="ad_type">
                        <?php foreach ($adTypes as $v): ?>
                          <option value="<?=$v?>" <?php if ($ad_type === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Тип товара в Авито (ProductType) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <select class="form-control" name="product_type" id="avito-product-type">
                        <?php foreach (['Шины для грузовиков и спецтехники', 'Мотошины', 'Диски', 'Для грузовиков и спецтехники'] as $v): ?>
                          <option value="<?=$v?>" <?=$product_type === $v ? 'selected' : ''?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Название объявления (Title) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <input maxlength="50" required class="form-control" name="title" value="<?=avito_h($ad->title ?? '')?>">
                      <small class="form-text text-muted">До 50 символов, как будет видно на Авито.</small>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="editor1">
                      Описание объявления (Description) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <textarea name="description" id="editor1" cols="80" rows="10">
                        <?=avito_h($ad->description ?? '')?>
                      </textarea>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Цена, ₽ (Price) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <input type="number" class="form-control" name="price_rub" value="<?=avito_h($ad->price_rub ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Тип размещения (ListingFee)</label>
                    <div class="col-sm-9">
                      <?php
                        $feesMap = [
                            'Package'       => 'Пакет',
                            'PackageSingle' => 'Пакет + разовое размещение',
                            'Single'        => 'Разовое размещение',
                        ];
                        $curFee = !empty($ad->listing_fee) ? $ad->listing_fee : 'Package';
                      ?>
                      <select class="form-control" name="listing_fee">
                        <?php foreach ($feesMap as $val => $label): ?>
                          <option value="<?=$val?>" <?php if ($curFee === $val) echo 'selected'; ?>>
                            <?=$label?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Платные услуги (AdStatus)</label>
                    <div class="col-sm-9">
                      <?php
                      $adStatuses  = ['Free','Highlight','XL','x2_1','x2_7','x5_1','x5_7','x10_1','x10_7','x15_1','x15_7','x20_1','x20_7'];
                      $curAdStatus = !empty($ad->ad_status) ? $ad->ad_status : 'Free';
                      ?>
                      <select class="form-control" name="ad_status">
                        <?php foreach ($adStatuses as $v): ?>
                          <option value="<?=$v?>" <?php if ($curAdStatus === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Дата начала публикации (DateBegin)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="date_begin" placeholder="YYYY-MM-DD HH:MM:SS"
                             value="<?=avito_h($ad->date_begin ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Дата окончания (DateEnd)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="date_end" placeholder="YYYY-MM-DD HH:MM:SS"
                             value="<?=avito_h($ad->date_end ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Фотографии товара (image) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <textarea class="form-control" name="images_json" rows="4" placeholder="https://..."><?=$imgsVal?></textarea>
                      <small class="form-text text-muted">По одному URL в строке. Для Авито фото фактически обязательно.</small>
                    </div>
                  </div>

                </div>
              </div>

              <!-- ================== ТАБ: КОНТАКТЫ ================== -->
              <div class="tab-pane" id="tab-contacts">
                <div class="box-body">

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Имя менеджера (ManagerName)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="manager_name" value="<?=avito_h($ad->manager_name ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Контактный телефон (ContactPhone) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <input class="form-control" name="contact_phone" value="<?=avito_h($ad->contact_phone ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Способ связи (ContactMethod)</label>
                    <div class="col-sm-9">
                      <?php $cmValues = ['По телефону и в сообщениях','По телефону','В сообщениях']; ?>
                      <select class="form-control" name="contact_method">
                        <?php foreach ($cmValues as $v): ?>
                          <option value="<?=$v?>" <?php if ($contact_method === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Адрес показа объявления (Address) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <input class="form-control" name="address" value="<?=avito_h($ad->address ?? '')?>">
                      <small class="form-text text-muted">Полный адрес — обязателен для Авито.</small>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Широта (Latitude)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="latitude" value="<?=avito_h($ad->latitude ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Долгота (Longitude)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="longitude" value="<?=avito_h($ad->longitude ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">ID адреса продавца (SellerAddressId)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="seller_address_id" value="<?=avito_h($ad->seller_address_id ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Интернет-звонки (InternetCalls)</label>
                    <div class="col-sm-9">
                      <?php $ic = ['Да','Нет']; ?>
                      <select class="form-control" name="internet_calls">
                        <?php foreach ($ic as $v): ?>
                          <option value="<?=$v?>" <?php if ($internet_calls === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Устройства для звонков (CallsDevices JSON)</label>
                    <div class="col-sm-9">
                      <textarea class="form-control" name="calls_devices_json" rows="3"><?=avito_h($ad->calls_devices_json ?? '')?></textarea>
                    </div>
                  </div>

                </div>
              </div>

              <!-- ============ ТАБ: ХАРАКТЕРИСТИКИ ТОВАРА ============ -->
              <div class="tab-pane" id="tab-tires">
                <div class="box-body">

                  <div class="alert alert-info py-2">
                    Показываются только поля выбранного типа товара. Обязательные параметры отмечены звёздочкой.
                  </div>

                  <div class="avito-profile avito-profile-tires">

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Бренд шины (Brand) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <input class="form-control" name="brand" value="<?=avito_h($ad->brand ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Модель шины (Model)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="model" value="<?=avito_h($ad->model ?? '')?>">
                      <?php $xmlModel = \app\models\admin\Avito::avitoModelValue($product_type, (string)($ad->model ?? '')); ?>
                      <?php if (!empty($ad->model) && $xmlModel === ''): ?>
                        <small class="form-text text-warning">Этой модели нет в справочнике Avito: она остаётся в названии, но тег Model не отправляется.</small>
                      <?php elseif ($xmlModel !== ''): ?>
                        <small class="form-text text-success">В XML Avito: <?=avito_h($xmlModel)?></small>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Ширина профиля (Section) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <input class="form-control" name="tire_section_width" value="<?=avito_h($ad->tire_section_width ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Высота профиля (Aspect) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <input class="form-control" name="tire_aspect_ratio" value="<?=avito_h($ad->tire_aspect_ratio ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Диаметр диска (Rim) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <input class="form-control" name="rim_diameter" value="<?=avito_h($ad->rim_diameter ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Тип шин (TireType)</label>
                    <div class="col-sm-9">
                      <?php $tt = ['Всесезонные','Зимние нешипованные','Зимние шипованные','Летние']; ?>
                      <select class="form-control" name="tire_type">
                        <option value=""></option>
                        <?php foreach ($tt as $v): ?>
                          <option value="<?=$v?>" <?php if (($ad->tire_type ?? '') === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Количество в комплекте (Quantity)</label>
                    <div class="col-sm-9">
                      <?php $qv = ['за 1 шт.','за 2 шт.','за 3 шт.','за 4 шт.','за 5 шт.','за 6 шт.','за 7 шт.','за 8 шт.']; ?>
                      <select class="form-control" name="quantity">
                        <option value=""></option>
                        <?php foreach ($qv as $v): ?>
                          <option value="<?=$v?>" <?php if (($ad->quantity ?? '') === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Индекс скорости (SpeedIndex)</label>
                    <div class="col-sm-9">
                      <?php $si = ['A5','A6','A8','C','D','E','F','G','J','K','L','M','N','P','Q','R','S','T']; ?>
                      <select class="form-control" name="speed_index">
                        <option value=""></option>
                        <?php foreach ($si as $v): ?>
                          <option value="<?=$v?>" <?php if (($ad->speed_index ?? '') === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                      <small class="form-text text-muted">Например, в маркировке <code>178A8</code>: индекс нагрузки — <strong>178</strong>, индекс скорости — <strong>A8</strong>.</small>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Слойность (PlyRating)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="ply_rating" value="<?=avito_h($ad->ply_rating ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Конструкция (Construction)</label>
                    <div class="col-sm-9">
                      <?php $cn = ['Диагональный','Радиальный']; ?>
                      <select class="form-control" name="construction">
                        <option value=""></option>
                        <?php foreach ($cn as $v): ?>
                          <option value="<?=$v?>" <?php if (($ad->construction ?? '') === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Тип камеры (TubeType)</label>
                    <div class="col-sm-9">
                      <?php $tp = ['Камерная','Бескамерная']; ?>
                      <select class="form-control" name="tube_type">
                        <option value=""></option>
                        <?php foreach ($tp as $v): ?>
                          <option value="<?=$v?>" <?php if (($ad->tube_type ?? '') === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Ось установки (WheelAxle)</label>
                    <div class="col-sm-9">
                      <?php $wa = ['Рулевая','Ведущая','Прицепная','Рулевая/Прицепная','Универсальная','Передняя','Задняя','Любая']; ?>
                      <select class="form-control" name="wheel_axle">
                        <option value=""></option>
                        <?php foreach ($wa as $v): ?>
                          <option value="<?=$v?>" <?php if (($ad->wheel_axle ?? '') === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Индекс нагрузки (LoadIndex)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="load_index" value="<?=avito_h($ad->load_index ?? '')?>">
                      <small class="form-text text-muted">Числовая часть комбинированной маркировки: для <code>178A8</code> укажите <strong>178</strong>.</small>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Остаток протектора, мм (ResidualTreadSV)</label>
                    <div class="col-sm-9">
                      <input type="number" class="form-control" name="residual_tread_sv" value="<?=avito_h($ad->residual_tread_sv ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Тип конструкции (Design)</label>
                    <div class="col-sm-9">
                      <?php $des = ['Пневматическая','Цельнолитая']; ?>
                      <select class="form-control" name="design">
                        <option value=""></option>
                        <?php foreach ($des as $v): ?>
                          <option value="<?=$v?>" <?php if (($ad->design ?? '') === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Тип техники (VehicleType)</label>
                    <div class="col-sm-9">
                      <?php $vt = ['Грузовая','Для спецтехники']; ?>
                      <select class="form-control" name="vehicle_type">
                        <option value=""></option>
                        <?php foreach ($vt as $v): ?>
                          <option value="<?=$v?>" <?php if (($ad->vehicle_type ?? '') === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  </div>

                  <div class="avito-profile avito-profile-moto">
                    <div class="alert alert-warning py-2">
                      Для шин ATV выберите тип «Мотошины». Для размера <strong>13×6-5</strong>: ширина — <strong>6</strong>, диаметр диска — <strong>5</strong>, а в поле высоты профиля вручную выберите <strong>Другое</strong>. Полный размер сохраняйте в названии и описании.
                    </div>
                    <div class="form-group row">
                      <label class="col-sm-3 col-form-label">Бренд (Brand)</label>
                      <div class="col-sm-9"><input class="form-control" name="brand" value="<?=avito_h($ad->brand ?? '')?>"></div>
                    </div>
                    <div class="form-group row">
                      <label class="col-sm-3 col-form-label">Модель (Model)</label>
                      <div class="col-sm-9"><input class="form-control" name="model" value="<?=avito_h($ad->model ?? '')?>"></div>
                    </div>
                    <?php
                      $motoRims = ['5','6','7','8','9','10','11','12','13','14','15','16','16.5','17','18','19','20','21','22','23','24'];
                      $motoWidths = ['2.5','2.75','3','3.5','3.25','3.75','4','4.1','4.5','4.6','5','6','6.5','7','7.1','8','8.5','9','10','11','12','16','18','19','20','21','22','25','26','27','28','29','30','60','70','80','90','100','110','120','125','130','140','145','150','160','170','180','190','200','205','210','215','220','225','230','240','250','260','270','280','290','300','310','320','330','340','350','360','370','380','390','MT90','MU85'];
                      $motoAspects = ['7','8','9','9.5','10','11.0','12','25','26','27','28','29','29.5','30','35','40','45','50','55','60','65','70','75','80','85','90','95','100','105','110','Другое'];
                      $motoSelects = [
                        'rim_diameter' => ['Диаметр диска (RimDiameter)', $motoRims],
                        'tire_section_width' => ['Ширина профиля (TireSectionWidth)', $motoWidths],
                        'tire_aspect_ratio' => ['Высота профиля (TireAspectRatio)', $motoAspects],
                        'wheel_axle' => ['Ось установки (WheelAxle)', ['Передняя','Задняя','Любая']],
                      ];
                    ?>
                    <?php foreach ($motoSelects as $field => [$label, $values]): ?>
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label"><?=$label?> <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                          <select class="form-control" name="<?=$field?>">
                            <option value=""></option>
                            <?php foreach ($values as $value): ?>
                              <option value="<?=avito_h($value)?>" <?php if ((string)($ad->$field ?? '') === $value) echo 'selected'; ?>><?=avito_h($value)?></option>
                            <?php endforeach; ?>
                          </select>
                          <?php if ($field === 'tire_aspect_ratio'): ?>
                            <small class="form-text text-muted">Список взят из шаблона Avito «Мотошины» от 15.10.2026 и не обновляется с сайта Avito автоматически. Если нужного значения нет, выберите «Другое»: объявление можно выгрузить, но оно останется жёлтым до замены на точное значение.</small>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Состояние товара (Condition) <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                      <?php $cond = ['Новое','Б/у']; ?>
                      <select class="form-control" name="item_condition">
                        <?php foreach ($cond as $v): ?>
                          <option value="<?=$v?>" <?php if ($item_condition === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Целевая аудитория (TargetAudience)</label>
                    <div class="col-sm-9">
                      <?php $ta = ['Частные лица','Бизнес','Частные лица и бизнес']; ?>
                      <select class="form-control" name="target_audience">
                        <option value=""></option>
                        <?php foreach ($ta as $v): ?>
                          <option value="<?=$v?>" <?php if (($ad->target_audience ?? '') === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="avito-profile avito-profile-disks">
                  <h5>Характеристики диска</h5>
                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Диаметр диска (RimDiameter) <span class="text-danger">*</span></label>
                    <div class="col-sm-9"><input class="form-control" name="rim_diameter" value="<?=avito_h($ad->rim_diameter ?? '')?>"></div>
                  </div>
                  <?php
                    $rimFields = [
                      'rim_width' => ['Ширина обода (RimWidth)', 'Например: 9.5'],
                      'rim_offset' => ['Вылет ET (RimOffset)', 'Например: 0'],
                      'rim_bolts' => ['Количество отверстий (RimBolts)', 'Например: 8'],
                      'rim_bolts_diameter' => ['Диаметр отверстий PCD (RimBoltsDiameter)', 'Например: 203.2'],
                    ];
                  ?>
                  <?php foreach ($rimFields as $field => $meta): ?>
                    <div class="form-group row">
                      <label class="col-sm-3 col-form-label"><?=$meta[0]?> <span class="text-danger">*</span></label>
                      <div class="col-sm-9">
                        <input class="form-control" name="<?=$field?>" placeholder="<?=$meta[1]?>" value="<?=avito_h($ad->$field ?? '')?>">
                      </div>
                    </div>
                  <?php endforeach; ?>
                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Центральное отверстие DIA (RimDIA) <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                      <?php $currentDia = str_replace(',', '.', trim((string)($ad->rim_dia ?? ''))); ?>
                      <select class="form-control" name="rim_dia">
                        <option value=""></option>
                        <?php if ($currentDia !== '' && !in_array($currentDia, \app\models\admin\Avito::rimDiaValues(), true)): ?>
                          <option value="<?=$currentDia?>" selected disabled><?=$currentDia?> — нет в справочнике Avito</option>
                        <?php endif; ?>
                        <?php foreach (\app\models\admin\Avito::rimDiaValues() as $v): ?>
                          <option value="<?=$v?>" <?=$currentDia === $v ? 'selected' : ''?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                      <small class="form-text text-muted">Значения взяты из официального шаблона Avito для дисков.</small>
                    </div>
                  </div>
                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Тип диска (RimType) <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                      <?php $rimTypes = ['Штампованные','Литые','Кованые','Спицованные','Сборные','Карбоновые']; ?>
                      <select class="form-control" name="rim_type">
                        <option value=""></option>
                        <?php foreach ($rimTypes as $v): ?>
                          <option value="<?=$v?>" <?php if (($ad->rim_type ?? '') === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <h5 class="mt-3">Дополнительные характеристики диска</h5>
                  <div class="form-group row"><label class="col-sm-3 col-form-label">Доступность</label><div class="col-sm-9"><select class="form-control" name="availability"><option value=""></option><?php foreach (['В наличии','Под заказ'] as $v): ?><option value="<?=$v?>" <?=(string)($ad->availability ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div></div>
                  <div class="form-group row"><label class="col-sm-3 col-form-label">Продавать по</label><div class="col-sm-9"><select class="form-control" name="minimum_order_quantity"><option value=""></option><?php foreach (['по 2 шт.','по 4 шт.'] as $v): ?><option value="<?=$v?>" <?=(string)($ad->minimum_order_quantity ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div></div>
                  <div class="form-group row"><label class="col-sm-3 col-form-label">Объединять с другими объявлениями</label><div class="col-sm-9"><select class="form-control" name="multi_item"><option value=""></option><?php foreach (['Да','Нет'] as $v): ?><option value="<?=$v?>" <?=(string)($ad->multi_item ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div></div>
                  <?php
                    $diskSelects = [
                      'rims_repair' => ['Был ли ремонт дисков', ['Да','Нет','Не знаю']],
                      'rims_straightened' => ['Сколько дисков выпрямляли', ['Ни одного','1','2','Более 2']],
                      'rims_welded' => ['Сколько дисков варили', ['Ни одного','1','2','Более 2']],
                      'rims_with_cracks' => ['Сколько дисков с трещинами', ['Ни одного','1','2','Более 2']],
                      'rims_with_geometry_change' => ['Сколько дисков с изменением геометрии', ['Ни одного','1','2','Более 2']],
                      'painting_type' => ['Тип покраски', ['Заводской','Была покраска']],
                      'center_caps' => ['Центральные колпачки', ['Да, все','Не все','Нет']],
                      'pressure_sensors' => ['Датчики давления', ['Есть','Нет','Не знаю']],
                    ];
                  ?>
                  <?php foreach ($diskSelects as $field => $meta): ?>
                    <div class="form-group row"><label class="col-sm-3 col-form-label"><?=$meta[0]?></label><div class="col-sm-9"><select class="form-control" name="<?=$field?>"><option value=""></option><?php foreach ($meta[1] as $v): ?><option value="<?=$v?>" <?=(string)($ad->$field ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div></div>
                  <?php endforeach; ?>

                  </div>

                  <div class="avito-profile avito-profile-parts">
                    <h5>Категория запчасти</h5>
                    <div class="form-group row">
                      <label class="col-sm-3 col-form-label">Вид запчасти (SparePartType) <span class="text-danger">*</span></label>
                      <div class="col-sm-9">
                        <select class="form-control" name="spare_part_type" id="avito-spare-part-type">
                          <option value=""></option>
                          <?php foreach (['Двигатели и комплектующие','Гидравлические и пневмосистемы','Кабина'] as $v): ?>
                            <option value="<?=$v?>" <?=(string)($ad->spare_part_type ?? '') === $v ? 'selected' : ''?>><?=$v?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="col-sm-3 col-form-label">Тип запчасти (TechnicSparePartType) <span class="text-danger">*</span></label>
                      <div class="col-sm-9">
                        <select class="form-control" name="technic_spare_part_type" id="avito-technic-spare-part-type">
                          <option value=""></option>
                          <?php foreach (['Топливная система','Система смазки','Система охлаждения ДВС','Система выпуска','Фильтры и фильтроэлементы','Детали салона'] as $v): ?>
                            <option value="<?=$v?>" <?=(string)($ad->technic_spare_part_type ?? '') === $v ? 'selected' : ''?>><?=$v?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>

                    <div class="avito-part-subprofile" data-part-type="Топливная система">
                      <?php $fuelTypes = ['Форсунки и распылители','Крепёж и уплотнители','Топливные баки и комплектующие','Трубки и шланги','Клапаны и регуляторы','Сепараторы и фильтры','Топливные насосы и комплектующие']; ?>
                      <div class="form-group row"><label class="col-sm-3 col-form-label">Тип детали топливной системы</label><div class="col-sm-9"><select class="form-control" name="fuel_system_part_type"><option value=""></option><?php foreach ($fuelTypes as $v): ?><option value="<?=$v?>" <?=(string)($ad->fuel_system_part_type ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div></div>
                    </div>
                    <div class="avito-part-subprofile" data-part-type="Система смазки">
                      <?php $lubricationTypes = ['Масляные фильтры','Насосы масляные и комплектующие','Прокладки и уплотнители','Форсунки масляные','Щупы','Корпусы масляного фильтра','Теплообменники','Маслоприёмники','Поддоны картера','Датчики давления, уровня и температуры','Клапаны редукционные и перепускные','Трубки, шланги']; ?>
                      <div class="form-group row"><label class="col-sm-3 col-form-label">Тип детали системы смазки</label><div class="col-sm-9"><select class="form-control" name="part_type"><option value=""></option><?php foreach ($lubricationTypes as $v): ?><option value="<?=$v?>" <?=(string)($ad->part_type ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div></div>
                    </div>
                    <div class="avito-part-subprofile" data-part-type="Система охлаждения ДВС">
                      <?php $coolingTypes = ['Насос водяной','Вентилятор радиатора','Патрубки, фланцы, шланги','Термостат','Радиатор охлаждения','Интеркулер','Бачок расширительный','Крышка бачка расширительного','Прокладки, уплотнители системы охлаждения','Крышка радиатора','Другое']; ?>
                      <div class="form-group row"><label class="col-sm-3 col-form-label">Тип детали системы охлаждения</label><div class="col-sm-9"><select class="form-control" name="cooling_system_spare_part_type"><option value=""></option><?php foreach ($coolingTypes as $v): ?><option value="<?=$v?>" <?=(string)($ad->cooling_system_spare_part_type ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div></div>
                    </div>
                    <div class="avito-part-subprofile" data-part-type="Система выпуска">
                      <?php $exhaustTypes = ['Глушители и резонаторы','Приёмные и выхлопные трубы','Выпускные коллекторы','Гофры и металлокомпенсаторы','Катализаторы','Сажевые фильтры','Крепёж и уплотнения','Система EGR','Система AdBlue','Комплектующие']; ?>
                      <div class="form-group row"><label class="col-sm-3 col-form-label">Тип детали системы выпуска</label><div class="col-sm-9"><select class="form-control" name="part_type"><option value=""></option><?php foreach ($exhaustTypes as $v): ?><option value="<?=$v?>" <?=(string)($ad->part_type ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div></div>
                    </div>
                    <div class="avito-part-subprofile" data-part-type="Детали салона">
                      <?php $interiorTypes = ['Накладка','Обшивка','Панель','Ручка','Ящик','Крышка','Переключатель','Воздуховод','Дефлектор','Кожух','Кнопка','Плафон','Полка','Консоль','Солнцезащитный козырёк']; ?>
                      <div class="form-group row"><label class="col-sm-3 col-form-label">Тип детали салона</label><div class="col-sm-9"><select class="form-control" name="interior_part_type"><option value=""></option><?php foreach ($interiorTypes as $v): ?><option value="<?=$v?>" <?=(string)($ad->interior_part_type ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div></div>
                    </div>

                    <h5 class="mt-3">Общие характеристики запчасти</h5>
                    <?php
                      $partTextFields = [
                        'manufacturer' => 'Производитель', 'oem_number' => 'Номер детали OEM',
                        'original_vendor' => 'Производитель оригинала', 'original_number' => 'Номер оригинала',
                        'vehicle_make' => 'Марка техники', 'vehicle_model' => 'Модель техники',
                        'vehicle_generation' => 'Поколение', 'vehicle_modification' => 'Модификация',
                      ];
                    ?>
                    <?php foreach ($partTextFields as $field => $label): ?>
                      <div class="form-group row"><label class="col-sm-3 col-form-label"><?=$label?></label><div class="col-sm-9"><input class="form-control" name="<?=$field?>" value="<?=avito_h($ad->$field ?? '')?>"></div></div>
                    <?php endforeach; ?>
                    <div class="form-group row"><label class="col-sm-3 col-form-label">Доступность</label><div class="col-sm-9"><select class="form-control" name="availability"><option value=""></option><?php foreach (['В наличии','Под заказ'] as $v): ?><option value="<?=$v?>" <?=(string)($ad->availability ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div></div>
                    <div class="form-group row"><label class="col-sm-3 col-form-label">Происхождение</label><div class="col-sm-9"><select class="form-control" name="originality"><option value=""></option><?php foreach (['Оригинал','Аналог','Не знаю'] as $v): ?><option value="<?=$v?>" <?=(string)($ad->originality ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div></div>
                    <div class="form-group row"><label class="col-sm-3 col-form-label">Тип спецтехники</label><div class="col-sm-9"><select class="form-control" name="technic"><option value=""></option><?php foreach (['Автобусы','Автодома','Автокраны','Бульдозеры','Грузовики','Коммунальная техника','Лёгкий коммерческий транспорт','Навесное оборудование','Погрузчики','Прицепы','Сельхозтехника','Строительная техника','Техника для лесозаготовки','Тягачи','Экскаваторы','Другое'] as $v): ?><option value="<?=$v?>" <?=(string)($ad->technic ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div></div>
                  </div>

                </div>
              </div>

              <!-- ============ ТАБ: ДОСТАВКА / ГАБАРИТЫ ============ -->
              <div class="tab-pane" id="tab-delivery">
                <div class="box-body">

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Параметры доставки (DeliveryOptions)</label>
                    <div class="col-sm-9">
                      <textarea class="form-control" name="delivery_json" rows="4"><?=avito_h($ad->delivery_json ?? '')?></textarea>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Вес, кг (Weight)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="weight_kg" value="<?=avito_h($ad->weight_kg ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Длина, см (Length)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="length_cm" value="<?=avito_h($ad->length_cm ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Высота, см (Height)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="height_cm" value="<?=avito_h($ad->height_cm ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Ширина, см (Width)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="width_cm" value="<?=avito_h($ad->width_cm ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Условия возврата (ReturnPolicy)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="return_policy" value="<?=avito_h($ad->return_policy ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Субсидия доставки, ₽ (DeliverySubsidy)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="delivery_subsidy" value="<?=avito_h($ad->delivery_subsidy ?? '')?>">
                    </div>
                  </div>

                  <hr>
                  <h5>Условия продажи</h5>
                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">НДС (NDS)</label>
                    <div class="col-sm-9"><select class="form-control" name="nds"><?php foreach (['22'] as $v): ?><option value="<?=$v?>" <?=((string)($ad->nds ?? '') ?: '22') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div>
                  </div>
                  <div class="form-group row avito-profile avito-profile-parts">
                    <label class="col-sm-3 col-form-label">Отсрочка платежа (PostponedPayment)</label>
                    <div class="col-sm-9"><select class="form-control" name="postponed_payment"><option value=""></option><?php foreach (['Да','Нет'] as $v): ?><option value="<?=$v?>" <?=(string)($ad->postponed_payment ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div>
                  </div>
                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Оптовая продажа (WholesaleType)</label>
                    <div class="col-sm-9"><select class="form-control" name="wholesale_type"><option value=""></option><?php foreach (['Да','Нет'] as $v): ?><option value="<?=$v?>" <?=(string)($ad->wholesale_type ?? '') === $v ? 'selected' : ''?>><?=$v?></option><?php endforeach; ?></select></div>
                  </div>
                  <?php
                    $wholesaleFields = [
                      'wholesale_min_order_type' => 'Тип минимального заказа',
                      'wholesale_min_order_value' => 'Минимальный заказ',
                      'wholesale_packing' => 'Тип упаковки',
                      'wholesale_packing_value' => 'Количество в упаковке',
                      'wholesale_measure_unit' => 'Единица измерения',
                      'wholesale_discount_ladder_type' => 'Тип оптовой скидки',
                      'wholesale_discount_value' => 'Значение оптовой скидки',
                    ];
                  ?>
                  <?php foreach ($wholesaleFields as $field => $label): ?>
                    <div class="form-group row avito-wholesale-field">
                      <label class="col-sm-3 col-form-label"><?=$label?></label>
                      <div class="col-sm-9"><input class="form-control" name="<?=$field?>" value="<?=avito_h($ad->$field ?? '')?>"></div>
                    </div>
                  <?php endforeach; ?>

                </div>
              </div>

              <!-- ================== ТАБ: ПРОМО ================== -->
              <div class="tab-pane" id="tab-promo">
                <div class="box-body">

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Режим промо (Promo)</label>
                    <div class="col-sm-9">
                      <?php $promoValues = ['Manual','Auto_1','Auto_7','Auto_30']; ?>
                      <select class="form-control" name="promo">
                        <option value=""></option>
                        <?php foreach ($promoValues as $v): ?>
                          <option value="<?=$v?>" <?php if (($ad->promo ?? '') === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Авто-промо настройки (PromoAutoOptions)</label>
                    <div class="col-sm-9">
                      <textarea class="form-control" name="promo_auto_json" rows="4"><?=avito_h($ad->promo_auto_json ?? '')?></textarea>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Ручное промо (PromoManualOptions)</label>
                    <div class="col-sm-9">
                      <textarea class="form-control" name="promo_manual_json" rows="4"><?=avito_h($ad->promo_manual_json ?? '')?></textarea>
                    </div>
                  </div>

                </div>
              </div>

              <!-- ================== ТАБ: МЕДИА ================== -->
              <div class="tab-pane" id="tab-media">
                <div class="box-body">

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Видео (VideoURL)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="video_url" value="<?=avito_h($ad->video_url ?? '')?>">
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Файл видео (VideoFileURL)</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="video_file_url" value="<?=avito_h($ad->video_file_url ?? '')?>">
                    </div>
                  </div>

                </div>
              </div>

            </div><!-- /.tab-content -->
          </div><!-- /.card-body -->

        </div><!-- /.card -->

        <div class="box-footer">
          <button type="submit" class="btn btn-primary btn_save">
            <?php if (!empty($isEdit) && !empty($ad->id)): ?>
              Сохранить изменения
            <?php else: ?>
              Добавить объявление
            <?php endif; ?>
          </button>
        </div>

      </form>

      <script>
  (function($) {
    $(function() {
      var partGroups = {
        'Двигатели и комплектующие': ['Топливная система','Система смазки','Система охлаждения ДВС','Система выпуска'],
        'Гидравлические и пневмосистемы': ['Фильтры и фильтроэлементы'],
        'Кабина': ['Детали салона']
      };

      function setPartSubprofile() {
        var selectedGroup = $('#avito-spare-part-type').val() || '';
        var allowed = partGroups[selectedGroup] || [];
        var $type = $('#avito-technic-spare-part-type');
        $type.find('option').each(function() {
          var value = $(this).attr('value') || '';
          $(this).prop('disabled', value !== '' && allowed.indexOf(value) === -1).toggle(value === '' || allowed.indexOf(value) !== -1);
        });
        if (allowed.indexOf($type.val()) === -1) {
          $type.val('');
        }
        var selectedType = $type.val() || '';
        $('.avito-part-subprofile').each(function() {
          var active = $(this).data('part-type') === selectedType;
          $(this).toggle(active).find(':input').prop('disabled', !active);
        });
      }

      function setProductProfile() {
        var productType = $('#avito-product-type').val() || '';
        var profile = productType === 'Диски' ? 'disks' : (productType === 'Для грузовиков и спецтехники' ? 'parts' : (productType === 'Мотошины' ? 'moto' : 'tires'));
        $('.avito-profile').each(function() {
          var active = $(this).hasClass('avito-profile-' + profile);
          $(this).toggle(active).find(':input').prop('disabled', !active);
        });
        $('#avito-goods-type').val(profile === 'parts' ? 'Запчасти' : 'Шины, диски и колёса');
        if (profile === 'parts') setPartSubprofile();
      }

      $('#avito-product-type').on('change', setProductProfile);
      $('#avito-spare-part-type, #avito-technic-spare-part-type').on('change', setPartSubprofile);
      $('[name="wholesale_type"]').on('change', function() {
        $('.avito-wholesale-field').toggle($(this).val() === 'Да');
      }).trigger('change');
      setProductProfile();

      $('#avito-article').select2({
        width: '100%',
        placeholder: 'Начните вводить название или артикул товара',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
          url: '<?=ADMIN;?>/avito/product-search',
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return {
              term: params.term || ''
            };
          },
          processResults: function (data) {
            // ожидаем формат {results:[{id:'ART123', text:'ART123 — Название'}, ...]}
            return data;
          },
          cache: true
        },
        language: {
          inputTooShort: function() { return 'Введите минимум 2 символа...'; },
          noResults: function() { return 'Ничего не найдено'; },
          searching: function() { return 'Поиск...'; }
        }
      }).on('select2:select', function(event) {
        var item = event.params.data || {};
        if (item.product_id) {
          $('#avito-open-product')
            .attr('href', '<?=ADMIN;?>/product/edit?id=' + encodeURIComponent(item.product_id))
            .removeClass('d-none');
        }
      }).on('select2:clear', function() {
        $('#avito-open-product').attr('href', '#').addClass('d-none');
      });
    });
  })(jQuery);
</script>


    </div>
  </div>
</section>
