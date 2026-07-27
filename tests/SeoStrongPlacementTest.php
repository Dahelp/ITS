<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$layout = (string)file_get_contents($root . '/app/views/itscenter/layouts/watches.php');

if (strpos($layout, 'SeoStrong::apply($content)') !== false) {
    fwrite(STDERR, "FAILED: SeoStrong must not process the complete rendered page\n");
    exit(1);
}

$safeViews = [
    'app/views/itscenter/Category/view.php',
    'app/views/itscenter/Catalog/index.php',
    'app/views/itscenter/Technics/type.php',
    'app/views/itscenter/Podbor/index.php',
    'app/views/itscenter/Services/index.php',
    'app/views/itscenter/News/index.php',
    'app/views/itscenter/Articles/index.php',
    'app/views/itscenter/Pages/view.php',
    'app/views/itscenter/Promo/view.php',
    'app/views/itscenter/Services/view.php',
];

foreach ($safeViews as $relativePath) {
    $source = (string)file_get_contents($root . '/' . $relativePath);
    if (strpos($source, 'SeoStrong::apply') === false) {
        fwrite(STDERR, "FAILED: SEO content is not scoped in {$relativePath}\n");
        exit(1);
    }
}

$categoryView = (string)file_get_contents($root . '/app/views/itscenter/Category/view.php');
if (!preg_match('~return\s+\$isFilterLanding\s*\?\s*\\\\app\\\\helpers\\\\SeoStrong::apply\(\$rendered\)\s*:\s*\$rendered;~', $categoryView)) {
    fwrite(STDERR, "FAILED: regular category DB content must not be changed at render time\n");
    exit(1);
}

echo "SeoStrong placement tests passed\n";
