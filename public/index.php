<?php

$t0 = microtime(true);

register_shutdown_function(function () use ($t0) {
    $t    = microtime(true) - $t0;
    $uri  = ($_SERVER['REQUEST_METHOD'] ?? '-') . ' ' . ($_SERVER['REQUEST_URI'] ?? '-');
    $code = http_response_code();
    $mem  = memory_get_peak_usage(true);

    if ($t > 1.0) {
        error_log(sprintf(
            "[%s] %.3fs %s %s mem=%dKB\n",
            date('Y-m-d H:i:s'),
            $t,
            $code,
            $uri,
            (int) ($mem / 1024)
        ), 3, __DIR__ . '/../tmp/slow.log');
    }

    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        error_log(sprintf(
            "[%s] FATAL %s %s | %s:%d | %s%s",
            date('Y-m-d H:i:s'),
            $uri,
            $code,
            $err['file'],
            $err['line'],
            $err['message'],
            PHP_EOL
        ), 3, __DIR__ . '/../tmp/php_fatal.log');
    }
});

define('BASEPATH', true);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$pathOnly   = parse_url($requestUri, PHP_URL_PATH) ?: '/';
$method     = $_SERVER['REQUEST_METHOD'] ?? 'GET';

require_once dirname(__DIR__) . '/app/services/SessionPolicy.php';
\app\services\SessionPolicy::register($pathOnly, $method);

// Validate token requests before any session or database bootstrap work.
if ($pathOnly === '/session/lead-token') {
    require_once dirname(__DIR__) . '/config/init.php';
    \app\services\LeadTokenEndpoint::handle();
    exit;
}

if (($method === 'GET' || $method === 'HEAD') && preg_match('~[A-Z]~', $pathOnly)) {
    $lowerPath = strtolower($pathOnly);

    if ($lowerPath !== $pathOnly) {
        $query = parse_url($requestUri, PHP_URL_QUERY);
        header('Location: https://' . $_SERVER['SERVER_NAME'] . $lowerPath . ($query ? '?' . $query : ''), true, 301);
        exit();
    }
}

// Canonical cross-number redirects for legacy/incorrect aliases.
// Keep these before session/bootstrap work so duplicate URLs are cheap and
// always return the same permanent redirect, independently of database data.
if ($method === 'GET' || $method === 'HEAD') {
    $crossAliasRedirects = [
        '/cross/4'        => '/cross/4s00484',
        '/cross/24719401' => '/cross/24719401a',
    ];

    // Removed trademark/manufacturer landing pages. Keep the legacy URLs only
    // as permanent redirects to the neutral ATV tyre category.
    $removedTechnicsRedirects = [
        '/technics/kvadrocikl/sf-moto' => '/category/atv',
        '/technics/kvadrocikl-moto-cf-c-forse-400l-eps-x4-eps' => '/category/atv',
        '/technics/kvadrocikl-moto-cf-500-x5-basic' => '/category/atv',
        '/technics/kvadrocikl-moto-cf-500-x5-ho-eps' => '/category/atv',
    ];

    if (isset($removedTechnicsRedirects[$pathOnly])) {
        header('Location: https://' . $_SERVER['SERVER_NAME'] . $removedTechnicsRedirects[$pathOnly], true, 301);
        exit();
    }

    if (isset($crossAliasRedirects[$pathOnly])) {
        header('Location: https://' . $_SERVER['SERVER_NAME'] . $crossAliasRedirects[$pathOnly], true, 301);
        exit();
    }
}
$needSession = \app\services\SessionPolicy::needsSession($pathOnly, $method, $_COOKIE, $_GET);

if ($needSession && session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_GET['debug'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "HIT_ROOT=" . ($_SERVER['REDIRECT_HIT_ROOT'] ?? '-') . PHP_EOL;
    echo "HIT_PUBLIC=" . ($_SERVER['REDIRECT_HIT_PUBLIC'] ?? '-') . PHP_EOL;
    echo "REQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? '-') . PHP_EOL;
    echo "SCRIPT_NAME=" . ($_SERVER['SCRIPT_NAME'] ?? '-') . PHP_EOL;
    echo "PATH_INFO=" . ($_SERVER['PATH_INFO'] ?? '-') . PHP_EOL;
    echo "REQUEST_METHOD=" . $method . PHP_EOL;
    echo "SESSION_NEEDED=" . ($needSession ? 'yes' : 'no') . PHP_EOL;
    echo "SESSION_STATUS=" . session_status() . PHP_EOL;
    echo "SESSION_ID=" . session_id() . PHP_EOL;
    exit;
}

$query     = parse_url($requestUri, PHP_URL_QUERY);
$queryPart = $query ? '?' . $query : '';

if ($pathOnly === '/index.php' || $pathOnly === '/index.html') {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: /' . $queryPart);
    exit();
}

if ($pathOnly === '/main') {
    header('Location: /', true, 301);
    exit();
}

if (strpos($requestUri, '/public') !== false) {
    $public = str_replace('/public', '', $requestUri);

    if ($public) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: https://' . $_SERVER['SERVER_NAME'] . $public);
        exit();
    }
}

$uri = preg_replace("/\?.*/i", '', $requestUri);

if ((strpos($uri, 'simpla') === false) && (strlen($uri) > 1)) {
    if (rtrim($uri, '/') !== $uri) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: https://' . $_SERVER['SERVER_NAME'] . str_replace($uri, rtrim($uri, '/'), $requestUri));
        exit();
    }
}

// =========================================================
// Legacy filter URL redirects
// Старые URL фильтров без категории:
// /brand/herade, /size/10-16-5, /rim/12-inches
// Рабочий формат фильтров: /category/{category_alias}/{filter_alias}
// =========================================================

$legacyPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
$legacyPath = '/' . trim($legacyPath, '/');
$legacyPath = preg_replace('~/+~', '/', $legacyPath);

// Исключения для конкретных старых URL, если понадобится точный редирект
$legacyRedirectExceptions = [
    // '/brand/ist' => '/category/shiny-dlya-vilochnyh-pogruzchikov',
    // '/brand/ekka' => '/category/shiny',
];

// Сначала точные исключения
if (isset($legacyRedirectExceptions[$legacyPath])) {
    header('Location: https://' . $_SERVER['SERVER_NAME'] . $legacyRedirectExceptions[$legacyPath], true, 301);
    exit();
}

// Общие правила для старых URL фильтров
$legacyFilterRedirects = [
/*
    'brand'        => '/category/shiny',
    'manufacturer' => '/category/shiny',
    'size'         => '/category/shiny',
    'rim'          => '/category/diski',
    'filter'       => '/category/shiny',
*/
];

foreach ($legacyFilterRedirects as $prefix => $targetUrl) {
    if (preg_match('~^/' . preg_quote($prefix, '~') . '/([a-z0-9\-_\.]+)$~iu', $legacyPath, $matches)) {
        $filterAlias = $matches[1];

        header('Location: https://' . $_SERVER['SERVER_NAME'] . $targetUrl . '/' . $filterAlias, true, 301);
        exit();
    }
}


require_once dirname(__DIR__) . '/app/services/HtmlPageCache.php';
$htmlPageCache = \app\services\HtmlPageCache::instance();
// Covers admin modules using PDO directly as well as stateful legacy GET actions.
if ((str_starts_with($pathOnly,'/admin/') && ($method==='POST' || preg_match('~/(?:add|edit|delete|save|update|import|copy|clear|refresh|change|apply|sync|set)[a-z-]*(?:/|$)~i',$pathOnly)))
    || preg_match('~^/cron/(?:refresh|import|sync|update)~i',$pathOnly)) {
    $htmlPageCache->beginMutation();
}
try {
    if ($htmlPageCache->handle($_SERVER,$_COOKIE)) exit;
} catch (\Throwable $cacheError) {
    header('X-Page-Cache: BYPASS');
    error_log('HTML page cache bypassed: '.$cacheError->getMessage());
}

require_once dirname(__DIR__) . '/config/init.php';

if (preg_match('~^/([a-z0-9\-_]+)/([a-z0-9\.\-_]+)$~iu', $legacyPath, $matches)) {
    \ishop\Db::instance();

    $target = (new \app\services\filters\LegacyFilterRedirectService())->resolve(
        (string)$matches[1],
        rawurldecode((string)$matches[2])
    );

    if (!empty($target)) {
        header('Location: ' . $target, true, 301);
        exit();
    }
}

require_once LIBS . '/functions.php';
require_once CONF . '/routes.php';

new \ishop\App();
