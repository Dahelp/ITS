<?php
/** php scripts/import_tsep_chains.php [--apply]. Safe to repeat; never overwrites existing products. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/config/init.php';
require CONF.'/db_bootstrap.php';
use app\services\TsepChainCatalog;

$catalog = json_decode(file_get_contents(ROOT.'/app/data/tsep-chains.json'), true, 512, JSON_THROW_ON_ERROR);
$products = TsepChainCatalog::products($catalog);
$apply = in_array('--apply', $argv, true);
$aliases = array_column($products, 'alias');
if (count($aliases) !== count(array_unique($aliases)) || count($products) !== 55) throw new RuntimeException('Catalog validation failed');
$report = ['mode'=>$apply?'apply':'preview', 'source_series'=>count($catalog['series']), 'products'=>count($products),
    'groups'=>array_count_values(array_column($products,'group')), 'created'=>[], 'skipped'=>[], 'categories'=>[], 'filter_groups'=>[]];
if (!$apply) { echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL; exit; }
$chainMutations=[];
$chainManifest=ROOT.'/storage/tsep-chains-import/insertions-'.date('Ymd-His').'.json';

function chainInsert(string $table, array $data): int {
    global $chainMutations, $chainManifest;
    // Explicit defaults also support legacy tables without default values in strict SQL mode.
    $columns = R::getAll('SHOW COLUMNS FROM `'.$table.'`');
    foreach ($columns as $col) {
        $field = $col['Field'];
        if (array_key_exists($field,$data) || $col['Extra']==='auto_increment' || $col['Null']==='YES' || $col['Default']!==null) continue;
        if ($field==='id') $data[$field]=(int)R::getCell('SELECT COALESCE(MAX(id),0)+1 FROM `'.$table.'`');
        elseif (preg_match('/int|decimal|float|double/', $col['Type'])) $data[$field]=0;
        elseif ($col['Type']==='date') $data[$field]=date('Y-m-d');
        elseif (strpos($col['Type'],'datetime')===0) $data[$field]=date('Y-m-d H:i:s');
        else $data[$field]='';
    }
    R::exec('INSERT INTO `'.$table.'` (`'.implode('`,`',array_keys($data)).'`) VALUES ('.R::genSlots(array_values($data)).')',array_values($data));
    $id=isset($data['id']) ? (int)$data['id'] : (int)R::getCell('SELECT LAST_INSERT_ID()');
    $chainMutations[]=['table'=>$table,'id'=>$id,'data'=>$data];
    file_put_contents($chainManifest,json_encode($chainMutations,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    return $id;
}
function chainCategory(string $name, string $alias, int $parent, int $type, string $image): int {
    $existing=R::getRow('SELECT id,parent_id FROM category WHERE alias=?',[$alias]);
    if($existing) { if((int)$existing['parent_id']!==$parent) throw new RuntimeException('Category alias conflict'); return (int)$existing['id']; }
    return chainInsert('category',['name'=>$name,'h1'=>$name,'alias'=>$alias,'parent_id'=>$parent,'type_id'=>$type,
        'hide'=>'show','position'=>50,'img'=>$image,'title'=>$name.' — изготовление под заказ | ИТС-Центр',
        'description'=>$name.'. Подбор по размеру шин, рисунку и калибру цепи. Изготовление под заказ. Цена и срок поставки согласовываются с менеджером.',
        'content'=>'<p>'.htmlspecialchars($name,ENT_QUOTES,'UTF-8').'. '.TsepChainCatalog::orderText().'</p>',
        'top_content'=>'<p>Подберите цепи по размеру шин, рисунку и калибру. Все цепи изготавливаются под заказ; цена и срок поставки — по запросу.</p>']);
}
$backupDir=ROOT.'/storage/tsep-chains-import';
if(!is_dir($backupDir)) mkdir($backupDir,0775,true);
foreach(['baseimg','mini','original'] as $imageDir) {
    if(!is_dir(WWW.'/images/product/'.$imageDir)) mkdir(WWW.'/images/product/'.$imageDir,0775,true);
    $target=WWW.'/images/product/'.$imageDir.'/tsep-chain-placeholder.jpg';
    if(!is_file($target) && !copy(WWW.'/images/no_image.jpg',$target)) throw new RuntimeException('Unable to install placeholder');
}
$existing=R::getAll("SELECT id,article,name,alias FROM product WHERE article LIKE 'TSEP-%'");
file_put_contents($backupDir.'/before-'.date('Ymd-His').'.json',json_encode(['products'=>$existing,'category'=>R::getAll('SELECT * FROM category WHERE alias LIKE ?', ['%cepi%'])],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
// Serialize this importer; do not race manual MAX(id) allocations in legacy link tables.
if((int)R::getCell("SELECT GET_LOCK('its_tsep_chains_import',10)")!==1) throw new RuntimeException('Importer already running');
R::begin();
$committed=false;
try {
    $placeholder = is_file(WWW.'/images/category/baseimg/no_image.jpg') ? 'no_image.jpg' : '';
    $parent=chainCategory('Цепи противоскольжения','cepi-protivoskolzheniya',0,1,$placeholder);
    $categories=[
        'special'=>chainCategory('Цепи противоскольжения для спецтехники','cepi-dlya-spectehniki',$parent,2,$placeholder),
        'forklift'=>chainCategory('Цепи противоскольжения для вилочных погрузчиков','cepi-dlya-vilochnyh-pogruzchikov',$parent,2,$placeholder),
    ];
    if(max($categories)>255 || $parent>255) throw new RuntimeException('Category id exceeds legacy product.category_id limit');
    $report['categories']=$categories+['parent'=>$parent];
    $brand=0; // Supplier identity is private; keep public products unbranded.
    $groupNames=['chain_application'=>'Назначение цепи','chain_pattern'=>'Рисунок цепи',
        'chain_caliber'=>'Калибр цепи (рабочая/боковая), мм','chain_working'=>'Калибр рабочей цепи, мм',
        'chain_side'=>'Калибр боковой цепи, мм','chain_thorns'=>'Шипы','chain_strength'=>'Прочность цепи',
        'size'=>'Размер шины','chain_package'=>'Комплектация цепей'];
    $filterGroups=[];
    foreach($groupNames as $key=>$name) {
        $group=(int)R::getCell('SELECT id FROM attribute_group WHERE url_params=?',[$key]);
        if(!$group) $group=chainInsert('attribute_group',['title'=>$name,'url_params'=>$key,'page_name'=>$name,'page_mode'=>'standalone']);
        $filterGroups[$key]=$group;
        foreach([$parent,$categories['special'],$categories['forklift']] as $cat) {
            if(!R::getCell('SELECT 1 FROM attribute_category WHERE category_id=? AND group_id=?',[$cat,$group])) chainInsert('attribute_category',['category_id'=>$cat,'group_id'=>$group]);
        }
    }
    $report['filter_groups']=$filterGroups;
    // Group 1 is the existing visible product-specification section (attribute.id=1).
    if(!R::getCell('SELECT id FROM attribute WHERE id=1')) throw new RuntimeException('Missing specification group');
    $attrIds=[]; $filterValues=[];
    foreach($products as $product) {
        $existingProduct=R::getRow('SELECT id,article FROM product WHERE alias=? OR article=?',[$product['alias'],$product['article']]);
        if($existingProduct) {
            if($existingProduct['article']!==$product['article']) throw new RuntimeException('Product alias conflict');
            $report['skipped'][]=(int)$existingProduct['id']; continue;
        }
        $id=chainInsert('product',[
            'name'=>$product['name'],'seo_h1'=>$product['name'],'alias'=>$product['alias'],'article'=>$product['article'],
            'sku'=>'','category_id'=>$categories[$product['group']],'brand_id'=>$brand,'model'=>$product['model'],
            'price'=>0,'price_rrs'=>0,'opt_price'=>0,'spec_price'=>0,
            'stock_status_id'=>2,'quantity'=>0,'rest'=>0,'reserve'=>0,'inventory_quantity'=>0,'inventory_rest'=>0,'stock_adjustment'=>0,
            'hide'=>'show','unit'=>'компл.','weight'=>$product['weight'],'img'=>'tsep-chain-placeholder.jpg',
            'title'=>$product['name'].' — под заказ | ИТС-Центр','description'=>$product['description'],'content'=>$product['content'],
            'short_description'=>TsepChainCatalog::orderText(),
            'note'=>TsepChainCatalog::orderText(),
        ]);
        // Internal site code is distinct from the supplier designation, preserved in specs.
        R::exec('UPDATE product SET sku=? WHERE id=?',[(string)$id,$id]);
        $position=100;
        foreach($product['specs'] as $name=>$value) {
            if(!isset($attrIds[$name])) {
                $attr=(int)R::getCell('SELECT id FROM attribute WHERE attribute_group_id=1 AND attribute_name=?',[$name]);
                if(!$attr) $attr=chainInsert('attribute',['attribute_group_id'=>1,'attribute_name'=>$name,'attribute_position'=>$position,'attribute_hide'=>'show','hide_product'=>in_array($name,['Производитель цепей','Обозначение у производителя'],true)?'hide':'show']);
                $attrIds[$name]=$attr;
            }
            chainInsert('product_attribute',['product_id'=>$id,'attribute_id'=>$attrIds[$name],'attribute_group_id'=>1,'attribute_text'=>$value]);
            $position++;
        }
        foreach($product['filters'] as $key=>$values) foreach($values as $value) {
            $group=$filterGroups[$key]; $cacheKey=$group.'|'.$value;
            if(!isset($filterValues[$cacheKey])) {
                $fv=(int)R::getCell('SELECT id FROM attribute_value WHERE attr_group_id=? AND value=?',[$group,$value]);
                if(!$fv) $fv=chainInsert('attribute_value',['value'=>$value,'alias'=>'chain-'.str_replace('_','-',$key).'-'.substr(sha1($value),0,12),'attr_group_id'=>$group,'hide'=>'show','title'=>$value]);
                $filterValues[$cacheKey]=$fv;
            }
            chainInsert('attribute_product',['attr_id'=>$filterValues[$cacheKey],'product_id'=>$id]);
        }
        $report['created'][]=['id'=>$id,'article'=>$product['article'],'alias'=>$product['alias']];
    }
    $ids=R::getCol('SELECT id FROM product WHERE category_id IN (?,?)',array_values($categories));
    $bad=(int)R::getCell("SELECT COUNT(*) FROM product WHERE article LIKE 'TSEP-%' AND (stock_status_id<>2 OR quantity<>0 OR rest<>0 OR inventory_quantity<>0 OR inventory_rest<>0 OR stock_adjustment<>0)");
    if($bad) throw new RuntimeException('Imported products violate made-to-order status');
    R::commit();
    $committed=true;
    $cache=\ishop\Cache::instance();
    foreach(['cats','ishop_menu','admin_select','admin_cat','category','categories','brands','filter_group_all'] as $key) $cache->delete($key);
    foreach([$parent,$categories['special'],$categories['forklift']] as $cat) $cache->delete('filter_group_'.md5((string)$cat));
    file_put_contents($backupDir.'/result-'.date('Ymd-His').'.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch(Throwable $e) {
    if(!$committed) {
        R::rollback();
        // Legacy attribute/link tables use MyISAM: compensate only this run's inserts.
        foreach(array_reverse($chainMutations) as $mutation) {
            $where='id=?'; $args=[$mutation['id']];
            if(isset($mutation['data']['product_id'])) { $where.=' AND product_id=?'; $args[]=$mutation['data']['product_id']; }
            R::exec('DELETE FROM `'.$mutation['table'].'` WHERE '.$where,$args);
        }
    }
    throw $e;
}
finally { R::getCell("SELECT RELEASE_LOCK('its_tsep_chains_import')"); }
