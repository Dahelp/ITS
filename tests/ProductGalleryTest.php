<?php
/** Rendering regression checks for single photos, legacy galleries and sets. */
if (PHP_SAPI !== 'cli') exit(1);
$root = getenv('LEAD_SITE_ROOT') ?: dirname(__DIR__);
require $root . '/config/init.php';
\ishop\App::$app = \ishop\Registry::instance();
\ishop\App::$app->setProperty('base_url', 'https://its-center.ru');
// Older Img versions issue a non-fatal notice for missing optional AVIF srcsets.
ini_set('display_errors', '0');
function renderGallery(string $root, array $rows, bool $set = false): string {
    $product = (object)['img' => 'gallery-test.webp', 'name' => 'Шина "Тест" <товар>'];
    $gallery = $rows;
    if ($set) { $galleryProduct = $product; $galleryKind = 'complete'; }
    ob_start();
    require $root . '/app/views/itscenter/partials/product-gallery.php';
    return ob_get_clean();
}
function checkGallery(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$single = renderGallery($root, []);
checkGallery(substr_count($single, 'data-pg-photo ') === 1, 'Single photo missing');
checkGallery(!str_contains($single, 'data-pg-thumb='), 'Single photo has redundant thumbnails');
checkGallery(!str_contains($single, '<товар>'), 'Product name is not escaped');
$rows = array_map(static fn($i) => (object)['id' => 0, 'img' => "gallery-$i.webp"], range(1, 4));
$multiple = renderGallery($root, $rows);
checkGallery(substr_count($multiple, 'data-pg-photo ') === 5, 'Legacy gallery rows lost');
checkGallery(substr_count($multiple, 'data-pg-thumb=') === 5, 'Thumbnail count differs from photos');
checkGallery(substr_count($multiple, ' hidden') === 4, 'Initial gallery selection is invalid');
checkGallery(str_contains($multiple, '/images/product/gallery/gallery-4.webp'), 'Last gallery photo missing');
$set = renderGallery($root, $rows, true);
checkGallery(str_contains($set, '/images/complete/baseimg/gallery-test.webp'), 'Set base photo path invalid');
checkGallery(str_contains($set, '/images/complete/gallery/gallery-4.webp'), 'Set gallery path invalid');
foreach (['Product', 'Complete'] as $type) {
    $view = file_get_contents($root . '/app/views/itscenter/' . $type . '/view.php');
    checkGallery(str_contains($view, "/../partials/product-gallery.php"), "$type gallery not connected");
}
echo "PASS: single photo, four zero-ID rows, escaped labels, product and set galleries\n";
