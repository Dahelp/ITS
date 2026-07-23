<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/init.php';
require dirname(__DIR__) . '/config/db_bootstrap.php';

$alias = 'shiny-dlya-minipogruzchikov';
$category = \RedBeanPHP\R::findOne('category', 'alias = ?', [$alias]);

if (!$category) {
    fwrite(STDERR, "Category not found: {$alias}\n");
    exit(1);
}

$content = (string)$category->content;
$replacements = [
    'резину на мини погрузчик' => '<strong>резину на мини погрузчик</strong>',
    'шин для мини погрузчиков' => '<strong>шин для мини погрузчиков</strong>',
];

if (preg_match('~<strong>\s*(?:резину на мини погрузчик|шин для мини погрузчиков)\s*</strong>~iu', $content)) {
    echo "SEO strong already applied: {$alias}\n";
    exit(0);
}

$changed = false;

foreach ($replacements as $phrase => $replacement) {
    if (mb_stripos($content, $replacement, 0, 'UTF-8') !== false) {
        continue;
    }

    $pattern = '~' . preg_quote($phrase, '~') . '~iu';
    $updated = preg_replace($pattern, $replacement, $content, 1, $count);

    if ($count > 0 && is_string($updated)) {
        $content = $updated;
        $changed = true;
        break;
    }
}

if (!$changed) {
    fwrite(STDERR, "Target phrase not found in category content: {$alias}\n");
    exit(1);
}

$category->content = $content;
\RedBeanPHP\R::store($category);

echo "SEO strong applied: {$alias}\n";
