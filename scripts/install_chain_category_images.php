<?php
// CLI only, changes category.img in the three requested chain categories.
if(PHP_SAPI!=='cli') {http_response_code(404);exit;}
require dirname(__DIR__).'/config/init.php'; require CONF.'/db_bootstrap.php';
$map=json_decode(file_get_contents(ROOT.'/app/data/chain-category-images.json'),true,512,JSON_THROW_ON_ERROR);
$expected=['cepi-protivoskolzheniya','cepi-dlya-spectehniki','cepi-dlya-vilochnyh-pogruzchikov'];
if(count($map['categories'])!==3 || array_column($map['categories'],'alias')!==$expected) throw new RuntimeException('Invalid scope');
$before=[]; $updates=[];
foreach($map['categories'] as $asset) {
    $row=R::getRow('SELECT * FROM category WHERE alias=?',[$asset['alias']]);
    if(!$row) throw new RuntimeException('Missing category');
    foreach(['baseimg','mini'] as $folder) {
        $path=WWW.'/images/category/'.$folder.'/'.$asset['filename'];
        if(!is_file($path)||!getimagesize($path)) throw new RuntimeException('Missing image');
    }
    $before[$row['id']]=$row; $updates[$row['id']]=$asset['filename'];
}
$parent=(int)reset($before)['id'];
foreach(array_slice($before,1,null,true) as $row) if((int)$row['parent_id']!==$parent) throw new RuntimeException('Unexpected category hierarchy');
$report=['mode'=>in_array('--apply',$argv,true)?'apply':'preview','categories'=>[]];
foreach($before as $id=>$row) $report['categories'][]=['id'=>$id,'alias'=>$row['alias'],'before'=>$row['img'],'after'=>$updates[$id]];
if(!in_array('--apply',$argv,true)) {echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;exit;}
$dir=ROOT.'/storage/chain-category-images'; if(!is_dir($dir)) mkdir($dir,0775,true);
$backup=$dir.'/before-'.date('Ymd-His').'.json';
if(file_put_contents($backup,json_encode($before,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false) throw new RuntimeException('Backup failed');
R::begin();
try {
 foreach($updates as $id=>$image) {
    R::exec('UPDATE category SET img=? WHERE id=?',[$image,$id]);
    $after=R::getRow('SELECT * FROM category WHERE id=?',[$id]); $expectedRow=$before[$id]; $expectedRow['img']=$image;
    if($after!==$expectedRow) throw new RuntimeException('Other category data changed');
 }
 R::commit();
} catch(Throwable $e) {
 R::rollback();
 foreach($before as $id=>$row) R::exec('UPDATE category SET img=? WHERE id=?',[$row['img'],$id]);
 fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);
}
foreach(['cats','ishop_menu','admin_select','admin_cat'] as $key) \ishop\Cache::instance()->delete($key);
$report['backup']=$backup; $report['other_fields_unchanged']=true;
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
