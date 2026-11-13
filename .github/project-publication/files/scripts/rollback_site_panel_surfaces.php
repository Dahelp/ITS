<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$file=$root.'/public/css/style.css';$css=file_get_contents($file);
$marker='/* Shared panel surface: matches the product detail card. */';
if(substr_count($css,$marker)!==1)throw new RuntimeException('Unexpected rollback context');
$at=strpos($css,$marker);$tail=substr($css,$at);
if(!str_ends_with(trim($tail),'box-shadow: var(--site-panel-shadow);' . "\n}"))throw new RuntimeException('Later styles detected; stop');
$dir=$root.'/storage/site-panel-surfaces';if(!is_dir($dir))mkdir($dir,0775,true);
copy($file,$dir.'/before-rollback-'.date('Ymd-His').'.css');
file_put_contents($file,rtrim(substr($css,0,$at))."\n");echo "Last shared panel overrides removed\n";
