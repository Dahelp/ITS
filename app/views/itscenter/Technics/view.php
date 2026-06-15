<?php
$typeAlias = h($type->alias ?? '');
$typeSeoName1 = h($type->seoname_1 ?? '');
$typeSeoName2 = h($type->seoname_2 ?? '');
$typeSeoName3 = h($type->seoname_3 ?? '');
$typeName = h($type->name ?? '');

$manufacturerAlias = h($manufacturer->alias ?? '');
$manufacturerName = h($manufacturer->name ?? '');

$technicsAlias = h($technics->alias ?? '');
$technicsModel = h($technics->model ?? '');
$technicsContent = $technics->content ?? '';

$isKvadr = (($type->name ?? '') === 'Квадроцикл');
$techGenitive = $isKvadr ? 'квадроцикла' : 'спецтехники';

$curr = \ishop\App::$app->getProperty('currency');

$frontFactory = $groupedSizes['1'] ?? [];
$rearFactory  = $groupedSizes['2'] ?? [];
$frontAlt     = $groupedSizes['3'] ?? [];
$rearAlt      = $groupedSizes['4'] ?? [];

$allTechnicsSizes = array_merge($frontFactory, $rearFactory, $frontAlt, $rearAlt);
$sizes_vse = $allTechnicsSizes;

$vsesizeParts = [];
foreach ($sizes_vse as $vse) {
    $sizeValue = h($vse['value']);
    $sizeValueId = (int)$vse['value_id'];
    $linkData = $sizeLinksMap[$sizeValueId] ?? null;

    if ($linkData && !empty($linkData['clickable']) && !empty($linkData['url'])) {
        $vsesizeParts[] = '<a href="' . h($linkData['url']) . '" title="Все шины размера ' . $sizeValue . '">' . $sizeValue . '</a>';
    } else {
        $vsesizeParts[] = $sizeValue;
    }
}
$vsesize = implode(', ', $vsesizeParts);

$plainSizeParts = [];
foreach ($sizes_vse as $vse) {
    if (!empty($vse['value'])) {
        $plainSizeParts[] = trim((string)$vse['value']);
    }
}
$plainSizeParts = array_values(array_unique($plainSizeParts));
$vsesizeText = h(implode(', ', $plainSizeParts));

if (!function_exists('renderTechnicsSizeLinks')) {
    function renderTechnicsSizeLinks(array $items, array $sizeLinksMap): string
    {
        $out = [];

        foreach ($items as $item) {
            $sizeValueId = (int)$item['value_id'];
            $sizeValue = htmlspecialchars((string)$item['value'], ENT_QUOTES, 'UTF-8');
            $linkData = $sizeLinksMap[$sizeValueId] ?? null;

            if ($linkData && !empty($linkData['clickable']) && !empty($linkData['url'])) {
                $url = htmlspecialchars((string)$linkData['url'], ENT_QUOTES, 'UTF-8');
                $out[] = '<a href="' . $url . '" title="Все шины размера ' . $sizeValue . '">' . $sizeValue . '</a>';
            } else {
                $out[] = '<span class="size-disabled">' . $sizeValue . '</span>';
            }
        }

        return implode(', ', $out);
    }
}

$schemaName = 'Шины на ' . trim(($type->name ?? '') . ' ' . ($manufacturer->name ?? '') . ' ' . ($technics->model ?? ''));
$schemaDescription = 'Подбор и продажа шин на ' . trim(($type->name ?? '') . ' ' . ($manufacturer->name ?? '') . ' ' . ($technics->model ?? '')) . '. Подходящие размеры, совместимые товары и комплекты шин.';

$schemaImage = !empty($technics->img)
    ? PATH . '/images/technics/baseimg/' . ltrim((string)$technics->img, '/')
    : PATH . '/images/' . \ishop\App::$app->getProperty('og_logo');

$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Главная',
            'item' => PATH,
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'Каталог техники',
            'item' => PATH . '/technics',
        ],
        [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => 'Производители ' . trim((string)($type->seoname_1 ?? '')),
            'item' => PATH . '/technics/type/' . ltrim((string)($type->alias ?? ''), '/'),
        ],
        [
            '@type' => 'ListItem',
            'position' => 4,
            'name' => trim(\ishop\App::upFirstLetter((string)($type->seoname_3 ?? '')) . ' ' . ($manufacturer->name ?? '')),
            'item' => PATH . '/technics/' . ltrim((string)($type->alias ?? ''), '/') . '/' . ltrim((string)($manufacturer->alias ?? ''), '/'),
        ],
        [
            '@type' => 'ListItem',
            'position' => 5,
            'name' => $schemaName,
            'item' => PATH . '/technics/' . ltrim((string)($technics->alias ?? ''), '/'),
        ],
    ],
];

$collectionSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $schemaName,
    'description' => $schemaDescription,
    'url' => PATH . '/technics/' . ltrim((string)($technics->alias ?? ''), '/'),
    'image' => $schemaImage,
    'mainEntity' => [
        '@type' => 'ItemList',
        'name' => 'Подходящие шины для ' . trim(($type->name ?? '') . ' ' . ($manufacturer->name ?? '') . ' ' . ($technics->model ?? '')),
        'numberOfItems' => !empty($products) ? count($products) : 0,
        'itemListElement' => [],
    ],
];

if (!empty($products)) {
    $pos = 1;
    foreach ($products as $product) {
        $collectionSchema['mainEntity']['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $pos++,
            'url' => PATH . '/product/' . ltrim((string)($product->alias ?? ''), '/'),
            'name' => (string)($product->name ?? ''),
        ];
    }
}

$completeSchema = null;
if (!empty($complete)) {
    $completeSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Комплекты шин для ' . trim(($type->name ?? '') . ' ' . ($manufacturer->name ?? '') . ' ' . ($technics->model ?? '')),
        'numberOfItems' => count($complete),
        'itemListElement' => [],
    ];

    $pos = 1;
    foreach ($complete as $cpl) {
        $completeSchema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $pos++,
            'url' => PATH . '/complete/' . ltrim((string)($cpl['alias'] ?? ''), '/'),
            'name' => (string)($cpl['name'] ?? ''),
        ];
    }
}
?>

<script type="application/ld+json"><?= json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
<script type="application/ld+json"><?= json_encode($collectionSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
<?php if (!empty($completeSchema)): ?>
<script type="application/ld+json"><?= json_encode($completeSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
<?php endif; ?>

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
                    <a href="<?= PATH ?>/technics/type/<?= $typeAlias ?>">Производители <?= $typeSeoName1 ?></a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>/technics/<?= $typeAlias ?>/<?= $manufacturerAlias ?>">
                        <?= \ishop\App::upFirstLetter($type->seoname_3 ?? ''); ?> <?= $manufacturerName ?>
                    </a>
                </li>
                <li class="breadcrumb-item active">Шины на <?= $typeName ?> <?= $manufacturerName ?> <?= $technicsModel ?></li>
            </ol>
        </nav>
    </div>
</div>
<!--end-breadcrumbs-->

<!--start-single-->
<div class="single contact">
    <div class="container">
        <section class="product-hero-its technics-hero-its">
            <div class="product-hero-grid technics-hero-grid technics-hero-grid--2">

                <aside class="product-col product-col--gallery technics-col technics-col--gallery">
                    <div class="product-sticky-wrap">
                        <div class="product-card product-gallery-card technics-gallery-card h-100">
                            <section class="slider">
                                <div class="flexslider product-flexslider-main technics-flexslider-main">
                                    <ul class="slides">
                                        <?php if (!empty($technics->img)): ?>
                                            <li>
                                                <img itemprop="image"
                                                     src="<?= PATH ?>/images/technics/baseimg/<?= h($technics->img); ?>"
                                                     alt="Шины на <?= $typeName ?> <?= $manufacturerName ?> <?= $technicsModel ?>">
                                            </li>
                                        <?php else: ?>
                                            <li>
                                                <img itemprop="image"
                                                     src="<?= PATH ?>/images/no_image.jpg"
                                                     alt="Изображение отсутствует">
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </section>
                        </div>
                    </div>
                </aside>

                <main class="product-col product-col--main technics-col technics-col--main">
                    <div class="product-sticky-wrap">
                        <div class="product-card product-main-card technics-main-card h-100">

                            <div class="product-main-topline">
                                <div class="product-main-topline__left">
                                    <a class="product-meta"
                                       href="<?= PATH ?>/technics/type/<?= $typeAlias ?>"
                                       title="Производители <?= $typeSeoName1 ?>">
                                        <?= $typeName ?>
                                    </a>

                                    <div class="product-brand-chip">
                                        Бренд: <span><?= $manufacturerName ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($_SESSION['user']['id']) && $administr && $administr['groups'] == '1'): ?>
                                    <div class="edit_prod product-edit-link">
                                        <a target="_blank" href="<?= ADMIN ?>/plagins/technics-edit?id=<?= (int)$technics->id ?>">
                                            <i class="far fa-edit"></i> Редактировать
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <h1 class="product-title">
                                Шины на <?= $typeName ?> <?= $manufacturerName ?> <?= $technicsModel ?>
                            </h1>

                            <div class="technics-info-scroll">
                                <div class="product-quick-props technics-props-box">
                                    <div class="product-block-title">Характеристики техники</div>
                                    <ul class="product-props-list">
                                        <li>
                                            <span class="prop-name">Тип техники</span>
                                            <span class="prop-value"><?= $typeName ?></span>
                                        </li>
                                        <li>
                                            <span class="prop-name">Производитель</span>
                                            <span class="prop-value"><?= $manufacturerName ?></span>
                                        </li>
                                        <li>
                                            <span class="prop-name">Модель</span>
                                            <span class="prop-value"><?= $technicsModel ?></span>
                                        </li>
                                    </ul>
                                </div>

                                <?php if ($frontFactory || $rearFactory || $frontAlt || $rearAlt): ?>

                                    <?php if ($frontFactory || $rearFactory): ?>
                                        <div class="product-quick-props technics-props-box mt-3">
                                            <div class="product-block-title">Заводские размеры шин</div>
                                            <ul class="product-props-list">
                                                <?php if ($frontFactory && $rearFactory): ?>
                                                    <li>
                                                        <span class="prop-name">Передние</span>
                                                        <span class="prop-value"><?= renderTechnicsSizeLinks($frontFactory, $sizeLinksMap); ?></span>
                                                    </li>
                                                    <li>
                                                        <span class="prop-name">Задние</span>
                                                        <span class="prop-value"><?= renderTechnicsSizeLinks($rearFactory, $sizeLinksMap); ?></span>
                                                    </li>
                                                <?php elseif ($frontFactory): ?>
                                                    <li>
                                                        <span class="prop-name">Размер</span>
                                                        <span class="prop-value"><?= renderTechnicsSizeLinks($frontFactory, $sizeLinksMap); ?></span>
                                                    </li>
                                                <?php elseif ($rearFactory): ?>
                                                    <li>
                                                        <span class="prop-name">Размер</span>
                                                        <span class="prop-value"><?= renderTechnicsSizeLinks($rearFactory, $sizeLinksMap); ?></span>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($frontAlt || $rearAlt): ?>
                                        <div class="product-quick-props technics-props-box mt-3">
                                            <div class="product-block-title">Альтернативные размеры шин</div>
                                            <ul class="product-props-list">
                                                <?php if ($frontAlt && $rearAlt): ?>
                                                    <li>
                                                        <span class="prop-name">Передние</span>
                                                        <span class="prop-value"><?= renderTechnicsSizeLinks($frontAlt, $sizeLinksMap); ?></span>
                                                    </li>
                                                    <li>
                                                        <span class="prop-name">Задние</span>
                                                        <span class="prop-value"><?= renderTechnicsSizeLinks($rearAlt, $sizeLinksMap); ?></span>
                                                    </li>
                                                <?php elseif ($frontAlt): ?>
                                                    <li>
                                                        <span class="prop-name">Размер</span>
                                                        <span class="prop-value"><?= renderTechnicsSizeLinks($frontAlt, $sizeLinksMap); ?></span>
                                                    </li>
                                                <?php elseif ($rearAlt): ?>
                                                    <li>
                                                        <span class="prop-name">Размер</span>
                                                        <span class="prop-value"><?= renderTechnicsSizeLinks($rearAlt, $sizeLinksMap); ?></span>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </main>

            </div>
        </section>

        <?php if (!empty($complete)): ?>
            <section class="complete-premium">
                <div class="complete-premium__head">
                    <div>
                        <h2>Купить комплект шин</h2>
                        <p>
                            Готовые комплекты шин для <?= $typeName ?> <?= $manufacturerName ?> <?= $technicsModel ?> с понятной ценой,
                            быстрым оформлением и проверкой наличия по каждой позиции.
                        </p>
                    </div>
                </div>

                <div class="complete-premium__list">
                    <?php foreach ($complete as $cpl): ?>
                        <?php
                        $prods = $completeItemsById[(int)$cpl['id']] ?? [];

                        $prodid = '';
                        $price_complete = 0;
                        $discount_complete = 0;
                        $itg_qty = 0;
                        $vcomplecte = 0;

                        foreach ($prods as $prod) {
                            $price_complete += ((float)$prod['price_complete'] * (int)$prod['qty']);
                            $discount_complete += ((float)$prod['discount'] * (int)$prod['qty']);

                            if ((int)$prod['quantity'] >= (int)$prod['qty']) {
                                $quantity_ok = 1;
                                $prodid .= $prod['product_id'] . '-';
                            } elseif ((int)$prod['quantity'] > 0) {
                                $quantity_ok = 0;
                                $prodid .= $prod['product_id'] . '-';
                            } else {
                                $quantity_ok = 0;
                            }

                            $itg_qty += $quantity_ok;
                            $vcomplecte += (int)$prod['qty'];
                        }

                        $prodid = rtrim($prodid, '-');

                        $isFullAvailable = ($itg_qty == count($prods));
                        $isPartialAvailable = ($itg_qty > 0 && $itg_qty < count($prods));

                        $effective_discount_complete = ($isFullAvailable && $discount_complete > 0) ? $discount_complete : 0;
                        $itog_price_complete = max(0, $price_complete - $effective_discount_complete);
                        $discountLabel = ($effective_discount_complete > 0)
                            ? number_format($effective_discount_complete, 0, '.', ' ') . ' ' . $curr['symbol_right']
                            : '';

                        $nm = htmlspecialchars((string)$cpl['name'], ENT_QUOTES, 'UTF-8');
                        ?>
                        <article class="complete-premium__card">
                            <div class="complete-premium__main">

                                <div class="complete-premium__visual">
                                    <div class="complete-premium__image">
                                        <img
                                            class="complete-premium__img"
                                            src="<?= PATH ?>/images/complete/mini/<?= h($cpl['img']) ?>"
                                            alt="<?= $nm ?>"
                                            title="<?= $nm ?>"
                                            loading="lazy"
                                        >
                                    </div>

                                    <div class="complete-premium__meta">
                                        <div class="complete-premium__badges">
                                            <span class="complete-badge complete-badge--dark">Готовый комплект</span>
                                            <span class="complete-badge complete-badge--light">В комплекте <?= (int)$vcomplecte ?> шт.</span>

                                            <?php if ($isFullAvailable): ?>
                                                <span class="complete-badge complete-badge--ok">Все позиции в наличии</span>
                                            <?php elseif ($isPartialAvailable): ?>
                                                <span class="complete-badge complete-badge--warn">Частично в наличии</span>
                                            <?php else: ?>
                                                <span class="complete-badge complete-badge--empty">Нет в наличии</span>
                                            <?php endif; ?>
                                        </div>

                                        <h3><?= h($cpl['name']) ?></h3>

                                        <?php if (!empty($cpl['description'])): ?>
                                            <div class="complete-premium__desc"><?= $cpl['description'] ?></div>
                                        <?php else: ?>
                                            <div class="complete-premium__desc">
                                                Комплект шин, подобранный для <?= $typeName ?> <?= $manufacturerName ?> <?= $technicsModel ?>.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="complete-premium__composition">
                                    <button
                                        class="complete-premium__toggle"
                                        type="button"
                                        aria-expanded="false"
                                        aria-controls="complete-composition-<?= (int)$cpl['id'] ?>"
                                    >
                                        <span class="complete-premium__composition-title">Состав комплекта</span>
                                        <span class="complete-premium__toggle-icon" aria-hidden="true">
                                            <i class="fas fa-chevron-down"></i>
                                        </span>
                                    </button>

                                    <div class="complete-premium__composition-drop" id="complete-composition-<?= (int)$cpl['id'] ?>" hidden>
                                        <div class="complete-premium__composition-list">
                                            <?php foreach ($prods as $prod): ?>
                                                <div class="complete-comp-item">
                                                    <div class="complete-comp-item__name"><?= h($prod['name']) ?></div>
                                                    <div class="complete-comp-item__meta">
                                                        <span><?= (int)$prod['qty'] ?> шт.</span>
                                                        <span><?= number_format((float)$prod['price_complete'], 0, '.', ' ') ?> <?= h($curr['symbol_right']) ?> / шт.</span>

                                                        <?php if ((int)$prod['quantity'] >= (int)$prod['qty']): ?>
                                                            <span class="is-ok">Достаточно</span>
                                                        <?php elseif ((int)$prod['quantity'] > 0): ?>
                                                            <span class="is-warn">Частично</span>
                                                        <?php else: ?>
                                                            <span class="is-empty">Нет</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <aside class="complete-premium__aside">
                                <div class="complete-summary">
                                    <div class="complete-summary__title">Итого по комплекту</div>

                                    <?php if ($effective_discount_complete > 0): ?>
                                        <div class="complete-summary__old">
                                            <?= number_format($price_complete, 0, '.', ' ') ?> <?= h($curr['symbol_right']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="complete-summary__price">
                                        <?= number_format($itog_price_complete, 0, '.', ' ') ?> <?= h($curr['symbol_right']) ?>
                                    </div>

                                    <?php if ($effective_discount_complete > 0): ?>
                                        <div class="complete-summary__save">
                                            Экономия: <?= $discountLabel ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="complete-summary__status">
                                        <?php if ($isFullAvailable): ?>
                                            <span class="status-ok">Комплект доступен полностью</span>
                                        <?php elseif ($isPartialAvailable): ?>
                                            <span class="status-warn">Доступен не в полном составе</span>
                                        <?php else: ?>
                                            <span class="status-empty">Комплект недоступен</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="complete-summary__actions">
                                        <?php if ($isFullAvailable): ?>
                                            <input class="form-control" style="display:none;" name="quantity" type="number" value="1" min="1" data-min="1">
                                            <a
                                                data-id="<?= h($prodid) ?>"
                                                data-complete="1"
                                                data-complete-id="<?= (int)$cpl['id'] ?>"
                                                data-set="<?= (int)$cpl['id'] ?>"
                                                class="btn btn-danger add-to-cart-complete korzina-<?= (int)$cpl['id'] ?> clear-korzina"
                                                href="<?= PATH ?>/cart/addcomplete?id=<?= h($prodid) ?>"
                                                data-toggle="modal"
                                                data-target="#exampleModalLive"
                                                onclick="try{window.ym&&ym(87229051,'reachGoal','VKORZINU')}catch(e){}; return true;"
                                            >
                                                <i class="fas fa-cart-plus"></i> Купить комплект
                                            </a>
                                        <?php elseif ($isPartialAvailable): ?>
                                            <input class="form-control" style="display:none;" name="quantity" type="number" value="1" min="1" data-min="1">
                                            <a
                                                data-id="<?= h($prodid) ?>"
                                                data-complete="0"
                                                data-complete-id="<?= (int)$cpl['id'] ?>"
                                                data-set="<?= (int)$cpl['id'] ?>"
                                                class="btn btn-warning add-to-cart-complete korzina-<?= (int)$cpl['id'] ?> clear-korzina"
                                                href="<?= PATH ?>/cart/addcomplete?id=<?= h($prodid) ?>"
                                                data-toggle="modal"
                                                data-target="#exampleModalLive"
                                                onclick="try{window.ym&&ym(87229051,'reachGoal','VKORZINU')}catch(e){}; return true;"
                                            >
                                                <i class="fas fa-cart-plus"></i> Купить неполный комплект
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary" type="button" disabled>Нет в наличии</button>
                                        <?php endif; ?>

                                        <a class="btn btn-outline-dark" href="<?= PATH ?>/complete/<?= h($cpl['alias']) ?>">Подробнее о комплекте</a>
                                    </div>
                                </div>
                            </aside>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($products)): ?>
            <section class="technics-related-products">
                <div class="product-block-title section-title--products">
                    Подходящие шины для <?= $typeName ?> <?= $manufacturerName ?> <?= $technicsModel ?>
                </div>

                <div class="row gx-3 gy-3 product-one">
                    <?php foreach ($products as $product): ?>
                        <div class="col-xl-3 col-lg-6 col-md-4 col-sm-6">
                            <?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php', $productWidgetContext ?? []); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="catalog_text">
            <?= $technicsContent ?>

            <h2>Шины на <?= $typeName ?> <?= $manufacturerName ?> <?= $technicsModel ?> — подбор и покупка</h2>

            <p>
                В интернет-магазине ИТС-Центр вы можете подобрать и купить шины на <?= $typeName ?> <?= $manufacturerName ?> <?= $technicsModel ?> с учетом рекомендуемых параметров эксплуатации. Для данной модели техники особенно важно использовать резину подходящего типоразмера: от этого зависят устойчивость, проходимость, управляемость, ресурс ходовой части и комфорт работы на разных типах покрытия.
            </p>

            <p>
                На этой странице собраны подходящие шины для <?= $typeSeoName2 ?> <?= $manufacturerName ?> <?= $technicsModel ?>. Мы ориентируемся именно на продажу шин для конкретной техники, поэтому пользователь сразу видит заводские и альтернативные размеры, совместимые товары и готовые комплекты. Такой формат помогает быстрее выбрать нужную резину и сократить время на поиск.
            </p>

            <?php if (!empty($vsesizeText)): ?>
                <p>
                    Для <?= $typeName ?> <?= $manufacturerName ?> <?= $technicsModel ?> могут применяться размеры: <?= $vsesize ?>. Переходя по размерам, вы можете открыть подборки товаров и посмотреть доступные модели шин, которые подходят для этой техники.
                </p>
            <?php endif; ?>

            <h2>Какие шины подходят для <?= $typeSeoName1 ?></h2>

            <p>
                При выборе шин для <?= $techGenitive ?> важно учитывать не только посадочный диаметр и ширину, но и условия работы: тип покрытия, нагрузку на ось, интенсивность эксплуатации, сезонность и требования к рисунку протектора. Для складской, строительной, коммунальной, сельскохозяйственной и другой техники параметры резины напрямую влияют на эффективность работы и безопасность.
            </p>

            <p>
                Если техника используется на асфальте, бетоне, грунте, щебне или смешанных покрытиях, шины должны обеспечивать надежное сцепление, устойчивость к износу и стабильную работу под нагрузкой. Для <?= $typeSeoName2 ?> <?= $manufacturerName ?> <?= $technicsModel ?> в каталоге можно подобрать решения под разные задачи: от повседневной эксплуатации до интенсивной профессиональной работы.
            </p>

            <h2>Почему стоит купить шины у нас</h2>

            <ul>
                <li>Подбор шин под конкретную модель техники и нужный размер.</li>
                <li>Наличие популярных размеров и совместимых товаров в одном разделе.</li>
                <li>Удобный переход из карточки техники сразу в каталог подходящих шин.</li>
                <li>Возможность купить как отдельные позиции, так и комплект шин.</li>
                <li>Консультация по подбору, рисунку протектора, нагрузке и применению.</li>
                <li>Помощь в выборе шин для склада, стройплощадки, производства и других задач.</li>
            </ul>

            <h2>Подбор шин по размерам и эксплуатации</h2>

            <p>
                Правильно подобранные шины на <?= $typeName ?> <?= $manufacturerName ?> <?= $technicsModel ?> позволяют снизить риск неравномерного износа, повысить устойчивость техники и сохранить рабочие характеристики на длительный срок. Если для модели предусмотрены альтернативные размеры, их также стоит учитывать при подборе — особенно когда требуется адаптация техники под конкретные условия эксплуатации.
            </p>

            <p>
                В каталоге ИТС-Центр вы можете выбрать шины для <?= $techGenitive ?> по производителю, размеру и назначению. Мы стараемся формировать понятные страницы под каждую единицу техники, чтобы клиенту было проще найти нужную резину, сравнить варианты и перейти к покупке без лишних шагов.
            </p>

            <h2>Заказать шины на <?= $typeName ?> <?= $manufacturerName ?> <?= $technicsModel ?></h2>

            <p>
                Если вам нужны шины на <?= $typeName ?> <?= $manufacturerName ?> <?= $technicsModel ?>, используйте блоки с подходящими товарами и размерами на этой странице. Вы можете выбрать нужную модель самостоятельно или обратиться к нашим специалистам за помощью. Мы поможем подобрать шины под условия эксплуатации, нагрузку, тип покрытия и требуемый размер.
            </p>

            <p>
                ИТС-Центр предлагает шины для <?= $techGenitive ?> с удобным подбором по параметрам. На сайте можно быстро найти совместимые размеры, открыть карточки товаров, уточнить наличие и оформить заказ. Если нужен точный подбор шин на <?= \ishop\App::downFirstLetter($type->name ?? '') ?> <?= $manufacturerName ?> <?= $technicsModel ?>, свяжитесь с нами — подскажем оптимальный вариант под вашу задачу.
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.complete-premium__toggle');
    if (!btn) return;

    var targetId = btn.getAttribute('aria-controls');
    var panel = document.getElementById(targetId);
    if (!panel) return;

    var expanded = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    panel.hidden = expanded;
});
</script>
<!--end-single-->