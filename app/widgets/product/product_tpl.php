<div class="card product-card card-static pb-3">
	<div class="znachki">
		<?php if($product["hit"]) { ?>
			<div class="badge bg-warning badge-shadow">Хит</div>
		<?php } ?>
		<?php if($product["new_product"]) { ?>
			<div class="badge bg-success badge-shadow">Новинка</div>
		<?php } ?>
		<?php if($product["sale"] == "1" && $product['price'] < $product["price_rrs"]) { ?>
			<div class="badge bg-danger badge-shadow">Скидка</div>
		<?php } ?>
		<?php if($_SESSION['user']['id']) { 
			$bookmarks = \R::count('product_bookmarks', 'product_id = ? AND user_id = ?', [$product["id"], $_SESSION['user']['id']]);
			if($bookmarks==1){
		?>
			<button id="wishlist-<?=$product["id"]?>" class="btn-wishlist2 btn-sm" type="button" data-bs-toggle="tooltip" data-bs-placement="left" title="" data-bs-original-title="Wishlist" aria-label="Wishlist"><i class="far fa-heart"></i></button>
		<?php } else { ?>
			<button id="wishlist-<?=$product["id"]?>" class="btn-wishlist btn-sm" type="button" data-id="<?=$product["id"]?>" data-userid="<?=$_SESSION['user']['id']?>" data-bs-toggle="tooltip" data-bs-placement="left" title="Добавить в избранное" data-bs-original-title="Add to wishlist" aria-label="Add to wishlist"><i class="far fa-heart"></i></button>
		<?php } ?>
		<?php } ?>
		<?php if(!$_SESSION['comparison'][$product["id"]]) { ?>
			<button id="comparison-<?=$product["id"]?>" class="btn-comparison btn-sm" type="button" data-id="<?=$product->id?>" data-categoryid="<?=$product["category_id"]?>" data-bs-toggle="tooltip" data-bs-placement="left" title="Добавить в сравнени" data-bs-original-title="Comparison" aria-label="Comparison"><i class="far fa-tasks"></i></button>
		<?php } else { ?>
			<button id="comparison-<?=$product["id"]?>" class="btn-comparison2 btn-sm" type="button" data-bs-toggle="tooltip" data-bs-placement="left" title="Добавить в сравнени" data-bs-original-title="Comparison" aria-label="Comparison"><i class="far fa-tasks"></i></button>
		<?php } ?>
	</div>						            
	<a class="card-img-top d-block overflow-hidden" href="product/<?=$product["alias"]?>">							
		<img itemprop="image" src="images/product/mini/<?=$product["img"]?>" alt="<?php																								
				if($inseo_prod["name"]) { 					
					echo $name = \ishop\App::seoreplace($inseo_prod["name"], $product["id"]);
				}
				else { echo $product["name"]; }
			?>" title="<?php																							
				if($inseo_prod["name"]) { 					
					echo $name = \ishop\App::seoreplace($inseo_prod["name"], $product["id"]);
				}
				else { echo $product["name"]; }
			?>" />
	</a>
	<?php $cat_prod = \R::findOne('category', "id = ?", [$product["category_id"]]); ?>
	<div class="card-body py-2">
		<span class="product-meta d-block fs-xs pb-1"><?=$cat_prod["name"]?></span>			
		<div class="product-title fs-sm text-truncate">
			<a href="product/<?=$product["alias"]?>">
				<span itemprop="name">
				<?php
					$inseo_prod = \R::findOne('plagins_inseo', "tip = ? AND category_id = ? AND hide = 'show'", [product, $cat_prod["id"]]);												
					if($inseo_prod["name"]) { 					
						echo $name = \ishop\App::seoreplace($inseo_prod["name"], $product["id"]);
					}
					else { echo $product["name"]; }
				?>
				</span>
				<span itemprop="description"></span>
				<link itemprop="url" href="product/<?=$product["alias"]?>">
				<meta itemprop="priceCurrency" content="RUB">
				
			</a>
		</div>
		<span class="product-review">
			<div class="rating">
			<?php $review_prod = \R::getAll("SELECT SUM(review.point) as bal FROM review_product JOIN review ON review.id = review_product.review_id WHERE review_product.product_id = ?", [$product["id"]]); ?>
			<?php $rwcount = \R::count('review_product', "product_id = ?", [$product["id"]]); ?>
			<?php if($rwcount>0) { $srew = $review_prod[0]['bal']/$rwcount; }else{ $srew = 0; } ?>
			<?php for ($i = 1; $i <= 5; $i++) { ?>
				<?php if ($srew < $i) { ?>
					<span class="fa fa-stack"><i class="far fa-star fa-stack-2x"></i></span>
				<?php } else { ?>
					<span class="fa fa-stack"><i class="fas fa-star fa-stack-2x"></i><i class="fa fa-star-o fa-stack-2x"></i></span>
				<?php } ?>
			<?php } ?>
			</div>
			<div class="rating-count"><?=$rwcount?> отзывов</div>
		</span>		
		<div class="product-info">
			<?php // модификации
				$modification = \R::getAll("SELECT quantity,price FROM modification WHERE product_id = '".$product["id"]."'");
				$rezerv = \R::findOne('in_stock', 'product_id = ? AND branch_id = ?', [$product["id"], 9]);
				if($modification) {
					foreach($modification as $item) {						
						$quantity[$product["id"]] += $item["quantity"];
						$modprice[$product["id"]] .= "".$item["price"].", ";
					}
					$quantity[$product["id"]] = $quantity[$product["id"]] + $product["quantity"] - $rezerv["quantity"];
					
					$sql_modprice[$product["id"]] = "".$product["price"].", ".$modprice[$product["id"]]."";
					$sql_modprice[$product["id"]] = rtrim($sql_modprice[$product["id"]], ', ');	
					$max[$product["id"]]=[];
					$max_price[$product["id"]]=max($max[$product["id"]]=explode(",", $sql_modprice[$product["id"]]));
				}else{
					$quantity[$product["id"]] = $product["quantity"]-$rezerv["quantity"];
				}
				
				if($modification){
			?>
				<div class="product-price">
					<div class="product-sku">Код: <?=$product["article"]?></div>
					<div class="product-curr">
						<span class="item_price"><?=$max_price[$product["id"]]?></span>
						<meta itemprop="price" content="<?=$max_price[$product["id"]]?>">
					</div>
				</div>	
						
				<?php }else{ ?>
			<div class="product-price">
				<div class="product-sku">Код: <?=$product["article"]?></div>
				<?php $ucompany = \R::getRow('SELECT company.tip, company_typeprice.znachenie FROM company, company_typeprice WHERE company.id = company_typeprice.company_id AND company.user_id = ? AND company_typeprice.category_id = ?', [$_SESSION['user']['id'], $cat_prod["id"]]); ?>
				<div class="product-curr">							
					<?php if($ucompany["tip"]!=2): ?>
					<?php 
					$date = date("Y-m-d H:i:s");
					$action = \R::findOne('actions', "product_id = ? AND hide = 'show' AND date_end > '".$date."'", [$product["id"]]);
					
					if($action->product_id): ?>
					<span class="item_price">
						<?=$curr['symbol_left'];?> <?php
							if($action['type_id'] == "1") {
										$skidka = $product['price']-($product['price'] / 100 * $action['znachenie']);
										$skidka = explode('.', $skidka);  
										$skidka = $skidka[0];
										$skidka = round($skidka, -1);
									}
									if($action['type_id'] == "2") {
										$skidka = $product['price']-$action['znachenie'];
									}
							echo $skidka * $curr['value'];
						?> <?=$curr['symbol_right'];?>
					</span>
						<del style="float: left;"><small>
							<?=$curr['symbol_left'];?>
							<?=$product["price"] * $curr['value'];?>
							<?=$curr['symbol_right'];?>
						</small></del>
					<meta itemprop="price" content="<?=$product["price"] * $curr['value']?>">
					<?php else: ?>
						<?php if($product["sale"] == "1" && $product['price'] < $product["price_rrs"]): ?>
									<span class="item_price">
										<?=$curr['symbol_left'];?>
										<?=$product["price"] * $curr['value'];?>
										<?=$curr['symbol_right'];?>
									</span>
									<del style="float: left;">
									<?=$curr['symbol_left'];?>
									<?=$product["price_rrs"] * $curr['value'];?>
									<?=$curr['symbol_right'];?>
									</del>
									<meta itemprop="price" content="<?=$product["price"] * $curr['value']?>">
								<?php else: ?>
									<span class="item_price">
										<?=$curr['symbol_left'];?>										
										<?=$product["price"] * $curr['value'];?>
										<?=$curr['symbol_right'];?>
									</span>
									<meta itemprop="price" content="<?=$product["price"] * $curr['value']?>">
								<?php endif; ?>				
					<?php endif; ?>
				<?php else: ?>
					<span class="item_price">
						<?=$curr['symbol_left'];?>
						<?=$product["price"] * $curr['value'];?>
						<?=$curr['symbol_right'];?>
					</span>
					<meta itemprop="price" content="<?=$product["price"] * $curr['value']?>">
					<br>Опт: 
					<?=$curr['symbol_left'];?>
					<?php if($ucompany["znachenie"] =="" ) { ?>
					<?=$product["opt_price"] * $curr['value'];?>
					<?php }else{ ?>
					<?php $price_nds = round($product["price"] - ($product["price"]/1.2), 0) * 6 * $curr['value']; $price_opt = $price_nds - (($price_nds/100) * $ucompany["znachenie"]); echo $opt = round($price_opt / 6) * 6; ?>
					<?php } ?>
					<?=$curr['symbol_right'];?>
				<?php endif; ?>
				</div>
			</div>									
		<?php } ?>
		<?php if($quantity[$product["id"]] > 0) { ?>
		<div class="product-btn">
			<div class="product-floating-btn">										
				<?php if($_SESSION['cart'][$product["id"]]) { ?>
					<input class="form-control detail-quantity me-2 korzina-<?=$product->id;?> clear-korzina" style="display:none;caret-color:transparent;" name="quantity" type="hidden" value="1" min="1" max="<?=$itog_qty?>" data-max="<?=$itog_qty?>" data-min="1">
					<a data-id="<?=$product["id"]?>" class="btn btn-danger <?php if($modification) { ?>add-to-cart-mod<?php }else{ ?>add-to-cart-link<?php } ?> korzina-<?=$product["id"]?> clear-korzina" style="display:none;" href="cart/add?id=<?=$product["id"]?>" data-max="<?=$quantity[$product["id"]]?>" data-toggle="modal" data-target="#exampleModalLive" onclick="ym(87229051,'reachGoal','VKORZINU'); return true;">Купить</a>
					<button class="btn btn-success vkorzine-<?=$product["id"]?> clear-vkorzine">В корзине</button>
				<?php }else{ ?>
					<input class="form-control detail-quantity me-2 korzina-<?=$product->id;?> clear-korzina" style="caret-color:transparent;" name="quantity" type="hidden" value="1" min="1" max="<?=$itog_qty?>" data-max="<?=$itog_qty?>" data-min="1">                  
					<a data-id="<?=$product["id"]?>" class="btn btn-danger <?php if($modification) { ?>add-to-cart-mod<?php }else{ ?>add-to-cart-link<?php } ?> korzina-<?=$product["id"]?> clear-korzina" href="cart/add?id=<?=$product["id"]?>" data-max="<?=$quantity[$product["id"]]?>" data-toggle="modal" data-target="#exampleModalLive" onclick="ym(87229051,'reachGoal','VKORZINU'); return true;">Купить</a>
					<button class="btn btn-success vkorzine-<?=$product["id"]?> clear-vkorzine" style="display:none;">В корзине</button>
				<?php } ?>
				<input type="hidden" class="modification" value="<?=$product["id"]?>" name="modification" />
			</div>
		</div>
	</div>
	<?php if($quantity[$product["id"]] > 0) { ?>
		<link itemprop="availability" href="http://schema.org/InStock">
	<?php }else{ ?>
		<link itemprop="availability" href="http://schema.org/OutOfStock">
	<?php } ?>
	<div class="product-nalichie">
		<span class="btn-nalichie">В наличии: <?=$quantity[$product["id"]]?> шт.</span>
	</div>
	<?php } ?>
	<?php if($quantity[$product["id"]] < 0) { ?>
		<div class="product-btn"></div>
		</div>
		<div class="product-nonalichie">		
			<span class="btn-nonalichie">Нет в наличии</span>		
		</div>
	<?php } ?>
	<?php if($quantity[$product["id"]] == 0) { ?>
	<div class="product-btn"></div>
	</div>
	<div class="product-nonalichie">
		<?php if($product["stock_status_id"]==0) { ?>
		<span class="btn-nonalichie">Нет в наличии</span>
		<?php } ?>
		<?php if($product["stock_status_id"]==1) { ?>
		<span class="btn-nonalichie">Нет в наличии</span>
		<?php } ?>
		<?php if($product["stock_status_id"]==2) { ?>
		<span class="btn-nonalichie">Под заказ</span>
		<?php } ?>
		<?php if($product["stock_status_id"]==3) { ?>
		<span class="btn-postuplenie">Ожидается поступление</span>
		<?php } ?>
	</div>
	<?php } ?>
	
	</div>
</div>
				            