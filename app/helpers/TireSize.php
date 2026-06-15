<?php
namespace app\helpers;

class TireSize {
    public static function u($s) { // унификация строки
        $s = mb_strtolower((string)$s, 'UTF-8');
        // русская «х» и звездочка считаются как x
        $s = strtr($s, ['х'=>'x','*'=>'x']);
        // нормализуем пробелы
        $s = preg_replace('~\s+~u',' ', trim($s));
        return $s;
    }
    private static function onlyDigits($s){ return preg_replace('~\D+~u','', $s); }

    // === ПАРСЕР РАЗМЕРА ===
    // A) 26x9-12 / 20x10-10 / 26 x 9.00 - 12
    // B) 12-16.5 / 16.9-24 / 18.4-26
    public static function extract($name) {
        $src = self::u($name);

        $reA = '~\b(?P<a>\d{1,2})\s*x\s*(?P<b>\d{1,2}(?:\.\d{1,2})?)\s*-\s*(?P<c>\d{2}(?:\.\d)?)\b~u';
        $reB = '~\b(?P<a>\d{1,2}(?:\.\d{1,2})?)\s*-\s*(?P<b>\d{2}(?:\.\d)?)\b~u';

        if (preg_match($reA, $src, $m)) {
            return self::buildTokens($m['a'], $m['b'], $m['c'], 'A');
        }
        if (preg_match($reB, $src, $m)) {
            return self::buildTokens($m['a'], $m['b'], null, 'B');
        }
        return null;
    }

    // Варианты числа:
    // - если с десятичной: убираем точку (16.5 -> 165; 9.00 -> 900)
    // - если целое «короткое» (1–2 цифры), можем добавлять 1–2 нуля (9 -> 90, 900; 10 -> 100, 1000)
    // roles: 'a' (диаметр/высота), 'b' (ширина/второй параметр), 'c' (посадка)
    private static function expandNumber($num, $role) {
        $out = [];
        $s = (string)$num;

        if (strpos($s, '.') !== false) {
            $out[] = str_replace('.', '', $s); // 10.00 -> 1000; 16.5 -> 165
        } else {
            $out[] = $s;
            $len = strlen($s);
            // Для b почти всегда допускают «нулевые» записи (9 -> 900; 10 -> 1000)
            if ($role === 'b') {
                if ($len === 1) { // 9 → 90, 900
                    $out[] = $s.'0';
                    $out[] = $s.'00';
                } elseif ($len === 2) { // 10 → 100, 1000; 12 → 120, 1200 встречается реже, но пусть будет
                    $out[] = $s.'0';
                    $out[] = $s.'00';
                }
            }
            // Для c бывают 16.5 (обработается через точку), для «10» иногда пишут 10.00 → подстрахуем одним нулём
            if ($role === 'c' && $len === 2) {
                $out[] = $s.'0'; // 10 -> 100
            }
        }

        // Уникализируем
        return array_values(array_unique($out));
    }

    private static function buildTokens($a, $b, $c, $type) {
        $aZ = self::expandNumber((string)$a, 'a');
        $bZ = self::expandNumber((string)$b, 'b');
        $cZ = $c !== null ? self::expandNumber((string)$c, 'c') : [];

        $variants = [];

        if ($type === 'A') { // AxB-C => склейки A+B+C и укороченный A+B
            foreach ($aZ as $aa) foreach ($bZ as $bb) {
                $variants[] = $aa.$bb; // префикс (для 12-16.5 → 1216; для 20x10-10 → 2010)
                foreach ($cZ as $cc) {
                    $variants[] = $aa.$bb.$cc; // 26912, 2690012, 201010, 20100010 и т.д.
                }
            }
        } else { // B: A-B => A+B и сокращение A+первые2(B)
            foreach ($aZ as $aa) foreach ($bZ as $bb) {
                $variants[] = $aa.$bb; // 12165, 16924, 16928 …
                $variants[] = $aa.substr($bb, 0, 2); // 1216, 169 …
            }
        }

        // Отсекаем слишком короткие
        $variants = array_values(array_unique(array_filter($variants, function($s){ return strlen($s) >= 4; })));
        // Каноничный — самый длинный
        usort($variants, function($x,$y){ return strlen($y) - strlen($x); });

        return [
            'digits' => $variants ? $variants[0] : '',
            'tokens' => $variants,
        ];
    }

    // Токены из пользовательского ввода (если не распознали паттерн — вернём «голые цифры»)
    public static function tokensFromQuery($q) {
        $hit = self::extract($q);
        if ($hit) return $hit['tokens'];
        $d = self::onlyDigits(self::u($q));
        return ($d && strlen($d) >= 4) ? [$d] : [];
    }
}
