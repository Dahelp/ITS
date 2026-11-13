<?php
$root=dirname(__DIR__);$stage=$root.'/outputs/ct08-unified-release';
$paths=['app/controllers/CategoryController.php','app/views/itscenter/Category/view.php','app/views/itscenter/partials/catalog-facets.php','app/views/itscenter/partials/catalog-controls.php','app/services/CatalogSelection.php','public/css/catalog-facets.css','public/js/catalog-facets.js','public/js/catalog-controls.js','app/views/itscenter/admin/CatalogDisplay/index.php'];$manifest=[];
foreach($paths as$path){
 $source=in_array($path,['app/controllers/CategoryController.php','app/views/itscenter/Category/view.php'],true)?$root.'/outputs/ct08-ux-release/files/'.$path:$root.'/'.$path;
 $text=file_get_contents($source);
 if(str_ends_with($path,'CategoryController.php'))$text=str_replace('CatalogSelection::state($_GET)','CatalogSelection::categoryState($_GET)',$text);
 if(str_ends_with($path,'Category/view.php'))$text=str_replace('v=20261112e','v=20261112g',$text);
 $dest=$stage.'/files/'.$path;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0777,true);file_put_contents($dest,$text);
 if(in_array($path,['app/controllers/CategoryController.php','app/views/itscenter/Category/view.php'],true)){
  $local=file_get_contents($root.'/'.$path);
  if(str_ends_with($path,'CategoryController.php'))$local=str_replace('CatalogSelection::state($_GET)','CatalogSelection::categoryState($_GET)',$local);
  else $local=str_replace(['v=20261112e','v=20261112f'],'v=20261112g',$local);
  file_put_contents($root.'/'.$path,$local);
 }
 $original=$path==='app/views/itscenter/admin/CatalogDisplay/index.php'?$root.'/outputs/ct08-unified-live/display-index.php':$root.'/outputs/ct08-unified-live/'.$path;
 $manifest[$path]=['before'=>hash_file('sha256',$original),'after'=>hash('sha256',$text)];
}
file_put_contents($stage.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
$install=str_replace("'/ct08-manifest.json'","'/manifest.json'",file_get_contents(__DIR__.'/install_ct08.php'));$install=str_replace('CT08 published;','CT08 unified controls published;',$install);file_put_contents($stage.'/install.php',$install);
copy(__DIR__.'/verify_ct08_ux.php',$stage.'/verify.php');
echo count($manifest)." files staged: pending UX + unified form and full rows\n";
