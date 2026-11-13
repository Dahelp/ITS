<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$file=$root.'/app/views/itscenter/Main/catalog.php';$html=file_get_contents($file);
$old='<i class="far fa-link" aria-hidden="true"></i> Цепи</a>';
$new='<i class="far fa-link" aria-hidden="true"></i> Цепи противоскольжения</a>';
if(substr_count($html,$old)!==1)throw new RuntimeException('Unexpected link context');
$dir=$root.'/storage/home-chain-link';if(!is_dir($dir))mkdir($dir,0775,true);
if(!copy($file,$dir.'/catalog-'.date('Ymd-His').'.php'))throw new RuntimeException('Backup failed');
file_put_contents($file,str_replace($old,$new,$html));echo "Home chain link renamed\n";
