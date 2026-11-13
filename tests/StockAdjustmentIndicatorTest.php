<?php
require dirname(__DIR__) . '/vendor/autoload.php';
use app\services\StockAdjustment;
if (StockAdjustment::adminIndicators(['stock_adjustment'=>0]) !== '') throw new RuntimeException('Zero must have no indicator');
$html = StockAdjustment::adminIndicators(['inventory_quantity'=>2,'quantity'=>1,'stock_adjustment'=>-1], [
    ['article'=>'<variant>','inventory_quantity'=>0,'quantity'=>4,'stock_adjustment'=>4],
    ['article'=>'zero','stock_adjustment'=>0],
]);
foreach (['Корректировка: −1', 'Модификация &lt;variant&gt;: +4'] as $text) {
    if (!str_contains($html, $text)) throw new RuntimeException('Missing indicator: ' . $text);
}
if (str_contains($html, '<variant>') || str_contains($html, 'zero')) throw new RuntimeException('Unsafe or zero indicator');
echo "Stock adjustment indicators OK: signed values, variants and escaping\n";
