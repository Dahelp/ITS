<?php
if(PHP_SAPI!=='cli')exit(1);
$root='/home/s/shinaspec/its-center.ru/public_html';$package=__DIR__;
$manifest=json_decode(file_get_contents($package.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
foreach($manifest as$path=>$hash){
 if(!preg_match('~^(app|public)/[a-zA-Z0-9_./-]+$~D',$path)||str_contains($path,'..'))throw new RuntimeException('Invalid path');
 if(hash_file('sha256',$package.'/files/'.$path)!==$hash['after'])throw new RuntimeException('Package mismatch '.$path);
 if($hash['before']?(!is_file($root.'/'.$path)||!in_array(hash_file('sha256',$root.'/'.$path),$hash['before'],true)):file_exists($root.'/'.$path))throw new RuntimeException('Production drift '.$path);
 if(str_ends_with($path,'.php')){exec('/usr/local/bin/php8.2 -l '.escapeshellarg($package.'/files/'.$path).' 2>&1',$out,$status);if($status!==0)throw new RuntimeException('Lint failure '.$path);}
}
if(file_exists($package.'/receipt.json'))throw new RuntimeException('Already applied');
require $root.'/config/init.php';require CONF.'/db_bootstrap.php';$db=R::getPDO();
$existing=$db->query("SHOW TABLES LIKE 'catalog_category_display'")->fetchColumn();
if($existing)throw new RuntimeException('Settings table already exists; inspect current settings before applying');
foreach($manifest as$path=>$hash)if(is_file($root.'/'.$path)){$backup=$package.'/backup/'.$path;if(!is_dir(dirname($backup)))mkdir(dirname($backup),0700,true);if(!copy($root.'/'.$path,$backup))throw new RuntimeException('Backup failure');}
// Install only a new settings table. Existing facts, categories and filter links stay intact.
require $package.'/files/app/services/CatalogDisplay.php';
\app\services\CatalogDisplay::install($db);
$written=[];
try{
 foreach($manifest as$path=>$hash){$dest=$root.'/'.$path;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0755,true);if(!copy($package.'/files/'.$path,$dest.'.ct08-new')||!rename($dest.'.ct08-new',$dest))throw new RuntimeException('Write failure '.$path);$written[]=$path;}
 foreach($manifest as$path=>$hash)if(hash_file('sha256',$root.'/'.$path)!==$hash['after'])throw new RuntimeException('Verification failure');
 file_put_contents($package.'/receipt.json',json_encode(['applied_at'=>date(DATE_ATOM),'files'=>$manifest,'new_table'=>'catalog_category_display','settings_rows'=>0,'legacy_data_writes'=>false],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo "Catalog settings published; SHA256 and empty settings table verified\n";
}catch(Throwable$e){foreach(array_reverse($written)as$path){if(!$manifest[$path]['before'])unlink($root.'/'.$path);else copy($package.'/backup/'.$path,$root.'/'.$path);}throw$e;}
