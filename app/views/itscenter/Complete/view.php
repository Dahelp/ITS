<span itemscope itemtype="http://schema.org/Product">
<meta itemprop="name" content="<?=$complete->name?>" />
<span itemprop="brand" itemtype="https://schema.org/Brand" itemscope>
	<meta itemprop="name" content="<?=$vendor->name?>" />
</span>
<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <!--start-breadcrumbs-->
		<nav class="pt-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap" itemscope="" itemtype="http://schema.org/BreadcrumbList">
				<li class="breadcrumb-item">
					<span itemscope="" itemprop="itemListElement" itemtype="http://schema.org/ListItem">
						<a itemprop="item" class="text-nowrap" href="https://its-center.ru"><meta itemprop="name" content="Главная"><i class="fas fa-home"></i><meta itemprop="position" content="1"></a>
					</span>
				</li>
				<li class="breadcrumb-item">
					<span itemscope="" itemprop="itemListElement" itemtype="http://schema.org/ListItem">
						<a itemprop="item" class="text-nowrap" href="https://its-center.ru/complete">Комплекты товаров</a>
						<meta itemprop="name" content="Комплекты товаров"><link itemprop="item" href="https://its-center.ru/complete">
						<meta itemprop="position" content="2">
					</span>
				</li>
				<li class="breadcrumb-item text-nowrap active" data-id="3">
					<span itemscope="" itemprop="itemListElement" itemtype="http://schema.org/ListItem"><?=$complete->name?>
						<meta itemprop="name" content="<?=$complete->name?>">
						<link itemprop="item" href="https://its-center.ru/complete/<?=$complete->alias?>">
						<meta itemprop="position" content="3">
					</span>
				</li>
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
          <!-- Product Gallery + description-->
          <section class="row g-0 mx-n2">
            <div class="col-xl-6 byp-rght-pdd-15 mb-3 byp-float-lft">
              	<section class="slider">			
				<?php if($gallery): ?>		
				
					<div id="slider" class="flexslider">
					  <ul class="slides">
						<li><img itemprop="image" src="images/complete/baseimg/<?=$complete->img;?>" alt=""></li>
						<?php foreach($gallery as $item): ?>
						<li>
							<img itemprop="image" src="images/complete/gallery/<?=$item->img;?>" />
						</li>
						<?php endforeach; ?>
					  </ul>
					</div>
					<div id="carousel" class="flexslider">
					  <ul class="slides">
						<li><img itemprop="image" src="images/complete/baseimg/<?=$complete->img;?>" alt=""></li>
						<?php foreach($gallery as $item): ?>
						<li>
							<img itemprop="image" src="images/complete/gallery/<?=$item->img;?>" />
						</li>
						<?php endforeach; ?>
					  </ul>
					</div>
				
				<?php else: ?>
                    <div id="slider" class="flexslider">
					  <ul class="slides">
						<li><img itemprop="image" src="images/complete/baseimg/<?=$complete->img;?>" alt=""></li>
						</ul>
					</div>
				<?php endif; ?>
				</section>
            </div>
            <div class="col-xl-6 mb-3 byp-float-rght shadow" style="position: relative;display: grid;">
              <div class="h-100 bg-light rounded-3 p-4">
				<?php $administr = \R::findOne('user', 'id = ?', [$_SESSION['user']['id']]); ?>
				<?php if($administr['groups'] == "1") { ?>
					<div class="edit_prod"><a target="_blank" href="<?= ADMIN ?>/plagins/complete-edit?id=<?=$complete->id?>"><i class="far fa-edit"></i> Редактировать</a></div>
				<?php } ?>
				<a class="product-meta d-block fs-sm pb-2" href="category/<?=$cat_prod->alias?>" title="<?=$cat_prod->name?>"><?=$cat_prod->name?></a>				
                <h1 class="h3">
					<?=$complete->name?>
				</h1>
				<?php			
					$prods = \R::getAll("SELECT product.name, product.price as price, product.quantity, product.alias, plagins_complete_product.product_id, plagins_complete_product.qty, plagins_complete_product.price as price_complete, plagins_complete_product.discount FROM plagins_complete_product, product WHERE plagins_complete_product.product_id = product.id AND plagins_complete_product.complete_id = ?", [$complete["id"]]);
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
					}
					$prod_id = rtrim($prod_id, ',');
					$prod_qty = rtrim($prod_qty, ',');
					$itog_price_complete = $price_complete-$discount_complete;
					$prodid = rtrim($prodid, '-');
				?>
				<span itemprop="offers" itemtype="https://schema.org/Offer" itemscope>
				<link itemprop="url" href="<?=PATH?>/complete/<?=$complete->alias?>" />
				<meta itemprop="availability" content="https://schema.org/InStock" />
				<meta itemprop="priceCurrency" content="RUR" />
				<meta itemprop="itemCondition" content="https://schema.org/UsedCondition" />
				<meta itemprop="price" content="<?=$itog_price_complete * $curr['value'];?>" />
				<meta itemprop="priceValidUntil" content="<?=$complete->data_edit_price?>" />				
                <div class="fw-normal">
					<div class="item_price_2 text-accent" id="base-price" data-base="<?=$itog_price_complete * $curr['value'];?>">
						<span>Цена за полный комплект: </span>
						<?php if($discount_complete !=0) { ?>
							<del style="float: left;">
									<?=$curr['symbol_left'];?>
									<?=$price_complete * $curr['value'];?>
									<?=$curr['symbol_right'];?>
								</del>
						<?php } ?>
							<?=$curr['symbol_left'];?>
							<?=$itog_price_complete * $curr['value'];?>
							<?=$curr['symbol_right'];?>						
					</div>
				</div>				
				<div class="vnalichie">
					<?php if($itg_qty == count($prods)) { ?>
						<span class="btn-nalichie">Доступны все позиции.</span>
					<?php } ?>
					<?php if($itg_qty > 0 && $itg_qty < count($prods)) { ?>
						<span class="btn-postuplenie">Не полный комплект. Доступно <?php echo "".$itg_qty.""; if($itg_qty == 1) { echo " позиция"; } if($itg_qty > 1) { echo " позиции"; } ?>.</span>
					<?php } ?>
					<?php if($itg_qty == 0) { ?>
						<span class="btn-nonalichie">Нет в наличии</span>
					<?php } ?>						
				</div>
				<div class="vnalichie_quantuty">
					<?php 
						foreach($prods as $prod) {
							if($prod["quantity"]==0) {$ncolor = "text-danger";}
							if($prod["quantity"]>0) {$ncolor = "text-success";}
							echo "<a href=\"product/".$prod["alias"]."\" title=\"".$prod["name"]."\">".$prod["name"]."</a>: <span class=\"".$ncolor."\">в наличии ".$prod["quantity"]." шт.</span><br />";
						}					
					?>
				</div>
				<div class="d-flex flex-wrap align-items-center pt-4 pb-2 mb-3 quantity-complete">
			
				<?php if($itg_qty == count($prods)) { ?>				
					<input class="form-control" style="display:none;" name="quantity" type="number" value="<?=(int)$prod_qty?>" min="1" data-min="1">
					<a data-id="<?=$prodid;?>" data-complete="1" data-set="<?=$complete->id;?>" class="btn btn-soft-primary me-2 add-to-cart-complete korzina-<?=$complete->id;?> clear-korzina" href="cart/addcomplete?id=<?=$prodid;?>" data-toggle="modal" data-target="#exampleModalLive" onclick="ym(87229051,'reachGoal','VKORZINU'); return true;"><i class="fas fa-cart-plus"></i> Купить комплект</a>					
				<?php }elseif($itg_qty > 0 && $itg_qty < count($prods)) { ?>
					<input class="form-control" style="display:none;" name="quantity" type="number" value="<?=(int)$prod_qty?>" min="1" data-min="1">
					<a data-id="<?=$prodid;?>" data-complete="0" class="btn btn-soft-primary me-2 add-to-cart-complete korzina-<?=$complete->id;?> clear-korzina" href="cart/addcomplete?id=<?=$prodid;?>" data-toggle="modal" data-target="#exampleModalLive" onclick="ym(87229051,'reachGoal','VKORZINU'); return true;"><i class="fas fa-cart-plus"></i> Купить не полный комплект</a>				
				<?php }else{ } ?>
				
				</div>

				<div class="share">
					<script src="https://yastatic.net/share2/share.js"></script>
					<div class="ya-share2" data-curtain data-services="vkontakte,odnoklassniki,telegram,whatsapp"></div>
				</div>
				<div class="info">
					
					<?php $filters = \R::getAll("SELECT attribute_group.title, attribute_group.id FROM attribute_group, attribute_category WHERE attribute_category.group_id = attribute_group.id AND attribute_category.category_id = ?", [$complete->category_id]); ?>
					<ul class="list-unstyled">
							<li >Артикул: <span><?=$complete->article?></span></li>
						<?php foreach($filters as $filter) { ?>						
							<li ><?=$filter['title']?>: <span>
							<?php								
									$fv = \R::getAll("SELECT attribute_value.value, attribute_value.alias FROM attribute_group, attribute_product, attribute_value, plagins_complete_product WHERE plagins_complete_product.product_id = attribute_product.product_id AND attribute_product.attr_id = attribute_value.id AND attribute_value.attr_group_id = attribute_group.id AND plagins_complete_product.complete_id = ? AND attribute_group.id = ? GROUP BY attribute_value.value", [$complete->id, $filter['id']]); 
									$val = '';
									foreach($fv as $value) { 
										$val .= "".$value["value"].", ";
										
									}
								echo $val = rtrim($val, ', ');	
							?>
							</span></li>
						<?php } ?>
            		</ul>					
				</div>
				
				</span>
              </div>
			  <?php if($cat_prod->alias == "atv") { ?>
					<div class="articles_content">
						<a href="https://its-center.ru/articles/kak-uznat-razmer-shin-dlya-kvadrocikla" title="Как узнать размер шин для квадроцикла">Как узнать размер шин для квадроцикла?</a>
					</div>
				<?php } ?>
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
				<li class="nav-item" role="presentation">
					<a class="nav-link" id="pills-primenenie-tab" data-toggle="pill" href="#pills-primenenie" role="tab" aria-controls="pills-primenenie" aria-selected="true">Применяемость</a>
				</li>
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
				<table class="table">
					<tr style="display: flex;">
						<td style="width:250px;display: inline-block;padding: 0;margin: 0;border-bottom: 0px;">
							<table class="table table-bordered table-striped">								
								<?php 
									$attr_names = \R::getAll("SELECT attribute.attribute_name FROM attribute, attribute_comparison WHERE attribute_comparison.attribute_id = attribute.id AND attribute_comparison.category_id = ? ORDER BY attribute.attribute_position ASC", [$complete->category_id]);
								?>
								<?php foreach($attr_names as $attr): ?>
								<tr style="height:40px">
									<td class="compar-attr"><?=$attr["attribute_name"]?></td>
								</tr>
								<?php endforeach; ?>
							</table>
						</td>					
						<?php foreach($prods as $prods): ?>
								<td style="width:250px;display: inline-block;padding: 0;margin: 0;border-bottom: 0px;">
									<table class="table table-bordered table-striped">										
										<?php $attr_name = \R::getAll("SELECT attribute.id FROM attribute, attribute_comparison WHERE attribute_comparison.attribute_id = attribute.id AND attribute_comparison.category_id = ? ORDER BY attribute.attribute_position ASC", [$complete->category_id]); ?>
										<?php foreach($attr_name as $att): ?>
										<?php $attribute[$att['attribute_id']] = $att["attribute_text"]; echo $attribute[$att['attribute_id']];?>
										<?php $row = \R::getRow("SELECT * FROM attribute, product_attribute WHERE product_attribute.attribute_id = attribute.id AND product_attribute.attribute_id = '".$att["id"]."' AND product_attribute.product_id = '".$prods["product_id"]."'"); ?>
										<tr style="height:40px">
											<td class="compar-attr"><?php echo !empty($row["attribute_text"])? "".$row["attribute_text"]."": "-"?></td>
										</tr>
										<?php endforeach; ?>
									</table>
								</td>
						<?php endforeach; ?>
					</tr>
				</table>
				</div>		
				
				</div>
				<div class="tab-pane fade" id="pills-opisanie" role="tabpanel" aria-labelledby="pills-opisanie-tab" itemprop="description">
					<?php
						if($inseo->content) { 					
							echo $content = \ishop\App::seoreplace($inseo->content, $product->id);
						} 
						echo $complete->content;
					?>										
				</div>
				<?php
						//$technics = \R::getAll("SELECT technics.model, technics_manufacturer.name, technics.alias FROM technics_tiposize, attribute_value, technics, technics_manufacturer WHERE technics_manufacturer.id = technics.manufacturer_id AND technics.id = technics_tiposize.technics_id AND technics_tiposize.value_id = attribute_value.id AND attribute_value.value IN (".$tiposize.")");
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
				
			</div>
			</section>
		  <!-- /Descriptions -->
        
      </section>
    </div>
</div>
<!--end-single-->
        
      </section>
    </div>
</div>
<!--end-single-->
</span>