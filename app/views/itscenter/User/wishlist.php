<?php $curr = \ishop\App::$app->getProperty('currency'); ?>
<?php
$comp_priceopt = \R::getRow(
    'SELECT tip FROM company WHERE company.user_id = ?',
    [$_SESSION['user']['id']]
);
$companyTip = $comp_priceopt['tip'] ?? null;
?>
<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
				<li class="breadcrumb-item"><a href="<?= PATH ?>/user/cabinet">Личный кабинет</a></li>
                <li class="breadcrumb-item active">Закладки</li>
            </ol>
		</nav>
    </div>
</div>
<!--end-breadcrumbs-->
<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">
            <div class="aiz-user-sidenav-wrap position-relative z-1 shadow-sm">				
				<?php new \app\widgets\cabinet\Cabinet('cabinet_tpl.php'); ?>
			</div>
			<div class="aiz-user-panel">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0 h6">Закладки</h5>
					</div>
					<div class="card-body">
					<?php if($wishlists): ?>
						<div class="table-responsive">
							<table class="table aiz-table mb-0 footable footable-1 breakpoint-xl">
								<thead>
								<tr class="footable-header">
									<th class="footable-first-visible" style="display: table-cell;">Фото</th>
									<th style="display: table-cell;">Артикул</th>
									<th style="display: table-cell;">Наименование</th>									
									<th style="display: table-cell;">Наличие</th>
									<th style="display: table-cell;">Цена</th>
									<th style="display: table-cell;"></th>
									<th class="text-right footable-last-visible" style="display: table-cell;"></th>
								</tr>
								</thead>
								<tbody>
								<?php foreach($wishlists as $item): ?>									
									<tr>
										<td class="footable-first-visible" style="display: table-cell;"><img src="images/product/mini/<?=$item["img"]?>" /></td>
										<td style="display: table-cell;"><?=$item["article"]?></td>
										<td style="display: table-cell;"><?=$item["name"]?></td>
										<td style="display: table-cell;"><?=$item["quantity"]?></td>
										<td class="wishlist-price" style="display: table-cell;">											
											<?php
												$prod_priceopt = \R::getRow(
													'SELECT company.tip, company_typeprice.znachenie
													FROM company, company_typeprice
													WHERE company.id = company_typeprice.company_id
													AND company.user_id = ?
													AND company_typeprice.category_id = ?',
													[$_SESSION['user']['id'], $item["category_id"]]
												);
											?>
											<?php if ($companyTip != 2): ?>
												<?=$curr['symbol_left'];?> <?=$item["price"] * $curr['value'];?> <?=$curr['symbol_right'];?>
											<?php else: ?>
												<?=$curr['symbol_left'];?> <?=$item["price"] * $curr['value'];?> <?=$curr['symbol_right'];?>
												<br>( Опт: <?=$curr['symbol_left'];?>
												<?php if (($prod_priceopt["znachenie"] ?? '') == '') { ?>
													<?=$item["opt_price"] * $curr['value'];?>
												<?php }else{ ?>
													<?php $price_nds = round($item["price"] - ($item["price"]/1.2), 0) * 6; $price_opt = $price_nds - (($price_nds/100) * $prod_priceopt["znachenie"]); echo $opt = round($price_opt / 6) * 6; ?>
												<?php } ?>
												<?=$curr['symbol_right'];?>	)
											<?php endif; ?>
										</td>
										<td class="btn-korz wishlist-cart-cell" style="display: table-cell;">
											<?php if ($item["quantity"] > 0) { ?>
												
												<?php $inCart = !empty($_SESSION['cart'][$item["product_id"]]); ?>

												<a data-id="<?=$item["product_id"]?>"
												class="btn btn-danger pc-buy add-to-cart-link korzina-<?=$item["product_id"]?> clear-korzina"
												href="<?=PATH?>/cart/add?id=<?=$item["product_id"]?>"
												data-max="<?=$item["quantity"]?>"
												data-bs-toggle="modal"
												data-bs-target="#exampleModalLive"
												style="<?= $inCart ? 'display:none;' : '' ?>">
												В корзину
												</a>

												<button type="button"
														class="btn btn-success pc-in-cart vkorzine-<?=$item["product_id"]?> clear-vkorzine js-open-cart"
														data-bs-toggle="modal"
														data-bs-target="#exampleModalLive"
														style="<?= $inCart ? '' : 'display:none;' ?>">
													В корзине
												</button>

											<?php } else {
												if ($item["stock_status_id"] == "3") { ?>
													<span class="nalich_postuplenie">Ожидается<br>поступление</span>
												<?php }
												if ($item["stock_status_id"] == "0") { ?>
													<span class="nalich_no">Нет в наличии</span>
												<?php }
											} ?>
										</td>
										<td class="text-right footable-last-visible wishlist-actions" style="display: table-cell;">
											<a href="user/wishlist-delete?id=<?=$item["id"]?>" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" title="Удалить закладку">
                                               <i class="far fa-trash"></i>
                                            </a>
										    <a target="_blank" href="product/<?=$item["alias"]?>" class="btn btn-soft-info btn-icon btn-circle btn-sm" title="Просмотр товара">
												<i class="far fa-eye"></i>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
								</tbody>															
							</table>
						</div>
					<?php else: ?>
						<p class="text-danger">Вы пока не добавляли товары в закладки.</p>
					<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!--product-end-->