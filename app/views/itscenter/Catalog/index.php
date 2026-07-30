<!--prdt-starts-->
<?php
$renderCategoryText = static function ($content) {
	$content = trim((string)$content);
	if ($content === '') {
		return '';
	}

	return $content === strip_tags($content)
		? nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'))
		: $content;
};
$renderSeoCategoryText = static function ($content) use ($renderCategoryText) {
	return \app\helpers\SeoStrong::apply($renderCategoryText($content));
};
?>
<div class="prdt">
    <div class="container">
		<!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
				<?php if (!empty($breadcrumbs)): ?>

					<?php if (is_array($breadcrumbs)): ?>
						<?php foreach ($breadcrumbs as $crumb): ?>
							<?php
							$crumbTitle = $crumb['title'] ?? $crumb['name'] ?? '';
							$crumbLink  = $crumb['link'] ?? $crumb['url'] ?? '';

							if (!$crumbTitle) {
								continue;
							}
							?>

							<?php if (!empty($crumbLink)): ?>
								<li class="breadcrumb-item">
									<a href="<?= htmlspecialchars($crumbLink, ENT_QUOTES, 'UTF-8') ?>">
										<?= htmlspecialchars($crumbTitle, ENT_QUOTES, 'UTF-8') ?>
									</a>
								</li>
							<?php else: ?>
								<li class="breadcrumb-item active" aria-current="page">
									<?= htmlspecialchars($crumbTitle, ENT_QUOTES, 'UTF-8') ?>
								</li>
							<?php endif; ?>
						<?php endforeach; ?>

					<?php else: ?>
						<?= $breadcrumbs; ?>
					<?php endif; ?>

				<?php else: ?>
					<li class="breadcrumb-item">
						<a href="<?= PATH ?>">Главная</a>
					</li>
					<li class="breadcrumb-item active" aria-current="page">Каталог</li>
				<?php endif; ?>
			</ol>
		</nav>
		<!--end-breadcrumbs-->

		<?php
		$pageH1 = '';

		if (!empty($h1)) {
			$pageH1 = $h1;
		} elseif (!empty($cats["name"])) {
			$pageH1 = $cats["name"];
		} elseif (!empty($this->meta['title'])) {
			$pageH1 = $this->meta['title'];
		} else {
			$pageH1 = 'Каталог';
		}
		?>

		<section class="align-items-center">
            <h1 class="h2 mb-3 mb-md-0 me-3">
				<?= htmlspecialchars($pageH1, ENT_QUOTES, 'UTF-8') ?>
			</h1>
        </section>

		<div class="catalog-description mb-4">
			<p>
				<strong>Каталог шин ИТС-Центр</strong> — это широкая номенклатура качественных комплектующих для <strong>складской, строительной и спецтехники</strong>.
				Здесь вы найдёте <strong>индустриальные шины</strong> для погрузчиков и экскаваторов, <strong>шины для квадроциклов (ATV)</strong>,
				<strong>колёсные диски</strong>, <strong>камеры и ободные ленты</strong>, а также <strong>фильтры</strong> для двигателей, гидравлики и топливной системы.
				Все товары подобраны с учётом требований производителей техники и условий эксплуатации.
			</p>
			<p>
				В нашем ассортименте представлена продукция ведущих мировых брендов: <strong>EKKA</strong>, <strong>IST</strong>, <strong>Superguider</strong>,
				<strong>SOLID STAR</strong>, <strong>HERADE</strong>, <strong>HUITON</strong>, <strong>HALITRAX</strong>, <strong>Power Advance</strong>,
				<strong>Jantsa</strong>, <strong>LANTIAN</strong> и многих других. Мы сотрудничаем только с проверенными поставщиками, гарантируя
				<strong>оригинальное качество</strong> и <strong>долгий срок службы</strong> каждой позиции.
			</p>
			<p>
				Удобная навигация по разделам позволит вам быстро найти нужный товар: выберите категорию, уточните параметры с помощью фильтров
				или воспользуйтесь <strong>подбором по технике</strong> и <strong>по размеру</strong>. Для вашего удобства на сайте реализованы
				<strong>сравнение товаров</strong>, <strong>корзина</strong> и <strong>личный кабинет</strong> с историей заказов.
			</p>
			<p>
				Мы осуществляем <strong>доставку по всей территории Российской Федерации</strong>, включая <strong>Москву</strong>,
				<strong>Луганск</strong> и <strong>Донецк</strong>. Для оптовых клиентов и юридических лиц действуют специальные условия
				и система накопительных скидок. Если у вас возникли вопросы — наши менеджеры всегда готовы помочь с выбором и комплектацией заказа.
			</p>
		</div>

		<?php if (!empty($cats) && !empty($cats->top_content)): ?>
			<div class="catalog-top-block mb-4">
				<div class="catalog-top-text">
					<?= $renderSeoCategoryText($cats->top_content); ?>
				</div>
			</div>
		<?php endif; ?>
 
        <div class="prdt-top">
            <div class="col-md-12">                
				<div class="row menu-cat">
					<?php foreach($category as $cat): ?>
						<?php
						$catImg = trim((string)($cat["img"] ?? ''));
						$catImgSrc = $catImg !== ''
							? '/images/category/baseimg/' . $catImg
							: '/images/no_image.jpg';
						?>
						<a href="<?php							
							if($cat->type_id == 1) { 
								$parent = \R::findOne('category', 'parent_id = ?', [$cat["id"]]);
								if($parent){ echo "catalog/".$cat["alias"].""; }else{ echo "category/".$cat["alias"].""; }
							}else{ echo "category/".$cat["alias"].""; } ?>" title="<?= htmlspecialchars($cat["name"], ENT_QUOTES, 'UTF-8') ?>" class="col-md-3">
							<div class="p_cat">
								<div class="cb-img">
									<img src="<?= htmlspecialchars($catImgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($cat["name"], ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($cat["name"], ENT_QUOTES, 'UTF-8') ?>">
								</div>
								<div class="cb-span">
									<h2><?= htmlspecialchars($cat["name"], ENT_QUOTES, 'UTF-8') ?></h2>
								</div>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
            </div>
            <div class="clearfix"></div>
        </div>

		<?php if (!empty($cats) && !empty($cats->content)): ?>
			<div class="catalog_text">
				<?= $renderSeoCategoryText($cats->content); ?>
			</div>
		<?php endif; ?>
    </div>
</div>
<!--product-end-->
