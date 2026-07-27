<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/init.php';
require dirname(__DIR__) . '/config/db_bootstrap.php';

$targets = [
    'shiny-dlya-minipogruzchikov' => [
        'резину на мини погрузчик',
        'шин для мини погрузчиков',
        'Шины для минипогрузчиков',
    ],
    'shiny-dlya-frontalnyh-pogruzchikov' => [
        'резину на фронтальный погрузчик',
        'Шины для фронтального погрузчика',
        'шины для фронтальных погрузчиков',
    ],
    'shiny-dlya-shahtnoy-tehniki' => [
        'Шины для шахтной техники',
    ],
    'filtry' => [
        'фильтры для спецтехники',
        'фильтры',
    ],
    'diski-dlya-vilochnyh-pogruzchikov' => [
        'диски для вилочных погрузчиков',
        'колесные диски',
    ],
    'diski-dlya-minipogruzchikov' => [
        'дисков для мини-погрузчиков',
        'диски для мини-погрузчиков',
    ],
    'shiny-dlya-kolesnyh-ekskavatorov' => [
        'Шины для колесных экскаваторов',
        'шины на колесный экскаватор',
    ],
    'shiny-dlya-gruntovyh-katkov' => [
        'Шины для грунтовых катков',
    ],
    'kamery-i-obodnye-lenty' => [
        'Камеры для шин погрузчиков',
        'камеры и ободные ленты',
    ],
    'kolca' => [
        'Кольца для дисков спецтехники',
        'кольца для спецтехники',
    ],
];

$updated = 0;
$alreadyApplied = 0;
$notFound = [];

foreach ($targets as $alias => $phrases) {
    $category = \RedBeanPHP\R::findOne('category', 'alias = ?', [$alias]);
    if (!$category) {
        $notFound[] = "{$alias}: category not found";
        continue;
    }

    $fields = ['top_content', 'content'];
    $changed = false;
    $hasStrong = false;

    foreach ($fields as $field) {
        $html = (string)$category->{$field};
        if ($html === '') {
            continue;
        }

        foreach ($phrases as $phrase) {
            $wrappedPattern = '~<strong(?:\s[^>]*)?>\s*' . preg_quote($phrase, '~') . '\s*</strong>~iu';
            if (preg_match($wrappedPattern, $html)) {
                $hasStrong = true;
                break 2;
            }
        }
    }

    if ($hasStrong) {
        $alreadyApplied++;
        echo "SEO strong already applied: {$alias}\n";
        continue;
    }

    foreach ($fields as $field) {
        $html = (string)$category->{$field};
        if ($html === '') {
            continue;
        }

        foreach ($phrases as $phrase) {
            $pattern = '~' . preg_quote($phrase, '~') . '~iu';
            $replacement = '<strong>$0</strong>';
            $result = preg_replace($pattern, $replacement, $html, 1, $count);

            if ($count > 0 && is_string($result)) {
                $category->{$field} = $result;
                \RedBeanPHP\R::store($category);
                $updated++;
                $changed = true;
                echo "SEO strong stored in category.{$field}: {$alias} ({$phrase})\n";
                break 2;
            }
        }
    }

    if (!$changed) {
        $notFound[] = "{$alias}: target phrase not found";
    }
}

echo "Summary: updated={$updated}, already={$alreadyApplied}, missed=" . count($notFound) . "\n";
foreach ($notFound as $message) {
    fwrite(STDERR, "WARNING: {$message}\n");
}

exit($notFound === [] ? 0 : 1);
