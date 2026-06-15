<?php
$inseo_prod = null;

if (!empty($category->id)) {
    $inseo_prod = \R::findOne(
        'plagins_inseo',
        "tip = ? AND category_id = ? AND hide = 'show'",
        ['product', $category->id]
    );
}
?>

<!--prdt-starts-->
<div class="prdt">
    <div class="container">

        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>"><i class="fas fa-home"></i></a>
                </li>
                <li class="breadcrumb-item active"><?= h($pdr_name) ?></li>
            </ol>
        </nav>

        <section class="align-items-center">
            <h1 class="h2 mb-3 mb-md-0 me-3"><?= h($pdr_name) ?></h1>
        </section>

        <section class="d-md-flex justify-content-between align-items-center pb-4">
            <div class="w_sidebar col-md-12 fltr">
                <?php new \app\widgets\filter\Filter($ids, $selectedFilters ?? []); ?>
            </div>
        </section>

        <div class="d-flex align-items-center col-md-12 pb-4 sort-inner">
            <div class="sort-inner">
                <div class="sort-name">Сортировать по:</div>
                <span class="nav-link" id="nal">Наличию</span>
                <span class="nav-link" id="price">Цене</span>
                <span class="nav-link" id="rate">Рейтингу</span>
            </div>
        </div>

        <div class="prdt-top">
            <div class="col-md-12">

                <?php require APP . '/views/itscenter/Podbor/filter.php'; ?>

                <?php if (!empty($inseo_prod) && !empty($inseo_prod->content)): ?>
                    <div class="catalog_text">
                        <?= $inseo_prod->content; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="clearfix"></div>
        </div>
    </div>
</div>
<!--product-end-->