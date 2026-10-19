<?php
declare(strict_types=1);

// Isolated cart calculation test: no database writes or real orders.
require dirname(__DIR__) . '/vendor/autoload.php';
class R {
    public static $stock = 12;
    public static function findOne($table, $sql, $args) {
        return (object)['quantity' => self::$stock];
    }
    public static function getAll($sql, $args) {
        return [['product_id' => 1, 'qty' => 2], ['product_id' => 2, 'qty' => 2]];
    }
}
function expectQuantity($expected, $actual, string $label): void {
    if ($expected != $actual) throw new RuntimeException($label . ': ' . json_encode($actual));
}
$cart = (new ReflectionClass(app\models\Cart::class))->newInstanceWithoutConstructor();
$row = ['product_id' => 1, 'qty' => 2, 'price' => 100, 'weight' => 3, 'volume' => 0.5, 'max' => 99];
$_SESSION = ['cart' => ['1' => $row]];
$cart->setQuantity('1', 5);
expectQuantity(5, $_SESSION['cart.qty'], 'quantity');
expectQuantity(500, $_SESSION['cart.sum'], 'sum');
expectQuantity(15, $_SESSION['cart.weight'], 'weight');
expectQuantity(2.5, $_SESSION['cart.volume'], 'volume');
$cart->setQuantity('1', 100);
expectQuantity(12, $_SESSION['cart']['1']['qty'], 'live stock limit');
$cart->setQuantity('1', 0);
expectQuantity(12, $_SESSION['cart']['1']['qty'], 'zero must not remove row');
R::$stock = 0;
$cart->setQuantity('1', 8);
expectQuantity(12, $_SESSION['cart']['1']['qty'], 'unavailable item must not increase');
R::$stock = 12;
$_SESSION = ['cart' => [
    's7_1' => $row + ['set' => '7', 'base_price' => 100, 'price_complete' => 100, 'price_discount' => 10],
    's7_2' => array_merge($row, ['product_id' => 2, 'set' => '7', 'base_price' => 100, 'price_complete' => 100, 'price_discount' => 10]),
]];
$cart->setQuantity('s7_1', 1);
expectQuantity(300, $_SESSION['cart.sum'], 'incomplete set loses discount');
$cart->setQuantity('s7_1', 2);
expectQuantity(360, $_SESSION['cart.sum'], 'complete set restores discount');
echo "Cart quantity tests passed\n";
