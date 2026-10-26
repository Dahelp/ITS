<?php
require dirname(__DIR__).'/app/services/LeadForm.php';
require dirname(__DIR__).'/app/services/admin/AdminLeadOrderService.php';
use app\services\LeadForm;
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function rejects(callable $call){try{$call();}catch(DomainException $e){return;}throw new RuntimeException('Expected validation failure');}
class R {
    public static $recent=false; public static $query; public static $args;
    public static function getCell($sql,$args){return self::$recent ? 42 : null;}
    public static function exec($sql,$args){self::$query=$sql;self::$args=$args;}
}
session_save_path(dirname(__DIR__).'/tmp');
session_start(); $_SERVER['REQUEST_METHOD']='POST';
define('PATH','https://example.test');
$token=LeadForm::token('oneclick');
LeadForm::guard('oneclick',['lead_token'=>$token]);
rejects(fn()=>LeadForm::guard('oneclick',['lead_token'=>'bad']));
rejects(fn()=>LeadForm::guard('callback',['lead_token'=>$token]));
rejects(fn()=>LeadForm::guard('oneclick',['lead_token'=>$token,'hp_field'=>'bot']));
LeadForm::consumed('oneclick');
rejects(fn()=>LeadForm::guard('oneclick',['lead_token'=>$token]));
$next=LeadForm::token('callback');
rejects(fn()=>LeadForm::guard('callback',['lead_token'=>$next]));
$_SESSION['lead_last_sent']=time()-61;
LeadForm::guard('callback',['lead_token'=>$next]);
check(LeadForm::phone('8 (912) 345-67-89')==='+79123456789','normalized phone');
foreach(['+7 (9','+7 (999) 999-99-99','123',[], ''] as $phone) rejects(fn()=>LeadForm::phone($phone));
foreach([0,-1,'1.5','1e2',10000,[]] as $qty) rejects(fn()=>LeadForm::quantity($qty));
check(LeadForm::quantity('4')===4,'quantity');
R::$recent=true;rejects(fn()=>LeadForm::rejectRecent('callback','phone','+79123456789'));R::$recent=false;
LeadForm::rejectRecent('callback','phone','+79123456789');
$method=new ReflectionMethod(app\services\admin\AdminLeadOrderService::class,'createOrderProduct');$method->setAccessible(true);
$method->invoke(null,77,['product_id'=>11,'article'=>'A','quantity'=>4,'unit'=>'шт','product_name'=>'New name','name'=>'Snapshot','price'=>900,'unit_price'=>650]);
check(R::$args[3]===4 && R::$args[6]===650.0,'lead quantity and price used');
$method->invoke(null,77,['product_id'=>11,'article'=>'A','product_name'=>'Old','price'=>900]);
check(R::$args[3]===1 && R::$args[6]===900.0,'legacy leads fallback');
set_error_handler(function($severity,$message){throw new RuntimeException($message);});
$namecomp='Test';$tell_site='';$fio='Client';$tell='+79123456789';$email='';$note='<script>x</script>';$name_tovar='Test tire';$quantity=4;$unitPrice=650;
foreach(['mail_oneclick.php','mail_oneclick_user.php'] as $file){ob_start();require dirname(__DIR__).'/app/views/itscenter/mail/'.$file;$body=ob_get_clean();check(str_contains($body,'650')&&str_contains($body,'Test tire')&&!str_contains($body,'<script>x</script>'),'mail snapshot and escaping');}
restore_error_handler();
echo "Lead forms: tokens, replay, throttle, phone, quantity, duplicate, admin conversion and emails OK; no real writes/mail\n";
