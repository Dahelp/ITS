<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
class R {
    public static $rate = 5;
    public static $active = true;
    public static function getRow($sql, $args) {
        if (strpos($sql, "hide = 'show'") === false) throw new RuntimeException('Active status not checked');
        return self::$active && $args[0] === 'TEST' ? ['id' => 7, 'value' => self::$rate] : [];
    }
    public static function getCol($sql, $args) { return [10]; }
    public static function getAll($sql, $args) { return []; }
    public static function getCell($sql, $args) { return $args[0] === 2 ? 20 : 10; }
    public static function findOne($table, $sql, $args) {
        if ($table === 'actions') return $args[0] === 3 ? (object)['id' => 1] : null;
        return (object)['quantity' => 100, 'reserve' => 0];
    }
    public static function findAll($table, $sql, $args) { return []; }
}
function promoExpect($expected, $actual, $message) {
    if ($expected != $actual) throw new RuntimeException($message . ': ' . json_encode($actual));
}
$cart = (new ReflectionClass(app\models\Cart::class))->newInstanceWithoutConstructor();
$row = ['product_id' => 1, 'qty' => 1, 'price' => 1000, 'base_price' => 1000, 'weight' => 0, 'volume' => 0];
$_SESSION = ['cart' => [1 => $row, 2 => array_merge($row, ['product_id' => 2]),
    3 => array_merge($row, ['product_id' => 3]),
    's8_4' => array_merge($row, ['product_id' => 4, 'set' => '8', 'complete_discount_active' => 1, 'price_complete' => 1000, 'price_discount' => 200, 'price' => 800])],
    'cart.currency' => ['value' => 1]];
$cart->promocartItem('TEST');
promoExpect(950, $_SESSION['cart'][1]['price'], 'configured 5 percent');
promoExpect(1000, $_SESSION['cart'][2]['price'], 'other category excluded');
promoExpect(1000, $_SESSION['cart'][3]['price'], 'sale excluded');
promoExpect(800, $_SESSION['cart']['s8_4']['price'], 'set discount preserved');
promoExpect(3750, $_SESSION['cart.sum'], 'actual total');
$cart->setQuantity('1', 2);
promoExpect(950, $_SESSION['cart'][1]['price'], 'quantity change does not compound discount');
R::$rate = 2;
$cart->promocartItem('TEST');
promoExpect(980, $_SESSION['cart'][1]['price'], 'updated percentage applied');
$cart->promocartItem('INVALID');
promoExpect('TEST', $_SESSION['promocart'], 'invalid replacement preserves applied code');
promoExpect(980, $_SESSION['cart'][1]['price'], 'invalid replacement preserves consistent price');
$cart->clearpromoItem();
promoExpect(1000, $_SESSION['cart'][1]['price'], 'remove restores base price');
R::$active = false;
$cart->promocartItem('TEST');
promoExpect(false, isset($_SESSION['promocart']), 'inactive code rejected');
R::$active = true;
R::$rate = 150;
$cart->promocartItem('TEST');
promoExpect(false, isset($_SESSION['promocart']), 'invalid rate rejected');
R::$rate = 5;
$cart->promocartItem('TEST');
R::$active = false;
$cart->reconcileAvailability();
promoExpect(1000, $_SESSION['cart'][1]['price'], 'disabled code removed before checkout');
echo "CartPromoTest OK\n";
