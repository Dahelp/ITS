<?php

$publicDir = __DIR__;
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = realpath($publicDir . $uriPath);

if ($file !== false && str_starts_with($file, realpath($publicDir)) && is_file($file)) {
    return false;
}

$route = trim($uriPath, '/');
$query = $_SERVER['QUERY_STRING'] ?? '';
$_SERVER['QUERY_STRING'] = $route !== ''
    ? $route . ($query !== '' ? '&' . $query : '')
    : $query;

$_SERVER['SCRIPT_FILENAME'] = $publicDir . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require $publicDir . '/index.php';
