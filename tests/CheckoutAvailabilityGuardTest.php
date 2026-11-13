<?php
namespace app\models {
 class Cart {public static $checked=false;public function reconcileAvailability():bool{self::$checked=true;return true;}}
 class Order {public function __construct(){throw new \RuntimeException('An order must not be created after availability changes');}}
 class User {public function __construct(){throw new \RuntimeException('A customer must not be created after availability changes');}}
}
namespace {
 require dirname(__DIR__).'/vendor/autoload.php';
 require dirname(__DIR__).'/outputs/cart-recovery-20261113/files/app/controllers/CartController.php';
 define('PATH','https://example.test');ini_set('session.use_cookies','0');session_save_path(sys_get_temp_dir());session_id('recovery-checkout-'.bin2hex(random_bytes(6)));session_start();$sessionFile=session_save_path().'/sess_'.session_id();
 $_SERVER['REQUEST_METHOD']='POST';$_POST=['checkout_token'=>'fixture-token','name'=>'fixture'];$_SESSION=['checkout_token'=>'fixture-token','cart'=>[1=>['qty'=>2]]];
 register_shutdown_function(function()use($sessionFile){
  $ok=\app\models\Cart::$checked&&isset($_SESSION['error'],$_SESSION['form_data'])&&$_SESSION['checkout_token']==='fixture-token'&&isset($_SESSION['cart']);
  if(session_status()===PHP_SESSION_ACTIVE)session_write_close();if(is_file($sessionFile))unlink($sessionFile);
  if(!$ok){fwrite(STDERR,"Checkout guard did not preserve cart/form/token\n");exit(1);}echo "Checkout guard OK: changed availability returns to review before customer/order creation; cart, form and token preserved\n";
 });
 $controller=(new \ReflectionClass(\app\controllers\CartController::class))->newInstanceWithoutConstructor();$controller->checkoutAction();
 throw new \RuntimeException('Changed availability did not stop checkout');
}
