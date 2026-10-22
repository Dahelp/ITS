<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\AvitoTireFields as Fields;
const INDUSTRIAL='Шины для грузовиков и спецтехники';
function tireCheck($ok,$message){if(!$ok)throw new RuntimeException($message);}
function product($name,$attributes=[],$path=['Шины для вилочных погрузчиков','Шины для спецтехники']){return ['name'=>$name,'characteristics'=>$attributes,'category_path'=>$path];}
function expected($name,$type,$width,$aspect,$rim){$p=product($name,[], $type==='Мотошины'?['Шины для квадроциклов']:['Шины для спецтехники']);$v=Fields::suggest($p,$type)['fields'];tireCheck(($v['tire_section_width']??'')===$width&&($v['tire_aspect_ratio']??'')===$aspect&&($v['rim_diameter']??'')===$rim,'Wrong size: '.$name);}
expected('6.50-10 12PR TTF QH207 SUPERGUIDER Шина',INDUSTRIAL,'6.50','Другое','10');
expected('12-16.5 12PR EKS-1 EKKA',INDUSTRIAL,'12','Другое','16.5');
expected('12,5/80-18 Шина',INDUSTRIAL,'12.5','80','18');
expected('315/80R22.5 Шина грузовая',INDUSTRIAL,'315','80','22.5');
expected('6.50R10 Шина',INDUSTRIAL,'6.50','Другое','10');
expected('27х11-14 4PR P350 EKKA','Мотошины','11','Другое','14');
expected('13×6-5 Шина ATV','Мотошины','6','Другое','5');
$p=product('Шина', [['label'=>'Размер диска (RIM)','value'=>'5.00F-10']]);$s=Fields::suggest($p,INDUSTRIAL)['fields'];
tireCheck(($s['rim_diameter']??'')==='10'&&!isset($s['tire_section_width']),'RIM width must not become tyre section width');
$p=product('Модель 134A5', [['label'=>'Размер шины','value'=>'6.50-10'],['label'=>'Ширина протектора (мм)','value'=>'165'],['label'=>'Слойность (PR)','value'=>'12']]);$s=Fields::suggest($p,INDUSTRIAL)['fields'];
tireCheck($s['tire_section_width']==='6.50'&&$s['ply_rating']==='12','Use structured size, not tread width');
tireCheck(Fields::vehicleType(product('Шина',[],['Грузовые шины','Шины']))==='Грузовая','Truck category');
tireCheck(Fields::vehicleType(product('Шина'))==='Для спецтехники','Industrial category');
$s=Fields::suggest(product('Комплект 26x9-12/26x11-12'), 'Мотошины');tireCheck(!$s['fields']&&count($s['notes'])>0,'Ambiguous sizes must not silently select first');
$ad=(object)['product_type'=>INDUSTRIAL,'tire_section_width'=>'6.50','tire_aspect_ratio'=>'10','rim_diameter'=>'10','vehicle_type'=>'Грузовая'];
Fields::apply($ad,product('6.50-10 Шина'),true);
tireCheck($ad->tire_aspect_ratio==='Другое'&&$ad->vehicle_type==='Для спецтехники','Repair copied rim and wrong vehicle type');
$ad->tire_aspect_ratio='80';Fields::apply($ad,product('6.50-10 Шина'),true);tireCheck($ad->tire_aspect_ratio==='80','Preserve independent manual aspect');
tireCheck(Fields::canonical('Мотошины','tire_section_width','6.50')==='6.5','Numeric aliases');
tireCheck(in_array('Другое',Fields::options(INDUSTRIAL,'tire_aspect_ratio'),true),'Official other option');
tireCheck(!in_array('Другое',Fields::options(INDUSTRIAL,'rim_diameter'),true),'Do not invent absent rim option');
echo "Avito tyre fields: industrial, metric, flotation, RIM distinction, category, dictionaries and safe repair OK\n";
