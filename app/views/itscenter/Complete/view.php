<?php use app\helpers\Img; ?>

<?php
$curr = \ishop\App::$app->getProperty('currency');

$AUTH      = $_SESSION['user'] ?? null;
$userId    = (is_array($AUTH) && isset($AUTH['id'])) ? (int)$AUTH['id'] : null;
$cart      = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];

$administr = null;
if ($userId) {
    $administr = \R::findOne('user', 'id = ?', [$userId]);
}

$totalPositions = !empty($prods) ? count($prods) : 0;
$isFullAvailable = ($itg_qty === $totalPositions && $totalPositions > 0);
$isPartialAvailable = ($itg_qty > 0 && $itg_qty < $totalPositions);
$isUnavailable = ($itg_qty === 0);

$effective_discount_complete = ($isFullAvailable && $discount_complete > 0) ? $discount_complete : 0;
$itog_price_complete = max(0, $price_complete - $effective_discount_complete);
?>

<span itemscope itemtype="http://schema.org/Product">
    <meta itemprop="name" content="<?=htmlspecialchars($complete->name ?? '', ENT_QUOTES)?>" />
    <span itemprop="brand" itemtype="https://schema.org/Brand" itemscope>
        <meta itemprop="name" content="<?=htmlspecialchars($vendor->name ?? \ishop\App::$app->getProperty('shop_name'), ENT_QUOTES)?>" />
    </span>

    <!--start-breadcrumbs-->
    <div class="breadcrumbs">
        <div class="container">
            <nav class="pt-4 breadcrumb-blok" aria-label="breadcrumb">
                <ol class="breadcrumb flex-lg-nowrap" itemscope itemtype="http://schema.org/BreadcrumbList">
                    <li class="breadcrumb-item">
                        <span itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem">
                            <a itemprop="item" class="text-nowrap" href="https://its-center.ru">
                                <meta itemprop="name" content="Главная">
                                <i class="fas fa-home"></i><span class="visually-hidden">Главная</span>
                                <meta itemprop="position" content="1">
                            </a>
                        </span>
                    </li>
                    <li class="breadcrumb-item">
                        <span itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem">
                            <a itemprop="item" class="text-nowrap" href="https://its-center.ru/complete">Комплекты товаров</a>
                            <meta itemprop="name" content="Комплекты товаров">
                            <link itemprop="item" href="https://its-center.ru/complete">
                            <meta itemprop="position" content="2">
                        </span>
                    </li>
                    <li class="breadcrumb-item text-nowrap active">
                        <span itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem">
                            <?=htmlspecialchars($complete->name ?? '', ENT_QUOTES)?>
                            <meta itemprop="name" content="<?=htmlspecialchars($complete->name ?? '', ENT_QUOTES)?>">
                            <link itemprop="item" href="https://its-center.ru/complete/<?=htmlspecialchars($complete->alias ?? '', ENT_QUOTES)?>">
                            <meta itemprop="position" content="3">
                        </span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <!--end-breadcrumbs-->

    <div class="single contact complete-page">
        <div class="container">
            <section>

                <section class="product-hero-its complete-hero-its">
                    <div class="product-hero-grid">

                        <!-- LEFT: GALLERY -->
                        <aside class="product-col product-col--gallery">
                            <div class="product-sticky-wrap">
                                <div class="product-card product-gallery-card">
                                    <section class="slider">
                                        <?php if (!empty($gallery)): ?>
                                            <div id="slider" class="flexslider product-flexslider-main">
                                                <ul class="slides">
                                                    <li>
                                                        <?= Img::picture(
                                                            ['fallback' => '/images/complete/baseimg/' . ltrim((string)$complete->img, '/')],
                                                            $complete->name,
                                                            \ishop\App::$app->getProperty('full_img_width') ?? 600,
                                                            \ishop\App::$app->getProperty('full_img_height') ?? 600,
                                                            ['lazy' => false, 'classPicture' => 'main-photo', 'attrs' => ['itemprop' => 'image']]
                                                        ) ?>
                                                    </li>
                                                    <?php foreach ($gallery as $item): ?>
                                                        <li>
                                                            <?= Img::picture(
                                                                ['fallback' => '/images/complete/gallery/' . ltrim((string)$item->img, '/')],
                                                                $complete->name . ' — фото',
                                                                600,
                                                                600,
                                                                ['lazy' => true, 'attrs' => ['itemprop' => 'image']]
                                                            ) ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>

                                            <div id="carousel" class="flexslider product-flexslider-thumbs">
                                                <ul class="slides">
                                                    <li>
                                                        <?= Img::picture(
                                                            ['fallback' => '/images/complete/baseimg/' . ltrim((string)$complete->img, '/')],
                                                            $complete->name . ' — превью',
                                                            120,
                                                            120,
                                                            ['lazy' => true]
                                                        ) ?>
                                                    </li>
                                                    <?php foreach ($gallery as $item): ?>
                                                        <li>
                                                            <?= Img::picture(
                                                                ['fallback' => '/images/complete/gallery/' . ltrim((string)$item->img, '/')],
                                                                $complete->name . ' — фото',
                                                                120,
                                                                120,
                                                                ['lazy' => true]
                                                            ) ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php else: ?>
                                            <div id="slider" class="flexslider product-flexslider-main">
                                                <ul class="slides">
                                                    <li>
                                                        <?= Img::picture(
                                                            ['fallback' => '/images/complete/baseimg/' . ltrim((string)$complete->img, '/')],
                                                            $complete->name,
                                                            600,
                                                            600,
                                                            ['lazy' => false, 'classPicture' => 'main-photo', 'attrs' => ['itemprop' => 'image']]
                                                        ) ?>
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

                                    <?php if (!empty($administr) && (string)$administr->groups === "1"): ?>
                                        <div class="edit_prod product-edit-link">
                                            <a target="_blank" href="<?=ADMIN?>/plagins/complete-edit?id=<?=(int)$complete->id?>">
                                                <i class="far fa-edit"></i> Редактировать
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <div class="product-main-topline">
                                        <?php if (!empty($cat_prod)): ?>
                                            <a class="product-meta" href="category/<?=htmlspecialchars($cat_prod->alias ?? '', ENT_QUOTES)?>" title="<?=htmlspecialchars($cat_prod->name ?? '', ENT_QUOTES)?>">
                                                <?=htmlspecialchars($cat_prod->name ?? '', ENT_QUOTES)?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($vendor->name)): ?>
                                            <div class="product-brand-chip">Бренд: <span><?=htmlspecialchars($vendor->name, ENT_QUOTES)?></span></div>
                                        <?php endif; ?>
                                    </div>

                                    <h1 class="product-title"><?=htmlspecialchars($complete->name ?? '', ENT_QUOTES)?></h1>

                                    <div class="product-rating-row">
                                        
                                    </div>

                                    <?php if (!empty($filters)): ?>
                                        <div class="product-quick-props">
                                            <div class="product-block-title">Краткие характеристики</div>
                                            <ul class="product-props-list">
                                                <?php foreach ($filters as $filter): ?>
                                                    <li>
                                                        <span class="prop-name"><?=htmlspecialchars($filter['title'] ?? '', ENT_QUOTES)?></span>
                                                        <span class="prop-value"><?=htmlspecialchars($filterValues[$filter['id']] ?? '-', ENT_QUOTES)?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <div class="complete-short-list">
                                        <div class="product-block-title">Состав комплекта</div>
                                        <ul class="complete-short-list__items">
                                            <?php foreach ($prods as $prod): ?>
                                                <li>
                                                    <span class="complete-short-list__name"><?=htmlspecialchars($prod['name'] ?? '', ENT_QUOTES)?></span>
                                                    <span class="complete-short-list__qty"><?= (int)$prod['qty'] ?> шт.</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    
                                </div>

                                <?php if (!empty($cat_prod) && ($cat_prod->alias ?? '') === 'atv'): ?>
                                    <div class="product-card product-help-card">
                                        <div class="product-block-title">Полезные материалы</div>
                                        <div class="Ps_content product-help-links">
                                            <ul>
                                                <li>
                                                    <a href="https://its-center.ru/articles/kak-uznat-razmer-shin-dlya-kvadrocikla" title="Как узнать размер шин для квадроцикла">
                                                        Как узнать размер шин для квадроцикла?
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </main>

                        <!-- RIGHT: BUY BOX -->
                        <aside class="product-col product-col--buy">
                            <div class="product-sticky-wrap">
                                <div class="product-card product-buy-card">

                                    <div class="product-buy-top">
                                        <div class="product-block-title">Цена и заказ</div>

                                        <span itemprop="offers" itemtype="https://schema.org/Offer" itemscope>
                                            <link itemprop="url" href="<?=PATH?>/complete/<?=htmlspecialchars($complete->alias ?? '', ENT_QUOTES)?>" />
                                            <meta itemprop="availability" content="https://schema.org/InStock" />
                                            <meta itemprop="priceCurrency" content="RUR" />
                                            <meta itemprop="itemCondition" content="https://schema.org/NewCondition" />
                                            <meta itemprop="price" content="<?=htmlspecialchars((string)($itog_price_complete * $curr['value']), ENT_QUOTES)?>" />

                                            <div class="item_price_2 product-price-box text-accent" id="base-price" data-base="<?=htmlspecialchars((string)($itog_price_complete * $curr['value']), ENT_QUOTES)?>">
                                                <?php if ($effective_discount_complete > 0): ?>
                                                    <div class="product-price-old">
                                                        <?=$curr['symbol_left'];?><?=number_format($price_complete * $curr['value'], 0, '.', ' ');?><?=$curr['symbol_right'];?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="product-price-current">
                                                    <?=$curr['symbol_left'];?><?=number_format($itog_price_complete * $curr['value'], 0, '.', ' ');?><?=$curr['symbol_right'];?>
                                                </div>
                                            </div>
                                        </span>
                                    </div>

                                    <div class="product-stock-box">
                                        <div class="product-block-title">Наличие</div>
                                        <div class="vnalichie">
                                            <?php if ($isFullAvailable): ?>
                                                <span class="nalich_ok"><i class="fas fa-check" aria-hidden="true"></i> Доступны все позиции комплекта</span>
                                            <?php elseif ($isPartialAvailable): ?>
                                                <span class="nalich_postuplenie">Доступен не полный комплект. В наличии позиций: <?=$itg_qty?></span>
                                            <?php else: ?>
                                                <span class="nalich_no"><i class="far fa-times-circle fa-tabls" aria-hidden="true"></i> Нет в наличии</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="product-order-box">
                                        <div class="product-block-title">Оформление заказа</div>
                                        <div class="product-order-actions">
                                            <?php if ($isFullAvailable): ?>
                                                <input class="form-control" style="display:none;" name="quantity" type="number" value="1" min="1" data-min="1">
                                                <a
                                                    data-id="<?=htmlspecialchars($prodid ?? '', ENT_QUOTES)?>"
                                                    data-complete="1"
                                                    data-set="<?=(int)$complete->id?>"
                                                    class="btn btn-soft-primary me-2 add-to-cart-complete korzina-<?=(int)$complete->id?> clear-korzina"
                                                    href="cart/addcomplete?id=<?=htmlspecialchars($prodid ?? '', ENT_QUOTES)?>"
                                                    data-toggle="modal"
                                                    data-target="#exampleModalLive"
                                                    onclick="try{window.ym&&ym(87229051,'reachGoal','VKORZINU')}catch(e){}; return true;"
                                                >
                                                    <i class="fas fa-cart-plus"></i> Купить комплект
                                                </a>
                                            <?php elseif ($isPartialAvailable): ?>
                                                <input class="form-control" style="display:none;" name="quantity" type="number" value="1" min="1" data-min="1">
                                                <a
                                                    data-id="<?=htmlspecialchars($prodid ?? '', ENT_QUOTES)?>"
                                                    data-complete="0"
                                                    data-set="<?=(int)$complete->id?>"
                                                    class="btn btn-warning me-2 add-to-cart-complete korzina-<?=(int)$complete->id?> clear-korzina"
                                                    href="cart/addcomplete?id=<?=htmlspecialchars($prodid ?? '', ENT_QUOTES)?>"
                                                    data-toggle="modal"
                                                    data-target="#exampleModalLive"
                                                    onclick="try{window.ym&&ym(87229051,'reachGoal','VKORZINU')}catch(e){}; return true;"
                                                >
                                                    <i class="fas fa-cart-plus"></i> Купить не полный комплект
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" type="button" disabled>Нет в наличии</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </aside>

                    </div>
                </section>

                <?php if (!empty($prods)): ?>
                    <section class="complete-items-premium">
                        <div class="complete-items-premium__head">
                            <h2>Состав комплекта</h2>
                            <p>Все позиции, входящие в комплект, с ценой и статусом наличия.</p>
                        </div>

                        <div class="row gx-3 gy-3 product-one">
                            <?php foreach ($prods as $prod): ?>
								<?php
								$pid = (int)($prod['product_id'] ?? 0);
								$alias = htmlspecialchars((string)($prod['alias'] ?? ''), ENT_QUOTES, 'UTF-8');
								$img   = htmlspecialchars((string)($prod['img'] ?? ''), ENT_QUOTES, 'UTF-8');
								$name  = htmlspecialchars((string)($prod['name'] ?? ''), ENT_QUOTES, 'UTF-8');
								$sku   = htmlspecialchars((string)($prod['sku'] ?? ''), ENT_QUOTES, 'UTF-8');

								$qtyNeed = (int)($prod['qty'] ?? 0);
								$qtyHave = (int)($prod['quantity'] ?? 0);

								$prodFull    = ($qtyHave >= $qtyNeed);
								$prodPartial = ($qtyHave > 0 && $qtyHave < $qtyNeed);

								$base  = (float)($prod['price'] ?? 0);
								$rrs   = (float)($prod['price_rrs'] ?? 0);
								$cross = null;
								$final = (float)($prod['price_complete'] ?? $base);

								$date = date("Y-m-d H:i:s");
								$prodAction = \R::findOne('actions', "product_id = ? AND hide = 'show' AND date_end > ?", [$pid, $date]);

								if ($prodAction && (int)$prodAction->product_id === $pid) {
									$typeId = (string)($prodAction['type_id'] ?? '');
									$zn     = (float)($prodAction['znachenie'] ?? 0);

									if ($typeId === "1") {
										$final = round($base * (1 - ($zn / 100)), -1);
									} elseif ($typeId === "2") {
										$final = $base - $zn;
									}

									$final = max(0.0, (float)$final);
									$cross = $base;
								} elseif ($rrs > 0 && $rrs > $base) {
									$final = $base;
									$cross = $rrs;
								}

								$rwcount = (int)\R::count('review_product', "product_id = ?", [$pid]);
								$review_prod = \R::getAll(
									"SELECT SUM(review.point) as bal
									FROM review_product
									JOIN review ON review.id = review_product.review_id
									WHERE review_product.product_id = ?",
									[$pid]
								);
								$bal  = (float)($review_prod[0]['bal'] ?? 0);
								$srew = ($rwcount > 0) ? ($bal / $rwcount) : 0;
								$srewText = number_format($srew, 1, '.', '');
								?>
								<div class="col-xl-3 col-lg-6 col-md-4 col-sm-6 mb-3">
									<div class="card product-card card-static">

										<a class="pc-media" href="product/<?= $alias ?>">
											<img
												itemprop="image"
												loading="lazy"
												src="images/product/mini/<?= $img ?>"
												alt="<?= $name ?>"
												title="<?= $name ?>">
										</a>

										<div class="pc-body">
											<div class="pc-meta">
												В комплекте: <?= $qtyNeed ?> шт.
											</div>

											<div class="pc-title">
												<a href="product/<?= $alias ?>">
													<span itemprop="name"><?= $name ?></span>
												</a>
											</div>

											<div class="pc-row pc-row--top">
												<div class="pc-rating">
													<i class="fas fa-star"></i>
													<span class="pc-rating__score"><?= $srewText ?></span>
													<span class="pc-rating__count">· <?= $rwcount ?> отзывов</span>
												</div>

												<div class="pc-sku">
													Код: <?= $sku ?>
												</div>
											</div>

											<div class="pc-row pc-row--price">
												<div class="pc-price">
													<div class="pc-price__main">
														<span class="item_price">
															<?= $curr['symbol_left']; ?> <?= ($final * $curr['value']); ?> <?= $curr['symbol_right']; ?>
														</span>
													</div>

													<?php if ($cross !== null): ?>
														<div class="pc-price__old">
															<?= $curr['symbol_left']; ?> <?= ($cross * $curr['value']); ?> <?= $curr['symbol_right']; ?>
														</div>
													<?php endif; ?>
												</div>

												<?php if ($prodFull): ?>
													<div class="pc-stock pc-stock--ok">В наличии</div>
												<?php elseif ($prodPartial): ?>
													<div class="pc-stock pc-stock--warn">Частично</div>
												<?php else: ?>
													<div class="pc-stock pc-stock--no">Нет в наличии</div>
												<?php endif; ?>
											</div>

											<div class="pc-cta">
												<a class="btn btn-outline-danger pc-buy" href="product/<?= $alias ?>">
													Перейти к товару
												</a>
											</div>
										</div>

									</div>
								</div>
							<?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Descriptions -->
                <section class="desc-prod-inner bg-light shadow">
                    <ul class="nav nav-pills p-3" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="pills-harakteristics-tab" data-toggle="pill" href="#pills-harakteristics" role="tab" aria-controls="pills-harakteristics" aria-selected="false">Характеристики</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="pills-opisanie-tab" data-toggle="pill" href="#pills-opisanie" role="tab" aria-controls="pills-opisanie" aria-selected="true">Описание</a>
                        </li>
                        <?php if (!empty($technics)): ?>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="pills-primenenie-tab" data-toggle="pill" href="#pills-primenenie" role="tab" aria-controls="pills-primenenie" aria-selected="true">Применяемость</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="pills-delivery-tab" data-toggle="pill" href="#pills-delivery" role="tab" aria-controls="pills-delivery" aria-selected="false">Доставка</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="pills-pay-tab" data-toggle="pill" href="#pills-pay" role="tab" aria-controls="pills-pay" aria-selected="false">Оплата</a>
                        </li>
                    </ul>

                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-harakteristics" role="tabpanel" aria-labelledby="pills-harakteristics-tab">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped complete-compare-table">
                                    <thead>
                                        <tr>
                                            <th style="min-width:240px;">Характеристика</th>
                                            <?php if (!empty($prods)): ?>
                                                <?php foreach ($prods as $prod): ?>
                                                    <th style="min-width:240px;"><?=htmlspecialchars($prod['name'] ?? '', ENT_QUOTES)?></th>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($attr_names)): ?>
                                            <?php foreach ($attr_names as $attr): ?>
                                                <tr>
                                                    <td class="compar-attr"><?=htmlspecialchars($attr['attribute_name'] ?? '', ENT_QUOTES)?></td>
                                                    <?php if (!empty($prods)): ?>
                                                        <?php foreach ($prods as $prod): ?>
                                                            <td>
                                                                <?=htmlspecialchars($productAttributes[$prod['product_id']][$attr['id']] ?? '-', ENT_QUOTES)?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="<?=1 + count($prods)?>">Характеристики не заполнены.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="pills-opisanie" role="tabpanel" aria-labelledby="pills-opisanie-tab" itemprop="description">
                            <?php
                            if (!empty($inseo->content)) {
								echo \ishop\App::seoreplace($inseo->content, $complete->id);
							}
							echo $complete->content ?? '';
                            ?>
                        </div>

                        <?php if (!empty($technics)): ?>
                            <div class="tab-pane fade" id="pills-primenenie" role="tabpanel" aria-labelledby="pills-primenenie-tab">
                                <table class="table table-bordered table-striped">
                                    <?php foreach ($technics as $tech): ?>
                                        <tr>
                                            <td><?=htmlspecialchars($tech["name"] ?? '', ENT_QUOTES)?></td>
                                            <td>
                                                <a href="technics/<?=htmlspecialchars($tech["alias"] ?? '', ENT_QUOTES)?>" title="Посмотреть все шины для <?=htmlspecialchars(($tech["name"] ?? '') . ' ' . ($tech["model"] ?? ''), ENT_QUOTES)?>">
                                                    <?=htmlspecialchars($tech["model"] ?? '', ENT_QUOTES)?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        <?php endif; ?>

                        <div class="tab-pane fade" id="pills-delivery" role="tabpanel" aria-labelledby="pills-delivery-tab">
                            <?= \ishop\App::options('option_dostavka'); ?>
                        </div>

                        <div class="tab-pane fade" id="pills-pay" role="tabpanel" aria-labelledby="pills-pay-tab">
                            <?= \ishop\App::options('option_oplata'); ?>
                        </div>
                    </div>
                </section>
                <!-- /Descriptions -->
            </section>
        </div>
    </div>
    <!--end-single-->
</span>