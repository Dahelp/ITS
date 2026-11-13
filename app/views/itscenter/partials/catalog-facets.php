<?php
$facetSelected = \app\services\CatalogFacets::ids($allFilterIds ?? []);
$catalogDisplay = $catalogDisplay ?? (new \app\services\CatalogDisplay(\R::getPDO()))->read((int)$category->id);
$facetGroups = \app\services\CatalogFacets::options((string)$ids, $facetSelected, \app\services\CatalogSelection::state($_GET), $catalogDisplay['groups']);
$facetUrl = PATH.'/category/'.rawurlencode((string)$category->alias);
$resetQuery=http_build_query(array_intersect_key($_GET,array_flip(['sort','perpage'])));
$facetResetUrl=$facetUrl.($resetQuery?'?'.$resetQuery:'');
?>
<aside class="catalog-facets" data-facets>
  <button type="button" class="btn btn-primary catalog-facets__open" data-facets-open aria-expanded="false" aria-controls="catalog-facet-panel">Фильтры (<span data-facets-count><?=count($facetSelected)+(int)\app\services\CatalogSelection::state($_GET)['in_stock']?></span>)</button>
  <div class="catalog-facets__backdrop" data-facets-close hidden></div>
  <form id="catalog-facet-panel" class="catalog-facets__panel" method="get" action="<?=h($facetUrl)?>" data-facets-form data-primary-id="<?=(int)($selectedAttr->id??0)?>" data-primary-group="<?=(int)($selectedAttr->attr_group_id??0)?>" data-primary-alias="<?=h((string)($selectedAttr->alias??''))?>">
    <div class="catalog-facets__header"><strong>Фильтры</strong><a class="catalog-facets__reset" data-facets-reset href="<?=h($facetResetUrl)?>">Сбросить</a><button type="button" data-facets-close class="catalog-facets__close" aria-label="Закрыть фильтры">×</button></div>
    <div class="catalog-facets__body">
      <div class="catalog-facets__availability"><label class="catalog-facets__option"><input type="checkbox" name="in_stock" value="1" <?=\app\services\CatalogSelection::state($_GET)['in_stock']?'checked':''?>><span>Только в наличии</span></label></div>
      <?php foreach ($facetGroups as $group): ?>
        <details class="catalog-facets__group" open>
          <summary><?=h($group['title'])?></summary>
          <fieldset id="facet-values-<?=(int)$group['id']?>"><legend class="visually-hidden"><?=h($group['title'])?></legend>
            <?php foreach ($group['values'] as $value): $checked=in_array((int)$value['id'],$facetSelected,true); ?>
              <label class="catalog-facets__option <?=$value['count']==0&&!$checked?'is-empty':''?>">
                <input type="checkbox" name="filter[]" value="<?=(int)$value['id']?>" data-group="<?=(int)$group['id']?>" data-alias="<?=h($value['alias'])?>" <?=$checked?'checked':''?> <?=$value['count']==0&&!$checked?'disabled':''?>>
                <span><?=h($value['value'])?></span><small><?=(int)$value['count']?></small>
              </label>
            <?php endforeach; ?>
          </fieldset>
          <button type="button" class="catalog-facets__more" aria-controls="facet-values-<?=(int)$group['id']?>" aria-expanded="false" hidden>Показать ещё</button>
        </details>
      <?php endforeach; ?>
    </div>
    <div class="catalog-facets__footer"><button type="submit" class="btn btn-primary catalog-facets__manual">Применить</button><button type="button" class="btn btn-primary catalog-facets__done" data-facets-close>К товарам</button></div>
  </form>
</aside>
