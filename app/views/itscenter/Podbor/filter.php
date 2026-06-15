<?php if (empty($hasFilter)): ?>

    <div class="alert alert-light border rounded-3 p-4 text-center">
        Выберите параметры фильтра, чтобы показать товары.
    </div>

<?php elseif (!empty($products)): ?>

    <div class="row gx-3 gy-3 product-one">
        <?php foreach ($products as $product): ?>
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
                <?php require APP . '/widgets/product/product_tpl.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="clearfix"></div>

    <div class="text-center mt-4">
        <p>(<?= count($products) ?> товара(ов) из <?= (int)$total; ?>)</p>
        <?php if (!empty($pagination) && $pagination->countPages > 1): ?>
            <?= $pagination; ?>
        <?php endif; ?>
    </div>

<?php else: ?>

    <div class="alert alert-warning border rounded-3 p-4 text-center">
        Товаров по выбранным параметрам не найдено.
    </div>

<?php endif; ?>