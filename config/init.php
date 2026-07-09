<?php

define("DEBUG", 0);
define("ROOT", dirname(__DIR__));
define("WWW", ROOT . '/public');
define("APP", ROOT . '/app');
define("CORE", ROOT . '/vendor/ishop/core');
define("LIBS", ROOT . '/vendor/ishop/core/libs');
define("CACHE", ROOT . '/tmp/cache');
define("CONF", ROOT . '/config');
define("LAYOUT", 'watches');
define("TEMPLATE", 'itscenter');

$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'its-center.ru';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : ($_SERVER['REQUEST_SCHEME'] ?? 'http');
$site = $scheme . '://' . $host;
$phpSelf = $_SERVER['PHP_SELF'] ?? '/index.php';
$app_path = $site . $phpSelf;

$app_path = preg_replace("#[^/]+$#", '', $app_path);
$app_path = str_replace('/public/', '', $app_path);
$app_path = rtrim($app_path, '/');
if (
    PHP_SAPI === 'cli'
    || strpos($phpSelf, '/home/') !== false
    || strpos($phpSelf, 'public_html') !== false
    || strpos($phpSelf, '\\') !== false
) {
    $app_path = $site;
}
define("PATH", $app_path);

// Новый define: SITE без путей
define("SITE", $site);

define("ADMIN", PATH . '/admin');
require_once ROOT . '/vendor/autoload.php';
