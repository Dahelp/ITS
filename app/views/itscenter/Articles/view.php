<div class="breadcrumbs">
    <div class="container">
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>"><i class="fas fa-home"></i><span class="visually-hidden">Главная</span></a>
                </li>

                <?php if ($type->hide_anons == 'show'): ?>
                    <li class="breadcrumb-item">
                        <a href="<?= PATH ?>/<?=$type->param_url?>"><?=htmlspecialchars($type->name, ENT_QUOTES, 'UTF-8');?></a>
                    </li>
                <?php endif; ?>

                <li class="breadcrumb-item active"><?=htmlspecialchars($find->name, ENT_QUOTES, 'UTF-8');?></li>
            </ol>
        </nav>
    </div>
</div>

<?php
$schemaType = 'Article';
$typeParam = mb_strtolower((string)($type->param_url ?? ''));

switch ($typeParam) {
    case 'news':
        $schemaType = 'NewsArticle';
        break;
    case 'pages':
    case 'page':
        $schemaType = 'WebPage';
        break;
    case 'services':
        $schemaType = 'WebPage';
        break;
    case 'articles':
    default:
        $schemaType = 'Article';
        break;
}

$shopName = \ishop\App::$app->getProperty('shop_name');
$logoUrl = rtrim(PATH, '/') . '/images/' . \ishop\App::$app->getProperty('og_logo');
$pageUrl = rtrim(PATH, '/') . '/' . trim((string)$type->param_url, '/') . '/' . $find->alias;
$imageUrl = !empty($find->img)
    ? rtrim(PATH, '/') . '/images/contents/baseimg/' . $find->img
    : $logoUrl;

$descRaw = '';
if (!empty($find->description)) {
    $descRaw = (string)$find->description;
} elseif (!empty($find->anons)) {
    $descRaw = strip_tags((string)$find->anons);
} else {
    $descRaw = trim(strip_tags((string)$find->content));
}
$descRaw = mb_substr($descRaw, 0, 300);

$entitySchema = [
    '@context' => 'https://schema.org',
    '@type' => $schemaType,
    'headline' => $find->name,
    'name' => $find->name,
    'url' => $pageUrl,
    'description' => $descRaw,
    'image' => [$imageUrl],
    'author' => [
        '@type' => 'Organization',
        'name' => $shopName,
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => $shopName,
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $logoUrl,
        ],
    ],
];

if (!empty($find->date_post)) {
    $entitySchema['datePublished'] = date('c', strtotime($find->date_post));
}
if (!empty($find->date_last_modified)) {
    $entitySchema['dateModified'] = date('c', strtotime($find->date_last_modified));
}

$breadcrumbItems = [
    [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Главная',
        'item' => rtrim(PATH, '/'),
    ],
];

if ($type->hide_anons == 'show') {
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => $type->name,
        'item' => rtrim(PATH, '/') . '/' . trim((string)$type->param_url, '/'),
    ];
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $find->name,
        'item' => $pageUrl,
    ];
} else {
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => $find->name,
        'item' => $pageUrl,
    ];
}

$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems,
];
?>

<script type="application/ld+json"><?=json_encode($entitySchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?></script>
<script type="application/ld+json"><?=json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?></script>

<section class="contents py-4 py-md-5">
    <div class="container">
        <div class="row">
            <?php if (!empty($find)):
                if ($type->hide_clicks == 'show') {
                    \R::exec('UPDATE contents SET clicks = clicks + 1 WHERE id = ?', [$find->id]);
                }
            ?>
                <div class="col-12">
                    <article class="bg-white rounded-4 shadow-sm p-3 p-md-4 p-lg-5">
                        <header class="mb-4">
                            <h1 class="mb-3"><?=htmlspecialchars($find->name, ENT_QUOTES, 'UTF-8');?></h1>

                            <?php if (($type['hide_date_post'] ?? '') === 'show'): ?>
                                <div class="cont_info_data">
                                    <time datetime="<?=date('c', strtotime($find['date_post']))?>">
                                        <?= \ishop\App::contdate($find['date_post']); ?>
                                    </time>
                                </div>
                            <?php endif; ?>
                        </header>

                        <?php if ($find->img && $find->img_hide == 'show'): ?>
                            <div class="cont-img mb-4">
                                <img class="img-fluid rounded-4" src="<?= PATH ?>/images/contents/baseimg/<?=$find->img;?>" alt="<?=htmlspecialchars($find->name, ENT_QUOTES, 'UTF-8');?>">
                            </div>
                        <?php endif; ?>

                        <div class="cont-desc">
                            <?=$find->content;?>
                        </div>
                    </article>
                </div>

                <?php
                    $curr = \ishop\App::$app->getProperty('currency');
                    $cats = \ishop\App::$app->getProperty('cats');
                ?>

                <?php if($related): ?>
                    <div class="related_prod article-related-products">
                        <section class="pb-5 mb-2 mb-xl-4 recomend-1">
                            <h2 class="h3 pb-2 mb-grid-gutter text-center">Связанные товары</h2>

                            <div class="review-wrap">
                                <div class="wrap-container">
                                    <div class="inner-container">
                                        <div class="swiper-container swiper1">
                                            <div class="swiper-wrapper">
                                                <?php foreach($related as $product): ?>
                                                    <div class="swiper-slide">
                                                        <?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php'); ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="swiper-button-inner">
                                <div class="swiper-button-next swiper-button-next-1"></div>
                                <div class="swiper-button-prev swiper-button-prev-1"></div>
                            </div>
                        </section>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</section>
