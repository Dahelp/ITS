
<div class="prdt-top">			
            <div class="col-md-12">
                <div class="bg-light rounded-3 py-5 px-4 px-xxl-5">
                    <div class="register-top heading">
                        <h2>Оформление заказа</h2>
                    </div> 
					
                    <div id="prodcart" class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                <tr>
                                    <th>Фото</th>
                                    <th>Наименование</th>
                                    <th>Кол-во</th>
                                    <th>Цена</th>
                                    <th><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></th>
                                </tr>
                                </thead>
                                <tbody>								
								
                                <?php foreach($_SESSION['cart'] as $id => $item): ?>
								<?php 
									if($item['set']) {
										if($item['qty'] < $item["min"]) { 
											$price = "".$item["price_complete"]."";
																						
										}
										if($item['qty'] == $item["min"]) {
											$price = $item["price_complete"] - $item["price_discount"];
											
										}
										if($item['qty'] > $item["min"]) {
											$price = $item["price_complete"] - $item["price_discount"];
											
										}
										
									}else{
										$price = "".$item["price"]."";
									}
									
								?>
                                    <tr>
                                        <td><a href="product/<?=$item['alias'] ?>"><img src="images/product/mini/<?= $item['img'] ?>" alt="<?=$item['name']?>"></a></td>
                                        <td><a href="product/<?=$item['alias'] ?>"><?=$item['name'] ?></a><?php if($item['set']) { ?><br />Комплект № <?=$item['set'];?><?php } ?></td>
                                        <td style="text-align:center">
											<?php if($item['qty'] > 1) { ?><span data-id="<?=$id;?>" <?php if($item['set']) { ?>data-min="<?=$item["min"];?>" data-set="<?=$item["set"];?>"<?php } ?> class="my-minus-<?=$id;?> <?php if(!$item['set']) { ?> my-minus-cart<?php }else{ ?> my-minus-complete-cart<?php } ?>"><i class="fa fa-minus" aria-hidden="true"></i></span><?php } ?>
												<span class="qty-item"><?=$item['qty'];?></span>
											<?php if($item['qty'] < $item['max']) { ?><span data-id="<?=$id;?>" <?php if($item['set']) { ?>data-min="<?=$item["min"];?>" data-set="<?=$item["set"];?>"<?php } ?> class="my-plus-<?=$id;?> <?php if(!$item['set']) { ?> my-plus-cart<?php }else{ ?> my-plus-complete-cart<?php } ?>"><i class="fa fa-plus" aria-hidden="true"></i></span><?php } ?>
										</td>
                                        <td><?=$price?></td>
                                        <td><span data-id="<?=$id;?>" <?php if($item['set']) { ?>data-min="<?=$item["min"];?>" data-set="<?=$item["set"];?>"<?php } ?> class="glyphicon glyphicon-remove text-danger<?php if(!$item['set']) { ?> del-item-cart<?php }else{ ?> del-item-complete-cart<?php } ?>" aria-hidden="true"><i class="fas fa-times"></i></span></td>
                                    </tr>
                                <?php endforeach;?>
                                <tr>
                                    <td>Итого:</td>
                                    <td colspan="4" class="text-right cart-qty"><?=$_SESSION['cart.qty'];?></td>
                                </tr>
                                <tr>
                                    <td>На сумму:</td>
                                    <td colspan="4" class="text-right cart-sum"><?= $_SESSION['cart.currency']['symbol_left'] . $_SESSION['cart.sum'] . $_SESSION['cart.currency']['symbol_right'] ?></td>
                                </tr>
                                </tbody>
                            </table>
						</div>
                    </div>                                            
				<div class="product-info">
					<div class="col-md-6 bg-light px-xxl-5" id="prodinfo">
						<div class="register-top heading">
							<h2>Габаритные размеры</h2>
						</div>
						<ul class="list-unstyled fs-sm pt-4 pb-2 border-bottom">
							<li class="d-flex justify-content-between align-items-center"><span class="me-2">Вес, кг:</span><span class="text-end fw-medium simpleCart_weight"><?=$_SESSION['cart.weight']?></span></li>
							<li class="d-flex justify-content-between align-items-center"><span class="me-2">Объем, м3:</span><span class="text-end fw-medium simpleCart_volume"><?=$_SESSION['cart.volume']?></span></li>
						</ul>                            
					</div>					
					<div class="col-md-6 row bg-light px-xxl-5" id="prodinfo">
						<div class="col-md-4">
						</div>
						<div class="col-md-8 code-block">
							<div class="register-top heading">
								<h2 class="text-white">Промокод</h2>
							</div>
							<?php if($_SESSION['promocart']) { ?>
								<div class="col-md-12">
									<div class="promo-blk row">
										<div class="col-md-6">Промокод <?=$_SESSION['promocart']?> применён.</div>
										<div class="col-md-6">
											<button type="button" class="btn btn-primary w-100" onclick="clearPromo()">Отменить</button>
										</div>
									</div>
								</div>
							<?php }else{ ?>
								<div class="col-md-12" id="promocode">
									<div class="promo-blk row">
										<div class="col-md-6">
											<input type="text" name="promocode" class="form-control vpromo" id="promocode" data-value="" placeholder="Введите промокод на скидку">
										</div>
										<div class="col-md-6">
											<div class="btn btn-primary btn-promo w-100">Применить</div>
										</div>
									</div>
								</div>
								<div class="col-md-12 text-white" style="padding:10px 0 0 0">
									Где взять промокод? <a href="/promo/hochesh-2-skidki-na-ves-zakaz-zhmi" title="Промо-код на скидку">Тут!</a>
								</div>
							<?php } ?>	
						</div>
					</div>					
				</div>
            </div>
		</div>