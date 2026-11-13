<?php
// Scoped SEO publication. No catalog, stock, pricing or image changes.
if(PHP_SAPI!=='cli') {http_response_code(404);exit;}
require dirname(__DIR__).'/config/init.php'; require CONF.'/db_bootstrap.php';
use app\services\ChainSeoContent;
$plan=json_decode(file_get_contents(ROOT.'/app/data/chain-seo-content.json'),true,512,JSON_THROW_ON_ERROR);
if(count($plan['products'])!==55 || count($plan['categories'])!==3 || count($plan['landings'])!==30) throw new RuntimeException('Invalid publication scope');
$categoryIds=[]; $categories=[]; $products=[]; $attributes=[]; $inseoBefore=[]; $landingBefore=[];
foreach($plan['categories'] as $alias=>$data) {
 $row=R::getRow('SELECT * FROM category WHERE alias=?',[$alias]); if(!$row) throw new RuntimeException('Missing category');
 $categoryIds[$alias]=(int)$row['id']; $categories[(int)$row['id']]=$row;
}
foreach($plan['products'] as $page) {
 $p=R::getRow('SELECT * FROM product WHERE article=?',[$page['article']]);
 if(!$p || $p['alias']!==$page['alias'] || !in_array((int)$p['category_id'],array_values($categoryIds),true)) throw new RuntimeException('Product scope mismatch');
 $products[(int)$p['id']]=$p;
}
foreach($plan['attribute_params'] as $name=>$param) {
 $rows=R::getAll('SELECT * FROM attribute WHERE attribute_name=? AND attribute_group_id=1',[$name]);
 if(count($rows)!==1 || ($rows[0]['url_params']!=='' && $rows[0]['url_params']!==$param)) throw new RuntimeException('Attribute variable conflict: '.$name);
 $attributes[(int)$rows[0]['id']]=$rows[0];
}
$ids=array_values($categoryIds); $slots=R::genSlots($ids);
$inseoBefore=R::getAll('SELECT * FROM plagins_inseo WHERE category_id IN ('.$slots.') AND tip IN (?,?)',array_merge($ids,['category','product']));
$landingBefore=R::getAll('SELECT * FROM attribute_value_category_canonical WHERE category_id IN ('.$slots.')',$ids);
$report=['mode'=>in_array('--apply',$argv,true)?'apply':'preview','categories'=>3,'products'=>55,'product_inseo_templates'=>2,'category_inseo_rules'=>3,'filter_landings'=>30];
if(!in_array('--apply',$argv,true)) {echo json_encode($report,JSON_PRETTY_PRINT).PHP_EOL;exit;}
$dir=ROOT.'/storage/chain-seo-publication'; if(!is_dir($dir)) mkdir($dir,0775,true);
$backup=$dir.'/before-'.date('Ymd-His').'.json';
$snapshot=['categories'=>$categories,'products'=>$products,'attributes'=>$attributes,'inseo'=>$inseoBefore,'landings'=>$landingBefore];
if(file_put_contents($backup,json_encode($snapshot,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false) throw new RuntimeException('Backup failed');
function chainSeoUpdate($table,$id,$data) {
 unset($data['id']); $parts=[];foreach(array_keys($data) as $field) $parts[]='`'.$field.'`=?';
 R::exec('UPDATE `'.$table.'` SET '.implode(',',$parts).' WHERE id=?',array_merge(array_values($data),[$id]));
}
function chainSeoInsert($table,$data) {
 R::exec('INSERT INTO `'.$table.'` (`'.implode('`,`',array_keys($data)).'`) VALUES ('.R::genSlots(array_values($data)).')',array_values($data));
 return (int)R::getCell('SELECT LAST_INSERT_ID()');
}
$created=[]; $restore=[]; $committed=false;
R::begin();
try {
 foreach($attributes as $id=>$a) {
    $restore[]=['attribute',$id,$a]; chainSeoUpdate('attribute',$id,['url_params'=>$plan['attribute_params'][$a['attribute_name']]]);
 }
 foreach($plan['categories'] as $alias=>$data) {
    $id=$categoryIds[$alias];$restore[]=['category',$id,$categories[$id]];
    $faqRows=$data['faq']??[];unset($data['faq']);
    chainSeoUpdate('category',$id,$data);
    foreach($faqRows as $i=>$faq) {
      if(!R::getCell('SELECT id FROM category_faq WHERE category_id=? AND question=?',[$id,$faq['question']])) {
        $faqId=chainSeoInsert('category_faq',['category_id'=>$id,'question'=>$faq['question'],'answer'=>$faq['answer'],'sort'=>($i+1)*10,'hide'=>'show']);
        $created[]=['category_faq',$faqId];
      }
    }
 }
 $rules=[];
 foreach($plan['categories'] as $alias=>$data) $rules[]=['tip'=>'category','category_id'=>$categoryIds[$alias],'name'=>$data['h1'],'title'=>$data['title'],'description'=>$data['description'],'keywords'=>$data['keywords'],'content'=>$data['content'],'hide'=>'show'];
 foreach($plan['templates'] as $group=>$template) {unset($template['h1']);$rules[]=array_merge($template,['tip'=>'product','category_id'=>$categoryIds[$group==='special'?'cepi-dlya-spectehniki':'cepi-dlya-vilochnyh-pogruzchikov'],'hide'=>'show']);}
 foreach($rules as $rule) {
    $old=R::getAll('SELECT * FROM plagins_inseo WHERE tip=? AND category_id=?',[$rule['tip'],$rule['category_id']]);
    if(count($old)>1) throw new RuntimeException('Duplicate InSEO rules');
    if($old) {$restore[]=['plagins_inseo',$old[0]['id'],$old[0]];chainSeoUpdate('plagins_inseo',$old[0]['id'],$rule);}
    else {$id=chainSeoInsert('plagins_inseo',$rule);$created[]=['plagins_inseo',$id];}
 }
 foreach($plan['products'] as $page) {
    $id=(int)R::getCell('SELECT id FROM product WHERE article=?',[$page['article']]);
    $restore[]=['product',$id,$products[$id]];
    // Empty overrides activate the two live InSEO templates; the detailed body remains product-specific.
    chainSeoUpdate('product',$id,['seo_h1'=>$plan['templates'][$page['group']]['h1'],'title'=>'','description'=>'','keywords'=>'','content'=>$page['content'],'short_description'=>$page['short_description']]);
    $actual=R::load('product',$id);
    $rule=$plan['templates'][$page['group']];
    foreach(['name','title','description','keywords'] as $field) {
        $rendered=\ishop\App::seoreplace($rule[$field],$id);
        if($rendered!==$page['rendered'][$field]) throw new RuntimeException('InSEO expansion mismatch '.$id.' '.$field);
    }
 }
 foreach($plan['landings'] as $row) {
    if(!in_array((int)$row['category_id'],array_values($categoryIds),true)) throw new RuntimeException('Landing outside scope');
    $old=R::getAll('SELECT * FROM attribute_value_category_canonical WHERE category_id=? AND attr_value_id=?',[$row['category_id'],$row['attr_value_id']]);
    if(count($old)>1) throw new RuntimeException('Duplicate landing rows');
    $row['updated_at']=date('Y-m-d H:i:s');
    if($old) {$restore[]=['attribute_value_category_canonical',$old[0]['id'],$old[0]];chainSeoUpdate('attribute_value_category_canonical',$old[0]['id'],$row);}
    else {$row['created_at']=$row['updated_at'];$id=chainSeoInsert('attribute_value_category_canonical',$row);$created[]=['attribute_value_category_canonical',$id];}
 }
 foreach($products as $id=>$before) {
    $after=R::getRow('SELECT * FROM product WHERE id=?',[$id]);
    foreach(['name','alias','article','sku','brand_id','img','price','price_rrs','opt_price','spec_price','stock_status_id','quantity','rest','reserve','inventory_quantity','inventory_rest','stock_adjustment'] as $field) if($after[$field]!==$before[$field]) throw new RuntimeException('Non-SEO field changed: '.$field);
 }
 foreach($categories as $id=>$before) {
    $after=R::getRow('SELECT * FROM category WHERE id=?',[$id]);
    foreach(['alias','parent_id','type_id','img','hide'] as $field) if($after[$field]!==$before[$field]) throw new RuntimeException('Category structure changed');
 }
 R::commit(); $committed=true;
} catch(Throwable $e) {
 if(!$committed) {
    R::rollback();
    // Restore legacy MyISAM rows too; these do not participate in rollback.
    foreach(array_reverse($created) as [$table,$id]) R::exec('DELETE FROM `'.$table.'` WHERE id=?',[$id]);
    foreach(array_reverse($restore) as [$table,$id,$row]) chainSeoUpdate($table,$id,$row);
 }
 fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);
}
foreach(['cats','ishop_menu','admin_select','admin_cat'] as $key) \ishop\Cache::instance()->delete($key);
$report['backup']=$backup; $report['non_seo_fields_unchanged']=true;
file_put_contents($dir.'/result-'.date('Ymd-His').'.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
