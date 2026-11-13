<?php
namespace app\services;

/** Add explicit tyre fields using supplied facts only; retain source attributes. */
final class MerchantTyreAttributes
{
    public static function enrich(string $title, array $attributes): array
    {
        if (!preg_match('/шина|шины/iu', $title)) return $attributes;
        $put = static function (string $key, string $value) use (&$attributes): void {
            if (trim((string)($attributes[$key] ?? '')) === '' && trim($value) !== '') $attributes[$key] = trim($value);
        };
        $size = trim((string)($attributes['Размер шины'] ?? ''));
        if (preg_match('/^\d+(?:\.\d+)?(?:[xх]\d+(?:\.\d+)?|\/\d+(?:\.\d+)?|L)?[-R](\d+(?:\.\d+)?)$/iu', $size, $m)) {
            $put('Посадочный диаметр (дюйм)', $m[1]);
        }
        $diameter = (string)($attributes['Посадочный диаметр (дюйм)'] ?? '');
        if (preg_match('/^\s*(\d+(?:\.\d+)?)(?:\s+дюйм\S*)?\s*$/u', $diameter, $m)) $put('Диаметр', $m[1]);
        $put('Ширина профиля', (string)($attributes['Ширина профиля (дюйм)'] ?? ''));
        $put('Конструкция шины', (string)($attributes['Конструкция'] ?? ''));
        $speed = strtr((string)($attributes['Индекс категории скорости'] ?? ''), ['А'=>'A','В'=>'B','С'=>'C','Е'=>'E']);
        $put('Индекс скорости', $speed);
        // A marked load/speed pair is usable; a size such as 21L-24 is not.
        if (preg_match('/(?:^|\s)(\d{2,3})(A[1-8]|[B-Z])(?=\s|$)/u', $title, $m)) {
            $put('Индекс нагрузки', $m[1]);
            $put('Индекс скорости', $m[2]);
        }
        $type = (string)($attributes['Тип шины'] ?? '');
        if ($type === '' && preg_match('/пневматическ/iu', $title)) $put('Тип шины', 'пневматическая');
        if ($type === '' && preg_match('/цельнолит/iu', $title)) $put('Тип шины', 'цельнолитая');
        $sealing = (string)($attributes['Тип'] ?? '');
        if (preg_match('/\bTTF?\b/u', $sealing)) $put('Способ герметизации', 'камерные');
        elseif (preg_match('/\bTL\b/u', $sealing)) $put('Способ герметизации', 'бескамерные');
        if (!empty($attributes['Применение'])) $put('Тип автотехники', 'спецтехника');
        return $attributes;
    }
}
