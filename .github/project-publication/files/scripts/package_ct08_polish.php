<?php
$root=dirname(__DIR__);$stage=$root.'/outputs/ct08-polish-release';
$installer=str_replace("'/ct08-manifest.json'","'/manifest.json'",file_get_contents(__DIR__.'/install_ct08.php'));file_put_contents($stage.'/install.php',$installer);
$verify=<<<'CODE'
<?php
$root='/home/s/shinaspec/its-center.ru/public_html';$package=__DIR__;
$all=json_decode(file_get_contents('/home/s/shinaspec/ct08-auto-20261112/current-manifest.json'),true,512,JSON_THROW_ON_ERROR);$latest=json_decode(file_get_contents($package.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);$all=array_replace($all,$latest);
foreach($all as$path=>$hash)if(hash_file('sha256',$root.'/'.$path)!==$hash['after'])throw new RuntimeException('Hash mismatch '.$path);
file_put_contents($package.'/current-manifest.json',json_encode($all,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo "Polish verified: ".count($all)." published file hashes; no database writes\n";
CODE;
file_put_contents($stage.'/verify.php',$verify);
$files=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage,FilesystemIterator::SKIP_DOTS))as$file){$path=str_replace('\\','/',substr($file->getPathname(),strlen($stage)+1));$files[$path]=base64_encode(file_get_contents($file->getPathname()));}
$runner=<<<'CODE'
<?php
if(PHP_SAPI!=='cli')exit(1);
$package='/home/s/shinaspec/ct08-polish-20261112';if(file_exists($package.'/receipt.json'))throw new RuntimeException('Already applied');if(!is_dir($package))mkdir($package,0700,true);
$files=json_decode(base64_decode('__FILES__',true),true,512,JSON_THROW_ON_ERROR);foreach($files as$path=>$data){if(str_contains($path,'..')||!preg_match('~^[a-zA-Z0-9_./-]+$~D',$path))throw new RuntimeException('Invalid path');$dest=$package.'/'.$path;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0700,true);file_put_contents($dest,base64_decode($data,true));}
require $package.'/install.php';require $package.'/verify.php';
CODE;
file_put_contents($root.'/outputs/ct08-polish-runner.php',str_replace('__FILES__',base64_encode(json_encode($files,JSON_UNESCAPED_SLASHES)),$runner));
echo "Polish runner ready\n";
