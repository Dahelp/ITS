<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>"><i class="fas fa-home"></i><span class="visually-hidden">Главная</span></a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>/technics">Каталог техники</a>
                </li>
                <li class="breadcrumb-item active">Производители <?= h($type['seoname_1']) ?></li>
            </ol>
        </nav>
    </div>
</div>
<!--end-breadcrumbs-->

<!--start-single-->
<div class="single contact technics-page">
    <div class="container">
        <div class="register-top heading">
            <h1>Производители <?= h($type['seoname_1']) ?></h1>
        </div>

        <?php if (!empty($manufacturers)): ?>
            <div class="row technics-grid g-4">
                <?php foreach ($manufacturers as $item): ?>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                        <a href="<?= PATH ?>/technics/<?= h($type['alias']) ?>/<?= h($item['alias']) ?>"
                           title="<?= h($item['name']) ?>"
                           class="technics-card-link">
                            <div class="technics-card technics-card--manufacturer">
                                <div class="technics-card__img">
                                    <img src="<?= PATH ?>/images/technics_manufacturer/baseimg/<?= h($item['img']) ?>"
                                         alt="<?= h($item['name']) ?>"
                                         title="<?= h($item['name']) ?>">
                                </div>
                                <div class="technics-card__body">
                                    <div class="technics-card__title"><?= h($item['name']) ?></div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($type['content'])): ?>
            <div class="catalog_text mt-4">
                <?= $type['content'] ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<!--end-single-->