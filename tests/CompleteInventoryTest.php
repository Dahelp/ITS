<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CompleteInventory;
function stockCheck($expected, $rows, $label) {
    if (CompleteInventory::calculate($rows) !== $expected) throw new RuntimeException($label);
}
$a = ['product_id'=>1, 'qty'=>2, 'quantity'=>22, 'reserve'=>2, 'hide'=>'show'];
$b = ['product_id'=>2, 'qty'=>2, 'quantity'=>15, 'reserve'=>2, 'hide'=>'show'];
stockCheck(['stock'=>7,'reserve'=>1,'available'=>6], [$a,$b], 'Scarcest component and reserves');
stockCheck(['stock'=>0,'reserve'=>0,'available'=>0], [], 'Empty set');
stockCheck(['stock'=>0,'reserve'=>0,'available'=>0], [$a,array_replace($b,['product_id'=>null])], 'Missing component');
stockCheck(['stock'=>0,'reserve'=>0,'available'=>0], [$a,array_replace($b,['hide'=>'hide'])], 'Hidden component');
stockCheck(['stock'=>5,'reserve'=>0,'available'=>5], [$a,$a], 'Repeated component needs combined quantity');
stockCheck(['stock'=>3,'reserve'=>0,'available'=>3], [array_replace($a,['quantity'=>0,'reserve'=>0,'modification_quantity'=>6])], 'Modifications');
stockCheck(['stock'=>11,'reserve'=>11,'available'=>0], [array_replace($a,['reserve'=>99])], 'Reserve exceeds stock');
echo "Complete inventory: 7 cases OK\n";
