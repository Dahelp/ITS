<?php
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/app/services/HtmlPageCache.php';
$cache=\app\services\HtmlPageCache::instance();
switch($argv[1]??'') {
    case 'purge': if (!$cache->invalidate()) exit(1);echo "HTML cache purged\n";break;
    case 'disable': $cache->disable();echo "HTML cache disabled\n";break;
    case 'enable': $cache->enable();echo "HTML cache enabled with a new generation\n";break;
    default: fwrite(STDERR,"Usage: php bin/html_cache.php purge|disable|enable\n");exit(1);
}
