<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
class R {
    public static $sql = '';
    public static function exec($sql) { self::$sql = $sql; }
}
$_SESSION = ['promocart' => 'TEST', 'cart' => [
    1 => ['product_id' => 1, 'article' => 'A', 'qty' => 2, 'unit' => 'pc', 'name' => 'eligible',
          'base_price' => 1000, 'price' => 950, 'promo_applied' => 1, 'promo_rate' => 5],
    2 => ['product_id' => 2, 'article' => 'B', 'qty' => 1, 'unit' => 'pc', 'name' => 'excluded',
          'base_price' => 1000, 'price' => 1000, 'promo_applied' => 0],
]];
app\models\Order::saveOrderProduct(123);
if (strpos(R::$sql, "'1000', '5', '3', '50', '950', '100'") === false) {
    throw new RuntimeException('Saved discount must match actual charged amount and quantity');
}
if (strpos(R::$sql, "'excluded', '1000', '', '', '', '', ''") === false) {
    throw new RuntimeException('Excluded row must not record promo discount');
}
echo "OrderPromoTest OK\n";
