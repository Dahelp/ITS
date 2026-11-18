<?php
namespace app\models { class AppModel {} }
namespace ishop { class App {public static $app;public function getProperty($key){return ['smtp_login'=>'shop@example.test','admin_email'=>'staff@example.test','shop_name'=>'Shop'][$key]??'';}public static function options($key){return '';}} }
namespace app\services {class TrafficSource {public static function emailHtml(){return '<p>Traffic</p>';}}}
namespace {
require dirname(__DIR__).'/app/services/CheckoutPayment.php';
require dirname(__DIR__).'/app/models/Order.php';
define('PATH','https://example.test');define('APP',dirname(__DIR__).'/app');define('TEMPLATE','itscenter');
class R {public static function findOne($table,$sql,$p){return $table==='order'?['inv'=>'TEST','payment_method'=>'card','payment_customer_type'=>3]:['email'=>'manager@example.test'];}}
class Swift_SmtpTransport {public function __construct(...$a){}public function setUsername($v){return $this;}public function setPassword($v){return $this;}}
class Swift_Message {public $body,$to;public function __construct(...$a){}public function setFrom($v){return $this;}public function setTo($v){$this->to=$v;return $this;}public function setBody($v,...$a){$this->body=$v;return $this;}}
class Swift_Mailer {public static $sent=[],$failStaff=false;public function __construct($v){}public function send($m){if(self::$failStaff && $m->to!=='buyer@example.test')throw new \RuntimeException('staff failure');self::$sent[]=clone $m;return 1;}}
\ishop\App::$app=new \ishop\App();
ini_set('session.use_cookies','0');session_save_path(sys_get_temp_dir());session_start();
function sendFixture($access){$_SESSION=['cart'=>[],'cart.qty'=>0,'cart.sum'=>0,'cart.currency'=>['symbol_left'=>'','symbol_right'=>'₽']];return \app\models\Order::mailOrder(1,'buyer@example.test','Buyer','+79991234567',2,'','','','','','','','','','','','','',$access);}
$secret=PATH.'/user/setup-password?key='.str_repeat('a',64);
if(!sendFixture(['guest'=>true,'setup_url'=>$secret]))throw new \RuntimeException('Delivery status lost');
if(count(Swift_Mailer::$sent)!==3 || strpos(Swift_Mailer::$sent[0]->body,$secret)===false)throw new \RuntimeException('Buyer setup link absent');
foreach(array_slice(Swift_Mailer::$sent,1)as$m)if(strpos($m->body,$secret)!==false)throw new \RuntimeException('Private setup link disclosed to staff');
Swift_Mailer::$sent=[];Swift_Mailer::$failStaff=true;
if(!sendFixture(['guest'=>true]))throw new \RuntimeException('Staff failure hid buyer delivery');
if(strpos(Swift_Mailer::$sent[0]->body,'Задать пароль')!==false || strpos(Swift_Mailer::$sent[0]->body,'/user/login')===false)throw new \RuntimeException('Existing account invitation incorrect');
session_destroy();
echo "CR10 mail: customer-only setup link, existing account login and independent staff failure OK; no mail sent\n";
}
