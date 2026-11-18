<?php
/** Private CLI operation, limited to the one mailbox explicitly named for CR-10 QA. */
if(PHP_SAPI!=='cli')exit(1);
$root=$argv[1]??'';$mode=$argv[2]??'inspect';
if(!is_dir($root)||!in_array($mode,['inspect','reset','suspend','restore'],true))throw new RuntimeException('Invalid maintenance arguments');
require $root.'/config/init.php';require CONF.'/db_bootstrap.php';
if(in_array($mode,['suspend','restore'],true)){
    // State and backups are outside public_html and are never uploaded as artifacts.
    $stateFile=dirname(dirname(realpath($root))).'/cr10-email-handoff.json';
    $state=is_file($stateFile)?json_decode(file_get_contents($stateFile),true,512,JSON_THROW_ON_ERROR):null;
    $saveState=static function(array $data)use($stateFile):void{
        $tmp=$stateFile.'.tmp-'.getmypid();
        if(file_put_contents($tmp,json_encode($data,JSON_THROW_ON_ERROR))===false)throw new RuntimeException('Cannot save private handoff state');
        chmod($tmp,0600);if(!rename($tmp,$stateFile))throw new RuntimeException('Cannot commit private handoff state');
    };
    if($mode==='suspend'){
        if($state){
            if($state['phase']==='suspended' && R::getCell('SELECT email FROM user WHERE id=?',[$state['original_user']['id']])===$state['temporary_email']){
                echo json_encode(['mode'=>$mode,'changed'=>false,'already_suspended'=>true]).PHP_EOL;exit;
            }
            throw new RuntimeException('Existing handoff state requires review');
        }
        $rows=R::getAll('SELECT * FROM user WHERE SHA2(LOWER(TRIM(email)),256)=?', ['ced2dd0c5959d5bbd651de6056dc102758b725e416d55f868883e15df542a5d6']);
        if(count($rows)!==1||!in_array($rows[0]['role'],['user','b2buser'],true))throw new RuntimeException('Unexpected mailbox owner');
        $original=$rows[0];$uid=(int)$original['id'];
        $state=['phase'=>'prepared','created_at'=>date(DATE_ATOM),'original_user'=>$original,'temporary_email'=>'cr10-hold-'.$uid.'-'.bin2hex(random_bytes(8)).'@example.invalid','order_ids'=>array_column(R::getAll('SELECT id FROM `order` WHERE user_id=? ORDER BY id',[$uid]),'id'),'company_ids'=>array_column(R::getAll('SELECT id FROM company WHERE user_id=? ORDER BY id',[$uid]),'id')];
        $saveState($state);
        R::begin();
        try{
            $locked=R::getRow('SELECT * FROM user WHERE id=? FOR UPDATE',[$uid]);
            if(!$locked||$locked['email']!==$original['email']||$locked['password']!==$original['password'])throw new RuntimeException('Account changed during handoff');
            R::exec('DELETE FROM recover WHERE email=?',[$original['email']]);
            if(R::exec('UPDATE user SET email=? WHERE id=? AND email=?',[$state['temporary_email'],$uid,$original['email']])!==1)throw new RuntimeException('Email not suspended');
            R::commit();
        }catch(Throwable $e){R::rollback();throw $e;}
        $state['phase']='suspended';$saveState($state);
        echo json_encode(['mode'=>$mode,'changed'=>true,'orders_preserved'=>count($state['order_ids']),'companies_preserved'=>count($state['company_ids']),'password_unchanged'=>true]).PHP_EOL;exit;
    }
    if(!$state)throw new RuntimeException('No suspended mailbox state');
    if($state['phase']==='restored'){echo json_encode(['mode'=>$mode,'changed'=>false,'already_restored'=>true]).PHP_EOL;exit;}
    if($state['phase']!=='suspended')throw new RuntimeException('Handoff state requires review');
    $original=$state['original_user'];$uid=(int)$original['id'];$transferred=0;
    R::begin();
    try{
        $locked=R::getRow('SELECT * FROM user WHERE id=? FOR UPDATE',[$uid]);
        if(!$locked||$locked['email']!==$state['temporary_email']||$locked['password']!==$original['password'])throw new RuntimeException('Original account changed; refusing restore');
        $qa=R::getRow('SELECT * FROM user WHERE email=? FOR UPDATE',[$original['email']]);
        if($qa){
            $qaId=(int)$qa['id'];
            if($qaId===$uid||$qa['role']!=='user'||R::getCell('SELECT COUNT(*) FROM company WHERE user_id=?',[$qaId]))throw new RuntimeException('Unexpected QA account');
            $qaOrders=R::getAll('SELECT id,note FROM `order` WHERE user_id=?',[$qaId]);
            foreach($qaOrders as$o)if(!str_contains((string)$o['note'],'ТЕСТ CR-10 — НЕ ОБРАБАТЫВАТЬ'))throw new RuntimeException('QA account has a non-test order');
            R::exec('UPDATE user SET email=? WHERE id=?',['cr10-tested-'.$qaId.'-'.bin2hex(random_bytes(8)).'@example.invalid',$qaId]);
            R::exec('UPDATE checkout_account_access SET pending=0,token_hash=NULL,expires=0 WHERE user_id=?',[$qaId]);
            $transferred=R::exec('UPDATE `order` SET user_id=? WHERE user_id=?',[$uid,$qaId]);
            $state['qa_user_id']=$qaId;
        }
        R::exec('DELETE FROM recover WHERE email=?',[$original['email']]);
        R::exec('UPDATE user SET email=? WHERE id=?',[$original['email'],$uid]);
        R::commit();
    }catch(Throwable $e){R::rollback();throw $e;}
    $state['phase']='restored';$state['restored_at']=date(DATE_ATOM);$saveState($state);
    echo json_encode(['mode'=>$mode,'changed'=>true,'original_mailbox_restored'=>true,'original_password_unchanged'=>true,'test_orders_preserved'=>$transferred]).PHP_EOL;exit;
}
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
