<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$isEdit = !empty($certificate->id);
$rows = $assignments ?: [['target_type' => 'category_brand', 'product_id' => '', 'category_id' => '', 'brand_id' => '']];
?>
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-7"><h1 class="m-0"><?= $isEdit ? 'Редактирование сертификата' : 'Новый сертификат'; ?></h1></div>
      <div class="col-sm-5">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>/certificate">Сертификаты</a></li>
          <li class="breadcrumb-item active"><?= $isEdit ? 'Редактирование' : 'Добавление'; ?></li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="row">
    <div class="col-12">
      <form method="post" action="<?=ADMIN;?>/certificate/<?= $isEdit ? 'edit' : 'add'; ?>" data-toggle="validator">
        <div class="card card-primary card-outline">
          <div class="card-header d-flex p-0">
            <h3 class="card-title p-3"><i class="fas fa-file-signature mr-2"></i><?= $isEdit ? $e($certificate->number) : 'Добавление документа'; ?></h3>
            <ul class="nav nav-pills ml-auto p-2">
              <li class="nav-item"><a class="nav-link active" href="#certificate-details" data-toggle="tab"><i class="fas fa-id-card mr-1"></i> Реквизиты</a></li>
              <li class="nav-item"><a class="nav-link" href="#certificate-assignments" data-toggle="tab"><i class="fas fa-project-diagram mr-1"></i> Применение</a></li>
            </ul>
          </div>

          <div class="card-body">
            <div class="tab-content">
              <div class="tab-pane active" id="certificate-details">
                <div class="form-group row">
                  <label class="col-sm-3 col-form-label" for="document_type">Тип документа</label>
                  <div class="col-sm-9">
                    <select id="document_type" name="document_type" class="form-control">
                      <option value="declaration" <?= $certificate->document_type === 'declaration' ? 'selected' : ''; ?>>Декларация о соответствии</option>
                      <option value="certificate" <?= $certificate->document_type === 'certificate' ? 'selected' : ''; ?>>Сертификат соответствия</option>
                    </select>
                  </div>
                </div>
                <div class="form-group row">
                  <label class="col-sm-3 col-form-label" for="number">Номер документа *</label>
                  <div class="col-sm-9"><input id="number" name="number" class="form-control" required value="<?= $e($certificate->number); ?>"></div>
                </div>
                <div class="form-group row">
                  <label class="col-sm-3 col-form-label" for="name">Краткое название</label>
                  <div class="col-sm-9"><input id="name" name="name" class="form-control" value="<?= $e($certificate->name); ?>" placeholder="Например: воздушные, топливные и масляные фильтры EKKA"></div>
                </div>
                <div class="form-group row">
                  <label class="col-sm-3 col-form-label">Срок действия</label>
                  <div class="col-sm-3"><input type="date" name="date_start" class="form-control" value="<?= $e($certificate->date_start); ?>" title="Дата начала"></div>
                  <div class="col-sm-3"><input type="date" name="date_end" class="form-control" value="<?= $e($certificate->date_end); ?>" title="Дата окончания"></div>
                  <div class="col-sm-3">
                    <select name="status" class="form-control" title="Статус">
                      <?php foreach (['active'=>'Действует','suspended'=>'Приостановлен','expired'=>'Истёк','archived'=>'Архив'] as $v=>$label): ?>
                        <option value="<?=$v;?>" <?= $certificate->status === $v ? 'selected' : ''; ?>><?=$label;?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="form-group row">
                  <label class="col-sm-3 col-form-label" for="registry_url">Официальный реестр *</label>
                  <div class="col-sm-9">
                    <input type="url" id="registry_url" name="registry_url" class="form-control" required value="<?= $e($certificate->registry_url); ?>" placeholder="https://...">
                    <small class="form-text text-muted"><i class="fas fa-info-circle mr-1"></i>Эта ссылка показывается покупателю. PDF не заменяет запись в официальном реестре.</small>
                  </div>
                </div>
                <div class="form-group row">
                  <label class="col-sm-3 col-form-label" for="file_url">PDF-копия</label>
                  <div class="col-sm-9"><input type="url" id="file_url" name="file_url" class="form-control" value="<?= $e($certificate->file_url); ?>" placeholder="https://..."></div>
                </div>
                <div class="form-group row">
                  <label class="col-sm-3 col-form-label" for="issuer">Орган по сертификации</label>
                  <div class="col-sm-9"><input id="issuer" name="issuer" class="form-control" value="<?= $e($certificate->issuer); ?>"></div>
                </div>
                <div class="form-group row">
                  <label class="col-sm-3 col-form-label" for="applicant">Заявитель</label>
                  <div class="col-sm-9"><input id="applicant" name="applicant" class="form-control" value="<?= $e($certificate->applicant); ?>"></div>
                </div>
                <div class="form-group row">
                  <label class="col-sm-3 col-form-label" for="regulations">Технические регламенты</label>
                  <div class="col-sm-9"><textarea id="regulations" name="regulations" class="form-control" rows="3"><?= $e($certificate->regulations); ?></textarea></div>
                </div>
                <div class="form-group row mb-0">
                  <label class="col-sm-3 col-form-label" for="note">Внутреннее примечание</label>
                  <div class="col-sm-9"><textarea id="note" name="note" class="form-control" rows="3"><?= $e($certificate->note); ?></textarea></div>
                </div>
              </div>

              <div class="tab-pane" id="certificate-assignments">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                  <p class="text-muted mb-2 mr-3"><i class="fas fa-info-circle mr-1"></i>Правило «Категория + производитель» подходит для разных документов внутри одной категории. Прямая привязка товара имеет наивысший приоритет.</p>
                  <button type="button" class="btn btn-primary btn-sm mb-2" id="add-assignment"><i class="fa fa-fw fa-plus"></i> Добавить правило</button>
                </div>
                <div id="assignments">
                  <?php foreach ($rows as $i => $row): ?>
                    <div class="assignment-row border rounded p-3 mb-3" data-index="<?= (int)$i; ?>">
                      <div class="row align-items-end">
                        <div class="form-group col-lg-3 col-md-6">
                          <label>Правило</label>
                          <select class="form-control target-type" name="assignments[<?=$i;?>][target_type]">
                            <?php foreach (['category_brand'=>'Категория + производитель','product'=>'Конкретный товар','category'=>'Категория','brand'=>'Производитель'] as $v=>$label): ?>
                              <option value="<?=$v;?>" <?= ($row['target_type'] ?? '') === $v ? 'selected' : ''; ?>><?=$label;?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="form-group col-lg-3 col-md-6 field-category">
                          <label>Категория</label>
                          <select class="form-control select2" name="assignments[<?=$i;?>][category_id]"><option value="">— Не выбрана —</option><?php foreach($categories as $v): ?><option value="<?=$v['id'];?>" <?= (int)($row['category_id'] ?? 0)===(int)$v['id']?'selected':''; ?>><?=$e($v['name']);?></option><?php endforeach; ?></select>
                        </div>
                        <div class="form-group col-lg-3 col-md-6 field-brand">
                          <label>Производитель</label>
                          <select class="form-control select2" name="assignments[<?=$i;?>][brand_id]"><option value="">— Не выбран —</option><?php foreach($brands as $v): ?><option value="<?=$v['id'];?>" <?= (int)($row['brand_id'] ?? 0)===(int)$v['id']?'selected':''; ?>><?=$e($v['name']);?></option><?php endforeach; ?></select>
                        </div>
                        <div class="form-group col-lg-3 col-md-6 field-product">
                          <label>Товар</label>
                          <select class="form-control select2" name="assignments[<?=$i;?>][product_id]"><option value="">— Не выбран —</option><?php foreach($products as $v): ?><option value="<?=$v['id'];?>" <?= (int)($row['product_id'] ?? 0)===(int)$v['id']?'selected':''; ?>><?=$e($v['article'].' · '.$v['name']);?></option><?php endforeach; ?></select>
                        </div>
                        <div class="col-auto mb-3">
                          <button type="button" class="btn btn-outline-danger remove-assignment" title="Удалить правило" aria-label="Удалить правило"><i class="fas fa-times-circle"></i></button>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$certificate->id; ?>"><input type="hidden" name="created_at" value="<?= $e($certificate->created_at); ?>"><?php endif; ?>
            <button class="btn btn-success" type="submit"><i class="fas fa-save mr-1"></i> Сохранить</button>
            <a class="btn btn-default" href="<?=ADMIN;?>/certificate"><i class="fas fa-times mr-1"></i> Отмена</a>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>

<script>
$(function(){
  function toggle(row){
    var type=row.find('.target-type').val();
    row.find('.field-category').toggle(type==='category'||type==='category_brand');
    row.find('.field-brand').toggle(type==='brand'||type==='category_brand');
    row.find('.field-product').toggle(type==='product');
  }
  $('#assignments .assignment-row').each(function(){toggle($(this));});
  $(document).on('change','.target-type',function(){toggle($(this).closest('.assignment-row'));});
  $(document).on('click','.remove-assignment',function(){$(this).closest('.assignment-row').remove();});
  var next=<?= count($rows); ?>;
  $('#add-assignment').on('click',function(){
    var first=$('#assignments .assignment-row').first().clone();
    first.attr('data-index',next);
    first.find('select').each(function(){this.name=this.name.replace(/assignments\[\d+\]/,'assignments['+next+']');this.selectedIndex=0;});
    first.find('.select2-container').remove();
    first.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id tabindex aria-hidden');
    $('#assignments').append(first);
    first.find('.select2').select2();
    toggle(first);
    next++;
  });
});
</script>
