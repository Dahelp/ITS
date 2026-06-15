<div class="breadcrumbs">
    <div class="container">
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>"><i class="fas fa-home"></i><span class="visually-hidden">Главная</span></a>
                </li>
                <li class="breadcrumb-item active"><?=htmlspecialchars($type->name, ENT_QUOTES, 'UTF-8');?></li>
            </ol>
        </nav>
    </div>
</div>

<?php
$typeName = trim((string)($type->name ?? ''));
$typeTitle = trim((string)($type->title ?? ''));
$pageTitle = $typeTitle !== '' ? $typeTitle : $typeName;
$typeUrl = rtrim(PATH, '/') . '/' . trim((string)$type->param_url, '/');

$itemList = [];
$position = 1;

if (!empty($conts)) {
    foreach ($conts as $item) {
        $itemUrl = $typeUrl . '/' . $item['alias'];
        $itemList[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'url' => $itemUrl,
            'name' => $item['name'],
        ];
    }
}

$collectionSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'url' => $typeUrl,
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListElement' => $itemList,
    ],
];

$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Главная',
            'item' => rtrim(PATH, '/'),
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $typeName,
            'item' => $typeUrl,
        ],
    ],
];
?>

<script type="application/ld+json"><?=json_encode($collectionSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?></script>
<script type="application/ld+json"><?=json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?></script>

<section class="main-articles content-type-archive py-4 py-md-5">
    <div class="container">
        <h1 class="mb-4"><?=htmlspecialchars($type->name, ENT_QUOTES, 'UTF-8');?></h1>

        <?php if (!empty($conts)): ?>
            <div class="cont-blok">
                <?php foreach($conts as $item): ?>
                    <div class="cont-one">
                        <div class="cont_ht">
                            <a class="cont_blok_img" href="<?= PATH ?>/<?=$type->param_url;?>/<?=$item['alias'];?>">
                                <?php if (!empty($item['img'])): ?>
                                    <img
                                        src="<?= PATH ?>/images/contents/baseimg/<?=$item['img'];?>"
                                        alt="<?=htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');?>"
                                        title="<?=htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');?>"
                                    >
                                <?php else: ?>
                                    <img
                                        src="<?= PATH ?>/images/no_image.jpg"
                                        alt="<?=htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');?>"
                                        title="<?=htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');?>"
                                    >
                                <?php endif; ?>
                            </a>

                            <div class="cont_info">
                                <?php if (($type['hide_date_post'] ?? '') === 'show'): ?>
                                    <div class="cont_info_data">
                                        <?= \ishop\App::contdate($item['date_post']); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="cont_info_name">
                                    <a href="<?= PATH ?>/<?=$type->param_url;?>/<?=$item['alias'];?>">
                                        <?=htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');?>
                                    </a>
                                </div>

                                <div class="cont_info_anons">
                                    <p><?=mb_strimwidth(strip_tags((string)$item['anons']), 0, 220, '...');?></p>
                                </div>

                                <div class="cont_info_bottom">
                                    <a class="cont_read_btn" href="<?= PATH ?>/<?=$type->param_url;?>/<?=$item['alias'];?>">
                                        Читать подробнее
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pb-4 pt-3">
                <?php if ($pagination->countPages > 1): ?>
                    <?=$pagination;?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-light border rounded-3">
                Материалы пока не добавлены.
            </div>
        <?php endif; ?>
    </div>
</section>