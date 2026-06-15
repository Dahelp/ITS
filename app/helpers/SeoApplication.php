<?php

namespace app\helpers;

class SeoApplication
{
    public static function productShortText(string $size = '', array $technics = [], $catProd = null): string
    {
        $categoryName = self::categoryName($catProd);
        $applicationText = self::applicationTextByCategoryName($categoryName);
        $brandsText = self::brandsText($technics);

        $size = trim($size);

        if ($size !== '') {
            $text = "Шины {$size} применяются {$applicationText}.";
        } else {
            $text = "Данный типоразмер шин применяется {$applicationText}.";
        }

        if ($brandsText !== '') {
            $text .= " Используются на технике {$brandsText}.";
        }

        return $text;
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

    private static function applicationTextByCategoryName(string $categoryName): string
    {
        $name = self::normalize($categoryName);

        /**
         * Строгая логика по названию категории.
         * Сначала конкретные категории, потом общие.
         */

        if ($name === 'шины для минипогрузчиков') {
            return 'на минипогрузчиках и строительной технике';
        }

        if ($name === 'шины для вилочных погрузчиков') {
            return 'на вилочных погрузчиках и складской технике';
        }

        if ($name === 'шины для экскаваторов-погрузчиков') {
            return 'на экскаваторах-погрузчиках и строительной технике';
        }

        if ($name === 'шины для фронтальных погрузчиков') {
            return 'на фронтальных погрузчиках и строительной технике';
        }

        if ($name === 'шины для колесных экскаваторов' || $name === 'шины для колёсных экскаваторов') {
            return 'на колёсных экскаваторах и строительной технике';
        }

        if ($name === 'шины для грунтовых катков') {
            return 'на грунтовых катках и дорожной технике';
        }

        if ($name === 'шины для грейдеров') {
            return 'на грейдерах и дорожно-строительной технике';
        }

        if ($name === 'шины для шахтной техники') {
            return 'на шахтной и горнодобывающей технике';
        }

        if ($name === 'шины для мобильных кранов') {
            return 'на мобильных кранах и подъёмной технике';
        }

        if ($name === 'шины atv для квадроциклов') {
            return 'на квадроциклах, ATV-технике и мотовездеходах';
        }

        if ($name === 'грузовые шины') {
            return 'на грузовой и коммерческой технике';
        }

        if ($name === 'шины для спецтехники') {
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
            $brandsText .= ' и др.';
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