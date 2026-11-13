<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/config/init.php';
\ishop\App::$app=\ishop\Registry::instance();
foreach ((require CONF.'/params.php') as $k=>$v) { \ishop\App::$app->setProperty($k,$v); }
\ishop\Db::instance();
\app\services\CompleteAvito::ensureSchema();
$siteTemplate=R::getRow('SELECT * FROM plagins_complete WHERE id = 25');
$adTemplate=R::getRow('SELECT * FROM avito_ad WHERE complete_id = 25 LIMIT 1');
if (!$siteTemplate || !$adTemplate) { throw new RuntimeException('Не найден предыдущий комплект и его объявление'); }
$specs=[
 ['front'=>'12487','rear'=>'12488','front_size'=>'27х9-14','rear_size'=>'27х11-14','pr'=>'4','rim'=>'14',
  'hide'=>'hide','image'=>'ekka-p350-27x9-27x11-14-4pr-set.png',
  'alias'=>'komplekt-shin-27x9-14-27x11-14-4pr-p350-ekka-dlya-kvadrociklov','avito_id'=>null],
 ['front'=>'11150','rear'=>'11151','front_size'=>'26х9-12','rear_size'=>'26х11-12','pr'=>'6','rim'=>'12',
  'hide'=>'show','image'=>'ekka-p350-26x9-26x11-12-set.png',
  'alias'=>'komplekt-shin-26x9-12-26x11-12-p350-ekka-dlya-kvadrociklov','avito_id'=>'2444312908'],
];
// All assets and both product pairs must exist before writing anything.
foreach ($specs as &$spec) {
 $spec['products']=[];
 foreach ([$spec['front'],$spec['rear']] as $article) {
  $p=R::getRow('SELECT * FROM product WHERE article = ?',[$article]);
  if (!$p || $p['model'] !== 'P350' || (int)$p['category_id'] !== 2) { throw new RuntimeException('Не найдена шина P350 '.$article); }
  $spec['products'][]=$p;
  foreach (['baseimg','mini'] as $dir) {
   if (!is_file(WWW.'/images/product/'.$dir.'/'.$p['img'])) { throw new RuntimeException('Нет фотографии шины '.$article); }
  }
 }
 foreach (['baseimg','mini'] as $dir) {
  if (!is_file(WWW.'/images/complete/'.$dir.'/'.$spec['image'])) { throw new RuntimeException('Нет обложки комплекта '.$spec['image']); }
 }
}
unset($spec);
$backup=ROOT.'/storage/p350-27-26-sets-before-20261016.json';
if (!is_file($backup) && file_put_contents($backup,json_encode([
 'sets'=>R::getAll('SELECT * FROM plagins_complete'),
 'items'=>R::getAll('SELECT * FROM plagins_complete_product'),
 'ads'=>R::getAll('SELECT * FROM avito_ad WHERE id IN (140,177)'),
],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))===false) { throw new RuntimeException('Не создана резервная копия'); }
function insertP350Row(string $table,array $row): void {
 R::exec('INSERT INTO `'.$table.'` (`'.implode('`,`',array_keys($row)).'`) VALUES ('.implode(',',array_fill(0,count($row),'?')).')',array_values($row));
}
function uuidP350(): string {
 $b=random_bytes(16); $b[6]=chr((ord($b[6])&15)|64); $b[8]=chr((ord($b[8])&63)|128);
 return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));
}
function replaceP350Text(string $text,array $spec): string {
 $height=explode('х',$spec['front_size'])[0];
 return strtr($text,[
  '29х9-14'=>$spec['front_size'],'29х11-14'=>$spec['rear_size'],
  '29x9-14'=>str_replace('х','x',$spec['front_size']),'29x11-14'=>str_replace('х','x',$spec['rear_size']),
  '29*11-14'=>str_replace('х','*',$spec['rear_size']),'29*9-15'=>str_replace('х','*',$spec['front_size']),
  '29-14'=>$height.'-'.$spec['rim'],'r14'=>'r'.$spec['rim'],
  '14 дюймов'=>$spec['rim'].' дюймов','29 дюймов'=>$height.' дюймов','6PR'=>$spec['pr'].'PR',
 ]);
}
if (!(int)R::getCell("SELECT GET_LOCK('its_p350_sets_create',10)")) { throw new RuntimeException('Создание уже запущено'); }
$result=[];
try {
 foreach ($specs as $spec) {
  $id=(int)R::getCell('SELECT id FROM plagins_complete WHERE alias = ?',[$spec['alias']]);
  $name='Комплект шин '.$spec['front_size'].'/'.$spec['rear_size'].($spec['pr']==='4'?' 4PR':'').' P350 EKKA для квадроциклов';
  R::begin();
  try {
   if (!$id) {
    $row=$siteTemplate; unset($row['id']);
    $row['name']=$name; $row['alias']=$spec['alias']; $row['hide']=$spec['hide']; $row['img']=$spec['image'];
    $row['content']=replaceP350Text($siteTemplate['content'],$spec);
    if ($spec['pr']==='4') { $row['content'].='<p>Слойность обеих шин — 4PR. Наличие и цену уточняйте.</p>'; }
    $row['title']='Комплект '.$spec['front_size'].'/'.$spec['rear_size'].' P350 EKKA '.$spec['pr'].'PR — ИТС-Центр';
    $row['description']='Разноширокий комплект EKKA P350 '.$spec['pr'].'PR: 2 шины '.$spec['front_size'].' и 2 шины '.$spec['rear_size'].'. Цена за 4 шины. Самовывоз и доставка по России.';
    $row['keywords']='комплект шин P350 EKKA, '.$spec['front_size'].', '.$spec['rear_size'].', '.$spec['pr'].'PR, 4 шины для квадроцикла';
    insertP350Row('plagins_complete',$row); $id=(int)R::getCell('SELECT LAST_INSERT_ID()');
    foreach ($spec['products'] as $p) {
     R::exec('INSERT INTO plagins_complete_product (complete_id,product_id,qty,price,discount,discount_amount) VALUES (?,?,2,?,0,0)',[$id,$p['id'],$p['price']]);
    }
   }
   $adId=(int)R::getCell('SELECT id FROM avito_ad WHERE complete_id = ?',[$id]);
   if (!$adId && $spec['avito_id']) {
    $existing=R::getRow('SELECT * FROM avito_ad WHERE avito_id = ?',[$spec['avito_id']]);
    if (!$existing || !empty($existing['complete_id'])) { throw new RuntimeException('Объявление занято или не найдено'); }
    $adId=(int)$existing['id'];
   }
   $description=replaceP350Text($adTemplate['description'],$spec);
   if ($spec['hide']==='hide') { $description=str_replace('В наличии!','Наличие и цену уточняйте.',$description); }
   $fields=['complete_id'=>$id,'article'=>null,'title'=>'Комплект '.$spec['front_size'].'/'.$spec['rear_size'].' P350 EKKA '.$spec['pr'].'PR',
    'description'=>$description,'product_type'=>'Мотошины','brand'=>'EKKA','model'=>'P350','ply_rating'=>$spec['pr'],
    'rim_diameter'=>$spec['rim'],'tire_section_width'=>'9','tire_aspect_ratio'=>'Другое','wheel_axle'=>'Любая','quantity'=>'за 4 шт.',
    'weight_kg'=>array_sum(array_map(static function($p){return (float)$p['weight']*2;},$spec['products'])),
    'status'=>'archived','stock_quantity'=>0,'price_rub'=>null,'review_status'=>null,
    'images_json'=>json_encode(['https://its-center.ru/images/complete/baseimg/'.$spec['image']])];
   if (!$adId) {
    $ad=$adTemplate;
    $adId=(int)R::getCell('SELECT COALESCE(MAX(id),0)+1 FROM avito_ad');
    $ad=array_merge($ad,$fields,['id'=>$adId,'uuid'=>uuidP350(),'avito_id'=>null,'avito_url'=>null,
      'ad_external_id'=>'its-complete-'.$id,'date_begin'=>null,'date_end'=>null,
      'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
    insertP350Row('avito_ad',$ad);
   } else {
    $sql=implode(',',array_map(static function($k){return '`'.$k.'` = ?';},array_keys($fields)));
    R::exec('UPDATE avito_ad SET '.$sql.' WHERE id = ?',array_merge(array_values($fields),[$adId]));
   }
   foreach ($spec['products'] as $p) {
    $img=$p['img']; $dest=WWW.'/images/complete/gallery/'.$img;
    if (!is_file($dest) && !copy(WWW.'/images/product/baseimg/'.$img,$dest)) { throw new RuntimeException('Не скопировано фото галереи'); }
    if (!R::getCell('SELECT id FROM plagins_complete_gallery WHERE complete_id = ? AND img = ?',[$id,$img])) {
     R::exec('INSERT INTO plagins_complete_gallery (complete_id,img) VALUES (?,?)',[$id,$img]);
    }
   }
   R::commit();
   $result[]=['complete_id'=>$id,'ad_id'=>$adId,'avito_id'=>$spec['avito_id'],'name'=>$name,'hide'=>$spec['hide'],
    'url'=>'https://its-center.ru/complete/'.$spec['alias'],'totals'=>\app\services\CompleteAvito::totals($id)];
  } catch (Throwable $e) { R::rollback(); throw $e; }
 }
} finally { R::getCell("SELECT RELEASE_LOCK('its_p350_sets_create')"); }
\app\services\CompleteAvito::sync();
echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
if (in_array('--publish',$argv,true)) {
 $publication=\app\services\CompleteAvitoPublication::run();
 echo json_encode($publication,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
 if (empty($publication['autoload_requested'])) {
  echo json_encode(\app\models\admin\Avito::triggerAutoloadUpload(),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
 }
}
