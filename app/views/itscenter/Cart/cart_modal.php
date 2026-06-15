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
                $isSet = !empty($item['set']);
                $min   = max(1, (int)($item['min'] ?? 1));
                $max   = max(1, (int)($item['max'] ?? PHP_INT_MAX));
                $pid = (int)($item['product_id'] ?? 0);
                $price = (string)($item['price'] ?? 0);
            ?>


                <tr data-product-id="<?= $pid ?>">
                    <td><a href="product/<?=$item['alias'];?>"><img src="images/product/mini/<?=$item['img'];?>" alt=""></a></td>
                    <td><a href="product/<?=$item['alias'];?>"><?=$item['name'];?></a><?php if($isSet) { ?><br />Комплект № <?=$item['set'];?><?php } ?></td>
                    <td style="text-align:center;width:72px">
                        <span data-id="<?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?>"
                            <?php if ($isSet): ?>data-complete-id="<?= (int)$item['set']; ?>"<?php endif; ?>
                            class="my-minus-<?= preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$id); ?><?= (!$isSet ? ' my-minus' : ' my-minus-complete'); ?>">
                            <i class="fa fa-minus" aria-hidden="true"></i>
                        </span>

                        <span class="qty-item"><?= (int)($item['qty'] ?? 0); ?></span>

                        <?php if ((int)($item['qty'] ?? 0) < $max): ?>
                            <span data-id="<?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?>"
                            <?php if ($isSet): ?>data-complete-id="<?= (int)$item['set']; ?>"<?php endif; ?>
                            class="my-plus-<?= preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$id); ?><?= (!$isSet ? ' my-plus' : ' my-plus-complete'); ?>">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?=$price?></td>
                    <td>
                    <span data-id="<?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?>"
                        <?php if ($isSet): ?>data-complete-id="<?= (int)$item['set']; ?>"<?php endif; ?>
                        class="glyphicon glyphicon-remove text-danger<?=(!$isSet ? ' del-item' : ' del-item-complete');?>"
                        aria-hidden="true"
                    ><i class="fas fa-times"></i></span>
                    </td>

                </tr>
            <?php endforeach; ?>
                <tr>
                    <td>Итого:</td>
                    <td colspan="4" class="text-right cart-qty-modal"><?=$_SESSION['cart.qty'];?></td>
                </tr>
                <tr>
                    <td>На сумму:</td>
                    <td colspan="4" class="text-right cart-sum-modal">
                    <?= $_SESSION['cart.currency']['symbol_left'] . $_SESSION['cart.sum'] . $_SESSION['cart.currency']['symbol_right']; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <h3>Корзина пуста</h3>
<?php endif; ?>
<span class="cart-qty-modal d-none"><?= (int)($_SESSION['cart.qty'] ?? 0); ?></span>
<span class="cart-sum-modal d-none">
  <?= ($_SESSION['cart.currency']['symbol_left'] ?? '') . (float)($_SESSION['cart.sum'] ?? 0) . ($_SESSION['cart.currency']['symbol_right'] ?? ''); ?>
</span>
