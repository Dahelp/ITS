<?php
$root=dirname(__DIR__);$stage=$root.'/outputs/quality-seo-release';$installer=str_replace("'/ct08-manifest.json'","'/manifest.json'",file_get_contents($root.'/scripts/install_ct08.php'));file_put_contents($stage.'/install.php',$installer);
$preflight=<<<'CODE'
<?php
$root='/home/s/shinaspec/its-center.ru/public_html';
if(hash_file('sha256',$root.'/vendor/ishop/core/App.php')!=='__APP_HASH__')throw new RuntimeException('SEO renderer drift: inspect live App before deployment');
require $root.'/config/init.php';require CONF.'/db_bootstrap.php';require __DIR__.'/files/app/services/CatalogSeoAudit.php';require __DIR__.'/files/app/services/CatalogQuality.php';$report=\app\services\CatalogQuality::fromDatabase(\R::getPDO());if(count($report['items'])<1000)throw new RuntimeException('Unexpected count');file_put_contents(__DIR__.'/summary.json',json_encode($report['summary'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));$samples=[];foreach($report['items'] as$r)if($r['visible']&&$r['quantity']>0&&count($samples)<4)$samples[]=['id'=>$r['id'],'sku'=>$r['sku'],'alias'=>$r['alias'],'seo'=>$r['effective_seo']];file_put_contents(__DIR__.'/samples.json',json_encode($samples,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo 'Read-only preflight: '.count($report['items'])." products\n";
CODE;
file_put_contents($stage.'/preflight.php',str_replace('__APP_HASH__',json_decode(file_get_contents($root.'/outputs/seo-runtime-live.json'),true,512,JSON_THROW_ON_ERROR)['vendor/ishop/core/App.php']['sha256'],$preflight));$files=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage,FilesystemIterator::SKIP_DOTS))as$f){$p=str_replace('\\','/',substr($f->getPathname(),strlen($stage)+1));$files[$p]=base64_encode(file_get_contents($f->getPathname()));}
$runner=<<<'CODE'
<?php
if(PHP_SAPI!=='cli')exit(1);$package='/home/s/shinaspec/quality-seo-20261112';if(file_exists($package.'/receipt.json'))throw new RuntimeException('Already applied');if(!is_dir($package))mkdir($package,0700,true);$files=json_decode(base64_decode('__FILES__'),true,512,JSON_THROW_ON_ERROR);foreach($files as$p=>$data){if(str_contains($p,'..')||!preg_match('~^[a-zA-Z0-9_./-]+$~D',$p))throw new RuntimeException('Invalid path');$dest=$package.'/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0700,true);file_put_contents($dest,base64_decode($data,true));}require$package.'/preflight.php';require$package.'/install.php';echo file_get_contents($package.'/summary.json');echo file_get_contents($package.'/samples.json');
CODE;
file_put_contents($root.'/outputs/quality-seo-runner.php',str_replace('__FILES__',base64_encode(json_encode($files)),$runner));echo "Packaged\n";
