<?php
$selection=\app\services\CatalogSelection::state($_GET);
$listingUrl=strtok($_SERVER['REQUEST_URI'],'?');
?>
<link rel="stylesheet" href="/css/catalog-controls.css?v=20261026b">
<form id="catalog-controls" class="catalog-controls" method="get" action="<?=h($listingUrl)?>" data-catalog-controls>
  <?php foreach($_GET as$key=>$value): if(!preg_match('~^(category|filter|filter_[a-z0-9_]+)$~i',(string)$key)||!is_scalar($value))continue; ?>
    <input type="hidden" name="<?=h($key)?>" value="<?=h((string)$value)?>">
  <?php endforeach; ?>
  <div class="catalog-controls__fields">
    <label>Показывать<select name="status" class="form-control"><?php foreach(\app\services\CatalogSelection::STATUSES as$value=>$label): ?><option value="<?=h($value)?>" <?=$selection['status']===$value?'selected':''?>><?=h($label)?></option><?php endforeach; ?></select></label>
    <label>Сортировка<select name="sort" class="form-control" data-catalog-sort><?php foreach(\app\services\CatalogSelection::SORTS as$value=>$label): ?><option value="<?=h($value)?>" <?=$selection['sort']===$value?'selected':''?>><?=h($label)?></option><?php endforeach; ?></select></label>
    <label>На странице<select name="perpage" class="form-control"><?php foreach([25,50,100]as$value):?><option <?=$selection['perpage']===$value?'selected':''?>><?=$value?></option><?php endforeach;?></select></label>
    <label class="catalog-controls__stock"><input type="checkbox" name="in_stock" value="1" <?=$selection['in_stock']?'checked':''?>> Только в наличии</label>
    <button type="submit" class="btn btn-primary">Показать</button>
  </div>
  <div class="catalog-controls__summary" aria-live="polite">Найдено: <strong><?=(int)$total?></strong> · <?=h(\app\services\CatalogSelection::SORTS[$selection['sort']])?></div>
</form>
<script src="/js/catalog-controls.js?v=20261026" defer></script>
