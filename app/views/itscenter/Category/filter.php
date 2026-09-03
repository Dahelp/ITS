<?php if(!empty($products)): ?>
    <?php $curr = \ishop\App::$app->getProperty('currency'); ?>

    <?php foreach(array_slice(array_values($products), 0, 10) as $product): ?>
        <div class="col-xl-3 col-lg-6 col-md-4 col-sm-6 mb-3 p-2">
            <?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php', $productWidgetContext ?? []); ?>
        </div>
    <?php endforeach; ?>

    <?php if (count($products) > 10): ?>
        <div class="col-12 category-products-lazy-sentinel"
             data-category-lazy-sentinel
             aria-hidden="true"></div>
    <?php endif; ?>

    <div class="clearfix"></div>

    <div class="text-center">
        <p>(<?=count($products)?> товара(ов) из <?=$total;?>)</p>
        <?php if($pagination->countPages > 1): ?>
            <?=$pagination;?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <h3>Товаров не найдено...</h3>
<?php endif; ?>
