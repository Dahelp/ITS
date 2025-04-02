<?php if(!empty($_SESSION['cart'])): ?>
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

            <?php foreach($_SESSION['cart'] as $id => $item): 
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
                    <td><a href="product/<?=$item['alias'];?>"><img src="images/product/mini/<?=$item['img'];?>" alt=""></a></td>
                    <td><a href="product/<?=$item['alias'];?>"><?=$item['name'];?></a><?php if($item['set']) { ?><br />Комплект № <?=$item['set'];?><?php } ?></td>
                    <td style="text-align:center;width:72px">
						<?php if($item['qty'] > 1) { ?><span data-id="<?=$id;?>" <?php if($item['set']) { ?>data-min="<?=$item["min"];?>" data-set="<?=$item["set"];?>"<?php } ?> class="my-minus-<?=$id;?><?php if(!$item['set']) { ?> my-minus<?php }else{ ?> my-minus-complete<?php } ?>"><i class="fa fa-minus" aria-hidden="true"></i></span><?php } ?>
						<span class="qty-item"><?=$item['qty'];?></span>
						<?php if($item['qty'] < $item['max']) { ?><span data-id="<?=$id;?>" <?php if($item['set']) { ?>data-min="<?=$item["min"];?>" data-set="<?=$item["set"];?>"<?php } ?> class="my-plus-<?=$id;?> <?php if(!$item['set']) { ?> my-plus<?php }else{ ?> my-plus-complete<?php } ?>"><i class="fa fa-plus" aria-hidden="true"></i></span><?php } ?>
					</td>
                    <td><?=$price?></td>
                    <td><span data-id="<?=$id;?>" <?php if($item['set']) { ?>data-min="<?=$item["min"];?>" data-set="<?=$item["set"];?>"<?php } ?> class="glyphicon glyphicon-remove text-danger<?php if(!$item['set']) { ?> del-item<?php }else{ ?> del-item-complete<?php } ?>" aria-hidden="true"><i class="fas fa-times"></i></span></td>
                </tr>
            <?php endforeach; ?>
                <tr>
                    <td>Итого:</td>
                    <td colspan="4" class="text-right cart-qty"><?=$_SESSION['cart.qty'];?></td>
                </tr>
                <tr>
                    <td>На сумму:</td>
                    <td colspan="4" class="text-right cart-sum"><?= $_SESSION['cart.currency']['symbol_left'] . $_SESSION['cart.sum'] . $_SESSION['cart.currency']['symbol_right'];?></td>
                </tr>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <h3>Корзина пуста</h3>
<?php endif; ?>