<?php
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__) . '/config/init.php';
require CONF . '/db_bootstrap.php';
\app\services\StockAdjustment::migrate();
echo "Stock adjustment schema ready\n";
