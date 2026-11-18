<?php
/** Private CLI operation, limited to the one mailbox explicitly named for CR-10 QA. */
if(PHP_SAPI!=='cli')exit(1);
$root=$argv[1]??'';$mode=$argv[2]??'inspect';
if(!is_dir($root)||!in_array($mode,['inspect','reset'],true))throw new RuntimeException('Invalid maintenance arguments');
require $root.'/config/init.php';require CONF.'/db_bootstrap.php';
$matches=R::getAll('SELECT * FROM user WHERE SHA2(LOWER(TRIM(email)),256)=?', ['ced2dd0c5959d5bbd651de6056dc102758b725e416d55f868883e15df542a5d6']);
if(count($matches)!==1){echo json_encode(['mode'=>$mode,'matches'=>count($matches),'changed'=>false]).PHP_EOL;exit(count($matches)?1:0);}
$user=$matches[0];$uid=(int)$user['id'];
$orders=R::getAll('SELECT * FROM `order` WHERE user_id=?',[$uid]);
$otherOrders=array_filter($orders,static fn($o)=>!str_contains((string)($o['note']??''),'ТЕСТ CR-10 — НЕ ОБРАБАТЫВАТЬ'));
$companies=(int)R::getCell('SELECT COUNT(*) FROM company WHERE user_id=?',[$uid]);
$report=['mode'=>$mode,'matches'=>1,'role'=>(string)$user['role'],'orders'=>count($orders),'non_cr10_orders'=>count($otherOrders),'companies'=>$companies,'changed'=>false];
if($mode==='inspect'){echo json_encode($report,JSON_UNESCAPED_UNICODE).PHP_EOL;exit;}
if($user['role']!=='user'||$otherOrders||$companies||!empty($user['comp_id']))throw new RuntimeException('Refusing account deletion: non-test history, company or privileged account; inspect report first');
// Keep historical test orders visible by moving them to an inert archive contact.
// The original login account is deleted; its real mailbox is freed for a new guest.
$backup=__DIR__.'/account-backup.json';
if(file_exists($backup))throw new RuntimeException('Maintenance already executed in this private release');
$payload=['user'=>$user,'orders'=>$orders];
if(file_put_contents($backup,json_encode($payload,JSON_THROW_ON_ERROR))===false)throw new RuntimeException('Private backup failed');
chmod($backup,0600);
R::begin();
try{
    $locked=R::getRow('SELECT id,email,role FROM user WHERE id=? FOR UPDATE',[$uid]);
    if(!$locked||$locked['role']!=='user')throw new RuntimeException('Account changed after inspection');
    $archiveId=null;
    if($orders){
        R::exec('INSERT INTO user (email,name,password,role,`groups`,telefon) VALUES (?,?,?,?,?,?)', ['cr10-archive-'.$uid.'-'.bin2hex(random_bytes(6)).'@example.invalid','Архив тестового пользователя CR-10',password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),'user',3,'']);
        $archiveId=(int)R::getCell('SELECT LAST_INSERT_ID()');
        if(!$archiveId)throw new RuntimeException('Archive contact not created');
        R::exec('UPDATE `order` SET user_id=? WHERE user_id=?',[$archiveId,$uid]);
    }
    R::exec('DELETE FROM recover WHERE email=?',[$locked['email']]);
    R::exec('DELETE FROM checkout_account_access WHERE user_id=?',[$uid]);
    R::exec('DELETE FROM user WHERE id=? AND role=?',[$uid,'user']);
    R::commit();
    $report['changed']=true;$report['orders_preserved']=count($orders);$report['mailbox_freed']=true;
    file_put_contents(__DIR__.'/maintenance-receipt.json',json_encode($report,JSON_PRETTY_PRINT));
    echo json_encode($report,JSON_UNESCAPED_UNICODE).PHP_EOL;
}catch(Throwable $e){R::rollback();throw $e;}
