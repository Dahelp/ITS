<?php

require dirname(__DIR__) . '/config/init.php';
require CONF . '/db_bootstrap.php';

use app\services\CrossReferenceService;
use PhpOffice\PhpSpreadsheet\IOFactory;

$options = getopt('', ['file:', 'apply', 'brand::']);
$file = isset($options['file']) ? (string)$options['file'] : '';
$apply = array_key_exists('apply', $options);
$brandName = trim((string)($options['brand'] ?? 'EKKA'));

if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "Usage: php bin/import_disk_crosses.php --file=path.xlsx [--brand=EKKA] [--apply]\n");
    exit(2);
}

if (!CrossReferenceService::hasUnifiedSchema()) {
    fwrite(STDERR, "Unified cross tables are not installed. Run scripts/install_unified_crosses.php first.\n");
    exit(2);
}

$brandId = 0;
if ($brandName !== '') {
    $brandId = (int)\R::getCell('SELECT id FROM brand WHERE LOWER(name) = LOWER(?) LIMIT 1', [$brandName]);
}

function normalizeSpec(string $value): string
{
    $value = mb_strtoupper(trim($value), 'UTF-8');
    $value = str_replace(["\r", "\n", 'Х', '×'], ['', '', 'X', 'X'], $value);
    return preg_replace('~[^A-ZА-ЯЁ0-9.\-]+~u', '', $value) ?: '';
}

function categoryIsWheel(int $categoryId): bool
{
    $seen = [];
    while ($categoryId > 0 && !isset($seen[$categoryId])) {
        $seen[$categoryId] = true;
        $category = \R::getRow('SELECT id, parent_id FROM category WHERE id = ? LIMIT 1', [$categoryId]);
        if (!$category) return false;
        if ((int)$category['id'] === 3 || (int)$category['parent_id'] === 3) return true;
        $categoryId = (int)$category['parent_id'];
    }
    return false;
}

function splitCrosses(string $value): array
{
    $items = preg_split('~\s*[,;]\s*~u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $result = [];
    foreach ($items as $item) {
        $item = trim(preg_replace('~\s+~u', ' ', $item));
        $normalized = CrossReferenceService::normalizeCode($item);
        if ($normalized === '' || isset($result[$normalized])) continue;
        $result[$normalized] = $item;
    }
    return $result;
}

$sheet = IOFactory::load($file)->getActiveSheet();
$highestRow = $sheet->getHighestDataRow();
$products = \R::getAll(
    'SELECT p.id,p.article,p.name,p.category_id,p.brand_id,b.name AS brand_name
     FROM product p LEFT JOIN brand b ON b.id=p.brand_id WHERE p.hide != ?',
    ['hide']
);
$wheelProducts = array_values(array_filter($products, static function ($product) use ($brandId) {
    return categoryIsWheel((int)$product['category_id'])
        && (!$brandId || (int)$product['brand_id'] === $brandId);
}));

$rows = [];
$ready = 0;
$needsReview = 0;
for ($row = 2; $row <= $highestRow; $row++) {
    $size = trim((string)$sheet->getCell('A' . $row)->getValue());
    $sourceKey = trim((string)$sheet->getCell('B' . $row)->getValue());
    $crossRaw = trim((string)$sheet->getCell('C' . $row)->getValue());
    $fitment = trim((string)$sheet->getCell('D' . $row)->getValue());
    $description = trim((string)$sheet->getCell('E' . $row)->getValue());
    if ($size === '' && $sourceKey === '' && $crossRaw === '') continue;

    $spec = normalizeSpec($size);
    $candidates = [];
    foreach ($wheelProducts as $product) {
        $productSpec = normalizeSpec((string)$product['name']);
        if ($spec !== '' && strpos($productSpec, $spec) === 0) {
            $candidates[] = $product;
        }
    }

    $mappedId = (int)\R::getCell(
        'SELECT product_id FROM product_cross_context WHERE source = ? AND source_key = ? LIMIT 1',
        ['wheel_cross_excel', $sourceKey]
    );
    if ($mappedId) {
        $mapped = array_values(array_filter($wheelProducts, static function ($product) use ($mappedId) {
            return (int)$product['id'] === $mappedId;
        }));
        if ($mapped) $candidates = $mapped;
    }

    $status = count($candidates) === 1 ? 'ready' : (count($candidates) === 0 ? 'not_found' : 'ambiguous');
    if ($status === 'ready') $ready++; else $needsReview++;
    $rows[] = [
        'row' => $row,
        'size' => $size,
        'source_key' => $sourceKey,
        'crosses' => splitCrosses($crossRaw),
        'fitment' => $fitment,
        'description' => $description,
        'status' => $status,
        'candidates' => $candidates,
    ];
}

foreach ($rows as $item) {
    $candidateText = implode(', ', array_map(static function ($product) {
        return $product['article'] . ' / ' . $product['name'];
    }, $item['candidates']));
    echo sprintf(
        "Row %d | %s | key=%s | crosses=%d | %s%s\n",
        $item['row'],
        $item['status'],
        $item['source_key'],
        count($item['crosses']),
        $candidateText,
        $item['status'] === 'ready' ? '' : ' [REVIEW REQUIRED]'
    );
}
echo "Summary: ready={$ready}, review={$needsReview}, mode=" . ($apply ? 'apply' : 'preview') . "\n";

if (!$apply) exit(0);
if ($needsReview > 0) {
    fwrite(STDERR, "Import stopped: every row must have exactly one confirmed product.\n");
    exit(3);
}

$legacyVendorId = (int)\R::getCell("SELECT id FROM plagins_cross_vendor WHERE name = 'OEM' LIMIT 1");
if (!$legacyVendorId) {
    \R::exec("INSERT INTO plagins_cross_vendor (name) VALUES ('OEM')");
    $legacyVendorId = (int)\R::getCell('SELECT LAST_INSERT_ID()');
}
$nextLegacyId = (int)\R::getCell('SELECT COALESCE(MAX(cross_id),0) FROM plagins_cross');
$inserted = 0;

\R::begin();
try {
    foreach ($rows as $item) {
        $product = $item['candidates'][0];
        $productId = (int)$product['id'];
        \R::exec(
            'INSERT INTO product_cross_context (product_id,source_key,fitment_text,description,source)
             VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE source_key=VALUES(source_key),
             fitment_text=VALUES(fitment_text),description=VALUES(description),updated_at=CURRENT_TIMESTAMP',
            [$productId, $item['source_key'], $item['fitment'], $item['description'], 'wheel_cross_excel']
        );

        foreach ($item['crosses'] as $normalized => $display) {
            $crossId = (int)\R::getCell(
                'SELECT id FROM cross_reference WHERE normalized_code = ? AND vendor_id IS NULL LIMIT 1',
                [$normalized]
            );
            if (!$crossId) {
                \R::exec(
                    'INSERT INTO cross_reference (display_code,normalized_code,slug,vendor_id) VALUES (?,?,?,NULL)',
                    [$display, $normalized, CrossReferenceService::slug($display)]
                );
                $crossId = (int)\R::getCell('SELECT LAST_INSERT_ID()');
            }
            \R::exec(
                "INSERT IGNORE INTO product_cross
                 (cross_id,product_id,relation_type,part_role,product_type,source,source_key)
                 VALUES (?,?,'oem','unspecified','wheel','wheel_cross_excel',?)",
                [$crossId, $productId, $item['source_key']]
            );

            // Временный слой совместимости: существующие поиск и админка продолжают видеть дисковые номера.
            $legacyExists = (int)\R::getCell(
                'SELECT id FROM plagins_cross WHERE product_id=? AND UPPER(cross_abbreviated_name)=? LIMIT 1',
                [$productId, $normalized]
            );
            if (!$legacyExists) {
                $nextLegacyId++;
                \R::exec(
                    'INSERT INTO plagins_cross
                     (cross_id,product_id,vendor_id,cross_name,cross_abbreviated_name,tip_cross,equipment_vendor)
                     VALUES (?,?,?,?,?,3,1)',
                    [$nextLegacyId, $productId, $legacyVendorId, $display, $normalized]
                );
                $inserted++;
            }
        }
    }
    \R::commit();
} catch (\Throwable $e) {
    \R::rollback();
    throw $e;
}

echo "Import completed. Legacy-compatible disk crosses inserted: {$inserted}\n";
