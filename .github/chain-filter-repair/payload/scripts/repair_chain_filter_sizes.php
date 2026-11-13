<?php
/** Restore only the documented alternative size links. php ... [--apply] */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/config/init.php';
require CONF.'/db_bootstrap.php';
$source=json_decode(file_get_contents(ROOT.'/app/data/tsep-chains.json'),true,512,JSON_THROW_ON_ERROR);
$products=\app\services\TsepChainCatalog::products($source);
$expected=array_values(array_filter($products, static function($p) {
    return in_array('8.15-15',$p['filters']['size']??[],true) && in_array('28x9-15',$p['filters']['size']??[],true);
}));
if(count($expected)!==4) throw new RuntimeException('Unexpected alternative-size scope');
$sizeIds=[];
foreach(['8.15-15','28x9-15'] as $size) {
    $rows=R::getAll('SELECT id FROM attribute_value WHERE attr_group_id=2 AND value=? AND hide=?',[$size,'show']);
    if(count($rows)!==1) throw new RuntimeException('Size value is not unique: '.$size);
    $sizeIds[$size]=(int)$rows[0]['id'];
}
$before=[]; $missing=[];
foreach($expected as $p) {
    $rows=R::getAll('SELECT id,article,alias,category_id FROM product WHERE article=?',[$p['article']]);
    if(count($rows)!==1 || (int)$rows[0]['category_id']!==45 || $rows[0]['alias']!==$p['alias']) throw new RuntimeException('Product identity changed');
    $id=(int)$rows[0]['id'];
    $before[$id]=R::getAll('SELECT * FROM attribute_product WHERE product_id=?',[$id]);
    $linked=array_map('intval',array_column($before[$id],'attr_id'));
    foreach($sizeIds as $size=>$attrId) if(!in_array($attrId,$linked,true)) $missing[]=['product_id'=>$id,'attr_id'=>$attrId,'size'=>$size];
}
// The old routes require explicit redirects; never infer applicability from size alone.
$routeChecks=[];
foreach(['10-16.5','12-16.5','4.5-12'] as $size) {
    $value=R::getRow('SELECT id,alias FROM attribute_value WHERE attr_group_id=2 AND value=? AND hide=?',[$size,'show']);
    if(!$value) throw new RuntimeException('Missing route size');
    $old=R::getRow('SELECT * FROM attribute_value_category_canonical WHERE attr_value_id=? AND category_id=45 AND is_active=1',[$value['id']]);
    $new=R::getRow('SELECT * FROM attribute_value_category_canonical WHERE attr_value_id=? AND category_id=44 AND is_active=1',[$value['id']]);
    if(!$old || $old['mode']!=='redirect' || (int)$old['redirect_category_id']!==44 || !$new || $new['mode']!=='landing') throw new RuntimeException('Missing reviewed redirect or destination: '.$size);
    $routeChecks[]=['old'=>'/category/cepi-dlya-vilochnyh-pogruzchikov/'.$value['alias'],'new'=>'/category/cepi-dlya-spectehniki/'.$value['alias']];
}
$report=['mode'=>in_array('--apply',$argv,true)?'apply':'preview','products'=>array_keys($before),'missing_links'=>$missing,'routes'=>$routeChecks];
if(!in_array('--apply',$argv,true)) { echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL; exit; }
$dir=ROOT.'/storage/chain-filter-repair'; if(!is_dir($dir))mkdir($dir,0775,true);
$backup=$dir.'/before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
if(file_put_contents($backup,json_encode(['links'=>$before,'planned'=>$missing],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false)throw new RuntimeException('Backup failed');
$inserted=[];
try {
    foreach($missing as $link) {
        if(R::getCell('SELECT COUNT(*) FROM attribute_product WHERE product_id=? AND attr_id=?',[$link['product_id'],$link['attr_id']]))continue;
        R::exec('INSERT INTO attribute_product (product_id,attr_id) VALUES (?,?)',[$link['product_id'],$link['attr_id']]);
        $inserted[]=$link;
    }
    foreach(array_keys($before) as $id) foreach($sizeIds as $attrId) {
        if((int)R::getCell('SELECT COUNT(*) FROM attribute_product WHERE product_id=? AND attr_id=?',[$id,$attrId])!==1)throw new RuntimeException('Size link verification failed');
    }
} catch(Throwable $e) {
    foreach(array_reverse($inserted) as $link)R::exec('DELETE FROM attribute_product WHERE product_id=? AND attr_id=?',[$link['product_id'],$link['attr_id']]);
    throw $e;
}
$report['backup']=$backup; $report['inserted']=$inserted;
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
