<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
$categories = [
    ['id' => 1, 'parent_id' => 34, 'name' => 'Industrial', 'alias' => 'industrial'],
    ['id' => 9, 'parent_id' => 1, 'name' => 'Loaders', 'alias' => 'loaders'],
    ['id' => 99, 'parent_id' => 9, 'name' => 'Nested', 'alias' => 'nested'],
    ['id' => 2, 'parent_id' => 34, 'name' => 'ATV', 'alias' => 'atv'],
    ['id' => 36, 'parent_id' => 34, 'name' => 'Truck', 'alias' => 'truck'],
];
$groups = app\services\HomeCatalog::build($categories, [
    ['category_id' => 1, 'total' => 3], ['category_id' => 9, 'total' => 5],
    ['category_id' => 99, 'total' => 7], ['category_id' => 2, 'total' => 11],
    ['category_id' => 36, 'total' => 13], ['category_id' => 123, 'total' => 100],
]);
if ($groups[1]['total'] !== 15 || $groups[1]['children'][0]['total'] !== 12 || $groups[2]['total'] !== 11 || $groups[36]['total'] !== 13) {
    throw new RuntimeException('Counts must include all visible descendants, without unrelated categories');
}
if (isset($groups[3]) || array_keys($groups) !== [1, 2, 36]) throw new RuntimeException('Missing groups must not generate links; priorities must be stable');
// Malformed category data must never cause an infinite loop.
$categories[0]['parent_id'] = 99;
$groups = app\services\HomeCatalog::build($categories, [['category_id' => 1, 'total' => 3]]);
if ($groups[1]['total'] !== 3) throw new RuntimeException('Cycle counted twice');
echo "HomeCatalogTest OK\n";
