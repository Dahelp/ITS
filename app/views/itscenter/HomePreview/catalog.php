<?php
$escape = static function($text): string { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); };
$countLabel = static function($number): string {
    $n = (int)$number; $last = $n % 10; $hundred = $n % 100;
    return $n . ' ' . ($last === 1 && $hundred !== 11 ? 'товар' : ($last >= 2 && $last <= 4 && ($hundred < 12 || $hundred > 14) ? 'товара' : 'товаров'));
};
$industrialCards = [9 => ['Для минипогрузчиков', 'skid-steer.webp'], 18 => ['Для вилочных погрузчиков', 'forklift.webp'],
    21 => ['Для экскаваторов', 'wheeled-excavator.webp'], 20 => ['Для фронтальных погрузчиков', 'wheel-loader.webp']];
?>
<section class="hp-catalog container" aria-labelledby="hp-title">
  <div class="hp-catalog__inner">
    <div class="hp-tools" aria-label="Быстрый подбор">
      <a href="<?= PATH ?>/podbor/shiny"><i class="far fa-search" aria-hidden="true"></i> По размеру</a>
      <a href="<?= PATH ?>/technics"><i class="far fa-tractor" aria-hidden="true"></i> По технике</a>
      <a href="<?= PATH ?>/complete"><i class="far fa-layer-group" aria-hidden="true"></i> Комплекты</a>
      <?php if (!empty($previewCatalog[36])): ?><a class="hp-truck" href="<?= PATH ?>/category/gruzovye-shiny"><i class="far fa-truck" aria-hidden="true"></i> Грузовые шины <span aria-hidden="true">→</span></a><?php endif; ?>
    </div>
    <div class="hp-heading"><h1 id="hp-title">Каталог шин</h1></div>
    <div class="hp-toolbar">
      <div class="hp-tabs" role="tablist" aria-label="Направление каталога">
        <button type="button" id="hp-tab-industrial" role="tab" aria-selected="true" aria-controls="hp-industrial" data-hp-tab="industrial">Индустриальные · <span><?= (int)($previewCatalog[1]['total'] ?? 0) ?></span></button>
        <button type="button" id="hp-tab-atv" role="tab" aria-selected="false" aria-controls="hp-atv" tabindex="-1" data-hp-tab="atv">ATV · <span><?= (int)($previewCatalog[2]['total'] ?? 0) ?></span></button>
      </div>
      <a class="hp-section-link hp-section-link--desktop" data-hp-section-link href="<?= PATH ?>/category/industrialnye-shiny">Все индустриальные шины <span aria-hidden="true">→</span></a>
      <a class="hp-all-top" href="<?= PATH ?>/catalog/shiny">Все шины в каталоге <span aria-hidden="true">→</span></a>
    </div>

    <div id="hp-industrial" role="tabpanel" aria-labelledby="hp-tab-industrial" data-hp-panel="industrial" tabindex="0">
      <div class="hp-grid">
        <?php foreach ($industrialCards as $id => $card): if (empty($previewCatalog[$id])) continue; $category = $previewCatalog[$id]; ?>
        <a class="hp-tile" href="<?= PATH ?>/category/<?= $escape($category['alias']) ?>">
          <div class="hp-tile__heading"><h2><?= $escape($card[0]) ?></h2><span class="hp-arrow" aria-hidden="true">→</span></div>
          <span class="hp-tile__visual"><span class="hp-machine hp-machine--<?= $id ?>" role="img" aria-label="<?= $escape($card[0]) ?>"></span></span>
        </a>
        <?php endforeach; ?>
      </div>
      <div class="hp-sizes"><h2>Популярные размеры</h2><nav aria-label="Популярные размеры индустриальных шин" class="hp-chips">
        <?php foreach ($previewCatalog[1]['sizes'] ?? [] as $size): ?><a href="<?= PATH ?>/category/industrialnye-shiny/<?= $escape($size['alias']) ?>"><?= $escape($size['value']) ?><span class="hp-chip-arrow" aria-hidden="true"><svg width="8" height="14" viewBox="0 0 8 14" fill="none"><path d="M1 1l6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span></a><?php endforeach; ?>
      </nav></div>
    </div>

    <div id="hp-atv" role="tabpanel" aria-labelledby="hp-tab-atv" data-hp-panel="atv" tabindex="0" hidden>
      <div class="hp-grid">
        <?php foreach ($previewCatalog[2]['patterns'] ?? [] as $pattern): ?>
        <a class="hp-tile hp-tile--atv" href="<?= PATH ?>/category/atv/<?= $escape($pattern['alias']) ?>">
          <div class="hp-tile__heading"><div><h2><?= $escape($pattern['value']) ?></h2><span class="hp-tile__count"><?= $escape($countLabel($pattern['total'])) ?></span></div><span class="hp-arrow" aria-hidden="true">→</span></div>
          <span class="hp-tile__visual"><svg class="hp-tile__image hp-tire-studio" viewBox="<?= $escape($pattern['image_viewbox']) ?>" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Шина ATV <?= $escape($pattern['value']) ?>"><image href="<?= PATH ?><?= $escape($pattern['image_url']) ?>" width="800" height="800"/></svg></span>
        </a>
        <?php endforeach; ?>
      </div>
      <div class="hp-sizes"><h2>Популярные размеры ATV</h2><nav aria-label="Популярные размеры шин ATV" class="hp-chips">
        <?php foreach ($previewCatalog[2]['sizes'] ?? [] as $size): ?><a href="<?= PATH ?>/category/atv/<?= $escape($size['alias']) ?>"><?= $escape($size['value']) ?><span class="hp-chip-arrow" aria-hidden="true"><svg width="8" height="14" viewBox="0 0 8 14" fill="none"><path d="M1 1l6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span></a><?php endforeach; ?>
      </nav></div>
    </div>
    <div class="hp-bottom-action"><a class="hp-primary" href="<?= PATH ?>/catalog/shiny">Все шины в каталоге <span aria-hidden="true">→</span></a></div>
    <a class="hp-section-link hp-section-link--mobile" data-hp-section-link href="<?= PATH ?>/category/industrialnye-shiny">Все индустриальные шины <span aria-hidden="true">→</span></a>
    <noscript><p><a href="<?= PATH ?>/category/atv">Перейти в каталог шин для квадроциклов</a></p></noscript>

    <div class="hp-accessories"><div class="hp-accessories__heading"><h2>Сопутствующие товары</h2></div><div class="hp-accessories__grid">
      <?php foreach ([25 => ['Камеры и ободные ленты', 'cat-5.webp'], 3 => ['Диски', 'cat-3.webp'], 4 => ['Фильтры', 'cat-4.jpg']] as $id => $item): if (empty($previewCatalog[$id])) continue; ?>
      <a class="hp-accessory" href="<?= PATH ?>/category/<?= $escape($previewCatalog[$id]['alias']) ?>"><span class="hp-accessory__media"><?php if ($id === 3): ?><img class="hp-accessory__image hp-accessory__image--rims" src="<?= PATH ?>/images/home-preview/wheel-rims.webp" width="800" height="533" alt="" loading="lazy"><?php else: ?><span class="hp-accessory__image hp-accessory__image--<?= $id ?>" aria-hidden="true"></span><?php endif; ?></span><span class="hp-accessory__text"><strong><?= $item[0] ?></strong><small><?= $escape($countLabel($previewCatalog[$id]['total'])) ?></small><span class="hp-arrow" aria-hidden="true">→</span></span></a>
      <?php endforeach; ?>
    </div></div>
  </div>
</section>
