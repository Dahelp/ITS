<?php $curr = \ishop\App::$app->getProperty('currency'); ?>
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
					<?php if($bookmarks): ?>
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
								<?php foreach($bookmarks as $item): ?>									
									<tr>
										<td class="footable-first-visible" style="display: table-cell;"><img src="images/product/mini/<?=$item["img"]?>" /></td>
										<td style="display: table-cell;"><?=$item["article"]?></td>
										<td style="display: table-cell;"><?=$item["name"]?></td>
										<td style="display: table-cell;"><?=$item["quantity"]?></td>
										<td style="display: table-cell;">
											<?php $prod_priceopt = \R::getRow('SELECT company.tip, company_typeprice.znachenie FROM company, company_typeprice WHERE company.id = company_typeprice.company_id AND company.user_id = ? AND company_typeprice.category_id = ?', [$_SESSION['user']['id'], $item["category_id"]]); ?>
											<?php if($prod_priceopt["tip"]!=2):	?>
												<?=$curr['symbol_left'];?> <?=$item["price"] * $curr['value'];?> <?=$curr['symbol_right'];?>
											<?php else: ?>
												<?=$curr['symbol_left'];?> <?=$item["price"] * $curr['value'];?> <?=$curr['symbol_right'];?>
												<br>( Опт: <?=$curr['symbol_left'];?>
												<?php if($prod_priceopt["znachenie"] =="" ) { ?>
													<?=$item["opt_price"] * $curr['value'];?>
												<?php }else{ ?>
													<?php $price_nds = round($item["price"] - ($item["price"]/1.2), 0) * 6; $price_opt = $price_nds - (($price_nds/100) * $prod_priceopt["znachenie"]); echo $opt = round($price_opt / 6) * 6; ?>
												<?php } ?>
												<?=$curr['symbol_right'];?>	)
											<?php endif; ?>
										</td>
										<td style="display: table-cell;" class="btn-korz">
											<?php if($item["quantity"]>0) { ?>
												<a data-id="<?=$item["product_id"]?>" class="btn btn-danger btn-shadow btn-cart add-to-cart-link korzina-<?=$item["product_id"]?> clear-korzina" href="cart/add?id=<?=$item["product_id"]?>" data-max="<?=$item["quantity"]?>" data-toggle="modal" data-target="#exampleModalLive">В корзину</a>
											<?php }else{ 
												if($item["stock_status_id"]=="3") {
											?>
												<span class="nalich_postuplenie">Ожидается<br />поступление</span>
											<?php }
												if($item["stock_status_id"]=="0") {
											?>
												<span class="nalich_no">Нет в наличии</span>
											<?php } ?>
											<?php } ?>
										</td>
										<td class="text-right footable-last-visible" style="display: table-cell;">
											<a href="user/bookmarks-delete?id=<?=$item["id"]?>" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" title="Удалить закладку">
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