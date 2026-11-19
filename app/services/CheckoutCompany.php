<?php
namespace app\services;

final class CheckoutCompany
{
    public const LABELS = ['comp_name'=>'Название компании','inn'=>'ИНН','kpp'=>'КПП','url_address'=>'Юридический адрес','ogrn'=>'ОГРН / ОГРНИП','dir_name'=>'Руководитель','bank'=>'Наименование банка','bik'=>'БИК','raschet'=>'Расчётный счёт','korschet'=>'Корреспондентский счёт'];
    public const PURPOSES = [
        'own_use'=>'Для собственного использования — без дальнейшей перепродажи. Маркированные товары подлежат выводу из оборота.',
        'resale'=>'Для перепродажи — без вывода маркированных товаров из оборота. Коды маркировки передаются покупателю.'
    ];
    public static function fields(array $input): array
    {
        $out=[];
        foreach (array_merge(array_keys(self::LABELS),['purchase_purpose']) as $key) {
            $out[$key]=isset($input[$key]) && is_string($input[$key]) ? mb_substr(trim($input[$key]),0,500) : '';
        }
        return $out;
    }
    public static function validate(array $fields, bool $legal, bool $attachment): void
    {
        if (!isset(self::PURPOSES[$fields['purchase_purpose']])) throw new \DomainException('Выберите цель покупки: для собственного использования или для перепродажи.');
        foreach (['inn'=>[10,12],'kpp'=>[9],'bik'=>[9],'raschet'=>[20],'korschet'=>[20]] as $key=>$lengths) {
            if ($fields[$key]!=='' && (!ctype_digit($fields[$key]) || !in_array(strlen($fields[$key]),$lengths,true))) throw new \DomainException('Проверьте поле «'.self::LABELS[$key].'»: укажите '.implode(' или ',$lengths).' цифр.');
        }
        if ($legal && !$attachment) foreach (['comp_name','bank','bik','raschet','korschet'] as $key) {
            if ($fields[$key]==='') throw new \DomainException('Заполните поле «'.self::LABELS[$key].'» или прикрепите файл с реквизитами.');
        }
    }
    public static function text(array $fields): string
    {
        $lines=[];
        foreach (self::LABELS as $key=>$label) if (($fields[$key]??'')!=='') $lines[]=$label.': '.$fields[$key];
        $purpose=self::PURPOSES[$fields['purchase_purpose']??'']??'';
        if ($purpose!=='') $lines[]='Цель покупки: '.$purpose;
        return implode("\n",$lines);
    }
    public static function html(array $fields): string
    {
        $html='<table role="presentation" style="width:100%;margin:16px 0;border-collapse:collapse">';
        foreach (self::LABELS+['purchase_purpose'=>'Цель покупки'] as $key=>$label) {
            $value=$key==='purchase_purpose' ? (self::PURPOSES[$fields[$key]??'']??'') : ($fields[$key]??'');
            if ($value!=='') $html.='<tr><td style="padding:7px 12px 7px 0;vertical-align:top"><b>'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</b></td><td style="padding:7px 0">'.htmlspecialchars($value,ENT_QUOTES,'UTF-8').'</td></tr>';
        }
        return $html.'</table>';
    }
}
