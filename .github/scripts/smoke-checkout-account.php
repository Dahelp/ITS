<?php
if(PHP_SAPI!=='cli')exit(1);
$root=getenv('LEAD_SITE_ROOT') ?: getenv('PROJECT_RELEASE_TEST_ROOT');
if(!$root)throw new RuntimeException('Missing site root');
require $root.'/config/init.php';
require CONF.'/db_bootstrap.php';
app\services\CheckoutAccount::ensureSchema();
foreach(['checkout_account_access'=>['user_id','token_hash','expires','sent_at','pending'],'checkout_order_contact'=>['order_id','email','name','telefon']]as$table=>$required){
    $columns=array_column(R::getAll("SHOW COLUMNS FROM `$table`"),'Field');
    if(array_diff($required,$columns))throw new RuntimeException('Incomplete CR-10 schema: '.$table);
}
$cardFields=\app\services\CheckoutCompany::fields(['comp_name'=>'Тестовая организация','purchase_purpose'=>'own_use']);
$card=\app\services\CompanyCard::bytes($cardFields,['number'=>'PREFLIGHT','date'=>date('d.m.Y')]);
if(substr($card,0,2)!=='PK')throw new RuntimeException('Company DOCX preflight failed');
$companyColumns=array_column(R::getAll('SHOW COLUMNS FROM company'),'Field');
if(array_diff(array_merge(array_keys(\app\services\CheckoutCompany::LABELS),['id','comp_short_name','user_id','tip','postal_address','nds','dogovor','data_create','hide']),$companyColumns))throw new RuntimeException('Incomplete company schema');
$checks=[];
foreach(['/' => null,'/cart/view'=>'id="checkout-login"','/user/login'=>'name="login_csrf"','/user/setup-password'=>'Задать пароль','/js/checkout-progress.js'=>'checkout-login-resume','/js/main.js'=>"submitter.id === 'checkout-login'"]as$path=>$marker){
    $ch=curl_init('https://its-center.ru'.$path.'?cr10='.time());
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_HTTPHEADER=>['Cache-Control: no-cache']]);
    $body=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
    if($body===false||$status!==200||preg_match('~Fatal error:|Uncaught (?:Error|Exception)|Parse error:~',$body))throw new RuntimeException('HTTP check failed: '.$path);
    if($marker!==null&&!str_contains($body,$marker))throw new RuntimeException('CR-10 marker absent: '.$path);
    $checks[]=['path'=>$path,'status'=>$status];
}
file_put_contents(__DIR__.'/smoke.json',json_encode(['company_docx'=>'ok','schema'=>'ok','http'=>$checks,'orders_created'=>0,'mail_sent'=>false],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo "CR-10: schema and six public HTTP checks OK; no orders or email created by deployment\n";
