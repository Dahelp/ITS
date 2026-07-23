<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use app\helpers\SeoStrong;

$cases = [
    [
        '/category/shiny-dlya-minipogruzchikov',
        '<h1>Шины для минипогрузчиков</h1><p>Купить резину на мини погрузчик с доставкой.</p>',
        '<p>Купить <strong>резину на мини погрузчик</strong> с доставкой.</p>',
    ],
    [
        '/category/kamery/10.00-20',
        '<h1>10.00-20</h1><p>Камеры размера 10.00-20 есть в наличии.</p>',
        '<p>Камеры размера <strong>10.00-20</strong> есть в наличии.</p>',
    ],
    [
        '/category/gruzovye-shiny/275-70r22-5',
        '<h1>Грузовые шины</h1><p>Размер 275/70R22.5 доступен для заказа.</p>',
        '<p>Размер <strong>275/70R22.5</strong> доступен для заказа.</p>',
    ],
    [
        '/services/oshipovka-i-narezka-protektora-shin-dlya-spectehniki',
        '<p>Ошиповка шин и нарезка протектора для спецтехники.</p>',
        '<p><strong>Ошиповка шин</strong> и <strong>нарезка протектора</strong> для спецтехники.</p>',
    ],
    [
        '/sitemap',
        '<p>Шины для спецтехники</p>',
        '<p>Шины для спецтехники</p>',
    ],
];

foreach ($cases as [$path, $input, $expectedFragment]) {
    $actual = SeoStrong::apply($input, $path);
    if (strpos($actual, $expectedFragment) === false) {
        fwrite(STDERR, "FAILED {$path}\nExpected: {$expectedFragment}\nActual: {$actual}\n");
        exit(1);
    }
}

if (count(SeoStrong::configuredPaths()) !== 80) {
    fwrite(STDERR, 'FAILED configured path count: ' . count(SeoStrong::configuredPaths()) . "\n");
    exit(1);
}

echo "SeoStrong tests passed\n";
