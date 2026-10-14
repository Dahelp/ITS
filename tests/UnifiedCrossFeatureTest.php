<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/config/init.php';
require CONF . '/db_bootstrap.php';

use app\services\CrossReferenceService;

if (!CrossReferenceService::hasUnifiedSchema()) {
    fwrite(STDERR, "FAILED: unified cross schema is not installed\n");
    exit(1);
}

if (CrossReferenceService::normalizeCode('3EB-27-11220') !== '3EB2711220') {
    fwrite(STDERR, "FAILED: cross normalization is unstable\n");
    exit(1);
}

$wheelRows = \R::getAll("SELECT product_id FROM product_cross WHERE product_type='wheel' AND source='wheel_cross_excel'");
if (count($wheelRows) !== 121) {
    fwrite(STDERR, 'FAILED: expected 121 imported wheel cross relations, got ' . count($wheelRows) . "\n");
    exit(1);
}

$wheelProducts = (int)\R::getCell("SELECT COUNT(DISTINCT product_id) FROM product_cross WHERE product_type='wheel' AND source='wheel_cross_excel'");
if ($wheelProducts !== 8) {
    fwrite(STDERR, "FAILED: expected cross numbers for 8 wheel products\n");
    exit(1);
}

$contexts = (int)\R::count('product_cross_context', 'source = ?', ['wheel_cross_excel']);
if ($contexts !== 9) {
    fwrite(STDERR, "FAILED: expected context data for 9 wheel products\n");
    exit(1);
}

$sample = CrossReferenceService::findMatches('441081048071', 'diski');
if (!$sample || ($sample[0]['product_type'] ?? '') !== 'wheel') {
    fwrite(STDERR, "FAILED: disk cross page lookup did not return a wheel\n");
    exit(1);
}

if (($sample[0]['cross_manufacturer'] ?? '') !== 'TOYOTA') {
    fwrite(STDERR, "FAILED: wheel OEM manufacturer metadata is missing\n");
    exit(1);
}

$sampleProduct = \R::load('product', (int)$sample[0]['product_id']);
$sampleContext = CrossReferenceService::context($sampleProduct, $sample[0], 'wheel');
if (strpos($sampleContext['heading'], 'Диск — аналог OEM 441081048071 TOYOTA') !== 0
    || strpos($sampleContext['title'], (string)$sampleProduct->name) !== false) {
    fwrite(STDERR, "FAILED: wheel cross SEO does not have an independent OEM intent\n");
    exit(1);
}

if ((int)\R::getCell("SELECT COUNT(*) FROM product_cross_context WHERE description LIKE '%в к-те%'") !== 0) {
    fwrite(STDERR, "FAILED: abbreviated wheel description remains in the database\n");
    exit(1);
}

$routes = (string)file_get_contents($root . '/config/routes.php');
if (strpos($routes, "(?P<kind>diski)") === false) {
    fwrite(STDERR, "FAILED: disk cross route is missing\n");
    exit(1);
}

$controller = (string)file_get_contents($root . '/app/controllers/CrossController.php');
if (strpos($controller, "CrossReferenceService::findMatches") === false) {
    fwrite(STDERR, "FAILED: CrossController does not use the unified resolver\n");
    exit(1);
}

echo "Unified cross feature tests passed\n";
