<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$layout = (string)file_get_contents($root . '/app/views/itscenter/layouts/watches.php');
$shell = (string)file_get_contents($root . '/app/views/itscenter/partials/catalog-menu-shell.php');
$controller = (string)file_get_contents($root . '/app/controllers/CatalogController.php');
$javascript = (string)file_get_contents($root . '/public/js/main.js');

if (strpos($layout, "partials/catalog-menu-shell.php") === false) {
    fwrite(STDERR, "FAILED: layout does not render the lightweight catalog shell\n");
    exit(1);
}

if (strpos($layout, "partials/catalog-menu.php") !== false) {
    fwrite(STDERR, "FAILED: full catalog source is still rendered in the initial layout\n");
    exit(1);
}

if (strpos($layout, "main.js?v=") === false || strpos($layout, "filemtime") === false) {
    fwrite(STDERR, "FAILED: main.js is not cache-busted after catalog markup changes\n");
    exit(1);
}

if (strpos($shell, 'id="catalogSource"') !== false) {
    fwrite(STDERR, "FAILED: catalog source remains in the initial DOM shell\n");
    exit(1);
}

if (strpos($controller, "function menuAction") === false
    || strpos($controller, "partials/catalog-menu.php") === false) {
    fwrite(STDERR, "FAILED: AJAX catalog menu endpoint is missing\n");
    exit(1);
}

if (strpos($javascript, "fetch(basePath + '/catalog-menu'") === false) {
    fwrite(STDERR, "FAILED: catalog menu is not loaded on demand\n");
    exit(1);
}

if (strpos($javascript, 'contentWrap.replaceChildren(contentEl)') === false) {
    fwrite(STDERR, "FAILED: catalog panels are not limited to one active DOM subtree\n");
    exit(1);
}

echo "Catalog menu lazy loading tests passed\n";
