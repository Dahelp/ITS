<?php
namespace app\controllers {class AppController {}}
namespace app\models {class User {public static $ok=false;public function login(){if(self::$ok)$_SESSION['user']=['id'=>7,'email'=>'buyer@example.test'];return self::$ok;}}}
namespace {
define('PATH','https://example.test');require dirname(__DIR__).'/app/controllers/UserController.php';
class Redirected extends \Exception {}function redirect($url=''){throw new Redirected($url);}
ini_set('session.use_cookies','0');session_save_path(sys_get_temp_dir());session_start();
function check($v,$m){if(!$v)throw new \RuntimeException($m);}
foreach([false,true]as$ok){
 if(session_status()!==PHP_SESSION_ACTIVE)session_start();
 $_SESSION=['login_csrf'=>'csrf','cart'=>[1=>['qty'=>2]],'checkout_login'=>['fields'=>['address'=>'Москва'],'step'=>3,'attachment'=>true,'time'=>time()]];
 $_POST=['login_csrf'=>'csrf','loginok'=>md5(date('Y-m-d')),'email'=>'buyer@example.test','password'=>'secret123'];
 \app\models\User::$ok=$ok;
 try{(new \app\controllers\UserController())->loginAction();throw new \RuntimeException('Missing redirect');}catch(Redirected $e){check($e->getMessage()===PATH.($ok?'/cart/view':'/user/login?checkout=1'),'Return destination');}
 check($_SESSION['cart'][1]['qty']===2,'Cart preserved');
 if($ok)check(empty($_SESSION['checkout_login']) && $_SESSION['checkout_login_resume']['fields']['address']==='Москва' && $_SESSION['checkout_login_resume']['step']===3,'Handoff consumed only after successful login');
 else check(isset($_SESSION['checkout_login']) && !isset($_SESSION['checkout_login_resume']),'Failed login retains checkout');
}
if(session_status()!==PHP_SESSION_ACTIVE)session_start();session_destroy();
echo "CR10 login controller: failed/successful login preserves cart and resumes original fields and step OK\n";
}
