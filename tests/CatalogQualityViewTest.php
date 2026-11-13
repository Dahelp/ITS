<?php
require dirname(__DIR__).'/vendor/autoload.php';
function h($s){return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}define('ADMIN','/admin');
$root=dirname(__DIR__);$report=json_decode(file_get_contents($root.'/outputs/catalog-quality-stage2b-20261111.json'),true,512,JSON_THROW_ON_ERROR);
$criteria=array_fill_keys(['q','type','status','source','category','stock','seo'],'');$types=\app\services\CatalogProperties::types();$definitions=\app\services\CatalogProperties::definitions();$items=array_slice($report['items'],0,50);$total=count($report['items']);$page=1;$pages=(int)ceil($total/50);
$original=$items[0]['name'];$items[0]['name']='<script>alert(1)</script>';
ob_start();require$root.'/app/views/itscenter/admin/CatalogProperties/quality.php';$html=ob_get_clean();
if(str_contains($html,'href="?')||!str_contains($html,'href="/admin/catalog-properties/quality?'))throw new RuntimeException('Pagination must ignore admin base href');
if(str_contains($html,'<script>alert(')||!str_contains($html,'&lt;script&gt;'))throw new RuntimeException('Unsafe product text');
$items[0]['name']=$original;
$css=file_get_contents($root.'/public/adminlte/dist/css/adminlte.min.css');
$render=static function($view)use($root,$css,&$report,&$criteria,&$types,&$definitions,&$items,&$total,&$page,&$pages){ob_start();require$root.'/app/views/itscenter/admin/CatalogProperties/'.$view.'.php';$html=ob_get_clean();$html=preg_replace('~<form\b[^>]*>~','<fieldset disabled>',$html);$html=str_replace('</form>','</fieldset>',$html);return '<!doctype html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>'.$css.'body{padding:24px;background:#f4f6f9}main{max-width:1500px;margin:auto}td{min-width:150px}td:first-child{min-width:240px}small{display:block}</style><main><p class="alert alert-warning">Статический предпросмотр. Источник: снимок 11.11.2026 13:57 МСК. Сохранённый позднее пилот в снимок не входит.</p>'.$html.'</main></html>';};
if(!is_dir($root.'/tmp/catalog-quality-ui-preview'))mkdir($root.'/tmp/catalog-quality-ui-preview',0775,true);
foreach(['quality','dictionaries'] as $view){$html=$render($view);file_put_contents($root.'/outputs/catalog-'.$view.'-stage2b-preview.html',$html);file_put_contents($root.'/tmp/catalog-quality-ui-preview/'.$view.'.html',$html);}
echo "Queue and dictionaries render, escape product text, show source boundaries; static previews generated OK\n";
