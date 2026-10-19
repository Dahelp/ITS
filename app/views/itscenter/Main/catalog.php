<section class="home-catalog" aria-labelledby="homeCatalogTitle">
  <div class="container">
    <h1>Интернет-магазин резины, дисков и фильтров для спецтехники</h1>
    <div class="home-catalog__heading">
      <div><span class="home-catalog__eyebrow">Основные направления</span><h2 id="homeCatalogTitle">Каталог шин</h2></div>
      <p>Для вашей техники и условий работы</p>
    </div>
    <div class="home-catalog__tires">
      <?php foreach ([1 => ['Индустриальные шины', 'Для погрузчиков, экскаваторов и другой спецтехники', 'cat-1.webp'],
                      2 => ['Шины для квадроциклов', 'Для ATV, багги и мотовездеходов', 'cat-2.webp']] as $id => $card): ?>
      <?php if (empty($homeCatalog[$id])) continue; $group = $homeCatalog[$id]; $href = PATH . '/category/' . $group['alias']; ?>
      <article class="home-tire-card home-tire-card--<?= $id === 1 ? 'industrial' : 'atv' ?>">
        <div class="home-tire-card__copy">
          <span class="home-tire-card__count">Товаров в каталоге: <?= (int)$group['total'] ?></span>
          <h3><a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"><?= $card[0] ?></a></h3>
          <p><?= $card[1] ?></p>
        </div>
        <a class="home-tire-card__image" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= $card[0] ?>">
          <img src="<?= PATH ?>/images/<?= $card[2] ?>" width="300" height="150" alt="<?= $card[0] ?>" fetchpriority="high">
        </a>
        <nav class="home-tire-card__links" aria-label="<?= $id === 1 ? 'Шины по виду техники' : 'Популярные размеры шин ATV' ?>">
          <?php if ($id === 1): ?>
            <?php foreach (array_slice($group['children'], 0, 4) as $child): ?>
              <a href="<?= PATH ?>/category/<?= htmlspecialchars($child['alias'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(preg_replace('/^Шины для /u', 'Для ', $child['name']), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
          <?php else: ?>
            <?php foreach ($group['sizes'] ?? [] as $size): ?>
              <a href="<?= PATH ?>/category/atv/<?= htmlspecialchars($size['alias'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($size['value'], ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
          <?php endif; ?>
        </nav>
        <a class="home-tire-card__browse" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"><?= $id === 1 ? 'Все индустриальные шины' : 'Все шины для квадроциклов' ?> <span aria-hidden="true">→</span></a>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="home-catalog__footer">
      <a class="btn btn-primary" href="<?= PATH ?>/catalog/shiny">Все шины в каталоге <span aria-hidden="true">→</span></a>
      <?php if (!empty($homeCatalog[36])): ?><a class="home-catalog__secondary" href="<?= PATH ?>/category/gruzovye-shiny">Грузовые шины <span aria-hidden="true">↗</span></a><?php endif; ?>
    </div>

    <div class="home-catalog__heading home-catalog__heading--related"><h2>Сопутствующие товары</h2><p>Всё необходимое для комплектации и обслуживания</p></div>
    <div class="home-catalog__accessories">
      <?php foreach ([25 => ['Камеры и ободные ленты', 'cat-5.webp'], 3 => ['Диски и кольца', 'cat-3.webp'], 4 => ['Фильтры', 'cat-4.jpg']] as $id => $card): ?>
      <?php if (empty($homeCatalog[$id])) continue; $group = $homeCatalog[$id]; ?>
      <a class="home-accessory" href="<?= PATH ?>/category/<?= htmlspecialchars($group['alias'], ENT_QUOTES, 'UTF-8') ?>">
        <img src="<?= PATH ?>/images/<?= $card[1] ?>" width="300" height="150" alt="" loading="lazy">
        <span><strong><?= $card[0] ?></strong><small>Товаров: <?= (int)$group['total'] ?></small></span><span class="home-accessory__arrow" aria-hidden="true">→</span>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="home-catalog__tools">
      <h2>Знаете параметры? Подберите точнее</h2>
      <div class="home-catalog__tool-links">
        <?php foreach ([['podbor/shiny', 'Шины по размеру'], ['technics', 'Шины по марке техники'], ['complete', 'Комплекты шин'],
                        ['podbor/kamery', 'Камеры по размеру'], ['podbor/diski', 'Диски по параметрам'], ['category/filtry', 'Каталог фильтров']] as $tool): ?>
        <a href="<?= PATH ?>/<?= $tool[0] ?>"><?= $tool[1] ?> <span aria-hidden="true">↗</span></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
