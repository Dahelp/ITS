<?php
if(PHP_SAPI!=='cli') exit;
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\TsepChainCatalog;
use app\services\ChainSeoContent;
$root=dirname(__DIR__); $dir=$root.'/outputs/chain-seo-20261030'; if(!is_dir($dir)) mkdir($dir,0775,true);
$catalog=json_decode(file_get_contents($root.'/app/data/tsep-chains.json'),true);
$audit=json_decode(file_get_contents($root.'/outputs/chain-seo-20261030-audit.json'),true);
$products=TsepChainCatalog::products($catalog);
$categories=ChainSeoContent::categories(); $landings=[];
$ids=[44=>'cepi-dlya-spectehniki',45=>'cepi-dlya-vilochnyh-pogruzchikov'];
$nav=[];
foreach($audit['filters'] as $filter) {
 if(!in_array($filter['url_params'],['size','chain_pattern','chain_caliber'],true)) continue;
 $matching=array_values(array_filter($products,static function($p) use($filter){return $p['group']===((int)$filter['category_id']===44?'special':'forklift') && in_array($filter['value'],$p['filters'][$filter['url_params']]??[],true);}));
 if(count($matching)!==(int)$filter['products']) throw new RuntimeException('Filter facts mismatch');
 $landings[]=ChainSeoContent::landing($filter,$matching);
 $label=$filter['value'].($filter['url_params']==='chain_caliber'?' мм':'');
 $nav[$ids[(int)$filter['category_id']]][$filter['url_params']][]='<a href="/category/'.$ids[(int)$filter['category_id']].'/'.htmlspecialchars($filter['alias'],ENT_QUOTES,'UTF-8').'">'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</a>';
}
foreach($nav as $alias=>$groups) {
 $categories[$alias]['content'].='<h2>Перейти к подходящим исполнениям</h2>';
 foreach($groups as $group=>$links) {
  if($group==='size')continue; // Render active size landings in the separate category block.
  $categories[$alias]['content'].='<p>'.(['chain_pattern'=>'Рисунки цепи','chain_caliber'=>'Номинальные калибры'][$group]).': '.implode(' · ',$links).'.</p>';
 }
}
$pages=[]; $keywords=[];
foreach($products as $p) {
 $page=ChainSeoContent::product($p,$products); $pages[]=$page;
 $sizes=$p['filters']['size']??[]; $pattern=$p['specs']['Рисунок цепи']; $caliber=$p['specs']['Калибр цепи (рабочая/боковая), мм'];
 $terms=$p['group']==='special'?['цепи для спецтехники '.$pattern.' '.$caliber,'цепи для трактора '.$pattern.' '.$caliber,'цепи противоскольжения '.$pattern.' '.$caliber.' купить']:[];
 foreach($sizes as $size) $terms=array_merge($terms,['цепи противоскольжения '.$size,'цепи на погрузчик '.$size,'цепи на колеса '.$size,'купить цепи '.$size.' '.$pattern,'цепи '.$size.' '.$pattern.' '.$caliber]);
 $keywords[]=['url'=>'/product/'.$p['alias'],'intent'=>'конкретный размер и исполнение','queries'=>array_values(array_unique($terms))];
}
$plan=['prepared_on'=>'2026-10-30','attribute_params'=>ChainSeoContent::ATTR_PARAMS,'templates'=>ChainSeoContent::templates(),'categories'=>$categories,'products'=>$pages,'landings'=>$landings];
$allTitles=[]; $allDescriptions=[];
foreach($categories as $row) {$allTitles[]=$row['title'];$allDescriptions[]=$row['description'];}
foreach($pages as $row) {$allTitles[]=$row['rendered']['title'];$allDescriptions[]=$row['rendered']['description'];}
foreach($landings as $row) {$allTitles[]=$row['title'];$allDescriptions[]=$row['description'];}
if(count($allTitles)!==count(array_unique($allTitles)) || count($allDescriptions)!==count(array_unique($allDescriptions))) throw new RuntimeException('Duplicate metadata');
foreach($pages as $p) {
 foreach($p['rendered'] as $field=>$value) if($value==='' || strpos($value,'{chain_')!==false || mb_strlen($value)>255) throw new RuntimeException('Invalid metadata '.$p['article'].' '.$field);
 if(strpos($p['content'],'Производитель цепей')!==false || mb_stripos($p['content'],'ЦЕПЬ-НН')!==false) throw new RuntimeException('Supplier is private');
}
$file=$root.'/app/data/chain-seo-content.json';
file_put_contents($file,json_encode($plan,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
$semantic=['method'=>'Качественный анализ русскоязычной поисковой выдачи и фактического ассортимента. Частотности и позиции не измерялись; данные Wordstat не запрашивались.',
 'sources'=>['https://www.sklad.ru/catalog/category/tsepi_protivoskolzheniya/','https://fork.su/tsepi-protivoskolzheniya/dlya-vilochnykh-pogruzchikov/','https://more-moto.ru/product-category/cepi-na-kolyosa-spectexniki/','https://taned.ru/c/snow-chains/forklift-snow-chains/'],
 'editorial_guidance'=>['https://yandex.ru/support/webmaster/ru/threat/seo-text','https://developers.google.com/search/docs/appearance/title-link','https://developers.google.com/search/docs/appearance/snippet'],
 'clusters'=>[
  ['page'=>'/catalog/cepi-protivoskolzheniya','queries'=>['цепи противоскольжения','цепи на колеса','цепи на шины','цепи противоскольжения купить','цепи противоскольжения под заказ','изготовление цепей противоскольжения','цепи противоскольжения цена']],
  ['page'=>'/category/cepi-dlya-spectehniki','queries'=>['цепи противоскольжения для спецтехники','цепи на колеса трактора','цепи для тракторов','цепи для фронтального погрузчика','цепи для экскаватора погрузчика','цепи для минипогрузчика','лесенка 8/8','лесенка 10/8','лесенка 10/10','сота 10/10']],
  ['page'=>'/category/cepi-dlya-vilochnyh-pogruzchikov','queries'=>['цепи для вилочного погрузчика','цепи на погрузчик','цепи на колеса вилочного погрузчика','цепи противоскольжения для автопогрузчика','зимние цепи на погрузчик','цепи лесенка для погрузчика','цепи сота для погрузчика','цепи зигзаг для погрузчика','комплект цепей на погрузчик','цепи 6 мм для погрузчика','цепи 8 мм для погрузчика']],
 ],'product_queries'=>$keywords,'filter_pages'=>array_map(static function($p){return ['url'=>$p['canonical_url'],'h1'=>$p['seo_h1']];},$landings),
 'excluded'=>'Не добавлены легковые/грузовые цепи, браслеты, шинозащитные цепи, шипованные и высокопрочные серии, неподтверждённые размеры и совместимость с марками техники. Для спецтехники размеры согласуются по заказу. Утверждения о наличии и фиксированной цене не используются.'];
file_put_contents($dir.'/keyword-map.json',json_encode($semantic,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
$terms=[];foreach($semantic['clusters'] as $cluster) $terms=array_merge($terms,$cluster['queries']);foreach($keywords as $p) $terms=array_merge($terms,$p['queries']);
$report=['categories'=>count($categories),'product_templates'=>count($plan['templates']),'products'=>count($pages),'filter_landings'=>count($landings),'unique_titles'=>count(array_unique($allTitles)),'unique_descriptions'=>count(array_unique($allDescriptions)),'mapped_query_variants'=>count(array_unique($terms)),'max_title_chars'=>max(array_map('mb_strlen',$allTitles)),'max_description_chars'=>max(array_map('mb_strlen',$allDescriptions))];
file_put_contents($dir.'/preflight.json',json_encode($report,JSON_PRETTY_PRINT));echo json_encode($report,JSON_PRETTY_PRINT).PHP_EOL;
