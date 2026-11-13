<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/config/init.php';require CONF.'/db_bootstrap.php';
$dir=ROOT.'/storage/chain-size-block/'.date('Ymd-His');
if(!mkdir($dir,0775,true))throw new RuntimeException('Backup');
$view=ROOT.'/app/views/itscenter/Category/view.php';
$text=file_get_contents($view);
$marker='<?php if ($isFilterLanding && !empty($filterBottomContent)): ?>';
if(!str_contains($text,"partials/category-size-links.php")) {
 if(substr_count($text,$marker)!==1)throw new RuntimeException('View context');
 copy($view,$dir.'/category-view.php');
 $insert="<?php if (!\$isFilterLanding) require APP.'/views/itscenter/partials/category-size-links.php'; ?>\n          ";
 file_put_contents($view,str_replace($marker,$insert.$marker,$text));
}
if(in_array('--files-only',$argv,true))exit;
$before=R::getAll('SELECT id,content FROM category WHERE id IN(44,45)');
$rules=R::getAll("SELECT id,content FROM plagins_inseo WHERE tip='category' AND category_id IN(44,45)");
file_put_contents($dir.'/database.json',json_encode(['categories'=>$before,'inseo'=>$rules],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
$clean=static function($content) {
 $content=preg_replace('~<p>Размеры шин:.*?</p>~su','',$content);
 return preg_replace('~<h2>Подбор по размерам шин</h2><p>.*?</p>~su','<h2>Как проверить размер цепи</h2><p>Выбирайте цепи по полной маркировке установленной шины. Размер 8.15-15 / 28x9-15 в карточках указан как одно исполнение с двумя обозначениями. Доступность рисунков и калибров зависит от выбранного размера.</p>',$content);
};
foreach($before as $row)R::exec('UPDATE category SET content=? WHERE id=?',[$clean($row['content']),$row['id']]);
foreach($rules as $row) {
 $content=preg_replace('~<h2>(?:Вопросы о выборе цепей|Частые вопросы)</h2>((?:<h3>.*?</h3><p>.*?</p>)+)~su','',$row['content']);
 R::exec('UPDATE plagins_inseo SET content=? WHERE id=?',[$clean($content),$row['id']]);
}
echo json_encode(['backup'=>$dir,'categories'=>count($before)],JSON_UNESCAPED_SLASHES).PHP_EOL;
