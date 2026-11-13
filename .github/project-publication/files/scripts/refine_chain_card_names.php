<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/config/init.php';require CONF.'/db_bootstrap.php';
$plan=json_decode(file_get_contents(ROOT.'/app/data/chain-seo-content.json'),true);
$before=[];$rules=[];
foreach(['special'=>44,'forklift'=>45] as $group=>$category) {
 $r=R::getAll("SELECT * FROM plagins_inseo WHERE category_id=? AND tip='product' AND hide='show'",[$category]);
 if(count($r)!==1)throw new RuntimeException('Expected one InSEO rule');$rules[$group]=$r[0];
}
foreach($plan['products'] as $page) {
 $p=R::getRow('SELECT * FROM product WHERE article=?',[$page['article']]);if(!$p)throw new RuntimeException('Missing product');$before[$p['id']]=$p;
}
$dir=ROOT.'/storage/chain-card-names';if(!is_dir($dir))mkdir($dir,0775,true);
$backup=$dir.'/before-'.date('Ymd-His').'.json';
if(file_put_contents($backup,json_encode(['products'=>$before,'inseo'=>$rules],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false)throw new RuntimeException('Backup failed');
R::begin();
try {
 foreach($rules as $group=>$rule) R::exec('UPDATE plagins_inseo SET name=? WHERE id=?',[$plan['templates'][$group]['name'],$rule['id']]);
 foreach($plan['products'] as $page) {
  $p=R::getRow('SELECT id FROM product WHERE article=?',[$page['article']]);$id=(int)$p['id'];
  R::exec('UPDATE product SET seo_h1=? WHERE id=?',[$plan['templates'][$page['group']]['h1'],$id]);
  if(\ishop\App::seoreplace($plan['templates'][$page['group']]['name'],$id)!==$page['rendered']['name'])throw new RuntimeException('Card name expansion failed');
  $after=R::getRow('SELECT * FROM product WHERE id=?',[$id]);$expected=$before[$id];$expected['seo_h1']=$plan['templates'][$page['group']]['h1'];
  if($after!==$expected)throw new RuntimeException('Unexpected product change');
 }
 R::commit();
}catch(Throwable $e){R::rollback();foreach($rules as $rule)R::exec('UPDATE plagins_inseo SET name=? WHERE id=?',[$rule['name'],$rule['id']]);fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
echo json_encode(['short_inseo_names'=>2,'full_seo_h1'=>count($before),'backup'=>$backup],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
