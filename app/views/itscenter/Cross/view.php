<?php
// --- SAFE SESSION LOCALS (без хелперов) ---
$AUTH = $_SESSION['user'] ?? null;
$userId = (is_array($AUTH) && isset($AUTH['id'])) ? (int)$AUTH['id'] : null;
$userName = (is_array($AUTH) && isset($AUTH['name'])) ? (string)$AUTH['name'] : '';

$cart = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];
$comparison = (isset($_SESSION['comparison']) && is_array($_SESSION['comparison'])) ? $_SESSION['comparison'] : [];
$comparison_count = isset($_SESSION['comparison_count']) ? (int)$_SESSION['comparison_count'] : 0;

// --- SAFE CALCS для модов/наличия ---
$sum_mods = 0;
if (!empty($mods)) {
    foreach ($mods as $m) { $sum_mods += (int)$m->quantity; }
}
$itog_qty = (int)$product->quantity;
if (property_exists($product, 'reserve')) {
    $itog_qty = max(0, (int)$product->quantity - (int)$product->reserve);
}
?>
<span itemscope itemtype="http://schema.org/Product">
<meta itemprop="name" content="<?php echo "".$product->name." аналог фильтра ".$cross["cross_name"]." ".$crossvendor->name.""; ?>" />
<span itemprop="brand" itemtype="https://schema.org/Brand" itemscope>
	<meta itemprop="name" content="<?=$vendor->name?>" />
</span>
<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <!--start-breadcrumbs-->
		<nav class="pt-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap" itemscope="" itemtype="http://schema.org/BreadcrumbList">
				<li class='breadcrumb-item'>
					<span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'>
						<a itemprop='item' class='text-nowrap' href='https://its-center.ru'>
							<meta itemprop='name' content='Главная'><i class='fas fa-home'></i><span class="visually-hidden">Главная</span>
							<meta itemprop='position' content='1'>
						</a>
					</span>
				</li>
				<li class='breadcrumb-item'>
					<span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'>
						<a itemprop='item' class='text-nowrap' href='https://its-center.ru/catalog'>Каталог</a>
						<meta itemprop='name' content='Каталог'>
						<link itemprop='item' href='https://its-center.ru/catalog'>
						<meta itemprop='position' content='2'>
					</span>
				</li>
				<li class='breadcrumb-item' data-id='3'>
					<span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'>
						<a itemprop='item' href='https://its-center.ru/category/filtry'>
							<span itemprop='name'>Фильтры</span>
							<meta itemprop='position' content='3'>
						</a>
					</span>
				</li>
				<li class='breadcrumb-item' data-id='3'>
					<span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'>
						<a itemprop='item' href='https://its-center.ru/category/<?=$cat_prod->alias?>'>
							<span itemprop='name'><?=$cat_prod->name?></span>
							<meta itemprop='position' content='4'>
						</a>
					</span>
				</li>
				<li class='breadcrumb-item text-nowrap active' data-id='7'>
					<span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'><?php echo "".$product->name." аналог фильтра ".$cross["cross_name"]." ".$crossvendor->name.""; ?>
						<meta itemprop='name' content='<?php echo "".$product->name." аналог фильтра ".$cross["cross_name"]." ".$crossvendor->name.""; ?>'>
						<link itemprop='item' href='https://its-center.ru/product/<?=$product->alias?>'>
						<meta itemprop='position' content='5'>
					</span>
				</li>
			</ol>
		</nav>
    </div>
</div>
<!--end-breadcrumbs-->
<?php
    $curr = \ishop\App::$app->getProperty('currency');
    $cats = \ishop\App::$app->getProperty('cats');
?>
<!--start-single-->
<div class="single contact cross-page">
    <div class="container">
        
        <!-- Content-->
        <!-- Product Gallery + description-->
        <?php
			$crossTitle = $product->name . ' аналог фильтра ' . $cross["cross_name"] . ' ' . $crossvendor->name;

			$price = (float)$product->price;

			$prod_priceopt = [];
			if ($userId) {
				$prod_priceopt = \R::getRow(
					'SELECT company.tip, company_typeprice.znachenie
					FROM company, company_typeprice
					WHERE company.id = company_typeprice.company_id
					AND company.user_id = ?
					AND company_typeprice.category_id = ?',
					[$userId, $cat_prod->id]
				) ?: [];
			}

			$filters = \R::getAll(
				'SELECT attribute_group.title, attribute_group.url_params, attribute_value.value, attribute_value.alias
				FROM attribute_group, attribute_category, attribute_product, attribute_value
				WHERE attribute_category.group_id = attribute_group.id
				AND attribute_product.attr_id = attribute_value.id
				AND attribute_value.attr_group_id = attribute_group.id
				AND attribute_product.product_id = ?
				GROUP BY attribute_group.title, attribute_group.url_params, attribute_value.value, attribute_value.alias',
				[$product->id]
			);
			?>

			<section class="product-hero-its">
			<div class="product-hero-grid">

				<!-- LEFT: GALLERY -->
				<aside class="product-col product-col--gallery">
				<div class="product-sticky-wrap">
					<div class="product-card product-gallery-card">
					<section class="slider">
						<?php if($gallery): ?>
						<div id="slider" class="flexslider product-flexslider-main">
							<ul class="slides">
							<li>
								<img itemprop="image"
									class="main-photo"
									src="images/product/baseimg/<?=$product->img;?>"
									alt="<?=$crossTitle;?>">
							</li>
							<?php foreach($gallery as $item): ?>
								<li>
								<img itemprop="image"
									src="images/product/gallery/<?=$item->img;?>"
									alt="<?=$crossTitle;?>">
								</li>
							<?php endforeach; ?>
							</ul>
						</div>

						<div id="carousel" class="flexslider product-flexslider-thumbs">
							<ul class="slides">
							<li>
								<img itemprop="image"
									src="images/product/baseimg/<?=$product->img;?>"
									alt="<?=$crossTitle;?>">
							</li>
							<?php foreach($gallery as $item): ?>
								<li>
								<img itemprop="image"
									src="images/product/gallery/<?=$item->img;?>"
									alt="<?=$crossTitle;?>">
								</li>
							<?php endforeach; ?>
							</ul>
						</div>
						<?php else: ?>
						<div id="slider" class="flexslider product-flexslider-main">
							<ul class="slides">
							<li>
								<img itemprop="image"
									class="main-photo"
									src="https://its-center.ru/images/product/baseimg/<?=$product->img;?>"
									alt="<?=$crossTitle;?>">
							</li>
							</ul>
						</div>
						<?php endif; ?>
					</section>
					</div>
				</div>
				</aside>

				<!-- CENTER: MAIN INFO -->
				<main class="product-col product-col--main">
				<div class="product-sticky-wrap">
					<div class="product-card product-main-card">

					<?php if ($userId): ?>
						<?php $administr = \R::findOne('user', 'id = ?', [$userId]); ?>
						<?php if ($administr && (string)$administr->groups === "1"): ?>
						<div class="edit_prod product-edit-link">
							<a target="_blank" href="<?= ADMIN ?>/product/edit?id=<?=$product->id?>">
							<i class="far fa-edit"></i> Редактировать
							</a>
						</div>
						<?php endif; ?>
					<?php endif; ?>

					<div class="product-main-topline">
						<a class="product-meta" href="category/<?=$cat_prod->alias?>" title="<?=$cat_prod->name?>">
						<?=$cat_prod->name?>
						</a>

						<?php if (!empty($vendor->name)): ?>
						<div class="product-brand-chip">Бренд: <span><?=$vendor->name?></span></div>
						<?php endif; ?>
					</div>

					<h1 class="product-title"><?=$crossTitle;?></h1>

					<div class="product-rating-row">
						<div class="product-sku-inline">
						Фильтр аналог: <strong><?=$cross["cross_name"]?> <?=$crossvendor->name?></strong>
						</div>

						<?php if (!empty($cross["cross_abbreviated_name"])): ?>
						<div class="product-sku-inline">
							Краткое наименование: <strong><?=$cross["cross_abbreviated_name"]?></strong>
						</div>
						<?php endif; ?>
					</div>

					<div class="product-quick-props">
						<div class="product-block-title">Краткие характеристики</div>
						<ul class="product-props-list">
						<?php foreach($filters as $filter): ?>
							<li>
							<span class="prop-name"><?=$filter['title']?></span>
							<span class="prop-value">
								<?php if(!empty($filter['url_params'])): ?>
								<a href="<?=h(\app\services\filters\FilterUrlHelper::buildCategoryFilterPath((string)$cat_prod->alias, (string)$filter['alias']))?>" title="<?=h($filter['value'])?>">
									<?=h($filter['value'])?>
								</a>
								<?php else: ?>
								<?=h($filter['value'])?>
								<?php endif; ?>
							</span>
							</li>
						<?php endforeach; ?>

						<li>
							<span class="prop-name">Модель</span>
							<span class="prop-value">
							<a href="product/<?=$product->alias?>" title="<?=$product->name?>">
								<?=$product->model?>
							</a>
							</span>
						</li>

						<li>
							<span class="prop-name">ID товара</span>
							<span class="prop-value"><?=$product->sku?></span>
						</li>
						</ul>
					</div>

					</div>
				</div>
				</main>

				<!-- RIGHT: BUY BOX -->
				<aside class="product-col product-col--buy">
				<div class="product-sticky-wrap">
					<div class="product-card product-buy-card">

					<span itemprop="offers" itemtype="https://schema.org/Offer" itemscope>
						<link itemprop="url" href="<?=PATH?>/cross/<?=rawurlencode(mb_strtolower($cross["cross_abbreviated_name"]))?>" />
						<meta itemprop="availability" content="https://schema.org/InStock" />
						<meta itemprop="priceCurrency" content="RUR" />
						<meta itemprop="itemCondition" content="https://schema.org/UsedCondition" />
						<meta itemprop="price" content="<?=$price * $curr['value'];?>" />
						<meta itemprop="priceValidUntil" content="<?=$product->data_edit_price?>" />

						<div class="product-buy-top">
						<div class="product-block-title">Цена и заказ</div>

						<div class="item_price_2 product-price-box text-accent" id="base-price" data-base="<?=$price * $curr['value'];?>">
							<?php if( (isset($prod_priceopt["tip"]) ? (int)$prod_priceopt["tip"] : 0) != 2): ?>
							<?php if(isset($action) && is_object($action) && (int)$action->product_id === (int)$product->id): ?>
								<?php
								if((string)$action->type_id === "1") {
									$sk = $product->price - ($product->price / 100 * (float)$action->znachenie);
									$sk = round($sk, -1);
								} elseif((string)$action->type_id === "2") {
									$sk = $product->price - (float)$action->znachenie;
								} else {
									$sk = $product->price;
								}
								?>
								<div class="product-price-old">
								<?=$curr['symbol_left'];?><?=$price * $curr['value'];?><?=$curr['symbol_right'];?>
								</div>
								<div class="product-price-current">
								<?=$curr['symbol_left'];?><?=$sk * $curr['value'];?><?=$curr['symbol_right'];?>
								</div>
							<?php else: ?>
								<?php if((string)$product->sale === "1" && (float)$price < (float)$product->price_rrs): ?>
								<div class="product-price-current">
									<?=$curr['symbol_left'];?><?=$price * $curr['value'];?><?=$curr['symbol_right'];?>
								</div>
								<div class="product-price-old">
									<?=$curr['symbol_left'];?><?=$product->price_rrs * $curr['value'];?><?=$curr['symbol_right'];?>
								</div>
								<?php else: ?>
								<div class="product-price-current">
									<?=$curr['symbol_left'];?><?=$price * $curr['value'];?><?=$curr['symbol_right'];?>
								</div>
								<?php endif; ?>
							<?php endif; ?>
							<?php else: ?>
							<div class="product-price-current">
								<?=$curr['symbol_left'];?><?=$price * $curr['value'];?><?=$curr['symbol_right'];?>
							</div>

							<div class="product-price-opt">
								<span>Опт:</span>
								<strong>
								<?=$curr['symbol_left'];?>
								<?php if(empty($prod_priceopt["znachenie"])) { ?>
									<?= (float)$product->opt_price * $curr['value']; ?>
								<?php } else {
									$price_nds = round($product->price - ($product->price/1.2), 0) * 6 * $curr['value'];
									$price_opt = $price_nds - (($price_nds/100) * (float)$prod_priceopt["znachenie"]);
									echo $opt = ceil($price_opt / 6) * 6;
								} ?>
								<?=$curr['symbol_right'];?>
								</strong>
							</div>
							<?php endif; ?>
						</div>

						<?php if($product->quantity > 0): ?>
							<div class="current_price_success product-price-date">Цена актуальна на: <?=$product->data_edit_price?></div>
						<?php else: ?>
							<div class="current_price_warning product-price-date">Цена может отличаться при заказе</div>
						<?php endif; ?>
						</div>

						<div class="product-stock-box">
						<div class="product-block-title">Наличие</div>
						<div class="vnalichie">
							<?php if($product->quantity == 0): ?>
							<?php if((int)$product->stock_status_id === 0): ?>
								<span class="nalich_no"><i class="far fa-times-circle fa-tabls" aria-hidden="true"></i> Нет в наличии, о поступлении уточняйте у менеджера</span>
							<?php endif; ?>

							<?php if((int)$product->stock_status_id === 2): ?>
								<span class="nalich_no">Товар можно приобрести под заказ. Цена, наличие и срок доставки согласовываются с менеджером.</span>
							<?php endif; ?>

							<?php if((int)$product->stock_status_id === 3): ?>
								<span class="nalich_postuplenie">Ожидается поступление. Уточняйте у менеджера.</span>
							<?php endif; ?>
							<?php else: ?>
							<?php if((int)$product->reserve > 0): ?>
								<span class="nalich_ok"><i class="fas fa-check" aria-hidden="true"></i> Свободное наличие: <?=$itog_qty?></span> (В резерве: <?= (int)$product->reserve ?> шт.)
							<?php else: ?>
								<span class="nalich_ok"><i class="fas fa-check" aria-hidden="true"></i> В наличии: <?=((int)$product->quantity + (int)$sum_mods)?> шт.</span>
							<?php endif; ?>
							<?php endif; ?>
						</div>
						</div>

						<div class="product-order-box">
						<div class="product-block-title">Оформление заказа</div>
						<div class="product-order-actions">
							<?php if($product->quantity > 0): ?>
							<?php if(isset($cart[$product->id])): ?>
								<input class="form-control detail-quantity me-2 korzina-<?=$product->id;?> clear-korzina" style="display:none;caret-color:transparent;" name="quantity" type="number" value="1" min="1" max="<?=$product->quantity?>" data-max="<?=$product->quantity?>" data-min="1">
								<a data-id="<?=$product->id;?>" class="btn btn-soft-primary me-2 add-to-cart-link korzina-<?=$product->id;?> clear-korzina" style="display:none;" href="cart/add?id=<?=$product->id;?>" data-max="<?=$product->quantity?>" data-toggle="modal" data-target="#exampleModalLive"><i class="fas fa-cart-plus"></i> В корзину</a>
								<button class="btn btn-warning one-click korzina-<?=$product->id;?> clear-korzina" style="display:none;" type="submit">Купить в 1 клик</button>
								<button href="cart/show" onclick="getCart(); return false;" class="btn btn-success vkorzine-<?=$product->id;?> clear-vkorzine">В корзине</button>
							<?php else: ?>
								<input class="form-control detail-quantity me-2 korzina-<?=$product->id;?> clear-korzina" name="quantity" type="number" value="1" min="1" max="<?=$product->quantity?>" data-max="<?=$product->quantity?>" data-min="1">
								<a data-id="<?=$product->id;?>" class="btn btn-soft-primary me-2 add-to-cart-link korzina-<?=$product->id;?> clear-korzina" href="cart/add?id=<?=$product->id;?>" data-max="<?=$product->quantity?>" data-toggle="modal" data-target="#exampleModalLive"><i class="fas fa-cart-plus"></i> В корзину</a>
								<button class="btn btn-warning one-click korzina-<?=$product->id;?> clear-korzina" type="submit" data-toggle="modal" data-target="#Modalclick">Купить в 1 клик</button>
								<button href="cart/show" onclick="getCart(); return false;" class="btn btn-success vkorzine-<?=$product->id;?> clear-vkorzine" style="display:none;">В корзине</button>
							<?php endif; ?>
							<?php endif; ?>
						</div>
						</div>

					</span>

					<div class="product-side-tools">
						<button class="btn btn-primary product-kp-btn" id="btnpdf" type="button">
						<i class="fas fa-print"></i> Коммерческое предложение
						</button>

						<div class="product-icon-actions">
							<?php if ($userId): ?>
								<?php $wishlisted = \R::count('product_wishlists', 'product_id = ? AND user_id = ?', [$product->id, $userId]) > 0; ?>
								<button
									id="wishlist-<?= (int)$product->id ?>"
									class="pc-iconbtn js-wishlist <?= $wishlisted ? 'is-active' : '' ?>"
									type="button"
									data-id="<?= (int)$product->id ?>"
									data-userid="<?= (int)$userId ?>"
									title="<?= $wishlisted ? 'В избранном' : 'Добавить в избранное' ?>"
									aria-label="Избранное">
									<i class="<?= $wishlisted ? 'fas fa-heart' : 'far fa-heart' ?>"></i>
								</button>
							<?php endif; ?>

							<?php $inCompare = !empty($comparison[$product->id]); ?>
							<button
								id="comparison-<?= (int)$product->id ?>"
								class="pc-iconbtn js-compare <?= $inCompare ? 'is-active' : '' ?>"
								type="button"
								data-id="<?= (int)$product->id ?>"
								data-categoryid="<?= (int)$product->category_id ?>"
								title="<?= $inCompare ? 'В сравнении' : 'Добавить в сравнение' ?>"
								aria-label="Сравнение">
								<i class="far fa-chart-bar"></i>
							</button>
						</div>
					</div>

					</div>
				</div>
				</aside>

			</div>
			</section>

		  <!-- Descriptions -->
			<section class="desc-prod-inner bg-light shadow">
			<ul class="nav nav-pills p-3" id="pills-tab" role="tablist">
				<li class="nav-item" role="presentation">
					<a class="nav-link active" id="pills-harakteristics-tab" data-toggle="pill" href="#pills-harakteristics" role="tab" aria-controls="pills-harakteristics" aria-selected="false">Характеристики</a>
				</li>
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="pills-opisanie-tab" data-toggle="pill" href="#pills-opisanie" role="tab" aria-controls="pills-opisanie" aria-selected="true">Описание</a>
				</li>
			<?php if($cross) { ?>
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="pills-analog-tab" data-toggle="pill" href="#pills-analog" role="tab" aria-controls="pills-analog" aria-selected="false">Аналоги</a>
				</li>
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="pills-oem-tab" data-toggle="pill" href="#pills-oem" role="tab" aria-controls="pills-oem" aria-selected="false">OEM номера</a>
				</li>
			<?php } ?>
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="pills-delivery-tab" data-toggle="pill" href="#pills-delivery" role="tab" aria-controls="pills-delivery" aria-selected="false">Доставка</a>
				</li>
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="pills-pay-tab" data-toggle="pill" href="#pills-pay" role="tab" aria-controls="pills-pay" aria-selected="false">Оплата</a>
				</li>
			<?php if($product->url_video) { ?>
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="pills-video-tab" data-toggle="pill" href="#pills-video" role="tab" aria-controls="pills-video" aria-selected="false">Видео</a>
				</li>
			<?php } ?>
			</ul>
			<div class="tab-content" id="pills-tabContent">
				<div class="tab-pane fade show active" id="pills-harakteristics" role="tabpanel" aria-labelledby="pills-harakteristics-tab">
				<!-- harakteristics -->
					<?php if($attribute_group): ?>
						<table class="table table-bordered table-striped">
							<?php foreach($attribute_group as $group): ?>
								<thead>
									<tr>
										<td colspan="2" class="hide_td">
											<div class="hide_td_1"><strong><?=$group["attribute_name"]?></strong></div>
										</td>
									</tr>
								</thead>
								<tbody>
									<?php
									$atts = \R::getAll(
										"SELECT a.attribute_name, pa.attribute_text
										FROM attribute a
										JOIN product_attribute pa ON pa.attribute_id = a.id
										WHERE pa.product_id = ? AND pa.attribute_group_id = ?
										ORDER BY a.attribute_position",
										[$product->id, $group["attribute_group_id"]]
									);
									?>
									<?php foreach($atts as $att): ?>
										<tr>
											<td><?=$att["attribute_name"]?></td>
											<td><?=$att["attribute_text"]?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							<?php endforeach; ?>
						</table>
					<?php endif; ?>
				<!-- /harakteristics -->
				</div>
				<div class="tab-pane fade" id="pills-opisanie" role="tabpanel" aria-labelledby="pills-opisanie-tab" itemprop="description">
					<p>Аналогом для фильтра <?php echo "".$cross["cross_name"]." ".$crossvendor->name.""; ?> является <?=$product->name?>.</p>
					<p>Фильтр EKKA (Польский бренд) соответствует европейским стандартам. Полностью совпадает по всем характеристикам с оригиналом. Успешно прошёл испытания в России, получен сертификат соответствия.</p>
					<p>Купить фильтр можно у нас в ИТС-Центр по низким ценам с доставкой во все регионы РФ. Самовывоз из 5 складов: Климовск (Московская обл.), Санкт-Петербург, Воронеж, Краснодар, Екатеринбург. Позвоните нам по телефону или напишите нам на почту, мы поможем Вам подобрать фильтр по OEM номеру, подберём по марке техники, проконсультируем по всем вопросам.</p>
				</div>
				<?php if($cross) { ?>
					<div class="tab-pane fade" id="pills-analog" role="tabpanel" aria-labelledby="pills-analog-tab">
						<?php $cross_analog = \R::getAll('SELECT plagins_cross_vendor.name, plagins_cross.cross_name, plagins_cross.cross_abbreviated_name, plagins_cross.tip_cross, plagins_cross.equipment_vendor FROM plagins_cross, plagins_cross_vendor WHERE plagins_cross.vendor_id = plagins_cross_vendor.id AND plagins_cross.equipment_vendor = ? AND plagins_cross.product_id = ? AND plagins_cross.cross_abbreviated_name != ?', [2, $product->id, $cross["cross_abbreviated_name"]]); ?>
						<table class="table table-bordered table-striped">
							<tbody>
							<?php foreach($cross_analog as $analog): ?>
								<tr><td><?=$analog["name"]?></td><td><a href="cross/<?=rawurlencode(mb_strtolower($analog["cross_abbreviated_name"]))?>" title="<?=$analog["cross_name"]?>"><?=$analog["cross_name"]?></a></td>
								<?php if($analog['tip_cross'] < 3) {
									if($analog['tip_cross'] == 1) { $analog_tip = "Внешняя часть"; }
									if($analog['tip_cross'] == 2) { $analog_tip = "Внутренняя часть"; }
									if($analog['tip_cross'] == 3) { $analog_tip = "Не определено"; }
									if($analog['tip_cross'] == 4) { $analog_tip = "Комплект из 2х частей"; }
									echo "<td>$analog_tip</td>";
								}?>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div class="tab-pane fade" id="pills-oem" role="tabpanel" aria-labelledby="pills-oem-tab">
						<?php $cross_oem = \R::getAll('SELECT plagins_cross_vendor.name, plagins_cross.cross_name, plagins_cross.cross_abbreviated_name, plagins_cross.tip_cross, plagins_cross.equipment_vendor FROM plagins_cross, plagins_cross_vendor WHERE plagins_cross.vendor_id = plagins_cross_vendor.id AND plagins_cross.equipment_vendor = ? AND plagins_cross.product_id = ? AND plagins_cross.cross_abbreviated_name != ?', [1, $product->id, $cross["cross_abbreviated_name"]]); ?>
						<table class="table table-bordered table-striped">
							<tbody>
							<?php foreach($cross_oem as $oem): ?>
								<tr><td><?=$oem["name"]?></td><td><a href="cross/<?=rawurlencode(mb_strtolower($oem["cross_abbreviated_name"]))?>" title="<?=$oem["cross_name"]?>"><?=$oem["cross_name"]?></a></td>
								<?php if($oem['tip_cross'] < 3) {
									if($oem['tip_cross'] == 1) { $oem_tip = "Внешняя часть"; }
									if($oem['tip_cross'] == 2) { $oem_tip = "Внутренняя часть"; }
									if($oem['tip_cross'] == 3) { $oem_tip = "Не определено"; }
									if($oem['tip_cross'] == 4) { $oem_tip = "Комплект из 2х частей"; }
									echo "<td>$oem_tip</td>";
								} ?>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php } ?>
				<div class="tab-pane fade" id="pills-delivery" role="tabpanel" aria-labelledby="pills-delivery-tab">
				<!-- dostavka -->
					<?php echo $op_dostavka = \ishop\App::options('option_dostavka'); ?>
				<!-- /dostavka-->
				</div>
				<!-- oplata -->
				<div class="tab-pane fade" id="pills-pay" role="tabpanel" aria-labelledby="pills-pay-tab">
					<?php echo $op_oplata = \ishop\App::options('option_oplata'); ?>
				</div>
				<!-- /oplata -->
				<?php if($product->url_video) { ?>
				<!-- video -->
				<div class="tab-pane fade video-tab" id="pills-video" role="tabpanel" aria-labelledby="pills-video-tab">
					<iframe class="video_frame" src="<?php parse_str( parse_url( $product->url_video, PHP_URL_QUERY ), $video ); echo "https://www.youtube.com/embed/".(isset($video['v']) ? $video['v'] : ""); ?>" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
				</div>
				<!-- /video -->
				<?php } ?>
			</div>
			</section>
		  <!-- /Descriptions -->

		  <!-- Related products-->
		  <?php if($related): ?>
		  <div class="related_prod">
          <section class="pb-5 mb-2 mb-xl-4 recomend-1">
            <h2 class="h3 pb-2 mb-grid-gutter text-center">Связанные товары</h2>
            <div class="review-wrap">
			<div class="wrap-container">
			<div class="inner-container">
			<div class="swiper-container swiper1">
				<div class="swiper-wrapper">
				<?php foreach($related as $item): ?>
					<div class="swiper-slide">
					    <?php new \app\widgets\product\Product($item, $curr, 'product_tpl.php'); ?>
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
		  <!-- /Related products-->

		  <!-- Similar products-->
		  <?php if($similar): ?>
		  <div class="related_prod">
          <section class="pb-5 mb-2 mb-xl-4 recomend-1">
            <h2 class="h3 pb-2 mb-grid-gutter text-center">Похожие товары</h2>
            <div class="review-wrap">
			<div class="wrap-container">
			<div class="inner-container">
			<div class="swiper-container swiper2">
				<div class="swiper-wrapper">
				<?php foreach($similar as $item2): ?>
					<div class="swiper-slide">
					    <?php new \app\widgets\product\Product($item2, $curr, 'product_tpl.php'); ?>
					</div>
				<?php endforeach; ?>
				</div>
				</div>
				</div>
				</div>
			</div>
			<div class="swiper-button-inner">
				<div class="swiper-button-next swiper-button-next-2"></div>
				<div class="swiper-button-prev swiper-button-prev-2"></div>
			</div>
          </section>
		  </div>
		  <?php endif; ?>
		  <!-- /Similar products-->

      </section>
    </div>
</div>
<!--end-single-->
</span>
<!-- Modal 1 click -->
<form action="/product/<?=$product->alias?>" method="post" class="modal fade" id="Modalclick" tabindex="-1" role="dialog" aria-labelledby="ModalclickLabel" aria-hidden="true" data-toggle="validator">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
            <div class="modal-header">
				<h5 class="modal-title" id="exampleModalLiveLabel">Купить в 1 клик</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
      </div>
            <div class="modal-body">
				<div class="fc-3"><?=$product->name;?>
					<input type="hidden" name="name_tovar" value="<?=$product->name;?>">
					<input type="hidden" name="product_id" value="<?=$product->id;?>">
				</div>
				<div class="fc-1">
					<div class="fc_name">Ф.И.О. <span>*</span></div>
					<div class="fc_input"><input type="text" name="fio_click" placeholder="Укажите Ф.И.О." value="<?=$userName?>" required></div>
				</div>
				<div class="fc-1">
					<div class="fc_name">Телефон <span>*</span></div>
					<div class="fc_input"><input type="tel" name="tell_click" id="phone-input2" required></div>
				</div>
				<div class="fc-1">
					<div class="fc_name">Электронная почта <span>*</span></div>
					<div class="fc_input"><input type="text" name="email_click" placeholder="Укажите адрес электронной почты" required></div>
				</div>
				<div class="fc-1">
					<div class="fc_name">Комментарий к заказу</div>
					<div class="fc_textarea"><textarea name="prim_click" placeholder="Комментарий к заказу (количество, время для обратного звонка или другую необходимую информацию)"></textarea></div>
				</div>
            </div>
            <div class="modal-footer">
                <table class="zayvka_1click"><tbody><tr><td><input type="checkbox" name="politika" value="pk" required></td><td style="font-size:14px;padding:0 0 0 20px">Я принимаю <a href="/pages/privacy" target="_blank" rel="noopener">Политику конфиденциальности</a> и даю <a href="/pages/personal-data-consent" target="_blank" rel="noopener">согласие на обработку персональных данных</a>.</td></tr></tbody></table>
				<button type="submit" name="oneclick" value="<?php echo md5(date('Y-m-d')); ?>" class="btn btn-danger">Отправить</button>
            </div>
        </div>
  </div>
</form>

<?php
use app\helpers\PdfAssets;

$IMG   = PdfAssets::imagesFor($product);
$logo  = $IMG['logo'];
$logos = $IMG['logos'];
$images= $IMG['product'];
echo "\n<!-- PDFIMG Product: topLogo=".($logo?'ok':'no')." bottomLogo=".($logos?'ok':'no')." product=".($images?'ok':'no')." -->\n";

// attrsBody из твоих переменных
$attrsBody = [];
if (!empty($attribute_group)) {
    foreach ($attribute_group as $group) {
        $attrsBody[] = [
            ['text' => (string)$group['attribute_name'], 'fontSize' => 10, 'style' => 'tableHeader'],
            ['text' => '']
        ];
        foreach ($attributs as $att) {
            $attrsBody[] = [
                ['text' => (string)$att['attribute_name'], 'fontSize' => 10, 'style' => 'tableHeader'],
                ['text' => (string)$att['attribute_text'], 'fontSize' => 10, 'style' => 'tableHeader']
            ];
        }
    }
}

$subtitle = $product->name . ' аналог фильтра ' . $cross['cross_name'] . ' ' . $crossvendor->name;

// ===== СКИДКА ДЛЯ КП (просто и жёстко) =====
$base  = (float)$product->price_rrs > 0 ? (float)$product->price_rrs : (float)$product->price; // "старая" цена
$final = (float)$product->price; // цена к оплате по умолчанию

// Акция имеет приоритет над обычной ценой
if (isset($action) && is_object($action) && (int)$action->product_id === (int)$product->id) {
    $type = (string)$action->type_id; // "1" = %, "2" = фикс
    $val  = (float)$action->znachenie;
    if ($type === '1') {
        $final = max(0, $final - ($final * ($val / 100)));
        // Если у тебя в карточке ты обычно округляешь до десятков — раскомментируй:
        // $final = round($final, -1);
    } elseif ($type === '2') {
        $final = max(0, $final - $val);
    }
}

// Итоги
$hasDiscount = ($final < $base);
$discountBadge = '';
if ($hasDiscount) {
    // если есть РРЦ, покажем процент от неё
    $percent = $base > 0 ? round(100 - ($final / $base * 100)) : 0;
    if ($percent > 0) $discountBadge = '-'.$percent.'%';
}

// Формируем строки цен
$priceStr    = $curr['symbol_left'] . ' ' . number_format($final * $curr['value'], 0, '.', ' ') . ' ' . $curr['symbol_right'];
$oldPriceStr = $hasDiscount ? ($curr['symbol_left'] . ' ' . number_format($base  * $curr['value'], 0, '.', ' ') . ' ' . $curr['symbol_right']) : '';

// Собираем payload
$kpData = [
    'title'         => 'Коммерческое предложение',
    'subtitle'      => $product->name, // или свой subtitle в Cross
    'sku'           => (string)$product->sku,
    'priceStr'      => $priceStr,
    'oldPriceStr'   => $oldPriceStr,
    'discountBadge' => $discountBadge,
    'hasDiscount'   => $hasDiscount,
    'attrsBody'     => $attrsBody,
    'images'        => ['logo'=>$logo, 'logos'=>$logos, 'product'=>$images],
];



?>
<script id="kp-data-<?= (int)$product->id ?>" type="application/json">
<?= json_encode($kpData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>
</script>

<script src="/js/pdfmake.js"></script>
<script src="/js/vfs_fonts.js"></script>
<script src="/js/kp_template.js"></script>
<script>
  (function(){
    var data = JSON.parse(document.getElementById('kp-data-<?= (int)$product->id ?>').textContent || '{}');
    KPTemplate.attach('#btnpdf', data, 'KP_<?= addslashes($product->alias) ?>.pdf');
  })();
</script>
