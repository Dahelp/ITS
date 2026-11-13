<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$file=$root.'/public/css/style.css';$css=file_get_contents($file);
$marker='/* Approved block shadow: exact .desc-prod-inner.bg-light.shadow reference. */';
if(substr_count($css,$marker)!==1)throw new RuntimeException('Unexpected rollback context');
$at=strpos($css,$marker);$tail=substr($css,$at);
if(!str_ends_with(trim($tail),"box-shadow: var(--site-block-shadow) !important;\n}"))throw new RuntimeException('Later styles detected');
$dir=$root.'/storage/approved-panel-shadow';if(!is_dir($dir))mkdir($dir,0775,true);
if(!copy($file,$dir.'/before-rollback-'.date('Ymd-His').'.css'))throw new RuntimeException('Backup failed');
file_put_contents($file,rtrim(substr($css,0,$at))."\n");
echo "Mass shadow override removed; original component shadows restored\n";
