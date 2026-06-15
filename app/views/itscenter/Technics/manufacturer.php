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
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>/technics/type/<?= h($type['alias']) ?>">
                        Производители <?= h($type['seoname_1']) ?>
                    </a>
                </li>
                <li class="breadcrumb-item active">
                    <?= \ishop\App::upFirstLetter($type['seoname_3']); ?> <?= h($manufacturer['name']) ?>
                </li>
            </ol>
        </nav>
    </div>
</div>
<!--end-breadcrumbs-->

<!--start-single-->
<div class="single contact technics-page">
    <div class="container">
        <div class="register-top heading">
            <h1><?= \ishop\App::upFirstLetter($type['seoname_3']); ?> <?= h($manufacturer['name']) ?></h1>
        </div>

        <?php if (!empty($technics)): ?>
            <div class="row technics-grid g-4">
                <?php foreach ($technics as $item): ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                        <a href="<?= PATH ?>/technics/<?= h($item['alias']) ?>"
                           title="<?= h($item['model']) ?>"
                           class="technics-card-link">
                            <div class="technics-card technics-card--model">
                                <div class="technics-card__img">
                                    <?php if (!empty($item['img'])): ?>
                                        <img src="<?= PATH ?>/images/technics/mini/<?= h($item['img']) ?>"
                                             alt="<?= h($item['model']) ?>"
                                             title="<?= h($item['model']) ?>">
                                    <?php else: ?>
                                        <img src="<?= PATH ?>/images/no_image.jpg"
                                             alt="<?= h($item['model']) ?>"
                                             title="<?= h($item['model']) ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="technics-card__body">
                                    <div class="technics-card__title"><?= h($item['model']) ?></div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-4">
                <?php if ($pagination->countPages > 1): ?>
                    <?= $pagination; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($manufacturer['content'])): ?>
            <div class="catalog_text mt-4">
                <?= $manufacturer['content'] ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<!--end-single-->