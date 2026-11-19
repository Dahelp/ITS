<?php
declare(strict_types=1);

// Isolated database double: no catalogue writes or external connections.
class R
{
    public static $product;
    public static $mods = [];
    public static function findOne($table, $where, $params) {
        return $table === 'product' ? self::$product : (self::$mods[$params[0]] ?? null);
    }
    public static function findAll($table, $where, $params) { return self::$mods; }
}
require dirname(__DIR__) . '/vendor/autoload.php';
$_SESSION = ['cart' => []];
$cart = (new ReflectionClass(app\models\Cart::class))->newInstanceWithoutConstructor();
foreach ([[14,100,14], [0,100,0], [14,0,14], [-2,100,0]] as [$quantity,$reserve,$expected]) {
    R::$product = (object)compact('quantity','reserve');
    if ($cart->availableForRow('595', ['product_id'=>595]) !== $expected) {
        throw new RuntimeException('Cart deducted reserve from free stock');
    }
}
R::$product = (object)['quantity'=>14,'reserve'=>100];
R::$mods = [7=>(object)['quantity'=>3,'reserve'=>100]];
foreach (['595-0'=>17,'595-7'=>3] as $key=>$expected) {
    if ($cart->availableForRow($key, ['product_id'=>595]) !== $expected) throw new RuntimeException('Variant stock mismatch');
}
$_SESSION['cart'] = ['595-0'=>['product_id'=>595,'qty'=>4]];
if ($cart->availableForRow('595', ['product_id'=>595]) !== 10) throw new RuntimeException('Shared cart stock guard lost');

// Execute both actual availability blocks (simple product and variants).
$source = file_get_contents(dirname(__DIR__) . '/app/views/itscenter/Product/view.php');
preg_match_all('~<div class="vnalichie">\s*<\?php(.*?)\?>~s', $source, $blocks);
if (count($blocks[1]) !== 2) throw new RuntimeException('Missing stock rendering blocks');
foreach ($blocks[1] as $block) {
    foreach ([[14,100], [0,100], [14,0]] as [$quantity,$reserve]) {
        $itog_qty = max(0,$quantity);
        $stockStatusId = 1;
        ob_start(); eval($block); $html = ob_get_clean();
        if ($quantity > 0 && (!str_contains($html,'14 шт.') || str_contains($html,'Свободного остатка нет'))) throw new RuntimeException('Incorrect available stock text');
        if ($quantity === 0 && (!str_contains($html,'Свободного остатка нет') || str_contains($html,'Весь товар'))) throw new RuntimeException('Incorrect zero stock text');
    }
}
if (preg_match('~(?:quantity|totalStock)\s*-\s*\$(?:reserve|reserveStock)~', $source)) throw new RuntimeException('Double reserve deduction remains in product view');
echo "Product free stock OK: 14 free / 100 reserved, zero stock, variants, cart limits and shared stock guard\n";
