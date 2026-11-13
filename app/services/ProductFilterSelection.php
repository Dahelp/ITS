<?php
namespace app\services;

final class ProductFilterSelection
{
    /** Accept the existing single-select forms and the chain size multi-select. */
    public static function ids(array $groups): array
    {
        $ids = [];
        foreach ($groups as $values) {
            foreach (is_array($values) ? $values : [$values] as $value) {
                if ($value === '' || $value === null) continue;
                if ((!is_string($value) && !is_int($value)) || !ctype_digit((string)$value) || (int)$value < 1) {
                    throw new \InvalidArgumentException('Некорректное значение фильтра');
                }
                $ids[(int)$value] = (int)$value;
            }
        }
        return array_values($ids);
    }
}
