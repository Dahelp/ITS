<?php
namespace app\services;
final class CatalogPropertyAccess
{
    public static function check(array $session,string $method,$token=null): void
    {
        if(($session['user']['role']??'')!=='admin')throw new \RuntimeException('Доступ только администратору.',403);
        if($method==='POST'&&(!is_string($token)||!is_string($session['catalog_properties_csrf']??null)||!hash_equals($session['catalog_properties_csrf'],$token)))throw new \RuntimeException('Проверка формы не пройдена. Обновите страницу.',403);
        if(!in_array($method,['GET','POST'],true))throw new \RuntimeException('Метод не поддерживается.',405);
    }
}
