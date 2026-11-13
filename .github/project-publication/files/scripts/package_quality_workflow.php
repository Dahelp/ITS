<?php
$root=dirname(__DIR__);$stage=$root.'/outputs/quality-workflow-release';
$installer=str_replace("'/ct08-manifest.json'","'/manifest.json'",file_get_contents($root.'/scripts/install_ct08.php'));file_put_contents($stage.'/install.php',$installer);
$preflight=<<<'CODE'
<?php
$root='/home/s/shinaspec/its-center.ru/public_html';require $root.'/config/init.php';require CONF.'/db_bootstrap.php';require __DIR__.'/files/app/services/CatalogQuality.php';$report=\app\services\CatalogQuality::fromDatabase(\R::getPDO());
if(count($report['items'])<1000)throw new RuntimeException('Unexpected product count');file_put_contents(__DIR__.'/quality-report-summary.json',json_encode($report['summary'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo 'Read-only quality preflight: '.count($report['items'])." products\n";
CODE;
file_put_contents($stage.'/preflight.php',$preflight);$files=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage,FilesystemIterator::SKIP_DOTS))as$f){$p=str_replace('\\','/',substr($f->getPathname(),strlen($stage)+1));$files[$p]=base64_encode(file_get_contents($f->getPathname()));}
$runner=<<<'CODE'
<?php
if(PHP_SAPI!=='cli')exit(1);$package='/home/s/shinaspec/quality-workflow-20261112';if(file_exists($package.'/receipt.json'))throw new RuntimeException('Already applied');if(!is_dir($package))mkdir($package,0700,true);$files=json_decode(base64_decode('__FILES__'),true,512,JSON_THROW_ON_ERROR);foreach($files as$p=>$data){if(str_contains($p,'..')||!preg_match('~^[a-zA-Z0-9_./-]+$~D',$p))throw new RuntimeException('Invalid path');$dest=$package.'/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0700,true);file_put_contents($dest,base64_decode($data,true));}require$package.'/preflight.php';require$package.'/install.php';echo file_get_contents($package.'/quality-report-summary.json');
CODE;
file_put_contents($root.'/outputs/quality-workflow-runner.php',str_replace('__FILES__',base64_encode(json_encode($files)),$runner));echo "Packaged\n";
