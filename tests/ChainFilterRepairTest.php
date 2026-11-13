<?php
namespace app\services {
    // Keep this focused routing test independent of a database installation.
    class CatalogFacetBridge { public static function relationSql() { return 'attribute_product'; } }
}
namespace {
require dirname(__DIR__).'/vendor/autoload.php';
class R {
    public static $products = [], $redirects = [], $queries = [], $writes = [], $missing = false;
    public static function genSlots($values) { return implode(',', array_fill(0, count($values), '?')); }
    public static function getCol($sql, $params) { return self::$missing ? [] : $params; }
    public static function exec($sql, $params) { self::$writes[] = [$sql, $params]; }
    public static function getAll($sql, $params) {
        self::$queries[] = [$sql, $params];
        if (strpos($sql, 'SELECT av.id, COUNT') !== false) {
            preg_match('/category_id IN \(([^)]+)\)/', $sql, $m);
            return self::$products[$m[1] ?? ''][$params[0]] ?? [];
        }
        if (strpos($sql, "avcc.mode = 'redirect'") !== false) return self::$redirects[$params[1]][$params[0]] ?? [];
        throw new \RuntimeException('Unexpected query');
    }
    public static function load($table, $id) { return (object)['id'=>$id]; }
}
class ChainFilterControllerProbe extends \app\controllers\CategoryController {
    public function find($alias, $ids, $category) { return $this->findApplicableFilterValue($alias, $ids, $category); }
    protected function filterCategoryIds(int $categoryId): string { return (string)$categoryId; }
}
function verifyChainRepair($ok, $message) { if (!$ok) throw new \RuntimeException($message); }
use app\services\ProductFilterSelection;
verifyChainRepair(ProductFilterSelection::ids([2=>['193','194'],16=>'600']) === [193,194,600], 'Both sizes and scalar filters');
verifyChainRepair(ProductFilterSelection::ids([2=>['193','','194','193'],16=>null]) === [193,194], 'Ignore empty values and deduplicate');
verifyChainRepair(ProductFilterSelection::ids([]) === [], 'Empty selection');
foreach (['1,2', '1);DELETE', '-1', ['nested'], 1.5] as $bad) {
    $rejected = false;
    try { ProductFilterSelection::ids([2=>[$bad]]); } catch (\InvalidArgumentException $e) { $rejected = true; }
    verifyChainRepair($rejected, 'Reject invalid IDs before deleting existing links');
}
$controller=(new \ReflectionClass(ChainFilterControllerProbe::class))->newInstanceWithoutConstructor();
$product=(new \ReflectionClass(\app\models\admin\Product::class))->newInstanceWithoutConstructor();
verifyChainRepair($product->traverseArray([2=>['193','194']]), 'Controller accepts nested size values');
$product->editFilter(1216,['attrs'=>[2=>['193','194','193'],16=>'600']]);
verifyChainRepair(count(R::$writes)===2 && R::$writes[1][1]===[193,1216,194,1216,600,1216], 'Save both size relations once using bound parameters');
R::$writes=[]; R::$missing=true;
try { $product->editFilter(1216,['attrs'=>[2=>['999']]]); } catch (\InvalidArgumentException $e) {}
verifyChainRepair(R::$writes===[], 'Unknown filter cannot remove existing selections');
R::$missing=false;
$product->editFilter(1216,['attrs'=>[]]);
verifyChainRepair(count(R::$writes)===1 && strpos(R::$writes[0][0],'DELETE')===0, 'Empty set does not issue invalid INSERT');
R::$products=['45'=>['28x9-15'=>[['id'=>194]]]];
verifyChainRepair($controller->find('28x9-15','45',45)->id===194, 'Normal applicable filter remains valid');
R::$products=['44'=>['10-16.5'=>[['id'=>92]]]];
R::$redirects=[45=>['10-16.5'=>[['id'=>92,'redirect_category_id'=>44]]]];
verifyChainRepair($controller->find('10-16.5','45',45)->id===92, 'Resolve explicit redirect after products moved');
R::$redirects=[];
verifyChainRepair($controller->find('10-16.5','45',45)===null, 'No implicit compatibility or redirect');
R::$redirects=[45=>['10-16.5'=>[['id'=>92,'redirect_category_id'=>44]]]];
R::$products=[];
verifyChainRepair($controller->find('10-16.5','45',45)===null, 'Empty destination is not accepted');
R::$products=['44'=>['10-16.5'=>[['id'=>999]]]];
verifyChainRepair($controller->find('10-16.5','45',45)===null, 'Do not redirect through a different value with the same alias');
echo "Chain filter repair: multi-size input and moved-category routing OK\n";
}
