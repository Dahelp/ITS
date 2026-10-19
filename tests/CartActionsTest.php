<?php
declare(strict_types=1);
// Run each scenario in its own PHP process: controller redirects intentionally exit.
require dirname(__DIR__) . '/vendor/autoload.php';
define('PATH', 'https://example.test');
$scenario = $argv[1] ?? 'save';
class R {
    public static $inserts = [];
    public static $rolledBack = false;
    public static function begin() {}
    public static function commit() {}
    public static function rollback() { self::$rolledBack = true; }
    public static function count($table, $where, $args) { return $args[0] === 2 ? 1 : 0; }
    public static function exec($sql, $args) {
        if ($GLOBALS['scenario'] === 'failure') throw new RuntimeException('Simulated storage failure');
        self::$inserts[] = $args;
    }
    public static function getCell($sql, $args) {
        if ($args !== [42] || strpos($sql, 'WHERE user_id = ?') === false) throw new RuntimeException('Order owner filter missing');
        return 0;
    }
}
// No real session files or database writes in this isolated controller test.
session_set_save_handler(
    static function($path, $name) { return true; },
    static function() { return true; },
    static function($id) { return ''; },
    static function($id, $data) { return true; },
    static function($id) { return true; },
    static function($lifetime) { return 0; }
);
session_start();
$_SERVER['REQUEST_METHOD'] = $scenario === 'get' ? 'GET' : 'POST';
$_SESSION = ['user' => ['id' => 42], 'checkout_token' => 'token', 'cart.qty' => 3,
    'cart' => ['1' => ['product_id' => 1, 'qty' => 1], 's5_2' => ['product_id' => 2, 'qty' => 2]]];
$_POST = ['checkout_token' => 'token'];
if ($scenario === 'guest') unset($_SESSION['user']);
if ($scenario === 'csrf') $_POST['checkout_token'] = 'wrong';
register_shutdown_function(static function() use ($scenario) {
    $ok = false;
    if ($scenario === 'save') $ok = !isset($_SESSION['cart']) && R::$inserts === [[1, 42]];
    if ($scenario === 'failure') $ok = isset($_SESSION['cart']['s5_2']) && R::$rolledBack;
    if (in_array($scenario, ['guest', 'csrf', 'get', 'repeat'], true)) $ok = isset($_SESSION['cart']['s5_2']) && R::$inserts === [];
    if (!$ok) { fwrite(STDERR, 'FAILED: ' . $scenario . "\n"); exit(1); }
    echo 'CartActionsTest ' . $scenario . " OK\n";
});
$controller = (new ReflectionClass(app\controllers\CartController::class))->newInstanceWithoutConstructor();
if ($scenario === 'repeat') $controller->repeatAction();
else $controller->saveLaterAction();
