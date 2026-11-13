<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CatalogProperties as P;
if(!function_exists('h')){function h($s){return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}}
define('ADMIN','/admin');
$root=dirname(__DIR__);
$pilot=json_decode(file_get_contents($root.'/outputs/catalog-foundation-pilot-stage2a-20261111.json'),true,512,JSON_THROW_ON_ERROR);
$legacy=$pilot['items'][0];$id=$legacy['product_id'];$product=['sku'=>$legacy['sku'],'name'=>$legacy['name']];$brand='IST';
$definitions=P::definitions();$types=P::types();$form=$legacy['proposed'];$form['version']=0;$saved=['version'=>0];$history=[];$csrf='preview-only-not-a-session-token';$error='';$notice='Предпросмотр пилота. Сохранение в этом файле отключено.';$issues=P::normalize($form)['issues'];
// Escaping is tested with deliberately hostile text, not copied to actual product fields.
$form['values']['manufacturer']=['state'=>'known','verification'=>'candidate','value'=>'<script>alert("xss")</script>','source_ref'=>'" onfocus="alert(1)'];
ob_start();require$root.'/app/views/itscenter/admin/CatalogProperties/edit.php';$html=ob_get_clean();
if(str_contains($html,'<script>alert(')||!str_contains($html,'&lt;script&gt;alert('))throw new RuntimeException('View escaping failed');
if(!str_contains($html,'name="_csrf"')||!str_contains($html,'name="version"'))throw new RuntimeException('Missing form protections');
if(!str_contains($html,'33x12-20')||!str_contains($html,'12-16.5'))throw new RuntimeException('Missing size relation');
if(!str_contains($html,'.forEach(')||str_contains($html,'forEac$'))throw new RuntimeException('Invalid UI script');
// Produce a safe, static preview from the actual view. No live form submission possible.
unset($form['values']['manufacturer']);
ob_start();require$root.'/app/views/itscenter/admin/CatalogProperties/edit.php';$html=ob_get_clean();
$html=preg_replace('~<form\b[^>]*>~','<fieldset disabled>',$html);
$html=str_replace('</form>','</fieldset>',$html);
$css=file_get_contents($root.'/public/adminlte/dist/css/adminlte.min.css');
$html='<!doctype html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Пилот характеристик — IT000007251</title><style>'.$css.' body{padding:20px;background:#f4f6f9}main{max-width:1400px;margin:auto}.catalog-property-row td:nth-child(1){min-width:220px}.catalog-property-row td:nth-child(3){min-width:200px}fieldset:disabled{opacity:1}input:disabled,select:disabled{background:#fafbfc;color:#253858}label{font-weight:500!important}</style><main>'.$html.'</main></html>';
file_put_contents($root.'/outputs/catalog-properties-stage2a-preview.html',$html);
if(!is_dir($root.'/tmp/catalog-properties-ui-preview'))mkdir($root.'/tmp/catalog-properties-ui-preview',0775,true);
file_put_contents($root.'/tmp/catalog-properties-ui-preview/index.html',$html);
echo "Editor render: escaped values, source text, CSRF/version controls and both sizes OK; static preview generated\n";
