<?php
$items = \R::getAll("SELECT cp.qty, cp.price, cp.discount, p.quantity, p.reserve,
    COALESCE((SELECT SUM(m.quantity) FROM modification m WHERE m.product_id = p.id), 0) AS modification_quantity
    FROM plagins_complete_product cp JOIN product p ON p.id = cp.product_id WHERE cp.complete_id = ?", [(int)$complete['id']]);
$price = $discount = $pieces = $available = 0;
foreach ($items as $item) {
    $price += (float)$item['price'] * (int)$item['qty'];
    $discount += (float)$item['discount'] * (int)$item['qty'];
    $pieces += (int)$item['qty'];
    if ((float)$item['quantity'] + (float)$item['modification_quantity'] - (float)$item['reserve'] >= (int)$item['qty']) $available++;
}
$inStock = count($items) > 0 && $available === count($items);
$stockText = $inStock ? 'В наличии' : ($available > 0 ? 'Неполный комплект' : 'Нет в наличии');
$category = \R::getCell('SELECT name FROM category WHERE id = ?', [(int)$complete['category_id']]);
$url = PATH . '/complete/' . (string)$complete['alias'];
$name = (string)$complete['name'];
$value = max(0, $price - $discount) * (float)($curr['value'] ?? 1);
?>
<article class="product-card complete-list-card">
 <a class="pc-media" href="<?= h($url) ?>"><img src="<?= PATH ?>/images/complete/mini/<?= h(basename((string)$complete['img'])) ?>" width="250" height="250" loading="lazy" alt="<?= h($name) ?>"></a>
 <div class="pc-body">
  <div class="pc-meta"><?= h($category) ?></div>
  <div class="pc-title"><a href="<?= h($url) ?>"><?= h($name) ?></a></div>
  <div class="complete-list-composition">В комплекте: <?= (int)$pieces ?> шт.</div>
  <div class="pc-row pc-row--price">
   <div class="pc-price"><div class="pc-price__main"><?= h($curr['symbol_left'] ?? '') ?> <?= number_format($value, 0, '.', ' ') ?> <?= h($curr['symbol_right'] ?? '₽') ?></div>
   <?php if ($discount > 0): ?><del class="pc-price__old"><?= number_format($price * (float)($curr['value'] ?? 1), 0, '.', ' ') ?> <?= h($curr['symbol_right'] ?? '₽') ?></del><?php endif; ?></div>
   <span class="pc-stock <?= $inStock ? 'pc-stock--ok' : 'pc-stock--no' ?>"><?= h($stockText) ?></span>
  </div>
  <div class="pc-cta"><a class="btn btn-danger pc-buy" href="<?= h($url) ?>">Подробнее</a></div>
 </div>
</article>
