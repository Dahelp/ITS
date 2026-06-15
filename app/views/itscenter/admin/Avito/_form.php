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
$ad_type        = isset($ad->ad_type) ? $ad->ad_type : 'Товар приобретен на продажу';
$product_type   = isset($ad->product_type) ? $ad->product_type : 'Шины для грузовиков и спецтехники';
$item_condition = isset($ad->item_condition) ? $ad->item_condition : 'Новое';
$contact_method = isset($ad->contact_method) ? $ad->contact_method : 'По телефону и в сообщениях';
$internet_calls = isset($ad->internet_calls) ? $ad->internet_calls : 'Нет';

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
              <li class="nav-item"><a class="nav-link" href="#tab-tires" data-toggle="tab">Характеристики шин</a></li>
              <li class="nav-item"><a class="nav-link" href="#tab-delivery" data-toggle="tab">Доставка/габариты</a></li>
              <li class="nav-item"><a class="nav-link" href="#tab-promo" data-toggle="tab">Промо</a></li>
              <li class="nav-item"><a class="nav-link" href="#tab-media" data-toggle="tab">Медиа</a></li>
            </ul>
          </div>

          <div class="card-body">
            <p>
              <span class="text-danger">*</span> — обязательные поля для корректной автозагрузки на Avito.
            </p>

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
                      <input class="form-control" name="goods_type" value="<?=avito_h($goods_type)?>">
                      <small class="form-text text-muted">Например: «Шины, диски и колёса».</small>
                    </div>
                  </div>

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Тип объявления (AdType)</label>
                    <div class="col-sm-9">
                      <?php $adTypes = ['Товар приобретен на продажу','Товар от производителя']; ?>
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
                      <input class="form-control" name="product_type" value="<?=avito_h($product_type)?>">
                      <small class="form-text text-muted">Например: «Шины для грузовиков и спецтехники».</small>
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

              <!-- ============ ТАБ: ХАРАКТЕРИСТИКИ ШИН ============ -->
              <div class="tab-pane" id="tab-tires">
                <div class="box-body">

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
                      <?php $si = ['C','D','E','F','G','J','K','L','M','N','P','Q','R','S','T']; ?>
                      <select class="form-control" name="speed_index">
                        <option value=""></option>
                        <?php foreach ($si as $v): ?>
                          <option value="<?=$v?>" <?php if (($ad->speed_index ?? '') === $v) echo 'selected'; ?>><?=$v?></option>
                        <?php endforeach; ?>
                      </select>
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
                      <?php $wa = ['Рулевая','Ведущая','Прицепная','Рулевая/Прицепная','Универсальная']; ?>
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

                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">
                      Состояние товара (ItemCondition) <span class="text-danger">*</span>
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
      });
    });
  })(jQuery);
</script>


    </div>
  </div>
</section>
