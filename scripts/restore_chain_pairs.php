<?php
/** Restore sale units and package facts for the explicit 54-product manifest. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$args=getopt('', ['root:', 'report-dir:', 'manifest:', 'apply']);
$root=realpath($args['root']??dirname(__DIR__));$dir=realpath($args['report-dir']??__DIR__);
if (!$root||!$dir||str_starts_with($dir.'/', $root.'/public/')) throw new RuntimeException('Private report directory required');
$manifest=json_decode(file_get_contents($args['manifest']??''),true,512,JSON_THROW_ON_ERROR);
$excluded='TSEP-F673331FA546';
if(count($manifest)!==54||count(array_unique(array_column($manifest,'article')))!==54||in_array($excluded,array_column($manifest,'article'),true))throw new RuntimeException('Invalid 54-product scope');
require $root.'/config/init.php';require CONF.'/db_bootstrap.php';
$db=R::getPDO();$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$fetch=static function($sql,$params=[])use($db){$q=$db->prepare($sql);$q->execute($params);return $q->fetchAll(PDO::FETCH_ASSOC);};
$write=static function($change,$reverse=false)use($db){
 $values=$reverse?$change['before']:$change['after'];$fields=array_keys($change['after']);
 $q=$db->prepare('UPDATE `'.$change['table'].'` SET '.implode(',',array_map(static fn($f)=>'`'.$f.'`=?',$fields)).' WHERE id=?');
 $q->execute(array_merge(array_map(static fn($f)=>$values[$f],$fields),[$change['before']['id']]));
};
$apply=isset($args['apply']);$plan=[];$written=[];$report=['products'=>[],'mode'=>$apply?'apply':'preview'];
$lock=$fetch("SELECT GET_LOCK('its_chain_sale_units',10) AS acquired");
if((int)$lock[0]['acquired']!==1)throw new RuntimeException('Chain update already running');
$db->beginTransaction();
try{
 $products=$fetch("SELECT * FROM product WHERE article LIKE 'TSEP-%' ORDER BY id FOR UPDATE");
 if(count($products)!==55)throw new RuntimeException('Expected 55 chain products');
 $index=array_column($products,null,'article');$ids=implode(',',array_map('intval',array_column($products,'id')));
 $attrs=$fetch("SELECT pa.*,a.attribute_name FROM product_attribute pa JOIN attribute a ON a.id=pa.attribute_id WHERE pa.product_id IN ($ids) ORDER BY pa.id FOR UPDATE");
 $links=$fetch("SELECT * FROM attribute_product WHERE product_id IN ($ids) ORDER BY product_id,attr_id FOR UPDATE");
 $targets=[];
 foreach($manifest as $m){
  $p=$index[$m['article']]??null;
  if(!$p||$p['alias']!==$m['alias']||!str_starts_with($m['package'],'2 цепи'))throw new RuntimeException('Identity/package mismatch');
  $targets[]=(int)$p['id'];$change=['unit'=>'компл.'];$pack=$m['package'];
  foreach(['content','short_description','description','title','seo_h1','name']as $field){
   $old=(string)($p[$field]??'');$new=str_replace(['1 цепь на одно колесо','от одной цепи до оснащения'],['комплект из 2 цепей на 2 колеса','от одного комплекта до оснащения'],$old);
   if($field==='content'){
    $new=str_replace(['Крепления, натяжные элементы и упаковку согласуем при заказе.','Крепления и состав поставки согласовываются при заказе.'],['Состав комплекта: '.$pack.'.','Состав комплекта: '.$pack.'.'],$new);
    if(!str_contains($new,'data-chain-sale-unit="2"'))$new='<p data-chain-sale-unit="2" style="margin:20px 0;padding:16px 20px;background:#edf8f1;border-radius:10px;line-height:1.6"><strong>Цена указана за комплект из 2 цепей на 2 колеса.</strong><br>Комплектация: '.htmlspecialchars($pack,ENT_QUOTES,'UTF-8').'.</p>'.$new;
    if(str_contains($new,'1 цепь на одно колесо'))throw new RuntimeException('Single chain statement remains');
   }
   if($new!==$old)$change[$field]=$new;
  }
  $change=array_filter($change,static fn($v,$k)=>$v!==($p[$k]??null),ARRAY_FILTER_USE_BOTH);
  if($change)$plan[]=['table'=>'product','before'=>$p,'after'=>$change];
  $packageRows=array_values(array_filter($attrs,static fn($a)=>(int)$a['product_id']===(int)$p['id']&&$a['attribute_name']==='Комплектация цепей'));
  if(count($packageRows)!==1)throw new RuntimeException('Expected one package attribute per target');
  $a=$packageRows[0];unset($a['attribute_name']);
  if($a['attribute_text']!==$pack)$plan[]=['table'=>'product_attribute','before'=>$a,'after'=>['attribute_text'=>$pack]];
  $report['products'][]=['article'=>$p['article'],'alias'=>$p['alias'],'price'=>(float)$p['price']>0?(float)$p['price']:null,'quantity_per_sale'=>2,'package'=>$pack];
 }
 // Preserve package filter URLs and links; change their displayed unit only.
 $values=$fetch("SELECT v.* FROM attribute_value v JOIN attribute_group g ON g.id=v.attr_group_id WHERE g.url_params='chain_package' ORDER BY v.id FOR UPDATE");
 foreach($values as $v){
  $refs=$fetch('SELECT product_id FROM attribute_product WHERE attr_id=?',[$v['id']]);
  $chainRefs=array_filter($refs,static fn($a)=>in_array((int)$a['product_id'],$targets,true));
  if(!$chainRefs)continue;
  if(count($chainRefs)!==count($refs))throw new RuntimeException('Package filter is shared outside the 54 targets');
  $changed=[];foreach(['value','title']as $f)if(isset($v[$f])&&$v[$f]!=='Комплект из 2 цепей')$changed[$f]='Комплект из 2 цепей';
  if($changed)$plan[]=['table'=>'attribute_value','before'=>$v,'after'=>$changed];
 }
 if($apply&&$plan){
  $fp=fopen($dir.'/before.json','x');if(!$fp)throw new RuntimeException('Backup already exists');chmod($dir.'/before.json',0600);
  $backup=json_encode($plan,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);if(fwrite($fp,$backup)!==strlen($backup))throw new RuntimeException('Backup failed');fclose($fp);
  foreach($plan as $change){$write($change);$written[]=$change;}
  foreach($plan as $c){$row=$fetch('SELECT * FROM `'.$c['table'].'` WHERE id=?',[$c['before']['id']])[0];foreach($c['before']as $f=>$value)if(($c['after'][$f]??$value)!==$row[$f])throw new RuntimeException('Unexpected field change '.$c['table'].'/'.$f);}
  $after=$fetch("SELECT * FROM product WHERE article LIKE 'TSEP-%' ORDER BY id");
  foreach($after as $p){$old=$index[$p['article']];if($p['price']!==$old['price'])throw new RuntimeException('Price changed');if($p['article']===$excluded&&$p!==$old)throw new RuntimeException('Excluded Sota 10/10 changed');}
  if($links!==$fetch("SELECT * FROM attribute_product WHERE product_id IN ($ids) ORDER BY product_id,attr_id"))throw new RuntimeException('Filter links changed');
  $afterAttrs=$fetch("SELECT pa.*,a.attribute_name FROM product_attribute pa JOIN attribute a ON a.id=pa.attribute_id WHERE pa.product_id IN ($ids) ORDER BY pa.id");
  foreach($afterAttrs as $i=>$a)if($a['attribute_name']!=='Комплектация цепей'&&$a!==$attrs[$i])throw new RuntimeException('Non-package specification changed');
  $db->commit();
 }else $db->rollBack();
 $report+=['changed_rows'=>count($plan),'tables'=>array_count_values(array_column($plan,'table')),'prices_unchanged'=>true,'excluded_unchanged'=>true,'filter_links_unchanged'=>true,'other_specifications_unchanged'=>true,'checked_at'=>date(DATE_ATOM)];
 file_put_contents($dir.'/'.($apply?'publication':'preview').'.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
 echo json_encode(['products'=>54,'changed_rows'=>count($plan),'tables'=>$report['tables']]).PHP_EOL;
}catch(Throwable $e){
 if($db->inTransaction())$db->rollBack();
 foreach(array_reverse($written)as $c)$write($c,true);
 file_put_contents($dir.'/failure.json',json_encode(['error'=>$e->getMessage(),'compensated_nontransactional_rows'=>count($written)]));fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);
}finally{$fetch("SELECT RELEASE_LOCK('its_chain_sale_units')");}
