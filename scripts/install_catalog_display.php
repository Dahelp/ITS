<?php
if(PHP_SAPI!=='cli')exit(1);
require dirname(__DIR__).'/config/init.php';require CONF.'/db_bootstrap.php';
\app\services\CatalogDisplay::install(\R::getPDO());
echo "Catalog display settings table ready; existing category/product/filter data unchanged\n";
