<?php
require dirname(__DIR__).'/config/init.php';
require CONF.'/db_bootstrap.php';
\app\services\CompleteEngagement::ensureSchema();
echo "Complete reviews, favorites and order attribution are ready\n";
