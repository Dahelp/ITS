<?php
if(PHP_SAPI!=='cli')exit(1);
$root=realpath($argv[1]??'');$mode=$argv[2]??'audit';
if($root!=='/home/s/shinaspec/its-center.ru/public_html'||!in_array($mode,['audit','publish'],true))throw new RuntimeException('Invalid target');
require $root.'/config/init.php';require CONF.'/db_bootstrap.php';
$alias='cepi-protivoskolzheniya-dlya-spectehniki-v-prodazhe';
$type=R::getRow('SELECT * FROM content_type WHERE param_url=? AND hide=?',['news','show']);
if(!$type)throw new RuntimeException('News section missing');
$cats=R::getAll('SELECT id,name,alias,hide FROM category WHERE id IN(43,44,45)');
$existing=R::getAll('SELECT * FROM contents WHERE alias=?',[$alias]);
$report=['type'=>$type,'columns'=>R::getAll('SHOW COLUMNS FROM contents'),'categories'=>$cats,'existing'=>$existing,'recent'=>R::getAll('SELECT id,name,alias,img,date_post FROM contents WHERE type_id=? ORDER BY date_post DESC LIMIT 3',[$type['id']])];
file_put_contents(__DIR__.'/audit.json',json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
if($mode==='audit'){echo "News audit ready\n";exit;}
if(count($cats)!==3||array_filter($cats,fn($r)=>$r['hide']!=='show'))throw new RuntimeException('Categories not published');
$name='Цепи противоскольжения для спецтехники — теперь в продаже в ИТС-Центр';
$content=file_get_contents(__DIR__.'/news.html');
$content=preg_replace('~^<h1>.*?</h1>\s*~su','',$content);
$fields=['type_id'=>(int)$type['id'],'name'=>$name,'alias'=>$alias,'anons'=>'В каталоге ИТС-Центр появились цепи противоскольжения для спецтехники и вилочных погрузчиков. Подберём исполнение по размеру шин и условиям работы.','content'=>$content,'title'=>'Цепи противоскольжения для спецтехники появились в продаже | ИТС-Центр','description'=>'В ИТС-Центр появились цепи противоскольжения для спецтехники и вилочных погрузчиков. Подбор по размеру шин и условиям работы, уточнение стоимости и сроков поставки.','keywords'=>'цепи противоскольжения, цепи для спецтехники, цепи для погрузчиков','hide'=>'show','img_hide'=>'show','img_source'=>'own'];
if($existing){
 if(count($existing)!==1)throw new RuntimeException('Duplicate alias');
 foreach($fields as $k=>$v)if((string)$existing[0][$k]!== (string)$v)throw new RuntimeException('Existing news differs: '.$k);
 $id=(int)$existing[0]['id'];
}else{
 $image='snow-chains-special-equipment-category-v1.webp';
 foreach(['baseimg','mini'] as $size){$src=WWW.'/images/category/'.$size.'/'.$image;$dest=WWW.'/images/contents/'.$size.'/'.$image;if(!is_file($src))throw new RuntimeException('Image source missing');if(is_file($dest)&&hash_file('sha256',$dest)!==hash_file('sha256',$src))throw new RuntimeException('Image conflict');if(!is_dir(dirname($dest)))throw new RuntimeException('Image directory missing');if(!is_file($dest)&&!copy($src,$dest))throw new RuntimeException('Image copy failed');}
 $fields['img']=$image;$fields['date_post']=(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format('Y-m-d H:i:s');$fields['clicks']=0;
 file_put_contents(__DIR__.'/before.json',json_encode(['alias'=>$alias,'existing'=>$existing],JSON_PRETTY_PRINT));
 $cols=array_keys($fields);$sql='INSERT INTO contents (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')';
 R::exec($sql,array_values($fields));$id=(int)R::getCell('SELECT id FROM contents WHERE alias=?',[$alias]);
}
$saved=R::getRow('SELECT * FROM contents WHERE id=?',[$id]);
foreach($fields as $k=>$v)if((string)$saved[$k]!== (string)$v)throw new RuntimeException('Readback mismatch: '.$k);
$receipt=['id'=>$id,'url'=>'https://its-center.ru/news/'.$alias,'published_at'=>date(DATE_ATOM),'title'=>$name,'private_backup'=>__DIR__.'/before.json'];
file_put_contents(__DIR__.'/receipt.json',json_encode($receipt,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo json_encode($receipt,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
