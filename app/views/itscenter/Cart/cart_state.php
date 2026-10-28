<?php
// AJAX state fragment; it is parsed by cart JS and is never opened as a dialog.
$e=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
?>
<div class="cart-state" hidden>
 <span class="cart-state-qty"><?=(int)($_SESSION['cart.qty']??0)?></span>
 <span class="cart-state-sum"><?=$e(($_SESSION['cart.currency']['symbol_left']??'').(float)($_SESSION['cart.sum']??0).($_SESSION['cart.currency']['symbol_right']??''))?></span>
 <?php if(!empty($_SESSION['cart.quantity_notice'])):?><span class="cart-feedback"><?=$e($_SESSION['cart.quantity_notice'])?></span><?php unset($_SESSION['cart.quantity_notice']);endif;?>
 <table><tbody><?php foreach(($_SESSION['cart']??[])as$key=>$item):?>
  <tr data-set="<?=$e($item['set']??'')?>" data-product-id="<?=(int)($item['product_id']??0)?>" data-cart-key="<?=$e($key)?>" data-qty="<?=(int)($item['qty']??0)?>" data-max="<?=(int)($item['max']??PHP_INT_MAX)?>"><td class="qty-item"><?=(int)($item['qty']??0)?></td></tr>
 <?php endforeach;?></tbody></table>
</div>
