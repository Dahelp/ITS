<?php

namespace app\helpers;

/**
 * Adds the SEO emphasis requested for a small, explicit set of landing pages.
 *
 * Content is transformed at render time so the rule also applies to text coming
 * from INSEO fallbacks and category/filter records without duplicating HTML in DB.
 */
final class SeoStrong
{
    private const PHRASES = [
        '/category/shiny-dlya-minipogruzchikov' => ['резину на мини погрузчик', 'шин для мини погрузчиков'],
        '/category/shiny-dlya-frontalnyh-pogruzchikov' => ['шины для фронтальных погрузчиков'],
        '/category/shiny-dlya-shahtnoy-tehniki' => ['шины для шахтной техники'],
        '/technics' => ['шины для спецтехники', 'размеры шин на погрузчики'],
        '/category/filtry' => ['фильтры для спецтехники', 'купить фильтры'],
        '/category/diski-dlya-vilochnyh-pogruzchikov' => ['диски для вилочных погрузчиков'],
        '/category/diski-dlya-minipogruzchikov' => ['диски для мини-погрузчиков', 'диски для минипогрузчиков'],
        '/promo' => ['шины для спецтехники', 'выгодные предложения'],
        '/category/shiny-dlya-kolesnyh-ekskavatorov' => ['шины на колесный экскаватор', 'шины для колесных экскаваторов'],
        '/category/shiny-dlya-gruntovyh-katkov' => ['шины для грунтовых катков'],
        '/podbor/diski' => ['подбор дисков', 'подобрать диски по параметрам'],
        '/category/kamery-i-obodnye-lenty' => ['камеры и ободные ленты', 'камеры для спецтехники'],
        '/podbor/shiny' => ['подбор шин', 'подобрать шины по параметрам'],
        '/pages/sotrudnichestvo' => ['выгодные условия сотрудничества', 'индивидуальные условия', 'надежный поставщик'],
        '/category/kolca' => ['кольца для спецтехники', 'купить кольца'],
        '/services' => ['услуги для спецтехники', 'шины для спецтехники'],
        '/news' => ['шины для спецтехники'],
        '/articles' => ['шины для спецтехники'],
        '/catalog' => ['каталог шин', 'шины для спецтехники'],
        '/catalog/shiny' => ['шины для спецтехники', 'купить шины'],
        '/services/oshipovka-i-narezka-protektora-shin-dlya-spectehniki' => ['ошиповка шин', 'нарезка протектора'],
        '/services/shinomontazh-shin-dlya-spectehniki' => ['шиномонтаж', 'шины для спецтехники'],
        '/technics/type/kolesnyj-ekskavator' => ['колесный экскаватор'],
        '/technics/type/vilochnyj-pogruzchik' => ['вилочный погрузчик'],
        '/technics/type/teleskopicheskij-pogruzchik' => ['телескопический погрузчик'],
        '/technics/type/mini-pogruzchik' => ['мини-погрузчик', 'мини погрузчик'],
        '/podbor/kamery' => ['подбор камер', 'подобрать камеры по параметрам'],
    ];

    private const FILTER_PATHS = [
        '/category/shiny-dlya-ekskavatorov-pogruzchikov/ekka' => 'ekka',
        '/category/shiny-dlya-vilochnyh-pogruzchikov/ist' => 'ist',
        '/category/kamery/10.00-20' => '10.00-20',
        '/category/kamery/17.5-25' => '17.5-25',
        '/category/shiny-dlya-frontalnyh-pogruzchikov/17.5-25' => '17.5-25',
        '/category/atv/25x8-12' => '25x8-12',
        '/category/kamery/4.00-8' => '4.00-8',
        '/category/atv/13x5-6' => '13x5-6',
        '/category/gruzovye-shiny/275-70r22-5' => ['275-70r22-5', '275/70R22.5'],
        '/category/atv/18x8.50-8' => '18x8.50-8',
        '/category/atv/26x11-12' => '26x11-12',
        '/category/kamery/16.9-28' => '16.9-28',
        '/category/kamery/5.00-8' => '5.00-8',
        '/category/atv/10-inches' => '10-inches',
        '/category/gruzovye-shiny/kama' => 'kama',
        '/category/atv/20x11-9' => '20x11-9',
        '/category/kamery/6.00-9' => '6.00-9',
        '/category/atv/14-inches' => '14-inches',
        '/category/atv/19x9.50-8' => '19x9.50-8',
        '/category/atv/20x10-8' => '20x10-8',
        '/category/atv/12-inches' => '12-inches',
        '/category/atv/20x10-9' => '20x10-9',
        '/category/atv/22x7-10' => '22x7-10',
        '/category/kamery/14-9-28' => ['14-9-28', '14.9-28'],
        '/category/gruzovye-shiny/annaite' => 'annaite',
        '/category/shiny-dlya-shahtnoy-tehniki/10.00-20' => '10.00-20',
        '/category/shiny-dlya-gruntovyh-katkov/13/80-20' => '80-20',
        '/category/kamery/14-9-24' => ['14-9-24', '14.9-24'],
        '/category/atv/4-inches' => '4-inches',
        '/category/kamery/16.9-24' => '16.9-24',
        '/category/shiny-dlya-greyderov/14.00-24' => '14.00-24',
        '/category/kamery/11-00-20' => ['11-00-20', '11.00-20'],
        '/category/atv/9-inches' => '9-inches',
        '/category/atv/8-inches' => '8-inches',
        '/category/atv/7-inches' => '7-inches',
        '/category/atv/21x7-10' => '21x7-10',
        '/category/shiny-dlya-mobilnyh-kranov/16-00r25' => ['16-00r25', '16.00R25'],
        '/category/shiny-dlya-shahtnoy-tehniki/14.00-24' => '14.00-24',
        '/category/kamery/6.50-10' => '6.50-10',
        '/category/atv/26x9-12' => '26x9-12',
        '/category/atv/15x6-6' => '15x6-6',
        '/category/atv/6-inches' => '6-inches',
        '/category/shiny-dlya-vilochnyh-pogruzchikov/18x7-8' => '18x7-8',
        '/category/shiny-dlya-vilochnyh-pogruzchikov/23x9-10' => '23x9-10',
        '/category/shiny-dlya-vilochnyh-pogruzchikov/28x9-15' => '28x9-15',
        '/category/kamery/14.00-24' => '14.00-24',
        '/category/shiny-dlya-vilochnyh-pogruzchikov/10.00-20' => '10.00-20',
        '/category/shiny-dlya-frontalnyh-pogruzchikov/29.5-25' => '29.5-25',
        '/category/shiny-dlya-frontalnyh-pogruzchikov/26.5-25' => '26.5-25',
        '/category/shiny-dlya-frontalnyh-pogruzchikov/15.5-25' => '15.5-25',
        '/category/shiny-dlya-frontalnyh-pogruzchikov/20-5-70-16' => ['20-5-70-16', '20.5/70-16'],
        '/category/kamery/15x6-6' => '15x6-6',
        '/category/obodnye-lenty/14.00-24' => '14.00-24',
    ];

    public static function apply(string $html, ?string $requestUri = null): string
    {
        $path = self::normalizePath($requestUri ?? ($_SERVER['REQUEST_URI'] ?? '/'));
        $phrases = self::PHRASES[$path] ?? [];

        if (isset(self::FILTER_PATHS[$path])) {
            $filterPhrases = (array)self::FILTER_PATHS[$path];
            $phrases = array_merge($phrases, $filterPhrases);
        }

        if ($phrases === [] || $html === '') {
            return $html;
        }

        foreach (array_values(array_unique($phrases)) as $phrase) {
            $html = self::wrapFirstTextOccurrence($html, $phrase);
        }

        return $html;
    }

    public static function configuredPaths(): array
    {
        return array_values(array_unique(array_merge(array_keys(self::PHRASES), array_keys(self::FILTER_PATHS))));
    }

    private static function normalizePath(string $uri): string
    {
        $path = rawurldecode((string)(parse_url($uri, PHP_URL_PATH) ?: '/'));
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private static function wrapFirstTextOccurrence(string $html, string $phrase): string
    {
        if ($phrase === '' || stripos($html, '<strong>' . $phrase . '</strong>') !== false) {
            return $html;
        }

        $parts = preg_split('~(<[^>]+>)~u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts)) {
            return $html;
        }

        $blocked = 0;
        $blockedTags = ['strong', 'b', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'script', 'style', 'title', 'option', 'a'];
        $pattern = '~' . preg_quote($phrase, '~') . '~iu';

        foreach ($parts as $index => $part) {
            if ($part === '') {
                continue;
            }

            if ($part[0] === '<') {
                if (preg_match('~^<\s*/\s*([a-z0-9]+)~i', $part, $match)) {
                    if (in_array(strtolower($match[1]), $blockedTags, true)) {
                        $blocked = max(0, $blocked - 1);
                    }
                } elseif (preg_match('~^<\s*([a-z0-9]+)\b~i', $part, $match)) {
                    if (
                        in_array(strtolower($match[1]), $blockedTags, true)
                        && !preg_match('~/\s*>$~', $part)
                    ) {
                        $blocked++;
                    }
                }
                continue;
            }

            if ($blocked === 0 && preg_match($pattern, $part)) {
                $parts[$index] = preg_replace($pattern, '<strong>$0</strong>', $part, 1);
                return implode('', $parts);
            }
        }

        return $html;
    }
}
