<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CheckoutPayment as Payment;
function payCheck($ok,$message){if(!$ok)throw new RuntimeException($message);}
foreach([true,false]as$pickup){$options=Payment::options(3,$pickup);payCheck(count($options)===($pickup?4:3),'Physical options');foreach($options as$code=>$label)payCheck(Payment::validate($code,3,$pickup)===$code,'Physical method accepted');payCheck(Payment::options(4,$pickup)===['bank_transfer'=>'Оплата по реквизитам'],'Legal bank only');payCheck(Payment::validate('',4,$pickup)==='bank_transfer','Legal automatic bank');}
foreach([['cash',3,false],['card',4,true],['qr',4,false],['cash',4,true],['online',3,true],['',3,true],[['card'],3,true],['card',0,true]]as[$code,$group,$pickup]){try{Payment::validate($code,$group,$pickup);throw new RuntimeException('Invalid payment accepted');}catch(DomainException$expected){}}
payCheck(Payment::group(4,3)===4&&Payment::group(3,4)===3,'Account type is authoritative');payCheck(Payment::group(0,4)===4&&Payment::group(0,[])===0,'Guest kind whitelist');
$rules=Payment::rules([['id'=>1,'name'=>'Самовывоз'],['id'=>2,'name'=>'Транспортная компания'],['id'=>3,'name'=>'Курьер по г. Москва']]);payCheck($rules['pickupIds']===['1'],'Real pickup mapping');
payCheck(Payment::label('bank_transfer',4)==='Оплата по реквизитам'&&Payment::label('',3)==='Не указан','Stored label and old orders');
echo "Payment policy: physical/legal, pickup-only cash, authoritative account and invalid values OK\n";
