<?php
if (PHP_SAPI !== 'cli') exit(1);
$root=dirname(__DIR__,2);
$target=getenv('CHECKOUT_ACCOUNT_PACKAGE_DIR') ?: '/tmp/checkout-account-package';
if (file_exists($target)) throw new RuntimeException('Package already exists');
mkdir($target,0700,true);
$manifest=json_decode(file_get_contents($root.'/.github/checkout-account-manifest.json'),true,512,JSON_THROW_ON_ERROR);
foreach($manifest as $path=>$hashes){
    if(!preg_match('~^(app|public)/[A-Za-z0-9_./-]+$~D',$path)||str_contains($path,'..'))throw new RuntimeException('Invalid release path');
    if(hash_file('sha256',$root.'/'.$path)!==$hashes['after'])throw new RuntimeException('Source mismatch: '.$path);
    $dest=$target.'/files/'.$path;
    if(!is_dir(dirname($dest)))mkdir(dirname($dest),0700,true);
    if(!copy($root.'/'.$path,$dest))throw new RuntimeException('Copy failed');
}
if(!copy($root.'/.github/checkout-account-manifest.json',$target.'/manifest.json') ||
   !copy(__DIR__.'/deploy-lead-forms.php',$target.'/install.php') ||
   !copy(__DIR__.'/smoke-checkout-account.php',$target.'/smoke.php'))throw new RuntimeException('Package support copy failed');
if(!copy(__DIR__.'/checkout-company-mail-qa.php',$target.'/company-mail-qa.php'))throw new RuntimeException('QA script copy failed');
echo 'CR-10 bounded package: '.count($manifest)." files\n";
