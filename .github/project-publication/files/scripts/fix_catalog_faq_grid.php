<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$file=$root.'/app/views/itscenter/Catalog/index.php';
$text=str_replace("\r\n","\n",file_get_contents($file));
$old="    </div>\n</div>\n<?php if (!empty(\$categoryFaqRows)): ?>\n<section class=\"container faq-block mb-4\">";
$new="<?php if (!empty(\$categoryFaqRows)): ?>\n<section class=\"faq-block mb-4\">";
if(substr_count($text,$old)!==1)throw new RuntimeException('Unexpected FAQ position');
$text=str_replace($old,$new,$text);
$end="</section>\n<?php endif; ?>\n<!--product-end-->";
if(substr_count($text,$end)!==1)throw new RuntimeException('Unexpected end');
$text=str_replace($end,"</section>\n<?php endif; ?>\n    </div>\n</div>\n<!--product-end-->",$text);
$dir=$root.'/storage/chain-menu-faq';if(!is_dir($dir))mkdir($dir,0775,true);
if(!copy($file,$dir.'/catalog-grid-'.date('Ymd-His').'.php'))throw new RuntimeException('Backup failed');
file_put_contents($file,$text);echo "FAQ aligned inside catalog container\n";
