<?php
$root=dirname(__DIR__);$stage=$root.'/outputs/ct08-unified-release';
$paths=['app/views/itscenter/partials/catalog-facets.php','app/views/itscenter/partials/catalog-controls.php','app/services/CatalogSelection.php','public/css/catalog-facets.css','public/js/catalog-facets.js','public/js/catalog-controls.js','app/views/itscenter/admin/CatalogDisplay/index.php'];
$manifest=json_decode(file_get_contents($stage.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
foreach($paths as$path){copy($root.'/'.$path,$stage.'/files/'.$path);$manifest[$path]['after']=hash_file('sha256',$root.'/'.$path);}
file_put_contents($stage.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
$install=str_replace("'/ct08-manifest.json'","'/manifest.json'",file_get_contents(__DIR__.'/install_ct08.php'));
$anchor="\$manifest=json_decode(file_get_contents(\$package.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);";
$patch=<<<'CODE'

// Patch the live card in place, preserving all other production template changes.
$cardPath='app/widgets/product/product_tpl.php';
$card=file_get_contents($root.'/'.$cardPath);$beforeCard=hash('sha256',$card);
$replacements=['$quantity = $productQuantity - $productReserve;'=>'$quantity = max(0, $productQuantity);','$quantity = $qtyMods + $productQuantity - $productReserve;'=>'$quantity = max(0, $qtyMods + $productQuantity);'];
foreach($replacements as$old=>$new){if(substr_count($card,$old)!==1)throw new RuntimeException('Live card stock anchor changed; inspection required');$card=str_replace($old,$new,$card);}
if(!is_dir(dirname($package.'/files/'.$cardPath)))mkdir(dirname($package.'/files/'.$cardPath),0700,true);
file_put_contents($package.'/files/'.$cardPath,$card);
$manifest[$cardPath]=['before'=>$beforeCard,'after'=>hash('sha256',$card)];
file_put_contents($package.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
CODE;
if(substr_count($install,$anchor)!==1)throw new RuntimeException('Installer anchor');
$install=str_replace($anchor,$anchor.$patch,$install);$install=str_replace('CT08 published;','Automatic catalog filters published;',$install);file_put_contents($stage.'/install.php',$install);
copy(__DIR__.'/verify_ct08_ux.php',$stage.'/verify.php');
$files=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage,FilesystemIterator::SKIP_DOTS))as$file){$path=str_replace('\\','/',substr($file->getPathname(),strlen($stage)+1));$files[$path]=base64_encode(file_get_contents($file->getPathname()));}
$payload=base64_encode(json_encode($files,JSON_UNESCAPED_SLASHES));
$runner=<<<'CODE'
<?php
if(PHP_SAPI!=='cli')exit(1);
$package='/home/s/shinaspec/ct08-auto-20261112';
if(file_exists($package.'/receipt.json'))throw new RuntimeException('Release already applied');
if(!is_dir($package))mkdir($package,0700,true);
$files=json_decode(base64_decode('__PAYLOAD__',true),true,512,JSON_THROW_ON_ERROR);
foreach($files as$path=>$encoded){if(!preg_match('~^[a-zA-Z0-9_./-]+$~D',$path)||str_contains($path,'..'))throw new RuntimeException('Invalid package path');$dest=$package.'/'.$path;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0700,true);file_put_contents($dest,base64_decode($encoded,true));}
require $package.'/install.php';require $package.'/verify.php';
CODE;
file_put_contents($root.'/outputs/ct08-auto-publish-runner.php',str_replace('__PAYLOAD__',$payload,$runner));
echo "Automatic filters package ready; one SSH connection carries upload, backup, install and verification\n";
