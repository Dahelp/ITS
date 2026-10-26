<?php
$_SESSION=['cart.qty'=>2,'cart.sum'=>1500,'cart.currency'=>['symbol_left'=>'','symbol_right'=>'₽'],'cart'=>['<script>'=>['product_id'=>1,'qty'=>2,'max'=>5]],'cart.quantity_notice'=>'Limited <script>'];
ob_start();include dirname(__DIR__).'/app/views/itscenter/Cart/cart_state.php';$html=ob_get_clean();
if(strpos($html,'<script>')!==false||strpos($html,'my-plus')!==false||strpos($html,'cart-state-qty">2')===false||strpos($html,'data-product-id="1"')===false||isset($_SESSION['cart.quantity_notice']))throw new RuntimeException('Unsafe or incomplete cart state');
$_SESSION=[];ob_start();include dirname(__DIR__).'/app/views/itscenter/Cart/cart_state.php';$empty=ob_get_clean();if(strpos($empty,'cart-state-qty">0')===false)throw new RuntimeException('Empty state marker absent');
echo "Cart state response: escaped metadata, quantities, one-time feedback and empty state OK\n";
