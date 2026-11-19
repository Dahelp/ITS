<?php
require dirname(__DIR__).'/app/services/CheckoutCompany.php';
use app\services\CheckoutCompany as Company;
class R {
    public static $users=[1=>['id'=>1,'comp_id'=>0],2=>['id'=>2,'comp_id'=>0]],$companies=[],$next=10,$locks=0;
    public static function getRow($sql,$params){
        if(!str_contains($sql,'FOR UPDATE'))throw new RuntimeException('Missing lock');
        if(str_contains($sql,'FROM user')){self::$locks++;return self::$users[$params[0]]??[];}
        foreach(self::$companies as$company)if((int)$company['user_id']===$params[0])return $company;
        return [];
    }
    public static function exec($sql,$params){
        if(str_starts_with($sql,'INSERT INTO company')){preg_match('/INSERT INTO company \((.+)\) VALUES/',$sql,$m);$keys=explode(',',str_replace('`','',$m[1]));$row=array_combine($keys,$params);$row['id']=++self::$next;self::$companies[$row['id']]=$row;return 1;}
        if(str_starts_with($sql,'UPDATE company')){preg_match('/SET `([a-z_]+)`/',$sql,$m);if(self::$companies[$params[1]]['user_id']!==$params[2])throw new RuntimeException('Ownership missing');self::$companies[$params[1]][$m[1]]=$params[0];return 1;}
        if(str_starts_with($sql,'UPDATE user SET comp_id')){self::$users[$params[1]]['comp_id']=$params[0];return 1;}
        throw new RuntimeException('Unexpected SQL');
    }
    public static function getInsertID(){return self::$next;}
}
function ownerCheck($value,$message){if(!$value)throw new RuntimeException($message);}
$fields=Company::fields(['comp_name'=>'ООО «Компания покупателя»','inn'=>'5614073401','bank'=>'Банк покупателя','bik'=>'044525225','raschet'=>'40702810000000000001','korschet'=>'30101810000000000001','purchase_purpose'=>'resale','user_id'=>2,'id'=>999]);
$id=Company::saveForOwner(1,$fields,['nds'=>'1','dogovor'=>'2']);ownerCheck($id===11 && R::$companies[$id]['user_id']===1 && R::$users[1]['comp_id']===$id,'Company not bound to owner');
ownerCheck(R::$companies[$id]['nds']==='1' && R::$companies[$id]['comp_short_name']===$fields['comp_name'],'Company data lost');
$again=Company::saveForOwner(1,array_replace($fields,['bank'=>'Подмена банка','kpp'=>'561401001']),['nds'=>'2']);ownerCheck($again===$id && count(R::$companies)===1,'Duplicate company');
ownerCheck(R::$companies[$id]['bank']==='Банк покупателя' && R::$companies[$id]['nds']==='1' && R::$companies[$id]['kpp']==='561401001','Existing company overwritten or missing fields not filled');
$second=Company::saveForOwner(2,$fields,[]);ownerCheck($second!==$id && R::$users[2]['comp_id']===$second,'Different owners merged by INN');
ownerCheck(Company::saveForOwner(1,Company::fields([]),[])===null,'Empty card created for file-only checkout');
try{Company::saveForOwner(999,$fields,[]);throw new LogicException('Unknown owner accepted');}catch(RuntimeException $expected){}
ownerCheck(R::$locks===4,'Missing owner lock');
echo "Company ownership: owner lock, automatic link, repeat checkout, preserved terms, missing fields, separate owners and attachment-only checkout OK\n";
