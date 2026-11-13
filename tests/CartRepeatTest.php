<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

class R {
    public static function findOne($table, $sql, $args) {
        if ($table === 'modification') return $args[1] === 'variant'
            ? (object)['id' => 7, 'price' => 250] : null;
        if ($args[0] === 2) return null; // Hidden or removed product.
        return (object)['id' => $args[0], 'article' => 'base', 'price' => $args[0] === 3 ? 0 : 200];
    }
}
class RepeatTestCart extends app\models\Cart {
    public function __construct() {}
    public function availableForRow($id, array $item): int { return 5; }
    public function addToCart($product, $qty = 1, $max = null, $mod = null) {
        $key = $product->id . ($mod ? '-' . $mod->id : '');
        $_SESSION['cart'][$key] = ['qty' => min($qty, 5), 'price' => $mod ? $mod->price : $product->price];
    }
}
function checkRepeat($expected, $actual, $label) {
    if ($expected !== $actual) throw new RuntimeException($label . ': ' . json_encode($actual));
}
$service = new app\services\CartRepeat();
$cart = new RepeatTestCart();
$_SESSION = ['cart' => [1 => ['qty' => 2, 'price' => 99]], 'promocart' => 'KEEP'];
$result = $service->addRows([
    ['product_id' => 1, 'article' => 'base', 'qty' => 10],
    ['product_id' => 2, 'article' => 'base', 'qty' => 1],
    ['product_id' => 3, 'article' => 'base', 'qty' => 1],
    ['product_id' => 4, 'article' => 'removed-variant', 'qty' => 1],
    ['product_id' => 5, 'article' => 'variant', 'qty' => 2],
], $cart);
checkRepeat(['added' => 5, 'skipped' => 3, 'limited' => 1], $result, 'current stock and skipped rows');
checkRepeat(5, $_SESSION['cart'][1]['qty'], 'existing quantities merged within limit');
checkRepeat(200, $_SESSION['cart'][1]['price'], 'historical price replaced');
checkRepeat(250, $_SESSION['cart']['5-7']['price'], 'variant matched by article');
checkRepeat('KEEP', $_SESSION['promocart'], 'promo preserved');
$result = $service->addRows([['product_id' => 1, 'article' => 'base', 'qty' => 2]], $cart);
checkRepeat(['added' => 0, 'skipped' => 1, 'limited' => 0], $result, 'full row not incremented');
echo "CartRepeatTest OK\n";
