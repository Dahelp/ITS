<?php
namespace app\controllers { class AppController {} }
namespace app\controllers\admin { class AppController {} }
namespace app\models {
    class User {
        public static $auth=true,$admin=false;
        public static function checkAuth(){return self::$auth;}
        public static function isAdmin(){return self::$admin;}
    }
}
namespace app\services {
    class CompanyCard {
        public static function download(array $fields,array $context,string $suffix=''): void {
            throw new \DownloadedCard($fields,$context);
        }
    }
}
namespace {
define('PATH','https://example.test');
require dirname(__DIR__).'/app/services/CheckoutCompany.php';
require dirname(__DIR__).'/app/services/CompanyCardData.php';
require dirname(__DIR__).'/app/controllers/UserController.php';
require dirname(__DIR__).'/app/controllers/admin/OrderController.php';
class DownloadedCard extends \Exception {
    public $fields,$context;
    public function __construct($fields,$context){$this->fields=$fields;$this->context=$context;}
}
class RedirectedCard extends \Exception {}
function redirect($url=''){throw new RedirectedCard($url);}
class R {
    public static $reads=0,$snapshot=[],$company=[],$order=[];
    public static function getRow($sql,$p=[]){
        self::$reads++;
        if(str_contains($sql,'FROM `order`')){
            $row=self::$order[$p[0]]??[];
            if(isset($p[1]) && (int)($row['user_id']??0)!==$p[1])return [];
            return $row;
        }
        if(str_contains($sql,'FROM checkout_order_company'))return self::$snapshot[$p[0]]??[];
        if(str_contains($sql,'FROM company WHERE user_id=')){
            foreach(self::$company as$row)if((int)$row['user_id']===$p[0])return $row;
            return [];
        }
        if(str_contains($sql,'FROM company WHERE id=')){
            $row=self::$company[$p[0]]??[];
            return (int)($row['user_id']??0)===$p[1]?$row:[];
        }
        if(str_contains($sql,'FROM checkout_order_contact'))return ['name'=>'Контакт заказа','telefon'=>'+79990000000','email'=>'order@example.test'];
        if(str_contains($sql,'FROM user'))return ['name'=>'Владелец кабинета','telefon'=>'+79991111111','email'=>'owner@example.test'];
        throw new \RuntimeException('Unexpected query: '.$sql);
    }
}
function cardCheck($value,$message){if(!$value)throw new \RuntimeException($message);}
function expectDownload($controller,$query){
    $_GET=$query;
    try{$controller->companyCardAction();throw new \LogicException('No card');}catch(DownloadedCard $card){return $card;}
}
function expectDenied($controller,$query,$code){
    $_GET=$query;
    try{$controller->companyCardAction();throw new \LogicException('Unauthorized download');}catch(\Exception $e){cardCheck($e->getCode()===$code,'Wrong access result: '.$e->getMessage());}
}
$_SESSION=['user'=>['id'=>7,'role'=>'user']];
R::$company=[70=>['id'=>70,'user_id'=>7,'comp_name'=>'Текущая компания','inn'=>'5614073401','bank'=>'Новый банк'],80=>['id'=>80,'user_id'=>8,'comp_name'=>'Чужая компания']];
R::$order=[10=>['id'=>10,'user_id'=>7,'comp_id'=>70,'inv'=>'TEST10','date'=>'2026-11-19','note'=>'','payment_customer_type'=>4],11=>['id'=>11,'user_id'=>8,'comp_id'=>80,'inv'=>'TEST11','date'=>'2026-11-19','note'=>'','payment_customer_type'=>4]];
R::$snapshot[10]=['fields_json'=>json_encode(['comp_name'=>'Компания на дату заказа','bik'=>'044525225','bank'=>'Первоначальный банк','purchase_purpose'=>'resale']),'contact_json'=>json_encode(['name'=>'Контакт на дату заказа','telefon'=>'+79992222222','email'=>'snapshot@example.test'])];
$user=new \app\controllers\UserController();
$card=expectDownload($user,['id'=>80]);cardCheck($card->fields['comp_name']==='Текущая компания','Company ID bypassed ownership');
$card=expectDownload($user,['order_id'=>10]);cardCheck($card->fields['bank']==='Первоначальный банк' && $card->context['email']==='snapshot@example.test','Historical snapshot changed with profile');
expectDenied($user,['order_id'=>11],404);expectDenied($user,['order_id'=>-1],404);expectDenied($user,['order_id'=>['bad']],404);
\app\models\User::$auth=false;$reads=R::$reads;
try{$user->companyCardAction();throw new \LogicException('Guest downloaded');}catch(RedirectedCard $e){cardCheck($e->getMessage()===PATH.'/user/login' && R::$reads===$reads,'Guest read organization details');}
\app\models\User::$auth=true;
$admin=new \app\controllers\admin\OrderController();
expectDenied($admin,['id'=>10],403);
\app\models\User::$admin=true;
$card=expectDownload($admin,['id'=>10]);cardCheck($card->fields['bank']==='Первоначальный банк','Admin did not receive order snapshot');
expectDenied($admin,['id'=>999],404);
$legacy=\app\services\CheckoutCompany::fields(['comp_name'=>'Историческая организация','inn'=>'5614073401','bik'=>'044525225','purchase_purpose'=>'own_use']);
R::$order[12]=['id'=>12,'user_id'=>7,'comp_id'=>70,'inv'=>'OLD12','note'=>"Комментарий\n\nРеквизиты и цель покупки:\n".\app\services\CheckoutCompany::text($legacy)];
$card=expectDownload($user,['order_id'=>12]);cardCheck($card->fields['comp_name']==='Историческая организация' && $card->fields['bik']==='044525225' && $card->fields['purchase_purpose']==='own_use','Legacy order details lost');
R::$order[13]=['id'=>13,'user_id'=>7,'comp_id'=>70,'inv'=>'OLD13','note'=>'Старый заказ'];
$card=expectDownload($user,['order_id'=>13]);cardCheck($card->fields['comp_name']==='Текущая компания','Old order cannot use its linked organization');
cardCheck($card->context['source_profile']===true,'Current profile not distinguished from a historical snapshot');
R::$order[14]=['id'=>14,'user_id'=>7,'comp_id'=>null,'inv'=>'OLD14','note'=>'Заказ до появления сохранённых реквизитов'];
$card=expectDownload($user,['order_id'=>14]);cardCheck($card->fields['comp_name']==='Текущая компания' && $card->context['source_profile']===true,'Legacy order without company ID cannot export its owner organization');
echo "DOCX downloads: own company, own order, admin permissions, immutable details, legacy orders and leading zeros OK; no files or mail exposed\n";
}
