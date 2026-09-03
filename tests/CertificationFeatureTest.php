<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$read = static fn(string $file): string => (string)file_get_contents($root . '/' . $file);
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAILED: {$message}\n");
        exit(1);
    }
};

$service = $read('app/services/ProductCertificationService.php');
$productPos = strpos($service, "['product', (int)\$product->id]");
$categoryBrandPos = strpos($service, "['category_brand', (int)\$product->category_id");
$brandPos = strpos($service, "['brand', (int)\$product->brand_id]");
$categoryPos = strpos($service, "['category', (int)\$product->category_id]");
$assert($productPos !== false && $categoryBrandPos !== false && $brandPos !== false && $categoryPos !== false, 'all assignment scopes must exist');
$assert($productPos < $categoryBrandPos && $categoryBrandPos < $brandPos && $brandPos < $categoryPos, 'assignment priority must be product, category+brand, brand, category');
$assert(strpos($service, "c.status = 'active'") !== false, 'only active documents may be shown');
$assert(strpos($service, 'c.date_end >= CURDATE()') !== false, 'expired documents must not be shown');

$view = $read('app/views/itscenter/Product/view.php');
$assert(strpos($view, 'Обязательное подтверждение соответствия для данного товара не требуется') !== false, 'not-required disclosure is missing');
$assert(strpos($view, "\$doc['registry_url']") !== false, 'official registry link is missing');
$assert(strpos($view, 'rel="noopener noreferrer nofollow"') !== false, 'external registry link must be isolated');

$schema = $read('scripts/install_certificates.php');
$assert(strpos($schema, 'certification_required TINYINT(1) NULL') !== false, 'three-state product flag must remain nullable');
$assert(strpos($schema, 'certificate_assignments') !== false, 'assignment table is missing');

$seed = $read('scripts/seed_ekka_certificate.php');
foreach (['маслян', 'топлив', 'воздуш'] as $word) {
    $assert(strpos($seed, $word) !== false, "EKKA seed is not restricted by {$word} filter scope");
}
$assert(strpos($seed, "preg_match_all('/EK-?") !== false, 'EKKA seed must use the official article list');

echo "Certification feature tests passed\n";
