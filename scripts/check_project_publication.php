<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);$dir=$root.'/outputs/project-publication';$inventory=json_decode(file_get_contents($dir.'/inventory.json'),true);
$report=['lint'=>[],'tests'=>[],'skipped_database_tests'=>[],'literal_credential_candidates'=>[]];
foreach($inventory as $r) {
    if($r['action']!=='write'||strtolower(pathinfo($r['path'],PATHINFO_EXTENSION))!=='php')continue;
    $file=$root.'/'.$r['path'];$out=[];exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1',$out,$code);
    $report['lint'][]=['path'=>$r['path'],'ok'=>$code===0,'output'=>$code?implode("\n",$out):''];
    $tokens=token_get_all(file_get_contents($file));$n=count($tokens);
    for($i=0;$i<$n;$i++) {
        $t=$tokens[$i];if(!is_array($t)||$t[0]!==T_VARIABLE||!preg_match('/(?:password|api_?key|access_?token|client_?secret)$/i',$t[1]))continue;
        $j=$i+1;while($j<$n&&is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)$j++;
        if(($tokens[$j]??null)!=='=')continue;$j++;while($j<$n&&is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)$j++;
        $v=$tokens[$j]??null;if(is_array($v)&&$v[0]===T_CONSTANT_ENCAPSED_STRING&&strlen($v[1])>14)$report['literal_credential_candidates'][]=['path'=>$r['path'],'line'=>$t[2],'variable'=>$t[1]];
    }
}
$dbSkip=['CatalogCardStockTest.php','CatalogDisplayDatabaseTest.php','CatalogFacetBridgeDatabaseTest.php','CatalogFacetsDatabaseTest.php','CatalogNamingDatabaseTest.php','CatalogSelectionDatabaseTest.php','CatalogSizeSearchDatabaseTest.php','CatalogUpdateHealthDatabaseTest.php','CatalogWorkflowDatabaseTest.php','StockAdjustmentDatabaseTest.php','YandexProductsCabinetDatabaseTest.php','UnifiedCrossFeatureTest.php','SpecialTyreFeedStockTest.php'];
foreach(glob($root.'/tests/*Test.php') as $test) {
    if(in_array(basename($test),$dbSkip,true)||str_contains(basename($test),'DatabaseTest')||basename($test)==='MerchantTyrePreviewTest.php'){$report['skipped_database_tests'][]=basename($test);continue;}
    $output=[];exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($test).' 2>&1',$output,$code);
    $report['tests'][]=['path'=>'tests/'.basename($test),'ok'=>$code===0,'output'=>substr(implode("\n",$output),0,5000)];
    if(count($report['tests'])%15===0)echo 'Tests checked: '.count($report['tests']).PHP_EOL;
}
file_put_contents($dir.'/local-checks.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo json_encode(['php_files'=>count($report['lint']),'lint_failed'=>array_values(array_column(array_filter($report['lint'],static fn($r)=>!$r['ok']),'path')),'tests'=>count($report['tests']),'tests_failed'=>array_values(array_column(array_filter($report['tests'],static fn($r)=>!$r['ok']),'path')),'database_tests_skipped'=>count($report['skipped_database_tests']),'literal_credential_candidates'=>$report['literal_credential_candidates']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
