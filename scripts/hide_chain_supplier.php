<?php
if(PHP_SAPI!=='cli') {http_response_code(404); exit;}
require dirname(__DIR__).'/config/init.php'; require CONF.'/db_bootstrap.php';
use app\services\TsepChainCatalog;
$products=TsepChainCatalog::products(json_decode(file_get_contents(ROOT.'/app/data/tsep-chains.json'),true));
$images=json_decode(file_get_contents(ROOT.'/app/data/tsep-chain-images.json'),true);
$pattern='~(?:цеп[ьи]|ценпи)[\s\-–—_]*нн|(?:tsep|tsepi|cepi)[\s\-_]*nn~iu';
$before=[]; $changes=[]; $brandIds=[];
foreach($products as $expected) {
    $p=R::getRow('SELECT * FROM product WHERE article=?',[$expected['article']]);
    if(!$p) throw new RuntimeException('Missing product');
    if(!in_array($p['alias'],[$expected['alias'],str_replace('cepi-','cepi-tsep-nn-',$expected['alias'])],true)) throw new RuntimeException('Unexpected current URL');
    $collision=R::getCell('SELECT id FROM product WHERE alias=? AND id<>?',[$expected['alias'],$p['id']]);
    if($collision) throw new RuntimeException('Alias conflict');
    $image=$images['products'][$p['article']]['filename'];
    foreach(['mini','baseimg','original'] as $folder) if(!is_file(WWW.'/images/product/'.$folder.'/'.$image)) throw new RuntimeException('Missing neutral image '.$image);
    $update=['brand_id'=>0,'alias'=>$expected['alias'],'img'=>$image];
    foreach(['name','seo_h1','title','description','keywords','content','short_description','note','search_brand','search_features','model_search_canonical','model_search_variants','model_search_prefixes'] as $field) {
        if(!isset($p[$field])) continue;
        $value=preg_replace($pattern,'',$p[$field]);
        if($field==='content') $value=preg_replace('~\s*Обозначение производителя:\s*[^<]*(?=</p>)~u','',$value);
        $value=preg_replace('~[ \t]{2,}~',' ',$value);
        $update[$field]=trim($value);
    }
    $before[$p['id']]=$p; $changes[$p['id']]=$update;
    if((int)$p['brand_id']>0) $brandIds[(int)$p['brand_id']]=true;
}
$attrs=R::getAll("SELECT * FROM attribute WHERE attribute_name IN ('Производитель цепей','Обозначение у производителя')");
$brands=[]; foreach(array_keys($brandIds) as $id) {
 if(!R::getCell("SELECT COUNT(*) FROM product WHERE brand_id=? AND article NOT LIKE 'TSEP-%'",[$id])) $brands[]=R::getRow('SELECT * FROM brand WHERE id=?',[$id]);
}
if(!in_array('--apply',$argv,true)) {echo json_encode(['products'=>count($changes),'hidden_attributes'=>count($attrs),'supplier_brands'=>count($brands)],JSON_PRETTY_PRINT).PHP_EOL;exit;}
$dir=ROOT.'/storage/chain-supplier-visibility'; if(!is_dir($dir)) mkdir($dir,0775,true);
$backup=$dir.'/before-'.date('Ymd-His').'.json';
if(file_put_contents($backup,json_encode(['products'=>$before,'attributes'=>$attrs,'brands'=>$brands],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false) throw new RuntimeException('Backup failed');
R::begin();
try {
 foreach($changes as $id=>$update) {
    $parts=[]; foreach(array_keys($update) as $field) $parts[]='`'.$field.'`=?';
    R::exec('UPDATE product SET '.implode(',',$parts).' WHERE id=?',array_merge(array_values($update),[$id]));
 }
 foreach($attrs as $a) R::exec("UPDATE attribute SET hide_product='hide' WHERE id=?",[$a['id']]);
 foreach($brands as $brand) R::exec("UPDATE brand SET name=?,alias=?,title=?,content='',description='',keywords='' WHERE id=?",['Цепи противоскольжения','cepi-catalog-'.$brand['id'],'Цепи противоскольжения',$brand['id']]);
 foreach($before as $id=>$old) {
    $p=R::getRow('SELECT * FROM product WHERE id=?',[$id]);
    foreach(['price','price_rrs','opt_price','spec_price','stock_status_id','quantity','rest','reserve','inventory_quantity','inventory_rest','stock_adjustment'] as $field) if($p[$field]!==$old[$field]) throw new RuntimeException('Commercial data changed');
    foreach(['name','seo_h1','title','description','keywords','content','short_description','note','alias'] as $field) if(preg_match($pattern,(string)$p[$field])) throw new RuntimeException('Supplier remains in '.$field);
 }
 R::commit();
} catch(Throwable $e) {
 R::rollback();
 foreach($attrs as $a) R::exec('UPDATE attribute SET hide_product=? WHERE id=?',[$a['hide_product'],$a['id']]);
 foreach($brands as $b) R::exec('UPDATE brand SET name=?,alias=?,title=?,content=?,description=?,keywords=? WHERE id=?',[$b['name'],$b['alias'],$b['title'],$b['content'],$b['description'],$b['keywords'],$b['id']]);
 fwrite(STDERR,$e->getMessage().PHP_EOL); exit(1);
}
foreach(['cats','brands','ishop_menu'] as $key) \ishop\Cache::instance()->delete($key);
echo json_encode(['products'=>count($changes),'hidden_attributes'=>count($attrs),'backup'=>$backup,'commercial_data_unchanged'=>true],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
