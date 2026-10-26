<?php
require dirname(__DIR__).'/vendor/autoload.php';
class ClearCartFixture extends \app\controllers\CartController {
    public $rendered;
    public function loadView($view,$vars=[]){$this->rendered=$view;}
}
ini_set('session.use_cookies','0');session_save_path(sys_get_temp_dir());session_id('codex-cart-clear-'.bin2hex(random_bytes(6)));session_start();
$path=session_save_path().'/sess_'.session_id();
try{
 $_SESSION=['user'=>['id'=>123],'cart'=>[1=>['qty'=>2]],'cart.qty'=>2,'cart.sum'=>100,'cart.weight'=>5,'cart.volume'=>1,'cart.currency'=>['code'=>'RUB'],'promocart'=>'X','promo_state'=>['ok'=>1],'cart.notice'=>'old','cart.quantity_notice'=>'old','cart.undo'=>['old'],'checkout_draft_generation'=>'old'];
 $_SESSION['form_data']=['checkout_token'=>'old','name'=>'old contact'];
 $_SERVER['HTTP_X_REQUESTED_WITH']='XMLHttpRequest';$_GET['ctx']='modal';
 $controller=(new ReflectionClass(ClearCartFixture::class))->newInstanceWithoutConstructor();$controller->clearAction();
 foreach(['cart','cart.qty','cart.sum','cart.weight','cart.volume','cart.currency','promocart','promo_state','cart.notice','cart.quantity_notice','cart.undo','form_data']as$key)if(isset($_SESSION[$key]))throw new RuntimeException('Not cleared: '.$key);
 if($_SESSION['user']['id']!==123||$_SESSION['checkout_draft_generation']==='old'||$controller->rendered!=='cart_table')throw new RuntimeException('Clear scope, generation or response mismatch');
 echo "Cart clear: all cart/promo/undo state removed, draft generation invalidated, user session retained OK\n";
}finally{if(session_status()===PHP_SESSION_ACTIVE)session_write_close();if(is_file($path))unlink($path);}
