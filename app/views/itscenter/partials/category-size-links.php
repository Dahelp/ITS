<?php
// Use the same active filter landings as the category filters; never invent sizes.
$sizeCategoryId=(int)($category->id??0);
if (in_array($sizeCategoryId,[44,45],true)):
$categorySizeLinks=\R::getAll("SELECT DISTINCT av.value,av.alias
 FROM attribute_value av
 JOIN attribute_group ag ON ag.id=av.attr_group_id
 JOIN attribute_value_category_canonical ac ON ac.attr_value_id=av.id
 WHERE ac.category_id=? AND ac.is_active=1 AND ac.mode='landing'
 AND ag.url_params='size' AND av.hide='show'
 AND EXISTS (SELECT 1 FROM attribute_product ap JOIN product p ON p.id=ap.product_id
 WHERE ap.attr_id=av.id AND p.category_id=? AND p.hide='show')
 ORDER BY av.value",[$sizeCategoryId,$sizeCategoryId]);
if($categorySizeLinks): ?>
<section class="related-sizes category-size-links mb-4" aria-labelledby="category-size-links-title">
  <h2 class="h4" id="category-size-links-title">Подбор цепей по размеру шин</h2>
  <div class="related-sizes__items">
    <?php foreach($categorySizeLinks as $sizeLink): ?>
    <a class="related-sizes__item" href="<?= h(\app\services\filters\FilterUrlHelper::buildCategoryFilterUrl((string)$category->alias,(string)$sizeLink['alias'])) ?>"><?= h($sizeLink['value']) ?></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; endif; ?>
