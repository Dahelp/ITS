<?php
namespace app\services\admin {class AdminActivityLogger {const ACTION_ORDER_SITE=1;public static function incoming(...$args){}}}
namespace app\services {class TrafficSource {public static function code(){return 'direct_visit';}}}
namespace {
require dirname(__DIR__).'/vendor/autoload.php';
class R {
 public static $stored;public static $delivery='Самовывоз';public static $writes=0;
 public static function getCell($sql,$params){return self::$delivery;}
 public static function dispense($table){return new \stdClass();}
 public static function findLast($table){return(object)['id'=>10];}
 public static function store($bean){self::$stored=$bean;self::$writes++;return 11;}
 public static function exec(...$args){}
}
$_SESSION=['cart.currency'=>['code'=>'RUB'],'cart'=>[]];
$data=['user_id'=>1,'groups'=>3,'dostavka_id'=>1,'note'=>'test','address'=>'','transport_id'=>'','city_id'=>'','branch_id'=>'','comp_id'=>'','admin_id'=>0,'payment_method'=>'cash'];
\app\models\Order::saveOrder($data);if(R::$stored->payment_method!=='cash'||R::$stored->payment_customer_type!==3)throw new \RuntimeException('Payment not persisted');
R::$delivery='Курьер по г. Москва';$writes=R::$writes;try{\app\models\Order::saveOrder($data);throw new \RuntimeException('Invalid cash persisted');}catch(\DomainException$expected){}if(R::$writes!==$writes)throw new \RuntimeException('Store called for invalid payment');
$data['groups']=4;$data['nds']=1;$data['payment_method']='bank_transfer';\app\models\Order::saveOrder($data);if(R::$stored->payment_method!=='bank_transfer'||R::$stored->payment_customer_type!==4)throw new \RuntimeException('Legal bank not stored');
$data['payment_method']='card';$writes=R::$writes;try{\app\models\Order::saveOrder($data);throw new \RuntimeException('Legal card persisted');}catch(\DomainException$expected){}if(R::$writes!==$writes)throw new \RuntimeException('Invalid legal write');
echo "Order model: payment code and customer type persisted; invalid combinations rejected before write; no real orders or mail\n";
}
