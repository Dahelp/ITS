<?php
use app\services\ProductAnalytics;
$insight = $insight ?? 'all';
$analytics = $analytics ?? [];
$analyticsUrl = static function(string $key) use ($categoryId,$activeBadge) {
    $params = [];
    if ($categoryId) $params['category_id'] = $categoryId;
    if ($activeBadge !== '') $params['badge'] = $activeBadge;
    if ($key !== 'all') $params['insight'] = $key;
    return ADMIN.'/product'.($params ? '?'.http_build_query($params) : '').'#product-results';
};
$metric = static function(string $key,string $label = '') use ($analytics,$analyticsUrl,$insight) {
    $label = $label ?: ProductAnalytics::labels()[$key];
    return '<a class="pa-metric'.($insight === $key ? ' is-active' : '').'" href="'.htmlspecialchars($analyticsUrl($key),ENT_QUOTES,'UTF-8').'"'.($insight === $key ? ' aria-current="page"' : '').'><span>'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</span><strong>'.number_format((int)($analytics[$key]??0),0,',',' ').'</strong><i class="fas fa-chevron-right" aria-hidden="true"></i></a>';
};
$hero = static function(string $key,string $label) use ($analytics,$analyticsUrl,$insight) {
    return '<a class="pa-hero'.($insight === $key ? ' is-active' : '').'" href="'.htmlspecialchars($analyticsUrl($key),ENT_QUOTES,'UTF-8').'"'.($insight === $key ? ' aria-current="page"' : '').'><strong>'.number_format((int)($analytics[$key]??0),0,',',' ').'</strong><span>'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</span><i class="fas fa-arrow-right" aria-hidden="true"></i></a>';
};
?>
<link rel="stylesheet" href="<?= PATH ?>/css/admin-product-analytics.css?v=<?= (int)filemtime(WWW.'/css/admin-product-analytics.css') ?>">
<section class="product-analytics" aria-label="Аналитика каталога">
    <div class="pa-heading"><div><h2>Аналитика каталога</h2><p>Нажмите на показатель, чтобы открыть товары для работы.<?= $categoryId || $activeBadge !== '' ? ' Счётчики учитывают выбранную категорию и отметку.' : '' ?></p></div><span class="pa-scope"><?= $categoryId ? 'Выбранная категория' : 'Весь каталог' ?></span></div>
    <div class="pa-overview">
        <?= $metric('all','Всего товаров') ?><?= $metric('active','Активны на сайте') ?><?= $metric('stock','Есть свободный остаток') ?><?= $metric('out_of_stock','Нет свободного остатка') ?>
    </div>
    <div class="pa-grid">
        <section class="pa-panel pa-panel--avito"><header><span class="pa-icon"><i class="fas fa-bullhorn" aria-hidden="true"></i></span><div><h3>Авито</h3><p>Найти товары для новых объявлений</p></div></header>
            <?= $hero('avito_missing_stock','В наличии, но без объявления') ?>
            <div class="pa-metrics"><?= $metric('avito_linked','Есть объявление в базе') ?><?= $metric('avito_missing','Без объявления — всего') ?><?= $metric('avito_prepare','Активные с ценой и остатком') ?><?= $metric('avito_published','Опубликованы по данным API') ?></div>
            <p class="pa-note">Считаются товары с собственным объявлением. Объявления комплектов не заменяют объявления отдельных товаров.</p>
        </section>
        <section class="pa-panel pa-panel--seo"><header><span class="pa-icon"><i class="fas fa-search" aria-hidden="true"></i></span><div><h3>SEO</h3><p>Заполнение карточек и шаблоны InSEO</p></div></header>
            <?= $hero('meta_uncovered','Без Title / Description даже с InSEO') ?>
            <div class="pa-metrics"><?= $metric('seo_complete','Собственное SEO — 100%') ?><?= $metric('seo_incomplete','Собственное SEO — неполное') ?><?= $metric('title_missing','Нет собственного Title') ?><?= $metric('description_missing','Нет собственного Description') ?><?= $metric('title_duplicate','Одинаковые собственные Title') ?><?= $metric('image_missing','Без основного фото') ?></div>
            <p class="pa-note">Процент показывает заполнение полей, а не позиции в поиске. Для покрытия метаданных учитываются только активные шаблоны товаров.</p>
        </section>
        <section class="pa-panel pa-panel--links"><header><span class="pa-icon"><i class="fas fa-project-diagram" aria-hidden="true"></i></span><div><h3>Перелинковка</h3><p>Связи товаров и ссылки из материалов</p></div></header>
            <?= $hero('isolated','Товары полностью без связей') ?>
            <div class="pa-metrics"><?= $metric('related_missing','Без связанных товаров') ?><?= $metric('similar_missing','Без похожих товаров') ?><?= $metric('content_missing','Нет ссылок из материалов') ?><?= $metric('incoming_missing','Никто не ссылается на товар') ?><?= $metric('linked','Есть хотя бы одна связь') ?></div>
            <p class="pa-note">Входящие — ссылки из связанных, похожих товаров и материалов. Учитываются существующие товары и материалы; отборы могут пересекаться.</p>
        </section>
    </div>
</section>
