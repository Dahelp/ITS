<?php

$publicDir = __DIR__ . '/public';
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = realpath($publicDir . $uriPath);

$serveStaticFile = static function (string $file): bool {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        'pdf' => 'application/pdf',
        'xml' => 'application/xml; charset=UTF-8',
    ];

    header('Content-Type: ' . ($mimeTypes[$extension] ?? (mime_content_type($file) ?: 'application/octet-stream')));
    header('Content-Length: ' . filesize($file));
    readfile($file);

    return true;
};

if ($file !== false && str_starts_with($file, realpath($publicDir)) && is_file($file)) {
    return $serveStaticFile($file);
}

if ($uriPath !== '/' && str_starts_with($uriPath, '/public/')) {
    $publicPath = substr($uriPath, 7);
    $file = realpath($publicDir . $publicPath);

    if ($file !== false && str_starts_with($file, realpath($publicDir)) && is_file($file)) {
        return $serveStaticFile($file);
    }
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
