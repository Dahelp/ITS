<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);
$candidates=glob($root.'/storage/catalog-faq-spacing/*/style.css');sort($candidates);
if(!$candidates)throw new RuntimeException('Pre-shadow backup missing');
$source=$candidates[0];$css=file_get_contents($source);
foreach(['--surface-card-shadow','--site-block-shadow','--site-panel-shadow','Catalog FAQ follows the spacing'] as $marker)
 if(str_contains($css,$marker))throw new RuntimeException('Wrong baseline');
$target=$root.'/public/css/style.css';
$dir=$root.'/storage/full-style-restore/'.date('Ymd-His');
if(!mkdir($dir,0775,true)||!copy($target,$dir.'/before.css'))throw new RuntimeException('Backup failed');
if(!copy($source,$target)||hash_file('sha256',$source)!==hash_file('sha256',$target))throw new RuntimeException('Restore verification failed');
echo json_encode(['restored_from'=>$source,'sha256'=>hash_file('sha256',$target),'exact_copy'=>true],JSON_UNESCAPED_SLASHES).PHP_EOL;
