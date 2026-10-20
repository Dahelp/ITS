<?php
require dirname(__DIR__) . '/app/services/HomePreviewCatalog.php';
use app\services\HomePreviewCatalog;
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$tree = HomePreviewCatalog::tree([
 ['id'=>1,'parent_id'=>0], ['id'=>9,'parent_id'=>1], ['id'=>10,'parent_id'=>9], ['id'=>2,'parent_id'=>0]
], [['category_id'=>1,'total'=>2], ['category_id'=>9,'total'=>3], ['category_id'=>10,'total'=>5], ['category_id'=>2,'total'=>7]]);
check($tree[1]['total'] === 10, 'Parent must include all descendants exactly once');
check($tree[9]['total'] === 8, 'Nested category subtotal');
check($tree[2]['total'] === 7, 'Separate directions must not mix');
$cycle = HomePreviewCatalog::tree([['id'=>1,'parent_id'=>2],['id'=>2,'parent_id'=>1]], [['category_id'=>1,'total'=>3]]);
check($cycle[1]['total'] === 3 && count($cycle[1]['ids']) === 2, 'Cycles must terminate without duplicate totals');
check(HomePreviewCatalog::tree([],[]) === [], 'Empty catalogue');
echo "HomePreviewCatalog: 5 checks passed\n";
