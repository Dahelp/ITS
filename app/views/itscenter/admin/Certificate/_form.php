<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$isEdit = !empty($certificate->id);
$rows = $assignments ?: [['target_type' => 'category_brand', 'product_id' => '', 'category_id' => '', 'brand_id' => '']];
?>
<div class="content-header"><div class="container-fluid"><div class="row mb-2"><div class="col-sm-7"><h1 class="m-0"><?= $isEdit ? 'Редактирование документа' : 'Новый документ'; ?></h1></div><div class="col-sm-5"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="<?=ADMIN;?>/certificate">Сертификаты</a></li><li class="breadcrumb-item active"><?= $isEdit ? 'Редактирование' : 'Добавление'; ?></li></ol></div></div></div></div>
<section class="content"><form method="post" action="<?=ADMIN;?>/certificate/<?= $isEdit ? 'edit' : 'add'; ?>" data-toggle="validator">
<div class="card"><div class="card-header"><h3 class="card-title">Реквизиты документа</h3></div><div class="card-body">
  <div class="row">
    <div class="form-group col-md-4"><label>Тип документа</label><select name="document_type" class="form-control"><option value="declaration" <?= $certificate->document_type === 'declaration' ? 'selected' : ''; ?>>Декларация о соответствии</option><option value="certificate" <?= $certificate->document_type === 'certificate' ? 'selected' : ''; ?>>Сертификат соответствия</option></select></div>
    <div class="form-group col-md-8"><label>Номер *</label><input name="number" class="form-control" required value="<?= $e($certificate->number); ?>"></div>
    <div class="form-group col-md-12"><label>Краткое название</label><input name="name" class="form-control" value="<?= $e($certificate->name); ?>" placeholder="Например: шины пневматические Armour"></div>
    <div class="form-group col-md-3"><label>Действует с</label><input type="date" name="date_start" class="form-control" value="<?= $e($certificate->date_start); ?>"></div>
    <div class="form-group col-md-3"><label>Действует до</label><input type="date" name="date_end" class="form-control" value="<?= $e($certificate->date_end); ?>"></div>
    <div class="form-group col-md-3"><label>Статус</label><select name="status" class="form-control"><?php foreach (['active'=>'Действует','suspended'=>'Приостановлен','expired'=>'Истёк','archived'=>'Архив'] as $v=>$label): ?><option value="<?=$v;?>" <?= $certificate->status === $v ? 'selected' : ''; ?>><?=$label;?></option><?php endforeach; ?></select></div>
    <div class="form-group col-md-12"><label>Ссылка на запись в официальном реестре *</label><input type="url" name="registry_url" class="form-control" required value="<?= $e($certificate->registry_url); ?>" placeholder="https://..."><small class="form-text text-muted">Именно эта ссылка показывается покупателю. PDF не заменяет запись в реестре.</small></div>
    <div class="form-group col-md-12"><label>Ссылка на PDF (дополнительно)</label><input type="url" name="file_url" class="form-control" value="<?= $e($certificate->file_url); ?>"></div>
    <div class="form-group col-md-6"><label>Орган по сертификации</label><input name="issuer" class="form-control" value="<?= $e($certificate->issuer); ?>"></div>
    <div class="form-group col-md-6"><label>Заявитель</label><input name="applicant" class="form-control" value="<?= $e($certificate->applicant); ?>"></div>
    <div class="form-group col-md-12"><label>Технические регламенты</label><textarea name="regulations" class="form-control" rows="2"><?= $e($certificate->regulations); ?></textarea></div>
    <div class="form-group col-md-12"><label>Внутреннее примечание</label><textarea name="note" class="form-control" rows="2"><?= $e($certificate->note); ?></textarea></div>
  </div>
</div></div>
<div class="card"><div class="card-header d-flex justify-content-between"><h3 class="card-title">Область действия</h3><button type="button" class="btn btn-sm btn-outline-primary ml-auto" id="add-assignment"><i class="fas fa-plus"></i> Добавить правило</button></div><div class="card-body"><p class="text-muted">Для разных производителей внутри категории используйте правило «Категория + производитель». Прямая привязка товара имеет наивысший приоритет.</p><div id="assignments">
<?php foreach ($rows as $i => $row): ?>
<div class="assignment-row border rounded p-2 mb-2" data-index="<?= (int)$i; ?>"><div class="row align-items-end">
  <div class="form-group col-md-3"><label>Правило</label><select class="form-control target-type" name="assignments[<?=$i;?>][target_type]"><?php foreach (['category_brand'=>'Категория + производитель','product'=>'Конкретный товар','category'=>'Категория','brand'=>'Производитель'] as $v=>$label): ?><option value="<?=$v;?>" <?= ($row['target_type'] ?? '') === $v ? 'selected' : ''; ?>><?=$label;?></option><?php endforeach; ?></select></div>
  <div class="form-group col-md-3 field-category"><label>Категория</label><select class="form-control select2" name="assignments[<?=$i;?>][category_id]"><option value="">—</option><?php foreach($categories as $v): ?><option value="<?=$v['id'];?>" <?= (int)($row['category_id'] ?? 0)===(int)$v['id']?'selected':''; ?>><?=$e($v['name']);?></option><?php endforeach; ?></select></div>
  <div class="form-group col-md-3 field-brand"><label>Производитель</label><select class="form-control select2" name="assignments[<?=$i;?>][brand_id]"><option value="">—</option><?php foreach($brands as $v): ?><option value="<?=$v['id'];?>" <?= (int)($row['brand_id'] ?? 0)===(int)$v['id']?'selected':''; ?>><?=$e($v['name']);?></option><?php endforeach; ?></select></div>
  <div class="form-group col-md-3 field-product"><label>Товар</label><select class="form-control select2" name="assignments[<?=$i;?>][product_id]"><option value="">—</option><?php foreach($products as $v): ?><option value="<?=$v['id'];?>" <?= (int)($row['product_id'] ?? 0)===(int)$v['id']?'selected':''; ?>><?=$e($v['article'].' · '.$v['name']);?></option><?php endforeach; ?></select></div>
  <div class="col-md-1 mb-3"><button type="button" class="btn btn-outline-danger remove-assignment"><i class="fas fa-times"></i></button></div>
</div></div>
<?php endforeach; ?>
</div></div></div>
<?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$certificate->id; ?>"><input type="hidden" name="created_at" value="<?= $e($certificate->created_at); ?>"><?php endif; ?>
<button class="btn btn-success mb-4" type="submit">Сохранить</button> <a class="btn btn-default mb-4" href="<?=ADMIN;?>/certificate">Отмена</a>
</form></section>
<script>
$(function(){
  function toggle(row){var t=row.find('.target-type').val();row.find('.field-category').toggle(t==='category'||t==='category_brand');row.find('.field-brand').toggle(t==='brand'||t==='category_brand');row.find('.field-product').toggle(t==='product');}
  $('#assignments .assignment-row').each(function(){toggle($(this));});
  $(document).on('change','.target-type',function(){toggle($(this).closest('.assignment-row'));});
  $(document).on('click','.remove-assignment',function(){$(this).closest('.assignment-row').remove();});
  var next=<?= count($rows); ?>;
  $('#add-assignment').on('click',function(){var first=$('#assignments .assignment-row').first().clone();first.attr('data-index',next);first.find('select').each(function(){this.name=this.name.replace(/assignments\[\d+\]/,'assignments['+next+']');this.selectedIndex=0;});first.find('.select2-container').remove();first.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id tabindex aria-hidden');$('#assignments').append(first);first.find('.select2').select2();toggle(first);next++;});
});
</script>
