<?php
$root=dirname(__DIR__); $stage=$root.'/outputs/ct08-release';
foreach([$root.'/app/controllers/CategoryController.php',$stage.'/app/controllers/CategoryController.php']as$file){
 $text=file_get_contents($file);
 $old="                \$sqlPart = \"AND product.id IN (\n                    SELECT product_id\n                    FROM attribute_product\n                    WHERE attr_id = {\$selectedAttrId}\n                )\";";
 if(substr_count($text,$old)!==1)throw new RuntimeException('Fallback SQL anchor');
 file_put_contents($file,str_replace($old,'                // The shared facet predicate above remains authoritative.',$text));
}
$originals=['app/controllers/CategoryController.php'=>'CategoryController.php','app/widgets/filter/Filter.php'=>'Filter.php','app/services/CatalogSelection.php'=>'CatalogSelection.php','app/views/itscenter/Category/view.php'=>'view.php','app/views/itscenter/partials/catalog-controls.php'=>'catalog-controls.php'];
$manifest=[];$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage,FilesystemIterator::SKIP_DOTS));
foreach($it as$file){$path=str_replace('\\','/',substr($file->getPathname(),strlen($stage)+1));$manifest[$path]=['before'=>isset($originals[$path])?hash_file('sha256',$root.'/outputs/ct08-production/'.$originals[$path]):null,'after'=>hash_file('sha256',$file->getPathname())];}
file_put_contents($root.'/outputs/ct08-manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo count($manifest)." files in narrowly scoped CT08 release\n";
