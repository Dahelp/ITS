<?php

namespace ishop;

use NumberFormatter;
use Guzzlehttp\Guzzle;

class App
{
    public static $app;

    public function __construct()
    {
        $query = trim($_SERVER['QUERY_STRING'] ?? '', '/');

        // ВАЖНО:
        // Не стартуем сессию глобально на каждом запросе.
        // Для публичных страниц это мешает кэшированию и добавляет лишнюю задержку.
        self::$app = Registry::instance();
        $this->getParams();
        new ErrorHandler();
        Router::dispatch($query);
    }

    protected function getParams()
    {
        $params = require_once CONF . '/params.php';
        if (!empty($params)) {
            foreach ($params as $k => $v) {
                self::$app->setProperty($k, $v);
            }
        }
    }

    public static function contdate($date_post)
    {
        if (empty($date_post)) {
            return '';
        }

        static $_monthsList = [
            "01" => "января",
            "02" => "февраля",
            "03" => "марта",
            "04" => "апреля",
            "05" => "мая",
            "06" => "июня",
            "07" => "июля",
            "08" => "августа",
            "09" => "сентября",
            "10" => "октября",
            "11" => "ноября",
            "12" => "декабря",
        ];

        $parts = explode(' ', trim((string)$date_post));
        $currentDate = $parts[0] ?? '';

        if (!$currentDate) {
            return '';
        }

        $dateParts = explode('-', $currentDate);
        if (count($dateParts) !== 3) {
            return $currentDate;
        }

        [$y, $m, $d] = $dateParts;

        return (int)$d . ' ' . ($_monthsList[$m] ?? $m) . ' ' . $y;
    }

    public static function abbreviateddate($date_post)
    {
        if (empty($date_post)) {
            return '';
        }

        static $_monthsList = [
            "01" => "янв",
            "02" => "фев",
            "03" => "мар",
            "04" => "апр",
            "05" => "мая",
            "06" => "июн",
            "07" => "июл",
            "08" => "авг",
            "09" => "сен",
            "10" => "окт",
            "11" => "ноя",
            "12" => "дек",
        ];

        $parts = explode(' ', trim((string)$date_post));
        $currentDate = $parts[0] ?? '';

        $dateParts = explode('-', $currentDate);
        if (count($dateParts) !== 3) {
            return $currentDate;
        }

        [$y, $m, $d] = $dateParts;

        return (int)$d . ' ' . ($_monthsList[$m] ?? $m) . ' ' . $y;
    }

    public static function formatDate($date)
    {
        if (empty($date)) {
            return '';
        }

        $parts = explode(' ', trim((string)$date));
        $currentDate = $parts[0] ?? '';

        $dateParts = explode('-', $currentDate);
        if (count($dateParts) !== 3) {
            return (string)$date;
        }

        return "{$dateParts[2]}.{$dateParts[1]}.{$dateParts[0]}";
    }

    public static function contdatetime($date_post)
    {
        if (empty($date_post)) {
            return '';
        }

        static $_monthsList = [
            "01" => "января",
            "02" => "февраля",
            "03" => "марта",
            "04" => "апреля",
            "05" => "мая",
            "06" => "июня",
            "07" => "июля",
            "08" => "августа",
            "09" => "сентября",
            "10" => "октября",
            "11" => "ноября",
            "12" => "декабря",
        ];

        $parts = explode(' ', trim((string)$date_post));
        $currentDate = $parts[0] ?? '';
        $currentOclock = $parts[1] ?? '';

        if (!$currentDate) {
            return '';
        }

        $dateParts = explode('-', $currentDate);
        if (count($dateParts) !== 3) {
            return (string)$date_post;
        }

        [$y, $m, $d] = $dateParts;

        $h = '00';
        $i = '00';

        if ($currentOclock !== '') {
            $timeParts = explode(':', $currentOclock);
            $h = $timeParts[0] ?? '00';
            $i = $timeParts[1] ?? '00';
        }

        return (int)$d . ' ' . ($_monthsList[$m] ?? $m) . ' ' . $y . ' ' . $h . ':' . $i;
    }

    public static function getPeriod($date1, $date2)
    {
        $date1 = date_create_from_format('Y-m-d', (string)$date1);
        $date2 = date_create_from_format('Y-m-d', (string)$date2);

        if (!$date1 || !$date2) {
            return '';
        }

        $interval = date_diff($date1, $date2);
        $y = '';
        $m = '';
        $d = '';

        if ($interval->y > 0) {
            if ($interval->y > 4) {
                $y .= $interval->y . ' лет';
            } elseif ($interval->y == 1) {
                $y .= $interval->y . ' год';
            } else {
                $y .= $interval->y . ' года';
            }
            $y .= ', ';
        }

        if ($interval->m > 0) {
            if ($interval->m > 4) {
                $m .= $interval->m . ' месяцев';
            } elseif ($interval->m > 1) {
                $m .= $interval->m . ' месяца';
            } else {
                $m .= $interval->m . ' месяц';
            }
            $m .= ', ';
        }

        if ($interval->d > 0) {
            if ($interval->d > 4) {
                $d .= $interval->d . ' дней';
            } elseif ($interval->d > 1) {
                $d .= $interval->d . ' дня';
            } else {
                $d .= $interval->d . ' день';
            }
        } else {
            $d .= ' сегодня';
        }

        return $y . $m . $d;
    }

    public static function getPeriodMailbox($date1, $date2)
    {
        $date1 = date_create_from_format('Y-m-d H:i:s', (string)$date1);
        $date2 = date_create_from_format('Y-m-d H:i:s', (string)$date2);

        if (!$date1 || !$date2) {
            return 0;
        }

        $interval = date_diff($date1, $date2);
        return (int)$interval->d;
    }

    public static function options($alt_name)
    {
        static $cache = [];

        $alt_name = (string)$alt_name;
        if ($alt_name === '') {
            return '';
        }

        if (array_key_exists($alt_name, $cache)) {
            return $cache[$alt_name];
        }

        $cache[$alt_name] = \R::getCell(
            "SELECT znachenie FROM options WHERE alt_name = ? LIMIT 1",
            [$alt_name]
        ) ?: '';

        return $cache[$alt_name];
    }

    public static function invoice_num($input, $pad_len = 7, $prefix = null)
    {
        if ($pad_len <= strlen((string)$input)) {
            trigger_error('<strong>$pad_len</strong> не может быть меньше или равна длине <strong>$input</strong> для генерации номера счета', E_USER_ERROR);
        }

        if (is_string($prefix)) {
            return sprintf("%s%s", $prefix, str_pad((string)$input, $pad_len, "0", STR_PAD_LEFT));
        }

        return str_pad((string)$input, $pad_len, "0", STR_PAD_LEFT);
    }

    public static function format_price($value)
    {
        return number_format((float)$value, 2, ',', ' ');
    }

    public static function str_price($value)
    {
        $value = explode('.', number_format((float)$value, 2, '.', ''));

        $f = new NumberFormatter('ru', NumberFormatter::SPELLOUT);

        $str = $f->format($value[0]);
        $str = mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1, mb_strlen($str));

        $num = ((int)$value[0]) % 100;
        if ($num > 19) {
            $num = $num % 10;
        }

        switch ($num) {
            case 1:
                $rub = 'рубль';
                break;
            case 2:
            case 3:
            case 4:
                $rub = 'рубля';
                break;
            default:
                $rub = 'рублей';
        }

        return $str . ' ' . $rub . ' ' . $value[1] . ' копеек.';
    }

    public static function seoreplace($text, $id)
    {
        return self::replaceSeoPlaceholders(
            (string)$text,
            function (string $urlsys) use ($id) {
                return \R::getCell(
                    "SELECT pa.attribute_text
                     FROM product_attribute pa
                     JOIN attribute a ON pa.attribute_id = a.id
                     WHERE pa.product_id = ? AND a.url_params = ?
                     LIMIT 1",
                    [(int)$id, $urlsys]
                ) ?: '';
            }
        );
    }

    public static function seoreplacefilter($text, $id)
    {
        return self::replaceSeoPlaceholders(
            (string)$text,
            function (string $urlsys) use ($id) {
                return \R::getCell(
                    "SELECT av.value
                     FROM attribute_value av
                     JOIN attribute_group ag ON av.attr_group_id = ag.id
                     WHERE av.id = ? AND ag.url_params = ?
                     LIMIT 1",
                    [(int)$id, $urlsys]
                ) ?: '';
            }
        );
    }

    public static function seoreplacetiposize($text, $values)
    {
        $values = trim((string)$values);
        if ($values === '') {
            return trim(str_replace(["\r", "\n"], ' ', (string)$text));
        }

        return self::replaceSeoPlaceholders(
            (string)$text,
            function (string $urlsys) use ($values) {
                return \R::getCell(
                    "SELECT pa.attribute_text
                     FROM product_attribute pa
                     JOIN attribute a ON a.id = pa.attribute_id
                     WHERE a.url_params = ?
                       AND pa.product_id IN (" . $values . ")
                     LIMIT 1",
                    [$urlsys]
                ) ?: '';
            }
        );
    }

    protected static function replaceSeoPlaceholdersAdvanced(
        string $text,
        array $vars = [],
        array $context = [],
        array $options = []
    ): string {
        $text = trim((string)$text);

        if ($text === '') {
            return '';
        }

        $removeUnresolvedLines = $options['remove_unresolved_lines'] ?? true;
        $removeUnresolvedPlaceholders = $options['remove_unresolved_placeholders'] ?? true;
        $normalizeSpaces = $options['normalize_spaces'] ?? true;

        $lines = preg_split('/\R/u', $text) ?: [$text];
        $resultLines = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $hasUnresolved = false;

            $line = preg_replace_callback('/{([^}]+)}/u', function ($matches) use ($vars, $context, &$hasUnresolved) {
                $key = trim((string)$matches[1]);

                /*
                * Если переменная известна, подставляем её даже если значение пустое.
                * Это нужно для {min_price_text}: если цены нет, просто удаляем эту фразу,
                * но не удаляем весь description.
                */
                if (array_key_exists($key, $vars)) {
                    return (string)$vars[$key];
                }

                $value = self::resolveInseoPlaceholderFromProducts($key, $context);

                if ($value !== '') {
                    return $value;
                }

                /*
                * Неизвестная переменная — это уже ошибка шаблона.
                * Например, опечатка {priemenenie}.
                */
                $hasUnresolved = true;

                return $matches[0];
            }, $line);

            if ($hasUnresolved && $removeUnresolvedLines) {
                continue;
            }

            if ($removeUnresolvedPlaceholders) {
                $line = preg_replace('/{[^}]+}/u', '', $line);
            }

            $line = trim($line);

            if ($line !== '') {
                $resultLines[] = $line;
            }
        }

        $result = implode(' ', $resultLines);

        if ($normalizeSpaces) {
            $result = str_replace(["\r", "\n", "\t"], ' ', $result);

            while (strpos($result, '  ') !== false) {
                $result = str_replace('  ', ' ', $result);
            }

            $result = preg_replace('~\s+([.,!?;:])~u', '$1', $result);
            $result = preg_replace('~\s{2,}~u', ' ', $result);
            $result = trim($result);
        }

        return $result;
    }

    protected static function resolveInseoPlaceholderFromProducts(string $placeholder, array $context = []): string
    {
        $placeholder = trim($placeholder);

        if ($placeholder === '') {
            return '';
        }

        $productIds = $context['product_ids'] ?? [];

        if (empty($productIds) || !is_array($productIds)) {
            return '';
        }

        $productIds = array_values(array_filter(array_map('intval', $productIds)));

        if (empty($productIds)) {
            return '';
        }

        $ids = implode(',', $productIds);

        /*
        * Ищем значение атрибута по системному имени attribute.url_params.
        * Например:
        * {primenenie} -> attribute.url_params = primenenie
        * {brand}      -> attribute.url_params = brand
        * {protector}  -> attribute.url_params = protector
        * {PR}         -> attribute.url_params = PR
        */
        $value = \R::getCell(
            "SELECT pa.attribute_text
            FROM product_attribute pa
            JOIN attribute a ON a.id = pa.attribute_id
            WHERE pa.product_id IN ($ids)
            AND a.url_params = ?
            AND pa.attribute_text <> ''
            GROUP BY pa.attribute_text
            ORDER BY COUNT(*) DESC, pa.attribute_text ASC
            LIMIT 1",
            [$placeholder]
        );

        $value = trim((string)$value);

        if ($value === '') {
            return '';
        }

        /*
        * Для применения в SEO-шаблонах используем нижний регистр:
        * {primenenie} -> вилочный погрузчик
        * Чтобы не получалось: "на Вилочный погрузчик"
        */
        if (in_array($placeholder, ['primenenie', 'application', 'technique'], true)) {
            $value = mb_strtolower($value, 'UTF-8');
        }

        return $value;
    }

    protected static function replaceSeoPlaceholders(string $text, callable $resolver): string
    {
        if ($text === '' || strpos($text, '{') === false) {
            return trim(str_replace(["\r", "\n", "  "], ' ', $text));
        }

        $lines = preg_split('/\R/u', $text) ?: [$text];
        $resolvedCache = [];

        foreach ($lines as $line) {
            if (!preg_match_all('/{([^}]+)}/u', $line, $matches)) {
                continue;
            }

            $removeLine = false;

            foreach ($matches[1] as $placeholder) {
                if (!array_key_exists($placeholder, $resolvedCache)) {
                    $resolvedCache[$placeholder] = (string)$resolver($placeholder);
                }

                $value = $resolvedCache[$placeholder];

                if ($value !== '') {
                    $text = str_replace('{' . $placeholder . '}', $value, $text);
                } else {
                    $removeLine = true;
                    break;
                }
            }

            if ($removeLine) {
                $text = str_replace($line, '', $text);
            }
        }

        $text = str_replace(["\r", "\n"], ' ', $text);
        while (strpos($text, '  ') !== false) {
            $text = str_replace('  ', ' ', $text);
        }

        return trim($text);
    }

    public static function renderInseo(string $text, array $context = [], array $options = []): string
    {
        $text = trim((string)$text);

        if ($text === '') {
            return '';
        }

        $options = array_merge([
            'remove_unresolved_lines' => true,
            'remove_unresolved_placeholders' => true,
            'normalize_spaces' => true,
        ], $options);

        $vars = self::buildInseoVariables($context);

        return self::replaceSeoPlaceholdersAdvanced($text, $vars, $context, $options);
    }

    protected static function buildInseoVariables(array $context = []): array
    {
        $vars = [];

        $category = $context['category'] ?? null;

        if ($category) {
            $categoryName = trim((string)($category->name ?? ''));
            $categoryH1 = trim((string)($category->h1 ?? ''));
            $categoryAlias = trim((string)($category->alias ?? ''), '/');
            $categorySeoTitle = trim((string)($category->title ?? ''));

            $vars['category'] = $categoryName;
            $vars['category_name'] = $categoryName;
            $vars['category_h1'] = $categoryH1 !== '' ? $categoryH1 : $categoryName;
            $vars['category_alias'] = $categoryAlias;
            $vars['category_title'] = $categorySeoTitle;
            $vars['category_primenenie'] = self::getInseoPrimenenieByCategory($category);
            $vars['primenenie_category'] = $vars['category_primenenie'];
            $vars['primenenie'] = $vars['category_primenenie'];
        }

        $filter = $context['filter'] ?? null;

        if ($filter) {
            $filterValue = trim((string)($filter->value ?? ''));
            $filterAlias = trim((string)($filter->alias ?? ''));

            $vars['filter'] = $filterValue;
            $vars['filter_value'] = $filterValue;
            $vars['filter_alias'] = $filterAlias;

            /*
            * Для текущей страницы category/filter:
            * если выбранный фильтр — группа size, то {size} = значение фильтра.
            */
            $filterGroupUrl = trim((string)($context['filter_group_url_params'] ?? ''));

            if ($filterGroupUrl !== '') {
                $vars[$filterGroupUrl] = $filterValue;
            }

            if ($filterGroupUrl === 'size') {
                $vars['size'] = $filterValue;
            }
        }

        if (isset($context['min_price']) && (float)$context['min_price'] > 0) {
            $minPrice = (float)$context['min_price'];
            $minPriceFormatted = number_format($minPrice, 0, '.', ' ');

            $vars['min_price'] = $minPriceFormatted;
            $vars['price_min'] = $minPriceFormatted;
            $vars['min_price_text'] = 'Цены от ' . $minPriceFormatted . ' руб.';
            $vars['price_from'] = 'Цены от ' . $minPriceFormatted . ' руб.';
        } else {
            $vars['min_price'] = '';
            $vars['price_min'] = '';
            $vars['min_price_text'] = '';
            $vars['price_from'] = '';
        }

        if (!empty($context['extra']) && is_array($context['extra'])) {
            foreach ($context['extra'] as $key => $value) {
                $vars[(string)$key] = trim((string)$value);
            }
        }

        return $vars;
    }

    protected static function getInseoPrimenenieByCategory($category): string
    {
        $alias = trim((string)($category->alias ?? ''), '/');

        $map = [
            'atv' => 'квадроциклы',

            'industrialnye-shiny' => 'спецтехнику',
            'shiny-dlya-minipogruzchikov' => 'минипогрузчики',
            'shiny-dlya-kolesnyh-ekskavatorov' => 'колёсные экскаваторы',
            'shiny-dlya-vilochnyh-pogruzchikov' => 'вилочные погрузчики',
            'shiny-dlya-frontalnyh-pogruzchikov' => 'фронтальные погрузчики',
            'shiny-dlya-gruntovyh-katkov' => 'грунтовые катки',
            'shiny-dlya-greyderov' => 'грейдеры',
            'shiny-dlya-shahtnoy-tehniki' => 'шахтную технику',
            'shiny-dlya-mobilnyh-kranov' => 'мобильные краны',

            'kamery' => 'спецтехнику',
            'kamery-i-obodnye-lenty' => 'спецтехнику',
            'obodnye-lenty' => 'спецтехнику',
        ];

        if (isset($map[$alias])) {
            return $map[$alias];
        }

        return trim((string)($category->name ?? ''));
    }

    public static function on_line()
    {
        if (!function_exists('ensure_session_started')) {
            return;
        }

        ensure_session_started();

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $wine = 300;
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

        \R::exec(
            "DELETE FROM user_online WHERE unix + ? < ? OR user_id = ?",
            [$wine, time(), $userId]
        );

        \R::exec(
            "INSERT INTO user_online (ip, user_id, unix) VALUES (?, ?, ?)",
            [$remoteAddr, $userId, time()]
        );
    }

    public static function styleCss()
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $cached = \R::getCell(
            "SELECT znachenie FROM options WHERE tip = ? LIMIT 1",
            ['Оформление']
        ) ?: '';

        return $cached;
    }

    public static function upFirstLetter($str, $encoding = 'UTF-8')
    {
        $str = (string)$str;
        if ($str === '') {
            return '';
        }

        return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding)
            . mb_substr($str, 1, null, $encoding);
    }

    public static function downFirstLetter($str, $encoding = 'UTF-8')
    {
        $str = (string)$str;
        if ($str === '') {
            return '';
        }

        return mb_strtolower(mb_substr($str, 0, 1, $encoding), $encoding)
            . mb_substr($str, 1, null, $encoding);
    }

    public static function generate_password($number)
    {
        $arr = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','r','s','t','u','v','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','R','S','T','U','V','X','Y','Z','1','2','3','4','5','6','7','8','9','0'];

        $pass = "";
        for ($i = 0; $i < (int)$number; $i++) {
            $index = rand(0, count($arr) - 1);
            $pass .= $arr[$index];
        }

        return $pass;
    }

    public static function upRegistrLetter($alias)
    {
        if (preg_match('/(?=.*[A-Z])(?=.*\D)/', (string)$alias)) {
            header('HTTP/1.1 404 Not Found', true, 404);
            include("errors/404.php");
            exit();
        }
    }
}
