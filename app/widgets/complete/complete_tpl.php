<?php $prods = \R::getAll("SELECT * FROM plagins_complete_product WHERE complete_id=?", [$complete["id"]]);

	foreach($prods as $prod) {
		$price_complete += $prod["price"]*$prod["qty"];
		$discount_complete += $prod["discount"]*$prod["qty"];
		$quant = \R::findOne('product', 'id = ?', [$prod["product_id"]]);

		if($quant["quantity"]>=$prod["qty"]) {
			$quantity = 1;
		}else{
			$quantity = 0;
		}
		
		$itg_qty += $quantity;		
	}
	$itog_price_complete = $price_complete-$discount_complete;

 ?>
<div class="card product-card card-static pb-3">				            
	<a class="card-img-top d-block overflow-hidden" href="complete/<?=$complete["alias"]?>">							
		<img src="images/complete/mini/<?=$complete["img"]?>" alt="<?=$complete["name"]?>" title="<?=$complete["name"]?>" />
	</a>
	<?php $cat_prod = \R::findOne('category', "id = ?", [$complete["category_id"]]); ?>
	<div class="card-body py-2">
		<span class="product-meta d-block fs-xs pb-1"><?=$cat_prod["name"]?></span>			
		<h2 class="product-title fs-sm text-truncate">
			<?=$complete["name"]?>
		</h2>
		<div class="product-info">
			<div class="product-price">				
				<div class="product-curr">				
					<span class="item_price">						
						<?=$curr['symbol_left'];?>
						<?=$itog_price_complete * $curr['value'];?>
						<?=$curr['symbol_right'];?>
					</span>
					<?php if($discount_complete !=0) { ?>
					<del style="float: left;">
						<?=$curr['symbol_left'];?>
						<?=$price_complete * $curr['value'];?>
						<?=$curr['symbol_right'];?>
					</del>
					<?php } ?>
				</div>
			</div>
			<div class="product-btn">
				<div class="product-floating-btn">
					<a class="btn btn-danger" href="complete/<?=$complete["alias"]?>">Подробнее</a>					
				</div>
			</div>
		</div>
		<div class="product-nalichie">
		<?php if($itg_qty == count($prods)) { ?>
			<span class="btn-nalichie">В наличии</span>
		<?php } ?>
		<?php if($itg_qty > 0 && $itg_qty < count($prods)) { ?>
			<span class="btn-postuplenie">Не полный комплект</span>
		<?php } ?>
		<?php if($itg_qty == 0) { ?>
			<span class="btn-nonalichie">Нет в наличии</span>
		<?php } ?>
		</div>
	</div>
</div>				            