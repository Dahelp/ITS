<?php
$root=dirname(__DIR__);
$stage=$root.'/outputs/ct08-release';
function replaceOnce(string $text,string $old,string $new):string {
    if(substr_count($text,$old)!==1)throw new RuntimeException('Patch anchor mismatch: '.substr($old,0,70));
    return str_replace($old,$new,$text);
}
foreach(['local','production']as$target){
    $sources=[
        'app/controllers/CategoryController.php'=>'CategoryController.php',
        'app/widgets/filter/Filter.php'=>'Filter.php',
        'app/services/CatalogSelection.php'=>'CatalogSelection.php',
        'app/views/itscenter/Category/view.php'=>'view.php',
        'app/views/itscenter/partials/catalog-controls.php'=>'catalog-controls.php',
    ];
    foreach($sources as$path=>$original){
        $file=$target==='local'?$root.'/'.$path:$root.'/outputs/ct08-production/'.$original;
        $text=str_replace("\r\n","\n",file_get_contents($file));
        if($original==='CategoryController.php'){
            $start=strpos($text,'    if (!empty($allFilterIds)) {');
            $end=strpos($text,'    // fallback старого режима', $start);
            if($start===false||$end===false)throw new RuntimeException('Controller anchors');
            $text=substr_replace($text,"    \$sqlPart = \\app\\services\\CatalogFacets::predicate(\$allFilterIds);\n\n",$start,$end-$start);
            $text=replaceOnce($text,'if (empty($selectedAttrId) && $legacyFilter) {','if (empty($selectedAttrId) && $legacyFilter && count($allFilterIds) === 1) {');
            $text=replaceOnce($text,"if (!empty(\$selectedAttrId) && \$total < 1 &&", "if (!empty(\$selectedAttrId) && count(\$allFilterIds) === 1 && \$total < 1 &&");
            $text=replaceOnce($text,"\n    \$productWidgetContext = \\app\\widgets\\product\\Product::buildContext(\$products);", "    if (count(\$allFilterIds) > 1 || !empty(\$_GET['filter']) || !empty(\$namedFilterIds)) \$metaRobots = 'noindex, follow';\n\n    \$productWidgetContext = \\app\\widgets\\product\\Product::buildContext(\$products);");
        }elseif($original==='Filter.php'){
            $start=strpos($text,'        $filter = null;',strpos($text,'public static function getFilter'));
            $end=strpos($text,'        return $filter;', $start)+strlen('        return $filter;');
            $text=substr_replace($text,"        \$ids = \\app\\services\\CatalogFacets::ids(\$_GET['filter'] ?? []);\n        return \$ids ? implode(',', \$ids) : null;",$start,$end-$start);
        }elseif($original==='CatalogSelection.php'){
            $text=replaceOnce($text,'-COALESCE(product.reserve,0)','');
        }elseif($original==='catalog-controls.php'){
            $text=replaceOnce($text,'!is_scalar($value))continue;',"(!is_scalar(\$value) && \$key!=='filter'))continue;\n    if(\$key==='filter') \$value=implode(',',\\app\\services\\CatalogFacets::ids(\$value));");
        }else{
            $start=strpos($text,'    <section class="d-md-flex justify-content-between align-items-center pb-4">');
            $end=strpos($text,"    <?php require APP.'/views/itscenter/partials/catalog-controls.php'; ?>",$start);
            if($start===false||$end===false)throw new RuntimeException('View anchors');
            $text=substr_replace($text,"    <link rel=\"stylesheet\" href=\"/css/catalog-facets.css?v=20261112a\">\n    <script src=\"/js/catalog-facets.js?v=20261112a\" defer></script>\n    <div class=\"catalog-facet-layout\">\n      <?php require APP.'/views/itscenter/partials/catalog-facets.php'; ?>\n      <div class=\"catalog-facet-results\">\n",$start,$end-$start);
            $text=replaceOnce($text,"    </div>\n  </div>\n\n  <div class=\"clearfix\"></div>\n\n<?php\n\$jsonProducts", "    </div>\n      </div>\n    </div>\n  </div>\n\n  <div class=\"clearfix\"></div>\n\n<?php\n\$jsonProducts");
            $text=replaceOnce($text,'          <h3>'."\n",'          <p class="alert alert-info">По выбранным условиям товары не найдены. Измените фильтры или сбросьте выбор.</p><h3>'."\n");
        }
        $destination=$target==='local'?$file:$stage.'/'.$path;
        if(!is_dir(dirname($destination)))mkdir(dirname($destination),0777,true);
        file_put_contents($destination,$text);
    }
}
foreach(['app/services/CatalogFacets.php','app/views/itscenter/partials/catalog-facets.php','public/css/catalog-facets.css','public/js/catalog-facets.js']as$path){
    if(!is_dir(dirname($stage.'/'.$path)))mkdir(dirname($stage.'/'.$path),0777,true);
    copy($root.'/'.$path,$stage.'/'.$path);
}
echo "CT08 staged from current production; local changes preserved\n";

