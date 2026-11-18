<?php
namespace app\controllers {class AppController {}}
namespace {
define('PATH','https://example.test');require dirname(__DIR__).'/app/controllers/UserController.php';
class RecoverRedirect extends \Exception {}function redirect($url=''){throw new RecoverRedirect($url);}
ini_set('session.use_cookies','0');session_save_path(sys_get_temp_dir());session_start();
$_SERVER['REQUEST_METHOD']='POST';$_SESSION=['recover_token'=>'csrf'];$_POST=['recover_token'=>'csrf','email'=>'invalid'];
try{(new \app\controllers\UserController())->recoverAction();throw new \RuntimeException('Missing invalid-email redirect');}
catch(RecoverRedirect $e){if($e->getMessage()!==PATH.'/user/recover')throw new \RuntimeException('Recovery redirect is relative');}
foreach(['recover','recover-pass']as$view){
    $_GET=['key'=>'fixture-key'];ob_start();include dirname(__DIR__).'/app/views/itscenter/User/'.$view.'.php';$html=ob_get_clean();
    if(!str_contains($html,'action="'.PATH.'/user/'.$view.'"'))throw new \RuntimeException('Recovery form action is relative: '.$view);
}
session_destroy();echo "CR10 recovery: request/reset form actions and error redirect are absolute; no /user/user paths or email sent\n";
}
