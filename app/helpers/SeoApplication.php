<?php

namespace app\helpers;

class SeoApplication
{
    public static function productShortText(string $size = '', array $technics = [], $catProd = null): string
    {
        $categoryName = self::categoryName($catProd);
        $productKind = self::productKind($catProd, $categoryName);
        $applicationText = self::applicationTextByCategoryName($categoryName);
        $brandsText = self::brandsText($technics);

        $size = trim($size);

        $text = self::baseProductText($productKind, $size, $applicationText);

        if ($brandsText !== '') {
            $text .= " Используются на технике {$brandsText}.";
        }

        return $text;
    }

    private static function baseProductText(string $productKind, string $size, string $applicationText): string
    {
        if ($productKind === 'disk') {
            return $size !== ''
                ? "Диски под шину {$size} применяются {$applicationText}."
                : "Данные диски применяются {$applicationText}.";
        }

        if ($productKind === 'camera') {
            return $size !== ''
                ? "Камеры {$size} применяются {$applicationText}."
                : "Данные камеры применяются {$applicationText}.";
        }

        if ($productKind === 'rim_strip') {
            return $size !== ''
                ? "Ободные ленты {$size} применяются {$applicationText}."
                : "Данные ободные ленты применяются {$applicationText}.";
        }

        if ($productKind === 'tire') {
            return $size !== ''
                ? "Шины {$size} применяются {$applicationText}."
                : "Данный типоразмер шин применяется {$applicationText}.";
        }

        return $size !== ''
            ? "Товары типоразмера {$size} применяются {$applicationText}."
            : "Данные товары применяются {$applicationText}.";
    }

    private static function productKind($catProd, string $categoryName): string
    {
        $id = self::categoryInt($catProd, 'id');
        $parentId = self::categoryInt($catProd, 'parent_id');
        $alias = self::categoryAlias($catProd);
        $name = self::normalize($categoryName);

        if ($id === 3 || $parentId === 3 || strpos($alias, 'disk') !== false || mb_strpos($name, 'диск') !== false) {
            return 'disk';
        }

        if ($alias === 'obodnye-lenty' || mb_strpos($name, 'ободн') !== false) {
            return 'rim_strip';
        }

        if ($alias === 'kamery' || mb_strpos($name, 'камер') !== false) {
            return 'camera';
        }

        if ($id === 1 || $id === 2 || $parentId === 1 || $parentId === 2 || mb_strpos($name, 'шин') !== false) {
            return 'tire';
        }

        return 'product';
    }

    private static function categoryName($catProd): string
    {
        if (is_array($catProd)) {
            return trim((string)($catProd['name'] ?? ''));
        }

        if (is_object($catProd)) {
            return trim((string)($catProd->name ?? ''));
        }

        return '';
    }

    private static function categoryAlias($catProd): string
    {
        if (is_array($catProd)) {
            return trim((string)($catProd['alias'] ?? ''));
        }

        if (is_object($catProd)) {
            return trim((string)($catProd->alias ?? ''));
        }

        return '';
    }

    private static function categoryInt($catProd, string $field): int
    {
        if (is_array($catProd)) {
            return (int)($catProd[$field] ?? 0);
        }

        if (is_object($catProd)) {
            return (int)($catProd->{$field} ?? 0);
        }

        return 0;
    }

    private static function applicationTextByCategoryName(string $categoryName): string
    {
        $name = self::normalize($categoryName);

        /**
         * Строгая логика по названию категории.
         * Сначала конкретные категории, потом общие.
         */

        if (mb_strpos($name, 'минипогруз') !== false) {
            return 'на минипогрузчиках и строительной технике';
        }

        if (mb_strpos($name, 'вилочн') !== false) {
            return 'на вилочных погрузчиках и складской технике';
        }

        if (mb_strpos($name, 'экскаватор') !== false && mb_strpos($name, 'погруз') !== false) {
            return 'на экскаваторах-погрузчиках и строительной технике';
        }

        if (mb_strpos($name, 'фронтальн') !== false) {
            return 'на фронтальных погрузчиках и строительной технике';
        }

        if (mb_strpos($name, 'колесн') !== false && mb_strpos($name, 'экскаватор') !== false) {
            return 'на колёсных экскаваторах и строительной технике';
        }

        if (mb_strpos($name, 'грунтов') !== false && mb_strpos($name, 'катк') !== false) {
            return 'на грунтовых катках и дорожной технике';
        }

        if (mb_strpos($name, 'грейдер') !== false) {
            return 'на грейдерах и дорожно-строительной технике';
        }

        if (mb_strpos($name, 'шахт') !== false) {
            return 'на шахтной и горнодобывающей технике';
        }

        if (mb_strpos($name, 'мобильн') !== false && mb_strpos($name, 'кран') !== false) {
            return 'на мобильных кранах и подъёмной технике';
        }

        if (mb_strpos($name, 'atv') !== false || mb_strpos($name, 'квадроцикл') !== false) {
            return 'на квадроциклах, ATV-технике и мотовездеходах';
        }

        if (mb_strpos($name, 'грузов') !== false) {
            return 'на грузовой и коммерческой технике';
        }

        if (mb_strpos($name, 'спецтехник') !== false) {
            return 'на спецтехнике, строительной и промышленной технике';
        }

        /**
         * Безопасный fallback.
         */
        return 'на технике, для которой предусмотрен данный типоразмер';
    }

    private static function brandsText(array $technics): string
    {
        $brands = [];

        foreach ($technics as $t) {
            if (is_array($t)) {
                $name = $t['name'] ?? '';
            } elseif (is_object($t)) {
                $name = $t->name ?? '';
            } else {
                $name = '';
            }

            $name = trim((string)$name);

            if ($name !== '') {
                $brands[] = $name;
            }
        }

        $brands = array_values(array_unique($brands));

        if (!$brands) {
            return '';
        }

        $brandsShort = array_slice($brands, 0, 4);
        $brandsText = implode(', ', $brandsShort);

        if (count($brands) > 4) {
            $brandsText .= ' и др';
        }

        return $brandsText;
    }

    private static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace('ё', 'е', $text);
        $text = preg_replace('~\s+~u', ' ', $text);

        return trim($text);
    }
}
