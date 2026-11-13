<?php
$root=dirname(__DIR__);$stage=$root.'/outputs/ct08-ux-release';
foreach(['local','production']as$target){
 foreach(['app/controllers/CategoryController.php','app/views/itscenter/Category/view.php']as$path){
  $file=$target==='local'?$root.'/'.$path:$root.'/outputs/ct08-ux-live/'.$path;
  $text=file_get_contents($file);
  if(str_ends_with($path,'CategoryController.php')){
   $old="                        \$this->redirectPermanent(\$target);";
   $new=<<<'CODE'
                        $keptQuery = [];
                        foreach ($_GET as $key => $value) {
                            if (preg_match('/^(filter|filter_[a-z0-9_]+|sort|status|in_stock|perpage|page)$/D', (string)$key) && (is_scalar($value) || is_array($value))) $keptQuery[$key] = $value;
                        }
                        if ($keptQuery) $target .= '?'.http_build_query($keptQuery);
                        $this->redirectPermanent($target);
CODE;
   if(substr_count($text,$old)!==1)throw new RuntimeException('Redirect anchor');$text=str_replace($old,$new,$text);
   $anchor="    \$sqlPart = \\app\\services\\CatalogFacets::predicate(\$allFilterIds);";
   $guard=<<<'CODE'
    // A landing for one size/brand must not display an OR-union of that same group.
    if (!empty($selectedAttrId) && !empty($extraFilterIds)) {
        $routeGroups = \app\services\CatalogFacets::groups($allFilterIds);
        if (count($routeGroups[(int)$selectedAttr->attr_group_id] ?? []) > 1) {
            $query = ['filter' => implode(',', $allFilterIds)];
            foreach (['sort','status','in_stock','perpage','page'] as $key) if (isset($_GET[$key]) && is_scalar($_GET[$key])) $query[$key] = $_GET[$key];
            header('Location: '.PATH.'/category/'.rawurlencode((string)$category->alias).'?'.http_build_query($query), true, 302);
            exit;
        }
    }

CODE;
   if(substr_count($text,$anchor)!==1)throw new RuntimeException('Facet anchor');$text=str_replace($anchor,$guard.$anchor,$text);
  }else{
   $text=str_replace('catalog-facets.css?v=20261112d','catalog-facets.css?v=20261112e',$text);
   $text=str_replace('catalog-facets.js?v=20261112a','catalog-facets.js?v=20261112e',$text);
  }
  $dest=$target==='local'?$file:$stage.'/files/'.$path;
  if(!is_dir(dirname($dest)))mkdir(dirname($dest),0777,true);file_put_contents($dest,$text);
 }
}
foreach(['app/views/itscenter/partials/catalog-facets.php','public/js/catalog-facets.js','public/css/catalog-facets.css']as$path){if(!is_dir(dirname($stage.'/files/'.$path)))mkdir(dirname($stage.'/files/'.$path),0777,true);copy($root.'/'.$path,$stage.'/files/'.$path);}
$manifest=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage.'/files',FilesystemIterator::SKIP_DOTS))as$file){$path=str_replace('\\','/',substr($file->getPathname(),strlen($stage.'/files/')));$manifest[$path]=['before'=>hash_file('sha256',$root.'/outputs/ct08-ux-live/'.$path),'after'=>hash_file('sha256',$file->getPathname())];}
file_put_contents($stage.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo count($manifest)." files staged from current production\n";
