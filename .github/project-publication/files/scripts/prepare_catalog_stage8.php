<?php
$r=dirname(__DIR__);$stage=$r.'/outputs/stage8-release';$raw=is_file($r.'/outputs/stage8-live-baseline.json')?trim(file_get_contents($r.'/outputs/stage8-live-baseline.json'),"\xEF\xBB\xBF \r\n\t"):'';$baseline=$raw!==''?json_decode($raw,true,512,JSON_THROW_ON_ERROR):[];
$draftBaseline=is_file($r.'/outputs/stage8-local-baseline.json')?json_decode(file_get_contents($r.'/outputs/stage8-local-baseline.json'),true,512,JSON_THROW_ON_ERROR):[];
$paths=['app/services/CatalogWorkflow.php','app/controllers/admin/CatalogWorkflowController.php'];foreach(['CatalogWorkflow/index.php','CatalogWorkflow/product.php','CatalogWorkflow/history.php','CatalogWorkflow/landings.php','partials/catalog-workflow-nav.php','partials/catalog-workflow-history.php']as$p)$paths[]='app/views/itscenter/admin/'.$p;
$existing=['app/views/itscenter/layouts/admin.php','app/services/CatalogQuality.php'];foreach(['CatalogProperties/index.php','CatalogProperties/edit.php','CatalogProperties/quality.php','CatalogProperties/reviews.php','CatalogProperties/dictionaries.php','CatalogProperties/dictionary-edit.php','CatalogProperties/migrations.php','CatalogNames/index.php','CatalogFacetBindings/index.php','CatalogDisplay/index.php','Product/edit.php','Plagins/yandex-tovars.php']as$p)$existing[]='app/views/itscenter/admin/'.$p;
$manifest=[];foreach(array_merge($paths,$existing)as$p){
 $new=in_array($p,$paths,true);$src=$new?file_get_contents($r.'/'.$p):(isset($baseline[$p])?base64_decode($baseline[$p]['content']):(isset($draftBaseline[$p])?base64_decode($draftBaseline[$p]['content']):file_get_contents($r.'/'.$p)));
 if(!$new){
  if(str_ends_with($p,'/layouts/admin.php')){
   $marker='<li class="nav-header">Каталог и склад</li>';
   $insert=<<<'HTML'
<li class="nav-header">Каталог и склад</li>
                <?php if(($_SESSION['user']['role']??'')==='admin'): ?>
                <li class="nav-item"><a class="nav-link" href="<?=ADMIN?>/catalog-workflow"><i class="nav-icon fas fa-tasks"></i><p>Рабочий центр каталога</p></a></li>
                <?php endif ?>
HTML;
   if(substr_count($src,$marker)!==1)throw new RuntimeException('Sidebar marker');$src=str_replace($marker,$insert,$src);
  }elseif(str_ends_with($p,'/CatalogQuality.php')){
   $marker="            if((\$criteria['seo']??'')==='missing'&&!\$r['seo_gaps'])return false;";
   if(substr_count($src,$marker)!==1)throw new RuntimeException('Quality filter marker');
   $src=str_replace($marker,$marker."\n            if((\$criteria['seo']??'')==='effective'&&empty(\$r['effective_seo']['issues']))return false;",$src);
   $marker="\$criteria['seo']!=='missing'&&!in_array";
   if(substr_count($src,$marker)!==1)throw new RuntimeException('Quality exclusion marker');$src=str_replace($marker,"\$criteria['seo']!=='missing'&&\$criteria['seo']!=='effective'&&!in_array",$src);
  }else{
   $src="<?php require (defined('APP')?APP:dirname(__DIR__,4)).'/views/itscenter/admin/partials/catalog-workflow-nav.php'; ?>\n".$src;
   if(str_ends_with($p,'CatalogProperties/index.php')){
    $src=str_replace('Пилотные характеристики','Характеристики и размеры',$src);
    $marker='<a href="<?=ADMIN?>/catalog-properties/edit?id=<?=(int)$p[\'id\']?>">Характеристики и размеры</a>';
    if(substr_count($src,$marker)!==1)throw new RuntimeException('Product list action marker');
    $src=str_replace($marker,'<a href="<?=ADMIN?>/catalog-workflow/product?id=<?=(int)$p[\'id\']?>">Рабочая карточка</a> · '.$marker,$src);
   }
   if(str_ends_with($p,'CatalogProperties/edit.php'))$src=str_replace('История пилотных изменений','История изменений характеристик',$src);
   if(str_ends_with($p,'Plagins/yandex-tovars.php')){
    $marker='href="?preview=1"';if(substr_count($src,$marker)!==1)throw new RuntimeException('Feed preview link marker');$src=str_replace($marker,'href="<?=ADMIN?>/plagins/yandex-tovars?preview=1"',$src);
    $src=str_replace('/catalog-properties/edit?id=','/catalog-workflow/product?id=',$src);
   }
   if(str_ends_with($p,'CatalogProperties/reviews.php'))$src=str_replace('/product/edit?id=','/catalog-workflow/product?id=',str_replace('Карточка товара</a>','Рабочая карточка</a>',$src));
   if(str_ends_with($p,'CatalogProperties/quality.php')){
    $marker="'SEO и содержимое карточки',['effective_title'";
    if(substr_count($src,$marker)!==1)throw new RuntimeException('Quality select marker');$src=str_replace($marker,"'SEO и содержимое карточки',['effective'=>'Все итоговые SEO-замечания','effective_title'",$src);
    $marker='href="<?=ADMIN?>/catalog-properties/edit?id=<?=$r[\'id\']?>">';
    if(substr_count($src,$marker)!==1)throw new RuntimeException('Quality product link marker');$src=str_replace($marker,'href="<?=ADMIN?>/catalog-workflow/product?id=<?=$r[\'id\']?>">',$src);
   }
  }
 }
 $dest=$stage.'/files/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0775,true);file_put_contents($dest,$src);$manifest[$p]=['before'=>$new?null:($baseline[$p]['sha256']??null),'after'=>hash_file('sha256',$dest)];
}
if(!$baseline){echo "Prepared local draft; no live baseline yet\n";exit;}
file_put_contents($stage.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));$installer=str_replace("'/ct08-manifest.json'","'/manifest.json'",file_get_contents(__DIR__.'/install_ct08.php'));file_put_contents($stage.'/install.php',$installer);
copy(__DIR__.'/check_catalog_stage8.php',$stage.'/check.php');
$files=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage,FilesystemIterator::SKIP_DOTS))as$f){$p=str_replace('\\','/',substr($f->getPathname(),strlen($stage)+1));$files[$p]=base64_encode(file_get_contents($f->getPathname()));}
$runner=<<<'CODE'
<?php
if(PHP_SAPI!=='cli')exit(1);$package='/home/s/shinaspec/catalog-stage8-20261113';if(file_exists($package.'/receipt.json'))throw new RuntimeException('Already applied');if(!is_dir($package))mkdir($package,0700,true);foreach(json_decode(base64_decode('__FILES__'),true)as$p=>$data){if(str_contains($p,'..'))throw new RuntimeException('Path');$dest=$package.'/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0700,true);file_put_contents($dest,base64_decode($data));}passthru('/usr/local/bin/php8.2 '.escapeshellarg($package.'/check.php').' before',$code);if($code)throw new RuntimeException('Preflight failed');require$package.'/install.php';passthru('/usr/local/bin/php8.2 '.escapeshellarg($package.'/check.php').' after',$code);if($code)throw new RuntimeException('Verification failed');
CODE;
file_put_contents($r.'/outputs/stage8-runner.php',str_replace('__FILES__',base64_encode(json_encode($files)),$runner));echo 'Packaged '.count($manifest)." workflow files\n";
