<?php
$root=dirname(__DIR__);$stage=$root.'/outputs/ct08-settings-release';
$paths=['app/services/CatalogDisplay.php','app/services/CatalogFacets.php','app/controllers/admin/CatalogDisplayController.php','app/views/itscenter/admin/CatalogDisplay/index.php','app/views/itscenter/partials/catalog-facets.php','app/views/itscenter/Category/view.php','app/views/itscenter/layouts/admin.php','app/views/itscenter/admin/Category/edit.php','public/css/catalog-facets.css'];
$old=json_decode(file_get_contents($root.'/outputs/ct08-manifest.json'),true,512,JSON_THROW_ON_ERROR);
$layout=json_decode(file_get_contents($root.'/outputs/ct08-current-manifest.json'),true,512,JSON_THROW_ON_ERROR);
$manifest=[];
foreach($paths as$path){
 $source=$path==='app/views/itscenter/Category/view.php'?$root.'/outputs/ct08-release/'.$path:$root.'/'.$path;
 $live=$root.'/outputs/ct08-settings-live/'.$path;
 if(is_file($live)){
  $text=file_get_contents($live);
  if($path==='app/views/itscenter/layouts/admin.php'){
   $oldLink='<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/category"><i class="fas fa-folder-tree nav-icon"></i><p>Категории</p></a></li>';
   $newLink=$oldLink."\n".'                        <li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/catalog-display"><i class="fas fa-th nav-icon"></i><p>Вывод каталога</p></a></li>';
  }else{
   $oldLink='<h1 class="m-0">Категории</h1>';
   $newLink=$oldLink."\n".'            <a class="btn btn-outline-primary btn-sm mt-2" href="<?=ADMIN?>/catalog-display?id=<?=(int)$category->id?>">Сетка и порядок фильтров</a>';
  }
  if(substr_count($text,$oldLink)!==1)throw new RuntimeException('Live admin anchor mismatch');
  $source=$root.'/outputs/ct08-settings-live-patched/'.$path;if(!is_dir(dirname($source)))mkdir(dirname($source),0777,true);file_put_contents($source,str_replace($oldLink,$newLink,$text));
 }
 if(!is_dir(dirname($stage.'/files/'.$path)))mkdir(dirname($stage.'/files/'.$path),0777,true);copy($source,$stage.'/files/'.$path);
 $before=[];
 if(isset($old[$path]))$before[]=$old[$path]['after'];if(isset($layout[$path]))$before[]=$layout[$path]['after'];
 if($path==='public/css/catalog-facets.css'){
  $text=file_get_contents($source);$text=substr($text,0,strpos($text,'@media(min-width:992px) and (max-width:1399.98px)'));
  $text=str_replace(['clear:both;width:100%;','width:calc(100% / var(--catalog-columns,4));','z-index:2001','z-index:2000'],['','width:33.333333%;','z-index:1052','z-index:1051'],$text);
  $text=str_replace('.catalog-facets{position:sticky;top:20px}.catalog-facets__panel{position:relative;display:flex;flex-direction:column;max-height:calc(100dvh - 40px)}.catalog-facets__body{overflow:auto;overscroll-behavior:contain}.catalog-facets__header,.catalog-facets__footer{flex-shrink:0}', '.catalog-facets__panel{position:relative}',$text);
  if(hash('sha256',$text)!==$old[$path]['after'])throw new RuntimeException('Initial stylesheet reconstruction mismatch');
  $before[]=hash('sha256',str_replace('.catalog-facet-layout{display:grid;','.catalog-facet-layout{clear:both;width:100%;display:grid;',$text));
 }
 if($path==='app/views/itscenter/layouts/admin.php')$before[] = hash_file('sha256',is_file($live)?$live:$root.'/outputs/ct08-settings-original/admin.php');
 if($path==='app/views/itscenter/admin/Category/edit.php')$before[]=hash_file('sha256',is_file($live)?$live:$root.'/outputs/ct08-settings-original/category-edit.php');
 $manifest[$path]=['before'=>array_values(array_unique($before)),'after'=>hash_file('sha256',$source)];
}
file_put_contents($stage.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
copy(__DIR__.'/publish_ct08_settings.php',$stage.'/publish_ct08_settings.php');
echo count($manifest)." files staged; local UI harness excluded\n";
