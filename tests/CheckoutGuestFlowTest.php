<?php
namespace app\controllers {class AppController {}}
namespace app\models {
 class Cart {public function reconcileAvailability(){return false;}}
 class User {
  public static $auth=false,$loaded=[];public $attributes=[];
  public static function checkAuth(){return self::$auth;}
  public function load($data){self::$loaded=$data;$this->attributes=$data;}
  public function validate($data){return true;}
  public function save($table){return 9;}
 }
 class Order {
  public static $data,$access,$fail=false;
  public static function saveOrder($data){self::$data=$data;return 12;}
  public static function mailOrder(...$args){self::$access=end($args);if(self::$fail)throw new \RuntimeException('SMTP offline');return true;}
 }
}
namespace app\services {class CompleteEngagement {public static function ensureSchema(){}}}
namespace app\services\admin {class AdminActivityLogger {const ACTION_CLIENT_SIGNUP=1;public static function incoming(...$args){}}}
namespace {
define('PATH','https://example.test');
define('ROOT',dirname(__DIR__));
require dirname(__DIR__).'/app/services/CheckoutAccount.php';
require dirname(__DIR__).'/app/services/CheckoutPayment.php';
require dirname(__DIR__).'/app/services/CheckoutCompany.php';
require dirname(__DIR__).'/app/services/CheckoutPickup.php';
require dirname(__DIR__).'/app/controllers/CartController.php';
class Redirected extends \Exception {}
function redirect($url=''){throw new Redirected($url);}
class R {
 public static $existing=false,$committed=false,$profileReads=0;
 public static function begin(){self::$committed=false;}public static function commit(){self::$committed=true;}public static function rollback(){}
 public static function exec($sql,$p=[]){return 1;}
 public static function getCell($sql,$p=[]){return 3;}
 public static function getRow($sql,$p=[]){return ['id'=>1,'name'=>'Самовывоз'];}
 public static function getAll($sql,$p=[]){return [['branch_id'=>0,'branch_name'=>'Подольск (мкр. Климовск)']];}
 public static function load($table,$id){return new \ArrayObject(['id'=>$id,'comp_id'=>22,'groups'=>3,'name'=>'Owner','telefon'=>'+79991234567','admin_id'=>0],\ArrayObject::ARRAY_AS_PROPS);}
 public static function findOne($table,$sql,$p=[]) {
  if($table==='user')return self::$existing?['id'=>5,'comp_id'=>22,'groups'=>4,'name'=>'PRIVATE','telefon'=>'PRIVATE','admin_id'=>42]:null;
  if($table==='company'){self::$profileReads++;throw new \RuntimeException('Guest read private company');}
  if($table==='order')return ['inv'=>'TEST12','dostavka_id'=>1,'branch_id'=>1,'transport_id'=>0,'address'=>'','note'=>'','date'=>''];
  if($table==='branch_office')return ['branch_name'=>'Москва'];
  return null;
 }
}
ini_set('session.use_cookies','0');session_save_path(sys_get_temp_dir());session_start();
function check($v,$message){if(!$v)throw new \RuntimeException($message);}
foreach(['new','existing','authenticated','mail-failed','legal-own','legal-resale']as$case){
 if(session_status()!==PHP_SESSION_ACTIVE)session_start();
 $_SESSION=['checkout_token'=>'test-token','cart'=>[1=>['qty'=>1]],'cart.currency'=>['code'=>'RUB']];
 $_POST=['checkout_token'=>'test-token','checkout_legal_agree'=>'1','email'=>'BUYER@example.test','telefon'=>'+79991234567','name'=>'Submitted Name','groups'=>'3','dostavka_id'=>'1','payment_method'=>'card','form_callback'=>'E-mail','purchase_purpose'=>'own_use','role'=>'admin','password'=>'attacker'];
 if(str_starts_with($case,'legal-'))$_POST=array_replace($_POST,['groups'=>'4','nds'=>'1','dogovor'=>'2','payment_method'=>'bank_transfer','comp_name'=>'ООО Тест','inn'=>'5614073401','kpp'=>'561401001','bank'=>'Тестовый банк','bik'=>'044525225','raschet'=>'40702810000000000001','korschet'=>'30101810000000000001','purchase_purpose'=>$case==='legal-resale'?'resale':'own_use']);
 $_SERVER['REQUEST_METHOD']='POST';
 $_COOKIE=['cookie_accepted'=>'true'];
 R::$existing=$case==='existing';R::$profileReads=0;
 \app\models\User::$auth=$case==='authenticated';
 if(\app\models\User::$auth)$_SESSION['user']=['id'=>5,'email'=>'owner@example.test','telefon'=>'+79991234567'];
 \app\models\Order::$fail=$case==='mail-failed';
 $controller=new \app\controllers\CartController();
 try{$controller->checkoutAction();throw new \RuntimeException('No redirect');}catch(Redirected $e){check($e->getMessage()===PATH.'/cart/complete','Success redirect');}
 check(R::$committed,'Order committed before mail');
 check(empty($_SESSION['cart']) && isset($_SESSION['checkout_result']),'Cart cleared and confirmation retained');
 $data=\app\models\Order::$data;
 check($data['branch_id']==='0','Zero-valued pickup branch preserved');
 check($data['groups']===(str_starts_with($case,'legal-')?4:3) && $data['admin_id']==0,'Guest cannot inherit private customer terms or manager');
 if(str_starts_with($case,'legal-')){check(str_contains($data['note'],'044525225')&&str_contains($data['note'],'5614073401'),'Company and bank details not persisted');check(\app\models\Order::$access['company_fields']['purchase_purpose']===($case==='legal-resale'?'resale':'own_use'),'Purpose lost in mail handoff');check(\app\models\Order::$access['company_card'],'Card not requested');}
 if($case==='existing')check($data['user_id']===5 && $data['comp_id']===null && R::$profileReads===0 && empty(\app\models\Order::$access['setup_url']),'Existing mailbox routes order without profile access or password reset');
 if($case==='new'){
  check(\app\models\User::$loaded['role']==='user' && \app\models\User::$loaded['password']!=='attacker','Guest cannot choose role or password');
  check(!empty(\app\models\Order::$access['setup_url']),'New account gets setup invitation');
 }
 if($case==='authenticated')check($_SESSION['checkout_result']['email']==='owner@example.test' && !\app\models\Order::$access['guest'],'Authenticated owner is authoritative');
 if($case==='mail-failed')check(!$_SESSION['checkout_result']['sent'],'SMTP failure keeps committed order and confirmation');
}
if(session_status()!==PHP_SESSION_ACTIVE)session_start();session_destroy();
echo "CR10 checkout: new/existing/authenticated accounts, no guest profile inheritance, server-generated credentials, commit before mail and SMTP failure OK\n";
}
