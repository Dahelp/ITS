<?php
// Isolated persistence fixture. No database, orders or email are touched.
define('PATH', 'https://example.test');
putenv('CHECKOUT_ACCOUNT_BASE_URL=https://example.test');
require dirname(__DIR__).'/app/services/CheckoutAccount.php';
class R {
    public static array $access=[],$passwords=[];
    public static function begin() {} public static function commit() {} public static function rollback() {}
    public static function exec($sql,$p=[]) {
        if (strpos($sql,'CREATE TABLE')===0 || strpos($sql,'DELETE FROM recover')===0) return 0;
        if (strpos($sql,'SET token_hash=')!==false) {
            $row=self::$access[$p[3]]??null;
            if (!$row || !$row['pending'] || $row['sent_at'] >= $p[4]) return 0;
            self::$access[$p[3]]=array_replace($row,['token_hash'=>$p[0],'expires'=>$p[1],'sent_at'=>$p[2]]);return 1;
        }
        if (strpos($sql,'UPDATE user SET password=')===0) {self::$passwords[$p[1]]=$p[0];return 1;}
        if (strpos($sql,'SET pending=0')!==false) {self::$access[$p[0]]['pending']=0;self::$access[$p[0]]['token_hash']=null;return 1;}
        throw new RuntimeException('Unexpected fixture query');
    }
    public static function getRow($sql,$p) {
        foreach(self::$access as $id=>$row)if($row['pending'] && $row['token_hash']===$p[0] && $row['expires']>$p[1])return $row+['user_id'=>$id];
        return [];
    }
}
function check($v,$message){if(!$v)throw new RuntimeException($message);}
use app\services\CheckoutAccount as Account;
R::$access[7]=['pending'=>1,'sent_at'=>0,'expires'=>0,'token_hash'=>null];
$url=Account::issue(7);parse_str(parse_url($url,PHP_URL_QUERY),$q);$token=$q['key'];
check(strlen($token)===64,'High entropy token');
check(R::$access[7]['token_hash']===hash('sha256',$token),'Only hash persisted');
check(Account::issue(7)===null,'Cooldown enforced');
check(Account::issue(8)===null,'Existing account cannot receive first-access token');
check(!Account::setPassword(str_repeat('a',64),'secret123'),'Invalid token rejected');
R::$access[7]['expires']=time()-1;
check(!Account::setPassword($token,'secret123'),'Expired token rejected');
R::$access[7]['expires']=time()+60;
check(Account::setPassword($token,'secret123'),'Valid token accepted');
check(password_verify('secret123',R::$passwords[7]),'Password hashed');
check(!Account::setPassword($token,'changed123'),'Token single use');
check(Account::issue(7)===null,'Setup cannot reset existing password');
$fields=Account::fields(['password'=>'secret','role'=>'admin','checkout_token'=>'csrf','name'=>'Иван','email'=>['bad'],'note'=>str_repeat('x',6000)]);
check(!isset($fields['password'],$fields['role'],$fields['checkout_token'],$fields['email']) && $fields['name']==='Иван' && strlen($fields['note'])===5000,'Safe bounded checkout handoff');
echo "CR10 account: hashed expiring single-use tokens, cooldown, existing account protection and safe handoff OK\n";
check(Account::mode(null)==='guest' && Account::mode('guest')==='guest' && Account::mode('register')==='register','Checkout modes');
foreach (['admin',[],17] as $badMode) {
    try { Account::mode($badMode); throw new LogicException('Invalid checkout mode accepted'); }
    catch (DomainException $expected) {}
}
