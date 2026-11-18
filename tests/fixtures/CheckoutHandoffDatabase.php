<?php
class R {
    private static $data,$before;
    private static function data(){if(self::$data===null)self::$data=json_decode(file_get_contents(getenv('CR10_HANDOFF_FIXTURE_DATA')),true);return self::$data;}
    public static function begin(){self::data();self::$before=self::$data;}
    public static function commit(){file_put_contents(getenv('CR10_HANDOFF_FIXTURE_DATA'),json_encode(self::$data));}
    public static function rollback(){self::$data=self::$before;}
    public static function getAll($sql,$p=[]){$d=self::data();
        if(str_contains($sql,'SHA2('))return array_values(array_filter($d['users'],fn($u)=>hash('sha256',strtolower(trim($u['email'])))===$p[0]));
        if(str_contains($sql,'FROM `order`'))return array_values(array_filter($d['orders'],fn($o)=>$o['user_id']==$p[0]));
        if(str_contains($sql,'FROM company'))return array_values(array_filter($d['companies'],fn($c)=>$c['user_id']==$p[0]));
        throw new RuntimeException('Unexpected fixture select');
    }
    public static function getRow($sql,$p=[]){$d=self::data();foreach($d['users']as$u)if(str_contains($sql,'WHERE id=')?$u['id']==$p[0]:$u['email']===$p[0])return $u;return [];}
    public static function getCell($sql,$p=[]){if(str_contains($sql,'COUNT(*) FROM company'))return count(self::getAll('FROM company',$p));return self::getRow('WHERE id=?',$p)['email']??null;}
    public static function exec($sql,$p=[]){self::data();
        if(str_contains($sql,'DELETE FROM recover')){self::$data['recover']=[];return 1;}
        if(str_contains($sql,'UPDATE user SET email=')){$id=$p[1];if(!isset(self::$data['users'][$id])||(isset($p[2])&&self::$data['users'][$id]['email']!==$p[2]))return 0;self::$data['users'][$id]['email']=$p[0];return 1;}
        if(str_contains($sql,'UPDATE checkout_account_access')){self::$data['pending'][$p[0]]=0;return 1;}
        if(str_contains($sql,'UPDATE `order`')){$n=0;foreach(self::$data['orders']as&$o)if($o['user_id']==$p[1]){$o['user_id']=$p[0];$n++;}return $n;}
        throw new RuntimeException('Unexpected fixture mutation');
    }
}
