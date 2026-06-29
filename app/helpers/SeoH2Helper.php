<?php

namespace app\helpers;

class SeoH2Helper
{
    private const H2_BY_PATH = [
        '/category/filtry' => 'Фильтры по типу назначения',
        '/pages/contacts' => 'Адрес, телефон и реквизиты',
        '/services/dostavka' => 'Условия и способы доставки',
        '/category/shiny-dlya-mobilnyh-kranov' => 'Шины для крановой техники по размерам',
        '/category/shiny-dlya-gruntovyh-katkov' => 'Шины для катков по размерам',
        '/category/gruzovye-shiny' => 'Ведущие, рулевые и прицепные',
        '/podbor/shiny' => 'Выберите параметры для подбора',
        '/category/kamery/10.00-20' => 'Камеры для грузовых шин и спецтехники',
        '/podbor/diski' => 'Подберите диск по параметрам',
        '/category/kamery/4.00-8' => 'Камеры для малогабаритной техники',
        '/category/atv/25x8-12' => 'Все производители и модели шин 25x8-12',
        '/category/kamery/17.5-25' => 'Камеры для спецтехники и экскаваторов',
        '/category/atv/26x9-12' => 'Все производители и модели шин 26x9-12',
        '/category/atv/20x11-9' => 'Все производители и модели шин 20x11-9',
        '/category/atv/23x7-10' => 'Все производители и модели шин 23x7-10',
        '/category/kamery/6.50-10' => 'Камеры для спецтехники и прицепов',
        '/category/atv/25x10-12' => 'Все производители и модели шин 25x10-12',
        '/category/atv/21x7-10' => 'Все производители и модели шин 21x7-10',
        '/category/atv/13x5-6' => 'Все производители и модели шин 13x5-6',
        '/category/atv/18x8.50-8' => 'Все производители и модели шин 18x8.50-8',
        '/category/atv/10-inches' => 'Каталог шин ATV 10 дюймов',
        '/category/shiny-dlya-greyderov/14.00-24' => 'Модели шин 14.00-24 для грейдеров',
        '/category/gruzovye-shiny/275-70r22-5' => 'Модели шин 275/70R22.5 от разных производителей',
        '/category/kamery/11-00-20' => 'Камеры для грузовиков и спецтехники',
        '/category/atv/20x10-8' => 'Все производители и модели шин 20x10-8',
        '/category/shiny-dlya-gruntovyh-katkov/13/80-20' => 'Модели шин 13/80-20 для грунтовых катков',
        '/category/atv/22x7-10' => 'Все производители и модели шин 22x7-10',
        '/category/atv/19x9.50-8' => 'Все производители и модели шин 19x9.50-8',
        '/category/atv/145/70-6' => 'Все производители и модели шин 145/70-6',
        '/category/kamery/6.00-9' => 'Камеры для погрузчиков и спецтехники',
        '/category/shiny-dlya-mobilnyh-kranov/16-00r25' => 'Модели шин 16.00R25 для кранов',
        '/category/atv/16x6.50-8' => 'Все производители и модели шин 16x6.50-8',
        '/category/atv/12-inches' => 'Каталог шин ATV 12 дюймов',
        '/category/atv/26x11-12' => 'Все производители и модели шин 26x11-12',
        '/category/atv/20x10-9' => 'Все производители и модели шин 20x10-9',
        '/category/kamery/5.00-8' => 'Камеры для мототехники и прицепов',
        '/category/kamery/14-9-28' => 'Камеры для сельхозтехники',
        '/category/shiny-dlya-shahtnoy-tehniki/14.00-24' => 'Модели шин 14.00-24 для шахтной техники',
        '/category/atv/9-inches' => 'Каталог шин ATV 9 дюймов',
        '/category/atv/4-inches' => 'Каталог шин ATV 4 дюйма',
        '/category/atv/14-inches' => 'Каталог шин ATV 14 дюймов',
        '/category/gruzovye-shiny/kama' => 'Ведущие, рулевые и прицепные шины Кама',
        '/sitemap' => 'Разделы и страницы каталога',
        '/category/kamery/8.25-15' => 'Камеры для грузовых автомобилей',
        '/articles' => 'Полезные материалы о шинах и спецтехнике',
        '/category/atv/7-inches' => 'Каталог шин ATV 7 дюймов',
        '/pages/about-us' => 'Поставщик шин для спецтехники с 2007 года',
        '/category/gruzovye-shiny/annaite' => 'Грузовые шины Annaite — ведущие и прицепные',
        '/services' => 'Шинные услуги от ИТС-Центр',
        '/category/kolca' => 'Бортовые, замковые и конические кольца',
        '/pages/oplata' => 'Наличный и безналичный расчёт',
        '/news' => 'Последние новости компании и отрасли',
        '/pages/sotrudnichestvo' => 'Варианты партнёрства для дилеров и оптовиков',
        '/category/atv/8-inches' => 'Каталог шин ATV 8 дюймов',
        '/product/w15x28-et31-pcd5x335-hub289-5-ekka-disk-dlya-ekskavatora-pogruzchika' => 'Параметры диска и совместимость',
        '/technics/type/ekskavator-pogruzchik' => 'О шинах для экскаваторов-погрузчиков',
        '/product/w9x18-et0-pcd5x335-hub290-5-ekka-disk-dlya-ekskavatora-pogruzchika' => 'Параметры диска и совместимость',
        '/product/kamera-5-00-8-dlya-shin' => 'Характеристики камеры и совместимость',
        '/product/w15x28-et50-pcd10x335-hub281-5-ekka-disk-dlya-ekskavatora-pogruzchika' => 'Параметры диска и совместимость',
        '/product/11x18-et64-pcd8x275-hub221-ekka-disk-dlya-ekskavatora-pogruzchika' => 'Параметры диска и совместимость',
        '/services/oshipovka-i-narezka-protektora-shin-dlya-spectehniki' => 'Как мы выполняем ошиповку и нарезку',
        '/product/12-00r20-nr-701-d-kama-shina-gruzovaya-veduschaya' => 'Характеристики и применение шины',
        '/technics/type/vilochnyj-pogruzchik' => 'О шинах для вилочных погрузчиков',
        '/product/kamera-17-5-25-dlya-shin' => 'Характеристики камеры и совместимость',
        '/technics/type/kolesnyj-ekskavator' => 'О шинах для колёсных экскаваторов',
        '/product/16x26-et79-pcd5x335-hub-290-ekka-disk-dlya-ekskavatora-pogruzchika' => 'Параметры диска и совместимость',
        '/product/kamera-4-00-8-dlya-shin' => 'Характеристики камеры и совместимость',
        '/product/8-25r16-lt-d-306-annaite-shina-gruzovaya-veduschaya' => 'Характеристики и применение шины',
        '/technics/type/mini-pogruzchik' => 'О шинах для мини-погрузчиков',
        '/product/245-70r19-5-nu-301-m-s-kama-shina-gruzovaya-universalnaya' => 'Характеристики и применение шины',
        '/product/315-70r22-5-d-785-annaite-shina-gruzovaya-veduschaya' => 'Характеристики и применение шины',
        '/product/225-75r17-5-d-nr-202-kama-shina-gruzovaya-veduschaya' => 'Характеристики и применение шины',
        '/product/12-00r24-nr-701-d-kama-shina-gruzovaya-veduschaya' => 'Характеристики и применение шины',
        '/product/225-75r17-5-f-nf-202-kama-shina-gruzovaya-rulevaya' => 'Характеристики и применение шины',
        '/technics/type/teleskopicheskij-pogruzchik' => 'О шинах для телескопических погрузчиков',
        '/product/245-70r17-5-d-nr-202-kama-shina-gruzovaya-veduschaya' => 'Характеристики и применение шины',
        '/product/215-75r17-5-t-nt-202-kama-shina-gruzovaya-na-pricep' => 'Характеристики и применение шины',
        '/product/235-75r17-5-d-nr-202-kama-shina-gruzovaya-veduschaya' => 'Характеристики и применение шины',
        '/podbor/kamery' => 'Выберите параметры для подбора камеры',
        '/product/15lx24-et36-pcd5x335-hub290-lantian-disk-dlya-ekskavatora-pogruzchika' => 'Параметры диска и совместимость',
        '/product/16-00r20-425-95r20-ttf-on-off-m-s-r-3-073a-advance-shina-gruzovaya-veduschaya' => 'Характеристики и применение шины',
        '/product/15lx24-et82-pcd5x335-hub290-lantian-disk-dlya-ekskavatora-pogruzchika' => 'Параметры диска и совместимость',
        '/product/245-70r17-5-f-nf-202-kama-shina-gruzovaya-rulevaya' => 'Характеристики и применение шины',
        '/product/dw15x24-et82-pcd5x335-hub290-5-ekka-disk-dlya-ekskavatora-pogruzchika' => 'Параметры диска и совместимость',
        '/technics/type/grejder' => 'О шинах для грейдеров',
        '/technics/type/karernaya-tehnika' => 'О шинах для карьерной техники',
        '/technics/type/sadovye-traktory' => 'О шинах для садовых тракторов',
        '/product/filtr-ohlazhdayuschey-zhidkosti-ek-6021-ek-1121-ek-1021' => 'Технические характеристики фильтра',
        '/product/315-80r22-5-d-755-annaite-shina-gruzovaya-veduschaya' => 'Характеристики и применение шины',
        '/product/315-80r22-5-f-766-annaite-shina-gruzovaya-rulevaya' => 'Характеристики и применение шины',
        '/technics/type/kvadrocikl' => 'О шинах для квадроциклов',
        '/technics/type/portovaya-tehnika' => 'О шинах для портовой техники',
        '/technics/type/kranovaya-tehnika' => 'О шинах для крановой техники',
        '/product/dw15x24-et36-pcd5x335-hub290-5-ekka-disk-dlya-ekskavatora-pogruzchika' => 'Параметры диска и совместимость',
        '/technics/type/gruntovyj-katok' => 'О шинах для грунтовых катков',
        '/product/filtr-ohlazhdayuschey-zhidkosti-ek-6026-ek-1026' => 'Технические характеристики фильтра',
        '/product/385-65r22-5-t-396-annaite-shina-gruzovaya-pricep' => 'Характеристики и применение шины',
        '/product/filtr-ohlazhdayuschey-zhidkosti-ek-6022-ek-1022-ek-1122' => 'Технические характеристики фильтра',
        '/technics/type/frontalnyj-pogruzchik' => 'О шинах для фронтальных погрузчиков',
        '/product/315-80r22-5-on-off-700-annaite-shina-gruzovaya-veduschaya' => 'Характеристики и применение шины',
        '/technics/type/selskohozyajstvennaya-tehnika' => 'О шинах для сельхозтехники',
        '/product/filtr-ohlazhdayuschey-zhidkosti-ek-6025-ek-1125-ek-1025' => 'Технические характеристики фильтра',
        '/category/uslugi' => 'Монтаж, ошиповка и нарезка протектора',
    ];

    public static function injectIntoContent(string $content, ?string $requestUri = null): string
    {
        if ($content === '' || preg_match('~<h2\b~i', $content)) {
            return $content;
        }

        $path = parse_url($requestUri ?? ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        $path = strtolower($path === '/' ? '/' : $path);

        $h2 = self::H2_BY_PATH[$path] ?? null;
        if ($h2 === null || !preg_match('~</h1>~i', $content)) {
            return $content;
        }

        $html = '<h2 class="seo-h2">' . htmlspecialchars($h2, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h2>';

        return preg_replace('~</h1>~i', '</h1>' . PHP_EOL . $html, $content, 1) ?? $content;
    }
}
