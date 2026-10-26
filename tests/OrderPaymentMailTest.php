<?php
define('PATH','https://example.test');
$_SESSION=['cart'=>[],'cart.qty'=>0,'cart.sum'=>0,'cart.currency'=>['symbol_left'=>'','symbol_right'=>'₽']];
foreach(['order_id','order_prefix','tell_site','namecomp','dostavka_name','transport_company','branch_name','city_name','address','vid','compname','nds','dogovor','uname','telefon','user_email','formCallback','note','date','promo']as$name)$$name='';
$paymentLabel='Оплата по реквизитам';
ob_start();include dirname(__DIR__).'/app/views/itscenter/mail/mail_order.php';$html=ob_get_clean();
if(strpos($html,'Способ оплаты:')===false||strpos($html,$paymentLabel)===false)throw new RuntimeException('Payment absent from shared customer/manager mail template');
echo "Shared order email renders selected payment; no messages sent\n";
