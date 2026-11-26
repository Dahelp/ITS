<?php
if(PHP_SAPI!=='cli')exit(1);
$_SERVER['HTTP_HOST']='its-center.ru';$_SERVER['HTTPS']='on';$_SERVER['PHP_SELF']='/index.php';
require dirname(__DIR__).'/config/init.php';require ROOT.'/config/db_bootstrap.php';
define('SITEMAP_CLI',true);
$completed=false;register_shutdown_function(static function()use(&$completed){if(!$completed)exit(1);});
require APP.'/views/itscenter/Cron/sitemap.php';

$xml=simplexml_load_file(WWW.'/cron/sitemap.xml');if(!$xml||count($xml->url)<100)throw new RuntimeException('Invalid sitemap output');$completed=true;
