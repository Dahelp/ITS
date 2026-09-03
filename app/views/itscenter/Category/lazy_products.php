<?php if (!empty($products)): ?>
    <?php $curr = \ishop\App::$app->getProperty('currency'); ?>
    <?php foreach ($products as $product): ?>
        <div class="col-xl-3 col-lg-6 col-md-4 col-sm-6 mb-3">
            <?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php', $productWidgetContext ?? []); ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
