<?php
require dirname(__DIR__).'/app/services/CheckoutPickup.php';
use app\services\CheckoutPickup;
$branches=[['branch_id'=>0,'branch_name'=>'Подольск (мкр. Климовск)']];
foreach(['0','',null]as$id)if(CheckoutPickup::resolve($branches,$id)['branch_id']!==0)throw new RuntimeException('Pickup ID zero discarded');
foreach([['bad'],['999'],['0','1']]as$ids){
    try{CheckoutPickup::resolve(count($ids)>1?array_merge($branches,[['branch_id'=>1,'branch_name'=>'Other']]):$branches,$ids===['0','1']?'':$ids[0]);throw new RuntimeException('Bad pickup accepted');}
    catch(DomainException $expected){}
}
define('PATH','https://example.test');$_SESSION=['cart'=>[],'cart.qty'=>0,'cart.sum'=>0,'cart.currency'=>['symbol_left'=>'','symbol_right'=>'₽']];
foreach(['order_id','order_prefix','tell_site','namecomp','dostavka_name','transport_company','branch_name','city_name','address','vid','compname','nds','dogovor','uname','telefon','user_email','formCallback','note','date','promo']as$name)$$name='';
foreach(['','Подольск (мкр. Климовск)']as$branchName){
    $pickupDetails=CheckoutPickup::details($branchName);ob_start();include dirname(__DIR__).'/app/views/itscenter/mail/mail_order.php';$html=ob_get_clean();
    foreach(['Пункт самовывоза:','Климовск','Адрес самовывоза:','Коммунальная','26, стр. 2']as$text)if(!str_contains($html,$text))throw new RuntimeException('Pickup email incomplete: '.$text);
}
if(CheckoutPickup::details('Другой пункт')['address']!=='')throw new RuntimeException('Klimovsk address applied to unrelated branch');
echo "CR10 pickup: zero ID and sole-branch fallback preserved, invalid choices rejected, city and full address rendered for current/historical orders; no mail sent\n";
