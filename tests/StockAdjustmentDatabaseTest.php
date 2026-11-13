<?php
// Run only against an empty, disposable database, never the configured shop DB.
require dirname(__DIR__) . '/config/init.php';
use app\services\StockAdjustment;
foreach (['-1'=>-1, '+4'=>4, '0'=>0] as $input=>$expected) {
    if (StockAdjustment::parse($input) !== $expected) throw new RuntimeException('Signed input');
}
foreach (['1.5', 'abc', [], '1000000001', '999999999999999999999'] as $input) {
    try { StockAdjustment::parse($input); throw new RuntimeException('Invalid correction accepted'); }
    catch (InvalidArgumentException $e) {}
}
if (!getenv('STOCK_TEST_DSN')) { echo "Input validation OK. Set STOCK_TEST_DSN to an empty disposable MySQL database for integration checks.\n"; exit; }
class_alias(\RedBeanPHP\R::class, 'R');
R::setup(getenv('STOCK_TEST_DSN'), getenv('STOCK_TEST_USER') ?: 'root', getenv('STOCK_TEST_PASSWORD') ?: '');
R::freeze(true);
if (R::getCell('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')) {
    throw new RuntimeException('Integration test requires an empty database');
}
function checkStock(int $quantity, int $rest, int $status = 1): void {
    $row = R::getRow('SELECT quantity, rest, stock_status_id FROM product WHERE id = 1');
    if (array_map('intval', array_values($row)) !== [$quantity, $rest, $status]) throw new RuntimeException(json_encode($row));
}
try {
    R::exec('CREATE TABLE product (id INT PRIMARY KEY, quantity INT NOT NULL DEFAULT 0, rest INT DEFAULT 0, reserve INT DEFAULT 0, stock_status_id INT DEFAULT 1, price INT DEFAULT 10)');
    R::exec('CREATE TABLE modification (id INT PRIMARY KEY AUTO_INCREMENT, product_id INT, quantity INT NOT NULL DEFAULT 0)');
    R::exec('INSERT INTO product (id, quantity, rest) VALUES (1,4,4)');
    StockAdjustment::migrate();
    StockAdjustment::migrate();
    checkStock(4,4);
    R::exec('UPDATE product SET stock_adjustment = -1 WHERE id = 1'); checkStock(3,3);
    R::exec('UPDATE product SET inventory_quantity = 4, inventory_rest = 4 WHERE id = 1'); checkStock(3,3);
    R::exec('UPDATE product SET price = 12 WHERE id = 1'); checkStock(3,3);
    // Incoming source equals previous effective value: must still apply exactly once.
    R::exec('UPDATE product SET inventory_quantity = 3, inventory_rest = 3 WHERE id = 1'); checkStock(2,2);
    R::exec('UPDATE product SET stock_adjustment = -10 WHERE id = 1'); checkStock(0,0,0);
    R::exec('UPDATE product SET stock_adjustment = 0 WHERE id = 1'); checkStock(3,3);
    R::exec('UPDATE product SET stock_adjustment = 4 WHERE id = 1'); checkStock(7,7);
    R::exec('UPDATE product SET inventory_quantity = 0, inventory_rest = 0 WHERE id = 1'); checkStock(4,4);
    R::exec('UPDATE product SET inventory_quantity = 6, inventory_rest = 4, reserve = 2, stock_adjustment = -1 WHERE id = 1'); checkStock(5,3);
    R::exec('UPDATE product SET stock_status_id = 2 WHERE id = 1'); checkStock(5,3,2);
    R::exec('INSERT INTO modification (product_id, inventory_quantity, stock_adjustment) VALUES (1,4,-1)');
    if ((int)R::getCell('SELECT quantity FROM modification') !== 3) throw new RuntimeException('Variant correction');
    R::exec('UPDATE modification SET inventory_quantity = 3');
    if ((int)R::getCell('SELECT quantity FROM modification') !== 2) throw new RuntimeException('Variant refresh');
    // Test the actual export row, including round-trip source metadata.
    $client = (new ReflectionClass(\app\services\InventoryApiClient::class))->newInstanceWithoutConstructor();
    $cacheDir = new ReflectionProperty($client, 'cacheDir'); $cacheDir->setAccessible(true);
    $cacheDir->setValue($client, sys_get_temp_dir() . '/nonexistent-stock-cache-' . bin2hex(random_bytes(8)));
    $export = new \app\services\InventoryCsvExportService($client);
    $method = new ReflectionMethod($export, 'makeRow'); $method->setAccessible(true);
    $row = R::getRow('SELECT *, 10 AS opt_price FROM product WHERE id = 1');
    $csv = $method->invoke($export, '100', $row);
    if ($csv[3] !== 5 || $csv[18] !== 4 || $csv[19] !== 6) throw new RuntimeException('Export/source metadata');
    $_SESSION = [];
    $cart = (new ReflectionClass(\app\models\Cart::class))->newInstanceWithoutConstructor();
    if ($cart->availableForRow('1', ['product_id'=>1]) !== 3) throw new RuntimeException('Cart limit');
    $sets = \app\services\CompleteInventory::calculate([$row + ['product_id'=>1, 'qty'=>2, 'hide'=>'show']]);
    if ($sets['available'] !== 1) throw new RuntimeException('Complete stock');
    $catalog = R::getCell('SELECT ' . \app\services\CatalogSelection::stock() . ' FROM product WHERE id = 1');
    if ((int)$catalog !== 5) throw new RuntimeException('Catalog stock including variant');
    echo "Stock adjustment integration OK: migration, signed changes, clamping/reset, repeated refresh, variants, export metadata.\n";
} finally {
    R::exec('DROP TABLE IF EXISTS modification');
    R::exec('DROP TABLE IF EXISTS product');
}
