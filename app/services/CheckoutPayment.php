<?php
namespace app\services;

final class CheckoutPayment
{
    public static function pickup(string $deliveryName): bool
    {
        return mb_stripos($deliveryName,'самовывоз')!==false;
    }
    public static function group($accountGroup,$requestedGroup): int
    {
        $account=is_scalar($accountGroup)?(int)$accountGroup:0;
        if(in_array($account,[3,4],true))return $account;
        $requested=is_scalar($requestedGroup)?(int)$requestedGroup:0;
        return in_array($requested,[3,4],true)?$requested:0;
    }
    public static function options(int $group,bool $pickup): array
    {
        if($group===4)return ['bank_transfer'=>'Оплата по реквизитам'];
        if($group!==3)return [];
        $options=['card'=>'Оплата картой','qr'=>'Оплата по QR-коду','bank_transfer'=>'Перевод по реквизитам через банк'];
        if($pickup)$options['cash']='Наличные (самовывоз)';
        return $options;
    }
    public static function validate($method,int $group,bool $pickup): string
    {
        if(!in_array($group,[3,4],true))throw new \DomainException('Выберите вид клиента.');
        if(!is_string($method))throw new \DomainException('Выберите способ оплаты.');
        $method=trim($method);
        if($group===4&&$method==='')$method='bank_transfer';
        if(!array_key_exists($method,self::options($group,$pickup))){
            if($method==='cash'&&$group===3&&!$pickup)throw new \DomainException('Наличные доступны только при самовывозе. Выберите другой способ оплаты.');
            throw new \DomainException('Выберите доступный способ оплаты для вашего вида клиента и получения.');
        }
        return $method;
    }
    public static function label($method,int $group=3): string
    {
        return self::options($group===4?4:3,true)[(string)$method]??'Не указан';
    }
    public static function rules(array $deliveries): array
    {
        $ids=[];foreach($deliveries as$d)if(self::pickup((string)$d['name']))$ids[]=(string)$d['id'];
        return ['methods'=>['3'=>self::options(3,true),'4'=>self::options(4,true)],'pickupIds'=>$ids];
    }
}
