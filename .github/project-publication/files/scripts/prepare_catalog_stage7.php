<?php
$r=dirname(__DIR__);$stage=$r.'/outputs/stage7-release';$decode=static fn($p)=>json_decode(trim(file_get_contents($p),"\xEF\xBB\xBF \n\r\t"),true,512,JSON_THROW_ON_ERROR);
$baseline=array_merge($decode($r.'/outputs/stage7-live-baseline.json'),$decode($r.'/outputs/stage7-view-baseline.json'));
$paths=['public/js/catalog-facets.js','app/views/itscenter/Category/view.php'];$manifest=[];
foreach($paths as$p){$src=base64_decode($baseline[$p]['content']);
 if(str_ends_with($p,'.js')){
  $old="    if (saved?.open && !desktop.matches) state.open();";
  $new="    if (saved?.open && !desktop.matches) {\n      state.open();\n      root.querySelector('.catalog-facets__body').scrollTop = saved.panelScroll || 0;\n    }";
  if(substr_count($src,$old)!==1)throw new RuntimeException('Scroll restore marker');$src=str_replace($old,$new,$src);
  $old="const saved = {open:current.root.classList.contains('is-open'), groups:";
  $new="const saved = {open:current.root.classList.contains('is-open'), panelScroll:current.root.querySelector('.catalog-facets__body').scrollTop, groups:";
  if(substr_count($src,$old)!==1)throw new RuntimeException('Scroll save marker');$src=str_replace($old,$new,$src);
  $old="'link[rel=\"canonical\"]']";
  $new="'link[rel=\"canonical\"]','meta[property=\"og:title\"]','meta[property=\"og:description\"]','meta[property=\"og:url\"]','meta[property=\"og:image\"]']";
  if(substr_count($src,$old)!==1)throw new RuntimeException('Meta marker');$src=str_replace($old,$new,$src);
  $old="if (next) {if (previous) previous.replaceWith(document.importNode(next,true)); else document.head.append(document.importNode(next,true));}";
  if(substr_count($src,$old)!==1)throw new RuntimeException('Meta removal marker');$src=str_replace($old,$old."\n        else if (previous) previous.remove();",$src);
 }else{
  $src=preg_replace('~(/js/catalog-facets\.js\?v=)[a-zA-Z0-9_-]+~','$1'.'stage7-20261113',$src,1,$n);if($n!==1)throw new RuntimeException('JS version marker');
  $old='foreach (array_slice(array_values($products), 0, 12) as $product)';
  $new="foreach (array_slice(array_values(\$products), 0, ((\$_GET['all_products'] ?? '') === '1' ? count(\$products) : 12)) as \$product)";
  if(substr_count($src,$old)!==1)throw new RuntimeException('Batch marker');$src=str_replace($old,$new,$src);
  $old='if (count($products) > 12):';$new="if (count(\$products) > 12 && (\$_GET['all_products'] ?? '') !== '1'):";
  if(substr_count($src,$old)!==1)throw new RuntimeException('Sentinel marker');$src=str_replace($old,$new,$src);
  $old='aria-hidden="true"></div>';
  $new=<<<'HTML'
aria-hidden="true"></div>
              <?php
              $fullPageQuery = $_GET;
              unset($fullPageQuery['lazy_products']);
              $fullPageQuery['all_products'] = '1';
              // Existing perpage policy keeps this fallback URL out of the index.
              $fullPageQuery['perpage'] = (string)$perpage;
              $fullPageUrl = PATH . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '?' . http_build_query($fullPageQuery);
              ?>
              <noscript><div class="col-12 mb-3"><a class="btn btn-outline-primary" href="<?=h($fullPageUrl)?>">Показать все <?=count($products)?> товаров этой страницы без JavaScript</a></div></noscript>
HTML;
  if(substr_count($src,$old)!==1)throw new RuntimeException('Noscript marker');$src=str_replace($old,$new,$src);
 }
 $dest=$stage.'/files/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0775,true);file_put_contents($dest,$src);$manifest[$p]=['before'=>$baseline[$p]['sha256'],'after'=>hash_file('sha256',$dest)];
}
file_put_contents($stage.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
$install=str_replace("'/ct08-manifest.json'","'/manifest.json'",file_get_contents(__DIR__.'/install_ct08.php'));
// The generic installer lint command must apply to PHP only, not JavaScript.
$install=str_replace(" exec('/usr/local/bin/php8.2 -l '"," if(str_ends_with(\$path,'.php'))exec('/usr/local/bin/php8.2 -l '",$install);
file_put_contents($stage.'/install.php',$install);
$files=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage,FilesystemIterator::SKIP_DOTS))as$f){$p=str_replace('\\','/',substr($f->getPathname(),strlen($stage)+1));$files[$p]=base64_encode(file_get_contents($f->getPathname()));}
$runner=<<<'CODE'
<?php
if(PHP_SAPI!=='cli')exit(1);$package='/home/s/shinaspec/catalog-stage7-20261113';if(file_exists($package.'/receipt.json'))throw new RuntimeException('Already applied');if(!is_dir($package))mkdir($package,0700,true);foreach(json_decode(base64_decode('__FILES__'),true)as$p=>$data){if(str_contains($p,'..'))throw new RuntimeException('Path');$dest=$package.'/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0700,true);file_put_contents($dest,base64_decode($data));}require$package.'/install.php';echo file_get_contents($package.'/receipt.json');
CODE;
file_put_contents($r.'/outputs/stage7-runner.php',str_replace('__FILES__',base64_encode(json_encode($files)),$runner));echo "Stage7 packaged: mobile panel scroll and live meta synchronization\n";
