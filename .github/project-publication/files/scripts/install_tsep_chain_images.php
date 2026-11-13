<?php
// CLI only. php scripts/install_tsep_chain_images.php [--apply]. Changes product.img only.
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/config/init.php'; require CONF.'/db_bootstrap.php';
$map=json_decode(file_get_contents(ROOT.'/app/data/tsep-chain-images.json'),true,512,JSON_THROW_ON_ERROR);
if(count($map['assets'])!==9 || count($map['products'])!==55) throw new RuntimeException('Incomplete image mapping');
$before=[]; $updates=[];
foreach($map['products'] as $article=>$expected) {
    $p=R::getRow('SELECT id,article,model,alias,img,price,price_rrs,opt_price,spec_price,stock_status_id,quantity,rest,reserve,inventory_quantity,inventory_rest,stock_adjustment FROM product WHERE article=?',[$article]);
    if(!$p || $p['model']!==$expected['series'] || $p['alias']!==$expected['alias']) throw new RuntimeException('Product mapping mismatch: '.$article);
    foreach(['original','baseimg','mini'] as $folder) {
        $path=WWW.'/images/product/'.$folder.'/'.$expected['filename'];
        if(!is_file($path) || !getimagesize($path)) throw new RuntimeException('Missing image: '.$path);
    }
    $before[$p['id']]=$p;
    if($p['img']!==$expected['filename']) $updates[$p['id']]=$expected['filename'];
}
$report=['mode'=>in_array('--apply',$argv,true)?'apply':'preview','images'=>9,'products'=>count($before),'updates'=>count($updates)];
if(!in_array('--apply',$argv,true)) { echo json_encode($report,JSON_PRETTY_PRINT).PHP_EOL; exit; }
$backupDir=ROOT.'/storage/tsep-chain-images'; if(!is_dir($backupDir)) mkdir($backupDir,0775,true);
$backup=$backupDir.'/before-'.date('Ymd-His').'.json';
if(file_put_contents($backup,json_encode($before,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false) throw new RuntimeException('Backup failed');
R::begin();
try {
    foreach($updates as $id=>$filename) {
        $old=R::getRow('SELECT * FROM product WHERE id=? FOR UPDATE',[$id]);
        if($old['img']!==$before[$id]['img']) throw new RuntimeException('Image changed concurrently: '.$id);
        R::exec('UPDATE product SET img=? WHERE id=?',[$filename,$id]);
    }
    foreach($before as $id=>$expectedState) {
        $actual=R::getRow('SELECT '.implode(',',array_keys($expectedState)).' FROM product WHERE id=?',[$id]);
        $expectedImage=$map['products'][$expectedState['article']]['filename'];
        if($actual['img']!==$expectedImage) throw new RuntimeException('Image assignment failed: '.$id);
        unset($actual['img'],$expectedState['img']);
        if($actual!==$expectedState) throw new RuntimeException('Non-image product fields changed: '.$id);
    }
    R::commit();
} catch(Throwable $e) { R::rollback(); fwrite(STDERR,$e->getMessage().PHP_EOL); exit(1); }
$report['backup']=$backup;
$report['non_image_fields_unchanged']=true;
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
