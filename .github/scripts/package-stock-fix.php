<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__,2);$target=getenv('STOCK_PACKAGE_DIR')?:'/tmp/stock-fix-package';
if(file_exists($target))throw new RuntimeException('Package already exists');mkdir($target,0700,true);
$manifest=json_decode(file_get_contents($root.'/.github/stock-fix-manifest.json'),true,512,JSON_THROW_ON_ERROR);
if(array_keys($manifest)!==['app/views/itscenter/Product/view.php','app/models/Cart.php'])throw new RuntimeException('Unexpected stock release files');
foreach($manifest as $path=>$hashes){
 if(hash_file('sha256',$root.'/'.$path)!==$hashes['after'])throw new RuntimeException('Source mismatch: '.$path);
 $dest=$target.'/files/'.$path;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0700,true);
 if(!copy($root.'/'.$path,$dest))throw new RuntimeException('Copy failed');
}
copy($root.'/.github/stock-fix-manifest.json',$target.'/manifest.json');
copy($root.'/.github/scripts/deploy-lead-forms.php',$target.'/install.php');
echo "Free stock package: 2 files, private backups and drift checks\n";