<?php
/** CLI-only QA of the actual order mailer with an isolated, read-only database double. */
if(PHP_SAPI!=='cli')exit(1);
$root=$argv[1]??dirname(__DIR__,2);
if (!is_dir($root)) $root=dirname(__DIR__).'/'.$root;
$send=in_array('--send',$argv,true);
$report=__DIR__.'/company-mail-qa.json';
if($send && is_file($report))throw new RuntimeException('QA mail already attempted in this release');
class R {
    public static $purpose='own_use';
    public static function findOne($table,$sql,$args){if($table!=='order')throw new RuntimeException('Unexpected QA database access');return ['inv'=>'ТЕСТ НЕ ОБРАБАТЫВАТЬ '.(self::$purpose==='own_use'?'СВОИ НУЖДЫ':'ПЕРЕПРОДАЖА'),'payment_method'=>'bank_transfer','payment_customer_type'=>4];}
    public static function getCell($sql,$args=[]){return $args[0]==='option_telefon'?'+7 (495) 424-98-90':'';}
}
require $root.'/config/init.php';
if(!$send){
    class Swift_Mailer {
        public static $messages=[];
        public function __construct($transport){}
        public function send($message){self::$messages[]=clone $message;return 1;}
    }
}
$registry=\ishop\Registry::instance();
foreach(require CONF.'/params.php' as$key=>$value)$registry->setProperty($key,$value);
$registry->setProperty('admin_email','oooitscenter@yandex.com');
\ishop\App::$app=$registry;
ini_set('session.use_cookies','0');session_save_path(sys_get_temp_dir());session_start();
$fixture=['comp_name'=>'ООО «ТЕСТОВАЯ ОРГАНИЗАЦИЯ»','inn'=>'5614073401','kpp'=>'561401001','url_address'=>'Тестовый юридический адрес для проверки письма','bank'=>'ТЕСТОВЫЙ БАНК','bik'=>'044525225','raschet'=>'40702810000000000001','korschet'=>'30101810000000000001'];
if($send)file_put_contents($report,json_encode(['state'=>'started','at'=>date(DATE_ATOM)]));
$result=['mode'=>$send?'smtp':'captured','orders_created'=>0,'mailbox'=>'oooitscenter@yandex.com','scenarios'=>[]];
foreach(['own_use','resale']as$purpose){
    R::$purpose=$purpose;$fields=\app\services\CheckoutCompany::fields($fixture+['purchase_purpose'=>$purpose]);
    \app\services\CheckoutCompany::validate($fields,true,false);
    $_SESSION=['cart'=>[1=>['name'=>'ТЕСТ — НЕ ОБРАБАТЫВАТЬ','qty'=>1,'price'=>0]],'cart.qty'=>1,'cart.sum'=>0,'cart.currency'=>['symbol_left'=>'','symbol_right'=>'₽']];
    $ok=\app\models\Order::mailOrder(0,'oooitscenter@yandex.com','Тестовый покупатель','+7 999 123-45-67',0,'ТЕСТ — НЕ ОБРАБАТЫВАТЬ. Проверка заполненных реквизитов и карточки организации. Реальный заказ не создавался.',date('d.m.Y H:i'),'Самовывоз','Климовск','','','','<b>Вид клиента:</b> Юридическое лицо<br>',\app\services\CheckoutCompany::html($fields),'<b>Налогообложение:</b> С НДС<br>','<b>Условия поставки:</b> Счёт-договор<br>','Электронная почта','',['company_card'=>true,'company_fields'=>$fields],$delivery);
    if(!$ok || empty($delivery['staff']) || empty($delivery['company_card']))throw new RuntimeException('Customer/staff delivery or card failed');
    $result['scenarios'][]=['purpose'=>$purpose,'customer_sent'=>$ok,'staff_sent'=>$delivery['staff'],'docx_attached'=>$delivery['company_card']];
}
if(!$send){
    if(count(Swift_Mailer::$messages)!==4)throw new RuntimeException('Missing customer/staff message');
    foreach(Swift_Mailer::$messages as$i=>$message){
        foreach(['5614073401','561401001','044525225','40702810000000000001','30101810000000000001']as$value)if(!str_contains($message->getBody(),$value))throw new RuntimeException('Field absent from mail');
        file_put_contents(__DIR__.'/company-mail-'.$i.'.html',$message->getBody());
        if($i%2===1){$attachments=$message->getChildren();if(count($attachments)!==1)throw new RuntimeException('Missing DOCX attachment');$bytes=$attachments[0]->getBody();if(substr($bytes,0,2)!=='PK')throw new RuntimeException('Invalid DOCX');file_put_contents(__DIR__.'/company-mail-'.$i.'.docx',$bytes);}
    }
}
file_put_contents($report,json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
session_destroy();
echo json_encode($result,JSON_UNESCAPED_UNICODE).PHP_EOL;
