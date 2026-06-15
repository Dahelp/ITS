<!--prdt-starts-->
<div class="prdt search-results-page">
    <div class="container">
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item">
                    <a class="text-nowrap" href="<?= PATH ?>"><i class="fas fa-home"></i><span class="visually-hidden">Главная</span></a>
                </li>
                <li class="breadcrumb-item text-nowrap active">
                    Поиск по запросу "<strong><?= h($query); ?></strong>"
                </li>
            </ol>
        </nav>

        <section class="d-md-flex justify-content-between align-items-center mb-4 pb-2">
            <h1 class="h2 mb-3 mb-md-0 me-3">
                Поиск по запросу: <strong><?= h($query); ?></strong>
            </h1>
        </section>

        <div class="prdt-top">
            <div class="col-md-12">
                <?php if (!empty($products)): ?>
                    <div class="row gx-3 gy-3 product-one">
                        <?php $curr = \ishop\App::$app->getProperty('currency'); ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col-xl-3 col-lg-6 col-md-4 col-sm-6 mb-3">
                                <?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php', $searchWidgetContext ?? []); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($pagination) && !empty($pagination->countPages) && $pagination->countPages > 1): ?>
                        <div class="text-center mt-3">
                            <?= $pagination; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <h3>По вашему запросу ничего не найдено.</h3>
                <?php endif; ?>
            </div>

            <div class="clearfix"></div>
        </div>
    </div>
</div>
<!--product-end-->
