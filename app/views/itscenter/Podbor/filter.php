<?php if(!empty($products)): ?>
    <?php $curr = \ishop\App::$app->getProperty('currency'); ?>
    <?php foreach($products as $product): ?>
		<?php $inseo_prod = \R::findOne('plagins_inseo', "tip = ? AND category_id = ? AND hide = 'show'", [product, $category->id]); ?>
        <div class="col-xl-3 col-lg-6 col-md-4 col-sm-6 mb-3">
					            <div class="card product-card card-static pb-3">
									<div class="znachki">
									<?php if($product->hit) { ?>
										<div class="badge bg-warning badge-shadow">Хит</div>
									<?php } ?>
									<?php if($product->new_product) { ?>
										<div class="badge bg-success badge-shadow">Новинка</div>
									<?php } ?>
									<?php if($product->sale) { ?>
										<div class="badge bg-danger badge-shadow">Скидка</div>
									<?php } ?>
									<button class="btn-wishlist btn-sm" type="button" data-bs-toggle="tooltip" data-bs-placement="left" title="" data-bs-original-title="Add to wishlist" aria-label="Add to wishlist"><i class="far fa-heart"></i></button>
									</div>						            
						            <a class="card-img-top d-block overflow-hidden" href="product/<?=$product->alias;?>">							
							            <img src="images/product/mini/<?=$product->img;?>" alt="<?php																						
												if($inseo_prod->name) { 					
													echo $name = \ishop\App::seoreplace($inseo_prod->name, $product->id);
												}
												else { echo $product->name; }
											?>" title="<?php																				
												if($inseo_prod->name) { 					
													echo $name = \ishop\App::seoreplace($inseo_prod->name, $product->id);
												}
												else { echo $product->name; }
											?>" />
						            </a>
									<?php $cat_prod = \R::findOne('category', "id = ?", [$product->category_id]); ?>
						            <div class="card-body py-2"><span class="product-meta d-block fs-xs pb-1"><?=$cat_prod["name"]?></span>
							            <h3 class="product-title fs-sm text-truncate">
											<a href="product/<?=$product->alias;?>">
												<?php
													$inseo_prod = \R::findOne('plagins_inseo', "tip = ? AND category_id = ? AND hide = 'show'", [product, $category->id]);												
													if($inseo_prod->name) { 					
														echo $name = \ishop\App::seoreplace($inseo_prod->name, $product->id);
													}
													else { echo $product->name; }
												?>
											</a>
										</h3>
							            <div class="product-price">
											<div class="product-sku">Код: <?=$product->article;?></div>
											<div class="product-curr">
									            <span class="item_price"><?=$curr['symbol_left'];?> <?php echo round($product->price / $curr['value'], 2); ?> <?=$curr['symbol_right'];?></span>
									            <?php if($product->old_price): ?>
									            	<small><del><?=$curr['symbol_left'];?> <?php echo round($product->old_price / $curr['value'], 2); ?> <?=$curr['symbol_right'];?></del></small>
									            <?php endif; ?>
											</div>
							            </div>									
						            <?php // модификации
										$modification = \R::getAll("SELECT quantity FROM modification WHERE product_id = '".$product["id"]."'");
										if($modification) {
											foreach($modification as $item) {
												
													$quantity[$product["id"]] += $item["quantity"];												
											}
											$quantity[$product["id"]] = $quantity[$product["id"]] + $product->quantity;
										}else{
											$quantity[$product["id"]] = $product->quantity;
										}
									?>									
									<?php if($quantity[$product["id"]] > 0) { ?>
									<div class="product-btn">
										<div class="product-floating-btn">										
											<?php if($_SESSION['cart'][$product->id]) { ?>
												<a data-id="<?=$product->id;?>" class="btn btn-danger btn-shadow btn-cart add-to-cart-link korzina-<?=$product->id;?> clear-korzina" style="display:none;" href="cart/add?id=<?=$product->id;?>" data-max="<?=$quantity[$product["id"]]?>" data-toggle="modal" data-target="#exampleModalLive"><i class="fas fa-cart-plus fs-base"></i> Купить</a>
												<button class="btn btn-success btn-shadow btn-cart vkorzine-<?=$product->id?> clear-vkorzine" style="padding: 4px 10px 4px 10px;">В корзине</button>
											<?php }else{ ?>
												<a data-id="<?=$product->id;?>" class="btn btn-danger btn-shadow btn-cart add-to-cart-link korzina-<?=$product->id;?> clear-korzina" href="cart/add?id=<?=$product->id;?>" data-max="<?=$quantity[$product["id"]]?>" data-toggle="modal" data-target="#exampleModalLive"><i class="fas fa-cart-plus fs-base"></i> Купить</a>
												<button class="btn btn-success btn-shadow btn-cart vkorzine-<?=$product->id?> clear-vkorzine" style="display:none; padding: 4px 10px 4px 10px;">В корзине</button>
											<?php } ?>
										</div>
									</div>
									<div class="product-nalichie">
										<span class="btn-nalichie">В наличии: <?=$quantity[$product["id"]];?> шт.</span>
									</div>
									<?php }else{ ?>
									<div class="product-btn"></div>
									<div class="product-nonalichie">
										<span class="btn-nonalichie">Нет в наличии</span>
									</div>
									<?php } ?>
									</div>
					            </div>
				            </div>
    <?php endforeach; ?>
    <div class="clearfix"></div>
    <div class="text-center">
        <p>(<?=count($products)?> товара(ов) из <?=$total;?>)</p>
        <?php if($pagination->countPages > 1): ?>
            <?=$pagination;?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <h3>Товаров не найдено...</h3>
<?php endif; ?>
