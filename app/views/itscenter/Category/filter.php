<?php if(!empty($products)): ?>
    <?php $curr = \ishop\App::$app->getProperty('currency'); ?>

    <?php foreach($products as $product): ?>
		<?php $inseo_prod = \R::findOne('plagins_inseo', "tip = ? AND category_id = ? AND hide = 'show'", [product, $category->id]); ?>
        <div class="col-xl-3 col-lg-6 col-md-4 col-sm-6 mb-3 p-2">
			<?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php'); ?>		            
		</div>
    <?php endforeach; ?>
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
