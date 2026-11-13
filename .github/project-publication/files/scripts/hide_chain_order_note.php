<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$file=$root.'/app/views/itscenter/Product/view.php';
$text=file_get_contents($file);
$old="if (!empty(\$product->note))";
$new="if (!empty(\$product->note) && !\\app\\services\\TsepChainCatalog::isChain(\$product))";
if(substr_count($text,$old)!==2)throw new RuntimeException('Unexpected note context');
$backup=$root.'/storage/chain-order-labels/product-note-'.date('Ymd-His').'.php';
if(!copy($file,$backup))throw new RuntimeException('Backup failed');
file_put_contents($file,str_replace($old,$new,$text));
echo $backup.PHP_EOL;
