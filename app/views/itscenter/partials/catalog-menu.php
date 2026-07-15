<?php

if (!function_exists('cm_h')) {
	function cm_h($value): string {
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

$path = rtrim(PATH, '/');

/**
 * Быстрый генератор ссылки вида /category/{categoryAlias}/{sizeAlias}
 */
$menuSizeUrl = static function (string $categoryAlias, string $sizeAlias) use ($path): string {
	$categoryAlias = trim($categoryAlias, '/');
	$sizeAlias = trim($sizeAlias, '/');
	return $path . '/category/' . $categoryAlias . '/' . $sizeAlias;
};

$menuFilterUrl = static function (string $categoryAlias, string $filterAlias) use ($path): string {
	$categoryAlias = trim($categoryAlias, '/');
	$filterAlias = trim($filterAlias, '/');
	return $path . '/category/' . $categoryAlias . '/' . rawurlencode($filterAlias);
};

/**
 * Подготавливаем категории, которые участвуют в меню.
 * Здесь можно дополнять список по мере правок меню.
 */
$menuCategoryGroups = [
	'industrialnye-shiny' => [
		'industrialnye-shiny',
		'shiny-dlya-minipogruzchikov',
		'shiny-dlya-vilochnyh-pogruzchikov',
		'shiny-dlya-ekskavatorov-pogruzchikov',
		'shiny-dlya-frontalnyh-pogruzchikov',
		'shiny-dlya-shahtnoy-tehniki',
		'shiny-dlya-gruntovyh-katkov',
		'shiny-dlya-greyderov',
		'shiny-dlya-kolesnyh-ekskavatorov',
		'shiny-dlya-mobilnyh-kranov',
	],
	'atv' => [
		'atv',
	],
	'kamery-i-obodnye-lenty' => [
		'kamery-i-obodnye-lenty',
		'kamery',
		'obodnye-lenty',
		'uplotnitelnye-kolca',
	],
	'gruzovye-shiny' => [
		'gruzovye-shiny',
	],
];

$allCategoryAliases = [];
foreach ($menuCategoryGroups as $aliases) {
	foreach ($aliases as $alias) {
		$allCategoryAliases[$alias] = $alias;
	}
}
$allCategoryAliases = array_values($allCategoryAliases);

$categoryIdByAlias = [];
if (!empty($allCategoryAliases)) {
	$catPlaceholders = implode(',', array_fill(0, count($allCategoryAliases), '?'));
	$categoryRows = \R::getAll("
		SELECT id, alias, name
		FROM category
		WHERE alias IN ($catPlaceholders)
	", $allCategoryAliases);

	foreach ($categoryRows as $row) {
		$categoryIdByAlias[$row['alias']] = (int)$row['id'];
	}
}

/**
 * Получаем id категорий по alias-группе
 */
$getCategoryIds = static function (array $aliases) use ($categoryIdByAlias): array {
	$ids = [];
	foreach ($aliases as $alias) {
		if (!empty($categoryIdByAlias[$alias])) {
			$ids[] = (int)$categoryIdByAlias[$alias];
		}
	}
	return array_values(array_unique($ids));
};

/**
 * Проверяем, есть ли связующая таблица category_product
 * чтобы учитывать товары в нескольких категориях.
 */
$hasCategoryProductTable = false;
try {
	$hasCategoryProductTable = (bool)\R::getCell("SHOW TABLES LIKE 'category_product'");
} catch (\Throwable $e) {
	$hasCategoryProductTable = false;
}

/**
 * Счётчик товаров по брендам внутри набора категорий
 */
$getBrandCountsByCategoryIds = static function (array $brandAliases, array $categoryIds) use ($hasCategoryProductTable): array {
	$result = [];
	foreach ($brandAliases as $alias) {
		$result[$alias] = 0;
	}

	if (empty($brandAliases) || empty($categoryIds)) {
		return $result;
	}

	$brandPlaceholders = implode(',', array_fill(0, count($brandAliases), '?'));
	$catPlaceholders   = implode(',', array_fill(0, count($categoryIds), '?'));

	if ($hasCategoryProductTable) {
		$sql = "
			SELECT b.alias, COUNT(DISTINCT p.id) AS total
			FROM brand b
			LEFT JOIN product p
				ON p.brand_id = b.id
				AND (p.hide IS NULL OR p.hide != 'hide')
			LEFT JOIN category_product cp
				ON cp.product_id = p.id
			WHERE b.alias IN ($brandPlaceholders)
			  AND (
					p.category_id IN ($catPlaceholders)
					OR cp.category_id IN ($catPlaceholders)
			  )
			GROUP BY b.id, b.alias
		";

		$params = array_merge($brandAliases, $categoryIds, $categoryIds);
	} else {
		$sql = "
			SELECT b.alias, COUNT(DISTINCT p.id) AS total
			FROM brand b
			LEFT JOIN product p
				ON p.brand_id = b.id
				AND (p.hide IS NULL OR p.hide != 'hide')
			WHERE b.alias IN ($brandPlaceholders)
			  AND p.category_id IN ($catPlaceholders)
			GROUP BY b.id, b.alias
		";

		$params = array_merge($brandAliases, $categoryIds);
	}

	$rows = \R::getAll($sql, $params);
	foreach ($rows as $row) {
		$result[$row['alias']] = (int)$row['total'];
	}

	return $result;
};

/**
 * Популярные бренды для блока "Индустриальные шины"
 * alias поправил: solid-star вместо "solid star"
 */
$industrialBrandMap = [
	'ekka'        => ['title' => 'EKKA',        'class' => 'ekka',        'category' => 'shiny-dlya-ekskavatorov-pogruzchikov'],
	'superguider' => ['title' => 'Superguider', 'class' => 'superguider', 'category' => 'shiny-dlya-ekskavatorov-pogruzchikov'],
	'halitrax'    => ['title' => 'HALITRAX',    'class' => 'halitrax',    'category' => 'shiny-dlya-frontalnyh-pogruzchikov'],
	'hengruida'   => ['title' => 'Hengruida',   'class' => 'hengruida',   'category' => 'shiny-dlya-frontalnyh-pogruzchikov'],
	'herade'      => ['title' => 'HERADE',      'class' => 'herade',      'category' => 'shiny-dlya-vilochnyh-pogruzchikov'],
	'huiton'      => ['title' => 'HUITON',      'class' => 'huiton',      'category' => 'shiny-dlya-vilochnyh-pogruzchikov'],
	'ist'         => ['title' => 'IST',         'class' => 'ist',         'category' => 'shiny-dlya-vilochnyh-pogruzchikov'],
	'solid-star'  => ['title' => 'SOLID STAR',  'class' => 'solid-star',  'category' => 'shiny-dlya-vilochnyh-pogruzchikov'],
];

$industrialCategoryIds = $getCategoryIds($menuCategoryGroups['industrialnye-shiny'] ?? []);
$industrialBrandCounts = $getBrandCountsByCategoryIds(array_keys($industrialBrandMap), $industrialCategoryIds);
$industrialBrandsTotal = count(array_filter($industrialBrandCounts, static fn($count) => $count > 0));
?>
<!--MEGAMENU-->
<div id="catalogMenu" class="catalog-menu" aria-hidden="true">
	<div class="catalog-menu__backdrop" data-close="1"></div>

	<div class="catalog-menu__panel" role="dialog" aria-modal="true">
		<div class="catalog-menu__bar">
			<button type="button" class="catalog-menu__back-btn" data-back="1" aria-label="Назад" style="display:none;">←</button>
			<div class="catalog-menu__bar-title">Каталог</div>
			<button type="button" class="catalog-menu__close" data-close="1" aria-label="Закрыть">×</button>
		</div>
		<div class="catalog-menu__container">
			<nav class="catalog-menu__nav" id="catalogNav"></nav>
			<div class="catalog-menu__content-wrap" id="catalogContentWrap"></div>
		</div>
	</div>
	
	<div id="catalogSource" class="catalog-source" hidden>

		<div class="dropdown mega-dropdown">									
			<div class="dropdown-menu clear">				
				<a class="dropdown-item submenu" href="category/industrialnye-shiny">Индустриальные шины</a>
				<div class="dropdown-menu-byp">
					<div class="catalog-manual">
						<div class="catalog-menu__title">
							<a href="/category/industrialnye-shiny" class="catalog-menu__name">Индустриальные шины</a>
							<span class="catalog-menu__count">407 товар</span>
						</div>

						<div class="catalog-menu__row">
							<div class="catalog-menu__col">
								<a href="/category/shiny-dlya-minipogruzchikov" class="catalog-menu__sub-title">
									Минипогрузчики
								</a>

								<div class="subcategory-wrapper">
									<div class="catalog-menu__sub-wrapper">
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-minipogruzchikov', '10-16.5')) ?>">10-16.5</a>
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-minipogruzchikov', '12-16.5')) ?>">12-16.5</a>
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-minipogruzchikov', '14-17.5')) ?>">14-17.5</a>
									<a href="/category/shiny-dlya-minipogruzchikov" class="more">Смотреть все</a>
									</div>
								</div>
							</div>

							<div class="catalog-menu__col">
								<a href="/category/shiny-dlya-vilochnyh-pogruzchikov" class="catalog-menu__sub-title">
									Вилочные погрузчики
								</a>

								<div class="subcategory-wrapper">
									<div class="catalog-menu__sub-wrapper">
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-vilochnyh-pogruzchikov', '4.00-8')) ?>">4.00-8</a>
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-vilochnyh-pogruzchikov', '5.00-8')) ?>">5.00-8</a>
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-vilochnyh-pogruzchikov', '6.00-9')) ?>">6.00-9</a>
									<a href="/category/shiny-dlya-vilochnyh-pogruzchikov" class="more">Смотреть все</a>
									</div>
								</div>
							</div>

							<div class="catalog-menu__col">
								<a href="/category/shiny-dlya-ekskavatorov-pogruzchikov" class="catalog-menu__sub-title">
									Экскаваторы-погрузчики
								</a>

								<div class="subcategory-wrapper">
									<div class="catalog-menu__sub-wrapper">
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-ekskavatorov-pogruzchikov', '12.5/80-18')) ?>">12.5/80-18</a>
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-ekskavatorov-pogruzchikov', '18.4-26')) ?>">18.4-26</a>
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-ekskavatorov-pogruzchikov', '16.9-24')) ?>">16.9-24</a>
									<a href="/category/shiny-dlya-ekskavatorov-pogruzchikov" class="more">Смотреть все</a>
									</div>
								</div>
							</div>
							<!-- ... подкатегории -->
							 <div class="catalog-menu__col">
								<a href="/category/shiny-dlya-frontalnyh-pogruzchikov" class="catalog-menu__sub-title">
									Фронтальные погрузчики
								</a>

								<div class="subcategory-wrapper">
									<div class="catalog-menu__sub-wrapper">
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-frontalnyh-pogruzchikov', '17.5-25')) ?>">17.5-25</a>
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-frontalnyh-pogruzchikov', '20.5-25')) ?>">20.5-25</a>
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-frontalnyh-pogruzchikov', '23.5-25')) ?>">23.5-25</a>
									<a href="/category/shiny-dlya-frontalnyh-pogruzchikov" class="more">Смотреть все</a>
									</div>
								</div>
							</div>

							<div class="catalog-menu__col">
								<a href="/category/shiny-dlya-shahtnoy-tehniki" class="catalog-menu__sub-title">
									Шахтная техника
								</a>

								<div class="subcategory-wrapper">
									<div class="catalog-menu__sub-wrapper">
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-shahtnoy-tehniki', '10.00-20')) ?>">10.00-20</a>
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-shahtnoy-tehniki', '12.00-24')) ?>">12.00-24</a>
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-shahtnoy-tehniki', '14.00-24')) ?>">14.00-24</a>
									<a href="/category/shiny-dlya-shahtnoy-tehniki" class="more">Смотреть все</a>
									</div>
								</div>
							</div>						

							<div class="catalog-menu__col">
								<a href="/category/shiny-dlya-gruntovyh-katkov" class="catalog-menu__sub-title">
									Грунтовый каток
								</a>

								<div class="subcategory-wrapper">
									<div class="catalog-menu__sub-wrapper">
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-gruntovyh-katkov', '13/80-20')) ?>">13/80-20</a>
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-gruntovyh-katkov', '23.1-26')) ?>">23.1-26</a>								
									</div>
								</div>
							</div>

							<!-- ... подкатегории -->
							<div class="catalog-menu__col">
								<a href="/category/shiny-dlya-greyderov" class="catalog-menu__sub-title">
									Грейдеры
								</a>

								<div class="subcategory-wrapper">
									<div class="catalog-menu__sub-wrapper">
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-greyderov', '14.00-24')) ?>">14.00-24</a>									
									</div>
								</div>
							</div>

							<div class="catalog-menu__col">
								<a href="/category/shiny-dlya-kolesnyh-ekskavatorov" class="catalog-menu__sub-title">
									Колесные экскаваторы
								</a>

								<div class="subcategory-wrapper">
									<div class="catalog-menu__sub-wrapper">
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-kolesnyh-ekskavatorov', '10.00-20')) ?>">10.00-20</a>								
									</div>
								</div>
							</div>

							<div class="catalog-menu__col">
								<a href="/category/shiny-dlya-mobilnyh-kranov" class="catalog-menu__sub-title">
									Мобильные краны
								</a>

								<div class="subcategory-wrapper">
									<div class="catalog-menu__sub-wrapper">
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-mobilnyh-kranov', '14-00r25')) ?>">14.00R25</a>
									<a href="<?= cm_h($menuSizeUrl('shiny-dlya-mobilnyh-kranov', '16-00r25')) ?>">16.00R25</a>						
									</div>
								</div>
							</div>

						</div>

						<div class="catalog-menu__title catalog-menu__title_brand">
						<div class="catalog-menu__name">Популярные бренды</div>
						<span class="catalog-menu__count"><?= $industrialBrandsTotal ?> брендов</span>
					</div>

					<div class="catalog-menu__row catalog-menu__row_brands">
						<?php foreach ($industrialBrandMap as $brandAlias => $brandData): ?>
							<?php
								$brandCount = (int)($industrialBrandCounts[$brandAlias] ?? 0);
								if ($brandCount <= 0) {
									continue;
								}
							?>
							<a href="<?= cm_h($menuFilterUrl($brandData['category'] ?? 'industrialnye-shiny', $brandAlias)) ?>"
							class="brands-logo__el <?= cm_h($brandData['class']) ?>">
								<span><?= cm_h($brandData['title']) ?></span>
								<div class="brands-logo__el-count"><?= $brandCount ?></div>
							</a>
						<?php endforeach; ?>
					</div>
					</div>
				</div>
				<a class="dropdown-item submenu" href="category/atv">Шины для квадроциклов АТВ</a>
				<div class="dropdown-menu-byp">					
					<div class="mbl-1">
						<div class="menu-h2">Шины на диск</div>
						<a class="dropdown-item-byp" href="category/atv/4-inches">4 дюйма</a>
						<a class="dropdown-item-byp" href="category/atv/6-inches">6 дюймов</a>
						<a class="dropdown-item-byp" href="category/atv/7-inches">7 дюймов</a>
						<a class="dropdown-item-byp" href="category/atv/8-inches">8 дюймов</a>
						<a class="dropdown-item-byp" href="category/atv/9-inches">9 дюймов</a>
						<a class="dropdown-item-byp" href="category/atv/10-inches">10 дюймов</a>
						<a class="dropdown-item-byp" href="category/atv/12-inches">12 дюймов</a>
						<a class="dropdown-item-byp" href="category/atv/14-inches">14 дюймов</a>
					</div>
					<div class="mbl-2">
						<div class="menu-h2">Популярные размеры шин</div>
						<div class="mbl-popul">
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '13x5-6')) ?>">13x5-6</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '15x6-6')) ?>">15x6-6</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '145/70-6')) ?>">145/70-6</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '16x6.50-8')) ?>">16x6.50-8</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '18x8.50-8')) ?>">18х8.50-8</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '19x9.50-8')) ?>">19x9.50-8</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '20x10-9')) ?>">20x10-9</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '20x10-8')) ?>">20x10-8</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '20x11-9')) ?>">20х11-9</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '21x7-10')) ?>">21x7-10</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '22x7-10')) ?>">22x7-10</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '23x7-10')) ?>">23x7-10</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '25x8-12')) ?>">25x8-12</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '25x10-12')) ?>">25x10-12</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '26x9-12')) ?>">26x9-12</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('atv', '26x11-12')) ?>">26x11-12</a>					
						</div>
					</div>
					<div class="mbl-3">
						<div class="menu-h2">Комплект шин на квадроцикл</div>
						<div class="mbl-img">
							<img src="../images/komplect_kvadro.webp" alt="Комплект шин на квадроцикл" title="Комплект шин на квадроцикл" />
						</div>							
					</div>
				</div>
				<a class="dropdown-item submenu" href="category/diski">Диски на технику</a>
				<div class="dropdown-menu-byp">					
					<div class="mbl-1">
						<div class="menu-h2">Диски для погрузчиков и грузовых машин</div>
						<a class="dropdown-item-byp" title="Диски для вилочных погрузчиков" href="category/diski-dlya-vilochnyh-pogruzchikov">Диски для вилочных погрузчиков</a>
						<a class="dropdown-item-byp" title="Диски для минипогрузчиков" href="category/diski-dlya-minipogruzchikov">Диски для минипогрузчиков</a>
						<a class="dropdown-item-byp" title="Диски для экскаваторов-погрузчиков" href="category/diski-dlya-ekskavatorov-pogruzchikov">Диски для экскаваторов-погрузчиков</a>
						<a class="dropdown-item-byp" title="Диски для грузовой техники" href="category/diski-dlya-gruzovoy-tehniki">Диски для грузовой техники</a>
						<a class="dropdown-item-byp" title="Диски для фронтальных погрузчиков" href="category/diski-dlya-frontalnyh-pogruzchikov">Диски для фронтальных погрузчиков</a>						
						<a class="dropdown-item-byp" title="Кольца для дисков спецтехники" href="category/kolca">Кольца для дисков спецтехники</a>	
					</div>
					<div class="mbl-2">
						<div class="menu-h2">Диски для минипогрузчиков</div>
						<div class="mbl-img">
							<a href="category/diski-dlya-minipogruzchikov" title="Диски для вилочных погрузчиков">
								<img src="../images/disk-mini.webp" alt="Диски для минипогрузчиков" title="Диски для минипогрузчиков" />
							</a>
						</div>
					</div>
					<div class="mbl-3">
						<div class="menu-h2">Диски для вилочных погрузчиков</div>
						<div class="mbl-img">
							<a href="category/diski-dlya-vilochnyh-pogruzchikov" title="Диски для вилочных погрузчиков">
								<img src="../images/disk-vil.webp" alt="Диски для вилочных погрузчиков" title="Диски для вилочных погрузчиков" />
							</a>
						</div>							
					</div>
				</div>
				<a class="dropdown-item submenu" href="category/filtry">Фильтры для спецтехники</a>
				<div class="dropdown-menu-byp">
					<div class="mbl-1">
						<div class="menu-h2">Фильтры для спецтехники</div>
						<a class="dropdown-item-byp" href="category/vozdushnye-filtry">Воздушные фильтры</a>
						<a class="dropdown-item-byp" href="category/gidravlicheskie-filtry">Гидравлические фильтры</a>
						<a class="dropdown-item-byp" href="category/maslyanye-filtry">Масляные фильтры</a>
						<a class="dropdown-item-byp" href="category/toplivnye-filtry">Топливные фильтры</a>
						<a class="dropdown-item-byp" href="category/filtry-osushiteli">Фильтры салона (кабины)</a>
						<a class="dropdown-item-byp" href="category/filtry-osushiteli">Фильтры осушители</a>
						<a class="dropdown-item-byp" href="category/filtry-ohlazhdayuschey-zhidkosti">Фильтры охлаждающей жидкости</a>
						<a class="dropdown-item-byp" href="category/filtry-sapuna">Фильтры сапуна</a>
					</div>
					<div class="mbl-2">
						<div class="menu-h2">Комплект фильтров на технику JCB</div>
						<div class="mbl-img">
							<img src="../images/mini_filters_its_jcb.webp" alt="Комплект фильтров на технику JCB" title="Комплект фильтров на технику JCB" />
						</div>
					</div>
					<div class="mbl-3">
						<div class="menu-h2">Комплект фильтров на технику BOBCAT</div>
						<div class="mbl-img">
							<img src="../images/mini_filters_its_bobcat.webp" alt="Комплект фильтров на технику BOBCAT" title="Комплект фильтров на технику BOBCAT" />
						</div>							
					</div>
				</div>
				<a class="dropdown-item submenu" href="category/kamery-i-obodnye-lenty">Камеры и ободные ленты</a>
				<div class="dropdown-menu-byp">
					<div class="mbl-1">
						<div class="menu-h2">Камеры и ободные ленты</div>
						<a class="dropdown-item-byp" href="category/kamery">Камеры</a>
						<a class="dropdown-item-byp" href="category/obodnye-lenty">Ободные ленты</a>
						<a class="dropdown-item-byp" href="category/uplotnitelnye-kolca">Уплотнительные кольца (O-Ring)</a>
					</div>
					<div class="mbl-2">
						<div class="menu-h2">Популярные размеры камер</div>
						<div class="mbl-popul">
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '4.00-8')) ?>">4.00-8</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '5.00-8')) ?>">5.00-8</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '6.00-9')) ?>">6.00-9</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '6.50-10')) ?>">6.50-10</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '8.25-15')) ?>">8.25-15</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '10.00-20')) ?>">10.00-20</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '11-00-20')) ?>">11.00-20</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '14-9-24')) ?>">14.9-24</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '14-9-28')) ?>">14.9-28</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '16.9-24')) ?>">16.9-24</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '16.9-28')) ?>">16.9-28</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '17.5-25')) ?>">17.5-25</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '20.5-25')) ?>">20.5-25</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('kamery', '23.5-25')) ?>">23.5-25</a>													
						</div>
					</div>
				</div>
				<a class="dropdown-item submenu" href="category/gruzovye-shiny">Грузовые шины</a>
				<div class="dropdown-menu-byp">
					<div class="mbl-1">
						<div class="menu-h2">Производители</div>						
							<a class="dropdown-item-byp" href="<?= cm_h($menuFilterUrl('gruzovye-shiny', 'kama')) ?>">КАМА</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuFilterUrl('gruzovye-shiny', 'annaite')) ?>">ANNAITE</a>
					</div>
					<div class="mbl-2">
						<div class="menu-h2">Популярные размеры шин</div>
						<div class="mbl-popul">
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('gruzovye-shiny', '12-00r20')) ?>">12.00R20</a>
							<a class="dropdown-item-byp" href="<?= cm_h($menuSizeUrl('gruzovye-shiny', '275-70r22-5')) ?>">275/70R22.5</a>																			
						</div>
					</div>
					<div class="mbl-3">
						<div class="menu-h2">Самый популярный товар</div>
						<div class="mbl-img">
							<img src="../images/product/baseimg/06bde34572e293f789e67c9b6a91807c.webp" alt="315/80R22.5 F NF-202 КАМА Шина грузовая рулевая" title="315/80R22.5 F NF-202 КАМА Шина грузовая рулевая" />
						</div>							
					</div>
				</div>
			</div>
		</div>

	</div>
</div>
