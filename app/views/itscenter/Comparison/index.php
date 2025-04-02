<?php $curr = \ishop\App::$app->getProperty('currency'); ?>
<!--prdt-starts-->
<div class="prdt">
    <div class="container">
		<!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>							
                <li class="breadcrumb-item active">Сравнение товаров</li>
            </ol>
		</nav>
		<!--end-breadcrumbs-->
		<section class="align-items-center">
            <h1 class="h2 mb-3 mb-md-0 me-3">Сравнение товаров</h1>			
        </section>
		<?php if($_SESSION['comparison']) { ?>
		<div class="col-md-12 no-comparison">
			<div class="compar_category compar-vse"><a href="comparison" class="comparison-catvse">Все (<?php echo $_SESSION['comparison_count'];?>)</a></div><div class="comparcat">			
		<?php foreach($_SESSION['comparison_category'] as $category => $key) { ?>
			<?php if(!empty($category)) { 
				$ncat = \R::findOne('category', 'id = ?', [$category]);
			?>
				<div class="compar_category catcamp-<?=$category?>"><a href="comparison?cat_id=<?=$category?>"><?=$ncat["name"]?> (<?php echo count($_SESSION['comparison_category'][$category]);?>)</a></div>
			<?php $keys .= "$category,";
			} ?>		
		<?php } ?>
			</div>
			<a href="comparison/deletevse" class="delete-comparison btn btn-soft-primary btn-sm fw-600">Очистить сравнение</a>
		</div>
        <div class="prdt-top no-comparison">
            <div class="col-md-12">
				<div class="table-responsive">
				<table class="table">
					<tr style="display: flex;">
						<td style="width:250px;display: inline-block;padding: 0;margin: 0;border-bottom: 0px;">
							<table class="table table-comparison">
								<tr>
									<td class="compar-img" style="height: 251px;"></td>									
								</tr>
								<tr style="height:83px">
									<td class="compar-name">Наименование товара</td>									
								</tr>
								<tr style="height:40px">
									<td class="compar-name">Наличие</td>									
								</tr>
								<tr style="height:40px">
									<td class="compar-name">Цена
									<?php if($prod_priceopt["tip"]==2):	?>
									<br><span>Цена опт: </span></td>
									<?php endif; ?>
								</tr>
								<tr style="height:60px">
									<td class="compar-name"></td>									
								</tr>
								<?php 
									if($cat_id !="") { $keys = $cat_id; }else{ $keys = $keys; }
									$attr_names = \R::getAll("SELECT attribute.attribute_name FROM attribute, attribute_comparison WHERE attribute_comparison.attribute_id = attribute.id AND attribute_comparison.category_id = ? ORDER BY attribute.attribute_position ASC", [$keys]);
								?>
								<?php foreach($attr_names as $attr): ?>
								<tr style="height:40px">
									<td class="compar-attr"><?=$attr["attribute_name"]?></td>
								</tr>
								<?php endforeach; ?>
							</table>
						</td>					
				<?php foreach($_SESSION['comparison'] as $compar): ?>
					<?php if(!empty($compar)) { ?>
						<?php 
							if($cat_id !="") { $product = \R::getRow("SELECT * FROM product WHERE id = ? AND category_id = ?", [$compar, $keys]); }
							else { $product = \R::getRow("SELECT * FROM product WHERE id = ?", [$compar]); }
							if($product["id"]) {
							?>
							<?php // модификации
								$modification = \R::getAll("SELECT quantity FROM modification WHERE product_id = '".$product["id"]."'");
								if($modification) {
									foreach($modification as $item) {
										
											$quantity[$product["id"]] += $item["quantity"];												
									}
									$quantity[$product["id"]] = $quantity[$product["id"]] + $product["quantity"];
								}else{
									$quantity[$product["id"]] = $product["quantity"];
								}
							?>
								<td class="close-compartd-<?=$product["id"]?>" style="width:250px;display: inline-block;padding: 0;margin: 0;border-bottom: 0px;">
									<table class="table table-comparison">
										<tr>									
											<td class="compar-img">
												<a href="product/<?=$product["alias"];?>" title="<?=$product["name"]?>">
													<img src="images/product/mini/<?=$product["img"]?>" alt="<?=$product["name"]?>" />
												</a>
												<div class="btn btn-danger btn-close-comparison"><button class="btn-close" id="comparison-close" data-id="<?=$product["id"]?>" data-categoryid="<?=$product["category_id"]?>" type="button" aria-label="Close"></button></div>												
											</td>
										</tr>
										<tr style="height:83px">
											<td class="compar-name"><a href="product/<?=$product["alias"];?>" title="<?=$product["name"]?>"><?=$product["name"]?></a></td>
										</tr>
										<tr style="height:40px">
											<td class="compar-name">
												<?php if($quantity[$product["id"]]) { ?><span class="btn-nalichie">В наличии: <?=$quantity[$product["id"]]?> шт.</span><?php }else{ ?><span class="btn-nonalichie">Нет в наличии</span><?php } ?>
											</td>
										</tr>
										<tr style="height:40px">
											<td class="compar-name fw-bold">
											<?php $prod_priceopt = \R::getRow('SELECT company.tip, company_typeprice.znachenie FROM company, company_typeprice WHERE company.id = company_typeprice.company_id AND company.user_id = ? AND company_typeprice.category_id = ?', [$_SESSION['user']['id'], $product["category_id"]]); ?>
												<?php if($prod_priceopt["tip"]!=2):	?>
													<?=$curr['symbol_left'];?>
													<?=$product["price"] * $curr['value'];?>
													<?=$curr['symbol_right'];?>
												<?php else: ?>
													<?=$curr['symbol_left'];?>
													<?=$product["price"] * $curr['value'];?>
													<?=$curr['symbol_right'];?>
													( Опт: 
													<?=$curr['symbol_left'];?>
													<?php if($prod_priceopt["znachenie"] =="" ) { ?>
														<?=$product["opt_price"] * $curr['value'];?>
													<?php }else{ ?>														
														<?php $price_nds = round($product["price"] - ($product["price"]/1.2), 0) * 6 * $curr['value']; $price_opt = $price_nds - (($price_nds/100) * $prod_priceopt["znachenie"]); echo $opt = round($price_opt / 6) * 6; ?>
													<?php } ?>													
													<?=$curr['symbol_right'];?>
													)
												<?php endif; ?>
											</td>
										</tr>
										<tr style="height:55px">
											<td class="compar-name">										
												
												<?php if($_SESSION['cart'][$product["id"]]) { ?>
													<a data-id="<?=$product["id"];?>" class="btn btn-primary add-to-cart-link korzina-<?=$product["id"];?> clear-korzina" style="display:none;" href="cart/add?id=<?=$product["id"];?>" data-max="<?=$quantity[$product["id"]]?>" data-toggle="modal" data-target="#exampleModalLive"><i class="fas fa-cart-plus fs-base"></i> Купить</a>
													<button class="btn btn-success vkorzine-<?=$product["id"]?> clear-vkorzine">В корзине</button>
												<?php }else{ ?>
													<a data-id="<?=$product["id"];?>" class="btn btn-primary add-to-cart-link korzina-<?=$product["id"];?> clear-korzina" href="cart/add?id=<?=$product["id"];?>" data-max="<?=$quantity[$product["id"]]?>" data-toggle="modal" data-target="#exampleModalLive"><i class="fas fa-cart-plus fs-base"></i> Купить</a>
													<button class="btn btn-success vkorzine-<?=$product["id"]?> clear-vkorzine" style="display:none;">В корзине</button>
												<?php } ?>
											</td>
										</tr>
										<?php $attr_name = \R::getAll("SELECT attribute.id FROM attribute, attribute_comparison WHERE attribute_comparison.attribute_id = attribute.id AND attribute_comparison.category_id = ? ORDER BY attribute.attribute_position ASC", [$keys]); ?>
										<?php foreach($attr_name as $att): ?>
										<?php $row = \R::getRow("SELECT * FROM attribute, product_attribute WHERE product_attribute.attribute_id = attribute.id AND product_attribute.attribute_id = '".$att["id"]."' AND product_attribute.product_id = '".$product["id"]."'"); ?>
										<tr style="height:40px">
											<td class="compar-attr"><?php echo !empty($row["attribute_text"])? "".$row["attribute_text"]."": "-"?></td>
										</tr>
										<?php endforeach; ?>
									</table>
								</td>
							<?php } ?>
					<?php } ?>
				<?php endforeach; ?>
					</tr>
				</table>
				</div>
            </div>
            <div class="clearfix"></div>
        </div>
		<?php }else{ echo "<div class=\"no-compar-sess\">Товары для сравнения не добавлены! Чтобы сравнить товары, Вам необходимо нажать на карточке товара значок <i class=\"far fa-tasks\"></i>.</div>"; } ?>
		<div class="no-compar-sess-block">Товары для сравнения не добавлены! Чтобы сравнить товары, Вам необходимо нажать на карточке товара значок <i class="far fa-tasks"></i>.</div>
    </div>
</div>
<!--product-end-->
<?php //unset($_SESSION['comparison']); unset($_SESSION['comparison_count']); unset($_SESSION['comparison_category']); ?>