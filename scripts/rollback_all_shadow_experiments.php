<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$file=$root.'/public/css/style.css';$css=file_get_contents($file);
$legacy='/* Historical product card surface, verified in the 2026-10-28 stylesheet. */';
if(substr_count($css,$legacy)!==1)throw new RuntimeException('Unexpected legacy rule context');
$at=strpos($css,$legacy);$tail=substr($css,$at);
if(!str_ends_with(trim($tail),"box-shadow: var(--surface-card-shadow) !important;\n}"))throw new RuntimeException('Newer styles detected');
$clean=rtrim(substr($css,0,$at))."\n";
// Preserve the FAQ positioning correction; revert its experimental paint and shadow.
$clean=preg_replace_callback('~\.faq-block\.catalog-faq\s*\{([^{}]*)\}~',static function($m){
 $body=preg_replace('~^\s*(?:border|border-radius|background|box-shadow):[^;]+;\s*$~m','',$m[1]);
 return '.faq-block.catalog-faq {'.$body.'}';
},$clean);
foreach(['--surface-card-shadow','--site-block-shadow','--site-panel-shadow'] as $removed)
 if(str_contains($clean,$removed))throw new RuntimeException('Experiment remains: '.$removed);
$dir=$root.'/storage/shadow-experiments-rollback/'.date('Ymd-His');
if(!mkdir($dir,0775,true)||!copy($file,$dir.'/style.css'))throw new RuntimeException('Backup failed');
file_put_contents($file,$clean);
echo json_encode(['backup'=>$dir,'experimental_shadow_variables'=>0,'faq_spacing_preserved'=>true],JSON_UNESCAPED_SLASHES).PHP_EOL;
