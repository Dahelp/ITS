<span itemscope itemtype="http://schema.org/Product">
<meta itemprop="name" content="<?=$product->name?>" />
<span itemprop="brand" itemtype="https://schema.org/Brand" itemscope>
	<meta itemprop="name" content="<?=$vendor->name?>" />
</span>
<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <!--start-breadcrumbs-->
		<nav class="pt-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap" itemscope="" itemtype="http://schema.org/BreadcrumbList">
				<?=$breadcrumbs;?>
			</ol>
		</nav>
    </div>
</div>
<!--end-breadcrumbs-->
<?php
    $curr = \ishop\App::$app->getProperty('currency');
?>
<!--start-single-->
<div class="single contact">
    <div class="container">
        <section>
          <!-- Content-->
		  <?php if($product->note) { ?>
			<div class="alert alert-success product-note">
				<?=$product->note;?>
			</div>
		  <?php } ?>
          <!-- Product Gallery + description-->
          <section class="row g-0 mx-n2">
            <div class="col-xl-6 byp-rght-pdd-15 mb-3 byp-float-lft">
              	<section class="slider">			
				<?php if($gallery): ?>		
				
					<div id="slider" class="flexslider">
					  <ul class="slides">
						<li><img itemprop="image" src="/images/product/baseimg/<?=$product->img;?>" alt=""></li>
						<?php foreach($gallery as $item): ?>
						<li id="slide-<?=$item->id;?>">
							<img itemprop="image" src="/images/product/gallery/<?=$item->img;?>" />
						</li>
						<?php endforeach; ?>
					  </ul>
					</div>
					<div id="carousel" class="flexslider">
					  <ul class="slides">
						<li><img itemprop="image" src="/images/product/baseimg/<?=$product->img;?>" alt=""></li>
						<?php foreach($gallery as $item): ?>
						<li id="slide-<?=$item->id;?>">
							<img itemprop="image" src="/images/product/gallery/<?=$item->img;?>" />
						</li>
						<?php endforeach; ?>
					  </ul>
					</div>
					<?php foreach($gallery as $item): ?>
					<script type="text/javascript">
						$('.slides li#slide-<?=$item->id;?>').each( function() {
							var height = $(this).height();
							var imageHeight = $(this).find('img').height();

							var offset = (height - imageHeight) / 2;

							$(this).find('img').css('margin-top', offset + 'px');

						});
						</script>
					<?php endforeach; ?>
				<?php else: ?>
                    <div id="slider" class="flexslider">
					  <ul class="slides">
						<li><img itemprop="image" src="/images/product/baseimg/<?=$product->img;?>" alt=""></li>
						</ul>
					</div>
				<?php endif; ?>
				</section>
            </div>
            <div class="col-xl-6 mb-3 byp-float-rght shadow" style="position: relative;display: grid;">
              <div class="h-100 bg-light rounded-3 p-4">
				<?php $administr = \R::findOne('user', 'id = ?', [$_SESSION['user']['id']]); ?>
				<?php if($administr['groups'] == "1") { ?>
					<div class="edit_prod"><a target="_blank" href="<?= ADMIN ?>/product/edit?id=<?=$product->id?>"><i class="far fa-edit"></i> Редактировать</a></div>
				<?php } ?>
				<a class="product-meta d-block fs-sm pb-2" href="category/<?=$cat_prod->alias?>" title="<?=$cat_prod->name?>"><?=$cat_prod->name?></a>				
                <h1 class="h3">
					<?php
					if($inseo->name) { 					
						echo $name = \ishop\App::seoreplace($inseo->name, $product->id);
					}
					else { echo $product->name; } ?>
				</h1>
				<span class="product-review">
					<div class="rating">
					<?php $review_prod = \R::getAll("SELECT SUM(review.point) as bal FROM review_product JOIN review ON review.id = review_product.review_id WHERE review_product.product_id = ?", [$product->id]); ?>
					<?php $rwcount = \R::count('review_product', "product_id = ?", [$product->id]); ?>
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
				<?php 
					$prod_priceopt = \R::getRow('SELECT company.tip, company_typeprice.znachenie FROM company, company_typeprice WHERE company.id = company_typeprice.company_id AND company.user_id = ? AND company_typeprice.category_id = ?', [$_SESSION['user']['id'], $cat_prod->id]);					
					$price = $product->price;
				?>
				<span itemprop="offers" itemtype="https://schema.org/Offer" itemscope>
				<link itemprop="url" href="<?=PATH?>/product/<?=$product->alias?>" />
				<meta itemprop="availability" content="https://schema.org/InStock" />
				<meta itemprop="priceCurrency" content="RUR" />
				<meta itemprop="itemCondition" content="https://schema.org/UsedCondition" />
				<meta itemprop="price" content="<?=$price * $curr['value'];?>" />
				<meta itemprop="priceValidUntil" content="<?=$product->data_edit_price?>" />
				<?php $rezerv = \R::findOne('in_stock', 'product_id = ? AND branch_id = ?', [$product->id, 9]); ?>
				<?php if(!$mods): // Без модификация товаров ?>
					<div class="fw-normal">
						<div class="item_price_2 text-accent" id="base-price" data-base="<?=$price * $curr['value'];?>">
							<span>Цена: </span>
							<?php if($prod_priceopt["tip"]!=2): ?>
								<?php if($action->product_id): ?>
									<del style="float: left;">
										<?=$curr['symbol_left'];?>
										<?=$price * $curr['value'];?>
										<?=$curr['symbol_right'];?>
									</del>
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
							
									
								<?php else: ?>
									<?php if($product->sale == "1" && $price < $product->price_rrs): ?>
										<?=$curr['symbol_left'];?>
										<?=$price * $curr['value'];?>
										<?=$curr['symbol_right'];?>
										<del style="float: left;">
										<?=$curr['symbol_left'];?>
										<?=$product->price_rrs * $curr['value'];?>
										<?=$curr['symbol_right'];?>
										</del>
									<?php else: ?>
										<?=$curr['symbol_left'];?>
										<?=$price * $curr['value'];?>
										<?=$curr['symbol_right'];?>
									<?php endif; ?>
								<?php endif; ?>							
							<?php else: ?>
								<?=$curr['symbol_left'];?>
								<?=$price * $curr['value'];?>
								<?=$curr['symbol_right'];?>
								<br><span>Опт: </span>
								<?=$curr['symbol_left'];?>
								<?php if($prod_priceopt["znachenie"] =="" ) { ?>
									<?=$product->opt_price * $curr['value'];?>
								<?php }else{ ?>								
									<?php $price_nds = round($product->price - ($product->price/1.2), 0) * 6 * $curr['value']; $price_opt = $price_nds - (($price_nds/100) * $prod_priceopt["znachenie"]); echo $opt = ceil($price_opt / 6) * 6; ?>
								<?php } ?>								
								<?=$curr['symbol_right'];?>
							<?php endif; ?>
						</div>
						<?php if($product->quantity > 0) { ?>
							<div class="current_price_success">цена актуальна на: <?=$product->data_edit_price?></div>
						<?php }else{ ?>
							<div class="current_price_warning">цена может отличаться при заказе</div>
						<?php } ?>
					</div>
					<div class="fw-normal">
						<div class="vnalichie">
							<?php $itog_qty = $product->quantity - $rezerv["quantity"];		
								if($itog_qty == 0) { 
									if($product->stock_status_id == 0){
								?>
									<span class="nalich_no"><i class="far fa-times-circle fa-tabls" aria-hidden="true"></i> Нет в наличии, о поступлении уточняйте у менеджера</span>
								<?php } 
									if($product->stock_status_id == 2){ ?>
									<span class="nalich_no">Товар можно приобрести под заказ. Цена, наличие и срок доставки согласовываются с менеджером. Доставка 3-5 рабочих дней после поступления оплаты по счёту.</span>
								<?php } 
									if($product->stock_status_id == 3){ ?>
									<span class="nalich_postuplenie">Ожидается поступление. Уточняйте у менеджера.</span>
								<?php } ?>						
							<?php } ?>
							<?php if($itog_qty < 0) { ?>
								<span class="nalich_no"><i class="far fa-times-circle fa-tabls" aria-hidden="true"></i> Нет в наличии, о поступлении уточняйте у менеджера</span>
							<?php } ?>
							<?php if($itog_qty > 0) { 
							
								$quantity = $product->quantity;								
								if($rezerv["quantity"] > 0) {								
								?>
									<span class="nalich_ok"><i class="fas fa-check" aria-hidden="true"></i> Свободное наличие: <?=$itog_qty?></span> (В резерве: <?=$rezerv["quantity"]?> шт.)
									
								<?php }else{ ?>
									<span class="nalich_ok"><i class="fas fa-check" aria-hidden="true"></i> В наличии: <?=$quantity?> шт.</span>
								<?php } ?>
							
							<?php } ?>					
							
						</div>		
					</div>				
					<div class="d-flex flex-wrap align-items-center pt-4 pb-2 mb-3 quantity">
						<?php if($quantity > 0) { ?>
						<?php if($_SESSION['cart'][$product->id]) { ?>
							<input class="form-control detail-quantity me-2 korzina-<?=$product->id;?> clear-korzina" style="display:none;caret-color:transparent;" name="quantity" type="number" value="1" min="1" max="<?=$itog_qty?>" data-max="<?=$itog_qty?>" data-min="1">                  
							<a data-id="<?=$product->id;?>" class="btn btn-soft-primary me-2 add-to-cart-link korzina-<?=$product->id;?> clear-korzina" style="display:none;" href="cart/add?id=<?=$product->id;?>" data-max="<?=$itog_qty?>" data-toggle="modal" data-target="#exampleModalLive" onclick="ym(87229051,'reachGoal','VKORZINU'); return true;"><i class="fas fa-cart-plus"></i> В корзину</a>
							<button class="btn btn-warning one-click korzina-<?=$product->id;?> clear-korzina" style="display:none;" type="submit" data-toggle="modal" data-target="#Modalclick">Купить в 1 клик</button>					
							<button href="cart/show" onclick="getCart(); return false;" class="btn btn-success vkorzine-<?=$product->id;?> clear-vkorzine">В корзине</button>
						<?php }else{ ?>
							<input class="form-control detail-quantity me-2 korzina-<?=$product->id;?> clear-korzina" style="caret-color:transparent;" name="quantity" type="number" value="1" min="1" max="<?=$itog_qty?>" data-max="<?=$itog_qty?>" data-min="1">                  
							<a data-id="<?=$product->id;?>" class="btn btn-soft-primary me-2 add-to-cart-link korzina-<?=$product->id;?> clear-korzina" href="cart/add?id=<?=$product->id;?>" data-max="<?=$itog_qty?>" data-toggle="modal" data-target="#exampleModalLive" onclick="ym(87229051,'reachGoal','VKORZINU'); return true;"><i class="fas fa-cart-plus"></i> В корзину</a>
							<button class="btn btn-warning one-click korzina-<?=$product->id;?> clear-korzina" type="submit" data-toggle="modal" data-target="#Modalclick">Купить в 1 клик</button>					
							<button href="cart/show" onclick="getCart(); return false;" class="btn btn-success vkorzine-<?=$product->id;?> clear-vkorzine" style="display:none;">В корзине</button>						
						<?php } ?>
						<?php }else{
							if($product->stock_status_id == 2){
							?>
								<button class="btn btn-warning" type="submit" data-toggle="modal" data-target="#ModalRequest">Оформить под заказ</button>
							<?php }
							if($product->stock_status_id == 3 or $product->stock_status_id == 0){
							?>
								<button class="btn btn-success" type="submit" data-toggle="modal" data-target="#ModalAvailability">Сообщить о поступлении на email</button>
							<?php } ?>
							<?php } ?>
					</div>
					<?php else: // Модификация товаров ?>
					<?php $mod_prod = \ishop\App::options('modification_product'); 
						if($mod_prod == "Нет") { ?>
						<div class="available">
							Производитель
									<select class="form-control">
										<option id="base-quantity" data-basequant="<?=$product->quantity?>"><?=$vendor->name?> - <?=$product->quantity?> шт.</option>
										<?php foreach($mods as $mod): ?>
										<?php $sum_mods += $mod->quantity;?>
										<option data-title="<?=$mod->name_modification;?>" data-quantity="<?=$mod->quantity;?>" data-price="<?=$mod->price * $curr['value'];?>" value="<?=$mod->id;?>"><?=$mod->name_modification;?> - <?=$mod->quantity?> шт.</option>
										<?php endforeach; ?>
									</select>							
						</div>
					<?php }else{ ?>						
						<?php foreach($mods as $mod):
								$sum_mods += $mod->quantity;
								$modprice .= "".$mod->price.", ";
							endforeach;
								$quantity = $product->quantity + $sum_mods;								
								$sql_modprice = "".$product->price.", ".$modprice."";
								$sql_modprice = rtrim($sql_modprice, ', ');								
								$max=[];
								$max_price=max($max=explode(",", $sql_modprice));
								
								
						?>
						<div class="fw-normal">
						<div class="item_price_2 text-accent" id="base-price" data-base="<?=$max_price * $curr['value'];?>">
							<span>Цена: </span>
								<?=$max_price * $curr['value'];?>
								<?=$curr['symbol_right'];?>
							</div>		
						</div>	
						<?php if($quantity == 0) { ?>
							<div class="fw-normal">
								<div class="vnalichie">								
									<span class="nalich_no"><i class="far fa-times-circle fa-tabls" aria-hidden="true"></i> Нет в наличии, о поступлении уточняйте у менеджера</span>
								</div>		
							</div>
						<?php } ?>
						<?php if($quantity > 0) { 
								$itog_qty = $product->quantity + $sum_mods - $rezerv["quantity"];
						?>
							<div class="fw-normal">
								<div class="vnalichie">
								<?php $quantity = $product->quantity + $sum_mods;								
								if($rezerv["quantity"] > 0) {									
								?>
									<span class="nalich_ok"><i class="fas fa-check" aria-hidden="true"></i> Свободное наличие: <?=$itog_qty?></span> (В резерве: <?=$rezerv["quantity"]?> шт.)
									
								<?php }else{ ?>
									<span class="nalich_ok"><i class="fas fa-check" aria-hidden="true"></i> В наличии: <?=$quantity?> шт.</span>
								<?php } ?>							
								</div>		
							</div>							
							<div class="d-flex flex-wrap align-items-center pt-4 pb-2 mb-3 quantity">								
								<?php if($_SESSION['cart'][$product->id]) { ?>
									<input class="form-control detail-quantity me-2 korzina-<?=$product->id;?> clear-korzina" style="display:none;caret-color:transparent;" name="quantity" type="number" value="1" min="1" max="<?=$itog_qty?>" data-max="<?=$itog_qty?>" data-min="1">                  
									<a data-id="<?=$product->id;?>" class="btn btn-soft-primary me-2 add-to-cart-mod korzina-<?=$product->id;?> clear-korzina" style="display:none;" href="cart/add?id=<?=$product->id;?>" data-max="<?=$itog_qty?>" data-toggle="modal" data-target="#exampleModalLive" onclick="ym(87229051,'reachGoal','VKORZINU'); return true;"><i class="fas fa-cart-plus"></i> В корзину</a>
									<button class="btn btn-warning one-click korzina-<?=$product->id;?> clear-korzina" style="display:none;" type="submit" data-toggle="modal" data-target="#Modalclick">Купить в 1 клик</button>					
									<button href="cart/show" onclick="getCart(); return false;" class="btn btn-success vkorzine-<?=$product->id;?> clear-vkorzine">В корзине</button>
								<?php }else{ ?>
									<input class="form-control detail-quantity me-2 korzina-<?=$product->id;?> clear-korzina" style="caret-color:transparent;" name="quantity" type="number" value="1" min="1" max="<?=$itog_qty?>" data-max="<?=$itog_qty?>" data-min="1">                  
									<a data-id="<?=$product->id;?>" data-modification="<?=$mod->id;?>" class="btn btn-soft-primary me-2 add-to-cart-mod korzina-<?=$product->id;?> clear-korzina" href="cart/add?id=<?=$product->id;?>" data-max="<?=$itog_qty?>" data-toggle="modal" data-target="#exampleModalLive" onclick="ym(87229051,'reachGoal','VKORZINU'); return true;"><i class="fas fa-cart-plus"></i> В корзину</a>
									<button class="btn btn-warning one-click korzina-<?=$product->id;?> clear-korzina" type="submit" data-toggle="modal" data-target="#Modalclick">Купить в 1 клик</button>					
									<button href="cart/show" onclick="getCart(); return false;" class="btn btn-success vkorzine-<?=$product->id;?> clear-vkorzine" style="display:none;">В корзине</button>						
								<?php } ?>
								<input type="hidden" class="modification" value="<?=$mod->id;?>" name="modification" />
							</div>
						<?php } ?>
						
					<?php } ?>
						
					<?php endif; ?>
				<div class="d-flex flex-wrap align-items-center pb-2 mb-3 quantity">
						<button class="btn btn-primary" id="btnpdf" type="submit"><i class="fas fa-print"></i> Коммерческое предложение</button>
					
					<?php if($_SESSION['user']['id']) { 
											$bookmarks = \R::count('product_bookmarks', 'product_id = ? AND user_id = ?', [$product->id, $_SESSION['user']['id']]);
											if($bookmarks==1){
										?>
											<button id="wishlist-<?=$product->id?>" class="btn-wishlist2 btn-icon m-2" type="button" data-bs-toggle="tooltip" data-bs-placement="left" title="" data-bs-original-title="Wishlist" aria-label="Wishlist"><i class="far fa-heart"></i></button>
										<?php } else { ?>
											<button id="wishlist-<?=$product->id?>" class="btn-wishlist btn-icon m-2" type="button" data-id="<?=$product->id?>" data-userid="<?=$_SESSION['user']['id']?>" data-bs-toggle="tooltip" data-bs-placement="left" title="Добавить в избранное" data-bs-original-title="Add to wishlist" aria-label="Add to wishlist"><i class="far fa-heart"></i></button>
										<?php } ?>
										<?php } ?>
										<?php if(!$_SESSION['comparison'][$product->id]) { ?>
											<button id="comparison-<?=$product->id?>" class="btn-comparison btn-icon" type="button" data-id="<?=$product->id?>" data-categoryid="<?=$product->category_id?>" data-bs-toggle="tooltip" data-bs-placement="left" title="Добавить в сравнени" data-bs-original-title="Comparison" aria-label="Comparison"><i class="far fa-tasks"></i></button>
										<?php } else { ?>
											<button id="comparison-<?=$product->id?>" class="btn-comparison2 btn-icon" type="button" data-bs-toggle="tooltip" data-bs-placement="left" title="Добавить в сравнени" data-bs-original-title="Comparison" aria-label="Comparison"><i class="far fa-tasks"></i></button>
										<?php } ?>
				</div>
				<div class="share">
					<script src="https://yastatic.net/share2/share.js"></script>
					<div class="ya-share2" data-curtain data-services="vkontakte,odnoklassniki,telegram,whatsapp"></div>
				</div>
				<div class="info">
					<?php $filters = \R::getAll('SELECT attribute_group.title, attribute_group.url_params, attribute_value.value, attribute_value.alias FROM attribute_group, attribute_category, attribute_product, attribute_value WHERE attribute_category.group_id = attribute_group.id AND attribute_product.attr_id = attribute_value.id AND attribute_value.attr_group_id = attribute_group.id AND attribute_product.product_id = ? GROUP BY attribute_group.title', [$product['id']]); ?>
					<ul class="list-unstyled">
							<li >Артикул: <span><?=$product->article?></span></li>
						<?php foreach($filters as $filter) { 
							if( $filter['url_params'] != "" ) { ?>
							<li ><?=$filter['title']?>: <span><a href="<?=$filter['url_params']?>/<?php echo mb_strtolower($filter['alias']); ?>" title="<?=$filter['value']?>"><?=$filter['value']?></a></span></li>
							<?php }else{ ?>
							<li ><?=$filter['title']?>: <span><?=$filter['value']?></span></li>
						<?php } } ?>
            		</ul>
				</div>
				
				</span>
              </div>
			  <div class="articles_content">
				<ul>
			  <?php if($cat_prod->alias == "atv") { ?>
					
						<li><a href="https://its-center.ru/articles/kak-uznat-razmer-shin-dlya-kvadrocikla" title="Как узнать размер шин для квадроцикла">Как узнать размер шин для квадроцикла?</a>
					
				<?php } ?>
				
				<?php if($cat_prod->alias == "shiny-dlya-vilochnyh-pogruzchikov") { ?>
					
						<li><a href="https://its-center.ru/articles/polnoe-rukovodstvo-po-vyboru-i-ekspluatacii-shin-dlya-vilochnyh-pogruzchikov" title="Полное руководство по выбору и эксплуатации шин для вилочных погрузчиков">Полное руководство по выбору и эксплуатации шин для вилочных погрузчиков</a>
					
				<?php } ?>
				
				<?php if($cat_prod->alias == "shiny-dlya-minipogruzchikov") { ?>
					
						<li><a href="https://its-center.ru/articles/rukovodstvo-po-vyboru-i-uhodu-za-shinami-dlya-mini-pogruzchikov" title="Руководство по выбору и уходу за шинами для мини-погрузчиков">Руководство по выбору и уходу за шинами для мини-погрузчиков</a></li>
					
				<?php } ?>
				
				<?php if($cat_prod->alias == "shiny-dlya-frontalnyh-pogruzchikov") { ?>
					
						<li><a href="https://its-center.ru/articles/rukovodstvo-po-shinam-dlya-frontalnyh-pogruzchikov-vybor-ekspluataciya-i-sovety" title="Руководство по шинам для фронтальных погрузчиков: выбор, эксплуатация и советы">Руководство по шинам для фронтальных погрузчиков: выбор, эксплуатация и советы</a></li>
					
				<?php } ?>
				
				<?php if($cat_prod->alias == "shiny-dlya-ekskavatorov-pogruzchikov") { ?>
					
						<li><a href="https://its-center.ru/articles/rukovodstvo-po-vyboru-i-ekspluatacii-shin-dlya-ekskavatorov-pogruzchikov" title="Руководство по выбору и эксплуатации шин для экскаваторов-погрузчиков">Руководство по выбору и эксплуатации шин для экскаваторов-погрузчиков</a></li>
					
				<?php } ?>
				
				<?php if($cat_prod->alias == "shiny-dlya-kolesnyh-ekskavatorov") { ?>
					
						<li><a href="https://its-center.ru/articles/rukovodstvo-po-vyboru-i-ekspluatacii-shin-dlya-kolesnyh-ekskavatorov" title="Руководство по выбору и эксплуатации шин для колесных экскаваторов">Руководство по выбору и эксплуатации шин для колесных экскаваторов</a></li>
					
				<?php } ?>
				
				<?php if($cat_prod->alias == "shiny-dlya-gruntovyh-katkov") { ?>
				
						<li><a href="https://its-center.ru/articles/rukovodstvo-po-shinam-dlya-gruntovyh-katkov" title="Руководство по шинам для грунтовых катков">Руководство по шинам для грунтовых катков</a></li>
		
				<?php } ?>
				<!-- Общая статья для шин -->
				<?php if($cat_prod->parent_id == 1 OR $cat_prod->parent_id == 2) { ?>
				
						<li><a href="https://its-center.ru/articles/rekomendacii-po-ekspluatacii-shin-bezopasnost-i-dolgovechnost" title="Общие рекомендации по эксплуатации шин">Общие рекомендации по эксплуатации шин</a></li>
					
				<?php } ?>
				</ul>
				</div>
            </div>
          </section>
			<?php
				$complete = \R::getAll("SELECT*FROM `plagins_complete_product`, `plagins_complete` WHERE plagins_complete_product.complete_id = plagins_complete.id AND plagins_complete_product.product_id = '".$product->id."'");
				if($complete) {				
			?>			
			<section>
				<div class="complete-inner">
						
					<h2>Купить комплект шин</h2>
					<div class="complete-block bg-light shadow">
					
					<?php 
						foreach($complete as $cpl) { ?>
						<?php			
							$prods = \R::getAll("SELECT product.name, product.price as price, product.quantity, plagins_complete_product.product_id, plagins_complete_product.qty, plagins_complete_product.price as price_complete, plagins_complete_product.discount FROM plagins_complete_product, product WHERE plagins_complete_product.product_id = product.id AND plagins_complete_product.complete_id = ?", [$cpl["id"]]);
							$prod_id = '';
							$prod_qty = '';
							$prodid = '';
							foreach($prods as $prod) {
								$price_complete += $prod["price_complete"]*$prod["qty"];
								$discount_complete += $prod["discount"]*$prod["qty"];
								$prod_id .= "".$prod["product_id"].","; $prod_qty .= "".$prod["qty"].",";
								if($prod["quantity"]>=$prod["qty"]) {
									$quantity = 1;							
									$prodid .= "".$prod["product_id"]."-";
								}elseif($prod["quantity"]>0 && $prod["quantity"]<$prod["qty"]){
									$quantity = 0;
									$prodid .= "".$prod["product_id"]."-";
								}else{
									$quantity = 0;							
								}
								$itg_qty += $quantity;
								$vcomplecte += $prod["qty"];
							}
							$prod_id = rtrim($prod_id, ',');
							$prod_qty = rtrim($prod_qty, ',');
							$itog_price_complete = $price_complete-$discount_complete;
							$prodid = rtrim($prodid, '-');
						?>
						<div class="complete-main-prod bg-grad-4">
							<div class="col-md-4 cmp-1"><img src="../images/complete/mini/<?=$cpl["img"]?>" alt="<?=$cpl["name"]?>" title="<?=$cpl["name"]?>" /></div>
							<div class="col-md-4 cmp-2"><span><?=$cpl["name"]?></span><br />В комплекте <?=$vcomplecte?> шт.</div>
							<div class="col-md-4 cmp-3 quantity-complete">
								<div class="complete_price">Цена за комплект<br><span><?=$itog_price_complete?> <?=$curr['symbol_right'];?><span></div>
								
								<?php if($itg_qty == count($prods)) { ?>				
									<input class="form-control" style="display:none;" name="quantity" type="number" value="<?=$cpl["qty"]?>" min="1" data-min="1">
									<a data-id="<?=$prodid;?>" data-complete="1" data-set="<?=$cpl["id"];?>" class="btn btn-success me-2 add-to-cart-complete korzina-<?=$complete->id;?> clear-korzina" href="cart/addcomplete?id=<?=$prodid;?>" data-toggle="modal" data-target="#exampleModalLive" onclick="ym(87229051,'reachGoal','VKORZINU'); return true;"><i class="fas fa-cart-plus"></i> Купить комплект</a>					
								<?php } if($itg_qty > 0 && $itg_qty < count($prods)) { ?>
									<input class="form-control" style="display:none;" name="quantity" type="number" value="<?=$cpl["qty"]?>" min="1" data-min="1">
									<a data-id="<?=$prodid;?>" data-complete="0" class="btn btn-success me-2 add-to-cart-complete korzina-<?=$cpl["id"];?> clear-korzina" href="cart/addcomplete?id=<?=$prodid;?>" data-toggle="modal" data-target="#exampleModalLive" onclick="ym(87229051,'reachGoal','VKORZINU'); return true;"><i class="fas fa-cart-plus"></i> Купить не полный комплект</a>				
								<?php } if($itg_qty == 0){ ?>
									<button class="btn btn-success me-2">Нет в наличии</button>
								<?php } ?>
								<a class="btn btn-danger" href="complete/<?=$cpl["alias"]?>">Подробнее</a>
							</div>
							
						</div>								
					<?php } ?>
					</div>
				</div>
			</section>
			<?php } ?>
		  <?php $cross = \R::getAll('SELECT plagins_cross_vendor.name, plagins_cross.cross_name, plagins_cross.cross_abbreviated_name, plagins_cross.tip_cross, plagins_cross.equipment_vendor FROM plagins_cross, plagins_cross_vendor WHERE plagins_cross.vendor_id = plagins_cross_vendor.id AND plagins_cross.product_id = ?', [$product['id']]); ?>
		  <!-- Descriptions -->
			<section class="desc-prod-inner bg-light shadow">
			<ul class="nav nav-pills p-3" id="pills-tab" role="tablist">
				<li class="nav-item" role="presentation">
					<a class="nav-link active" id="pills-harakteristics-tab" data-toggle="pill" href="#pills-harakteristics" role="tab" aria-controls="pills-harakteristics" aria-selected="false">Характеристики</a>
				</li>
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="pills-opisanie-tab" data-toggle="pill" href="#pills-opisanie" role="tab" aria-controls="pills-opisanie" aria-selected="true">Описание</a>
				</li>
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="pills-primenenie-tab" data-toggle="pill" href="#pills-primenenie" role="tab" aria-controls="pills-primenenie" aria-selected="true">Применяемость</a>
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
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="pills-review-tab" data-toggle="pill" href="#pills-review" role="tab" aria-controls="pills-review" aria-selected="false">Отзывы <span class="badge bg-light text-dark"><?php echo count($review); ?></span></a>
				</li>
			
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
								// аттрибуты товаров
								$attributs = \R::getAll("SELECT * FROM attribute JOIN product_attribute ON product_attribute.attribute_id = attribute.id WHERE product_attribute.product_id = ? AND product_attribute.attribute_group_id = ? ORDER BY attribute.attribute_position", [$product->id, $group["attribute_group_id"]]);
								foreach($attributs as $att): ?>
									<?php $attribute[$att['attribute_id']] = $att["attribute_text"]; ?>
									<tr><td><?=$att["attribute_name"]?></td><td><?=$att["attribute_text"]?></td></tr>
							
								<?php endforeach; ?>
							</tbody>
						
						<?php endforeach; ?>
					</table>
					<?php endif; ?>
				<!-- /harakteristics -->
				</div>
				<div class="tab-pane fade" id="pills-opisanie" role="tabpanel" aria-labelledby="pills-opisanie-tab" itemprop="description">
					<?php
						if(!$product->content) { 					
							echo $content = \ishop\App::seoreplace($inseo->content, $product->id);
						}else{
							echo $product->content;
						}
					?>										
				</div>
				<?php
						$technics = \R::getAll("SELECT technics.model, technics_manufacturer.name, technics.alias FROM technics_tiposize, attribute_value, technics, technics_manufacturer WHERE technics_manufacturer.id = technics.manufacturer_id AND technics.id = technics_tiposize.technics_id AND technics_tiposize.value_id = attribute_value.id AND attribute_value.value = ?", [$attribute[4]]);
				?>
				<?php if($technics) { ?>
				<div class="tab-pane fade" id="pills-primenenie" role="tabpanel" aria-labelledby="pills-primenenie-tab">
						
					<table class="table table-bordered table-striped">
					<?php foreach($technics as $tech): ?>
						
						<tr><td><?=$tech["name"]?></td><td><a href="technics/<?=$tech["alias"]?>" title="Посмотреть все шины для <?=$tech["name"]?> <?=$tech["model"]?>"><?=$tech["model"]?></a></td></tr>
						
					<?php endforeach; ?>
					</table>
					
				</div>
				<?php } ?>
				<?php if($cross) { ?>
					<div class="tab-pane fade" id="pills-analog" role="tabpanel" aria-labelledby="pills-analog-tab">
						<?php $cross_analog = \R::getAll('SELECT plagins_cross_vendor.name, plagins_cross.cross_name, plagins_cross.cross_abbreviated_name, plagins_cross.tip_cross, plagins_cross.equipment_vendor FROM plagins_cross, plagins_cross_vendor WHERE plagins_cross.vendor_id = plagins_cross_vendor.id AND plagins_cross.equipment_vendor = ? AND plagins_cross.product_id = ?', [2, $product['id']]); ?>
						<table class="table table-bordered table-striped">
							<tbody>
							<?php foreach($cross_analog as $analog): ?>									
								<tr><td><?=$analog["name"]?></td><td><a href="cross/<?=strtolower($analog["cross_abbreviated_name"])?>" title="<?=$analog["cross_name"]?>"><?=$analog["cross_name"]?></a></td>
								<?php if($analog['tip_cross'] < 3) { 
									if($analog['tip_cross'] == 1) { $analog_tip = "Внешняя часть"; }
									if($analog['tip_cross'] == 2) { $analog_tip = "Внутренняя часть"; }								
									echo "<td>$analog_tip</td>";
								}
								if($analog['tip_cross'] == 4) {									
									if($analog['tip_cross'] == 4) { $analog_tip = "Комплект из 2х частей"; }
									echo "<td>$analog_tip</td>";
								}
								?>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>										
					</div>
					<div class="tab-pane fade" id="pills-oem" role="tabpanel" aria-labelledby="pills-oem-tab">
						<?php $cross_oem = \R::getAll('SELECT plagins_cross_vendor.name, plagins_cross.cross_name, plagins_cross.cross_abbreviated_name, plagins_cross.tip_cross, plagins_cross.equipment_vendor FROM plagins_cross, plagins_cross_vendor WHERE plagins_cross.vendor_id = plagins_cross_vendor.id AND plagins_cross.equipment_vendor = ? AND plagins_cross.product_id = ?', [1, $product['id']]); ?>
						<table class="table table-bordered table-striped">
							<tbody>
							<?php foreach($cross_oem as $oem): ?>									
								<tr><td><?=$oem["name"]?></td><td><a href="cross/<?=strtolower($oem["cross_abbreviated_name"])?>" title="<?=$oem["cross_name"]?>"><?=$oem["cross_name"]?></a></td>
								<?php if($oem['tip_cross'] < 3) { 
									if($oem['tip_cross'] == 1) { $oem_tip = "Внешняя часть"; }
									if($oem['tip_cross'] == 2) { $oem_tip = "Внутренняя часть"; }																		
									echo "<td>$oem_tip</td>";
								} 
								if($oem['tip_cross'] == 4) { 									
									if($oem['tip_cross'] == 4) { $oem_tip = "Комплект из 2х частей"; }
									echo "<td>$oem_tip</td>";
								}
								?>
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
					<iframe class="video_frame" src="<?php parse_str( parse_url( $product->url_video, PHP_URL_QUERY ), $video ); echo "https://www.youtube.com/embed/".$video['v'].""; ?>" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
				</div>
				<!-- /video -->
				<?php } ?>
				
				<!-- review -->
				<div class="tab-pane fade review-tab" id="pills-review" role="tabpanel" aria-labelledby="pills-review-tab">
					<section class="container">
						<div class="row">
							<div class="col-md-12">								
								<div class="panel">
									<div class="panel-body">
										<div class="col-md-12 row">
											<div class="col-md-6">
											<?php if($_SESSION['user']['id']) { ?>
											<form method="post" action="product/view">
												<div class="form-group has-feedback mb-3">
													<label class="form-label" for="name">Ваш отзыв</label>
													<textarea name="content" class="form-control" rows="3" placeholder="Добавьте Ваш отзыв"></textarea>
												</div>
												<div class="form-group has-feedback mb-3">
													<label class="form-label" for="name">Поставьте оценку</label>
													<select name="point" class="form-control" style="width: 150px;">														
														<option value="5">5</option>
														<option value="4">4</option>
														<option value="3">3</option>
														<option value="2">2</option>
														<option value="1">1</option>
													</select>
													<input name="product_id" type="hidden" value="<?=$product->id?>" />													
												</div>											
											<div class="mar-top clearfix">
												<input name="addreview" class="btn btn-sm btn-primary pull-right" type="submit" value="Добавить">											
												<button class="btn btn-trans btn-icon fa fa-camera add-tooltip"></button>											
											</div>
											</form>
											</div>
												<div class="col-md-6 row">
													<div class="col-md-8">
														<h3>Оставьте отзыв в Яндекс Картах</h3>
														<p>ИТС-Центр</p>
														<p>Благодарит вас за посещение. Если вы хотите поделиться отзывом и поставить оценку, перейдите по ссылке в QR-коде.</p>
													</div>
													<div class="col-md-4">
														<a href="https://yandex.ru/maps/org/its_tsentr/131423444838/reviews/?ll=37.562380%2C55.360418&z=16" title="Оставьте отзыв на ИТС-Центр в Яндекс Картах"><img src="images/qr-yandex-card.jpg" alt="Оставьте отзыв на ИТС-Центр в Яндекс Картах" /></a>
													</div>
												</div>
											</div>
											<?php }else{ ?>
											<div class="col-md-12 row">
												<div class="col-md-6">
													<h3>Добавить отзыв</h3>													
													<p>Зарегистрируйтесь или войдите через быстрый вход Яндекс, Google, ВК, чтобы оставить отзыв о товаре.</p>
												</div>
												<div class="col-md-6 row">
													<div class="col-md-8">
														<h3>Оставьте отзыв в Яндекс Картах</h3>
														<p>ИТС-Центр</p>
														<p>Благодарит вас за посещение. Если вы хотите поделиться отзывом и поставить оценку, перейдите по ссылке в QR-коде.</p>
													</div>
													<div class="col-md-4">
														<a href="https://yandex.ru/maps/org/its_tsentr/131423444838/reviews/?ll=37.562380%2C55.360418&z=16" title="Оставьте отзыв на ИТС-Центр в Яндекс Картах"><img src="images/qr-yandex-card.jpg" alt="Оставьте отзыв на ИТС-Центр в Яндекс Картах" /></a>
													</div>
												</div>
											</div>
										<?php } ?>
									</div>
								</div>					
								<?php if($review) { ?>
								<div class="panel-review">
									<div class="panel-body" itemprop="review" itemscope itemtype="http://schema.org/Review">
									<?php foreach($review as $rw) {	?>
										<span itemprop="author" itemscope itemtype="http://schema.org/Person">
											<meta itemprop="name" content="<?=$rw["uname"]?>">
										</span>
										<meta itemprop="datePublished" content="<?=$rw["date_post"]?>">
										<span itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">
											<meta itemprop="ratingValue" content="<?=$rw["point"]?>">
											<meta itemprop="worstRating" content="1">
											<meta itemprop="bestRating" content="5">
										</span>
										<meta itemprop="reviewBody" content="<?=$rw["content"]?>">
										<div class="media-block">											
											<div class="media-body">
												<div class="mar-btm">
													<div class="text-semibold media-heading box-inline"><?=$rw["uname"]?></div>
													<div class="rating">										
														<?php for ($i = 1; $i <= 5; $i++) { ?>
														<?php if ($rw["point"] < $i) { ?>
														<span class="fa fa-stack"><i class="far fa-star fa-stack-2x"></i></span>
														<?php } else { ?>
														<span class="fa fa-stack"><i class="fas fa-star fa-stack-2x"></i><i class="fa fa-star-o fa-stack-2x"></i></span>
														<?php } ?>
														<?php } ?>
														<span class="text-muted text-sm ps-3"><?php echo \ishop\App::contdate($rw["date_post"]);?></span>
													</div>													
												</div>
												<p><?=$rw["content"]?></p>
												<?php $gallery_review = \R::getAll('SELECT id, img FROM review_gallery WHERE review_id = ?', [$rw["id"]]);
												if($gallery_review){ ?>
												<div class="review_img">
													<?php
													foreach($gallery_review as $gr) {
													?>
													<div class="rimg"><a class="thumb" data-id="<?=$product->id;?>" data-src="images/review/gallery/<?=$gr["img"]?>" href="images/review/gallery/<?=$gr["img"]?>"><img src="images/review/mini/<?=$gr["img"]?>" width="150" alt="" data-fancybox="gallery" /></a></div>
													<?php } ?>
												</div>
												<?php } ?>
											</div>
										</div>
									<?php } ?>	
									</div>
								</div>
								<?php } ?>
							</div>
						</div><!-- /.row -->
					</section><!-- /.container -->
				</div>
				<!-- /review -->
				
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
        
<!--end-single-->
</span>
<!-- Modal 1 click -->
<form action="/product/<?=$product->alias?>" method="post" class="modal fade" id="Modalclick" tabindex="-1" role="dialog" aria-labelledby="ModalclickLabel" aria-hidden="true" data-toggle="validator">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
            <div class="modal-header">
				<h5 class="modal-title" id="exampleModalLiveLabel">Купить в 1 клик</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<i class="fa-solid fa-xmark"></i>
				</button>
      </div>
            <div class="modal-body">
				<div class="fc-3"><?=$product->name;?>
					<input type="hidden" name="name_tovar" value="<?=$product->name;?>">
					<input type="hidden" name="product_id" value="<?=$product->id;?>">
				</div>
				<div class="fc-1">
					<div class="fc_name">Ф.И.О. <span>*</span></div>
					<div class="fc_input"><input type="text" name="fio_modal" placeholder="Укажите Ф.И.О." value="<?=$_SESSION['user']['name']?>" required></div>
				</div>
				<div class="fc-1">
					<div class="fc_name">Телефон <span>*</span></div>
					<div class="fc_input"><input type="tel" name="tell_modal" id="phone-input3" required></div>
				</div>
				<div class="fc-1">
					<div class="fc_name">E-Mail <span>*</span></div>
					<div class="fc_input"><input type="text" name="email_modal" placeholder="Укажите ваш e-mail" required></div>
				</div>
				<div class="fc-1">
					<div class="fc_name">Комментарий к заказу</div>
					<div class="fc_textarea"><textarea name="prim_modal" placeholder="Комментарий к заказу (количество, время для обратного звонка или другую необходимую информацию)"></textarea></div>
				</div>
            </div>
            <div class="modal-footer">
                <table class="zayvka_1click"><tbody><tr><td><input type="checkbox" name="politika" value="pk" required></td><td style="font-size:14px;padding:0 0 0 20px">Я соглашаюсь с<br><a href="/pages/privacy">политикой конфиденциальности.</a></td></tr></tbody></table>
				<button type="submit" name="oneclick" value="<?php echo md5(date('Y-m-d')); ?>" class="btn btn-danger">Отправить</button>
            </div>
        </div>
  </div>
</form>

<!-- Modal Podzakaz -->
<form action="/product/<?=$product->alias?>" method="post" class="modal fade" id="ModalRequest" tabindex="-1" role="dialog" aria-labelledby="ModalRequestLabel" aria-hidden="true" data-toggle="validator">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
            <div class="modal-header">
				<h5 class="modal-title" id="exampleModalLiveLabel">Оформить товар по заказ</h5>
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
					<div class="fc_input"><input type="text" name="fio_modal" placeholder="Укажите Ф.И.О." value="<?=$_SESSION['user']['name']?>" required></div>
				</div>
				<div class="fc-1">
					<div class="fc_name">Телефон <span>*</span></div>
					<div class="fc_input"><input type="tel" name="tell_modal" id="phone-input2" required></div>
				</div>
				<div class="fc-1">
					<div class="fc_name">E-Mail <span>*</span></div>
					<div class="fc_input"><input type="text" name="email_modal" placeholder="Укажите ваш e-mail" required></div>
				</div>
				<div class="fc-1">
					<div class="fc_name">Комментарий к заказу</div>
					<div class="fc_textarea"><textarea name="prim_modal" placeholder="Комментарий к заказу (количество товара, время для обратного звонка или другую необходимую информацию)"></textarea></div>
				</div>
            </div>
            <div class="modal-footer">
                <table class="zayvka_1click"><tbody><tr><td><input type="checkbox" name="politika" value="pk" required></td><td style="font-size:14px;padding:0 0 0 20px">Я соглашаюсь с<br><a href="/pages/privacy">политикой конфиденциальности.</a></td></tr></tbody></table>
				<button type="submit" name="request" value="<?php echo md5(date('Y-m-d')); ?>" class="btn btn-danger">Отправить</button>
            </div>
        </div>
  </div>
</form>

<!-- Modal Nalichie -->
<form action="/product/<?=$product->alias?>" method="post" class="modal fade" id="ModalAvailability" tabindex="-1" role="dialog" aria-labelledby="ModalAvailabilityLabel" aria-hidden="true" data-toggle="validator">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
            <div class="modal-header">
				<h5 class="modal-title" id="exampleModalLiveLabel">Сообщить о поступлении товара на email</h5>
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
					<div class="fc_name">E-Mail <span>*</span></div>
					<div class="fc_input"><input type="text" name="email_modal" placeholder="Укажите ваш e-mail" required></div>
				</div>
            </div>
            <div class="modal-footer">
                <table class="zayvka_1click"><tbody><tr><td><input type="checkbox" name="politika" value="pk" required></td><td style="font-size:14px;padding:0 0 0 20px">Я соглашаюсь с<br><a href="/pages/privacy">политикой конфиденциальности.</a></td></tr></tbody></table>
				<button type="submit" name="availability" value="<?php echo md5(date('Y-m-d')); ?>" class="btn btn-danger">Отправить</button>
            </div>
        </div>
  </div>
</form>
<?php 
function urlimagesbase64($path) {
	$type = pathinfo($path, PATHINFO_EXTENSION);
	$data = file_get_contents($path);
	$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
	return $base64;
}
$logo = urlimagesbase64('images/Logo_round.jpg');
$logos = urlimagesbase64('images/logo-2.png');
$images = urlimagesbase64('images/product/baseimg/'.$product->img.'');
?>
<script>
document.getElementById('btnpdf').onclick = function() {
var docDefinition = {
	info: {
		title:'<?=$product->name;?>',
		author:'ИТС-Центр',
		subject:'Товары',
		keywords:'Шины, диски, фильтры, на спецтехнику'
	},
	
	pageSize:'A4',
	pageOrientation:'portrait',
	pageMargins:[30,30,30,30],
	content:[		
        {
			columns: [
				{
						image: "<?=$logo?>",
						width: 80
				},
				[
					{
						text:'Общество с ограниченной ответственностью «ИТС-Центр»',
						fontSize:14,
						alignment: 'center',
						margin:[0, 0, 0, 10]
					},
					{
						text:'142117, Московская область, г. Подольск, деревня Коледино, ул. Троицкая, д.1Г, стр.1, помещение В-348/49,\nтел./факс +7 (495) 424-98-90, e-mail: info@its50.ru, ИНН/КПП 5036103305/503601001, р/с 40702810901080002314\n в филиале «Центральный Банк ВТБ (ПАО), корр/с 30101810145250000411, БИК 044525411',
						alignment: 'center',
						fontSize:8,
						margin:[0, 0, 0, 15]
					}
				]			
				 
			]
			
		},
		{
			table: {
				widths:['*'],				
				body: [
					[
						{
							border: [false,'#00ffff', false, '#00ffff'],
							text:'',							
						}
					]
				]
			}
		},
		{
			columns: [
				{
					text:'Коммерческое предложение',
					fontSize:14,
					alignment: 'center',
					margin:[0, 20, 0, 0],
					bold: true
				}
			]
		},
		{
			columns: [
				{
					text:'<?=$product->name;?>',
					fontSize:14,
					alignment: 'center',
					margin:[0, 20, 0, 20],
					style: 'header',
					bold: true
				}
			]
		},
		{
			columns: [
				{
					stack: [
						{ 
							image: "<?=$images?>",
							width: 200,
							margin:[20, 20, 0, 0]
						},
						{
							text: 'Артикул: <?=$product->article?>',
							fontSize:8,
							alignment: 'center',
							margin: [0,20,0,20]
						}						
					]
				},
				[
					{
							table:{
							widths:['*'],							
							body:[	
									[{text:'Цена: <?=$curr['symbol_left'];?> <?=$price * $curr['value'];?>	<?=$curr['symbol_right'];?>', fontSize:14, bold: true}],
									[{text:'Цена актуальна на: <?=$product->data_edit_price;?>', fontSize:10}],
							],
							headerRows:1
						},
						layout: 'lightHorizontalLines',
						margin: [0,0,20,20]
					},
					{
						style: 'tableExample',
						table:{
							widths:['*','auto'],							
							body:[
								<?php foreach($attribute_group as $group): ?>
								[{text: '<?=$group["attribute_name"]?>',fontSize: 10, style: 'tableHeader'}, {text: ''}],					
									<?php foreach($attributs as $att): ?>
									[{text: '<?=$att["attribute_name"]?>',fontSize: 10, style: 'tableHeader'},{text: '<?=$att["attribute_text"]?>',fontSize: 10, style: 'tableHeader'}],
									<?php endforeach; ?>
								<?php endforeach; ?>
							],
							headerRows:1
						},
						layout: 'lightHorizontalLines',
						margin: [0,0,20,0]
					}
				]			
				 
			]
			
		},
		{
			columns: [
				{					
					text: '',
					margin: [0,50,0,30],
					fontSize:8
				}
			]				
		}
		
		
	],
	footer:[
		{				
			columns: [
				{
						image: "<?=$logos?>",
						width: 110,
						margin: [30,0]					
				},
				[
					{
						text:'Телефон: +7 (495) 424-98-90',
						fontSize:10,
						alignment: 'left',
						margin:[90, 0, 0, 10],
						width: 280						
					}
					
				],
				[
					{
						text:'Email: info@its-center.ru\nСайт: its-center.ru',
						fontSize:10,
						alignment: 'left',
						margin:[100, 0, 0, 10],
						width: 250
					}
				]				 
			]
		}
    
	],
	styles: {
		footer: {			
			margin:[30, 0, 30, 0],
			background: '#cccccc'
		}
	}
};

var win = window.open('', '_blank')
pdfMake.createPdf(docDefinition).open({}, win);
}
</script>
<!-- PDF -->
<script src="js/pdfmake.js"></script>
<script src="js/vfs_fonts.js"></script>

<!-- /PDF -->