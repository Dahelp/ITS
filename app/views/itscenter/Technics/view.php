<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
				<li class="breadcrumb-item"><a href="technics">Каталог техники</a></li>
				<li class="breadcrumb-item"><a href="technics/type/<?=$type["alias"]?>">Производители <?=$type["seoname_1"]?></a></li>
				<li class="breadcrumb-item"><a href="technics/<?=$type["alias"]?>/<?=$manufacturer["alias"]?>"><?php echo \ishop\App::upFirstLetter($type["seoname_3"]);?> <?=$manufacturer["name"]?></a></li>
				<li class="breadcrumb-item active"><?=$type->name?> <?=$manufacturer->name?> <?=$technics->model?></li>
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
<div class="single contact">
    <div class="container">
        <section>
          <!-- Content-->
          <!-- Product Gallery + description-->
          <section class="row g-0 mx-n2">
            <div class="col-xl-6 byp-rght-pdd-15 mb-3 byp-float-lft">
              	<section class="slider">			
				<?php if($gallery): ?>		
				
					<div id="slider" class="flexslider">
					  <ul class="slides">						
						<li><img itemprop="image" src="images/technics/baseimg/<?=$technics->img;?>" alt=""></li>
						<?php foreach($gallery as $item): ?>
						<li>
							<img itemprop="image" src="images/technics/gallery/<?=$item->img;?>" />
						</li>
						<?php endforeach; ?>						
					  </ul>
					</div>
					<div id="carousel" class="flexslider">
					  <ul class="slides">
						<li><img itemprop="image" src="images/technics/baseimg/<?=$technics->img;?>" alt=""></li>
						<?php foreach($gallery as $item): ?>
						<li>
							<img itemprop="image" src="images/technics/gallery/<?=$item->img;?>" />
						</li>
						<?php endforeach; ?>
					  </ul>
					</div>
				
				<?php else: ?>
                    <div id="slider" class="flexslider">
					  <ul class="slides">
						<?php if($technics->img) { ?>
						<li><img itemprop="image" src="images/technics/baseimg/<?=$technics->img;?>" alt=""></li>
						<?php }else{ ?>
						<li>
							<img itemprop="image" src="images/no_image.jpg" style="width:250px" />
						</li>
						<?php } ?>
						</ul>
					</div>
				<?php endif; ?>
				</section>
            </div>
            <div class="col-xl-6 mb-3 byp-float-rght shadow" style="position: relative;">
              <div class="h-100 bg-light rounded-3 p-4">
				<?php $administr = \R::findOne('user', 'id = ?', [$_SESSION['user']['id']]); ?>
				<?php if($administr['groups'] == "1") { ?>
					<div class="edit_prod"><a target="_blank" href="<?= ADMIN ?>/plagins/technics-edit?id=<?=$technics->id?>"><i class="far fa-edit"></i> Редактировать</a></div>
				<?php } ?>
							
                <h1 class="h3">
					<?=$type->name?> <?=$manufacturer->name?> <?=$technics->model?>
				</h1>
			
                <div class="fw-normal">											
						<table class="table table-bordered table-striped">						
							<thead>
                                <tr>
                                    <td colspan="2" class="hide_td">
										<div class="hide_td_1"><strong>Характеристики:</strong></div>											    
									</td>
                                </tr>
                            </thead>
							<tbody>
								<tr><td>Тип техники:</td><td><?=$type->name?></td></tr>
								<tr><td>Производитель:</td><td><?=$manufacturer->name?></td></tr>
								<tr><td>Модель техники:</td><td><?=$technics->model?></td></tr>								
							</tbody>
							<?php	
									$prodsizes = \R::getAll("SELECT * FROM technics_tiposize, attribute_value WHERE technics_tiposize.value_id = attribute_value.id AND technics_tiposize.technics_id = '".$technics->id."'");
									foreach($prodsizes as $prodsize) {										
										$psize .= "'".$prodsize["value"]."', ";										
									}									
									$psize = rtrim($psize, ", ");
									
									$sizes = \R::getAll("SELECT * FROM technics_tiposize, attribute_value WHERE technics_tiposize.value_id = attribute_value.id AND technics_tiposize.technics_id = '".$technics->id."' AND tip_size = '1'");
									$sizes_back = \R::getAll("SELECT * FROM technics_tiposize, attribute_value WHERE technics_tiposize.value_id = attribute_value.id AND technics_tiposize.technics_id = '".$technics->id."' AND tip_size = '2'");
									$sizes_alt = \R::getAll("SELECT * FROM technics_tiposize, attribute_value WHERE technics_tiposize.value_id = attribute_value.id AND technics_tiposize.technics_id = '".$technics->id."' AND tip_size = '3'");
									$sizes_alt_back = \R::getAll("SELECT * FROM technics_tiposize, attribute_value WHERE technics_tiposize.value_id = attribute_value.id AND technics_tiposize.technics_id = '".$technics->id."' AND tip_size = '4'");
									$sizes_vse = \R::getAll("SELECT * FROM technics_tiposize, attribute_value WHERE technics_tiposize.value_id = attribute_value.id AND technics_tiposize.technics_id = '".$technics->id."'");
									if($sizes or $sizes_back or $sizes_alt or $sizes_alt_back) {
										
							?>
							<thead>
                                <tr>
                                    <td colspan="2" class="hide_td">
										<div class="hide_td_1"><strong>Заводские размеры шин:</strong></div>											    
									</td>
                                </tr>
                            </thead>
							<tbody>
								<?php if($sizes AND $sizes_back) { ?>
								<tr><td>Размер передних:</td><td>
								<?php
									 
									foreach($sizes as $size) {
										$vsize .= "<a href=\"size/".$size["alias"]."\" title=\"Все шины размера ".$size["value"]."\">".$size["value"]."</a>, ";																			
									} 
									echo $vsize = rtrim($vsize, ", ");								
								 ?>
								</td></tr>
								<tr><td>Размер задних:</td><td>
								<?php
									 
									foreach($sizes_back as $back) {
										$bsize .= "<a href=\"size/".$back["alias"]."\" title=\"Все шины размера ".$back["value"]."\">".$back["value"]."</a>, ";																			
									} 
									echo $bsize = rtrim($bsize, ", ");									
								 ?>
								</td></tr>
								<?php }else{ ?>
								<tr><td>Размер:</td><td>
								<?php
									 
									foreach($sizes as $size) {
										$vsize .= "<a href=\"size/".$size["alias"]."\" title=\"Все шины размера ".$size["value"]."\">".$size["value"]."</a>, ";																		
									} 
									echo $vsize = rtrim($vsize, ", ");									
								 ?>
								</td></tr>
								<?php } ?>
							</tbody>
							
							<?php if($sizes_alt or $sizes_alt_back) { ?>
							<thead>
                                <tr>
                                    <td colspan="2" class="hide_td">
										<div class="hide_td_1"><strong>Альтернативные размеры шин:</strong></div>											    
									</td>
                                </tr>
                            </thead>
							<tbody>
							<?php if($sizes_alt AND $sizes_alt_back) { ?>
								<tr><td>Размер передних:</td><td>
								<?php
									 
									foreach($sizes_alt as $asize) {
										$vasize .= "<a href=\"size/".$asize["alias"]."\" title=\"Все шины размера ".$asize["value"]."\">".$asize["value"]."</a>, ";																			
									} 
									echo $vasize = rtrim($vasize, ", ");								
								 ?>
								</td></tr>
								<tr><td>Размер задних:</td><td>
								<?php
									 
									foreach($sizes_alt_back as $aback) {
										$basize .= "<a href=\"size/".$aback["alias"]."\" title=\"Все шины размера ".$aback["value"]."\">".$aback["value"]."</a>, ";																			
									} 
									echo $basize = rtrim($basize, ", ");									
								 ?>
								</td></tr>
								<?php }else{ 
								if($sizes_alt) {
								?>
								<tr><td>Размер:</td><td>
								<?php
									 
									foreach($sizes_alt as $asize) {
										$vasize .= "<a href=\"size/".$asize["alias"]."\" title=\"Все шины размера ".$asize["value"]."\">".$asize["value"]."</a>, ";																		
									} 
									echo $vasize = rtrim($vasize, ", ");
									
								 ?>
								</td></tr>
								<?php } 
								if($sizes_alt_back) {
								?>
								<tr><td>Размер:</td><td>
								<?php
									 
									foreach($sizes_alt_back as $aback) {
										$basize .= "<a href=\"size/".$aback["alias"]."\" title=\"Все шины размера ".$aback["value"]."\">".$aback["value"]."</a>, ";																			
									} 
									echo $basize = rtrim($basize, ", ");									
								 ?>
								</td></tr>
								<?php } } ?>
							</tbody>
							<?php } ?>
							
							<?php } ?>
						</table>					
				</div>
								
				
              </div>
            </div>
          </section>
		  
		  <?php
				$values = \R::getAll("SELECT * FROM attribute_value WHERE value IN ($psize)");
				
				foreach($values as $v) {
								
					$ids = \R::getAll("SELECT product_id FROM attribute_product, product WHERE attribute_product.product_id = product.id AND attribute_product.attr_id = '".$v["id"]."'");
					if($ids){
						foreach($ids as $ds){
							$prid .= "".$ds["product_id"].",";
						}
						$ids = rtrim($prid, ',');
						
						$products = \R::find('product', "hide = 'show' AND id IN ($ids)");
					}
				}
		  
				if($ids){
		  
				$complete = \R::getAll("SELECT*FROM `plagins_complete_product`, `plagins_complete` WHERE plagins_complete_product.complete_id = plagins_complete.id AND plagins_complete_product.product_id IN (".$ids.") GROUP BY plagins_complete.id");
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
								$price_complete[$cpl["id"]] += $prod["price_complete"]*$prod["qty"];
								$discount_complete[$cpl["id"]] += $prod["discount"]*$prod["qty"];
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
								$itg_qty[$cpl["id"]] += $quantity;
								$vcomplecte[$cpl["id"]] += $prod["qty"];
							}
							$prod_id = rtrim($prod_id, ',');
							$prod_qty = rtrim($prod_qty, ',');
							$itog_price_complete[$cpl["id"]] = $price_complete[$cpl["id"]]-$discount_complete[$cpl["id"]];
							$prodid = rtrim($prodid, '-');
						?>
						<div class="complete-main-prod bg-grad-4">
							<div class="col-md-4 cmp-1"><img src="../images/complete/mini/<?=$cpl["img"]?>" alt="<?=$cpl["name"]?>" title="<?=$cpl["name"]?>" /></div>
							<div class="col-md-4 cmp-2"><span><?=$cpl["name"]?></span><br />В комплекте <?=$vcomplecte[$cpl["id"]]?> шт.</div>
							<div class="col-md-4 cmp-3 quantity-complete">
								<div class="complete_price">Цена за комплект<br><span><?=$itog_price_complete[$cpl["id"]]?> <?=$curr['symbol_right'];?><span></div>
								
								<?php if($itg_qty[$cpl["id"]] == count($prods)) { ?>				
									<input class="form-control" style="display:none;" name="quantity" type="number" value="<?=$cpl["qty"]?>" min="1" data-min="1">
									<a data-id="<?=$prodid;?>" data-complete="1" data-set="<?=$cpl["id"];?>" class="btn btn-success me-2 add-to-cart-complete korzina-<?=$complete->id;?> clear-korzina" href="cart/addcomplete?id=<?=$prodid;?>" data-toggle="modal" data-target="#exampleModalLive" onclick="ym(87229051,'reachGoal','VKORZINU'); return true;"><i class="fas fa-cart-plus"></i> Купить комплект</a>					
								<?php } if($itg_qty[$cpl["id"]] > 0 && $itg_qty[$cpl["id"]] < count($prods)) { ?>
									<input class="form-control" style="display:none;" name="quantity" type="number" value="<?=$cpl["qty"]?>" min="1" data-min="1">
									<a data-id="<?=$prodid;?>" data-complete="0" class="btn btn-success me-2 add-to-cart-complete korzina-<?=$cpl["id"];?> clear-korzina" href="cart/addcomplete?id=<?=$prodid;?>" data-toggle="modal" data-target="#exampleModalLive" onclick="ym(87229051,'reachGoal','VKORZINU'); return true;"><i class="fas fa-cart-plus"></i> Купить не полный комплект</a>				
								<?php } if($itg_qty[$cpl["id"]] == 0){ ?>
									<button class="btn btn-success me-2">Нет в наличии</button>
								<?php } ?>
								<a class="btn btn-danger" href="complete/<?=$cpl["alias"]?>">Подробнее</a>
							</div>
							
						</div>								
					<?php } ?>
					</div>
				</div>
			</section>
				<?php } } ?>		  
		 
		  <?php 
		  if($psize) {
		   ?>
		  <div class="desc-prod-inner row g-0 mx-n2 product-one">
			<?php 
				if($values) {			
				
				foreach($products as $product){ ?>
					<?php $curr = \ishop\App::$app->getProperty('currency'); ?>
					<div class="col-xl-3 col-lg-6 col-md-4 col-sm-6 mb-3">
					    <?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php'); ?>
				    </div>

				<?php } ?>
		  </div>
		  <?php } }?>
		  <div class="catalog_text">
			<?=$technics->content?>
			<?php 
				foreach($sizes_vse as $vse) {
					$vsesize .= "<a href=\"size/".$vse["alias"]."\" title=\"Все шины размера ".$vse["value"]."\">".$vse["value"]."</a>, ";																			
				}
				$vsesize = rtrim($vsesize, ", ");
			?>
			<p>Шины для <?=$type["seoname_2"]?> занимают не последнее место в перечне запчастей к <?php if($type->name != "Квадроцикл") { ?>спецтехнике<?php }else{ ?>колёсной мототехнике<?php } ?>. Основное предназначение покрышек демпфирование ударов, передаваемых подвеске и мосту от покрытия и обеспечение достаточного сцепления колес с грунтом. От конструкции и качества шин для <?php if($type->name != "Квадроцикл") { ?>специализированной <?php }else{ ?>мото<?php } ?>техники зависят коэффициент сцепления, расход топлива, проходимость в целом эффективность работы транспортного средства.</p>
			<p><?php if($type->name != "Квадроцикл") { ?>Спецтехника<?php }else{ ?>Квадроцикл<?php } ?>, которая интенсивно эксплуатируется с большими нагрузками, требует регулярной замены резины. При этом шины для <?=$type["seoname_1"]?> должны быть качественными, прочными и износостойкими. Всем этим критериям отвечает резина от различных производителей, которые предлагает ООО ИТС-Центр.</p>
			<p>На данный тип <?php if($type->name != "Квадроцикл") { ?>спецтехники<?php }else{ ?>техники<?php } ?> <?=$type->name?> <?=$manufacturer->name?> <?=$technics->model?> ООО ИТС-Центр предлагает резину размеры наружного и посадочного диаметра <?=$vsesize?>, которые реализуются нашей компанией. Шины отличаются по материалу и способу изготовления и делятся на два типа: <?php if($type->name != "Квадроцикл") { ?>цельнолитые и диагональные<?php }else{ ?>направленый и ненаправленный рисунок протектора<?php } ?>.</p>
			<p>Именно такие шины, которые соответствуют самым строгим требованиям нормативов и стандартов, и реализует наша компания.</p>

			<h2>Преимущества шин для <?=$type["seoname_1"]?> от нашей компании</h2>
			<ul>
				<li>Мы поставляем сверхпрочные шины, которые характеризуются следующими качествами</li>
				<li>повышенная износостойкость</li>
				<li>длительный срок эксплуатации</li>
				<li>улучшенное сцепление и управляемость <?=$type["seoname_2"]?></li>
				<li>надежная посадка на обод колеса</li>
				<li>стойкость к повреждениям шин</li>
				<li>легкость монтажа на <?php echo \ishop\App::downFirstLetter($type->name);?></li>
				<li>отличная амортизация ударов</li>
			</ul>
			<p>Продукция, представленная в каталоге, имеется в наличии на нашем складе. Вы сможете самостоятельно подобрать шины для <?=$type["seoname_2"]?> <?=$manufacturer->name?> <?=$technics->model?> в размере <?=$vsesize?> либо воспользоваться консультацией наших специалистов. У нас можно приобрести резину по минимальным ценам. На крупные заказы и для постоянных клиентов имеется система скидок.</p>
			<p>Купить шины для <?php if($type->name != "Квадроцикл") { ?>погрузчика<?php }else{ ?>техники<?php } ?> можно, найдя их в каталоге различных брендов на сайте its-center.ru. Наш интернет-магазин предоставляет широкий ассортимент товаров. Найти нужный товар можно по указанному артикулу. Если вы решили купить шины на <?php echo \ishop\App::downFirstLetter($type->name);?> <?=$manufacturer->name?> <?=$technics->model?> в размере <?=$vsesize?> ждем ваших звонков.</p>
			<p>Наши консультанты знают все о продукции и предоставят квалифицированную помощь при выборе шин на <?php if($type->name != "Квадроцикл") { ?>спецтехнику<?php }else{ ?>квадроцикл<?php } ?>. Они подберут резину нужного размера, с подходящим рисунком протектора и оптимальной нормой слойности. У нас вы найдете шины проверенных торговых марок, поэтому можете быть уверены в их качестве и долговечности.</p>

		  </div>
      </section>
    </div>
</div>
<!--end-single-->


