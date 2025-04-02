<!--prdt-starts-->
<div class="prdt">
    <div class="container">
		<!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a class="text-nowrap" href="<?=PATH?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item text-nowrap active">Поиск по запросу "<strong><?=h($query);?></strong>"</li>
            </ol>
		</nav>
		<!--end-breadcrumbs-->
		<section class="d-md-flex justify-content-between align-items-center mb-4 pb-2">
            <h1 class="h2 mb-3 mb-md-0 me-3">Поиск по запросу: <strong><?=h($query);?></strong></h1>
        </section>	
		
<!--start-single-->
<div class="single contact">
    <div class="container">
        <?php if(!empty($products)): ?>
                <div class="row g-0 mx-n2 product-one">
                        <?php $curr = \ishop\App::$app->getProperty('currency'); ?>
                        <?php foreach($products as $product): ?>
							<div class="col-xl-3 col-lg-6 col-md-4 col-sm-6 mb-3">
								<?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php'); ?>
							</div>
                        <?php endforeach; ?>
                        <div class="clearfix"></div>
                        <div class="text-center">                            
                            <?php if($pagination->countPages > 1): ?>
                                <?=$pagination;?>
                            <?php endif; ?>
                        </div>
                    </div>
        <?php endif; ?>
            </div>            
            <div class="clearfix"></div>
        </div>
    </div>
</div>
<!--product-end-->