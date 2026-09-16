<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$tabViews = [
    'app/views/itscenter/Product/view.php',
    'app/views/itscenter/Cross/view.php',
    'app/views/itscenter/Complete/view.php',
];

foreach ($tabViews as $relativePath) {
    $source = (string)file_get_contents($root . '/' . $relativePath);

    if (preg_match('~data-toggle=["\'](?:pill|tab)["\']~', $source)) {
        fwrite(STDERR, "FAILED: Bootstrap 4 tab markup remains in {$relativePath}\n");
        exit(1);
    }

    if (!preg_match('~<button[^>]+data-bs-toggle=["\']pill["\'][^>]+data-bs-target=["\']#[^"\']+["\']~', $source)) {
        fwrite(STDERR, "FAILED: Bootstrap 5 pill buttons not found in {$relativePath}\n");
        exit(1);
    }
}

$productView = (string)file_get_contents($root . '/app/views/itscenter/Product/view.php');
$reviewH2 = strpos($productView, '<h2 class="h3 mb-3">Отзывы</h2>');
$reviewFormH3 = strpos($productView, '<h3 class="h5">Оставить отзыв</h3>');
$reviewMapH3 = strpos($productView, '<h3 class="h5">Отзывы в Яндекс Картах</h3>');
if ($reviewH2 === false || $reviewFormH3 === false || $reviewMapH3 === false
    || !($reviewH2 < $reviewFormH3 && $reviewFormH3 < $reviewMapH3)) {
    fwrite(STDERR, 'FAILED: Product review headings must follow the H2-H3 hierarchy' . PHP_EOL);
    exit(1);
}

$technicsView = (string)file_get_contents($root . '/app/views/itscenter/Technics/view.php');
if (preg_match('~data-toggle=["\'](?:pill|tab)["\']~', $technicsView)) {
    fwrite(STDERR, "FAILED: Bootstrap 4 tab markup found in Technics/view.php\n");
    exit(1);
}

echo "Frontend product tabs tests passed\n";
