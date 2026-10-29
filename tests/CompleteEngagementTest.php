<?php
require dirname(__DIR__).'/app/services/CompleteEngagement.php';
use app\services\CompleteEngagement;
function engagementCheck($expected,$actual,$label){if($expected!==$actual)throw new RuntimeException($label);}
engagementCheck(3,CompleteEngagement::soldSetsFromRows([10=>2,20=>2],[1=>[10=>4,20=>4],2=>[10=>2,20=>3]]),'full sets per order');
engagementCheck(0,CompleteEngagement::soldSetsFromRows([10=>2,20=>2],[1=>[10=>10,20=>1]]),'incomplete order');
echo "Complete engagement tests passed\n";
