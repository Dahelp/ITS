<?php
$root=dirname(__DIR__);$manifest=json_decode(file_get_contents($root.'/outputs/ct08-manifest.json'),true,512,JSON_THROW_ON_ERROR);
foreach($manifest as$path=>&$hash)$hash['after']=hash_file('sha256',$root.'/outputs/ct08-release/'.$path);unset($hash);
file_put_contents($root.'/outputs/ct08-current-manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
$dir=$root.'/outputs/ct08-layout';foreach(['public/css/catalog-facets.css','app/views/itscenter/Category/view.php']as$path){if(!is_dir(dirname($dir.'/layout-files/'.$path)))mkdir(dirname($dir.'/layout-files/'.$path),0777,true);copy($root.'/outputs/ct08-release/'.$path,$dir.'/layout-files/'.$path);}
copy($root.'/outputs/ct08-current-manifest.json',$dir.'/ct08-current-manifest.json');copy(__DIR__.'/finish_ct08.php',$dir.'/finish_ct08.php');
echo "Layout package prepared\n";
